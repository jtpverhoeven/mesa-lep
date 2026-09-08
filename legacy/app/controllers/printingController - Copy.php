<?PHP

class printingController extends controller{

    private $sampleInfo = array();
    private $customSampleFields = array();
    private $sampleProcedure = array();
    private $projectInfo = array();
    private $saidInfo = array();
    private $assayInfo = array();
    private $printSpool = array();
    private $clientInfo = array();

    private $directLabelInfo;
    private $directCopies;

    private $_pdfHandle;
    private $_pdfContents;
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    function batchSampleReg($sampleIds){
        foreach($sampleIds as $sampleId){
            $this->sampleRegFire($sampleId, True);
        }        
    }

    function batchAssayFire($saids, $directPrint){
        foreach($saids as $said){
            $this->assayAddFire($said, $directPrint, False, False, False, False, True );
            //args: $said, $directPrint = True, $labelInfo = False, $copies = False, $printerInfo = False, $restrictDI = False, $defer)  
        }
    }

    function batchProcess($sampleIds, $saids, $directPrint = True){        
        $this->batchSampleReg($sampleIds);
        $this->batchAssayFire($saids, $directPrint);
        $this->releaseSpool();
    }

    function profiles(){

        $this->renderAlternateHeader = 'genericAdminHeader';
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');

        $results = $this->Printing->search();
        if(empty($results)){
            $results = 'Geen print profielen gedefinieerd';
        }

        $tF = new tableFactory();
        $tF->setTableId('printProfileTable');
        $tF->loadTemplate('printProfileTable');
        $tF->loadValues($results);
        $table = $tF->renderTable();
        $this->_template->set('table', $table);
    }

    function profileForm($id){
        $this->doNotRenderHeader = True;

        if($id != False){
            $this->Printing->where('id', $id);
            $result = $this->Printing->search();

            if(!empty($result)){
                $this->Printing->arrayToModel($result[0]);
            }
        }

        $addForm = new formFactory($this->_controller);

        $addForm->setId('addLabelForm');
        $addForm->action('{LB}/printing/saveLabel');
        $addForm->method('POST');
        $addForm->addClass('');
        $addForm->setTemplate('generic');

        $addForm->addInputField('name', 'Profiel naam', 'text', 'input-block-level',  $this->Printing->name, 'Profiel naam', False);
        $addForm->addTextArea('profile', 'Profiel code', '', 'textarea-block-level', $this->Printing->profile, 'Profiel code',  array('rows'=>10));
        $addForm->addInputField('id', '', 'hidden', 'hidden',  $this->Printing->id, False, False);
        $addForm->submitTrough('addProfileSubmit');
        $this->_template->set('render', $addForm->render());
    }

    function saveLabel(){
        $this->render = False;

        if(!empty($_POST['id'])){
            $this->Printing->id = $_POST['id'];
        }

        $this->Printing->name = $_POST['name'];
        $this->Printing->profile = $_POST['profile'];
        $this->Printing->save();
        $this->reRoute('printing/profiles', True);
    }

    function removeProfile($id){
        $this->Printing->id = $id;
        $this->Printing->remove();
        $this->reRoute('printing/profiles', True);
    }

    private function writeLogFile($handle, $msg){
        $date = date('d-m-Y H:i:s');
        fwrite($handle, $date . "\t" . $msg . "\t" . PHP_EOL);
        return;
    }

    private function releaseSpool(){

        $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'printerlog.txt';
        $spoolLog =  fopen($logLocation,"a+");

        $this->writeLogFile($spoolLog, 'Release spool');
        $spoolTXT = parray($this->printSpool, True);


        //debug stuff
        foreach($this->printSpool as $printId => $spoolInfo){
            $this->writeLogFile($spoolLog, 'Printer pool found');
            $this->writeLogFile($spoolLog, 'Printer ID:' . $printId);
            $this->writeLogFile($spoolLog, 'Contype:' . $spoolInfo['conType']);
            $this->writeLogFile($spoolLog,  $spoolInfo['spool']);
        }

       if(MESA_DISABLE_PRINTING == '1'){
         
   
           Debugger::log('Printing disabled by MESA_DISABLE_PRINTING: 1', 'warn' );           
           Debugger::log('SPOOL' . $spoolTXT );
           
           $this->writeLogFile($spoolLog, 'Warning: printing was disabled');

       } else{

         foreach($this->printSpool as $printId => $spoolInfo){

            //if($spoolInfo['conType'] == '0'){
            //@$handle = printer_open($spoolInfo['conAdres']);
            //if($handle != False && $handle != NULL){
            //   printer_set_option($handle, PRINTER_MODE, "RAW");
            //    printer_write($handle, $spoolInfo['spool']);
            //    printer_close($handle);
            // }
            //}

            try{
                $fp=fsockopen($spoolInfo['conAdres'],$spoolInfo['conPort']);
                $write = fputs($fp,$spoolInfo['spool']);
                fclose($fp);

                //if($write === false){
                //    fclose($fp);
                //}
                
            }catch (Exception $e){             
                $this->writeLogFile($spoolLog, 'Caught exception: ',  $e->getMessage(), "\n");
            }
         }         
       }

      $this->writeLogFile($spoolLog, 'Finished release spool');
      
      fclose($spoolLog);      
      
      
      return;
    }

    function innitInfo($sampleId){
        $this->sampleInfo = upa('samples', 'fetch', array($sampleId), False);


        if($this->sampleInfo == False){
            return False;
        }

        $this->sampleProcedure = upa('sampleProcedures','fetch', array($this->sampleInfo['sampling_method']));
        $this->projectInfo = upa('projects', 'fetch', array($this->sampleInfo['project']));

        if(!array($this->sampleProcedure)){
            $this->sampleProcedure = array();
        }

        $followObject = upa('samples', 'sampleGetProjectFollowNo', array($sampleId, $this->sampleInfo['project']), False);
        $this->sampleInfo['db_follow_no'] = $followObject['db_follow'];
        $this->sampleInfo['user_follow_no'] =  $followObject['custom_follow'];

        $this->clientInfo = upa('clients', 'fetch', array($this->sampleInfo['client']));
        $this->customSampleFields = upa('sampleFields', 'customFieldsArrayByName', array(), False);

        return True;
    }


    function sampleRegFire($sampleId, $defer = False){

        $this->render = false;
        if($this->innitInfo($sampleId) == False) {
            writeLog('Could not complete innitInfo in sampleRegFire', ALPC_ERROR);
            return False;
        }

        $regEvents = upa('labelEvents', 'fetchEvents', array('1'), False);
        $customFields = json_decode($this->sampleInfo['custom_fields'], True);

        foreach($regEvents as $event){

            $shouldPrint = False;
            $printerInfo = upa('printers', 'fetch', array($event['print_to']));
            $labelInfo = upa('labelDesigns', 'fetch', array($event['print_label']));


            if($event['event'] == 'P'){
               $shouldPrint = True;
            } elseif($event['event'] == 'L'){

                $logicArray = json_decode($event['event_data'], True);

                if($logicArray['type'] == 'sField'){

                   $sFieldCondition = False;
                   if(array_key_exists($logicArray['inspect'], $customFields) && $logicArray['inspect_value'] == $customFields[$logicArray['inspect']]){
                       $sFieldCondition = True;
                   }

                   if($sFieldCondition == True && $logicArray['negative'] == '0'){
                       $shouldPrint = True;
                   } elseif($sFieldCondition == False && $logicArray['negative'] == '1'){
                       $shouldPrint = True;
                   }

                }elseif($logicArray['type'] == 'sClient'){

                    $clientCondition = False;

                    if($logicArray['inspect_value'] == $this->sampleInfo['client']){
                       $clientCondition = True;
                    }

                    if($clientCondition == True && $logicArray['negative'] == '0'){
                       $shouldPrint = True;
                    } elseif($clientCondition == False && $logicArray['negative'] == '1'){
                        $shouldPrint = True;
                    }
                }elseif($logicArray['type'] == 'mMethod'){

                    $methodCondition = False;

                    if($logicArray['inspect_value'] == $this->sampleInfo['sampling_method']){
                       $methodCondition = True;
                    }

                    if($methodCondition == True && $logicArray['negative'] == '0'){
                       $shouldPrint = True;
                    } elseif($methodCondition == False && $logicArray['negative'] == '1'){
                        $shouldPrint = True;
                    }
                } elseif($logicArray['type'] == 'mType'){

                    $methodCondition = False;

                    if($logicArray['inspect_value'] == $this->sampleInfo['sample_type']){
                       $methodCondition = True;
                    }

                    if($methodCondition == True && $logicArray['negative'] == '0'){
                       $shouldPrint = True;
                    } elseif($methodCondition == False && $logicArray['negative'] == '1'){
                        $shouldPrint = True;
                    }
                }
            }

          if($shouldPrint == True){
              //setup printing connection              
              $this->printLabel($labelInfo['zpl'], $event['copies'], $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
          }
        }

        if($defer !== True){
            $this->releaseSpool();
        }        
    }

    //$this->assayAddFire($assay['id'], True, $assayLabel, $copies, $printerInfo, $restrict);
    function assayAddFire($said, $directPrint = True, $labelInfo = False, $copies = False, $printerInfo = False, $restrictDI = False, $defer=False){        

        global $lang;
        $this->render = false;
        $regEvents = upa('labelEvents', 'fetchEvents', array('2'), False);
        $this->saidInfo = upa('sampleAnalysis', 'fetch', array($said));

        if($this->saidInfo == False){            
            return False;
        }

        $this->innitInfo( $this->saidInfo['sample']);
        $this->assayInfo = upa('assays', 'fetch', array($this->saidInfo['assay_base']), False);

        //foreach($this->assayInfo['custom_fields'] as $cFieldName=>$cFieldValue){
        //    $this->assayInfo['custom_' . $cFieldName] = $cFieldValue;
        //}


        //load profile or roaming profile
        if($this->saidInfo['roaming_id'] !==  NULL && $this->saidInfo['roaming_id'] != 0){
            $assayProfileLoad = upa('roamingAnalysis', 'fetchSettings', array($this->saidInfo['roaming_id']));
            $assayProfile = $assayProfileLoad['0'];
        } else {
            $assayProfile = upa('assayProfiles', 'fetchProfileById', array($this->saidInfo['assay']));
            $assayProfile = $assayProfile[0];
        }

        //cphp($assayProfile);

        if($directPrint == True){
                $dillInfo = upa('results', 'getCurrentDillutions', array($said, 'printing_array'), False);

                #$dillArr = json_decode($assayProfile['dillutions'], True);
                $dillArr = $dillInfo['dillutions'];
                $assayProfile['replicates'] = $dillInfo['rep'];

                $dI = 1;
                foreach($dillArr as $asText => $asNum){

                    if(array_key_exists($asNum, $dillInfo['rep'])){
                      $noReplicates = $dillInfo['rep'][$asNum];
                    } else{
                      $noReplicates = 0;
                    }

                    if($asText == '0'){
                      $this->assayInfo['dillution_label'] = '';
                    }else{
                      $this->assayInfo['dillution_label'] = $asText;
                    }


                    //$this->assayInfo['dillution_label'] = $asText;
                    $this->assayInfo['dillution_numeric'] = $asNum;

                    //this was not innoculated yet.. calculate the end day as if today
                    //if($this->saidInfo['predicted_end'] == '' || $this->saidInfo['predicted_end'] == '-1'){
                    //    $endPointToUse = upa('sampleAnalysis', 'reestimateEndPont', array($this->saidInfo['assay_base'], $this->saidInfo['predicted_end'] ), False);
                    //} else{
                    //    $endPointToUse = $this->saidInfo['predicted_end'];
                    //}

                    //19-03 we are now just using the endpoint from NOW(), when printing. Innoc dates etc will still be used for
                    //time tracking, but not for printing on the labels

                    $endPointToUse = upa('sampleAnalysis', 'endPointFromNow', array($this->saidInfo['assay_base']), False);

                    $day = strtoupper(date('D', $endPointToUse ));
                    $this->assayInfo['predicted_end_day'] = $lang[$day];
                    //$this->assayInfo['plate_barcode'] = $this->sampleInfo['barcode'] . '>6.' . $this->saidInfo['follow_number'] . '.' .  $dI;
                    $this->assayInfo['plate_barcode'] = $this->sampleInfo['barcode'] . '.' . $this->saidInfo['follow_number'] . '.' .  $dI;
                    $this->assayInfo['plate_barcode_sample'] = $this->sampleInfo['barcode'];

                    if($restrictDI == False){
                      $this->printLabel($labelInfo['zpl'], $copies, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                    } else{
                      if($dI == $restrictDI){
                        $this->printLabel($labelInfo['zpl'], $copies, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                      }
                    }

                    //for($rI = 0;  $rI < $assayProfile['replicates']; $rI++){
                    for($rI = 0;  $rI < $noReplicates; $rI++){
                        $dI++;
                        $this->assayInfo['plate_barcode'] = $this->sampleInfo['barcode'] . '.' . $this->saidInfo['follow_number'] . '.' .  $dI;

                        if($restrictDI == False){
                          $this->printLabel($labelInfo['zpl'], $copies, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                        } else{
                          if($dI == $restrictDI){
                            $this->printLabel($labelInfo['zpl'], $copies, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                          }
                        }
                        #$this->printLabel($labelInfo['zpl'], $copies, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                    }

                  $dI++;
                }
        }

        else{

        foreach($regEvents as $event){

            $shouldPrint = False;
            $printerInfo = upa('printers', 'fetch', array($event['print_to']));
            $labelInfo = upa('labelDesigns', 'fetch', array($event['print_label']));

            if($event['event'] == 'P'){
               $shouldPrint = True;
            } elseif($event['event'] == 'L'){

                $logicArray = json_decode($event['event_data'], True);

                if($logicArray['type'] == 'assayIs'){

                   $assayIsCondition = False;

                   if($logicArray['inspect_value'] == $this->saidInfo['assay_base']){
                       $assayIsCondition = True;
                   }

                   if($assayIsCondition == True && $logicArray['negative'] == '0'){
                       $shouldPrint = True;
                   } elseif($assayIsCondition == False && $logicArray['negative'] == '1'){
                       $shouldPrint = True;
                   }
                }

                if($logicArray['type'] == 'assayTypeIs'){

                    $assayIsCondition = False;

                    $thisBase = upa('assays', 'fetch', array($this->saidInfo['assay_base']), False );

                    if($logicArray['inspect_value'] == $thisBase['type_base']){
                        $assayIsCondition = True;
                    }

                    if($assayIsCondition == True && $logicArray['negative'] == '0'){
                        $shouldPrint = True;
                    } elseif($assayIsCondition == False && $logicArray['negative'] == '1'){
                        $shouldPrint = True;
                    }
                }
            }

            if($shouldPrint == True){
                //ChromePhp::log('Shoudl rpint 1 ');

                $dillArr = json_decode($assayProfile['dillutions'], True);


                $dI = 1;
                foreach($dillArr as $asText => $asNum){

                    if($asText == '0'){
                      $this->assayInfo['dillution_label'] = '';
                    } else{
                      $this->assayInfo['dillution_label'] = $asText;
                    }

                    $this->assayInfo['dillution_numeric'] = $asNum;
                    $day = strtoupper(date('D', $this->saidInfo['predicted_end']));
                    $this->assayInfo['predicted_end_day'] = $lang[$day];                    
                    $this->assayInfo['plate_barcode'] = $this->sampleInfo['barcode'] . '.' . $this->saidInfo['follow_number'] . '.' .  $dI;
                    $this->assayInfo['plate_barcode_sample'] = $this->sampleInfo['barcode'];

                    $this->printLabel($labelInfo['zpl'], $event['copies'], $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);


                    for($rI = 0;  $rI < $assayProfile['replicates']; $rI++){
                        $dI++;                        
                        $this->assayInfo['plate_barcode'] = $this->sampleInfo['barcode'] . '.' . $this->saidInfo['follow_number'] . '.' .  $dI;
                        $this->printLabel($labelInfo['zpl'], $event['copies'], $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                    }


                  $dI++;
                }

          }


        }
          //$this->releaseSpool();

          
            if($defer !== True){
                $this->releaseSpool();
            }        
        }


    }

    function renderLabel($labelZPL){

        //need to render the label for any label
        //should create a replacer array
        global $lang;
        $patterns = array();
        $replacements = array();

        //dates


        //$replacements['date_today'] = $lang['today'];
        //$replacements['yesterday'] = $lang['yesterday'];
        //$replacements['tommorow'] = $lang['tommorow'];
        //$replacements['current_time'] = $lang['current_time'];
        //$replacements['current_year'] = $lang['current_year'];

        $replacements[0] = $lang['today'];
        $replacements[1] = $lang['yesterday'];
        $replacements[2] = $lang['tommorow'];
        $replacements[3] = $lang['current_time'];
        $replacements[4] = $lang['current_year'];
        $replacements[5] = userIdToName($_SESSION['ALPC_USER_ID']);

        $patterns[0] = '/{date_today}/';
        $patterns[1] = '/{yesterday}/';
        $patterns[2] = '/{tommorow}/';
        $patterns[3] = '/{current_time}/';
        $patterns[4] = '/{current_year}/';
        $patterns[5] = '/{current_user}/';




        $i = 6;

        /*
         * SAMPLE KEYS
        */

        //unset( $this->sampleInfo['sample_extra']);
        //unset( $this->projectInfo['project_extra']);

        foreach($this->sampleInfo as $sampleInfoKey => $sampleInfoValue){

            $patterns[$i] = '/{sample_' . $sampleInfoKey .'}/';

            //special fields
            if($sampleInfoKey == 'date_registered'){
                $replacements[$i] = date('d-m-Y', $sampleInfoValue);
            }


            elseif($sampleInfoKey == 'custom_fields'){
                //unwrap custom fields
                $custoFields = json_decode($sampleInfoValue, True);

                if(is_array($custoFields)){

                    foreach($custoFields as $custoFieldName => $custoFieldValue){

                        $type = $this->customSampleFields[$custoFieldName];

                        if($type == 'date'){
                            $patterns[$i] = '/{sample_custom_' . $custoFieldName .'}/';
                            if(is_long($custoFieldValue)){
                                $replacements[$i] = date('d-m-Y', $custoFieldValue);
                            } else{
                                $replacements[$i] = $custoFieldValue;
                            }
                        } else{
                            $patterns[$i] = '/{sample_custom_' . $custoFieldName .'}/';
                            $replacements[$i] = $custoFieldValue;
                        }
                        $i++;
                    }
                }
            }

            else{
                $replacements[$i] = $sampleInfoValue;
            }
            $i++;
        }

        /*
         * CLIENT KEYS
         */

        foreach($this->clientInfo as $clientInfoKey => $clientInfoValue){
            $patterns[$i] = '/{client_' . $clientInfoKey .'}/';
            $replacements[$i] = $clientInfoValue;
            $i++;
        }

        /*
         * Sample procedure fields
         */

        if(is_array($this->sampleProcedure)){

        foreach($this->sampleProcedure as $procFieldKey => $procFieldValue){

            if($procFieldKey == 'fields'){

                $custoFields = json_decode($procFieldValue, True);
                foreach($custoFields as $custoFieldName => $custoFieldValue){
                    $patterns[$i] = '/{method_custom_' . $custoFieldName .'}/';
                    $replacements[$i] = $custoFieldValue;
                    $i++;
                }

            } else{
                $patterns[$i] = '/{method_' . $procFieldKey .'}/';
                $replacements[$i] = $procFieldValue;

            }
            $i++;

        }
        }

        /*
         * Assays  info
         *
         */
        foreach($this->assayInfo as $assayInfoKey => $assayInfoVal){

              if($assayInfoKey == 'custom_fields'){

                $custoFields = json_decode($assayInfoVal, True);
                    foreach($custoFields as $custoFieldName => $custoFieldValue){
                        $patterns[$i] = '/{assay_custom_' . $custoFieldName .'}/';
                        $replacements[$i] = $custoFieldValue;
                        $i++;
                 }

              } else{
                 $patterns[$i] = '/{assay_' . $assayInfoKey .'}/';
                 $replacements[$i] = $assayInfoVal;
              }

            $i++;
        }

        /*
         * Project  info
         *
         */

        foreach($this->projectInfo as $projectKey => $projectInfoVal){

//            if($projectKey == 'project_extra' ){
//                continue;
//            }

            if($projectKey == 'custom_fields'){

                $custoFields = json_decode($projectInfoVal, True);
                foreach($custoFields as $custoFieldName => $custoFieldValue){
                    $patterns[$i] = '/{project_custom_' . $custoFieldName .'}/';
                    $replacements[$i] = $custoFieldValue;
                    $i++;
                }

            } else{
                $patterns[$i] = '/{project_' . $projectKey .'}/';
                $replacements[$i] = $projectInfoVal;
            }

            $i++;
        }

        //25 26
        #cphp($patterns['25']);
        #cphp($patterns['26']);
        #cphp($replacements['25']);
        #cphp($replacements['26']);

        return preg_replace($patterns, $replacements, $labelZPL);
    }

    private function printLabel($zpl, $copies, $printId,  $conType, $conAdres, $conPort = False ){

            $renderedLabel = $this->renderLabel($zpl);    

            //set in proper spool key
            if(!array_key_exists($printId, $this->printSpool)){
                $this->printSpool[$printId] = array();
                $this->printSpool[$printId]['conType'] = $conType;
                $this->printSpool[$printId]['conAdres'] = $conAdres;
                $this->printSpool[$printId]['conPort'] = $conPort;
                #$this->printSpool[$printId]['conPort'] = $conPort;
                $this->printSpool[$printId]['spool'] = MESA_ZEBRA_ALIGN;
            }

            for($i = 0; $i < $copies; $i++){
                $this->printSpool[$printId]['spool'] = $this->printSpool[$printId]['spool'] . $renderedLabel;
            }

            return True;

    }

    function testLabel($zpl, $printer){

        $printerInfo = $this->fetchPrinterInfo(False, $printer);


        $this->printSpool[$printer] = array();
        $this->printSpool[$printer]['conType'] = $printerInfo['type'];
        $this->printSpool[$printer]['conAdres'] = $printerInfo['adres'];
        $this->printSpool[$printer]['conPort'] = $printerInfo['port'];
        $this->printSpool[$printer]['spool'] = $zpl;

        $this->releaseSpool();

    }


    function s2p(){

        $profiles = $this->Printing->search();

        $sForm = new formFactory($this->_controller);
        $sForm->setId('scanForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();

        $sForm->addInputField('barcode', False, 'text', 'input-block-level', '', '{MESA_S2P_SCANTIP}', '', '<i class="icon-barcode"></i> ');
        $sForm->addInputField('range_start', False, 'text', 'input-block-level', '{today}', '', '', '<i class="icon-calendar-empty"></i> ');
        $sForm->addInputField('range_stop', False, 'text', 'input-block-level', '{today}', '', '', '<i class="icon-calendar"></i> ');

        $sForm->addInputField('bar_range_start', False, 'text', 'input-block-level', '', '', '', 'Van');
        $sForm->addInputField('bar_range_stop', False, 'text', 'input-block-level', '', '', '', 'Tot en met');

        $printers = upa('printers', 'listPrintersInArray', array(), False);
        $sForm->addDropdownField('leg_printer', '{MESA_S2P_PRINTO}', 'input-block-level', False, $printers, False);


        $printers[0] = 'Standaard printer';       
        $sForm->addDropdownField('printer', '{MESA_S2P_PRINTO}', 'input-block-level', '0', $printers, False);
        $labels = upa('labelDesigns', 'listLabelsInArray', array(True), False);

        $sForm->addDropdownField('sample_label_select', '{MESA_S2P_SAMPLELABEL}', 'input-block-level', '', $labels, False);
        $sForm->addDropdownField('assay_label_select', '{MESA_S2P_ASSAYLABEL}', 'input-block-level', '', $labels, False);

        //hacky
        $asText = '';
        foreach($labels as $labelValue => $labelName){
            if($labelValue != 'default'){
                $selected = '';
                if(standard_extra_label == $labelValue){
                  $selected = "selected='selected'";
                }
                $asText .= '<option value="' . $labelValue . '" ' . $selected .' >' . $labelName . '</option>';
            }
        }

        //grab sample analysis type
        $assayTypes = upa('assayTypes', 'listAssayTypes', array(), False);
        $assayTypeArr = array();
        $assayTypeArr['0'] = 'Alles';
        //$asText = '';
        foreach($assayTypes as $assayValue => $assayInfo){
            $assayTypeArr[$assayInfo['id']] = $assayInfo['name'];
        }

        $sForm->addDropdownField('assay_type_select', '{MESA_S2P_ASSAYCONSTRAIN}', 'input-block-level', '0', $assayTypeArr, False);

        $pProfiles = array();
        //$pProfiles[0] = 'Geen';
        //foreach($profiles as $pProfile){
        //    $pProfiles[$pProfile['id']] = $pProfile['name'];
        //}

        $printProfileSelection = '';
        foreach($profiles as $pProfile){
          $printProfileSelection .= generateHTML('s2p/printProfileLine', array('id' => $pProfile['id'], 'name' => $pProfile['name']));
        }

        $this->_template->set('printProfiles', $printProfileSelection);

        //$sForm->addDropdownField('printProfile', 'Print profiel', 'input-block-level', '0', $pProfiles, False);

        //construct possible date features
        $rangeTypes = array();
        $rangeTypes[0] = 'Registratie datum';
        $rangeTypes['i'] = 'Inzetdatum';

        //projects date fields
        $projectDateFields = upa('projectFields', 'fetchDateProjectFields', array(), False);
        foreach($projectDateFields as $field){
            $rangeTypes['p_' . $field['name']] = 'Project: ' . $field['alias'];
        }

        //sample date fields
        $sampleDateFields = upa('sampleFields', 'dateDropDownMenu', array(True), False);

        foreach($sampleDateFields as $field => $alias){
            $rangeTypes['s_' . $field] = 'Monster: ' . $alias;
        }

        //sample date fields
        $sForm->addDropdownField('range_type_select', '{MESA_S2P_RANGETYPESELECT}', 'input-block-level', 'i', $rangeTypes, False);

        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);
        $this->_template->set('label_values', $asText);
    }

    function runSampleRangeS2P(){
        $this->render = False;
        $sampleRangeStart = $_POST['bar_range_start'];
        $sampleRangeEnd =  $_POST['bar_range_stop'];
      
        $printNormal = False;
        $printRODAC = False;

        if(isset($_POST['print_normal_samples'])){
            $printNormal = True;
        }

        if(isset($_POST['print_rodac_samples'])){
            $printRODAC = True;
        }

        $samples = upa('samples', 'grabBetweenBarcodes', array($sampleRangeStart, $sampleRangeEnd), False);

        foreach($samples as $sample){

            $doPrint = False;
            if($sample['sample_type'] == 'S' && $printNormal == True){
                $doPrint = True;
            }

            if($sample['sample_type'] == 'L' && $printNormal == True){
                $doPrint = True;
            }

            if($sample['sample_type'] == 'R' && $printRODAC == True){
                $doPrint = True;
            }

            if($doPrint == True){
                $this->runS2P($sample['barcode'], True);
            }

        }

        $this->releaseSpool();
    }

    function runRangeS2P(){
        $this->render = False;
        $dateRangeStart = strtotime($_POST['range_start']);
        $dateRangeEnd = strtotime($_POST['range_stop']);
        $dateRangeEnd = $dateRangeEnd + (60 * 60 * 23); //transform to end-of-day
        $dateRangeType = $_POST['range_type_select'];

        if($dateRangeType[0] == 'p'){
            $searchTag = substr($dateRangeType, 2);
            $projectsInvolved = upa('projects', 'getProjectsInRoughDate', array($dateRangeStart, $dateRangeEnd, $searchTag ), False);
            foreach($projectsInvolved as $project){
                //grab samples and shunt them to S2P
                $samplesInThisProject = upa('samples', 'fetchSamplesInProject', array($project['id']), False);
                foreach($samplesInThisProject as $sample){
                    $this->runS2P($sample['barcode'], True);
                }
            }
        }
        elseif($dateRangeType[0] == 's'){
            $searchTag = substr($dateRangeType, 2);
            $samplesInvolved = upa('samples', 'getSamplesInRoughDate', array($dateRangeStart, $dateRangeEnd, $searchTag ), False);
            foreach($samplesInvolved as $sample){
                $this->runS2P($sample['barcode'], True);
            }
        } elseif($dateRangeType[0] == 'i'){
            $samplesInvolved = upa('samples', 'getSamplesInRoughDate', array($dateRangeStart, $dateRangeEnd, False, True ), False);
            foreach($samplesInvolved as $sample){
                $this->runS2P($sample['barcode'], True);
            }
        } else{
            $samplesInvolved = upa('samples', 'getSamplesInRoughDate', array($dateRangeStart, $dateRangeEnd, False ), False);
            foreach($samplesInvolved as $sample){
                $this->runS2P($sample['barcode'], True);
            }
        }

        $this->releaseSpool();
    }

    function runS2P($barcode = False, $keepSpool = False){
        //sample_label_select assay_label_select
        //print_sample    printer print_sample_amount print_analysis also_print also_print_selection also_print_amount
        $this->render = False;

        if($barcode == False){
            $barcode = $_POST['barcode'];
        }

        //check bar valid
        if(!isset($_POST['barcode']) && $barcode == False){
            print 'BAR_WRONG';
            return;
        }

        $barExp = explode('.', $barcode);
        $sampleBar = $barExp[0];
                
        
        $sampleId = upa('samples', 'barcodeToId', array($sampleBar));
        $sample = upa('samples', 'fetch', array($sampleId));

        if(empty($sample) || $sample == False){
            print 'BAR_WRONG';
            return;
        } else{
            $this->innitInfo($sample['id']);
        }

        /*
         * Valid bar requested, gather needed information on labels and printers
         */

        //printer
        //$printerInfo = upa('printers', 'fetch', array($_POST['printer']));

        //samp and anlaysis label
        if($_POST['sample_label_select'] == 'default'){
            $sampleLabel = upa('labelDesigns', 'returnDefaultSampleLabel', array(), False);
        } else{
            $sampleLabel = upa('labelDesigns', 'fetch', array($_POST['sample_label_select']) );
        }

        if($_POST['assay_label_select'] == 'default'){
            $assayLabel = upa('labelDesigns', 'returnAnalysisLabel', array(), False);
        } else{
            $assayLabel =  upa('labelDesigns', 'fetch', array($_POST['assay_label_select']) );
        }

        if($assayLabel == False){
            print 'NO_DEFAULT_ASSAY';
            return;
        }

        if($sampleLabel == False){
            print 'NO_DEFAULT_SAMPLE';
            return;
        }

        if(isset($_POST['duplicate_label'])){

            $printerInfo = $this->fetchPrinterInfo($sampleLabel['default_printer'], $_POST['printer']);
            $barLength = count($barExp);
            if($barLength == 1){
              $this->printLabel($sampleLabel['zpl'], 1, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
            }

            if($barLength == 2){
              //assay labels
              $assays = upa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId));
              $followNumber = $barExp[1];

              foreach($assays as $arrId => $assay){
                if($assay['follow_number'] == $followNumber){
                  $this->assayAddFire($assay['id'], True, $assayLabel, '1', $printerInfo);
                }
              }
            }

            if($barLength == 3){
              //assay, dillution /replicate label
              $assays = upa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId));
              $followNumber = $barExp[1];
              $dINumber = $barExp[2];

              foreach($assays as $arrId => $assay){
                if($assay['follow_number'] == $followNumber){
                    $this->assayAddFire($assay['id'], True, $assayLabel, '1', $printerInfo, $dINumber);
                }
              }

            }
        }

        if(isset($_POST['print_sample'])){
            if($_POST['assay_type_select'] == '0') {
                $printerInfo = $this->fetchPrinterInfo($sampleLabel['default_printer'], $_POST['printer']);
                $this->printLabel($sampleLabel['zpl'], $_POST['print_sample_amount'], $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
            } else{
                $printerInfo = $this->fetchPrinterInfo($sampleLabel['default_printer'], $_POST['printer']);
                $assays = upa('sampleAnalysis', 'fetchTypeSpecificAnalysisArray', array($sampleId, $_POST['assay_type_select']));
                if(!empty($assays)){
                    $this->printLabel($sampleLabel['zpl'], $_POST['print_sample_amount'], $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                }
            }
        }

        if(isset($_POST['print_analysis'])){
            $printerInfo = $this->fetchPrinterInfo($assayLabel['default_printer'], $_POST['printer']);

            if($_POST['assay_type_select'] == '0'){
                $assays = upa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId));
            } else{
                $assays = upa('sampleAnalysis', 'fetchTypeSpecificAnalysisArray', array($sampleId, $_POST['assay_type_select']));
            }


            foreach($assays as $arrId => $assay){
                $this->assayAddFire($assay['id'], True, $assayLabel, '1', $printerInfo);
            }
        }

        if(isset($_POST['also_print'])){
            $alsoLabel = upa('labelDesigns', 'fetch', array($_POST['also_print_selection']), False);
            $printerInfo = $this->fetchPrinterInfo($alsoLabel['default_printer'], $_POST['printer']);
            $this->printLabel($alsoLabel['zpl'], $_POST['also_print_amount'], $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
        }

        /* PROFILES HERE */
        //grab associated print profiles

        foreach($_POST as $postKey => $pProfileId){
            if(substr($postKey, 0, 8) == 'pprofile'){
              $this->runPrintProfile($pProfileId, $sample, $sampleId, $_POST['printer'] );
            }
        }

        if($keepSpool == False){
            $this->releaseSpool();
        }

    }


    private function fetchPrinterInfo($default, $printerId){

        if($printerId == 0){
            $printerInfo =  upa('printers', 'fetch', array($default));
        } else{
            $printerInfo =  upa('printers', 'fetch', array($printerId));
        }

        return $printerInfo;
    }

    function lists(){

        $sForm = new formFactory($this);
        $sForm->setId('listsForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');

        $lists = array();
        $lists['NULL'] = 'Selecteer een lijst';
        $lists['assays'] = 'Analyses';
        $lists['profiles'] = 'Onderzoeks profielen';
        $lists['assayTypes'] = 'Onderzoeks types';
        $lists['samplingMethods'] = 'Monstername procedures';
        $sForm->addDropdownField('export_list', False, 'input-block-level', false, $lists, False, '{MESA_S2P_SELECTLIST}');
        $this->_template->set('lists_form', $sForm->render());


        $wForm = new formFactory($this);
        $wForm->setId('worklistForm');
        $wForm->action('{LB}/workLists/exportWorkList');
        $wForm->method('POST');
        $wForm->addClass('');
        $wForm->setName('worklist');
        $wForm->target('_blank');
        $wForm->setTemplate('target');
        //$assays = upa('assays', 'assayDropdown', array(), False);

        //$assays = array();
        //$assays['listeria'] = 'Listeria';

        $assays = upa('workLists', 'getDropper', array(), False);
        $assays['all'] = 'Alle lijsten met monsters printen';


        //$templates = array();
        //$templates['listeria'] = "Listeria werkformulier";

        $wForm->addDropdownField('work_list_type', False, 'input-block-level', 'all', $assays, False, '{MESA_S2P_SELECTLIST}');
        //$wForm->addDropdownField('work_list_template', False, 'input-block-level', false, $templates , False, 'Selecteer formulier');
        $wForm->addInputField('work_list_date', 'Inzet datum:', 'text', 'input-block-level', '{today}', 'Datum', False, false);
        $wForm->submitTrough('exportWorkList');
        $this->_template->set('worklist_form', $wForm->render());

    }

    function printList($table){

        $this->render = false;

        // Output CSV-specific headers
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment;filename=lijst_'.$table . '.csv');
        $fh = fopen('php://output', 'w');

        if($table == 'assays'){

            $assays = upa('assays', 'getAssayList', array(), False);
            $assayFields = upa('assayFields', 'getAssayFields', array(), False);
            $headings = array('id', 'naam', 'basis assay', 'verdunningen', 'duplos', 'bevestiging', 'bevestigings type', 'telbaar max', 'telbaar min', 'tijd', 'start vanaf');

            foreach($assayFields as $assayField){
                array_push($headings, $assayField['name']);
            }

            fputcsv($fh, $headings);

            foreach($assays as $assay){
                $assayPrint = array(
                    $assay['id'],
                    $assay['name'],
                    $assay['type_base'],
                    $assay['dillution'],
                    $assay['replicates'],
                    $assay['confirmation'],
                    $assay['confirmation_type'],
                    $assay['max_count'],
                    $assay['min_count'],
                    $assay['duration'],
                    $assay['start_from'],
                );

                $custom = json_decode($assay['custom_fields'], JSON_FORCE_OBJECT);
                foreach($assayFields as $assayField){
                    $name = $assayField['name'];

                    //$pushValue = checkKeyOrFalse($custom, $name);
                    $pushValue = '';
                    if(array_key_exists($name, $custom)){
                      $pushValue = $custom[$name];
                    }

                    array_push($assayPrint, $pushValue);
                }
                fputcsv($fh, $assayPrint);
            }
        }

        if($table == 'profiles'){
            $profiles = upa('researchProfiles', 'getProfileList', array(), False);
            $headings = array('id','naam', 'klant');
            fputcsv($fh, $headings);

            foreach($profiles as $profile){

                if($profile['client'] == 0){
                    $clientName = 'Globaal';
                } else{
                    $clientName = upa('clients', 'clientIdToName', array($profile['client']));
                }

                $profilePrint = array(
                    $profile['id'],
                    $profile['name'],
                    $clientName
                );

                fputcsv($fh, $profilePrint);
            }
        }

        if($table == 'assayTypes'){
            $types = upa('assayTypes', 'listAssayTypes', array(), False);
            $headings = array('id','naam', 'omschrijving');
            fputcsv($fh, $headings);

            foreach($types as $type){

                $typePrint = array(
                    $type['id'],
                    $type['name'],
                    $type['description']
                );

                fputcsv($fh, $typePrint);
            }
        }

        if($table == 'samplingMethods'){

            $samplingTypes = upa('sampleProcedures', 'getProceduresList', array(), False);
            $customFields = upa('sampleProcedureFields', 'getFields', array(), False);

            $headings = array('id', 'naam');

            foreach($customFields as $customField){
                array_push($headings, $customField['name']);
            }

            fputcsv($fh, $headings);
            foreach($samplingTypes as $samplingType){

                    $samplePrint = array(
                        $samplingType['id'],
                        $samplingType['name']
                    );

                    $thisCustom = json_decode($samplingType['fields'], JSON_FORCE_OBJECT);

                    foreach($customFields as $customField){
                        $custoFieldName = $customField['name'];
                        array_push($samplePrint, $thisCustom[$custoFieldName]);
                    }
                fputcsv($fh, $samplePrint);
            }
        }

        fclose($fh);
    }

    private function checkPrinterOveride($printerId, $override){
      if($override == 0){
        return $printerId;
      } else{
        return $override;
      }
    }

    function runPrintProfile($id, $sample, $sampleId, $printerOveride = False){

        $this->Printing->where('id', $id);
        $result = $this->Printing->search();
        $this->Printing->deepFreed();

        //cphp('Profile started');

        if(empty($result)){
            writeLog('Could not find specified print profile', ALPC_ERROR);
            return;
        }

        $profile = $result[0];
        $instructions = array();
        $profileLines = explode(PHP_EOL, $profile['profile']);

        //rudimentary error check
        foreach($profileLines as $profileLine){
            if(empty($profileLine) || substr($profileLine, 0, 1) == '#'){
                continue;
            }
            $lineSplit = explode(" ", $profileLine,2);
            $instructionNumber = $lineSplit[0];
            if(array_key_exists($instructionNumber, $instructions)){
                writeLog('Error in print instruction file, id ' . $id );
                return;
            }
            $instructions[$instructionNumber] = $lineSplit[1];
        }


        $insObj = new ArrayObject($instructions);
        $insIter = $insObj->getIterator();

        foreach($insIter as $key => $val){

            $val = preg_replace('/\s+/', ' ',$val);
            $ins = explode(" ", $val);

            if(!array_key_exists(0, $ins)){                
                writeLog('No primary instruction set on line/id ' . $key . '/' . $id );
            }

            //check for exit
            if($ins[0] == 'EXIT'){
                return;
            }

            //check for redirect
            if($ins[0] == 'GOTO'){
                if(array_key_exists(1, $ins)){
                    $goto = (int)trim($ins[1]);
                    $goto = ( $goto / 10 ) - 2;
                    $insIter->seek($goto);
                } else{                    
                    return;
                }
            }

            //check for log
            if($ins[0] == 'LOG'){
                
            }

            # a comment, do nothing here 
            if($ins[0] == 'COMMENT'){                
            }

            /*
             * ANALYSIS
             */
            if($ins[0] == 'ANALYSIS'){

                //print any analysis attached to this sample
                if($ins[1] == 'ANY'){
                    $label = $ins[3];
                    $printer = $ins[5];
                    $copies = $ins[7];

                    $restrict = False;
                    if(array_key_exists(8, $ins)){
                        if($ins[8] == 'SINGLE'){
                          $restrict = 1;
                        }
                    }

                    $assays = upa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId));
                    $assayLabel =  upa('labelDesigns', 'fetch', array($label));

                    //checkPrinterOveride($printer, $printerOveride );
                    $printerInfo = $this->fetchPrinterInfo(0, $this->checkPrinterOveride($printer, $printerOveride ));
                    foreach($assays as $arrId => $assay){
                        $this->assayAddFire($assay['id'], True, $assayLabel, $copies, $printerInfo, $restrict);
                    }
                }

                //print any analysis except ones listed
                if($ins[1] == 'NOT' ){
                    $label = $ins[4];
                    $printer = $ins[6];
                    $copies = $ins[8];

                    $restrict = False;
                    if(array_key_exists(9, $ins)){
                        if($ins[9] == 'SINGLE'){
                          $restrict = True;
                        }
                    }

                    $assayLabel =  upa('labelDesigns', 'fetch', array($label));
                    $printerInfo = $this->fetchPrinterInfo(0, $this->checkPrinterOveride($printer, $printerOveride ));
                    $analysisBlock = json_decode($ins[2], JSON_FORCE_OBJECT);
                    $assays = upa('sampleAnalysis', 'fetchAssaySpecificAnalysisArray', array($sampleId, $analysisBlock, True));
                    foreach($assays as $arrId => $assay){
                        $this->assayAddFire($assay['id'], True, $assayLabel, $copies, $printerInfo, $restrict);
                    }
                }

                //only these analysis need to be printed
                if($ins[1] == 'ONLY' ){
                    $label = $ins[4];
                    $printer = $ins[6];
                    $copies = $ins[8];

                    $restrict = False;
                    if(array_key_exists(9, $ins)){
                        if($ins[9] == 'SINGLE'){
                          $restrict = 1;
                        }
                    }

                    $assayLabel =  upa('labelDesigns', 'fetch', array($label));
                    $printerInfo = $this->fetchPrinterInfo(0, $this->checkPrinterOveride($printer, $printerOveride ));
                    $analysisBlock = json_decode($ins[2], JSON_FORCE_OBJECT);
                    $assays = upa('sampleAnalysis', 'fetchAssaySpecificAnalysisArray', array($sampleId, $analysisBlock, False));
                    foreach($assays as $arrId => $assay){
                        $this->assayAddFire($assay['id'], True, $assayLabel, $copies, $printerInfo, $restrict);
                    }
                }

                //print only analysis in this analysis type
                if($ins[1] == 'GROUP' ){
                    $label = $ins[4];
                    $printer = $ins[6];
                    $copies = $ins[8];

                    $restrict = False;
                    if(array_key_exists(9, $ins)){
                        if($ins[9] == 'SINGLE'){
                          $restrict = 1;
                        }
                    }

                    $assayLabel =  upa('labelDesigns', 'fetch', array($label));
                    $printerInfo = $this->fetchPrinterInfo(0, $this->checkPrinterOveride($printer, $printerOveride ));
                    $analysisBlock = json_decode($ins[2], JSON_FORCE_OBJECT);
                    $assays = upa('sampleAnalysis', 'fetchTypeSpecificAnalysisArray', array($sampleId, $analysisBlock));
                    foreach($assays as $arrId => $assay){
                        $this->assayAddFire($assay['id'], True, $assayLabel, $copies, $printerInfo, $restrict);
                    }
                }

                //print only analysis which are NOT this group
                if($ins[1] == 'NOTGROUP' ){
                    $label = $ins[4];
                    $printer = $ins[6];
                    $copies = $ins[8];

                    $restrict = False;
                    if(array_key_exists(9, $ins)){
                        if($ins[9] == 'SINGLE'){
                          $restrict = True;
                        }
                    }

                    $assayLabel =  upa('labelDesigns', 'fetch', array($label));
                    $printerInfo = $this->fetchPrinterInfo(0, $this->checkPrinterOveride($printer, $printerOveride ));
                    $analysisBlock = json_decode($ins[2], JSON_FORCE_OBJECT);
                    $assays = upa('sampleAnalysis', 'fetchAssaySpecificAnalysisArray', array($sampleId, $analysisBlock, True));
                    foreach($assays as $arrId => $assay){
                        $this->assayAddFire($assay['id'], True, $assayLabel, $copies, $printerInfo, $restrict);
                    }
                }
            }

            /*
             * SAMPLE
             */
            if($ins[0] == 'SAMPLE'){

                if($ins[1] == 'PRINT' ){
                    $label = $ins[2];
                    $printer = $ins[4];
                    $copies = $ins[6];
                    $sampleLabel =  upa('labelDesigns', 'fetch', array($label));
                    $printerInfo = $this->fetchPrinterInfo(0, $this->checkPrinterOveride($printer, $printerOveride ));
                    $this->printLabel($sampleLabel['zpl'], $copies, $printerInfo['id'], $printerInfo['type'], $printerInfo['adres'], $printerInfo['port']);
                }

                if($ins[1] == 'HASONLYONE'){

                  if($ins[2] == 'GOTO'){
                    $goto = $ins[3];
                    $elseGoto = False;
                    if(array_key_exists(5, $ins)){
                        $elseGoto = $ins[5];
                    }
                  }

                  $count = upa('sampleAnalysis', 'countAnalysis', array($sampleId), False);
                  if($count <= 1){
                    $goto = (int)trim($goto);
                    $goto = ( $goto / 10 ) - 2;
                    $insIter->seek($goto);
                  } else{
                    $elseGoto = (int)trim($elseGoto);
                    $elseGoto = ( $elseGoto / 10 ) - 2;
                    $insIter->seek($elseGoto);
                  }

                }

                //this is only a switch statement, no printing should occur from here
                if($ins[1] == 'HASANY' ){
                    $analysisBlock = json_decode($ins[2], JSON_FORCE_OBJECT);

                    if($ins[3] == 'GOTO'){
                        $goto = $ins[4];
                        $elseGoto = False;
                        if(array_key_exists(6, $ins)){
                            $elseGoto = $ins[6];
                        }

                        $assays = upa('sampleAnalysis', 'fetchAssaySpecificAnalysisArray', array($sampleId, $analysisBlock));
                        if(empty($assays)){
                            $elseGoto = (int)trim($elseGoto);
                            $elseGoto = ( $elseGoto / 10 ) - 2;
                            $insIter->seek($elseGoto);
                        } else{
                            $goto = (int)trim($goto);
                            $goto = ( $goto / 10 ) - 2;
                            $insIter->seek($goto);
                        }
                    }
                }

                if($ins[1] == 'HASALL' ){
                    $analysisBlock = json_decode($ins[2], JSON_FORCE_OBJECT);

                    if($ins[3] == 'GOTO'){
                        $goto = $ins[4];
                        $elseGoto = False;
                        if(array_key_exists(6, $ins)){
                            $elseGoto = $ins[6];
                        }

                        $assays = upa('sampleAnalysis', 'fetchAssaySpecificAnalysisArray', array($sampleId, $analysisBlock));
                        $numReturn = count($assays);
                        $numRequested = count($analysisBlock);


                        if($numReturn < $numRequested){
                            $elseGoto = (int)trim($elseGoto);
                            $elseGoto = ( $elseGoto / 10 ) - 2;
                            $insIter->seek($elseGoto);
                        } else{
                            $goto = (int)trim($goto);
                            $goto = ( $goto / 10 ) - 2;
                            $insIter->seek($goto);
                        }
                    }
                }

            }

            //$insIter->next();
            if (!$insIter->valid()) break;
        }
    }

    public function printLegLabels(){

        $this->render = false;

        if(!is_numeric($_POST['legFrom']) || !is_numeric($_POST['legTo']) ){
            return; 
        }


        $from = (int)$_POST['legFrom'];
        $to = (int)$_POST['legTo'];
        $matrix = $_POST['legMatrix'];
        $printer = $_POST['legPrinter'];
       

        $matrixA = array();
        
        $matrixA[0] = array('media' => 'BCYE +',  'series_type' => 'KOUD', 'temperature' => '', 'follow' => 2 );
        $matrixA[1] = array('media' => 'BCYE +',  'series_type' => 'ZUUR', 'temperature' => '', 'follow' =>  7 );
        $matrixA[2] = array('media' => 'BCYE +',  'series_type' => 'WARM', 'temperature' => '', 'follow' => 5 );        
        $matrixA[3] = array('media' => 'BCYE',  'series_type' => 'KOUD', 'temperature' => '', 'follow' => 1 );
        $matrixA[4] = array('media' => 'BCYE',  'series_type' => 'ZUUR', 'temperature' => '', 'follow' => 6 );
        $matrixA[5] = array('media' => 'BCYE',  'series_type' => 'WARM', 'temperature' => '', 'follow' =>  4 );
        $matrixA[6] = array('media' => 'BCYE -',  'series_type' => 'KOUD', 'temperature' => '', 'follow' => 3 );
                               
        $matrixB = array();

        $matrixB[0] = array('media' => 'MWY',  'series_type' => 'KOUD', 'temperature' => '', 'follow' =>  1 );
        $matrixB[1] = array('media' => 'MWY',  'series_type' => 'ZUUR', 'temperature' => '','follow' =>  3 );
        $matrixB[2] = array('media' => 'MWY',  'series_type' => 'WARM', 'temperature' => '', 'follow' =>  2 );
                        
        $countOfNumbers = $to - $from;        

        $printerInfo = $this->fetchPrinterInfo(False, $printer);
        $zplStack = '';

        for($x = $from; $x <= $to; $x++){
            
            if($matrix == 'A'){                
                //$followNumber = 1;
                foreach($matrixA as $inx => $templateContent){                    
                    $templateContent['barcode'] = 'LA' . $x . '.' . $templateContent['follow'] . '.1';
                    $templateContent['short_code'] = 'LA' . $x ;
                    $label = generateHTML('legionella/leglabel', $templateContent );
                    $zplStack .= $label;                    
                    //$followNumber++;
                }               
            }

            if($matrix == 'B'){
                //$followNumber = 1;
                foreach($matrixB as $inx => $templateContent){                    
                    $templateContent['barcode'] = 'LB' . $x . '.' . $templateContent['follow'] . '.1';
                    $templateContent['short_code'] = 'LB' . $x ;
                    $label = generateHTML('legionella/leglabel', $templateContent );
                    $zplStack .= $label;                    

                    $templateContent['barcode'] = 'LB' . $x . '.' . $templateContent['follow'] . '.2';
                    $label = generateHTML('legionella/leglabel', $templateContent );
                    $zplStack .= $label;                    
                    
                    //$followNumber++;
                }                           
            }
        
        }
        
        $this->printSpool[$printer] = array();
        $this->printSpool[$printer]['conType'] = $printerInfo['type'];
        $this->printSpool[$printer]['conAdres'] = $printerInfo['adres'];
        $this->printSpool[$printer]['conPort'] = $printerInfo['port'];
        $this->printSpool[$printer]['spool'] = $zplStack;
        $this->releaseSpool();       
    }

    public function legionellaLabelsPDF($start, $stop){
        $this->render = False;
        $stop = $stop + 1;

        //header('Content-Type: application/pdf');
        require_once( ROOT . '/library/mpdf/mpdf.php');
        $this->_pdfHandle = new mPDF('utf-8','A4','7','arial',8,8,21.5,0,12.5,12.5, 'P');
        $this->_pdfHandle->debug = True;

        $pdfContent = ''; //initialize empty pdf contents

        $numberOfSamples =  $stop - $start;
        $pagesRaw = $numberOfSamples / 4  ; //4 columns per page
        $pages = ceil($pagesRaw);
        $firstPage = True;

        //print $numberOfSamples . '<br />';
        $assayPointers = array();
        $assayPointers[0] = array();
        $assayPointers[0]['pointer'] = '.2.1';
        $assayPointers[0]['label'] = 'BCYE +';
        $assayPointers[0]['color'] = 'blue';

        $assayPointers[1] = array();
        $assayPointers[1]['pointer'] = '.2.2';
        $assayPointers[1]['label'] = 'BCYE + ';
        $assayPointers[1]['color'] = 'blue';

        $assayPointers[2] = array();
        $assayPointers[2]['pointer'] = '.1.1';
        $assayPointers[2]['label'] = 'BCYE';
        $assayPointers[2]['color'] = 'blue';

        $assayPointers[3] = array();
        $assayPointers[3]['pointer'] = '.1.2';
        $assayPointers[3]['label'] = 'BCYE';
        $assayPointers[3]['color'] = 'blue';

        $assayPointers[4] = array();
        $assayPointers[4]['pointer'] = '.3.1';
        $assayPointers[4]['label'] = 'BCYE -';
        $assayPointers[4]['color'] = 'blue';

        $assayPointers[5] = array();
        $assayPointers[5]['pointer'] = '.5.1';
        $assayPointers[5]['label'] = 'BCYE +';
        $assayPointers[5]['color'] = 'red';

        $assayPointers[6] = array();
        $assayPointers[6]['pointer'] = '.5.2';
        $assayPointers[6]['label'] = 'BCYE +';
        $assayPointers[6]['color'] = 'red';

        $assayPointers[7] = array();
        $assayPointers[7]['pointer'] = '.4.1';
        $assayPointers[7]['label'] = 'BCYE ';
        $assayPointers[7]['color'] = 'red';

        $assayPointers[8] = array();
        $assayPointers[8]['pointer'] = '.4.2';
        $assayPointers[8]['label'] = 'BCYE';
        $assayPointers[8]['color'] = 'red';

        $assayPointers[9] = array();
        $assayPointers[9]['pointer'] = '.6.1';
        $assayPointers[9]['label'] = 'BCYE -';
        $assayPointers[9]['color'] = 'red';

        $currentSample = $start;

        for($p = 0; $p < $pages; $p++){

            $samplesOnPage = array();
            $samplesOnPage[1] = $currentSample;
            $samplesOnPage[2] = $currentSample + 1;
            $samplesOnPage[3] = $currentSample + 2;
            $samplesOnPage[4] = $currentSample + 3;
            $currentSample = $currentSample + 4;

            if($firstPage == False){
                $pdfContent .= '<pagebreak />';
            }

            $samplesPassed = $p * 4;
            $samplesLeft = $numberOfSamples - $samplesPassed;

            if($samplesLeft > 4){
                $columnsOnThisPage = 4;
            } else{
                $columnsOnThisPage = $samplesLeft;
            }
            $pdfContent .= '<table>';

            //rows
            for($y = 0; $y < 10; $y++ ){

                //need to add a correction line here to keep stickers aligned
                if($y == 6){
                  $pdfContent .= '<tr style="height: 0.25cm;">';
                  $pdfContent .= '<td style="height: 0.25cm;" colspan="' . $columnsOnThisPage .'">&nbsp;</td>';
                  $pdfContent .= '</tr>';
                }

                $pdfContent .= '<tr style="height: 2.54cm;">';
                //print 'Page: ' . $p . ' row: ' . $y . '<br />';;

                for($i = 1; $i <= $columnsOnThisPage; $i++){



                    $pdfContent .= '<td style="height: 2.54cm; width: 4.85cm; text-align: center;">';

                    $thisFullBar = 'L' . $samplesOnPage[$i] . $assayPointers[$y]['pointer'];
                    $thisTextBar = '' . $samplesOnPage[$i];
                    $thisBarCode = $this->bar($thisFullBar);

                    $thisTextLabel = $assayPointers[$y]['label'];

                    $pdfContent .=  '<img src="data:image/jpeg;base64,' . $thisBarCode . '" style="padding: 0px; margin: 0px;" width="160px" />';
                    $pdfContent .=  '<p style="font-weight: bold; font-size: 14px;">' . $thisTextBar . '</p>';
                    $pdfContent .=  '<p style="font-weight: bold; font-size: 14px; color: ' . $assayPointers[$y]['color'].';">' . $thisTextLabel . '</p>';
                    $pdfContent .= '</td>';
                }

                $pdfContent .= '</tr>';

            }

            $pdfContent .= '</table>';
            $firstPage = False;
        }

        $this->_pdfHandle->WriteHTML($pdfContent);
        $this->_pdfHandle->Output('legionella_stickers.pdf', 'I');


    }




    function bar($string, $size = 25){

        $this->render = False;

        //$text = (isset($_GET["text"])?$_GET["text"]:"0");
        $text = $string;
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
        } elseif ( strtolower($code_type) == "code128a" ) {
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
        }
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
