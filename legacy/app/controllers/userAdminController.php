<?PHP

class userAdminController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }


    function dashboard(){
        $newUserForm = upa('profiles', 'add', array());
        $this->_template->set('new_user_form', $newUserForm);
    }


    function fullOverview(){
      $this->doNotRenderHeader = True;
      $users = upa('users', 'getAllUsersWithProfiles', array());

      $boolAr[0] = 'Nee';
      $boolAr[1] = 'Ja';

      $tF = new tableFactory();
      $tF->loadTemplate('userListTable');
      $tF->specifyMod('enabled', 'flipIt', array( ALPC_TF_SELF, $boolAr ));
      #$tF->specifyMod('last_seen', 'date', array('d-m-Y', ALPC_TF_SELF));
      $tF->specifyMod('last_seen', 'dateWithFail', array(ALPC_TF_SELF));
      $tF->loadValues($users);
      $this->_template->set('table',  $tF->renderTable());

    }


    function editUser($id = False){
        $this->doNotRenderHeader = 1;

        $logInfo = upa('users', 'fetch', array($id));
        $userInfo = upa('profiles', 'fetch', array($id));

        $this->_template->setByArray($logInfo);
        $this->_template->setByArray($userInfo);

        if($logInfo['enabled'] == 0){
            $this->_template->set('show_lock_warning', '');
            $this->_template->set('show_lock_button', 'hidden');
            $this->_template->set('show_unlock_button', '');

        }

        if($logInfo['enabled'] == 1){
            $this->_template->set('show_lock_warning', 'hide');
            $this->_template->set('show_lock_button', '');
            $this->_template->set('show_unlock_button', 'hidden');
        }

        if($userInfo['sex'] == 'M' ){
            $this->_template->set('sex_icon', 'icon-male');
        } else{
            $this->_template->set('sex_icon', 'icon-female');
        }

        $avatar = generateAvatar($id, $userInfo['avatar_uri'], 'large', True);
        $this->_template->set('avatar', $avatar);
    }


    function loadUserGroups($id){
        $this->doNotRenderHeader = 1;
        $groups = upa('groupUsers', 'getUserAffiliation', array($id), False);

        if(empty($groups)){
            $this->_template->set('groups', generateHTML('alertWarning', array('alert_title' => 'Geen groepen', 'alert_message' => 'Deze gebruiker is niet deel van een groep')));
        }
        else{
            $affGroups = '';
            foreach($groups as $thisGroup){
                $info = upa('userGroups', 'fetch', array($thisGroup), 0);
                $groupInfo['id'] = $thisGroup;
                $groupInfo['name'] = $info['groupName'];
                $affGroups .= generateHTML('userAdmin/groupLine', $groupInfo);
            }
            $this->_template->set('groups', generateHTML('userAdmin/groupWrap', array('group_items' => $affGroups)));
        }
    }

    function editForm($id){
        $this->doNotRenderHeader = 1;
        $editUserForm = upa('profiles', 'add', array($id));
        $this->_template->set('user_form', $editUserForm);

    }

    function saveUserEdit(){
        upa('profiles', 'saveProfile', array($_POST));
    }

    function lock(){
        $this->render = 0;
        $id = $_POST['id'];
        $lock  = $_POST['lock'];
        upa('users', 'lockAccount', array($id, $lock));

    }
}
