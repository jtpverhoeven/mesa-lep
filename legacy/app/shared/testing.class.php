<?PHP

class testing
{

    private $testId; 
    private $actions = []; 
    
    private $sampleId; 
    private $sample; 
    private $assay; 
    private $loadedSAIDS = [];
    private $saidInfo = [];

    public $log = []; 

    public $failed = False; 
    public $errors = 0;
    public $evaluation = '';
    public $fullLog = '';


    protected $group; 
    public $projectId; 


    public function __construct($testId, $group = True, $projectId = False)    
    {
        $this->testId = $testId; 
        $this->actions = $this->getTestActions(); 
        $this->test = upa('tests', 'fetch', array($this->testId), False);            
        $this->evaluation = '';
        
        $this->group = filter_var($group, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);        
        $this->projectId = $projectId; 

        $this->_log('Test gestart, test naam: ' .  $this->test['name'] . ', test id:' . $testId);

    }

    public function run()
    {
        
        foreach($this->actions as $action)
        {
            $this->executeAction($action);                        
        }

        if($this->failed === True)
        {
            $this->_log('<strong>!!! MISLUKT !!! Test : ' . $this->test['name'] . ' (test id ' . $this->testId . ') gaf ' . $this->errors . ' fouten aan. </strong>', ['white', 'red']);
        }

        if($this->failed === False)
        {
            $this->_log('<strong>Test succesvol afgerond</strong>', ['black', 'green']);
        }


                
        $this->renderLog();

    }
    

    private function renderLog()
    {
        foreach($this->log as $log)
        {
            $this->fullLog .= $log; 
        }
    }

    private function getTestActions()
    {
        $actions = upa('testActions', 'getTestActionsForSet', array($this->testId), False); 
        return $actions; 
    }

    private function executeAction($action)
    {
                                                
        $actionType = $action['action'];
        $data = json_decode($action['data']);        

        //$this->_log('Actie gevonden:' . $actionType . ', <br/> Ruwe data voor automatische acties: ' . $action['data']);

        switch ($actionType) 
        {
            case 'create_sample':
                $this->_createSample($data);
            break;

            case 'create_sample_legionella':
                $this->_createSample($data, True, False);
                $this->_loadExistingSAID($this->sampleId); 
            break;

            case 'create_sample_rodac':
                $this->_createSample($data, False, True);
                #$this->_loadExistingSAID($this->sampleId); 
            break;

            case 'add_assay':
                $this->_addAssay($data);
            break; 

            case 'add_profile':
                $this->_addProfile($data);
            break;
            
            case 'register_innoculation':
                $this->_registerInnoc();
            break; 

            case 'set_result':
                $this->_setResult($data);
            break;                 

            case 'set_confirmation':
                $this->_setConfirmation($data);
            break;                 

            case 'set_confirmation_data':                
                $this->_setConfirmationData($data);
            break;

            case 'assert':
                $this->_log('Asert functie gevonden, <br/> Assay: ' . $data->assay_base . ', key:  ' . $data->key . '  , param: ' . $data->param . ', assertion:  ' . $data->assertion . ' value:'  . $data->value );
                $this->_assert($data);
            break;

        }
    
    }

    private function addSAIDinfo($saids)
    {

        foreach($saids as $said)
        {
            $saidRow = upa('sampleAnalysis', 'fetch', array($said), False);
            if($saidRow)
            {
                $this->saidInfo[$saidRow['assay_base']] = $saidRow;        
            }
            
        }
        
    }

    private function _loadExistingSAID($sampleId)
    {
        
        $saids = upa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId));
        
        foreach($saids as $said)
        {
            array_push($this->loadedSAIDS,  $said['id']);
        }        
        
        $this->addSAIDinfo($this->loadedSAIDS);
    }



    private function _createSample($data, $legionella=False, $rodac=False)
    {        
        
        $insertIntoProject = False; 

        if($this->group == True && $this->projectId != 0)
        {
            $this->_log('Inserting into this project:' . $insertIntoProject);
            $insertIntoProject = $this->projectId;
        }
        
        $this->sampleId = upa('samples', 'fakerEntryPoint', array($data, $insertIntoProject, $legionella, $rodac), False);                                   
        
        $this->sample = upa('samples', 'fetch', array($this->sampleId), False);

        $this->projectId = $this->sample['project'];
    }

    private function _addAssay($data)
    {

        if(empty($this->sampleId))
        {
            return; 
        }     


        $tipId = upa('assays', 'fetchTip', array($data->assay_base), False);
        $assay = upa('assays', 'fetch', array($tipId), False);
                
        $this->assay = $assay;

        $adata = new stdClass();        
        $adata->assay = $assay; 
        $adata->dillution = $data->dillution;
        $adata->replicates = $data->replicates; 
        $adata->sample = $this->sample;

        $newSAIDS = upa('samples', 'fakerAddNewResearch', array($adata), False);

        array_push($this->loadedSAIDS,  $newSAIDS);
        $this->addSAIDinfo($newSAIDS);

    }


    private function _addProfile($data)
    {
        if(empty($this->sampleId))
        {
            return; 
        }     

        $tip = upa('researchProfiles', 'fetchProfileTip', array($data->profile), False);
        $profile = upa('researchProfiles', 'fetch', array($tip), False);

  
        $adata = new stdClass();        
        $adata->profile = $profile['id'];         
        $adata->sample = $this->sample;

        $newSAIDS = upa('samples', 'fakerAddNewResearch', array($adata, True), False);

        array_push($this->loadedSAIDS,  $newSAIDS);
        $this->addSAIDinfo($newSAIDS);

    }


    private function _setResult($data)
    {
        

        $tipId = upa('assays', 'fetchTip', array($data->assay_base), False);

        $resultId = upa('results', 'findResultId', array($this->sample['id'], $tipId, $data->dillution, $data->rep ), False);

        if($resultId)
        {
            $_POST = [
                'dbRid' => $resultId,
                'fieldName' => $data->parameter, 
                'fieldValue' => $data->value
            ];

            upa('results', 'resultsHandler', array(True), False );
            
        }
    
    }

    private function _setConfirmation($data)
    {


        $tipId = upa('assays', 'fetchTip', array($data->assay_base), False);

        $said = checkKeyOrFalse($this->saidInfo, $tipId);

        if(!$said)
        {
            return;
        }
        
        if($data->setting == true)
        {

            upa('sampleAnalysis', 'setConfFlag', array(1, $said['id'] , True), False);

        }

        else
        {
            upa('sampleAnalysis', 'setConfFlag', array(2,  $said['id'], True ), false);
        }

        
    }    

    private function _setConfirmationData($data)
    {        

        $tipId = upa('assays', 'fetchTip', array($data->assay_base), False);

        
        $said = checkKeyOrFalse($this->saidInfo, $tipId);

        
        if(!$said)
        {            
            return;
        }        

        //call on confviewer to create initial db structure        
        upa('confirmations', 'confirmationViewer', array( $said['id'], $data->dillution, $data->rep, True, 3 ), False);
        

        // 0 = global 1 = per unit

        $saidInfo = upa('sampleAnalysis', 'fetchById', array($said['id']));
        $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));
        
        $availableFieds = json_decode($assayInfo['confirmation_script'], True);
        $availableSupport = json_decode($assayInfo['confirmation_support'], True);
        $activeArray = array();
      
        //fields
        if(is_array($availableFieds))
        {
            foreach($availableFieds as $idx => $thisField)
            {
                $mediaN = $thisField['mediaId'];
                $chainN = $thisField['chainId'];            
                $date =  date('d-m-Y');

                $auxFields = [
                    'inzet' => 
                        ['namefull' => 'Inzetdatum', 'value' => $date], 
                    'aflees' => 
                        ['namefull' => 'Afleesdatum','value' => $date],                  
                    'poscontrol' =>
                        ['namefull' => 'Positief controle', 'value' => '+'],
                    'negcontrol' => 
                        ['namefull' => 'Negatieve controle', 'value' => '-'],
                    'blankcontrol' => 
                        ['namefull' => 'Blanko controle', 'value' => '0'],
                    'tht' => 
                        ['namefull' => 'THT-datum', 'value' => $date]
            
                ];

                foreach($auxFields as $name => $fieldData)
                {

                    $_POST = [
                        "said" => $said['id'], 
                        "dF" => $data->dillution, 
                        "rep" => $data->rep, 
                        "globalConf" => ($data->dillution == 'global') ? 1 : 0 , 
                        "chainN" => $chainN, 
                        "value"  => $fieldData['value'],
                        "placeholder"  =>  $fieldData['namefull'], 
                        "mediaId" => $mediaN, 
                        "disposition" =>  $name,
                        "field" => $mediaN . '_' . $name
                    ]; 
                
                    upa('confirmations', 'saveField', array(), False);
                    
                    if($name == 'tht')
                    {
                        upa('assuranceForms', 'updateExpiryDate', array($said['id'], $chainN,  $fieldData['value'], $mediaN, True), False);
                    }
                }

                //set contenders 
                
                $settings = (isset($data->setting->$idx) ? $data->setting->$idx : False);

                if($settings == false)
                {                
                    return;
                }

                foreach($settings as $contender => $contenderVal)
                {
                    $_POST = [
                        "said" => $said['id'], 
                        "dF" => $data->dillution, 
                        "rep" => $data->rep, 
                        "globalConf" => ($data->dillution == 'global') ? 1 : 0 , 
                        "chainN" => $chainN, 
                        "value"  => $contenderVal,
                        "placeholder"  =>  "+/-", 
                        "mediaId" => $mediaN, 
                        "disposition" =>  $contenderVal,
                        "contender" => $contender,
                        "field" => 'contender'
                    ]; 
                
                    upa('confirmations', 'saveField', array(), False);
                }
                                                                                                                                                                                                
            }
        }
        
        if(is_array($availableSupport))
        {
            foreach($availableSupport as $idx => $thisField)
            {
                $mediaN = $thisField['mediaId'];
                $chainN = $thisField['chainId'];            
                $date =  date('d-m-Y');

                $_POST = [
                    "said" => $said['id'], 
                    "dF" => $data->dillution, 
                    "rep" => $data->rep, 
                    "globalConf" => ($data->dillution == 'global') ? 1 : 0 , 
                    "chainN" => $chainN, 
                    "value"  => $date,
                    "placeholder"  =>  "THT-datum", 
                    "mediaId" => $mediaN, 
                    "disposition" =>  'tht',                    
                    "field" => $mediaN . '_tht'
                ]; 
            
                upa('confirmations', 'saveField', array(), False);
                upa('assuranceForms', 'updateExpiryDate', array($said['id'], $chainN,  $date, $mediaN, True), False);
            }
        }
        
        
        upa('confirmations', 'confirmationViewer', array( $said['id'], $data->dillution, $data->rep, True ), False);
              
    }

    

    private function _assert($data)
    {

        $tipId = upa('assays', 'fetchTip', array($data->assay_base), False);
      
        //assay_base
        $said = checkKeyOrFalse($this->saidInfo, $tipId);


        
        if(!$said)
        {
            $this->failed = True;                
            $this->errors++;
            $this->_log('Could not find SAID, tried to lookup: ' . $data->assay_base . ' giving up this assertion', ['black', 'yellow']);

            $saids = parray($this->saidInfo, true);
            $this->_log('SAID array' . $saids, ['black', 'yellow'] );

            return False;
        }        

        
        $peekOutput = upa('results', 'peek', array($said['id'], True), False);
        $output = $peekOutput['results'];

        $inspectValue = checkKeyOrBlank($output, $data->key, $data->param);                
        $isNonConfirmed = $this->isNonConfirmed($inspectValue);
        $ncInspectValue = $this->stripTags($inspectValue);        
        $isIndicative = $this->isIndicative($ncInspectValue);
        $isGreaterThan = $this->isGreaterThan($ncInspectValue);
        $isLessThan = $this->isLessThan($ncInspectValue);        
        $trimmedEndResult = $this->trimWhiteSpaces($inspectValue);        

        switch($data->assertion)
        {

            case 'see_strict':


                $this->_log('Assert see_strict with the following value: ' . $inspectValue );

                if(strpos($trimmedEndResult,  $data->value) !== false) 
                {
                    $this->_log('Geslaagd: Str-pos value: ' . strpos($trimmedEndResult,  $data->value) );
                    return True;
                }
                
                $assertion =  'Bewering [see_strict] mislukt! Geinspecteerde eind-resultaat waarde [' . $inspectValue . '], getrimmed resultaat [' . $trimmedEndResult . '], had verwacht te zien in resultaat [' . $data->value . ']';
                
                $this->_log($assertion, ['black', 'yellow']);

                $assertion2 = 'Informatie voor handmatige evaluatie, barcode voor dit test monster: ' . $this->sample['barcode']; 
                $this->_log($assertion2, ['black', '#efefef']);
                $this->failed = True;                
                $this->errors++;
                return False; 

            break;
            
            case 'see':

                $this->_log('Assert see_strict with the following value: ' . $inspectValue );
                
                if(strpos($trimmedEndResult,  $data->value) !== false) 
                {
                    $this->_log('Geslaagd: Str-pos value: ' . strpos($trimmedEndResult,  $data->value) );
                    return True;
                }
                
                $assertion =  'Bewering [see] mislukt! Geinspecteerde eind-resultaat waarde [' . $inspectValue . '], getrimmed resultaat [' . $trimmedEndResult . '], had verwacht te zien in resultaat [' . $data->value . ']';
                
                $this->_log($assertion, ['black', 'yellow']);

                $assertion2 = 'Informatie voor handmatige evaluatie, barcode voor dit test monster: ' . $this->sample['barcode']; 
                $this->_log($assertion2, ['black', '#efefef']);
                $this->failed = True;                
                $this->errors++;
                return False; 

            break;
            
            case 'indicative':

               $value =  filter_var($data->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if($value === $isIndicative )
                {
                    return True; 
                }
                
                $assertion =  'Bewering [indicative] mislukt! Had verwacht indicatief ["' . $value . '"] te zien, maar was niet het geval. ';
                $this->_log($assertion, ['black', 'yellow']);

                $assertion2 = 'Informatie voor handmatige evaluatie, barcode voor dit test monster: ' . $this->sample['barcode']; 
                $this->_log($assertion2, ['black', '#efefef']);


                $this->failed = True;           
                $this->errors++;                     
                return False; 

                
            break;

            case 'nonconfirmed':

                $value =  filter_var($data->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if($value === $isNonConfirmed )
                {
                    return True; 
                }
                
                $assertion =  'Bewering [nonconfirmed] mislukt! Had verwacht bevestigd ["' . $value . '"] te zien, maar was niet het geval. ';
                $this->_log($assertion, ['black', 'yellow']);

                $assertion2 = 'Informatie voor handmatige evaluatie, barcode voor dit test monster: ' . $this->sample['barcode']; 
                $this->_log($assertion2, ['black', '#efefef']);

                $this->failed = True;           
                $this->errors++;                     
                return False; 
            
            break; 
    
        }
    
    }

    private function _registerInnoc()
    {
        if(empty($this->sampleId))
        {
            return; 
        }

        upa('samples', 'registerInnoculation', array($this->sample['barcode'], True), False);
                
    }

    private function _log($msg, $clr = ['#000000', '#FFFFFF'])
    {
        if(is_array($msg))
        {
            $msg = parray($msg, True);
        }

        $thisMsg = generateHTML('testlog', ['msg' => $msg, 'fontcol' => $clr[0], 'backcol' => $clr[1] ]);          
            
        array_push($this->log, $thisMsg);
    }

    

    private function isLessThan($composite){
        $composite = $this->stripTags($composite);
        if (strpos($composite, '<') !== false) {
          return True;
        } else{
          return False;
        }
      }
    
    private function isGreaterThan($composite){
        $composite = $this->stripTags($composite);
        if (strpos($composite, '>') !== false) {
          return True;
        } else{
          return False;
        }
      }
    
    private function isIndicative($composite){        
        if (strpos($composite, '*') !== false) {
          return True;
        }
        return False;
    }

    private function isNonConfirmed($composite){        
        if (strpos($composite, '**') !== false) {
          return True;
        }
        return False;
    }
      
    
    private function stripTags($input){
            $rep = array();
            $rep[0] = '<sup>';            
            $rep[1] = '</sup>';
            $rep[2] = '**';
            $replaces = str_replace($rep,  '',   $input);
            return trim($replaces);
    }

    private function trimWhiteSpaces($input)
    {
            $rep = array();
            $rep[0] = '<sup>';            
            $rep[1] = '</sup>';
            $rep[2] = ' ';
            $rep[3] = '*';            
            $replaces = str_replace($rep,  '',   $input);
            return trim($replaces);
    }
  

}