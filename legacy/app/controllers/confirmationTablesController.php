<?PHP


class confirmationTablesController extends controller{


  function beforeAction($queryString) {
      $this->_template->set('MESA_LIMS_ACTIVE', '');
      $this->_template->set('MESA_SOCIAL_ACTIVE', '');
      $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
  }


  function listing(){

    $table = new tableFactory();
    $table->setTableId('confTabels');
    $table->loadTemplate('confTableListing');
    $results = $this->ConfirmationTable->search();

    if(empty($results)){
        $results = 'Geen tabellen gevonden';
    }

    $table->loadValues($results);
    $this->_template->set('tables', $table->renderTable());


    $sForm = new formFactory('confirmationTables');

    $sForm->setId('addFieldForm');
    $sForm->addClass('');
    $sForm->action( ALPC_BASEPATH . '/confirmationTables/saveTable');
    $sForm->method('POST');
    $sForm->setTemplate('generic');
    $sForm->addInputField('name', 'Naam', 'text', 'input-block-level', '', 'Naam voor nieuwe bevestiging tabel', False, False);
    $sForm->addValidation('name', 'NO_DUPLICATE');
    $sForm->submitTrough('addTableSubmit');
    $this->_template->set('addTableForm', $sForm->render());
  }

  function edit($id){

    $this->ConfirmationTable->where('id', $id);
    $results = $this->ConfirmationTable->search();

    if(!empty($results)){
        $table = $results[0];


        $sForm = new formFactory('confirmationTables');
        $sForm->setId('editTableForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/confirmationTables/saveTable/' . $id);
        $sForm->method('POST');
        $sForm->setTemplate('generic');
        $sForm->addInputField('name', False, 'text', 'input-block-level', $table['name'], 'Naam', False, 'Naam');
        $sForm->addTextArea('html', 'Opmaak bevestigings tabel', 'text', 'summernote input-block-level ', $table['html'] , '', False);
        $sForm->addTextArea('id', '', 'hidden', 'hidden', $id, '', False);
        $sForm->submitTrough('saveTableButton');
        $this->_template->set('edit_form', $sForm->render());
        $this->_template->set('id', $id);
    }
  }

  function saveTable($id = false){

    $this->ConfirmationTable->postToModel();
    $this->ConfirmationTable->save();

    if($id == False){
      $id = $this->ConfirmationTable->lastInsertId;
    }

    // $revisedId = $this->ConfirmationTable->lastInsertId;
    $this->reRoute('confirmationTables/edit/' . $id, True);
  }


  function remove($id){
    $this->render =False;
    $this->ConfirmationTable->id = $id;
    $this->ConfirmationTable->remove();
  }

  function dropdown(){
    $results = $this->ConfirmationTable->search();
    $dropper = array();
    $dropper['NULL'] = 'Geen';
    foreach($results as $result){
      $dropper[$result['id']] = $result['name'];
    }
    return $dropper;
  }

  function fetchTable($id){
    $this->render = False;
    $this->ConfirmationTable->where('id', $id);
    $result = $this->ConfirmationTable->search();

    if(!empty($result)){
      return $result['0']['html'];
    }else{
      return 'Kon bevestigigns tabel niet vinden';
    }

  }

}
