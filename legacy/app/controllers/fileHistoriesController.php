<?PHP

class fileHistoriesController extends controller
{

    function index()
    {

    }

    function saveFileSentWithReport($sampleFile, $transactionId, $contactGroups)
    {                
        $this->FileHistory->sample_id = $sampleFile['sample_id'];
        $this->FileHistory->samplefile_id = $sampleFile['id'];
        $this->FileHistory->hash_name = $sampleFile['hash_name'];
        $this->FileHistory->sent_by = getUserId();        
        $this->FileHistory->sent_to = json_encode($contactGroups);
        $this->FileHistory->sent_method = 'report';
        $this->FileHistory->transaction_id = $transactionId;
        $this->FileHistory->save();

    }

    function saveFileSentWithSender($sampleFile, $transactionId, $contactGroups)
    {

        $this->FileHistory->sample_id = $sampleFile['sample_id'];
        $this->FileHistory->samplefile_id = $sampleFile['id'];
        $this->FileHistory->hash_name = $sampleFile['hash_name'];
        $this->FileHistory->sent_by = getUserId();        
        $this->FileHistory->sent_to = json_encode($contactGroups);
        $this->FileHistory->sent_method = 'filesender';
        $this->FileHistory->transaction_id = $transactionId;
        $this->FileHistory->save();
    }

    function renderHistory()
    {
        $this->render = False;

        $this->FileHistory->where('samplefile_id', $_POST['sampleFileId']);

        $results = $this->FileHistory->search();
        
        $table = new tableFactory();
        $table->setTableId('fileHistoryTable');
        $table->loadTemplate('fileHistoryTable');

        if(empty($results)){
            $tableList = 'Bestand nog niet verstuurd';            
        }

        foreach($results as $idx => $history)
        {

            if($history['sent_method'] == 'report')
            {
                $results[$idx]['link'] = ALPC_BASEPATH . '/exports/extern/' . $history['transaction_id'];
            }
            else
            {
                $results[$idx]['link'] = '#';
            }

            $contacts = json_decode($history['sent_to'], True);
            $results[$idx]['cgroup_names'] = $contacts['groups'];
            $results[$idx]['cgroup_extra'] = $contacts['extra'];
                      
        }

        $table->specifyMod('sent_method', 'flipIt', array( ALPC_TF_SELF, ['report' => 'Samen met rapport', 'filesender' => 'Via bestands mailer'] ));

        $table->specifyMod('sent_by', 'getUserName', array(ALPC_TF_SELF));

        $table->specifyMod('sent_on', 'mysqldateToApplicationTimeZone', array(ALPC_TF_SELF));
        
        $table->loadValues($results);

        print(json_encode(['html' => $table->renderTable()]));
        

        
    }

    public function previouslySentCount($sampleFileId)
    {
        $this->FileHistory->where('samplefile_id', $sampleFileId);
        $results = $this->FileHistory->search();
        
        return count($results);
    }
    
}


// `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,  
// `sample_id` int NOT NULL,
// `samplefile_id` int NOT NULL,
// `hash_name` text NOT NULL,  
// `sent_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,  
// `sent_by` int NOT NULL,
// `sent_to` text NOT NULL,
// `sent_method` text NOT NULL,  
// `transaction_id` int NULL DEFAULT NULL