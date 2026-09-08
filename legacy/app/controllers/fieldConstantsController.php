<?PHP

class fieldConstantsController extends controller{
    
    /* Constant types
     *  
     *  1:  Fixed value, no polling
     *  2:  Get value
     */
    
    /*  Operation types
     * 
     *  1:  Get value > Tied together with RESTRICTOR -> High, Low, Specific
     */
    
    /* Restrictor types
     * 
     * 0: Get first value in array (assumed no dillutions etc)
     * 1: Lowest dillution
     * 
     * 
     */
    
    
    function constantsArray($analysisId, $said){
        
        $this->render = 0;
        
        $this->FieldConstant->where('analysis', $analysisId);                
        $sampleValues = pa('results', 'getTestResults', array($said));
        
        parray($sampleValues);
        $constants = $this->FieldConstant->search();
        
        $consArray = array();
        
        foreach($constants as $constant){
                     
            //SET VALUE
            if($constant['const_type'] == 1){   
                $consArray[$constant['const_name']] = $constant['const_value'];
            }          
            
            //GET VALUE
            if($constant['const_type'] == 2){
                
                //Plain ol' retreive and replicate averaged retreive
                if($constant['operation'] == 1 || $constant['operation'] == 2){
                    
                    if($constant['operation'] == 2){
                         $averageResults = True;
                    } else {
                        $averageResults = False;
                    }
                    
                    //this assumes no dillution present, pull the first
                    //available number from the results array
                    if($constant['restrictor'] == 0){                                                
                        $sr = array_extractor($sampleValues, 'dillution_num', $sampleValues[0]['dillution_num']);
                    }
                    
                    //Lowest dillution                   
                    if($constant['restrictor'] == 1){
                        array_sort($sampleValues,'dillution', 'rep');                        
                        $sr = array_extractor($sampleValues, 'dillution_num', $sampleValues[0]['dillution_num']);
                    }
                    
                    //Highest dillution                   
                    if($constant['restrictor'] == 2){
                        array_sort($sampleValues,'!dillution', 'rep');                        
                        $sr = array_extractor($sampleValues, 'dillution_num', $sampleValues[0]['dillution_num']);
                    }
                    
                    //specific dillution
                    if($constant['restrictor'] == 3){
                        $sr = array_extractor($sampleValues, 'dillution_num', $constant['restrictor_dil']);
                    }

                    
                    //for averaging
                    $cumValue = 0;
                    $cumMeasurements = 0;
                    
                    foreach($sr as $arr_ind => $resultField){
                        $fieldVals = unserialize($resultField['fields']);       //load up the values
                        $cumValue = $cumValue + $fieldVals[$constant['poll_col']];
                        $cumMeasurements = $cumMeasurements + 1;
                    }
                                                            
                    $consArray[$constant['const_name']] = $cumValue / $cumMeasurements;
                }
                
                
            }
        }
        
        parray($consArray);
        return $consArray;
    }
    
    function addConstant(){        
                
        
        $this->render = 0;
        $this->FieldConstant->arrayToModel($_POST);
        $this->FieldConstant->save();
    }
    
    function fetchConstants($analysis){
        
        $this->FieldConstant->where('analysis', $analysis );
        $results = $this->FieldConstant->search();
        
        foreach($results as $arrInd => $constant){
            if($constant['const_type'] == 1){
                $results[$arrInd]['const_type_text'] = 'Set value (' . $constant['const_value'] .')';
            }
            if($constant['const_type'] == 2){
                $results[$arrInd]['const_type_text'] = 'Get value';
            }                        
        }
        
        return $results;        
    }
    
    function fetchConstantsAjax($analysis){
        
        $this->render = 0;
        $constants = $this->fetchConstants($analysis);
        $tF = new tableFactory();
        $tF->setTableId('constants_table');
        $tF->loadTemplate('constantsListing');
        $tF->loadValues($constants);    
        print $tF->renderTable();
    }
    
    function removeConstant($id = False){
        
        $this->render = 0;
        if($id == False){
            if(isset($_POST['id'])){
                $id = $_POST['id'];
            } else {
                return;
            }                        
        }
        
        $this->FieldConstant->id = $id;
        $this->FieldConstant->remove();
    }    
}