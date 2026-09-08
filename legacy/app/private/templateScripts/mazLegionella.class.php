<?PHP


class mazLegionella extends calculation{

    public $reportIn = 'kve';

    public function _reportOutput(){
        return array('kve' => 'kve');
    }

    public function _calculateFactor(){


        return $this->confirmationRatios['global']['0'];

    
    }

    public function _seroTypingDone(){

      $type1 = checkKeyOrFalse($this->enabledMedia, 17);
      $type2 = checkKeyOrFalse($this->enabledMedia, 18);
      $typeSpecies = checkKeyOrFalse($this->enabledMedia, 19);
      $seroDone = ($type1 || $type2 || $typeSpecies) ? true : false;
      return $seroDone;

    }

    public function _addendum($wasZero = False){

        if($wasZero == True){
          $this->output['hidden']['detected']  = False;
          return 'Geen Legionella spp. aangetoond';
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
        #$type1 = (array_key_exists(3, $this->confirmationData) && array_key_exists('17_pos', $this->confirmationData[3])) ? $this->confirmationData[3]['17_pos'] : false;
        #$type2 = (array_key_exists(4, $this->confirmationData) && array_key_exists('18_pos', $this->confirmationData[4])) ? $this->confirmationData[4]['18_pos'] : false;
        #$typeSpecies = (array_key_exists(5, $this->confirmationData) && array_key_exists('19_pos', $this->confirmationData[5])) ? $this->confirmationData[5]['19_pos'] : false;

        if($type1 == True){
            $addendum = 'Legionella Pneumophila – serotype 1';
        } if($type2 == True){
            $addendum  = 'Legionella Pneumophila – serotype 2-14';
        } if($typeSpecies == True){
            $addendum = 'Legionella spp. aangetoond (non-pneumophila)';
        } if($type1 == True && $type2 == True){
            $addendum = 'Legionella Pneumophila – serotype 1 & serotype 2-14';
        } if($type1 == True && $typeSpecies == True){
            $addendum = 'Legionella Pneumophila – serotype 1 & Legionella spp. aangetoond (non-pneumophila)';
        } if($type2 == True && $typeSpecies == True){
            $addendum = 'Legionella Pneumophila – serotype 2-14 & Legionella spp. aangetoond (non-pneumophila)';
        }

        if($type1 == True && $type2 == True && $typeSpecies == True){
           $addendum = 'Legionella Pneumophila – serotype 1, 2-14 & Legionella spp. aangetoond (non-pneumophila)';
       }

        if($type1 == False && $type2 == False && $typeSpecies == False){
          return False; //NOT READY YET!
        }

        if($addendum == False){
          return 'Geen Legionella spp. aangetoond';
        }

        return $addendum;
    }

    public function provide(){

        $this->verbose = True;
        $raceTrack = array();

        //check all done?
        $isDone = $this->_isDone();

        if($isDone == False){
            $this->output['output'][$this->reportIn] = 'Niet afgerond';
            $this->output['outputEn'][$this->reportIn] = 'Not completed';
            return $this->_dispatch();
        }


        //BCYE - 74
        foreach($this->metaResults[72] as $BCYE){
            $nBCYE = 0;
            $nBCYEplates = count($this->metaResults[72][1]);
            foreach($BCYE as $BCYEplate){
                $nBCYE = $nBCYE + $BCYEplate[$this->reportIn];
            }
            $raceTrack[72] = $nBCYE / $nBCYEplates;
        }

        //BCYE_WARM - 75
        foreach($this->metaResults[73] as $BCYE){
            $nBCYE = 0;
            $nBCYEplates = count($this->metaResults[73][1]);

            foreach($BCYE as $BCYEplate){
                $nBCYE = $nBCYE + $BCYEplate[$this->reportIn];
            }
            $raceTrack[73] = $nBCYE / $nBCYEplates;
        }

        foreach($this->metaResults[74] as $BCYE){
            $nBCYE = 0;

            $nBCYEplates = count($this->metaResults[74][1]);
            foreach($BCYE as $BCYEplate){
                $nBCYE = $nBCYE + $BCYEplate[$this->reportIn];
            }

            //this can not be higher then 0
            if($nBCYE > 0){
              $this->output['output'][$this->reportIn] = 'Fout resultaat gevonden, BCYE - Positief';
              $this->readyFailSignal = True;
              return $this->_dispatch();
            }

            $raceTrack[74] = $nBCYE / $nBCYEplates;
        }

        foreach($this->metaResults[75] as $BCYE){
            $nBCYE = 0;
            $nBCYEplates = count($this->metaResults[75][1]);
            foreach($BCYE as $BCYEplate){
                $nBCYE = $nBCYE + $BCYEplate[$this->reportIn];
            }
            $raceTrack[75] = $nBCYE / $nBCYEplates;
        }

        foreach($this->metaResults[76] as $BCYE){
            $nBCYE = 0;
            $nBCYEplates = count($this->metaResults[76][1]);
            foreach($BCYE as $BCYEplate){
                $nBCYE = $nBCYE + $BCYEplate[$this->reportIn];
            }
            $raceTrack[76] = $nBCYE / $nBCYEplates;
        }

        foreach($this->metaResults[77] as $BCYE){

            $nBCYE = 0;
            $nBCYEplates = count($this->metaResults[77][1]);
            foreach($BCYE as $BCYEplate){
                $nBCYE = $nBCYE + $BCYEplate[$this->reportIn];
            }

            //this can not be higher then 0
            if($nBCYE > 0){
              $this->output['output'][$this->reportIn] = 'Fout resultaat gevonden, BCYE - Positief';
              $this->readyFailSignal = True;
              return $this->_dispatch();
            }

            $raceTrack[77] = $nBCYE / $nBCYEplates;
        }

        arsort($raceTrack);
        reset($raceTrack);
        $setToUse = key($raceTrack);

        //74 en 77 are minus BCYE's


        $confirmationBCYE = $this->_calculateFactor();

        if($confirmationBCYE === False){
            $nonConfirmed = true;
            $confirmationBCYE = 1;
        } else{
            $nonConfirmed = false;
        }

        $n = ($raceTrack[$setToUse] * $confirmationBCYE * 5) / (0.1 * 0.25);
        $wasZero = False;

        $this->output['output']['kve'] = '';
        $this->output['output']['aanvulling'] = '';

        if ($n <= 0 ){
            //it is done, but check if they had any confirmations still active that werent finished?
            // if($this->_confirmationsDone() == False){
            //   $this->output['output'][$this->reportIn] = 'Niet afgerond';
            //   $this->metaIsReady = False;
            // } else{
              //ready.. it was zero. conf doesnt matter
              $n  = '<100';
              $wasZero = True;
              $this->metaIsReady = True;
              $addendum = $this->_addendum($wasZero);
              $this->output['output']['kve'] = $n;
              $this->output['output']['aanvulling'] = $addendum;
              $this->disposition['kve'] = '-';
            //}
            return $this->_dispatch();

        } else{

            if($this->_confirmationsDone() == False){
              $this->output['output'][$this->reportIn] = 'Niet afgerond';
              $this->output['outputEn'][$this->reportIn] = 'Not completed';
              $this->metaIsReady = False;
            } else{
               $addendum = $this->_addendum();
               $n = format_number_significant_figures($n, 2);
               $this->output['output']['kve'] = $n;
               $this->output['output']['aanvulling'] = $addendum;
               $this->metaIsReady = True;
               $this->disposition['kve'] = '+';
            }


            $this->askForMetaConfirmation = True;
        }

        
        return $this->_dispatch();
    }


    public function _isDone(){

        $allDone = True;

        foreach($this->metaResults[72] as $BCYE){
            foreach($BCYE as $BCYEplate){
             if($BCYEplate[$this->reportIn] == ''){
                 $allDone = False;
             }
            }
        }

        //BCYE_WARM - 75
        foreach($this->metaResults[73] as $BCYE){
            foreach($BCYE as $BCYEplate){
                if($BCYEplate[$this->reportIn] == ''){
                    $allDone = False;
                }
            }
        }

        foreach($this->metaResults[74] as $BCYE){
            foreach($BCYE as $BCYEplate){
                if($BCYEplate[$this->reportIn] == ''){
                    $allDone = False;
                }
            }
        }

        foreach($this->metaResults[75] as $BCYE){
            foreach($BCYE as $BCYEplate){
                if($BCYEplate[$this->reportIn] == ''){
                    $allDone = False;
                }
            }
        }

        foreach($this->metaResults[76] as $BCYE){
            foreach($BCYE as $BCYEplate){
                if($BCYEplate[$this->reportIn] == ''){
                    $allDone = False;
                }
            }
        }

        foreach($this->metaResults[77] as $BCYE){
            foreach($BCYE as $BCYEplate){
                if($BCYEplate[$this->reportIn] == ''){
                    $allDone = False;
                }
            }
        }

        return $allDone;
    }

}
