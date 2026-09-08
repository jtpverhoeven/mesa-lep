<?PHP

class resultsController extends controller{

  private $foundScriptController;
  private $foundScriptVariables = array();

  function removeResults($saID){

    $this->Result->where('sa_id', $saID);
    $results = $this->Result->search();

    foreach($results as $result){
      $this->Result->id = $result['id'];
      $this->Result->remove();
    }
  }

  function findMaximumCount($sample, $said,  $df, $rep, $param = 'kve', $meta = False){
    $this->render = 0;

    if($meta == False){
      if($df == 'global'){
        $this->Result->where('sa_id', $said );
      } else{
        $this->Result->where('sa_id', $said );
        $this->Result->where('df', $df );
        $this->Result->where('rep', $rep );
      }
    } else {
      
      //grab original base id's for meta analyses
      //and find them in the results table 
      
      $assays = new Assay(); 

   
      foreach($meta as $baseId)
      {        
         $assays->where('id', $baseId);
         $assays->insertOR();
         $assays->where('original_id', $baseId);
         $assays->insertOR();
      }

      $baseassays = $assays->search(); 

      $all_assay_ids = array_column($baseassays, 'id');
      $all_original_ids = array_column($baseassays, 'original_id');

      $all_assay_ids = array_combine($all_assay_ids, $all_original_ids);

  

      $extended_assays = new Assay(); 

      foreach($all_assay_ids as $assay_id)
      {        
         $extended_assays->where('id', $assay_id);
         $extended_assays->insertOR();
         $extended_assays->where('original_id', $assay_id);
         $extended_assays->insertOR();
      }

      $extended_assays_result = $extended_assays->search(); 

      $extended_ids = array_column($extended_assays_result, 'id');

    
      // foreach($meta as $idx => $baseId)
      // {
      //   $this->Result->where('sample', $sample );
      //   $this->Result->where('assay_base', $baseId );
      //   $this->Result->insertOR();
     
      // }

      foreach($extended_ids as $idx => $baseId)
      {
        $this->Result->where('sample', $sample );
        $this->Result->where('assay_base', $baseId );
        $this->Result->insertOR();
      }



    }

    $fields = $this->Result->search();




    if($meta == False){
      $numbers = array();
      if(!empty($fields)){
        foreach($fields as $field ){
          $fieldData = json_decode($field['data'], JSON_FORCE_OBJECT);
          if(array_key_exists($param, $fieldData)){
            if($fieldData[$param] == '+'){
              return '1000';
            }else{
              array_push($numbers, $fieldData[$param]);
            }
          }
        }
      }

      return array_sum($numbers);
    }

    if($meta == True){
      $saidNumbers = array();
      foreach($fields as $field){

        $fieldData = json_decode($field['data'], JSON_FORCE_OBJECT);
        $keyExists = checkKeyOrFalse($saidNumbers, $field['sa_id']);

        if($keyExists == False){
          $saidNumbers[$field['sa_id']]  = 0;
        }

        if(array_key_exists($param, $fieldData)){
          if($fieldData[$param] == '+'){
            $saidNumbers[$field['sa_id']] = $saidNumbers[$field['sa_id']] + 100;
          } else{
            $saidNumbers[$field['sa_id']] = $saidNumbers[$field['sa_id']] + $fieldData[$param];
          }
        }
      }

      $value = max(array_values($saidNumbers));
      return $value;

    }




  }


  function registerResult( $sample, $said, $profile, $assay, $roamingId = False){

    //for profiles
    $internalFollowNo = 1;


    if($roamingId === False){
      //fetch assay information
      $assayInfo = upa('assayProfiles' , 'fetchProfileById', array($assay), False);
      $baseAssayInfo = upa('assays', 'fetch', array($assayInfo['0']['assay']));
      $repNo = $assayInfo['0']['replicates'];
      $dilArr = json_decode($assayInfo['0']['dillutions'], JSON_FORCE_OBJECT);
      $roamId = 0;
    }

    //for single assays
    if($roamingId !== False){
      $assayInfo = pa('roamingAnalysis' , 'fetchSettings', array($roamingId), False);
      $baseAssayInfo = upa('assays', 'fetch', array($assayInfo['0']['assay']));
      $repNo = $assayInfo['0']['replicates'];
      $dilArr = json_decode($assayInfo['0']['dillutions'], JSON_FORCE_OBJECT);
      $roamId = $assayInfo['0']['id'];
    }

    //get fields for this assay
    $typeBaseId = $baseAssayInfo['type_base'];
    $assayFields = pa('assayTypeFields', 'fetchFields', array($typeBaseId) );

    $dbFields = array();
    foreach($assayFields as $field){
      $dbFields[$field['name']] = '';
    }

    if(empty($dilArr)){
      if($baseAssayInfo['type'] == 4){
        $dilArr = array();
      } else{
        $dilArr['0'] = 1;
      }
    }

    array_walk($dilArr, function(&$value, &$key) use(&$dilArr) {
      $dilArr[$key] = (float)$value; 
    });
        
    arsort($dilArr);

    foreach($dilArr as $dTitle => $dF)
    {

      $dF = rtrim(rtrim(sprintf('%.10F',  $dF), '0'), ".");

      $this->Result->sample = $sample;
      $this->Result->sa_id = $said;
      $this->Result->follow_no = $internalFollowNo;
      $this->Result->profile = $profile;
      $this->Result->assay = $assay;
      $this->Result->assay_base = $assayInfo['0']['assay'];
      $this->Result->df = $dF;
      $this->Result->roaming_id = $roamId;
      $this->Result->data = json_encode($dbFields, JSON_FORCE_OBJECT);

      if($repNo == 0){
        $this->Result->save();
        $internalFollowNo++;
      } else {
        //replicates required
        for($i = 0; $i <= $repNo ; $i++ ){
          $this->Result->rep = $i;
          $this->Result->follow_no = $internalFollowNo;
          $this->Result->save();
          $internalFollowNo++;
        }
      }
    }
  }


  function getTestResultsByDfAndRep($analysisId, $df, $rep){

  
      $this->render = 0;
      $this->Result->where('sa_id', $analysisId );
      $this->Result->where('df', $df );
      $this->Result->where('rep', $rep );
      $this->Result->limit(1);

      $fields = $this->Result->search();
    

      return $fields;
    }

  function getTestResults($analysisId, $orderByDF = False){
    $this->render = 0;
    $this->Result->where('sa_id', $analysisId );

    if($orderByDF == True){
      $this->Result->order('df', 'DESC');
    }

    $fields = $this->Result->search();
    return $fields;
  }

  function getTestResultsByDf($analysisId, $orderByDF = False){
    $this->render = 0;
    $this->Result->where('sa_id', $analysisId );

    if($orderByDF == True){
      $this->Result->order('df', 'DESC');
    }

    $fields = $this->Result->search();
    $returnArr = array();

    foreach($fields as $dFFields){

      if(!array_key_exists($dFFields['df'], $returnArr)){
        $returnArr[$dFFields['df']] = array();
      }

      $returnArr[$dFFields['df']][$dFFields['rep']] = $dFFields;

    }

    return $returnArr;
  }

  function getSampleResults($sampleId, $orderBySA = False){
    $this->render = 0;
    $this->Result->where('sample', $sampleId);
    //$this->Result->orderBy('group, follow_number');
    //$this->Result->order('ASC');
    //$this->Result->order('group', 'ASC');
    $this->Result->order('sa_id', 'ASC');
    $this->Result->order('follow_no', 'ASC');

    $results =  $this->Result->search();
    if($orderBySA == False){
      return $results;
    }

    if($orderBySA == True){
      $stratified = array();
      if(empty($results)){
        return $stratified;
      } else {
        foreach($results as $result){
          //$stratified[$result['analysis_id']][$result['id']] = $result;
          $stratified[$result['sa_id']][$result['id']] = $result;
        }
        return $stratified;
      }
    }
  }


  public function resultsHandler($faker = False){

    $this->render = 0;

    //field info from view
    $id = $_POST['dbRid'];
    $resultArray = array();
    $updateFieldName = $_POST['fieldName'];
    $updateFieldValue = $_POST['fieldValue'];
    $reveal = False;
    $hide = False;

    //import current results
    $this->Result->where('id', $id);
    $this->Result->limit(1);
    $foundLine = $this->Result->search();
    $sampleAnalysis = $foundLine[0]['sa_id'];
    $this->Result->arrayToModel($foundLine[0]);

    //setup result array
    $currentResults = json_decode($this->Result->data, True);

    foreach($currentResults as $fieldName=>$fieldValue){
      $resultArray[$fieldName] = $fieldValue;
    }

    if(array_key_exists($updateFieldName, $resultArray)){
      $previousSetting = $resultArray[$updateFieldName];
    } else{
      $previousSetting = '';
    }


    //mix in requested change
    $resultArray[$updateFieldName] = $updateFieldValue;


    //grab the sample analysis row
    $saInfo = upa('sampleAnalysis', 'fetch', array($sampleAnalysis), False);
    $assayInfo = upa('assays', 'fetch', array($saInfo['assay_base']), False);
    $assayTypeFields = upa('assayTypeFields', 'fetchFields', array($assayInfo['type_base']), False);
    $fieldAliasArray = array();
    foreach($assayTypeFields as $assayTypeField){
      $fieldAliasArray[$assayTypeField['name']] = $assayTypeField['alias'];
    }

    //check if there is a masking name for this field.
    if(checkKeyOrFalse($fieldAliasArray, $updateFieldName) != False){
      $alias = $fieldAliasArray[$updateFieldName];
    } else{
      $alias = $updateFieldName;
    }

    //add assay name
    $alias = $alias . ' [' . $assayInfo['name'] . ']' . ' Df: ' .   $this->Result->df . ' Rep: ' .   $this->Result->rep;

    //Signal the revisions controller to save the current revision    
    upa('changeTracker', 'changed', array(1, $saInfo['project'], $saInfo['sample'], $saInfo['id'], $alias, $previousSetting, $updateFieldValue), False);
    upa('projects', 'projectEdited', array(False, $this->Result->sample), False);


    /* Check bindings for this field, so
    * trigger_by should be called by THIS field
    */
    //Field binding triggers are now deprecated
    //$triggered = $updateFieldName;
    //$analytical = $this->Result->assay_base;
    //we want to get all the changed fields back into this array
    //$resultArray = upa('fieldBindings', 'bindingHandler', array(0=>$triggered, 1=>$analytical, 2=>$resultArray ), 0);
    
    $updatedResults = json_encode($resultArray);
    $this->Result->data = $updatedResults;
    $this->Result->save();

    //send ajax update
    $ajaxUpdateBlob = array();
    foreach($resultArray as $field=>$value){
      if($field == ''){
        continue;
      }
      $ajaxUpdateBlob[$field . '_' .$id  ] = $value;
    }


     if($saInfo['conf_requested'] == 1 && $assayInfo['confirmation_type'] != '0'){

       //check conf
       if($updateFieldName == 'kve'){
        if($updateFieldValue == '0' || ($updateFieldValue > $assayInfo['max_count'] &&  $assayInfo['replicates'] == '0')){
          $hide = $id . '_conf_addon';
        } else{
          $reveal = $id . '_conf_addon';
        }
       }

     }


  upa('confirmations', 'updateLine', array($saInfo['id'], $foundLine['0']['df'], $foundLine['0']['rep']), false);

  //update the end resuls, to allow isreadyFlag to be set etc.
  $msOutput = upa('results', 'msProcess', array($saInfo['id']), False);



  //Check if we need to ask for confirmation flags
  //conf requested 0 = unkonwn, 1 = yes, 2 = no
  $askForConf = False;
  $autoConfApplied = False;

  $askForMetaParentConfirmation = checkKeyOrFalse($msOutput, 'askForMetaParentConfirmation');
  $askForMetaConfirmation = checkKeyOrFalse($msOutput, 'askForMetaConfirmation');
  

  if($askForMetaParentConfirmation == True){

    //meta conf stuff here
    $metaId = checkKeyOrFalse($msOutput, 'meta_parent');

    if($metaId){

      //see if we can rescue it from the msprocess, else repick it
      $metaSaInfo = checkKeyOrFalse($msOutput, 'meta_said');
      if(!$metaSaInfo)
      {
        $metaSaInfo = upa('sampleanalysis', 'fetch', array($metaId), False);
      }
      
      //TODO: refactor to inline 
      $metaAssayInfo = upa('assays', 'fetch', array($metaSaInfo['assay_base']), False);

      if($metaAssayInfo['confirmation'] == 1 && $metaSaInfo['conf_requested'] == 0 )
      {

        //if standard strategy is to enable, just enable it. 
        if($metaAssayInfo['confirmation_init'] == '1')
        {          
          upa('sampleAnalysis', 'setConfFlag', array(1, $metaId, True), False);

          //turn off ask for, we alread explicitly ENABLED it 
          $askForMetaParentConfirmation = False; 
          $askForMetaConfirmation = False; 
        }

        else if($metaAssayInfo['confirmation_init'] == '2')
        {          
          upa('sampleAnalysis', 'setConfFlag', array(False, $metaId, True), False);

          //turn off ask for, we alread explicitly DISABLED it 
          $askForMetaParentConfirmation = False; 
          $askForMetaConfirmation = False; 
        }

        //if niether of those 2 quesions, leave ask for intact to let user decide. askForMetaParentConfirmation

      }
    }
  }

  $msOutput['askForMetaParentConfirmation'] = $askForMetaParentConfirmation;
  $rendered = $this->renderEndResults($saInfo['id'], $msOutput, $saInfo);

  if($assayInfo['confirmation'] == 1 && $saInfo['conf_requested'] == 0 ){

    //a zero was entered, cant do anything yet.
    // if(empty($updateFieldValue) || $updateFieldValue == '0' || $updateFieldValue == '-' || $updateFieldValue == '<' || $updateFieldValue == 'NVT'){
    //   $askForConf = False;
    // } else{

      //non zero value was given.
      //but since we are int he conf_requested == 0 loop it is unknown still if we want to confirm or not.

      //check here if this kve value has tripped the conf setting?
      $kveValue = checkKeyOrFalse($msOutput, 'output', 'kve');
      $disposition = checkKeyOrFalse($msOutput, 'disposition', 'kve');

      $kveValueNumeric =  preg_replace("/[^0-9]/", "", $kveValue);
      $kveValueNumeric =  (string)$kveValueNumeric;
      $profileValue = $saInfo['profile'];
      
      //also invoke this if the disposition is empty.
      if(strlen($kveValueNumeric) > 0 || $disposition == '+'){

        //if its zero, we can't confirm, so disable.
        if(($kveValueNumeric === '0') || $disposition == '-'){
          $askForConf = False;
          //$autoConfApplied = True;
          //upa('sampleAnalysis', 'setConfFlag', array(2, $sampleAnalysis, True), False);
        }

        else{
          //A) Loose analysis, use assay indicated behaviour
          if($profileValue == 0){

            if($assayInfo['confirmation_init'] == '0'){
                $askForConf = True;
            }

            if($assayInfo['confirmation_init'] == '1'){
              $askForConf = False;
              $autoConfApplied = True;
              upa('sampleAnalysis', 'setConfFlag', array(1, $sampleAnalysis, True), False);
            }

            if($assayInfo['confirmation_init'] == '2'){
              $askForConf = False;
              $autoConfApplied = True;
              upa('sampleAnalysis', 'setConfFlag', array(2, $sampleAnalysis, True), False);
            }


          }

          //B) Assay came out of a research profile. Find out if a confTripvalue was set
          else{

            //conf trip value from the profile.
            $confTripValue = upa('assayProfiles', 'getConfTripValue', array($saInfo['assay']), False);            

            

            //this is not a documented function anymore.
            //always ask
            // if($confTripValue == '-1'){
            //   $askForConf = True;
            // }

            //if set to zero, just follow assay settings
            if($confTripValue == '0'){

              //strategies: 1 = ask
              //2 = Dont ask, standard ON
              //3 = Dont ask, standard off

              if($assayInfo['confirmation_init'] == '0'){
                  $askForConf = True;
              }

              if($assayInfo['confirmation_init'] == '1'){
                $askForConf = False;
                $autoConfApplied = True;
                upa('sampleAnalysis', 'setConfFlag', array(1, $sampleAnalysis, True), False);
              }

              if($assayInfo['confirmation_init'] == '2'){
                $askForConf = False;
                $autoConfApplied = True;
                upa('sampleAnalysis', 'setConfFlag', array(2, $sampleAnalysis, True), False);
              }
            }

            elseif($confTripValue > 0){

              //the end result needs to be finished for this to work
                if($kveValueNumeric > $confTripValue){
                  $askForConf = False;
                  $autoConfApplied = True;
                  upa('sampleAnalysis', 'setConfFlag', array(1, $sampleAnalysis, True), False);
                } else{
                  $askForConf = False;
                  $autoConfApplied = True;
                  upa('sampleAnalysis', 'setConfFlag', array(2, $sampleAnalysis, True), False);
                }

            }
          }

        }

        }
  }

  $retObj = array();
  $retObj['ajaxUpdateBlob'] = $ajaxUpdateBlob;
  $retObj['askForConf'] = $askForConf;
  $retObj['said'] = $saInfo['id'];
  $retObj['reveal'] = $reveal;
  $retObj['hide'] = $hide;
  $retObj['autoConfApplied'] = $autoConfApplied;
  $retObj['isReady'] = checkKeyorTrue($msOutput, 'isReady');
  $retObj['result'] = $rendered;
  
  
  if($faker !== True)
  {
    print json_encode($retObj, JSON_FORCE_OBJECT);
  }
  
}


public function saveField(){

  $this->render = 0;
  $id = $_POST['dbRid'];
  $fieldName = $_POST['fieldName'];
  $fieldValue = $_POST['fieldValue'];

  $this->Result->where('id', $id);
  $foundLine = $this->Result->search();
  $this->Result->arrayToModel($foundLine[0]);

  $unSerialized = json_decode($this->Result->data, True);
  $unSerialized[$fieldName] = $fieldValue;
  $serialized = json_encode($unSerialized);
  $this->Result->data = $serialized;
  $this->Result->save();
}


function removeResultSample($sampleId){

  $this->Result->where('sample', $sampleId);
  $results = $this->Result->search();

  foreach($results as $result){
    $this->Result->id = $result['id'];
    $this->Result->delete();
  }
}


function buildAssayPanel($said){

  //$this->doNotRenderHeader = 1;
  $this->render = False;

  //get all info
  $ret = $this->msProcess($said);
    
  $sa = new SampleAnalysis();
  $sa->where('id', $said);  
  $sa->select(['id', 'roaming_id', 'sample', 'assay_base', 'profile']);
  $saInfo = $sa->first();

  if($saInfo == False)
  {
    $this->_template->set('panel', 'error');
  } 
  
  else 
  {
    
    
    //use it if its on the result
    if(array_key_exists('partOfAuth', $ret))
    {
      $projAuthStatus = $ret['partOfAuth'];
    }


    //otherwise use the old method to grab it. 
    else
    {
      $projAuthStatus = upa('samples', 'partOfAuthProject', array($saInfo['sample']));    
    }
    
    
    $lockObject = upa('keyrings', 'requestLockAndStatus', array('SAMPLE', $saInfo['sample']),  False);

    $assay = new Assay();
    $assay->where('id', $saInfo['assay_base']);
    $assay->select(['dillution', 'replicates', 'type_base']);
    $assayInfo = $assay->first();
    
    $assayFields = upa('assayTypeFields', 'fetchFields', array($assayInfo['type_base']) );

    if($saInfo['roaming_id'] !=  NULL && $saInfo['roaming_id'] != 0){
      $assayProfile = upa('roamingAnalysis', 'fetchSettings', array($saInfo['roaming_id']));
    } else {
      $assayProfile = upa('assayProfiles', 'fetchByProfileAndAssay', array($saInfo['profile'], $saInfo['assay_base'] ));
    }

    //dillutions
    if(array_key_exists(0,$assayProfile)){
      $dillutionNames = json_decode($assayProfile['0']['dillutions'], True);
    } else{
      $dillutionNames = array();
    }


    //get result lines
    $this->Result->where('sa_id', $said);
    $this->Result->order('df','DESC');
    $results = $this->Result->search();

    $thisDf = False;
    $newDf = False;
    $renderContent = array();
    $panel = '';

    $i = 0;
    foreach($results as $rl){

      $rData = json_decode($rl['data'], True);

      if($rl['df'] != $thisDf){
        //new dillution found
        $newDf = True;
        $thisDf = $rl['df'];
        $index = $i;
        $renderContent[$index] = '';
        $i++;
      } else {
        $newDf = False;
      }

      if($newDf == True){
        //needs a new heading and a place to render the fields
        if($thisDf == 1){
          $dillutionNamed = '{MESA_SLU_UNDILLUTED}';
        } else {
          $dillutionNamed = array_search($thisDf, $dillutionNames);

          //automagically create
          if($dillutionNamed == false){
            $split = explode('.', $thisDf);
            $dfLen = strlen($split[1]);
            $dillutionNamed = '-' . $dfLen;
          }
        }

        $panel .= generateHTML('lookup/resultPanel', array('dF_render' => $index, 'dillution' => '{MESA_SLU_DILLUTION}: ' . $dillutionNamed ) );
      }

      if($rl['rep'] > 0){

        if($rl['rep'] == 1 || $rl['rep'] == 2){
          $replicate_text = '{rep_' . $rl['rep'] . '}';
        }  else {
          $replicate_text = 'Duplo nummer: ' . ( $rl['rep']);
        }

        $renderContent[$index] .= generateHTML('lookup/repHeader', array('replicate_text' => $replicate_text));
      }

      $first = True;
      foreach($assayFields as $field){
        $fArr['alias'] = $field['alias'];
        $fArr['name'] = $field['name'];
        $fArr['db_id'] = $rl['id'];
        $fArr['input_filter'] = $field['filter'];

        $fArr['disabled'] = '';
        if($projAuthStatus == True || $lockObject['locked'] == True){
          $fArr['disabled'] = 'disabled';
        }

        if(isset($rData[$field['name']])){
          $fArr['value'] =  $rData[$field['name']];
        } else {
          $fArr['value'] = '';
        }

        //confirmation button needed?
        #if($assayInfo['confirmation'] == 1 && $assayInfo['confirmation_type'] == 1){
        if($ret['confirmation']['enabled'] == True && $ret['confirmation']['type'] === '1' && $ret['confirmation']['requested'] == 1 && $first === True){
          $fArr['show_confirmation_button'] = '';
          $fArr['said'] = $said;
          $fArr['dF'] = $thisDf;
          $fArr['rep'] = $rl['rep'];
          $fArr['globalConf'] = '0';
        } else{
          $fArr['show_confirmation_button'] = 'hidden';
          $fArr['said'] = '';
          $fArr['dF'] = '';
          $fArr['rep'] = '';
          $fArr['globalConf'] = '';
        }

        //filter needed?
        $filter = 2;

        if($filter == 2){

        }

        $kve = checkKeyorFalse($rData, 'kve');
        // if(($assayInfo['replicates'] == '0' && $kve > $assayInfo['max_count']) || $kve == '0' || empty($kve)){
        //     $fArr['show_confirmation_button'] = 'hidden';
        // }

        if($kve == '0' || empty($kve) || $kve == '>'){
            $fArr['show_confirmation_button'] = 'hidden';
        }

        $renderContent[$index] .= generateHTML('input_int', $fArr);
        $first = False;
      }
    }

    //$this->_template->set('panel', generateHTML($panel, $renderContent, True));

    $panelHtml = generateHTML($panel, $renderContent, True);
    $this->_template->set('panel',  $panelHtml);

    $testRender = $this->_template->render(True, False, True);

    $retObj = array();
    $retObj['panel'] = $testRender;
    $retObj['uses_dillution'] = filter_var($assayInfo['dillution'], FILTER_VALIDATE_BOOLEAN);
    $retObj['uses_replicates'] = filter_var($assayInfo['replicates'], FILTER_VALIDATE_BOOLEAN);

    if($saInfo['roaming_id'] > 0){
      $retObj['is_roaming'] = True;
    } else{
      $retObj['is_roaming'] = False;
    }


    $retObj['is_authorized'] = $projAuthStatus;

    $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
  
    //echo "Did stuff in $time seconds\n";

    print json_encode($retObj, JSON_FORCE_OBJECT);
  }



}

function debugMs($script = False){

  $this->render = 0;
  $retObj = array();

  if($script == False){
    $eval = $this->evalScrub($_POST['script'], True);
  } else{
    $eval = $this->evalScrub($script, False);
  }

  if($script != False && $eval == False){
    return False;
  } elseif($script != False && $eval != False){
    return True;
  }

  if(empty($eval)){
    eval($_POST['script']);
  } else{
    print 'Found forbidden function names in script, plesae revise. Offending words were:';
    foreach($eval as $word){
      print '[' . $word . ']';
    }
  }
}

function peek($said, $return = False){
    
    $msProcess = $this->msProcess($said, True);
    $results = $msProcess['calcResult'];
    $calculation = $msProcess['calcClass'];
    
    $this->render = false;  
    
    $log =  $calculation->renderLog();

    $rawresults = upa('results', 'getTestResultsByDf', array($said), False);
    $conf = upa('confirmations', 'fetchLine', array($said), False);
    $saidInfo = upa('sampleAnalysis', 'fetch', array($said), False);        
    $sample = upa('samples', 'fetch', array($saidInfo['sample']), False);

    if(!is_array($conf))
    {
      $conf = [];
    }

    if($return == True)
    {
      return [
        'rawresults' => $rawresults,
        'conf' => $conf, 
        'saidInfo' => $saidInfo,
        'sample' => $sample,
        'results' => $results
      ];
    }

    $this->render = true;  
        
    $this->_template->set('log', $log );
    $this->_template->set('results', parray($results, True));
    $this->_template->set('raw_results', parray($rawresults, True));
    $this->_template->set('confirmations', parray($conf, True));
    $this->_template->set('said', parray($saidInfo, True));
    $this->_template->set('sample', parray($sample, True));

}



function msProcess($said, $peek = false){

  $this->render = 0;

  $storeThisResult = False;
  $uses_confirmation = NULL;
  $countable_maximum = NULL;
  $countable_minimum = NULL;
  $assayInfo = NULL;
  $reference_value = NULL;
    
  $sa = new SampleAnalysis();
  $sa->where('id', $said);
  $sa->select(['project', 'storedResult', 'assay_base', 'sample']);
  $backupInfoChannel = $sa->first();
    
  $p = new Project();
  $p->where('id', $backupInfoChannel['project']);
  $p->select('auth_status');
  $project = $p->first();

  $partOfAuth = ($project['auth_status'] == 1) ? True : False;

  if($partOfAuth == True){    
    //has stored info, if so return this.
    $storedResult = $backupInfoChannel['storedResult'];
    
    if(empty($storedResult)){      
      $storeResult =  False;
    } else{
      $storedArray = json_decode($storedResult, JSON_FORCE_OBJECT);
      if(empty($storedArray)){
        $storedResult = false;
      } else{
        $storedResult = $storedArray;
      }
    }


    if($peek === true)
    {

      $assayBase = $backupInfoChannel['assay_base'];
      $assayInfo  = upa('assays', 'fetchSingle', array($assayBase));
      $msScript = $this->checkScriptIsFile($assayInfo['script']);

      if($assayInfo['type'] == '4' ){
        $calculation = new $this->foundScriptController($said, $partOfAuth, $this->foundScriptVariables, False, $backupInfoChannel['sample']);
      } else{
        $calculation = new $this->foundScriptController($said, $partOfAuth, $this->foundScriptVariables);
      }
      
      return [
        'calcResult' => $storedResult,
        'calcClass' => $calculation
      ];
    }
    
    //$storedResult = upa('sampleAnalysis', 'retrieveStoredResult', array($said), False);
    if($storedResult == False){      
      $storeThisResult = True;
    } else{
      return $storedResult;
    }
  }


  $assayBase = $backupInfoChannel['assay_base'];    
  $assay = new Assay();
  $assay->where('id', $assayBase);
  $assay->select(['script', 'type']);
  $assayInfo = $assay->first();

  $msScript = $this->checkScriptIsFile($assayInfo['script']);

  if($msScript === True){
    $classScript = True;
  } else{
    return $this->msProcessLegacy($said);
  }


  if(isset($classScript)){
    
    //check if meta analysis, if so provide
    if($assayInfo['type'] == '4' ){
      $calculation = new $this->foundScriptController($said, $partOfAuth, $this->foundScriptVariables, False, $backupInfoChannel['sample']);
    } else{
      $calculation = new $this->foundScriptController($said, $partOfAuth, $this->foundScriptVariables);
    }

    $calcResult =  $calculation->provide();   
    $calcResult['partOfAuth'] = $partOfAuth;
    
        
    if($storeThisResult == True){
      upa('sampleAnalysis' , 'setStoredResult', array($said,$calcResult), False);
    }


    if($peek === true)
    {
      return [
        'calcResult' => $calcResult,
        'calcClass' => $calculation
      ];
    }

    else
    {
      return $calcResult;
    }
    


  }
}


function msProcessLegacy($said){

  $this->render = 0;
  $retObj = array();

  //mesa script needed variables
  $results = array();
  $confirmation_data = array();
  $uses_confirmation = NULL;
  $countable_maximum = NULL;
  $countable_minimum = NULL;
  $assayInfo = NULL;
  $reference_value = NULL;
  $uses_confirmation = False;
  $messageBag = array();
  $analysisReady = $this->saidIsComplete($said);

  $output = array();
  $outputEn = array();
  $disposition = array();

  //load results attahed to this sample analysis id
  $this->Result->where('sa_id', $said);
  $this->Result->order('df', 'DESC');
  $rlRes = $this->Result->search();

  if(empty($rlRes)){
    //no results, use the backup sample upa to retreive some vital information
    $backupInfoChannel = upa('sampleAnalysis', 'fetch', array($said));
    $sample = $backupInfoChannel['sample'];
    $assayBase = $backupInfoChannel['assay_base'];
    $profile = $backupInfoChannel['profile'];
    $assay = $backupInfoChannel['assay'];
    $roamingId = $backupInfoChannel['roaming_id'];
  }

  //create the needed results array
  foreach($rlRes as $rl){
    $sample = $rl['sample'];
    $assayBase = $rl['assay_base'];
    $profile = $rl['profile'];
    $assay = $rl['assay'];
    $roamingId = $rl['roaming_id'];
    $data = json_decode($rl['data'], true);
    foreach($data as $field => $fieldValue){
      $results[$rl['df']][$rl['rep']][$field] = $fieldValue;
    }
  }


  $partOfAuth = pa('samples', 'partOfAuthProject', array($sample), 0);
  $retObj['authorised'] = $partOfAuth;

  //fetch assay info
  $assayInfo  = pa('assays', 'fetchSingle', array($assayBase));
  $confirmation_type = $assayInfo['confirmation_type'];
  $confirmation_methods = json_decode($assayInfo['confirmation_script'], True);


  //fetch needed output parameters
  $meta = array();
  if($assayInfo['type'] == 4){
    $fullAnalysisList = upa('sampleAnalysis', 'fetchAnalysisArray', array($sample, $said));
    foreach($fullAnalysisList as $metaAnalysis){
      $metaReturn = $this->msProcess($metaAnalysis['id']);
      $meta[$metaAnalysis['assay_base']] = $metaReturn['output'];
    }
  }


  /*/*setup confirmation
  if($assayInfo['confirmation'] == 1){
  //confirmation is used, check how its setup
  $uses_confirmation = True;
  $confLine = pa('confirmations', 'fetchLine', array($said));
  $confirmation_data = json_decode($confLine['data'], True);
}*/


//msScript
$msScript = $this->checkScriptIsFile($assayInfo['script']);

$reportIn = 'kve';

//setup count min-max
$countable_maximum = $assayInfo['max_count'];
$countable_minimum = $assayInfo['min_count'];


//uses confirmation
if($assayInfo['confirmation'] == '1'){
  $uses_confirmation = True;
  $confLine = pa('confirmations', 'fetchLine', array($said));
  $confirmation_data = json_decode($confLine['data'], True);

  if($assayInfo['confirmation_type'] == '0'){
    $retObj['confirmation']['enabled'] = True;
  }

} else{
  $uses_confirmation = False;
  $confirmation_data = array();
}


//set limits for this assay or roaming assay
if($roamingId !== NULL && $roamingId <> 0){
  $temp = upa('roamingAnalysis', 'fetchSettings', array($roamingId));
  $profileInfo = $temp['0'];  //bit of  hack to get rid of the leading zero
} else {
  $temp = upa('assayProfiles', 'fetchByProfileAndAssay', array($profile, $assayBase));
  $profileInfo = $temp['0'];  //bit of  hack to get rid of the leading zero
}

$reference_value = $profileInfo['reference'];

//we should put something in here to prevent malicious code injection.
//not that it should be able for a user to GET to here, but you never know.
//$runable = $this->evalScrub($assayInfo['script']);
$runable = $this->evalScrub($msScript);


if($runable == False){
  $output['Error'] = 'Script is not valid';
}else{
  // @eval($assayInfo['script']);
  @eval($msScript);
}

if(!isset($output) ||  !is_array($output)){
  $retObj['output'] = array();
  return $retObj;
}

$retObj['output'] = $output;
$retObj['messageBag'] = $messageBag;
$retObj['reportIn'] = $reportIn;
$retObj['outputEn'] = $outputEn;
$retObj['disposition'] = $disposition;

foreach($retObj['output'] as $outputParam => $outputValue){

  $vetoResult = pa('vetoResults', 'checkVeto', array($said, $outputParam), 0);

  if($vetoResult !== False){
    $retObj['output'][$outputParam] = $vetoResult;
  }
}
return $retObj;
}

private function checkScriptIsFile($msScript){

  preg_match("/file:(.*)$/i", $msScript, $fileMatch);
  $msScriptContents = preg_replace("/file:(.*)$/i", "", $msScript);

  if(!empty($fileMatch)){

    $msScriptFile = ROOT . '/app/private/templateScripts/'  . $fileMatch[1] . '.php';
    $msClassFile =  ROOT . '/app/private/templateScripts/'  . $fileMatch[1] . '.class.php';

    if(file_exists($msClassFile)){
      $this->foundScriptController = $fileMatch[1];
      $pattern = '/\$(.*)/';
      preg_match_all($pattern, $msScript, $varMatches);
      $external = array();
      if(!empty($varMatches[1])){

        foreach($varMatches[1] as  $hit){
          $varInfo = explode('=', $hit);          
          $external[$varInfo[0]] = trim($varInfo[1]);
        }
      }
      $this->foundScriptVariables = $external;
      return True;
    } elseif(file_exists($msScriptFile)){
      $msScriptContents .= file_get_contents($msScriptFile);
    }
  }

  return $msScriptContents;
}

function fetchOutputFields($assayBase){


  $assayInfo  = upa('assays', 'fetchSingle', array($assayBase));
  
  $msScript = $this->checkScriptIsFile($assayInfo['script']);

  if($msScript === True){

    $script = new $this->foundScriptController(False, False, False, True, False);
    return $script->reportOutput();
  } else{
    $this->evalScrub($msScript);
    @eval($msScript);
    if(!isset($output)){
      return array();
    } else {
      return $output;
    }
  }
}



function renderEndResultsProject($said, $assayName, $sa = False){

  $this->doNotRenderHeader = 1;

  $ret = $this->msProcess($said);

  
  //passed by reference, can skip the query 
  if($sa == False)
  {
    dd('have to select');
    $sa = upa('sampleAnalysis', 'fetch', array($said), False);
  }


  

   if($sa['roaming_id'] != 0 && $sa['roaming_id'] != NULL ){
      $resultForProfile = upa('roamingAnalysis', 'fetchSettings', array($sa['roaming_id']));
    } else {
      $resultForProfile = upa('assayProfiles', 'fetchProfileById', array($sa['assay']));
    }

    $references = json_decode($resultForProfile[0]['reference'], True);
    $referenceValue = checkKeyorFalse($references, 'ref_' . $ret['reportIn']);


  $readyHider = null;
  if($sa['is_ready'] == 1){
    $readyHider = '';
  } else{
    $readyHider = 'hide';
  }

  $confHider = null;
  $confIcon = '';
  if($sa['conf_requested'] == 0){
    $confHider = 'hide';
  } elseif($sa['conf_requested'] == 1){
    $confHider = '';
    $confIcon = 'eye-open';
  } elseif($sa['conf_requested'] == 2){
    $confHider = '';
    $confIcon = 'eye-close';
  }

  //this should be the rowspan for the said column
  $resultH = count($ret['output']);
  $render = '';

  $table = new tableFactory();
  $table->legoMode();
  $table->loadTemplate('projectEndResults');
  $i = 0;

  //authorised
  foreach($ret['output'] as $name => $value){

    if(array_key_exists('resultHide', $ret)){
      if(in_array($name, $ret['resultHide'])){
        $resultH = $resultH - 1;
        continue;
      }
    }

    if(array_key_exists('resultMask', $ret)){
      if(array_key_exists(  $name, $ret['resultMask'])){
        $arr['result_name'] = $ret['resultMask'][$name];
      } else{
        $arr['result_name'] = $name;
      }
    }

    $arr['result_value'] = $value;
    $arr['assay'] = $assayName;
    $arr['rowspan'] = $resultH;
    $arr['said'] = $said;
    $arr['parameter'] = $name;
    $arr['currentValue'] = $value;
    $arr['ready_hider'] = $readyHider;
    $arr['conf_hider'] =  $confHider;
    $arr['conf_icon'] = $confIcon;
    $arr['reference'] = $referenceValue;

    if($ret['authorised'] == True){
      $arr['veto_hide'] = 'hidden';
    } else {
      $arr['veto_hide'] = '';
    }


    if(checkKey($ret, 'vetoReasons', $said, $name)){
      $arr['currentReason'] = $ret['vetoReasons'][$said][$name];
    } else{
      $arr['currentReason'] = '';
    }

    if($i == 0){
      $table->useBrick('resultLineNew', $arr);
    } else {
      $table->useBrick('resultLine', $arr);
    }

    $i++;
  }

  return $table->returnRender();
}

function endResultsAsJson($said){
  $this->render = false;
  $saidResults = upa('sampleAnalysis', 'fetch', array($said), False);
  $ret = $this->msProcess($said);
  return json_encode($ret, JSON_FORCE_OBJECT);
}

function renderEndResults($said, $resultHandlerRerender = False, $saidResults = False){

  $this->doNotRenderHeader = 1;
  $confRequested = False;
  $confDenied = False;
  $askForMetaConfirmation = False;


  if($said == 'undefined'){
    return;
  }

  //TODO: refactor
  //if($saidResults == False)
  //{
    $saidResults = upa('sampleAnalysis', 'fetch', array($said), False);
  //}
  

  if($resultHandlerRerender == False)
  {
    $ret = $this->msProcess($said);
  }

  else
  {
    $ret = $resultHandlerRerender;
  }

  $resultValues = checkKeyOrFalse($ret, 'output') ;
  $messageBag = checkKeyOrEmpty($ret, 'messageBag');
  $isReady = checkKeyOrFalse($ret, 'isReady');
  $askForMetaParentConfirmation = checkKeyOrFalse($ret, 'askForMetaParentConfirmation');
  $metaParent =  checkKeyOrFalse($ret, 'metaParent');

  //global conf window needed?
  //we also need to check here, for meta analysis only, if conf was requested already
  //and if not, ask if it needs to be on.
  if(isset($ret['confirmation']['enabled']) && $ret['confirmation']['enabled'] == True){

    if($saidResults['conf_requested'] == 0){
      //$askForMetaConfirmation = $ret['askForMetaConfirmation'];
      $askForMetaConfirmation = checkKeyOrFalse($ret, 'askForMetaConfirmation');
    }

    $showConfButton = 'hide';
    $showCancelConf = 'hide';
    $showEnableConf = 'hide';
    $reset_allowed = 'hide';

    if($saidResults['conf_requested'] == 1){
      $showConfButton = '';
      $showCancelConf = '';
      $confRequested = True;

      //user has access to conf-reset? 
      $reset_allowed = (upa('groupPrivileges', 'checkGUI', ['resetConfirmation'], False ) == true) ? '' : 'hide';

    } elseif($saidResults['conf_requested'] == 2){
      $confDenied = True;
      $showEnableConf = '';
    }

    //global
    if($ret['confirmation']['type'] == 0){
      $confButton = generateHTML('lookup/confButton', array('reset_allowed' => $reset_allowed, 'show_conf_cancel' => $showCancelConf, 'show_conf_button'=>$showConfButton, 'show_enable_conf' => $showEnableConf, 'said'=> $said));
    }

    //per plate
    if($ret['confirmation']['type'] == 1){
      $showConfButton = 'hide';
      $confButton = generateHTML('lookup/confButton', array('reset_allowed' => $reset_allowed,'show_conf_cancel' => $showCancelConf, 'show_conf_button'=>$showConfButton, 'show_enable_conf' => $showEnableConf, 'said'=> $said));
    }


  } else {
    $confButton = '';
  }

  if(empty($resultValues)){
    global $lang;
    $tblValues = $lang['MESA_SLU_NOENDRESULTS'];
  }
  else{
    $i = 0;
    foreach($resultValues as $resultName => $resultValue){

    if(array_key_exists('resultHide', $ret)){
      if(in_array($resultName, $ret['resultHide'])){
        continue;
      }
    }


    if(array_key_exists('resultMask', $ret)){
      if(array_key_exists($resultName, $ret['resultMask'])){
        $tblValues[$i]['name'] =  $ret['resultMask'][$resultName];
      } else{
        $tblValues[$i]['name'] = $resultName;
      }
    }


      $tblValues[$i]['value'] = $resultValue;
      if($i == 0){
        $tblValues[$i]['confirmation'] = $confButton;
      }  else{
        $tblValues[$i]['confirmation'] = '';
      }
      $i++;
    }
  }

  $tF = new tableFactory();
  $tF->setTableId('endResultsTable');
  $tF->loadTemplate('endResults');
  $tF->loadValues($tblValues);
  $render =  $tF->renderTable();

  $retObj = array();
  $retObj['panel'] = $render;
  $retObj['messageBag'] = array();
  $retObj['confRequested'] = $confRequested;
  $retObj['isReady'] = $isReady;
  $retObj['confDenied'] = $confDenied;
  $retObj['askForMetaConfirmation'] = $askForMetaConfirmation;
  $retObj['askForMetaParentConfirmation'] = $askForMetaParentConfirmation;
  $retObj['metaParent'] = $metaParent;

  foreach($messageBag as $bagId => $bagMessage){
    $retObj['messageBag'][$bagId] = $bagMessage;
  }

  if($resultHandlerRerender == False)
  {
    print json_encode($retObj, JSON_FORCE_OBJECT);
  }

  else
  {
    return $retObj; 
  }
  
}

function evalScrub($evalCode, $debug = False){

  return true;

  $forbidden=array(
    'exec',
    'passthru',
    'system',
    'shell_exec',
    '``',
    'popen',
    'proc_open',
    'pcntl_exec',
    'eval',
    'assert',
    'preg_replace',
    'create_function',
    'include',
    'include_once',
    'require',
    'require_once',
    '$_GET',
    '$_POST',
    '$_SESSION',
    '$_GLOBALS',
    '$_FILES',
    '$_SERVER',
    '$_COOKIE',
    'curl',
    'move_uploaded_file',
    'ob_start',
    'array_diff_uassoc',
    'array_diff_ukey',
    'array_filter',
    'array_intersect_uassoc',
    'array_intersect_ukey',
    'array_map',
    'array_reduce',
    'array_udiff_assoc',
    'array_udiff_uassoc',
    'array_udiff',
    'array_uintersect_assoc',
    'array_uintersect_uassoc',
    'array_uintersect',
    'array_walk_recursive',
    'array_walk',
    'assert_options',
    'uasort',
    'uksort',
    'usort',
    'preg_replace_callback',
    'spl_autoload_register',
    'iterator_apply',
    'call_user_func',
    'call_user_func_array',
    'register_shutdown_function',
    'register_tick_function',
    'set_error_handler',
    'set_exception_handler',
    'session_set_save_handler',
    'sqlite_create_aggregate',
    'sqlite_create_function',
    'phpinfo',
    'posix_mkfifo',
    'posix_getlogin',
    'posix_ttyname',
    'getenv',
    'get_current_user',
    'proc_get_status',
    'get_cfg_var',
    'disk_free_space',
    'disk_total_space',
    'diskfreespace',
    'getcwd',
    'getlastmo',
    'getmygid',
    'getmyinode',
    'getmypid',
    'getmyuid',
    'extract',
    'parse_str',
    'putenv',
    'ini_set',
    'mail',
    'header',
    'proc_nice',
    'proc_terminate',
    'proc_close',
    'pfsockopen',
    'fsockopen',
    'apache_child_terminate',
    'posix_kill',
    'posix_mkfifo',
    'posix_setpgid',
    'posix_setsid',
    'posix_setuid',
    'fopen',
    'tmpfile',
    'bzopen',
    'gzopen',
    'SplFileObject->__construct',
    'chgrp',
    'chmod',
    'chown',
    'copy',
    'file_put_contents',
    'lchgrp',
    'lchown',
    'link',
    'mkdir',
    'move_uploaded_file',
    'rename',
    'rmdir',
    'symlink',
    'tempnam',
    'touch',
    'unlink',
    'imagepng ',
    'imagewbmp ',
    'image2wbmp',
    'imagejpeg ',
    'imagexbm  ',
    'imagegif  ',
    'imagegd   ',
    'imagegd2  ',
    'iptcembed',
    'ftp_get',
    'ftp_nb_get',
    'file_exists',
    'file_get_contents',
    'file',
    'fileatime',
    'filectime',
    'filegroup',
    'fileinode',
    'filemtime',
    'fileowner',
    'fileperms',
    'filesize',
    'filetype',
    'glob',
    'is_dir',
    'is_executable',
    'is_file',
    'is_link',
    'is_readable',
    'is_uploaded_file',
    'is_writable',
    'is_writeable',
    'linkinfo',
    'lstat',
    'parse_ini_file',
    'pathinfo',
    'readfile',
    'readlink',
    'realpath',
    'stat',
    'gzfile',
    'readgzfile',
    'getimagesize',
    'imagecreatefromgif',
    'imagecreatefromjpeg',
    'imagecreatefrompng',
    'imagecreatefromwbmp',
    'imagecreatefromxbm',
    'imagecreatefromxpm',
    'ftp_put',
    'ftp_nb_put',
    'exif_read_data',
    'read_exif_data',
    'exif_thumbnail',
    'exif_imagetype',
    'hash_file',
    'hash_hmac_file',
    'hash_update_file',
    'md5_file',
    'sha1_file',
    'highlight_file',
    'show_source',
    'php_strip_whitespace',
    'get_meta_tags',
    'str_replace',
    'define',
    'ALPC_DB_NAME',
    'ALPC_DB_USER',
    'ALPC_DB_PASSWORD',
    'ALPC_DB_HOST',
    'mb_internal_encoding',
    'error_reporting',
    'runkit_function_rename',
    'dotnet_load',
    'new COM',
    'new Java',
    'event_new',
    'base64',
    'str_repeat',
    'unserialize',
    'register_tick_function',
    'register_shutdown_function',
    'pcntl_signal',
    'pcntl_alarm',
    'set_time_limit'
  );

  $foundForbArray = array();

  foreach($forbidden as $word){
    if(strstr(strtolower($evalCode), strtolower($word))){
      if($debug == False){
        //throwError('SCRIPT_HAS_FORBIDDEN_VALUES');
        return False;
      } else{
        array_push($foundForbArray, $word);
      }
    }
  }

  if($debug == True){
    return $foundForbArray;
  }

  return True;
}

function returnFirstElement($said, $followNo){
  $this->Result->where('sa_id', $said);
  $this->Result->where('follow_no', $followNo);
  $this->Result->limit(1);
  $result = $this->Result->search();

  if(!empty($result)){
    return $result['0']['id'];
  }
}

function projOverview($project){

  dd('overv');
  $this->renderAlternateHeader = 'slim';

  //fetch main info
  $projInfo = pa('projects', 'fetchProjectInfo', array($project));
  $render['proj_name'] = $projInfo['project_name'];
  $render['proj_date'] = date('d-m-Y', $projInfo['project_date']);
  $render['client_name'] = customerIdToName($projInfo['client']);
  $render['subclient_name'] = subclientIdToName($projInfo['subclient']);
  $render['samples_in_proj'] = pa('projects', 'countSamplesInProject', array($project));

  $cFieldVal = json_decode($projInfo['custom_fields'], True);

  //fetch project customFields
  $render['custom_project_fields'] = '';
  $cFields = pa('projectFields', 'fetchProjectFields',array());

  foreach($cFields as $cField){
    $render['custom_project_fields'] .= generateHTML('results/customProjectField', array('alias' => $cField['alias'], 'value' => $cFieldVal[$cField['name']] ));
  }

  //fetch all samples in this
  $projSamples = pa('samples', 'fetchSamplesInProject', array($project));
  $render['result_contents'] = '';

  foreach($projSamples as $sample){
    $sampleSa = pa('sampleAnalysis', 'fetchAnalysisArray', array($sample['id']));
    $sampRender = array();
    $sampRender['sample_name'] = $sample['description'];
    $sampRender['results'] = '';

    foreach($sampleSa as $analysis){
      $assayBase = pa('assays', 'fetchSingle', array($analysis['assay_base']));
      $sampRender['results'] .= pa('results', 'renderEndResultsProject', array($analysis['id'], $assayBase['name']));
    }

    $render['result_contents'] .= generateHTML('results/overviewSampleWrap', $sampRender);
    unset($sampRender);
  }
  $this->_template->setByArray($render);
}



function getCurrentDillutions($said, $type = 'JSON', $sort = 'ASC'){

  $this->render = False;
  $this->Result->where('sa_id', $said );

  

  $rls = $this->Result->search(); 

//  parray($rls);

  array_walk($rls, function(&$value, &$key) use(&$rls) {
    $rls[$key]['df'] = (float)$value['df'];
  });
  
  
  $df  = array_column($rls, 'df');
  
  if($sort == 'ASC')
  {
    array_multisort($df, SORT_ASC, $rls);
  }

  else
  {
    array_multisort($df, SORT_DESC, $rls);
  }
  
  

  $dfArr = array();
  $followArr = array(); 



  foreach($rls as $rl){
    array_push($dfArr, $rl['df']);

    $txtDf = rtrim(rtrim(sprintf('%.10F',  $rl['df']), '0'), ".");

    if(!array_key_exists($txtDf, $followArr))
    {
      $followArr[$txtDf] = $rl['follow_no'];
    }
    
  }    

  if($type == 'JSON'){

    $retDillutions = '';
    foreach($dfArr as $df){

      //this prevents PHP from prining numbers below 1E-4 in scientific notations
      //which we do not want in this case. 
      $df = rtrim(rtrim(sprintf('%.10F', $df), '0'), ".");

      $split = explode('.', $df);

      if(isset($split[1])){
        $dfLen =  '-' . strlen($split[1]);
      } else{
        $dfLen = '0';
      }

      
      $retDillutions .=  $dfLen .  '=' . $df . '&#13;';
    }

    $retObj = array();
    $retObj['dillutions'] = $retDillutions;
    print json_encode($retObj, JSON_FORCE_OBJECT);
    }

    if($type == 'printing_array'){
      $retObj = array();
      $retDillutions = array();
      $foundDf = array();
      $repN = array();
      

      foreach($dfArr as $df){


          $df = rtrim(rtrim(sprintf('%.10F', $df), '0'), ".");
//          $df = (string)$df;

          $split = explode('.', $df);

          if(isset($split[1])){
            $dfLen =  '-' . strlen($split[1]);
          } else{
            $dfLen = '0';
          }

          if(!in_array($df, $foundDf)){
            $retDillutions[$dfLen] = $df;
            $repN[$df] = 0;
            array_push($foundDf, $df);
          } else{
            $repN[$df] = $repN[$df] + 1;
            //$repN++;
          }
      }

      $retObj['dillutions'] = $retDillutions;
      $retObj['dfs'] = $foundDf;
      $retObj['rep'] = $repN;
      $retObj['follow_no'] = $followArr;

      return $retObj;

    }
}

function saveNewDillutions($said){

  $this->render = False;
  $dillutionPost = trim($_POST['dillutions']);
  $saidInfo = upa('sampleAnalysis', 'fetch', array($said), False);
  $sampleInfo = upa('samples', 'fetch', array($saidInfo['sample']), False);

  $roamId = $saidInfo['roaming_id'];
  $replicateNumber = 0;

  if(!is_null($roamId)){
    //fetch replicates from roaminganalysis
    $roamInfo = upa('roamingAnalysis', 'fetch', array($roamId), False);
    if(!empty($roamInfo)){
      $replicateNumber = $roamInfo['replicates'];
    }

  } else{
    //fetch replicates from assayprofiles
    $assayInfo = upa('assayProfiles', 'fetch', array($saidInfo['assay']), False);
    if(!empty($assayInfo)){
      $replicateNumber = $assayInfo['replicates'];
    }
  }


  $nextAvailableFollow = false;
  $replicateArray = array();

  $event = 'Verdunningen gewijzigd voor  ' . $sampleInfo['barcode'];
  upa('changeTracker', 'changed', array(10, $sampleInfo['project'], $sampleInfo['id'], $said, $event, False, $dillutionPost), False);

  $dillutionArr = array();
  if(!empty($dillutionPost)){
    $textAr = explode("\n", $dillutionPost);
    $textAr = array_filter($textAr, 'trim');
    foreach ($textAr as $line) {
      $dInst = explode('=', $line);
      if(isset($dInst['0']) && isset($dInst['1'])){
        $dillutionText = str_replace(',', '.', trim($dInst['1']));
        $dillutionArr[$dInst['1']] = $dillutionText;

        if(checkKeyOrFalse($replicateArray, $dInst['1']) === False){
            $replicateArray[$dInst['1']] = 0;
        } else{
            $replicateArray[$dInst['1']] = $replicateArray[$dInst['1']] + 1;
        }

      }
    }
  }else{
    $dillutionArr['0'] = '1';
  }

  //load in existing dillutions
  $this->render = False;
  $this->Result->where('sa_id', $said );
  $this->Result->order('follow_no', 'ASC');
  $rls = $this->Result->search();

  //if not empty check what to remove and add new follow
  if(!empty($rls)){

    $endRls = end($rls);
    $lastFollow = $endRls['follow_no'] + 1;
    reset($rls);

    //check removals
    //if it exists in the old array, but not int he new one.. remove it
    $currentRep = 0;
    foreach($rls as $existingResultLine){

      $removeForRep = False;
      if($existingResultLine['rep'] > $replicateArray[$existingResultLine['df']]){
        $removeForRep = True;
      }

      if(!in_array($existingResultLine['df'], $dillutionArr, true) || $removeForRep == True){
        $this->Result->id = $existingResultLine['id'];
        $this->Result->remove();
        unset($this->Result->id);
        $lastFollow--;
      }
    }
  } else{
    $lastFollow = 1;
  }



  //check additions
  //if it exists in the new array, but not int he old one, add the dl
  foreach($dillutionArr as $dillution=>$df){



    $findExisting = array_extractor($rls, 'df', $df);
    $replicateN = checkKeyOrFalse($replicateArray, $df);
    if($replicateN == False){

      $replicateN = 0;
    }

    for($i = 0; $i <= $replicateN;$i++){

      $findExisting = array_extractor($rls, 'df', $df);
      $findExisting = array_extractor($findExisting, 'rep', $i);

      if(empty($findExisting)){
        $this->Result->sample = $saidInfo['sample'];
        $this->Result->sa_id = $saidInfo['id'];
        $this->Result->follow_no = $lastFollow;
        $this->Result->profile = $saidInfo['profile'];
        $this->Result->assay = $saidInfo['assay'];
        $this->Result->assay_base = $saidInfo['assay_base'];
        $this->Result->roaming_id = $saidInfo['roaming_id'];
        $this->Result->df = $df;
        $this->Result->rep = $i;

        $checkOutput = upa('results','fetchOutputFields', array($saidInfo['assay_base']), False  );
        $checkOutput = checkArrayOrEmpty($checkOutput);

        $preloadedDataArr = array();
        foreach($checkOutput as $outputKey => $dummyVar){
          $preloadedDataArr[$outputKey] = '';
        }

        #$this->Result->data = '{}';
        $this->Result->data = json_encode($preloadedDataArr, JSON_FORCE_OBJECT);

        $this->Result->save();
        $lastFollow++;
      }

      //to fix #735
      upa('confirmations', 'updateLine', array($saidInfo['id'], $df, $i), false);
    }
   }
  }

  function saidIsComplete($said){

    $this->Result->where('sa_id',  $said);
    $rLines = $this->Result->search();
    $complete = True;

    foreach($rLines as $rLine){
      $data = json_decode($rLine['data']);
      foreach($data as $key=>$value){
        if($value == '' || $value == NULL){
          $complete = False;
        }
      }
    }

    return $complete;
  }


  public function findResultId($sample, $assayBase, $df, $rep )
  {
    $this->Result->where('sample', $sample);
    $this->Result->where('assay_base', $assayBase); 
    $this->Result->where('df', $df);
    $this->Result->where('rep', $rep);
    $result = $this->Result->search();
    
    return (!empty($result)) ? $result[0]['id'] : False; 

  }


  public function testingFindResultId($sample, $assayBase, $df, $rep )
  {
    $this->Result->where('sample', $sample);
    $this->Result->where('assay_base', $assayBase); 
    $this->Result->where('df', $df);
    $this->Result->where('rep', $rep);
    $result = $this->Result->search();
    
    return (!empty($result)) ? $result[0]['id'] : False; 

  }



}
