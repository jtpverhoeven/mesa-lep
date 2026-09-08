<?PHP


class mazKarkasKiem extends calculation{

    protected $reportIn = 'kve';
    protected $indicative = False;
    protected $endResult = null;
    protected $dillFactor = array();

    protected function _reportOutput(){
        return array('kve' => 'kve');
    }


    public function provide(){

      //populate dill factor list
      $this->dillFactor['1'] = 5;
      $this->dillFactor['0.1'] = 50;
      $this->dillFactor['0.01'] = 500;
      $this->dillFactor['0.001'] = 5000;
      $this->dillFactor['0.0001'] = 50000;
      $this->dillFactor['0.00001'] = 500000;

      //check if done
      if($this->_isDone() == False){
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          return $this->_dispatch();
      }
                  
      if($this->confirmationChoicePending() || $this->doesNotNeedConfirmation() )
      {
         $this->_log('No (or pending) confirmation, setting work results to raw results ');         
         $this->_nonReplicates();
      }

      else
      {
         $this->_log('Confirmation set, setting work results to confirmation applied results ');         
         
         //is confirmation done? if not, throw an error         
         if(!$this->confirmationDataReady())
         {
           
            return $this->_dispatch();
         }


         $this->_log($this->confirmationRatios);
         $this->_nonReplicates(True);
         
      }



      if($this->endResult !== null){
          $this->brewEndResult();
      }

      return $this->_dispatch();
    }

    public function brewEndResult(){

      $neatResult = number_format((float)$this->endResult, 2, '.', '');
      $references = $this->attachLimits();

      $textual = array();
      $textual['low'] = 'Toereikend';
      $textual['medium'] = 'Acceptabel';
      $textual['high'] = 'Ontoereikend';

      

      $append = '';
      if($this->indicative == True){
        $append = '<sup>*</sup>';
      }

      #$this->output['hidden']['kve'] = $this->endResult;
      $this->output['hidden']['kve'] = $neatResult;
      $this->output['output'][$this->reportIn] = $neatResult . $append;
      $this->output['output']['addendum'] = '';
      $this->output['hidden']['limits'] = $references;
      $this->output['hidden']['indicatief'] = $this->indicative;


      if($this->endResult >=  $references['upper']){
          $addendum = '(' . $textual['high'] . ')';
      }

      elseif($this->endResult >= $references['lower'] ){
          $addendum = '(' . $textual['medium'] . ')';
      }

      //elseif($logSum > 5.0){
      else{
          $addendum = '(' . $textual['low'] . ')';
      }




      $this->output['output']['addendum'] = $addendum;

    }


    public function _nonReplicates($applyConf = false){

      $prelim = $this->_preliminaryCheck();

      if($prelim == False){
        $this->_cancelFailedOnSanity();
        return;
      }

      //is all zero?
      if($this->_isAllZero() == True){
          $this->estimateMinNonReplicates();
          return;
      }

      if($this->_isAllAboveMax() == True){
          $this->estimateMaxNonReplicates();
          return;
      }

      $sanityResult = $this->_sanityCheckForNonDuplicates($this->results);
      if($sanityResult == false){
          $this->_cancelFailedOnSanity();
          return;
      }

      $plates = $this->_refinePlateCollection($this->results);
      $aboveMin = $this->_foundAboveMinThreshold($plates);
      $plateSum = 0;
      $setDf = False;

      if($aboveMin == False){
          $this->indicative = True;
      }


      foreach($plates as $df => $result){
          if($setDf == False || $setDf < $df){
            $setDf = $df;
          }
          
          $kve =  $result[$this->reportIn];
          
          if($applyConf === True)
          {

            $this->_log('applying');

            if($this->confirmationType == '0')
            {
               $ratio = checkKeyOrFalse($this->confirmationRatios, 'global', '0');
            }
      
            else
            {
               $ratio = checkKeyOrFalse($this->confirmationRatios, $df, 0);
            }
                  
            $kve = round($kve * $ratio);

            $this->_log('kve now ' . $kve);

          }
          
          $plateSum = $plateSum + $kve;
      }

      if(count($plates) == 1){
        $divisor = 1 * 1;
      } else{
        $divisor = 1 * 1.1;
      }

      $kveSum = $plateSum / $divisor;
      $dilFactorApplied = $kveSum * $this->dillFactor[$setDf];
      $logSum = log10($dilFactorApplied);



      $this->endResult = $logSum;
    }


    public function estimateMaxNonReplicates(){
        $lastDillution = end($this->results);
        $divisor = 1 * 1;
        $kveSum = $lastDillution[$this->reportIn] / $divisor;
        $dilFactorApplied = $kveSum * $this->dillFactor[$this->highestDilution];
        $logSum = log10($dilFactorApplied);
        $this->endResult = $logSum;
        $this->indicative = True;
    }

    public function estimateMinNonReplicates(){

      $dFResults = reset($this->results); // First Element's Value
      $df = key($this->results); // First Element's Key

      $plateSum = 1;
      $firstDf = $df;
      //$divisor = 1 * 1.1 * $firstDf;
      $divisor = 1 * 1 * $firstDf;
      $kveSum = $plateSum / $divisor;
      $dilFactorApplied = $kveSum * 5;
      $logSum = log10($dilFactorApplied);
      $this->endResult = $logSum;
      $this->indicative = True;

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

    public function attachLimits(){
      $varkens = checkKeyOrFalse($this->externalVariables, 'varkens');

      $references = array();
      $references['lower'] = 0;
      $references['upper'] = 0;
      if($varkens == True){
        $references['lower'] = 4.00;
        $references['upper'] = (float)5.00;
      } else{
        $references['lower'] = 3.50;
        $references['upper'] = (float)5.00;
      }

      $references['lower_text'] = 'Toereikend';
      $references['medium_text'] = 'Acceptabel';
      $references['high_text'] = 'Ontoereikend';

      $references['lower_text_en'] = 'Adequate';
      $references['medium_text_en'] = 'Acceptable';
      $references['high_text_en'] = 'Insufficient';

      return $references;
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

}
