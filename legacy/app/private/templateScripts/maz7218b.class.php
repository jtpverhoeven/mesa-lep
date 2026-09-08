<?PHP


class maz7218 extends calculation{


    protected $reportIn = 'kve';
    protected $endResult = NULL;
    protected $prepend = NULL;
    protected $append = NULL;

    protected $indicative = False;
    protected $confirmed = False;

    protected function _reportOutput(){
       return array('kve' => 'kve');
    }

    public function provide(){

      $this->_log('******');
      $this->_log('maz7218, provide(), said:', $this->thisSAID);

      $prelimOk = $this->preliminaryChecks(False);
      $this->_log('prelim done check status', $prelimOk);

      if($prelimOk == False){
        $this->_log('prelim ok == false on line 28');
        $this->readyFailSignal = True;
        return $this->_dispatch();
      }
      

      if($this->usesReplicates == 1){
          if(empty($this->resultsReplicate)){   $this->_nonReplicates(); }
          else{ $this->_replicates(); }
      } else{
          $this->_nonReplicates();
      }

      if($this->endResult !== NULL){

        $prelimOk = $this->preliminaryChecks(True);
        $this->_log('prelim done TRUE check status', $prelimOk);

        if($prelimOk == False){
          $this->readyFailSignal = True;
          return $this->_dispatch();
        }


        $nSig = $this->brewEndCount();
        $addendum = $this->append;

        if($this->indicative == True){
            $addendum .= '<sup>*</sup> ';
        }

         if($this->usesConfirmation == True){

            if($this->confRequested == '2'){
               $addendum .= ' <sup>**</sup>';
            }

         }



        //confirmed really means isReady,
        // if($this->confirmed == False){
        //   if($this->usesConfirmation == True){
        //     $addendum .= ' <sup>**</sup>';
        //   }
        // }

        $this->output['output']['kve'] = $this->prepend .  $nSig . $addendum;
      }

      return $this->_dispatch();
    }

    public function brewEndCount(){

      $this->_log('Brew end count, end result was:', $this->endResult);
      //if zero, then make less then MINIMUM

      if($this->endResult == 0)
      {
        $this->prepend = '<';
        $this->disposition[$this->reportIn] = '-';
        $this->indicative = False;
        return $this->_returnLowestPossibleNumber();
      } 
      
      else{

        //$this->lowestDilution
        $autoAdjust = True;

        if($this->lowestDilution == '1'){
          $this->_log('no auto adjust, lowest dillution = 1');
          $autoAdjust = False;
        }

      #  return $this->endResult;
        $rounded = round($this->endResult);
        $this->_log('Rounded end result', $rounded);
        return format_number_significant_figures( $rounded, 2, $autoAdjust);
      }
    }

    //public function __replicates()
    //{
      
    //}

    public function __replicates(){

      $allAboveMax = $this->_isAllAboveMaxReplicate();
      $allZero = $this->_isAllZeroReplicate();

      // if($allAboveMax == True){
      //   cphp('all above max');
      //   $this->estimate = True;
      //   $this->estimateMaxReplicates();
      //   return;
      // }

      if($allZero == True){
        $this->_log('Found all zeroes');
        $this->confirmed = True;
        $this->estimateMinReplicates();
        return;
      }

      //need to check for above calc limits 
      
      $dFResults = reset($this->results); // First Element's Value
      $df = key($this->results); // First Element's Key

      $counts = array();                  
      $counts[0] = $dFResults['kve'];


      foreach($this->resultsReplicate[$df] as $rep => $values){
        $counts[$rep] = $values['kve'];
        //$i++;
      }
   
      $this->_log('Counts', $counts);      

      $sanityResult = $this->_sanityCheckForDuplicatesDispatch($counts);

      if($sanityResult == false){
          $this->_log('Cancel on failed sanity');
          $this->_cancelFailedOnSanity();
          return;
      }

      $aboveMin = $this->_foundAboveMinThresholdRep($counts);
      $inBoundMax = $this->_isMaxIndicativeRep($counts);

      if($aboveMin == false || $inBoundMax == true){
        $this->indicative = True;
        $this->_log('indicative set to true');
      }


      $this->confirmed = $this->confirmationDone;
      $this->_log('Confirmed', $this->confirmed);

      //if($this->confirmationType == 0 || $this->usesConfirmation == False || $this->confRequested  == '2' ){
      if($this->confirmationType == 0 || $this->usesConfirmation == False || $this->confRequested   == '2' || $this->confRequested   == '0' ){
        
        $countSum = 0;

        $this->_log('Confimration type 0, uses conf false, or conf requested 2 ');

        if($this->confRequested   == '2'){
          $ratio = 1;
        } else{
          $ratio = checkKeyOrFalse($this->confirmationRatios, 'global', '0');
        }

        $this->_log('Ratio', $ratio);
        
        //foreach($counts as $rep => $thisCount){          
          //$thisCountAdjusted = $thisCount * $ratio;
          //$thisCountAdjustedRounded = round($thisCountAdjusted);
          //$countSum = $countSum + $thisCountAdjustedRounded;
        //}


        $countSum =  array_sum($counts);
        $this->_log('Countsum set', $countSum);
        $divisor = 1 * count($counts) * $df;

        $this->_log('$divisor', $divisor);
        $this->_log('$countSum', $countSum);

        $result = $countSum / $divisor;        

        //$countSum = array_sum($counts);
        //$divisor = 1 * count($counts) * $df;
        //$result = $countSum / $divisor;

      }

//      if($this->confirmationType == 1 &&  $this->confRequested   == '1' ){

      if($this->confirmationType == 1  && ($this->confRequested != '2'  && $this->confRequested != '0' ) && $this->usesConfirmation == True){

        $this->_log('Confimration type 1 and conf requested 1 ');
        $this->_log('conf ratios', $this->confirmationRatios);
        $countSum = 0;
        $this->_log('counts', $counts);

        foreach($counts as $rep => $thisCount){
          $ratio = checkKeyOrFalse($this->confirmationRatios, $df, $rep);

          $thisCountAdjusted = $thisCount * $ratio;
          $thisCountAdjustedRounded = round($thisCountAdjusted);
          $countSum = $countSum + $thisCountAdjustedRounded;
        }


        $divisor = 1 * count($counts) * $df;

        $this->_log('$divisor', $divisor);
        $this->_log('$countSum', $countSum);

        $result = $countSum / $divisor;
      }

      $this->_log('replicates setting end result of ' . $result);
      $this->endResult = $result;
    }


    public function _nonReplicates(){

      //special cases * All zero  * All Above Max
      $this->_log('Non replicates function');
      $allAboveMax = $this->_isAllAboveMax();

      $this->_log('All above max', $allAboveMax);
      $allZero = $this->_isAllZero();

      if($allAboveMax == True){
        $this->estimateMaxNonReplicates();
        return;
      }

      if($allZero == True){
        $this->confirmed = True;
        $this->estimateMinNonReplicates();
        return;
      }

      //check if dillution curve makes sense on a gross scale
      //before proceeding to the real calcuations
      $prelim = $this->_preliminaryCheck();
      if($prelim == False){
        $this->_cancelFailedOnSanity();
        return;
      }

      $sanityResult = $this->_sanityCheckForNonDuplicates($this->results);

      if($sanityResult == false){
          $this->_cancelFailedOnSanity();
          return;
      }

      $keptPlates = $this->_refinePlateCollection($this->results,3);

      $this->_log('Kept plates:', $keptPlates);

      if($this->_foundAboveMinThreshold($keptPlates) == False){
        $this->indicative = True;
      }

      $numberOfPlates = count($keptPlates);
      $ratio = 1;

      if($this->usesConfirmation == True){
        $confirmation = $this->_returnConfirmationStatusAndFactor($keptPlates);
        if($confirmation['confirmed'] == False){
          $this->confirmed = False;
        } else {
          $this->confirmed = True;
          //$ratio = $confirmation['confirmationFactor'];
        }
      }

      $this->_log('$this->confirmationType',$this->confirmationType);
      $this->_log('$this->usesConfirmation',$this->usesConfirmation);
      $this->_log('$this->confRequested',$this->confRequested);

      //global confirmation, or calc for NO calculation
      if($this->confirmationType == 0 || $this->usesConfirmation == False || $this->confRequested   == '2' || $this->confRequested   == '0' ){

        $this->_log('ConfType 0 or UseConf False or Conf requested 2');

        if($this->confRequested  == '2'){
          $ratio = 1;
        } else{
          $ratio = checkKeyOrFalse($this->confirmationRatios, 'global', '0');
        }

        if($numberOfPlates < 2){
            $this->_log('Number of plates < 2 ');
            #18/08/2017
            $result =  $this->_genericOnePlateCalculation($keptPlates, False, $ratio);
        } else{
            $this->_log('Number of plates => 2 ');
            $result =  $this->_genericTwoPlateCalculation($keptPlates, False, $ratio);
        }
      }


      if($this->confirmationType == 1  && ($this->confRequested != '2'  && $this->confRequested != '0' ) && $this->usesConfirmation == True){

        $this->_log('ConfType 1 and conf Requested NOT 2, and usesConfirmation True');

        $result = NULL;
        //$ratio = checkKeyOrFalse($this->confirmationRatios, 'global', '0');

        if($numberOfPlates < 2){
            $result =  $this->_genericOnePlateCalculation($keptPlates, False, $this->confirmationRatios);
        } else{
            $result =  $this->_perPlateTwoPlateCalculation($keptPlates, False, $this->confirmationRatios);
        }

      }

      //send end result
      $this->_log('Eind resultaat nu: ', $result);
      $this->endResult = $result;
    }

    private function _refinePlateCollection($plates, $retain = 2){
        $kept = array();
        foreach($plates as $df => $data){
            $n = $data[$this->reportIn];
            $nPlates = count($kept);
            if($n <= $this->max && $n != '>' && $n > 0 && $nPlates < $retain ){
                $kept[$df] = $data;
            }
        }
        return $kept;
    }

    public function estimateMaxNonReplicates(){

        $lastDillution = end($this->results);
        $lastDillutionResult = $lastDillution[$this->reportIn];
        $this->_log('$lastDillutionResult:', $lastDillutionResult);

        if($lastDillutionResult == '>'){
          $this->_log('Lowest dillution result was still greater than max, falling back on max value:', $this->max);
          $lastDillutionResult = $this->max;
        }

        #$n = $lastDillution[$this->reportIn]  / ( 1 *  $this->highestDilution );
        $n = $lastDillutionResult / ( 1 *  $this->highestDilution );
        $this->_log('$this->highestDilution', $this->highestDilution);

        $this->_log('Estimated max, non replicated, N:', $n);

        $this->endResult = $n;
        $this->indicative = True;

        if($this->usesConfirmation == True){
          $this->_log('Uses confirmation');
          $confStatus = $this->_returnConfirmationStatusAndFactor();
          $this->_log($confStatus);
          if($confStatus['confirmed'] == False){
            $this->_log('$confStatus[confirmed] == False');
            $this->confirmed = False;
          } else {
            $this->confirmed = True;
            // get conf factor
            $lastDillutionConfFactor = checkKeyOrFalse($this->confirmationRatios, $this->highestDilution, 0);
            //$this->_log('$lastDillutionConfFactor', $lastDillutionConfFactor);
            //cphp($this->confirmationRatios, )
            #$n = $n * $confStatus['confirmationFactor'];

            $n = $n * $lastDillutionConfFactor;
            $this->endResult = $n;
            if($n == 0){
                $this->indicative = False;
                $this->prepend = '<';
                $this->endResult = $this->_returnHighestPossibleNumber();
                $this->disposition[$this->reportIn] = '-';                
            }
          }
        }
    }

    public function estimateMaxReplicates(){
        $lastDillution = end($this->results);

        //average the maximums
        $counts = array();

        $counts[0] =  $lastDillution[$this->reportIn];
        $countI = 1;
        $replicateResults = checkKeyOrFalse($this->resultsReplicate, $this->highestDilution);
        $replicateResults = checkArrayOrEmpty($replicateResults);
        foreach($replicateResults as $rF => $repFields){
          $reportInValue = checkKeyOrFalse($repFields,$this->reportIn );
          if($reportInValue != False){
            $counts[$countI] = $reportInValue;
            $countI++;
          }
       }

        $nAvg = array_sum($counts) / count($counts);
        $n = $nAvg  / ( 1 *  $this->highestDilution );

        $this->endResult = $n;
        $this->indicative = True;

        if($this->usesConfirmation == True){
          $confStatus = $this->_returnConfirmationStatusAndFactor();
          if($confStatus['confirmed'] == False){
            $this->confirmed = False;
          } else {
            $this->confirmed = True;
            $n = $n * $confStatus['confirmationFactor'];
            $this->endResult = $n;
            if($n == 0){
                $this->prepend = '<';
                $this->indicative = False;
                $this->endResult = $this->_returnHighestPossibleNumber();
                $this->disposition[$this->reportIn] = '-';     
            }
          }
        }
    }

    public function estimateMinNonReplicates(){
      $this->disposition[$this->reportIn] = '-';
      $n = 1 / $this->lowestDilution;
      $this->prepend = '<';
      $this->endResult = $n;
    }

   private function _isAllAboveMax(){
       $allAbove = True;
       foreach($this->results as $dF => $dFFields ){
           foreach($dFFields as $count){  if($count <= $this->max && $count != '>'){ $allAbove = False; }}
       }

       return $allAbove;
   }

   private function _isAllZero(){
       $allZero = True;
       foreach($this->results as $dF => $dFFields ){
           foreach($dFFields as $count){  if($count > 0 || $count == '>'){ $allZero = False; }}
       }
       return $allZero;
   }

   public function estimateMinReplicates(){
     $this->disposition[$this->reportIn] = '-';
     $n = 1 / $this->lowestDilution;
     $this->prepend = '<';
     $this->endResult = $n;
   }

   private function _isAllZeroReplicate(){
       $allZero = True;
       foreach($this->results as $dF => $dFFields ){
           foreach($dFFields as $count){  if($count > 0 || $count == '>'){ $allZero = False; }}
           $replicateResults = checkKeyOrFalse($this->resultsReplicate, $dF);
           $replicateResults = checkArrayOrEmpty($replicateResults);
           foreach($replicateResults as $rF => $repFields){
             foreach($repFields as $count){  if($count > 0 || $count == '>'){ $allZero = False; }}
           }
       }
       return $allZero;
   }

   private function _isAllAboveMaxReplicate(){
       $allAbove = True;
       foreach($this->results as $dF => $dFFields ){
           foreach($dFFields as $count){  if($count <= $this->max && $count != '>'){ $allAbove = False; }}
           $replicateResults = checkKeyOrFalse($this->resultsReplicate, $dF);
           $replicateResults = checkArrayOrEmpty($replicateResults);
           foreach($replicateResults as $rF => $repFields){
             foreach($repFields as $count){  if($count <= $this->max && $count != '>'){ $allAbove = False; }}
           }
         }
       return $allAbove;
   }


    public function preliminaryChecks($conf = False){

      // if($this->_confirmationsDone() == False){
      //   $this->output['output'][$this->reportIn] = 'Niet afgerond';
      //   return False;
      // }
      
      if($conf == True){
        if($this->_confirmationsDone() == False){
          $this->_log('preliminary check TRUE confimration done() == False');
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          return False;
        }
      } else{
        if($this->_isDone() == False){
            $this->output['output'][$this->reportIn] = 'Niet afgerond';
            return False;
        }
      }

      return True;
    }

    private function _returnLowestPossibleNumber(){
        $n = 1 / $this->lowestDilution;
        return  $n;
    }

    private function _returnHighestPossibleNumber(){
        $n = 1 / $this->highestDilution;
        return   $n;
    }

    private function _foundAboveMinThreshold($plates){
        $foundAboveThreshold = False;
        foreach($plates as $dF => $data){
            $n = $data[$this->reportIn];
            if($n >= $this->min){
                $foundAboveThreshold = True;
            }
        }
        return $foundAboveThreshold;
    }

    private function _returnConfirmationStatusAndFactor($plates = False){

        $confirmationComplete = False;
        $confirmationFactor = False;

        //0 not started yet so return false object too here
        if($this->confRequested == '2' || $this->confRequested == '0'){
          $this->_log('_returnConfirmationStatusAndFactor as false');
          $returnObject = array('confirmed' => $confirmationComplete, 'confirmationFactor' => $confirmationFactor);
          return $returnObject;
        }


        if($this->confirmationDone == True){
          $this->confCalcDone = True;
        }


        $returnObject = array('confirmed' => $this->confirmationDone, 'confirmationFactor' => NULL );
        return $returnObject;
    }


        private function _foundAboveMinThresholdRep($counts){

          if(empty($counts)){
            return False;
          }

          $foundAboveThreshold = False;
          $numberOfCounts = count($counts);
          $numberOfCountsAbove = 0;

          foreach($counts as $count){
            if($count >= $this->min){
                $foundAboveThreshold = True;
                $numberOfCountsAbove++;
            }
          }

          $ratio = $numberOfCountsAbove / $numberOfCounts;

          if($ratio <= 0.50){
            return False;
          } else{
            return True;
          }
        }

        private function _isMaxIndicativeRep($counts){
          if(empty($counts)){
            return False;
          }

          $foundAboveThreshold = False;
          $numberOfCounts = count($counts);
          $numberOfCountsAbove = 0;

          foreach($counts as $count){
            if($count > $this->max){
                $numberOfCountsAbove++;
            }
          }

          $ratio = $numberOfCountsAbove / $numberOfCounts;

          if($ratio < 0.50){
            return False;
          } else{
            return True;
          }
        }


}
