<?PHP

class confirmationsController extends controller{

  protected $_note;
  protected $_id;

  private function assuranceExplanationButton($info, $mediaId){
    $formId = checkKeyOrFalse($info, 'formId');
    $note = checkKeyOrFalse($info, 'explanation');
    $sampleText = checkKeyOrFalse($info, 'sampleText');
    $isOutOfDate = checkKeyOrFalse($info, 'outOfDateHere');

    if($note === False){
      $note = '';
    }
    if($sampleText === False){
      $sampleText = '';
    }

    return generateHTML('confirmation/explanationButton', array(
      'form_id' => htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'),
      'field_id' => htmlspecialchars('b3_' . $mediaId, ENT_QUOTES, 'UTF-8'),
      'explanation_note' => htmlspecialchars($note, ENT_QUOTES, 'UTF-8'),
      'out_of_date_here' => htmlspecialchars($sampleText, ENT_QUOTES, 'UTF-8'),
      'show_button' => $isOutOfDate ? '' : 'display: none;'
    ));
  }

  function addContender($said, $df = 'global', $rep = 0)
  {

    $this->render = False;     
    $this->Confirmation->where('said', $said);
    $line = $this->Confirmation->search();
    $this->Confirmation->deepFreed();

    //conf line not set yet, create it
    if(empty($line)){
      return;
    } else{
      $line = $line[0];
    }


    $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
    $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));
    $availableFieds = json_decode($assayInfo['confirmation_script'], True);

    $raceTrack = json_decode($line['racetrack'], JSON_FORCE_OBJECT);
    $exists = checkKeyOrFalse($raceTrack, $df, $rep);

    $newContenderIndex = count($exists);
    
    foreach($availableFieds as $idx => $confLink)
    {      
      $raceTrack[$df][$rep][$newContenderIndex][$idx] = '';
    }    


    $this->Confirmation->id = $line['id'];
    $this->Confirmation->racetrack = json_encode($raceTrack, JSON_FORCE_OBJECT);
    $this->Confirmation->save();
      
  }


  function removeContender($said, $contender, $df = 'global', $rep = 0){
    $this->render = False;
    $this->Confirmation->where('said', $said);
    $line = $this->Confirmation->search();
    $this->Confirmation->deepFreed();

    //conf line not set yet, create it
    if(empty($line)){
      return;
    } else{
      $line = $line[0];
    }

    //convert data arrays
    $raceTrack = json_decode($line['racetrack'], JSON_FORCE_OBJECT);

    $exists = checkKeyOrFalse($raceTrack, $df, $rep, $contender);

    if($exists){
        unset($raceTrack[$df][$rep][$contender]);
    }

    $reordered = array();
    $i = 0;
    foreach($raceTrack[$df][$rep] as $contenderNow => $contenderInfo){
      $reordered[$i] = $contenderInfo;
      $i++;
    }

    $raceTrack[$df][$rep] = $reordered;

    $this->Confirmation->id = $line['id'];
    $this->Confirmation->racetrack = json_encode($raceTrack, JSON_FORCE_OBJECT);
    $this->Confirmation->save();
  }


  function initRacetrack($id, $racetrack, $said, $sample,  $availableFieds, $df, $rep, $isMeta, $confirmationDepth = 5 ){

    //checks if this df and rep
    $raceTrackExists = checkKeyOrFalse($racetrack, $df, $rep);

    if($raceTrackExists == False){

        $maxContenders = upa('results', 'findMaximumCount', array($sample, $said, $df, $rep, 'kve', $isMeta), False);

        //adjust if more then 5
        if($maxContenders > $confirmationDepth )
        { 
          $maxContenders = $confirmationDepth;
        }

        for($i = 0; $i < $maxContenders; $i++)
        {
            foreach($availableFieds as $idx => $confLink)
            {
              $racetrack[$df][$rep][$i][$idx] = '';
            }
        }
    }

    //save racetrack
    $this->Confirmation->id = $id;
    $this->Confirmation->racetrack = json_encode($racetrack, JSON_FORCE_OBJECT);
    $this->Confirmation->save();
    return $racetrack;
  }

  function disableMediaOnLine($said, $df, $rep){
    $this->render = False;
    $this->Confirmation->where('said', $said);
    $line = $this->Confirmation->search();
    $this->Confirmation->deepFreed();

    //conf line not set yet, create it
    if(empty($line)){
      return;
    } else{
      $line = $line[0];
    }

    $inUse = json_decode($line['in_use'], JSON_FORCE_OBJECT);
    $raceTracks = json_decode($line['racetrack'], JSON_FORCE_OBJECT);
    $inUseArr = checkKeyorEmpty($inUse, $df, $rep);

    foreach($inUseArr as $mediaId => $active){
      $inUse[$df][$rep][$mediaId] = False;
    }

    unset($raceTracks[$df][$rep]);

    $this->Confirmation->id = $line['id'];
    $this->Confirmation->in_use = json_encode($inUse, JSON_FORCE_OBJECT);
    $this->Confirmation->racetrack = json_encode($raceTracks, JSON_FORCE_OBJECT);
    $this->Confirmation->save();
  }

  function recheckReadyStatus($said, $df, $rep){

    $this->doNotRenderHeader = True;
    $this->Confirmation->deepFreed();

    $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
    $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));

    //meta flag
    $isMeta = ($assayInfo['type'] == '4') ? true : false;

    if($assayInfo['confirmation_type'] == '0'){
      $df = 'global';
      $globalConf = True;
    } else{
      $globalConf = False;
    }

    $availableFieds = json_decode($assayInfo['confirmation_script'], True);
    $availableSupport = json_decode($assayInfo['confirmation_support'], True);

    //check if line is there
    $this->Confirmation->deepFreed();
    $this->Confirmation->where('said', $said);
    $line = $this->Confirmation->search();


    if(empty($line)){
      return;
    }

    $line = $line[0];

    $raceTrack = json_decode($line['racetrack'], JSON_FORCE_OBJECT);
    $metadata = json_decode($line['metadata'], JSON_FORCE_OBJECT);
    $metaDataDone = True;

    $this->Confirmation->deepFreed();

    //first check, above max in the case of non global config?
    if($globalConf == False) {
      $result = upa('results', 'getTestResultsByDfAndRep', array($said, $df, $rep), False);
      if(!empty($result)){
        $data = json_decode($result[0]['data'], JSON_FORCE_OBJECT);
        //cphp($data);
        $kve = checkKeyOrFalse($data, 'kve');
        //cphp('kve = ' . $kve);
        //if($kve > $assayInfo['max_count'] || $kve == '0'){
        if($kve == '0' || $kve == '>'){
          $this->setReady($line, True, $df, $rep);
          $this->Confirmation->deepFreed();
          $this->disableMediaOnLine($line['said'], $df, $rep);
          $this->Confirmation->deepFreed();
          return;
        }
      }
    }

    $rtContenders = checkKeyorEmpty($raceTrack, $df, $rep);
    $numberOfRTs = count($rtContenders);

    $contenderStatus = array();
    $contenderIds = array_keys($rtContenders);
    $contenderFinished = array();
    $contenderConfirmed = array();

    for($i = 0; $i < $numberOfRTs; $i++){
      $contenderId = $contenderIds[$i];
      $contenderStatus[$i] = True;
      $contenderConfirmed[$i] = False;
      $contenderFinished[$i] = False;
    }

    //check if the tracks are ready
    foreach($availableFieds as $idx => $confLink){


        $media = upa('media', 'fetch', array($confLink['mediaId']), False);
        $confirmationControls = json_decode($media['confirmation_controls'], JSON_FORCE_OBJECT);
        $anyLeftForThisMedia = False;


        //check each
        for($i = 0; $i < $numberOfRTs; $i++){

          //no longer contendinG? skip
          if($contenderStatus[$i] == False){
            continue;
          }

          $anyLeftForThisMedia = True;

          //still contending? see if this current value is correct
          $thisLinkValue = checkKeyOrFalse($raceTrack, $df, $rep,  $i, $idx);
          $thisDisposition = $confLink['disposition'];

          if($thisDisposition <> $thisLinkValue && $thisDisposition != '?'){
            $contenderStatus[$i] = False;
            $contenderConfirmed[$i] = False;

            if(empty($thisLinkValue)){
              $contenderFinished[$i] = False;
            } else{
              $contenderFinished[$i] = True;
            }
          } else{

            //for optionals, give a colour based on setting, but also check if filled in.
            if($thisDisposition == '?'){
                if(empty($thisLinkValue)){
                  $contenderStatus[$i] = False;
                  $contenderConfirmed[$i] = False;
                }
            }

            ///did we reach this with an OK result,than this guy is confirmed and finished
            if($confLink['chainId'] == count($availableFieds)){
              $contenderFinished[$i] = True;
              $contenderConfirmed[$i] = True;
            }
          }
        }

      //cphp('any left:' . $anyLeftForThisMedia);
      if($anyLeftForThisMedia == True){
        $fArr = array();
        $fArr['out_of_date_here'] = '0';
        $fArr['explanation_button'] = '';

        $fArr['fieldName'] = $confLink['mediaId'] . '_inzet';
        $chainInnocDate = checkKeyorFalse($metadata, $df, $rep, $idx, $fArr['fieldName']);
        $fArr['default'] = $chainInnocDate;
        if(empty($fArr['default'])){
          //cphp('Failed ' .  $confLink['mediaId'] . '_inzet');
          $metaDataDone = False;
        }


        $fArr['fieldName'] = $confLink['mediaId'] . '_aflees';
        $fArr['default'] = checkKeyorFalse($metadata, $df, $rep, $idx, $fArr['fieldName']);
        if(empty($fArr['default'])){
          //cphp('Failed ' . $confLink['mediaId'] . '_aflees');
          $metaDataDone = False;
        }

        if(checkKeyOrFalse($confirmationControls, 'pos')){
          $fArr['fieldName'] = $confLink['mediaId'] . '_poscontrol';
          $fArr['disposition'] = 'poscontrol';
          $fArr['default'] = upa('confKeyStore', 'fetchValue', array($chainInnocDate, $confLink['mediaId'], $fArr['disposition']), False);
          if(empty($fArr['default'])){
            //cphp('Failed ' . $confLink['mediaId'] . '_poscontrol');
            $metaDataDone = False;
          }
        }

        //neg
        if(checkKeyOrFalse($confirmationControls, 'neg')){
          $fArr['fieldName'] = $confLink['mediaId'] . '_negcontrol';
          $fArr['disposition'] = 'negcontrol';
          $fArr['default'] = upa('confKeyStore', 'fetchValue', array($chainInnocDate, $confLink['mediaId'], $fArr['disposition']), False);
          if(empty($fArr['default'])){
            $metaDataDone = False;
          }
        }

        //blanco
        if(checkKeyOrFalse($confirmationControls, 'blank')){
          $fArr['fieldName'] = $confLink['mediaId'] . '_blankcontrol';
          $fArr['disposition'] = 'blankcontrol';
          $fArr['default'] = upa('confKeyStore', 'fetchValue', array($chainInnocDate, $confLink['mediaId'], $fArr['disposition']), False);
          if(empty($fArr['default'])){
            $metaDataDone = False;
          }
        }

        if($media['hasDate'] == "1"){
          $fArr['default'] = upa('assuranceForms', 'checkExpiryDate', array($said,False,$confLink['mediaId'], True ), False);
          $fArr['fieldName'] = $confLink['mediaId'] . '_tht';
          $tht = generateHTML('confirmation/dateInput', $fArr);

          if(empty($fArr['default'])){
            $metaDataDone = False;
          }
        }
      }
    } //


    // cphp($contenderFinished);

    $finished = array_unique(array_values($contenderFinished));

    if($metaDataDone == False){
      $finished = array();
    }

    $this->Confirmation->deepFreed();

    if(count($finished) === 1 && $finished[0] === True){
      $this->setReady($line, 1, $df, $rep );
    } else{
      $this->setReady($line, 0, $df, $rep, False );
    }

  }

  function saveMediaInUse($id, $said, $inUse, $globalConf){
    $array = $this->createMediaArray($inUse, $said, $globalConf);
    $this->Confirmation->deepFreed();
    $this->Confirmation->id = $id;
    $this->Confirmation->in_use = json_encode($inUse, JSON_FORCE_OBJECT);
    $this->Confirmation->save();
  }

  function confirmationViewer($said, $df = 'global', $rep = 0, $silent = False, $fakerContenders = False ){

    $this->doNotRenderHeader = True;
    $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
    $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));
    $partOfAuth = upa('samples', 'partOfAuthProject', array($saidInfo['sample']));
    $sampleInfo = upa('samples', 'fetch', array($saidInfo['sample']));
    $lockObject = upa('keyrings', 'requestLockAndStatus', array('SAMPLE', $saidInfo['sample']),  False);

    $this->_template->set('errorClassShow', 'hidden');
    $this->_template->set('contentClassShow', '');
    $this->_template->set('barcode', $sampleInfo['barcode']);

   
    $dfText = array();

    $dfText['global'] = 'Globaal';
    $dfText['0'] = 'Onverdund monster';
    $dfText['0.1'] = '-1';
    $dfText['0.01'] = '-2';
    $dfText['0.001'] = '-3';
    $dfText['0.0001'] = '-4';
    $dfText['0.00001'] = '-5';
    $dfText['0.00001'] = '-6';
    $dfText['0.00001'] = '-7';
    $dfText['0.00001'] = '-8';

    $textualDil = checkKeyOrFalse($dfText, $df);
    if($textualDil == false){ $textualDil = $df; }

    $this->_template->set('dillution', $textualDil);

    $disabled = '';

    if($partOfAuth == True || $lockObject['locked'] == True){
        $disabled = 'disabled';
    }

    //meta flag
    $isMeta = ($assayInfo['type'] == '4') ? true : false;

    $globalConf = '0';
    if($df == 'global'){
      $globalConf = '1';
    }



    else {
      //per plate confirmation, also need to check if this value doesnt max out? If so , throw an error that this cant be confirmed.
      //and set to ready
      $result = upa('results', 'getTestResultsByDfAndRep', array($said, $df, $rep), False);
      if(!empty($result)){
        $data = json_decode($result[0]['data'], JSON_FORCE_OBJECT);
        $kve = checkKeyOrFalse($data, 'kve');

        #if(($kve > $assayInfo['max_count'] && $assayInfo['replicates'] == '0' ) || $kve == '0'){
        //  $this->_template->set('errorClassShow', '');
        //  $this->_template->set('contentClassShow', 'hide');
        //  return;
         //}
      }
    }

    
    $this->_template->set('said', $said);
    $this->_template->set('df', $df);
    $this->_template->set('rep', $rep);
    $this->_template->set('globalConf', $globalConf);


    $availableFieds = json_decode($assayInfo['confirmation_script'], True);
    $availableSupport = json_decode($assayInfo['confirmation_support'], True);

    //check if line is there
    $this->Confirmation->where('said', $said);
    $line = $this->Confirmation->search();

    //conf line not set yet, create it
    if(empty($line)){
      $line = $this->createConfLine($said, $globalConf);
    } else{
      $line = $line[0];
    }

    $renderLines = array();   //should hold all rendereable lines

    //convert data arrays
    $raceTrack = json_decode($line['racetrack'], JSON_FORCE_OBJECT);

    if($isMeta){
      $metaAssays = explode(',', $assayInfo['meta_assays'] );
    } else{
      $metaAssays = False;
    }

    if($fakerContenders != False && !empty($fakerContenders))
    {
      $nContend = $fakerContenders;
    }

    else{
      $nContend = $assayInfo['confirmation_depth'];
    }

    $raceTrack = $this->initRacetrack($line['id'], $raceTrack, $said, $saidInfo['sample'],  $availableFieds, $df , $rep, $metaAssays, $nContend);
    $mediaInUse = json_decode($line['in_use'], JSON_FORCE_OBJECT);
    #$fakePayLoad = $this->genfakearray();
    #$raceTrack = json_decode($fakePayLoad, JSON_FORCE_OBJECT);
    $metadata = json_decode($line['metadata'], JSON_FORCE_OBJECT);

    //count number of racetrack contenders for this df, and rep (if global this is 'global' and 0)
    $rtContenders = checkKeyorEmpty($raceTrack, $df, $rep);
    $numberOfRTs = count($rtContenders);


    if($numberOfRTs == 0){
      //can not render conf. chain, no contenders are available
      foreach($availableFieds as $idx => $confLink){
        $mediaInUse[$df][$rep][$confLink['mediaId']] = False;
      }
    }

    else{

      //write out number of RT contenders
      $contendertheaders = '';
      $contenderStatus = array();
      $contenderIds = array_keys($rtContenders);

      for($i = 0; $i < $numberOfRTs; $i++){
        $contenderId = $contenderIds[$i];
        $contendertheaders .= generateHTML('confirmation/tree/contenderthead', array('no' => $i+1, 'contenderId'=> $i, 'said' => $said, 'df'=> $df, 'rep'=> $rep, 'global'=> $globalConf));
        $contenderStatus[$i] = True;
        $contenderConfirmed[$i] = False;
        $contenderFinished[$i] = False;
      }

      //init conf table holder variable
      $confTableLines = '';


      //go through all conf links if they are still needed
      $metaDataDone = True;
      foreach($availableFieds as $idx => $confLink){

          $media = upa('media', 'fetch', array($confLink['mediaId']), False);
          $confirmationControls = json_decode($media['confirmation_controls'], JSON_FORCE_OBJECT);
          $contenderLineContent = '';
          $mediaInUse[$df][$rep][$confLink['mediaId']] = False;

          //check each
          for($i = 0; $i < $numberOfRTs; $i++){

            //no longer contendinG? skip
            if($contenderStatus[$i] == False){
              $contenderLineContent .= '<td></td>';
              continue;
            }

            //media was used
            $mediaInUse[$df][$rep][$confLink['mediaId']] = True;

            //still contending? see if this current value is correct
            $thisLinkValue = checkKeyOrFalse($raceTrack, $df, $rep,  $i, $idx);
            $thisDisposition = $confLink['disposition'];
            $class = '';

            if($thisDisposition <> $thisLinkValue && $thisDisposition != '?'){
              $contenderStatus[$i] = False;
              $contenderConfirmed[$i] = False;

              if(empty($thisLinkValue)){
                $contenderFinished[$i] = False;
              } else{
                $class = 'confError';
                $contenderFinished[$i] = True;
              }
            } else{

              //for optionals, give a colour based on setting, but also check if filled in.
              if($thisDisposition == '?'){

                  if(empty($thisLinkValue)){
                    $contenderStatus[$i] = False;
                    $contenderConfirmed[$i] = False;
                  } else{
                    if($thisLinkValue == '+'){
                      $class = 'confOK';
                    } else{
                      $class = 'confError';
                    }
                  }
              }

              //normal situation
              else{
                $class = 'confOK';
              }


              ///did we reach this with an OK result,than this guy is confirmed and finished
              if($confLink['chainId'] == count($availableFieds)){
                $contenderFinished[$i] = True;
                $contenderConfirmed[$i] = True;
              }
            }



            $thisFieldValues = array('default' => $thisLinkValue, 'chainN' => $confLink['chainId'], 'disposition' => $confLink['disposition'], 'contender' => $i, 'fieldName'=> 'contender',
                                      'placeHolder' => '+/-', 'said' => $said, 'dF' => $df, 'rep' => $rep, 'globalConf' => $globalConf, 'mediaId'=> $confLink['mediaId'], 'class' => $class, 'disabled'=> $disabled);

            $thisLinkField = generateHTML('confirmation/tree/confInput', $thisFieldValues);
            //$contenderLineContent .= '<td>'. $thisLinkField. '</td>';
            $contenderLineContent .= generateHTML('confirmation/tree/contenderCell', array('field' => $thisLinkField));
          }


          //check if this was even used anymore here
          if($mediaInUse[$df][$rep][$confLink['mediaId']] == False ){
            continue;
          }


          //request meta data for this link

          $inzetDatum = '';
          $afleesDatum = '';
          $posControl = '';
          $negControl = '';
          $blankControl = '';

          //request THT for this medium
          $thtDate = '';

          $fArr = array();
          $fArr['out_of_date_here'] = '0';
          $fArr['explanation_button'] = '';
          $fArr['maxbound'] = '';
          $fArr['contender'] = $i;
          $fArr['said'] = $said;
          $fArr['dF'] = $df;
          $fArr['rep'] = $rep;
          $fArr['globalConf'] = $globalConf;
          $fArr['chainN'] = $confLink['chainId'];
          $fArr['disabled'] = $disabled;

          $fArr['fieldName'] = $confLink['mediaId'] . '_inzet';
          $chainInnocDate = checkKeyorFalse($metadata, $df, $rep, $idx, $fArr['fieldName']);
          $fArr['default'] = $chainInnocDate;
          $fArr['fieldAlias'] = $confLink['mediaId']. '_inzet';
          $fArr['placeHolder'] = 'Inzetdatum';
          $fArr['disposition'] = 'inzet';
          $fArr['mediaId'] = $confLink['mediaId'];
          $inzet = generateHTML('confirmation/dateInput', $fArr);

          if(empty($fArr['default'])){
            $metaDataDone = False;
          }


          $fArr['fieldName'] = $confLink['mediaId'] . '_aflees';
          $fArr['default'] = checkKeyorFalse($metadata, $df, $rep, $idx, $fArr['fieldName']);
          $fArr['fieldAlias'] = $confLink['mediaId']. '_aflees';
          $fArr['placeHolder'] = 'Afleesdatum';
          $fArr['disposition'] = 'aflees';
          $aflees = generateHTML('confirmation/dateInput', $fArr);

          if(empty($fArr['default'])){
            $metaDataDone = False;
          }

          if(checkKeyOrFalse($confirmationControls, 'pos')){
            $fArr['fieldName'] = $confLink['mediaId'] . '_poscontrol';
            $fArr['fieldAlias'] = $confLink['mediaId']  . '_poscontrol';
            $fArr['placeHolder'] = 'Positief controle';
            $fArr['disposition'] = 'poscontrol';
            $fArr['default'] = upa('confKeyStore', 'fetchValue', array($chainInnocDate, $confLink['mediaId'], $fArr['disposition']), False);
            $posControl =  generateHTML('confirmation/tree/confInput', $fArr);

            if(is_null($fArr['default'])){
              $metaDataDone = False;
            }
          }

          //neg
          if(checkKeyOrFalse($confirmationControls, 'neg')){
            $fArr['fieldName'] = $confLink['mediaId'] . '_negcontrol';
            $fArr['fieldAlias'] = $confLink['mediaId']  . '_negcontrol';
            $fArr['placeHolder'] = 'Negatieve controle';
            $fArr['disposition'] = 'negcontrol';
            $fArr['default'] = upa('confKeyStore', 'fetchValue', array($chainInnocDate, $confLink['mediaId'], $fArr['disposition']), False);
            $negControl =  generateHTML('confirmation/tree/confInput', $fArr);

            //if
            if(is_null($fArr['default'])){
              $metaDataDone = False;
            }
          }

          //blanco
          if(checkKeyOrFalse($confirmationControls, 'blank')){
            $fArr['fieldName'] = $confLink['mediaId'] . '_blankcontrol';
            $fArr['fieldAlias'] = $confLink['mediaId']  . '_blankcontrol';
            $fArr['placeHolder'] = 'Blanco';
            $fArr['disposition'] = 'blankcontrol';
            $fArr['default'] = upa('confKeyStore', 'fetchValue', array($chainInnocDate, $confLink['mediaId'], $fArr['disposition']), False);
            $blankControl =  generateHTML('confirmation/tree/confInput', $fArr);

            if(is_null($fArr['default'])){
              $metaDataDone = False;
            }
          }

          $tht = '';
          if($media['hasDate'] == "1"){
            $fArr['default'] = upa('assuranceForms', 'checkExpiryDate', array($said,False,$confLink['mediaId'], True ), False);
            $outOfDateInfo = upa('assuranceForms', 'getOutOfDateInfo', array($said, $confLink['mediaId']), False);
            $fArr['fieldName'] = $confLink['mediaId'] . '_tht';
            $fArr['fieldAlias'] = $confLink['mediaId']. '_tht';
            $fArr['placeHolder'] = 'THT-datum';
            $fArr['disposition'] = 'tht';
            $fArr['out_of_date_here'] = checkKeyOrFalse($outOfDateInfo, 'outOfDateHere') ? '1' : '0';
            $fArr['explanation_button'] = $this->assuranceExplanationButton($outOfDateInfo, $confLink['mediaId']);
            $tht = generateHTML('confirmation/dateInput', $fArr);

            if(empty($fArr['default'])){
              $metaDataDone = False;
            }
          }

          $confTableLines .= generateHTML('confirmation/tree/line', array('linkName' => $media['short_name'], 'disposition'=> $confLink['disposition'], 'contenders'=> $contenderLineContent,
                                                                          'inzet' => $inzet, 'aflees' => $aflees, 'pos' => $posControl, 'neg' => $negControl, 'blankControl' => $blankControl, 'tht'=>$tht));

          // if(empty($fArr['default'])){
          //   $metaDataDone = False;
          // }
        }
    }

    $supportFieldsHtml = '';
    if(is_array($availableSupport)){

      foreach($availableSupport as $thisField){

        $media = upa('media', 'fetch', array($thisField['mediaId']), False);

        if($media['hasDate'] == '0'){
          continue;
        }

        $fieldName = $media['short_name'];
        $mediaN = $thisField['mediaId'];
        $chainN = $thisField['chainId'];

        $boxEnabled = checkKeyOrFalse($mediaInUse, $df, $rep, $mediaN );
        //if(in_array($mediaN, $enabledFields) && $enabledFields[$mediaN] == True ){
        if($boxEnabled == True){
          $active = '<input type="checkbox" globalConf="' . $globalConf . '" class="activatorCheckbox" df="' . $df . '" rep="'. $rep .'" said="'. $said . '" mediaId="'. $mediaN. '" checked="checked" id="activator_'. $mediaN . '" /> ';
        }else{
          $active = '<input type="checkbox" globalConf="' . $globalConf . '" class="activatorCheckbox" df="' . $df . '" rep="'. $rep .'" said="'. $said . '" mediaId="'. $mediaN . '" id="activator_'. $mediaN . '"/> ';
        }

        $fieldName = '<label for="activator_'. $mediaN . '">' . $active .  $media['short_name']. '</label>';

        $fArr['fieldName'] = $mediaN . '_tht';
        $fArr['fieldAlias'] = $mediaN. '_tht';
        $fArr['placeHolder'] = 'THT-datum';
        $fArr['disposition'] = 'tht';
        $fArr['mediaId'] = $mediaN;
        $fArr['chainN'] = $chainN;
        $fArr['disabled'] = $disabled;

        $fArr['default'] = upa('assuranceForms', 'checkExpiryDate', array($said,False,$mediaN, True ), False);
        $outOfDateInfo = upa('assuranceForms', 'getOutOfDateInfo', array($said, $mediaN), False);
        $fArr['out_of_date_here'] = checkKeyOrFalse($outOfDateInfo, 'outOfDateHere') ? '1' : '0';
        $fArr['explanation_button'] = $this->assuranceExplanationButton($outOfDateInfo, $mediaN);

        if($media['type'] == '3'){
          $fArr['placeHolder'] = $media['supplements'];
          $fArr['acceptable_range'] = isset($media['acceptable_range']) ? htmlspecialchars($media['acceptable_range'], ENT_QUOTES, 'UTF-8') : '';
          $blankControl =  generateHTML('confirmation/materialInput', $fArr);
          $tht = generateHTML('confirmation/materialInput', $fArr);
        } else{
          $tht = generateHTML('confirmation/dateInput', $fArr);
        }

        if($boxEnabled == True){
          if(empty($fArr['default'])){
            $metaDataDone = False;
          }
        }


        $supportFieldsHtml .= generateHTML('confirmation/tree/supportline', array('chainN'=> $chainN, 'fieldName' => $fieldName ,
            'positive' => NULL, 'tested' => NULL, 'inzet'=> NULL, 'aflees' => NULL, 'tht'=> $tht,
            'pos' => NULL, 'neg' => NULL, 'blanco' => NULL, 'active'=> $active
        ));

      }
    }

    $confNote = generateHTML('confirmation/note', array('id' => $line['id']  ,'note'=> $line['note']));

    //check if table is needed
    if(!empty($assayInfo['show_conf_table']) && $assayInfo['show_conf_table'] != 'NULL'){
        //$confTable = file_get_contents(ROOT . '/app/private/confirmationtables/' . $assayInfo['show_conf_table'] . '.table.php');
        $confTable = upa('confirmationTables', 'fetchTable', array($assayInfo['show_conf_table']), False);
    } else{
        $confTable = '';
    }


    $this->saveMediaInUse($line['id'], $line['said'], $mediaInUse, $globalConf);

    //output holder variable
    $confView = '';


    $finished = array_unique(array_values($contenderFinished));


    $finished = array_unique(array_values($contenderFinished));
    $endConclusion = 'Nog niet afgerond';
    $endPos = '[?]';

    if($metaDataDone == False){
      $finished = False;
    }


    if(is_array($finished) && count($finished) === 1 && $finished[0] === True){
      $endConclusion = 'Afgerond';
      $endPos =  count(array_filter(array_values($contenderConfirmed)));

      $confirmedCount = count(array_filter($contenderConfirmed));
      $ratio = $confirmedCount / $numberOfRTs;
      $this->setReady($line, 1, $df, $rep, $ratio );
    } else{

      $this->setReady($line, 0, $df, $rep, 'unset' );
    }

    //add wrapper table
    $conHeader = generateHTML('confirmation/tree/confwrapperheader', array('contenderHeaders' => $contendertheaders));
    $confView .= generateHTML('confirmation/tree/confwrapper', array('confHeader' => $conHeader , 'confLines'=> $confTableLines, 'contenders' => NULL, 'endConclusion' => $endConclusion, 'tested' => $numberOfRTs, 'endPos' => $endPos, 'analysis' => $assayInfo['name'] ));

    //set table
    if($silent == False)
    {
      $this->_template->set('output', $confView);
      $this->_template->set('support_output', $supportFieldsHtml);
      $this->_template->set('conf_note', $confNote);
      $this->_template->set('conf_table', $confTable);
    }
    
  }

  
  function setReady($line, $flag, $df, $rep, $ratio = False, $said = False){
    if($line == False){
      $this->Confirmation->deepFreed();
      $line = $this->fetchLine($said);
    }

    $this->Confirmation->deepFreed();
    $isCompleteReady = True;
    $metaData = json_decode($line['metadata'], JSON_FORCE_OBJECT);
    $metaData[$df][$rep]['isReady'] = $flag;

    if($ratio !== False && $ratio !== 'unset'){
      $metaData[$df][$rep]['ratio'] = $ratio;
    }

    if($ratio === 'unset')
    {
      $metaData[$df][$rep]['ratio'] = False;
    }

    //check if completly ready
    foreach($metaData as $df => $reps){
      foreach($reps as $rep){
        $repDone = $rep['isReady'];
        if($repDone == False){
          $isCompleteReady = False;
        }
      }
    }


    $this->Confirmation->id = $line['id'];
    $this->Confirmation->metadata = json_encode($metaData, JSON_FORCE_OBJECT);
    $this->Confirmation->isReady = $isCompleteReady;
    $this->Confirmation->save();
  }




  function updateLine($said, $df, $rep ){



    $this->render = false;
    $this->Confirmation->where('said', $said);
    $line = $this->Confirmation->first();
    $this->Confirmation->deepFreed();
    $oriRep = $rep;
    $oriDf = $df;

    $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
    $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));

    $globalConf = False;
    if($assayInfo['confirmation_type'] == '0'){
      $globalConf = '1';
    }

    if(empty($line)){
      return;
    }


    $inUse = json_decode($line['in_use'], JSON_FORCE_OBJECT);
    $metaData = json_decode($line['metadata'], JSON_FORCE_OBJECT);
    $raceTrack = json_decode($line['racetrack'], JSON_FORCE_OBJECT);
    $availableFieds = json_decode($assayInfo['confirmation_script'], True);

    if($globalConf == '1')
    {

    } 
    
    else
    {

      $results = upa('results', 'getTestResults', array($said), False);

      //scrub out non existing ones here
      $resultsCurrent = upa('results', 'getCurrentDillutions', array($said, 'printing_array'), False);
      $resultsThere = $resultsCurrent['dfs'];

      foreach($metaData as $rtDf => $metaContent){

        foreach($metaContent as $metaRep => $metaRepContent){
          if($metaRep > $resultsCurrent['rep']){
              unset($metaData[$rtDf][$metaRep]);
              unset($raceTrack[$rtDf][$metaRep]);
              unset($inUse[$rtDf][$metaRep]);
          }
        }

        if(!in_array($rtDf, $resultsThere)){
          unset($metaData[$rtDf]);
          unset($raceTrack[$rtDf]);
          unset($inUse[$rtDf]);
        }
      }

      foreach($results as $rl){

        $dfExists = checkKeyorFalse($metaData, $rl['df']);

        //add df array if it exists
        if(!$dfExists){
          $metaData[$rl['df']] = array();
        }

        $repExists = checkKeyorFalse($metaData, $rl['df'], $rl['rep']);

        if(!$repExists){
          $metaData[$rl['df']][$rl['rep']] = array();
          $metaData[$rl['df']][$rl['rep']]['isReady'] = False;
        }

        //check KVE bound
        $result = upa('results', 'getTestResultsByDfAndRep', array($said, $rl['df'], $rl['rep']), False);
        if(!empty($result)){
          $data = json_decode($result[0]['data'], JSON_FORCE_OBJECT);
          $kve = checkKeyOrFalse($data, 'kve');


          //min-max? Cant confirm, thus, ready
          //11-05-2017 changes this to include counts over max since this is now allowed.
          //if($kve > $assayInfo['max_count'] || $kve == '0'){
          if( $kve === '0' || $kve == '>'){
            
            $metaData[$rl['df']][$rl['rep']]['isReady'] = True;
          } else{
            //check if there is a racetrack
            $isMeta = False;

            if($assayInfo['type'] == 4 && $assayInfo['meta_assays'] != ''){
              $isMeta = explode(',', $assayInfo['meta_assays'] );
            }

            $this->initRacetrack($line['id'], $raceTrack, $said, $saidInfo['sample'],  $availableFieds, $rl['df'], $rl['rep'], $isMeta, $assayInfo['confirmation_depth']);
          }
        }
      }
    }

    $isCompleteReady = True;

    //check if completly ready
    foreach($metaData as $df => $reps){
      foreach($reps as $rep){
        $repDone = $rep['isReady'];
        if($repDone == False){
          $isCompleteReady = False;
        }
      }
    }


    $this->Confirmation->id = $line['id'];
    $this->Confirmation->metadata = json_encode($metaData, JSON_FORCE_OBJECT);
    $this->Confirmation->in_use = json_encode($inUse, JSON_FORCE_OBJECT);
    $this->Confirmation->raceTrack = json_encode($raceTrack, JSON_FORCE_OBJECT);
    $this->Confirmation->isReady = $isCompleteReady;
    $this->Confirmation->save();

    $this->Confirmation->deepFreed();
    $this->recheckReadyStatus($said, $oriDf, $oriRep);

  }

  /* end conf overhaul */
    function fetchLine($said){
        $this->Confirmation->where('said', $said);
        $this->Confirmation->limit(1);
        $line = $this->Confirmation->search();

        //conf line not set yet, create it
        if(empty($line)){
            $confData = $this->createConfLine($said);
        } else {
            $this->_note = $line['0']['note'];
            $this->_id = $line['0']['id'];
            $confData = $line['0'];
        }

        return $confData;
    }


    function createConfLine($said, $globalConf = False){

        $this->Confirmation->free();
        $this->Confirmation->said = $said;        

        //need to popuplate the data field to reduce the risk of errors downstream
        $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
        $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));
        $metaData = array();

        if($globalConf == False){
            if($assayInfo['confirmation_type'] == '0'){
              $globalConf = '1';
            }
        }

        $availableFieds = json_decode($assayInfo['confirmation_script'], True);
        $availableSupport = json_decode($assayInfo['confirmation_support'], True);

        $defaultArray = array();
        $activeArray = array();

        #$fieldNames = array('tht', 'inzet', 'n', 'pos', 'aflees', 'poscontrol', 'negcontrol', 'blankcontrol');

        $fieldNames = array('inzet', 'aflees');

        if(is_array($availableFieds)){

          foreach($availableFieds as $thisField){

                //need to stratify this on DF or global depending on                //settings.

                $mediaN = $thisField['mediaId'];
                $chainN = $thisField['chainId'];
                $activeArray[$mediaN] = True;

                foreach($fieldNames as $fieldName){
                  $composite = $mediaN . '_' .  $fieldName;
                  $defaultArray[$chainN][$composite] = '';
                }
            }
        }

        if(is_array($availableSupport)){
          foreach($availableSupport as $thisSupportField){
            $mediaN =  $thisSupportField['mediaId'];
            $activeArray[$mediaN] = False;
          }
        }

        //setup is ready metadata
        if($globalConf == 1){
          $metaData['global']['0']['isReady'] = False;
        } else{
          $results = upa('results', 'getTestResults', array($said), False);
          foreach($results as $rl){
            $dfExists = checkKeyorFalse($metaData, $rl['df']);

            if(!$dfExists){
              $metaData[$rl['df']] = array();
            }

            $repExists = checkKeyorFalse($metaData, $rl['df'], $rl['rep']);

            if(!$repExists){
              $metaData[$rl['df']][$rl['rep']] = array();
            }

            $metaData[$rl['df']][$rl['rep']]['isReady'] = False;
          }
        }


        //$this->Confirmation->data = '{}';
        #$this->Confirmation->data = json_encode($defaultArray, JSON_FORCE_OBJECT);
        $this->Confirmation->data = json_encode(array(), JSON_FORCE_OBJECT);
        $this->Confirmation->racetrack = json_encode(array(), JSON_FORCE_OBJECT);
        $this->Confirmation->metadata = json_encode($metaData, JSON_FORCE_OBJECT);

        $mediaArray = $this->createMediaArray(array(), $said, $globalConf);

        $this->Confirmation->in_use = json_encode($mediaArray, JSON_FORCE_OBJECT);
        $this->Confirmation->save();
        $this->Confirmation->free();

        //we need to check now if some are ready already because of min/max constraints for KVE
        if($globalConf == 1){
          $this->recheckReadyStatus($said, 'global', 0);
        } else{
          $results = upa('results', 'getTestResults', array($said), False);
          foreach($results as $rl){
            $this->recheckReadyStatus($said, $rl['df'], $rl['rep']);
          }
        }

        $this->Confirmation->deepFreed();

        $this->Confirmation->where('said', $said);
        $rl = $this->Confirmation->search();
        return $rl['0'];
    }

 

    function createMediaArray($currentArray, $said, $globalConf){

      $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
      $assayInfo = upa('assays', 'fetchSingle', array($saidInfo['assay_base']));
      $availableFieds = json_decode($assayInfo['confirmation_script'], True);
      $availableSupport = json_decode($assayInfo['confirmation_support'], True);
      $activeArray = array();

      if(is_array($availableFieds)){
        foreach($availableFieds as $thisField){
              $mediaN = $thisField['mediaId'];
              $chainN = $thisField['chainId'];
              $activeArray[$mediaN] = True;
          }
      }

      if(is_array($availableSupport)){
        foreach($availableSupport as $thisSupportField){
          $mediaN =  $thisSupportField['mediaId'];
          $activeArray[$mediaN] = True;
        }
      }


      if($globalConf == 1){
        //setup one global, 0 array key
        foreach($activeArray as $media => $status){
          if($status == True){
            $exists = checkKeyOrFalse($currentArray, 'global', '0', $media);
            if(!$exists){
              $currentArray['global']['0'][$media] = True;
            }
          }
        }
      }

      else{
        //grab results and reps, create one for each
        $results = upa('results', 'getTestResults', array($said), False);
        foreach($results as $rl){
          $dfExists = checkKeyorFalse($currentArray, $rl['df']);

          if(!$dfExists){
            $currentArray[$rl['df']] = array();
          }

          $repExists = checkKeyorFalse($currentArray, $rl['df'], $rl['rep']);

          if(!$repExists){
            $currentArray[$rl['df']][$rl['rep']] = array();
          }

          foreach($activeArray as $media => $status){
            $mediaExists = checkKeyorFalse($currentArray, $rl['df'], $rl['rep'], $media );
            if(!$mediaExists){
                $currentArray[$rl['df']][$rl['rep']][$media] = True;
            }
          }

        }
      }

      return $currentArray;
    }


    function renderDialog($said){

        $this->render = 0;
        $saidInfo = upa('sampleAnalysis', 'fetchById', array($said));
        $partOfAuth = upa('samples', 'partOfAuthProject', array($saidInfo['sample']));
        $lockObject = upa('keyrings', 'requestLockAndStatus', array('SAMPLE', $saidInfo['sample']),  False);

        $dF = $_POST['dF'];
        $rep = $_POST['rep'];
        $globalConf = $_POST['globalConf'];


        $disabled = '';
        if($partOfAuth == True || $lockObject['locked'] == True){
            $disabled = 'disabled';
        }

        $assayInfo = pa('assays', 'fetchSingle', array($saidInfo['assay_base']));
        $availableFieds = json_decode($assayInfo['confirmation_script'], True);
        $supportFields = json_decode($assayInfo['confirmation_support'], True);
        $isMeta = False;

        if($assayInfo['type'] == 4 && $assayInfo['meta_assays'] != ''){
          $isMeta = explode(',', $assayInfo['meta_assays'] );
        }

        $data = $this->fetchLine($said);
        $enabledFields = json_decode($data['in_use'], JSON_FORCE_OBJECT);

        if(!is_array($enabledFields)){
          //was not yet set, force array
          $enabledFields = array();
        }

        $dArr = json_decode($data['data'], True);

        //global configuration
        if($globalConf == '1' || $globalConf == 1){
            $thisPlate = $dArr;
        }

        //per plate analysis
        //dArr now holds ALL the dillutions and replicates
        else{
            if(array_key_exists($dF, $dArr) && array_key_exists($rep, $dArr[$dF])){
                $thisPlate = $dArr[$dF][$rep];
            } else{
                $thisPlate = array();
            }
        }

        //$cForm = new formFactory($this->_controller);
        //$cForm->setId('confForm');
        //$cForm->setTemplate('generic');

        $confFieldsHTML = generateHTML('confirmation/header', array());
        //$chainN = 1;

        if(is_array($availableFieds)){

            foreach($availableFieds as $thisField){

            $media = upa('media', 'fetch', array($thisField['mediaId']), False);
            $confirmationControls = json_decode($media['confirmation_controls'], JSON_FORCE_OBJECT);


            if($media['type'] == 1){

                $defaultN = '' ;
                $defaultPos = '' ;
                $defaultInzet = '';
                $defaultAflees = '';
                $active = '';


                $mediaN = $thisField['mediaId'];
                $chainN = $thisField['chainId'];

                if(in_array($mediaN, $enabledFields) && $enabledFields[$mediaN] == True ){
                  $active = '<input type="checkbox" globalConf="' . $globalConf . '" class="activatorCheckbox" df="' . $dF . '" rep="'. $rep .'" said="'. $said . '" mediaId="'. $mediaN. '" checked="checked" id="activator_'. $mediaN . '" /> ';
                }else{
                  $active = '<input type="checkbox" globalConf="' . $globalConf . '" class="activatorCheckbox" df="' . $dF . '" rep="'. $rep .'" said="'. $said . '" mediaId="'. $mediaN . '" id="activator_'. $mediaN . '"/> ';
                }

                $fieldName = '<label for="activator_'. $mediaN . '">' . $active .  $media['short_name']. '</label>';

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_n', $thisPlate[$chainN])){
                    $defaultN = $thisPlate[$chainN][$mediaN . '_n'];
                }

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_pos', $thisPlate[$chainN])){
                    $defaultPos = $thisPlate[$chainN][$mediaN . '_pos'];
                }

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_inzet', $thisPlate[$chainN])){
                    $defaultInzet = $thisPlate[$chainN][$mediaN . '_inzet'];
                }

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_aflees', $thisPlate[$chainN])){
                    $defaultAflees = $thisPlate[$chainN][$mediaN . '_aflees'];
                }


                $defaultPosControl = '';
                $defaultNegControl  = '';
                $defaultBlankControl = '';

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_poscontrol', $thisPlate[$chainN])){
                    $defaultPosControl = $thisPlate[$chainN][$mediaN . '_poscontrol'];
                }

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_negcontrol', $thisPlate[$chainN])){
                    $defaultNegControl = $thisPlate[$chainN][$mediaN . '_negcontrol'];
                }

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_blankcontrol', $thisPlate[$chainN])){
                    $defaultBlankControl = $thisPlate[$chainN][$mediaN . '_blankcontrol'];
                }

                $fArr = array();
                $fArr['out_of_date_here'] = '0';
                $fArr['explanation_button'] = '';
                $fArr['maxbound'] = '';
                $fArr['said'] = $said;
                $fArr['dF'] = $dF;
                $fArr['rep'] = $rep;
                $fArr['globalConf'] = $globalConf;
                $fArr['chainN'] = $chainN;
                $fArr['disabled'] = $disabled;

                $fArr['default'] = $defaultInzet;
                $fArr['fieldName'] = $mediaN . '_inzet';
                $fArr['fieldAlias'] = $mediaN. '_inzet';
                $fArr['placeHolder'] = 'Inzetdatum';
                $fArr['disposition'] = 'inzet';
                $fArr['mediaId'] = $mediaN;
                $inzet = generateHTML('confirmation/dateInput', $fArr);

                //tested
                $fArr['default'] = $defaultN;
                $fArr['fieldName'] = $mediaN . '_n';
                $fArr['fieldAlias'] = $mediaN. '_n';
                $fArr['placeHolder'] = 'Getest';
                $fArr['disposition'] = 'n';
                $fArr['maxbound'] = upa('results', 'findMaximumCount', array($saidInfo['sample'], $said, 'kve', $isMeta), False);
                $tested = generateHTML('confirmation/confInput', $fArr);

                //positive
                $fArr['default'] = $defaultPos;
                $fArr['fieldName'] = $mediaN . '_pos';
                $fArr['fieldAlias'] = $mediaN  . '_pos';
                $fArr['placeHolder'] = 'Positief';
                $fArr['disposition'] = 'pos';
                $positive =  generateHTML('confirmation/confInput', $fArr);

                //afleesdatum
                $fArr['default'] = $defaultAflees;
                $fArr['fieldName'] = $mediaN . '_aflees';
                $fArr['fieldAlias'] = $mediaN. '_aflees';
                $fArr['placeHolder'] = 'Afleesdatum';
                $fArr['disposition'] = 'aflees';
                $aflees = generateHTML('confirmation/dateInput', $fArr);

                //afleesdatum
                $fArr['default'] = upa('assuranceForms', 'checkExpiryDate', array($said,False,$mediaN, True ), False);

                $posControl = NULL;
                $negControl = NULL;
                $blankControl = NULL;

                $fArr['fieldName'] = $mediaN . '_tht';
                $fArr['fieldAlias'] = $mediaN. '_tht';
                $fArr['placeHolder'] = 'THT-datum';
                $fArr['disposition'] = 'tht';
                $tht = generateHTML('confirmation/dateInput', $fArr);


                //pos
                if(checkKeyOrFalse($confirmationControls, 'pos')){
                  $fArr['default'] = $defaultPosControl;
                  $fArr['fieldName'] = $mediaN . '_poscontrol';
                  $fArr['fieldAlias'] = $mediaN  . '_poscontrol';
                  $fArr['placeHolder'] = 'Positief controle';
                  $fArr['disposition'] = 'poscontrol';
                  $posControl =  generateHTML('confirmation/confInput', $fArr);
                }

                //neg
                if(checkKeyOrFalse($confirmationControls, 'neg')){
                  $fArr['default'] = $defaultNegControl;
                  $fArr['fieldName'] = $mediaN . '_negcontrol';
                  $fArr['fieldAlias'] = $mediaN  . '_negcontrol';
                  $fArr['placeHolder'] = 'Negatieve controle';
                  $fArr['disposition'] = 'negcontrol';
                  $negControl =  generateHTML('confirmation/confInput', $fArr);
                }

                //blanco
                if(checkKeyOrFalse($confirmationControls, 'blank')){
                  $fArr['default'] = $defaultBlankControl;
                  $fArr['fieldName'] = $mediaN . '_blankcontrol';
                  $fArr['fieldAlias'] = $mediaN  . '_blankcontrol';
                  $fArr['placeHolder'] = 'Blanco';
                  $fArr['disposition'] = 'blankcontrol';
                  $blankControl =  generateHTML('confirmation/confInput', $fArr);
                }

                $confFieldsHTML .= generateHTML('confirmation/line', array('chainN'=> $chainN, 'fieldName' => $fieldName ,
                    'positive' => $positive, 'tested' => $tested, 'inzet'=> $inzet, 'aflees' => $aflees, 'tht'=> $tht,
                    'pos' => $posControl, 'neg' => $negControl, 'blanco' => $blankControl
                ));
            }

            elseif($media['type'] == 2){

                $defaultN = '0' ;

                $fieldName = $media['name'];
                $mediaN = $thisField['mediaId'];
                $chainN = $thisField['chainId'];

                if(array_key_exists($chainN, $thisPlate) && array_key_exists($mediaN . '_n', $thisPlate[$chainN])){
                    $defaultN = $thisPlate[$chainN][$mediaN . '_n'];
                }

                $fArr = array();
                $fArr['out_of_date_here'] = '0';
                $fArr['explanation_button'] = '';
                $fArr['said'] = $said;
                $fArr['dF'] = $dF;
                $fArr['rep'] = $rep;
                $fArr['globalConf'] = $globalConf;
                $fArr['chainN'] = $chainN;
                $fArr['disabled'] = $disabled;

                $fArr['default'] = $defaultN;
                $fArr['fieldName'] = $mediaN . '_n';
                $fArr['fieldAlias'] = $mediaN. '_n';
                $fArr['placeHolder'] = 'Getest';
                $fArr['disposition'] = 'n';


                if($defaultN == '0'){
                    $positive =  generateHTML('confirmation/confDropper0', $fArr);
                } else{
                    $positive =  generateHTML('confirmation/confDropper1', $fArr);
                }

                $confFieldsHTML .= generateHTML('confirmation/line', array('chainN'=> $chainN, 'fieldName' => $fieldName ,
                    'positive' => $positive, 'tested' => '', 'inzet'=> '', 'aflees' => '', 'tht'=> '',
                    'pos' => '', 'neg' => '', 'blanco' => ''
                ));
            }
            //$chainN++;
            //$cForm->addInputField( 'conf_' . $fieldName, $fieldAlias, 'text', 'confirmation-input input-block-level' , $default, 'Enter value', array('said'=>$said, 'dF' => $dF, 'rep' => $rep, 'globalConf' => $globalConf, 'fieldname' => $fieldName, '' => $disabled) );
            }
        }

        if(is_array($supportFields)){


          foreach($supportFields as $thisField){


            $media = upa('media', 'fetch', array($thisField['mediaId']), False);
            $fieldName = $media['short_name'];
            $mediaN = $thisField['mediaId'];
            $chainN = $thisField['chainId'];

            $boxEnabled = checkKeyOrFalse($enabledFields,$mediaN );
            //if(in_array($mediaN, $enabledFields) && $enabledFields[$mediaN] == True ){
            if($boxEnabled == True){
              $active = '<input type="checkbox" globalConf="' . $globalConf . '" class="activatorCheckbox" df="' . $dF . '" rep="'. $rep .'" said="'. $said . '" mediaId="'. $mediaN. '" checked="checked" id="activator_'. $mediaN . '" /> ';
            }else{
              $active = '<input type="checkbox" globalConf="' . $globalConf . '" class="activatorCheckbox" df="' . $dF . '" rep="'. $rep .'" said="'. $said . '" mediaId="'. $mediaN . '" id="activator_'. $mediaN . '"/> ';
            }

            $fieldName = '<label for="activator_'. $mediaN . '">' . $active .  $media['short_name']. '</label>';

            $fArr['fieldName'] = $mediaN . '_tht';
            $fArr['fieldAlias'] = $mediaN. '_tht';
            $fArr['placeHolder'] = 'THT-datum';
            $fArr['disposition'] = 'tht';
            $fArr['mediaId'] = $mediaN;
            $fArr['chainN'] = $chainN;
            $fArr['disabled'] = $disabled;

            $fArr['default'] = upa('assuranceForms', 'checkExpiryDate', array($said,False,$mediaN, True ), False);

            if($media['type'] == '3'){
              $fArr['placeHolder'] = $media['supplements'];
              $fArr['acceptable_range'] = isset($media['acceptable_range']) ? htmlspecialchars($media['acceptable_range'], ENT_QUOTES, 'UTF-8') : '';
              $tht = generateHTML('confirmation/materialInput', $fArr);
            } else{
              $tht = generateHTML('confirmation/dateInput', $fArr);
            }


            $supportFieldsHtml .= generateHTML('confirmation/tree/suppline', array('chainN'=> $chainN, 'fieldName' => $fieldName ,
                'positive' => NULL, 'tested' => NULL, 'inzet'=> NULL, 'aflees' => NULL, 'tht'=> $tht,
                'pos' => NULL, 'neg' => NULL, 'blanco' => NULL, 'active'=> $active
            ));



          }
        }

        //add note field
        //use a this because reasons
        $confNote = generateHTML('confirmation/note', array('id' => $this->_id  ,'note'=> $this->_note));

        //check if table is needed
        if(!empty($assayInfo['show_conf_table']) && $assayInfo['show_conf_table'] != 'NULL'){
            //$confTable = file_get_contents(ROOT . '/app/private/confirmationtables/' . $assayInfo['show_conf_table'] . '.table.php');
            $confTable = upa('confirmationTables', 'fetchTable', array($assayInfo['show_conf_table']), False);
        } else{
            $confTable = '';
        }

        $wrapper = generateHTML('confirmation/wrapper', array('conf_content' => $confFieldsHTML, 'conf_note'=> $confNote,  'LB' => ALPC_BASEPATH, 'conf_table' => $confTable));
        print $wrapper;

    }

    function saveActivator(){

      $said = $_POST['said'];
      $dF = $_POST['dF'];
      $rep = $_POST['rep'];

      $globalConf = filter_var($_POST['globalConf'], FILTER_VALIDATE_BOOLEAN);
      $active = filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN);
      $media = $_POST['mediaId'];


      $this->render = 0;
      $data = $this->fetchLine($said);
      $dataArr = json_decode($data['in_use'], True);

      if(!is_array($dataArr)){
        $dataArr = array();
      }

      if($globalConf == '1' || $globalConf == 1 || $globalConf == True){
          //$dataArr[$media] = $active;
          $dataArr['global']['0'][$media] = $active;
      }else{
          $dataArr[$dF][$rep][$media] = $active;
      }

      $this->Confirmation->id = $data['id'];
      $this->Confirmation->said = $said;
      //$this->Confirmation->data = $data['data'];
      $this->Confirmation->in_use = json_encode($dataArr, JSON_FORCE_OBJECT);
      $this->Confirmation->save();

      // $mediaName = upa('media', 'getMediaName', array($media, True), False);
      // $mediaName = $mediaName . ' - ' . $placeholder;
      //
      // $sampleId = upa('sampleAnalysis', 'saidToSample', array($said), False);
      // upa('revisions', 'registerRevision', array('SCOPE_SAMPLERESULT_CONFIRMATION_CHANGE',
      //   $sampleId,
      //   $mediaName,
      //   $prevValue,
      //   $value,
      //   $said), 0);

    }


    function saveNote(){
      $this->render = False;
      $this->Confirmation->id = $_POST['id'];
      $this->Confirmation->note = $_POST['note'];
      $this->Confirmation->save();
    }

    function saveField(){

        $this->render = 0;

        $said = $_POST['said'];
        $dF = $_POST['dF'];
        $rep = $_POST['rep'];
        $chainN = $_POST['chainN'];
        $globalConf = $_POST['globalConf'];
        $media = $_POST['mediaId'];
        $placeholder = $_POST['placeholder']; //we are using the placeholder as a small hack here to get the name of the thing we are changing.
        $contender = (isset($_POST['contender']) ? $_POST['contender'] : null); //TODO fix this
        $disposition = $_POST['disposition'];

        $field = (isset($_POST['field']) ? $_POST['field'] : null );
        $value = $_POST['value'];

        $data = $this->fetchLine($said);
        $metaData = json_decode($data['metadata'], True);        

        $prevValue = '';

        $this->Confirmation->id = $data['id'];
        $confControls = array('poscontrol', 'negcontrol', 'blankcontrol');

        //mostly for revisios
        $saidInfo = upa('sampleAnalysis', 'fetch', array($said), false);
        $sampleInfo = upa('samples', 'fetch', array($saidInfo['sample']), False);
        $assayInfo = upa('assays', 'fetch', array($saidInfo['assay_base']), False);
        $mediaInfo = upa('media', 'fetch', array($media), False);

        if(in_array($disposition, $confControls)){
          //delegate to confKeyStore
          $idx = $chainN - 1;
          $innocField = $media . '_inzet';
          $innocDate = checkKeyOrFalse($metaData, $dF, $rep, $idx, $innocField);

          $event = 'Bevestiging aangepast: ' . $sampleInfo['barcode'] . ', analyse: ' . $assayInfo['name'] . ', test:' . $mediaInfo['short_name'] . ' verdunning: ' . $dF . ', replica: ' . $rep . ', controle:' . $disposition;
          upa('changeTracker', 'changed', array(16, $saidInfo['project'], $sampleInfo['id'], $said, $event, False, $value), False);
          upa('confKeyStore', 'store',  array($innocDate, $media, $disposition, $value), False);
          return;
        }

        //save to racetrack
        if($field == 'contender'){

          $currRaceTrack = json_decode($data['racetrack'], JSON_FORCE_OBJECT);

          //set new info
          $idx = $chainN - 1;

          $from = checkKeyOrFalse($currRaceTrack, $dF, $rep, $contender, $idx);
          $contenderText = $contender + 1;
          $event = 'Bevestiging aangepast: ' . $sampleInfo['barcode'] . ', analyse: ' . $assayInfo['name'] . ', test:' . $mediaInfo['short_name'] . ' verdunning: ' . $dF . ', replica: ' . $rep . ', kolonie:' . $contenderText ;
          upa('changeTracker', 'changed', array(14, $saidInfo['project'], $sampleInfo['id'], $said, $event, $from, $value), False);

          $currRaceTrack[$dF][$rep][$contender][$idx] = $value;

          //reset downstream if needed
          if($disposition != '?' && $value <> $disposition){
            $raceTrackLength = count($currRaceTrack[$dF][$rep][$contender]);
            for($i = $idx + 1; $i < $raceTrackLength; $i++){
              $currRaceTrack[$dF][$rep][$contender][$i] = NULL;
            }
          }


          $newRaceTrack = json_encode($currRaceTrack, JSON_FORCE_OBJECT);
          $this->Confirmation->racetrack = $newRaceTrack;
        }


        //save to metadata
        if($field != 'contender'){
          
          $currMetaData = json_decode($data['metadata'], JSON_FORCE_OBJECT);

          //check if df and rep are set
          $idx = $chainN - 1;
          $from = checkKeyOrFalse($currMetaData, $dF, $rep, $idx, $field);
          $currMetaData[$dF][$rep][$idx][$field] = $value;
          $currMetaData[$dF][$rep][$idx][$field . '_user'] = getUserId();

          if($disposition != 'tht'){
            $event = 'Bevestiging aangepast: ' . $sampleInfo['barcode'] . ', analyse: ' . $assayInfo['name'] . ', test:' . $mediaInfo['short_name'] . ' verdunning: ' . $dF . ', replica: ' . $rep . ', veld:' . $field;
            upa('changeTracker', 'changed', array(15, $saidInfo['project'], $sampleInfo['id'], $said, $event, $from, $value), False);
          }

          $newMetaData = json_encode($currMetaData, JSON_FORCE_OBJECT);          

          $this->Confirmation->metadata = $newMetaData;
        }


        $this->Confirmation->save();


    }

    function removeConfirmationBySA($said){
        $this->render = 0;
        $this->Confirmation->where('said', $said);
        $results = $this->Confirmation->search();
        if(!empty($results)){
            $this->Confirmation->deepFreed();
            $this->Confirmation->id = $results['0']['id'];
            $this->Confirmation->remove();
        }
    }

    function getConfirmationName(){

    }

}
