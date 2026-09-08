<?PHP

class flowComponentsController extends controller{
 
    const SAMPLE = 'SAMPLE';
    const ANALYSIS = 'ANALYSIS';
    const MATH = 'MATH';
    const ISOCOUNT = 'ISOCOUNT';
    
    const FLOW_START = 'FLOW_START';
    const FLOW_END = 'FLOW_END';
    const SAMPLE_CONNECTOR = 'SAMPLE_CONNECTOR';
    const DIRECTION_OUT = 'OUT';
    const DIRECTION_IN = 'IN';
    
    function getSampleOrigin($flowId){        
        $this->render = 0;
        $this->FlowComponent->where('flow', $flowId);
        $this->FlowComponent->where('type', self::FLOW_START );
        $result = $this->FlowComponent->search();
        return $result[0];       
    }
    
    function getFlowEndResults($flowId){
        $end = $this->getFlowEnd($flowId);                         
        $endConnex = $this->_getComponentInputs($flowId, $end['id']);        
        return $endConnex;        
    }
    
    function getFlowEnd($flowId){
        $this->render = 0;
        $this->FlowComponent->where('flow', $flowId);
        $this->FlowComponent->where('type', self::FLOW_END );
        $result = $this->FlowComponent->search();
        return $result[0];       
    }
    
    function getComponentInfo($componentId){                
        $this->FlowComponent->free();
        $this->FlowComponent->where('id', $componentId);
        $result = $this->FlowComponent->search();        
        return $result[0];
    }
    
    
    function getAllFormulas($flow){
        $this->FlowComponent->where('type', self::MATH);
        $this->FlowComponent->where('flow', $flow);
        $this->FlowComponent->order('level', 'ASC');        
        $results = $this->FlowComponent->search();
        return $results;        
    }
        
     
    function inspector($componentId){
    
        $componentId = substr($componentId, 3);        
        
        $this->doNotRenderHeader = 1;
        //$this->render = 0;
        
        $this->FlowComponent->where('id', $componentId);
        $result = $this->FlowComponent->search();
        $compInfo = $result[0];
        
        //basic info
        $flowInfo = pa('flows','getFlow', array( $compInfo['flow'] ));        
                
        //check what this component receives
        $conIn = pa('flowConnections', 'getConnections', array($compInfo['flow'], $componentId,self::DIRECTION_IN) );
        $conOut = pa('flowConnections', 'getConnections', array($compInfo['flow'], $componentId, self::DIRECTION_OUT) );                
        
        $inputsHtml = '';
        $outputsHtml = '';        
        
        //if this is the beginning (Sample) set no inputs
        if($compInfo['type'] == self::FLOW_START){
            $inputsHtml = generateHTML('flow/noInputsLine', array('message'=> '{MESA_FLW_INPUTS_NO_START}'));
        }
        
        if($compInfo['type'] == self::FLOW_START){
            $outputsHtml = generateHTML('flow/onlyLinkLine', array('message'=> '{MESA_FLW_INPUTS_SENDS_LINK}'));
        }
        
        
        if($compInfo['type'] == self::FLOW_END){
            $outputsHtml = generateHTML('flow/noInputsLine', array('message'=> '{MESA_FLW_END_OF_FLOW}'));
        }
        
        /*
         * INPUTS
         */        
        
        foreach($conIn as $ind=>$input){            
            //find info on the incoming component
            $thisComp = $input['component'];
            $thisCompInfo = $this->getComponentInfo($thisComp);            
            
            //if the linker is the START OF FLOW
            if($thisCompInfo['type'] === self::FLOW_START){
                $inputsHtml = generateHTML('flow/onlyLinkLine', array('message'=> '{MESA_FLW_INPUTS_RECEIVES_LINK}'));
            }
            
            //if this is an analysis, get the input fields for this
            if($thisCompInfo['type'] === self::ANALYSIS){                
                $anaFields = pa('analyticalFields', 'fetchFields', array($thisCompInfo['analytical']));
                foreach($anaFields as $fInd => $anaField){
                    $temp['input_name'] = $anaField['name'] . '(' . $anaField['alias'] . ')';
                    $temp['sender_name'] = $thisCompInfo['name'];
                    $inputsHtml .= generateHTML('flow/compInputLine', $temp);
                    unset($temp);
                }                
            }
            
            //MATH
            if($thisCompInfo['type'] == self::MATH){
                $instructArr = json_decode($thisCompInfo['instructions'], True);
                
                if(!empty($instructArr['math_instructions'])){
                   foreach($instructArr['math_instructions'] as $form_name => $formula){
                        $temp['input_name'] =  $form_name;
                        $temp['sender_name'] = $thisCompInfo['name'];
                        $inputsHtml .= generateHTML('flow/compInputLine', $temp);
                        unset($temp);
                   }
                }
                
            }
            
        }
        
        
        
        /*
         * OUTPUTS
         */      
                        
        foreach($conOut as $ind => $output){
            
            $thisComp = $output['target'];
            $thisCompInfo = $this->getComponentInfo($thisComp);                
        
            if($compInfo['type'] == self::MATH ){                
                $temp['output_name'] = '{MESA_FLW_SUM_ANSWER}';
                $temp['target_name'] = $thisCompInfo['name'];
                $outputsHtml .= generateHTML('flow/compOutputLine', $temp);
                unset($temp);
            }         
            
            if($compInfo['type'] == self::ANALYSIS){
                $temp['output_name'] = '{MESA_FLW_SENDS_FIELDS}';
                $temp['target_name'] = $thisCompInfo['name'];
                $outputsHtml .= generateHTML('flow/compOutputLine', $temp);
                unset($temp);
            }
        }
        
        if(empty($conOut)){
            $outputsHtml = generateHTML('flow/noInputsLine', array('message'=> 'None'));
        }
        
        /*
         *  Instruction window 
         */
        
        
//        $this->_template->set('instructions_window', $instructionContent);        
//        $this->_template->set('outputs_component', $outputsHtml);
//        $this->_template->set('inputs_component', $inputsHtml);
//        $this->_template->set('workflow_name', $flowInfo['name']);
//        $this->_template->set('component_name', $compInfo['name']);
//        $this->_template->set('component_level', $compInfo['level']);
//        $this->_template->set('component_type', $compInfo['type']);
        
        
        //basic info
        $infoWin['component_name'] = $compInfo['name'];
        $infoWin['component_level'] = $compInfo['level'];
        $infoWin['component_type'] = $compInfo['type'];                
        $returnObj['info_window'] = generateHtml('flow/inspectorInfo', $infoWin);
        
        //output window
        $returnObj['outputs_window'] = $outputsHtml;
        
        //setup window if type is math
        $instructionContent = '';        
        
        if($compInfo['type'] === self::MATH){
            $currentInstructions = $this->_loadMathRules($compInfo['instructions']);
            $instructionContent = generateHTML('flow/mathWindowWrap', array('current_instructions' => $currentInstructions, 'comp_id' =>  $componentId) );
        }
        
        if($compInfo['type'] === self::ANALYSIS){
            $anaFields = pa('analyticalFields', 'fetchFields', array($compInfo['analytical']));
            $instructions = json_decode($compInfo['instructions'], True);
            
            
            foreach($anaFields as $fInd => $anaField){
                $anaField['flow_component'] = $compInfo['id'];
                if(array_key_exists($anaField['name'], $instructions['std_values'])){
                    $anaField['std_value'] = $instructions['std_values'][$anaField['name']];
                } else{
                     $anaField['std_value'] = '';
                }
                
                $instructionContent .= generateHTML('flow/assayStandard', $anaField);
            }
        }
        
        if($compInfo['type'] == self::ISOCOUNT){
            
            $incomingCount = $this->_getComponentInputs($compInfo['flow'], $compInfo['id']);
            $instructions = json_decode($compInfo['instructions'], True);
            
            
            $optionArr = '';
                                        
            foreach($incomingCount[0]['fields'] as $countField){                                            
                $optionArr .= '<option  value="' . $countField['name'] .'">' . $countField['alias'] . '</option>';
             }
                                    
            $instructionContent .= generateHTML('flow/isCountInspector', array('fields' => $optionArr, 'value' => $instructions['iso_count']['max_cfu'], 'component_id' => $compInfo['id']));
        }
        
        $returnObj['instruct_window'] = $instructionContent;        
        $this->_template->set('obj', json_encode($returnObj, JSON_FORCE_OBJECT));               
    }
    
    private function _saveStdValue($componentId, $field, $value){
     
        //fetch old ones
        $this->FlowComponent->where('id', $componentId);
        $result = $this->FlowComponent->search();        
        $instructions = json_decode($result[0]['instructions'], True);        
        $instructions['std_values'][$field] = $value;
        
        $this->FlowComponent->id = $componentId;
        $this->FlowComponent->instructions = json_encode($instructions);       
        $this->FlowComponent->save();
    }
    
    function saveStdValue(){
        
        $componentId = $_POST['componentId'];
        $field = $_POST['fieldName'];
        $value = $_POST['newValue'];
        
        $this->_saveStdValue($componentId, $field, $value);
    }
    
    
    private function _loadMathRules($instruct){
        
        $instructionSet = json_decode($instruct, True);
        
        if(empty($instructionSet['math_instructions'])){
            return 'No instructions';
        } else{
            $html = '';
            foreach($instructionSet['math_instructions'] as $name => $formula){
                $html .= generateHTML('flow/mathLine', array('form_name' => $name ) );
            }
        }
       
        return $html;                
    }
    
    function addFlowComponent(){
        $this->render = 0;
        
        $flowId = $_POST['flow'];
        $component = $_POST['component'];
        $level = $_POST['level'];
        $name = $_POST['name'];
                
        $this->FlowComponent->flow = $flowId;
        $this->FlowComponent->name = $name;        
        
        if($component == self::FLOW_START){
            $level = 1;
            $this->FlowComponent->type = self::FLOW_START;
            $this->FlowComponent->permanent = 1;
            $this->FlowComponent->level = 1;
        }
        
        if($component == self::FLOW_END){
            $level = $this->getMaxLevel($flowId) + 1;
            $this->FlowComponent->type = self::FLOW_END;
            $this->FlowComponent->level = $level;
        }
        
        if($component == self::MATH){            
            $instructions['math_instructions'] = array();
            $this->FlowComponent->type = self::MATH;
            $this->FlowComponent->instructions = json_encode($instructions);
            $this->FlowComponent->level = $level;
        }
        
        if($component == self::ISOCOUNT){
            
            $instructions['iso_count']['max_cfu'] = False;
            $instructions['iso_count']['count_field'] = False;
            $instructions['iso_count']['dilution_field'] = False;
            
            $this->FlowComponent->type = self::ISOCOUNT;
            $this->FlowComponent->instructions = json_encode($instructions);
            $this->FlowComponent->level = $level;
        }
      
                
        $this->FlowComponent->save();        
    }
    
    public function addAssayComponent(){
        
        $this->render = 0;
        
        $flowId = $_POST['flow'];
        $component = $_POST['component'];
        $level = $_POST['level'];
        $name = $_POST['name'];
        
        $instructions['std_values'] = array();        
        
        $this->FlowComponent->flow = $flowId;
        $this->FlowComponent->level = $level;
        $this->FlowComponent->type = self::ANALYSIS;        
        $this->FlowComponent->permanent = 0;
        $this->FlowComponent->name = $name;
        $this->FlowComponent->analytical = $component;       
        $this->FlowComponent->instructions = json_encode($instructions);                
        $this->FlowComponent->save();
        
    }
    
    private function getMaxLevel($flowId){
        
        $this->FlowComponent->where('flow', $flowId);
        $this->FlowComponent->order('level', 'DESC');
        $result = $this->FlowComponent->search();        
        return $result[0]['level'];
    }
    
    
    function addStartPoint(){
        
        $this->render = 0;
        $flowId = $_POST['flow'];        
        $this->FlowComponent->level = 1;
        
        $this->FlowComponent->type = self::FLOW_START;
        $this->FlowComponent->permanent = 1;
        $this->FlowComponent->name = 'Sample';        
        $this->FlowComponent->save();      
    }
    
    function loadMathVar(){
        
        $this->render = 0;
        $componentId = $_POST['componentId'];
        $flowId = $_POST['flow'];
        
        $conIn = $this->_getComponentInputs($flowId, $componentId);
        $fields = array();
        
        
        
        foreach($conIn as $ind => $input){           
        
            $thisName = $input['component_info']['name'];
            $thisType = $input['component_info']['type'];
            
            if($thisType == self::ANALYSIS){
                foreach($input['fields'] as $fInd => $field){
                $constName = '{' . $thisName . ':' . $field['name'] . '}';
                $fields[$constName] = $constName;            
                }
            }
            
            if($thisType == self::MATH){
                $instructions = json_decode($input['component_info']['instructions'], True);
                if(!empty($instructions['math_instructions'])){
                    foreach($instructions['math_instructions'] as $formName => $formula){
                        $constName = '{' . $thisName . ':' . $formName . '}';
                        $fields[$constName] = $constName; 
                    }    
                }                
            } 
            
            if($thisType == self::ISOCOUNT){
                $constName = '{' .  $input['component_info']['name'] . '}';
                $fields[$constName] = $constName;
            }
                        
        }
        
        
        $renderedFields = '';
        
        foreach($fields as $option=>$value){
            $renderedFields .=  '<option value="' . $option .'">' . $value . '</option>';
        }
                
        $obj['variables'] = $renderedFields;
        print json_encode($obj, JSON_FORCE_OBJECT);                                
    }
 
    
    function saveMath(){
        
        $this->render = 0;
        
        $component = $_POST['componentId'];
        $flow = $_POST['flow'];
        $formula = trim($_POST['formula']);
        
        $this->FlowComponent->where('id', $component);
        $result = $this->FlowComponent->search();
        
        $instructions = json_decode($result[0]['instructions'], True);
        $currentMath = $instructions['math_instructions'];
        
        //parray($currentMath);
               
        //dont if its empty
        if($formula == ''){            
            return;
        }
        
        //find target field
        $splice = explode('=', $formula);
        $targetField = trim($splice[0]);
                   
        
        if(array_key_exists($targetField, $currentMath)){
            print 'False';
            return;
        } else{
            $currentMath[$targetField] = $splice[1];
        }
        
        $instructions['math_instructions'] = $currentMath;
        $newInstructionSet = json_encode($instructions);
        
        $this->FlowComponent->id = $component;
        $this->FlowComponent->instructions = $newInstructionSet;
        $this->FlowComponent->save();
        
        print 'True';                
    }
    
    function saveISOcount(){
        
        $this->render = 0;
        $component = $_POST['component'];
        $cfuField = $_POST['cfuField'];
        $dilField = $_POST['dilField'];
        $maxCFU = $_POST['maxCFU'];
        
        $instruct['iso_count']['max_cfu'] = $maxCFU;
        $instruct['iso_count']['count_field'] = $cfuField;
        $instruct['iso_count']['dilution_field'] = $dilField;
        
        $this->FlowComponent->id = $component;
        $this->FlowComponent->instructions = json_encode($instruct);
        
        $this->FlowComponent->save();                    
    }
    
    function getComponentInputs($flow, $componentId){
        $this->render = 0;
        return $this->_getComponentInputs($flow, $componentId);
    }
    
     private function _getComponentInputs($flow, $componentId){
                           
        $conIn = pa('flowConnections', 'getConnections', array($flow,$componentId, $componentId,self::DIRECTION_IN) );
        
        foreach($conIn as $ind => $con){
                                  
            //get info for this component
            $component = $this->getComponentInfo($con['component']);                
            $type = $component['type'];
        
            $conIn[$ind]['component_info'] =  $component;
                        
            //add the available incoming fields to the inputs
            if($type == self::ANALYSIS){                                
                $anaFields = pa('analyticalFields', 'fetchFields', array($component['analytical']));
                foreach($anaFields as $fInd => $anaField){
                    $conIn[$ind]['fields'][$fInd] = $anaField;
                }
            }                        
        }
                
        return $conIn;
        
    }
}