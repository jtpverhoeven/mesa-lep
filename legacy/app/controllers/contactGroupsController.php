<?PHP

class contactGroupsController extends controller{


    public function show($clientId){


        $groups = upa('portal', 'getContactLists', array($clientId), False);                        
        
        $clientName = customerIdToName($clientId);
                
        $table = new tableFactory();
        $table->setTableId('contactGroupTable');
        $table->loadTemplate('contactGroupTable');                
        $results = [];

        $groupsDecoded = json_decode($groups, JSON_FORCE_OBJECT);

        if(!empty($groupsDecoded)){
            foreach($groupsDecoded as $group){
                array_push($results, ['id' => $group['id'], 'name' => $group['name']]);
            }
        }
        
        if(empty($results)){
            $results = 'Geen contactgroepen';
        }

        $table->loadValues($results);
        $this->_template->set('contactgroup_table', $table->renderTable());
        $this->_template->set('client_name', $clientName);        
        $this->_template->set('client_id', $clientId);        
    }

    public function add($clientId){

        $clientName = customerIdToName($clientId);        
        $sForm = new formFactory('contactGroups');
        $sForm->setId('addcontactgroupform');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/contactGroups/save');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', 'Contactgroep naam', 'text', 'input-block-level', '', 'Contactgroep naam', False, False);        
        $sForm->addInputField('client', False, 'hidden', 'hide', $clientId, False);

        $sForm->addButton('saveContactGroepBtn' , 'icon-save', False, 'btn btn-primary', 'Opslaan', False);
        $sForm->submitTrough('saveContactGroepBtn');

        $this->_template->set('addGroepForm', $sForm->render());
        $this->_template->set('client_name', $clientName);        
        $this->_template->set('client_id', $clientId);        
    }

    public function save(){              
    
        
        //$this->ContactGroup->name = $_POST['name'];
        //$this->ContactGroup->client = $_POST['client'];
        //$this->ContactGroup->save();        
        
        if(isset($_POST['id'])){
            $response = upa('portal', 'updateContactLists', array( $_POST['client'], $_POST['name'], $_POST['id']), False);            
            $this->reRoute('contactGroups/edit/' .  $_POST['id'], True);
        } 
        
        else{
            $response = upa('portal', 'createContactLists', array( $_POST['client'], $_POST['name']), False);            
            $this->reRoute('contactGroups/show/' .  $_POST['client'], True);
        }              
    }

    public function destroy($client, $listId){
        $response = upa('portal', 'destroyContactList', array( $listId), False);            
        $this->reRoute('contactGroups/show/' .  $client, True);
    }

    public function edit($cgroup){

        //$this->ContactGroup->where('id', $cgroup);
        //$result = $this->ContactGroup->search();

        $result = json_decode(upa('portal', 'showList', array($cgroup), False));
        

        if(empty($result)){
           die('Kon contactgroep niet vinden');
        }
        

        $clientName = customerIdToName($result->client_id);        
        $sForm = new formFactory('contactGroups');
        $sForm->setId('addcontactgroupform');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/contactGroups/save');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', 'Contactgroep naam', 'text', 'input-block-level',  $result->name, 'Contactgroep naam', False, False);        
        $sForm->addInputField('client', False, 'hidden', 'hide', $result->client_id, False);
        $sForm->addInputField('id', False, 'hidden', 'hide', $result->id,  False);

        $sForm->addButton('saveContactGroepBtn' , 'icon-save', False, 'btn btn-primary', 'Naam wijzigen', False);
        $sForm->submitTrough('saveContactGroepBtn');

        $this->_template->set('addGroepForm', $sForm->render());
        $this->_template->set('client_name', $clientName);        
        $this->_template->set('group_name', $result->name);
        $this->_template->set('group_id', $result->id);
        $this->_template->set('client_id', $result->client_id);        

        // $members = upa('contactgroupMembers', 'getGroupMembers', array($result['id']), False);
        
        $members = json_decode(json_encode($result->members), JSON_FORCE_OBJECT);
        
        foreach($members as $key => $arr){
            unset($members[$key]['user']);
        }

        $table = new tableFactory();
        $table->setTableId('contactGroupMembers');
        $table->loadTemplate('contactGroupMembersTable');                

        if(empty($members)){
            $members = 'Geen adressen gevonden';
        }

        $table->loadValues($members);
        $this->_template->set('contactgroupmembers_table', $table->renderTable());


        $changes = json_decode(json_encode($result->changes), JSON_FORCE_OBJECT);

        $cTable = new tableFactory();
        $cTable->setTableId('contactGroupMembers');
        $cTable->loadTemplate('contactGroupChanges');           


        if(empty($changes)){
            $changes = 'Geen adressen gevonden';
        }


        $cTable->loadValues($changes);
        $this->_template->set('contactgroupmembers_changes_table', $cTable->renderTable());



        $eForm = new formFactory('contactGroups');
        $eForm->setId('addContactgroupEmailForm');
        $eForm->addClass('');
        $eForm->action( ALPC_BASEPATH . '/contactgroupMembers/add');
        $eForm->method('POST');
        $eForm->setTemplate('generic');
        $eForm->addInputField('name', 'Naam', 'text', 'input-block-level', NULL, 'Naam persoon', False, False);        
        $eForm->addInputField('email', 'Email', 'text', 'input-block-level',  NULL, 'Email persoon', False, False);        
        $eForm->addInputField('cgroup', False, 'hidden', 'hide', $result->id, False);
        $eForm->submitTrough('addEmailSubmit');

        $this->_template->set('emailForm', $eForm->render());
    }


    public function generateGroupList($client, $preselect = []){
        
        //$this->ContactGroup->where('client', $client);
        //$results = $this->ContactGroup->search();

        $groups = upa('portal', 'getContactLists', array($client), False);
        $groups = json_decode($groups, JSON_FORCE_OBJECT);

        $cGroupList = '';

        foreach($groups as $group){            

            $members = $group['members'];
            
            unset($group['members']);

            $memberText = '';
            foreach($members as $member){
                $memberText .= $member['name'] . '(' . $member['email'] . ') ';
            }

            $group['members'] = $memberText;
            $group['selected'] = '';
            
            if(count($groups) === 1 || in_array($group['id'], $preselect)){
                $group['selected'] = 'checked';
            }
            

            $cGroupList .= generateHTML('contactgroups/group', $group);
        }

        return $cGroupList;
    }


}