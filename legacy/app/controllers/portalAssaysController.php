<?PHP

class portalAssaysController extends controller{

  public function generate_sql_batch()
  {

    $this->render = False; 

    $client = new Client();
    $client->where('active', 1);  
    $clients = $client->search();

    $portalCommons = new PortalAssay();

    $portalCommons->where('active', 1);
    $portalCommons->where('selectable', 1);

    $portalCommons = $portalCommons->search();

    foreach($clients as $client)
    {

      foreach($portalCommons as $common)
      {

        $rawTxtSqlQuery = "INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES (" . $client['id'] . ", " . $common['id'] . "); <br />";

        print $rawTxtSqlQuery;

      }

    }
  }

  public function setAllActiveForClient($clientId = False)
  {

    if(!$clientId)
    {
      dd('no client id set');
      return; 
    }

    $portalCommons = new PortalAssay();
    $portalCommons->where('active', 1);
    $portalCommons->where('selectable', 1);
    $portalCommons = $portalCommons->search();
    
    foreach($portalCommons as $common)
    {

      $sql = "INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES (:client_id, :assay_id)";

      $this->PortalAssay->customSetQuery($sql, array('client_id' => $clientId, 'assay_id' => $common['id']));      

    }

  }

  function beforeAction($queryString) {
      $this->_template->set('MESA_LIMS_ACTIVE', '');
      $this->_template->set('MESA_SOCIAL_ACTIVE', '');
      $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
  }

  public function setClientAvailability()
  {
    $this->render = False; 

    $sql = "SELECT * FROM client_portal_assay WHERE client_id = :client_id AND portal_assay_id = :assay_id";
    
    $result = $this->PortalAssay->customQuery($sql, array('client_id' => $_POST['client'], 'assay_id' => $_POST['assay']));    
    
    if(empty($result) && $_POST['active'] == 1)
    {
      
      $sql = "INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES (:client_id, :assay_id)";
      
      $this->PortalAssay->customSetQuery($sql, array('client_id' => $_POST['client'], 'assay_id' => $_POST['assay']));      

    }

    if(!empty($result) && $_POST['active'] == 0)
    {
      $sql = "DELETE FROM client_portal_assay WHERE client_id = :client_id AND portal_assay_id = :assay_id";
      
      $this->PortalAssay->customSetQuery($sql, array('client_id' => $_POST['client'], 'assay_id' => $_POST['assay']));      
    }

            
  }

  //ugly hack
  public function bulkAddUpa($assay)
  {

    $_POST['assay'] =  $assay;

    $_POST['includeStale'] = 0;

    $this->bulkAdd();

  }

  public function bulkAdd()
  {
  
    $this->render = False;

    $sql = "DELETE FROM client_portal_assay WHERE  portal_assay_id = :assay_id";
      
    $this->PortalAssay->customSetQuery($sql, array( 'assay_id' => $_POST['assay']));      

    //filter_validate boolean $_POST['includeStale]

    $includeStale = filter_var($_POST['includeStale'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);


    $client = new Client();    

    if(!$includeStale)
    {   
      $client->where('active', 1);
    }

    $clients = $client->search();

    $ids = array_column($clients, 'id');  

    
    foreach($ids as $clientId)
    {
      //add all client ids for this assay
      $sql = "INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES (:client_id, :assay_id)";

      $this->PortalAssay->customSetQuery($sql, array('client_id' => $clientId, 'assay_id' => $_POST['assay']));      

    }


  }

  public function bulkRemove()
  {

    $this->render = False; 
  
    $sql = "DELETE FROM client_portal_assay WHERE  portal_assay_id = :assay_id";
      
    $this->PortalAssay->customSetQuery($sql, array( 'assay_id' => $_POST['assay']));      

  }

  public function bulkCopy()
  {
    
    $this->render = False; 

  
    //drop existing
    $sql = "DELETE FROM client_portal_assay WHERE  portal_assay_id = :assay_id";
      
    $this->PortalAssay->customSetQuery($sql, array( 'assay_id' => $_POST['assay']));      

  
    //grab rows for assay we are copying from 

    $sql = 'SELECT * FROM client_portal_assay WHERE portal_assay_id = :assay_id';
    $assayResults = $this->PortalAssay->customQuery($sql, array('assay_id' => $_POST['copy_from']));

    
    //insert under the selected assay 
    foreach($assayResults as $assaySetting)
    {
    
      $sql = "INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES (:client_id, :assay_id)";

      $this->PortalAssay->customSetQuery($sql, array('client_id' => $assaySetting['client_id'], 'assay_id' => $_POST['assay']));      
      
    }
  }

  public function clientAvailability($id)
  {
  
    $this->PortalAssay->where('id', $id);
    $assay = $this->PortalAssay->first();

    if(!$assay){
        $this->reRoute('portalAssays/listing', True);
    }

    $assaysObj = new PortalAssay();
    $assaysObj->where('active', 1);
    $assaysObj->where('selectable', 1);
    $assays = $assaysObj->search();


    $client = new Client();    
    $client->where('active', 1);
    $clients = $client->search();
    
    
    $sql = 'SELECT * FROM client_portal_assay WHERE portal_assay_id = :assay_id';
    $assayResults = $this->PortalAssay->customQuery($sql, array('assay_id' => $id));

    $assayClients = array_column($assayResults, 'client_id');            
    $clientCats = upa('clientCategories', 'all', array(), False);

    //set the id as array key
    $clientCats = array_column($clientCats, NULL, 'id');

    $clientCatPivotSql = 'SELECT * FROM categories_clients';
    $clientCatPivot = $this->PortalAssay->customQuery($clientCatPivotSql, array());

    $categoriesForClients = array();

    foreach($clientCatPivot as $pivot)
    {
      $categoriesForClients[$pivot['client_id']][] = $pivot['clientcategory_id'];
    }
    
    //dd($categoriesForClients);

    foreach($clients as $idx => $client)
    {

      //attach client cateogry names
      $clients[$idx]['categories'] = '';

      if(isset($categoriesForClients[$client['id']]))
      {
        foreach($categoriesForClients[$client['id']] as $catId)
        {          
          $clients[$idx]['categories'] .= $clientCats[$catId]['name'] . ', ';
        }
      }
      
      if(in_array($client['id'], $assayClients))
      {
        $clients[$idx]['assay_active'] = 'Ja';
        $clients[$idx]['assay_ticker'] =  'checked="checked"' ;
        continue;
      }


      $clients[$idx]['assay_active'] = 'Nee';
      $clients[$idx]['assay_ticker'] = '';

    }
    


    $table = new tableFactory();
    $table->setTableId('assayAssociationTable');
    $table->loadTemplate('portalAssayTableClient');
    

    if(empty($clients)){
        $results = 'No clients defined';
    }

    $table->loadValues($clients);


    //more ugly hacks..
    $opts = '';
    foreach($assays as $copyAssay)
    {
    
      //dont include self
      if($copyAssay['id'] == $id)
      {
        continue;
      }

        $opts .= '<option value="' . $copyAssay['id'] . '">' . $copyAssay['common_name'] . '</option>';
    
    }


    $this->_template->set('portal_assay_table', $table->renderTable());

    $this->_template->set('assay_id', $id);

    $this->_template->set('assay_opts', $opts);

    $this->_template->set('template_name', $assay['common_name']);
  
  }

  public function bulkClientCopy(){
  
    
    $client = new Client();    
    $client->where('active', 1);
    $clients = $client->search();

    $clientLinksSql = 'SELECT client_id, COUNT(*) as count FROM client_portal_assay GROUP BY client_id';
    $assayCounts = $this->PortalAssay->customQuery($clientLinksSql, array());
    $assayCounts = array_column($assayCounts, 'count', 'client_id');

    $clientCats = upa('clientCategories', 'all', array(), False);

    //set the id as array key
    $clientCats = array_column($clientCats, NULL, 'id');

    $clientCatPivotSql = 'SELECT * FROM categories_clients';
    $clientCatPivot = $this->PortalAssay->customQuery($clientCatPivotSql, array());

    $categoriesForClients = array();
    $clientOptions = '';

    foreach($clientCatPivot as $pivot)
    {
      $categoriesForClients[$pivot['client_id']][] = $pivot['clientcategory_id'];
    }
      
    foreach($clients as $idx => $client)
    {

      $clientOptions .= '<option value="' . $client['id'] . '">' . $client['name'] . '</option>';

      //attach client cateogry names
      $clients[$idx]['categories'] = '';

      if(isset($categoriesForClients[$client['id']]))
      {
        foreach($categoriesForClients[$client['id']] as $catId)
        {          
          $clients[$idx]['categories'] .= $clientCats[$catId]['name'] . ', ';
        }
      }

      //check if client has access and if so how many
      $clients[$idx]['count'] = (array_key_exists($client['id'], $assayCounts) ) ? $assayCounts[$client['id']] : 0;
                 
    }


    

    $table = new tableFactory();
    $table->setTableId('assayAssociationTable');
    $table->loadTemplate('portalAssayBulkClientTable');
    

    if(empty($clients)){
        $results = 'No clients defined';
    }

    $table->loadValues($clients);

    $this->_template->set('portal_assay_table', $table->renderTable());

    $this->_template->set('client_opts', $clientOptions);


  }

  public function cloneClientAvailability()
  {

    //incoming,  client (= origin) and clients = targets
    //we are going to completly replace the clients assay availability with the origin clients availability

    $this->render = False; 

    //get the clients array from post
    $clients = $_POST['clients'];

    foreach($clients as $targetClient)
    {

      if($targetClient == $_POST['client'])
      {
        //cant delete origin
        continue;
      }

      $sql = "DELETE FROM client_portal_assay WHERE  client_id = :client_id";
      
      $this->PortalAssay->customSetQuery($sql, array( 'client_id' => $targetClient));      

    }

    //grab rows for assay we are copying from
    $sql = 'SELECT * FROM client_portal_assay WHERE client_id = :client_id';
    $assayResults = $this->PortalAssay->customQuery($sql, array('client_id' => $_POST['client']));

    $bulkInsert = array();  

    //create a bulk insert for each of these rows, for each of the clients
    foreach($assayResults as $assaySetting)
    {
    
      foreach($clients as $targetClient)
      {

        if($targetClient == $_POST['client'])
        {
          //no need to update origin
          continue;
        }

        $bulkInsert[] = array('client_id' => $targetClient, 'portal_assay_id' => $assaySetting['portal_assay_id']);
      }
      
    }

    $pdo = $this->PortalAssay->getPdo();  

    
    $stmt = $pdo->prepare("INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES (?, ?)");

    foreach($bulkInsert as $insert) {
        $stmt->execute([$insert['client_id'], $insert['portal_assay_id']]);
    }

    



  }


  public function getClientAvailability()
  {

    $this->render = False;

 
    $sql = "SELECT * FROM client_portal_assay WHERE client_id = :client_id";

    $sql = "SELECT client_portal_assay.*,  portalassays.common_name
        FROM client_portal_assay 
        JOIN portalassays ON client_portal_assay.portal_assay_id = portalassays.id 
        WHERE client_portal_assay.client_id = :client_id";


    
    $results = $this->PortalAssay->customQuery($sql, array('client_id' => $_POST['client']));        

    
    $table = new tableFactory();
    $table->setTableId('assaysTable');
    $table->loadTemplate('bulkCopyCommonListing');
    
    if(empty($results)){
        $results = 'Geen actieve analyses voor deze klant';
    }

    $table->loadValues($results);

    print $table->renderTable();
    
  }

  public function listing(){

    $table = new tableFactory();
    $table->setTableId('assaysTable');
    $table->loadTemplate('portalAssayTable');
    $this->PortalAssay->where('active', 1);
    $results = $this->PortalAssay->search();

    if(empty($results)){
        $results = 'No assays defined';
    }

    $table->loadValues($results);

    $sForm = new formFactory('portalAssays');

    $sForm->setId('addAssayForm');
    $sForm->addClass('');
    $sForm->action( ALPC_BASEPATH . '/portalAssays/saveAssay');
    $sForm->method('POST');
    $sForm->setTemplate('generic');

    $sForm->addInputField('common_name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', '', '{MESA_ASE_ASSAYNAME}', False, False);
    $sForm->addInputField('common_name_en', '{MESA_ASE_ASSAYNAME} engels', 'text', 'input-block-level', $this->PortalAssay->common_name_en, '{MESA_ASE_ASSAYNAME} engels', False, False);
    $sForm->addDropdownField('selectable', False, 'input-block-level', '1', array('1' => 'Ja', '0' => 'Nee'), False, 'Selecteerbaar door klant?');
    $sForm->addDropdownField('alertable', False, 'input-block-level', 1, array('1' => 'Ja', '0' => 'Nee'), False, 'Kan een klant een alarm instellen?');
    $sForm->addDropdownField('border_reaction', False, 'input-block-level', '', array('1' => 'Ja', '0' => 'Nee'), False, 'Is dit een grens reactie?');
    
    $sForm->addDropdownField('add_to_all_clients', 'Nieuwe common gelijk toevoegen aan alle klanten?', 'input-block-level', '1', array('1' => 'Ja', '0' => 'Nee'), False);

    $sForm->submitTrough('addAssaySubmit');

    $this->_template->set('portal_assay_table', $table->renderTable());
    $this->_template->set('addAssayForm', $sForm->render());
  }

  public function saveAssay(){

    $new = True; 

    if(isset($_POST['id'])){
      $new = False; 
      $this->PortalAssay->id = $_POST['id'];
    }       
    
    $this->PortalAssay->common_name = $_POST['common_name'];
    $this->PortalAssay->common_name_en = $_POST['common_name_en'];
    $this->PortalAssay->selectable = $_POST['selectable'];
    $this->PortalAssay->alertable = $_POST['alertable'];
    $this->PortalAssay->border_reaction = $_POST['border_reaction'];
    $this->PortalAssay->active = 1;
    $this->PortalAssay->save();

    if($new && isset($_POST['add_to_all_clients']) && $_POST['add_to_all_clients'] == 1)
    {      
      upa('portalAssays', 'bulkAddUpa', array($this->PortalAssay->lastInsertId), False);
    }



    $this->reRoute('portalAssays/listing', True);
  }

  public function remove($id){
    $this->PortalAssay->id = $id;
    $this->PortalAssay->active = 0;
    $this->PortalAssay->save();
    $this->reRoute('portalAssays/listing');
  }


  public function edit($id, $showHidden = False ){

    $this->PortalAssay->where('id', $id);
    $results = $this->PortalAssay->search();

    if(empty($results)){
        $this->reRoute('portalAssays/listing', True);
    }

    $this->PortalAssay->arrayToModel($results['0']);

    $this->_template->set('template_name', $this->PortalAssay->common_name);
    $this->_template->set('common_id', $id);

    $sForm = new formFactory('assays');
    $sForm->setId('editAssayForm');
    $sForm->addClass('');
    $sForm->action( ALPC_BASEPATH . '/portalAssays/saveAssay/' . $id);
    $sForm->method('POST');
    $sForm->setTemplate('generic');
    $sForm->addInputField('common_name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', $this->PortalAssay->common_name, '{MESA_ASE_ASSAYNAME}', False, False);    
    $sForm->addInputField('id', '', 'hidden', 'hidden', $this->PortalAssay->id, False, False);
    $tickerTable = upa('portalAssayContent', 'renderAssociations', array($this->PortalAssay->id, $showHidden), False    );

    $this->_template->set('ticker_table', $tickerTable);
    $this->_template->set('name_edit_form', $sForm->render());
  }

  public function changeName($id){
    $this->PortalAssay->id = $id;
    $this->PortalAssay->common_name = $_POST['common_name'];
    $this->PortalAssay->common_name_en = $_POST['common_name_en'];
    $this->PortalAssay->selectable = $_POST['selectable'];    
    $this->PortalAssay->alertable =  $_POST['alertable'];    
    $this->PortalAssay->border_reaction = $_POST['border_reaction'];
    $this->PortalAssay->save();
    $this->reRoute('portalAssays/edit/' . $id);

  }

  public function editname($id){
   
    $this->PortalAssay->where('id', $id);
    $results = $this->PortalAssay->search();
    
    if(empty($results)){
        $this->reRoute('portalAssays/listing', True);
    }

    $sForm = new formFactory('portalAssays');

    $sForm->setId('addAssayForm');
    $sForm->addClass('');
    $sForm->action( ALPC_BASEPATH . '/portalAssays/changeName/' . $id);
    $sForm->method('POST'); 
    $sForm->setTemplate('generic');

    $sForm->addInputField('common_name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', $results[0]['common_name'], '{MESA_ASE_ASSAYNAME}', False, False);
    $sForm->addInputField('common_name_en', '{MESA_ASE_ASSAYNAME} engels', 'text', 'input-block-level',  $results[0]['common_name_en'], '{MESA_ASE_ASSAYNAME} engels', False, False);    
    $sForm->addDropdownField('selectable', False, 'input-block-level', $results[0]['selectable'], array('1' => 'Ja', '0' => 'Nee'), False, 'Selecteerbaar door klant?');
    $sForm->addDropdownField('alertable', False, 'input-block-level', $results[0]['alertable'], array('1' => 'Ja', '0' => 'Nee'), False, 'Kan een klant een alarm instellen?');
    $sForm->addDropdownField('border_reaction', False, 'input-block-level', $results[0]['border_reaction'], array('1' => 'Ja', '0' => 'Nee'), False, 'Is dit een grens reactie?');
    $sForm->addButton('editAssaySubmit' , 'icon-save', False, 'btn btn-primary', 'Naam wijzigen', False);
    $sForm->submitTrough('editAssaySubmit');
        
    $this->_template->set('editAssayForm', $sForm->render());

  }


  public function enableAllForAll($password = False)
  {

    $this->render = False;
  
    if($password !== 'deploy')
    {    
      dd('no password set');    
    }

    //get all client ids
    $client = new Client();
    $client->where('active', 1);
    $clients = $client->search();

    $ids = array_column($clients, 'id');


    //assay ids
    $portalCommons = new PortalAssay();
    $portalCommons->where('active', 1);
    $portalCommons->where('selectable', 1);
    $portalCommons = $portalCommons->search();

    $assayIds = array_column($portalCommons, 'id');


    foreach($assayIds  as $assayId)
    {
    
      $sqlForAssay = 'INSERT INTO client_portal_assay (client_id, portal_assay_id) VALUES ';
      $values = '';

      foreach($ids as $clientId)
      {

        $values .= '(' . $clientId . ', ' . $assayId . '),';        
                        
      }

      $values = rtrim($values, ',');

      $this->PortalAssay->customSetQuery($sqlForAssay . $values, array());     
    
    }


    
  
  
  }

}
