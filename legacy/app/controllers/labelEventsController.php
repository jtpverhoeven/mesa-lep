<?php

class labelEventsController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function fetchEvents($group){

        $this->LabelEvent->where('event_group', $group);
        $results = $this->LabelEvent->search();

        if(!empty($results)){
            return $results;
        } else{
            return array();
        }
    }

    function listing(){

        $this->LabelEvent->where('event_group', 1);
        $resultGroup1 = $this->LabelEvent->search();
        $this->LabelEvent->free();
        $this->LabelEvent->where('event_group', 2);
        $resultGroup2 = $this->LabelEvent->search();
        $this->LabelEvent->free();
        $this->LabelEvent->where('event_group', 3);
        $resultGroup3 = $this->LabelEvent->search();

        $table = new tableFactory();
        $table->setTableId('group1Events');
        $table->loadTemplate('labelEventsTable');

        $table2 = new tableFactory();
        $table2->setTableId('group2Events');
        $table2->loadTemplate('labelEventsTable');

        $table3 = new tableFactory();
        $table3->setTableId('group3Events');
        $table3->loadTemplate('labelEventsTable');

        $sampleTypes = array();
        $sampleTypes['L'] = 'Legionella';
        $sampleTypes['S'] = 'Normaal';
        $sampleTypes['R'] = 'RODAC';

        foreach($resultGroup1 as $eventId => $event){

            $printerInfo = upa('printers', 'fetch', array($event['print_to']));
            $labelInfo = upa('labelDesigns', 'fetch', array($event['print_label']));

            $resultGroup1[$eventId]['printer_name'] = $printerInfo['name'];
            $resultGroup1[$eventId]['label_name'] = $labelInfo['name'];
            $resultGroup1[$eventId]['event_group'] = '1';

            if($event['event'] == 'P'){
                $resultGroup1[$eventId]['event_name'] = '{MESA_LBD_PRINTNORMAL}';
                $resultGroup1[$eventId]['event_description'] = '';
            } elseif($event['event'] == 'L'){

                $logicArray = json_decode($event['event_data'], True);
                if($logicArray['negative'] == '1'){
                    $resultGroup1[$eventId]['event_name'] = '{MESA_LBD_PRINTLOGIC} {MESA_LSB_NOT}';
                } else{
                    $resultGroup1[$eventId]['event_name'] = '{MESA_LBD_PRINTLOGIC}';
                }

                if($logicArray['type'] == 'sField'){
                    $resultGroup1[$eventId]['event_description'] = $logicArray['inspect'] . ' {MESA_LBD_SAMPFIELDEQUALS}: ' . $logicArray['inspect_value'];
                }elseif($logicArray['type'] == 'sClient'){
                    $clientName = upa('clients', 'fetch', array($logicArray['inspect_value']), False);
                    $resultGroup1[$eventId]['event_description'] = '{MESA_LBD_CLIENTFIELDFILTER}: ' . $clientName['name'];
                }elseif($logicArray['type'] == 'mMethod'){
                    $methodName = upa('sampleProcedures', 'fetch',array($logicArray['inspect_value']), False);
                    $resultGroup1[$eventId]['event_description'] = '{MESA_LBD_SELECTSAMPLETAKE}: ' . $methodName['name'];
                }elseif($logicArray['type'] == 'mType'){
                  $methodName = checkKeyOrFalse($sampleTypes, $logicArray['inspect_value']);
                  $resultGroup1[$eventId]['event_description'] = 'Monster type: ' . $methodName;
                }
            }
        }

        foreach($resultGroup2 as $eventId => $event){

            $printerInfo = upa('printers', 'fetch', array($event['print_to']));
            $labelInfo = upa('labelDesigns', 'fetch', array($event['print_label']));

            $resultGroup2[$eventId]['printer_name'] = $printerInfo['name'];
            $resultGroup2[$eventId]['label_name'] = $labelInfo['name'];
            $resultGroup2[$eventId]['event_group'] = '2';

            if($event['event'] == 'P'){
                $resultGroup2[$eventId]['event_name'] = '{MESA_LBD_PRINTNORMAL}';
                $resultGroup2[$eventId]['event_description'] = '';
            } elseif($event['event'] == 'L'){
                $logicArray = json_decode($event['event_data'], True);
                if($logicArray['negative'] == '1'){
                    $resultGroup2[$eventId]['event_name'] = '{MESA_LBD_PRINTLOGIC} {MESA_LSB_NOT}';
                } else{
                    $resultGroup2[$eventId]['event_name'] = '{MESA_LBD_PRINTLOGIC}';
                }

                if($logicArray['type'] == 'assayIs'){
                    $assaysIncluded = explode(',', $logicArray['inspect_value']);
                    $assaysIncludedText = '';
                    foreach($assaysIncluded as $assay){
                        $assayInfo = upa('assays', 'fetch', array($assay), False);
                        $assaysIncludedText .= $assayInfo['name'] . ',';
                    }

                    $resultGroup2[$eventId]['event_description'] = '{MESA_LSB_ISCERTAINASSAY}: ' .  $assaysIncludedText;
                }

                if($logicArray['type'] == 'assayTypeIs'){

                    $assayTypeInfo = upa('assayTypes', 'fetch', array($logicArray['inspect_value']), False);
                    $resultGroup2[$eventId]['event_description'] = '{MESA_LSB_ISCERTAINASSAYTYPE}: ' .  $assayTypeInfo['name'];
                }
            }

        }

        foreach($resultGroup3 as $eventId => $event){

            $printerInfo = upa('printers', 'fetch', array($event['print_to']));
            $labelInfo = upa('labelDesigns', 'fetch', array($event['print_label']));

            $resultGroup3[$eventId]['printer_name'] = $printerInfo['name'];
            $resultGroup3[$eventId]['label_name'] = $labelInfo['name'];
            $resultGroup3[$eventId]['event_group'] = '3';

            if($event['event'] == 'P'){
                $resultGroup3[$eventId]['event_name'] = '{MESA_LBD_PRINTNORMAL}';
                $resultGroup3[$eventId]['event_description'] = '';
            } 

        }

        if(!empty($resultGroup1)){
            $table->loadValues($resultGroup1);
        } else{
            $table->loadValues('{MESA_LSB_NORULESFOUND}');
        }

        if(!empty($resultGroup2)){
            $table2->loadValues($resultGroup2);
        } else{
            $table2->loadValues('{MESA_LSB_NORULESFOUND}');
        }

        if(!empty($resultGroup3)){
            $table3->loadValues($resultGroup3);
        } else{
            $table3->loadValues('{MESA_LSB_NORULESFOUND}');
        }




        $this->_template->set('group1Table', $table->renderTable());
        $this->_template->set('group2Table', $table2->renderTable());
        $this->_template->set('group3Table', $table3->renderTable());
    }

    function generateEventForm($eventGroup, $editId = False){

        $this->doNotRenderHeader = True;

        $eventInfo = array();
        $eventLogic = array();

        if($editId != False && $editId != 'undefined'){
            $eventInfo = upa('labelEvents', 'fetch', array($editId), False);
            $eventLogic = json_decode($eventInfo['event_data'], JSON_FORCE_OBJECT);
        }

        $aF = new formFactory('labelEvents');
        $aF->setId('addLabelEventForm');
        $aF->addClass('');
        //$aF->action( ALPC_BASEPATH . '/userGroups/saveGroup');
        $aF->action('#');
        $aF->method('POST');
        $aF->setTemplate('generic');

        $option['P'] = '{MESA_LBD_PRINTNORMAL}';

        if($eventGroup != '3')
        {
            $option['L'] = '{MESA_LBD_PRINTLOGIC}';    
        }

        
        $negOp['0'] = '{MESA_LBD_NO}';
        $negOp['1'] = '{MESA_LBD_YES}';

        $aF->addDropdownField('event', '{MESA_LBD_EVENTTYPE}', 'input-block-level', checkKeyOrBlank($eventInfo, 'event'), $option, False);
                
        //$aF->addInputField('event_group', False, 'hidden', '',  checkKeyOrBlank($eventInfo, 'event_group'), False, False);
        $aF->addInputField('event_group', False, 'hidden', '', $eventGroup, False, False);
        $aF->addInputField('event_id', False, 'hidden', '' , checkKeyOrBlank($eventInfo, 'id'), False, False);

        if($eventGroup == '1'){
            //sample based logic
            $sLogic['NULL'] = '{MESA_LBD_SELECT}';
            $sLogic['sField'] = '{MESA_LBD_SAMPLEFIELDFILTER}';
            $sLogic['sClient'] = '{MESA_LBD_CLIENTFIELDFILTER}';
            $sLogic['mMethod'] = '{MESA_LBD_SAMPMETHODFILTER}';
            $sLogic['mType'] = 'Monster type';

            $foundEvent1Type = checkKeyOrFalse($eventLogic, 'type');
            $aF->addDropdownField('group1_condition', '{MESA_LBD_CONDITION}', 'input-block-level', $foundEvent1Type, $sLogic, False);

            $prevSetArray = array();
            $prevSetArray['negative'] = checkKeyOrFalse($eventLogic, 'negative');

            if($foundEvent1Type == 'sField'){
                 $prevSetArray['sample_field'] = checkKeyOrFalse($eventLogic, 'inspect');
                 $prevSetArray['sample_field_equals'] = checkKeyOrFalse($eventLogic, 'inspect_value');
            } 

            if($foundEvent1Type == 'sClient'){
                $prevSetArray['client_name'] = checkKeyOrFalse($eventLogic, 'inspect_value');
            } 

            if($foundEvent1Type == 'mMethod'){
              $prevSetArray['sample_collection'] = checkKeyOrFalse($eventLogic, 'inspect_value');  
            } 

            if($foundEvent1Type == 'mType'){
                 $prevSetArray['sample_type'] = checkKeyOrFalse($eventLogic, 'inspect_value');
            } 

            $sampleFields = upa('sampleFields', 'customFieldsArray', array(), False);

            $aF->addDropdownField('sample_field', '{MESA_LBD_SELSAMPLEFIELD}', 'input-block-level', checkKeyOrBlank($prevSetArray, 'sample_field'), $sampleFields, False);
            $aF->addInputField('sample_field_equals', '{MESA_LBD_SAMPFIELDEQUALS}', 'text', 'input-block-level', checkKeyOrBlank($prevSetArray, 'sample_field_equals'), '{MESA_LBD_SAMPFIELDEQUALS}', False, False);

            $sampleCollection = upa('sampleProcedures', 'getProceduresArr', array(), False);
            $aF->addDropdownField('sample_collection', '{MESA_LBD_SELECTSAMPLETAKE}', 'input-block-level', checkKeyOrBlank($prevSetArray, 'sample_collection'), $sampleCollection, False);

            $sampleTypeArr = array();
            $sampleTypeArr['S'] = 'Normaal';
            $sampleTypeArr['L'] = 'Legionella';
            $sampleTypeArr['R'] = 'RODAC';

            $aF->addDropdownField('sample_type', 'Monster type', 'input-block-level', checkKeyOrBlank($prevSetArray, 'sample_type'), $sampleTypeArr, False);

            $aF->addInputField('client_name', '{MESA_LBD_CLIENTNAME}', 'text', 'select2-input select2-default input-block-level', checkKeyOrBlank($prevSetArray, 'client_name'), False,
                array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351' ) );

            $aF->addDropdownField('negative_filter', '{MESA_LBD_NEGFILTER}', 'input-block-level', checkKeyOrFalse($prevSetArray, 'negative'), $negOp, False);

        }


        elseif($eventGroup == '2'){
            //assay  based logic
            $sLogic['NULL'] = '{MESA_LBD_SELECT}';
            $sLogic['assayIs'] = '{MESA_LSB_ISCERTAINASSAY}';
            $sLogic['assayTypeIs'] = '{MESA_LSB_ISCERTAINASSAYTYPE}';

            $foundEvent1Type = checkKeyOrFalse($eventLogic, 'type');
            $aF->addDropdownField('group2_condition', '{MESA_LBD_CONDITION}', 'input-block-level', $foundEvent1Type, $sLogic, False);

            $prevSetArray = array();
            $prevSetArray['negative'] = checkKeyOrFalse($eventLogic, 'negative');

            if($foundEvent1Type == 'assayTypeIs'){                 
                 $prevSetArray['assay_type'] = checkKeyOrFalse($eventLogic, 'inspect_value');
            } 
            if($foundEvent1Type == 'assayIs'){                 
                 $prevSetArray['assay_name'] = checkKeyOrFalse($eventLogic, 'inspect_value');
            } 

            $aF->addInputField('assay_name', '{MESA_LBD_ASSAYNAME}', 'text', 'select2-input select2-default input-block-level',  checkKeyOrBlank($prevSetArray, 'assay_name'), False,
                array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351' ) );

            $types = upa('assayTypes', 'listAssayTypes', array(True), False);
            $aF->addDropdownField('assay_type', '{MESA_LSB_ASSAYTYPE}', 'input-block-level', checkKeyOrBlank($prevSetArray, 'assay_type'), $types, False);
            $aF->addDropdownField('negative_filter', '{MESA_LBD_NEGFILTER}', 'input-block-level', checkKeyOrFalse($prevSetArray, 'negative'), $negOp, False);
        }


        elseif($eventGroup == '3'){
            //sample based logic
            
            $sLogic['NULL'] = '{MESA_LBD_SELECT}';


        }


        $labelDesigns = upa('labelDesigns', 'listLabelsInArray', array(), False);
        $aF->addDropdownField('printLabel', '{MESA_LBD_LABELTOPRINT}', 'input-block-level', checkKeyOrBlank($eventInfo, 'print_label'), $labelDesigns, False);

        $printers = upa('printers', 'listPrintersInArray', array(), False);
        $aF->addDropdownField('printTo', '{MESA_LBD_LABELPRINTTO}', 'input-block-level', checkKeyOrBlank($eventInfo, 'print_to'), $printers, False);

        $aF->addInputField('copies', '{MESA_UGA_COPYNUMBER}', 'text', 'input-block-level', checkKeyOrBlank($eventInfo, 'copies'), '{MESA_UGA_COPYNUMBER}', False, False);

        //$aF->submitTrough('addEventSubmit');

        $this->_template->set('render', $aF->render() );
    }

    function saveEvent(){

        

        $this->render = False;

        if(isset($_POST['event_id']) && !empty($_POST['event_id'])){
            $this->LabelEvent->id = $_POST['event_id'];
        }

        $this->LabelEvent->event = $_POST['event'];
        $this->LabelEvent->event_group = $_POST['event_group'];

        $this->LabelEvent->print_label = $_POST['printLabel'];
        $this->LabelEvent->print_to = $_POST['printTo'];
        $this->LabelEvent->copies = $_POST['copies'];

        //request with logic, save in data array
        if($_POST['event'] == 'L'){
            $logic = array();
            if($this->LabelEvent->event_group == '1'){

                $logicType = $_POST['group1_condition'];
                $logic['type'] = $logicType;

                if($logicType == 'sField'){
                    $logic['inspect'] = $_POST['sample_field'];
                    $logic['inspect_value'] = $_POST['sample_field_equals'];
                } elseif($logicType == 'sClient'){
                    $logic['inspect_value'] = $_POST['client_name'];
                } elseif($logicType == 'mMethod'){
                    $logic['inspect_value'] = $_POST['sample_collection'];
                }elseif($logicType == 'mType'){
                    $logic['inspect_value'] = $_POST['sample_type'];
                }


            }

            elseif($this->LabelEvent->event_group == '2'){
                $logicType = $_POST['group2_condition'];
                $logic['type'] =  $logicType;

                if($logicType == 'assayIs'){
                    $logic['inspect_value'] = $_POST['assay_name'];
                }

                if($logicType == 'assayTypeIs'){
                    $logic['inspect_value'] = $_POST['assay_type'];
                }
            }

            $logic['negative'] = $_POST['negative_filter'];
            $this->LabelEvent->event_data = json_encode($logic, JSON_FORCE_OBJECT);
        }

        $this->LabelEvent->save();

    }

    function removeEvent($id){
        $this->render = False;
        $this->LabelEvent->id = $id;
        $this->LabelEvent->remove();
    }

}
