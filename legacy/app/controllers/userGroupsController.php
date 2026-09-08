<?PHP

class userGroupsController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function listing(){

        $groups = $this->UserGroup->search();

        if(empty($groups)){
            $groups = 'No groups found';
        } else{

            foreach($groups as $gArrInd => $group){

                //super group?
                if($group['superGroup'] == '1'){
                    $groups[$gArrInd]['superGroupIcon'] = '<i class="icon icon-star-empty " title="{MESA_UGA_SUPERGROUP}"></i>';
                } else{
                    $groups[$gArrInd]['superGroupIcon'] = '';
                }

                //count users in
                $groups[$gArrInd]['userNumber'] = upa('groupUsers', 'countUsersInGroup', array($group['id']));
            }
        }

        $tF = new tableFactory();
        $tF->setTableId('userGroupsTable');
        $tF->loadTemplate('usergroupListing');
        $tF->loadValues($groups);
        $tF->specifyMod('groupLeader', 'getUserName', array(ALPC_TF_SELF));

        $aF = new formFactory('userGroups');
        $aF->setId('addGroupForm');
        $aF->addClass('');
        $aF->action( ALPC_BASEPATH . '/userGroups/saveGroup');
        $aF->method('POST');
        $aF->setTemplate('generic');

        $aF->addInputField('groupName', '{MESA_UGA_GROUPNAME}', 'text', 'input-block-level', '', '{MESA_UGA_GROUPNAME}', False, False);
        $aF->addValidation('groupName', 'NO_DUPLICATE');

        //$aF->addInputField('groupLeader', 'Group leader', 'text', 'input-block-level', '', 'Enter groupleader', False, False);
        //$aF->addInputField('superGroup', 'Super group', 'text', 'input-block-level', '', 'Supergroup', False, False);
        $aF->submitTrough('addGroupSubmit');

        $this->_template->set('addGroupForm', $aF->render() );
        $this->_template->set('user_groups', $tF->renderTable());


    }

    function isPartOfSuperGroup($userId){

        $this->render = 0;
        $partOfGroups = upa('groupUsers', 'getUserAffiliation', array($userId));

        if(empty($partOfGroups)){
          return False;
        }

        foreach($partOfGroups as $groupId){
            $this->UserGroup->where('id', $groupId);
            $this->UserGroup->insertOr();
        }

        $results = $this->UserGroup->search();
        $partOfSuper = False;

        foreach($results as $group){
            if($group['superGroup'] == 1){
                $partOfSuper = True;
            }
        }

        return $partOfSuper;
    }

    function edit($groupId = False){

        $this->UserGroup->where('id', $groupId);
        $group = $this->UserGroup->search();

        if(empty($group)){
            $this->reRoute('userGroups/listing', True);
        } else{
            $this->UserGroup->arrayToModel($group[0]);
        }

        if($this->UserGroup->superGroup == 1){
            //this is a supergroup, we should be part of a supergroup to be allowed to access this
            $isPartOfSuperGroup = upa('userGroups', 'isPartOfSuperGroup', array(getUserId()));
            if($isPartOfSuperGroup == False){
                $this->reRoute('users/noAccess');
            }
        }

        if($this->UserGroup->groupLeader <> 0 ){
            $lInfo = getUserProfile($this->UserGroup->groupLeader);
            $lName = $lInfo['title'] . ' ' . $lInfo['first_name'] . ' ' . $lInfo['last_name'];
            $lAvatar = generateAvatar($this->UserGroup->groupLeader, False, 'small', True);
            $leader = generateHTML('userGroups/leader', array('leader_avatar' => $lAvatar, 'leader_name' => $lName));
        } else{
            $leader = generateHTML('userGroups/noLeader', array());
        }

        $this->_template->set('group_leader', $leader);

        //get users in this group
        $groupUsers = upa('groupUsers', 'usersInGroup', array($groupId));
        $userTable = '';

        if(!empty($groupUsers)){

            //3 columns
            $userNumber = count($groupUsers);  //2


            $rowLength = 0;

            $thisRow = '';

            for($x = 0; $x < $userNumber; $x++){

                $thisUser = array();
                $profile = getUserProfile($groupUsers[$x]['userId']);
                //$profile['avatar_path'] = userAvatarUri($profile['id'], $profile['avatar_uri']);
                $profile['avatar'] = generateAvatar($profile['id'], $profile['avatar_uri'], 'small', True);

                $thisRow .= generateHTML('userGroups/userCell', $profile );
                $rowLength++;

                if($rowLength == 3 ){
                    $userTable .= '<tr>' . $thisRow . '</tr>';
                    $thisRow = '';
                    $rowLength = 0;
                }
            }

            //finish row
            if($rowLength < 3){
                for($x = $rowLength; $x < 3; $x++){
                    $thisRow .= '<td></td><td></td>';
                }
                $userTable .= $thisRow;
            }
        }

        $this->_template->set('group_users', $userTable);
        $this->_template->setByArray($group[0]);

        if($this->UserGroup->superGroup == 1){
            $this->_template->set('privileges_table', '{MESA_UGA_SUPERGROUPPRIV}');
        } else{
            $privTable = upa('groupPrivileges', 'generatePrivilegeForm', array($groupId), True);
            $this->_template->set('privileges_table', $privTable);
        }

    }



    function saveGroup(){
        $this->render = 0;
        $this->UserGroup->postToModel();
        $this->UserGroup->save();
        $insertId = $this->UserGroup->lastInsertId;
        $this->reRoute('userGroups/edit/' . $insertId, True);
    }

    function demoteLeader($groupId){

        $this->UserGroup->id = $groupId;
        $this->UserGroup->groupLeader = '';
        $this->UserGroup->save();

    }

    function promoteLeader($groupId, $userId){
        $this->UserGroup->id = $groupId;
        $this->UserGroup->groupLeader = $userId;
        $this->UserGroup->save();
    }

    function isSuperGroup($groupId){

        
        $this->UserGroup->where('id', $groupId);
        $result = $this->UserGroup->search();
        
        if(empty($result)){
            return NULL;
        } else{
            if($result[0]['superGroup'] == 1){        
                return True;
            } else{        
                return False;
            }
        }
    }

    function removeGroup($id){

        $this->render = False;

        upa('groupPrivileges', 'removeGroupPrivileges', array($id));
        upa('groupUsers', 'removeGroupUsers', array($id));
        $this->UserGroup->id = $id;
        $this->UserGroup->delete();
    }


    function showGroup($groupId){

        $this->doNotRenderHeader = True;

        $this->UserGroup->where('id', $groupId);
        $result = $this->UserGroup->search();

        if(!empty($result)){


        $usersInGroup = upa('groupUsers', 'usersInGroup', array($groupId));
        $groupRender = '';

        foreach($usersInGroup as $userInGroup){
            $userProfile = getUserProfile($userInGroup['userId']);
            $avatar = generateAvatar($userProfile['id'], $userProfile['avatar_uri'], 'small', False, True);
            $userProfile['avatar_uri'] = $avatar;
            $groupRender .= generateHTML('userGroups/groupListLine', $userProfile);
        }

        $this->_template->set('group_title', $result[0]['groupName']);
        $this->_template->set('group_render', $groupRender);
        $this->_template->set('id', $groupId);

        }
    }

}
