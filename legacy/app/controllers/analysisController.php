<?PHP

class analysisController extends controller{
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');                
    }

    
    
    function listing(){
             
        $availableAnalysis = $this->Analysis->listAnalysis();
        
        if(empty($availableAnalysis)){
            $values = '{MESA_ALS_NONE}';
        } else {
            $values = $availableAnalysis;
        }        
                
        $tF = new tableFactory();
        $tF->loadTemplate('analysisListing');
        $tF->loadValues($values);        
        
        $tF->specifyMod('added_by', 'getUserName', array(ALPC_TF_SELF));
        
        $tF->specifyMod('added_date', 'date', array('d-m-Y', ALPC_TF_SELF));
        
        $this->_template->set('available_analysis_listing', $tF->renderTable());
                
    }
    
    function add(){
        
        $addForm = new formFactory($this->_controller);
                
        $addForm->setId('addAnalysisForm');
        $addForm->action('{LB}/analysis/addSubmit/');
        $addForm->method('POST');
        
        $addForm->addClass('form-horizontal');       
        $addForm->setTemplate('generic');
        
        $addForm->addInputField('name', 'Analysis name', 'text', 'input-block-level', '', 'Enter a name for the analysis here', False);                
        $addForm->addValidation('name', 'NO_DUPLICATE');        
        
        $analyticals = performAction('analyticals', 'analyticalListArray', array());
        $addForm->addDropdownField('base_analytical', 'Based on analytical', 'input-block-level', '', $analyticals, False);
        
        $addForm->addInputField('description', 'Analysis description', 'text', 'input-block-level', '', 'Enter a clear description here', False);        
        $addForm->addValidation('description', 'NOT_EMPTY');
        
        $addForm->addInputField('added_by', False, 'hidden', 'hidden', getUserId(), '' , False);
        $addForm->addInputField('added_date', False, 'hidden', 'hidden', time(), '' , False);
                                        
        $addForm->addButton('submitButton', 'icon-plus-sign', False, 'btn btn-primary btn-small', 'Add analysis', False);        
        $addForm->submitTrough('submitButton');
                                
        $this->_template->set('addAnalysisForm', $addForm->render());                
        
    }
    
    function addSubmit(){        
        $this->render = 0;
        $this->Analysis->postToModel();
        $this->Analysis->save();                
        $this->reRoute('Analysis/listing');        
    }
    
    function edit($id){
        
        $this->Analysis->id = $id;
        $results = $this->Analysis->search();
        $this->Analysis->arrayToModel($results[0]);                
        
        
        /* Existing tests table */
        $tF = new tableFactory();        
        $existingTests = performAction('analysisTests', 'get', array(1=>$id));                        
        if(empty($existingTests)){
            $existingTests = '{MESA_ALT_NONE}';
        }
        
        $tF->loadTemplate('analysisTestListing');
        $tF->loadValues($existingTests);                 
        $listTable = $tF->renderTable();
        unset($tF);
        
        
        /* Add Test Form */        
        $addForm = new formFactory('analysisTests');
        
        $addForm->setId('addTestForm');
        $addForm->submitTrough('submitAddTest');        
        $addForm->action('{LB}/analysisTests/addSubmit/');
        $addForm->method('POST');
        $addForm->setTemplate('generic');        
        $addForm->addInputField('name', 'Test Name', 'text', 'input-block-level', '', 'Add a test name', False);
        $addForm->addValidation('name', 'NO_DUPLICATE', 'analysis_id', $this->Analysis->id );        
        $addForm->addDropdownField('dillution_use', 'Dillutions', 'input-block-level', False, array('0' => 'No', '1' => 'Yes'), False);                                    
        $addForm->addInputField('dillution_steps', 'Dillution steps', 'text', 'input-block-level', '', 'Dillution step in log', False);        
        $addForm->addValidation('dillution_steps', 'ONLY_NUM');        
        $addForm->addInputField('dillution_number', 'Number of dillutions', 'text', 'input-block-level', '', 'Number of dillutions', False);
        $addForm->addValidation('dillution_number', 'ONLY_NUM');        
        $addForm->addInputField('dillution_start', 'Dillution start', 'text', 'input-block-level', '', 'Start dillution at', False, '1&times;10&circ;');
        $addForm->addValidation('dillution_start', 'ONLY_NUM');
        $addForm->addDropdownField('replicates_use', 'Replicates', 'input-block-level', False, array('0' => 'No', '1' => 'Yes'), False);                
        $addForm->addInputField('replicates_number', 'Number of replicates', 'text', 'input-block-level', '', 'Number of replicates', False);
        $addForm->addValidation('replicates_number', 'ONLY_NUM');        
        $addForm->addInputField('tags', 'Description Tags', 'text', 'input-block-level', '', 'Enter search terms (separated by semi-colons)', False);        
        $addForm->addInputField('analysis_id', '', 'hidden', 'hidden', $this->Analysis->id, False, False);
        $addForm->addInputField('base_analytical', '', 'hidden', 'hidden', $this->Analysis->base_analytical, False, False);
                        
        /*result fields */        
        $availableFields = pa('resultFields', 'getResultFieldList', array($this->Analysis->id)); 

        if(empty($availableFields)){
            $availableFields = '{MESA_RFL_NONE}';
        } else{
                        
            foreach($availableFields as $arrIndex => $thisField){
                if($thisField['apply_to'] == 0){
                    $availableFields[$arrIndex]['apply_text'] = 'All';
                }
            }
            
        }
                        
        $tF = new tableFactory();        
        $tF->loadTemplate('resultFieldListing');
        $tF->loadValues($availableFields);         
        
        $resFieldList = $tF->renderTable();
        
                        
        $this->_template->set('resultfields_list', $resFieldList );
        
        /* Set template */                                       
        $this->_template->set('name', $this->Analysis->name );
        $this->_template->set('id', $this->Analysis->id);
        $this->_template->set('addTestForm', $addForm->render());
        $this->_template->set('availableTestsTable', $listTable);
        
                                
    }   
    
    function generateAnalysisList(){
        
        $this->render = 0;       
        $results = $this->Analysis->search();                
        
        $html = '';
        
        foreach($results as $analysis){            
            
            //fetch tests within this analysis
            $thisTests = performAction('analysisTests', 'get', array(0=>$analysis['id']));
            $numberOfTests = count($thisTests);
            
            //generate 
            $innerAccordion = '';
            foreach($thisTests as $test){
                $innerAccordion .= generateHTML('testsLineAcc', $test);
            }
            
            //create accordion section
            $analysis['noOfTests'] = $numberOfTests;
            $analysis['testsSection'] = $innerAccordion;
            
            $thisAnalysis = generateHTML('analysisAccordion', $analysis );
            
            $html .= $thisAnalysis;            
        }
                                
        return $html;        
    }
    
    function fetchOne($analysisId){        
        $this->Analysis->id = $analysisId;
        $result = $this->Analysis->search();        
        return $result;        
    }
    
    function changeName(){
        
        $newName = $_POST['name'];
        $anaId = $_POST['id'];
        
        $this->Analysis->id = $anaId;
        $this->Analysis->name = $newName;
        $this->Analysis->save();     
    }
    
    function removeAnalysis($analysisId){
        
    }
    
    function hideAnalysis($analysisId){
        
    }
 
    function derp(){
        
   //need to retain at least 2 dishes of which one at least 10
        $retained = array();
        $firstRetained = False;
        $indicative = False;
        $noRetained = 0;
        $overMax = False;
        
        foreach($results as $vF => $replicas){            
            foreach($replicas as $repId => $plaat){
                
                $countRetained = count($retained);
                if($countRetained == 2){
                    continue;
                }
                
                if($plaat['kve'] > $countable_maximum){
                    $overMax = True;
                    continue;
                }
                
                if($plaat['kve'] < $countable_maximum){
                    if($firstRetained == False) {
                         $firstRetained = $vF;
                    }
               
                    $retained[$vF] = $plaat['kve'];
                    $noRetained++;                    
                }
                
                if($plaat['kve'] < $countable_minimum){
                    $indicative = True;
                }
                                
            }            
        }
        
        $sumC = 0;
        foreach($retained as $dF => $count){
            $sumC = $sumC + $count;
        }

        $n = $sumC / ( 1 * 1.1 * $firstRetained);
        $nRound = round($n, -3);
                
                
        
        if($indicative == True){
            $output['kve'] = '*' . $nRound;
        } else {
            $output['kve'] = $nRound;
        }
        
    }
    
}