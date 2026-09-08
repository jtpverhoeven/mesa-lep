<?PHP

class calculation {

    //master query
    protected $thisSAID;

    //input
    protected $externalVariables = array();

    //base information
    protected $sample;
    protected $assayBase;
    protected $profile;
    protected $profile_group;
    protected $assay;
    protected $originalAssayId;
    protected $roamingId;
    protected $isMeta;
    protected $readyFailSignal = False;

    protected $typeBase;
    protected $endResultDrivers = array();

    //sample attached information
    protected $profileData;
    protected $sampleReferenceValues;
    protected $min;
    protected $max;

    protected $reportIn;
    protected $reportInHumanReadable = 'kve';
    protected $resultHide = array();
    protected $outputEn = array();
    protected $disposition = array();

    //process flags
    protected $usesDillutions; 
    protected $usesReplicates;
    protected $usesConfirmation;
    protected $confRequested;
    protected $confCalcDone;

    //confirmation
    protected $confirmationType;
    protected $confirmationMethods = array();
    protected $confirmationData = array();
    protected $confirmationMetaData = array();
    protected $confirmationDone;
    protected $mediaInfo = array();
    protected $confMediaTht = array();
    protected $confirmationRatios = array();
    protected $supportMediaTht = array();
    protected $supportMedia = array();
    protected $enabledMedia = array();
    protected $confDeniedByCalculation = False;

    //plate results
    protected $results = array();
    protected $resultsReplicate = array();
    protected $lowestDilution = False;
    protected $highestDilution = False;

    //meta
    protected $metaResults = array();
    protected $askForMetaConfirmation = False;
    protected $askForMetaParentConfirmation = False;
    protected $metaIsReady = false;
    protected $isChildOfMeta = false;
    protected $metaParent = false;

    //messageBag
    protected $messageBag = array();

    //output
    protected $output = array();
    protected $verbose = 0;
    protected $authorisedSample = False;
    protected $outputFields = array();

    //internal log
    protected $log = array();

    //df
    protected $duplicatePermutation = array();


    public function __construct($said, $authorised, $external = False, $inquiry = False, $sample = false){

        $this->verbose = script_verbosity;        
        $this->authorisedSample = $authorised;

        if($inquiry == True){
            //$this->_log('Inquiry was True');
            $this->outputFields = $this->_reportOutput();
        } else{
            $this->thisSAID = $said;
            $this->_getBaseInfo();
            $this->_getAssayInfo();
            $this->_loadAssociatedProfile();
            $this->_loadEndResultDrivers();

            if($this->isMeta == True){
                $this->_getMetaResults($sample);  //said is now SAMPLE
            } else{
                $this->_findMetaParent();
                $this->_getResults();
            }

            $this->output = array('output' => array(), 'messageBag'=> array());
            if($external != False && is_array($external)){
                $this->_loadInExternalVars($external);
            }
        }
    }

    private function _loadEndResultDrivers(){
        $assayFields = upa('assayTypeFields', 'fetchFields', array($this->typeBase));
        foreach($assayFields as $assayField){

          if($assayField['endresults_driver'] == '1'){
              $this->endResultDrivers[$assayField['name']] = True;
          }

        }
    }

    private function _loadInExternalVars($external){
        foreach($external as $var=>$val){
            $this->externalVariables[$var] = $val;
        }
    }

    public function reportOutput(){
        return $this->outputFields;
    }

    private function _getBaseInfo(){
        
        $sa = new SampleAnalysis();
        $sa->where('id', $this->thisSAID);
        $sa->select(['sample','assay_base','profile','assay','profile_group','roaming_id','conf_requested']);
        $infoChannel = $sa->first();
        
        $this->sample = $infoChannel['sample'];
        $this->assayBase = $infoChannel['assay_base'];
        $this->profile = $infoChannel['profile'];
        $this->assay = $infoChannel['assay'];
        $this->profile_group = $infoChannel['profile_group'];        
        $this->roamingId = $infoChannel['roaming_id'];
        $this->confRequested = $infoChannel['conf_requested'];
        
    }

    private function _getAssayInfo(){
        $assayInfo  = upa('assays', 'fetchSingle', array($this->assayBase));
        
        $this->confirmationType = $assayInfo['confirmation_type'];
        $this->typeBase = $assayInfo['type_base'];
        $this->originalAssayId = $assayInfo['original_id'];

        $customArray = json_decode($assayInfo['custom_fields'], True);



        if(array_key_exists('resultin', $customArray) && !empty($customArray['resultin'])){
          $this->reportInHumanReadable = $customArray['resultin'];
        }

        $allMedia = upa('media', 'fetchAllNoParams', array(), False);

        
        //$confirmationArray =
        $this->confirmationMethods = json_decode($assayInfo['confirmation_script'], True);
        $this->supportMedia = json_decode($assayInfo['confirmation_support'], True);


        if(!is_array($this->supportMedia)){
          $this->supportMedia = array();
        }
        
        $confMedia = array_column($this->confirmationMethods, 'mediaId');
        array_merge( $confMedia, array_column($this->supportMediaTht, 'mediaId'));        
        $bulkDates = upa('assuranceForms', 'bulkCheckExpiryDate', [$this->sample, $this->thisSAID, $confMedia], False);                
        
        foreach($this->confirmationMethods as $n =>  $thisConfMethod){          
          $this->mediaInfo[$thisConfMethod['mediaId']] = checkKeyOrEmpty($allMedia, $thisConfMethod['mediaId']);
          $this->confMediaTht[$thisConfMethod['mediaId']] = checkKeyOrEmpty($bulkDates, $thisConfMethod['mediaId']);      
        }
                
        foreach($this->supportMedia as $n => $thisSupMethod){          
          $this->mediaInfo[$thisSupMethod['mediaId']] = checkKeyOrEmpty($allMedia, $thisSupMethod['mediaId']); 
          $this->supportMediaTht[$thisSupMethod['mediaId']] = checkKeyOrEmpty($bulkDates, $thisSupMethod['mediaId']);           
        }
        
        if($assayInfo['confirmation'] == '1'){
            $this->usesConfirmation = True;
            $this->_loadConfirmationData();
        } else{
            $this->usesConfirmation = False;
            $this->confirmationData = array();
        }

        $this->max = $assayInfo['max_count'];
        $this->min = $assayInfo['min_count'];
        $this->usesReplicates = $assayInfo['replicates'];
        $this->usesDillutions = $assayInfo['dillution'];

        if($assayInfo['type'] == '4'){
            $this->isMeta = True;
        } else{
            $this->isMeta = False;
        }
    }

    private function _loadAssociatedProfile(){


        if($this->roamingId !== NULL && $this->roamingId <> 0){
            $fetchedProfile = upa('roamingAnalysis', 'fetchSettings', array($this->roamingId));
        } else {
            //$fetchedProfile = upa('assayProfiles', 'fetchByProfileAndAssay', array($this->profile, $this->assayBase));
            $fetchedProfile = upa('assayProfiles', 'fetchProfileById', array($this->assay));
        }

        if(array_key_exists(0, $fetchedProfile)){
            $this->profileData = $fetchedProfile['0'];
        } else{
            $this->profileData = $fetchedProfile;
        }

        if(array_key_exists('reference', $this->profileData)){
            $this->sampleReferenceValues = json_decode($this->profileData['reference'], True);
        } else{
            $this->sampleReferenceValues = array();
        }

    }

    protected function _loadConfirmationData($inject = False){

        if($inject == False){
            $confLine = upa('confirmations', 'fetchLine', array($this->thisSAID));
        } else{
            $confLine = upa('confirmations', 'fetchLine', array($inject));
        }

        //$this->_log('Confline', $confLine);
        $this->confirmationDone = $confLine['isReady'];
        $this->confirmationData = json_decode($confLine['racetrack'], True);
        //$confirmationData =  json_decode($confLine['data'], True);
        $this->confirmationMetaData = json_decode($confLine['metadata'], JSON_FORCE_OBJECT);
        $this->enabledMedia = json_decode($confLine['in_use'], JSON_FORCE_OBJECT);
        $this->enabledMedia = checkArrayOrEmpty($this->enabledMedia);

        foreach($this->confirmationMetaData as $df => $rep){
          foreach($rep as $thisRep => $repData){
            $ratio = checkKeyOrFalse($repData, 'ratio');
            $this->confirmationRatios[$df][$thisRep] = $ratio;
          }
        }

        //$this->confirmationData = checkArrayOrEmpty($confirmationData);
    }

    private function _getMetaResults($sample){
        $results = upa('results', 'getSampleResults', array($sample, True), false);
        $resultsStrat = array();

        //transform sample results to per assay_base_original id, and then dillutions
        //to be able to acces it from outside

        foreach($results as $thisSaid => $saidResults){
            $thisSaid = upa('sampleAnalysis', 'fetch', array($thisSaid), false); //TODO: refactor
            $resultsStrat[$thisSaid['original_assay_base']] = array();

            foreach($saidResults as $saidResult){

                $thisDf = $saidResult['df'];
                if(!array_key_exists($thisDf, $resultsStrat[$thisSaid['original_assay_base']])){
                    $resultsStrat[$thisSaid['original_assay_base']][$thisDf] = array();
                }

                $resultFields = json_decode($saidResult['data'], JSON_FORCE_OBJECT);
                array_push($resultsStrat[$thisSaid['original_assay_base']][$thisDf], $resultFields);
            }
        }

    $this->metaResults = $resultsStrat;

    }

    protected function _getResults(){

        //if($inject == False){
            $results = upa('results', 'getTestResults', array($this->thisSAID, TRUE), False);
        //} else{
      //      $results = upa('results', 'getTestResults', array($inject, TRUE), False);
      //  }

        foreach($results as $rl){

            //set dillution factors here
            if($this->lowestDilution == False){
                $this->lowestDilution = $rl['df'];
            }
            $this->highestDilution = $rl['df'];

            $data = json_decode($rl['data'], true);
            foreach($data as $field => $fieldValue){
                if($rl['rep'] == 0){
                    $this->results[$rl['df']][$field] = $fieldValue;
                } else{
                    $this->resultsReplicate[$rl['df']][$rl['rep']][$field] = $fieldValue;
                }
            }
        }
    }

    protected function _returnResults($inject = False){

        $results = upa('results', 'getTestResults', array($inject, TRUE), False);
        $retResults = array();

        foreach($results as $rl){

            //set dillution factors here
            $data = json_decode($rl['data'], true);
            foreach($data as $field => $fieldValue){
                if($rl['rep'] == 0){
                    $retResults['results'][$rl['df']][$field] = $fieldValue;
                } else{
                    $retResults['resultsReplicate'][$rl['df']][$rl['rep']][$field] = $fieldValue;
                }
            }
        }

        return $retResults;
    }

    protected function _perPlateTwoPlateCalculation($plates, $doSigRounding = False){

        //$this->_log('_perPlateTwoPlateCalculation');
        //$this->_log('$doSigRounding', $doSigRounding);
        $low = False;
        $nSum = 0;
        $nonZeroPlates = 0;

        foreach($plates as $dF => $data){

            $thisN = $data[$this->reportIn];

            $confFactor = checkKeyOrFalse($this->confirmationRatios, $dF, 0);
            if($confFactor === False){
              $confFactor = 1;
            }

            $thisSum = $thisN * $confFactor;

            //$this->_log('this sum for df: ' . $dF, $thisSum);

            $thisSumRounded = round($thisSum);

            //$this->_log('$thisSumRounded for df: ' . $dF, $thisSumRounded);
            $nSum = $nSum + $thisSumRounded;

            if($thisSumRounded > 0){
              $nonZeroPlates += 1;
            }

            if($low == False){
                $low = $dF;
            }
        }

        //$this->_log('$nSum', $nSum);
        //$this->_log('$low', $low);

        if($nonZeroPlates < 2){
          //$this->_log('Less then 2 plates found above zero after confirmationRatio adjust');
          $n = $nSum / ( 1 * 1 * $low );
        } else{
          $n = $nSum / ( 1 * 1.1 * $low );
        }


        //$this->_log('n', $n);

        $autoAdjust = True;
        if($this->lowestDilution == '1'){
          //$this->_log('AutoAdjust tripped');
          $autoAdjust = False;
        }

        $nSig = format_number_significant_figures($n, 2,$autoAdjust);

        if($doSigRounding){
            return $nSig;
        } else{
            return $n;
        }
    }




    protected function _genericTwoPlateCalculation($plates, $doSigRounding = True, $ratio = False){

        //$this->_log('_genericTwoPlateCalculation');
        $low = False;
        $nSum = 0;
        foreach($plates as $dF => $data){
            $nSum = $nSum + $data[$this->reportIn];
            if($low == False){
                $low = $dF;
            }
        }
        
        $n = $nSum / ( 1 * 1.1 * $low );

        if($ratio !== False){
            $n = $n * $ratio;
        }

        $autoAdjust = True;
        if($this->lowestDilution == '1'){
          $autoAdjust = False;
        }

        $nSig = format_number_significant_figures($n, 2,$autoAdjust);

        if($doSigRounding){
            return $nSig;
        } else{
            return $n;
        }
    }

    protected function _genericOnePlateCalculation($plates, $doSigRounding = True, $ratio = False){

        //$this->_log('_genericOnePlateCalculation');
        $low = False;
        $nSum = 0;

        //$this->_log('Ratio', $ratio);

        foreach($plates as $dF => $data){

            $thisN =  $data[$this->reportIn];

            if(is_array($ratio)){
              //if(array_key_exists($dF, $ratio)){
                  $useRatio = $ratio[$dF][0];
            //  } else{
            //    $useRatio = 1;
          //    }
              $thisN = $thisN * $useRatio;
            }

            $nSum = $nSum + $thisN;
            if($low == False){
                $low = $dF;
            }
        }


        //$this->_log(' kve som:', $nSum);
        //$this->_log(' laagste verdunning:', $low);

        $div = ( 1 * 1 * $low );
        $n = $nSum / $div;

        if(!is_array($ratio)){
          if($ratio !== False){
              $n = $n * $ratio;
          }
        }


        $autoAdjust = True;

        if($this->lowestDilution == '1'){
          $autoAdjust = False;
        }

        //$this->_log('Auto adjust:', $autoAdjust);
        //$this->_log('berekend resultaat' , $n);

        $nSig = format_number_significant_figures($n, 2,$autoAdjust);
        //$this->_log('berekend nSig resultaat ' , $nSig);

        //$this->_log('do sig rounding', $doSigRounding);

        if($doSigRounding){
            return $nSig;
        } else{
            return $n;
        }
    }

    protected function _log($msg){
      // if($this->verbose == 1){
      //   $args = func_get_args();
      //   for ($i = 0; $i < count($args); $i++) {
      //     cphp($args[$i]);
      //   }
      // }

      $args = func_get_args();
      for ($i = 0; $i < count($args); $i++) {        
        array_push($this->log, $args[$i]);
      }
    }

    public function renderLog(){
      
      $log = '';
      foreach($this->log as $logMsg)
      {         
        if(is_array($logMsg)){
          $log .= generateHTML('calclog', ['log' => parray($logMsg, true)]);          
        }

        else{ 
          $log .=  generateHTML('calclog', ['log' => $logMsg]);          
        }
        
      }

      return $log;
    }

    public function renderBaseClassParams()
    {
      
    }


    protected function _attachMessage($msg){
        array_push($this->messageBag, $msg);
    }

    private function _fetchVetoResults(){

        foreach($this->output['output'] as $outputParam => $outputValue){
            $this->output['vetoReasons'][$this->thisSAID][$outputParam] = '';
            $vetoResult = upa('vetoResults', 'checkVeto', array($this->thisSAID, $outputParam, True), False);
            if(is_array($vetoResult) && $vetoResult['result'] != False){
                $this->output['vetoOverWrite'][$outputParam] = $this->output['output'][$outputParam];
                $this->output['output'][$outputParam] = $vetoResult['result'];
                $this->output['outputEn'][$outputParam] = $vetoResult['result'];

                //1: Does the Veto result have it's own packed dispo, if so, set
                if($vetoResult['disposition'] !== null)
                {
                  $this->output['disposition'][$outputParam] = $vetoResult['disposition'];
                //  $this->output['disposition'] =  $vetoResult['disposition'];
                }

                //2: If not, does the veto result contain text, if so, set it to ZERO. 
                //    For example: niet te bepalen, n.t.b. etc. 
                else 
                {
                  if(preg_match("/[a-z]/i",  $vetoResult['result'])){
                    $this->output['disposition'][$outputParam] = '-';    
                  }
                }                                
                
                //if the vetoresult has text in it, make the dispo black                                
                $this->output['vetoReasons'][$this->thisSAID][$outputParam] = $vetoResult['reason'];
            }
        }
    }

    protected function _dispatch(){

        $this->_log('Dispatching result');

        $this->_log('Ready fail signal:' . $this->readyFailSignal);

        $this->output['messageBag'] = $this->messageBag;
        $this->output['authorised'] = $this->authorisedSample;
        $this->output['askForMetaConfirmation'] = $this->askForMetaConfirmation;
        $this->output['askForMetaParentConfirmation'] = $this->askForMetaParentConfirmation;
        $this->output['metaParent'] = $this->metaParent;

        //if($this->confirmationType == '0'){
          //$this->output['confirmation']['enabled'] = $this->usesConfirmation;
        //}

        $this->output['confirmation']['enabled'] = $this->usesConfirmation;
        $this->output['confirmation']['type'] = $this->confirmationType;
        $this->output['confirmation']['requested'] =   $this->confRequested;
        $this->output['reportIn'] = $this->reportIn;
        //$this->output['outputEn'] = $this->outputEn;
        $this->output['disposition'] = $this->disposition;

        $this->_fetchVetoResults();


        if($this->readyFailSignal == True){
          $this->_log('Ready-fail signal set to true, setting ready flag to zero for SAID:', $this->thisSAID);
          $this->output['isReady'] = False;
          upa('sampleAnalysis', 'setReadyFlag', array(0, $this->thisSAID), False);
        } else{
          $this->_log('Reporting this analysis results as ready');
          $this->output['isReady'] = $this->_isReady();
        }


        //add main reporter info
        $this->output['resultMask'] = array($this->reportIn => $this->reportInHumanReadable);
        $this->output['resultHide'] = $this->resultHide;
        

        return $this->output;
    }

    /**
     * Check is all fields are NULL (aka: empty)
     * @return bool
     */
    protected function _isAllNull(){
        $allNull = True;
        foreach($this->results as $dF => $dFFields ){
            foreach($dFFields as $count){  if($count != ''){ $allNull = False; }}
        }
        return $allNull;
    }

    /*
    * Check if all fields  are filled in?
    * Is confirmation requested, check this out
    */

    protected function _isReady(){

        //different rules for meta analysis
        if($this->isMeta == True){

            //rely on manually set isready flag for meta analysis
            if($this->metaIsReady == True){
              upa('sampleAnalysis', 'setReadyFlag', array(1, $this->thisSAID), False);
              return True;
            } else{
              upa('sampleAnalysis', 'setReadyFlag', array(0, $this->thisSAID), False);
              return False;
            }

            return;
        }
        //invoke said meta trigger
        upa('sampleAnalysis', 'triggerProfileMetas', array($this->profile_group), False);

        $allFinished = True;
        foreach($this->results as $dF => $dFFields ){

            foreach($dFFields as $fieldName => $count){
                //filter out non end result drivers first
               if(array_key_exists($fieldName, $this->endResultDrivers)){
                 if($count == ''){ $allFinished = False; }}

                 //check replicate of this is done?
                 if($this->usesReplicates == 1){
                   //check if dF exists
                   if(array_key_exists($dF, $this->resultsReplicate)){
                    foreach($this->resultsReplicate[$dF] as $repNo => $repFields) {
                         if($repFields[$fieldName] == ''){ $allFinished = False; }
                    }
                   }
                 }
               }
        }

        //check before going on to conf methods
        if($allFinished == False){
            upa('sampleAnalysis', 'setReadyFlag', array(0, $this->thisSAID), False);
            return False;
        }



        //if everything is zero or - or NVT. this sample should  be ready
        $allZeroes = True;
        foreach($this->results as $dF => $dFFields ){
            foreach($dFFields as $fieldName => $count){

                if(array_key_exists($fieldName, $this->endResultDrivers)){
                  if($count == '0' || $count == '-' || $count == '<' || $count == 'NVT'){
                      //welp!
                  } else{
                      $allZeroes = False;
                  }
                }
              }
        }


        if($allZeroes == True){
            upa('sampleAnalysis', 'setReadyFlag', array(1, $this->thisSAID), False);
            return True;
        }

        //conf method activated?
        if($this->usesConfirmation == True){

            if($this->confRequested == 1 || $this->confRequested == 0 ){
              //  if($this->confCalcDone == True || $this->_confirmationsDone() == True){
              if($this->confCalcDone == True || $this->_confirmationsDone() == True){
                    //conf was done,all done
                    upa('sampleAnalysis', 'setReadyFlag', array(1, $this->thisSAID), False);
                    return True;
                } else{
                    //conf was not done, not done yet
                    upa('sampleAnalysis', 'setReadyFlag', array(0, $this->thisSAID), False);
                    return False;
                }

            } else{
                //conf was not requsted, all done
                upa('sampleAnalysis', 'setReadyFlag', array(1, $this->thisSAID), False);
                return True;
            }
        } else{
            //is not all null, and no conf needed, all done!
            upa('sampleAnalysis', 'setReadyFlag', array(1, $this->thisSAID), False);
            return True;
        }
    }

    protected function _confirmationsDone(){

        $confDone = True;

        //return true if conf disabled by user
        if($this->confRequested == '2' || $this->confRequested == '0'){
          return True;
        } else{
          if($this->confirmationDone == 0){
            $confDone = False;
          }
        }
    

        return $confDone;
      }


      protected function _sanityCheckForNonDuplicates($plates, $hasNameTags = True){
      
        //trim of larger than signs
        foreach($plates as $idx => $plate){          
          
          $thisKve = ($hasNameTags) ?  $plate[$this->reportIn] : $plate;                               
          
          if($thisKve === '>'){
            unset($plates[$idx]);
          }
        }

        

        $numPlates = count($plates);
        $dfs = array_keys($plates);
        $reachedZero = False;
        $reachedEnd = False;
        $passed = True;
        $pCutoff = 0.01;

        if($numPlates == 1){
          //there is only one plate here, i guess ok!
          $this->_log('Sanity check  between plates not performed, there was only one plate left. ');
          return True;
        }



        for($i = 0; $i < $numPlates; $i++ ){
          //skip first Plate

          if($i == 0){
            continue;
          }




          $thisKve = ($hasNameTags) ? $plates[$dfs[$i]][$this->reportIn] : (int)$plates[$dfs[$i]];

          //hit a zero in the range, check this one and then quit
          if($thisKve == 0){
            $reachedZero = True;
          }

          //check also if this is the last plate, if so break after checking this
          if(!array_key_exists($i+1, $dfs)){
            $reachedEnd = true;
          }

          //$D6 = $plates[$dfs[$i-1]][$this->reportIn];
          //$D7 = $plates[$dfs[$i]][$this->reportIn];
          
          $D6 =  ($hasNameTags) ? $plates[$dfs[$i-1]][$this->reportIn] : (int)$plates[$dfs[$i-1]];
          $D7 =  ($hasNameTags) ? $plates[$dfs[$i]][$this->reportIn] : (int)$plates[$dfs[$i]];


          if($reachedZero == True){
            $chi = (2*(($D6*log($D6/(10*($D6+$D7)/11)))));
          } else{
            $chi = (2*(($D6*log($D6/(10*($D6+$D7)/11)))+($D7*log($D7/(1*($D6+$D7)/11)))));
          }

          
          $pChi = ChiSq($chi,1);          

          
          
          if($pChi < $pCutoff){
            $this->_log('P chi for ' . $D6 . ' vs ' . $D7 . ' is ' . $pChi . '. This is an unacceptable result outside of the P cutoff of: ' . $pCutoff);
            $passed = False;
            break;
          }

          else
          {
            $this->_log('P chi for ' . $D6 . ' vs ' . $D7 . ' is ' . $pChi . '. Which is within the limit of P cutoff:' . $pCutoff);  
          }
          

          //if this plate set passed, but its a zero now, skip the nxt plate.
          //will be zero to, as we already asserted that the dillution curve is sane.
          if($reachedZero == True || $reachedEnd == True){
            break;
          }
        }
        return $passed;
      }

      protected function _sanityCheckForDuplicatesDispatch($counts){

        if(count($counts) <= 2){
            return $this->_sanityCheckForDuplicates($counts);
        } else{
          //permutation
          $this->pc_permute($counts);
          foreach($this->duplicatePermutation as $permutation){
            $thisPerm = $this->_sanityCheckForDuplicates($permutation);
            if($thisPerm == False){
              return False;
            }
          }
          return True;
        }
      }

      protected function _sanityCheckForDuplicates($counts){

        $count1 = checkKeyOrFalse($counts, 0);
        $count2 = checkKeyOrFalse($counts, 1);
        $passed = True;
        $pCutoff = 0.01;

        if($count1 === False || $count2 === False){
          return $passed;
        }

        if($count1 == '>' || $count2 == '>'){
          return $passed;
        }

        $J6 = max($count1, $count2);
        $K6 = min($count1, $count2);

        if(min($count1, $count2) == 0){
          $chi = (2*(($J6*(log($J6/(($J6+$K6)/2))))));
        } else{
          $chi = (2*(($J6*(log($J6/(($J6+$K6)/2))))+($K6*(log($K6/(($J6+$K6)/2))))));
        }

        $pChi = ChiSq($chi,1);        

        if($pChi < $pCutoff)
        {
          $this->_log('P chi for ' . $J6 . ' vs ' . $K6 . ' is ' . $pChi . '. This is an unacceptable result outside of the P cutoff of: ' . $pCutoff);
          $passed = False;
        }

        else{
          $this->_log('P chi for ' . $J6 . ' vs ' . $K6 . ' is ' . $pChi . '. Which is within the limit of P cutoff of: ' . $pCutoff);
        }

        return $passed;
      }



        /* Find the meta analysis within this profile */
        protected function _findMetaParent(){
           if($this->isMeta == False){
             $metaParent = upa('sampleAnalysis', 'findMetaParent', array($this->profile_group), False);
             if($metaParent != False){
               $this->metaParent = $metaParent;
             }
             return $metaParent;
           }
        }

        protected function _cancelFailedOnSanity(){
          $this->output['output'][$this->reportIn] = 'Fout resultaat gevonden';
          $this->readyFailSignal = True;
          return $this->_dispatch();
        }

        protected function _preliminaryCheck(){


          if($this->usesReplicates == False){

              $previousValue = False;
              krsort($this->results);

              foreach($this->results as $df => $data){
                  $n = $data[$this->reportIn];


                  if($previousValue === False){
                    $previousValue = $n;
                    continue;
                  }

                  if(is_numeric($previousValue) && $n == '>' ){
                   return False;
                  }

                  if($previousValue == '>' && $n == '0' ){
                   return False;
                  }

                  if(max($n, $previousValue) > 0){
                    if($n >= $previousValue || $n == '>'){
                        return False;
                    }
                  }

                  $previousValue = $n;
              }
          }
          return True;
        }


        protected function _isDone(){
            $allDone = True;


            if($this->usesReplicates == True){
              return $this->_isDoneRep();
            } else{
              foreach($this->results as $dF => $dFFields ){
                  foreach($dFFields as $count){  if($count == ''){ $allDone = False; }}
              }
            }

            return $allDone;
        }

        protected function _isDoneRep(){

            $allDone = True;
            foreach($this->results as $dF => $dFFields ){
                foreach($dFFields as $count){
                    if($count == ''){
                      $allDone = False;
                    }

                    $repExists = checkArrayOrEmpty($this->resultsReplicate, $dF);
                    if($repExists == True){
                      foreach($this->resultsReplicate[$dF] as $dfRep => $dfRepData ){
                      $n = $dfRepData[$this->reportIn];
                        if($n == ''){
                          $allDone = False;
                        }
                      }
                    }
                  }
            }
            return $allDone;
        }

        function pc_permute($items, $perms = array( )) {
            if (empty($items)) {
                $return = array($perms);
            }  else {
                $return = array();
                for ($i = count($items) - 1; $i >= 0; --$i) {
                     $newitems = $items;
                     $newperms = $perms;
                 list($foo) = array_splice($newitems, $i, 1);
                     array_unshift($newperms, $foo);
                     $return = array_merge($return, $this->pc_permute($newitems, $newperms));
                 }
            }
            $this->duplicatePermutation = $return;
            return $return;
        }


        public function internals()
        {

          $internals = [

            

          ];

          return $internals;
        }

    /**
    * areResultDone(), check if results of all fields are in
    *
    * @return boolean
    */
    protected function areResultsDone()
    {
        if(!$this->_isDone())
        {
          $this->_log('Aborting calculation, because not all results are ready.');
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          $this->readyFailSignal = True;
          return False;
        }
        
        return True;
    }

    protected function confirmationDataReady()
    {
        if(filter_var($this->confirmationDone, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) == false)
        {                  
          $this->_log('Can not continue, confirmation data is not ready.');         
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          $this->readyFailSignal = True;
          return False;
        }
        
        return True; 
    }

    protected function confirmationChoicePending()
   {
      return ($this->usesConfirmation == True && $this->confRequested == '0' ) ? True  : False;
   }

   protected function doesNotNeedConfirmation()
   {
      return  ($this->usesConfirmation === false ||  $this->confRequested == '2' ) ? True: False; 
   }



}
