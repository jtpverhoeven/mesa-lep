<?PHP

class resultFieldsController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');                
    }    
    
    
    function edit($analysis, $id = False){
        
        //existing constants
        $constantsAvail = pa('fieldConstants', 'fetchConstants', array($analysis));
        
        if(empty($constantsAvail)){
            $constantsAvail = '{MESA_CST_NONE}';
        }     
                
        $tF = new tableFactory();
        $tF->setTableId('constants_table');
        $tF->loadTemplate('constantsListing');
        $tF->loadValues($constantsAvail);                
        $this->_template->set('available_constants', $tF->renderTable());       
        
        
        //edit
        $addForm = new formFactory($this->_controller);        
        $addForm->setId('editResultFieldForm');
        $addForm->submitTrough('saveResultFieldButton');                
        $addForm->action('{LB}/resultFields/saveField');                       
        
        //check if we are editing or creating new one
        if($id !== False && $id !== ''){
          $this->ResultField->where('id', $id);
          $result = $this->ResultField->search();                  
          
          if(!empty($result)){
            $this->ResultField->arrayToModel($result[0]);
            $addForm->addInputField('id', '', 'hidden', 'hidden', $this->ResultField->id, False, False);
          }           
        } 
        
        
        $analysisDetails = performAction('analysis', 'fetchOne', array($analysis));        
                       
        $addForm->method('POST');
        $addForm->setTemplate('generic');        
        
        $addForm->addInputField('name', 'Result field name', 'text', 'input-block-level', $this->ResultField->name, 'Add a result field name', False);        
        
        //load tests
        $availableTests = pa('analysisTests', 'get', array($analysis));        
        foreach($availableTests as $thisTest){
            $applyOptions[$thisTest['id']] = '{MESA_RFL_ONLYFOR}: ' . $thisTest['name'];
        }
        $applyOptions['0'] = 'All tests';                        
                       
        $addForm->addDropdownField('apply_to', 'Use for', 'input-block-level', $this->ResultField->apply_to, $applyOptions, False);                                                    
        $addForm->addTextArea('formula', 'Formula', '', 'textarea-block-level', $this->ResultField->formula, 'Enter instructions', array('rows'=>2));               
        $addForm->addInputField('analysis', '', 'hidden', 'hidden', $analysis, False, False);

        $limitOptions['0'] = 'None';
        $addForm->addDropdownField('limit_applied', 'Use limit', 'input-block-level', $this->ResultField->limit_applied, $limitOptions, False);                                                    
               
        $this->_template->set('add_resultfield_form', $addForm->render());                
        $this->_template->set('analysis_id', $analysis);
        
        
        //add constant form        
        $addConstant = new formFactory($this->_controller);
        $addConstant->setId('addConstantForm');
        $addConstant->submitTrough('submitAddConstant', 'submitConstant');                
        $addConstant->action('{LB}/fieldConstants/addConstant');
        $addConstant->method('POST');
        $addConstant->setTemplate('generic');        
        
        
        $addConstant->addInputField('const_name', 'Constant name', 'text', 'input-block-level', False, 'Add a name for the new constant', False);        
        $constTypes['1'] = 'Constant set value';
        $constTypes['2'] = 'Value entered by user';

        $addConstant->addDropdownField('const_type', 'Constant type', 'input-block-level', False, $constTypes, False);                                                           
        $addConstant->addInputField('const_value', 'Constant value', 'text', 'input-block-level', False, 'Attach a value to the constant', False);                
        
        $pollCols = pa('analyticalFields', 'fetchFieldsOptions', array($analysisDetails[0]['base_analytical']));
        $addConstant->addDropdownField('poll_col', 'Poll column', 'input-block-level', False, $pollCols, False);                                                                   
    
        $operations['1'] = 'Get value';
        $operations['2'] = 'Get average value of all replicates';
        
        
        $addConstant->addDropdownField('operation', 'Operation type', 'input-block-level', False, $operations, False);                                                                   
        
        $restrictors['0'] = 'Of field (no dillution)';
        $restrictors['1'] = 'Lowest dillution';
        $restrictors['2'] = 'Highest dillution';
        $restrictors['3'] = 'Specific dillution';        
        $addConstant->addDropdownField('restrictors', 'Restriction type', 'input-block-level', False, $restrictors, False);                                                                   
        
        $dillArr = pa('analysisTests', 'generateDillutionArray', array($analysis) );
        
        $addConstant->addDropdownField('restrictor_dill', 'Dillution', 'input-block-level', False, $dillArr, False);                                                                   
        
        $addConstant->addInputField('analysis', '', 'hidden', 'hide', $analysis, NULL, NULL, NULL);
        
        $this->_template->set('add_constant_form', $addConstant->render());
        
    }
    
    function saveField(){        
        $this->ResultField->arrayToModel($_POST);
        $this->ResultField->save();        
    }
    
    function getResultFieldList($analysisId){
        
        $this->render = 0;
        $this->ResultField->where('analysis', $analysisId);
        $results = $this->ResultField->search();        
        return $results;        
    }
    
    function getResultFields($analysisId, $testId){
        
        $this->render = 0;
        $fields = array();
        
        $this->ResultField->where('analysis', $analysisId);                
        
        $this->ResultField->where('apply_to', '0');                
        $globalTestFields = $this->ResultField->search();        
        
        if(!empty($globalTestFields)){
            foreach($globalTestFields as $test){
                $fields[$test['id']] = $test;
            }
        }
        
        $this->ResultField->where('apply_to', $testId);        
        $specificTestFields = $this->ResultField->search();
        
        if(!empty($specificTestFields)){
            foreach($specificTestFields as $test2){
                $fields[$test2['id']] = $test2;
            }
        }
        
        return $fields;                   
    }
    
    function computeField($resultField, $sampleAnalysis, $analysisId){
              
        //set the result field info
        $this->ResultField->where('id', $resultField);
        $fFetch = $this->ResultField->search();
        $this->ResultField->arrayToModel($fFetch[0]);
       
        $constArray = pa('fieldConstants', 'constantsArray', array($analysisId, $sampleAnalysis));
        $formula = $this->_replace($this->ResultField->formula, $constArray);
        $result = $this->_runMath($formula);
        
        return $result;
        
    }
    
    private function _replace($str, $arr){
        
        if (preg_match_all("!\{(\w+)\}!", $str, $matches)) {                        
            foreach ($matches[0] as $hitId => $hitTag) {
                $deCurledTag = $matches[1][$hitId];
                $tagExists = array_key_exists($deCurledTag, $arr);
                
                if ($tagExists === True) {
                    $tagContent = $arr[$deCurledTag];   // dump tag content here
                } else {                    
                    $tagContent = '';  
                }
         
                $str = str_replace($hitTag, $tagContent, $str, $noReps);                
            }
         }
        return $str;
    }
    
    
    private function _runMath($instructions){
        
        //instanciate EOS
        $eos = new eqEOS();
         
        //hack, remove this @ at some point by error checking
        @$result = $eos->solveIF($instructions, null);
    
    
        //set result array
        unset($eos);  
        return $result;
                                         
    }
    
}