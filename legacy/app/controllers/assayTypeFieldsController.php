<?PHP

class assayTypeFieldsController extends Controller {

    function fetchFields($id) {

        $this->AssayTypeField->where('test_id', $id);
        $results = $this->AssayTypeField->search();
        return $results;
    }

    function fetchFieldsOptions($id){

        $this->render = 0;

        $fieldTable = new tableFactory();
        $fieldTable->setTableId('analyticalRowListing');
        $fieldTable->loadTemplate('analyticalRowListing');

        $results = $this->fetchFields($id);
        $optionList  = '';

        if (empty($results)) {
            $optionList['NULL'] = 'No columns in this analytical';
        } else {
            foreach($results as $column){
                $optionList[$column['name']] =  $column['alias'] . ' (' . $column['name'] . ')';
            }
        }
        return $optionList;
    }

    function generateFieldsTable($id) {

        $this->render = 0;

        $fieldTable = new tableFactory();
        $fieldTable->setTableId('analyticalRowListing');
        $fieldTable->loadTemplate('analyticalRowListing');

        $results = $this->fetchFields($id);

        if (empty($results)) {
            $tableValues = '{MESA_AST_NOFIELDS}';
        } else {
            $tableValues = $results;
        }

        $fieldTable->loadValues($tableValues);

        return $fieldTable->renderTable();
    }

    function printFieldsTable($id){

        $table =  $this->generateFieldsTable($id);
        $this->doNotRenderHeader = 1;
        $this->render = 1;
        $this->_template->set('table', $table);
    }

    function addField($id) {
        $this->render = 0;
        $this->AssayTypeField->postToModel();
        $this->AssayTypeField->test_id = $id;
        $this->AssayTypeField->save();
    }

    function removeField($id){
        $this->render = 0;
        $this->AssayTypeField->id = $id;
        $this->AssayTypeField->delete();
    }

    function removeFieldsByParent($test_id){
        $this->AssayTypeField->where('test_id', $test_id);
        $results = $this->AssayTypeField->search();

        if(!empty($results)){
            foreach($results as $field){
                $this->AssayTypeField->id = $field['id'];
                $this->AssayTypeField->remove();
            }
        }
    }

    function edit($id = False){

        $this->doNotRenderHeader = 1;
        $this->AssayTypeField->where('id', $id);
        $results = $this->AssayTypeField->search();

        if(!empty($results)){

            $editFieldForm = new formFactory('testFields');

            $editFieldForm->setId('editFieldForm');
            $editFieldForm->method('POST');
            $editFieldForm->setTemplate('generic');

            $editFieldForm->addInputField('alias', '{MESA_AST_FIELDALIAS}', 'text', 'input-block-level', $results[0]['alias'], '{MESA_AST_FIELDNAME}', False);
            $editFieldForm->addInputField('pos', '{MESA_AST_POSITION}', 'text', 'input-block-level', $results[0]['pos'], '{MESA_AST_POSITION}', False);

            $checkboxClass = '' ;
            if($results[0]['endresults_driver'] == 1){
              $checkboxClass = 'checked="checked"' ;
            }

            $dropOptions = array();

            $dropOptions[0] = 'Geen filter';
            $dropOptions[1] = 'Enkel [0-9] invoer toegestaan';
            $dropOptions[2] = 'Enkel [A-Z] en [0-9] toegestaan ';
            $dropOptions[3] = 'Enkel [A-Z] toegetaan';
            $dropOptions[4] = 'Enkel [+] of [-] toegestaan';

            $editFieldForm->addDropdownField('filter', 'Invoer filter', 'input-block-level', $results[0]['filter'], $dropOptions, False);

            $editFieldForm->addInputField('endresults_driver', 'Deel van eindresultaat?', 'checkbox', 'input-block-level', '1', '{MESA_AST_FIELDNAME}', $checkboxClass);
            $editFieldForm->submitTrough('submitEditField', "saveFieldEdit");
            $this->_template->set('addFieldForm', $editFieldForm->render() );
        }
    }

    function save($id){

      $this->render = False;
      $this->AssayTypeField->id = $id;
      $this->AssayTypeField->alias = $_POST['alias'];
      $this->AssayTypeField->pos = $_POST['pos'];
      $this->AssayTypeField->filter = $_POST['filter'];


      if(!isset($_POST['endresults_driver'])){
        $this->AssayTypeField->endresults_driver = 0;
      } else{
        $this->AssayTypeField->endresults_driver = 1;
      }

      $this->AssayTypeField->save();


    }

}
