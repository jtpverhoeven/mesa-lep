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

    protected function _reportOutput(){
       return array('kve' => 'kve');
    }

   public function provide()
   {
         
      $this->_log('Calculating results for SampleAnalysis ID: ' .  $this->thisSAID);

      //What type of curve are we working with
      $this->curveType = $this->determineCurveType();

      //Are all results fields filled in, can not do anything until yes
      if(!$this->areResultsDone())
      {
         return $this->_dispatch();
      }

      //Were all the results zero before confirmation?  Then nothing is to be calculated, report lowest possible
      if($this->allResultsZero())
      {
         $this->setResultZero();
         return $this->_dispatch();
      }

      //TODO: If everything is uncountable, skip to guess-timating end results
      //based on last plate max countable. 


      //Not everything was zero, check uf the dillution and/or replicate curve makes sense
      if(!$this->resultCurveOk())
      {
         return $this->_cancelFailedOnSanity();         
      }
      
      //If this assay does not use confirmations, or the choice is pending
      //use the normal, raw data as modified result.
      //in the case of conf using assays, this calculation also serves to trigger the 
      //confirmation dialog
      if($this->confirmationChoicePending() || $this->usesConfirmation === false)
      {
         $this->_log('No (or pending) confirmation, setting work results to raw results ');
         $this->workResults = $this->distilResultSet(False);
      }

      else
      {
         $this->_log('Active confirmation, applying confirmation factors to work results if they are ready. ');

         if(!$this->processConfirmation())
         {
            return $this->cancelFailedOnConfirmation();         
            return $this->_dispatch();
         }

         $this->workResults = $this->distilResultSet(True);
         $this->_log('Active confirmation, applying confirmation factors to results ');
      }
      
      
      


                  

   }

   private function processConfirmation()
   {
      if($this->usesConfirmation == True)
      {         
         $this->_log('The base analysis of this results indicates confirmation IS used.');          
         
         if(!$this->checkConfirmationStatus())
         {
            $this->_log('Confirmations were not done yet,aborting calculation.');   
            $this->output['output'][$this->reportIn] = 'Niet afgerond';            
            return false;
         }

         else
         {

         }

      }

      else
      {
         $this->_log('The base analysis of this results indicates confirmation IS NOT used. ');

      }


   }


   private function distilResultSet($applyConf = false)
   {
      $combined = $this->combineResults(true);

      foreach($combined as $df => $values)
      {
         
         foreach($values as $rep => $kveValue)
         {
            $combined[$df][$rep] = $this->applyConfirmation($df, $rep, $value);
         }         
      }


   }

   private function applyConfirmation($df, $rep, $value)
   {

   }

   private function confirmationChoicePending()
   {
      return ($this->usesConfirmation == True && $this->confRequested == '0' ) ? True  : False;
   }

   private function cancelFailedOnConfirmation()
   {      
      $this->_log('Aborting calculation, confirmations are not done yet.');             
      $this->output['output'][$this->reportIn] = 'Niet afgerond';
      $this->readyFailSignal = True;
      return $this->_dispatch();
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

      if($this->curveType == 'dillution')
      {
         $curveEvaluation =  $this->_sanityCheckForNonDuplicates($this->results);         
      }

      if($this->curveType == 'replicates')
      {         
         $combinedResultsForFirstDf = $this->combineResults();
         $this->_log('Combined replicates to check result curve ok', $combinedResultsForFirstDf);
         $curveEvaluation = $this->_sanityCheckForDuplicatesDispatch($combinedResultsForFirstDf);
      }

      if($this->curveType == 'both')
      {
         $combinedResultsForFirstDf = $this->combineResults();      
      }

      $this->_log('Evaluation (TRUE/FALSE) of results dillution/replicates curve was:' . $curveEvaluation);
      return $curveEvaluation;

   }

   /**
    * areResultDone(), check if results of all fields are in
    *
    * @return boolean
    */
   private function areResultsDone()
   {
      if(!$this->_isDone())
      {
         $this->_log('Aborting calculation, because not all results are ready.');
         $this->output['output'][$this->reportIn] = 'Niet afgerond';
         $this->readyFailSignal = True;
         return False;
      }
      
      return True;
   }


   /**
    * combineResults(), combine replicate results of either the first dillution
    * or stratified on dillution factor if $allDillutions = true. 
    *
    * @param boolean $allDillutions
    * @return array
    */
   private function combineResults($allDillutions = False)
   {      
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
         
      foreach($results as $df)
      {
         foreach($df as $result)
         {
            if($result > 0 || $result == '>')
            { 
               $allZero = False; 
            }
         }
      }

      $this->_log('Evaluating (TRUE/FALSE) if all results were zero: ' . $allZero);
      
      return $allZero;
   }

   private function _returnLowestPossibleNumber(){
      $n = 1 / $this->lowestDilution;
      return  $n;
   }

   private function _returnHighestPossibleNumber(){
      $n = 1 / $this->highestDilution;
      return   $n;
   }
 
   private function _isAllAboveMax()
   {
      $allAbove = True;
      foreach($this->results as $dF => $dFFields ){
          foreach($dFFields as $count){  if($count <= $this->max && $count != '>'){ $allAbove = False; }}
      }

      return $allAbove;
   }
  

  }