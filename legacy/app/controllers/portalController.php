<?PHP

class portalController extends controller{

  private $accepts = '';
  private $authorization = '';
  private $apiRoute = '';
  private $log;

  private $linkActive = True;

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
        $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'apiLog.txt';
        $this->log = fopen($logLocation,"a+");

        //are we in EXP mode? 
        if($_SESSION['DEVELOPMENT'] == True)
        {          
          $this->linkActive = False;           
        }

    }

    public function log($logStr){
      $date = date('d-m-Y H:i:s');
      fwrite($this->log, $date . "\t" . $logStr . "\t" . PHP_EOL);
      return;
    }

    public function connection(){
      $portalSettings = $this->Portal->search();
      $settingsArr = array();

      foreach($portalSettings as $setting){
        $settingsArr[$setting['pk']] = $setting['pv'];
      }

      $this->_template->set('portalUrl', checkKeyOrFalse($settingsArr, 'portalUrl'));
      $this->_template->set('acceptType', checkKeyOrFalse($settingsArr, 'acceptType'));
      $this->_template->set('bearer', checkKeyOrFalse($settingsArr, 'bearer'));
    }

    public function email(){
      $portalSettings = $this->Portal->search();
      $settingsArr = array();

      foreach($portalSettings as $setting){
        $settingsArr[$setting['pk']] = $setting['pv'];
      }

      $this->_template->set('portalBCC', checkKeyOrFalse($settingsArr, 'portalBCC'));
      $this->_template->set('portalMessage', checkKeyOrFalse($settingsArr, 'portalMessage'));
    }

    public function saveEmailSettings(){

      $this->Portal->where('pk', 'portalBCC');
      $result = $this->Portal->search();
      $id = (empty($result)) ? False : $result[0]['id'];              
      $this->Portal->id = $id;
      $this->Portal->pk = 'portalBCC';
      $this->Portal->pv = checkKeyOrFalse($_POST, 'portalBCC');
      $this->Portal->save();

      $this->Portal->deepFreed();

      $this->Portal->where('pk', 'portalMessage');
      $result = $this->Portal->search();
      $id = (empty($result)) ? False : $result[0]['id'];              
      $this->Portal->id = $id;
      $this->Portal->pk = 'portalMessage';
      $this->Portal->pv = checkKeyOrFalse($_POST, 'portalMessage');
      $this->Portal->save();
      $this->Portal->deepFreed();

      $this->reRoute('portal/email', True);
    }

    public function getEmailSettings(){

      $portalSettings = $this->Portal->search();
      $settingsArr = array();

      foreach($portalSettings as $setting){
        $settingsArr[$setting['pk']] = $setting['pv'];
      }


      return ['bcc' => checkKeyOrFalse($settingsArr, 'portalBCC'), 
              'message' => checkKeyOrFalse($settingsArr, 'portalMessage')];

    }

    public function saveConnectionSettings(){

      $this->Portal->where('pk', 'portalUrl');
      $result = $this->Portal->search();
      $id = (empty($result)) ? False : $result[0]['id'];              
      $this->Portal->id = $id;
      $this->Portal->pk = 'portalUrl';
      $this->Portal->pv = checkKeyOrFalse($_POST, 'portalUrl');
      $this->Portal->save();

      $this->Portal->deepFreed();

      $this->Portal->where('pk', 'acceptType');
      $result = $this->Portal->search();
      $id = (empty($result)) ? False : $result[0]['id'];              
      $this->Portal->id = $id;
      $this->Portal->pk = 'acceptType';
      $this->Portal->pv = checkKeyOrFalse($_POST, 'acceptType');
      $this->Portal->save();
      $this->Portal->deepFreed();

      $this->Portal->where('pk', 'bearer');
      $result = $this->Portal->search();
      $id = (empty($result)) ? False : $result[0]['id'];              
      $this->Portal->id = $id;
      $this->Portal->pk = 'bearer';
      $this->Portal->pv = checkKeyOrFalse($_POST, 'bearer');
      $this->Portal->save();
      $this->reRoute('portal/connection', True);
    }

    public function loadSettings(){
      $portalSettings = $this->Portal->search();
      $settingsArr = array();

      foreach($portalSettings as $setting){
        $settingsArr[$setting['pk']] = $setting['pv'];
      }

      $this->apiRoute = checkKeyOrFalse($settingsArr, 'portalUrl');
      $this->authorization = checkKeyOrFalse($settingsArr, 'bearer');
      $this->accepts = checkKeyOrFalse($settingsArr, 'acceptType');

    }

    public function connectionTest($code = False){

      


      $this->render = False;
      $this->loadSettings();
      $route = $this->apiRoute . 'connectionTest';

      if($this->linkActive === False){
        print(json_encode(array('message' => 'Link is not active')));
        return; 
      }

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization
      ));

      $return = curl_exec ($ch);

      $info = curl_getinfo($ch);

      curl_close ($ch);

      if($code === True)
      { 
                
        return $info['http_code'];
      }

      else
      {
        print $return;
      }
      
    }

    public function dropTht($portalSampleId){

      if($this->linkActive === False){
        return;
      }

      
      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'drop-tht-from-sample';

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
          'sample_id' => $portalSampleId, 
       ));

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch);


    }

    public function changeToTHT($portalSampleId, $date, $storage){

      if($this->linkActive === False){
        return;
      }

      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'changeSampleToTHT';
    
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
          'sample_id' => $portalSampleId, 
          'tht_start_date' => $date,  
          'tht_storage' => $storage));

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch);

    }

    public function acceptSamples($samples){

      if($this->linkActive === False){
        return;
      }

      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'acceptSamples';
      $ch = curl_init();
      $data = json_encode($samples);


      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array('samples' => $data));

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch);
    }

    public function projectAuthorized($projectId, $quiet){

      $this->log('PA');

      if($this->linkActive === False){
        $this->log('Link aborted');
        return;
      }
    

      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'projectAuthorized/' . $projectId;
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array('quiet' => $quiet));

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      
      $this->log($return);

      $this->log($return);
      curl_close ($ch);
    }

    public function projectDeauthorized($projectId, $previewFlag ){

      $this->log('DA');

      if($this->linkActive === False){
        $this->log('Link aborted');
        return;
      }

      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'projectDeauthorized';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array('project_id' => $projectId, 'preview' => $previewFlag ? "True" : "False"));

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      
      $this->log($return);
      
      curl_close ($ch);
    }

    public function sampleStart($portalSampleId, $innocDate, $estimatedEnd){

      if($this->linkActive === False){
        return;
      }

      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'sampleInnoc';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array('sample_id' => $portalSampleId, 'innoc_time' => $innocDate,  'estimated_end' => $estimatedEnd));

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch);
    }

    public function destroySample($portalSampleIds){

      if($this->linkActive === False){
        return;
      }

      $portalSampleIds =  is_array($portalSampleIds) ? $portalSampleIds : array($portalSampleIds);
      $this->loadSettings();

      $route = $this->apiRoute . 'destroySample';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, ['sample_ids' => json_encode($portalSampleIds)]);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch);

    }

    public function setTHTflag($portalSampleIds){

      if($this->linkActive === False){
        return;
      }

      $portalSampleIds =  is_array($portalSampleIds) ? $portalSampleIds : array($portalSampleIds);
      $this->loadSettings();

      $route = $this->apiRoute . 'accept-tht';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, ['sample_ids' => json_encode($portalSampleIds)]);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));

      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch);

    }

    public function documentMailer($receivers, $extraMail, $cc, $bcc, $message, $client, $sample, $project, $hashes)
    {
      
      if($this->linkActive === False){
        return;
      }      

           
      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'mail-document';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));
      
      curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
            'emails' => json_encode($receivers), 
            'extraMail' => $extraMail,
            'cc' => $cc, 
            'bcc' => $bcc, 
            'message' => $message,             
            'client' => $client,             
            'sample' => $sample,
            'project' => $project,
            'sampleFileHashes' => json_encode($hashes)
      )); 


      $return = curl_exec ($ch);
      
      $this->log($return);
      
      curl_close ($ch); 

      return $return;

    }
    
    public function reportMailer($receivers, $extraMail, $cc, $bcc, $message, $data, $md5, $client, $hash,  $project, $projectName, $projectDate, $revision, $printVersion, $trueReference, $temporary, $trueFileName, $includeSampleFiles)
    {      
      
      if($this->linkActive === False){
        return;
      }      
           
      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'mail-report';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));
      
      curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
            'emails' => json_encode($receivers), 
            'extraMail' => $extraMail,
            'cc' => $cc, 
            'bcc' => $bcc, 
            'message' => $message, 
            'data' => $data, 
            'md5' => $md5,  
            'client' => $client,             
            'hash' => $hash,
            'project' => $project,
            'project_name' => $projectName,
            'project_date' => $projectDate,
            'revision' => $revision, 
            'print_version' => $printVersion,
            'true_reference' => $trueReference,
            'temporary' => $temporary,
            'true_file_name' => $trueFileName,
            'includeSampleFiles' => json_encode($includeSampleFiles)
      ));


      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch); 

      return $return;

    }

    public function reportMailerNotificationDispatch($hashes, $client){
      
      if($this->linkActive === False){
        return;
      }
           
      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'notify-reports';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: ' . $this->accepts,
        'Authorization: ' . $this->authorization,
      ));
      
      curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
            'hashes' => json_encode($hashes),
            'client' => $client
      ));


      $return = curl_exec ($ch);
      $this->log($return);
      curl_close ($ch); 

      return $return;

    }


    public function updateclient($clientId){

   
      if($this->linkActive === False){
        return;
      }   
      
      $this->render = False;
      $this->loadSettings();

      $route = $this->apiRoute . 'client-updated';
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $route );
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, array( 'mesa_client_id' => $clientId));
    

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $return = curl_exec ($ch);
    $this->log($return);
    curl_close ($ch); 
  }

  public function getContactLists($id){


    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/' . $id;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }

  public function destroyContactList($id){

    if($this->linkActive === False){
      return;
    }


    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/destroy/' . $id;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }  

  public function createContactLists($clientId, $groupName){

    if($this->linkActive === False){
      return;
    }


    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists' ;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
        'cliend_id' => $clientId,
        'group_name' => $groupName
      ));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }  

  public function updateContactLists($clientId, $groupName, $listId){

    if($this->linkActive === False){
      return;
    }


    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/update' ;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array( 
        'list_id' => $listId,
        'cliend_id' => $clientId,
        'group_name' => $groupName
      ));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }  

  public function showList($contactList){
    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/show/' . $contactList;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }

  public function checkClientIsPortalUser($clientId){


    $this->render = False;

    if($this->linkActive === False){
      print(json_encode(array('users' => [] )));
    }

    $this->loadSettings();
    $route = $this->apiRoute . 'client-user-of-portal' ;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(         
        'client' => $clientId,        
      ));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    print $return;
  }

  public function dropContact($contactId){

    if($this->linkActive === False){
      return;
    }


    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/delete-member/' . $contactId;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }

  public function addContact($name, $email, $listId){

    if($this->linkActive === False){
      return;
    }
    
    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/add-member' ;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(         
        'name' => $name,
        'email' => $email,
        'list_id' => $listId
      ));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }  

  public function flushProject($projectId){

    if($this->linkActive === False){
      return;
    }

    $this->render = False;
    $this->loadSettings();

    $route = $this->apiRoute . 'flush-project/' . $projectId;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $return = curl_exec ($ch);
    $this->log($return);
    curl_close ($ch);    

    print $return;

  }

  public function forceSync($projectId){

    if($this->linkActive === False){
      return;
    }

    $this->render = False;
    $this->loadSettings();

    $route = $this->apiRoute . 'force-project/' . $projectId;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $return = curl_exec ($ch);
    $this->log($return);
    curl_close ($ch);
    print $return;
  
  }

  public function getAllContactLists()  
  {
    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/all';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }
  

  public function getAllContactListsByLine()  
  {
    $this->render = False;
    $this->loadSettings();
    $route = $this->apiRoute . 'contact-lists/all-by-line';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization
    ));

    $return = curl_exec ($ch);
    curl_close ($ch);
    return $return;
  }  

  public function getOrderForm($portalProjectId){

    if($this->linkActive === False){
      return;
    }
    
    $this->loadSettings();

    $route = $this->apiRoute . 'project-order-form';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['portal_project_id' => $portalProjectId]);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $returnData = curl_exec ($ch);
    
    return $returnData;

  }

  public function updateProductGroup($sampleId, $productGroupId){

    if($this->linkActive === False){
      return;
    }
    
    $this->loadSettings();

    $route = $this->apiRoute . 'update-sample-product-group';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['portal_sample_id' => $sampleId, 'product_group_id' => $productGroupId]);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $returnData = curl_exec ($ch);
    
    return $returnData;

  }

  public function swapProjectClient($portalProjectId, $mesaProjects)
  {

   
    if($this->linkActive === False){
      return;
    }
    
    $this->loadSettings();

    $route = $this->apiRoute . 'swap-project-client';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['portal_project_id' => $portalProjectId, 'mesa_project_ids' => implode(',' , $mesaProjects)]);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $returnData = curl_exec ($ch);
    
    return $returnData;    

  }

  public function dropSampleFile($sampleId, $mesaHash)
  {

    if($this->linkActive === False){
      return;
    }
    
    $this->loadSettings();

    $route = $this->apiRoute . 'drop-sample-file';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['mesaHash' => $mesaHash, 'sampleId' => $sampleId]);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $returnData = curl_exec ($ch);
    
    return $returnData;    

  }

  public function storeSampleFileInPortal($fileData, $mesaHash, $dataHash, $clientId, $sampleId, $originalFileName)
  {

    if($this->linkActive === False){
      return;
    }
    
    $this->loadSettings();

    $route = $this->apiRoute . 'accept-sample-file';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $data = array(
      'fileData' => $fileData,
      'mesaHash' => $mesaHash,
      'dataHash' => $dataHash,
      'clientId' => $clientId,
      'sampleId' => $sampleId,
      'originalFileName' => $originalFileName
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  
  
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $returnData = curl_exec ($ch);
    
    return $returnData;
  
  }


  public function getReportingPreferenceForProject($portalProjectId)
  {

    if($this->linkActive === False){
      return;
    }
    
    $this->loadSettings();

    $route = $this->apiRoute . 'report-preferences';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $route );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $data = array(
      'id' => $portalProjectId
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  
  
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept: ' . $this->accepts,
      'Authorization: ' . $this->authorization,
    ));

    $returnData = curl_exec ($ch);
    
    return $returnData;
  
  }

  
 

}