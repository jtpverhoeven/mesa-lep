//<?PHP

//de hoeveelheid
if(!isset($hoeveelheid)){
    $hoeveelheid = '25';
}



if($results[1][0]['kve'] == '-' ){
    $output['kve'] = 'Niet aangetoond';
}

elseif($results[1][0]['kve'] == '+' ){

    //plus, but conf required, need to check it
    if($uses_confirmation == True && $confirmation_type == 0 ) {

        $nBeginChain = 0;
        $nEndChain = False;
        $confirmedEndChain = 0;

        $chainEnd = count($confirmation_methods);

        if (array_key_exists(1, $confirmation_data)) {
            $thisConfMethod = $confirmation_methods[0];
            $nBeginChain = $confirmation_data[1][$thisConfMethod . '_n'];
        }

        if (array_key_exists($chainEnd, $confirmation_data)) {
            $thisConfMethod = end($confirmation_methods);
            $endOfChainDisposition = substr(trim($thisConfMethod), -1);
            $nEndChain = $confirmation_data[$chainEnd][$thisConfMethod . '_n'];
            $confirmedEndChainTested = $confirmation_data[$chainEnd][$thisConfMethod . '_n'];
            $confirmedEndChain = $confirmation_data[$chainEnd][$thisConfMethod . '_pos'];
        }


        if($nBeginChain == 0 || ( $nEndChain === False || $nEndChain == '') ){

            //confirmation methods might not be entered yet. OR the chain had been interrupted.
            //check which one is true and set the appropriate value            

            //check chain interrupted
            $chainInterrupted = False;
            reset($confirmation_methods);
            
            foreach($confirmation_methods as $chainI => $cfMethod){


                if (array_key_exists($chainI + 1, $confirmation_data)) {

                    $chainI = $chainI + 1;
                    $thisChainDisposition = substr(trim($cfMethod), -1);
                    $thisTested = $confirmation_data[$chainI][$cfMethod . '_n'];
                    $thisConfirmed = $confirmation_data[$chainI][$cfMethod . '_pos'];
                    

                    if($thisChainDisposition == '-' && $thisConfirmed != '' && $thisConfirmed > 0){
                        $chainInterrupted = True;
                    } elseif($thisChainDisposition != '-' && $thisConfirmed != '' && $thisConfirmed == 0){
                        $chainInterrupted = True;
                    }
                }
            }

            if($chainInterrupted == True){
                $output['kve'] = 'Niet aangetoond';
                $disposition['kve'] = '-';
                $outputEn['kve'] = 'Not detected';
            } else{
                $output['kve'] = 'Verdacht';
                $disposition['kve'] = '-';
                $outputEn['kve'] = 'Suspected';
            }

        } else{

            if($endOfChainDisposition == '-'){

                if($confirmedEndChain < $confirmedEndChainTested){
                    $output['kve'] = 'Aangetoond';
                    $disposition['kve'] = '+';
                    $outputEn['kve'] = 'Detected';
                } else{
                    $output['kve'] = 'Niet aangetoond';
                    $disposition['kve'] = '-';
                    $outputEn['kve'] = 'Not detected';
                }

            } else{
                if($confirmedEndChain > 0){
                    $output['kve'] = 'Aangetoond';
                    $disposition['kve'] = '+';
                    $outputEn['kve'] = 'Detected';
                }else{
                    $output['kve'] = 'Niet aangetoond';
                    $disposition['kve'] = '-';
                    $outputEn['kve'] = 'Not detected';
                }
            }

        }

    }

    //plus, no conf, report as found
    else{
        $output['kve'] = 'Aangetoond';
        $disposition['kve'] = '+';
        $outputEn['kve'] = 'Detected';
    }
}

else{
    $output['kve'] = 'Onbekend';
    $disposition['kve'] = '-';
    $outputEn['kve'] = 'Unknown';
}



/*
if($uses_confirmation == True && $confirmation_type == 0 ) {

    $nBeginChain = 0;
    $nEndChain = False;
    $confirmedEndChain = 0;

    $chainEnd = count($confirmation_methods);

    if (array_key_exists(1, $confirmation_data)) {
        $thisConfMethod = $confirmation_methods[0];
        $nBeginChain = $confirmation_data[1][$thisConfMethod . '_n'];
    }

    if (array_key_exists($chainEnd, $confirmation_data)) {
        $thisConfMethod = end($confirmation_methods);
        $endOfChainDisposition = substr(trim($thisConfMethod), -1);
        $nEndChain = $confirmation_data[$chainEnd][$thisConfMethod . '_n'];
        $confirmedEndChain = $confirmation_data[$chainEnd][$thisConfMethod . '_pos'];
    }


}



if($nBeginChain == 0 || ( $nEndChain === False || $nEndChain == '') ){
    $output['kve'] = 'Verdacht';
} else{

    if($confirmedEndChain > 0){
        $output['kve'] = 'Aangetoond';
    } else{
        $output['kve'] = 'Niet aangetoond';
    }
}
} else{
    $output['kve'] = 'Aangetoond';
}
}

else{
    $output['kve'] = 'Onbekend';
}

    */