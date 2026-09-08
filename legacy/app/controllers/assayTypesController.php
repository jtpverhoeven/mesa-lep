<?PHP

class assayTypesController extends Controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function listAnalyticals(){
        return $this->AssayType->listAssayTypes();
    }

    function listAssayTypes($dropFormat = False){

        if($dropFormat == False){
            return $this->AssayType->search();
        } else{
            $results = $this->AssayType->search();
            $dropper = array();
            foreach($results as $dropItem){
                $dropper[$dropItem['id']] = $dropItem['name'];
            }
            return $dropper;
        }
    }

    function listing(){

        $availableTypes = $this->AssayType->listAssayTypes();

        if(empty($availableTypes)){
            $values = 'No assay types defined';
        } else {
            $values = $availableTypes;
        }

        $tF = new tableFactory();
        $tF->setTableId('assayTypesTable');
        $tF->loadTemplate('assaytypeListing');
        $tF->loadValues($values);
        $tF->specifyMod('added_by', 'getUserName', array(ALPC_TF_SELF));
        $tF->specifyMod('added_date', 'date', array('d-m-Y', ALPC_TF_SELF));
        $this->_template->set('available_assay_listing', $tF->renderTable());

    }

    function add(){

        $addForm = new formFactory($this->_controller);

        $addForm->setId('addAssayTypeForm');
        $addForm->action('{LB}/assayTypes/addSubmit/');
        $addForm->method('POST');

        $addForm->addClass('form-horizontal');
        $addForm->setTemplate('generic');

        $addForm->addInputField('name', '{MESA_AST_NAME}', 'text', 'input-block-level', '', '{MESA_AST_NAME}', False);
        $addForm->addValidation('name', 'NO_DUPLICATE');
        $addForm->addInputField('description', '{MESA_AST_DESCRIPTION}', 'text', 'input-block-level', '', '{MESA_AST_DESCRIPTION_TIP}', False);
        $addForm->addValidation('description', 'NOT_EMPTY');

        $addForm->addInputField('added_by', False, 'hidden', 'hidden', getUserId(), '' , False);
        $addForm->addInputField('added_date', False, 'hidden', 'hidden', time(), '' , False);

        $addForm->addButton('submitButton', 'icon-plus-sign', False, 'btn btn-primary btn-small', '{MESA_AST_SAVE}', False);
        $addForm->submitTrough('submitButton');

        $this->_template->set('addAssayTypeForm', $addForm->render());
    }



    function addSubmit(){
        $this->render = 0;
        $this->AssayType->postToModel();
        $this->AssayType->save();
        $this->reRoute('assayTypes/listing', True);
    }


    function edit($analyticalId){

        $this->AssayType->id = $analyticalId;
        $results = $this->AssayType->search();

        //not existant, back to list
        if(empty($results)){
            $this->reRoute('analyticals/listing/');
            return;
        }

        $this->AssayType->arrayToModel($results['0']);
        $this->_template->set('name', $this->AssayType->name);
        $this->_template->set('id', $this->AssayType->id);
        $this->_template->set('notice', '');
        $this->_template->set('analyticalRowListing', performAction('assayTypeFields', 'generateFieldsTable', array(0=> $this->AssayType->id)));

        $addFieldForm = new formFactory('testFields');

        $addFieldForm->setId('addFieldForm');
        $addFieldForm->method('POST');
        $addFieldForm->setTemplate('generic');

        $addFieldForm->addInputField('name', '{MESA_AST_FIELDNAME}', 'text', 'input-block-level', '', '{MESA_AST_FIELDNAME}', False);
        $addFieldForm->addValidation('name', 'ALPHANUM_ONLY_NOT_EMPTY_NODUP', 'test_id', $this->AssayType->id);

        $addFieldForm->addInputField('alias', '{MESA_AST_FIELDALIAS}', 'text', 'input-block-level', '', '{MESA_AST_FIELDALIAS}', False);
        $addFieldForm->addValidation('alias', 'NOT_EMPTY');

        $dOptions = array( 'varchar' => '{MESA_AST_VARCHAR}', 'int'=> '{MESA_AST_INT}', 'float' => '{MESA_AST_FLOAT}');
        $addFieldForm->addDropdownField('type', '{MESA_AST_FIELDTYPE}', 'input-block-level', 'text', $dOptions, False);

        $addFieldForm->addInputField('pos', '{MESA_AST_POSITION}', 'text', 'input-block-level', '', '{MESA_AST_POSITION}', False);
        $addFieldForm->addValidation('pos', 'ONLY_NUM');

        $addFieldForm->submitTrough('submitAddField', "submitField");

        $bindingForm = new formFactory('fieldBindings');
        $bindingForm->setId('addBindingForm');
        $bindingForm->action( ALPC_BASEPATH . '/fieldBindings/saveBinding');
        $bindingForm->method('POST');
        $bindingForm->setTemplate('generic');
        $bindingForm->submitTrough('saveBindingButton');


        $bindingForm->addInputField('analytical', 'analytical', 'hidden', '', $analyticalId, '');

        $trigTypes = array ( 'math' => '{MESA_AST_BINDTYPEMATH}');
        $bindingForm->addDropdownfield('type', '{MESA_AST_BINDINGTYPE}', 'input-block-level', 'text', $trigTypes, False);

        $triggers = array ( 'onChange' => '{MESA_AST_TRIGGERTYPECHANGE}');
        $bindingForm->addDropdownfield('trigger_type', '{MESA_AST_TRIGGER}', 'input-block-level', 'text', $triggers, False);

        $triggerBy = $this->createDropdownFields($analyticalId);
        $bindingForm->addDropdownfield('trigger_by', '{MESA_AST_TRIGGERBY}', 'input-block-level', 'text', $triggerBy, False);

        $bindingForm->addTextArea('instructions', '{MESA_AST_BINDING}', '', 'textarea-block-level', '', '{MESA_AST_BINDINGTIP}', array('rows'=>2));
        $bindingForm->addButton('saveBindingButton', 'icon-save', '', 'btn btn-primary', '{MESA_AST_SAVEBIND}', False);

        $this->_template->set('addBindingForm', $bindingForm->render() );
        $this->_template->set('addFieldForm', $addFieldForm->render() );

        $availableBindings = performAction('fieldBindings', 'listBindings', array(0=>$analyticalId ), 0);
        $this->_template->set('current_bindings', $availableBindings);

    }

    function analyticalListArray(){

        $this->render = 0;
        $results = $this->AssayType->search();

        $returnArray = array();

        foreach($results as $fetchId => $analytical){
            $returnArray[$analytical['id']] = $analytical['name'];
        }

        return $returnArray;

    }

    function fetchOne($analyticalId){
        $this->AssayType->id = $analyticalId;
        $result = $this->AssayType->search();
        return $result;
    }

    function getTypeName($analyticalId){
        $this->AssayType->id = $analyticalId;
        $result = $this->AssayType->search();
        return $result['0']['name'];
    }

    function createDropdownFields($analyticalId){

        $fields = performAction('assayTypeFields', 'fetchFields', array(0=>$analyticalId), 0);
        $dropdown = array();

        foreach($fields as $field){
            $dropdown[$field['name']] = $field['alias'];
        }
        return $dropdown;
    }



    function changeName(){

        if(isset($_POST['id'])){
            $this->AssayType->id = $_POST['id'];
            $this->AssayType->name = $_POST['name'];
            $this->AssayType->save();
        }

    }



    function remove($baseType){

        $this->render = 0;

        // //fetch all assays with this typebase
        // $basedOn = pa('assays', 'fetchByBase', array($baseType));
        //
        // //loop trough
        // if(!empty($basedOn)){
        //     foreach($basedOn as $assay){
        //
        //     //remove assay itself
        //     pa('assays', 'remove', array($assay['id']));
        //
        //  }
        // }
        //
        // //remove associated fields
        // pa('assayTypeFields', 'removeFieldsByParent', array($baseType));

        //remove assay type
        $this->AssayType->id = $baseType;
        $this->AssayType->active = 0;
        $this->AssayType->save();
        //$this->AssayType->remove();
    }

}
