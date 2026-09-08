<?PHP

class clientsController extends Controller{


  function importSave($data){
    $this->render = False;
    $this->Client->name = $data[1];
    $this->Client->title = $data[2];
    $this->Client->fname = $data[3];
    $this->Client->mname = $data[4];
    $this->Client->lname = $data[5];
    $this->Client->street_name = $data[6];
    $this->Client->street_number = $data[7];
    $this->Client->postal_code = $data[8];
    $this->Client->place = $data[9];
    $this->Client->country = $data[10];
    $this->Client->telephone = $data[11];
    $this->Client->cellphone = $data[12];
    $this->Client->email = $data[13];
    $this->Client->notes = $data[14];
    $this->Client->save();
  }


    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }
    
    function checkForWishesAndFiles($id){

        $this->render = False;
        $this->Client->where('id', $id);
        $result = $this->Client->search();

        $trip = False;

        if(!empty($result)){
            $notes = $result[0]['notes'];
            //$files = json_decode($result[0]['attachment'], JSON_FORCE_OBJECT);
            $files = json_decode($result[0]['attachment'], JSON_FORCE_OBJECT);
                
            if(!is_array($files)){
                $files = array();
            }

            if(!empty($notes)){
                $trip = True;
            }

            if(count($files) > 0){
                $trip = True;
            }
        }

        if($trip == True ){
            print json_encode(array('wishes' => True));
        } else{
            print json_encode(array('wishes' => False));
        }
    }

    function renderWishesAndFilesDialog($id){

        $this->render = false;
        $this->Client->id = $id;
        $result = $this->Client->search();

        if(!empty($result)){
            $wishes = $result[0]['notes'];
            $currentFiles = $this->fetchFiles($id, True);
            $files = '';

            if(is_array($currentFiles)){
                foreach($currentFiles as $fid => $fileAttributes){
                    $href = ALPC_BASEPATH . '/clients/serveFile/' . $id . '/' . $fid;
                    $files .= generateHTML('clients/fileLine', array('href'=>$href, 'file_description' => $fileAttributes['file_description']));
                }
            }
            print json_encode(array('wishes' => $wishes, 'files' => $files));
        }

    }

    function fetchWishes($id){

        $this->render = false;
        $this->Client->where('id', $id);
        $result = $this->Client->search();

        $notes = False;
        if(!empty($result)){
            $notes = $result[0]['notes'];
        }

        print json_encode(array('notes' => $notes));
    }

    function saveWishes($id){
        $this->render = false;
        $this->Client->id = $id;
        $this->Client->notes = $_POST['notes'];
        $this->Client->save();
    }


    function renderFileList($id){

        $this->render = false;
        $currentFiles = $this->fetchFiles($id, True);
        $html = '';

        if(is_array($currentFiles)){
            foreach($currentFiles as $fid => $fileAttributes){
                $href = ALPC_BASEPATH . '/clients/serveFile/' . $id . '/' . $fid;
                //$html = '<i class="icon icon-paper-clip"></i> <a href="' . $href . '" target="_blank">' . $fileAttributes['file_description'] . '</a><br />';
                $html .= generateHTML('clients/fileLine', array('href'=>$href, 'file_description' => $fileAttributes['file_description']));
            }
        }


        print $html;
    }

    function renderEditFileList($id){

        $this->render = false;
        $currentFiles = $this->fetchFiles($id, True);
        $html = '';

        if(is_array($currentFiles)){
            foreach($currentFiles as $fid => $fileAttributes){
                $href = ALPC_BASEPATH . '/clients/serveFile/' . $id . '/' . $fid;
                $delHref = ALPC_BASEPATH . '/clients/removeFile/' . $id . '/' . $fid;
                //$html = '<i class="icon icon-paper-clip"></i> <a href="' . $href . '" target="_blank">' . $fileAttributes['file_description'] . '</a><br />';
                $html .= generateHTML('clients/fileEditLine', array('href'=>$href, 'del_href'=> $delHref, 'file_description' => $fileAttributes['file_description']));
            }
        }

        print $html;
    }

    function removeFile($id, $fileId){
        $this->render = False;
        $currentFiles = $this->fetchFiles($id, True);
        $delFileInfo = $currentFiles[$fileId];
        unset($currentFiles[$fileId]);
        mesaUnlink($delFileInfo['file']);
        $this->Client->id = $id;
        $this->Client->attachment = json_encode($currentFiles, JSON_FORCE_OBJECT);
        $this->Client->save();
        $this->reRoute('clients/show/' . $id , True);
    }

    function serveFile($id, $fileId){
        $this->render = false;
        $this->Client->where('id', $id);
        $result = $this->Client->search();
        if(!empty($result)){
            $files = json_decode($result[0]['attachment'], JSON_FORCE_OBJECT);
            $type = mime_content_type($files[$fileId]['file']);
            header('Content-Type: ' . $type);
            header('Content-Length: ' . filesize($files[$fileId]['file']));
            readfile($files[$fileId]['file']);
        }
    }

    function fetchFiles($id, $arr = false){

        $this->Client->where('id', $id);
        $result = $this->Client->search();

        if(!empty($result)){

            if($arr == True){
                return json_decode($result[0]['attachment'], JSON_FORCE_OBJECT);
            } else{
                $this->render = False;
                print json_encode($result[0]['attachment'], JSON_FORCE_OBJECT);
            }

        } else{
            if($arr == True){
                return array();
            } else{
                $this->render = False;
                print json_encode(array(),JSON_FORCE_OBJECT);
            }
        }
    }

    function saveFile($id){

        $this->render = False;
        $curFiles = $this->fetchFiles($id, True);

        if($curFiles == NULL){
            $curFiles = array();
        }


        $fileName = $_FILES['clientFile']['name'];
        $newFileName = md5($id + time() + $fileName);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileDir = ROOT . DS . 'app' . DS . 'private' .DS . 'clientFiles' . DS . $newFileName . '.' . $ext;

        if (move_uploaded_file($_FILES['clientFile']['tmp_name'], $fileDir)){
            $newFile = array('file_description' => $fileName, 'file' => $fileDir);
            array_push($curFiles, $newFile);
        }

        $this->Client->id = $id;
        $this->Client->attachment = json_encode($curFiles, JSON_FORCE_OBJECT);
        $this->Client->save();
        $this->reRoute('clients/show/' . $id , True);

    }

    function predictNoSub(){
        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;
        $q = $_POST['term'];

        $this->Client->like('name', $q );
        $this->Client->where('active', '1');
        $results = $this->Client->search();

        $clients = array();
        $iClient = 0;

        if(!empty($results)){
            foreach($results as $thisClient){
                $clients[$iClient]['id'] = $thisClient['id'];
                $clients[$iClient]['text'] = $thisClient['name'];
                $iClient++;
            }
        }

        $ret['more'] = false;
        $ret['results'] = $clients;
        print json_encode($ret);
    }

    function predict(){


        return $this->predictNoSub();


        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;
        return;
        $q = $_POST['term'];

        $clients = array();
        $iClient = 0;


        $this->Client->like('name', $q );
        $results = $this->Client->search();

        if(!empty($results)){
            foreach($results as $thisClient){



                $clients[$iClient]['text'] = $thisClient['name'];
                $subClientRecords = upa('subClients', 'fetchSubClients', array($thisClient['id']), False);
                $subclients = array();

                if(!empty($subClientRecords)){


                    $iSubclient = 0;

                    foreach($subClientRecords as $thisSubClient){
                        $subclients[$iSubclient]['id'] = $thisClient['id'] . '||' . $thisSubClient['id'];
                        $subclients[$iSubclient]['text'] = $thisClient['name'] . ' - ' .$thisSubClient['loc_name'];
                        $iSubclient++;
                    }
                }

                $clients[$iClient]['children'] = $subclients;
                $iClient++;
           }
        }

        $ret = array();
        $ret['more'] = false;
        $ret['results'] =  $clients;
        print json_encode($ret);


    }

   function predictInit($id, $sub=False){

        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;
        $found = array();
        $this->Client->where('id', $id);
        $result = $this->Client->search();

        if(!empty($result)){
            $found['id'] = $result[0]['id'];
            $found['text'] =  $result[0]['name'];            
        }

        $ret = array();
        $ret = $found;
        echo json_encode($ret);
   }

    function listing($selected = 'A'){

       if($selected == '0-9'){
            $this->Client->where('active', '1');
            $this->Client->whereRegExp('name', '^[0-9]');
       } elseif($selected == 'SYM'){
            $this->Client->where('active', '1');
            $this->Client->whereRegExp('name', '^[^0-9A-Za-z]');
       } elseif($selected =='DELETED'){
            $this->Client->where('active', '0');
       }else {
            $this->Client->where('active', '1');
            $this->Client->likeManual('name', $selected . '%');
       }

       $results = $this->Client->search();

       if(empty($results)){
           $results = 'No Clients found.';
       }

       $tF = new tableFactory();
       $tF->loadTemplate('clientsListing');
       $tF->loadValues($results);

       //set char
       $this->_template->set('char_selected', $selected);
       $this->_template->set('client_table_paginated', $tF->renderTable());
    }

    function dashboard(){
    }

    function search(){
    }

    function reactivate($clientId){
      $this->Client->id = $clientId;
      $this->Client->active = 1;
      $this->Client->save();
    }

    function remove($clientId){

        $this->render = 0;

        //remove all subclients
        //pa('subClients', 'removeAllSubclients', array($clientId));

        //remove projects
        //pa('projects', 'removeProjectsForClient', array($clientId));

        //remove profiles
        //pa('researchProfiles', 'removeByClient', array($clientId));

        //deactivate self
        $this->Client->id = $clientId;
        $this->Client->active = 0;
        $this->Client->save();

    }

    function searchResult(){

        $this->doNotRenderHeader = True;
        $searchTerm = $_POST['searchTerm'];
        $searchScope = $_POST['searchScope'];

//        $this->Client->like('adres', $searchTerm);

        //all, name, place
        if($searchScope == 'place'){
            $this->Client->like('adres', $searchTerm);
            //$this->Client->where('active', '1');
            $this->Client->insertOR();
            $this->Client->like('place', $searchTerm);
            //$this->Client->where('active', '1');
            $this->Client->insertOR();
            $this->Client->like('country', $searchTerm);
            //$this->Client->where('active', '1');
        }

        elseif($searchScope == 'name'){
             $this->Client->like('name', $searchTerm);
             //$this->Client->where('active', '1');
        }

        else{
            $this->Client->likeAny($searchTerm);
        }

        $results = $this->Client->search();


        $tF = new tableFactory();
        $tF->setTableId('resultForClients');
        $tF->loadTemplate('clientSearchResult');

        if(empty($results)){
            $results = 'No Results found for query';
        } else{
            foreach($results as $idx => $result){
                
                if($result['active'] == '1' )
                {
                    $results[$idx]['style'] = '';
                    $results[$idx]['nonactive'] = '';                    
                } 
                
                else
                {                    
                    $results[$idx]['style'] = 'style="background: #FF91A4; "';
                    $results[$idx]['nonactive'] = 'Niet actief';    
                }
            }
        }

        $tF->loadValues($results);

        $this->_template->set('results_number', $this->Client->lastQueryCount);
        $this->_template->set('results', $tF->renderTable());

    }

    function show($clientId){

        $this->Client->where('id', $clientId);
        $results = $this->Client->search();


        if(empty($results)){
            $this->reRoute('clients/listing', True);
        }

        $clientCats = $this->clientCategories($clientId);
        $clientCatNames = implode(',', array_column($clientCats, 'name'));

        $this->Client->arrayToModel($results[0]);
        $this->_template->setByArray($results[0]);


        if($this->Client->active == 0){
            $this->_template->set('activate_hider', '');
            $this->_template->set('delete_hider', 'hidden');
        }

        if($this->Client->active == 1){
            $this->_template->set('activate_hider', 'hidden');
            $this->_template->set('delete_hider', '');
        }
        
        $this->_template->set('no_samples', pa('samples', 'findNumberOfSaplesPerClient', array($clientId)));
        $this->_template->set('no_projects', pa('projects', 'findNumberOfProjectsPerClient', array($clientId)));
        $this->_template->set('perc_closed', pa('projects', 'findPercentageOfProjectsClosed', array($clientId)));
        $this->_template->set('reports', upa('exports', 'projectReportOverviewFull', array($clientId), False));        
        $this->_template->set('trip_red', ($this->Client->trip_red == '1') ? 'Ja' : 'Nee' );
        $this->_template->set('id', $clientId);
        $this->_template->set('categories', $clientCatNames);
    }


    function create()
    {
        $this->_template->set('action', '{MESA_CLI_CLIENTACTIONADD}');

        $cF = new formFactory('clients');
        $cF->setId('addAnalysisForm');
        $cF->action('{LB}/clients/saveNewClient');
        $cF->method('POST');
        $cF->addClass('form-horizontal');
        $cF->setTemplate('generic');
        $cF->returnAsFieldArray();

        $cF->addInputField('cname', '{MESA_CLI_EDITCLIENTNAAM}', 'text', 'input-block-level', $this->Client->name, '{MESA_CLI_EDITCLIENTNAAMPLACEHOLDER}', False);
        //$cF->addInputField('reference', '{MESA_CLI_EDITCLIENTREFERENCE}', 'text', 'input-block-level', $this->Client->reference, '{MESA_CLI_EDITCLIENTREFERENCEPLACEHOLDER}', False);
        $cF->addInputField('street_name', '{MESA_CLI_EDITCLIENTADRES}', 'text', 'input-block-level', $this->Client->street_name, '{MESA_CLI_EDITCLIENTADRESPLACEHOLDER}', False);
        $cF->addInputField('street_number', 'Huisnummer', 'text', 'input-block-level', $this->Client->street_number, 'Huisnummer', False);
        $cF->addInputField('postal_code', '{MESA_CLI_EDITCLIENTPOSTAL}', 'text', 'input-block-level', $this->Client->postal_code, '{MESA_CLI_EDITCLIENTPOSTALPLACEHOLDER}', False);
        $cF->addInputField('place', '{MESA_CLI_EDITCLIENTPLACE}', 'text', 'input-block-level', $this->Client->place, '{MESA_CLI_EDITCLIENTPLACEPLACEHOLDER}', False);
        $cF->addInputField('country', '{MESA_CLI_EDITCLIENTCOUNTRY}', 'text', 'input-block-level', $this->Client->country, '{MESA_CLI_EDITCLIENTCOUNTRYPLACEHOLDER}', False);

        $cF->addInputField('telephone', '{MESA_CLI_EDITCLIENTPHONE}', 'text', 'input-block-level', $this->Client->telephone, '{MESA_CLI_EDITCLIENTPHONEPLACEHOLDER}', False);
        $cF->addInputField('cellphone', 'GSM nummer', 'text', 'input-block-level', $this->Client->cellphone, 'GSM nummer', False);
        $cF->addInputField('email', '{MESA_CLI_EDITCLIENTEMAIL}', 'text', 'input-block-level', $this->Client->email, '{MESA_CLI_EDITCLIENTEMAILPLACEHOLDER}', False);

        $cF->addInputField('title', '{MESA_CLI_EDITCLIENTCONTACTPERSONTITLE}', 'text', 'input-block-level', $this->Client->title, '{MESA_CLI_EDITCLIENTCONTACTPERSONTITLEPLACEHOLDER}', False);
        $cF->addInputField('fname', '{MESA_CLI_EDITCLIENTCONTACTPERSONFNAME}', 'text', 'input-block-level', $this->Client->fname, '{MESA_CLI_EDITCLIENTCONTACTPERSONFNAMEPLACEHOLDER}', False);
        $cF->addInputField('mname', 'Tussenvoegsel', 'text', 'input-block-level', $this->Client->mname, 'Tussenvoegsel', False);
        $cF->addInputField('lname', '{MESA_CLI_EDITCLIENTCONTACTPERSONLNAME}', 'text', 'input-block-level', $this->Client->lname, '{MESA_CLI_EDITCLIENTCONTACTPERSONLNAMEPLACEHOLDER}', False);

        $cF->addInputField('nvwa_number', 'NVWA nummer', 'text', 'input-block-level', $this->Client->nvwa_number, 'NVWA number', False);
        $cF->addInputField('debit_number', 'Debiteur nummer', 'text', 'input-block-level', $this->Client->debit_number, 'Debiteur number', False);

        $fields = $cF->render();        
        $this->_template->setByArray($fields);

        $allCats = upa('clientCategories', 'all', array(), False );

        $table = new tableFactory();
        $table->setTableId('catSelectTable');
        $table->loadTemplate('catSelectTable');     

        foreach($allCats as $idx => $cat){        
            $allCats[$idx]['active'] = '';       
        }
                    
        if(empty($allCats)){
            $allCats = 'Geen categorien gevonden';
        }

        $table->loadValues($allCats);

        $this->_template->set('cat_selector', $table->renderTable());

        $this->_template->set('name', $this->Client->name);
        $this->_template->set('id', $this->Client->id);
        



    }



     function edit($id = False){

        $this->Client->where('id', $id);
        $results = $this->Client->search();

        if($id == False){
              $this->_template->set('action', '{MESA_CLI_CLIENTACTIONADD}');
        }

        elseif($id !== False){
               $this->_template->set('action', '{MESA_CLI_CLLIENTACTIONEDIT}');
               if(empty($results)){
                    $this->reRoute('clients/listing', True);
               } else {
                    $this->Client->arrayToModel($results[0]);
               }
        }

        $cF = new formFactory('clients');
        $cF->setId('addAnalysisForm');
        $cF->action('{LB}/clients/doSave/' . $id);
        $cF->method('POST');
        $cF->addClass('form-horizontal');
        $cF->setTemplate('generic');        

        $cF->addInputField('name', '{MESA_CLI_EDITCLIENTNAAM}', 'text', 'input-block-level', $this->Client->name, '{MESA_CLI_EDITCLIENTNAAMPLACEHOLDER}', False);
        //$cF->addInputField('reference', '{MESA_CLI_EDITCLIENTREFERENCE}', 'text', 'input-block-level', $this->Client->reference, '{MESA_CLI_EDITCLIENTREFERENCEPLACEHOLDER}', False);
        $cF->addInputField('street_name', '{MESA_CLI_EDITCLIENTADRES}', 'text', 'input-block-level', $this->Client->street_name, '{MESA_CLI_EDITCLIENTADRESPLACEHOLDER}', False);
        $cF->addInputField('street_number', 'Huisnummer', 'text', 'input-block-level', $this->Client->street_number, 'Huisnummer', False);
        $cF->addInputField('postal_code', '{MESA_CLI_EDITCLIENTPOSTAL}', 'text', 'input-block-level', $this->Client->postal_code, '{MESA_CLI_EDITCLIENTPOSTALPLACEHOLDER}', False);
        $cF->addInputField('place', '{MESA_CLI_EDITCLIENTPLACE}', 'text', 'input-block-level', $this->Client->place, '{MESA_CLI_EDITCLIENTPLACEPLACEHOLDER}', False);
        $cF->addInputField('country', '{MESA_CLI_EDITCLIENTCOUNTRY}', 'text', 'input-block-level', $this->Client->country, '{MESA_CLI_EDITCLIENTCOUNTRYPLACEHOLDER}', False);

        $cF->addInputField('telephone', '{MESA_CLI_EDITCLIENTPHONE}', 'text', 'input-block-level', $this->Client->telephone, '{MESA_CLI_EDITCLIENTPHONEPLACEHOLDER}', False);
        $cF->addInputField('cellphone', 'GSM nummer', 'text', 'input-block-level', $this->Client->cellphone, 'GSM nummer', False);
        $cF->addInputField('email', '{MESA_CLI_EDITCLIENTEMAIL}', 'text', 'input-block-level', $this->Client->email, '{MESA_CLI_EDITCLIENTEMAILPLACEHOLDER}', False);


        $cF->addInputField('title', '{MESA_CLI_EDITCLIENTCONTACTPERSONTITLE}', 'text', 'input-block-level', $this->Client->title, '{MESA_CLI_EDITCLIENTCONTACTPERSONTITLEPLACEHOLDER}', False);
        $cF->addInputField('fname', '{MESA_CLI_EDITCLIENTCONTACTPERSONFNAME}', 'text', 'input-block-level', $this->Client->fname, '{MESA_CLI_EDITCLIENTCONTACTPERSONFNAMEPLACEHOLDER}', False);
        $cF->addInputField('mname', 'Tussenvoegsel', 'text', 'input-block-level', $this->Client->mname, 'Tussenvoegsel', False);
        $cF->addInputField('lname', '{MESA_CLI_EDITCLIENTCONTACTPERSONLNAME}', 'text', 'input-block-level', $this->Client->lname, '{MESA_CLI_EDITCLIENTCONTACTPERSONLNAMEPLACEHOLDER}', False);

        $cF->addInputField('nvwa_number', 'NVWA nummer', 'text', 'input-block-level', $this->Client->nvwa_number, 'NVWA number', False);
        $cF->addInputField('debit_number', 'Debiteur nummer', 'text', 'input-block-level', $this->Client->debit_number, 'Debiteur number', False);

        $cF->addButton('save', 'icon-save', False, 'btn btn-primary', '{MESA_CLI_SAVE}', False);        
        $cF->submitTrough('save');  

        $this->_template->set('name', $this->Client->name);
        $this->_template->set('id', $this->Client->id);
        $this->_template->set('edit_client_form', $cF->render());
    }


    function doSave($id = ''){

        $this->render = 0;
        $this->Client->postToModel($_POST);
        $new = False; 

        if($id != ''){
            $this->Client->id = $id;
        }

        $this->Client->last_edit = time();
        $this->Client->save();

        if($id == ''){
            $id = $this->Client->lastInsertId;
            $new = True;
        }

        //notify portal of new/edited client
        upa('portal', 'updateclient', array($id), False);

        if($new === True){

            //setup the common assay availability for this client
            //upa('portalAssays', 'setAllActiveForClient', array($id), False);

            $this->reRoute('clients/editCat/' . $id, True);
        } else{
            $this->reRoute('clients/show/' . $id, True);
        }
        
    }

    function saveNewClient()
    {

        $this->render = 0;                    

        $this->Client->postToModel($_POST);
        
        //hacking a fix for the form rendering weirdly if you put just "name"
        $this->Client->name = $_POST['cname'];    
        $this->Client->last_edit = time();
        $this->Client->save();
        $id = $this->Client->lastInsertId;

        //attach categories, json_encoded in categories_selected
        $cats = json_decode($_POST['categories_selected'], True);

        if(is_array($cats)){
            foreach($cats as $cat){
                // $this->addCategoryToClient($id, $cat);
                upa('clients', 'addCategoryToClient', array($id, $cat), False);
            }
        }

        upa('portal', 'updateclient', array($id), False);

        sleep(1);

        upa('portalAssays', 'setAllActiveForClient', array($id), False);

        sleep(1);

        $response = upa('portal', 'createContactLists', array($id, 'Standaard verzendlijst'), False);          

        //convert to array, from $response json
        $response = json_decode($response, True);                    

        foreach($_POST as $postVar => $postValue)
        {

            //does this start with cg_name_ 
            if(substr($postVar, 0, 8) == 'cg_name_')
            {
                $cgId = substr($postVar, 8);
                $cgEmail = $_POST['cg_email_' . $cgId];
                $cgName = $postValue;
                $cgName = trim($cgName);
                $cgEmail = trim($cgEmail);

                if (filter_var($cgEmail, FILTER_VALIDATE_EMAIL)) 
                {
                    upa('portal', 'addContact', array($cgName, $cgEmail,$response['id']), false); 
                }
        
            }

        }
                          
        $this->reRoute('clients/show/' . $id, True);

    }

    function fetch($clientId){
        $this->Client->where('id', $clientId);
        $result = $this->Client->search();

        if(array_key_exists(0, $result)){
            return $result['0'];
        } else{
            return False;
        }
    }

    function clientIdToName($clientId){
        $this->Client->id = $clientId;
        $this->Client->select(['name']);
        $this->Client->limit(1);
        
        $results = $this->Client->search();                

        if(empty($results)){
            return 'Fout: Klant ID bestaat niet (ID:' . $clientId . ')';
        } else{
            return $results[0]['name'];
        }

    }

    function addCategoryToClient($client, $category)
    {                
        $this->render = False; 
        $params = array();
        $params['client_id'] = $client;
        $params['category_id'] = $category;        
        $sql = "SELECT * FROM `categories_clients` WHERE `client_id` = :client_id AND `clientcategory_id` = :category_id LIMIT 1";
        $existing = $this->Client->customQuery($sql, $params);

        if(count($existing) == 0)
        {
            $sql = "INSERT INTO `categories_clients` (`id`, `client_id`, `clientcategory_id`) VALUES (NULL, :client_id, :category_id) ";     
            $link = $this->Client->customSetQuery($sql, $params);
        }                             
    }

    function removeCategoryFromClient($client, $category)
    {
        $this->render = False; 
        $params = array();
        $params['client_id'] = $client;
        $params['category_id'] = $category;                
        $sql = "DELETE FROM `categories_clients` WHERE `categories_clients`.`client_id` = :client_id AND `categories_clients`.`clientcategory_id` = :category_id";
        $link = $this->Client->customSetQuery($sql, $params);        
    }
    
    function clientCategories($client)
    {        
        $params = array();
        $params['client_id'] = $client;
        
        $sql = "SELECT categories_clients.*,clientcategories.name
        FROM categories_clients 
        LEFT JOIN clientcategories
        ON categories_clients.clientcategory_id = clientcategories.id
        WHERE categories_clients.client_id = :client_id ";

        $categories = $this->Client->customQuery($sql, $params);        
        return $categories;
    }

    function clientsInCategory($category)
    {        
        $params = array();
        $params['category_id'] = $category;
        
        $sql = "SELECT categories_clients.*,clients.name, clients.id AS clientid
        FROM categories_clients 
        LEFT JOIN clients
        ON categories_clients.client_id = clients.id
        WHERE categories_clients.clientcategory_id = :category_id AND clients.active = 1";

        $categories = $this->Client->customQuery($sql, $params);
        return $categories;
    }

    public function editCat($clientId)
    {

        $client = upa('clients', 'fetch', array($clientId), False);
        $cats = $this->clientCategories($clientId);
        $allCats = upa('clientCategories', 'all', array(), False );
        $activeCats = array_column($cats, 'clientcategory_id');
             
        $table = new tableFactory();
        $table->setTableId('catSelectTable');
        $table->loadTemplate('catSelectTable');        

        foreach($allCats as $idx => $cat){
            if(in_array($cat['id'], $activeCats)){
                $allCats[$idx]['active'] = 'checked';
            }else{
                $allCats[$idx]['active'] = '';
            }
        }
              
        if(empty($allCats)){
            $allCats = 'Geen categorien gevonden';
        }

        $table->loadValues($allCats);

        $this->_template->set('category_table', $table->renderTable());
        $this->_template->set('id', $clientId);
        $this->_template->set('name', $client['name']);
    }

    public function importCats($code){

        $this->render = false;

        if($code !== 'import'){
            return;
        }

       
        $path =  ROOT . DS . 'app' . DS . 'private' . DS . 'clientcats.csv';
        $fp = fopen($path, 'r');
        
        fgetcsv($fp);

        $delimiter = ',';
        $first = True;

        $contactLists = array();
        $knownCats = array();

        while (!feof($fp) )
        {
            
            if($first == True){
                $first = False;
                continue;
            }

            $line = fgets($fp, 2048);
            $data = str_getcsv($line, $delimiter);            
                 
            $cats = explode(',', $data[4]);
            $clientId = $data[0];
		    $cparams = array();                    
            $cparams['id'] = $clientId;

	        $sql = "SELECT `id` FROM `clients` WHERE `id` = :id LIMIT 1";
            $existingc = $this->Client->customQuery($sql, $cparams);

            if(count($existingc) == 0)
            {
			    print('DNE-');
 			    continue;
		    }

            
            foreach($cats as $cat){
            
                $cat = trim($cat);

		

                //not in buffer yet
                if(!array_key_exists($cat, $knownCats))
                {
			
                    //exists in DB?
                    $params = array();                    
                    $params['name'] = $cat;        
                    $sql = "SELECT * FROM `clientcategories` WHERE `name` = :name LIMIT 1";
                    $existing = $this->Client->customQuery($sql, $params);

                    if(count($existing) == 0)
                    {
 			
                        $sql = "INSERT INTO `clientcategories` (`id`, `name`) VALUES (NULL, :name) ";     
                        $link = $this->Client->customSetQuery($sql, $params);
                        $thisCatId = $this->Client->lastInsertId;                        
                    }           

                    else
                    {
			
                        $thisCatId = $existing[0]['id'];
                    }
		


                    $knownCats[$cat] = $thisCatId;
                 
                }

                else
                {
			
                    $thisCatId = $knownCats[$cat];
                }
	

                $this->addCategoryToClient($clientId, $thisCatId);                    

            }



            $thisContactListName = $data[2];
            
            
            if(!array_key_exists($clientId, $contactLists))
            {                                
                $contactLists[$clientId] = array();
            }

            if(!array_key_exists($thisContactListName, $contactLists[$clientId])){
                $contactLists[$clientId][$thisContactListName] = array();
            }

            //push email if not empty 
            $thisContact = trim($data[3]);

            if(!is_null($thisContact)){
                array_push($contactLists[$clientId][$thisContactListName], $thisContact);
            }            
            
                        
        }

        foreach($contactLists as $clientId => $lists){

            foreach($lists as $listName => $contacts){
                
                $response = upa('portal', 'createContactLists', array(  $clientId , $listName), False);     
                $responseObj = json_decode($response);

                if(!$responseObj->id){
                    print($response );
                    print('++');
                    parray($responseObj);
                    die();
                }
                            
                foreach($contacts as $contact){                    
                    upa('portal', 'addContact', array(trim($contact), trim($contact), $responseObj->id), false);
                }


            }

        }

        fclose($fp);

    }

function has_files(){
	
$this->render = False;

$sqlCC = "SELECT * from clients;";
$cc = $this->Client->customQuery($sqlCC, array());

print('Klantid   Klant-naam');

foreach($cc as $line)
{

$att = json_decode($line['attachment'], JSON_FORCE_OBJECT);

if(is_array($att) && count($att) > 0)
{
print($line['id'] . '   '  . $line['name'] . '  ' . $line['attachment'] . ' <br />');
}

}



}

    function dump_contact_lists()
    {
        $this->render = false; 

        $sqlCClinks = "SELECT * from categories_clients;";
        $sqlCC = "SELECT * from clientcategories";

        $ccLinks = $this->Client->customQuery($sqlCClinks, array());
        
        $cc = $this->Client->customQuery($sqlCC, array());
        $cc = array_column($cc, null, 'id');

        $csv = fopen('php://temp', 'r+');
        $cls = upa('portal', 'getAllContactListsByLine', array(), False);
        $cl = json_decode($cls, JSON_FORCE_OBJECT);

    
        $headers =  [
            'klant-id', 'klant-naam', 'actief',  'categorie', 'verzendlijst', 'email'        
        ]; 
        
        
        $this->fputcsv_eol($csv,$headers, ';' , "\"" );


        foreach($cl as $line)
        {
            
            

            $thisClientLinks =  array_filter($ccLinks, function ($var) use ($line) {                
                return ($var['client_id'] == $line['id']);
            });

            $thisClientCategoryIds = array_column($thisClientLinks, 'clientcategory_id');
            $clientCategories = '';

		

            foreach($thisClientCategoryIds as $ccid)
            {
                $cat = $cc[$ccid];
                if(is_array($cat))
                {
                    $clientCategories .= $cat['name'] . '- ';
                }            
            }

            $row = [
                $line['id'],
                $line['cname'],
                $line['active'],		
                substr($clientCategories, 0, -2),
                $line['listname'],
                $line['email']            
            ];
        
            $this->fputcsv_eol($csv,$row, ';' , "\"" );
            
        }   
        
        
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"test.csv\"");
  

        rewind($csv);
        print "\xEF\xBB\xBF"; // UTF-8 BOM        
        print stream_get_contents($csv);
        fclose($csv);    

        return;

    }


    function dump()
    {               
        $this->render = false; 

        $sqlCClinks = "SELECT * from categories_clients;";
        $sqlCC = "SELECT * from clientcategories";

        $ccLinks = $this->Client->customQuery($sqlCClinks, array());
        
        $cc = $this->Client->customQuery($sqlCC, array());
        $cc = array_column($cc, null, 'id');
                    
        $csv = fopen('php://temp', 'r+');
        $cls = upa('portal', 'getAllContactLists', array(), False);
        $cl = json_decode($cls, JSON_FORCE_OBJECT);

        $sql = "SELECT id, date_registered, client FROM `samples` ORDER BY date_registered ASC";
        $samples = $this->Client->customQuery($sql, array());

        $sql = "SELECT id, client FROM `projects`";
        $projects = $this->Client->customQuery($sql, array());

        fwrite($csv, 'SEP=;'  . PHP_EOL);


        $clientInfo = [];
        $std =  [
            'earliest' => null,
            'latest' => null,
            'n' => 0, 
            'n_projects' => 0
        ];

        foreach($samples as $sample)
        {

            if(array_key_exists($sample['client'], $clientInfo))
            {
                $clientInfo[$sample['client']]['latest'] = $sample['date_registered'];
                $clientInfo[$sample['client']]['n'] += 1;
            } 
            
            else
            {
                
                $clientInfo[$sample['client']] = [
                    'earliest' => $sample['date_registered'], 
                    'latest' => null,
                    'n' => 1, 
                    'n_projects' => 0
                ];
            }                    
        }

        foreach($projects as $project)
        {
            if(array_key_exists($project['client'], $clientInfo))
            {
                $clientInfo[$project['client']]['n_projects'] += 1;
            }
        }
        
        $sql = "SELECT * from `clients` ";
        $clients = $this->Client->customQuery($sql, array());

        $headers =  [
            'ID', 'Klant Naam', 
            'Adres', 'Postcode', 'Plaats', 'Land', 
            'Telefoon', 'Contact Persoon', 'Telefoon mobiel', 'email',
            'debiteurnummer',
            'Actief',
            'Categorien',
            'Eerste analyse', 'Laatste analyse',
            'Bijzonderheden',
            'N monsters',
            'N projecten',
            'Contact lijsten'
        ]; 
        
        $this->fputcsv_eol($csv,$headers, ';' , "\"" );

        foreach($clients as $client)
        {

            if(!array_key_exists($client['id'], $clientInfo))
            {
                $client = array_merge($client, $std);
            }

            else
            {
                $client = array_merge($client, $clientInfo[$client['id']]);
            }
            
                       
            $adres = $client['street_name'] . ' ' . $client['street_number'];
            $cperson = $client['title'] . ' ' . $client['fname'] . ' ' . $client['mname'] . ' ' . $client['lname'];

            $thisClientLinks =  array_filter($ccLinks, function ($var) use ($client) {                
                return ($var['client_id'] == $client['id']);
            });

            $thisClientCategoryIds = array_column($thisClientLinks, 'clientcategory_id');
            $clientCategories = '';

            foreach($thisClientCategoryIds as $ccid)
            {
                $cat = $cc[$ccid];
                if(is_array($cat))
                {
                    $clientCategories .= $cat['name'] . '; ';
                }            
            }
            

            $row = [
                    $client['id'], $client['name'],
                    $adres, $client['postal_code'], $client['place'], $client['country'],
                    $client['telephone'], $cperson, $client['cellphone'], $client['email'], 
                    $client['debit_number'],
                    ($client['active'] == 1) ? 'Ja' : 'Nee',
                    $clientCategories,
                    ( $client['earliest'] != null) ?  date('d-m-Y', $client['earliest']) : 'NVT',
                    ( $client['latest'] != null) ? date('d-m-Y', $client['latest']) : 'NVT', 
                    strip_tags($client['notes']),
                    $client['n'],
                    $client['n_projects']                    
            ]; 
           
            if(array_key_exists($client['id'], $cl))
            {

                foreach($cl[$client['id']] as $ccl)
                {
                                        
                    $thisClMembers = '';
                    
                    foreach($ccl['members'] as $clMember)
                    {
                        $thisClMembers = $thisClMembers . $clMember['name'] . '(' . $clMember['email'] . ');';
                    }
                                        

                    array_push($row, $ccl['name']);
                    array_push($row, $thisClMembers);
                }

            }
            
            $this->fputcsv_eol($csv,$row, ';' , "\"" );


        }

        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"test.csv\"");
  

        rewind($csv);
        print "\xEF\xBB\xBF"; // UTF-8 BOM        
        print stream_get_contents($csv);
        fclose($csv);    

        return;
     
        
    }

    
    private function  fputcsv_eol($handle, $array, $delimiter = ',', $enclosure = '"', $eol = PHP_EOL) {
      //$delimiter = ',';
      $return = fputcsv($handle, $array, $delimiter, $enclosure);
      if($return !== FALSE && "\n" != $eol && 0 === fseek($handle, -1, SEEK_CUR)) {
        fwrite($handle, $eol);
      }
      return $return;
    }


    public function reportSettings($client)    
    {

        $this->Client->where('id', $client);
        $results = $this->Client->search();


        if(empty($results)){
            $this->reRoute('clients/listing', True);
        }

        $client = $results[0];

        $cF = new formFactory('clients');
        $cF->setId('addAnalysisForm');
        $cF->action('{LB}/clients/doSaveReportSettings/' . $client['id']);
        $cF->method('POST');
        $cF->addClass('form-horizontal');
        $cF->setTemplate('generic');

        $redTrip = array();
        $redTrip[0] = 'Nee';
        $redTrip[1] = 'Ja';

        $cF->addDropdownField('trip_red', 'Op rapport overschreden waardes in rood?', 'input-block-level', $client['trip_red'], $redTrip, False);
        $cF->addTextArea('report_notes', 'Notities voor rapport opmaken', '', 'textarea-block-level', $client['report_notes'], '', array('rows'=>2));

        $cF->addButton('save', 'icon-save', False, 'btn btn-primary', '{MESA_CLI_SAVE}', False);
        $cF->submitTrough('save');

        $this->_template->set('edit_client_form', $cF->render());
        $this->_template->set('name', $client['name']);
        $this->_template->set('id', $client['id']);
    }

    public function doSaveReportSettings($client)
    {
        $this->Client->id = $client; 
        $this->Client->trip_red = $_POST['trip_red'];
        $this->Client->report_notes = $_POST['report_notes'];
        $this->Client->save();
        $this->reRoute('clients/show/' . $client, True);
    }

    public function allClientsById()
    {
        $this->render = false; 
        $clients =$this->Client->search();         
        $clientArr = [];

        foreach($clients as $client)
        {
            $clientArr[$client['id']] = $client['name'];
        }

        return $clientArr; 
    }

    public function checkClientAlreadyExists()
    {

        $name = $_POST['name'] ?? False;

        $this->render = false; 

        $obj = [    
            'candidate' => [
                'id' => '',
                'name' =>  ''
            ], 
            'exists' => False
        ];  

        $candidate = $this->clientNameSearcher($name);

        if($candidate !== False)
        {
            $clientDetails = upa('clients', 'fetch', array($candidate), False);
            $obj['exists'] = True;
            $obj['candidate']['id'] = $candidate;
            $obj['candidate']['name'] = $clientDetails['name'];            
            $obj['candidate']['text'] = $clientDetails['name'] . ' (Klant ID: ' . $candidate . ')';            

        }

        print(json_encode($obj));
      
    }

    private function clientNameSearcher($name)
    {
        
        $name = trim($name);

        $exists = $this->existsByName($name);

        if($exists !== False)
        {            
            
            return $exists;
        }

        $phonetic = $this->existsPhonetically($name);

        if($phonetic !== False)
        {
            return $phonetic;
        }

        $fuzzy = $this->existsFuzzy($name);

        if($fuzzy !== False)
        {
            return $fuzzy;
        }

        return False;
    }

    private function existsByName($name)
    {

        $this->Client->where('name', $name);

        $results = $this->Client->search();

        if(empty($results)){
            return False;
        } else{
            return $results[0]['id'];
        }

    }

    private function existsPhonetically($name)    
    {
        
        $sql = "SELECT name,id FROM clients WHERE SOUNDEX(LOWER(name)) = SOUNDEX(LOWER(:name)) ";

        $params = array();
        $params['name'] = $name;

        $this->Client->deepFreed();
        
        $results = $this->Client->customQuery($sql, $params);

        if(empty($results)){
            return False;
        } else{
            return $results[0]['id'];
        }

    }

    private function existsFuzzy($name)
    {

        $nameLength = strlen($name);

        $maxFuzz = 1; 

        if($nameLength <= 3)
        {
            $maxFuzz = 0;
        }

        elseif($nameLength < 5)
        {
            $maxFuzz = 1;
        }
        
        else if($nameLength >= 5 && $nameLength < 10)
        {
            $maxFuzz = 2;
        }
        
        else
        {
            $maxFuzz = 4;
        }

        

        $this->Client->deepFreed();

        $this->Client->select(['id', 'name']);

        $results = $this->Client->search();
        

        //use levenshtein distance to find the closest match
        $closest = -1;
        $closestId = False;
        
        foreach($results as $result)
        {
            
            $lev = levenshtein(strtolower($name), strtolower($result['name']));

            if($lev == 0)
            {
                $closest = $result['id'];
                break;
            }

            if ($lev <= $closest || $closest < 0) {
                
                $closestId = $result['id'];                

                $closest = $lev;
              
            }
        }

        if($closest <= $maxFuzz)
        {
            return $closestId;
        }

        return False; 

    }

    public function checkClientAdresAlreadyExists()
    {

        $this->render = False; 

        //street_name
        //street_number
        //postal_code

        $obj = [    
            'candidate' => [
                'id' => '',
                'name' =>  ''
            ], 
            'exists' => False
        ];  

        //if any of the params not set, return fasle
        if(!isset($_POST['street_name']) || !isset($_POST['street_number']) || !isset($_POST['postal_code']))
        {
            print(json_encode($obj));
            return;
        }

        $this->Client->where('street_name', $_POST['street_name']);
        $this->Client->where('street_number', $_POST['street_number']);
        $this->Client->where('postal_code', $_POST['postal_code']);

        $results = $this->Client->search();


        if(empty($results)){
            print(json_encode($obj));
            return;
        } else{
            $obj['exists'] = True;
            $obj['candidate']['id'] = $results[0]['id'];
            $obj['candidate']['name'] = $results[0]['name'];            
            $obj['candidate']['text'] = $results[0]['name'] . ' (Klant ID: ' . $results[0]['id'] . ')';            
            print(json_encode($obj));
            return;
        }


    }




    
  
}
