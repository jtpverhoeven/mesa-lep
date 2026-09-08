<?PHP

class profilesController extends Controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', 'active');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    function add($userId = False){

        $this->doNotRenderHeader = 1;

        if($userId != False){
            $this->Profile->where('id', $userId);
            $results = $this->Profile->search();
            if(empty($results)){
                return;
            } else{
                $this->Profile->arrayToModel($results[0]);
            }
        }


        $uForm = new formFactory('profiles');

        if($userId != False){
            $uForm->setId('editUserForm');
        } else{
            $uForm->setId('addUserForm');
        }

        $uForm->addClass('');
        $uForm->action('');
        $uForm->method('POST');
        $uForm->setTemplate('generic');

        if($userId != False){ $classForUsername = 'disabled'; }
        else{ $classForUsername = '' ;  }

        $uForm->addInputField('username', '{MESA_UAD_USERNAME}', 'text', 'input-block-level ', $this->Profile->username, '{MESA_UAD_USERNAME}',  $classForUsername, False);

        if($userId == False){
            $uForm->addValidation('username', 'NO_DUPLICATE');
        }


        $uForm->addInputField('title', '{MESA_UAD_TITLE}', 'text', 'input-block-level ', $this->Profile->title, '{MESA_UAD_TITLE}',  False, False);
        $uForm->addInputField('first_name', '{MESA_UAD_FIRSTNAME}', 'text', 'input-block-level ', $this->Profile->first_name, '{MESA_UAD_FIRSTNAME}',  False, False);
        $uForm->addInputField('last_name', '{MESA_UAD_LASTNAME}', 'text', 'input-block-level ', $this->Profile->last_name, '{MESA_UAD_LASTNAME}',  False, False);

        $sex['M'] = '{MESA_UAD_MALE}';
        $sex['F'] = '{MESA_UAD_FEMALE}';
        $uForm->addDropdownField('sex', '{MESA_UAD_SEX}', 'input-block-level', $this->Profile->sex, $sex, False, False);
        $uForm->addInputField('function', '{MESA_UAD_FUNCTION}', 'text', 'input-block-level ', $this->Profile->function, '{MESA_UAD_FUNCTION}',  False, False);
        $uForm->addInputField('email', '{MESA_UAD_EMAIL}', 'text', 'input-block-level ', $this->Profile->email, '{MESA_UAD_EMAIL}',  False, False);
        $uForm->addInputField('phone', '{MESA_UAD_TELEPHONE}', 'text', 'input-block-level ', $this->Profile->phone, '{MESA_UAD_TELEPHONE}',  False, False);

        if($userId == False){
            $uForm->addInputField('password', '{MESA_UAD_PASSWORD}', 'text', 'input-block-level ', '', '{MESA_UAD_PASSWORD}',  False, False);
            $uForm->addInputField('repeat', '{MESA_UAD_PASSWORD_REPEAT}', 'text', 'input-block-level ', '', '{MESA_UAD_PASSWORD_REPEAT}',  False, False);
            $iface['eng'] = 'English';
            $iface['nl'] = 'Nederlands';
            $uForm->addDropdownField('lang', '{MESA_UAD_LANGUAGE}', 'input-block-level', 'eng', $iface, False, False);
            $uForm->submitTrough('saveUser', 'saveUser');
        } else{
            $uForm->addInputField('id', '', 'hidden', '', $userId, $userId);
            $uForm->submitTrough('editUser', 'saveUserEdit');
        }

        return $uForm->render();

    }

    function saveProfile($data){

        $this->render = 0;
        $this->Profile->arrayToModel($data);
        $this->Profile->save();
    }

    function saveMyProfile($data){
        $this->render = 0;
        $this->Profile->arrayToModel($_POST);
        $this->Profile->id = getUserId();
        $this->Profile->save();
    }

    function notFound(){
    }

    function view($username){

        if($username == False || $username == ''){

            $this->reRoute('profiles/view/' .   getUserName(getUserId()) , True);
            return;
        }


        $userId = usernameToId($username);

        if($userId == False){
            $this->reRoute('profiles/notFound');
            return;
        }

        $this->Profile->where('username', $username);
        $user = $this->Profile->search();
        $this->Profile->arrayToModel($user[0]);



        $avatar = generateAvatar($this->Profile->id, $this->Profile->avatar_uri, 'large', True);
        $this->_template->set('avatar', $avatar);
        $this->_template->setByArray($user[0]);

        $userOnline = pa('users', 'checkUserOnline', array($userId));

        if($userOnline == True){
            $this->_template->set('status', $this->Profile->first_name . ' is online');
            $this->_template->set('status_icon', 'icon-circle icon-green');
        } else{
            $this->_template->set('status', $this->Profile->first_name . ' is offline');
            $this->_template->set('status_icon', 'icon-circle icon-red');
        }

        //this profile is you
        if($userId == getUserId()){
           $this->_template->set('wall_message', '{MESA_PRO_WALLPLACEHOLDERYOU}') ;
        } else{
            $this->_template->set('wall_message', '{MESA_PRO_WALLPLACEHOLDEROTHER}') ;
        }

        //fetch user groups
        $affiliation = upa('groupUsers', 'getUserAffiliation', array($userId));
        $affiliationRender = '';

        if(empty($affiliation)){
              $affiliationRender = '{MESA_PRO_NOTPARTOFGROUPS}';
        } else{


            foreach($affiliation as $uGroupInd => $uGroupId){

                $uGroupInfo = upa('userGroups', 'fetch', array($uGroupId));
                if($uGroupInfo['groupLeader'] == $userId){
                    $uGroupInfo['group_leader'] = 'icon-trophy';
                } else{
                    $uGroupInfo['group_leader'] = 'hide';
                }
                $affiliationRender .= generateHTML('profiles/userGroupLine', $uGroupInfo);

            }


        }



        //fetch following
        $following = upa('bookmarks', 'getBookmarks', array($userId), False);
        if(empty($following)){
            $followRender = '{MESA_PRO_DOESNOTFOLLOWANY}';
        }

        else{

            $followRender = '';

            foreach($following as $followInd => $follow){

                if($follow['type'] == 1){
                    $follow['follow_type_icon'] = 'icon-suitcase';
                    $follow['follow_title'] = $follow['info']['project_name'];
                    $follow['link'] = ALPC_BASEPATH . '/projects/search/' . $follow['type_id'];
            }

            $followRender .= generateHTML('profiles/followingLine', $follow);
            }
        }




        $this->_template->set('following', $followRender);
        $this->_template->set('user_groups', $affiliationRender);
        $this->_template->set('userId', getUserId());
        $this->_template->set('profileId', $userId);

    }


    function fetchProfileArray($userId){

        $this->Profile->where('id', $userId);
        $user = $this->Profile->search();
        $this->Profile->arrayToModel($user[0]);

        $userOnline = pa('users', 'checkUserOnline', array($userId));
        if($userOnline == True){
            $user[0]['user_online'] = True;
        } else{
            $user[0]['user_online'] = False;
        }

        return $user[0];

    }

    function getProfileInfo($userId){

        $this->Profile->where('id', $userId);
        $result = $this->Profile->search();

        if(empty($result)){
            return False;
        }

        return $result[0];
    }


    function edit(){


        $this->doNotRenderHeader = True;

        $userId = getUserId();
        $info = $this->getProfileInfo($userId);
        $this->Profile->arrayToModel($info);



        $this->Profile->where('id', $userId);
        $results = $this->Profile->search();
        if(empty($results)){
            return;
        } else{
            $this->Profile->arrayToModel($results[0]);
        }




        $this->_template->setByArray($info);


    }

    function profileEditLoader($load){

        $this->doNotRenderHeader = True;

        if($load == 'details'){

            $userId = getUserId();
            $info = $this->getProfileInfo($userId);
            $this->Profile->arrayToModel($info);

            //it starts with update details
            $uForm = new formFactory('profiles');
            $uForm->setId('editMyProfileForm');
            $uForm->addClass('');
            $uForm->action('');
            $uForm->method('POST');
            $uForm->setTemplate('generic');

            $uForm->addInputField('title', '{MESA_UAD_TITLE}', 'text', 'input-block-level ', $this->Profile->title, '{MESA_UAD_TITLE}',  False, False);
            $uForm->addInputField('first_name', '{MESA_UAD_FIRSTNAME}', 'text', 'input-block-level ', $this->Profile->first_name, '{MESA_UAD_FIRSTNAME}',  False, False);
            $uForm->addInputField('last_name', '{MESA_UAD_LASTNAME}', 'text', 'input-block-level ', $this->Profile->last_name, '{MESA_UAD_LASTNAME}',  False, False);

            $sex['M'] = '{MESA_UAD_MALE}';
            $sex['F'] = '{MESA_UAD_FEMALE}';
            $uForm->addDropdownField('sex', '{MESA_UAD_SEX}', 'input-block-level', $this->Profile->sex, $sex, False, False);
            $uForm->addInputField('function', '{MESA_UAD_FUNCTION}', 'text', 'input-block-level ', $this->Profile->function, '{MESA_UAD_FUNCTION}',  False, False);
            $uForm->addInputField('email', '{MESA_UAD_EMAIL}', 'text', 'input-block-level ', $this->Profile->email, '{MESA_UAD_EMAIL}',  False, False);
            $uForm->addInputField('phone', '{MESA_UAD_TELEPHONE}', 'text', 'input-block-level ', $this->Profile->phone, '{MESA_UAD_TELEPHONE}',  False, False);
            $uForm->addButton('submitProfile', 'icon-save', '', 'btn btn-mini btn-primary', '{MESA_UAD_SAVE}', False);
            $uForm->submitTrough('submitProfile', 'saveProfileEdit');
            $this->_template->set('content', $uForm->render());
        }

        if($load == 'password'){

            $userId = getUserId();
            $info = $this->getProfileInfo($userId);
            $this->Profile->arrayToModel($info);

            //it starts with update details
            $uForm = new formFactory('profiles');
            $uForm->setId('changePasswordForm');
            $uForm->addClass('');
            $uForm->action('');
            $uForm->method('POST');
            $uForm->setTemplate('generic');

            $uForm->addInputField('oldPass', '{MESA_ACS_CURRPASS}', 'password', 'input-block-level ', '', '{MESA_ACS_CURRPASS}',  False, False);
            $uForm->addInputField('newPass', '{MESA_ACS_NEWPASS}', 'password', 'input-block-level ', '', '{MESA_ACS_NEWPASS}',  False, False);
            $uForm->addValidation('newPass', 'NOT_EMPTY');
            $uForm->addInputField('newPassRepeat', '{MESA_ACS_NEWPASSCHECK}', 'password', 'input-block-level ', '', '{MESA_ACS_NEWPASSCHECK}',  False, False);
            $uForm->addValidation('newPassRepeat', 'NOT_EMPTY');

            $uForm->addButton('submitPassChange', 'icon-key', '', 'btn btn-mini btn-primary', '{MESA_ACS_NEWPASSSET}', False);
            $uForm->submitTrough('submitPassChange', 'changePassword');
            $this->_template->set('content', $uForm->render());
        }

        if($load == 'avatar'){
            $userId = getUserId();
            $info = $this->getProfileInfo($userId);
            $this->Profile->arrayToModel($info);
            $avatar = generateAvatar($this->Profile->id, $this->Profile->avatar_uri, 'large', True);
            $this->_template->set('content', generateHTML('profiles/uploadAvatar', array('avatar' => $avatar)));
        }

        if($load == 'signature'){
                $sigNl =  generateSignature(getUserId(), True);
                $sigEn =  generateSignature(getUserId(), True, 'En');
                $this->_template->set('content', generateHTML('profiles/uploadSig', array('signature' => $sigNl, 'signature_en' => $sigEn )));
        }

        if($load == 'language'){

            $uForm = new formFactory('profiles');
            $uForm->setId('changeLanguageForm');
            $uForm->addClass('');
            $uForm->action('');
            $uForm->method('POST');
            $uForm->setTemplate('generic');

            $options['nl'] = 'Nederlands';
            $options['eng'] = 'English';

            $uForm->addDropdownField('accSetLang', '{MESA_PRO_LANG}', 'input-block-level', 'nl', $options, False, False);
            $uForm->addButton('submitLangChange', 'icon-key', '', 'btn btn-mini btn-primary', '{MESA_PRO_LANGSET}', False);
            $uForm->submitTrough('submitLangChange', 'changeLanguage');
            $this->_template->set('content', $uForm->render());
        }

        if($load == 'alias'){

            $userInfo = upa('users', 'fetch', array(getUserId()), False);
            $alias = $userInfo['alias'];


            $uForm = new formFactory('users');
            $uForm->setId('changeAliasForm');
            $uForm->addClass('');
            $uForm->action('');
            $uForm->method('POST');
            $uForm->setTemplate('generic');

            $uForm->addInputField('alias', '{MESA_ACS_ENTER_ALIAS}', 'text', 'input-block-level ', $alias, '{MESA_ACS_ENTER_ALIAS_DESC}',  False, False);
            $uForm->addValidation('alias', 'NO_DUPLICATE');

            $uForm->addButton('submitAliasChange', 'icon-user', '', 'btn btn-mini btn-primary', '{MESA_ACS_SAVE_ALIAS}', False);
            $uForm->submitTrough('submitAliasChange', 'changeAlias');
            $this->_template->set('content', $uForm->render());
        }

    }

    function removeSignature(){
      $this->render = False;
      $sigAddendum = 'En';
      $userId = getUserId();
      $sigDir = ROOT . DS . 'app' . DS . 'private' .DS . 'signatures' . DS . $userId . $sigAddendum .'.png';
      mesaUnlink($sigDir);
      $sigAddendum = '';
      $sigDir = ROOT . DS . 'app' . DS . 'private' .DS . 'signatures' . DS . $userId . $sigAddendum .'.png';
      mesaUnlink($sigDir);
      $this->reRoute('lims/dashboard', True);
    }

    function saveSignature(){

        $this->render = 0;

        if(!isset($_FILES['signature'])){
            $this->reRoute('profiles/edit/', True);
            return;
        }


        $userId = getUserId();
        $allowed =  array('png' ,'jpg','jpeg');
        $fileName = $_FILES['signature']['name'];
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if(!in_array(strtolower($ext),$allowed) ) {
            $this->reRoute('profiles/view', True);
            return;
        } else{

            $sigAddendum = '';

            if(isset($_POST['signatureLanguage'])){
                if($_POST['signatureLanguage'] == 'nl'){
                    $sigAddendum = '';
                }
                if($_POST['signatureLanguage'] == 'en'){
                    $sigAddendum = 'En';
                }
            }

            $uploadDir = ROOT . DS . 'tmp' . DS . 'upload' . DS;
            $uploadFile = $uploadDir . $userId . '.' . $ext;
            $sigDir = ROOT . DS . 'app' . DS . 'private' .DS . 'signatures' . DS . $userId . $sigAddendum .'.png';

            if(file_exists($sigDir)){
                mesaUnlink($sigDir);
            }

            if(strtolower($ext) == 'png'){
              move_uploaded_file($_FILES['signature']['tmp_name'], $sigDir);
            } else{
                if (move_uploaded_file($_FILES['signature']['tmp_name'], $uploadFile)){
                  imagepng(imagecreatefromstring(file_get_contents($uploadFile)), $sigDir);
                }
                mesaUnlink($uploadFile);
            }
        }
        $this->reRoute('profiles/view', True);
    }

    function saveAvatar(){

        $this->render = 0;

        if(!isset($_FILES['avatar'])){
            //$this->reRoute('profiles/view', True);
            return;
        }

        $userId = getUserId();
        $allowed =  array('gif','png' ,'jpg','jpeg');
        $fileName = $_FILES['avatar']['name'];
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if(!in_array(strtolower($ext),$allowed) ) {
            $this->reRoute('profiles/view', True);
            return;
        } else{
            $uploadDir = ROOT . DS . 'tmp' . DS . 'upload' . DS;
            $uploadFile = $uploadDir . $userId . '.' . $ext;

            if(!file_exists (ROOT . DS . 'public' . DS . 'uploads' . DS . $userId )){
                mkdir(ROOT . DS . 'public' . DS . 'uploads' . DS . $userId );

            }

            //check if user dirs are in place
            if(!file_exists (ROOT . DS . 'public' . DS . 'uploads' . DS . $userId . DS . 'photos' )){
                mkdir(ROOT . DS . 'public' . DS . 'uploads' . DS . $userId . DS . 'photos');
            }

            if(!file_exists (ROOT . DS . 'public' . DS . 'uploads' . DS . $userId . DS . 'avatar' )){
                mkdir(ROOT . DS . 'public' . DS . 'uploads' . DS . $userId . DS . 'avatar');
            }


            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)){
                $photoDir =  ROOT . DS . 'public' . DS . 'uploads' . DS . $userId . DS . 'photos' . DS . $userId . '.jpeg'; //160x160
                $avatarDir = ROOT . DS . 'public' . DS . 'uploads' . DS . $userId . DS . 'avatar' . DS . $userId . '.jpeg'; // 32 x 32

                if(file_exists($photoDir)){
                    mesaUnlink($photoDir);
                } if(file_exists($avatarDir)){
                    mesaUnlink($avatarDir);
                }

                create_thumbnail($uploadFile, $photoDir, 160, 160, True);
                create_thumbnail($uploadFile, $avatarDir, 32, 64, True);

                mesaUnlink($uploadFile);

                $this->Profile->id = $userId;
                $this->Profile->avatar_uri = $userId . '.jpeg';
                $this->Profile->save();

            } else{
                $this->Profile->id = $userId;
                $this->Profile->avatar_uri = '';
                $this->Profile->save();
            }
        }

        $this->reRoute('profiles/view', True);
    }

    function removeAvatar($userId){
        $this->render = 0;
        $this->Profile->id = $userId;
        $this->Profile->avatar_uri = '';
        $this->Profile->save();
    }

    function getUserFirstName($userId){
        $this->Profile->where('id', $userId);
        $results = $this->Profile->search();

        if(!empty($results)){
            return $results['0']['first_name'];
        } else{
            return 'Onbekend';
        }

    }

    function getUserFullName($userId){
        $this->Profile->where('id', $userId);
        $results = $this->Profile->search();

        if(!empty($results)){
            return $results['0']['first_name'] . ' ' . $results['0']['last_name'];
        } else{
            return 'Onbekend';
        }
    }

    function getUserAvatar($userId){

        $this->Profile->where('id', $userId);
        $user = $this->Profile->search();

        /* if(empty($user)){
            $url = ALPC_BASEPATH . '/public/img/no_avatar.jpg';
        } else{
            $file = $user[0]['avatar_uri'];
            $url = ALPC_BASEPATH . '/public/uploads/' . $userId . '/avatar/' . $file;
        } */

        if(empty($user)){
            return False;
        } else{
            return $user[0]['avatar_uri'];
        }

    }


    function userSearch($searchTerm){

        //$this->render = 0;
        $this->Profile->where('username', $searchTerm);
        $this->Profile->insertOr();
        $this->Profile->where('first_name', $searchTerm);
        $this->Profile->insertOr();
        $this->Profile->where('last_name', $searchTerm);
        $results = $this->Profile->search();
        return $results;
    }

    function getUserTable($mode = 'groups'){

        $this->doNotRenderHeader = True;
        $searchTerm = $_POST['searchTerm'];

        $results = $this->userSearch($searchTerm);

        if(empty($results)){
          $results = 'No hits';
        }

        $tF = new tableFactory();

        if($mode == 'groups'){
            $tF->loadTemplate('usersearchList');
        } elseif($mode == 'useredit'){
            $tF->loadTemplate('usersearchedit');
        }

        $tF->loadValues($results);

        $this->_template->set('table',  $tF->renderTable());
    }

    function saveDashCookie(){

        $this->render = false;
        $this->Profile->id = getUserId();

        if (isset($_COOKIE['mesaDashboard'])){
            $dashSet = $_COOKIE['mesaDashboard'];
        } else {
            $dashSet = '';
        }

        $this->Profile->dashboard = $dashSet;
        $this->Profile->save();
    }


}
