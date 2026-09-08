<?PHP


class maz7218grens extends calculation
{


    protected $reportIn = 'kve';
    protected $borderResult;

    protected $wasConfirmed = False;

    //0 not found
    //1 found
    //2 suspected
    //3 not done

    protected function _reportOutput()
    {
        return array('kve' => 'Aanwezigheid');
    }

    public function provide()
    {
        $this->verbose = True;

        $this->_log('1 $this->borderResult', $this->borderResult);      

        if($this->_isDoneGrens() == False){
            $this->output['output'][$this->reportIn] = 'Niet afgerond';
            $this->output['outputEn'][$this->reportIn] = 'Not completed';
            return $this->_dispatch();
        }


        if( ($this->usesReplicates == False && $this->usesConfirmation == False) || $this->confRequested == '2'){
            $this->simpleBorderReaction();
            $this->_log('2 $this->borderResult', $this->borderResult);
        }

        elseif($this->usesReplicates == False && $this->usesConfirmation == True){
            $this->wasConfirmed = True;
            $this->simpleWithConfirmation();
        }

        $this->_log('3 $this->borderResult', $this->borderResult);

        if($this->authorisedSample == False && isset($this->disposition[$this->reportIn]) && $this->disposition[$this->reportIn] == '+'){
            $showMessage = checkKeyOrFalse($this->externalVariables, 'toonMeldingIndienPositief');          
            if($showMessage !== False){
              $this->_attachMessage($showMessage);  
            }          
        }


        if($this->_confirmationsDone() == False && $this->borderResult !== 0){
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          $this->disposition[$this->reportIn] = '';
          //$this->disposition[$this->reportIn] = '.';
          return $this->_dispatch();
        }

        $this->resultUnifier();
        return $this->_dispatch();
    }

    /**
     * isDone()
     * check if all results are in!
     */
    private function _isDoneGrens(){

        $allDone = True;
        foreach($this->results as $dF => $dFFields ){

            foreach($dFFields as $fieldName => $count){

                if($count == ''){

                  //Exception for PCRs, check if we fail it, only if kve is +
                  if($fieldName == 'Ctwaarde'){
                    $kveSetting = checkKeyOrFalse($this->results[$dF]['kve']);
                    if($kveSetting == '-' || $kveSetting == '0'){
                      //result was neg, ct can be zero  or empty
                    } else{
                        $this->readyFailSignal = True;
                        $allDone = False;
                    }
                  } else{
                    $allDone = False;
                  }
                }
              }
        }
        return $allDone;
    }

    protected function resultUnifier(){


      $this->output['output']['kve'] = $this->borderResult;
      $assayIsPCR = checkKeyOrFalse($this->externalVariables, 'analyseIsPCR');          
  
      $this->_log('$asasyIsPcr' , $assayIsPCR);

      $assayIsPCR = filter_var($assayIsPCR, FILTER_VALIDATE_BOOLEAN);

      $this->_log('$asasyIsPcr' , $assayIsPCR);
      $this->_log('$this->borderResult', $this->borderResult);

      //check if replacer is active
      $replaceOnPositive = filter_var(checkKeyOrFalse($this->externalVariables, 'bijPositiefResultaatVervangen'), FILTER_VALIDATE_BOOLEAN);
      $replaceOnNegative = filter_var(checkKeyOrFalse($this->externalVariables, 'bijNegatiefResultaatVervangen'), FILTER_VALIDATE_BOOLEAN);
      $replacerArray = checkKeyOrFalse($this->externalVariables, 'resultaatVervangenMet');                  
      $replacedDisposition = checkKeyOrFalse($this->externalVariables, 'nieuwResultaatDispositie');

      if($this->borderResult == 0)
      {


        if($replaceOnNegative === True)
        {

          $this->_log('Replace on negative was requested' );

          if($replacerArray === False)
          {
            $replacerArray = [];
          } 

          else
          {                
              $replacerArray = json_decode($replacerArray, JSON_FORCE_OBJECT);                                                
          }


          $this->output['output'][$this->reportIn] = checkKeyOrBlank($replacerArray, 'nl');
          $this->output['outputEn'][$this->reportIn] = checkKeyOrBlank($replacerArray, 'en');

          if($replacedDisposition !== False ){             
            $this->_log('Replace disposition was requested',$replacedDisposition  );   
              $this->disposition[$this->reportIn] = $replacedDisposition;
          }            
              
          return;

        }

        
          $this->output['output'][$this->reportIn] = 'Niet aangetoond';
          $this->output['outputEn'][$this->reportIn] = 'Not detected';
      }

      if($this->borderResult == 1){

        


        $confAddition = '';

        if($replaceOnPositive === True)
        {
            
            if($replacerArray === False)
            {
                $replacerArray = [];
            } 
            
            else
            {                
                $replacerArray = json_decode($replacerArray, JSON_FORCE_OBJECT);                                                
            }
                       
            $this->output['output'][$this->reportIn] = checkKeyOrBlank($replacerArray, 'nl');
            $this->output['outputEn'][$this->reportIn] = checkKeyOrBlank($replacerArray, 'en');

            if($replacedDisposition !== False ){                
                $this->disposition[$this->reportIn] = $replacedDisposition;
            }            
                
            return;
        }

     
        
        

        if($this->usesConfirmation == '1' && $this->confRequested == '2'){
            $confAddition = ' <sup>**</sup>';
        }

        if($assayIsPCR === True)
        {

            $this->_log('Assay is PCR');

            $hideOnPositive = filter_var(checkKeyOrFalse($this->externalVariables, 'bijPositiefNietOpRapport'), FILTER_VALIDATE_BOOLEAN);
            $suspectedOnPositive = filter_var(checkKeyOrFalse($this->externalVariables, 'bijPositiefVerdachtWeergevenOpRapport'), FILTER_VALIDATE_BOOLEAN);
        

            if($suspectedOnPositive === True){
              $this->output['output'][$this->reportIn] = 'Verdacht';
              $this->output['outputEn'][$this->reportIn] = 'Suspected';
            }
                    

            elseif($hideOnPositive === True){                          
              $this->output['output'][$this->reportInHumanReadable . ' '] = 'Verdacht';                            
              $this->output['outputEn'][$this->reportIn] = '';
              $this->output['output'][$this->reportIn] = '';                          
            }
                                     
            else{
              $this->output['output'][$this->reportIn] = 'Aangetoond';
              $this->output['outputEn'][$this->reportIn] = 'Detected';
            }

          } 
          
          else{
            //is kweekmethode
            $this->output['output'][$this->reportIn] = 'Aangetoond' . $confAddition;
            $this->output['outputEn'][$this->reportIn] = 'Detected' . $confAddition;
          }
      }

      if($this->borderResult == 2){
          $this->output['output']['limsResultaat'] = 'Verdacht';
          $this->output['output'][$this->reportIn] = '';
          $this->output['outputEn'][$this->reportIn] = '';
      }

      if($this->borderResult == 3 ){
          $this->output['output']['limsResultaat'] = 'Niet afgerond';
          $this->output['output'][$this->reportIn] = '';
          $this->output['outputEn'][$this->reportIn] = '';
      }

    }

    protected function simpleBorderReaction(){

        $data = reset($this->results);
        $result = $data[$this->reportIn];

        $this->_log('simple border');
        $this->_log('$result', $result);

        if($result == '+' || $result == '1' || $result == '>' || $result == 'J' || $result == 'Y') {

            //$this->output['output'][$this->reportIn] = 'Verdacht';
            //$this->output['outputEn'][$this->reportIn] = 'Suspected';
            $this->borderResult = 1;
            $this->disposition[$this->reportIn] = '+';



        } elseif($result =='-' || $result =='0' || $result =='N'){
            //$this->output['output'][$this->reportIn] = 'Niet aangetoond';
            //$this->output['outputEn'][$this->reportIn] = 'Not detected';
            $this->borderResult = 0;
            $this->disposition[$this->reportIn] = '-';
        } else{
            //$this->output['output'][$this->reportIn] = 'Niet afgerond';
            //$this->output['outputEn'][$this->reportIn] = 'Niet afgerond';
            $this->borderResult = 3;
            $this->disposition[$this->reportIn] = '+';
        }
    }

    protected function simpleWithConfirmation(){

        $data = reset($this->results);
        $result = $data[$this->reportIn];

        $this->_log('simple with conf $result', $result);

        if($result == '-' || $result == '0' || $result == '<'   || $result == 'N' ){
            //$this->output['output'][$this->reportIn] = 'Niet aangetoond';
            //$this->output['outputEn'][$this->reportIn] = 'Not detected';
            $this->borderResult = 0;
            $this->disposition[$this->reportIn] = '-';
        }

        elseif($result == '+' || $result == '1' || $result == '>' || $result == 'J' || $result == 'Y'){

           $this->_log('was plus in simple with conf ');
           $this->_log('$this->confirmationType', $this->confirmationType);

           if($this->confirmationType == '1'){
                $this->checkConfirmation('1', '0');
            } elseif($this->confirmationType == '0'){
                if(in_array($this->originalAssayId, json_decode(MAZ_SALM_IDS) )){
                  $this->checkGlobalConfirmation();
                } else{
                  $this->checkGlobalConfirmation();
                }
            }

        } else{

            $this->borderResult = 3;
            //$this->output['output'][$this->reportIn] = 'Niet afgerond';
            //$this->output['outputEn'][$this->reportIn] = 'Niet afgerond';
        }

    }

    private function checkGlobalSalmonellaConfirmation(){



      // $nBeginChain = 0;
      // $nEndChain = False;
      // $confirmedEndChain = 0;
      //
      // $chainEnd = count($this->confirmationMethods);
      //
      // if (array_key_exists(1, $this->confirmationData))  {
      //     $thisConfMethod = $this->confirmationMethods[0];
      //     $nBeginChain = $this->confirmationData[1][$thisConfMethod['mediaId'] . '_n'];
      // }
      //
      // $investigatePrior = False;
      // if (array_key_exists($chainEnd, $this->confirmationData)) {
      //     $thisConfMethod = end($this->confirmationMethods);
      //     $endOfChainDisposition = $thisConfMethod['disposition'];
      //     $nEndChain = $this->confirmationData[$chainEnd][$thisConfMethod['mediaId']  . '_n'];
      //     $confirmedEndChainTested = $this->confirmationData[$chainEnd][$thisConfMethod['mediaId']  . '_n'];
      //     $confirmedEndChain = $this->confirmationData[$chainEnd][$thisConfMethod['mediaId']  . '_pos'];
      //
      //     if($confirmedEndChainTested == 0){
      //       $investigatePrior = True;
      //     }
      //
      // } else{
      //     $investigatePrior = True;
      // }
      //
      //     //we want to emulate the second to last conf method as chain end,
      //     //EXCEPT if its zero. Because then we need to use the
      //     //last chain link
      // if($investigatePrior == True){
      //   $chainEndPrior = $chainEnd  - 1;
      //   $confMethodId = $chainEnd - 2; //need to do minus to as the conf data array is from 1 - ... & confMethods array is from 0 - ...
      //
      //   if (array_key_exists($chainEndPrior, $this->confirmationData)) {
      //       $thisConfMethod = $this->confirmationMethods[$confMethodId];
      //       $endOfChainDisposition = $thisConfMethod['disposition'];
      //       $nEndChain = $this->confirmationData[$chainEndPrior][$thisConfMethod['mediaId']  . '_n'];
      //       $confirmedEndChainTested = $this->confirmationData[$chainEndPrior][$thisConfMethod['mediaId']  . '_n'];
      //       $confirmedEndChain = $this->confirmationData[$chainEndPrior][$thisConfMethod['mediaId']  . '_pos'];
      //
      //       //reset end chain if was zero
      //       if($confirmedEndChain == 0 || $confirmedEndChain == '' || $confirmedEndChain == False){
      //         $nEndChain = False;
      //         $confirmedEndChain = 0;
      //       }
      //   }
      // }
      //
      //
      // if ($nBeginChain == 0 || ($nEndChain === False || $nEndChain == '')) {
      //
      //     //check chain interrupted
      //     $chainInterrupted = False;
      //     reset($this->confirmationMethods);
      //
      //     foreach ($this->confirmationMethods as $chainI => $cfMethod) {
      //
      //         if (array_key_exists($chainI + 1, $this->confirmationData)) {
      //             $chainI = $chainI + 1;
      //             $thisChainDisposition = $cfMethod['disposition'];
      //
      //             if( !array_key_exists($cfMethod['mediaId'] . '_pos', $this->confirmationData[$chainI]) ||
      //                 !array_key_exists($cfMethod['mediaId'] . '_n', $this->confirmationData[$chainI])){
      //                     $chainInterrupted = False;
      //             }
      //
      //             $thisConfirmed = $this->confirmationData[$chainI][$cfMethod['mediaId'] . '_pos'];
      //             $thisTested = $this->confirmationData[$chainI][$cfMethod['mediaId'] . '_n'];
      //
      //             if ($thisChainDisposition == '-' && $thisConfirmed != '' && $thisConfirmed > 0 && $chainI != 6) {
      //                 $chainInterrupted = True;
      //             } elseif ($thisChainDisposition != '-' && $thisConfirmed != '' && $thisConfirmed == 0 ) {
      //               if($chainI != 7){
      //                 //selectivly disable the interupt for chainI 7, which is the prior to last conf method
      //                 //we still want to keep this as suspected for the next I.
      //                 $chainInterrupted = True;
      //               }
      //
      //             }
      //         }
      //     }
      //
      //     if($chainInterrupted == True){
      //         //$this->output['output'][$this->reportIn] = 'Niet aangetoond';
      //         $this->disposition[$this->reportIn] = '-';
      //         //$this->output['outputEn'][$this->reportIn] = 'Not detected';
      //         $this->borderResult = 0;
      //         $this->confCalcDone = True;
      //     } else{
      //         //$this->output['output'][$this->reportIn] = 'Verdacht';
      //         $this->disposition[$this->reportIn] = '+';
      //         //$this->output['outputEn'][$this->reportIn] = 'Suspected';
      //         $this->borderResult = 3;
      //         $this->confCalcDone = False;
      //     }
      //
      // } else{
      //     $this->confCalcDone = True;
      //     if($endOfChainDisposition == '-'){
      //
      //         if($confirmedEndChain < $confirmedEndChainTested){
      //             //$this->output['output'][$this->reportIn] = 'Aangetoond';
      //             $this->disposition[$this->reportIn] = '+';
      //             //$this->output['outputEn'][$this->reportIn] = 'Detected';
      //
      //             $this->borderResult = 1;
      //         } else{
      //             //$this->output['output'][$this->reportIn] = 'Niet aangetoond';
      //             $this->disposition[$this->reportIn] = '-';
      //             //$this->output['outputEn'][$this->reportIn] = 'Not detected';
      //             $this->borderResult = 0;
      //         }
      //
      //     } else{
      //         if($confirmedEndChain > 0){
      //             //$this->output['output'][$this->reportIn] = 'Aangetoond';
      //             $this->disposition[$this->reportIn] = '+';
      //             $this->borderResult = 1;
      //             //$this->output['outputEn'][$this->reportIn] = 'Detected';
      //         }else{
      //             //$this->output['output'][$this->reportIn] = 'Niet aangetoond';
      //             $this->disposition[$this->reportIn] = '-';
      //             $this->borderResult = 0;
      //             //$this->output['outputEn'][$this->reportIn] = 'Not detected';
      //         }
      //     }
      //
      // }
    }

    private function checkConfirmation($df, $rep)
    {


      $ratio = checkKeyOrFalse($this->confirmationRatios, $df, $rep);

      

      if($ratio === False){
         $this->disposition[$this->reportIn] = '+';
         $this->confCalcDone = False;
         $this->borderResult = 3;
      }

      if($ratio == '0'){
         $this->disposition[$this->reportIn] = '-';
         $this->borderResult = 0;
         $this->confCalcDone = True;
      }

      if($ratio > 0){
        $this->disposition[$this->reportIn] = '+';
        $this->borderResult = 1;
        $this->confCalcDone = True;
      }


    }


    private function checkGlobalConfirmation()
    {


        $ratio = checkKeyOrFalse($this->confirmationRatios, 'global', 0);        

        $this->_log('Ratio in checkglobal conf', $ratio);

        if($ratio === False){
           $this->disposition[$this->reportIn] = '+';
           $this->confCalcDone = False;
           $this->borderResult = 3;
        }

        if($ratio === '0' || $ratio === 0){
           $this->disposition[$this->reportIn] = '-';
           $this->borderResult = 0;
           $this->confCalcDone = True;
        }

        if($ratio > 0){
          $this->disposition[$this->reportIn] = '+';
          $this->borderResult = 1;
          $this->confCalcDone = True;
        }

    }
}
