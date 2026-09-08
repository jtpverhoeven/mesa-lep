<?PHP

class flowsController extends controller{
    
    const RESULT_LINK_PREFIX = 'result_link_';
    
    const SAMPLE = 'SAMPLE';
    const ANALYSIS = 'ANALYSIS';
    const MATH = 'MATH';
    const ISOCOUNT = 'ISOCOUNT';
    
    const FLOW_START = 'FLOW_START';
    const FLOW_END = 'FLOW_END';
    const SAMPLE_CONNECTOR = 'SAMPLE_CONNECTOR';
    
    private $_components = array();    
    private $_origin = array();
    
    private $_receives = array();
    
    //solving
    private $_sampleResultsName = array();
    private $_sampleResultsComponent = array();   
    private $_sampleMathResults = array();
    private $_eqEOS;
    
   // private $_clickElements = array();
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }
    
    function gatherFlowConnections($flowId){                        
        //get all the components in the flow
                
        $foundComponents = $this->_gatherFlowComponents($flowId);
        
        if(empty($foundComponents)){
            return;
        }
        
        $this->_components = $this->_shiftComponentsArray($foundComponents);    
        //find level 1 for the flow
        $this->_origin = pa('flowComponents', 'getSampleOrigin', array($flowId));                                                                       
    }

    
    function generateDropdownOptions(){
        
        $this->render =0;
        $results = $this->Flow->search(); 
        
        $flowArr = array();
        
        if(!empty($results)){
            foreach($results as $flowInd=>$flowInfo){
                $flowArr[$flowInfo['id']] = $flowInfo['name']; 
            }
        }
        
        return $flowArr;  
    }
    
    /*
     * Bits for flow designer
     */
    
    function reloadSVG(){
        $this->render = 0;
        
        $flowId = $_POST['flow'];
        $this->gatherFlowConnections($flowId); 
        $flowVisual = $this->drawFlow($flowId);        
        
        print $flowVisual;
    }
    
    function edit($flowId){
        
        //set up the flow
        $this->gatherFlowConnections($flowId);                        
        
        //make the visualness
        $flowVisual = $this->drawFlow($flowId);        
        
        //setup available assay blocks
        $availableAna = pa('analyticals', 'listAnalyticals', array() );
        $listGroup = '';
        foreach($availableAna as $ind=>$analytical) {
            $listGroup .= generateHTML('flow/availableAnaLine', $analytical);
        }
     
        
        
        $this->_template->set('svg_content', $flowVisual);
        $this->_template->set('flow_id', $flowId);
        $this->_template->set('analyticals', $listGroup);
        
    }
    
    function drawFlow($flowId){
        
        $flowSVG = new flowSVG();        
        
        //draw components
        foreach($this->_components as $id=>$component){                        
                                         
            if($component['type'] == self::FLOW_START){
                $flowSVG->addAnchor($component['name'], $component['id'], $component['level']);
            }
            
            if($component['type'] == self::FLOW_END){
                $flowSVG->addAnchor($component['name'], $component['id'], $component['level']);
            }
                                    
            if($component['type'] == self::ANALYSIS){                
                $flowSVG->addAnalysisComp($component['name'], $component['id'], $component['level']);
            }
            
            if($component['type'] == self::MATH){                
                $flowSVG->addMathComponent($component['name'], $component['id'], $component['level']);
            }                      
            
            if($component['type'] == self::ISOCOUNT){
                $flowSVG->addCustom($component['name'], $component['id'], $component['level']);
            }
        }
        
        //draw connections
        foreach($this->_components as $id=>$component){
            
            $getOut = pa('flowConnections', 'getConnections', array($flowId, $component['id']));            
            $lineSending = array();
            
            if($component['type'] == self::ANALYSIS){
                $anaFields = pa('analyticalFields', 'fetchFields', array($component['analytical']));                
                foreach($anaFields as $lineInd => $line){
                    $lineSending['sender_' . $lineInd] = $line['name'];
                }
            }
            
            if($component['type'] == self::MATH){
                $formulas = json_decode($component['instructions'], True);                                
                if(!empty($formulas)){
                foreach($formulas['math_instructions'] as $name => $formula){
                    $lineSending['form' . $name] = $name;
                }    
                }
                                             
            }
            
            if($component['type'] == self::ISOCOUNT){
                 $lineSending['isocount' . $component['name']] = $component['name'];
             }
                        
            foreach($getOut as $indice => $con){                                                
                $flowSVG->drawConnection('con_' . $con['id'], $con['component'], $con['target'], $lineSending);
            }                        
        }
        
        
        return $flowSVG->getBuffer();
    }
    
                
    private function _shiftComponentsArray($array){
        $shifted = array();
        foreach($array as $ind => $component){
            $shifted[$component['id']] = $component;
        }
        
        return $shifted;
    }
    
    /*
     * selects components from a given flow
     */
    private function _gatherFlowComponents($flowId){                        
        $this->Flow->join('flowcomponents', 'id', 'flow', 'RIGHT' );
        $this->Flow->where('id', $flowId);
        $this->Flow->order('level', 'ASC');
        $results = $this->Flow->search();
        return $results;    
    }
    
    /*
     * when a flow is added to a sample, we will need to know which components  
     * in the flow  will be editable by the user (i.e.: receive a barcode and be registerd in 
     * sampleAnalysis and Results)    
     */    
    function findInputComponents($flowId){        
        $this->render = 0;        
        $components = $this->_gatherFlowComponents($flowId);
        $regComponents = array();
                
        foreach($components as $component){
            //in principle only the ANALYSIS components are editable
            if($component['type'] == self::ANALYSIS){
                $regComponents[$component['id']]['component_id'] = $component['id'];
                $regComponents[$component['id']]['component_type'] = $component['type'];
                $regComponents[$component['id']]['component_analytical'] = $component['analytical'];
                $regComponents[$component['id']]['name'] = $component['name'];
                $regComponents[$component['id']]['instructions'] = $component['instructions'];
            }
        }                               
        return $regComponents;
    }
    
    /*
     * get a single component, to derive name etc from the db table
     */
    function getSingleInputComponent($flowId, $flowComponentId){                        
        $components = $this->findInputComponents($flowId);            
        return $components[$flowComponentId];
    }
    
    
    //flow list for sample entry page
    function generateFlowList(){
        $this->render = 0;       
        $results = $this->Flow->search();                        
        $html = '';        
        foreach($results as $flow){                                    
            $html .= generateHTML('flowReqLine', $flow);
        }                                
        return $html;        
    }
    
    
    //for sample entry page, request a 'line' of flow
    function request($idOnSample, $testId){        
        $this->render = 0;
        $this->Flow->where('id', $testId);
        $result = $this->Flow->search();        
        $result[0]['idOnSample'] = $idOnSample;                
        $html = generateHTML('testsLineReq', $result[0]);        
        print $html;
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
            
            $this->Flow->where('id', $singleTest['flow_id'] );
            $result = $this->Flow->search();
            $result[0]['idOnSample'] = $currentId;
            $returnObj['table_html'] .= generateHTML('testsLineReq', $result[0]);       
            $returnObj['no_added_analysis'] = $testsAdded;                        
            
            
            //$this->AnalysisTest->id = $singleTest['test_id'];
            //$result = $this->AnalysisTest->search();
            //$result[0]['idOnSample'] = $currentId;                                    
            //$returnObj['table_html'] .= generateHTML('testsLineReq', $result[0]);       
            //$returnObj['no_added_analysis'] = $testsAdded;                        
        }
        
        print json_encode($returnObj, JSON_FORCE_OBJECT);
    }
    
    function findByTags($query){
        
        $this->render = 0;        
        $tagArray = explode(';', $query);        
        
        foreach($tagArray as $tag){
            $this->Flow->like('tags', trim($tag));
        }
        
        $results = $this->Flow->search();        
        return $results;        
    }    
    
    //generic for getting a flow from the database
    function getFlow($flowId){        
        $this->render = 0;
        $this->Flow->where('id', $flowId);
        $results = $this->Flow->search();        
        return $results[0];        
    }
    
        
    function listing(){
    
        $flows = $this->Flow->search();
        
        if(empty($flows)){
            $values = '{MESA_FLW_NONE}';
        } else {
            $values = $flows;
        }
                        
        $tF = new tableFactory();
        $tF->loadTemplate('flowsListing');
        $tF->loadValues($values);         
        
        $this->_template->set('available_flows', $tF->renderTable());                
    }        
    
    function addSubmit(){
        $this->render = 0;
        $this->Flow->postToModel();
        $this->Flow->save();        
        $this->reRoute('flows/listing', True);
    }
    
    function add(){
        
        $addForm = new formFactory($this->_controller);
                
        $addForm->setId('addFlowForm');
        $addForm->action('{LB}/flows/addSubmit/');
        $addForm->method('POST');
        
        $addForm->addClass('form-horizontal');       
        $addForm->setTemplate('generic');
        
        $addForm->addInputField('name', 'Flow name', 'text', 'input-block-level', '', 'Enter a name for the work flow here', False);                
        $addForm->addValidation('name', 'NO_DUPLICATE');        
                
        $addForm->addInputField('tags', 'Workflow tags', 'text', 'input-block-level', '', 'Enter descriptional tags here', False);                
                                                       
        $addForm->addButton('submitButton', 'icon-plus-sign', False, 'btn btn-primary btn-small', 'Add Workflow', False);        
        $addForm->submitTrough('submitButton');
                                
        $this->_template->set('add_flow_form', $addForm->render());                
        
    }
    
    function fetchEndResults($sampleAnalysis){
        
        $analysis = pa('sampleAnalysis', 'fetchById', array($sampleAnalysis));                    
        
        $this->_eqEOS = new eqEOS();
        $this->_fetchTestResults($analysis['sample']  , $sampleAnalysis, $analysis['flow'] );
        $this->_solveFormulas($analysis['flow']);
        
        //return the end results to the input panel
        $resultFields = $this->getResultFields($analysis['flow']);
        
        //add the prefix
        foreach($resultFields as $ind => $result){
                $resultFields[$ind]['element_id'] = self::RESULT_LINK_PREFIX . $ind;
        }
        
        
        return $resultFields;
        
    }    
    
    //entry panel for flows
    function generateInputPanel($sampleAnalysis, $followNo){
        
        $this->render = 0;               
        $focusOnElement = False;
        $inputPanel = '';
               
        //get the needed info from sampleAnalysis
        $analysis = pa('sampleAnalysis', 'fetchById', array($sampleAnalysis));            
        $resultData = performAction('results', 'getTestResults', array(0=>$sampleAnalysis));
            
        //check authorisation
        $isEditable = True;        
        if($analysis['auth_status'] == '1' || $analysis['auth_status'] == '2'  ){
            $isEditable = False;
        }
                        
        //start building the input panel, loop trough all found editable flow components
        foreach($resultData as $resultLine){
               
            $theseResults = json_decode($resultLine['data'], True);            
            $flowComponentDetails = $this->getSingleInputComponent($analysis['flow'], $resultLine['component_id']);
            $testFields = pa('analyticalFields', 'fetchFields', array($resultLine['ana_base']) );   
            
            
            
            $inputPanel .= generateHtml('flowComponentLine', array('name'=>$flowComponentDetails['name'] ));
                                
            //foreach flow components, build all the nessecairy input fields based on its analytical
            
            
            foreach($testFields as $inputField){

                //check if value is there, else make up an empty one
                if(!isset($theseResults[$inputField['name']])){  $inputField['value'] = '';   }
                else { $inputField['value'] = $theseResults[$inputField['name']];  }
                
                //set database id
                $inputField['db_id'] = $resultLine['id'];
                
                //check for follow numbers
                if($followNo == $resultLine['follow_number']){
                    $focusOnElement = $inputField['name'] . '_' . $inputField['db_id'];
                }
                
                //editable or not
                if($isEditable == False){
                    $inputField['disabled'] = ' disabled ';
                } else{
                    $inputField['disabled'] = '';
                }
                
                if($inputField['type'] == 'int'){                                                
                    $renderedControl = generateHTML('input_int', $inputField);
                }    
                
                if($inputField['type'] == 'float'){                                                
                    $renderedControl = generateHTML('input_int', $inputField);
                }    
                
                $inputPanel .= $renderedControl; 
                                
            }            
        }
        
        //grab results and calculate results
        $this->_eqEOS = new eqEOS();
        $this->_fetchTestResults($analysis['sample']  , $sampleAnalysis, $analysis['flow'] );
        $this->_solveFormulas($analysis['flow']);
        
        //return the end results to the input panel
        $resultFields = $this->getResultFields($analysis['flow']);
        
        //render result fields
        $resultFieldHTML = '';
        foreach($resultFields as $connectId => $resultFieldArr){                        
            $elementId =  self::RESULT_LINK_PREFIX . $connectId;
            $resultFieldHTML .= generateHTML('resultField', array('result_name' => $resultFieldArr['name'], 'result_value' => $resultFieldArr['value'], 'element_id' => $elementId));
        }
        
        $inputPanel .= $resultFieldHTML;
        
        $returnTupp = array ('input_panel' => $inputPanel, 'focus_element' => $focusOnElement);
        return $returnTupp;
    }
    
    function getResultFieldsArray($sampleAnalysis){
        
        $analysis = pa('sampleAnalysis', 'fetchById', array($sampleAnalysis));            
        $resultData = performAction('results', 'getTestResults', array(0=>$sampleAnalysis));
        
        $this->_eqEOS = new eqEOS();
        $this->_fetchTestResults($analysis['sample']  , $sampleAnalysis, $analysis['flow'] );
        $this->_solveFormulas($analysis['flow']);
        $resultFields = $this->getResultFields($analysis['flow']);
        
        $resultArr = array();
        foreach($resultFields as $connectId => $resultFieldArr){                        
            $resultArr[$resultFieldArr['name']] = $resultFieldArr['value'];
        }
        
        return $resultArr;
    }
    
    function getResultFields($flowId){               
        
        $incoming = pa('flowComponents', 'getFlowEndResults', array($flowId));
        $resultFields = array();                       
                        
        foreach($incoming as $arr => $sender){
                    
            $conId = $sender['id'];
            $type = $sender['component_info']['type'];
            $name = $sender['component_info']['name'];
                        
            if($type == self::MATH){
                $instructions = json_decode($sender['component_info']['instructions'], True);                    
                if(!empty($instructions['math_instructions'])){
                    foreach($instructions['math_instructions'] as $mName=>$mForm){
                        $resultFields[$conId]['name'] = $mName;
                        $resultFields[$conId]['value'] = $this->_sampleResultsName[$name][$mName];
                    }
                }
            }
            
            if($type == self::ANALYSIS){
                foreach($sender['fields'] as $fInd => $sendField){
                    $alias = $sendField['alias'];                    
                    $compName = $sender['component_info']['name'];
                    $name = $sendField['name'];  
                    $resultFields[$conId]['name'] = $alias;
                    $resultFields[$conId]['value'] = $this->_sampleResultsName[$compName][$name];                          
                }
            }  
            
            if($type == self::ISOCOUNT){
                $alias = 'N';
                $compName = $sender['component_info']['name'];
                $name = 'N';
                $resultFields[$conId]['name'] = $alias;                
                $resultFields[$conId]['value'] = $this->_solveISOCOUNT($sender['component_info']['id']);
                
            }
        }
                        
        return $resultFields;
    }
    
    private function _solveISOCOUNT($componentId){                                
        
        $compInfo = pa('flowComponents', 'getComponentInfo', array($componentId));                
        $inputs = pa('flowComponents', 'getComponentInputs', array($compInfo['flow'], $componentId));                        
        
        $instructions = json_decode($compInfo['instructions'], True);                
        $dillArr = array();        

        $dilField =  $instructions['iso_count']['count_field'];
        $resField =  $instructions['iso_count']['dilution_field'];
        
        foreach($inputs as $inputInd => $inputInfo){
            
            $senderId = $inputInfo['component_info']['id'];                                                           
            $dillutionNow = $this->_sampleResultsComponent[$senderId][$dilField];
            $resultNow = $this->_sampleResultsComponent[$senderId][$resField];
            $dillArr[$dillutionNow] = $resultNow;
        }
        
        //krsort($dillArr);
                
        
        $cfuArr = array();
        $inArr = 0;
        $maxCfu = 500;                         
                        
        foreach($dillArr as $dilution => $result){
            
            if(!isset($lowest)){
                $lowest = $dilution;
            }
      
            $firstSign = substr($result, 0, 1);
            
            if( $firstSign == '>' || $result >= $maxCfu || $result == 0){
                continue;
            } 
            
            if($inArr >= 2){
                continue;
            }
            
            else{
                
                if(!isset($firstRet)){
                    $firstRet = $dilution;
                }
                
                $cfuArr[$inArr] = $result;
                $inArr++;                
            }                                             
        }
     
        if($inArr == 0){          
            $d = '';
            //$d = sprintf('%e', $lowest);            
            $d = log($lowest, 10);
            return 'less than 1 / 10^' . $d . ' microorganisms per millilitre';
        }
        
        if($inArr == 1){
            return 'dont know what to do now';
        }
        
        if($inArr == 2){  
            
            $n = ( $cfuArr[0] + $cfuArr[1] ) / ( 1 * 1.1 * $firstRet );                                                 
            
            $nRound = round($n, 2,  PHP_ROUND_HALF_UP);            
            return $nRound;
        }
        
        
    }
    
    private function _fetchTestResults($sample, $sampleAnalysis, $flowId){
                     
        $results = pa('results', 'getTestResults', array($sampleAnalysis));
        foreach($results as $ind=>$resultField){                        
            $componentInfo = pa('flowComponents','getComponentInfo', array($resultField['component_id']));
            $data = json_decode($resultField['data'], True);            
            $this->_sampleResultsComponent[$resultField['component_id']] = $data;                                
            $this->_sampleResultsName[$componentInfo['name']] = $data;
        }                
    }
    
   
    
    private function _solveFormulas($flowId){
                        
        $formulas = pa('flowComponents', 'getAllFormulas', array($flowId));        
        foreach($formulas as $ind=>$formComp){            
            $instructions = json_decode($formComp['instructions'], True);            
            foreach($instructions['math_instructions'] as $formula_name => $formula){
                $result = $this->_runFormula($formula);
                $this->_sampleMathResults[$formComp['name']][$formula_name] = $result;
                $this->_sampleResultsName[$formComp['name']][$formula_name] = $result;
            }
        }        
    }
    
    private function _runFormula($formula){

        if (preg_match_all("!\{(.*?)\}!", $formula, $matches)) {                        
                                    
            foreach ($matches[0] as $hitId => $hitTag) {
                
                $deCurledTag = $matches[1][$hitId];
                $tagParts = explode(':', $deCurledTag);
                                                
                $tagExists = False;
                $keyExists = False;
                
                $tagExists = array_key_exists($tagParts[0], $this->_sampleResultsName);
                if($tagExists == True){
                    $keyExists = array_key_exists($tagParts[1], $this->_sampleResultsName[$tagParts[0]]);
                }
                                
                
                if($tagExists == True && $keyExists == True){
                    $tagContent = $this->_sampleResultsName[$tagParts[0]][$tagParts[1]];                                        
                    if(trim($tagContent) == ''){
                        $tagContent = 0;
                    }
                }                                                
                else{
                    $tagContent = 0;
                }
               
                $formula = str_replace($hitTag, $tagContent, $formula);                                                
            }
        }
        
        //todo: error checking
        @$result = $this->_eqEOS->solveIF($formula, null);
        return $result;        
    }    
}