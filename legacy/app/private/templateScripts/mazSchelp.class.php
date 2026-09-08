<?PHP


class mazSchelp extends calculation{

    protected $reportIn = 'kve';

    protected function _reportOutput(){
        return array('kve' => 'kve');
    }

    /**
     * isDone()
     * check if all results are in!
     */
    protected function _isDone(){
        $allDone = True;
        foreach($this->results as $dF => $dFFields ){
            foreach($dFFields as $count){  if($count == ''){ $allDone = False; }}
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

                if(array_key_exists($dF, $this->resultsReplicate)){
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

    public function provide(){

        $this->verbose = True;

        if($this->usesReplicates == 1){
            $simpleResult = $this->_calculateDuplo();


            if($simpleResult >= $this->min){
              $foundAboveMin = True;
            } else{
              $foundAboveMin = False;
            }
            //$foundAboveMin = $this->_foundAboveMinThreshold();

            $allNull = $this->_isAllNull();
            $allZero = $this->_isAllZeroReplicate();

            if($this->_isDoneRep() == False){
              $this->output['output'][$this->reportIn] = 'Niet afgerond';
              $this->output['outputEn'][$this->reportIn] = 'Not completed';
              return $this->_dispatch();
            }

            if($allNull == True){
                $this->output['output'][$this->reportIn] = 'Niet afgerond';
                $this->output['outputEn'][$this->reportIn] = 'Not completed';
                return $this->_dispatch();
            }
            else{

                if($this->usesConfirmation){
                    $confirmation = $this->_returnConfirmationStatusAndFactor(False);

                    if($allZero == True){
                        $result = $this->_returnLowestPossibleNumber();
                        $this->output['output'][$this->reportIn] = $result;
                        return $this->_dispatch();
                    }

                    elseif($confirmation['confirmed'] == False){
                        $result = $simpleResult * 20;
                        if($result == 0){ $result = $this->_returnLowestPossibleNumber(); }

                        if($foundAboveMin == false){
                            $this->output['output'][$this->reportIn] = $result  . '<sup>*</sup> <sup>**</sup>';
                        } else{
                            $this->output['output'][$this->reportIn] = $result  . '<sup>**</sup>';
                        }

                    } else{
                        //$result =  $this->_genericTwoPlateCalculation($keptPlates, True, $confirmation['confirmationFactor']);
                        $simpleResult = $simpleResult *  $confirmation['confirmationFactor'];
                        $result = $simpleResult * 20;
                        if($result == 0){ $result = $this->_returnLowestPossibleNumber(); }
                        $this->output['output'][$this->reportIn] = $result;

                        if($foundAboveMin == false){
                            $this->output['output'][$this->reportIn] = $result  . '<sup>*</sup>';
                        } else{
                              $this->output['output'][$this->reportIn] = $result;
                        }


                    }
                }

                else{
                    $result = $simpleResult * 20;


                    if($allZero == True){
                        $result = $this->_returnLowestPossibleNumber();
                        $this->output['output'][$this->reportIn] = $result;
                        return $this->_dispatch();
                    }

                    $result = format_number_significant_figures( $result, 2, True);

                    //if($result == 0){ $result = $this->_returnLowestPossibleNumber(); }

                    if($simpleResult < $this->min || $simpleResult > $this->max){
                        $this->output['output'][$this->reportIn] = $result  . '<sup>*</sup>';
                    } else{
                        $this->output['output'][$this->reportIn] = $result;
                    }
                }

            }

        } else{
            $this->_calculateForNonReplicates();
        }


        return $this->_dispatch();
    }


    protected function _calculateDuplo(){

        $nSum = 0;

          foreach($this->results as $df => $data){
            $n = $data[$this->reportIn];
            //if($n <= $this->max && $n != '>' && $n > 0 ){
            if(is_numeric($n)){
                $nSum = $nSum + $n;
            }

            //}

            if(array_key_exists($df, $this->resultsReplicate)){
              foreach($this->resultsReplicate[$df] as $rep => $data){
                  $n = $data[$this->reportIn];
                  //if($n <= $this->max && $n != '>' && $n > 0 ){
                    if(is_numeric($n)){
                          $nSum = $nSum + $n;
                    }

                  //}
              }
            }
        }

        return $nSum;
    }


    /**
     * Checks if any of the plates are above the minimum countable threshold
     * @param $plates
     * @return bool
     */
    protected function _foundAboveMinThreshold(){
        $foundAboveThreshold = False;
        foreach($this->results as $df => $data){
            $n = $data[$this->reportIn];
            if($n >= $this->min){
                $foundAboveThreshold = True;
            }

            if(array_key_exists($df, $this->resultsReplicate)){
            foreach($this->resultsReplicate[$df] as $rep => $data){
                $n = $data[$this->reportIn];
                if($n >= $this->min){
                    $foundAboveThreshold = True;
                }
            }
          }
        }
        return $foundAboveThreshold;
    }



    protected function _calculateForNonReplicates(){

        $allAboveMax = $this->_isAllAboveMax();
        $allZero = $this->_isAllZero();

        //return indicative when all zero or all above max
        if($allAboveMax == True || $allZero == True){
            $this->_calculateOutOfBounds($allAboveMax, $allZero);
            return;
        }

        //plates inbounds, get suitable plates
        $countablePlates = $this->_getCountablePlates();
        $keptPlates = $this->_refinePlateCollection($countablePlates);
        $this->_sanityCheckForNonDuplicates($keptPlates);

        if($this->usesConfirmation == False){

            if(count($keptPlates) == 2){
                $result =  $this->_genericTwoPlateCalculation($keptPlates);

                if($this->_foundAboveMinThreshold($keptPlates) == False){
                    $result = $result . '<sup>*</sup>';
                }

                $this->output['output'][$this->reportIn] = $result;
            } else{
                $result =  $this->_genericOnePlateCalculation($keptPlates);

                if($this->_foundAboveMinThreshold($keptPlates) == False){
                    $result = $result . '<sup>*</sup>';
                }


                $this->output['output'][$this->reportIn] = $result;
            }
        }

        if($this->usesConfirmation == True){

            if(count($keptPlates) == 2){

                $confirmation = $this->_returnConfirmationStatusAndFactor($keptPlates);
                if($confirmation['confirmed'] == False){
                    $result =  $this->_genericTwoPlateCalculation($keptPlates);
                    if($result == 0){ $result = $this->_returnLowestPossibleNumber(); }
                    $this->output['output'][$this->reportIn] = $result . '<sup>**</sup>';
                } else{
                    $result =  $this->_genericTwoPlateCalculation($keptPlates, True, $confirmation['confirmationFactor']);
                    if($result == 0){ $result = $this->_returnLowestPossibleNumber(); }
                    $this->output['output'][$this->reportIn] = $result;
                }
            } else{
                $confirmation = $this->_returnConfirmationStatusAndFactor($keptPlates);

                if($confirmation['confirmed'] == False){
                    $result =  $this->_genericOnePlateCalculation($keptPlates);

                    if($result == 0){
                        $result = $this->_returnLowestPossibleNumber();
                        $this->output['output'][$this->reportIn] = $result . '<sup>**</sup>';
                    }

                    else{
                        if(!$this->_foundAboveMinThreshold($keptPlates)){
                            $this->output['output'][$this->reportIn] = $result . '<sup>*</sup> <sup>**</sup>';
                        } else{
                            $this->output['output'][$this->reportIn] = $result . '<sup>**</sup>';
                        }
                    }

                } else{
                    $result =  $this->_genericOnePlateCalculation($keptPlates, True, $confirmation['confirmationFactor']);
                    if($result == 0){
                        $result = $this->_returnLowestPossibleNumber();
                        if(!$this->_foundAboveMinThreshold($keptPlates)){
                            $this->output['output'][$this->reportIn] = $result . '<sup>*</sup>';
                        } else{
                            $this->output['output'][$this->reportIn] = $result;
                        }
                    } else{
                        if(!$this->_foundAboveMinThreshold($keptPlates)){
                            $this->output['output'][$this->reportIn] = $result . '<sup>*</sup>';
                        } else{
                            $this->output['output'][$this->reportIn] = $result;
                        }
                    }

                }
            }
        }

    }


    protected function _returnConfirmationStatusAndFactor($plates = False){


        $confirmationComplete = False;
        $confirmationFactor = False;

        if($this->confirmationType == '0') {

            $nBeginChain = 0;
            $nEndChain = False;
            $confirmedEndChain = 0;

            $chainEnd = count($this->confirmationMethods);

            if (array_key_exists(1, $this->confirmationData)) {
                $thisConfMethod = $this->confirmationMethods[0];

                $nBeginChain = $this->confirmationData[1][$thisConfMethod['mediaId'] . '_n'];
            }

            if (array_key_exists($chainEnd, $this->confirmationData)) {
                $thisConfMethod = end($this->confirmationMethods);
                $nEndChain = $this->confirmationData[$chainEnd][$thisConfMethod['mediaId'] . '_n'];
                $confirmedEndChain = $this->confirmationData[$chainEnd][$thisConfMethod['mediaId'] . '_pos'];
                $confirmedEndChainTested = $this->confirmationData[$chainEnd][$thisConfMethod['mediaId'] . '_n'];
                #$endOfChainDisposition = substr(trim($thisConfMethod), -1);
                $endOfChainDisposition = $thisConfMethod['disposition'];
            }

            /*
             *
             * CONFIRMATION DATA SEEMS INCOMPLETE OR INTERRUPTED
             *
             */

            if ($nBeginChain == 0 || ($nEndChain === False || $nEndChain == '')) {
                $confirmationComplete = False;
                $confirmationFactor = 1;

                $chainInterrupted = False;
                reset($this->confirmationMethods);

                foreach ($this->confirmationMethods as $chainI => $cfMethod) {

                    if (array_key_exists($chainI + 1, $this->confirmationData)) {
                        $chainI = $chainI + 1;

                        #$thisChainDisposition = substr(trim($cfMethod), -1);
                        #$thisConfirmed = $this->confirmationData[$chainI][$cfMethod . '_pos'];

                        $thisChainDisposition = $cfMethod['disposition'];
                        $thisConfirmed = $this->confirmationData[$chainI][$cfMethod['mediaId'] . '_pos'];

                        if ($thisChainDisposition == '-' && $thisConfirmed != '' && $thisConfirmed > 0) {
                            $chainInterrupted = True;
                        } elseif ($thisChainDisposition != '-' && $thisConfirmed != '' && $thisConfirmed == 0) {
                            $chainInterrupted = True;
                        }
                    }
                }

                if ($chainInterrupted == True) {
                    $confirmationComplete = True;
                    $confirmationFactor = 0;
                } else {
                    $confirmationComplete = False;
                    $confirmationFactor = 1;
                }
            }

            /*
             *      CONFIRMATION DATA SEEMS COMPLETE
             */

            else {
                $confirmationComplete = True;

                if ($endOfChainDisposition == '-') {
                    $ratio = ($confirmedEndChainTested - $confirmedEndChain) / $nBeginChain;
                } else {
                    $ratio = $confirmedEndChain / $nBeginChain;
                }

                $this->_warn($ratio);
                $confirmationFactor = $ratio;
            }
        }

        else{

        }

        $returnObject = array('confirmed' => $confirmationComplete, 'confirmationFactor' => $confirmationFactor);
        return $returnObject;
    }


    /**
     *  Checks if the given plate dillution curve makes sense
     */
    protected function _sanityCheckForNonDuplicates($plates, $hasNameTags = True){

        $previousValue = False;
        $previousDillution = False;

        foreach($plates as $dF => $data){

            if($previousValue == False){
                $previousValue = $data[$this->reportIn];
                $previousDillution = $dF;
                continue;
            }

            $thisValue = $data[$this->reportIn];
            $thisValueCorrected = $thisValue * 10;
            $avg = ( $thisValueCorrected + $previousValue ) / 2;
            $lowestCount = min($thisValueCorrected, $previousValue);
            $highestCount = max($thisValueCorrected, $previousValue);



            if($avg > 30){
                $lowestCount50percent = $lowestCount * 0.5;
                $maximumPossible = $lowestCount + $lowestCount50percent;

                if($highestCount > $maximumPossible){
                    $this->_attachMessage('Verdacht resultaat, verschil te groot tussen verdunning (' . $previousDillution . ') en (' . $dF . ')');
                } else{
                }

            } elseif($avg <=30){
                $lowestCount50percent = $lowestCount * 1.0;
                $maximumPossible = $lowestCount + $lowestCount50percent;

                if($highestCount > $maximumPossible){
                    $this->_attachMessage('Verdacht resultaat, verschil te groot tussen verdunning (' . $previousDillution . ') en (' . $dF . ')');
                } else{
                }
            }
        }
    }



    /**
     * @param $plates
     * @param $retain
     * @return array of retained plates
     *
     * this function clips the end of a dillution range in case we hav something like this
     * -1   150
     * -2   15
     * -3   1
     *
     * -3 is still technically countable, but we dont want it in this case since we have a solid -1 and -2 to do our calcuation on
     * However, if -1 was over the upper limit, we still want the
     */

    protected function _refinePlateCollection($plates, $retain = 2){
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

    /**
     * Gets the plates within countable reach based on reportIn
     * @return array
     */

    protected function _getCountablePlates(){
        $kept = array();
        foreach($this->results as $df => $data){
            $n = $data[$this->reportIn];
            if($n <= $this->max && $n != '>' && $n > 0 ){
                $kept[$df] = $data;
            }
        }
        return $kept;
    }

    /**
     *  Calculates the plate count when its all above maximum count
     *  or all zero.
     */
    protected function  _calculateOutOfBounds($allAboveMax, $allZero){

        if($allZero == True){
            $n = 1 / $this->lowestDilution;
            $this->output['output']['kve'] = '<' . $n;
        }elseif($allAboveMax == True){
            $indicator = '';
            $lastDillution = end($this->results);
            if( $lastDillution[$this->reportIn] == '>' ){
                $indicator = '>';
                $n = $this->max  / ( 1 *  $this->highestDilution );
            } else{
                $n = $lastDillution[$this->reportIn]  / ( 1 *  $this->highestDilution );
            }

            /* check if this uses confirmation, if so, fetch the status and factor */
            if($this->usesConfirmation == True){


                $lastPlate = array( $this->highestDilution => $this->results[$this->highestDilution]);
                $confStatus = $this->_returnConfirmationStatusAndFactor($lastPlate);

                if($confStatus['confirmed'] == False){
                    $nSig = format_number_significant_figures($n, 2);
                    $this->output['output']['kve'] =  $indicator . $nSig . '<sup>*</sup> <sup>**</sup> ';
                }

                if($confStatus['confirmed'] == True){

                    $n = $n * $confStatus['confirmationFactor'];
                    if($n == 0){
                        $n = $this->_returnHighestPossibleNumber();
                        $this->output['output']['kve'] =  $n;
                    } else{
                        $nSig = format_number_significant_figures($n, 2);
                        $this->output['output']['kve'] = $indicator .  $nSig . '<sup>*</sup>';
                    }
                }

            } else{
                $nSig = format_number_significant_figures($n, 2);
                $this->output['output']['kve'] = $indicator . $nSig . '*';
            }

        }
    }

    /**
     * Check if all the results are above $this->max
     * @return bool
     */
    protected function _isAllAboveMax(){
        $allAbove = True;
        foreach($this->results as $dF => $dFFields ){
            foreach($dFFields as $count){  if($count <= $this->max && $count != '>'){ $allAbove = False; }}
        }
        return $allAbove;
    }

    /**
     * Check if any of the results are above zero
     * @return bool
     */
    protected function _isAllZero(){
        $allZero = True;
        foreach($this->results as $dF => $dFFields ){
            foreach($dFFields as $count){  if($count > 0 || $count == '>'){ $allZero = False; }}
        }
        return $allZero;
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

    protected function _returnLowestPossibleNumber(){
        //$n = 1 / $this->lowestDilution;
        $this->disposition[$this->reportIn] = '-';
        $n = 20;
        return  '<' . $n;
    }

    protected function _returnHighestPossibleNumber(){
        $n = 1 / $this->highestDilution;
        return  '<' . $n;
    }

}
