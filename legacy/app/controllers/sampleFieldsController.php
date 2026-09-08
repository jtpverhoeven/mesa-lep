<?PHP

class sampleFieldsController extends controller{
        
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');                
    }
    
    function listing(){
        
        $table = new tableFactory();   
        $table->setTableId('sampleFieldTable');
        $table->loadTemplate('sampleFieldsListing');
        $results = $this->SampleField->search();

        if(empty($results)){
            $results = '{MESA_SMF_NOFIELDDEFINED}';
        }
        
        $table->loadValues($results);
        
        $this->_template->set('field_table', $table->renderTable());
                
        $sForm = new formFactory('sampleFields');
                
        $sForm->setId('addFieldForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/sampleFields/saveField');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');
               
        $sForm->addInputField('name', '{MESA_SMF_FIELDNAME}', 'text', 'input-block-level', '', '{MESA_SMF_FIELDNAMEDESC}', False, False);
        $sForm->addValidation('name', 'NO_DUPLICATE');        
        $sForm->addInputField('alias', '{MESA_SMF_FIELDALIAS}', 'text', 'input-block-level', '', '{MESA_SMF_FIELDALIASDESC}');
        $sForm->addValidation('alias', 'NOT_EMPTY');                       
        $sForm->addDropdownField('type', '{MESA_SMF_FIELDTYPE}','input-block-level', '', array('text'=>'{MESA_SMF_FIELTEXT}', 'date'=>'{MESA_SMF_FIELDATE}', 'textarea' => '{MESA_SMF_FIELDTEXTAREA}'), False);        
        $sForm->addInputField('std_value', '{MESA_SMF_FIELDSTDVALUE}', 'text', 'input-block-level', '', '{MESA_SMF_FIELDSTDVALUEDESC}');             
        $sForm->addInputField('position', '{MESA_SMF_FIELDPOSITION}', 'text', 'input-block-level', '', '{MESA_SMF_FIELDPOSITION}');
        $sForm->addValidation('position', 'ONLY_NUM');                
        $sForm->submitTrough('addFieldSubmit');
        
        $this->_template->set('addFieldForm', $sForm->render());
    }
    
    function edit($id){
        
        $this->SampleField->where('id', $id);
        $results = $this->SampleField->search();
        
        if(empty($results)){
            return;
        }
        
        $this->SampleField->arrayToModel($results[0]);
        
        $sForm = new formFactory('sampleFields');
                
        $sForm->setId('editFieldForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/sampleFields/saveField');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');
        
        //$sForm->addInputField('fieldName', '$label', $fieldType, $classes, $value, $placeHolder, $tags, $prepend);        
        $sForm->addInputField('name', '{MESA_SMF_FIELDNAME}', 'text', 'input-block-level', $this->SampleField->name, '{MESA_SMF_FIELDNAMEDESC}', 'disabled', False);        
        $sForm->addInputField('alias', '{MESA_SMF_FIELDALIAS}', 'text', 'input-block-level',  $this->SampleField->alias, '{MESA_SMF_FIELDALIASDESC}');
        $sForm->addValidation('alias', 'NOT_EMPTY');                       
        $sForm->addDropdownField('type', '{MESA_SMF_FIELDTYPE}','input-block-level', $this->SampleField->type, array('text'=>'{MESA_SMF_FIELTEXT}', 'date'=>'{MESA_SMF_FIELDATE}', 'textarea' => '{MESA_SMF_FIELDTEXTAREA}'), False);        
        $sForm->addInputField('std_value', '{MESA_SMF_FIELDSTDVALUE}', 'text', 'input-block-level', $this->SampleField->std_value, '{MESA_SMF_FIELDSTDVALUEDESC}');               
        $sForm->addInputField('position', '{MESA_SMF_FIELDPOSITION}', 'text', 'input-block-level', $this->SampleField->position, '{MESA_SMF_FIELDPOSITION}');
        $sForm->addValidation('position', 'ONLY_NUM');       
        $sForm->addInputField('id', '', 'hidden', 'hide', $this->SampleField->id, NULL);                
        $sForm->submitTrough('editFieldSubmit');        
        $this->_template->set('editFieldForm', $sForm->render());        
    }
        
    function saveField(){        
        $this->render = 0;
        $this->SampleField->postToModel();
        $this->SampleField->save();        
        $this->reRoute( 'sampleFields/listing', True);
    }
            
    function removeField(){
        $this->render = 0;
        $this->SampleField->id = $_POST['field_id'];
        $this->SampleField->remove();
    }
    
    function fetchCustomFields(){        
        $this->render = 0;
        $sampleFields = $this->SampleField->search();
        return $sampleFields;
    }
    
    function customFieldsArrayByName(){        
        $this->render = 0;
        $sampleFields = $this->SampleField->search();
              
        $retArr = array();
        foreach($sampleFields as $field){
            $retArr[$field['name']] = $field['type'];
        }
        
        return $retArr;
    }
    
    function renderCustomFields($data = False, $disabled = False){
        
        $this->doNotRenderHeader = 1;
        $sampleFields = $this->SampleField->search();
        $renderedHtml = '';
                
        
        $fieldData = array();
     
        if(!empty($data) && $data !== False && $data != NULL && $data !== 'null'){
            $fieldData = json_decode($data, True);            
        }
        
        if($disabled == True){
            $disabled = 'disabled';
        } else{
            $disabled = '';
        }
                    
        foreach($sampleFields as $field){  
            
            $field['disabled'] = $disabled;            
            if(key_exists($field['name'], $fieldData)){
                $field['std_value'] = $fieldData[$field['name']];                
            }                
            
            if($field['type'] == 'text'){                                       
                $renderedHtml .= generateHTML('customFields/customFieldText', $field);
            }                        
            if($field['type'] == 'date'){                            
                $renderedHtml .= generateHTML('customFields/customFieldDatepick', $field);
            }                        
            if($field['type'] == 'textarea'){
                 $renderedHtml .= generateHTML('customFields/customFieldTextArea', $field);
            }
        }
        
        return $renderedHtml;                
    }
           
    function renderCustomFieldsLookup(){
        
        $sampleFields = $this->SampleField->search();
        $renderedHtml = '';
        
        foreach($sampleFields as $field){                        
                $renderedHtml .= generateHTML('sampleInfoStandard', $field);                                    
        }
        return $renderedHtml;
        
    }
    
    function renderSampleFieldsHTML($sampleInfo){        
        $this->render = 0;                        
    }
    
    function dateDropDownMenu($array = False){
        $this->render = 0;
        $this->SampleField->where('type', 'date');
        $fields = $this->SampleField->search();        
        $dd = '';
        $da = array();
        
        foreach($fields as $field){
            $dd .= '<option value="' . $field['name'] . '">' . $field['alias'] . '</option>';
            $da[$field['name']] = $field['alias'];
        }
        
        if($array == False){
            print $dd;
        } else{
            return $da;
        }                
    }
    
    function customFieldsArray(){
        
        $this->render = False;
        $result = $this->SampleField->search();
        
        if(!empty($result)){           
           $retArr = array();
           foreach($result as $label){
               $retArr[$label['name']] = $label['alias'];
           }
           return $retArr;
       } else{
           return array('NULL' => 'No sample fields');
       }
        
    }
    
}