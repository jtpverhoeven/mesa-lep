<?PHP

class workListsController extends controller{

    private $_pdfHandle;
    private $_pdfContents;

    private $_dayStart;
    private $_dayEnd;

    private $_listHTML;
    private $_colAContent;
    private $_colBContent;

    private $_daySamples;
    private $_sampleAnalysis;
    
    protected $_publicActions = array( 'loadImage' => True);

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');                
    }

    function remove($id){
        $this->WorkList->id = $id;
        $this->WorkList->remove();
    }

    function saveImage(){

        $this->render = 0;
        $name = md5(rand(100, 200) . $_FILES['file']['name'] . time());
        $ext = explode('.',$_FILES['file']['name']);
        $filename = $name;
        $destination = ROOT . DS . 'app' . DS . 'private' . DS . 'worklistUpload' . DS . $filename . '.jpg';
        $location =  $_FILES["file"]["tmp_name"];

        imagejpeg(imagecreatefromstring(file_get_contents($location)), $destination);
        echo ALPC_BASEPATH  . '/workLists/loadImage/'. $filename . '.jpg';
    }

    function loadImage($fileName){

        $this->render = 0;
        //$size = getimagesize(ROOT .  DS .  'app' . DS . 'private' . DS . 'worklistUpload' . DS . $fileName);

        header("Content-type: image/jpg");
        //header("Content-Length: " . $size);

        //$img = file_get_contents( ROOT .  DS .  'app' . DS . 'private' . DS . 'worklistUpload' . DS . $fileName );
        //$
        $baseDir = ROOT .  DS .  'app' . DS . 'private' . DS . 'worklistUpload';
        $path = realpath($baseDir .  DS . $fileName);



        //$img = ROOT .  DS .  'app' . DS . 'private' . DS . 'worklistUpload' . DS . $fileName ;
        if (dirname($path) === $baseDir) {
          $fp = fopen($path, 'rb');
          fpassthru($fp);
          die();
        }
    }

    function pdfLoadImage($fileName){

    }

    function show(){

        $table = new tableFactory();
        $table->setTableId('worklistTable');
        $table->loadTemplate('worklistsTable');
        $results = $this->WorkList->search();

        if(empty($results)){
            $results = 'No worklists defined';
        }

        $table->loadValues($results);
        $this->_template->set('worklists_table', $table->renderTable());

        $sForm = new formFactory('assays');

        $sForm->setId('addWorklistform');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/workLists/saveNewWorklist');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('title', 'Werklijst title', 'text', 'input-block-level', '', 'Werklijst titel', False, False);
        $sForm->submitTrough('addWorklistSubmit');

        $this->_template->set('addworklistform', $sForm->render());

    }


    function edit($id){

        $this->WorkList->where('id', $id);
        $results = $this->WorkList->search();

        if(empty($results)){
            $this->reRoute('workLists/show', True);
        }

        $this->WorkList->arrayToModel($results['0']);

        $sForm = new formFactory('workLists');
        $sForm->setId('editWorklistForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/workLists/saveWorklist/' . $id);
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('title', 'Titel', 'text', 'input-block-level', $this->WorkList->title, 'Title', False, False);
        $sForm->addInputField('subtitle', 'Subtitel', 'text', 'input-block-level', $this->WorkList->subtitle, 'Subtitel', False, False);
        $sForm->addInputField('footer_text', 'Voet tekst', 'text', 'input-block-level', $this->WorkList->footer_text, 'Voet tekst', False, False);

        $options = array();
        $options['1'] = 'Een';
        $options['2'] = 'Twee';
        $sForm->addDropdownField('no_columns', 'Aantal kolommen', 'input-block-level', $this->WorkList->no_columns, $options, False);

        $optionsFormat = array();
        $optionsFormat['P'] = 'Staand';
        $optionsFormat['L'] = 'Liggend';
        $sForm->addDropdownField('orientation', 'Afdrukstand', 'input-block-level', $this->WorkList->orientation, $optionsFormat, False);

        $ids = json_decode($this->WorkList->analyses);
        $idsTextual = '';

        if(is_array($ids)){
            foreach($ids as $thisId){
                $idsTextual .= $thisId . ',';
            }
        }

        $idsTextual = substr($idsTextual, 0, -1);
        $sForm->addInputField('analyses', 'Analyses voor deze lijst', 'text', 'ajax-typeahead input-block-level ', $idsTextual , 'Type een analyse', array('autocomplete' => 'off', 'multiple' => "multiple"));

        $rows = json_decode($this->WorkList->extra_rows, JSON_FORCE_OBJECT);
        $rowsText = '';

        if(is_array($rows)){
            foreach($rows as $extraRow){
                $rowsText .= $extraRow . '&#10;';
            }
        }

        $rowsText = substr($rowsText, 0, -5);

        $sForm->addTextArea('extra_rows', 'Werklijst specifieke rijen', 'text-area', 'input-block-level', $rowsText, 'Extra rijen, een per regel', False);

        $columnGroups = json_decode($this->WorkList->colum_groups, JSON_FORCE_OBJECT);
        $columnGroupsText = '';

        if(is_array($columnGroups)){
            foreach($columnGroups as $extraRow){
                $columnGroupsText .= $extraRow['title'] . ';' . $extraRow['colspan'] . '&#10;';
            }
        }
        $columnGroupsText = substr($columnGroupsText, 0, -5);

        $sForm->addTextArea('colum_groups', 'Kolom groepen', 'text-area', 'input-block-level', $columnGroupsText, 'Groep kolomen samen, een per regel Title:colomSpan', False);

        $columnsarr = json_decode($this->WorkList->columns, JSON_FORCE_OBJECT);
        $colCount = 0;

        if(is_array($columnsarr)){
            foreach($columnsarr as $id => $column){
                $sForm->addInputField('column_title_' . $id, 'Lijst kolom ' . $id, 'text', 'input-block-level ', $column['name'] , 'Kolom naam');

                $idsTextual = '';
                if(array_key_exists('tick_on', $column)){
                    foreach($column['tick_on'] as $thisId){
                        $idsTextual .= $thisId . ',';
                    }
                    $idsTextual = substr($idsTextual, 0, -1);
                }

                $sForm->addInputField('column_trip_' . $id,  False, 'text', 'input-block-level tripcol', $idsTextual , 'Vink aan bij analyse');
                $colCount = $colCount + 1;
            }
        }


        $sForm->addTextArea('extra_pages', 'Extra documentatie werklijst', 'text', 'summernote input-block-level ', $this->WorkList->extra_pages , '', False);
        $sForm->submitTrough('saveWorklistButton');

        $this->_template->set('edit_form', $sForm->render());
        $this->_template->set('id', $id);
        $this->_template->set('col_count', $colCount);

    }

    function saveWorklist($id){
        $this->render = false;

        $extra_pages = $_POST['extra_pages'];

        //remove br and br tags
        $extra_pages_check = str_replace('<br>', '', $extra_pages);
        $extra_pages_check = str_replace('<br />', '', $extra_pages_check);

        //strip empty tags
        $extra_pages_check = preg_replace('/<[^\/>]*>([\s]?)*<\/[^>]*>/', '', $extra_pages_check);

        if(empty($extra_pages_check)){
            $extra_pages = '';
        }

        $this->WorkList->id = $id;
        $this->WorkList->title = $_POST['title'];
        $this->WorkList->subtitle = $_POST['subtitle'];
        $this->WorkList->footer_text = $_POST['footer_text'];
        $this->WorkList->no_columns = $_POST['no_columns'];
        $this->WorkList->orientation = $_POST['orientation'];
        $this->WorkList->extra_pages = $extra_pages;

        $extraRows = array();
        if(isset($_POST['extra_rows']) && !empty($_POST['extra_rows'])){
            $textAr = explode("\n", $_POST['extra_rows']);
            $textAr = array_filter($textAr, 'trim');
            foreach ($textAr as $line) {
                array_push($extraRows, trim($line));
            }
        }

        $this->WorkList->extra_rows = json_encode($extraRows);

        $analysis = array();
        if(isset($_POST['analyses']) && !empty($_POST['analyses'])){
            $textAr = explode(",", $_POST['analyses']);
            $textAr = array_filter($textAr, 'trim');
            foreach ($textAr as $line) {
                array_push($analysis, $line);
            }
        }

        $this->WorkList->analyses = json_encode($analysis);

        $columnsAndTrip = array();

        foreach($_POST as $post=>$value){
            $colStrip = substr($post, 0, 13);
            if($colStrip == 'column_title_'){
                $colId = explode('_', $post)[2];
                $colName = $_POST['column_title_' . $colId];
                $colTripText = $_POST['column_trip_' . $colId];
                $colTripExplode = explode(',', $colTripText);

                $colTripArr = array();
                $columnsAndTrip[$colId]['name'] = $colName;


                foreach($colTripExplode as $colTripId){
                    if(!empty($colTripId)){
                        array_push($colTripArr,$colTripId);
                    }
                }

                if(!empty($colTripArr)){
                    $columnsAndTrip[$colId]['tick_on'] = $colTripArr;
                }

            }
        }

        $this->WorkList->columns = json_encode($columnsAndTrip);

        //save col groups
        $colGroups = array();
        $i = 0;
        if(isset($_POST['colum_groups']) && !empty($_POST['colum_groups'])){
            $textAr = explode("\n", $_POST['colum_groups']);
            $textAr = array_filter($textAr, 'trim');
            foreach ($textAr as $line) {

                if(!empty($line)){
                    $split = explode(';', trim($line));

                    if(array_key_exists(0, $split) && array_key_exists(1, $split)){

                        $title = trim($split[0]);
                        if(is_int((int)$split[1])){
                            $colSpan = $split[1];
                        } else{
                            $colSpan = 1;
                        }
                        $colGroups[$i]['title'] = $title;
                        $colGroups[$i]['colspan'] = $colSpan;
                    }
                    $i++;
                }
            }
        }

        $this->WorkList->colum_groups = json_encode($colGroups, JSON_FORCE_OBJECT);
        $this->WorkList->save();
        $this->reRoute('workLists/edit/' . $id, True);
    }

    function saveNewWorklist(){

        $this->WorkList->title = $_POST['title'];

        $this->WorkList->save();
        $id = $this->WorkList->lastInsertId;
        $this->reRoute('workLists/edit/' . $id, True);
    }


    private function loadDaySamples()
    {
        $sql = 'SELECT * FROM `samples` WHERE sample_innoculated >= :start AND sample_innoculated < :end';
        
        $samples = $this->WorkList->customQuery($sql, [
            'start' => $this->_dayStart,
            'end' => $this->_dayEnd
        ]);

        $adjustedSamples = [];
        $samplesSeen = [];

        foreach($samples as $thisSample)
        {                        
            array_push($adjustedSamples, $thisSample);
            array_push($samplesSeen, $thisSample['id']);
        }

        $this->_daySamples = array_column($adjustedSamples, null, 'id');

        if(empty($this->_daySamples))
        {
            print('Geen monsters gevonden voor deze inzet-datum, kon geen formulieren aanmaken.');
            exit;
        }
        
        $idMap = implode(',', array_map('intval', array_keys($this->_daySamples)));        


        $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS day_analysis AS (SELECT * FROM sampleanalysis WHERE sample IN (' . $idMap . '));';
        
        $this->WorkList->customSetQuery($sql, []);
    
    }

    private function getApplicableSamples($analysis){

        //need to grab the original_id's of this 
        if(count($analysis) > 0)
        {
            $idMap = implode(',', array_map('intval', $analysis));        
            $sql = 'SELECT original_id FROM `assays` WHERE id IN (' . $idMap . ');';
            $assays = $this->WorkList->customQuery($sql, array());
            $originals = array_column($assays, 'original_id'); 
        } 
        
        else
        {
            $originals = $analysis;
        }
        

        $idMap = implode(',', array_map('intval', $originals));                            
        $sql = 'SELECT * FROM day_analysis WHERE assay_base IN (' . $idMap . ') OR  original_assay_base IN (' . $idMap .');';        
        
        $samplesWithAnalysis = $this->WorkList->customQuery($sql, array());
        

        $samples = [];

        foreach($samplesWithAnalysis as $sa)
        {
            $foundSample = $this->_daySamples[$sa['sample']];                        
            $foundSample['original_sample_follow'] = $foundSample['follow_no'];
            $foundSample['follow_no_int'] =  $sa['follow_number'];
            $foundSample['follow_no'] = '.' . $sa['follow_number'] . '.1';
            array_push($samples,  $foundSample);
        }
                

        /* $sampleCollection = upa('sampleAnalysis', 'getSamplesByAnalysis', array($originals, True), False);


        
        $samplesSeen = array();
        $samples = array();
        foreach($sampleCollection as $sample) {

            //TODO: make this a batch operation and select the time there
            $thisSample = upa('samples', 'fetch', array($sample['sample']), False);
            //if(($thisSample['sample_innoculated'] >= $this->_dayStart && $thisSample['sample_innoculated'] < $this->_dayEnd) && !in_array($thisSample['id'], $samplesSeen) ){
            if(($thisSample['sample_innoculated'] >= $this->_dayStart && $thisSample['sample_innoculated'] < $this->_dayEnd)){
                $thisSample['original_sample_follow'] = $thisSample['follow_no'];
                $thisSample['follow_no_int'] =  $sample['follow_number'];
                $thisSample['follow_no'] = '.' . $sample['follow_number'] . '.1';
                array_push($samples, $thisSample);
                array_push($samplesSeen, $thisSample['id']);
            }
        } */
        
        return $samples;
    }

    private function getColumnStructure($columns){
        
        $colStruct = json_decode($columns, JSON_FORCE_OBJECT);

        foreach($colStruct as $idx => $col)
        {
            if(array_key_exists('tick_on', $col))
            {
                $idMap = implode(',', array_map('intval', $col['tick_on']));        
                $sql = 'SELECT original_id FROM `assays` WHERE id IN (' . $idMap . ');';
                $assays = $this->WorkList->customQuery($sql, array());
                $originals = array_column($assays, 'original_id');                 
                $colStruct[$idx]['tick_on'] = $originals; 
            }
        }
     

        return $colStruct;
    }

    private function writeTable($workList){

        $hasExtraPage = False; 

        if($workList['extra_pages'] != '' && !empty($workList['extra_pages'])){
            $hasExtraPage = True;
        }

        $nPerPageColumn = 12;
        $customRows = json_decode($workList['extra_rows'], JSON_FORCE_OBJECT);
        $noOfCustomRows = count($customRows);
        $sampleCollection = $this->getApplicableSamples(json_decode($workList['analyses'], JSON_FORCE_OBJECT));
        $numberOfSamples = count($sampleCollection);
        $nPerPage = ($nPerPageColumn * $workList['no_columns']) - $noOfCustomRows;
        $samplesPerPage = array_chunk($sampleCollection, $nPerPage);

        foreach($samplesPerPage as $idx => $page)
        {

            if($workList['no_columns'] == 1){
                $this->_pdfContents .= $this->generate1colpage($workList, $page);
            } else{
                $this->_pdfContents .= $this->generate2colpage($workList, $page);
            }

            //is this the last element in loop
            $isLast = $idx == count($samplesPerPage) - 1;

            if(!$isLast)
            {
                $this->_pdfContents .= '<pagebreak />';
            }
        }

        if($hasExtraPage)
        {
            $this->_pdfContents .= '<pagebreak />';
        }

            
    }

    private function generate1colpage($workList, $page){
        $listLayout = $this->generateListLayout($workList);
        $customRows = json_decode($workList['extra_rows'], JSON_FORCE_OBJECT);
        $columnStructure = $this->getColumnStructure($workList['columns']);

        



        $columnAHTML =  $this->generateHeaders($workList);

        $limit = 11 - count($customRows);
        //create col A
        for ($x = 0; $x <= $limit; $x++) {

            if(array_key_exists($x, $page)){
                $sample = $page[$x];
                $columnAHTML .= $this->getSampleLine($sample, $columnStructure);
            } else{
                $columnAHTML .= $this->writeBlankLine($columnStructure);
            }
        }

        foreach($customRows as $customRow){
            $columnAHTML .= $this->writeCustomRow($customRow, $columnStructure);
        }

        //close col A
        $columnAHTML .= '</table>';
        return generateHTML($listLayout, array('colA' => $columnAHTML), True);
    }

    private function  generate2colPage($workList, $page){

        $listLayout = $this->generateListLayout($workList);
        $customRows = json_decode($workList['extra_rows'], JSON_FORCE_OBJECT);
        $columnStructure = $this->getColumnStructure($workList['columns']);

        $columnAHTML =  $this->generateHeaders($workList);
        $columnBHTML =  $this->generateHeaders($workList);

        //create col A
        for ($x = 0; $x <= 11; $x++) {
            if(array_key_exists($x, $page)){
                $sample = $page[$x];
                $columnAHTML .= $this->getSampleLine($sample, $columnStructure);
            } else{
                $columnAHTML .= $this->writeBlankLine($columnStructure);
            }
        }
        //close col A
        $columnAHTML .= '</table>';

        //create col B
        $colLimit = (12 + 11) - count($customRows);

        for ($x = 12; $x <= $colLimit; $x++) {
            if(array_key_exists($x, $page)){
                $sample = $page[$x];
                $columnBHTML .= $this->getSampleLine($sample, $columnStructure);
            } else{
                $columnBHTML .= $this->writeBlankLine($columnStructure);
            }
        }

        //write custom rows
        foreach($customRows as $customRow){
            $columnBHTML .= $this->writeCustomRow($customRow, $columnStructure);
        }

        //close col B
        $columnBHTML .= "</table>";

        //graft into page table
        return generateHTML($listLayout, array('colA' => $columnAHTML, 'colB' => $columnBHTML), True);
    }

    private function writeCustomRow($customRow, $columnStructure){
        $row = '<tr>';
        $row .= '<td>'. $customRow .'</td>';

        foreach($columnStructure as $column){
            $row .=  '<td></td>';
        }

        $row .= '</tr>';
        return $row;
    }

    private function getSampleLine($sample, $columnStructure){

        $row = '<tr>';
        
        $bar = $this->bar($sample['barcode'] . $sample['follow_no'], 20);

        //"data:image/jpeg;base64,
       // $row .= '<td style="padding: 0px; margin: 0px;">'.$sample['barcode'] . $sample['follow_no'] .'<br /> <img src="data:image/jpeg;base64,' . $bar . '" style="padding: 0px; margin: 0px;" width="160px" /> </td>';

       //todo: refactor
        $row .= '<td style="padding: 0px; margin: 0px;"><strong>'.$sample['original_sample_follow'] .
                '</strong><br /> <img src="data:image/jpeg;base64,' . $bar . '" style="padding: 0px; margin: 0px;" width="160px" /><br />' 
                .$sample['description'] . '<br />'
                . customerIdToName($sample['client'])
                .'</td>';

        foreach($columnStructure as $column){

            $hasTickmarker = False;
            if(array_key_exists('tick_on', $column)) {
                //todo: refactor
                //print('looking for:' . parray($column, true));
                //print('for sample: ' . $sample['id'] . $sample['follow_no_int'] );
                $hasTickmarker = upa('sampleAnalysis', 'checkIfSampleFollowHas', array($sample['id'], $sample['follow_no_int'], $column['tick_on']), False);
            } else{
                $hasTickmarker = False;
            }

            if($hasTickmarker == True){  $row .=  '<td>X</td>';  }
            else{  $row .=  '<td></td>'; }
        }

        $row .= '</tr>';
        return $row;
    }

    private function writeBlankLine($columnStructure){
        $row = '<tr>';
        $row .= "<td style='padding: 0px; margin: 0px;'>&nbsp;</td>";
        foreach($columnStructure as $column){
            $row .= "<td>&nbsp;</td>";
        }
        $row .= '</tr>';
        return $row;
    }

    private function generateHeaders($workList){

        $tableHeader = "<table class='CSSTableGenerator' style='width: 100%'>";
        $colGroups = json_decode($workList['colum_groups'], JSON_FORCE_OBJECT);


        if(is_array($colGroups)){
            $groupHTML = '<tr>';
            foreach($colGroups as $colGroup){

                if(empty($colGroup['title'])){
                    $groupHTML .= '<td colspan="'. $colGroup['colspan'] . '" style="border: 0px;   background-color:#ffffff;">&nbsp;</td>';
                } else{
                    $groupHTML .= '<td colspan="'. $colGroup['colspan'] . '">' . $colGroup['title'] . '</td>';
                }
            }
            $groupHTML .= '</tr>';
            $tableHeader .= $groupHTML;
        }

        $columnStructure = $this->getColumnStructure($workList['columns']);
        $columnTRdata = '<td>Monsternummer <br /> Labnummer</td>';
        foreach($columnStructure as $column){
            //$columnTRdata .= "<td>" . $column['name'] . "</td>";

            $columnTRdata .= '<td>
            <svg style="margin: 0px; padding: 0px;" height="50" width="20" >
            <text transform="rotate(90)" x="0" y="0">'. $column['name'] . '</text>
            </svg></td>';

        }

        $headerData = "<tr>" . $columnTRdata . "</tr>";
        $tableHeader .= $headerData;
        return $tableHeader;
    }

    private function generateListLayout($workList){
        if($workList['no_columns'] == 2){
            $listLayout = '<table style="width: 100%; "><tr>
                                    <td style="vertical-align: top; width: 40%;">{colA}</td>
                                    <td style="vertical-align: top; width: 40%;">{colB}</td>
                                </tr></table>';
        } else{
            $listLayout = '<table style="width: 100%"><tr>
                                    <td style="vertical-align: top;width: 90%;">{colA}</td>
                                </tr></table>';
        }
        return $listLayout;
    }

    function appendCustomPage($workList){

        if($workList['extra_pages'] != '' && !empty($workList['extra_pages'])){
            $this->_pdfContents .= $workList['extra_pages'];
        }
        
    }

    function exportAllLists($innocDate = False){

        $this->render = False;


        $innocDate = strtotime($innocDate);
        $result = $this->WorkList->search();
        $namesInUse = array();

        require_once( ROOT . '/library/mpdf/mpdf.php');
        date_default_timezone_set('Europe/Amsterdam');

        $this->_dayStart = $innocDate;
        $this->_dayEnd = $innocDate + (60 * 60 * 23); //transform to end-of-day
        
        $this->loadDaySamples();
        

        foreach($result as $workList){

          $sampleCollection = $this->getApplicableSamples(json_decode($workList['analyses'], JSON_FORCE_OBJECT));          
          $samplesFound = count($sampleCollection);          

          if($samplesFound == 0){            
            continue;
          }

          if( strtoupper($workList['orientation']) == 'P'){   $page = 'A4';   $orientation = 'P'; }
          else{ $page = 'A4-L'; $orientation = 'L'; }

          //load in header footer
          $repStack = array();
          $repStack['worklist_title'] = $workList['title'];
          $repStack['worklist_subtitle'] = $workList['subtitle'];
          $repStack['worklist_date'] = date('d-m-Y', $innocDate);
          $repStack['footer_text'] = $workList['footer_text'];
          $headFootContent = file_get_contents(ROOT . '/app/docgen/worklists/header.php');
          $headFootContentFoot = file_get_contents(ROOT . '/app/docgen/worklists/footer.php');
          $header =  generateHTML($headFootContent, $repStack, True);
          $footer =  generateHTML($headFootContentFoot, $repStack, True);          

          $this->writeTable($workList);
          
          $this->appendCustomPage($workList);
          

          //$this->appendCustomPage($workList);

          $this->_pdfHandle = new mPDF('utf-8', $page,'7','arial',10,10,30,13,5,5, $orientation);
          $this->_pdfHandle->showImageErrors = true;
          $this->_pdfHandle->debug = True;
          $this->_pdfHandle->SetHTMLHeader($header);
          $this->_pdfHandle->setHTMLFooter($footer);
          $stylesheet = file_get_contents( ROOT . '/app/docgen/worklists/style.css');
          $this->_pdfHandle->WriteHTML($stylesheet, 1);
          $this->_pdfHandle->WriteHTML($this->_pdfContents);

          $thisWorkListOutput = $this->_pdfHandle->Output('werklijst.pdf', 'S');

          $this->_pdfContents = '';
          unset($this->_pdfHandle);

          if(!isset($zip)){
              $pdfName =  'werklijsten.zip';
              $filename =  ROOT . '/app/private/scratch/werklijsten_' . time() . '.zip' ;
              $zip = new ZipArchive();
              if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
                  exit("cannot open <$filename>\n");
              }
          }

          $pdfInZipName = $workList['title'] . '.pdf';

          if(array_key_exists($pdfInZipName, $namesInUse)){
              $fileAdd = '(' . $namesInUse[$pdfInZipName] . ')';
              $namesInUse[$pdfInZipName] += 1;
          } else{
              $fileAdd = '';
              $namesInUse[$pdfInZipName] = 1;
          }

          $zip->addFromString( $pdfInZipName . $fileAdd . ".pdf" , $thisWorkListOutput);

        }


        if($zip){
          $zip->close();

          header("Content-Type: application/zip");
          header("Content-Length: " . filesize($filename));
          header("Content-Disposition: attachment; filename=\"" . "werklijsten.zip" . "\"");
          readfile($filename);
        } else{
          print '<h1> Geen monsters gevonden voor werklijsten </h1>';
        }


    }


    function exportWorkList($listId = False, $innocDate= False){

        $this->render = False;

        if($listId == False || $innocDate == False){
            $listId = $_POST['work_list_type'];
            $innocDate = $_POST['work_list_date'];
        }

        if($listId == 'all'){
          return $this->exportAllLists($innocDate);          
        }

        $innocDate = strtotime($innocDate);

        $this->WorkList->where('id', $listId);
        $result = $this->WorkList->search();

        if(empty($result)){
            die('Selected list does not exist');
        } else{
            $workList = $result[0];
        }

        //set orientation
        require_once( ROOT . '/library/mpdf/mpdf.php');
        date_default_timezone_set('Europe/Amsterdam');
        if( strtoupper($workList['orientation']) == 'P'){   $page = 'A4';   $orientation = 'P'; }
        else{ $page = 'A4-L'; $orientation = 'L'; }

        //load in header footer
        $repStack = array();
        $repStack['worklist_title'] = $workList['title'];
        $repStack['worklist_subtitle'] = $workList['subtitle'];
        $repStack['worklist_date'] = date('d-m-Y', $innocDate);
        $repStack['footer_text'] = $workList['footer_text'];
        $headFootContent = file_get_contents(ROOT . '/app/docgen/worklists/header.php');
        $headFootContentFoot = file_get_contents(ROOT . '/app/docgen/worklists/footer.php');
        $header =  generateHTML($headFootContent, $repStack, True);
        $footer =  generateHTML($headFootContentFoot, $repStack, True);

        //date
        $this->_dayStart = $innocDate;
        $this->_dayEnd = $innocDate + (60 * 60 * 23); //transform to end-of-day
        
        $this->loadDaySamples();        

        $this->writeTable($workList);
        $this->appendCustomPage($workList);

        $this->_pdfHandle = new mPDF('utf-8', $page,'7','arial',10,10,30,13,5,5, $orientation);
        $this->_pdfHandle->showImageErrors = true;
        $this->_pdfHandle->debug = True;
        $this->_pdfHandle->SetHTMLHeader($header);
        $this->_pdfHandle->setHTMLFooter($footer);
        $stylesheet = file_get_contents( ROOT . '/app/docgen/worklists/style.css');
        $this->_pdfHandle->WriteHTML($stylesheet, 1);
        $this->_pdfHandle->WriteHTML($this->_pdfContents);

        $this->_pdfHandle->Output('werklijst.pdf', 'I');
    }




    function getDropper($html = false){
        $this->render = false;
        $workLists = $this->WorkList->search();

        $workListsHtml = '';
        $workListsArray = array();

        foreach($workLists as $list){
            $workListsHtml .= '<option value="' . $list['id'] . '">' . $list['title'] .'</option>';
            $workListsArray[$list['id']] = $list['title'];
        }

        if($html == false){
            return $workListsArray;
        } else{
            return $workListsHtml;
        }
    }

    function bar($string, $size = 12){

        $this->render = False;

        //$text = (isset($_GET["text"])?$_GET["text"]:"0");
        $text = $string;
        $barTitle = $string;
        //$size = (isset($_GET["size"])?$_GET["size"]:"20");
        $orientation = (isset($_GET["orientation"])?$_GET["orientation"]:"horizontal");
        $code_type = (isset($_GET["codetype"])?$_GET["codetype"]:"code128");
        $code_string = "";
        // Translate the $text into barcode the correct $code_type
        if ( in_array(strtolower($code_type), array("code128", "code128b")) ) {
            $chksum = 104;
            // Must not change order of array elements as the checksum depends on the array's key to validate final code
            $code_array = array(" "=>"212222","!"=>"222122","\""=>"222221","#"=>"121223","$"=>"121322","%"=>"131222","&"=>"122213","'"=>"122312","("=>"132212",")"=>"221213","*"=>"221312","+"=>"231212",","=>"112232","-"=>"122132","."=>"122231","/"=>"113222","0"=>"123122","1"=>"123221","2"=>"223211","3"=>"221132","4"=>"221231","5"=>"213212","6"=>"223112","7"=>"312131","8"=>"311222","9"=>"321122",":"=>"321221",";"=>"312212","<"=>"322112","="=>"322211",">"=>"212123","?"=>"212321","@"=>"232121","A"=>"111323","B"=>"131123","C"=>"131321","D"=>"112313","E"=>"132113","F"=>"132311","G"=>"211313","H"=>"231113","I"=>"231311","J"=>"112133","K"=>"112331","L"=>"132131","M"=>"113123","N"=>"113321","O"=>"133121","P"=>"313121","Q"=>"211331","R"=>"231131","S"=>"213113","T"=>"213311","U"=>"213131","V"=>"311123","W"=>"311321","X"=>"331121","Y"=>"312113","Z"=>"312311","["=>"332111","\\"=>"314111","]"=>"221411","^"=>"431111","_"=>"111224","\`"=>"111422","a"=>"121124","b"=>"121421","c"=>"141122","d"=>"141221","e"=>"112214","f"=>"112412","g"=>"122114","h"=>"122411","i"=>"142112","j"=>"142211","k"=>"241211","l"=>"221114","m"=>"413111","n"=>"241112","o"=>"134111","p"=>"111242","q"=>"121142","r"=>"121241","s"=>"114212","t"=>"124112","u"=>"124211","v"=>"411212","w"=>"421112","x"=>"421211","y"=>"212141","z"=>"214121","{"=>"412121","|"=>"111143","}"=>"111341","~"=>"131141","DEL"=>"114113","FNC 3"=>"114311","FNC 2"=>"411113","SHIFT"=>"411311","CODE C"=>"113141","FNC 4"=>"114131","CODE A"=>"311141","FNC 1"=>"411131","Start A"=>"211412","Start B"=>"211214","Start C"=>"211232","Stop"=>"2331112");
            $code_keys = array_keys($code_array);
            $code_values = array_flip($code_keys);
            for ( $X = 1; $X <= strlen($text); $X++ ) {
                $activeKey = substr( $text, ($X-1), 1);
                $code_string .= $code_array[$activeKey];
                $chksum=($chksum + ($code_values[$activeKey] * $X));
            }
            $code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];
            $code_string = "211214" . $code_string . "2331112";
        } 



        /*
        elseif ( strtolower($code_type) == "code128a" ) {
            $chksum = 103;
            $text = strtoupper($text); // Code 128A doesn't support lower case
            // Must not change order of array elements as the checksum depends on the array's key to validate final code
            $code_array = array(" "=>"212222","!"=>"222122","\""=>"222221","#"=>"121223","$"=>"121322","%"=>"131222","&"=>"122213","'"=>"122312","("=>"132212",")"=>"221213","*"=>"221312","+"=>"231212",","=>"112232","-"=>"122132","."=>"122231","/"=>"113222","0"=>"123122","1"=>"123221","2"=>"223211","3"=>"221132","4"=>"221231","5"=>"213212","6"=>"223112","7"=>"312131","8"=>"311222","9"=>"321122",":"=>"321221",";"=>"312212","<"=>"322112","="=>"322211",">"=>"212123","?"=>"212321","@"=>"232121","A"=>"111323","B"=>"131123","C"=>"131321","D"=>"112313","E"=>"132113","F"=>"132311","G"=>"211313","H"=>"231113","I"=>"231311","J"=>"112133","K"=>"112331","L"=>"132131","M"=>"113123","N"=>"113321","O"=>"133121","P"=>"313121","Q"=>"211331","R"=>"231131","S"=>"213113","T"=>"213311","U"=>"213131","V"=>"311123","W"=>"311321","X"=>"331121","Y"=>"312113","Z"=>"312311","["=>"332111","\\"=>"314111","]"=>"221411","^"=>"431111","_"=>"111224","NUL"=>"111422","SOH"=>"121124","STX"=>"121421","ETX"=>"141122","EOT"=>"141221","ENQ"=>"112214","ACK"=>"112412","BEL"=>"122114","BS"=>"122411","HT"=>"142112","LF"=>"142211","VT"=>"241211","FF"=>"221114","CR"=>"413111","SO"=>"241112","SI"=>"134111","DLE"=>"111242","DC1"=>"121142","DC2"=>"121241","DC3"=>"114212","DC4"=>"124112","NAK"=>"124211","SYN"=>"411212","ETB"=>"421112","CAN"=>"421211","EM"=>"212141","SUB"=>"214121","ESC"=>"412121","FS"=>"111143","GS"=>"111341","RS"=>"131141","US"=>"114113","FNC 3"=>"114311","FNC 2"=>"411113","SHIFT"=>"411311","CODE C"=>"113141","CODE B"=>"114131","FNC 4"=>"311141","FNC 1"=>"411131","Start A"=>"211412","Start B"=>"211214","Start C"=>"211232","Stop"=>"2331112");
            $code_keys = array_keys($code_array);
            $code_values = array_flip($code_keys);
            for ( $X = 1; $X <= strlen($text); $X++ ) {
                $activeKey = substr( $text, ($X-1), 1);
                $code_string .= $code_array[$activeKey];
                $chksum=($chksum + ($code_values[$activeKey] * $X));
            }
            $code_string .= $code_array[$code_keys[($chksum - (intval($chksum / 103) * 103))]];
            $code_string = "211412" . $code_string . "2331112";
        } elseif ( strtolower($code_type) == "code39" ) {
            $code_array = array("0"=>"111221211","1"=>"211211112","2"=>"112211112","3"=>"212211111","4"=>"111221112","5"=>"211221111","6"=>"112221111","7"=>"111211212","8"=>"211211211","9"=>"112211211","A"=>"211112112","B"=>"112112112","C"=>"212112111","D"=>"111122112","E"=>"211122111","F"=>"112122111","G"=>"111112212","H"=>"211112211","I"=>"112112211","J"=>"111122211","K"=>"211111122","L"=>"112111122","M"=>"212111121","N"=>"111121122","O"=>"211121121","P"=>"112121121","Q"=>"111111222","R"=>"211111221","S"=>"112111221","T"=>"111121221","U"=>"221111112","V"=>"122111112","W"=>"222111111","X"=>"121121112","Y"=>"221121111","Z"=>"122121111","-"=>"121111212","."=>"221111211"," "=>"122111211","$"=>"121212111","/"=>"121211121","+"=>"121112121","%"=>"111212121","*"=>"121121211");
            // Convert to uppercase
            $upper_text = strtoupper($text);
            for ( $X = 1; $X<=strlen($upper_text); $X++ ) {
                $code_string .= $code_array[substr( $upper_text, ($X-1), 1)] . "1";
            }
            $code_string = "1211212111" . $code_string . "121121211";
        } elseif ( strtolower($code_type) == "code25" ) {
            $code_array1 = array("1","2","3","4","5","6","7","8","9","0");
            $code_array2 = array("3-1-1-1-3","1-3-1-1-3","3-3-1-1-1","1-1-3-1-3","3-1-3-1-1","1-3-3-1-1","1-1-1-3-3","3-1-1-3-1","1-3-1-3-1","1-1-3-3-1");
            for ( $X = 1; $X <= strlen($text); $X++ ) {
                for ( $Y = 0; $Y < count($code_array1); $Y++ ) {
                    if ( substr($text, ($X-1), 1) == $code_array1[$Y] )
                        $temp[$X] = $code_array2[$Y];
                }
            }
            for ( $X=1; $X<=strlen($text); $X+=2 ) {
                if ( isset($temp[$X]) && isset($temp[($X + 1)]) ) {
                    $temp1 = explode( "-", $temp[$X] );
                    $temp2 = explode( "-", $temp[($X + 1)] );
                    for ( $Y = 0; $Y < count($temp1); $Y++ )
                        $code_string .= $temp1[$Y] . $temp2[$Y];
                }
            }
            $code_string = "1111" . $code_string . "311";
        } elseif ( strtolower($code_type) == "codabar" ) {
            $code_array1 = array("1","2","3","4","5","6","7","8","9","0","-","$",":","/",".","+","A","B","C","D");
            $code_array2 = array("1111221","1112112","2211111","1121121","2111121","1211112","1211211","1221111","2112111","1111122","1112211","1122111","2111212","2121112","2121211","1121212","1122121","1212112","1112122","1112221");
            // Convert to uppercase
            $upper_text = strtoupper($text);
            for ( $X = 1; $X<=strlen($upper_text); $X++ ) {
                for ( $Y = 0; $Y<count($code_array1); $Y++ ) {
                    if ( substr($upper_text, ($X-1), 1) == $code_array1[$Y] )
                        $code_string .= $code_array2[$Y] . "1";
                }
            }
            $code_string = "11221211" . $code_string . "1122121";
        } */
        // Pad the edges of the barcode
        $code_length = 20;
        for ( $i=1; $i <= strlen($code_string); $i++ )
            $code_length = $code_length + (integer)(substr($code_string,($i-1),1));
        if ( strtolower($orientation) == "horizontal" ) {
            $img_width = $code_length;
            $img_height = $size;
        } else {
            $img_width = $size;
            $img_height = $code_length;
        }
        $image = imagecreate($img_width, $img_height);
        $black = imagecolorallocate ($image, 0, 0, 0);
        $white = imagecolorallocate ($image, 255, 255, 255);
        imagefill( $image, 0, 0, $white );
        $location = 10;
        for ( $position = 1 ; $position <= strlen($code_string); $position++ ) {
            $cur_size = $location + ( substr($code_string, ($position-1), 1) );
            if ( strtolower($orientation) == "horizontal" )
                imagefilledrectangle( $image, $location, 0, $cur_size, $img_height, ($position % 2 == 0 ? $white : $black) );
            else
                imagefilledrectangle( $image, 0, $location, $img_width, $cur_size, ($position % 2 == 0 ? $white : $black) );
            $location = $cur_size;
        }
        // Draw barcode to the screen
        //header ('Content-type: image/png');
        //imagepng($image);
        //imagedestroy($image);

        ob_start ();
        imagejpeg($image, NULL, 100);
        $image_data = ob_get_contents ();
        ob_end_clean ();
        return base64_encode($image_data);

    }


}
