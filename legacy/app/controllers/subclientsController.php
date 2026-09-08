<?PHP

class subClientsController extends Controller{
    
     
    function fetchSubClients($client){        
        //$this->render = 0;
        $this->SubClient->where('client', $client);
        $results = $this->SubClient->search();        
        
        if(!empty($results)){
            return $results;        
        } else{
            return False;
        }
        
    }
    
    function fetch($subclient){
        $this->SubClient->where('id', $subclient);
        $results = $this->SubClient->search();        
        return $results['0'];     
    }
    
    function printSubClientsList($client){
        
        /* $subArr = $this->fetchSubClients($client);
        
        $tF = new tableFactory();
        $tF->setTableId('subclients');
        $tF->loadTemplate('subClientList');
        
        if(empty($subArr)){
            $subArr = 'No subclients found';
        }
        
        $tF->loadValues($subArr);
        return $tF->renderTable(); */
        
        $subclients = $this->fetchSubClients($client);
        
        
        if($subclients == False){
            return '{no_subclients}';
        } else{
            
            $subClientList = '';
            foreach($subclients as $subclient){
                $subClientList .= generateHTML('clients/subclient', $subclient);
            }
            
            return generateHTML('clients/subclientWrap', array('contents' => $subClientList));            
        }                        
    }
    
    function renderSubClientList($client){
        
        $this->render = 0;        
        $list = $this->printSubClientsList($client);
        print $list;
    }
    
    function printSubClientSelect($client){
        
        $this->render = 0;
        $subArr = $this->fetchSubClients($client);
        $options = '<option value="NULL">Select subclient</option>';    
        
        foreach($subArr as $subClient){
            $options .= '<option value="' . $subClient['id']  . '">' . $subClient['loc_name'] . ' - ' . $subClient['loc_place'] . ' </option>';
        }
        
        print $options;
    }
    
    function subclientIdToName($subclient){            
            //$this->SubClient->where('id', $subclient);
            //$results = $this->SubClient->search();
            //return $results[0]['loc_name'];
            return NULL;
    }
    
    function view(){
        
    }
    
    
    
    
    function edit($client, $id = False){
    
        $this->doNotRenderHeader = True;
        
        $this->SubClient->where('id', $id);
        $results = $this->SubClient->search();   
        
        if($id == False){
              $this->_template->set('action', '{MESA_CLI_SUBCLIENTADD}');
        }
        
        elseif($id !== False){
               $this->_template->set('action', '{MESA_CLI_SUBCLIENTEDIT}');         
               if(empty($results)){
                   throwError('SUBCLIENT_NOT_FOUND');
               } else {
                    $this->SubClient->arrayToModel($results[0]);  
               }
        }                  
                        
        $cF = new formFactory('subclients');        
        $cF->setId('subClientForm');
        $cF->action('');
        $cF->method('POST');                
        $cF->setTemplate('generic');        
        
        $labels['loc_name'] = 'Subclient name';
        $labels['loc_adres'] = 'Adres';
        $labels['loc_postal'] = 'Postal code';
        $labels['loc_place'] = 'Place';
        $labels['loc_country'] = 'Country';
        $labels['loc_phone'] = 'Phone';
        $labels['loc_fax'] = 'Fax';
        $labels['loc_email'] = 'Email';
        $labels['loc_raploc'] = 'Location as on report';        
        $labels['loc_raptitle'] = 'Subclient contact person title';
        $labels['loc_rapfname'] = 'Contact person fist name';
        $labels['loc_raplname'] = 'Contact person last name';
        $labels['loc_rapfun'] = 'Function';
        $labels['loc_rapadres'] = 'Report adres';
        $labels['loc_rappostal'] = 'Report postal code';
        $labels['loc_rapplace'] = 'Report place';
        $labels['loc_rapcountry'] = 'Report country';
        
        $cF->autoForm($labels,  array('id' => True, 'client'=> True), $this->SubClient);
        $cF->addInputField('client', '', 'hidden', 'hide', $client, False, False);
        
        
        $cF->submitTrough('saveSubclient', 'saveSubClient');                
        $this->_template->set('form', $cF->render());
        
    }
    
    
    
    function doSave($id = False){
        
        $this->render = 0;
        $this->SubClient->postToModel($_POST);
                
        if($id != ''){
               $this->SubClient->id = $id;
        } 
               
        $this->Subclient->last_edit = time();
        $this->SubClient->save();                        
    }
    
    function remove($id){

        return;
        $this->render = 0;        
        $this->SubClient->id = $id;
        $this->SubClient->remove();
        
        pa('projects', 'removeSubclientProjects', array($id));
        
        
    }
    
    function removeAllSubclients($clientId){
        return;
        $this->render = 0;
        $this->SubClient->where('client', $clientId);
        $results = $this->SubClient->search();
        
        if(!empty($results)){
            
            foreach($results as $subclient){
                $this->SubClient->id = $subclient['id'];
                $this->SubClient->remove();        
            }                        
        }
    }
    
    
}