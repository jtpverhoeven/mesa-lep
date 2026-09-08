//<?PHP
        $aboveZero = False;
        $belowMax = False;                        
        $retainedPlates = array();
        
        $dilHi = False;
        $dilLo = False;
        $reportIn = 'kve';
        $plateMissing = False;

        foreach($results as $dF => $plates){       

            if($dilLo == False){ $dilLo = $dF; }
            $dilHi = $dF;
            
            foreach($plates as $repPlate => $repContent){


                if($uses_confirmation == True && $confirmation_type == 0 ){            

                    $nBeginChain = 0;
                    $nEndChain = False;
                    $confirmedEndChain = 0;

                    $chainEnd = count($confirmation_methods);

                    if(array_key_exists(1, $confirmation_data)){
                        $thisConfMethod = $confirmation_methods[0];
                        $nBeginChain = $confirmation_data[1][$thisConfMethod . '_n'];
                    }

                    if(array_key_exists($chainEnd, $confirmation_data)){
                        $thisConfMethod = end($confirmation_methods);
                        $nEndChain = $confirmation_data[$chainEnd][$thisConfMethod . '_n'];

                        $confirmedEndChain = $confirmation_data[$chainEnd][$thisConfMethod . '_pos'];
                        $confirmedEndChainTested = $confirmation_data[$chainEnd][$thisConfMethod . '_n'];
                        $endOfChainDisposition = substr(trim($thisConfMethod), -1);
                        //array_push($messageBag, 'Disposition: ' . $endOfChainDisposition);
                    }


                    if($nBeginChain == 0 || ( $nEndChain === False || $nEndChain == '') ){
                        $results[$dF][$repPlate]['confirmed'] = False;
                        $results[$dF][$repPlate]['confirmed_value'] =  $repContent['kve'];


                        $chainInterrupted = False;
                        reset($confirmation_methods);
                        
                        foreach($confirmation_methods as $chainI => $cfMethod) {


                            if (array_key_exists($chainI + 1, $confirmation_data)) {

                                $chainI = $chainI + 1;
                                $thisChainDisposition = substr(trim($cfMethod), -1);
                                $thisTested = $confirmation_data[$chainI][$cfMethod . '_n'];
                                $thisConfirmed = $confirmation_data[$chainI][$cfMethod . '_pos'];
                                

                                if ($thisChainDisposition == '-' && $thisConfirmed != '' && $thisConfirmed > 0) {
                                    $chainInterrupted = True;
                                } elseif ($thisChainDisposition != '-' && $thisConfirmed != '' && $thisConfirmed == 0) {
                                    $chainInterrupted = True;
                                }
                            }
                        }

                        if($chainInterrupted == True){
                            $results[$dF][$repPlate]['confirmed'] = True;
                            $results[$dF][$repPlate]['confirmed_value'] =  0;
                        } else{
                            $results[$dF][$repPlate]['confirmed'] = False;
                        }


                    } else{
                        $results[$dF][$repPlate]['confirmed'] = True;

                        if($endOfChainDisposition == '-'){
                            $ratio = ( $confirmedEndChainTested - $confirmedEndChain) / $nBeginChain;
                        } else{
                            $ratio = $confirmedEndChain / $nBeginChain;
                        }

                        $results[$dF][$repPlate]['confirmed_value'] = round($repContent['kve'] * $ratio, 0);
                    }
                }
                
                if($uses_confirmation == True && $confirmation_type == 1 ){
        
                    //per plate analysis
                    if(array_key_exists($dF, $confirmation_data) && array_key_exists($repPlate, $confirmation_data[$dF]) && array_key_exists('getest', $confirmation_data[$dF][$repPlate]) && $confirmation_data[$dF][$repPlate] != 0){
                        $results[$dF][$repPlate]['confirmed'] = True;
                        $ratio = $confirmation_data[$dF][$repPlate]['bevestigd'] / $confirmation_data[$dF][$repPlate]['getest'];
                        $results[$dF][$repPlate]['confirmed_value'] = round($repContent['kve'] * $ratio, 0);
                    } else{
                        $results[$dF][$repPlate]['confirmed'] = False;
                        $results[$dF][$repPlate]['confirmed_value'] = $repContent['kve'];
                    }
                } 
                
                if($uses_confirmation == False){
                        $results[$dF][$repPlate]['confirmed'] = False;
                        $results[$dF][$repPlate]['confirmed_value'] =  $repContent['kve'];
                }    
                                                
                 //check if in range
                if($repContent['kve'] > 0 || $repContent['kve'] == '>'){
                    $aboveZero = True;
                }
                
                if($repContent['kve'] != '>' && $repContent['kve'] <= $countable_maximum && $repContent['kve'] > 0 && $repContent['kve'] != '<'){
                    $belowMax = True;
                    $aboveZero = True;
                    $retainedPlates[$dF][$repPlate] =  $results[$dF][$repPlate];
                }

                if($repContent['kve'] == ''){

                }

                
            }                                        
        }
        

        $dilResults = array();
        $resultsWasConfirmed = True;
        
        foreach($retainedPlates as $rdf => $rp){
            
            $nReps = count($rp);
            $nCFU = 0;
            
            $dilResults[$rdf]['minTresh'] =  False;
            
            foreach($rp as $reps){
                $nCFU = $nCFU + $reps['confirmed_value'];
                if($reps['confirmed'] == False){
                    $resultsWasConfirmed = False;
                }
                
                if($reps['kve'] < 10){
                    $dilResults[$rdf]['minTresh'] =  True;
                }
            }            
            
            $dilResults[$rdf]['kve'] = $nCFU / $nReps;                                                
            $dilResults[$rdf]['nPlates'] = $nReps;


        }
        
        $nDilRes = count($dilResults);
        
        //only HI
        if($aboveZero == True && $belowMax == False){
            

            $lastDil = end($results);          
            $outComposite['prefix'] = '';
            //$outComposite['value'] =  roundSigDigs($lastDil[0]['kve'] / ( 1 * 1.1 * $dilHi ), 2);
            
            $n = $lastDil[0]['kve'] / ( 1 * 1.1 * $dilHi );
            $outComposite['value'] = format_number_significant_figures($n, 2);
            $outComposite['confirmed'] = $lastDil[0]['confirmed'];
            $outComposite['estimate'] = True;
            
        }
        
        //only LO 
        if($aboveZero == False){


            $outComposite['prefix'] = '<';
           
            $n = 1 / $dilLo;

            //$outComposite['value'] =  format_number_significant_figures($n, 2);
            $outComposite['value'] = $n;
            $outComposite['confirmed'] = True;
            $outComposite['estimate'] = False;                        
        }
        
      
        
        //mutiple DF
        if($nDilRes > 0){
                        
            $onlyLow = True;
            $dilLo = false;
            $dilHi = false;
            $nSum = 0;
            
            
            foreach($dilResults as $dilBlockDf => $dilBlock){   
                
                if($dilBlock['minTresh'] != True){
                    $onlyLow = False;
                }


                if(isset($previousCFU)){
                    $thisCFU = $dilBlock['kve'] / $dilBlockDf;                   
                    $dilRatio =  $thisCFU / $previousCFU;
                    
                    if($dilRatio > 2){
                        array_push($messageBag, 'Verdacht resultaat ingevoerd bij verdunnings factor:' . $dilBlockDf);
                        //break;
                    }
                }
                                
                if($dilLo == False){ $dilLo = $dilBlockDf; }
                $dilHi = $dilBlockDf;                
                $nSum = $nSum + $dilBlock['kve'];                                
                $previousCFU = $dilBlock['kve'] / $dilBlockDf;
            }                              


            $numberOfPlates = count($retainedPlates);

            if($numberOfPlates < 2){
                $divisionFactor = 1;
            } else{
                $divisionFactor = 1.1;
            }

            $n = $nSum / ( 1 * $divisionFactor * $dilLo );


            $outComposite['value'] =  format_number_significant_figures($n, 2);            
            $outComposite['prefix'] = '';            
            $outComposite['confirmed'] = $resultsWasConfirmed;

            if($resultsWasConfirmed == True && $n == 0){
                $outComposite['value'] =  format_number_significant_figures( 1 / $dilLo, 2);
                $outComposite['prefix'] = '<';
            }

            if($onlyLow == True){
                $outComposite['estimate'] = True;  
            } else{
                $outComposite['estimate'] = False;  
            }
            
        }
         
        //gen result
        if($analysisReady == False){
            $output['kve'] = '';
        } else{
            $output['kve'] = $outComposite['prefix'] . ' ' . $outComposite['value'];

            if($uses_confirmation == True && $outComposite['confirmed'] == False){
                $output['kve'] =   $output['kve'] . ' <sup>**</sup>';
            }

            if($outComposite['estimate']== True){
                $output['kve'] =   $output['kve'] . ' <sup>*</sup>';
            }
        }



