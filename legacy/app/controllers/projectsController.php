<?PHP

//special types
//0 Normal
//1 Legionella
//2 Rodac

class projectsController extends Controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    public function lockProject(){

        $this->render = 0; 
        
        $this->Project->where('id', $_POST['project_id']);
        
        $result = $this->Project->search();

        $project = $result[0];

        $this->Project->arrayToModel($project);
      
        $this->Project->locked = 1;
        $this->Project->locked_by = getUserId();
        $this->Project->lock_message = $_POST['lock_reason'];

        $event = 'Project autorisatie geblokkeerd, reden:' . $_POST['lock_reason'];

        upa('changeTracker', 'changed', array(22, $this->Project->id, False, False, $event, False, False), False);
        
        $this->Project->save(); 

    }

    public function unlockProject(){
        
        $this->render = 0;         

        $this->Project->where('id', $_POST['project_id']);
        $result = $this->Project->search();
        $project = $result[0];
        $this->Project->arrayToModel($project);
      
        
        $this->Project->locked = false;
        $this->Project->locked_by = '';
        $this->Project->lock_message = ''; 
        $this->Project->save(); 

        $event = 'Project autorisatie vrijgegeven, reden:' . $_POST['unlock_reason'];

        upa('changeTracker', 'changed', array(23, $this->Project->id, False, False, $event, False, False), False);
        

        print(json_encode(true));
        

    }

    function predict(){
        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;
        $q = $_POST['term'];
        $client = $_POST['client'];

        $this->Project->where('auth_status', 1 );
        $this->Project->where('client', $client);
        $this->Project->like('project_name', $q);

        $results = $this->Project->search();

        $clients = array();
        $iClient = 0;

        if(!empty($results)){
            foreach($results as $thisClient){
                $clients[$iClient]['id'] = $thisClient['id'];
                $clients[$iClient]['text'] = $thisClient['project_name'];
                $iClient++;
            }
        }

        $ret['more'] = false;
        $ret['results'] = $clients;
        print json_encode($ret);
    }

     function predictInit($id){
        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;
        $found = array();
        $this->Project->where('id', $id);
        $result = $this->Project->search();

        if(!empty($result)){
            $found['id'] = $result[0]['id'];
            $found['text'] =  $result[0]['reference'];
        }

        $ret = array();
        $ret = $found;
        echo json_encode($ret);
   }

    public function updateProjReference($projectId){

        $this->render = 0;
        //set project reference based on MESA_PROJECT_REFERENCE
        if(MESA_PROJECT_REFERENCE == 1){
            $this->Project->id = $projectId;
            $this->Project->reference = $projectId;
            $this->Project->save();
        }

        if(MESA_PROJECT_REFERENCE == 2){
            $this->Project->id = $projectId;
            $sampleId = upa('samples', 'fetchFirstSampleInProject', array($projectId, False));
            $this->Project->reference = $sampleId;
            $this->Project->save();
        }

        if(MESA_PROJECT_REFERENCE == 3){
            $this->Project->id = $projectId;
            $sampleId = upa('samples', 'fetchFirstSampleInProject', array($projectId, True));
            $this->Project->reference = $sampleId;
            $this->Project->save();
        }

        return $this->Project->reference;
    }

    public function authStatus($projectId, $bool = True){
        $this->render = 0;
        $this->Project->where('id', $projectId);
        $results = $this->Project->search();

        if($bool == True){
            if($results['0']['auth_status'] == '1'){
                return True;
            } else {
                return False;
            }
        }

        else{
            return $results['0']['auth_status'];
        }
    }

    public function referenceSearch($reference){
       $this->clientProjectsList(False, False,  False,  False,  False, $reference);
    }    

    public function billingSearch($reference)
    {
    
        $this->clientProjectsList(False, False,  False,  False,  False, $reference, True);
    
    }

    private function getBillingSampleTypes($project)
    {   

        $counts = upa('billing', 'getSampleTypes', array([$project]), False);
        
        $tF = new tableFactory();
        $tF->setTableId('projectBillingTypesTable');
        $tF->loadTemplate('projectBillingTypesTable');
        $tF->loadValues($counts);        
        
        
        return  $tF->renderTable();            
    
    }

    private function getBillingInformation($project)
    {
        
        $sql = "SELECT sampleanalysis.assay_base,  assays.article_code, assays.name, COUNT(*) as total, 
            SUM(CASE WHEN sampleanalysis.conf_requested = '1' THEN 1 ELSE 0 END) as conf_requested_count 
            FROM sampleanalysis 
            JOIN assays ON sampleanalysis.assay_base = assays.id 
            WHERE sampleanalysis.project = " . $project . " 
            GROUP BY sampleanalysis.assay_base, assays.name";

        $sa = new SampleAnalysis();
        $results = $sa->customQuery($sql, []);

        foreach ($results as &$result) {
            if (!isset($result['article_code']) || empty($result['article_code'])) {
                $result['article_code'] = '-';
            }
        }

        unset($result); // Unset reference after loop

        if (empty($results))
        {
            return generateHTML('alertWarning', array('alert_title' => 'Geen resultaten', 'alert_message' => 'Geen resultaten gevonden voor dit project'));
        }

        $tF = new tableFactory();
        $tF->setTableId('projectBillingTable');
        $tF->loadTemplate('projectBillingTable');
        $tF->loadValues($results);        
        
        
        return  $tF->renderTable();            
    }

    public function clientProjectsList($clientId, $subClient, $timeSpan = False, $timeSpanEnd= False, $sample= False, $reference = False, $billing = False, $return = False)
    {

        $useClientNames = False;
        $this->render = 0;

        //for seaching per client with a timeframe limit
        if($timeSpan != False && $timeSpanEnd == False){

            $jsonObj = array();
            $jsonObj['client_name'] = pa('clients', 'clientIdToName', array($clientId));
            if($timeSpan == 'mostRecent'){
            $begin = strtotime('+1 day', time());
            $end =  strtotime('-32 days', time());
            $projects = $this->searchProjects($clientId, $subClient, $end, $begin );
            } else {
                $projects = $this->searchProjects($clientId, $subClient);
            }
        }

        //for searching projects in a time span
        if($timeSpan != False && $timeSpanEnd != False){
            $useClientNames = True;
            $begin = strtotime($timeSpan);
            $end =  strtotime($timeSpanEnd);
            $projects = $this->searchProjects(False, False, $begin, $end );
        }

        //for searching a certain sample in a project
        if($sample != False){
            $useClientNames = True;
            $barExp = explode('.', $sample);
            $projects = $this->searchProjects(False, False, False, False, $barExp[0] );
        }

        //seasrch by reference
        if($reference != False){
             $useClientNames = True;
             $projects = $this->searchProjects(False, False, False, False, False,  False, $reference);
        }

        if(empty($projects)){
            $jsonObj['panel'] =  generateHTML('alertWarning', array('alert_title' => 'No projects', 'alert_message' => 'No projects for this time period'));
        }

        else{

            $listItemHTML = '';

            foreach($projects as $project){

                if($sample != False){
                    $jsonObj['found'] = true;
                    $jsonObj['found_id'] = $project['id'];
                    $jsonObj['found_client'] = $project['client'];
                    $jsonObj['sample_id'] = upa('samples', 'barcodeToId', array($barExp[0]), False);
                }
                
                $project['real_date'] = date('d-m-Y',$project['project_date']);

                $project['bemonster'] = peekIntoJSON($project['custom_fields'], 'project_monster');                
                $project['ontvangst'] = peekIntoJSON($project['custom_fields'], 'project_ontvangst');                              
                $project['innoc'] = upa('samples', 'getProjectInnoculationDate', array($project['id']));                     
                $project['no_samples'] = $this->countSamplesInProject($project['id']);
                
                $project['billing'] = 'hidden';

                if($billing == True)
                {
                    $project['billing'] = 'shown';

                    $project['billing_data'] = $this->getBillingInformation($project['id']);

                    $project['billing_types'] = $this->getBillingSampleTypes($project['id']);   

                }

            
                $statusLabel = $this->statusLabel($project);
                $project['status'] = $statusLabel['status'];
                $project['flair'] = $statusLabel['flair'];

                if($useClientNames == True){
                    $clientName = pa('clients', 'clientIdToName', array($project['client']));
                    $project['client_name_short'] = '<i class="icon-building"></i> ' . substr($clientName, 0, 9) . '...';
                } else {
                    $project['client_name_short'] = '';
                }

                if($project['auth_status'] == '0'){
                    $project['auth_show'] = 'hide';
                }

                if($project['auth_status'] == '1'){
                    $project['auth_show'] = '';
                }

                $listItemHTML .= generateHTML('clientProjectItem', $project);
            }
            $jsonObj['panel'] = $listItemHTML;
        }

        if($return == True)
        {
            return $jsonObj;
        } 
                
        print json_encode($jsonObj, JSON_FORCE_OBJECT);
        
    }


    

    function authoriseProject($projectId){

        $this->render = 0;
        $this->Project->where('id', $projectId);
        $result = $this->Project->search();

        if(empty($result)){
            return;
        }

        //Only run if the project was not authorised && not blocked
        if($result[0]['auth_status'] == 0 && $result[0]['locked'] == 0){
          $this->Project->arrayToModel($result[0]);
          $this->Project->auth_on = time();
          $from = $this->Project->auth_status;
          $this->Project->auth_status = '1';
          $this->Project->auth_by = getUserId();
          $this->Project->save();
          //upa('revisions', 'registerRevision', array('SCOPE_PROJECTINFO_AUTH', $projectId, 'auth', $from, '1', 0, $this->Project->revision ), False);

          $event = 'Project authorisatie gewijzigd';

          upa('changeTracker', 'changed', array(9, $projectId, False, False, $event, '0', '1'), False);
          upa('projects', 'projectEdited', array($projectId), False);
          upa('sampleAnalysis', 'touchUpStoredResults', array($projectId), False);

          //synchronize to portal
          upa('portal', 'projectAuthorized', array($projectId, checkKeyOrFalse($_POST, 'quiet')), False);

        }

    }

    function deauthoriseProject($projectId, $previewFlag = False){
        $this->render = 0;
        $this->Project->where('id', $projectId);
        $result = $this->Project->search();

        if(empty($result)){
            return;
        }

        //Only run if the project was authorised
        if($result[0]['auth_status'] != 0){
          upa('samples', 'deAuthoriseAllInProject', array($projectId));
          $this->Project->arrayToModel($result[0]);
          $this->Project->auth_on = 0;
          $from = $this->Project->auth_status;
          $this->Project->auth_status = '0';
          $this->Project->revision = $this->Project->revision + 1;
          $this->Project->save();

          if($previewFlag == True){
            $scope = 'SCOPE_PROJECTINFO_AUTH_PREV';
          } else{
            $scope = 'SCOPE_PROJECTINFO_AUTH';
          }

          upa('sampleAnalysis', 'removeStoredResults', array($projectId), False);
          //upa('revisions', 'registerRevision', array($scope, $projectId, 'auth', $from, '0'), False);

          $origin = 'Onbekend';
          $reason = 'Onbekend';

          if(isset($_POST['reason'])){
            $reason = $_POST['reason'];
          }

          if(isset($_POST['origin'])){
            $origin = $_POST['origin'];
          }

          $event = 'Project authorisatie ingetrokken, bron wijziging: ' .  $origin . ', reden:' . $reason;
          upa('changeTracker', 'changed', array(9, $projectId, False, False, $event, '1', '0'), False);
          upa('projects', 'projectEdited', array($projectId), False);
          upa('projects', 'removeExportFlag', array($projectId), False);

          //synchronize to portal
          upa('portal', 'projectDeauthorized', array($projectId, $previewFlag), False);
        }
    }

    function recent(){

        //=========================
        //recently created is easy, just fetch the last
        $this->Project->order('project_date', 'DESC');
        $this->Project->where('auth_status', 0);
        $this->Project->limit(MESA_RECENT_PROJECT_LIMIT);
        $recent = $this->Project->search();

        if(empty($recent)){
            $recent = '{MESA_PLU_NORECENTCREATEDPROJECTSFOUND}';
        }


        $tF = new tableFactory();
        $tF->setTableId('recentProjects');
        $tF->loadTemplate('recentProjectListTable');
        $tF->loadValues($recent);
        $tF->specifyMod('project_date', 'date', array('d-m', ALPC_TF_SELF), 'renderDate');
        $tF->specifyMod('client', 'customerIdToName', array(ALPC_TF_SELF));
        $recentProjects = $tF->renderTable();
        unset($tF);
        $this->Project->free();

         //=========================
        //recently edited

        $this->Project->order('last_edit', 'DESC');
        $this->Project->where('auth_status', 0);
        $this->Project->limit(MESA_RECENT_PROJECT_LIMIT);
        $recentEdit = $this->Project->search();

        if(empty($recentEdit)){
            $recentEdit = '{MESA_PLU_NORECENTEDITPROJECTSFOUND}';
        }

        $tF = new tableFactory();
        $tF->setTableId('recentEditProject');
        $tF->loadTemplate('recentProjectListTable');
        $tF->loadValues($recentEdit);
        //$tF->specifyMod('project_date', 'date', array('d-m', ALPC_TF_SELF), 'renderDate');



        $tF->specifyMod('last_edit', 'date', array('d-m', ALPC_TF_SELF), 'renderDate');


        $tF->specifyMod('client', 'customerIdToName', array(ALPC_TF_SELF));
        $recentEditProjects = $tF->renderTable();
        unset($tF);
        $this->Project->free();

        //=========================
        //recently authorised

        $this->Project->where('auth_status', 1);
        $this->Project->order('auth_on', 'DESC');
        $this->Project->limit(MESA_RECENT_PROJECT_LIMIT);
        $auth = $this->Project->search();

        if(empty($auth)){
            $auth = '{MESA_PLU_NOAUTHPROJECTSFOUND}';
        }

        $tF = new tableFactory();
        $tF->setTableId('recentAuthProjects');
        $tF->loadTemplate('recentProjectListTable');
        $tF->loadValues($auth);
        $tF->specifyMod('auth_on', 'date', array('d-m', ALPC_TF_SELF), 'renderDate');
        $tF->specifyMod('client', 'customerIdToName', array(ALPC_TF_SELF));
        $recentAuthProjects = $tF->renderTable();

        unset($tF);
        $this->Project->free();

        $this->_template->set('recent_projects', $recentProjects);
        $this->_template->set('recent_auth_projects', $recentAuthProjects);
        $this->_template->set('recently_changed', $recentEditProjects);
    }

     function recentProjectsList($timePoint){

        $now = time();
        $startToday = strtotime("midnight", $now);
        $endToday   = strtotime("tomorrow", $startToday) - 1;
        $startToday = strtotime("midnight", $now);

        $startYesterday = strtotime("-1 day", $startToday);
        $endYesterday = $startToday - 1;

        $endLastWeek = $startYesterday -1;
        $beginLastWeek = strtotime("-7 days", $endLastWeek);

        if($timePoint == 'today'){
            $projects = $this->searchProjects(False, $startToday, $endToday, False, False);
        }

        if($timePoint == 'yesterday'){
            $projects = $this->searchProjects(False, $startYesterday, $endYesterday, False, False);
        }

        if($timePoint == 'lastweek'){
            $projects = $this->searchProjects(False, $beginLastWeek, $endLastWeek, False, False);
        }

        if(empty($projects)){
            return generateHTML('alertWarning', array('alert_title' => 'No projects', 'alert_message' => 'No projects found for selected criteria'));
        }

        else{

            $listItemHTML = '';
            foreach($projects as $project){
                $project['client_name'] = pa('clients', 'clientIdToName', array( $project['client']));
                $listItemHTML .= generateHTML('recentProjectItem', $project);
            }
            return $listItemHTML;
        }

    }



    private function searchProjects($client = False, $subclient = False, $from = False, $to = False, $sample = False, $limit = False, $reference = False){


        if($client != False){
            $this->Project->where('client', $client);
        }

        //if($subclient != False){
        //    $this->Project->where('subclient', $subclient);
        //}

        if($from != False ){
            $this->Project->greaterThan('project_date', $from);
        }

        if($to != False){
            $this->Project->lessThan('project_date', $to);
        }

        if($sample != False){
            $projectId = pa('samples', 'fetchSampleProject', array(False, $sample));
            $this->Project->where('id', $projectId);
        }

        if($reference != False){
            $this->Project->like('reference', $reference);
            $this->Project->insertOR();
            $this->Project->like('project_name', $reference);
        }

        if($limit != False){
            $this->Project->limit($limit);
        }

        $this->Project->order('project_date', 'DESC');
        $results = $this->Project->search();
        $this->Project->free();

       return $results;

    }



    function getProjectSelect($client, $subclient, $specialType = 0){

        $this->doNotRenderHeader = True;

        $this->Project->where('client', $client);
        $this->Project->where('subclient', $subclient);

        if($specialType != 0){
            $this->Project->where('special_type', $specialType);
        } else{
            $this->Project->where('special_type', 0);
        }

        $this->Project->where('auth_status', 0);
        $this->Project->order('id', 'DESC');
        $results = $this->Project->search();
        $today = strtotime(date('d-m-Y', time()));

        $options = '<option  selected="selected" value="CREATE">{MESA_SAD_SAMPLEPROJECTCREATE}</option>';

        foreach($results as $arrInd => $project){

            // if($arrInd == 0 && $project['project_date'] >= $today ){
            //      $selected = ' selected="selected" ';
            // } else{
                    $selected = '';
            // }

            $options .= '<option ' . $selected . ' value="' . $project['id'] . '">' . $project['project_name'] . '</option>';
            //$options .= '<option value="' . $project['id'] . '">' . $project['project_name'] . '</option>';
        }


        $this->_template->set('project_drop', $options );
    }

    function otfProjectCreation(){
        $this->render = 0;
        $dateStamp = time();
        $projectId = $this->createProject($_POST['client'], False, $_POST['project_name'], $dateStamp);
        print $projectId;
    }

    function createProject($client, $subclient, $projectName, $projectDate, $preload = False, $extra = False, $specialType = 0, $clientPortalProject = NULL){

        global $lang;
        $this->render = 0;
        $this->Project->client = $client;
        //$this->Project->subclient = $subclient;
        $this->Project->subclient = 0;
        $this->Project->project_name = $projectName;
        $this->Project->project_date = $projectDate;
        $this->Project->revision = 1;
        $this->Project->project_extra = $extra;
        $this->Project->special_type = (int)$specialType;
        $this->Project->added_by = getUserId();
        $this->Project->portal_id = $clientPortalProject;

        //load preloads into array if they were send
        $preDefaults = array();
        if($preload != False){

            foreach($preload as $preloadField){                
                //preload_project_monster
                $preDefaultsName = substr($preloadField['preloadFieldId'], 8);
                $preDefaults[$preDefaultsName] = $preloadField['preloadFieldVal'];
            }
        }

        $defaults = upa('projectFields', 'fetchProjectFields', array());
        $pValues = array();

        foreach($defaults as $aid => $projectField){

            if($preload != False){
                //grab info from array
                if(isset($preDefaults[$projectField['name']])){
                    $pValues[$projectField['name']] = $preDefaults[$projectField['name']];
                } else{
                    $pValues[$projectField['name']] = '???';
                }

            } else{
                //set defaults
                $standardValue = $projectField['std_value'];

                if($projectField['std_value'] == '{today}'){
                    $standardValue = $lang['today'];
                }

                if($projectField['std_value'] == '{tommorow}'){
                    $standardValue = $lang['tommorow'];
                }

                if($projectField['std_value'] == '{yesterday}'){
                    $standardValue = $lang['yesterday'];
                }

                if($projectField['std_value'] == '{current_time}'){
                    $standardValue = $lang['current_time'];
                }

                $pValues[$projectField['name']] = $standardValue;
            }



        }

        $this->Project->custom_fields = json_encode($pValues, JSON_FORCE_OBJECT);
        $this->Project->predicted_end = time();

        $this->Project->save();

        //$lteArray = array('project_name' => $projectName, 'client_name' => customerIdToName($client), 'project_id' => $this->Project->lastInsertId, 'client_id' => $this->Project->client );
        
        $event = 'Project ' . $projectName . ' aangemaakt';
        upa('changeTracker', 'changed', array(3,  $this->Project->lastInsertId, False, False, $event, False, False), False);

        return $this->Project->lastInsertId;
    }

    function projectIdToName($projectId, $alsoDate = False){
        $this->render =0;
        $this->Project->id = $projectId;
        $results = $this->Project->search();
        $return = $results['0']['project_name'];

        if($alsoDate == True){
            $date = date('d-m-Y', $results['0']['project_date']);
            $return = $date . ' / ' . $return;
        }
        return $return;
    }

    function renderExtraFields($id){

        $this->render = False;
        $this->Project->where('id', $id);
        $project = $this->Project->search();
        $renderedHtml = '';

        if(!empty($project)){

            $project_extra = json_decode($project[0]['project_extra'], JSON_FORCE_OBJECT);
            if(is_array($project_extra)){



                foreach($project_extra as $extraInfoKey => $extraInfoValue){

                    $field = array();
                    $field['input_class'] = 'project_extra_field';
                    $field['name_prefix'] = 'pj_';
                    $field['hider'] = NULL;
                    $field['hider'] = 'hidden';

                    if($extraInfoKey == 'sample_method' ){
                        $field['name'] = 'sample_method';
                        $field['alias'] = 'Bemonster methode';
                        $field['std_value'] = upa('sampleProcedures', 'getProcedureDropDown', array($extraInfoValue), false);
                        $renderedHtml .= generateHTML('customProjectFields/customDropdown', $field);
                    }

                    if($extraInfoKey == 'client_reference'){
                        $field['name'] = 'client_reference';
                        $field['alias'] = 'Klant referentie';
                        $field['std_value'] = $extraInfoValue;
                        $field['value'] = $extraInfoValue;
                        $renderedHtml .= generateHTML('customProjectFields/customFieldText', $field);
                    }

                    if($extraInfoKey == 'number_of_blanks'){
                        $field['name'] = 'number_of_blanks';
                        $field['alias'] = 'Aantal blankos';

                        if($extraInfoValue == '1'){
                            $yesSelect ='selected="selected"';
                            $noSelect ='';
                        } else{
                            $yesSelect ='';
                            $noSelect ='selected="selected"';
                        }

                        $optionsBlank = "<option value='0' " . $noSelect . ">Nee</option>";
                        $optionsBlank .= "<option value='1' " . $yesSelect . ">Ja</option>";
                        $field['std_value'] = $optionsBlank;
                        $renderedHtml .= generateHTML('customProjectFields/customDropdown', $field);
                    }
                }
            }

            print json_encode(array('extra_html'=> $renderedHtml));
        }
    }

    function search($entryProject = 'null'){

        $clientName = '';
        $entryClient = '';
        $entrySubClient = '';

        //$profiler = new Profiler();
        //$profiler->addCheckpoint('Reached project search page');
        
        if($entryProject != 'null'){
            $this->Project->where('id', $entryProject);
            $results = $this->Project->search();

            //$profiler->addCheckpoint('Project found, result loaded in');

            if(!empty($results)){
                $clientName = pa('clients', 'clientIdToName', array($results['0']['client']) );
                $entryClient = $results['0']['client'];
                //$entrySubClient = $results['0']['subclient'];

                //$profiler->addCheckpoint('Client info resolved');
            }
        }

        $projectFields = upa('projectFields', 'renderProjectFields', array(True));
        //$profiler->addCheckpoint('Project fields resolved');

        $projectNotes = upa('projectNotes', 'listNote', array(True));
        
        //$profiler->addCheckpoint('Project notes resolved');

        $this->_template->set('project_fields', $projectFields);
        $this->_template->set('entry_project', $entryProject);
        $this->_template->set('entry_client', $entryClient);
        //$this->_template->set('entry_subclient', $entrySubClient);
        $this->_template->set('entry_client_name', $clientName);
        $this->_template->set('project_notes', $projectNotes);
        $sampleNoteField =  generateHTML('customFields/notes', array());
        $this->_template->set('sample_notes', $sampleNoteField);

        //$profiler->addCheckpoint('Tenmplate information set, projectController@search complete');
    }

    function recentProjectsReturn(){

        $this->render = 0;

        $obj['today'] = $this->recentProjectsList('today');
        $obj['yesterday'] = $this->recentProjectsList('yesterday');
        $obj['lastweek'] = $this->recentProjectsList('lastweek');

        print json_encode($obj, JSON_FORCE_OBJECT);

    }

    function countSamplesInProject($projectId){
        return upa('samples', 'countSamplesInProject', array($projectId));
    }

    function loadProjectSamples($projectId){

        $this->doNotRenderHeader = 1;
        $this->render = 0;
        

        $list = '';
                
        $s = new Sample();
        $s->where('project', $projectId);
        $s->select(['id','project', 'barcode', 'description', 'sample_note']);
        $samples = $s->search();

        //$samples = upa('samples', 'projectSamples', array($projectId));        //TODO: refactor

        if(empty($samples)){
            $list .= generateHTML('alertWarning', array('alert_title' => '{MESA_PLU_PROJECTEMPTY}', 'alert_message' => '{MESA_PLU_PROJECTEMPTYEXPLANATION}' ));
        }

        else {

            $sa = new SampleAnalysis();
            $sampleIds = array_column($samples, 'id');
            $sql = 'SELECT id, sample, is_ready FROM sampleanalysis where sample IN (' . implode(',', $sampleIds) . ')';
            $saids = $sa->customQuery($sql, []);
            $readyStateGroups = _group_by($saids, 'sample');

            $follow = 1; 

            foreach($samples as $sample){
               

                if(array_key_exists($sample['id'], $readyStateGroups))
                {
                    $readyStates = array_column($readyStateGroups[$sample['id']], 'is_ready');
                }

                else
                {
                    $readyStates = [1];
                }
                                
                $isReady = (in_array(0, $readyStates)) ? False : True; 
                
                $sample['note_found'] = (empty($sample['sample_note'])) ? '' : '!';

                if($isReady){
                  $sample['isReady'] = '';
                } else{
                  $sample['isReady'] = 'hidden';
                }

                $sample['follow'] = $follow;

                $list .= generateHTML('projectSampleLine', $sample);
                $follow++;
            }
        }
        $this->_template->set('sample_list', $list);
        $render = $this->hardRender();
        return array('render' => $render, 'samples' => $samples);
    }



    function generateResultList($projectId){

        $this->renderAlternateHeader = 'slim';

        $this->Project->where('id', $projectId);
        $result = $this->Project->search();

        if(empty($result)){
            return;
        }

        $this->Project->arrayToModel($result[0]);

        //set header bits
        $this->_template->set('name', $this->Project->project_name);
        $clientByName = pa('clients', 'clientIdToName', array($this->Project->client));
        $subClientByName = pa('subClients', 'subclientIdToName', array($this->Project->subclient));
        $this->_template->set('client_by_name', $clientByName);
        $this->_template->set('subclient_by_name', $subClientByName);

        //get samples
        $samples = pa('samples', 'projectSamples', array($projectId, True));

        $sampleRender = '';
        $tF = new tableFactory();
        $tF->setTableId('reportResults');
        $tF->legoMode();
        $tF->loadTemplate('projectReportLego');

        //flow info caching
        $fCache = array();
        $resultArray = array();
        $nameArray = array();

        foreach($samples as $sample){

            $requested = pa('sampleAnalysis', 'fetchAnalysisArray', array($sample['id']));
            foreach($requested as $reqInd => $reqFlow){
                if(!array_key_exists($reqFlow['flow'], $fCache)){
                    $fCache[$reqFlow['flow']] = pa('flows', 'getFlow', array($reqFlow['flow']));
                }
                $flowResultFields = pa('flows', 'getResultFieldsArray', array($reqFlow['id']));
                $resultArray[$sample['id']][$reqFlow['id']] = $flowResultFields;
                $nameArray[$reqFlow['id']] = $fCache[$reqFlow['flow']]['name'];
            }
        }

        $previousAnalysis = False;
        $tF->useBrick('th', array());

        foreach($resultArray as $sId => $analysis){

            $sampleRowSpan = 0;
            foreach($analysis as $counter){
                $sampleRowSpan = $sampleRowSpan + count($counter);
            }

            $rArr['sample_barcode'] = $samples[$sId]['barcode'];
            $rArr['sample_name'] = $samples[$sId]['description'];

            $sampleFirstLine = True;
            $analysisFirstOne = True;

            foreach($analysis as $aId=>$aContent){

                $rArr['flow_name'] = $nameArray[$aId];

                //print $aId . ' vs ' . $previousAnalysis . ' First one:' . $analysisFirstOne .  '<br />';

                if($aId != $previousAnalysis && $analysisFirstOne != True){

                    $createNewSubLine = True;
                } else{
                    $createNewSubLine = False;
                }

                $analysisFirstOne = False;
                $previousAnalysis = $aId;

                foreach($aContent as $fName => $fContent){

                    $rArr['result_name'] = $fName;
                    $rArr['result_value'] = $fContent;

                    if($sampleFirstLine == True){
                        $rArr['rowspan'] = $sampleRowSpan;
                        $rArr['arowspan'] = count($aContent);
                        $tF->useBrick('sampleStart', $rArr);
                        $sampleFirstLine = False;
                    }

                    elseif($sampleFirstLine != True && $createNewSubLine == True){
                        $rArr['rowspan'] = count($aContent);
                        $tF->useBrick('newTest', $rArr);
                        $createNewSubLine = False;
                    }

                    elseif($sampleFirstLine != True && $createNewSubLine == False){
                        $tF->useBrick('resultOnly', $rArr);
                    }
                }
            }


        }

        $tF->useBrick('tf', array());
        $this->_template->set('results_table', $tF->returnRender());

    }

    function toolbarUpdate($projectId){

        $this->render = 0;

        $infoObj = array();

        /*  Bookmark Button  */
        $infoObj['following_project'] = 0;

        /*  Auth sample Button  */

        $samples = pa('samples', 'projectSamples', array($projectId));
        $fullAuth = True;

        foreach($samples as $sample){
            $thisSampleAuth = pa('sampleAnalysis', 'checkFullAuth', array($sample['id']) );
            if($thisSampleAuth != 'full'){
                $fullAuth = False;
            }
        }

        if($fullAuth == True){
            $infoObj['auth_button'] = 'show_deauth';
        } else{
            $infoObj['auth_button'] = 'show_auth';
        }


        /*  Auth project Button  */
        $this->Project->where('id', $projectId);
        $projectResult = $this->Project->search();

        if($projectResult[0]['auth_status'] == 0){
            $infoObj['project_auth'] = 'show_auth';
        }

        if($projectResult[0]['auth_status'] == 1){
            $infoObj['project_auth'] = 'show_deauth';
        }

        print json_encode($infoObj, JSON_FORCE_OBJECT);
    }

    function fetchProjectInfo($projectId){
        $this->Project->where('id', $projectId);
        $results = $this->Project->search();

        if(empty($results)){
            return False;
        } else{
            return $results[0];
        }
    }

    function removeProjectsForClient($clientId){
        $this->render = 0;
        $this->Project->where('client', $clientId);
        $results = $this->Project->search();

        if(!empty($results)){

            //remove projects
            foreach($results as $project){
                $this->Project->id = $project['id'];
                pa('samples', 'removeProjectSamples', array($project['id']));
                $this->Project->remove();
            }
        }
    }

    function removeSubclientProjects($subclientId){
        $this->render = 0;
        $this->Project->where('subclient', $subclientId);
        $results = $this->Project->search();

        if(!empty($results)){
            //remove projects
            foreach($results as $project){
                $this->Project->id = $project['id'];
                pa('samples', 'removeProjectSamples', array($project['id']));
                $this->Project->remove();
            }
        }
    }

    function removeProject($projectId){
        $this->render = 0;
        $this->Project->id = $projectId;
        $this->Project->delete();

        $samples = upa('samples', 'removeProjectSamples', array($projectId), False);
        $ids = array_column($samples, 'portal_sample_id');
                
        upa('revisions', 'removeProjectRevisions', array($projectId), False);
        upa('bookmarks', 'removeProjectBookmarks', array($projectId), False);
        upa('exports', 'removeSavedProjects', array($projectId), False);
        upa('portal', 'destroySample', array($ids), False );        
    }

    function updateProjectField($projectId){

        $field = $_POST['field'];
        $value = $_POST['value'];

        $this->render = 0;
        $this->Project->where('id', $projectId);
        $results =  $this->Project->search();

        if(empty($results)){
            return;
        }

        $this->Project->arrayToModel($results['0']);

        if(isset($this->Project->$field)){
            $from = $this->Project->$field;
            $this->Project->$field = $value;
            $this->Project->save();
        } else {
            $arr = json_decode($this->Project->custom_fields, True);
            $from = $arr[$field];
            $arr[$field] = $value;
            $this->Project->custom_fields = json_encode($arr, JSON_FORCE_OBJECT);
            $this->Project->save();
        }

        //this is a bit of a hack to prevent double entrys regarding the
        //date pickers for project fields
        if($from != $value){
            //upa('revisions', 'registerRevision', array('SCOPE_PROJECTINFO_CHANGE', $projectId, $field, $from, $value ), False);

            $event = 'Project informatie gewijzigd: ' . $field;
            upa('changeTracker', 'changed', array(17, $projectId, False, False, $event, $from, $value), False);

            upa('projects', 'projectEdited', array($projectId), False);
        }
    }



    function updateProjectExtraField($projectId, $field, $value){

        $this->render = 0;
        $this->Project->where('id', $projectId);
        $results =  $this->Project->search();

        if(empty($results)){
            return;
        }

        $this->Project->arrayToModel($results['0']);


        $arr = json_decode($this->Project->project_extra, True);
        $from = $arr[$field];
        $arr[$field] = $value;
        $this->Project->project_extra = json_encode($arr, JSON_FORCE_OBJECT);
        $this->Project->save();

        //this is a bit of a hack to prevent double entrys regarding the
        //date pickers for project fields
        if($from != $value){
            upa('revisions', 'registerRevision', array('SCOPE_PROJECTINFO_CHANGE', $projectId, $field, $from, $value ), False);
            upa('projects', 'projectEdited', array($projectId), False);
        }
    }


    function loadProjectFields($projectId){

        $this->render = 0;
        $this->Project->where('id', $projectId);
        $results = $this->Project->search();

        $return = array();

        if(empty($results['0']['custom_fields'])){
            $return['project_name'] = $results['0']['project_name'];
        }
        else {
            $return = json_decode($results['0']['custom_fields'], True);
            $return['project_name'] = $results['0']['project_name'];

        }

        //load in project "extra" fields
        if(!empty($results[0]['project_extra'])){
            $extra = json_decode($results[0]['project_extra'], JSON_FORCE_OBJECT);
            foreach($extra as $extraField => $extraValue){
                $return['project_extra_' . $extraField] = $extraValue;
            }
        }


        //print out
        print json_encode($return, JSON_FORCE_OBJECT);
    }

    function getProjectInformation($project){
      
      $this->render = 0;

      //$debugprofiler = new Profiler();
      //$debugprofiler->addCheckpoint('Fetching project information');

      //release previous lock
      if(isset($_POST['previous_project']))
      {
        upa('keyrings', 'removeOwnLock', array('PROJECT', $_POST['previous_project']));
      }
      
      $this->Project->where('id', $project);
      $results = $this->Project->search();
      
            

      if(!empty($results))
      {

        $lockObj = upa('keyrings', 'requestLockAndStatus', array('PROJECT', $project));

        //$debugprofiler->addCheckpoint('Lock requested');
        
        $projectInfo = upa('samples', 'buildProjectInfoBlock', array(False, $project));
        
        $retObj['project_block'] = $projectInfo['html'];                

        $projectSamples = upa('projects', 'loadProjectSamples', array($project));
    

        $retObj['sample_block'] = $projectSamples['render'];
        $retObj['project_auth'] = $results['0']['auth_status'];
        $retObj['lock_object'] = $lockObj;
        $retObj['following_project'] = upa('bookmarks', 'isFollowing', array($project, 'PROJECT') );

        //$debugprofiler->addCheckpoint('Ret object build, bookmarks detected');

        //set project wide notes as active, as default
        $projectNoteArray = $this->loadInNotesArray($results[0]['project_notes']);

        //$debugprofiler->addCheckpoint('Project notes loaded');

        //set dropdown options
        $found = (array_key_exists('project', $projectNoteArray) ? '!' : ' ');
        $sampleDropdown = '<option value="project" selected="selected">' .  $found . ' Algemene opmerkingen</option>';
        $pSamples = $projectSamples['samples'] ;

        foreach($pSamples as $sample){
            $found = (array_key_exists($sample['id'], $projectNoteArray) ? '!' : ' ');
            $sampleDropdown .= '<option value="' . $sample['id'] . '">' .  $found . '[' . $sample['barcode'] . '] ' . $sample['description'] . '</option>';
        }

        //$debugprofiler->addCheckpoint('Note dropdown created');

        $retObj['notes_present'] = False;
        if(!empty($projectNoteArray)){
            $retObj['notes_present'] = True;
        }        

        $authLockObj = array();

        if( $results['0']['locked'] == 1 )
        {
            $authLockObj['locked_by_id'] = $results['0']['locked_by'];            
            $authLockObj['locked_by_name'] = getUserName($results['0']['locked_by']);
            $authLockObj['locked_by_avatar'] = generateAvatar($results['0']['locked_by'], False, 'Small', False, True);          
            $authLockObj['lock_reason'] = $results['0']['lock_message'];            
        }
        
        
        $retObj['locked'] =  $results['0']['locked'];
        $retObj['auth_lock_object'] = $authLockObj;
        
        
        $retObj['project_notes'] = checkKeyOrBlank($projectNoteArray, 'project');        
        $retObj['project_samples_dropdown'] = $sampleDropdown;

        //$debugprofiler->addCheckpoint('Ret object done, sending back');

        print json_encode($retObj, JSON_FORCE_OBJECT);
      }
    }

    private function loadInNotesArray($json = False){
      $arr = json_decode($json, JSON_FORCE_OBJECT);
      if(!is_array($arr)){
          return array();
      } else{
          return $arr;
      }
    }

    function loadSampleNote($projectId, $sampleId = False){
      $this->render = 0;

      $this->Project->where('id', $projectId);
      $results = $this->Project->search();

      if(!empty($results)){
        $projectNoteArray = $this->loadInNotesArray($results[0]['project_notes']);
        if($sampleId == False){
          $sampleId = 'project';
        }
        print json_encode(array('note' => checkKeyOrBlank($projectNoteArray, $sampleId)));
      }

    }

    function saveProjectNotes($project, $sampleId){
        #$this->Project->id = $project;
        $this->render = False;
        $this->Project->where('id', $project);
        $results = $this->Project->search();

        if(!empty($results)){
            $projectNoteArray = $this->loadInNotesArray($results[0]['project_notes']);
            $this->Project->id = $project;
            $previousNote = checkKeyOrBlank(($projectNoteArray[$sampleId]));
            
            if(empty(trim($_POST['project_notes']))){
                unset($projectNoteArray[$sampleId]);
            } else{
                $projectNoteArray[$sampleId] = $_POST['project_notes'];
            }
                        
            
            $this->Project->project_notes = json_encode($projectNoteArray, JSON_FORCE_OBJECT);
            $this->Project->save();

            if($sampleId == 'project'){
                $event = 'Globale project notities gewijzigd';
                upa('changeTracker', 'changed', array(20, $project, False, False, $event, $previousNote, $_POST['project_notes']), False);			
            } else{
                $sample = upa('samples', 'fetch', array($sampleId), False);
                $barcode = checkKeyOrBlank($sample, 'barcode');
                $event = 'Project notitie voor monster gewijzigd, ' . $barcode;
                upa('changeTracker', 'changed', array(20, $project, False, False, $event, $previousNote, $_POST['project_notes']), False);			
            }
           

        }
    }

    function findNumberOfProjectsPerClient($clientId){
        $this->Project->where('client', $clientId);
        $this->Project->search();
        return $this->Project->lastQueryCount;
    }

    function findPercentageOfProjectsClosed($clientId){

        $number = $this->findNumberOfProjectsPerClient($clientId);

        $this->Project->free();
        $this->Project->where('client', $clientId);
        $this->Project->where('auth_status', '1');
        $this->Project->search();

        $auth = $this->Project->lastQueryCount;

        if($number == 0){
            return '-';
        }

        else{
            return ceil(($auth / $number) * 100);
        }
    }

    function projectEdited($projectId = False , $sampleId = False){

        if($projectId == False && $sampleId == False){
            return;
        } elseif($projectId != False){
            
            $this->Project->id = $projectId;
            $this->Project->last_edit = time();
            $this->Project->save();
        } elseif($sampleId != False){
            $sampleInfo = upa('samples', 'fetch', array($sampleId), False);
            if(!empty($sampleInfo)){
                $parentProject = $sampleInfo['project'];
                $this->Project->id = $parentProject;
                $this->Project->last_edit = time();
                $this->Project->save();
            }
        }
    }

    function projectEndTime($projectId){

        $samples = upa('samples', 'fetchSamplesInProject', array($projectId));
        $endPoint = 0;

        foreach($samples as $sample){

            if($sample['predicted_end'] > $endPoint){
                $endPoint = $sample['predicted_end'];
            }

        }

        if($endPoint == 0){
            return time() - 1;
        } else{
            return $endPoint;
        }
    }

    function overview(){

    }

    function projectRunningOpenCount(){
        $this->render = False;
        $currentTime = time();
        
        //not started yet
        $this->Project->where('started', 0);        
        $pending =  $this->Project->countIds();
        $this->Project->deepFreed();

        //not authorised, not ready
        $this->Project->where('auth_status', 0);
        $this->Project->where('is_ready', 0);
        $this->Project->where('started', 1);        
        $open =  $this->Project->countIds();
        $this->Project->deepFreed();

        $this->Project->where('auth_status', 0);
        $this->Project->where('started', 1);
        $this->Project->where('is_ready', 1);        
        $waiting = $this->Project->countIds();
        $this->Project->deepFreed();

        $this->Project->where('auth_status', 1);
        $this->Project->where('started', 1);
        $this->Project->where('rap_stat', 0);        
        $rapWaiting = $this->Project->countIds();

        //inject sample count, and buffer count here
        $samplesOpen = upa('samples', 'preciseCountEmptys', array(), False);
        $bufferOpen = upa('sampleBuffers', 'countBuffer', array(), False);
        $thtOpen = upa('sampleBuffers', 'countTHT', array(), False);
        $innocOpen = upa('samples', 'countOpenSamples', array(), False);

        print json_encode(array('samples_non_innoced' => $innocOpen, 'open'=> $open, 'waiting'=> $waiting, 'rap_waiting' => $rapWaiting, 'samples_open' => $samplesOpen, 'pending' => $pending, 'buffer' => $bufferOpen, 'tht_open' => $thtOpen), JSON_FORCE_OBJECT);
    }

    function setStartedFlag($projectId){
      $this->Project->id = $projectId;
      $this->Project->started = 1;
      $this->Project->save();
    }

    function setExportFlag($projectId){

        $projectInfo = upa('projects', 'fetch', array($projectId), False);

        //we can only set export flag if this project had been authorised
        if($projectInfo['auth_status'] == '1'){
          $this->Project->id = $projectId;
          $this->Project->rap_stat = 1;
          $this->Project->rap_by = getUserId();
          $this->Project->rap_on = time();
          $this->Project->rap_rev = $projectInfo['revision'];
          $this->Project->save();
        }
    }

    function removeExportFlag($projectId)
    {
      $this->Project->id = $projectId;
      $this->Project->rap_stat = 0;      
      $this->Project->save();
    }

    function statusLabel($project)
    {

        if($project['auth_status'] == '1' && $project['rap_stat'] == '1')
        {
            return [
                'status' => 'PDF gegenereerd',
                'flair' => 'label',
            ];
        }

        if($project['auth_status'] == '1')
        {
            return [
                'status' => 'Geauthoriseerd',
                'flair' => 'label-success',
            ];
        }

       

        if($project['locked'] == 1)
        {
            return [
                'status' => 'Geblokkeerd',
                'flair' => 'label-warning',
            ];
        }

        if($project['started'] == '0')
        {
            return [
                'status' => 'Ontvangen',
                'flair' => 'label-inverse',
            ];
        }

        
        if($project['is_ready'] == '1' && $project['auth_status'] == '0' && $project['started'] == '1')
        {
            return [
                'status' => 'Afgerond',
                'flair' => 'label-warning',
            ];
        }

        return [
            'status' => 'Lopend',
            'flair' => 'label-info',
        ];

    }



    function allProjectSamplesHaveAnalysis($projectIds)
    {
        $singleProject = False;

        if(!is_array($projectIds))
        {
            $singleProject = True;
            $projectIds = array($projectIds);
        }

        $projectIds = array_values(array_unique(array_filter($projectIds, 'is_numeric')));
        $projectAnalysisStatus = array();

        foreach($projectIds as $projectId)
        {
            $projectAnalysisStatus[(int)$projectId] = False;
        }

        if(count($projectIds) == 0)
        {
            return ($singleProject == True) ? False : $projectAnalysisStatus;
        }

        $params = array();
        $placeholders = array();

        foreach($projectIds as $idx => $projectId)
        {
            $paramName = 'project_' . $idx;
            $placeholders[] = ':' . $paramName;
            $params[$paramName] = (int)$projectId;
        }

        $sql = 'SELECT samples.project, COUNT(DISTINCT samples.id) as sample_count, COUNT(DISTINCT sampleanalysis.sample) as samples_with_analysis '
            . 'FROM samples '
            . 'LEFT JOIN sampleanalysis ON sampleanalysis.sample = samples.id AND sampleanalysis.project = samples.project '
            . 'WHERE samples.project IN (' . implode(',', $placeholders) . ') '
            . 'GROUP BY samples.project';

        $sampleObj = new Sample();
        $results = $sampleObj->customQuery($sql, $params);

        foreach($results as $result)
        {
            $projectAnalysisStatus[(int)$result['project']] = ((int)$result['sample_count'] > 0 && (int)$result['sample_count'] == (int)$result['samples_with_analysis']);
        }

        if($singleProject == True)
        {
            $projectId = (int)$projectIds[0];
            return $projectAnalysisStatus[$projectId];
        }

        return $projectAnalysisStatus;
    }

    function writePrintInfo($projectId)
    {

        $this->render = False; 
        
        $project = new Project();
        $project->id = $projectId;
        $project = $project->first();        

        $user = getUserId();

        $printData = array(); 
        
        
        if(!empty($project['print_info']))
        {
            
            $decoded = json_decode($project['print_info'], True);
            
            if(is_array($decoded))
            {
                $printData = $decoded;
            }
        }


        
        //check if json is already there and valid
        $printData = array(
            'last_print_id' => $user,
            'last_print_name' => pa('profiles', 'getUserFirstName', array($user)),
            'print_times' => ((array_key_exists('print_times', $printData)) ? $printData['print_times'] + 1 : 1)
        );


        $projecUp = new Project();
        $projecUp->id = $projectId;
        $projecUp->print_info = json_encode($printData, JSON_FORCE_OBJECT);
        $projecUp->save();

        print($projecUp->print_info);

    }


    function overviewLoader($skip = 0, $running = True, $auth = False, $reported = False, $pending = False, $blocked = False)
    {
        
        $this->render = False;
        $currentTime = time();
        $lastId = null;
        
        $holidays = new Holidays();

        //only return authorised
        if( filter_var($auth, FILTER_VALIDATE_BOOLEAN) == True){

            if( filter_var($reported, FILTER_VALIDATE_BOOLEAN) == True){
              $this->Project->order('rap_on', 'DESC');
              $this->Project->where('auth_status', 1);
              $this->Project->where('rap_stat', 1);
              $this->Project->where('started', 1);
            }else{
              $this->Project->order('auth_on', 'ASC');
              $this->Project->where('auth_status', 1);
              $this->Project->where('rap_stat', 0);
              $this->Project->where('started', 1);
            }
        }

        if( filter_var($running, FILTER_VALIDATE_BOOLEAN) == True){
          //return only pending  projects
          if( filter_var($pending, FILTER_VALIDATE_BOOLEAN) == True){
              $this->Project->order('project_date', 'ASC');
              $this->Project->where('started', 0);


          }
          //return only running projects
          else{
            $this->Project->order('predicted_end', 'ASC');
            $this->Project->where('is_ready', 0);
            $this->Project->where('started', 1);
            $this->Project->where('auth_status', 0);
          }



        }

        //return only completed, but unauthorised projects
        if( filter_var($running, FILTER_VALIDATE_BOOLEAN) == False && filter_var($auth, FILTER_VALIDATE_BOOLEAN) == False && filter_var($blocked, FILTER_VALIDATE_BOOLEAN) == False  ){
            //grab auth_status 0
            //select samples, check if all are isReady
            //if so, add to this list.
            $this->Project->order('became_ready_on', 'ASC');
            $this->Project->where('auth_status', 0);
            $this->Project->where('is_ready', '1');
            $this->Project->where('started', 1);
            //$this->Project->lessThan('predicted_end', $currentTime );
        }



        //only return blocked 
        if( filter_var($blocked, FILTER_VALIDATE_BOOLEAN) == True){

            $this->Project->order('predicted_end', 'ASC');
            $this->Project->where('locked', 1);            
            $this->Project->where('auth_status', 0);
        }

        //$this->Project->order('auth_status', 'ASC');
        if($skip != 0 ){
            $this->Project->limit($skip . ',50' );
        } else{
            $this->Project->limit(50);
        }

        $projects = $this->Project->search();
        $table = '';


        $projectIds = array_column($projects, 'id');
        $projectAnalysisStatus = $this->allProjectSamplesHaveAnalysis($projectIds);

        //get unique clients
        $clients = array_column($projects, 'client');        
        $clients = array_unique($clients);

        
        if(count($clients) > 0)
        {            
            $clientObj = new Client();
            $clientSql = 'SELECT id,name FROM clients WHERE id IN (' . implode(',', $clients) .')';
            $clientNames = $clientObj->customQuery($clientSql, []);
            $clientNames = array_column($clientNames, 'name', 'id');            
        }        

        //get unique added_by 
        $addedBy = array_column($projects, 'added_by');        
        $addedBy = array_unique($addedBy);

        $rapBy = array_column($projects, 'rap_by');
        $rapBy = array_unique($rapBy);

        //merge
        $addedBy = array_merge($addedBy, $rapBy);

        //remove anything that is not an int
        $addedBy = array_filter($addedBy, 'is_numeric');


        if(count($addedBy) > 0)
        {
            $userObj = new Profile();
            $userSql = 'SELECT id,first_name,last_name FROM profiles WHERE id IN (' . implode(',', $addedBy) .')';
            $userNames = $userObj->customQuery($userSql, []);
            $userNames = array_column($userNames, null, 'id');
        }

        if(count($projectIds)  > 0 )
        {
            $sampleObj = new Sample();
            $sampleSql = 'SELECT project, COUNT(*) as count FROM samples WHERE project IN (' . implode(',', $projectIds) .') GROUP BY project';
            $sampleCounts = $sampleObj->customQuery($sampleSql, []);    
            $sampleCounts = array_column($sampleCounts, 'count', 'project');
        }


            
        foreach($projects as $project){

                            

            $repStack = array();            
            $project['all_samples_have_analysis'] = (array_key_exists($project['id'], $projectAnalysisStatus)) ? $projectAnalysisStatus[$project['id']] : False;

            $printInfo = json_decode($project['print_info'], True);

            //username

            if(is_array($printInfo) && array_key_exists('last_print_name', $printInfo)){
                $repStack['last_print_name'] = $printInfo['last_print_name'];
            } else{
                $repStack['last_print_name'] = '';
            }


            if(is_array($printInfo) && array_key_exists('print_times', $printInfo)){
                $repStack['print_times'] = '(' . $printInfo['print_times'] . ')';
            } else{
                $repStack['print_times'] = '';
            }


            
            $repStack['print_button_class'] = ($project['all_samples_have_analysis'] == True) ? '' : 'hidden';
            $repStack['all_samples_have_analysis'] = ($project['all_samples_have_analysis'] == True) ? 'X' : '';
            $repStack['client'] = (array_key_exists($project['client'], $clientNames)) ? $clientNames[$project['client']] : '?';
            $repStack['reference'] = $project['reference'];
            $repStack['added'] = date('d-m-Y', $project['project_date']);                        
            $repStack['added_by'] = ($project['added_by'] != '') ? $userNames[$project['added_by']]['first_name'] . ' ' . $userNames[$project['added_by']]['last_name'] : '?';
            $repStack['id'] = $project['id'];
            $repStack['LB'] = ALPC_BASEPATH;
            $repStack['style'] = '';

            

            $repStack['samples_in_project'] = (array_key_exists($project['id'], $sampleCounts)) ? $sampleCounts[$project['id']] : '0';

            $normalIcon = ALPC_BASEPATH .'/public/img/normal.png';
            $legIcon = ALPC_BASEPATH .'/public/img/leg.png';
            $rodacIcon = ALPC_BASEPATH .'/public/img/rodac.png';

            $repStack['sample_flags'] = '';

            //if($info['leg'] == True){                
            if($project['special_type'] === '1'){
              $repStack['sample_flags'] .= '<img src="' . $legIcon . '"/>';
            }

            if($project['special_type'] === '0'){
              $repStack['sample_flags'] .= '<img src="' . $normalIcon . '"/>';
            }

            if($project['special_type'] === '2'){
              $repStack['sample_flags'] .= '<img src="' . $rodacIcon . '"/>';
            }
                        

            $repStack['client_reference'] = (trim($project['reference']) === trim($project['project_name']) ) ? '' : $project['project_name'];


            if($project['auth_status'] == '0'){

                $repStack['exportHide'] = 'hidden';

                if($running == False && $blocked == False){
                    $repStack['flair'] = 'label-warning';
                    $repStack['status'] = 'Afgerond';
                    $repStack['progressHide'] = 'hidden';

                } 
                
                elseif($blocked == True){
                    $repStack['flair'] = 'label-warning';
                    $repStack['status'] = 'Geblokkeerd';
                 
                }
                
                else{


                    if($pending == True){
                      $repStack['status'] = 'Ontvangen';
                      $repStack['flair'] = 'label-inverse';
                    } else{

                        if($project['locked'] == 1)
                        {
                            $repStack['status'] = 'Lopend (Geblokkeerd)';
                            $repStack['flair'] = 'label-warning';
                        }

                        else{
                            $repStack['status'] = 'Lopend';
                            $repStack['flair'] = 'label-info';
                        }
                        
                     
                    }
                }


                if($project['predicted_end'] == -1){
                  $percent = 0;
                } else{
                  $distance = $project['predicted_end'] - $project['project_date'];
                  $left = $project['predicted_end']  - time();
                  if($distance == 0){
                      $percent = 100;
                  } else{
                      $percent = (1 - ($left / $distance)) * 100;
                  }

                  if($percent > 100 ){
                      $percent = 100;
                  } else{
                      //use ceil here, its visiually more pleasing
                      $percent = ceil($percent);

                  }
                }

                $repStack['expected_title'] = 'Verwacht';

                if( filter_var($running, FILTER_VALIDATE_BOOLEAN) == False && filter_var($auth, FILTER_VALIDATE_BOOLEAN) == False ){
                    

                    $received = peekIntoJSON($project['custom_fields'], 'project_ontvangst');
                    $becameReady = date('d-m-Y', $project['became_ready_on']);

                    $repStack['expected_date'] = $received  . ' | ' . $becameReady;

                } 

                else
                {


                    if($project['predicted_end'] == -1){
                        $repStack['expected_date'] = 'Nog niet bekend';
                    }
                  
                    else
                    {
                        
                        if( filter_var($running, FILTER_VALIDATE_BOOLEAN) == True)
                        {
                            $markup = $this->runningMarkup($project['predicted_end'], $holidays);

                            if($markup == 'orange')
                            {
                                $repStack['style'] = 'font-weight: 900; color:orange;';
                            }

                            if($markup == 'red')
                            {
                                $repStack['style'] = 'font-weight: 900; color:red;';
                            }

                            
                            $repStack['expected_date'] = date('d-m-Y', $project['predicted_end']);
                            
                        }

                        else
                        {
                            $repStack['expected_date'] = date('d-m-Y', $project['predicted_end']);
                        }
                        
                        
                    
                    }

                }


            }

            if($project['auth_status'] == '1'){

                $repStack['dataDumpHide'] = '';

                if($project['rap_stat'] == '1'){
                  $repStack['exportHide'] = '';
                  $repStack['flair'] = 'label';
                  $repStack['status'] = 'PDF gegenereerd';
                  $repStack['expected_date'] = date('d-m-Y', $project['rap_on']);
                  $repStack['exported_by'] = $userNames[$project['rap_by']]['first_name'] . ' ' . $userNames[$project['rap_by']]['last_name'];

                } else{

                  $repStack['exportHide'] = 'hidden';
                  $repStack['flair'] = 'label-success';
                  $repStack['status'] = 'Geauthoriseerd';
                  $repStack['expected_date'] = date('d-m-Y', $project['auth_on']);
                }

                $repStack['expected_title'] = 'Afgerond';

                $repStack['progressHide'] = 'hidden';
                $percent = 100;
            } else{
                $repStack['dataDumpHide'] = 'hidden';
            }

            $repStack['percent'] = $percent;
            $repStack['client_id'] = $project['client'];

            if( filter_var($pending, FILTER_VALIDATE_BOOLEAN) == True ){
              $table .= generateHTML('projects/projectRowPending', $repStack);
            } else {
              $table .= generateHTML('projects/projectRow', $repStack);
            }
            //$table .= generateHTML('projects/projectRow', $repStack);
            //store last id
            $lastId = $project['id'];
        }

        //check if any projects were loaded. if not
        print json_encode(array('table_html' => $table, 'last_id' => $lastId), JSON_FORCE_OBJECT);
    }

    function viewPending(){ 

        $pending_pprofiles = upa('cvars', 'grabCvar', array('pending_pprofiles'), False);
        
        $pprofiles = ($pending_pprofiles) ? $pending_pprofiles : "pprofile_1: 1, pprofile_4: 4,";   

        $this->_template->set('pprofiles', $pprofiles);        

    }

    public function runningMarkup($timestamp, $holidays)
    {           


        //make a new date object 
        $predictedEnd = new DateTime();
        $predictedEnd->setTimestamp($timestamp);

        //set time to 12:00
        $predictedEnd->setTime(12, 0, 0);


        //get the current date
        $now = new DateTime();
        $now->setTime(12, 0, 0);

        //is the predicted end date in the future?
        if($predictedEnd >= $now)
        {
            return false; 
        } 

     
    
        //loop through the days from the predictedEnd to the current date
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($predictedEnd, $interval ,$now);

        $daysPassed = 0; 

        foreach($daterange as $date)
        {
            
                   
            //is this day a weekend day?
            if($date->format('N') >= 6 || $holidays->isHoliday($date->format('d-m-Y')))
            {
                //skip this day
                continue;
            }

            $daysPassed++;

        }

      

        if($daysPassed <= 3)
        {
            return 'orange';
        }


        if($daysPassed > 3)
        {
            return 'red';
        }
                                
    }

    function viewAuthorised(){ }
    function viewRunning(){ }
    function viewCompleted(){ }
    function viewReported(){}
    function viewBlocked(){}

    function loadProgressWidget($limit = 5){

        $this->Project->where('auth_status', '0');
        //$this->Project->order('')

    }


    function loadAuthProjects(){

        $this->render = False;

        $this->Project->where('auth_status', 1);
        $loadFrom = $_POST['loadFrom'];
        $limit = 20;

        if($loadFrom != False && $loadFrom != 'false'){
            $this->Project->lessThan('id', $loadFrom - 1);
        }

        $this->Project->order('id', 'DESC');
        $this->Project->limit($limit);
        $results = $this->Project->search();
        $n = count($results);

        $retObj = array();
        $retObj['html'] = '';

        foreach($results as $aProject){
            $cInfo = upa('clients', 'fetch', array($aProject['client']));
            $render = array_merge($cInfo, $aProject);
            $render['auth_on'] = date('d-m-Y', $aProject['auth_on']);
            $render['LB'] = ALPC_BASEPATH;
            $render['auth_by_user'] = getUserName($aProject['auth_by']);
            $retObj['html'] .=  generateHTML('projects/authProject', $render);
            $retObj['lastId'] = $aProject['id'];
        }

        if($n < $limit ){
            $retObj['lastPage'] = True;
        } else{
            $retObj['lastPage'] = False;
        }

        print json_encode($retObj, JSON_FORCE_OBJECT);
    }

    function checkProjectComplete($projectId){

        $this->render = False;

        $stampToDate = function($value) {
            if($value !== null && !empty($value)){
                return date('Ymd', $value);         
            } else{
                return False;
            }
            
        };
        

        //get all SAIDS for project, and check fi they are completed
        $samples = upa('samples', 'fetchSamplesInProject', array($projectId), False);
        $complete = True;
        $resultsComplete = True;
        $detailsComplete = True;
        $innocDateValid = True;
        
        foreach($samples as $pSample){
        
            $sCompleted = upa('samples', 'checkSampleComplete', array($pSample['id']), False);

            if($sCompleted == False){
                $resultsComplete = False;
            }

            $customFields = json_decode($pSample['custom_fields'], JSON_FORCE_OBJECT);
            if(array_key_exists('details', $customFields)){
                if($customFields['details'] == ''){
                    $detailsComplete = False;
                }
            }
        }

        $innocDates = array_column($samples, 'sample_innoculated');
        $innocDays = array_map($stampToDate, $innocDates);                        
        $innocDays = array_unique($innocDays);        
        
        if(count($innocDays) > 1 || in_array(False, $innocDays)){            
            $innocDateValid = False;
        }

        if($resultsComplete == False || $detailsComplete == False || count($innocDays) > 1 || in_array(False, $innocDays)){
            $complete = False;
        }

        print json_encode(array('project_complete' => $complete, 
        'results_complete' => $resultsComplete,
         'details_complete' => $detailsComplete, 
         'innoc_date_valid' => $innocDateValid), JSON_FORCE_OBJECT);

    }

    function upProjectPrintVersion($projectId){

        $this->render = False;
        $this->Project->where('id', $projectId);
        $result = $this->Project->search();

        if(!empty($result)){
          //only up print version if it was authorised
          if($result['0']['auth_status'] == 1){
            $this->Project->id = $result['0']['id'];
            $this->Project->print_version = $result['0']['print_version'] + 1;
            $this->Project->save();
          }
        } else{
          writeLog('Could not find specified project', ALPC_ERROR);
        }

    }

    function setProjectEndPoint($projectID, $endPoint){
        $this->Project->free();
        $this->Project->id = $projectID;
        $this->Project->predicted_end = $endPoint;
        $this->Project->save();
    }

    function updateEndPoints($projectID){

        //should check all samples and return the highest
        $samples = upa('samples', 'fetchSamplesInProject', array($projectID), False);
        $endPoints = array('0');

        foreach($samples as $pSample){
            array_push($endPoints, $pSample['predicted_end']);
        }

        $endPoint = max($endPoints);
        $minPoint = min($endPoints);

        if($minPoint == -1){
          $this->setProjectEndPoint($projectID, -1);
        }else{
          if ($endPoint == 0) {
              $this->setProjectEndPoint($projectID, time());
          } else {
              $this->setProjectEndPoint($projectID, $endPoint);
          }
        }
    }

    function checkAllProjects(){
        $projects = $this->Project->search();
        foreach($projects as $project){

            $samples = upa('samples', 'fetchSamplesInProject', array($project['id']), False);
            foreach($samples as $pSample){
                upa('samples', 'estimateEndPoints', array($pSample['id']), False);
            }

            $this->updateEndPoints($project['id']);
        }

    }

    function getProjectsInRoughDate($start, $end, $field){

        $yearEnd = $end + ( 365 * 82800 );
        $yearStart = $start -  ( 365 * 82800 ) ;

        $this->Project->greaterThan('project_date', $yearStart);
        $this->Project->lessThan('project_date', $yearEnd);

        $preSelection = $this->Project->search();
        $selectedProjects = array();

        foreach($preSelection as $preSelectedProject){
            $custom_fields = json_decode($preSelectedProject['custom_fields'], JSON_FORCE_OBJECT);
            if(array_key_exists($field, $custom_fields)){
                $dateForPoject = strtotime($custom_fields[$field]);

                if($dateForPoject >= $start && $dateForPoject <= $end){
                    array_push($selectedProjects, $preSelectedProject);
                }
            }
        }

        return $selectedProjects;
    }

    function setProjectReadyFlag($project, $flag){
        $this->Project->where('id', $project);
        $result = $this->Project->search();

        if(!empty($result)){

          $previousSetting = filter_var($result[0]['is_ready'], FILTER_VALIDATE_BOOLEAN);

          //check if it had the correct setting already, nothing changed!
          if($flag === True && $previousSetting === True){
            return;
          }

          if($flag === False && $previousSetting === False){
            return;
          }

          $this->Project->deepFreed();
          $this->Project->id = $project;
          $this->Project->is_ready = $flag;
          $this->Project->became_ready_on = time();
          $this->Project->save();
        }
        return;
    }

    function listAllClientProjects($clientId){

      $this->Project->order('id', 'DESC');
      $this->Project->where('client', $clientId);
      $projects = $this->Project->search();

      $normalIcon = ALPC_BASEPATH .'/public/img/normal.png';
      $legIcon = ALPC_BASEPATH .'/public/img/leg.png';
      $rodacIcon = ALPC_BASEPATH .'/public/img/rodac.png';

      $table = '';

      foreach($projects as $project){

          $repStack = array();
          $repStack['client'] = upa('clients', 'clientIdToName', array($project['client']));
          $repStack['reference'] = $project['reference'];
          $repStack['added'] = date('d-m-Y', $project['project_date']);
          $repStack['added_by'] = userIdToName($project['added_by']);
          $repStack['id'] = $project['id'];
          $repStack['LB'] = ALPC_BASEPATH;

          $repStack['gereed'] = 'NVT';
          $repStack['geauth'] = 'NVT';
          $repStack['gerap'] = 'NVT';


          if($project['special_type'] == '1' ){
            $repStack['sample_flags'] = '<img src="' . $legIcon . '"/>';
          }

          if($project['special_type']  == '0'){
            $repStack['sample_flags'] = '<img src="' . $normalIcon . '"/>';
          }

          if($project['special_type']  == '2'){
            $repStack['sample_flags'] = '<img src="' . $rodacIcon . '"/>';
          }

          if($project['auth_status'] == '0'){

            if($project['started'] == '0'){
                $repStack['flair'] = 'label-inverse';
                $repStack['status'] = 'Ontvangen';
            }

            if($project['started'] == '1' && $project['is_ready'] == '0'){
                $repStack['flair'] = 'label-info';
                $repStack['status'] = 'Lopend';
                $repStack['gereed'] = date('d-m-Y', $project['predicted_end']);
            }

            if($project['started'] == '1' && $project['is_ready'] == '1'){
                $repStack['flair'] = 'label-warning';
                $repStack['status'] = 'Afgerond';
                $repStack['gereed'] = date('d-m-Y', $project['predicted_end']);
            }

          }

          if($project['auth_status'] == '1'){

             if($project['rap_stat'] == '1'){
               $repStack['flair'] = 'label';
               $repStack['status'] = 'PDF gegenereerd';
               $repStack['gerap'] = date('d-m-Y', $project['rap_on']);
               $repStack['geauth'] = date('d-m-Y', $project['auth_on']);

             } else{
               $repStack['flair'] = 'label-success';
               $repStack['status'] = 'Geauthoriseerd';
               $repStack['geauth'] = date('d-m-Y', $project['auth_on']);
             }
         }

          $repStack['client_id'] = $project['client'];
          $table .= generateHTML('projects/projectAllListRow', $repStack);
        }

        $this->_template->set('table', $table);
    }

    function dataMiningGrab($client, $rangeStart, $rangeEnd, $special = 'all', $id = false, $scopeSelector){

        $params = array();


        cphp('scopeselector' . $scopeSelector);
        cphp('$client' . $client );
        cphp('$rangeStart' . $rangeStart );
        cphp('$rangeEnd' .  $rangeEnd );
        cphp('$special' . $special );
        cphp('$id' . $id );

        if($special === 'id'){
            $sql = "SELECT * FROM `projects` WHERE  id = :id";
            $params['id'] = $id;
        } else{
            $rangeStart = strtotime($rangeStart);
            $rangeEnd = strtotime($rangeEnd);

            $sql = "SELECT * FROM `projects` WHERE client = :client AND auth_status = :auth_status ";


            if($special === 'all'){
                //$sql .= "AND special_type = :special_type";
                //$params['special_type'] = '0';
            } else{
                $sql .= "AND special_type = :special_type";
                $params['special_type'] = $special;
            }

            $params['client'] = $client;
            $params['auth_status'] = 1;
        }

        cphp('Project sql:' . $sql);
        cphp($params);

        $resultsProjects = $this->Project->customQuery($sql, $params);


        //track down range
        $resultsReturn = array();
        foreach($resultsProjects as $idx => $project){

            $details = json_decode($project['custom_fields'], JSON_FORCE_OBJECT);
            //$samplingDate = (array_key_exists('project_monster', $details) && $details['project_monster'] != 'Onbekend') ? $details['project_monster']: false;

            $checkTime = False;

            if($scopeSelector === 'date'){
                $checkTime = (array_key_exists('project_monster', $details) && $details['project_monster'] != 'Onbekend') ? $details['project_monster']: false;
            }

            if($scopeSelector === 'date_arrival'){
                $checkTime = (array_key_exists('project_ontvangst', $details) && $details['project_ontvangst'] != 'Onbekend') ? $details['project_ontvangst']: false;
            }


            if($checkTime === False && $special !== 'id'){
                #unset($resultsProjects[$idx]);
                continue;
            } else{
                $projectTime = strtotime($checkTime);

                if(($projectTime < $rangeStart || $projectTime > $rangeEnd) && $special !== 'id' ){
                    #unset($resultsProjects[$idx]);
                    continue;
                } else{
                    $resultsReturn[$project['id']] = $project;
                    $customFields = json_decode($project['custom_fields'], JSON_FORCE_OBJECT);
                    $projectExtra = json_decode($project['project_extra'], JSON_FORCE_OBJECT);
                    $resultsReturn[$project['id']]['custom_fields'] = $customFields;
                    $resultsReturn[$project['id']]['project_extra'] = $projectExtra;

                }
            }
        }

        return $resultsReturn;
    }

    function setSamplingDate($id, $date){
        $this->Project->id = $id;
        $result = $this->Project->search();
        $this->Project->deepFreed();
        if(!empty($result)){
            $result = $result[0];
            $customFields = json_decode($result['custom_fields'], JSON_FORCE_OBJECT);
            $customFields['project_monster'] = $date;
            $customFields = json_encode($customFields, JSON_FORCE_OBJECT);
            $this->Project->id = $id;
            $this->Project->custom_fields = $customFields;
            $this->Project->save();
        }
    }


    function setReceivedDateAndTime($id, $date, $time){
        $this->Project->id = $id;
        $result = $this->Project->search();
        $this->Project->deepFreed();
        if(!empty($result)){
            $result = $result[0];
            $customFields = json_decode($result['custom_fields'], JSON_FORCE_OBJECT);
            $customFields['project_ontvangst'] = $date;
            $customFields['project_tijd_ontvangst'] = $time;
            $customFields = json_encode($customFields, JSON_FORCE_OBJECT);
            $this->Project->id = $id;
            $this->Project->custom_fields = $customFields;
            $this->Project->save();
        }
    }    

    public function orderForm($portalProjectId)
    {
        $this->render = False; 
        
        $json = upa('portal', 'getOrderForm', array($portalProjectId), False);     

        $response = json_decode($json);

        if($response->status == '404')
        {
            print('Order had geen opgeslagen order-formulier');
            return;
        }

        if($response->status == '422')
        {
            print('Geen order-formulier beschikbaar, deze order was niet door een klant aangepaakt');
            return;
        }

        header('Content-Type: application/pdf');
        
        print(base64_decode($response->pdf));
    }

    public function changeClientForProject()
    {
        $this->render = False; 

        $targetClient = (int)$_POST['targetClient'];
        $projectId = (int)$_POST['projectId'];

        if(empty($targetClient) || empty($projectId))
        {
            print json_encode(['status' => 'ok']);
            return;
        }

        $projects = [];
        $portalProjectId = null; 

        $project = new Project();
        $project->id = $projectId;
        $project = $project->first();

        if(!$project)
        {
            print json_encode(['status' => 'error']);
            return;
        }

        //don't need to do anything if for some reason the same client was selected
        if($project['client'] == $targetClient )
        {
            print json_encode('ok');
            return;
        }

        array_push($projects, $project);
        

        //does this project have a portal_id
        if($project['portal_id'])
        {
                
            $portalProjectId = $project['portal_id'];

            $allProjects = new Project();            
            $allProjects->where('portal_id', $project['portal_id']);                
            $allProjects->where('client', $project['client']);
            $allProjects->whereNot('id', $projectId);
            $allProjects = $allProjects->search();


            foreach($allProjects as $thisProject)
            {                             
                array_push($projects, $thisProject);
            }
           
        }        



        foreach($projects as $project)
        {

            //switch the the found id's 
            $projectId = $project['id'];
                                      
            $event = 'Project ' . $project['reference'] . ' van klant gewijzigd';
            upa('changeTracker', 'changed', array(24,  $project['id'], False, False, $event, $project['client'], $targetClient), False);
                    

            $project = new Project();
            $project->id = $projectId;
            $project->client = $targetClient;
            $project->portal_id = '%null%'; 
            $project->save();


            $sampleObj = new Sample();
            $sampleObj->select(['id']);
            $sampleObj->where('project', $projectId);
            $samples = $sampleObj->search();

            //get id's
            $sampleIds = array_column($samples, 'id');

            //update all samples 
            $defaultGroupForTarget = upa('productGroups', 'getClientDefaultGroup', array($targetClient), False);
            $defaultGroupForTarget = ($defaultGroupForTarget === false) ? null : $defaultGroupForTarget['portal_id'];

            $sql = 'UPDATE samples SET `client` = :client, `portal_sample_id` = NULL, `portal_project_id` = NULL, `portal_product_group_id` = :default WHERE project = :project';
            $params = ['client' => $targetClient, 'project' => $projectId, 'default' => $defaultGroupForTarget];
            $sampleObj->customSetQuery($sql, $params);

            
            //reset all meta_data_key_ids to NULL, so the portal can deal with the issue 
            $metaDataObj = new Metadata();    
            $sql = 'UPDATE metadata SET meta_data_key_id = NULL where `sample` IN (' . implode(',', $sampleIds) . ')';
            $metaDataObj->customSetQuery($sql, []);

            //load in all reference sources
            $refSourceObj = new ReferenceSource; 
            $referenceSources = $refSourceObj->search();        
            $referenceSources = array_column($referenceSources, 'client', 'id');        

            //select roaming and profiled analyses 
            $sql = 'SELECT * FROM sampleanalysis WHERE sample IN (' . implode(',', $sampleIds) . ') AND (roaming_id IS NOT NULL AND roaming_id != 0)';
            $sampleAnalysisObj = new SampleAnalysis();
            $roaming = $sampleAnalysisObj->customQuery($sql, []);
            $roamingIds = array_column($roaming, 'roaming_id');
                
            //roaming rows
            if(count($roamingIds) > 0)
            {
                $roamingAssayObj = new RoamingAnalysis();
                $sql = 'SELECT * FROM roaminganalysis WHERE id IN (' . implode(',', $roamingIds) . ')';
                $roamingAssays = $roamingAssayObj->customQuery($sql, []);
        
                foreach($roamingAssays as $roamingAssayRow)
                {            
        
                    if($roamingAssayRow['reference_source'] != '0')
                    {
                                    
                        $referenceSourceClient = (array_key_exists($roamingAssayRow['reference_source'], $referenceSources)) ? $referenceSources[$roamingAssayRow['reference_source']] : null;
        
                        if($referenceSourceClient != null && $referenceSourceClient != '0')
                        {                
                            //need to remove this reference source as it is tied to previous client 
                            $roamingAssayUpdate = new RoamingAnalysis();
                            $roamingAssayUpdate->id = $roamingAssayRow['id'];
                            $roamingAssayUpdate->reference_source = 0;
                            $roamingAssayUpdate->reference = '{"ref_kve":""}';
                            $roamingAssayUpdate->save();                    
                        }
        
                    }   
                    
                }
            }


            //research profiles         
            $sql = 'SELECT * FROM sampleanalysis WHERE sample IN (' . implode(',', $sampleIds) . ') AND (roaming_id IS NULL or roaming_id = 0)';
            $assaysInProfile = $sampleAnalysisObj->customQuery($sql, []);
            
            $profileIds = array_column($assaysInProfile, 'profile');

            if(count($profileIds) > 0)
            {
                
                $researchProfileObj = new ResearchProfile();
                $sql = 'SELECT * FROM researchprofiles WHERE id IN (' . implode(',', $profileIds) . ')';        
                $researchProfiles = $researchProfileObj->customQuery($sql, []);
                $researchProfiles = array_column($researchProfiles, null, 'id');
        
                
                //get the research assayprofiles
                $assayProfilesObj = new AssayProfile();
                $sql = 'SELECT * FROM assayprofiles WHERE research_profile IN (' . implode(',', $profileIds) . ')';
                $assayProfiles = $assayProfilesObj->customQuery($sql, []);        
                $assayProfiles = array_column($assayProfiles, null, 'id');
                
                foreach($assaysInProfile as $assayInProfile)
                {
        
                    //check if the used profile is a global one
                    $public = (int)checkKeyOrFalse($researchProfiles, $assayInProfile['profile'], 'global');
                    $owner = (int)checkKeyOrFalse($researchProfiles, $assayInProfile['profile'], 'client');
        
                    
        
                    //tied to a customer, need to bump this analysis to a roaming analysis
                    if($public == 0  && $targetClient != $owner )
                    {
        
                        //grab the assayprofile from the profile
                        $assayProfile = checkKeyOrFalse($assayProfiles, $assayInProfile['assay']);
        
                    
                        
                        $newRoam = new RoamingAnalysis();
                        $newRoam->said = $assayInProfile['id'];
                        $newRoam->assay = $assayInProfile['assay_base'];
                        $newRoam->dillutions = $assayProfile['dillutions'];
                        $newRoam->replicates =  $assayProfile['replicates'];
        
                        //check if reference is global?
        
                        $referenceSourceClient = (array_key_exists($assayProfile['reference_source'], $referenceSources)) ? $referenceSources[$assayProfile['reference_source']] : null;
        
                        if($referenceSourceClient != null && $referenceSourceClient != '0')
                        {                
                            $newRoam->reference =  '{"ref_kve":""}';
                            $newRoam->reference_scope =  '';
                            $newRoam->reference_source =  null;
                        }
        
                        else
                        {
                            $newRoam->reference =   $assayProfile['reference'];
                            $newRoam->reference_scope = '';
                            $newRoam->reference_source =  $assayProfile['reference_source'];
                        }
                                        
                        $newRoam->save();
                        $insertId = $newRoam->lastInsertId;
        
        
                        $sampleAnalysisObj = new SampleAnalysis();
                        $sampleAnalysisObj->id = $assayInProfile['id'];
                        $sampleAnalysisObj->profile = '0';
                        $sampleAnalysisObj->assay = $assayInProfile['assay_base'];
                        $sampleAnalysisObj->assay_base = $assayInProfile['assay_base'];
                        $sampleAnalysisObj->roaming_id = $insertId;
                        $sampleAnalysisObj->save();                
                    
                    }
                
                    
                }
            }
                    

            $exportObj = new Export();
            $exportObj->where('project', $projectId);
            $exports = $exportObj->search();
                
            foreach($exports as $export)
            {                
                $thisExport = new Export();
                $thisExport->id = $export['id'];
                $thisExport->client = $targetClient;
                $thisExport->save();
            }
        
        }

        if($portalProjectId != null)
        {

            $mesaProjectIds = array_column($projects, 'id');

            upa('portal', 'swapProjectClient', array($portalProjectId, $mesaProjectIds), False);            
        }

        
                                    
        print json_encode(['status' => 'ok']);

    }

    public function createProjectTableLine($projectId)
    {
        $listItemHTML = '';

        $project = new Project();
        $project->where('id', $projectId);
        $project = $project->first();
        
        $project['real_date'] = date('d-m-Y',$project['project_date']);

        $project['bemonster'] = peekIntoJSON($project['custom_fields'], 'project_monster');                
        $project['ontvangst'] = peekIntoJSON($project['custom_fields'], 'project_ontvangst');                              
        $project['innoc'] = upa('samples', 'getProjectInnoculationDate', array($project['id']));                     
        $project['no_samples'] = $this->countSamplesInProject($project['id']);
        
        $project['billing'] = 'hidden';
      
    
        $statusLabel = $this->statusLabel($project);
        $project['status'] = $statusLabel['status'];
        $project['flair'] = $statusLabel['flair'];


        $project['client_name_short'] = '';
   
        if($project['auth_status'] == '0'){
            $project['auth_show'] = 'hide';
        }

        if($project['auth_status'] == '1'){
            $project['auth_show'] = '';
        }

        $listItemHTML .= generateHTML('clientProjectItemBilling', $project);
    
        return $listItemHTML;

    }

}
