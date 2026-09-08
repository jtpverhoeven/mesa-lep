<?PHP

class assuranceFormsController extends controller{

  public $cutoffDate; 

  function beforeAction($queryString)
  {
      $this->_template->set('MESA_LIMS_ACTIVE', 'active');
      $this->_template->set('MESA_SOCIAL_ACTIVE', '');
      $this->_template->set('MESA_ADMIN_ACTIVE', '');

      //bit of a hack, but there needs to be 
      $this->cutoffDate =  DateTime::createFromFormat('d-m-Y', '22-09-2021'); 
  }


  function view($date = False){

    if($date == False){
      $thisDayForm = date('d-m-Y', time());
    } else{
      $thisDayForm = date('d-m-Y', $date);
    }


    $empties = upa('assuranceForms', 'checkEmpties', array(False), False);

    $errors  = '';
    foreach($empties as $errorForm){
      $errors .= "<option value='" . $errorForm['date'] ."'>" . $errorForm['readableDate'] . "</option>";
    }

    $this->_template->set('this_errors', $errors);
    $this->_template->set('this_date', $thisDayForm);
  }

  function fetchByDate($date){

    $this->render = False;
    $this->AssuranceForm->where('date', $date );

    $result = $this->AssuranceForm->search();

    if(empty($result)){
      return False;
    } else{
      return $result[0];
    }

  }


  private function createUserDrop($users, $selected = False){

    $dropper = '<option value="0"></option>';
    foreach($users as $userId => $userName){
      $sel = '';
      if($selected == $userId){
        $sel = 'SELECTED="SELECTED" ';
      }

      $dropper .= '<option value="' . $userId . '"' . $sel . ' >' . $userName . '</option>';
    }

    return $dropper;
  }

  public function removeForm($id){
    $this->AssuranceForm->id = $id;
    $this->AssuranceForm->delete();
    return;
  }

  public function show_array($id)
  {
    $this->render = False;

    $this->AssuranceForm->where('id', $id);
    $result = $this->AssuranceForm->search();

    if(!empty($result)){

      $form = $result[0];
      $date = $form['date'];
      $data = json_decode($form['data'], JSON_FORCE_OBJECT);

      parray($data);



    }
  }

  public function scanForFormUpdates($id){
    $this->render = False;

    $this->AssuranceForm->where('id', $id);
    $result = $this->AssuranceForm->search();

    if(!empty($result)){

      $form = $result[0];
      $date = $form['date'];
      $data = json_decode($form['data'], JSON_FORCE_OBJECT);

        //$samples = upa('samples', 'getSamplesForInnocDate', array($date), False);

      //
      //grab all in one query as part of speed opimization update  #830
      //
      $beginOfDay = strtotime("midnight", $date);
      $endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
      $sqlSamples = 'SELECT id FROM samples WHERE sample_innoculated >= :begin_day AND sample_innoculated <= :end_day;';

      $params = array();
      $params['begin_day'] = $beginOfDay;
      $params['end_day'] = $endOfDay;
      $samples = $this->AssuranceForm->customQuery($sqlSamples, $params);

      if(empty($samples)){
        upa('assuranceForms', 'removeForm', array($id), False);
        return False;
      }


      $sampleIDs = array_column($samples, 'id');
      if(empty($sampleIDs)){  return array();   }
      $idMap = implode(',', array_map('intval', $sampleIDs));

      //
      //fetch analysis arr.
      //
      $sqlSA = 'SELECT id,sample,assay_base,conf_requested FROM sampleanalysis  WHERE sample IN (' . $idMap . ') ';
      $globalSampleSA = $this->AssuranceForm->customQuery($sqlSA, array());
      $sortedglobalSampleSA = array();
      foreach($globalSampleSA as $key => $item){
         $sortedglobalSampleSA[$item['sample']][$item['id']] = $item;
      }
      ksort($sortedglobalSampleSA, SORT_NUMERIC);

      //
      //fetch mediaidlist
      //
      $fullMediaIdList = upa('assays', 'fetchMediaAssuranceFormStreamlined', array($globalSampleSA), False);

      $sampleMediaList = checkKeyOrEmpty($fullMediaIdList, 'mediaList');
      $sampleAssays = checkKeyOrEmpty($fullMediaIdList, 'assayList');

      $dayMedia = array();
      $dayDurations = array();


      foreach($samples as $sample){
        //grab saids
        //$thisSampleSA = upa('sampleAnalysis', 'fetchAnalysisArray' , array($sample['id']), False);
        $thisSampleSA = checkKeyOrEmpty($sortedglobalSampleSA, $sample['id']);
        

        //$mediaIdList = upa('assays', 'fetchMedia', array($thisSampleSA), False);
        $mediaIdList = checkKeyOrEmpty($sampleMediaList, $sample['id']);

        foreach($thisSampleSA as $thisSA){

          //$thisAssay = upa('assays', 'fetch', array($thisSA['assay_base']), false);
          $thisAssay = checkKeyOrEmpty($sampleAssays, $thisSA['assay_base']);


          //push to day-durations
          if(!in_array($thisAssay['duration'], $dayDurations)){
            array_push($dayDurations, $thisAssay['duration']);
          }

          //MCP-456: Removing functionality of duration tracking     
          //also add to array if not there yet
          /*
          if(!empty($thisAssay['duration']) && $thisAssay['duration'] != '0'){

            $present = checkKeyOrFalse($data, 1, $thisAssay['duration']);

            if($present === False){
               $checkRootPath = checkKeyOrFalse($data, 1);
               if($checkRootPath === False){
                 $data[1] = array();
               }

               $checkSpecPath = checkKeyOrFalse($data, 1, $thisAssay['duration']);
               if($checkSpecPath === False){
                 $data[1][$thisAssay['duration']] = array();
               }

               $datetime = new DateTime('@' . $date);
               $datetime->add(new DateInterval('P1D'));
               $interval = 'P' . $thisAssay['duration'] . 'D';
               $datetime->add(new DateInterval($interval));
               $uitStoofDate = $datetime->format('d-m-Y');
              

               $data[1][$thisAssay['duration']]['datum_uitstoof'] = $uitStoofDate;
               $data[1][$thisAssay['duration']]['tijd_uitstoof'] = '';

               $holidays = new holidays();               
               $afleesDate = $holidays->getReadDate($uitStoofDate);

               $data[1][$thisAssay['duration']]['datum_aflezen'] = $afleesDate;
               $data[1][$thisAssay['duration']]['tijd_aflezen'] = '';
               $data[1][$thisAssay['duration']]['afgelezen_door'] = '';
            }
          }*/
        }

        foreach($mediaIdList as $mediaId){

          if(!in_array($mediaId, $dayMedia)){
              array_push( $dayMedia,$mediaId);
          }
        }
      }



      $media = upa('media', 'fetchAll', array(True, False, True), False);

      foreach($media as $dbMedia){
        $dbMediaId = $dbMedia['id'];

        if(in_array($dbMediaId, $dayMedia)){
          $keyFound = True;

          if(!array_key_exists('3', $data)){
            $keyFound = False;
            $data[3] = array();
          } else{
            if(!array_key_exists($dbMediaId, $data['3'])){
              $keyFound = False;
            }
          }

          if($keyFound == False){
            $data[3][$dbMediaId] = '';
            //check for sup array
            $suppArray =  json_decode($media[$dbMediaId]['supplements'], JSON_FORCE_OBJECT);
            if(!empty($suppArray)){
              foreach($suppArray as $supMedia){
                $compositeSuppName = "extra_" . $dbMediaId . "_" . $supMedia['supplementId'];
                $data[3][$compositeSuppName] = '';
              }
            }
          }

          if($keyFound == True){
            //check if somethign changed in this media's extra.
            $suppArray =  json_decode($media[$dbMediaId]['supplements'], JSON_FORCE_OBJECT);

            //need to add?
            if(!empty($suppArray)){
              foreach($suppArray as $supMedia){
                $compositeSuppName = "extra_" . $dbMediaId . "_" . $supMedia['supplementId'];
                if(!array_key_exists($compositeSuppName, $data['3'])){
                  $data[3][$compositeSuppName] = '';
                }
              }
            }

            //need to remove?
            foreach($data[3] as $data3var => $data3varValue){
              $data3varexpl = explode('_', $data3var);
              if(is_array($data3varexpl) && $data3varexpl[0] == 'extra' && $data3varexpl[1] == $dbMediaId ){
                if($data3varexpl[1] == $dbMediaId ){
                  $suppIdPresent = checkKeyOrFalse($suppArray, $data3varexpl[2]);
                  if($suppIdPresent == False){
                    unset($data[3][$data3var]);
                  }
                }
              }
            }
          }
        }else{
          //this seems to be not in daymedia anymore
          //check if the key was there, and remove it otherwise
          //
          if(checkKey($data, 3, $dbMediaId )){
            unset($data[3][$dbMediaId]);
          }

          $suppArray =  json_decode($media[$dbMediaId]['supplements'], JSON_FORCE_OBJECT);
          if(!empty($suppArray)){
            foreach($suppArray as $supMedia){
                $compositeSuppName = "extra_" . $dbMediaId . "_" . $supMedia['supplementId'];
                if(checkKey($data, 3, $compositeSuppName )){
                    unset($data[3][$compositeSuppName]);
                }
            }
          }
        }
      }

      //round up any "changed to no date ones"
      if(array_key_exists(3, $data)){
        foreach($data[3] as $setMediaId => $setMediaValue){

          $exploded = explode('_', $setMediaId);

          if(count($exploded) > 1 && $exploded[0] == 'extra'){
            $exploMediaId = $exploded[1];
            if(!array_key_exists($exploMediaId, $media)){
              unset($data[3][$setMediaId]);
            }
          } else {
            if(!array_key_exists($setMediaId, $media)){
              unset($data[3][$setMediaId]);
            }
          }
        }
      }


      //check day-duration deletions.
      $dayDurationFoundArray = checkKeyOrEmpty($data, 1);
      foreach($dayDurationFoundArray as $thisDuration => $thisDurationInfo){

          if(in_array($thisDuration, $dayDurations)){
          }else{
            unset($data[1][$thisDuration]);
          }
      }

      if(array_key_exists(5, $data) && array_key_exists('wasOutOfDateHere', $data[5])){
        foreach($data[5]['wasOutOfDateHere'] as $mediaId => $sampleData){
          if(!in_array($mediaId, $dayMedia)){
            unset($data[5]['wasOutOfDateHere'][$mediaId]);
          }
        }
      }

      $encodedData = json_encode($data, JSON_FORCE_OBJECT);
      $this->AssuranceForm->free();
      $this->AssuranceForm->deepFreed();
      $this->AssuranceForm->id = $form['id'];
      $this->AssuranceForm->data = $encodedData;
      $this->AssuranceForm->save();

      $missing = upa('assuranceForms', 'checkFormReady', array($form['id']), false);
      return array('dayMedia' => $dayMedia, 'dayDurations' => $dayDurations, 'data' => $encodedData, 'missing' => $missing);

    }
  }

  private function isValidTimeStamp($timestamp){
      return ((string) (int) $timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }

  private function explanationButton($fieldId, $note, $outOfDateHere = False){
    if($note === False || $note === NULL){
      $note = '';
    }

    return generateHTML('assuranceForms/explanationButton', array(
      'field_id' => htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8'),
      'explanation_note' => htmlspecialchars($note, ENT_QUOTES, 'UTF-8'),
      'out_of_date_here' => htmlspecialchars($outOfDateHere, ENT_QUOTES, 'UTF-8')
    ));
  }

  private function outOfDateHereForField($data, $fieldId){
    if(substr($fieldId, 0, 3) != 'b3_'){
      return False;
    }

    $mediaId = substr($fieldId, 3);
    if(substr($mediaId, 0, 6) == 'extra_'){
      $parts = explode('_', $mediaId);
      $mediaId = $parts[1];
    }

    return !empty(checkKeyOrEmpty($data, 5, 'wasOutOfDateHere', $mediaId));
  }

  private function outOfDateHereText($data, $fieldId){
    if(substr($fieldId, 0, 3) != 'b3_'){
      return '';
    }

    $mediaId = substr($fieldId, 3);
    if(substr($mediaId, 0, 6) == 'extra_'){
      $parts = explode('_', $mediaId);
      $mediaId = $parts[1];
    }

    $samples = checkKeyOrEmpty($data, 5, 'wasOutOfDateHere', $mediaId);
    $sampleText = array();

    foreach($samples as $sampleId => $sample){
      $barcode = checkKeyOrFalse($sample, 'sampleBarcode');
      if($barcode === False || $barcode === ''){
        $barcode = 'Monster ID ' . $sampleId;
      }
      array_push($sampleText, $barcode);
    }

    if(empty($sampleText)){
      return '';
    }

    return 'Gebruikt na THT bij monster(s): ' . implode(', ', $sampleText);
  }

  private function dateIsAfterDate($value, $dateToCompare){
    $date = DateTime::createFromFormat('d-m-Y', $value);
    $dateErrors = DateTime::getLastErrors();
    $compareDate = DateTime::createFromFormat('d-m-Y', $dateToCompare);
    $compareDateErrors = DateTime::getLastErrors();

    if($date === False || $compareDate === False || ($dateErrors !== False && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || ($compareDateErrors !== False && ($compareDateErrors['warning_count'] > 0 || $compareDateErrors['error_count'] > 0))){
      return False;
    }

    $date->setTime(0, 0, 0);
    $compareDate->setTime(0, 0, 0);
    return $date->getTimestamp() > $compareDate->getTimestamp();
  }

  private function dateIsBeforeFormDate($value, $formDate){
    $date = DateTime::createFromFormat('d-m-Y', $value);
    $errors = DateTime::getLastErrors();

    if($date === False || ($errors !== False && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))){
      return False;
    }

    $date->setTime(0, 0, 0);
    return $date->getTimestamp() < $formDate;
  }

  private function isNotApplicable($value){
    return strtolower(trim((string) $value)) == 'nvt';
  }

  private function materialIsOutOfSpec($value, $range){
    $value = trim($value);
    $range = trim($range);

    if($value === '' || $range === '' || $this->isNotApplicable($value)){
      return False;
    }

    $numericValue = str_replace(',', '.', $value);
    if(!is_numeric($numericValue)){
      return True;
    }

    $pattern = '/(<=|>=|<|>|=)\s*(-?\d+(?:[.,]\d+)?)/';
    $remaining = preg_replace($pattern, '', $range);
    if(trim($remaining) !== ''){
      return False;
    }

    preg_match_all($pattern, $range, $matches, PREG_SET_ORDER);
    $acceptable = True;

    foreach($matches as $match){
      $bound = (float) str_replace(',', '.', $match[2]);
      $number = (float) $numericValue;

      if($match[1] == '<' && !($number < $bound)){ $acceptable = False; }
      if($match[1] == '<=' && !($number <= $bound)){ $acceptable = False; }
      if($match[1] == '>' && !($number > $bound)){ $acceptable = False; }
      if($match[1] == '>=' && !($number >= $bound)){ $acceptable = False; }
      if($match[1] == '=' && $number != $bound){ $acceptable = False; }
    }

    return !empty($matches) && !$acceptable;
  }

  private function needsExplanation($fieldId, $value, $formDate){
    if(empty($value) || $this->isNotApplicable($value)){
      return False;
    }

    if(substr($fieldId, 0, 3) == 'b2_'){
      return strpos($fieldId, 'tht_') === 3 && $this->dateIsBeforeFormDate($value, $formDate);
    }

    if(substr($fieldId, 0, 3) != 'b3_'){
      return False;
    }

    $mediaId = substr($fieldId, 3);
    if(substr($mediaId, 0, 6) == 'extra_'){
      $parts = explode('_', $mediaId);
      $mediaId = $parts[1];
    }

    $media = upa('media', 'fetch', array($mediaId), False);
    if(empty($media)){
      return False;
    }

    if($media['type'] == '3'){
      $range = isset($media['acceptable_range']) ? $media['acceptable_range'] : '';
      return $this->materialIsOutOfSpec($value, $range);
    }

    return $this->dateIsBeforeFormDate($value, $formDate);
  }

  function renderForm($return = False){

    $listAna = json_decode(MAZ_LISTERIAALL_IDS);
    $salmAna =  json_decode(MAZ_SALM_IDS);
    $campyAna = json_decode(MAZ_CAMPYLO_IDS);
    $stecAna = json_decode(MAZ_STEC_IDS);

    if($this->isValidTimeStamp($_POST['currDate'])){
        $timeStamp = $_POST['currDate'];
    } else{
        $timeStamp = strtotime($_POST['currDate']);
    }

    $this->render = False;
    $this->AssuranceForm->where('date', $timeStamp );
    $result = $this->AssuranceForm->search();

    if(empty($result)){
      $notFoundTemplate = generateHTML('assuranceForms/notfound', array());
      print json_encode(array('html' => $notFoundTemplate));
      return;
    }

    //run update
    $formId = $result[0]['id'];
    $updates = upa('assuranceForms', 'scanForFormUpdates', array($formId), False);

    if($updates == False){
      $notFoundTemplate = generateHTML('assuranceForms/notfound', array());
      print json_encode(array('html' => $notFoundTemplate));
      return;
    }

    $dayMedia = $updates['dayMedia'];
    $dayDurations = $updates['dayDurations'];
    $updatedData = $updates['data'];

    if($dayMedia === False){
      $notFoundTemplate = generateHTML('assuranceForms/notfound', array());
      print json_encode(array('html' => $notFoundTemplate));
      return;
    }

    //$data = json_decode($result[0]['data'], JSON_FORCE_OBJECT);
    $data = json_decode($updatedData, JSON_FORCE_OBJECT);
    $replace = array();

    //block 1
    $replace['hide_old_block1'] = 'hide';
    

    if(array_key_exists('beheer', $data[0]))
    {
      $replace['beheer_drop'] = $this->createUserDrop($data['users_available_at_time'],  $data[0]['beheer']);
      $replace['hide_old_block1'] = '';
    } 

    if(array_key_exists('instoof', $data[0]))
    {
      $replace['instoof'] = $data[0]['instoof'];
    }

    if(array_key_exists('afgewogen', $data[0]))
    {
      $replace['afgewogen_drop'] = $this->createUserDrop($data['users_available_at_time'],  $data[0]['afgewogen']);
    }
    
    $replace['ingezet_drop'] = $this->createUserDrop($data['users_available_at_time'],  $data[0]['ingezet']);
    $replace['gegoten_drop'] = $this->createUserDrop($data['users_available_at_time'],  $data[0]['gegoten']);
    

    $durationHeaders = '';
    $outOfStove = '';
    $timeOutOfStove = '';
    $dateReadOut = '';
    $timeReadOut = '';
    $readOutBy = '';

    $tabIndex = 6;

    asort($dayDurations);
    
    if(array_key_exists(1, $data))
    {
      foreach($dayDurations as $duration){

        $outOfStoveValue = checkKeyOrFalse($data, 1, $duration, 'datum_uitstoof' );
        $timeOutOfStoveValue = checkKeyOrFalse($data, 1, $duration, 'tijd_uitstoof' );
        $dateReadOutValue = checkKeyOrFalse($data, 1, $duration, 'datum_aflezen' );
        $timeReadOutValue = checkKeyOrFalse($data, 1, $duration, 'tijd_aflezen' );
        $reatOutByValue = checkKeyOrFalse($data, 1, $duration, 'afgelezen_door' );
  
        $durationHeaders .= generateHTML('assuranceForms/durationHeader', array('day' => $duration));
  
        $outOfStove .= generateHTML('assuranceForms/outOfStove', array('day' => $duration, 'value' => $outOfStoveValue, 'tbix' => $tabIndex));
        $tabIndex = $tabIndex++;
  
        $timeOutOfStove .= generateHTML('assuranceForms/timeOutOfStove', array('day' => $duration, 'value' => $timeOutOfStoveValue, 'tbix' => $tabIndex));
        $tabIndex = $tabIndex++;
  
        $dateReadOut .= generateHTML('assuranceForms/dateReadOut', array('day' => $duration, 'value' => $dateReadOutValue, 'tbix' => $tabIndex));
        $tabIndex = $tabIndex++;
  
        $timeReadOut .= generateHTML('assuranceForms/timeReadOut', array('day' => $duration, 'value' => $timeReadOutValue, 'tbix' => $tabIndex));
        $tabIndex = $tabIndex++;
  
        $dropOptions = $this->createUserDrop($data['users_available_at_time'],  $reatOutByValue);
        $readOutBy .= generateHTML('assuranceForms/readOutBy', array('day' => $duration, 'dropoptions' => $dropOptions, 'tbix' => $tabIndex));
        $tabIndex = $tabIndex++;
      }

      $replace['show_duration_block'] = '';
    }

    else
    {
      $replace['show_duration_block'] = 'hide';
    }
    

    $replace['duration_headers'] = $durationHeaders;
    $replace['duration_out_of_stove'] = $outOfStove;
    $replace['duration_time_out_of_stove'] = $timeOutOfStove;
    $replace['duration_date_readout'] = $dateReadOut;
    $replace['duration_time_readout'] = $timeReadOut;
    $replace['duration_read_by'] = $readOutBy;

    //block 3A
    $salmFlag = False;
    $listeriaFlag = False;
    $campyFlag = False;
    $stecFlag = False;

    $dateSamples = upa('samples', 'getSamplesForInnocDate', array(strtotime($_POST['currDate'])), False);

    //block 3B
    $replace['legionella_temp'] = '';
    $replace['tht_pfz'] = '';
    $replace['tht_pfz_buizen'] = '';
    //$replace['tht_citraat'] = '';
    $replace['tht_fraser'] = '';
    $replace['tht_bolton'] = '';
    $replace['tht_bpw'] = '';
    $replace['tht_pfz_explanation_button'] = '';
    $replace['tht_pfz_buizen_explanation_button'] = '';
    $replace['tht_bpw_explanation_button'] = '';

    $explanations = checkKeyOrEmpty($data, 4);

    if(array_key_exists(2, $data)){
      if(array_key_exists('legionella_temp', $data[2])){
        $replace['legionella_temp'] = $data[2]['legionella_temp'];
      }
      if(array_key_exists('tht_pfz', $data[2])){
        $replace['tht_pfz'] = $data[2]['tht_pfz'];
        $replace['tht_pfz_explanation_button'] = $this->explanationButton('b2_tht_pfz', checkKeyOrFalse($explanations, 'b2_tht_pfz'));
      }
      if(array_key_exists('tht_pfz_buizen', $data[2])){
        $replace['tht_pfz_buizen'] = $data[2]['tht_pfz_buizen'];
        $replace['tht_pfz_buizen_explanation_button'] = $this->explanationButton('b2_tht_pfz_buizen', checkKeyOrFalse($explanations, 'b2_tht_pfz_buizen'));
      }

      //if(array_key_exists('tht_citraat', $data[2])){
      //  $replace['tht_citraat'] = $data[2]['tht_citraat'];
      //}

      if(array_key_exists('tht_fraser', $data[2])){
        $replace['tht_fraser'] = $data[2]['tht_fraser'];
      }

      if(array_key_exists('tht_bolton', $data[2])){
        $replace['tht_bolton'] = $data[2]['tht_bolton'];
      }

      if(array_key_exists('tht_bpw', $data[2])){
        $replace['tht_bpw'] = $data[2]['tht_bpw'];
        $replace['tht_bpw_explanation_button'] = $this->explanationButton('b2_tht_bpw', checkKeyOrFalse($explanations, 'b2_tht_bpw'));
      }

    }


    //Block 4
    //$media = MESA_MEDIA_LIST;

    $media = upa('media', 'fetchAll', array(False), False);
    //$media = json_decode($media, JSON_FORCE_OBJECT);

    $mediaTable = '';
    $confTable = '';

    foreach($media as $mediaItem){

      if(!in_array($mediaItem['id'], $dayMedia)){
        continue;
      }

      $mediaId = $mediaItem['id'];
      $mediaName = $mediaItem['short_name'];
      $confirmationMedia = $mediaItem['confirmation_media'];

      $media_default = '';

      if(array_key_exists(3, $data) && array_key_exists($mediaId, $data[3])){
        $media_default = $data[3][$mediaId];
      }

      $mediaExtra = '';
      $mediaExtraNames = '';
      $extraMedia = json_decode($mediaItem['supplements'], JSON_FORCE_OBJECT);

      if(is_array($extraMedia)){
        foreach($extraMedia as $extraMediaItem){
          $suppName = $extraMediaItem['name'];
          $suppId = $extraMediaItem['supplementId'];

          $media_extra_default = '';
          if(array_key_exists(3, $data) && array_key_exists('extra_' . $mediaId . '_' . $suppId, $data[3])){
            $media_extra_default= $data[3]['extra_' . $mediaId . '_' . $suppId];
          }

          $mediaExtraNames .= $suppName . '<br />';
          $extraId = 'extra_' . $mediaId . '_' . $suppId;
          $mediaExtra .= generateHTML('assuranceForms/mediaitem', array('media_id' => $extraId, 'media_default'=> $media_extra_default, 'out_of_date_here' => $this->outOfDateHereForField($data, 'b3_' . $extraId) ? '1' : '0', 'explanation_button' => $this->explanationButton('b3_' . $extraId, checkKeyOrFalse($explanations, 'b3_' . $extraId), $this->outOfDateHereText($data, 'b3_' . $extraId))));
        }
      }


      $mediaItem = generateHTML('assuranceForms/mediaitem', array('media_id' => $mediaId, 'media_default'=> $media_default, 'out_of_date_here' => $this->outOfDateHereForField($data, 'b3_' . $mediaId) ? '1' : '0', 'explanation_button' => $this->explanationButton('b3_' . $mediaId, checkKeyOrFalse($explanations, 'b3_' . $mediaId), $this->outOfDateHereText($data, 'b3_' . $mediaId))));
      $newRow = generateHTML('assuranceForms/mediarow', array('media_name' => $mediaName, 'media_item' => $mediaItem, 'media_extra_name' =>  $mediaExtraNames, 'media_extra' => $mediaExtra));


      if($confirmationMedia == '1'){
        $confTable = $confTable . $newRow;
      } else{
        $mediaTable = $mediaTable . $newRow;
      }

    }

    $replace['media_tht_rows'] = $mediaTable;
    $replace['conf_tht_rows'] = $confTable;


    $mat = upa('media', 'fetchAll', array(False, True), False);
    //$media = json_decode($media, JSON_FORCE_OBJECT);
    $matTable = '';


    foreach($mat as $matItem){

      if(!in_array($matItem['id'], $dayMedia)){
        continue;
      }

      $matId = $matItem['id'];
      $matName = $matItem['short_name'];
      $matDefault = '';
      $acceptableRange = isset($matItem['acceptable_range']) ? htmlspecialchars($matItem['acceptable_range'], ENT_QUOTES, 'UTF-8') : '';

      if(array_key_exists(3, $data) && array_key_exists($matId, $data[3])){
        $matDefault = $data[3][$matId];
      }

      $mediaExtraName = $matItem['supplements'];
      $matItem = generateHTML('assuranceForms/matItem', array('media_id' => $matId, 'media_default'=> $matDefault, 'acceptable_range' => $acceptableRange, 'out_of_date_here' => $this->outOfDateHereForField($data, 'b3_' . $matId) ? '1' : '0', 'explanation_button' => $this->explanationButton('b3_' . $matId, checkKeyOrFalse($explanations, 'b3_' . $matId), $this->outOfDateHereText($data, 'b3_' . $matId))));
      $newRow = generateHTML('assuranceForms/mediarow', array('media_name' => $matName, 'media_item' => $matItem, 'media_extra_name' =>  $mediaExtraName, 'media_extra' => NULL));
      $matTable = $matTable . $newRow;
    }

    $replace['mat_rows'] = $matTable;

    $misString = '';
    $misClass = '';

    if(!empty($updates['missing'])){
      foreach($updates['missing'] as $missing){
        $misString .= $missing . ', ';
      }
    } else{
      $misClass = 'hidden';
    }


    $replace['missing'] = $misString;
    $replace['misClass'] = $misClass;

    $template = generateHTML('assuranceForms/form', $replace);

    if($return == True){
      return json_encode(array('html' => $template, 'id' => $formId ));
    } else{
      print json_encode(array('html' => $template, 'id' => $formId ));
    }


  }

  function change(){
    $this->render = False;
    $this->AssuranceForm->where('id', $_POST['id']);
    $result = $this->AssuranceForm->search();

    $revChanged = False;
    $revFrom = False;
    $revTo = False;
    $revMask = False;

    $userMask = array('beheer', 'afgewogen', 'ingezet', 'gegoten', 'afgelezen_door');

    $humanReadable = array();
    $humanReadable['beheer'] = 'Beheer monsteronderzoek';
    $humanReadable['afgewogen'] = 'Afgewogen door';
    $humanReadable['ingezet'] = 'Ingezet door';
    $humanReadable['gegoten'] = 'Gegoten door';
    $humanReadable['instoof'] = 'Tijd platen in broedstoof';

    $humanReadable['datum_uitstoof'] = 'Datum uit stoof, dag: ';
    $humanReadable['tijd_uitstoof'] = 'Tijd uit stoof, dag: ';
    $humanReadable['datum_aflezen'] = 'Datum aflezen, dag: ';
    $humanReadable['tijd_aflezen'] = 'Tijd aflezen, dag: ';
    $humanReadable['afgelezen_door'] = 'Afgelezen door, dag: ';

    $humanReadable['tht_pfz'] = 'THT PFZ';
    $humanReadable['tht_pfz_buizen'] = 'THT PFZ buizen';
    //$humanReadable['tht_citraat'] = 'THT Citraat';
    $humanReadable['tht_bpw'] = 'THT BPW';

    if(!empty($result)){

      $formData = json_decode($result[0]['data'], JSON_FORCE_OBJECT);
      $itemExplode = explode('_', $_POST['elementId'], 2);

      if($itemExplode[0] == 'b0'){
        $revMask = $itemExplode[1];
        $revFrom = checkKeyOrFalse($formData, 0, $itemExplode[1]);
        $revTo =  $_POST['elementValue'];
        $revChanged = checkKeyOrFalse($humanReadable, $itemExplode[1]);
        if($revChanged == False){
          $revChanged = $itemExplode[1];
        }


        $formData[0][$itemExplode[1]] = $_POST['elementValue'];
      }


      if($itemExplode[0] == 'b1'){

        $parts = explode('_', $itemExplode[1]);
        $duration = array_pop($parts);
        $item = array(implode('_', $parts), $duration);

        //$itemRowNo = substr($itemExplode[1], -1);
        //$itemRow = substr($itemExplode[1], 0, -2);
        $itemRowNo = $duration;
        $itemRow = $item[0];

        $revFrom = checkKeyOrFalse($formData, 1, $itemRowNo, $itemRow );
        $revTo =  $_POST['elementValue'];
        $revMask = $itemRow;
        $revChanged = checkKeyOrFalse($humanReadable, $itemRow);
        $revChanged = $revChanged . $duration;

        $formData[1][$itemRowNo][$itemRow] = $_POST['elementValue'];

      }

      if($itemExplode[0] == 'b2'){

        $revFrom = checkKeyOrFalse($formData, 2, $itemExplode[1]);
        $revTo =  $_POST['elementValue'];
        $revChanged = checkKeyOrFalse($humanReadable, $itemExplode[1]);
        $formData[2][$itemExplode[1]] = $_POST['elementValue'];
      }

      if($itemExplode[0] == 'b3'){

        //for revision tracking
        if(substr( $itemExplode[1], 0, 5 ) === "extra"){
          $extraSplode = explode('_', $itemExplode[1]);
          $thisMedia = upa('media', 'fetch', array($extraSplode[1]), False);
          $mediaName = $thisMedia['name'];
          $supplements = json_decode($thisMedia['supplements'], JSON_FORCE_OBJECT);
          foreach($supplements as $supplement){
            if($supplement['supplementId'] == $extraSplode['2']){
              $suppName = $supplement['name'];
            }
          }

          $revChanged = 'Supplement: ' . $suppName . ' voor media ' . $mediaName;

        } else {
          $thisMedia = upa('media', 'fetch', array($itemExplode[1]), False);
          $mediaName = $thisMedia['name'];
          $revChanged =  $mediaName;
        }


        if(!array_key_exists(3, $formData)){
          $formData[3] = array();
        }

        $revFrom = checkKeyOrFalse($formData, 3, $itemExplode[1]);
        $revTo =  $_POST['elementValue'];
        $formData[3][$itemExplode[1]] = $_POST['elementValue'];

        if($this->isNotApplicable($_POST['elementValue']) && array_key_exists(5, $formData) && array_key_exists('wasOutOfDateHere', $formData[5]) && array_key_exists($itemExplode[1], $formData[5]['wasOutOfDateHere'])){
          unset($formData[5]['wasOutOfDateHere'][$itemExplode[1]]);
        }
      }

      $this->AssuranceForm->id = $_POST['id'];
      $this->AssuranceForm->data = json_encode($formData);
      $this->AssuranceForm->save();

      //function registerRevision( $scope, $scopeId, $changed = False, $from = False, $to = False, $saId = 0, $resultId = 0){
      if($revMask != False){
        if(in_array($revMask, $userMask)){
          $revFrom = userIdToName($revFrom);
          $revTo = userIdToName($revTo);
        }
      }

      //upa('revisions', 'registerRevision', array('SCOPE_ASSURANCE_CHANGE', $result[0]['id'], $revChanged, $revFrom, $revTo), False );

      $event = 'Veld gewijzigd: ' . $revChanged;
      upa('changeTracker', 'changed', array(13, False, False, False, $event, $revFrom, $revTo, $result[0]['id'] ), False);

      upa('assuranceForms', 'checkFormReady', array($_POST['id']), False);

    }

    print json_encode(array());
  }

  function checkFormOrCreate($date = False){


    if($date == False){
      $thisDayForm = strtotime( date('d-m-Y', time()));
    } else{
      $thisDayForm = strtotime( date('d-m-Y', $date));
    }


    $this->AssuranceForm->where('date', $thisDayForm);
    $results = $this->AssuranceForm->search();

    if(empty($results)){
      upa('assuranceForms', 'createForm', array($thisDayForm, True), False);
    }

  }

  function createForm($currDate = False, $timeStamp = False){

    $this->render = False;
    $formArray = array();

    if($currDate == False){
      $this->AssuranceForm->date = strtotime($_POST['currDate']);
    } else{
      if($timeStamp == True){
        $this->AssuranceForm->date = $currDate;
      } else{
        $this->AssuranceForm->date = strtotime($currDate);
      }
    }


    //info
    $users = upa('users', 'getAllUserNames', array(True), False);
    $formArray['users_available_at_time'] = $users;

    //block 1    
    //$formArray[0]['beheer'] = getUserId();
    //$formArray[0]['afgewogen'] = '';        
    //$formArray[0]['instoof'] = '';

    //stays
    $formArray[0]['ingezet'] = '';
    $formArray[0]['gegoten'] = '';
    
    $formArray[2]['tht_pfz'] = '';
    $formArray[2]['tht_pfz_buizen'] = '';    
    $formArray[2]['tht_bpw'] = '';

    $this->AssuranceForm->data = json_encode($formArray, JSON_FORCE_OBJECT);
    $this->AssuranceForm->save();

    //run updater
    //scanForFormUpdates
    $formId = $this->AssuranceForm->lastInsertId;
    $this->AssuranceForm->free();
    upa('assuranceForms', 'scanForFormUpdates', array($formId), False );

  }

  function nextDate(){
    $this->render = False;
    $currDate = $_POST['currDate'];
    $datetime = new DateTime($currDate);
    $datetime->add(new DateInterval('P1D'));
    $newDate = $datetime->format('d-m-Y');
    print json_encode(array('date' => $newDate));
  }

  function previousDate(){
    $this->render = False;
    $currDate = $_POST['currDate'];
    $datetime = new DateTime($currDate);
    $datetime->sub(new DateInterval('P1D'));
    $newDate = $datetime->format('d-m-Y');
    print json_encode(array('date' => $newDate));
  }

  function printPage($date){

    $_POST['currDate'] = $date;
    $form = $this->renderForm(True);

    $this->render = True;
    $this->renderAlternateHeader = 'slim';
    $dat = json_decode($form, JSON_FORCE_OBJECT);

    $content = '<h3>Borgingsformulier:' . $date . '</h3>';
    $content .= $dat['html'];

    $this->_template->set('content', $content);


  }

  function bulkCheckExpiryDate($sample, $said, $mediaIds)
  {
    $this->render = False; 

    $s = new Sample();
    $s->where('id', $sample);
    $s->select('sample_innoculated');
    $innoc = $s->first();

    if(empty($innoc['sample_innoculated']))
    {
      return;
    }

    $beginOfDay = strtotime("midnight", $innoc['sample_innoculated']);

    $this->AssuranceForm->where('date', $beginOfDay);
    $result = $this->AssuranceForm->search();
    
    if(empty($result))
    {
      return;
    } 
    
    else
    {
      $form = $result[0];
      $data = json_decode($form['data'], JSON_FORCE_OBJECT);
      $dates = []; 

      foreach($mediaIds as $mediaId)
      {
        if(array_key_exists(3, $data) && array_key_exists($mediaId, $data[3]))
        {          
          $dates[$mediaId] = $data[3][$mediaId];                                            
        }

        else
        {
          $dates[$mediaId] = '';
        }
      }
      
      return $dates;
    }

  }

  function checkExpiryDate($said, $newDate, $mediaId, $return = False){

    $this->render = False;

    //$date = strtotime($newDate);
    $date = upa('sampleAnalysis', 'saidToInnoc', array($said), False);
    $this->AssuranceForm->where('date', $date);
    $result = $this->AssuranceForm->search();

    if(empty($result)){
      return;
    } else{
      $form = $result[0];
      $data = json_decode($form['data'], JSON_FORCE_OBJECT);
      if(array_key_exists(3, $data) && array_key_exists($mediaId, $data[3])){
        if($return == False){
          print $data[3][$mediaId];
        }
        if($return == True){
          return $data[3][$mediaId];
        }
      }
    }
  }

  function getOutOfDateInfo($said, $mediaId){
    $this->render = False;
    $date = upa('sampleAnalysis', 'saidToInnoc', array($said), False);

    if($date == False){
      return array('formId' => False, 'outOfDateHere' => False, 'sampleText' => '', 'explanation' => '');
    }

    $this->AssuranceForm->where('date', $date);
    $result = $this->AssuranceForm->search();

    if(empty($result)){
      return array('formId' => False, 'outOfDateHere' => False, 'sampleText' => '', 'explanation' => '');
    }

    $form = $result[0];
    $data = json_decode($form['data'], JSON_FORCE_OBJECT);
    $fieldId = 'b3_' . $mediaId;

    return array(
      'formId' => $form['id'],
      'outOfDateHere' => $this->outOfDateHereForField($data, $fieldId),
      'sampleText' => $this->outOfDateHereText($data, $fieldId),
      'explanation' => checkKeyOrFalse($data, 4, $fieldId)
    );
  }

  function updateOutOfDateHere($said, $mediaId, $inoculationDate, $expiryDate){
    $this->render = False;
    $innocDate = upa('sampleAnalysis', 'saidToInnoc', array($said), False);

    if($innocDate == False){
      print json_encode(array('status' => False));
      return;
    }

    $this->AssuranceForm->where('date', $innocDate);
    $result = $this->AssuranceForm->search();

    if(empty($result)){
      upa('assuranceForms', 'createForm', array($innocDate, True), False);
      $this->AssuranceForm->deepFreed();
      $this->AssuranceForm->where('date', $innocDate);
      $result = $this->AssuranceForm->search();
    }

    if(empty($result)){
      print json_encode(array('status' => False));
      return;
    }

    $form = $result[0];
    $data = json_decode($form['data'], JSON_FORCE_OBJECT);
    $saidInfo = upa('sampleAnalysis', 'fetch', array($said), False);
    $sampleId = $saidInfo['sample'];
    $sample = upa('samples', 'fetch', array($sampleId), False);
    $isOutOfDate = $this->dateIsAfterDate($inoculationDate, $expiryDate);

    if($isOutOfDate){
      if(!array_key_exists(5, $data)){
        $data[5] = array();
      }
      if(!array_key_exists('wasOutOfDateHere', $data[5])){
        $data[5]['wasOutOfDateHere'] = array();
      }
      if(!array_key_exists($mediaId, $data[5]['wasOutOfDateHere'])){
        $data[5]['wasOutOfDateHere'][$mediaId] = array();
      }

      $data[5]['wasOutOfDateHere'][$mediaId][$sampleId] = array(
        'sampleId' => $sampleId,
        'sampleBarcode' => $sample['barcode']
      );
    } else if(array_key_exists(5, $data) && array_key_exists('wasOutOfDateHere', $data[5]) && array_key_exists($mediaId, $data[5]['wasOutOfDateHere']) && array_key_exists($sampleId, $data[5]['wasOutOfDateHere'][$mediaId])){
      unset($data[5]['wasOutOfDateHere'][$mediaId][$sampleId]);
      if(empty($data[5]['wasOutOfDateHere'][$mediaId])){
        unset($data[5]['wasOutOfDateHere'][$mediaId]);
      }
    }

    $this->AssuranceForm->id = $form['id'];
    $this->AssuranceForm->data = json_encode($data, JSON_FORCE_OBJECT);
    $this->AssuranceForm->save();
    upa('assuranceForms', 'checkFormReady', array($form['id']), False);

    $fieldId = 'b3_' . $mediaId;
    $info = array(
      'formId' => $form['id'],
      'outOfDateHere' => $this->outOfDateHereForField($data, $fieldId),
      'sampleText' => $this->outOfDateHereText($data, $fieldId),
      'explanation' => checkKeyOrFalse($data, 4, $fieldId)
    );
    $info['status'] = True;
    print json_encode($info);
  }

  function updateExpiryDate($said, $chainN, $newDate, $mediaId, $silent = False){

      $this->render = False;
      $confirmation = upa('confirmations', 'fetchLine', array($said), False );
      $saidInfo = upa('sampleAnalysis', 'fetch', array($said), False);


      //set to empty if its false (from javascript);
      if($newDate == 'false' || $newDate ==  False){
        $newDate = '';
      }

      //check if this sample was innoculated, if not, what the hell is going on?
      $innocDate = upa('sampleAnalysis', 'saidToInnoc', array($said), False);

      if($innocDate == False){
        print json_encode(array('status' => False));
        //TODO: log an error here
        return;
      }

      $this->AssuranceForm->where('date', $innocDate);
      $result = $this->AssuranceForm->search();


      //the innoc date is here, but no form yet. Create it now
      //and then grab it
      if(empty($result)){
         upa('assuranceForms', 'createForm', array($innocDate, True), False);
         $this->AssuranceForm->deepFreed();
         $this->AssuranceForm->where('date', $innocDate);
         $result = $this->AssuranceForm->search();
      }

      //check if it is still empty, if it is, something went screwy, throw error
      if(empty($result)){
        writeLog('Failed to create assurance form on saving field.', ALPC_ERROR);
        return;
      }

      $form = $result[0];
      $data = json_decode($form['data'], JSON_FORCE_OBJECT);

      if(!array_key_exists(3, $data)){
        $data[3] = array();
      }


      $from = checkKeyOrFalse($data, 3, $mediaId);

      $data[3][$mediaId] = $newDate;

      $thisMedia = upa('media', 'fetch', array($mediaId), False);
      $mediaName = $thisMedia['name'];
      $revChanged =  $mediaName;

      $event = 'Veld gewijzigd: ' . $revChanged;
      upa('changeTracker', 'changed', array(13, False, False, False, $event, $from, $newDate, $form['id'] ), False);


      $this->AssuranceForm->id = $form['id'];
      $this->AssuranceForm->data = json_encode($data, JSON_FORCE_OBJECT);
      $this->AssuranceForm->save();
      upa('assuranceForms', 'checkFormReady', array($form['id']), False);
      
      if($silent !== True)
      {
        print json_encode(array('status' => True));
      }
      
  }

  function updateFormByInnocDate($innocDate){

    //check if there is a form with this date
    $beginOfDay = strtotime("midnight", $innocDate);
    $form = $this->fetchByDate($beginOfDay);

    //if so, run the update (which will aslo invoke is ready )
    if($form){
        upa('assuranceForms', 'scanForFormUpdates', array($form['id']), False);
    }

  }

  function saveExplanation(){
    $this->render = False;

    $this->AssuranceForm->where('id', $_POST['id']);
    $result = $this->AssuranceForm->search();

    if(empty($result)){
      print json_encode(array('status' => False));
      return;
    }

    $data = json_decode($result[0]['data'], JSON_FORCE_OBJECT);
    if(!array_key_exists(4, $data)){
      $data[4] = array();
    }

    $data[4][$_POST['elementId']] = $_POST['explanation'];

    $this->AssuranceForm->id = $_POST['id'];
    $this->AssuranceForm->data = json_encode($data, JSON_FORCE_OBJECT);
    $this->AssuranceForm->save();

    upa('assuranceForms', 'checkFormReady', array($_POST['id']), False);
    print json_encode(array('status' => True));
  }

  function checkFormReady($id){

    $this->AssuranceForm->where('id', $id);
    $results = $this->AssuranceForm->search();
    $missing = array();

    if(empty($results)){
      return;
    }

    $form = $results[0];
    $data = json_decode($form['data'], JSON_FORCE_OBJECT);
    $formError = False;

    $samples = upa('samples', 'getSamplesForInnocDate', array($form['date']), False);
    $dayMedia = array();

    if(empty($samples)){
      upa('assuranceForms', 'removeForm', array($form['id']), False);
      return;
    }

    foreach($data[0] as $block1fieldName => $block1fieldValue){
      if(empty($block1fieldValue) || $block1fieldValue == '0'){
          array_push($missing, 'Algemeen: ' . $block1fieldName);
          $formError = True;
      }
    }

    if(array_key_exists('1', $data)){
      

    foreach($data[1] as $day => $dayBlock){

      foreach($dayBlock as $block1fieldName => $block1fieldValue){
        if(empty($block1fieldValue) || $block1fieldValue == '0'){
            array_push($missing, 'Dag ' . $day . ': ' . $block1fieldName);
            $formError = True;
        }
      }
    }
    }

    if(array_key_exists('2', $data)){
      foreach($data[2] as $block1fieldName => $block1fieldValue){

        if(empty($block1fieldValue) || $block1fieldValue == '0'){

            //print $block1fieldName . ' was empty: '. $block1fieldValue;
            if($block1fieldName != 'tht_fraser' && $block1fieldName != 'tht_bolton' && $block1fieldName != 'tht_citraat'){
              array_push($missing, $block1fieldName);
              $formError = True;
            }
        }

          $explanationFieldId = 'b2_' . $block1fieldName;
          if($this->needsExplanation($explanationFieldId, $block1fieldValue, $form['date']) && empty(checkKeyOrFalse($data, 4, $explanationFieldId))){
            array_push($missing, 'Uitleg: ' . $block1fieldName);
            $formError = True;
          }
      }
    }


    if(array_key_exists('3', $data)){
      foreach($data[3] as $block1fieldName => $block1fieldValue){

        if(empty($block1fieldValue)){
            $formError = True;
            array_push($missing, 'Media: ' . $block1fieldName);
        }

        $explanationFieldId = 'b3_' . $block1fieldName;
        if(!$this->isNotApplicable($block1fieldValue) && ($this->needsExplanation($explanationFieldId, $block1fieldValue, $form['date']) || $this->outOfDateHereForField($data, $explanationFieldId)) && empty(checkKeyOrFalse($data, 4, $explanationFieldId))){
          $formError = True;
          array_push($missing, 'Uitleg media: ' . $block1fieldName);
        }
      }
    }

    $this->AssuranceForm->deepFreed();
    $this->AssuranceForm->id = $form['id'];

    if($formError == True){
      $this->AssuranceForm->is_complete = false;
    } else{
      $this->AssuranceForm->is_complete = true;
    }

    $this->AssuranceForm->save();
    
    return $missing;

  }

  function checkEmptiesCountOnly()
  {
    $this->render = False;

    $currDate = time();
    $upperLimit = $currDate - (BORG_TIME_BEFORE_CHECK * 86400);    
    $this->AssuranceForm->lessThan('date', $upperLimit);
    $this->AssuranceForm->where('is_complete', '0');      
    $forms = $this->AssuranceForm->countIds();

    print json_encode(array('number_of_error_forms' => $forms));

  }

  function checkEmpties($json = true, $countOnly = False){

      $this->render = False;

      $currDate = time();
      $upperLimit = $currDate - (BORG_TIME_BEFORE_CHECK * 86400);
      #$lowerLimit = $currDate - (BORG_TIME_CHECK_LIMIT * 86400);

      $this->AssuranceForm->lessThan('date', $upperLimit);
      $this->AssuranceForm->where('is_complete', '0');
      $forms = $this->AssuranceForm->search();

      $emptyFields = 0;
      $errorForms = 0;
      $errorFormStack = array();

      if(!empty($forms)){

     
        foreach($forms as $form){
            $errorForms = $errorForms +1;
            $errorFormStack[$form['id']] = array();
            $errorFormStack[$form['id']]['id'] = $form['id'];
       
            $errorFormStack[$form['id']]['timestamp'] = $form['date'];
            $errorFormStack[$form['id']]['readableDate'] = date("d-m-Y", $form['date']);
            $errorFormStack[$form['id']]['date'] = $form['date'];


        }
      } else{
        $number_of_error_forms = 0;
        $errorFormStack = array();
      }

      if($json == true){
        print json_encode(array('number_of_error_forms' => $errorForms, 'error_form_stack' => $errorFormStack));
      } else{
        return $errorFormStack;
      }
  }
}
