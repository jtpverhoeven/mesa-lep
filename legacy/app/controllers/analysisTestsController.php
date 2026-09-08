<?PHP

class analysisTestsController extends Controller{
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');                
    }
    
    function addSubmit(){                
        $this->render = 0;
        $this->AnalysisTest->postToModel();
        $this->AnalysisTest->save();    
        $this->reRoute('analysis/edit/' . $this->AnalysisTest->analysis_id);        
    }
    
    function get($analysisId){        
        $this->render = 0;
        $this->AnalysisTest->where('analysis_id', $analysisId);
        $results = $this->AnalysisTest->search();       
        return $results;                        
    }
    
    function getSingle($testId){
        $this->render = 0;
        $this->AnalysisTest->where('id', $testId);
        $result = $this->AnalysisTest->search();        
        return $result[0];
    }

    function requestPack($currentId, $packId){
        
        $this->render = 0;        
        $testsAdded = 0;
        $packContents = pa('packetsTests', 'getPacketTests', array($packId));
        
        $returnObj['table_html'] = '';
        $returnObj['no_added_analysis'] = $testsAdded;
        
        foreach($packContents as $singleTest){
            
            $currentId = $currentId + 1;
            $testsAdded = $testsAdded + 1;
            
            $this->AnalysisTest->id = $singleTest['test_id'];
            $result = $this->AnalysisTest->search();
            $result[0]['idOnSample'] = $currentId;                                    
            $returnObj['table_html'] .= generateHTML('testsLineReq', $result[0]);       
            $returnObj['no_added_analysis'] = $testsAdded;                        
        }
        
        print json_encode($returnObj, JSON_FORCE_OBJECT);
    }
    
    function request($idOnSample, $testId){
        
        $this->render = 0;
        $this->AnalysisTest->id = $testId;
        $result = $this->AnalysisTest->search();
        
        $result[0]['idOnSample'] = $idOnSample;                
        $html = generateHTML('testsLineReq', $result[0]);        
        print $html;
    }
    
    function generateDropdownOptions(){
        
        $this->render = 0;
        
        $results = $this->AnalysisTest->search();
        
        $dropdown = '';
        if(!empty($results)){
            foreach($results as $test){
                //$dropdown .= '<option value="' . $test['id'] . '">' . $test['name'] . '</option>';
                $dropdown[$test['id']] = $test['name'];
            }
        }                    
       return $dropdown;        
    }
    
    
    function generateInputPanel($sampleAnalysisId, $followNo){
 
        $this->render = 0;
        $focusOnElement = False;
            
        $resultData = performAction('results', 'getTestResults', array(0=>$sampleAnalysisId));
        $analysisDetails = performAction('sampleAnalysis', 'fetchAnalysisInfo', array(0=>$sampleAnalysisId));                  
        
        $isEditable = True;
        if($analysisDetails[0]['auth_status'] == '1' || $analysisDetails[0]['auth_status'] == '2'  ){
            $isEditable = False;
        }
                
        $baseAnalytical = $resultData[0]['analytical_base'];
        $testFields = performAction('analyticalFields', 'fetchFields', array( 0=>$baseAnalytical) );
        $inputPanel = '';
        
        foreach($resultData as $resultLine){
   
                $theseResults = unserialize($resultLine['fields']);
            
                $templatingArray = array();
                $templatingArray['name'] = $analysisDetails['test_info']['name'];
                $templatingArray['dillution'] = $resultLine['dillution'];
                $templatingArray['replicate'] = $resultLine['rep']; 
                $templatingArray['input_controls'] = '';
                //$templatingArray = array_merge($templatingArray, $theseResults);
                                                                                        
                foreach($testFields as $inputField){
                    
                    $renderedControl = 'error';
                    
                    //check if value is in blob, else add it.
                    if(!isset($theseResults[$inputField['name']])){
                        $inputField['value'] = '';
                    }
                    else {
                        $inputField['value'] = $theseResults[$inputField['name']];                    
                    }
                   
                    $inputField['db_id'] = $resultLine['id'];
                                                                                
                    if($followNo == $resultLine['follow_number']){
                        //follow up number found
                        $focusOnElement = $inputField['name'] . '_' . $inputField['db_id'];
                    }
                    
                    //actual field renderings below
                    if($isEditable == False){
                        $inputField['disabled'] = ' disabled ';
                    } else{
                        $inputField['disabled'] = '';
                    }
                    
                    if($inputField['type'] == 'int'){                                                
                        $renderedControl = generateHTML('input_int', $inputField);
                    }
                    
                    $templatingArray['input_controls'] .= $renderedControl;                    
                  
                }     
                
                $inputPanel .= generateHTML('entryLayout', $templatingArray);
        }
        
        /* Generate result fields */
        $resultFieldPanel = array();
        $resultFieldPanel['result_fields'] = '';
        $resFields = pa('resultFields', 'getResultFields', array($analysisDetails['test_info']['analysis_id'], $analysisDetails['test_info']['id'] ) );        
                
        foreach($resFields as $thisField){                        
            $thisField['current_result'] = pa('resultFields', 'computeField', array($thisField['id'], $sampleAnalysisId, $analysisDetails['test_info']['analysis_id'] ) );
            $resultFieldPanel['result_fields'] .= generateHTML('input_result', $thisField);
        }
        
        $inputPanel .= generateHTML('resultFieldWrapper', $resultFieldPanel);        
        $returnTupp = array ('input_panel' => $inputPanel, 'focus_element' => $focusOnElement);
        
        //return $inputPanel;
        return $returnTupp;        
    }    
                        
    function findByTags($query){
        
        $this->render = 0;
        
        $tagArray = explode(';', $query);        
        
        foreach($tagArray as $tag){
            $this->AnalysisTest->like('tags', trim($tag));
        }
        
        $results = $this->AnalysisTest->search();
        
        return $results;
        
    }    
    
    function editSubmit(){
        
        $this->AnalysisTest->arrayToModel($_POST);
        
        $this->AnalysisTest->save();       
        $this->reRoute('Analysis/edit/' . $this->AnalysisTest->analysis_id, True);        
    }
    
    function edit($testId){
        
        $this->AnalysisTest->where('id', $testId);
        $result = $this->AnalysisTest->search();
        $this->AnalysisTest->arrayToModel($result[0]);
        
        $this->_template->set('name', $this->AnalysisTest->name);
        $this->_template->set('analysis_id', $this->AnalysisTest->analysis_id);
        
        $addForm = new formFactory($this->_controller);        
        $addForm->setId('editTestForm');
        $addForm->submitTrough('submitEditTest');                
        $addForm->action('{LB}/analysisTests/editSubmit/');
        
        
        $addForm->method('POST');
        $addForm->setTemplate('generic');        
        
        $addForm->addInputField('name', 'Test Name', 'text', 'input-block-level', $this->AnalysisTest->name, 'Add a test name', False);    
        
        $addForm->addDropdownField('dillution_use', 'Dillutions', 'input-block-level', $this->AnalysisTest->dillution_use, array('0' => 'No', '1' => 'Yes'), False);                                            
        $addForm->addInputField('dillution_steps', 'Dillution steps', 'text', 'input-block-level', $this->AnalysisTest->dillution_steps, 'Dillution step in log', False);                
        $addForm->addValidation('dillution_steps', 'ONLY_NUM');        
        $addForm->addInputField('dillution_number', 'Number of dillutions', 'text', 'input-block-level', $this->AnalysisTest->dillution_number, 'Number of dillutions', False);
        $addForm->addValidation('dillution_number', 'ONLY_NUM');        
        $addForm->addInputField('dillution_start', 'Dillution start', 'text', 'input-block-level', $this->AnalysisTest->dillution_start, 'Start dillution at', False, '1&times;10&circ;');
        $addForm->addValidation('dillution_start', 'ONLY_NUM');
        $addForm->addDropdownField('replicates_use', 'Replicates', 'input-block-level', $this->AnalysisTest->replicates_use, array('0' => 'No', '1' => 'Yes'), False);                
        $addForm->addInputField('replicates_number', 'Number of replicates', 'text', 'input-block-level', $this->AnalysisTest->replicates_number, 'Number of replicates', False);
        $addForm->addValidation('replicates_number', 'ONLY_NUM');        
        $addForm->addInputField('tags', 'Description Tags', 'text', 'input-block-level', $this->AnalysisTest->tags, 'Enter search terms (separated by semi-colons)', False);        
        $addForm->addInputField('analysis_id', '', 'hidden', 'hidden', $this->AnalysisTest->analysis_id, False, False);
        $addForm->addInputField('base_analytical', '', 'hidden', 'hidden', $this->AnalysisTest->base_analytical, False, False);
        $addForm->addInputField('id', '', 'hidden', 'hidden', $this->AnalysisTest->id, False, False);
        
        $this->_template->set('analysis_test_form', $addForm->render());
    }
    
   function generateDillutionArray($id){
       
       $this->AnalysisTest->where('analysis_id', $id);
       $result = $this->AnalysisTest->search();              

       $dillutions = array();
       
       foreach($result as $foundTest){
           
           $this->AnalysisTest->arrayToModel($foundTest);
           for($i = 0; $i < $this->AnalysisTest->dillution_number; $i++ ){           
                $thisDillution = $this->AnalysisTest->dillution_start + ( $i * $this->AnalysisTest->dillution_steps);            
                $base = 1 * 10;
                $exp =  -1 * abs($thisDillution);            

                $dill_num = floatval(pow($base, $exp));
                
                $dill_text = "1x10^" . $exp;
                $dillutions['' .$dill_num]  =  $dill_text;           
            }
       }
   
       return $dillutions;
   }
    
    
}