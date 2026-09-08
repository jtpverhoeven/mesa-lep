<?PHP

class rodac extends calculation
{

    protected $reportIn = 'kve';


    protected function _reportOutput()
    {
        //return array('kve' => 'kve', 'kve/1cm' => 'kve/1cm',  'project gemiddelde/1cm' => 'project gemiddelde/1cm');
        return array('kve' => 'kve');
    }

    public function provide()
    {
        $this->verbose = True;

        //indicatief?
        $addendum = '';
        if($this->results[1]['kve'] > $this->max){
          $addendum = '<sup>*</sup>';
          $this->output['hidden']['indicatief'] = True;
        }

        if(array_key_exists('1', $this->results)){
                        
            if(is_numeric($this->results[1]['kve'] )){
                $this->output['output']['kve'] = $this->results[1]['kve'] .$addendum;                                    
                $number = ($this->results[1]['kve'] / 16);
                $nSig = sprintf("%.2f",round($number, 2));
                $this->output['output']['kve/1cm'] =  $nSig . $addendum;
            }
            
        }

        //get all samplse
        $sampleInfo = upa('samples', 'fetch', array($this->sample) , False );
        $samplesInProject = upa('samples', 'projectSamples',array($sampleInfo['project']), False);
        $projectInfo = upa('projects' , 'fetch' , array($sampleInfo['project']), False);
        $projectExtra = json_decode($projectInfo['project_extra'], JSON_FORCE_OBJECT);
        $projectExtra = checkArrayOrEmpty($projectExtra);

        $totalKveCounted = 0;
        $totalSamplesCounted = 0;
        $overMaxCounted = 0;

        foreach($samplesInProject as $sample){

            $totalSamplesCounted++;
            $saids = upa('sampleAnalysis', 'fetchAnalysisArray', array($sample['id']), false);
            foreach($saids as $thisSaid){

                //if($thisSaid['profile'] == RODAC_BASE_PROFILE)
                if($thisSaid['assay_base'] == $this->assayBase)
                {              
                    $retResult = $this->_returnResults($thisSaid['id']);
                    $result = $retResult['results'];
                    
                    if(is_numeric($result[1]['kve'] ) ){                              
                        $totalKveCounted = $totalKveCounted + $result[1]['kve'] / 16;                  
                        if($result[1]['kve'] > $this->max){
                          $overMaxCounted++;
                        }
                    }                
                }
            }
        }

        //$this->output['output']['project gemiddelde/1cm'] = $totalKveCounted / $totalSamplesCounted;
        if(array_key_exists('number_of_blanks', $projectExtra))
        {
            $blankNumber = $projectExtra['number_of_blanks'];
        } 

        else
        {
          //if number of blanks key not set, assume no blanks.
          $blankNumber = 0;
        }

        $dividerCount = $totalSamplesCounted - $blankNumber;
        if($dividerCount == 0){ $dividerCount = 1; }
        $indFlag = ($dividerCount - $overMaxCounted) / $dividerCount;
        $addendum = '';

        if($indFlag <= 0.50)
        {
            $addendum =  '<sup>*</sup>';
        }

        if($dividerCount == 0)
        {
          $this->output['output']['project gemiddelde/1cm'] = 0;
        } 
        
        else{
          $number = $totalKveCounted / $dividerCount;
          $nSig = sprintf("%.2f",round($number, 2));
          $this->output['output']['project gemiddelde/1cm'] = $nSig . $addendum;
        }


        return $this->_dispatch();
    }

}
