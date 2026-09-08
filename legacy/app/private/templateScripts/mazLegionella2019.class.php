<?PHP


class mazLegionella2019 extends calculation{

    public $reportIn = 'kve';
    public $matrixType = 'A';
    public $selfBase;
    public $stratResults = [];
    private $sampleInfo = [];

    public function _reportOutput(){
        return array('kve' => 'kve');
    }

    public function provide(){      
        
        $this->_log('Legionella provide function');       
                
        $this->matrixType = trim(checkKeyOrFalse($this->externalVariables, 'matrixType'));                        
        $this->sampleInfo = upa('samples', 'fetch', array($this->sample), False);
        
        //done? 
        $isDone = $this->_isDone();
        if($isDone == False){            
            $this->_log('Analysis was not done, setting to not complete');
            $this->output['output'][$this->reportIn] = 'Niet afgerond';
            $this->output['outputEn'][$this->reportIn] = 'Not completed';
            $this->metaIsReady = False;
            return $this->_dispatch();
        }

        //matrix a and bcye wrong? 
        if($this->matrixType === 'A' && $this->BCYEMinValid() === False){
            $this->_log('Found a wrong result, canceling calculation.');
            $this->output['output'][$this->reportIn] = 'Fout resultaat gevonden, BCYE - Positief';
            $this->readyFailSignal = True;
            return $this->_dispatch();
        }
        

        //use mock confirmation ratio if not yet set to calculate if a confirmation should be requested / set         
        $confirmationRatio = checkKeyOrFalse($this->confirmationRatios, 'global', '0');
        $notConfirmedYet = False;

        if($confirmationRatio === False){
            $this->_log('Confirmation ratio was not yet calculated, setting to 1 to calculate result for now');
            $notConfirmedYet = True;
            $confirmationRatio = 1;
        } 


        //fetch raw KVE end result
        $rawKVE = $this->calculateEndResult($this->matrixType, $confirmationRatio);

        $this->_log('Raw KVE:' . $rawKVE);

        //enable asking for confirmation if raw KVE is > 0 
        if($rawKVE > 0 && $notConfirmedYet){                 
            $this->_log('Raw kve was > 0, confirmation was not yet performed. Returning signalPositive() to ask for confirmation ');                   
            return $this->signalPositive();            
        } 
        
        return $this->formulateReportedResult($rawKVE);        

    }

    private function signalPositive(){
        $this->askForMetaConfirmation = True;
        $this->metaIsReady = False;
        $this->output['output']['kve'] = 'Bevestiging wacht';        
        $this->disposition['kve'] = '+';          
        return $this->_dispatch();
    }

    private function formulateReportedResult($rawKVE){
        

        //can report independantly of confirmation
        if($rawKVE <= 0){
            
            $this->_log('formulateReportedResult: Raw KVE was 0, we can report result without using confirmations.');
                        
            if($this->matrixType == 'C')
            {
                $this->_log('Analysis was matrix C, grabbing lowest possible count ');

                $lowestCount = $this->getMatrixCLowestCount();                

                $this->_log('Lowest possible count:' . $lowestCount);

                $reportedKVE = '<' . format_number_significant_figures($lowestCount, 2); ;
            }

            else
            {
                $this->_log('Analysis was not matrix C, reporting <100 by default ');
                
                $reportedKVE  = '<100';            
            }
            
            
            $this->metaIsReady = True;
            
            $addendum = $this->_addendum(True);

            $this->output['output']['kve'] = $reportedKVE;
            $this->output['output']['aanvulling'] = $addendum['nl'];

            $this->output['outputEn']['kve'] = $reportedKVE;
            $this->output['outputEn']['aanvulling'] = $addendum['en'];

            $this->disposition['kve'] = '-';          

            $this->_log('End result:' . $reportedKVE);
            $this->_log('Addendum NL:' . $addendum['nl']);
            $this->_log('Addendum EN:' .  $addendum['en']);


            return $this->_dispatch();
        }

        //need to figure out if conf was a go
        else {

            $this->_log('formulateReportedResult: Raw KVE was > 0, check confirmations');
                        
            if($this->_confirmationsDone() == False)
            {            
                $this->_log('Confirmations not done, reporting not complete');
                $this->output['output'][$this->reportIn] = 'Niet afgerond';                
                $this->output['outputEn'][$this->reportIn] = 'Not completed';         
                $this->metaIsReady = False;                
                return $this->_dispatch();
            } 
            
            else
            {            
                
                $this->_log('Confirmations done. Generating result');

                $addendum = $this->_addendum();             
                
                $reportedKVE = format_number_significant_figures($rawKVE, 2);
                
                $this->output['output']['kve'] = $reportedKVE;
                $this->output['output']['aanvulling'] = $addendum['nl'];

                $this->output['outputEn']['kve'] = $reportedKVE;
                $this->output['outputEn']['aanvulling'] = $addendum['en'];
                
                $this->metaIsReady = True;
                
                $this->disposition['kve'] = '+';

                $this->_log('End result:' . $reportedKVE);
                $this->_log('Addendum NL:' . $addendum['nl']);
                $this->_log('Addendum EN:' .  $addendum['en']);
                
                return $this->_dispatch();
            }

        }

    }

    private function calculateEndResult($matrix, $confirmationRatio){
        
        $this->_log('calculateEndResult: matrix: ' . $matrix . ' confirmation ratio:' . $confirmationRatio);

        $highestNumberFound = $this->getHighestSet($matrix);

        $this->_log('highest number found: ' . $highestNumberFound);
        
        if($matrix == 'A')
        {
            
            $kve = $this->matrixACalculation($highestNumberFound, $confirmationRatio);
        }

        else if($matrix == 'B')
        {
            $kve = $this->matrixBCalculation($highestNumberFound, $confirmationRatio);
        }

        else if ($matrix =='C')
        {
            $kve = $this->matrixCCalculation($highestNumberFound, $confirmationRatio);
        }


        return $kve;
    }

    private function matrixACalculation($highestNumberFound, $confirmationRatio)
    {        
        $this->_log('Entering Matrix A Calculation');
        $a = ($confirmationRatio) * (int)$highestNumberFound;
        $Vc = 5;
        $V =  0.2;
        $Vtot = $this->grabFilterVolume();
        $Vs = 1000;
        $kve = (($a * $Vc) / ($V * $Vtot)) * $Vs;



        $this->_log('a:' . $a );
        $this->_log('Vc:' . $Vc);
        $this->_log('V:' . $V);
        $this->_log('Vtot:' . $Vtot);
        $this->_log('Vs:' . $Vs);
        $this->_log('kve:' . $kve);

        return $kve;        
    }


    private function matrixBCalculation($highestNumberFound, $confirmationRatio)
    {   
        $this->_log('Entering Matrix B Calculation');     
        $a = ($confirmationRatio) * (int)$highestNumberFound;
        $Vc = 5;
        $V =  1;
        $Vtot = $this->grabFilterVolume();
        $Vs = 1000;
        $kve = (($a * $Vc) / ($V * $Vtot)) * $Vs;

        $this->_log('a:' . $a );
        $this->_log('Vc:' . $Vc);
        $this->_log('V:' . $V);
        $this->_log('Vtot:' . $Vtot);
        $this->_log('Vs:' . $Vs);
        $this->_log('kve:' . $kve);

        return $kve;        
    }

    private function matrixCCalculation($summedNumberOfColonies, $confirmationRatio)
    {   
        $this->_log('Entering Matrix C Calculation');
        
        $a = ($confirmationRatio) * (int)$summedNumberOfColonies;
        $Vtot =  $this->grabFilterVolume();
        $Vs = 1000;

        $kve = ($a / $Vtot) * $Vs;

        $this->_log('a:' . $a );
        $this->_log('Vtot:' . $Vtot);
        $this->_log('Vs:' . $Vs);        
        $this->_log('kve:' . $kve);

        return $kve;        
    }

    private function getMatrixCLowestCount()
    {        

        $this->_log('Calculating matrix C lowest bound');

        $a = 1;
        $Vs = 1000;
        $Vtot =  $this->grabFilterVolume();

        $kve = (($a / $Vtot) * $Vs);
        
        $this->_log('a:' . $a );
        $this->_log('Vtot:' . $Vtot);
        $this->_log('Vs:' . $Vs);        

        $this->_log('Calculated lower calculation bound as ' . $kve );
        return $kve;        
    }

    private function BCYEMinValid(){

        $stratifiedResults = $this->stratifyResults();
        $bcyeMinValue = max($stratifiedResults[2]);

        if($bcyeMinValue === ""){
            return False; 
        }

        $bcyeMinResult = (int)$bcyeMinValue;

        if($bcyeMinResult == 0){
            return True;
        } 

        if($bcyeMinResult > 0){
            return False; 
        }

    }

    private function grabFilterVolume(){        
        
        $sampleExtra = json_decode($this->sampleInfo['sample_extra'], JSON_FORCE_OBJECT);
        
        if(array_key_exists('filter_volume', $sampleExtra))
        {
            $vol =  $sampleExtra['filter_volume']; 
            $vol = (float) str_replace(',', '.', $vol);
            $this->_log('Filter volume grabbed: ' . $vol);
        } 
        
        else
        {
            $this->_log('Filter volume was not set as sample_extra key, falling back on default');
            $vol = 250; 
        }                

        return $vol;
    }

    private function getHighestSet($matrix){
        
        $stratifiedResults = $this->stratifyResults();
                
        if($matrix == 'A'){
            $highest = max(  $stratifiedResults[0], $stratifiedResults[1], 
                            $stratifiedResults[3], $stratifiedResults[4],
                            $stratifiedResults[5],$stratifiedResults[6] );            
        }

        if($matrix == 'B'){
            $highest = max( array_sum($stratifiedResults[0]) ,
                            array_sum($stratifiedResults[1]) ,
                            array_sum($stratifiedResults[2]) );                   
        }

        if($matrix == 'C'){
            $highest = array_sum($stratifiedResults[0]);    
        }
        
        
        if(is_array($highest)){
            $highest =  $highest[0];
        }

        $this->_log('Highest set value set to: ' . $highest );
        return $highest;

    }

    private function stratifyResults(){
        
        $stratResults = [];

        foreach($this->metaResults as $metaResultAssayId => $metaResult){

            $pos = count($stratResults);
            $stratResults[$pos] = [];

            if($metaResultAssayId == $this->originalAssayId){
                continue;
            }

            foreach($metaResult as $dillution => $dillutionPlates){                    

                foreach($dillutionPlates as $replicate => $replicatePlate){
                    array_push($stratResults[$pos], $replicatePlate[$this->reportIn]);                    
                }
            }
        }

        $this->_log('Stratified results:');
        $this->_log($stratResults);

        return $stratResults;
    }

    public function _isDone(){        
        
        $allDone = True;
        foreach($this->metaResults as $metaResultAssayId => $metaResult){

            if($metaResultAssayId == $this->originalAssayId){
                continue;
            }

            foreach($metaResult as $dillution => $dillutionPlates){                
                foreach($dillutionPlates as $replicate => $replicatePlate){
                    if($replicatePlate[$this->reportIn] == ''){
                        $allDone = False;
                    }
                }
            }
        }

        return $allDone;
    }

    public function _addendum($wasZero = False){

        if($wasZero == True){
          $this->output['hidden']['detected']  = False;
          return ['nl' => 'Geen Legionella spp. aangetoond', 'en' => 'Legionella spp. not detected' ];
        }

        $type1 = False;
        $type2 = False;
        $typeSpecies = False;

        $raceTrackExists = checkKeyOrFalse($this->confirmationData, 'global');
        if($raceTrackExists == false){
          return False;
        }


        foreach($this->confirmationData['global'][0] as $colony => $chainLinks){
          $thisType1 = checkKeyOrFalse($chainLinks, 2);
          $thisType2 = checkKeyOrFalse($chainLinks, 3);
          $thisTypeSpecies = checkKeyOrFalse($chainLinks, 4);

          if($thisType1 == '+'){
            $this->output['hidden']['detected']  = True;
            $type1 = True;
          }

          if($thisType2 == '+'){
            $this->output['hidden']['detected']  = True;
            $type2 = True;
          }

          if($thisTypeSpecies == '+'){
            $this->output['hidden']['detected']  = True;
            $typeSpecies = True;
          }
        }

        $addendum = False;
     
        if($type1 == True){
            $addendum = [ 'nl' => 'Legionella pneumophila – serotype 1', 'en' => 'Legionella pneumophila – serotype 1' ];
        } if($type2 == True){
            $addendum  = [ 'nl' => 'Legionella pneumophila – serotype 2-14', 'en' => 'Legionella pneumophila – serotype 2-14' ];
        } if($typeSpecies == True){
            $addendum = [ 'nl' => 'Legionella spp. (non-pneumophila)', 'en' => 'Legionella spp. (non-pneumophila)' ];
        } if($type1 == True && $type2 == True){
            $addendum = [ 'nl' => 'Legionella pneumophila – serotype 1 & serotype 2-14' , 'en' => 'Legionella pneumophila – serotype 1 & serotype 2-14' ];
        } if($type1 == True && $typeSpecies == True){
            $addendum = [ 'nl' => 'Legionella pneumophila – serotype 1 & Legionella spp. (non-pneumophila)', 'en' => 'Legionella pneumophila – serotype 1 & Legionella spp. (non-pneumophila)' ];
        } if($type2 == True && $typeSpecies == True){
            $addendum = [ 'nl' => 'Legionella pneumophila – serotype 2-14 & Legionella spp. (non-pneumophila)', 'en' => 'Legionella pneumophila – serotype 2-14 & Legionella spp. (non-pneumophila)' ];
        }

        if($type1 == True && $type2 == True && $typeSpecies == True){
           $addendum = [ 'nl' => 'Legionella pneumophila – serotype 1, 2-14 & Legionella spp. (non-pneumophila)', 'en' => 'Legionella pneumophila – serotype 1, 2-14 & Legionella spp. (non-pneumophila)' ];
       }

        if($type1 == False && $type2 == False && $typeSpecies == False){
          return False; //NOT READY YET!
        }

        if($addendum == False){
          return [ 'nl' => 'Geen Legionella spp. aangetoond', 'en' => 'Legionella spp. not detected'];
        }

        return $addendum;
    }

}