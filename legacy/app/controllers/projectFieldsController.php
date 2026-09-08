<?PHP

class projectFieldsController extends Controller{
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');                
    }


    
    function renderProjectFields($edit= False, $preloader = False){
        
        global $lang;
        $this->doNotRenderHeader = 1;
        $this->ProjectField->order('position', 'asc');
        $sampleFields = $this->ProjectField->search();

        $renderedHtml = '';
        
//        if($edit != False){
//            $field['name'] = 'project_name';
//            $field['alias'] = $lang['MESA_PLU_PROJECTNAME'];
//            $field['value'] = '';
//            $field['std_value'] = '';
//            $renderedHtml .= generateHTML('customProjectFields/customFieldText', $field);
//        }
        
        foreach($sampleFields as $field){     
            
            if($preloader == True){
                $field['input_class'] = 'project_preload_field';
                $field['name_prefix'] = 'preload_';
                $field['hider'] = NULL;
            } else{
                $field['input_class'] = 'project_input_field';
                $field['name_prefix'] = 'pj_';
                $field['hider'] = NULL;
            }

            if($edit != False){
                $field['hider'] = 'hidden';
            }
            
            if($field['type'] == 'text'){               
                $renderedHtml .= generateHTML('customProjectFields/customFieldText', $field);
            }                        
            if($field['type'] == 'date'){
                $renderedHtml .= generateHTML('customProjectFields/customFieldDatepick', $field);
            }                        
        }

        if($preloader == False)
        {
            $field['input_class'] = 'project_input_field';
            $field['name_prefix'] = 'pj_';
            $field['hider'] = 'hidden';
            $field['name'] = 'project_name';
            $field['alias'] = 'Project naam / Klant ref.';
            $field['std_value'] = '';

            $renderedHtml .= generateHTML('customProjectFields/customFieldText', $field);
            
        }

        
                
        
        return $renderedHtml;        
    }
    
    function dateDropDownMenu($array = False){
        $this->render = 0;
        $this->ProjectField->where('type', 'date');
        $fields = $this->ProjectField->search();        
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
    
    function fetchProjectFields(){        
        $this->render = 0;
        $this->ProjectField->order('position', 'asc');
        $results = $this->ProjectField->search();
        return $results;        
    }
    
    function listing(){
        
        $table = new tableFactory();   
        $table->setTableId('projectFieldTable');
        $table->loadTemplate('projectFieldListing');
        $results = $this->ProjectField->search();

        if(empty($results)){
            $results = 'No custom project fields added';
        }
        
        $table->loadValues($results);
        
        $this->_template->set('field_table', $table->renderTable());
        
        
        $sForm = new formFactory('projectFields');
                
        $sForm->setId('addFieldForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/projectFields/saveField');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');                
        $sForm->addInputField('name', '{MESA_PFD_FIELDNAME}', 'text', 'input-block-level', '', '{MESA_PFD_FIELDNAMEDESC}', False, False);
        $sForm->addValidation('name', 'NO_DUPLICATE');        
        $sForm->addInputField('alias', '{MESA_PFD_FIELDALIAS}', 'text', 'input-block-level', '', '{MESA_PFD_FIELDALIASDESC}');
        $sForm->addValidation('alias', 'NOT_EMPTY');                       
        $sForm->addDropdownField('type', '{MESA_PFD_FIELDTYPE}','input-block-level', '', array('text'=>'{MESA_PFD_TEXTFIELD}', 'date'=>'{MESA_PFD_DATEPICKER}'), False);        
        $sForm->addInputField('std_value', '{MESA_PFD_STDVALUE}', 'text', 'input-block-level', '', '{MESA_PFD_STDVALUEDESC}');              
        $sForm->addInputField('position', '{MESA_PFD_POSITION}', 'text', 'input-block-level', '', '{MESA_PFD_POSITION}');
        $sForm->addValidation('position', 'ONLY_NUM');                
        $sForm->submitTrough('addFieldSubmit');
        
        $this->_template->set('addFieldForm', $sForm->render());
    }
    
    
     function edit($id){
        
        $this->ProjectField->where('id', $id);
        $results = $this->ProjectField->search();
        
        if(empty($results)){
            return;
        }
        
        $this->ProjectField->arrayToModel($results[0]);
        
        $sForm = new formFactory('projectFields');
                
        $sForm->setId('editFieldForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/projectFields/saveField');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');
                
        $sForm->addInputField('name', '{MESA_PFD_FIELDNAME}', 'text', 'input-block-level', $this->ProjectField->name, '{MESA_PFD_FIELDNAMEDESC}', 'disabled', False);        
        $sForm->addInputField('alias', '{MESA_PFD_FIELDALIAS}', 'text', 'input-block-level',  $this->ProjectField->alias, '{MESA_PFD_FIELDALIAS}');
        $sForm->addValidation('alias', 'NOT_EMPTY');                       
        $sForm->addDropdownField('type', '{MESA_PFD_FIELDTYPE}','input-block-level', $this->ProjectField->type, array('text'=>'{MESA_PFD_TEXTFIELD}', 'date'=>'{MESA_PFD_DATEPICKER}'), False);       
        $sForm->addInputField('std_value', '{MESA_PFD_STDVALUE}', 'text', 'input-block-level', $this->ProjectField->std_value, '{MESA_PFD_STDVALUEDESC}');               
        $sForm->addInputField('position', '{MESA_PFD_POSITION}', 'text', 'input-block-level', $this->ProjectField->position, '{MESA_PFD_POSITION}');
        $sForm->addValidation('position', 'ONLY_NUM');        
        $sForm->addInputField('id', '', 'hidden', 'hide', $this->ProjectField->id, NULL);                
        $sForm->submitTrough('editFieldSubmit');        
        $this->_template->set('editFieldForm', $sForm->render());
        
    }
    
    
      function saveField(){        
        $this->render = 0;
        $this->ProjectField->postToModel();
        $this->ProjectField->save();        
        $this->reRoute( 'projectFields/listing', True);
    }
    
    function removeField(){
        $this->render = 0;
        $this->ProjectField->id = $_POST['field_id'];
        $this->ProjectField->remove();
    }
    
    function fieldType($fieldName){
        $this->ProjectField->where('name', $fieldName); 
        $result = $this->ProjectField->search();
        if(!empty($result)){
            return $result[0]['type'];
        }
    }

    function fetchDateProjectFields(){
        $this->ProjectField->where('type', 'date');
        $results = $this->ProjectField->search();
        return $results;
    }
    
}