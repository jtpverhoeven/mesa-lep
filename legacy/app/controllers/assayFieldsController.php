<?PHP

class assayFieldsController extends controller{
        
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');                
    }

    function allFields(){
        $this->render = False; 
        $results = $this->AssayField->search();
        return $results;
    }
    
    function listing(){                
        $table = new tableFactory();   
        $table->setTableId('assayFieldTable');
        $table->loadTemplate('globalAssayFieldListing');
        $results = $this->AssayField->search();

        if(empty($results)){ 
            $results = 'No global assay fields added';
        }
        
        $table->loadValues($results);
        
        $this->_template->set('field_table', $table->renderTable());
        
        
        $sForm = new formFactory('assayFields');
                
        $sForm->setId('addFieldForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/assayFields/saveField');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');
        $sForm->addInputField('name', '{MESA_GAF_FIELDNAME}', 'text', 'input-block-level', '', '{MESA_GAF_FIELDNAMEDESC}', False, False);
        $sForm->addValidation('name', 'NO_DUPLICATE');                               
        $sForm->addInputField('standard_value', '{MESA_GAF_FIELDSTDVAL}', 'text', 'input-block-level', '', '{MESA_GAF_FIELDSTDVALDESC}');               
        $sForm->addInputField('position', '{MESA_GAF_POSITION}', 'text', 'input-block-level', '', '{MESA_GAF_POSITIONDESC}');
        $sForm->addValidation('position', 'ONLY_NUM');
        $sForm->submitTrough('addFieldSubmit');
        $this->_template->set('addFieldForm', $sForm->render());
                
    }
    
    
    function saveField(){
        $this->AssayField->postToModel();
        $this->AssayField->save();
        $this->reRoute('assayFields/listing', True);        
    }

    function saveEdit($id){
        $this->AssayField->id = $id;
        $this->AssayField->postToModel();
        $this->AssayField->save();
        $this->reRoute('assayFields/listing', True);
    }
    
    function removeField(){
        $this->AssayField->id = $_POST['field_id'];
        $this->AssayField->remove();
    }
    
    function getAssayFields(){
        $result = $this->AssayField->search();
        return $result;
    }

    function edit($id){
        $this->AssayField->where('id', $id);
        $results = $this->AssayField->search();

        if(!empty($results)){

            $assay = $results[0];

            $sForm = new formFactory('assayFielsd');
            $sForm->setId('editAssayFieldForm');
            $sForm->addClass('');
            $sForm->action( ALPC_BASEPATH . '/assayFields/saveEdit/' . $id);
            $sForm->method('POST');
            $sForm->setTemplate('generic');

            $sForm->addInputField('name', '{MESA_GAF_FIELDNAME}', 'text', 'input-block-level', $assay['name'], '{MESA_GAF_FIELDNAMEDESC}', False, False);
            $sForm->addInputField('standard_value', '{MESA_GAF_FIELDSTDVAL}', 'text', 'input-block-level', $assay['standard_value'], '{MESA_GAF_FIELDSTDVALDESC}', False, False);
            $sForm->addInputField('position', '{MESA_GAF_POSITION}', 'text', 'input-block-level', $assay['position'], '{MESA_GAF_POSITIONDESC}', False, False);
            //$sForm->addInputField('id', False, 'hidden', 'hidden', $id, False, false, false);
            $sForm->submitTrough('reviseAssayFieldButton');
            $form = $sForm->render();
        }

        else{
            $form = 'Onbekend analyse veld id';
        }

        $this->_template->set('form', $form);

    }



}