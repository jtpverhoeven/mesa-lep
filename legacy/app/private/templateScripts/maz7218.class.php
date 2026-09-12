<?PHP


class maz7218 extends calculation{


    protected $reportIn = 'kve';
    protected $endResult = NULL;
    protected $prepend = NULL;
    protected $append = NULL;

    protected $indicative = False;
    protected $confirmed = False;
    protected $curveType = null; 

    protected $workResults = [];
    protected $stratifiedData = [];

  
    protected function _reportOutput(){
       return array('kve' => 'kve');
    }

   public function provide()
   {
         
      $this->_log('Calculating results for SampleAnalysis ID: ' .  $this->thisSAID);

      $this->stratifiedData = $this->stratifyOtherResultData();

      //What type of curve are we working with      
      $this->curveType = $this->determineCurveType();

      //Are all results fields filled in, can not do anything until yes
      if(!$this->areResultsDone())
      {
         return $this->_dispatch();
      }

      //do the result "sort-of" make sense?
      if(!$this->preliminaryCheckOK())
      {
         $this->_log('Calculation aborted, major problem detected in result set. ');      
         return $this->_cancelFailedOnSanity();          
      }
   
      //Were all the results zero before confirmation?  Then nothing is to be calculated, report lowest possible
      if($this->allResultsZero())
      {
         $this->setResultZero();
         return $this->_dispatch();
      }

      //Check if everything is uncountable       
      //if so, we cant calculate the result. 
      if($this->isAllUncountable())
      {         
         $this->setResultsOverMax();                       
         return $this->_dispatch();
      }

      //Not everything was zero, nor was everything uncountable
      //check uf the dillution and/or replicate curve makes sense
      if(!$this->resultCurveOk())
      {
         return $this->_cancelFailedOnSanity();         
      }
      
      //If this assay does not use confirmations, or the choice is pending
      //use the normal, raw data as modified result.
      //in the case of conf using assays, this calculation also serves to trigger the 
      //confirmation dialog
      if($this->confirmationChoicePending() || $this->doesNotNeedConfirmation() )
      {
         $this->_log('No (or pending) confirmation, setting work results to raw results ');         
         $this->workResults = $this->distilResultSet(False);
      }
      
      //is not pending, and might need confirmation 
      else
      {
         $this->_log('Confirmation set, setting work results to confirmation applied results ');         
         
         //is confirmation done? if not, throw an error         
         if(!$this->confirmationDataReady())
         {
            return $this->_dispatch();
         }

         $this->workResults = $this->distilResultSet(True);
      }
      
   
      $internalEndResult = $this->calculateEndResult($this->workResults); 
      $formatedEndResult = $this->formatEndResult($internalEndResult, $this->workResults);      
      $this->output['output']['kve'] = $this->prepend .  $formatedEndResult . $this->generateAddendums();

      $this->_log('Formated end result: ' . $formatedEndResult);     
      return $this->_dispatch();
   }

   private function setResultsOverMax()
   {
      $internalEndResult = $this->estimateMaximumNumber();
      $formatedEndResult = $this->formatEndResult($internalEndResult, end($this->results));      
      $this->confDeniedByCalculation = True; 
      $this->prepend = '>';
      $this->indicative = True; 
      $this->output['output']['kve'] = $this->prepend .  $formatedEndResult . $this->generateAddendums();
   }

   private function estimateMaximumNumber()
   {    
      $this->_log('Estimating maximum number,  maximum countable is: ' . $this->max . ' and highest dilution to work with is: ' . $this->highestDilution);            
      $lastDillutionResult = $this->max;
      $n = $lastDillutionResult / ( 1 *  $this->highestDilution );            
      return $n;
   }

   private function generateAddendums()
   {
      
      $addendum = '';

      if($this->indicative == True)
      {
         $addendum .= '<sup>*</sup> ';
      }

      if($this->usesConfirmation == True)
      {
         if($this->confRequested == '2' || $this->confDeniedByCalculation)
         {
            $addendum .= '<sup>**</sup>';
         }
      }

      return $addendum;
   }

   private function formatEndResult($internalEndResult, $workResults)
   {

      $dfs = array_keys($workResults);
            
      if($internalEndResult == 0)
      {
        $this->prepend = '<';
        $this->disposition[$this->reportIn] = '-';
        $this->indicative = False;
        return $this->_returnLowestPossibleNumber(False);
      } 
      
      else
      {        
         $autoAdjust = True;

         if($dfs[0] == '1')
         {
            $this->_log('Not formatting the end results, lowest dillution = 1');
            $autoAdjust = False;
         }
              
         return format_number_significant_figures( $internalEndResult, 2, $autoAdjust);
      }

   }

   private function distilResultSet($applyConf = false)
   {
            
      $trimmed = $this->trimUncountable($this->combineResults(True));
            
      $this->_log('This is the set that will be used for the distilation: ', $trimmed );
      
      $confApplied = [];      
      $foundWithinLimits = False; 
      $reportIndicativeBasedOnDuplos = False; 
      $endReached = False;

      foreach($trimmed as $df => $values)
      {
               

         $confApplied[$df] = ['passed' => [], 'indicative' => [], 'all' => [] ];

         foreach($values as $rep => $kveValue)
         {            

            $confAppliedValue = ($applyConf) ? $this->applyConfirmation($df, $rep, $kveValue) :  $kveValue;

            $addition = $this->checkAddition($df, $rep);

            if($addition !== False)
            {
               $kveAdjust = $kveValue + (int)$addition;

               $this->_log('Adjusting KVE value in sorting loop (determing counts within bounds) <u>temporarly</u> from: ' . $kveValue . ' to: ' . $kveAdjust . ' because of addition of parameter of:' . $addition);

               $kveValue = $kveAdjust;
            }                     
            
         

            //within range 
            if($kveValue <= $this->max && $kveValue >= $this->min)
            {
               array_push($confApplied[$df]['passed'],  $confAppliedValue);               
            }

            //indicative
            else
            {
               array_push($confApplied[$df]['indicative'], $confAppliedValue);               
            } 

            //all values 
            array_push($confApplied[$df]['all'], $confAppliedValue);                        
         }         

      }

      $this->_log('Result curve parsed on indicative/passed: ', $confApplied );

      $distilled = [];

      //move through the DF's and find out if it is better to skip certain plates
      $dfsAvailable = array_keys($confApplied);
      
      for($i = 0; $i <= count($dfsAvailable) - 1; $i++)
      {      
         
         $dfName = $dfsAvailable[$i];
         $dfValue = $confApplied[$dfName];         
         $passed = count($dfValue['passed']);         
         $hasIndicative = (count($dfValue['indicative']) > 0) ? True : False;
                  
         if($foundWithinLimits === True)
         {            
            $distilledForThisDf = $dfValue['all'];            
         }

         //passed values present, use these 
         else if($passed > 0 && $hasIndicative == False )
         {
            $this->_log('Dillution '  . $dfName . ' had passed values, using them. ');
            
            $distilledForThisDf = $dfValue['all'];

            $foundWithinLimits  = True;            
                        
         }

         //grab next DF for IF we need to check code passed===0 codeblock, but also just in general
         $nextDf = array_key_exists($i + 1, $dfsAvailable);
         
         if($nextDf)
         {
            $nextDfValues = $confApplied[$dfsAvailable[$i + 1]];
            $nextDfPassed = count($nextDfValues['passed']);
            $nextDfHasIndicative =  (count($nextDfValues['indicative']) > 0) ? True : False;

            //check if we have reached the end of the line, i.e.: Is the next plate only zeroes? If that case, we dont use the next dillution.
            $nextDfIsEmpty = ((int)array_sum($nextDfValues['all']) === 0) ? True :  False; 
         }

         //no passed result present, is there a following dillution
         //and is that maybe within range?      
         //if we already found a dillution with valid results, this is not needed
         if(($passed === 0 || $hasIndicative === True  ) && $foundWithinLimits == False)
         {


            $this->_log('Dillution '  . $dfName. ' did not have passed values or contains indicative replicate values, checking if next dillution is better. Number passed: ' . $passed . ', has indicative: ' . $hasIndicative);
            //$nextDf = array_key_exists($i + 1, $dfsAvailable);
            
            if($nextDf)
            {

               //$nextDfValues = $confApplied[$dfsAvailable[$i + 1]];
               //$nextDfPassed = count($nextDfValues['passed']);
               //$nextDfHasIndicative =  (count($nextDfValues['indicative']) > 0) ? True : False;
            
               if($nextDfPassed > 0 || $nextDfHasIndicative === False)
               {
                  $this->_log('The next dillution:  '  . $dfsAvailable[$i + 1] . ' has passed values ( Number passed:' . $nextDfPassed .', has indicative:' . $nextDfHasIndicative .'), calculation can be rescued (not indicative) with those results,  dropping this dillution (' . $dfName . ')' );
                  $nextDfAvailableForRescue = True;
               }             
               
               else
               {
                  $this->_log('The next dillution:  '  . $dfsAvailable[$i + 1] . ' has no passed values either, or also contains indicative. Can not rescue this calculation as "non-indicative"' );
                  $nextDfAvailableForRescue = False; 
               }

               
               
               $this->_log('Result of array_sum for $nextDfIsEmpty check:' . array_sum($nextDfValues['all']) . ' gettype:' . gettype(array_sum($nextDfValues['all'])) );
               $this->_log('nextDfIsEmpty:'  . $nextDfIsEmpty);

               if($nextDfIsEmpty)
               {
                  $this->_log('The next dillution:  '  . $dfsAvailable[$i + 1] . ' is empty! Will now stop distilling results at the <i>current</i> dillution: ' . $dfsAvailable[$i]);
               }

            }

            //next DF available? 
            if(!$nextDf || !$nextDfAvailableForRescue || $nextDfIsEmpty )
            {
               $distilledForThisDf = $dfValue['all'];
            }

            else
            {
               $distilledForThisDf = NULL;
            }
         } 
      
         //save the end distillation result for this 
         if(!is_null($distilledForThisDf))
         {
            $distilled[$dfName] = $distilledForThisDf;                        
         }

         //check if we have reached a zero-sum end for this set
         if(isset($nextDfIsEmpty) && $nextDfIsEmpty == True) 
         {
            break;
         }
         
      }      
      
      $this->indicative = ($foundWithinLimits) ? False : True;
      $this->_log('This is the set that will be used for result generation: ', $distilled);
      $this->_log('Evaluation if the result needs to be indicative: ' .  $this->indicative);

      return $distilled;
   }



   private function checkAddition($df, $rep = False)
   {
      $addition = checkKeyOrFalse($this->externalVariables, 'addition');

     
      if($addition)
      {           

         $this->_log('Manual addition requested, will add parameter "' . $addition . '" to result ');
            
         $additionValues = array();

         //return only 1 value for addition check (used @ checking for within counting limits)
         if($rep !== False)
         {
            $this->_log('Returning addition value for df: ' . $df . ' and rep: ' . $rep);

            $toAdd = checkKeyOrFalse($this->stratifiedData, $df, $rep, $addition );

            return $toAdd;
         }

         else
         {
            foreach($this->stratifiedData[$df] as $rep)
            {
               $toAdd = checkKeyOrFalse($rep, $addition);     
   
               if($toAdd !== False)
               {
                  array_push($additionValues, $toAdd);
               }
            }  
            
            if(count($additionValues) === 0)
            {
               $finalAdditionValue = 0;
            }
   
            else
            {
               $finalAdditionValue = array_sum($additionValues) / count($additionValues);      
            }
            
                                                
            $this->_log('Manual addition value summed: ' . $finalAdditionValue);
   
            return $finalAdditionValue;

         }
        
      }

      return False; 

   }

   private function calculateEndResult($distilled)
   {
      $this->_log('--- Now calculating end result ---');
      
      $numberOfPlates = count($distilled);
      $dfs = array_keys($distilled);

      $this->_log('Number of plates after collapsing replicates:' . $numberOfPlates);

      $C = 0; 

      foreach($distilled as $df => $dfValues)
      {         
         $COnThisDf = array_sum($dfValues) / count($dfValues);

         $addition = $this->checkAddition($df);

         if($addition !== False)
         {
            $COnThisDf = $COnThisDf + $addition;
            $this->_log('Count on this DF adjusted to:' . $COnThisDf);
         }


         $C = $C + $COnThisDf;
         $this->_log('Found (average) of ' . $COnThisDf . ' on dillution ' . $df);
      }  

      $this->_log('Summed KVE: ' . $C);      

      $V = 1;      
      $r = ($numberOfPlates > 1) ? 1.1 : 1;      
      $d = (float)$dfs[0];      
      $divisor = $V * $r * $d; 
      $N = $C / $divisor;       
      $roundedN = round($N);

      $this->_log('V: ' . $V);    
      $this->_log('r: ' . $r);    
      $this->_log('d: ' . $d);    
      $this->_log('KVE sum will be divided by : ' . $divisor);    
      $this->_log('Calculated KVE result: ' . $N);
      $this->_log('Rounded calculated KVE result: ' . $roundedN);      

      return $roundedN;         
   }

   private function applyConfirmation($df, $rep, $value)
   {
      if($this->confirmationType == '0')
      {
         $ratio = checkKeyOrFalse($this->confirmationRatios, 'global', '0');
      }

      else
      {
         $ratio = checkKeyOrFalse($this->confirmationRatios, $df, $rep);
      }
            
      $NconfApplied = round($value * $ratio);

      $this->_log('Calculating confirmation adjusted KVE count for dillution: ' . $df . 
                  ' and replicate: '  . $rep . ' with original value: ' . $value . 
                  ', is now:' . $NconfApplied);

      return $NconfApplied;                  
   }   


   private function trimUncountable($results)
   {      
      $trimmed = [];
      
      foreach($results as $df => $values)
      {
         $dfValues = [];
         
         foreach($values as $value)
         {
            if($value != '>')
            {
               array_push($dfValues, $value);
            }
         }

         if(!empty($dfValues)){
            $trimmed[$df] = $dfValues; 
         }
      }

      return $trimmed;

   }

   private function cancelFailedOnConfirmation()
   {      
      $this->_log('Aborting calculation, confirmations are not done yet.');             
      $this->output['output'][$this->reportIn] = 'Niet afgerond';
      $this->output['outputEn'][$this->reportIn] = 'Not completed';
      $this->readyFailSignal = True;
      return $this->_dispatch();
   }

   /**
    * setResultNotResolveable
    * 
    *
    * @return void
    */
    private function setResultNotResolveable()
    {
       
       $this->_log('Setting result to non resolveable');
       $this->confirmed = False;
       $this->indicative = False;
       $this->disposition[$this->reportIn] = '';      
       $this->prepend = '';      
       $this->output['output']['kve'] = 'Niet te bepalen';
       return;
    }

   /**
    * setResultZero(), sets the output result as 
    * "lower then the lower detection limit"
    *
    * @return void
    */
   private function setResultZero()
   {
      $nsig =  $this->_returnLowestPossibleNumber();
      $this->_log('Setting result to zero, _returnLowestPossibleNumber returned: ' . $nsig);
      $this->confirmed = True;
      $this->indicative = False;
      $this->disposition[$this->reportIn] = '-';      
      $this->prepend = '<';      
      $this->output['output']['kve'] = $this->prepend .  $nsig;
      return;
   }

   /**
    * resultsCurveOk(), check if the entered results make sense are are not too
    * far away from each other in each dillution (replicates) and across dillutions (or both)
    *
    * @return void
    */
   private function resultCurveOk()
   {        
      $trimmed = $this->trimUncountable($this->combineResults(True));
      
      $this->_log('Trimmed curve used for checking the validity of results:', $trimmed);
      
      foreach( $trimmed as $df)
      {         
         $replicatesEval = $this->_sanityCheckForDuplicatesDispatch($df);      
         
         if($replicatesEval == False)
         {
            break;
         }
      }            
      
                              
      $curveEvaluation =  $this->_sanityCheckForNonDuplicates($this->collapseReplicates($trimmed), False);               
      
      $this->_log('Evaluation (TRUE/FALSE) of results replicates curve was:' . $replicatesEval);
      $this->_log('Evaluation (TRUE/FALSE) of results dillution curve was:' . $curveEvaluation);                  
      
      return ($curveEvaluation && $replicatesEval ) ? True : False; 
   }


   private function collapseReplicates($results)
   {      
      $collapsed = []; 

      foreach($results as $df => $values)
      {
         $collapsed[$df] = array_sum($values) / count($values);
      }

      return $collapsed; 
   }

   private function stratifyOtherResultData()
   {

      $this->_log('Stratifying all data for later reference:');

      $data = array();

      foreach($this->results as $df => $values)
      {

         $data[$df] = array(); 

         foreach($values as $valuekey => $value)
         {
            $data[$df][0][$valuekey] = $value;
         }
         

         if(array_key_exists($df, $this->resultsReplicate))
         {
            foreach($this->resultsReplicate[$df] as $rep => $repValues)
            {     
               
               $data[$df][$rep] = array();

               foreach($repValues as $valuekey => $value)
               {
                  $data[$df][$rep][$valuekey] = $value;
               }
                                        
            }
         }         

      }   

      $this->_log($data);
      
      return $data;
   }


   /**
    * combineResults(), combine replicate results of either the first dillution
    * or stratified on dillution factor if $allDillutions = true. 
    *
    * @param boolean $allDillutions
    * @return array
    */
   private function combineResults($allDillutions = False, $procesAdditions = False)
   {      
      $otherData = array();
      $counts = array();                  

      if($allDillutions === false)
      {
         $dFResults = reset($this->results); 
         $df = key($this->results); 
         
         
         $counts[0] = $dFResults['kve'];
         
         foreach($this->resultsReplicate[$df] as $rep => $values)
         {
            $counts[$rep] = $values['kve'];            
         }
         
      }

      else
      {
         foreach($this->results as $df => $values)
         {


            
            $counts[$df] = array(); 
            $counts[$df][0] = $values['kve'];

            if(array_key_exists($df, $this->resultsReplicate))
            {
               foreach($this->resultsReplicate[$df] as $rep => $repValues)
               {                  
                  $counts[$df][$rep] = $repValues['kve'];               
               }
            }
            
         }         
      }

      return $counts;
   }

   /**
    * determineCurveType(), check if this is dillution, replicates or both
    *
    * @return string
    */
   private function determineCurveType()
   {
      $hasDillutions = (count($this->results) > 1) ? True : False;
      $hasReplicates = (count($this->resultsReplicate) > 0) ? True : False;      
      
      if($hasDillutions && $hasReplicates)
      {
         $type =  'both';
      }

      elseif($hasReplicates)
      {       
         $type =  'replicates';
      }

      elseif($hasDillutions)
      {      
         $type = 'dillution';
      }

      else
      {
         $type =  'single';
      }
      
      $this->_log('Type of result curve determined as: ' . $type);
      return $type;
   }
 
   private function allResultsZero()
   {
      $results = $this->combineResults(true);
      $allZero = True; 
      
               
      foreach($results as $df => $values)
      {
         foreach($values as $result)
         {

            $addition = $this->checkAddition($df);

            
            if($addition !== False)
            {
               $result = $result + $addition;
            }


            if($result > 0 || $result == '>')
            { 
               $allZero = False; 
            }
         }
      }

      $this->_log('Evaluating (TRUE/FALSE) if all results were zero: ' . $allZero);
      
      return $allZero;
   }

   private function _returnLowestPossibleNumber($useBaseDf = True)
   {

      if($useBaseDf == True)
      {
         
         $df = $this->lowestDilution;
         
         $this->_log('_returnLowestPossibleNumber is using base-df, which is: ' . $df);
      }  

      else
      {         
         
         reset($this->workResults);         
         
         $df = key($this->workResults);                        

         $this->_log('_returnLowestPossibleNumber is using lowest df from workresults, which is: ' . $df );
      }

      $n = 1 / $df;
      
      return  $n;
   }

   private function _returnHighestPossibleNumber()
   {
      $n = 1 / $this->highestDilution;
      return   $n;
   }
 
   private function isAllUncountable()
   {      
      $results = $this->combineResults(true);
      $allMax = True; 
         
      foreach($results as $df)
      {
         foreach($df as $result)
         {
            if($result != '>')
            { 
               $allMax = False; 
            }
         }
      }

      $this->_log('Evaluating (TRUE/FALSE) if all results were uncoutable: ' . $allMax);      
      return $allMax;
   }

   /**
    * PreliminaryCheckOk(), check if there are any major issues with the curve
    * Like, number of colonies going up, etc. 
    *
    * @return void
    */
   private function preliminaryCheckOK(){

      $testResults = $this->combineResults(true);
      krsort($testResults);

      $previousValue = False;      
      
      foreach($testResults as $df => $data)
      {

         //has one (or more) replicates
         if(count($data) > 1)
         {
            $hasOver = in_array('>', $data);            
            $hasZero = in_array('0', $data);
            
            //break if it has both in one replicate
            if($hasOver && $hasZero)
            {
               $this->_log('This replicate has both zero and uncountable in one set. This is not possible.');
               return False; 
            }

            //we do not allow "uncountable" together with any other
            //sort of "countable" result. It is either uncountable, or not. 
            //else, user has to repeat analysis as this might indicate something wrong. 
            if($hasOver)
            {
               $uniqueValues = array_unique($data);               

               if(count($uniqueValues) > 1 || trim($uniqueValues[0]) !== '>')
               {                                    
                  $this->_log('The preliminary check found conflicting values ( uncoutable + countables) in replicate values', $uniqueValues);
                  return False; 
               }
            }

            //average numeric values, or set to > if all above  
            $uValues = array_unique($data);
            if(count($uValues) === 1 && $uValues[0] == '>')
            {
               $thisValue = '>';
            }

            else
            {
               $numeric = [];
               foreach($data as $rValue)
               {
                  if(is_numeric($rValue)){
                     array_push($numeric, $rValue);
                  }
               }

               $thisValue = array_sum($numeric) / count($numeric);
            }      
         }

         else
         {
            $thisValue = array_shift($data);            
         }

         
         if($previousValue === False)
         {
            $previousValue = $thisValue;
            continue; 
         }
            
         if(is_numeric($previousValue) && $thisValue == '>' )
         {
            $this->_log('Previous value was numeric, and this value is uncountable. This seems off.');
            return False;
         }

         if($previousValue == '>' && $thisValue == '0' )
         {
            $this->_log('Previous value was uncountable, and current value is zero. This seems off.');
            return False;
         }

         if( (max($thisValue, $previousValue) > 0) && $previousValue !== '>')
         {

            if($thisValue >= $previousValue || $thisValue == '>'){
               $this->_log('The current value ' .  $thisValue. ' exceeded the previous value ' . $previousValue );
               return False;
            }
         }

         $previousValue = $thisValue;

      }

      $this->_log('Tested the overal results, and this set seems to be fine without major errors. ');      
      return True;
   }
  

  }