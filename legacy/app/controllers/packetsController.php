<?PHP

class packetsController extends controller{
    
   function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }
    
    function listing(){
    
        $packets = $this->Packet->listPackets();
        
        if(empty($packets)){
            $values = '{MESA_PCK_NONE}';
        } else {
            $values = $packets;
        }        
            
        $tF = new tableFactory();
        $tF->loadTemplate('packetsListing');
        $tF->loadValues($values);         
        $this->_template->set('available_packets_listing', $tF->renderTable());
        
    }
    
    function add(){
        
        $addForm = new formFactory($this->_controller);
                
        $addForm->setId('addPacketForm');
        $addForm->action('{LB}/packets/addSubmit/');
        $addForm->method('POST');
        
        $addForm->addClass('form-horizontal');       
        $addForm->setTemplate('generic');
        
        $addForm->addInputField('name', 'Packet name', 'text', 'input-block-level', '', 'Enter a name for the packet here', False);                
        $addForm->addValidation('name', 'NO_DUPLICATE');     

        $addForm->addInputField('tags', 'Packet tags', 'text', 'input-block-level', '', 'Enter one or more tags for the packet here, used for searching', False);                
        
        $addForm->addButton('submitButton', 'icon-plus-sign', False, 'btn btn-primary btn-small', 'Add packet', False);        
        $addForm->submitTrough('submitButton');
                                        
        $this->_template->set('add_packet_form', $addForm->render());
        
    }
    
    function addSubmit(){
        
        $this->render = 0;
        $this->Packet->postToModel();
        $this->Packet->save();                
        $this->reRoute('packets/listing');      
    }
    

    
    function edit($packetId){               
        
        
        $this->Packet->id = $packetId;
        $results = $this->Packet->search();
        $this->Packet->arrayToModel($results[0]);                
       
        /* Bound tests table */
        $tF = new tableFactory();        
        $existingTests = performAction('packetsTests', 'get', array($packetId));                        
        if(empty($existingTests)){
            $existingTests = '{MESA_PCK_NON_BOUND}';
        }       
        else{
            foreach($existingTests as $arrId=>$test){
                //$thisAnalysis = pa('analysisTests', 'getSingle', array($test['flow_id']));
                $thisAnalysis = pa('flows', 'getFlow', array($test['flow_id']));
                $existingTests[$arrId]['test_name'] = $thisAnalysis['name'];
            }
        }
        
        $tF->loadTemplate('packetsTestsListing');
        $tF->loadValues($existingTests); 
                
        $addForm = new formFactory($this->_controller);
        
        $addForm->setId('addTestPacketForm');
        $addForm->action('{LB}/packetsTests/addToPack/');
        $addForm->method('POST');
        
        $addForm->addClass('');       
        $addForm->setTemplate('generic');  
        
        //$testsArray = pa('analysisTests', 'generateDropdownOptions', array());
        
        $flowsArray = pa('flows', 'generateDropdownOptions', array());
        $addForm->addDropdownField('test', 'Packet to add', 'input-block-level', False, $flowsArray, False);                                    
        $addForm->addInputField('packet_id', '', 'hidden', 'hidden', $packetId, False, False);
        
        $addForm->submitTrough('submitAddTest');
        
        $this->_template->set('tags', $this->Packet->tags );
        $this->_template->set('name', $this->Packet->name );
        $this->_template->set('bound_table', $tF->renderTable());
        $this->_template->set('add_test_form', $addForm->render());
        $this->_template->set('packet_id', $this->Packet->id );
    }
    
    function updateTags(){
        
        $this->render = 0;
        
        $packetId = $_POST['packet_id'];
        $newTags = $_POST['tags'];
        
        $this->Packet->id = $packetId;
        $this->Packet->tags = $newTags;
        $this->Packet->save();
        
    }
    
    function removePacket(){
        
        $this->render = 0;
        $packetId = $_POST['packet_id'];
        
        $this->Packet->id = $packetId;
        $this->Packet->remove();
        
        pa('packetsTests', 'removeAll', array($packetId));
        
    }
    
    function generatePacketsList(){
        
        $this->doNotRenderHeader = 0;
        $result = $this->Packet->search(); 
        
        $listItems = '';        
        
        if(empty($result)){
            $listItems .= generateHTML('packetLineEmpty', array());
        }
        
        
        foreach($result as $pack){
            $listItems .= generateHTML('packetLine', $pack);
        }
        
        
        
        return $listItems;        
    }
    
    function getPacket($packetId){
        
        $this->render = 0;
        $this->Packet->where('id', $packetId);
        $result = $this->Packet->search();
        
        return $result[0];
        
    }
    
    function findByTags($query){
        
        $this->render = 0;
        
        $tagArray = explode(';', $query);        
        
        foreach($tagArray as $tag){

            $this->Packet->like('tags', trim($tag));
            
        }
        
        $results = $this->Packet->search();
        
        
        return $results;
        
    }
    
}