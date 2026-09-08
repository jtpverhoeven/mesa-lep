<?PHP

class sampleAnalysisController extends Controller{

  function retrieveStoredResult($said){
      $this->SampleAnalysis->where('id', $said);
      $result = $this->SampleAnalysis->search();

      if(empty($result)){
        return False;
      } else{
        $storedResult = $result[0]['storedResult'];
        if(empty($storedResult)){
          return False;
        } else{
          $storedArray = json_decode($storedResult, JSON_FORCE_OBJECT);
          if(empty($storedArray)){
            return false;
          } else{
            return $storedArray;
          }
        }
      }
  }

  function setStoredResult($said, $array){
    $this->render = False;
    $storedArray = json_encode($array, JSON_FORCE_OBJECT);
    $this->SampleAnalysis->id = $said;
    $this->SampleAnalysis->storedResult = $storedArray;
    $this->SampleAnalysis->save();
  }

  function removeStoredResults($project){
    $this->render = False;

    $this->SampleAnalysis->where('project', $project);
    $results = $this->SampleAnalysis->search();

    foreach($results as $saidRow){
      $this->SampleAnalysis->deepFreed();
      $this->SampleAnalysis->id = $saidRow['id'];
      $this->SampleAnalysis->storedResult = '';
      $this->SampleAnalysis->save();
    }
  }

  function touchUpStoredResults($project){
    $this->render = False;
    $this->SampleAnalysis->where('project', $project);
    $results = $this->SampleAnalysis->search();
    foreach($results as $saidRow){
      upa('results', 'msProcess', array($saidRow['id']), False);
    }
  }

  function countAnalysis($sample){
    $this->SampleAnalysis->where('sample', $sample);
    $results = $this->SampleAnalysis->search();

    return count($results);
  }

  function reorderOrderList($sample){

    $this->render = False;

    $this->SampleAnalysis->where('sample', $sample);
    $this->SampleAnalysis->order('project_order', 'ASC');
    $results = $this->SampleAnalysis->search();
    $i = 1;

    foreach($results as $said){
      $this->SampleAnalysis->id = $said['id'];
      $this->SampleAnalysis->project_order = $i;
      $this->SampleAnalysis->save();
      $this->SampleAnalysis->deepFreed();
      $i++;
    }

  }

  function getLastOrderNumber($project){

    $this->SampleAnalysis->where('project', $project);
    $this->SampleAnalysis->order('project_order', 'DESC');
    $this->SampleAnalysis->limit(1);
    $result = $this->SampleAnalysis->search();

    if(empty($result)){
      $lastOrderNumber = 0;
    } else{
      $lastOrderNumber = $result[0]['project_order'];
    }

    return $lastOrderNumber;

  }

  function saveAnalysisOrder($sample){
    $this->render = False;
    if(isset($_POST['sortArray'])){

      $sorterArray = json_decode($_POST['sortArray'], JSON_FORCE_OBJECT);
      $i = 1;

      foreach($sorterArray[0] as $thisSaid){
        $said = $thisSaid['id'];
        $this->SampleAnalysis->id = $said;
        $this->SampleAnalysis->project_order = $i;
        $this->SampleAnalysis->save();
        $this->SampleAnalysis->deepFreed();
        $i++;
      }
    }
  }

    function analysisOrderList($sample){
      $this->render = False;

      $this->SampleAnalysis->where('sample', $sample);
      $this->SampleAnalysis->order('project_order', 'ASC');
      $results = $this->SampleAnalysis->search();

      $analysisList = upa('assays', 'returnAll', array(), False);
      $list = array();

      foreach($results as $said){

        if(array_key_exists($said['project_order'], $list)){
          $columnIndexes = array_column($list, 'order');
          $indexForAssay = max($columnIndexes) + 1;
        } else{
          $indexForAssay = $said['project_order'];
        }

        $list[$indexForAssay] = array();
        $list[$indexForAssay]['name'] = $analysisList[$said['assay_base']]['name'];
        $list[$indexForAssay]['order'] = $indexForAssay;
        $list[$indexForAssay]['id'] = $said['id'];
      }

      $li = '<ol id="assaySorter" class="sorterList list">';

      foreach($list as $idx=>$listItem){
        $li .= '<li data-name="assay" data-id="' . $listItem['id'] .'" style="padding-top: 4px; padding-bottom: 4px;"> <i class="icon-move"></i>' . $listItem['name'] . '</li>';
      }

      $li .= '</ol>';
      print $li;

    }

    //TODO: Refactor
    function findMetaParent($profileGroup){

      $this->render = False;
      $this->SampleAnalysis->where('profile_group', $profileGroup);
      $results = $this->SampleAnalysis->search();

      //findMetaParent

      if(!empty($results)){

        //collect assays in profile
        $assays = array();
        $saidBind = array();
        foreach($results as $saidRow){
          array_push($assays, $saidRow['assay_base']);
          $saidBind[$saidRow['assay_base']] = $saidRow['id'];
        }

        $metaId = upa('assays', 'returnMetaInSet', array($assays), False);

        if($metaId != False){
          return $saidBind[$metaId];
        } else{
          return False;
        }
      }
      return False;
    }

    function getRunningConf(){
      $this->render = False;

      $this->SampleAnalysis->where('conf_requested', '1');
      $this->SampleAnalysis->where('is_ready', '0');
      $results = $this->SampleAnalysis->search();

      $confAssays = upa('assays', 'returnAllConfirmationAssays', array(), False);
      $confAssayHit = array();
      foreach($confAssays as $confAssay){
        array_push($confAssayHit, $confAssay['id']);
      }

      $returnResults = array();
      foreach($results as $result){
        $assayId = $result['assay_base'];
        if(in_array($assayId, $confAssayHit)){
          array_push($returnResults, $result);
        }
      }

      return $returnResults;

    }


    function setConfFlag($flag, $said, $clean = False){
        $this->render = False;

        if($flag === 'reset')
        {
          $confFlag = 0;
          $removeConf = True;
        }
        
        else
        {
          $flag = filter_var($flag, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

          $confFlag = 0;
          if($flag == True){
              $confFlag = 1;
              $removeConf = False;
          } else{
              $confFlag = 2;
              $removeConf = True;
          }
  
        }

        $this->SampleAnalysis->id = $said;
        $this->SampleAnalysis->conf_requested = $confFlag;
        $this->SampleAnalysis->save();

        //can we just use the msProcess to reduce overhead?
        upa('results', 'msProcess', array($said), False);

        if($removeConf === True){
          upa('confirmations', 'removeConfirmationBySA', array($said), False);
        } else{
          //update lines incase a
          upa('confirmations', 'removeConfirmationBySA', array($said), False);
        }

        //we need to return this to the GUI, to show or hide the
        //confirmation buttons now

        //also set revision
        $saInfo = upa('sampleAnalysis', 'fetch', array($said), False);
        $event = 'Bevestiging modus ingesteld';

        upa('changeTracker', 'changed', array(8, $saInfo['project'], $saInfo['sample'], $saInfo['id'], $event, False, $confFlag), False);

        //we also need to find out, if the "onderzoek" list needs Updating
        //to show the appropriate icons.
        if($clean == False){
          print json_encode(array(), JSON_FORCE_OBJECT);
        }


        return;
    }

    function setReadyFlag($flag, $said){
        $this->render = False;

        //check ok $said;

        $this->SampleAnalysis->where('id', $said);
        $results = $this->SampleAnalysis->search();

        if(!empty($results)){
          //scrub previous query
          $this->SampleAnalysis->deepFreed();
          //save project id
          $saidProjectId = $results[0]['project'];

          //set current flag
          $this->SampleAnalysis->id = $said;
          $this->SampleAnalysis->is_ready = $flag;
          $this->SampleAnalysis->save();

          //free up
          $this->SampleAnalysis->deepFreed();
          $this->SampleAnalysis->where('project', $saidProjectId);
          $foundRows = $this->SampleAnalysis->search();

          $projectComplete = True;
          foreach($foundRows as $saidRow){
            $thisStatus = $saidRow['is_ready'];
            $thisReady = filter_var($thisStatus, FILTER_VALIDATE_BOOLEAN);
            if($thisReady == False){
              $projectComplete = False;
            }
          }

          //pass cumulative said status to project
          upa('projects', 'setProjectReadyFlag', array($saidProjectId, $projectComplete), False);
        }
    }

    function checkProjectReady($projectId){

      $this->SampleAnalysis->where('project', $projectId);
      $foundRows = $this->SampleAnalysis->search();

      $projectComplete = True;
      foreach($foundRows as $saidRow){
        $thisStatus = $saidRow['is_ready'];
        $thisReady = filter_var($thisStatus, FILTER_VALIDATE_BOOLEAN);
        if($thisReady == False){
          $projectComplete = False;
        }
      }

      upa('projects', 'setProjectReadyFlag', array($projectId, $projectComplete), False);
    }


    function checkForReplacement($sample, $assayBase){

        $this->SampleAnalysis->where('sample', $sample);
        $this->SampleAnalysis->where('assay_base', $assayBase);
        $results = $this->SampleAnalysis->search();
        $this->SampleAnalysis->free();

        foreach($results as $sa){
            $this->SampleAnalysis->id = $sa['id'];
            $this->SampleAnalysis->remove();
            $this->SampleAnalysis->free();
        }
    }

    function checkProfileUsage($profile){

        $this->SampleAnalysis->where('profile', $profile);
        $results = $this->SampleAnalysis->search();
        return $results;
    }

    function removeById($id, $sampleId){
        $this->SampleAnalysis->id = $id;
        $this->SampleAnalysis->remove();
        //upa('revisions', 'removeSampleAssayRevisions', array($id), False );
        //thisis removed since we kind of want to keep these revisions here..
        upa('samples', 'checkSampleIsEmpty', array($sampleId), False);
    }

    function nextFreeGroupNumber(){

        $this->render = 0;
        $this->SampleAnalysis->order('id', 'desc');
        $this->SampleAnalysis->limit('1');
        $result = $this->SampleAnalysis->search();
        if(empty($result)){
            return 1;
        } else {
            return $result[0]['id'] + 1;
        }
    }

    function nextFreeFollowNumber($sample){

        $this->render = 0;
        $this->SampleAnalysis->where('sample', $sample );
        $this->SampleAnalysis->order('follow_number', 'desc');
        $this->SampleAnalysis->limit('1');
        $result = $this->SampleAnalysis->search();

        if(empty($result)){
            return 1;
        } else {
            return $result[0]['follow_number'] + 1;
        }
    }


    function registerSampleAnalysis($sample, $nextGroupNumber,  $followId, $profile, $assay, $roamingId, $assayBase, $project, $authStatus = 0, $authBy = 0, $order = False){

        $this->render = 0;
        $this->SampleAnalysis->sample = $sample;
        $this->SampleAnalysis->follow_number = $followId;
        $this->SampleAnalysis->profile = $profile;
        $this->SampleAnalysis->assay = $assay;
        $this->SampleAnalysis->project = $project;
        $this->SampleAnalysis->assay_base = $assayBase;
        $this->SampleAnalysis->project_order = $order;

        //set up confirmation flag, ask/on/off
        //11-23-2017: disabled this since we are now pushing this from the results controller, sinec we have to wait for the results to be finished.

        //$confInitStrategy = upa('assays', 'assayConfirmationInitStrategy', array($assayBase), False);
        //$this->SampleAnalysis->conf_requested = $confInitStrategy;
        $this->SampleAnalysis->conf_requested = '0';

        //get original id for downstream analysis
        $ori = upa('assays', 'fetchOriginalId', array($assayBase), False);

        $this->SampleAnalysis->original_assay_base = $ori;
        $this->SampleAnalysis->roaming_id = $roamingId;
        $this->SampleAnalysis->profile_group = $nextGroupNumber;
        $this->SampleAnalysis->save();
        $said = $this->SampleAnalysis->lastInsertId;

        $this->SampleAnalysis->deepFreed();
        $innoc = $this->saidToInnoc($said);

        if($innoc){
          upa('assuranceForms', 'updateFormByInnocDate', array($innoc), False);
        }

        upa('samples', 'estimateEndPoints', array($sample), False);
        upa('samples', 'checkSampleIsEmpty', array($sample), False);
        return $said;
    }

    function setRoam($said, $roamId){
        $this->SampleAnalysis->id = $said;
        $this->SampleAnalysis->roaming_id = $roamId;
        $this->SampleAnalysis->save();
    }


    function fetchBySampleAndFollow($sample, $follow, $id= False){
        $this->render = 0;
        $this->SampleAnalysis->where('sample', $sample);
        $this->SampleAnalysis->where('follow_number', $follow);
        $result = $this->SampleAnalysis->search();
        return $result[0];
    }

    function fetchById($id){
        $this->render = 0;
        $this->SampleAnalysis->where('id', $id);
        $result = $this->SampleAnalysis->search();

        if(empty($result)){
            return False;
        } else{
            return $result[0];
        }
    }

    function fetchBySampleFollow($sample, $follow){
        $this->render = 0;
        $this->SampleAnalysis->where('sample', $sample);
        $this->SampleAnalysis->where('follow_number', $follow);
        $result = $this->SampleAnalysis->search();

        if(!empty($result)){
            return $result['0']['id'];
        }
    }

    function addByBarcode(){

        $barcode = $_POST['barcode'];
        $flow = $_POST['flow'];
        $sampleId = pa('samples', 'barcodeToId', array($barcode));
        $this->registerFlow($sampleId, $flow);
    }
        /*
    function registerFlow($sampleId, $flow){

        $this->render = 0;

        $this->SampleAnalysis->where('sample', $sampleId );
        $results = $this->SampleAnalysis->search();

        if(empty($results)){   $follow_number = 1;  }
        else{ $follow_number = count($results) + 1; }

        $this->SampleAnalysis->sample = $sampleId;
        $this->SampleAnalysis->follow_number = $follow_number;
        $this->SampleAnalysis->flow = $flow;
        $this->SampleAnalysis->save();

        //register flow components in results
        performAction('results', 'registerResult', array(
                                0 => $flow,
                                1 => $sampleId,
                                2 => $this->SampleAnalysis->lastInsertId ));

        $this->render = 0;

        $this->SampleAnalysis->where('sample', $sampleId );
        $results = $this->SampleAnalysis->search();

        if(empty($results)){
            $follow_number = 1;
        } else{
            $follow_number = count($results) + 1;
        }

        //register analysis
        $this->SampleAnalysis->sample = $sampleId;
        $this->SampleAnalysis->follow_number = $follow_number;
        $this->SampleAnalysis->test = $test;
        $this->SampleAnalysis->save();

        //register test lines in results
        performAction('results', 'registerResult', array(
                                0 => $test,
                                1 => $sampleId,
                                2 => $this->SampleAnalysis->lastInsertId ));


    }*/


    function fetchAnalysis($sampleId, $residualBar){

        $this->render = 0;

        $this->SampleAnalysis->where('sample', $sampleId );
        $analysis = $this->SampleAnalysis->search();

        //check status of the project
        $partOfAuth = pa('samples', 'partOfAuthProject', array($sampleId));

        $generateTable = '';

        foreach($analysis as $saReq){

            $testInfo = performAction('flows', 'getFlow', array(0=>$saReq['flow']));
            $testInfo['saId'] = $saReq['id'];
            $barcode = 'A' . $residualBar . '.' . $saReq['follow_number'] .'.1' ;
            $barcodeForRender = 'A' . $residualBar . '.' . $saReq['follow_number'];
            $testInfo['barcode'] = $barcode;
            $testInfo['barcode_for_render'] = $barcodeForRender; //because we dont want the last .1 for the general list.

            if($partOfAuth == True){
                 $testInfo['anaIcon'] = 'icon-ok';
                 $testInfo['auth_dir'] = '-1';
            }
            else {
                if($saReq['auth_status'] == 1){
                    $testInfo['anaIcon'] = 'icon-star';
                    $testInfo['auth_dir'] = '0';
                }

                if($saReq['auth_status'] == 0){
                    $testInfo['anaIcon'] = 'icon-star-empty';
                    $testInfo['auth_dir'] = '1';
                }
            }

            $generateTable .= generateHTML('analysisListing', $testInfo);
        }

        return $generateTable;
    }

    function fetchAnalysisArray($sampleId, $excludeSelf = False){
        $this->render = 0;
        //$this->SampleAnalysis->where('sample', $sampleId);

        $params = array();

        $sql = "SELECT * FROM sampleanalysis WHERE sample = :sample ";
        $params['sample'] = $sampleId;

        if($excludeSelf != False){
            //$this->SampleAnalysis->notLike('id', $excludeSelf);
            $sql = $sql . 'AND id != :id ';
            $params['id'] = $excludeSelf;
        }

        $sql = $sql . 'ORDER BY project_order ASC, follow_number ASC;';

        //$this->SampleAnalysis->order('project_order', 'ASC' );
        //$this->SampleAnalysis->order('follow_number', 'ASC' );
        //return $this->SampleAnalysis->search();

        return $this->SampleAnalysis->customQuery($sql, $params);
    }

    function fetchTypeSpecificAnalysisArray($sampleId, $typeBaseId, $negative = False){

        $this->render = 0;
        //$this->SampleAnalysis->where('sample', $sampleId);
        //$this->SampleAnalysis->order('profile_group', 'ASC' );
        //$fullAnalysis = $this->SampleAnalysis->search();

        $sql = "SELECT * FROM sampleanalysis WHERE sample = :sample ";
        $params = array();
        $params['sample'] = $sampleId;

        $sql = $sql . 'ORDER BY project_order ASC, follow_number ASC;';
        $fullAnalysis = $this->SampleAnalysis->customQuery($sql, $params);

        $selectedAnalysis = array();

        foreach($fullAnalysis as $analysis){
            $analysisBase = upa('assays', 'fetch', array($analysis['assay_base']), False);
            $typeBase = $analysisBase['type_base'];

            if(is_array($typeBaseId)){
                if($negative == False && in_array($typeBase, $typeBaseId)){
                    array_push($selectedAnalysis, $analysis);
                }
                if($negative == True && !in_array($typeBase, $typeBaseId)){
                    array_push($selectedAnalysis, $analysis);
                }
            } else{
                if($negative == False && $typeBase == $typeBaseId){
                    array_push($selectedAnalysis, $analysis);
                }
                if($negative == True && $typeBase != $typeBaseId){
                    array_push($selectedAnalysis, $analysis);
                }
            }
        }

        return $selectedAnalysis;
    }



    function fetchAssaySpecificAnalysisArray($sampleId, $assayArray, $negative = False, $confActive = False){

        $this->render = 0;
        
        $sql = "SELECT * FROM sampleanalysis WHERE sample = :sample  ";

        if($confActive == True)
        {
          $sql = $sql . "AND conf_requested = 1 ";
        }

        $params = array();
        $params['sample'] = $sampleId;

        $sql = $sql . 'ORDER BY project_order ASC, follow_number ASC;';
        $fullAnalysis = $this->SampleAnalysis->customQuery($sql, $params);

        $selectedAnalysis = array();

        foreach($fullAnalysis as $analysis){

            if($analysis['original_assay_base'] == NULL){
                $baseId = $analysis['assay_base'];
            } else{
                $baseId = $analysis['original_assay_base'];
            }

            //remove the ones in the assayArray
            if($negative == True){
                if(!in_array($baseId, $assayArray)){
                    array_push($selectedAnalysis, $analysis);
                }
            }

            //keep the ones in the assayArray
            else{
                if(in_array($baseId, $assayArray)){
                    array_push($selectedAnalysis, $analysis);
                }
            }
        }

        return $selectedAnalysis;
    }

    function fetchAnalysisInfo($sampleAnalysisId){

        $this-> render = 0;

        //get standard table
        $this->SampleAnalysis->where('id', $sampleAnalysisId);
        $result = $this->SampleAnalysis->search();
        $analysisTestId = $result[0]['test'];

        //infuse bound test
        $testInfo = performAction('analysisTests', 'getSingle', array(0=>$analysisTestId));
        $result['test_info'] = $testInfo;
        return $result;
    }

    function authoriseAll($sampleId = False, $deAuth = False, $fillHoles = False){

        if($sampleId == False){
            if(!isset($_POST['sampleId'])){
                return;
            } else{
                $sampleId = $_POST['sampleId'];
            }
        }

        $this->render = 0;
        $this->SampleAnalysis->where('sample', $sampleId);

        if($fillHoles == True){
            $this->SampleAnalysis->where('auth_status', '0');
        }


        $result = $this->SampleAnalysis->search();

        foreach($result as $sa){
            $this->SampleAnalysis->arrayToModel($sa);
            $this->SampleAnalysis->auth_by = getUserId();

            if($deAuth == True){
                $this->SampleAnalysis->auth_status = '0';
            }else{
                $this->SampleAnalysis->auth_status = '1';
            }


            $this->SampleAnalysis->save();
        }

    }

    function authoriseAnalysis($analysisId = False, $auth = False){

        $this->render = 0;

        //try post backup
        if($analysisId == False){
            if(!isset($_POST['analysisId'])){
                return False;
            } else{
                $analysisId = $_POST['analysisId'];
            }
        }

        if($auth == False){
            if(!isset($_POST['authSetting'])){
                return False;
            } else {
                $auth = $_POST['authSetting'];
            }
        }

        $this->SampleAnalysis->where('id', $analysisId);
        $result = $this->SampleAnalysis->search();
        $this->SampleAnalysis->arrayToModel($result[0]);


        $this->SampleAnalysis->auth_status = $auth;
        $this->SampleAnalysis->auth_by = getUserId();
        $this->SampleAnalysis->save();
    }

    function checkFullAuth($sampleId){

        $this->SampleAnalysis->where('sample', $sampleId);
        $results = $this->SampleAnalysis->search();

        $noOfSa = count($results);
        $pending = 0;
        $auth = 0;
        $noAuth = 0;

        foreach($results as $sa){
            if($sa['auth_status'] == 0){ $noAuth++;}
            if($sa['auth_status'] == 1){ $auth++;}
            if($sa['auth_status'] == 2){ $pending++;}
        }


        if($auth == $noOfSa){
            return 'full';
        }
        elseif($noAuth == $noOfSa){
            return 'none';
        }

        else{
            return 'partial';
        }
    }

    function checkPartOfAuthProject($said){

        $this->render = 0;
        $this->SampleAnalysis->where('id', $said);
        $result = $this->SampleAnalysis->search();

        if(!empty($result)){
            $project = $result[0]['project'];
        } else {
            return NULL;
        }

    }

    function removeForSample($sampleId){

        $this->render = 0;

        $this->SampleAnalysis->where('sample', $sampleId);
        $result = $this->SampleAnalysis->search();

        foreach($result as $sa){
            upa('samples', 'removeAnalysis', array($sa['id'], $sampleId));
        }
    }

    function removeByAssay($assay){
         $this->render = 0;

        $this->SampleAnalysis->where('assay_base', $assay);
        $result = $this->SampleAnalysis->search();

         foreach($result as $sa){
            pa('samples', 'removeAnalysis', array($sa['id'], $sa['sample']));
        }
    }

    function fetchResultSummary($sampleId){

        $this->doNotRenderHeader = 1;
        $this->SampleAnalysis->where('sample', $sampleId);
        $result = $this->SampleAnalysis->search();

        

        $thisProfile = False;
        $wasRoaming = False;
        $render = '';
        $renderArr = array();

         //check for empty
        if(empty($result)){
            $render = generateHTML('alertWarning', array('alert_title' => '{MESA_PLU_NORESEARCH}', 'alert_message' => '{MESA_PLU_NORESEARCHEXPLANATION}'));
        }

        else{

            $table = new tableFactory();
            $table->legoMode();
            $table->loadTemplate('projectEndResults');

            //use array_column to get all assay_base ids
            $assayArray = array_column($result, 'assay_base');

            $assayBases = upa('assays', 'fetchBulk', array($assayArray));



            $i = 0;
            foreach($result as $sa){                        

                $assayBase = $assayBases[$sa['assay_base']];

                //new profile gorup, so new roaming or pofile
                //print a header for this
                if($thisProfile <> $sa['profile_group'] ){

                    if($wasRoaming == true && $sa['profile'] == 0){
                      
                    }
                    else{
                        $thisProfile = $sa['profile_group'];

                        if($i > 0){
                            $render .= $table->returnBrick('tf');
                        }


                        if($sa['profile'] == 0){
                            //$profileInfo = pa('roamingAnalysis' , 'fetchSettings', array($sa['roaming_id']), False);
                            $wasRoaming = true;
                            $name = $assayBase['name'];
                            //$render .= generateHTML('sublead', array('title'=>$name));
                            //start a new table for the sample analysis, only close the last one if I>0
                        }

                        else{
                            $wasRoaming = false;
                            $profileInfo = pa('researchProfiles', 'fetchProfile', array($sa['profile']));
                            $name = $profileInfo['name'];
                            $render .= generateHTML('sublead', array('title'=> 'Profiel:' . $name));
                        }

                        $render .= $table->returnBrick('th');
                    }

                }

                //header created if neccesairy, now create end results
                $render  .= upa('results', 'renderEndResultsProject', array($sa['id'], $assayBase['name'], $sa ));
                
                $i++;
                }

        }
        
        

        $this->_template->set('results', $render);
        
        return $this->hardRender();
    }

    function setEndPoint($id, $end){
        $this->SampleAnalysis->id = $id;
        $this->SampleAnalysis->predicted_end = $end;
        $this->SampleAnalysis->save();
    }

    function endPointFromNow($id, $offsetDays = null){
      $assayBase = upa('assays', 'fetchSingle', array($id));

      if($offsetDays)
      {
        $base = time() + ($offsetDays * 86400);
      }

      else
      {
        $base = time(); 
      }

      $endPoint = $base + (86400 * $assayBase['duration']);
      return $endPoint;
    }

    function reestimateEndPont($id, $previous){
      $assayBase = upa('assays', 'fetchSingle', array($id));
      if($assayBase['start_from'] == 'i'){
        $endPoint = time() + (86400 * $assayBase['duration']);
        return $endPoint;
      } else{
        return $previous;
      }
    }

    function readyToday(){

        $this->render = 0;

        $beginOfDay = strtotime("midnight", time());
        $endOfDay   = strtotime("tomorrow", $beginOfDay);
        $this->SampleAnalysis->lessThan('predicted_end', $endOfDay);
        $this->SampleAnalysis->greaterThan('predicted_end', $beginOfDay);
        $results = $this->SampleAnalysis->search();
        return $results;
    }

    function analysisAndProfilesArray($sampleId){

        $this->render = False;

        //fetch all
        $this->SampleAnalysis->where('sample', $sampleId);
        $req = $this->SampleAnalysis->search();

        if(empty($req))
        {
          $assays = [];
        }

        else
        {
          $sql = 'SELECT id,research_profile FROM assayprofiles WHERE research_profile IN (' . implode(',',array_unique(array_column($req, 'profile')))  . ' )  ORDER BY project_order ASC ';        
          $ap = new AssayProfile();
          $assays = $ap->customQuery($sql, []);          
        }

        $analysisIds = [];

       
        foreach($assays as $val) {
            $analysisIds[$val['research_profile']][] = $val;
        }
     
        $reqArr = array();
        $excArr = array();
        $loadedProfiles = array();



        $i = 0;
        foreach($req as $request){

            //is roaming add to req as loose assay
            if($request['roaming_id'] != 0){
                $reqArr[$i]['resId'] = $request['assay'];
                $reqArr[$i]['assayId'] = $request['assay'];
                $reqArr[$i]['resType'] = 'assay';
                $excArr[$i] = array();
            }

            //profile check if we have it already
            elseif($request['roaming_id'] == 0 && $request['profile'] != 0){

                //check if it was loaded already by a previous arr, if not, dump all assays in excluded
                //which will be removed if they are requested
                if(!array_key_exists($request['profile'], $loadedProfiles)){
                    $j = 0;

                    $profile = array_column($analysisIds[$request['profile']], 'id');
                                        
                    foreach($profile as $profileAssay){
                         //$excArr[$i][$j] = $profileAssay['id'];
                         $excArr[$i][$j] = $profileAssay;
                         $j++;
                    }

                    $loadedProfiles[$request['profile']] = $i;
                    $reqArr[$i]['resId'] = $request['profile'];
                    $reqArr[$i]['resType'] = 'profile';
                }

                if(($key = array_search($request['assay'], $excArr[$loadedProfiles[$request['profile']]])) !== false) {
                    unset($excArr[$loadedProfiles[$request['profile']]][$key]);
                }
            }
            $i++;
        }

        $retObj['req'] = $reqArr;
        $retObj['exc'] = $excArr;
        return $retObj;
    }


    function getSamplesByAnalysis($assays, $order = False){
        $this->render = False;
        foreach($assays as $assay){
            $this->SampleAnalysis->where('assay_base', $assay);
            $this->SampleAnalysis->insertOR();
            $this->SampleAnalysis->where('original_assay_base', $assay);
            $this->SampleAnalysis->insertOR();
        }

        if($order == True){
          $this->SampleAnalysis->order('sample', 'ASC');
        }

        $result = $this->SampleAnalysis->search();
        return $result;
    }

    function checkIfSampleHas($sample, $analysis){
        $this->SampleAnalysis->where('sample', $sample);
        $results = $this->SampleAnalysis->search();
        $hasIt = False;
        foreach($results as $result){
            if(in_array($result['original_assay_base'], $analysis)){
                $hasIt = True;
            }
            if(in_array($result['assay_base'], $analysis)){
                $hasIt = True;
            }
        }
        return $hasIt;
    }

    function batchCheckIfSampleHas($samples, $analysisArray){

        #$this->SampleAnalysis->where('sample', $sample);
        #$results = $this->SampleAnalysis->search();

        if(empty($samples)){
          return array();
        }

        $idMap = implode(',', array_map('intval', $samples));
        $sql = 'SELECT * FROM `sampleanalysis` WHERE sample IN (' . $idMap . ');';
        $result = $this->SampleAnalysis->customQuery($sql, array());

        #$bases = array_column($results, 'assay_base');
        #$ori_bases = array_column($results, 'original_assay_base');
        $hasItArray = array();

        foreach($analysisArray as $checkAnalysis => $checkAnalysisIds){

          foreach($result as $sampleRow){
            if(in_array($sampleRow['assay_base'], $checkAnalysisIds) || in_array($sampleRow['original_assay_base'], $checkAnalysisIds)){
              $hasItArray[$sampleRow['sample']][$checkAnalysis] = True;
            }
          }
        }

        return $hasItArray;
    }

    function checkIfSampleFollowHas($sample, $follow, $analysis){
        $this->SampleAnalysis->where('sample', $sample);
        $this->SampleAnalysis->where('follow_number', $follow);
        $results = $this->SampleAnalysis->search();
        $hasIt = False;
        foreach($results as $result){
            if(in_array($result['original_assay_base'], $analysis)){
                $hasIt = True;
            }
            if(in_array($result['assay_base'], $analysis)){
                $hasIt = True;
            }
        }
        return $hasIt;
    }


    function isSampleEmpty($sampleId){

        $this->SampleAnalysis->where('sample', $sampleId);
        $result = $this->SampleAnalysis->search();

        if(!empty($result)){
            return False;
        } else{
            return True;
        }
    }

    function saidToSample($said){
      $this->SampleAnalysis->where('id', $said);
      $result = $this->SampleAnalysis->search();
      if(!empty($result)){
          return $result[0]['sample'];
      } else{
          return True;
      }
    }

    function saidToInnoc($said){
        $sample = $this->saidToSample($said);
        $innoc = upa('samples', 'getSampleSpecificInnoculationDate', array($sample), False);
        $beginOfDay = strtotime("midnight", $innoc);

        if($beginOfDay < 0){
          return False;
        } else{
          return $beginOfDay;
        }
    }

    function triggerProfileMetas($profile){

        //fetch metas
        $meta = upa('assays', 'returnAllMetas', array(), False);
        $metaAssays = array();
        foreach($meta as $metaAssay){
          array_push($metaAssays, $metaAssay['original_id']);
        }

        //select where profile = profile, and where base assay is a metaResults
        $this->SampleAnalysis->where('profile_group', $profile);
        $saids = $this->SampleAnalysis->search();
        $saidsToTrigger = array();

        foreach($saids as $saidRow){

          if(in_array($saidRow['original_assay_base'], $metaAssays) && !in_array($saidRow['id'], $saidsToTrigger)){
            array_push($saidsToTrigger, $saidRow['id']);
          }
        }

        //run resultscontroller trigger
        foreach($saidsToTrigger as $saidTrigger){
            upa('results', 'msProcess', array($saidTrigger), False);
        }

    }

    function brewAnalysisOrderList($project){

      //make a best possible guess for analysis order
      $this->render = False;
      $this->SampleAnalysis->where('project', $project);
      $this->SampleAnalysis->order('sample', 'ASC');
      $this->SampleAnalysis->order('project_order', 'ASC');
      $results = $this->SampleAnalysis->search();


      $assayOrder = array();
      $orderList = array();
      $maxOrder = 0;

      $analysisList = upa('assays', 'returnAll', array(), False);

      $backupCounter = 0;
      $previousSample = false;

      foreach($results as $said){

          $sample = $said['sample'];

          if($previousSample == False){
            $previousSample = $sample;
            $backupCounter = 0;
          } else{
            if($sample != $previousSample){
              $backupCounter = 0;
            }

            $previousSample = $sample;
          }

          //sample key set?
          $sampleSet = checkKeyOrFalse($assayOrder, $sample);

          if(!$sampleSet){
            $assayOrder[$sample] = array();
          }

          $orderSet = checkKeyOrFalse($assayOrder, $sample,$said['project_order']);
          $projectOrder = $said['project_order'];

          if($orderSet != False){
            //this key is already set....
            //now what?
            //artificially increas this order by one + the offset.
            $projectOrder = $projectOrder + $backupCounter + 1;
            $backupCounter = $backupCounter + 1;
          }

          $assayOrder[$sample][$projectOrder] = $said['assay_base'];

          // if($said['project_order'] > $maxOrder){
          //   $maxOrder = $said['project_order'];
          // }
      }

      $orderList = array();
      $sampleLen = count($assayOrder);
      $arrayKeys = array_keys($assayOrder);

    //  cphp($assayOrder);
    //  cphp($sampleLen . '$sampleLen');

      foreach($assayOrder as $sampleAssayOrder){

        $count = count($sampleAssayOrder);
        if($count > $maxOrder){
           $maxOrder = $count;
        }
      }

      for($i=0; $i < $maxOrder; $i++){

        //  cphp('Trying to access assay at position' . $i);

          for($j=0; $j < $sampleLen; $j++){

            $keyLocation = $arrayKeys[$j];
          //  cphp('Accessing sample with sample key position of ' . $j . ' keylocation was ' . $keyLocation);

            $sampleOrderArray = checkKeyOrFalse($assayOrder, $keyLocation);

            if($sampleOrderArray != False){

                $thisSamplesKeys =  array_keys($sampleOrderArray);

              //  cphp($thisSamplesKeys);


                $assayPosition =   checkKeyOrFalse($thisSamplesKeys, $i);

              //  cphp($assayPosition);

                if($assayPosition != False){

                  $assayFound = checkKeyOrFalse($assayOrder, $keyLocation, $assayPosition);
                  if(!in_array($assayFound, $orderList) && $assayFound != False){
                    $name = $analysisList[$assayFound]['name'];
                    $orderList[$assayFound] = array('id' => $assayFound, 'name' => $name);
                    //array_push($orderList, array($assayFound => array('id' => $assayFound, 'name' => NULL)));
                  }
                }

            }

          }
      }


      //print json_encode($orderList);
//      cphp($orderList);

      return $orderList;
    }


    function dataMiningGrab($samples){

      foreach($samples as $sample){
        $this->SampleAnalysis->where('sample', $sample['id']);
        $this->SampleAnalysis->insertOR();
      }

      $this->SampleAnalysis->order('sample', 'ASC');
      $this->SampleAnalysis->order('follow_number', 'ASC');

      $results = $this->SampleAnalysis->search();
      $retResult = array();

      foreach($results as $result){
        $retResult[$result['id']] = $result;
      }

      return $retResult;

    }
}
