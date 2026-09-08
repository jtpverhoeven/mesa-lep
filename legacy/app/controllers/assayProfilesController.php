<?PHP

class assayProfilesController extends controller{

    function saveProfile(){

        $this->render = 0;
        $this->AssayProfile->postToModel();

        $dillutionArr = dillutionTextToArray($_POST['dillution']);
        $this->AssayProfile->dillutions = json_encode($dillutionArr, JSON_FORCE_OBJECT);

        $refValueArr = array();
        foreach($_POST as $postName => $postValue){
            if(substr($postName, 0, 4) == 'ref_'){
                $refValueArr[$postName] = $postValue;
            }
        }

        $referenceJSON = json_encode($refValueArr, JSON_FORCE_OBJECT);
        $this->AssayProfile->reference =  $referenceJSON;
        $this->AssayProfile->save();
    }

    function getConfTripValue($id){
      $this->AssayProfile->where('id', $id);
      $results = $this->AssayProfile->search();
      if(!empty($results)){
        return $results[0]['conf_trip'];
      }
    }


    function fetchProfileById($id){
        $this->render = 0;
        $this->AssayProfile->where('id', $id);
        $results = $this->AssayProfile->search();
        return $results;
    }

    function fetchByProfileAndAssay($researchProfile, $assay){
        $this->render = 0;
        $this->AssayProfile->where('research_profile', $researchProfile);
        $this->AssayProfile->where('assay', $assay);

        $results = $this->AssayProfile->search();

        return $results;
    }

    function fetchAnalysis($profileId, $raw = False, $incHidden = False){

        $this->render = 0;

        $this->AssayProfile->where('research_profile', $profileId);
        $this->AssayProfile->order('project_order', 'ASC');

        if($incHidden !== True){
          $this->AssayProfile->where('hidden', '0');
        }

        $results = $this->AssayProfile->search();

        if(empty($results)){
          return False;
        }

        // create the table for list here to save some ocde on the other side
        $addArr = array();

        foreach($results as $profile){

            //TODO: refactor
            $assayInfo = pa('assays', 'fetchSingle', array($profile['assay']));

            if($assayInfo == False){
                continue;
            }

            if($raw == True){
                $addArr[$profile['id']] = $profile;
                continue;
            }

            $addArr[$profile['id']]['id'] = $profile['id'];
            $addArr[$profile['id']]['assay'] = $assayInfo['name'];

            if($assayInfo['type'] == 1){ $type = '{enumeration}'; }
            if($assayInfo['type'] == 2){ $type = '{detection}'; }
            $addArr[$profile['id']]['type'] = $type;

            $dillVisual = '';
            $dillutions = json_decode($profile['dillutions'], True);
            foreach($dillutions as $dil=>$dilFac){
                $dillVisual .= $dil . ' (' . $dilFac . ') <br />';
            }


             $addArr[$profile['id']]['dillutions'] = $dillVisual;
             $addArr[$profile['id']]['replicates'] = $profile['replicates'];

             $ref = $profile['reference'];



             $addArr[$profile['id']]['refscope'] = $profile['reference'];
        }

        return $addArr;
    }

    function removeByAssay($assayId){

        $this->AssayProfile->where('assay', $assayId);
        $results = $this->AssayProfile->search();

        if(!empty($results)){
            foreach($results as $profile){
                $this->AssayProfile->id = $profile['id'];
                $this->AssayProfile->hidden = 1;
                $this->AssayProfile->save();
                $this->AssayProfile->deepFreed();
                //$this->AssayProfile->remove();
            }
        }

    }

    function removeByProfile($profileId){

        $this->AssayProfile->where('research_profile', $profileId);
        $results = $this->AssayProfile->search();

        if(!empty($results)){

            foreach($results as $profile){
                $this->AssayProfile->id = $profile['id'];
                $this->AssayProfile->remove();
            }

        }


    }

    function remove($id){
        $this->render = 0;
        $this->AssayProfile->id = $id;
        $this->AssayProfile->remove();
    }

    function validateAssayRequest(){

        //incoming:     * from javascript a list of already requested profiles and loose assays
        //              * the newly requested assay or profile

        //if nothing overlaps, return "true" and request can be done

        //if overlap is found, list the offending assays, and return their names to javascript
        //this should give the user an option to choose what to do


        //make an object containing:   NON-OFFENDING: -assays that were not added yet
        //                             OFFENDING: - assays that were there already, and in what profile,


        //if user selects add and not relace, only non-offenders willbe added
        //if users selects replace all will be added, and offending ones deleted
        $this->render = 0;




        //check if something was requested at all
        if(!isset($_POST['alreadyAssigned'])){
            $retObj['valid'] = True;
            print json_encode($retObj, True);
            return True;
        } else{
            $requestedId = $_POST['requestedId'];
            $requestedType = $_POST['requestedType'];
            $alreadyAssigned = $_POST['alreadyAssigned'];


            if(!isset($_POST['excluded'])){
                $excluded = array();
            } else{
                $excluded = $_POST['excluded'];
            }

        }



        //setup check array
        $assaysToCheck = array();

        //fetch assays if its a profile
        if($requestedType == 'profile'){
            $this->AssayProfile->where('research_profile', $requestedId);
            $this->AssayProfile->where('hidden', 0);
            $results = $this->AssayProfile->search();

            if(empty($results)){
                //does not matter, we can go trough with the request
                //return True;
            } else{
                foreach($results as $arbId => $potentialAssay){
                    if(is_array($potentialAssay)){
                        $assaysToCheck[$potentialAssay['assay']] = $potentialAssay;
                    }
                }
            }
        }
        //directly search for requested assay
        else{
            $assaysToCheck[$requestedId] = $requestedId;
        }



        unset($results);
        unset($arbId);
        unset($potentialAssay);
        //parray($assaysToCheck);
        //parray($alreadyAssigned);

        $addable = array();
        $offending = array();
        $replacers = array();

        foreach($assaysToCheck as $toAdd => $addInfo){

            $foundOffending = false;
            $foundOffendingInFollow = false;
            $idFound = false;

            foreach($alreadyAssigned as $followId => $commitedAssay){



                if(!is_array($commitedAssay) || !array_key_exists('resType', $commitedAssay)){
                    continue;
                }

                //single assay search
                if($commitedAssay['resType'] == 'assay'){
                    if($commitedAssay['assayId'] == $toAdd){
                        $foundOffending = True;
                        $foundOffendingInFollow = $followId;
                    }
                }

                //profile search
                if($commitedAssay['resType'] == 'profile'){
                    //get all profiles
                    $this->AssayProfile->free();
                    $this->AssayProfile->where('research_profile', $commitedAssay['resId']);
                    $this->AssayProfile->where('hidden', 0);
                    $results = $this->AssayProfile->search();

                    if(empty($results)){
                        //does not matter, this profile is empty!
                        continue;
                    } else{
                        foreach($results as $arbId => $potentialAssay){
                            if($potentialAssay['assay'] == $toAdd ){

                                $ignored = False;
                                if(array_key_exists($followId, $excluded) ){
                                    if(in_array($potentialAssay['id'], $excluded[$followId])){
                                        $ignored = True;
                                    }
                                }

                                if($ignored == False){
                                    $foundOffending = True;
                                    $foundOffendingInFollow = $followId;
                                    $idFound = $potentialAssay['id'];
                                }
                            }
                        }
                    }
                }
            }

            //decide what to do here
            if($foundOffending == false){

                if($requestedType == 'profile'){
                    array_push($addable, $toAdd);
                } else{
                    array_push($addable, True);
                }

            } else{

                if($requestedType == 'profile'){
                     //array_push($offending[$foundOffendingInFollow], $toAdd);
                    if(!array_key_exists($foundOffendingInFollow, $offending)){
                        $offending[$foundOffendingInFollow] = array();
                    }

                    if(!array_key_exists($foundOffendingInFollow, $replacers)){
                        $replacers[$foundOffendingInFollow] = array();
                    }

                    array_push($offending[$foundOffendingInFollow], $addInfo['id']);
                    array_push($replacers[$foundOffendingInFollow], $idFound);
                }  else{
                    array_push($offending, True);
                }
            }
        }

        //return object
        $retObj = array();

        //everything is sorted, if no offending assays were found we can silently continue
        if(count($offending) == 0){
            $retObj['valid'] = True;
        } else{
            $retObj['valid'] = False;
            $retObj['addable'] = $addable;
            $retObj['offending'] = $offending;
            $retObj['replacers'] = $replacers;
        }

        print json_encode($retObj, True);
    }

    function doAssayRevision($old, $new){

        $this->AssayProfile->where('assay', $old);        
        $this->AssayProfile->where('hidden', 0);
        $results = $this->AssayProfile->search();

        if(!empty($results)){
            foreach($results as $profile){

                $this->AssayProfile->id = $profile['id'];                
                $this->AssayProfile->hidden = '1';
                $this->AssayProfile->save();
                $this->AssayProfile->id = False;
                unset($this->Assay->id);
                $this->AssayProfile->deepFreed();
                
                $this->AssayProfile->research_profile = $profile['research_profile'];
                $this->AssayProfile->assay = $new;
                $this->AssayProfile->dillutions = $profile['dillutions'];
                $this->AssayProfile->replicates = $profile['replicates'];
                $this->AssayProfile->reference = $profile['reference'];                
                $this->AssayProfile->project_order = $profile['project_order'];
                $this->AssayProfile->conf_trip = $profile['conf_trip'];
                $this->AssayProfile->reference_source = $profile['reference_source'];                
                $this->AssayProfile->hidden = '0';
                $this->AssayProfile->save();
                $this->AssayProfile->deepFreed();
            }
        }
    }


    function moveDown($profile, $id){
      $this->render = False;
      $this->AssayProfile->where('research_profile', $profile);
      $this->AssayProfile->where('hidden', '0');
      $this->AssayProfile->order('project_order', 'ASC');

      $result = $this->AssayProfile->search();
      $this->AssayProfile->deepFreed();

      foreach($result as $idx => $assay){

            if($assay['assay'] == $id){

                $nextUp = $idx + 1;

                if(array_key_exists($nextUp, $result)){
                  $curAdj =  $assay['project_order'] + 1;
                  $this->AssayProfile->id = $assay['id'];
                  $this->AssayProfile->project_order = $curAdj;
                  $this->AssayProfile->save();
                  $this->AssayProfile->deepFreed();

                  $assay = $result[$nextUp];
                  $curAdj =  $assay['project_order'] - 1;
                  $this->AssayProfile->id = $assay['id'];
                  $this->AssayProfile->project_order = $curAdj;
                  $this->AssayProfile->save();
                  $this->AssayProfile->deepFreed();

                }
            }
      }
    }

    function moveUp($profile, $id){
      $this->render = False;
      $this->AssayProfile->where('research_profile', $profile);
      $this->AssayProfile->where('hidden', '0');
      $this->AssayProfile->order('project_order', 'ASC');

      $result = $this->AssayProfile->search();
      $this->AssayProfile->deepFreed();

      foreach($result as $idx => $assay){

            if($assay['assay'] == $id){

                if($assay['project_order'] == 1){
                  return;
                }

                $nextUp = $idx - 1;

                if(array_key_exists($nextUp, $result)){
                  $curAdj =  $assay['project_order'] - 1;
                  $this->AssayProfile->id = $assay['id'];
                  $this->AssayProfile->project_order = $curAdj;
                  $this->AssayProfile->save();
                  $this->AssayProfile->deepFreed();

                  $assay = $result[$nextUp];
                  $curAdj =  $assay['project_order'] + 1;
                  $this->AssayProfile->id = $assay['id'];
                  $this->AssayProfile->project_order = $curAdj;
                  $this->AssayProfile->save();
                  $this->AssayProfile->deepFreed();

                }
            }
          }
    }


    function renderAllAssays($id){

        $this->AssayProfile->where('research_profile', $id);
        $this->AssayProfile->where('hidden', '0');
        $this->AssayProfile->order('project_order', 'ASC');

        

        $results = $this->AssayProfile->search();
        $render = '';

        if(empty($results)){
          $this->doNotRenderHeader = True;
          $this->render = False;
          $sendObj['html'] = '';
          $sendObj['javascript'] = json_encode(array(), JSON_FORCE_OBJECT);
          print json_encode($sendObj, JSON_FORCE_OBJECT);

        } else{

            $data = array();
            foreach($results as $assay){

                $assayInfo = pa('assays', 'fetchSingle', array($assay['assay']));

                $data[$assay['assay']] = array();
                
                if(isset($_POST['showid']) &&  filter_var($_POST['showid'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === True)
                {
                    $data[$assay['assay']]['name'] = $assayInfo['id'] . ' ' . $assayInfo['name'];
                }

                else
                {
                    $data[$assay['assay']]['name'] = $assayInfo['name'];
                }
                


                $dillPrint = '';

                if($assayInfo['type'] == 4){
                    $dillArr = array();
                    $data[$assay['assay']]['is_meta_assay'] = 1;
                } else{
                    $dillArr = json_decode($assay['dillutions'], JSON_FORCE_OBJECT);

                    if(!is_array($dillArr))
                    {
                        $dillArr = array();
                    }
                }

                foreach ( $dillArr as $dil => $dilText) {
                    $dillPrint .= $dil . '=' . $dilText . PHP_EOL;
                }


                $data[$assay['assay']]['dillution'] = $dillPrint;
                $data[$assay['assay']]['replicates'] = $assay['replicates'];
                $data[$assay['assay']]['conf_trip'] = $assay['conf_trip'];
                $data[$assay['assay']]['reference_source'] = $assay['reference_source'];
                $data[$assay['assay']]['order'] = $assay['project_order'];

                $refArr = json_decode($assay['reference'], True);

                if(!is_array($refArr))
                {
                    $refArr = array();
                }

                foreach($refArr as $field=>$value){
                    $data[$assay['assay']][$field] = $value;
                }
            }

            $render = $this->renderAssayRequest($data);

            $this->doNotRenderHeader = True;
            $this->render = False;
            $sendObj['html'] = $render;
            $sendObj['javascript'] = json_encode($data, JSON_FORCE_OBJECT);
            print json_encode($sendObj, JSON_FORCE_OBJECT);

        }
    }

    private static function sortById($x, $y) {
        return $x['order'] - $y['order'];
    }



    function renderAssayRequest($data = False) {
        
        $this->doNotRenderHeader = True;
        $sources = upa('referenceSources', 'list', array(False), False);
        $render = '';

        if($data == False){
            $data = $_POST['data'];
            $this->doNotRenderHeader = True;
        }  else{
            $this->render = False;
        }        

        $preventSort = false; 

        if(array_key_exists('prevent_sorting', $_POST))
        {
            $preventSort = filter_var($_POST['prevent_sorting'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if(!$preventSort)
        {
            uasort($data, array('assayProfilesController','sortById'));
        }


        foreach ($data as $id =>  $req) {

            if (!empty($req)) {

                $refValues = '';
                foreach ($req as $postName => $postValue) {
                    if (substr($postName, 0, 4) == 'ref_') {
                        //$refValueArr[substr($postName, 4)] = $postValue;
                        $refValues .= substr($postName, 4) . ' = ' . $postValue;
                    }
                }

                $render .= '<tr id="assay_' . $id . '" assayId="' . $id  . '">';

                //position
                if(!$preventSort)
                {
                    $render .= '<td><a class="btn btn-mini up" ><i class="icon icon-chevron-up" ></i></a>
                    <a class="btn btn-mini down" ><i class="icon icon-chevron-down" ></i></a></td>';
                }
                

                $render .= '<td>' . $req['name'] . '</td>';
                $dillPrint = '';

                if(isset($req['is_meta_assay'])){
                    $dillutionArr = array();
                } else{
                    $dillutionArr = dillutionTextToArray($req['dillution']);
                }

                foreach ($dillutionArr as $dil => $dilText) {
                    $dillPrint .= $dil . '(' . $dilText . ') <br />';
                }


                $render .= '<td>' . $dillPrint . '</td>';
                $render .= '<td>' . $req['replicates'] . '</td>';
                $render .= '<td>' . $refValues . '</td>';
                $render .= '<td>'  . checkKeyOrBlank($sources, $req['reference_source']) . '</td>';
                $render .= '<td>' . $req['conf_trip'] . '</td>';
                
                if(!$preventSort)
                {
                    $render .= '<td><i class="icon-trash" onClick="removeAssay(\'' . $id . '\')"></i>  <i class="icon-pencil" onClick="editAssay(\'' . $id . '\')"></i>  </td>';
                }
                
                $render .= '</tr>';
            }
        }

        if($this->render == True){
            $this->_template->set('render', $render);
        } else {
            return $render;
        }
    }

    function forceUpdateAssays($data, $profileId){

        $this->render = 0;

        //remove all previous entrys for this profile id
        $this->AssayProfile->where('research_profile', $profileId );
        $results = $this->AssayProfile->search();

        if(!empty($results)){
            foreach($results as $prior){
                //disable the previous assays,needs to be retained for
                //samples already suing it.
                $this->AssayProfile->id = $prior['id'];
                $this->AssayProfile->hidden = 1;
                $this->AssayProfile->save();
            }
        }
        //free up
        unset($this->AssayProfile->id);
        $this->AssayProfile->free();

        if(!isset($data) || empty($data)){
            return;
        }

        //save new arrivals
        $orderCounter = 0;
        foreach($data as $assay_id => $assay){

            if(empty($assay)){
                continue;
            }

            $refValueArr = array();

            if(isset($assay['is_meta_assay']) && $assay['is_meta_assay'] == '1'){
                $dillutionArr = array();
            } else{
                $dillutionArr = dillutionTextToArray($assay['dillution']);
            }


            foreach($assay as $varName => $varValue){
                if(substr($varName, 0, 4) == 'ref_'){
                    $refValueArr[$varName] = $varValue;
                }
            }

            $referenceJSON = json_encode($refValueArr, JSON_FORCE_OBJECT);
            $this->AssayProfile->research_profile = $profileId;
            $this->AssayProfile->assay = $assay_id;
            $this->AssayProfile->dillutions = json_encode($dillutionArr, JSON_FORCE_OBJECT);
            $this->AssayProfile->replicates = $assay['replicates'];
            $this->AssayProfile->reference =  $referenceJSON;
            $this->AssayProfile->conf_trip = $assay['conf_trip'];
            $this->AssayProfile->hidden = 0;
            $this->AssayProfile->project_order = $assay['order'];
            $this->AssayProfile->reference_source = $assay['reference_source'];
            
            $this->AssayProfile->save();
            $orderCounter++;
        }

    }

    function saveProfileAssays($data, $profileId){

        $this->render = 0;

        if(!isset($data) || empty($data)){
            return;
        }

        //save new arrivals
        $orderCounter = 0;
        foreach($data as $assay_id => $assay){

            if(empty($assay)){
                continue;
            }

            $refValueArr = array();

            if(isset($assay['is_meta_assay']) && $assay['is_meta_assay'] == '1'){
                $dillutionArr = array();
            } else{
                $dillutionArr = dillutionTextToArray($assay['dillution']);
            }


            foreach($assay as $varName => $varValue){
                if(substr($varName, 0, 4) == 'ref_'){
                    $refValueArr[$varName] = $varValue;
                }
            }

            $referenceJSON = json_encode($refValueArr, JSON_FORCE_OBJECT);
            $this->AssayProfile->research_profile = $profileId;
            $this->AssayProfile->assay = $assay_id;
            $this->AssayProfile->dillutions = json_encode($dillutionArr, JSON_FORCE_OBJECT);
            $this->AssayProfile->replicates = $assay['replicates'];
            $this->AssayProfile->reference =  $referenceJSON;
            $this->AssayProfile->conf_trip = $assay['conf_trip'];
            $this->AssayProfile->project_order = $assay['order'];
            $this->AssayProfile->reference_source = $assay['reference_source']; 
            $this->AssayProfile->save();
            $orderCounter++;
        }
    }


    function reorderAll(){

        $this->render = False;

        $this->AssayProfile->where('hidden', 0);
        $this->AssayProfile->order('id', 'ASC');

        $results = $this->AssayProfile->search();

        $currGroup = False;
        $newGroup = False;
        $i = 1;

        foreach($results as $ap){

          if($currGroup == False){
              $currGroup =  $ap['research_profile'];
          }

          if($currGroup != $ap['research_profile'] ){
            $currGroup = $ap['research_profile'];
            $i = 1;
          }

          $this->AssayProfile->id = $ap['id'];
          $this->AssayProfile->project_order = $i;
          $this->AssayProfile->save();
          $this->AssayProfile->deepFreed();
          $i++;
        }
    }

    function grabAll()
    {
        $this->AssayProfile->where('hidden', '0');
        $this->AssayProfile->order('research_profile', 'ASC');
        $this->AssayProfile->order('project_order', 'ASC');        
        $results = $this->AssayProfile->search();

        return $results; 
    }
    


    function importSettings()
    {

        $this->render = False; 

        $assays = $this->loadAssayOutputs(); 

        $row = 0;
        $headers = array(); 

        if (($handle = fopen(ROOT . "/app/private/profiles.csv", "r")) !== FALSE) 
        {

         
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
                $num = count($data);

                if($row === 0)
                {                
                    $headers = $data; 
                    $row++;
                    continue; 
                }
                
                $profileId = $data[1];
                $assayId  = $data[4];
                $ref_value = trim($data[6]);
                $ref_source = $data[8];

                $ap = new AssayProfile; 
                
                $ap->where('research_profile', $profileId);
                $ap->where('assay', $assayId);             
                $ap->where('hidden', '0');
                $ap = $ap->first();

                if(!is_null($ap))
                {

                    $resultin = checkKeyOrNULL($assays, $assayId, 'custom_fields', 'resultin');

                    if(is_null($resultin))
                    {
                        echo sprintf('Could not find assay info, please manually check assay %d in research profile %d <br />', $assayId, $profileId);                        
                        continue; 
                    }

                    $setReferences = json_decode($ap['reference'], JSON_OBJECT_AS_ARRAY);

                    $setReferences = [];

                    $nap = new AssayProfile; 
                    $nap->arrayToModel($ap);
                    $nap->id = $ap['id'];

                    if(strlen($ref_value) == 0)
                    {
                                                

                        $setReferences['ref_kve'] = '';

                        $ref_source = '%null%';
                                            
                    }

                    else
                    {


                        $setReferences['ref_kve'] = $ref_value; 
                                
                    }

                    $nap->reference = json_encode($setReferences);
                    $nap->reference_source = $ref_source;                                             

                    $nap->save(); 

                }

                else
                {                 
                    echo sprintf('Did not find assay %d in research profile %d <br />', $assayId, $profileId);
                    
                }
                
             
              
                $row++;
            }   

            fclose($handle);
        }

    }

    private function loadAssayOutputs()
    {
        $a = new Assay; 

        $a->select(['id', 'custom_fields']);
        
        $assays= $a->search();
        
        array_walk($assays, function(&$value, &$key) use(&$assays) {
            
            $assays[$key]['custom_fields'] = json_decode($value['custom_fields'], JSON_OBJECT_AS_ARRAY);
        });

        

        return array_column($assays, null, 'id');


    }

    private function createSourceArray()
    {

    }


}
