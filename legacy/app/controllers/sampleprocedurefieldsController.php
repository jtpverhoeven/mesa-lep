<?PHP

class sampleProcedureFieldsController extends controller{
    
     
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }
             
    function listing(){
        
        $table = new tableFactory();   
        $table->setTableId('procfield_table');
        $table->loadTemplate('procFieldTable');
        $results = $this->SampleProcedureField->search();

        if(empty($results)){
            $results = '{MESA_SPF_NOFIELDSDEFINED}';
        }
        
        $table->loadValues($results);        
        $this->_template->set('procfield_table', $table->renderTable());                     
    }
    
    function edit($id = False){
               
         if($id != False){
            $this->SampleProcedureField->where('id', $id);
            $results = $this->SampleProcedureField->search();
            
             if(!empty($results)){
                $this->SampleProcedureField->arrayToModel($results['0']);
             }
         }
           
        $sForm =  new formFactory('sampleProcedureFields');
        $sForm->setId('editFieldForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/sampleProcedureFields/saveProcedure');
        $sForm->method('POST');        
        $sForm->setTemplate('generic');
        $sForm->submitTrough('saveProc');
        
        
        $sForm->addInputField('name', '{MESA_SPF_FIELDNAME}', 'text', 'input-block-level', $this->SampleProcedureField->name, '{MESA_SPF_FIELDNAME}',  False, False);
        $sForm->addInputField('alias', '{MESA_SPF_FIELDALIAS}', 'text', 'input-block-level', $this->SampleProcedureField->alias, '{MESA_SPF_FIELDALIAS}', False, False);        
        
         if($id != False){
             $sForm->addInputField('id', False, 'hidden', 'hide', $this->SampleProcedureField->id, False, False, False);
         }
               
        $sForm->addButton('saveProc' , 'icon-save', False, 'btn btn-primary', '{MESA_SPF_SAVEFIELD}', False);
        $this->_template->set('procedure_form', $sForm->render());
        
    }
    
    function removeProcedureField($id){
        $this->SampleProcedureField->id = $id;
        $this->SampleProcedureField->delete();
    }
    
    function saveProcedure(){        
        $this->render = 0;          
        $this->SampleProcedureField->postToModel();
        $this->SampleProcedureField->save();        
        $this->reRoute( 'sampleProcedureFields/listing', True);
    }
    
    function getFields(){        
        $result = $this->SampleProcedureField->search();
        return $result;                        
    }
    
}