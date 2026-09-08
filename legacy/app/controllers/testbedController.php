<?PHP

class testsController extends Controller {

    protected $_publicActions = array('testbed' => 'testbed', 'loadTest' => 'loadTest');

    function test2(){

    }

    function test(){  
            //$testbar = upa('samples', 'generateBarcode', array(False, 'THT'), False);
            //print($testbar);        
            $this->render = false; 
            $test = '{"0.1":{"0":{"0":{"0":"-"},"1":{"0":"-"},"2":{"0":"-"},"3":{"0":"-"},"4":{"0":"-"}}},"0.01":{"0":{"0":{"0":"-"},"1":{"0":"-"}}}}';
            $tester = json_decode($test);

            parray($tester);
            
    }

    function loadTest($nodename = 'unknown', $nodeTry = 'unknown'){
        $this->render = False;   
        
        $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'loadtest.txt';
        $profilerLog =  fopen($logLocation,"a+");

        $ident = uniqid();
        $ident = $ident . "\t" . $nodename . "\t" . $nodeTry;

        $str = $ident .  "\t Started \n";
        fwrite($profilerLog, $str);

        //select a project 
        $sql = 'SELECT * FROM `projects` ORDER BY RAND() LIMIT 1 ';
        $result =  $this->Test->customQuery($sql, array());                
        $projectId = $result[0]['id'];

        $str = $ident .  "\t Project to run " . $projectId . "  \n";
        fwrite($profilerLog, $str);

        $project = upa('projects', 'fetch', array($projectId), false);
        $samples = upa('samples', 'fetchSamplesInProject', array($projectId), false);

        foreach($samples as $idx => $sample){
            $template = 'Pranzo dacqua fa volti sghembi';
            $note = upa('samples', 'fetchNote', array($sample['id']), False );
            upa('samples', 'updateSampleField2', array($sample['id'], 'sample_notes', str_shuffle($template), False), False);     
            $note = upa('samples', 'fetchNote', array($sample['id']), False );       
        }
      
        $str = $ident .  "\t Done " . $projectId . "  \n";
        fwrite($profilerLog, $str);
        fclose($profilerLog);
    }


    function roundTest(){
        print format_number_significant_figures(1800, 2);
    }


    function testbed2() {

        $this->render = 0;

        /*      Plate counting calculation including exceptions
         *      based on ISO/DIS 7218
         *      Last revision by Joost on 10-03-2014
         *
         *      Note: This alghorithm does not handle replicates properly yet
         *      as of now, it just takes the average of all replicates. There is no error
         *      checking if replicates are to divergent
         */

        $countable_maximum = 300;

        $results['0.1']['0']['kve'] = '>';
        $results['0.01']['0']['kve'] = '300';
        $results['0.001']['0']['kve'] = '30';
        $results['0.0001']['0']['kve'] = '3';

        $output = array();

        //set maximum colonies per plate as based on assay rules
        $maxPerPlate = $countable_maximum;

        //calculate the upper confidence interval
        //95% conf is square root of expected variance divided by sample size ( 2plates )
        //this resulting digit is then multiplied by 1.96 ( for 95% CI)
        $CI = 1.96 * (sqrt($countable_maximum / 2));

        //this should give 324 for assays with a max count fo 300 and 167 of ones with 150
        $upperLimit = $maxPerPlate + $CI;

        //set lower limit and limits for when the result should become estimate
        $limitForEstimate = 10;     //this value is per ISO/TR 13843 9.4.3.4.2
        $lowerLimit = 4;            //this value is per ISO/TR 13843 9.4.3.4.2
        //set flags for corner cases
        $foundUnderMax = False;     //in case all plates are too high to count
        $foundAboveZero = False;    //in case all plates were negative (i.e.: no colonies)
        $foundConflicting = False;

        //set up a flag to save the LOWEST and HIGHEST dillution factor
        $dfHI = False;
        $dfLO = False;

        //keep track of number of dillutions retained
        $retained = 0;

        //setup an array for the retained dillutions
        $dillutionRetained = array();

        //loop through all the results, breaking each result into two parts
        //the $dillutionFactor which will hold the current dillution factor as a number
        //and the $dPlates, which is an array in itself, containing all the plates in this dillution
        //which are numbered 0, 1 , 2 for each replica.

        foreach ($results as $dillutionFactor => $dPlates) {

            if ($dfLO == False) {
                $dfLO = $dillutionFactor;
            }
            $dfHI = $dillutionFactor;

            //if we already have two consecutive plates we can stop
            if ($retained == 2) {
                continue;
            }

            //set a variable to hold result for this dillution
            $CFU = False;

            //count if this holds any replicates
            $noOfReplicates = count($dPlates);

            //below we aquire the CFU for the current dillution
            if ($noOfReplicates > 1) {

                //if we find any analysis with replicates, just calculate the average
                $repCFU = 0;            //counter for total CFU in replicates
                //loop through all the replicates and add CFU
                foreach ($dPlates as $plate) {
                    $repCFU = $repCFU + $plate['kve'];
                }

                //calculate average repCFU
                $CFU = $repCFU / $replicas;
                $CFU = trim($CFU);      //trim is done to remove any whitespaces the user might have accidenlty added
            } else {

                //if no replicates were found, then we just take the single plate CFU
                //the first plate is always stored as 0 in the plate array, so we can direclty acces this
                $CFU = trim($dPlates[0]['kve']);  //trim is done to remove any whitespaces the user might have accidenlty added
            }


            //now that we have the CFU for this dillution stored, we can check if it conforms
            //to our detection limits, and to setup the flags we started (foundAboveZero, and Undermax)
            //if its ZERO or not entered, we assume there are no colonies
            if ($CFU == '0' || $CFU == '' || $CFU == '<') {
                $previousCFU = $CFU;    //set a flag to check if we are getting weird results
                continue;
            } else {

                //if the previous was zero, and this is higher then 0, something weird is going on.....
                if (isset($previousCFU)) {
                    if ($previousCFU == '0' || $previousCFU == '' || $previousCFU == '<') {
                        $foundConflicting = True;
                        break;
                    }
                }
                $foundAboveZero = True; //notify that we have found something above zero
            }

            if ($CFU == '>' || $CFU > $upperLimit) {
                $previousCFU = $CFU;
                continue;
            } else {

                //check for excessive gaps in counting, if previous was higher than upper CI
                //and this one is lower than lower detection limit, something weird is going on....
                if (isset($previousCFU)) {
                    if ($previousCFU > $upperLimit && $CFU < $limitForEstimate) {
                        $foundConflicting = True;
                        break;
                    }
                }


                $dillutionRetained[$dillutionFactor] = $CFU;
                $retained++;
                $foundUnderMax = True;
                $previousCFU = $CFU;
            }
        }

        //now we start creating the final results by going through all the scenarios
        if ($foundConflicting == True) {
            $output['kve'] = '?';
        }

        //nothing was found..
        if ($foundAboveZero == False && $foundConflicting == False) {
            $kve = round(1 / $dfLO, -3);
            $output['kve'] = '< ' . $kve;
        }

        //everything was above
        elseif ($foundUnderMax == False && $foundConflicting == False) {
            $theoreticalUpper = round($maxPerPlate / $dfHI, -3);
            $output['kve'] = '> ' . $theoreticalUpper;
        }

        //we retained plates
        elseif ($foundConflicting == False) {

            //if we only have 1 plate, indicating we are low on the low end of the detection limit
            if ($retained == 1) {

                $singleCFU = reset($dillutionRetained);
                $singleDillution = key($dillutionRetained);

                //three scenarios are possible..
                //ISO7218: 9.4.2.5.3 = more than 10 but less then 300 on the last dillution > can report, but estimate
                if ($singleCFU <= $maxPerPlate && $singleCFU >= $limitForEstimate) {
                    $n = round($singleCFU / ( 1 * $singleDillution ), -3);
                    $output['kve'] = $n . ' <sup>(estimate</sup>';
                }

                //ISO7218: 9.4.2.4.1 = less then 10 (estimate limit) but more than 4 (lower limit), report  ESTIMATE
                if ($singleCFU <= $maxPerPlate && $singleCFU < $limitForEstimate && $singleCFU > $lowerLimit) {
                    $n = round($singleCFU / ( 1 * $singleDillution ), -3);
                    $output['kve'] = $n . ' <sup>(estimate</sup>';
                }

                //ISO7218: 9.4.2.4.1 = less then 4, can only serve as 'detection'
                if ($singleCFU <= $lowerLimit) {
                    $output['kve'] = 'Aanwezig';
                }
            }

            //else two plates are available, we only need to check if they are not out of bounds
            //and acceptable
            else {

                //setup plates
                $plate1CFU = reset($dillutionRetained);
                $plate1Dillution = key($dillutionRetained);
                array_shift($dillutionRetained);
                $plate2CFU = reset($dillutionRetained);

                //normal situation both are within bounds of counting
                //if($plate1CFU > $limitForEstimate && $plate1CFU < $maxPerPlate &&
                //   $plate2CFU > $limitForEstimate && $plate2CFU < $maxPerPlate ){

                if ($plate1CFU > $limitForEstimate && $plate1CFU < $maxPerPlate) {
                    $Nsum = $plate1CFU + $plate2CFU;
                    $n = $Nsum / (1 * 1.1 * $plate1Dillution);
                    $n = round($n, -3);
                    $output['kve'] = $n;
                }


                //if plate 1 is above max per plate BUT within CI, and second plate is under 10 but HIGHER than 4
                //can calculate normal number
                if ($plate1CFU > $maxPerPlate && $plate1CFU < $upperLimit &&
                        $plate2CFU > $lowerLimit && $plate2CFU < $maxPerPlate) {

                    $Nsum = $plate1CFU + $plate2CFU;
                    $n = $Nsum / (1 * 1.1 * $plate1Dillution);
                    $n = round($n, -3);
                    $output['kve'] = $n;
                }
            }
        }

        //just for clarity, if something went wrong with the calculations, print a question mark
        //to notify the user
        if (!array_key_exists('kve', $output)) {
            $output['kve'] = '?';
        }

        //output
        //parray($output);
    }

    function testbed() {

        $this->render = false;
        phpinfo();
        /*
          $print_data =
          <<<EOD
          ^XA~TA000~JSN^LT0^MNW^MTD^PON^PMN^LH0,0^JMA^PR5,5~SD15^JUS^LRN^CI0^XZ
          ^XA
          ^MMT
          ^PW305
          ^LL0203
          ^LS0
          ^FT6,36^A0N,17,16^FH\^FDAlbert schweitzer ziekenhuis^FS
          ^BY3,3,34^FT29,163^BCN,,Y,N
          ^FD>;14051001^FS
          ^FT9,75^A0N,28,28^FH\^FDVRBG^FS
          ^FT84,75^A0N,28,24^FH\^FD-^FS
          ^FT136,75^A0N,28,28^FH\^FD37\F8C^FS
          ^FT245,37^A0N,17,24^FH\^FDVRI^FS
          ^FO7,42^GB192,0,1^FS
          ^FT92,75^A0N,28,28^FH\^FD1^FS
          ^FO232,8^GB64,44,8^FS
          ^PQ1,0,1,Y^XZ
          EOD;


          die('Huh?');

          $addr = '\\\\192.168.1.58\\GK420d';
          $handle = printer_open($addr);
          printer_set_option($handle, PRINTER_MODE, "RAW");
          printer_write($handle, $print_data);
          printer_close($handle);
         */
    }

    function testbed3() {


        $this->render = False;

        print format_number_significant_figures(49090, 2);


        return;

        $countable_minimum = 10;
        $countable_maximum = 300;
        $confirmation_type = 1;
        $uses_confirmation = True;

        $output = array();
        $confirmation_data = json_decode('{"0.001":{"0":{"getest":"8","bevestigd":"6"}},"0.0001":{"0":{"getest":"4","bevestigd":"4"}}}', True);

         /*
        $results['0.01']['0']['kve'] = '';
        $results['0.01']['1']['kve'] = '';

        $results['0.001']['0']['kve'] = '';
        $results['0.001']['1']['kve'] = '';
         */


        $results['0.001']['0']['kve'] = '9';
        $results['0.0001']['0']['kve'] = '1';



        //$results['0.01']['0']['kve'] = '168';
        //$results['0.01']['1']['kve'] = '168';

        //$results['0.001']['0']['kve'] = '14';
        //$results['0.001']['1']['kve'] = '14';


        //$results['0.01']['0']['kve'] = '30';
        //$results['0.01']['1']['kve'] = '0';

        //$results['0.001']['0']['kve'] = '3';
        //$results['0.001']['1']['kve'] = '0';

        $aboveZero = False;
        $belowMax = False;
        $retainedPlates = array();

        $dilHi = False;
        $dilLo = False;



        //affix confirmation status
        foreach($results as $dF => $plates){

            if($dilLo == False){ $dilLo = $dF; }
            $dilHi = $dF;

            foreach($plates as $repPlate => $repContent){


                if($uses_confirmation == True && $confirmation_type == 0 ){
                    if(array_key_exists('getest', $confirmation_data) && $confirmation_data['getest'] != 0){
                        $results[$dF][$repPlate]['confirmed'] = True;
                        $ratio = $confirmation_data['bevestigd'] / $confirmation_data['getest'];
                        $results[$dF][$repPlate]['confirmed_value'] = round($repContent['kve'] * $ratio, 0);
                    }
                    else{
                        $results[$dF][$repPlate]['confirmed'] = False;
                        $results[$dF][$repPlate]['confirmed_value'] =  $repContent['kve'];
                    }
                }

                if($uses_confirmation == True && $confirmation_type == 1 ){

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

            }
        }


        /////////////////////////////
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
            $outComposite['prefix'] = '>';
            $outComposite['value'] =  round($lastDil[0]['kve'] / ( 1 * 1.1 * $dilHi ), -3);
            $outComposite['confirmed'] = $lastDil[0]['confirmed'];
            $outComposite['estimate'] = True;

        }

        //only LO
        if($aboveZero == False){
            $outComposite['prefix'] = '<';
            $outComposite['value'] =   round( 1 / $dilLo, -3);
            $outComposite['confirmed'] = True;
            $outComposite['estimate'] = False;
        }

        //one DF
        //if($nDilRes == 1){
        //    print 'only one dillution found in range';
        //}

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
                        break;
                    }
                }

                if($dilLo == False){ $dilLo = $dilBlockDf; }

                $dilHi = $dilBlockDf;
                $nSum = $nSum + $dilBlock['kve'];
                $previousCFU = $dilBlock['kve'] / $dilBlockDf;

            }

            $n =  round($nSum / ( 1 * 1.1 * $dilLo ), -3);

            $outComposite['prefix'] = '';
            $outComposite['value'] =   $n;
            $outComposite['confirmed'] = $resultsWasConfirmed;

            if($onlyLow == True){
                $outComposite['estimate'] = True;
            } else{
                $outComposite['estimate'] = False;
            }

        }

        //gen result
        $output['kve'] = $outComposite['prefix'] . ' ' . $outComposite['value'];
        if($uses_confirmation == True && $outComposite['confirmed'] == False){
            $output['kve'] =   $output['kve'] . ' <sup>NB</sup>';
        }

        if($outComposite['estimate']== True){
            $output['kve'] =   $output['kve'] . ' <sup>EST</sup>';
        }


    }

    function testbed4(){

    }

    function chiTest(){
      $this->doNotRenderHeader = True;
      $var = $this->ChiSq(2.8377315, 1);
      print $var;
    }


    function ChiSq($x,$n) {
        if ($x>1000 || $n>1000) {
                $q=Norm((pow($x/$n,1/3)+2/(9*$n)-1)/sqrt(2/(9*$n)))/2;
                if ($x>$n)
                    return $q;
                else
                    return 1-$q;
            }
        $p=exp(-0.5*$x);
            if(($n%2)==1) { $p=$p*sqrt(2*$x/pi());       }
        $k=$n;
            while($k>=2) {
            $p=$p*$x/$k;
            $k=$k-2;
            }
       $t=$p;
         $a=$n;
         while($t>1e-15*$p) {
            $a=$a+2;
            $t=$t*$x/$a;
            $p=$p+$t;
            }

        return 1-$p;
    }

}
