<?PHP

class sampleFilesController extends controller
{

    private $fileDir =  ROOT . DS . 'app' . DS . 'private' .DS . 'sampleFiles';

    public function visibleProjectFiles($projectId)
    {
        $this->render = false; 

        $samples = upa('samples', 'fetchSamplesInProject', array($projectId), False);

        $sampleIds = array_column($samples, 'id');

        $sql = "SELECT samplefiles.*, samples.barcode FROM samplefiles LEFT JOIN samples ON samples.id = samplefiles.sample_id  WHERE sample_id IN (" . implode(',', $sampleIds) . ") AND visible_for_client = 1 ORDER BY samples.barcode ASC";
        
        $files = $this->SampleFile->customQuery($sql, []);                

        return $files; 
        
    }

    public function visibleProjectFilesInReport($projectId, $pertainsToSamples )
    {
        $this->render = false; 

        $samples = upa('samples', 'fetchSamplesInProject', array($projectId), False);

        $sampleIds = array_column($samples, 'id');

        //retain only the samples that are in the report scope
        $sampleIds = array_intersect($sampleIds, $pertainsToSamples);

        if(count($sampleIds) == 0)
        {
            return [];
        }

        $sql = "SELECT samplefiles.*, samples.barcode FROM samplefiles LEFT JOIN samples ON samples.id = samplefiles.sample_id  WHERE sample_id IN (" . implode(',', $sampleIds) . ") AND visible_for_client = 1 ORDER BY samples.barcode ASC";        

        $files = $this->SampleFile->customQuery($sql, []);

        return $files;

    }

    public function hashesToFiles($hashes)
    {
        $this->render = False; 

        $sql = "SELECT * FROM samplefiles WHERE hash_name IN ('" . implode("','", $hashes) . "')";
    
        $files = $this->SampleFile->customQuery($sql, []);

        return $files; 
    }


    public function hashArrayToNames($hashes)
    {

        $this->render = False; 

        $sql = "SELECT original_name FROM samplefiles WHERE hash_name IN ('" . implode("','", $hashes) . "')";
    
        $files = $this->SampleFile->customQuery($sql, []);

        return array_column($files, 'original_name');

    }

    public function groupSampleHashesBySample($hashes)
    {

        $this->render = False; 

        $sql = "SELECT sample_id, hash_name FROM samplefiles WHERE hash_name IN ('" . implode("','", $hashes) . "')";
    
        $files = $this->SampleFile->customQuery($sql, []);

        $grouped = [];

        foreach($files as $file)
        {
            $grouped[$file['sample_id']][] = $file['hash_name'];
        }

        return $grouped;

    }

    public function visibleSampleFiles($sampleId)
    {
        $this->render = False; 

        $this->SampleFile->where('sample_id', $sampleId);
        
        return  $this->SampleFile->search();        
    }


    public function setAsPreviouslySent($hashes)
    {   
        $this->render = False; 

        foreach($hashes as $hash)
        {

            $this->SampleFile->where('hash_name', $hash); 

            $file = $this->SampleFile->first();

            if($file)
            {
                $this->SampleFile->deepFreed();                
                $this->SampleFile->id = $file['id'];
                $this->SampleFile->sent_to_client = 1;
                $this->SampleFile->save();
                $this->SampleFile->deepFreed();
            }

        }

    }

    public function toggleClientVis($fileId, $visible)
    {

      

        $visible_bool = filter_var($visible, FILTER_VALIDATE_BOOLEAN);

        $this->render = false; 

        $sampleFile = ( new SampleFile() )->find($fileId);

      
   
        if($sampleFile)
        {

            $sample = ( new Sample() )->find($sampleFile['sample_id']);
        
            $event = 'Bestand zichtbaarheid gewijzigd voor: ' . $sampleFile['original_name']; 

            $this->SampleFile->deepFreed();
            $this->SampleFile->id =  $sampleFile['id'];
            $this->SampleFile->visible_for_client = $visible_bool; 
            $this->SampleFile->save();          

            $from = ($sampleFile['visible_for_client'] == 1) ? 'Zichtbaar' : 'Niet zichtbaar';
            $to =  ($visible_bool == 1) ? 'Zichtbaar' : 'Niet zichtbaar';

            //now visible, upload to portal 
            //we can do thsi regardless of it having a portal-id, because it is stored with the mesa_id 
            //on the portal side! 
            if($visible_bool)
            {
                $fileData = file_get_contents($this->fileDir . DS . $sampleFile['hash_name']);

                $contentHash = md5($fileData);              

                upa('portal', 'storeSampleFileInPortal', [
                    base64_encode($fileData),
                    $sampleFile['hash_name'], 
                    $contentHash,  
                    $sampleFile['client_id'],
                    $sampleFile['sample_id'],  
                    $sampleFile['original_name']   
                ], False);
            }

            //now invisible, drop from portal 
            else
            {
                upa('portal', 'dropSampleFile', [
                    $sampleFile['sample_id'],
                    $sampleFile['hash_name']
                ], False);
    
            }
           

            upa('changeTracker', 'changed', array(27,False, $sample['id'], False, $event, $from, $to), False);
         

        }

    }


    

    public function storeFile()
    {

        $this->render = false; 

        //find corresponding sample 
        $sample = ( new Sample() )->find($_POST['sampleId']);

        $photoMode = isset($_POST['photoMode']) ? filter_var($_POST['photoMode'], FILTER_VALIDATE_BOOLEAN) : False;
        
        if(!$sample)
        {
            return; 
        }

        $clientId = $sample['client'];
        $projectId = $sample['project'];

        //get original file name
        $fileName = $_FILES['file']['name'];
        
        //get file extension
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        //if photo mode, rename file to barcode
        if($photoMode)
        {
            $fileName = $this->getFollowNumberForSample($sample['id'], $sample['barcode']) . '.' . $ext;
        }                
        
        
        //create unique hash for file
        $hash = md5($fileName . time());

        //move file to private directory
        if (!@move_uploaded_file($_FILES['file']['tmp_name'], $this->fileDir . DS . $hash)) {
            //throw an exception
            throw new Exception('Failed to move uploaded file.');
        }        

        //create new sampleFile object
        $sampleFile = new SampleFile();
        $sampleFile->sample_id = $_POST['sampleId'];
        $sampleFile->client_id = $clientId; 
        $sampleFile->hash_name = $hash;
        $sampleFile->original_name = $fileName;                
        $sampleFile->visible_for_client = False;        
        $sampleFile->save();

        $event = 'Bestand geupload';
        upa('changeTracker', 'changed', array(25, False, $sample['id'], False, $event, '', $fileName), False);

        

        if(isset($_POST['instantVis']) && $_POST['instantVis'] == 'true')
        {            
            $this->toggleClientVis($sampleFile->lastInsertId, True);
        }


    }

    private function getFollowNumberForSample($sampleId, $barcode)
    {
        $sampleFile = new SampleFile();
        $sampleFile->where('sample_id', $sampleId);
        $results = $sampleFile->search();

        $currentFileNames = array_column($results, 'original_name');

        //for each filename in the array, remove the extension
        $currentFileNames = array_map(function($fileName) {
            return pathinfo($fileName, PATHINFO_FILENAME);
        }, $currentFileNames);
        
        $currentNumber  = 1; 

        //check if the file name $barcode_1 is already set, if so, increment the number, until we fin da "free" number
        while(in_array($barcode . '_' . $currentNumber, $currentFileNames))
        {
            $currentNumber++;
        }

        return $barcode . '_' . $currentNumber;
      

    }

    public function dropFile($fileId)
    {

        $this->render = false; 

        $sampleFile = ( new SampleFile() )->find($fileId);
   
        if($sampleFile)
        {


            if(file_exists($this->fileDir . DS . $sampleFile['hash_name']))
            {
                unlink($this->fileDir . DS . $sampleFile['hash_name']);
            }

            $sample = ( new Sample() )->find($sampleFile['sample_id']);
            
            $this->SampleFile->deepFreed();
            $this->SampleFile->id =  $sampleFile['id'];
            $this->SampleFile->delete();

            upa('portal', 'dropSampleFile', [
                $sampleFile['sample_id'],
                $sampleFile['hash_name']
            ], False);


            $event = 'Bestand verwijderd';
            upa('changeTracker', 'changed', array(26, False, $sample['id'], False, $event, '', $sampleFile['original_name']), False);

        }

    }

    public function listFiles($sampleId, $hideDelete = False, $return = False )
    {

        $partOfAuth = upa('samples', 'partOfAuthProject', array($sampleId));

        $this->render = false; 

        $this->SampleFile->where('sample_id', $sampleId);
        $files = $this->SampleFile->search();

        $table =   "<table class='table table-striped'>"
                        . "<thead>"
                            . "<tr>"
                                . "<th>Bestands naam</th>"
                                . "<th>Klant zichtbaar</th>"
                                . "<th>Acties</th>"
                                . "<th></th>"
                            . "</tr>"
                        . "</thead>"
                        . "<tbody>"
                            . $this->listFilesRows($files, $hideDelete)
                        . "</tbody>"
                    . "</table>";

        if($return == True)
        {
            return json_encode([
                'table' => $table,
                'nfiles' => ( count($files) ) ? count($files) : ''
            ]);
        }

        print json_encode([
            'table' => $table,
            'nfiles' => ( count($files) ) ? count($files) : ''
        ]);
        
    }

    public function downloadFile($fileId)
    {

        $this->render = false; 

        $sampleFile = ( new SampleFile() )->find($fileId);
   
        if($sampleFile)
        {

            if(file_exists($this->fileDir . DS . $sampleFile['hash_name']))
            {
                $this->download($this->fileDir . DS . $sampleFile['hash_name'], $sampleFile['original_name']);
            }
            
        }

    }

    private function download($file, $fileName)
    {
        $this->render = false; 
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header("Content-Length: " . filesize($file));
        readfile($file);
        exit;
    
    }

    public function showFile($fileId)
    {

        $this->render = false; 

        $sampleFile = ( new SampleFile() )->find($fileId);
   
        if($sampleFile)
        {

            if(file_exists($this->fileDir . DS . $sampleFile['hash_name']))
            {
                $this->show($this->fileDir . DS . $sampleFile['hash_name'], $sampleFile['original_name']);
            }
            
        }
        
    }

    public function show($file, $fileName)
    {
        $this->render = false; 

        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);
                
        if ($mime === FALSE) 
        {
            $mime = 'application/octet-stream'; // Default MIME type
        }

        header('Content-Description: File Transfer');    
        header('Content-Type: ' . $mime);            
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header("Content-Length: " . filesize($file));
        readfile($file);
        exit;
    }


    private function listFilesRows($files, $hideDelete)
    {
            
            $html = "";
    
            foreach($files as $file)
            {

                $del = ($hideDelete === False) ? "<button class='btn btn-danger btn-sm'  onclick='dropFile(" . $file['id'] . ")'>Verwijder</button>" : "";
                
                if($hideDelete)
                {
                    $toggle = ($file['visible_for_client'] ? 'Ja' : 'Nee');
                }

                else
                {
                    $checked = ($file['visible_for_client']) ? 'checked' : '';
                    //$toggle = "<input type='checkbox' onclick='toggleClientVis(". $file['id'] ."," .  ($file['visible_for_client'] ? '0' : '1')  .")' " . $checked . " />";

                    $toggle = "<input type='checkbox' onclick='toggleClientVis(event, ". $file['id'] ."," .  ($file['visible_for_client'] ? '0' : '1')  .")' " . $checked . " style='zoom: 1.5;' />";                    
                }

                $icon = '';
                

                if($file['sent_to_client'] == 1)
                {
                    $icon = "<i class='icon icon-envelope'></i>";
                }
    
                $html .= "<tr>"
                            . "<td><a href='/sampleFiles/downloadFile/" .$file['id'] ."'>" . $file['original_name'] . "</a> </td>"                                                                    
                            . "<td>"
                            . $toggle 
                            . "</td>"
                            . "<td>"
                                . "<a class='btn btn-primary btn-sm' href='/sampleFiles/showFile/" .$file['id'] ."' target='_blank'>Toon</a> &nbsp;"
                                . $del 
                            . "</td>"
                            . "<td>"
                            
                            . $icon

                            . "</td>"
                        . "</tr>";
    
            }
    
            return $html;
    }



    
}