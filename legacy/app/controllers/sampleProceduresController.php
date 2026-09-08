<?PHP

class sampleProceduresController extends controller{
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }
    
    function getProceduresArr($limitVar = False, $includeUnknown = True){
        
        $results = $this->SampleProcedure->search();
        $drop = array();

        $limitVarValue = upa('cvars', 'grabCvar', array($limitVar), False);

        $limitArr = json_decode($limitVarValue, JSON_FORCE_OBJECT);
        
        if(empty($results)){

            if($includeUnknown === True){
                $drop['0'] = 'Onbekend';
            }
            
            
            return $drop;
        }
        
        else{
            foreach($results as $procedure){

                if($procedure['active'] == 1 ){

                    if($limitVar == False){
                        $drop[$procedure['id']] = $procedure['name'];
                    }

                    elseif($limitVar != False && in_array($procedure['id'], $limitArr)){
                        $drop[$procedure['id']] = $procedure['name'];
                    }



                }                                
            }            
            
            if($includeUnknown === True){
                $drop['0'] = 'Onbekend';
            }
        }        
        return $drop;        
    }


    function getProcedureDropDown($selected, $limitVar = False, $includeUnknown = True){

        $array = $this->getProceduresArr($limitVar, $includeUnknown);
        $dropper = '';
        foreach($array as $methodId => $methodName){

            if($methodId == $selected){
                $dropper .= '<option value="' .$methodId . '" selected="SELECTED">' . $methodName . '</option>"';
            } else{
                $dropper .= '<option value="' .$methodId . '">' . $methodName . '</option>"';
            }


        }

        return $dropper;
    }
    
    function procedureIdToName($id){
        
        $this->SampleProcedure->where('id', $id);
        $this->SampleProcedure->select(['id', 'name']);
        $this->SampleProcedure->limit(1);
        $result = $this->SampleProcedure->search();
        
        if(empty($result)){
            return 'Unknown';
        } else{
            return $result['0']['name'];
        }
        
    }
    
    function listing(){
        
        $table = new tableFactory();   
        $table->setTableId('procedure_table');
        $table->loadTemplate('procedureTable');
        $results = $this->SampleProcedure->search();

        if(empty($results)){
            $results = '{MESA_SMP_NOPROCEDURES}';
        } else{
            foreach($results as $i => $proc){
                if($proc['active'] == '0'){
                    $results[$i]['icon'] = 'icon-eye-close';
                }else{
                    $results[$i]['icon'] = '';
                }
            }
        }
        
        $table->loadValues($results);        
        $this->_template->set('procedure_table', $table->renderTable());                
    }
    
    
    function edit($id = False){
         $customData = array();
        
        if($id != False){
            $this->SampleProcedure->where('id', $id);
            $results = $this->SampleProcedure->search();
           
            
            if(!empty($results)){
                $this->SampleProcedure->arrayToModel($results['0']);
                $customData = json_decode($this->SampleProcedure->fields, True);                              
            }            
        } 
        
        $sForm = new formFactory('sampleProcedures');
        $customFields = pa('sampleProcedureFields', 'getFields', array());
                
        $sForm->setId('editProcedureForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/sampleProcedures/saveProcedure');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');
        $sForm->submitTrough('saveProc');
        
         if($this->SampleProcedure->id != False){
             $sForm->addInputField('name', '{MESA_SMP_PRONAME}', 'text', 'input-block-level', $this->SampleProcedure->name, '{MESA_SMP_PRONAME}', '', False);
             $sForm->addInputField('id', False, 'hidden', False, $this->SampleProcedure->id, False, False, False);
         } else {
             $sForm->addInputField('name', '{MESA_SMP_PRONAME}', 'text', 'input-block-level', $this->SampleProcedure->name, '{MESA_SMP_PRONAME}', '', False);
             $sForm->addValidation('name', 'NO_DUPLICATE');
         }

         
         $hideOpArr['0'] = '{MESA_SMP_NO}';
         $hideOpArr['1'] = '{MESA_SMP_YES}';         
         $sForm->addDropdownField('hide', '{MESA_SMP_HIDEREPORT}', 'input-block-level', $this->SampleProcedure->hide, $hideOpArr, False, False);
         
         
         if(!empty($customFields)){
            foreach($customFields as $field){
                              
               if(array_key_exists($field['name'], $customData)){
                   $value = $customData[$field['name']];
               } else {
                   $value = False;
               }
                
               $sForm->addInputField($field['name'], $field['alias'], 'text', 'input-block-level', $value,  '', False, False);  
            }
        }
        
        $sForm->addButton('saveProc' , 'icon-save', False, 'btn btn-primary', '{MESA_SMP_SAVE}', False);
        $this->_template->set('procedure_form', $sForm->render());
        
                        
    }
        
    
    function saveProcedure(){
                
        $this->render = 0;
                
        if(isset($_POST['id'])){
            $this->SampleProcedure->id = $_POST['id'];
            $this->SampleProcedure->name = $_POST['name'];
        } else{
            $this->SampleProcedure->name = $_POST['name'];
        }
        
        $this->SampleProcedure->hide = $_POST['hide'];
        
        //get the customFields from post
        $customFields = pa('sampleProcedureFields', 'getFields', array());
        $customVal = array();
        
        if(!empty($customFields)){
            foreach($customFields as $field){                
                $customVal[$field['name']] = $_POST[$field['name']];
            }
        }
        
        
        $this->SampleProcedure->fields = json_encode($customVal, JSON_FORCE_OBJECT);
        $this->SampleProcedure->save();                
        
        $this->reRoute( 'sampleProcedures/listing', True);
        
    }
    
    function removeProcedure(){
        $this->render = False;
        $this->SampleProcedure->id = $_POST['selected_proc'];
        $this->SampleProcedure->remove();
    }
    
    function hideProcedure(){
        $this->render = False;
        $this->SampleProcedure->id = $_POST['selected_proc'];
        
        $result = $this->SampleProcedure->search();
        
        if(!empty($result)){
            if($result['0']['active'] == '1'){
                $this->SampleProcedure->active = 0;
            } else{
                $this->SampleProcedure->active = 1;
            }
            
            $this->SampleProcedure->save();       
        }                      
    }

    function setDefaultProcedure($type = 0){

        $default = MESA_STD_SMPL_METHOD;
        $defaultLeg = MESA_STD_LEGSMPL_METHOD;
        $defaulRodac = MESA_STD_RODACSMPL_METHOD;

        $procedures = $this->getProceduresArr();

        $sForm = new formFactory('sampleProcedures');

        $sForm->setId('setStandardProcedure');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/sampleProcedures/saveDefaultProcedure');
        $sForm->method('POST');
        $sForm->setTemplate('generic');
        $sForm->submitTrough('saveDefaultProc');

        $sForm->addDropdownField('defaultProc', '{MESA_SMP_DEFAULT_INPUT}', 'input-block-level', $default, $procedures, False );
        $sForm->addDropdownField('defaultLegProc', 'Standaard bemonster procedure legionella', 'input-block-level', $defaultLeg, $procedures, False );
        $sForm->addDropdownField('defaultRodacProc', 'Standaard bemonster procedure RODAC', 'input-block-level', $defaulRodac, $procedures, False );

        $sForm->addButton('saveDefaultProc' , 'icon-save', False, 'btn btn-primary', '{MESA_SMP_SAVE}', False);
        $this->_template->set('procedure_form', $sForm->render());
    }

    function saveDefaultProcedure(){
        $this->render = False;
        upa('cvars', 'cvarSaveName', array('MESA_STD_SMPL_METHOD', $_POST['defaultProc']), False);
        upa('cvars', 'cvarSaveName', array('MESA_STD_LEGSMPL_METHOD', $_POST['defaultLegProc']), False);
        upa('cvars', 'cvarSaveName', array('MESA_STD_RODACSMPL_METHOD', $_POST['defaultRodacProc']), False);
        $this->reRoute( 'sampleProcedures/listing', True);
    }

    function getProceduresList(){
        return $this->SampleProcedure->search();
    }
        
}