<?PHP

class usersController extends Controller{

    protected $_publicActions = array( 'countActiveSessions' => True, 'login' => True, 'doLogin' => True, 'noAccess' => True, 'watchDog' => True, 'checkUserHasPrinter' => True );
    
    function checkUserHasPrinter(){
        $this->render = False;

        $this->User->where('username', $_POST['username']);
        $this->User->insertOR();
        $this->User->where('alias', $_POST['username']);
        $this->User->limit(1);
        $result = $this->User->search();

        if(count($result) > 0 )
        {
            $printer = $result[0]['printer'];
            if(!is_null($printer))
            {
                $printerInfo = upa('printers', 'fetch', array($printer), False);
                print json_encode(['printer' => $printerInfo['id']]);
                return;
            }
            
            else
            {
                print json_encode(['printer' => 0]);
                return;
            }
        }

        print json_encode(['printer' => 0 ]);

    }

    function getPrinterOfCurrentUser()
    {
        $this->User->where('id', getUserId());
        $user = $this->User->search();

        if(!empty($user))
        {
            return $user[0]['printer'];
        }

        else
        {
            return NULL;
        }
    }

    function countActiveSessions(){
      $this->render = False;
      $this->User->greaterThanHard('sessionStarted', 0);
      $results = $this->User->search();

      $active = 0;
      foreach($results as $session){

        $timeOutPoint = $session['sessionStarted'] + ALPC_SESSION_LIFETIME;
        $currentTime = time();
        if ($currentTime <= $timeOutPoint) {
          $active++;
        }

      }
      print json_encode(array('count' => $active));
    }

    function invalidateAllSessionsExceptMe($userId){
      $this->render = false;
      $result = $this->User->search();
      foreach($result as $user){
        if($user['id'] !== $userId){
          $this->User->id = $user['id'];
          $this->User->sessionStarted = 0;
          $this->User->save();
          $this->User->deepFreed();
        }
      }
    }

    function invalidateAllSessions(){
      $this->render = false;
      $this->invalidateAllSessionsExceptMe(0);
    }

    function watchDog(){

        $this->render = 0;
        $thisSessionValid = True;

        if (isset($_SESSION['ALPC_USER_LOGGED']) && $_SESSION['ALPC_USER_LOGGED'] != False) {

            if (!isset($_SESSION['ALPC_USER_ID'])) {
                $checkUserId = NULL;
            } else {
                $checkUserId = $_SESSION['ALPC_USER_ID'];
            }

            $query = "SELECT * FROM `users` WHERE `id`= :userid ";
            $params['userid'] = $checkUserId;

            $modelName = $this->_model;
            $queryResult = $this->$modelName->customQuery($query, $params);

            if ($this->$modelName->lastQueryCount == 0) {
                $thisSessionValid = False;
                $dbToken = False;
                $dbIp = False;
                $dbAgent = False;
                $dbSessionStart = False;
                $dbEnabled = False;
            } else {
                $dbEnabled = $queryResult[0]['enabled'];
                $dbToken = $queryResult[0]['sessionToken'];
                $dbIp = $queryResult[0]['sessionIp'];
                $dbAgent = $queryResult[0]['sessionAgent'];
                $dbSessionStart = $queryResult[0]['sessionStarted'];
            }

            if($dbEnabled == 0){
                    $thisSessionValid = False;
            }

            //check cookie set and correct
            if (isset($_COOKIE['ALPC_SESSION_TOKEN'])) {
                if ($_COOKIE['ALPC_SESSION_TOKEN'] != $dbToken) {
                    $thisSessionValid = False;
                }
            } else {
                $thisSessionValid = False;
            }

            if (getIpAddres() != $dbIp) {
                $thisSessionValid = False;
            }

            //check agent
            $thisAgent = hash('sha256', $_SERVER["HTTP_USER_AGENT"]);

            if ($thisAgent != $dbAgent) {
                $thisSessionValid = False;
            }

            //check timeout status of sesssion
            $timeOutPoint = $dbSessionStart + ALPC_SESSION_LIFETIME;
            $currentTime = time();
            if ($currentTime >= $timeOutPoint) {
                $thisSessionValid = False;
            }

        } else{
            $thisSessionValid = False;
        }

        //if request didnt pass checks, end session
        if ($thisSessionValid === False) {
            print '0';
        } elseif($thisSessionValid === True) {
            $this->User->id = $checkUserId;
            $this->User->lastPing = time();
            $this->User->save();
            print '1';
        }

    }

    function functionAuth($controller, $action){

        $this->render = False;
        $inPass = $_POST['password'];
        $inUser = $_POST['username'];

        //get current info
        $this->User->where('id', getUserId());
        $result = $this->User->search();
        $this->User->arrayToModel($result[0]);

        $saltPattern = $this->User->pattern;
        $salt1 = $this->User->salt1;
        $salt2 = $this->User->salt2;
        $passwordHash = hash('SHA256', $inPass);

        $grep = array("/{salt1}/", "/{salt2}/", "/{pass}/", "/{user}/");
        $repl = array($salt1, $salt2, $passwordHash, $this->User->username);
        $pwd = preg_replace($grep, $repl, $saltPattern);

        $checkPwd = hash('SHA256', $pwd);

        if ($checkPwd == $this->User->password && $inUser == ($this->User->username || $this->User->alias)) {
            $infoCorrect = True;
        } else{
            $infoCorrect = False;
        }

        $allowed = pa('groupPrivileges', 'checkAllowed', array($controller, $action), False);

        if($infoCorrect == True && $allowed == True){
            print '1';
        }

        elseif($infoCorrect == True && $allowed == False){
            print '2';
        }

        else{
            print '3';
        }
    }

    function functionAuthDialog(){

        $this->doNotRenderHeader = True;
        $loginForm = new formFactory($this->_controller);

        $loginForm->method('POST');
        $loginForm->action('#');
        $loginForm->setId('passwordAuthForm');
        $loginForm->addClass('');
        $loginForm->setTemplate('generic');

        $loginForm->addInputField('ALPC_AUTH_POP_USER', False , 'text', 'input-block-level', '', '{ALPC_ENTER_USERNAME}', False);
        $loginForm->addInputField('ALPC_AUTH_POP_PASSWORD', False , 'password', 'input-block-level', '', '{ALPC_ENTER_PASSWORD}', False);

        $this->_template->set('render', $loginForm->render());
    }


    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function getUserName($userId){
        $this->User->where('id', $userId);
        $result = $this->User->search();

        if(!empty($result)){
            return $result[0]['username'];
        } else {
            return 'unknown';
        }
    }

    function lockAccount($id, $status){

        $this->render = 0;
        $this->User->id = $id;

        writeLog('Changed account lock setting of ' . $id . ' to ' . $status, ALPC_SECURITY);

        /* False = Enabled , True = Locked */
        if($status == False || $status == 'false'){
            $enabled = 1;
            $this->User->last_seen = time();
        } elseif($status == True || $status == 'true'){
            $this->User->sessionStarted = '0';
            $this->User->sessionToken = '';
            $this->User->sessionIp = '';
            $this->User->sessionAgent = '';
            $enabled = 0;
        }

        $this->User->enabled =  $enabled;
        $this->User->save();
    }

    function getUserId($username){
        $this->render = 0;
        $this->User->where('username', $username);
        $result = $this->User->search();

        if(empty($result)){
            return False;
        } else{
            return $result[0]['id'];
        }

    }

    function login($loginReason = False){

        global $lang;
        $this->render = 1;


        if (isset($_SESSION['ALPC_USER_LOGGED']) && $_SESSION['ALPC_USER_LOGGED'] != False) {
            $this->reRoute('lims/dashboard', True);
        }

        //send the login screen reason
        if ($loginReason != False){
            $this->_template->set('ALPC_LOGIN_SCREEN_REASON', $lang[$loginReason]);
            $this->_template->set('SHOW_LOGIN_REASON', '');
        } else {
            $this->_template->set('SHOW_LOGIN_REASON', 'hide');
            $this->_template->set('ALPC_LOGIN_SCREEN_REASON', 'Login');
        }

        $printers = upa('printers', 'listPrintersInArray', array(), False);
        $printers[0] = 'Standaard instelling';

        $loginForm = new formFactory($this->_controller);

        $loginForm->method('POST');
        $loginForm->action('{LB}/landings/loginLanding');
        $loginForm->setId('loginForm');
        $loginForm->addClass('');
        $loginForm->setTemplate('generic');
        $loginForm->returnAsFieldArray();

        $loginForm->addInputField('ALPC_LOGIN_USERNAME', False , 'text', 'input-block-level', '', '{ALPC_ENTER_USERNAME}', False);
        $loginForm->addValidation('ALPC_LOGIN_USERNAME', 'NOT_EMPTY');
        $loginForm->addInputField('ALPC_LOGIN_PASSWORD', False , 'password', 'input-block-level', '', '{ALPC_ENTER_PASSWORD}', False);

        $loginForm->addDropdownField('ALPC_DEFAULT_PRINTER', 'Standaard Printer', 'input-block-level', '0', $printers, False, False );

    

        $loginForm->addInputField('ALPC_LOGIN_DEVELOPMENT', False , 'hidden', 'hide', '0', False, False);
        $loginForm->addInputField('ALPC_LOGIN_DEVELOPMENT_UPDATE', False , 'hidden', 'hide', '0', False, False);

        $loginForm->addButton('loginButton', 'icon-lock', False, 'btn btn-primary', '{ALPC_SIGN_IN}', False );

        $loginForm->submitTrough('loginButton');

        $this->_template->setByArray($loginForm->render());
        //$this->_template->set('loginForm', $loginForm->render() );
    }

    function checkUserOnline($userId){

        $time = time();

        $this->User->where('id', $userId);
        $result = $this->User->search();

        if(empty($result)){
            return False;
        }

        if($result[0]['sessionStarted']){
            $sessionSpan = $time - $result[0]['sessionStarted'];
            if($sessionSpan > ALPC_SESSION_LIFETIME){
                return False;
            } else {
                return True;
            }
        } else{
            return False;
        }
    }



    function checkOnlineUsers(){

        $cutOff = time() - ALPC_SESSION_LIFETIME;
        $this->User->greaterThan('sessionStarted', $cutOff);
        $result = $this->User->search();

        if(empty($result)){
            return False;
        }

        else{
           foreach($result as $arrInd => $onlineUser){
               $onlineArr[$arrInd] = pa('profiles', 'fetchProfileArray', array($onlineUser['id']));
           }
        }

        return $onlineArr;
    }


    function noAccess(){

    }

    function saveAlias(){
        //TODO: check if this does not overlap with any actual usernames, or other aliases
        $this->render = False;
        $this->User->id = getUserId();
        $this->User->alias = trim($_POST['alias']);
        $this->User->save();
    }

    function add(){


        $this->render = 0;
        //TODO: some extra checks here
        $this->User->username = $_POST['username'];

        $passwordHash = hash('SHA256', $_POST['password']);
        $saltString1 = "^F84S{qQ~+2>ha&oiz?zExO}xi_3!Q27z`|5>/S}.@`}l,MRGM|1>koJ(F..";
        $saltString2 = "7W9z9sHwH9BqVAxR6kRvXZpmA3GYDY9YsVUvz5BfgL4qeNWaYWUV4jlbsg8m3Dn";
        $salt1 = substr(str_shuffle($saltString1), 0, 9);
        $salt2 = substr(str_shuffle($saltString2), 0, 9);

        $part[1] = "{salt1}";
        $part[2] = "{salt2}";
        $part[3] = "{pass}";
        $part[4] = "{user}";

        $psort = array_rand($part, 4);
        shuffle($psort);

        $pattern = $part[$psort[0]] . "." . $part[$psort[1]] . "." . $part[$psort[2]] . "." . $part[$psort[3]] ;

        $grep = array("/{salt1}/", "/{salt2}/", "/{pass}/", "/{user}/");
        $repl = array($salt1, $salt2, $passwordHash, $_POST['username']);
        $sendpass = preg_replace($grep, $repl, $pattern);

        $pass = hash('SHA256', $sendpass);

        $this->User->salt1 = $salt1;
        $this->User->salt2 = $salt2;
        $this->User->pattern = $pattern;
        $this->User->password = $pass;
        $this->User->enabled = 1;
        $this->User->lang = 'nl';
        $this->User->last_seen = time();
        $this->User->save();

        upa('profiles', 'saveProfile', array($_POST));

    }

    function changePassword(){

        $this->render = 0;
        writeLog('User ' . getUserId() . ' changed password for user ' . $userId, ALPC_SECURITY);

        $newPass = $_POST['newPassword'];
        $userId = $_POST['userId'];

        //get current info
        $this->User->where('id', $userId);
        $result = $this->User->search();
        $this->User->arrayToModel($result[0]);

        $saltPattern = $this->User->pattern;
        $salt1 = $this->User->salt1;
        $salt2 = $this->User->salt2;

        //$passwordHash = hash('SHA256', $oldPass);
        //$grep = array("/{salt1}/", "/{salt2}/", "/{pass}/", "/{user}/");
        //$repl = array($salt1, $salt2, $passwordHash, $this->User->username);
        //$pwd = preg_replace($grep, $repl, $saltPattern);
        //$checkPwd = hash('SHA256', $pwd);


        //set new password
        $newPasswordHash = hash('SHA256', $newPass);
        $saltString1 = "^F84S{qQ~+2>ha&oiz?zExO}xi_3!Q27z`|5>/S}.@`}l,MRGM|1>koJ(F..";
        $saltString2 = "7W9z9sHwH9BqVAxR6kRvXZpmA3GYDY9YsVUvz5BfgL4qeNWaYWUV4jlbsg8m3Dn";
        $newSalt1 = substr(str_shuffle($saltString1), 0, 9);
        $newSalt2 = substr(str_shuffle($saltString2), 0, 9);

        $part[1] = "{salt1}";
        $part[2] = "{salt2}";
        $part[3] = "{pass}";
        $part[4] = "{user}";

        $psort = array_rand($part, 4);
        shuffle($psort);

        $pattern = $part[$psort[0]] . "." . $part[$psort[1]] . "." . $part[$psort[2]] . "." . $part[$psort[3]] ;

        $newGrep = array("/{salt1}/", "/{salt2}/", "/{pass}/", "/{user}/");
        $newRepl = array($newSalt1, $newSalt2, $newPasswordHash, $this->User->username);
        $sendpass = preg_replace($newGrep, $newRepl, $pattern);
        $newEncryptedPass = hash('SHA256', $sendpass);

        $this->User->salt1 = $newSalt1;
        $this->User->salt2 = $newSalt2;
        $this->User->pattern = $pattern;
        $this->User->password = $newEncryptedPass;
        $this->User->save();
        print 'true';


    }

    function changeMyPassword(){

        $this->render = 0;

        $oldPass = $_POST['oldPass'];
        $newPass = $_POST['newPass'];

        writeLog('User ' . getUserId() . ' changed own password', ALPC_SECURITY);

        //get current info
        $this->User->where('id', getUserId());
        $result = $this->User->search();
        $this->User->arrayToModel($result[0]);

        $saltPattern = $this->User->pattern;
        $salt1 = $this->User->salt1;
        $salt2 = $this->User->salt2;

        $passwordHash = hash('SHA256', $oldPass);

        $grep = array("/{salt1}/", "/{salt2}/", "/{pass}/", "/{user}/");
        $repl = array($salt1, $salt2, $passwordHash, $this->User->username);
        $pwd = preg_replace($grep, $repl, $saltPattern);

        $checkPwd = hash('SHA256', $pwd);

        if ($checkPwd == $this->User->password) {

            //set new password
            $newPasswordHash = hash('SHA256', $newPass);
            $saltString1 = "^F84S{qQ~+2>ha&oiz?zExO}xi_3!Q27z`|5>/S}.@`}l,MRGM|1>koJ(F..";
            $saltString2 = "7W9z9sHwH9BqVAxR6kRvXZpmA3GYDY9YsVUvz5BfgL4qeNWaYWUV4jlbsg8m3Dn";
            $newSalt1 = substr(str_shuffle($saltString1), 0, 9);
            $newSalt2 = substr(str_shuffle($saltString2), 0, 9);

            $part[1] = "{salt1}";
            $part[2] = "{salt2}";
            $part[3] = "{pass}";
            $part[4] = "{user}";

            $psort = array_rand($part, 4);
            shuffle($psort);

            $pattern = $part[$psort[0]] . "." . $part[$psort[1]] . "." . $part[$psort[2]] . "." . $part[$psort[3]] ;

            $newGrep = array("/{salt1}/", "/{salt2}/", "/{pass}/", "/{user}/");
            $newRepl = array($newSalt1, $newSalt2, $newPasswordHash, $this->User->username);
            $sendpass = preg_replace($newGrep, $newRepl, $pattern);
            $newEncryptedPass = hash('SHA256', $sendpass);

            $this->User->salt1 = $newSalt1;
            $this->User->salt2 = $newSalt2;
            $this->User->pattern = $pattern;
            $this->User->password = $newEncryptedPass;
            $this->User->save();
            print 'true';
        } else{
            print 'false';
        }

    }

    function setLang(){
        $this->render = False;
        $this->User->id = getUserId();
        $this->User->lang = $_POST['lang'];
        $this->User->save();
    }

    function getAllUserNames($fullNames = False){
        $this->User->where('enabled', '1');
        $result = $this->User->search();
        $usersList = array();
        foreach($result as $user){
            $usersList[$user['id']] = $user['username'];

            if($fullNames == True){
                $profile = getUserProfile($user['id']);
                $fullName = $profile['first_name'] . ' ' . $profile['last_name'];
                $usersList[$user['id']] = $fullName;
            }

        }
        return $usersList;
    }

    function getAllUsersWithProfiles(){
      $result = $this->User->search();
      $usersList = array();

      foreach($result as $user){
          $usersList[$user['id']] = $user;
          $profile = getUserProfile($user['id']);
          $fullName = $profile['first_name'] . ' ' . $profile['last_name'];
          $usersList[$user['id']]['full_name'] = $fullName;
      }

      return $usersList;
    }

}
