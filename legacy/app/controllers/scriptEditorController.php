<?PHP

class scriptEditorController extends controller{

    function generateStructure($assay){
        $this->render = False;
        
        //find the result structure of the base assay, like KVE 
        //make a results array, if dillutions, make a simple curve, if not use 0=1
        //make number of replciates if needed
        
        //create confirmation data array -> which needs to come from editor(might have changed), fill it with some numbers, or maybe 0
        //fill in max, min count
         //var typeBase = window.opener.$('#type_base').val();
         //var dilUse = window.opener.$("#dillution").val();
         //var repUse = window.opener.$("#replicates").val();
         //var conUse = window.opener.$("#confirmation").val();
         //var conFields = $('#confirmationScript').val();
        $textualArray = '$countable_minimum = ' . $_POST['minCount'] . '; ' . PHP_EOL;
        $textualArray .= '$countable_maximum = ' . $_POST['maxCount'] . ';' . PHP_EOL;;
                
        $baseInfo = upa('assayTypeFields', 'fetchFields', array($_POST['typeBase']));        
        $textualArray .= '//results[dF][duploNo][field] = value' . PHP_EOL;
        $confirmation = '';
                                
           
        $confList = array();                                   
        $text = trim($_POST['conFields']);
        $textAr = explode("\n", $text);
        $textAr = array_filter($textAr, 'trim');
        foreach ($textAr as $line) {
            $dInst = explode('=', $line);
                if(isset($dInst['0']) && isset($dInst['1'])){
                    $confList[$dInst['0']] = trim($dInst['1']);
            }
        }
        
        $dFi[0] = '1';
        $dFi[1] = '0.1';
        $dFi[2] = '0.01';
        $dFi[3] = '0.001';
                
        if($_POST['dilUse'] == '1'){   
    
            
            
            if($_POST['repUse'] == '1'){
                
                
                
                for($i = 0; $i < 4; $i++){
                   foreach($baseInfo as $field){
                     $textualArray .= '$results[\'' . $dFi[$i] .'\'][\'0\'][\'' . $field['name'] . '\'] = \'0\'; ' . PHP_EOL;
                     $textualArray .= '$results[\'' . $dFi[$i] .'\'][\'1\'][\'' . $field['name'] . '\'] = \'0\'; ' . PHP_EOL;
                     
                     if($_POST['conType'] == '1'){
                         foreach($confList as $conField => $conAlias){
                             $confirmation .= '$confirmation_data[\'' . $dFi[$i] .'\'][\'0\'][\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;
                             $confirmation .= '$confirmation_data[\'' . $dFi[$i] .'\'][\'1\'][\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;
                         }                         
                     }                     
                    } 
                }                
            }                
            else{
                                      
                
                for($i = 0; $i < 4; $i++){
                   foreach($baseInfo as $field){
                     $textualArray .= '$results[\'' . $dFi[$i] .'\'][\'0\'][\'' . $field['name'] . '\'] = \'0\'; ' . PHP_EOL;                     
                     
                     if($_POST['conType'] == '1'){
                         foreach($confList as $conField => $conAlias){
                             $confirmation .= '$confirmation_data[\'' . $dFi[$i] .'\'][\'0\'][\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;                         
                         }                         
                     }   
                    } 
                }
            }            
        }
              
        if($_POST['dilUse'] == '0'){            
            if($_POST['repUse'] == '1'){                                
                foreach($baseInfo as $field){
                  $textualArray .= '$results[\'1\'][\'0\'][\'' . $field['name'] . '\'] = \'0\'; ' . PHP_EOL;
                  $textualArray .= '$results[\'1\'][\'1\'][\'' . $field['name'] . '\'] = \'0\'; ' . PHP_EOL;                  
                 }   
                 
               if($_POST['conType'] == '1'){
                         foreach($confList as $conField => $conAliass){
                             $confirmation .= '$confirmation_data[\'1\'][\'0\'][\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;                         
                             $confirmation .= '$confirmation_data[\'1\'][\'1\'][\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;                         
                         }                         
                }     
            }               
            else{                
                   foreach($baseInfo as $field){
                     $textualArray .= '$results[\'' . $dFi[$i] .'\'][\'0\'][\'' . $field['name'] . '\'] = \'0\'; ' . PHP_EOL;                     
                    }   
                    
                    if($_POST['conType'] == '1'){
                         foreach($confList as $conField => $conAlias){
                             $confirmation .= '$confirmation_data[\'1\'][\'0\'][\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;                                                      
                         }                         
                    }  
            }            
        }
        
        if($_POST['conType'] == '0'){            
            foreach($confList as $conField => $conAlias){
                $confirmation .= '$confirmation_data[\'' . $conField . '\'] = \'0\'; ' . PHP_EOL;                                                      
            }              
        }
                        
        
        print $textualArray;
        print $confirmation;
        
    }
 
    function editor($assayId){
        
        
        $assayInfo = upa('assays', 'fetchSingle', array($assayId));
        $this->renderAlternateHeader = 'slim';
        
        $sF = new formFactory($this->_controller);
        $sF->action('');
        $sF->method('');
        $sF->setTemplate('generic');
        
        $optionStatic['NULL'] =  '{MESA_SED_DATAVARS}';       
        $optionStatic['$results'] =  '{MESA_SED_VARDATA}';       
        $optionStatic['$confirmation_data'] =  '{MESA_SED_VARCONFIRMATION}';       
        $optionStatic['$countable_maximum'] =  '{MESA_SED_VARCOUNTMAX}';       
        $optionStatic['$countable_minimum'] =  '{MESA_SED_VARCOUNTMIN}';       
        $optionStatic['$reference_value'] =  '{MESA_SED_VARREFERENCE}';                       
                
        $sF->addDropdownField('staticVar', False, 'input-block-level', 'NULL', $optionStatic, False, False);
        
        $assayFields = pa('assayFields', 'getAssayFields', array($assayId));        
        $baseFields = pa('assayTypeFields', 'fetchFields', array($assayInfo['type_base']));            
        
        if($assayInfo['type'] != 4){        
            $optionFields['NULL'] = '{MESA_SED_ASSAYFIELDS}';        
            foreach($baseFields as $bField){            
                $optionFields[$bField['name']] = $bField['alias'];
            }

            foreach($assayFields as $aField){
                $optionFields[$aField['name']] = $aField['name'];
            }            
        
            $sF->addDropdownField('assayFields', False, 'input-block-level', 'NULL', $optionFields, False, False);        
        }
        
        else{

            //fetch outputs of the input assays here
            $inputFields = array();
            $inputFields['NULL'] = '{MESA_SED_ASSAYFIELDS}';     
            
            $metaInputs = explode(',' , $assayInfo['meta_assays']);
            
            foreach($metaInputs as $metaInput ){
                
                $metaInputInfo = upa('assays', 'fetchSingle', array($metaInput));                
                $outputsOfInput = upa('results', 'fetchOutputFields', array($metaInput), False);
                
                foreach($outputsOfInput as $inField => $arbVeld){                                                            
                    $varName = '$meta[\'' . $metaInput . '\'][\''  .$inField . '\'];';
                    $varDescription = $metaInputInfo['name'] . ':' . $inField;                    
                    $inputFields[$varName] = $varDescription;
                }                
            }            
            $sF->addDropdownField('assayFields', False, 'input-block-level', 'NULL', $inputFields, False, False);        
        }
                                                    
        
        
        $optionConf['NULL'] = '{MESA_SED_CONFIRMATIONFIELDS}';
        
        $cFields = json_decode($assayInfo['confirmation_script'], True);
        if(!empty($cFields)){
            foreach($cFields as $cFieldName => $cFieldAlias){
            $optionConf[$cFieldName] = $cFieldAlias;
        }
        }
        
        
        $sF->addDropdownField('confField', False, 'input-block-level', 'NULL', $optionConf, False, False);
                
        $sF->addTextArea('resultsScript', False, 'text', 'textarea-block-level', False, '{MESA_SED_TABRESULTSCRIPT}', array('rows' => '20'));        
        $sF->addTextArea('varWindow', False, 'text', 'textarea-block-level', False, '{MESA_SED_DATAVARS}', array('rows' => '6'));        
        $sF->addTextArea('debugWindow', False, 'text', 'textarea-block-level' , False, '{MESA_SED_SCRIPTOUT}', array('rows' => '6'));                                        
        $sF->addTextArea('confirmationScript', False, 'text', 'textarea-block-level', False, '{MESA_SED_CONFFIELDSTIP}', array('rows' => '10'));
        
        $tempOptions = $this->fetchTemplates();
        $tempOptions['NULL'] = '-- {MESA_SED_SELECTTEMPLATESCRIPT} --';
        
        $sF->addDropdownField('templateSelector', False , 'input', 'NULL', $tempOptions, False, False);
        
        $sF->submitTrough('closeEditor', 'closeEditor');        
        $sF->returnAsFieldArray();
        
        $formFields = $sF->render();
        $this->_template->setByArray($formFields);
        $this->_template->set('id', $assayId);
        
    }
    
    function redoConfList(){
        
        $text = trim($_POST['newFields']);                
        $textAr = explode("\n", $text);
        $textAr = array_filter($textAr, 'trim'); 
        $this->doNotRenderHeader = 1;

        $confList = array();
                       
        foreach ($textAr as $line) {
            $dInst = explode('=', $line);                             
            
            if(isset($dInst['0']) && isset($dInst['1'])){
                $confList[$dInst['0']] = trim($dInst['1']);
            }            
        } 
        
        $printer = '<option value="NULL">{MESA_SED_CONFIRMATIONFIELDS}</option>';
        foreach($confList as $confFieldVal => $confFieldAlias){
            $printer .= '<option value="' . $confFieldVal  .'">' . $confFieldAlias . '</option>';
        }
                
        $this->_template->set('confList', $printer);
    }
    
    
    private function fetchTemplates(){
                
        
        $templates = array();                
        
        if ($handle = opendir( ROOT . DS . 'app' . DS . 'private' .DS . 'templateScripts' )) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {         
                    $fileTrim =  preg_replace("/\\.[^.\\s]{3,4}$/", "", $entry);           
                    $templates[$fileTrim] = $fileTrim;
                }
            }
            closedir($handle);
        }  
        
        return $templates;
        
    }
    
    function loadTemplate($fileName){
        
        $this->render = 0;
        
        $bad[0] = "/";
        $bad[1] = "\\";
        $bad[2] = ".";            
        $fileName = str_replace($bad, '', $fileName);
        
        $path =  ROOT . DS . 'app' . DS . 'private' .DS . 'templateScripts' . DS . $fileName . '.txt';
        $contents = file_get_contents($path);
        
        print $contents;
        
    }
    
    function saveScript($assayId){        
        $this->render = 0;
        upa('assays', 'saveOnlyScript', array($assayId, $_POST['script'], $_POST['confirmation']));        
    }
    
}   