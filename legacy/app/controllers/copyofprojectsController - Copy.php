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

    public function clientProjectsList($clientId, $subClient, $timeSpan = False, $timeSpanEnd= False, $sample= False, $reference = False){

        $useClientNames = False;
        $this->render = 0;

        //for seaching per client with a timeframe limit
        if($timeSpan != False && $timeSpanEnd == False){

            $jsonObj = array();
            $jsonObj['client_name'] = pa('clients', 'clientIdToName', array($clientId));
            if($timeSpan == 'recent'){
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

                    //$project['subclient_name'] = pa('subClients', 'subclientIdToName', array($project['subclient']));
                $project['real_date'] = date('d-m-Y',$project['project_date']);
                $project['no_samples'] = $this->countSamplesInProject($project['id']);

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

        print json_encode($jsonObj, JSON_FORCE_OBJECT);

    }

    function authoriseProject($projectId){

        $this->render = 0;
        $this->Project->where('id', $projectId);
        $result = $this->Project->search();

        if(empty($result)){
            return;
        }

        //Only run if the project was not authorised
        if($result[0]['auth_status'] == 0){
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
          upa('portal', 'projectAuthorized', array($projectId), False);

        }
        //create a hardcopy
        //upa('exports', 'projectReport', array($projectId, 'MAZ',  5, False, True));

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
          upa('portal', 'projectDeauthorized', array($projectId), False);
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

        $lteArray = array('project_name' => $projectName, 'client_name' => customerIdToName($client), 'project_id' => $this->Project->lastInsertId, 'client_id' => $this->Project->client );
        upa('labtalkEvents', 'automation', array('PROJECTADD', $lteArray), 0);

        //upa('revisions', 'registerRevision', array('SCOPE_PROJECT_START', $this->Project->lastInsertId), False);

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

        if($entryProject != 'null'){
            $this->Project->where('id', $entryProject);
            $results = $this->Project->search();

            if(!empty($results)){
                $clientName = pa('clients', 'clientIdToName', array($results['0']['client']) );
                $entryClient = $results['0']['client'];
                //$entrySubClient = $results['0']['subclient'];
            }
        }

        $projectFields = pa('projectFields', 'renderProjectFields', array(True));
        $projectNotes = pa('projectNotes', 'listNote', array(True));

        $this->_template->set('project_fields', $projectFields);
        $this->_template->set('entry_project', $entryProject);
        $this->_template->set('entry_client', $entryClient);
        //$this->_template->set('entry_subclient', $entrySubClient);
        $this->_template->set('entry_client_name', $clientName);
        $this->_template->set('project_notes', $projectNotes);
        
        $sampleNoteField =  generateHTML('customFields/notes', array());
        
        $this->_template->set('sample_notes', $sampleNoteField);
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
        $samples = upa('samples', 'projectSamples', array($projectId));


        if(empty($samples)){
            $list .= generateHTML('alertWarning', array('alert_title' => '{MESA_PLU_PROJECTEMPTY}', 'alert_message' => '{MESA_PLU_PROJECTEMPTYEXPLANATION}' ));
        }

        else {
            foreach($samples as $sample){

                $isReady = upa('samples', 'checkSampleComplete', array($sample['id']), False);                
                $sample['note_found'] = (empty($sample['sample_note'])) ? '' : '!';

                if($isReady){
                  $sample['isReady'] = '';
                } else{
                  $sample['isReady'] = 'hidden';
                }

                

                $list .= generateHTML('projectSampleLine', $sample);
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

        upa('samples', 'removeProjectSamples', array($projectId), False);
        upa('revisions', 'removeProjectRevisions', array($projectId), False);
        upa('bookmarks', 'removeProjectBookmarks', array($projectId), False);
        upa('exports', 'removeSavedProjects', array($projectId), False);
    }

    function updateProjectField($projectId, $field, $value){

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


        }


        //print out
        print json_encode($return, JSON_FORCE_OBJECT);
    }

    function getProjectInformation($project){
      $this->render = 0;

      //release previous lock
      upa('keyrings', 'removeOwnLock', array('PROJECT', $_POST['previous_project']));

      $this->Project->where('id', $project);
      $results = $this->Project->search();

      if(!empty($results)){
        $lockObj = upa('keyrings', 'requestLockAndStatus', array('PROJECT', $project));
        $retObj['project_block'] = upa('samples', 'buildProjectInfoBlock', array(False, $project));

        #$retObj['project_notes'] = $results['0']['project_notes'];


        $projectSamples = upa('projects', 'loadProjectSamples', array($project));

        $retObj['sample_block'] = $projectSamples['render'];
        $retObj['project_auth'] = $results['0']['auth_status'];
        $retObj['lock_object'] = $lockObj;
        $retObj['following_project'] = upa('bookmarks', 'isFollowing', array($project, 'PROJECT') );

        //set project wide notes as active, as default
        $projectNoteArray = $this->loadInNotesArray($results[0]['project_notes']);

        //set dropdown options
        $found = (array_key_exists('project', $projectNoteArray) ? '!' : ' ');
        $sampleDropdown = '<option value="project" selected="selected">' .  $found . ' Algemene opmerkingen</option>';
        $pSamples = $projectSamples['samples'] ;

        foreach($pSamples as $sample){
            $found = (array_key_exists($sample['id'], $projectNoteArray) ? '!' : ' ');
            $sampleDropdown .= '<option value="' . $sample['id'] . '">' .  $found . '[' . $sample['barcode'] . '] ' . $sample['description'] . '</option>';
        }

        $retObj['notes_present'] = False;
        if(!empty($projectNoteArray)){
            $retObj['notes_present'] = True;
        }


        $retObj['project_notes'] = checkKeyOrBlank($projectNoteArray, 'project');        
        $retObj['project_samples_dropdown'] = $sampleDropdown;

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
        $result = $this->Project->search();
        $pending = count($result);
        $this->Project->deepFreed();

        //not authorised, not ready
        $this->Project->where('auth_status', 0);
        $this->Project->where('is_ready', 0);
        $this->Project->where('started', 1);
        $result = $this->Project->search();
        $open = count($result);

        //$this->Project->greaterThan('predicted_end', $currentTime );
        //$this->Project->lessThan('predicted_end', $currentTime );
        $this->Project->deepFreed();

        $this->Project->where('auth_status', 0);
        $this->Project->where('started', 1);
        $this->Project->where('is_ready', 1);
        $result = $this->Project->search();
        $waiting = count($result);

        $this->Project->deepFreed();

        $this->Project->where('auth_status', 1);
        $this->Project->where('started', 1);
        $this->Project->where('rap_stat', 0);
        $result = $this->Project->search();
        $rapWaiting = count($result);

        //inject sample count, and buffer count here
        $samplesOpen = upa('samples', 'preciseCountEmptys', array(), False);
        $bufferOpen = upa('sampleBuffers', 'countBuffer', array(), False);
        $thtOpen = upa('sampleBuffers', 'countTHT', array(), False);
        print json_encode(array('open'=> $open, 'waiting'=> $waiting, 'rap_waiting' => $rapWaiting, 'samples_open' => $samplesOpen, 'pending' => $pending, 'buffer' => $bufferOpen, 'tht_open' => $thtOpen), JSON_FORCE_OBJECT);
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

    function removeExportFlag($projectId){
      $this->Project->id = $projectId;
      $this->Project->rap_stat = 0;
      //$this->Project->rap_by = NULL;
      //$this->Project->rap_on = NULL;
      //$this->Project->rap_rev = NULL;
      $this->Project->save();
    }

    function overviewLoader($skip = 0, $running = True, $auth = False, $reported = False, $pending = False){

        $this->render = False;
        $currentTime = time();
        $lastId = null;

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
        if( filter_var($running, FILTER_VALIDATE_BOOLEAN) == False && filter_var($auth, FILTER_VALIDATE_BOOLEAN) == False ){
            //grab auth_status 0
            //select samples, check if all are isReady
            //if so, add to this list.
            $this->Project->order('became_ready_on', 'ASC');
            $this->Project->where('auth_status', 0);
            $this->Project->where('is_ready', '1');
            $this->Project->where('started', 1);
            //$this->Project->lessThan('predicted_end', $currentTime );
        }

        //$this->Project->order('auth_status', 'ASC');
        if($skip != 0 ){
            $this->Project->limit($skip . ',50' );
        } else{
            $this->Project->limit(50);
        }

        $projects = $this->Project->search();
        $table = '';

        foreach($projects as $project){

            $repStack = array();
            $repStack['client'] = upa('clients', 'clientIdToName', array($project['client']));
            $repStack['reference'] = $project['reference'];
            $repStack['added'] = date('d-m-Y', $project['project_date']);
            $repStack['added_by'] = userIdToName($project['added_by']);
            $repStack['id'] = $project['id'];
            $repStack['LB'] = ALPC_BASEPATH;

            //$repStack['samples_in_project'] = upa('samples', 'countSamplesInProject', array($project['id']), False);
            $info = upa('samples', 'countSamplesAndTypesInProject', array($project['id'], False));

            $repStack['samples_in_project'] = $info['count'];

            //$repStack['leg_sample_show'] = 'hidden';
            //$repStack['normal_sample_show'] = 'hidden';

            $normalIcon = ALPC_BASEPATH .'/public/img/normal.png';
            $legIcon = ALPC_BASEPATH .'/public/img/leg.png';
            $rodacIcon = ALPC_BASEPATH .'/public/img/rodac.png';

            $repStack['sample_flags'] = '';

            if($info['leg'] == True){
              $repStack['sample_flags'] .= '<img src="' . $legIcon . '"/>';
            }

            if($info['normal'] == True){
              $repStack['sample_flags'] .= '<img src="' . $normalIcon . '"/>';
            }

            if($info['rodac'] == True){
              $repStack['sample_flags'] .= '<img src="' . $rodacIcon . '"/>';
            }


            if($project['auth_status'] == '0'){

                $repStack['exportHide'] = 'hidden';

                if($running == False){
                    $repStack['flair'] = 'label-warning';
                    $repStack['status'] = 'Afgerond';
                    $repStack['progressHide'] = 'hidden';

                } else{


                    if($pending == True){
                      $repStack['status'] = 'Ontvangen';
                      $repStack['flair'] = 'label-inverse';
                    } else{
                      $repStack['status'] = 'Lopend';
                      $repStack['flair'] = 'label-info';
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
                  //$repStack['expected_date'] = date('d-m-Y', $project['became_ready_on']);

                    $received = peekIntoJSON($project['custom_fields'], 'project_ontvangst');
                    $becameReady = date('d-m-Y', $project['became_ready_on']);

                  $repStack['expected_date'] = $received  . ' | ' . $becameReady;

                } else{
                  if($project['predicted_end'] == -1){
                    $repStack['expected_date'] = 'Nog niet bekend';
                  }else{
                    $repStack['expected_date'] = date('d-m-Y', $project['predicted_end']);
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
                  $repStack['exported_by'] = upa('profiles', 'getUserFullName', array($project['rap_by']));

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

    function viewPending(){ }
    function viewAuthorised(){ }
    function viewRunning(){ }
    function viewCompleted(){ }
    function viewReported(){}



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

        //get all SAIDS for project, and check fi they are completed
        $samples = upa('samples', 'fetchSamplesInProject', array($projectId), False);
        $complete = True;
        $resultsComplete = True;
        $detailsComplete = True;

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

        if($resultsComplete == False || $detailsComplete == False){
            $complete = False;
        }


         print json_encode(array('project_complete' => $complete, 'results_complete' => $resultsComplete, 'details_complete' => $detailsComplete), JSON_FORCE_OBJECT);

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

}
