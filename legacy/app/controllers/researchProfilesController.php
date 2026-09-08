<?PHP

class researchProfilesController extends controller {

    var $pForm;


    function fetchTip($id){

        $this->ResearchProfile->where('id', $id);
        $this->ResearchProfile->limit(1);
        $result = $this->ResearchProfile->search();

        if(empty($result)){
            return false;
        } else{
            $oriId = $result[0]['original_id'];
            $this->ResearchProfile->free();
            $this->ResearchProfile->limit(1);
            $this->ResearchProfile->where('original_id', $oriId);
            $this->ResearchProfile->order('id', 'DESC');
            $tipResult = $this->ResearchProfile->search();
            return $tipResult[0]['id'];
        }

    }

    public function fetchProfileTip($id){
        $this->ResearchProfile->where('id', $id);
        $this->ResearchProfile->insertOR();
        $this->ResearchProfile->where('original_id', $id);
        $this->ResearchProfile->order('id', 'DESC');
        $this->ResearchProfile->limit(1);
        $result = $this->ResearchProfile->search();        
        
        if(empty($result)){
            return $id; 
        } else{
            return $result[0]['id'];
        }

    }

    /*
    function createProfileFixer(){

        //header( 'Content-Type: text/csv;charset=utf-8' );
        //header( 'Content-Disposition: attachment;filename=lijst.csv');
        $fh = fopen(ROOT . '/app/private/scratch/fixer.tsv' , 'w');

        $this->render = false;
        $results = $this->ResearchProfile->search();

        fputcsv($fh, array('id', 'original_id', 'name', 'global', 'client', 'active', 'client_name'));

        foreach($results as $profile){

            if($profile['global'] == '0'){
                @$client = upa('clients', 'fetch', array($profile['client']));
                if(empty($client)){
                    fputcsv($fh, array($profile['id'], $profile['original_id'], $profile['name'], '1', '0', $profile['active'], '0'));
                } else{
                    fputcsv($fh, array($profile['id'], $profile['original_id'], $profile['name'], $profile['global'], $profile['client'], $profile['active'], $client['name']));
                }
            } else{
                fputcsv($fh, array($profile['id'], $profile['original_id'], $profile['name'], $profile['global'], $profile['client'], $profile['active'], '0'));
            }
        }
    }

    function importProfilesFromFixer(){

        $this->render = False;
        $fh = fopen(ROOT . '/app/private/scratch/fixReport.tsv' , 'w');
        fputcsv($fh, array('profiel naam', 'naam klant oude bestand', 'naam klant nieuwe bestand', 'uitslag'));

        if (($handle = fopen(ROOT . '/app/private/scratch/fixer.tsv', "r")) !== FALSE) {

            $first  = True;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {


                $process = True;
                if($first == True){
                    $first = False;
                    continue;
                }

                if($data[3] == '0'){
                    $client = upa('clients', 'predictForFixer', array($data[6]));

                    if(empty($client)){
                        //failed to find client
                        fputcsv($fh, array($data[2], $data[6], 'Niet gevonden', 'Mislukt'));
                        $process = false;
                    } else{

                        if(count($client) > 1){
                            //failed, found multiple possible clients
                            fputcsv($fh, array($data[2], $data[6], 'Meer dan 1 klant match gevonden', 'Mislukt'));
                            $process = false;
                        } else{
                            //success
                            fputcsv($fh, array($data[2], $data[6], $client[0]['name'], 'Success'));
                            $process =true;
                        }
                    }
                } else{
                    fputcsv($fh, array($data[2], 'Globaal profiel', 'Globaal profiel', 'Success'));
                    $process = true;
                }

                if($process == True){
                    $this->ResearchProfile->original_id = $data[1];
                    $this->ResearchProfile->name = $data[2];
                    $this->ResearchProfile->global = $data[3];

                    if($data[3] == '0'){
                        $this->ResearchProfile->client = $client[0]['id'];
                    } else{
                        $this->ResearchProfile->client = '0';
                    }

                    $this->ResearchProfile->active = $data[5];
                    $this->ResearchProfile->save($data[0]);
                } else{
                    $this->ResearchProfile->original_id = $data[1];
                    $this->ResearchProfile->name = $data[2];
                    $this->ResearchProfile->global = '1';
                    $this->ResearchProfile->client = '0';
                    $this->ResearchProfile->active = $data[5];
                    $this->ResearchProfile->save($data[0]);
                }

            }
            fclose($handle);
        }
    } */

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function fetchProfile($profileId){
        $this->ResearchProfile->where('id', $profileId);
        $result = $this->ResearchProfile->search();
        return $result['0'];
    }

    function getProfileList($includeInactive = False){

        if($includeInactive === True)
        {
            //...
        }

        else
        {
            $this->ResearchProfile->where('active', 1);
        }
        
        $result = $this->ResearchProfile->search();
        return $result;
    }

    private function profileForm() {

        $this->pForm = new formFactory('researchProfiles');
        $this->pForm->setId('profileForm');
        $this->pForm->addClass('');
        $this->pForm->action('');
        $this->pForm->method('POST');
        $this->pForm->setTemplate('generic');

        $this->pForm->addInputField('pname', '{MESA_ERP_NAME}', 'text', 'input-block-level', $this->ResearchProfile->name, '{MESA_ERP_NAME}', False, False);
        //$this->pForm->addValidation('name', 'NO_DUPLICATE');

        $boolOp['1'] = '{MESA_ERP_GLOBAAL}';
        $boolOp['0'] = '{MESA_ERP_CLIENTSPECIFIC}';
        $this->pForm->addDropdownField('global', '{MESA_ERP_PROFILETYPE}', 'input-block-level', $this->ResearchProfile->global, $boolOp, False);

        if ($this->ResearchProfile->client != 0) {
            $clientName = customerIdToName($this->ResearchProfile->client);
        } else {
            $clientName = False;
        }

        $this->pForm->addInputField('forceChanges', False, 'hidden', False, '0', False);
        $this->pForm->addInputField('original_id', False, 'hidden', False, $this->ResearchProfile->original_id, False);
        $this->pForm->addInputField('client_name', '{MESA_ERP_FORCLIENT}', 'text', 'ajax-typeahead input-block-level ', $this->ResearchProfile->client, 'Start typing client name', array('autocomplete' => 'off'));
        $this->pForm->addInputField('client', False, 'hidden', 'hidden', False, $this->ResearchProfile->client, '');

        $boolOp['1'] = 'Ja';
        $boolOp['0'] = 'Nee';
        $this->pForm->addDropdownField('portal_visible', 'Zichtbaar in client portal?', 'input-block-level', $this->ResearchProfile->portal_visible, $boolOp, False);

        $this->pForm->addDropdownField('lims_visible', 'Zichtbaar in profiel-selectie LIMS?', 'input-block-level', $this->ResearchProfile->lims_visible, $boolOp, False);
    }

    function listing() {

        
        $this->ResearchProfile->where('active', 1);

        $client = new Client();
        $activeClients = $client->getAllActive();

        $activeClients = array_column($activeClients, null, 'id');        
        $results = $this->ResearchProfile->search();        

        if (empty($results)) 
        {
            $results = '{MESA_ERP_NOPROFILES}';
        }

        foreach($results as $idx => $result)
        {

            $active = True; 

            if($result['client'] != '0')
            {
                $active = (array_key_exists($result['client'], $activeClients)) ? True: False;
            }
                        
            if($active == False)
            {
                unset($results[$idx]);
            }
        }

        $tF = new tableFactory();
        $tF->setTableId('profileTable');
        $tF->loadTemplate('researchProfileListing');
        $tF->loadValues($results);
        $tF->specifyMod('client', 'researchProfileScope', array(ALPC_TF_SELF));

        $this->_template->set('available_profiles', $tF->renderTable());

        $this->profileForm();
        $this->pForm->action('{LB}/researchProfiles/saveProfileInfo');
        $this->pForm->submitTrough('saveProfileButton');
        $this->_template->set('profile_form', $this->pForm->render());
    }


    function saveProfileInfo() {

        $this->render = 0;

        $this->ResearchProfile->name = $_POST['pname'];
        $this->ResearchProfile->portal_visible = $_POST['portal_visible'];
        


        if ($_POST['global'] == '1') {
            $this->ResearchProfile->global = '1';
            $this->ResearchProfile->client = '0';
        } else {
            $this->ResearchProfile->global = '0';
            $this->ResearchProfile->client = $_POST['client'];
        }

        $this->ResearchProfile->save();
        $id = $this->ResearchProfile->lastInsertId;

        //save ori id
        $this->ResearchProfile->id = $id;
        $this->ResearchProfile->original_id = $id;
        $this->ResearchProfile->save();
        $this->reRoute('researchProfiles/edit/' . $id, True);
    }



    function attachedAssays($id, $print = False){

        $attAssays = pa('assayProfiles', 'fetchAnalysis', array($id));

        if(empty($attAssays)){
            $attAssays = '{no_assays_attched}';
        }

        $aT = new tableFactory();
        $aT->loadTemplate('assayProfileList');

        $aT->loadValues($attAssays);

        if($print == False){
            return $aT->renderTable();
        } else {
            $this->render = 0;
            print $aT->renderTable();
        }
    }


    function edit($id = False){

        if($id == False){
            $this->reRoute('researchProfiles/listing', True);
            return;
        }

        if($id != False){
            $this->ResearchProfile->id = $id;
            $result = $this->ResearchProfile->search();

            if(empty($result)){
                $this->reRoute('researchProfiles/listing', True);
                return;
            }

            $this->ResearchProfile->arrayToModel($result[0]);
        }


        //global information form
        $this->profileForm();

        //load available assays
        $assaysAvail = upa('assays', 'fetchAssaysList', array());

        //$this->_template->set('assays_available', $assaysAvail);

        $this->_template->set('matrix_content', upa('matrix', 'matrixDropdown', array(), False));
        
        $this->_template->set('client', $this->ResearchProfile->client);
        $this->_template->set('profile_form', $this->pForm->render());

        $revisionInfo = $this->_fetchRevisionInfo($this->ResearchProfile->original_id, $this->ResearchProfile->id);
        $revDropdown = $revisionInfo['dropdown'];
        $revIsTip = $revisionInfo['isTip'];

        if($revIsTip == True){
            $this->_template->set('revision_istip', '1');
        } else{
            $this->_template->set('revision_istip', '0');
        }

        $this->_template->set('revision_dropdown', $revDropdown);
        $this->_template->set('id', $id);
    }



    //this is ONLY for already created profiles1
    function updateProfile($id){
        
        
        $this->render = False; 

        
        $this->ResearchProfile->id = $id;
        $forceChange = False;

        if(isset($_POST['forceChanges']) && $_POST['forceChanges'] == 1){
            $forceChange = True;
        }

        $this->ResearchProfile->name = $_POST['name'];
        $this->ResearchProfile->original_id = $_POST['original_id'];
        $this->ResearchProfile->portal_visible = $_POST['portal_visible'];
        $this->ResearchProfile->lims_visible = $_POST['lims_visible'];

        if ($_POST['global'] == '1') {
            $this->ResearchProfile->global = '1';
            $this->ResearchProfile->client = '0';
        } else {
            $this->ResearchProfile->global = '0';
            $this->ResearchProfile->client = $_POST['client'];
        }

        //for changes in this profile..
        if($forceChange == True){
            
            $this->ResearchProfile->save();
            //remove all associated assay profiles, and save them a new
            if(isset($_POST['data'])){
                $toSend = $_POST['data'];
            } else {
                $toSend = array();
            }


            upa('assayProfiles', 'forceUpdateAssays', array( $toSend, $this->ResearchProfile->id));
            $goTo = $this->ResearchProfile->id;
        }

        //update profile, dont overwrte
        else{
            $oldId = $this->ResearchProfile->id;
            
            unset($this->ResearchProfile->id);

            $this->ResearchProfile->save();
            
            $goTo = $this->ResearchProfile->lastInsertId;
            upa('assayProfiles', 'saveProfileAssays', array($_POST['data'], $goTo));
            $this->_deactiveProfile($oldId);
        }

        print $goTo;
        //$this->reRoute('researchProfiles/edit/' . $goTo, True);
    }

    function updateAssayForm ($id, $assayId, $client){
        $this->doNotRenderHeader = True;
        $form = $this->createAddAssayForm($id, $assayId, $client);
        $this->_template->set('render', $form);
    }

    function createAddAssayForm($id, $assayId, $client ){        
        
        //assay add dialog
        $sForm = new formFactory('assayFields');
        $sForm->setId('addAssayForm');
        $sForm->addClass('');
        $sForm->action('');
        $sForm->method('POST');
        $sForm->setTemplate('generic');
        $sForm->addInputField('research_profile', '', 'hidden', False, $id, False);

        $referenceSources = upa('referenceSources', 'list', array($client), False);

        $assayInfo = upa('assays', 'fetch', array($assayId));
        $sForm->addInputField('name', '{MESA_ERP_ASSAYTOADD}', 'text', 'input-block-level', $assayInfo['name'], False, array('disabled' => 'disabled'), False);

        $defaultDillution = '-1=0.1&#10;-2=0.01&#10;-3=0.001&#10;-4=0.0001';
        if($assayInfo['dillution'] == 1){
            $sForm->addTextArea('dillution', '{MESA_ERP_DILUTIONSETUP}', 'text', 'input-block-level' , $defaultDillution, '{MESA_ERP_DILUTIONSETUP}', array('rows' => '5'));
        } else{
            $sForm->addInputField('dillution', '', 'hidden', 'hidden', '', False);
        }

        $sForm->addDropdownField('reference_source', 'Referentie Bron', 'input-block-level', NULL, $referenceSources, False);

        if($assayInfo['replicates'] == 1){
           $sForm->addInputField('replicates', '{MESA_ERP_REPLICATES}', 'text', 'input-block-level' , '0', '{MESA_ERP_REPLICATES}');
        } else{
            $sForm->addInputField('replicates', '', 'hidden', 'hidden', '0', False);
        }

        if($assayInfo['type'] == 4){
            $sForm->addInputField('is_meta_assay', '', 'hidden', 'hidden', '1', False);
        }

        $restrict = [1,4,6,9];
        $restrictClass = '';

        if(in_array($assayInfo['type_base'], $restrict))
        {
            $restrictClass = ' numerical-only-filter';
        }

        $outputs = pa('results', 'fetchOutputFields', array($assayId) );

        foreach($outputs as $outputName => $dummyOutput){            
              $sForm->addInputField('ref_' . $outputName, '{MESA_ERP_REFERENCEVALUEFOR} ' . $outputName, 'text', 'input-block-level input-reference-field' . $restrictClass, '0', '{MESA_ERP_REFERENCEVALUE}');
        }

        $sForm->addInputField('conf_trip', 'Bevestigen boven ( 0 = Gebruik analyse instelling; waarde > 0 =  bevestigen boven waarde, anders automatisch niet)', 'text', 'input-block-level', '0', False, array(), False);

        return $sForm->render();
    }

    function fetchLegionellaProfiles($array = False, $dualArr = False){
        $this->ResearchProfile->where('global', '1');    
        $this->ResearchProfile->order('name', 'ASC');
        $results = $this->ResearchProfile->search();
        
        
        if(empty($results)){
            return False;
        }

        $acceptable = json_decode(LEGIONELLA_ALLOWED_PROFILES);
        $accepted = [];
        foreach($results as $result){
            
            if(in_array($result['id'], $acceptable)){
                array_push($accepted, $result);
            }
                        
        }

        if($array == True){            
            return $accepted;
        }

        $list = '';
        $options = '';

        $matrixA = json_decode(LEGIONELLA_PROFILES_IN_MATRIX_A);
        $matrixB = json_decode(LEGIONELLA_PROFILES_IN_MATRIX_B);
        $matrixC = json_decode(LEGIONELLA_PROFILES_IN_MATRIX_C);

        $firstType = False;
        $default = LEGIONELLA_STD_MATRIX;

        foreach($accepted as $result){

            $matrixType = 'A';
            $selected = '';

            if(in_array($result['id'], $matrixA)){
                $matrixType = 'A';
            }

            elseif(in_array($result['id'], $matrixB)){
                $matrixType = 'B';
            }

            elseif(in_array($result['id'], $matrixC)){
                $matrixType = 'C';
            }

            else{
                continue;
            }

            if((int)$result['id'] === (int)$default){
                $firstType = $matrixType;
                $selected = 'selected="selected"';
            }

            //set first in list type
            if($firstType === False){
                $firstType = $matrixType;
            }

            $list .= generateHTML('sampleEntry/resProfLine', $result);
            $options .= '<option value="' . $result['id'] . '" data-matrix-type="' . $matrixType .'" ' . $selected .'>' . $result['name'] . "</option>";
        }

        

        if($dualArr == false){
            return $list;
        } else{
            return array('html' => $list, 'options' => $options, 'firstType' => $firstType);
        }


    }

    function fetchGlobalProfiles($array = False, $dualArr = False){
        $this->ResearchProfile->where('global', '1');
        $this->ResearchProfile->where('active', 1);
        $this->ResearchProfile->where('lims_visible', 1);
        $this->ResearchProfile->order('name', 'ASC');
        $results = $this->ResearchProfile->search();

        if(empty($results)){
            return False;
        }

        if($array == True){
            return $results;
        }

        $list = '';
        $options = '';


        foreach($results as $result){
            $list .= generateHTML('sampleEntry/resProfLine', $result);
            $options .= '<option value="' . $result['id'] . '">' . $result['name'] . "</option>";
        }

        if($dualArr == false){
            return $list;
        } else{
            return array('html' => $list, 'options' => $options);
        }
    }

    function fetchCustomerProfiles($client, $array = False, $json = False){
        $this->render = 0;
        $this->ResearchProfile->where('client', $client);
        $this->ResearchProfile->where('active', 1);
        $this->ResearchProfile->where('lims_visible', 1);

        $this->ResearchProfile->order('name', 'ASC');
        $results = $this->ResearchProfile->search();
    
        usort($results, function($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });
    
        if(empty($results)){
            if($json == False){
              return False;
            }
        }

        if(filter_var($array, FILTER_VALIDATE_BOOLEAN) != False){
            return $results;
        }

        $list = '';
        $options =  '';

        foreach($results as $result){
            $list .= generateHTML('sampleEntry/resProfLine', $result);
            $options .= '<option value="'. $result['id'] .'">' . $result['name'] . '</option>';
        }

        if($json !== False){
            $retObj = array();
            $retObj['html'] = $list;
            $retObj['options'] = $options;
            print json_encode($retObj);
        } else{
            print $list;
        }


    }


    function renderProfileRequest($profileId, $followNumber){

        $this->doNotRenderHeader = 1;
        $this->ResearchProfile->where('id', $profileId);
        $results = $this->ResearchProfile->search();


        $keyArr['follow_no'] = $followNumber;
        $keyArr['id'] = $profileId;
        $keyArr['name'] = $results['0']['name'];

        $renderedRequest = generateHTML('sampleEntry/reqProfile',$keyArr);
        $this->_template->set('render', $renderedRequest);

    }

    function searchResearchProfiles($searchTerm, $customerId){

        $this->ResearchProfile->like('name', $searchTerm);
        $this->ResearchProfile->where('global', '0');
        $this->ResearchProfile->where('client', $customerId);
        $this->ResearchProfile->where('active', 1);

        $privateProfiles = $this->ResearchProfile->search();

        $this->ResearchProfile->free();

        $this->ResearchProfile->like('name', $searchTerm);
        $this->ResearchProfile->where('global', '1');

        $globalProfiles = $this->ResearchProfile->search();

        return array( 'private' => $privateProfiles, 'global' => $globalProfiles);

    }

    function removeByClient($clientId){

        $this->render = 0;
        $this->ResearchProfile->where('client', $clientId);
        $results = $this->ResearchProfile->search();

        if(!empty($results)){
            foreach($results as $profile){
                $this->remove($profile['id']);
            }
        }
    }


    function remove($id){

        $this->render = 0;
        $this->ResearchProfile->id = $id;
        $this->ResearchProfile->active = 0;
        $this->ResearchProfile->save();

    }


    function profileInfo($id, $follow_no){

        $this->doNotRenderHeader = 1;
        
        $attAssays = pa('assayProfiles', 'fetchAnalysis', array($id, True));

        //get research profile
        //$profiles = new ResearchProfile();
        //$profiles->where('id', $id);
        //$profile = $profiles->first();

        //dd($profile);

        if(empty($attAssays)){
            $tblLoad = 'No analysis attached to profile';
        } else{
            //neatly write dillutions and reference values here
            foreach($attAssays as $arbId => $attAssay){
                $assayInfo = pa('assays', 'fetch', array($attAssay['assay']));
                $tblLoad[$arbId] = $attAssay;
                $tblLoad[$arbId]['profile'] = $id;
                $tblLoad[$arbId]['assay_profile_id'] = $attAssay['id'];
                $tblLoad[$arbId]['follow_no'] = $follow_no;
                $tblLoad[$arbId]['assay_name'] = $assayInfo['name'];

                $tblLoad[$arbId]['assayId'] = $assayInfo['id'];
                $tblLoad[$arbId]['assaytype'] = $assayInfo['type_base'];
                $tblLoad[$arbId]['dillution'] = $assayInfo['dillution'];
                $tblLoad[$arbId]['replicates'] = $attAssay['replicates'];
                $tblLoad[$arbId]['dillutions_visual'] = dillutionToText($attAssay['dillutions'], True, True);

                $references = json_decode($attAssay['reference'], True);
                $refInText = False;
                foreach($references as $refName => $refValue){
                    $refRem = explode('_', $refName);
                    $refInText .= $refRem[1] . ' : ' . $refValue;
                }

                $tblLoad[$arbId]['reference_visual'] = $refInText;

            }
        }

        $dTbl = new tableFactory();
        $dTbl->setTableId('profileDetails');
        $dTbl->loadTemplate('profileDetails');

        $dTbl->loadValues($tblLoad);
        $this->_template->set('details', $dTbl->renderTable());

        //$this->_template->set('attached', $attAssasy);

    }



    private function _deactiveProfile($id){
        
        
        
        $this->ResearchProfile->deepFreed();
        $this->ResearchProfile->id = $id;
        $this->ResearchProfile->active = 0;
        $this->ResearchProfile->save();
    }

     private function _fetchRevisionInfo($originalId, $thisId){

       $this->ResearchProfile->free();
       unset($this->ResearchProfile->id);
       $this->ResearchProfile->where('original_id', $originalId);
       $this->ResearchProfile->order('id', 'DESC');

       $revResults = $this->ResearchProfile->search();
       $dropDown = '';
       $numberOfRevisions = count($revResults);

       $isTip = False;
       if($thisId == $revResults[0]['id']){
           $isTip = True;
       }

       foreach($revResults as $revision){
           $selected = False;
           $tipIndicator = '';

           if($revision['id'] == $thisId){
               $selected = 'selected="SELECTED"';
               if($isTip == True){
                   $tipIndicator = '({MESA_ADR_TIP})';
               }
           }


           $dropDown .= '<option ' . $selected . ' value="' . $revision['id']   .'">{MESA_ADR_REVISION}: ' . $numberOfRevisions . ' ' . $tipIndicator . '</option>' ;
           $numberOfRevisions--;
       }

       return array('dropdown'=> $dropDown, 'isTip' => $isTip);
    }


    public function doCopy()
    {
        $this->render = False; 


        if(!empty($_POST['copy_form_origin']) && !empty($_POST['copy_form_destination']))
        {
            
        
            $aps = new AssayProfile;
            $aps->where('research_profile', $_POST['copy_form_origin']);
            $aps->where('hidden', '0');
            $ap_to_copy = $aps->search(); 

            foreach($ap_to_copy as $ap)
            {
                $ap_new = new AssayProfile; 
                $ap_new->arrayToModel($ap);
                unset($ap_new->id);
                $ap_new->research_profile = $_POST['copy_form_destination'];
                $ap_new->save(); 
            }

            
        }    

        $this->reRoute('researchProfiles/edit/' . $_POST['copy_form_destination'], True);

    }

   

    public function bulkChange()
    {
        
        $this->ResearchProfile->where('active', 1);

        $assayObj = new Assay();
        $assayObj->where('active', 1);
        
        $assayOpts = '';

        foreach($assayObj->search() as $assay)
        {
            $assayOpts .= '<option value="' . $assay['id'] . '">' . $assay['id'] .': ' . $assay['name']   . '</option>';

        }

        $this->_template->set('available_assays', $assayOpts);

        $client = new Client();
        $activeClients = $client->getAllActive();

        $activeClients = array_column($activeClients, null, 'id');        
        $results = $this->ResearchProfile->search();        

        if (empty($results)) 
        {
            $results = '{MESA_ERP_NOPROFILES}';
        }

        foreach($results as $idx => $result)
        {

            $active = True; 

            if($result['client'] != '0')
            {
                $active = (array_key_exists($result['client'], $activeClients)) ? True: False;
            }
                        
            if($active == False)
            {
                unset($results[$idx]);
            }
        }

        $this->_template->set('matrix_content', upa('matrix', 'matrixDropdown', array(), False));


        $tF = new tableFactory();
        $tF->setTableId('profileTable');
        $tF->loadTemplate('researchTableBulkChange');
        $tF->loadValues($results);
        $tF->specifyMod('client', 'researchProfileScope', array(ALPC_TF_SELF));

        $this->_template->set('available_profiles', $tF->renderTable());

        $this->profileForm();
        $this->pForm->action('{LB}/researchProfiles/saveProfileInfo');
        $this->pForm->submitTrough('saveProfileButton');
        $this->_template->set('profile_form', $this->pForm->render());

    }

    public function executeBulkChange()
    {


        $profiles = explode(',', $_POST['profiles']);        
    
        if($_POST['selectedMutation'] == 'delete')
        {
            foreach($profiles as $profile)
            {
                $this->bulkDrop($profile, $_POST['primaryAssay']);
            }            
        }

        if($_POST['selectedMutation'] == 'swap')
        {
            foreach($profiles as $profile)
            {
                $this->bulkSwap($profile, $_POST['primaryAssay'], $_POST['secondaryAssay']);
            }            
        }

        if($_POST['selectedMutation'] == 'add')
        {

            $addition = json_decode($_POST['addition'], true);                        

            if(!is_array($addition))
            {
                return; 
            }

            foreach($profiles as $profile)
            {                
                $this->bulkAdd($profile, $addition);
            }            
        }


    }    

    private function loadProfile($id)
    {

        $this->ResearchProfile->deepFreed();

        $this->ResearchProfile->where('id', $id);

        $result = $this->ResearchProfile->search();

        if(empty($result))
        {
            return false; 
        }

        $profile = $result[0];

        return $profile; 
    }

    
    private function startNewCloned($data)
    {

        unset($this->ResearchProfile->id);

        $this->ResearchProfile->deepFreed();
        $this->ResearchProfile->name = $data['name'];
        $this->ResearchProfile->original_id = $data['original_id'];
        $this->ResearchProfile->portal_visible = $data['portal_visible'];
        $this->ResearchProfile->lims_visible = $data['lims_visible'];

        if ($data['global'] == '1') {
            $this->ResearchProfile->global = '1';
            $this->ResearchProfile->client = '0';
        } else {
            $this->ResearchProfile->global = '0';
            $this->ResearchProfile->client = $data['client'];
        }
    }

    private function bulkSwap($id, $old, $new)
    {

        $cloneProfile = $this->loadProfile($id);

        if($cloneProfile == false)
        {
            return;
        }        

        $this->startNewCloned($cloneProfile);

     
        //grab assays,and check first if this even needs an update. 
        $assayObj = new AssayProfile();
        $assayObj->where('research_profile', $id);
        $assayObj->where('hidden', '0');
        $assays = $assayObj->search();

        //does not have the to-swap assay, so no need to update.
        $currentAssays = array_column($assays, 'assay');
        if(!in_array($old,  $currentAssays))
        {
            return;
        }

        $this->ResearchProfile->save();            
        $goTo = $this->ResearchProfile->lastInsertId;


        foreach($assays as $assay)
        {

          

            $assayObj = new AssayProfile();
            $assayObj->research_profile =  $goTo;

            if($assay['assay'] == $old)
            {
                $assayObj->assay = $new; 
            }

            else
            {
                $assayObj->assay = $assay['assay'];
            }
            

            $assayObj->dillutions = $assay['dillutions'];
            $assayObj->replicates = $assay['replicates'];
            $assayObj->reference = $assay['reference'];
            $assayObj->hidden = '0';
            $assayObj->project_order = $assay['project_order'];
            $assayObj->conf_trip = $assay['conf_trip'];
            $assayObj->reference_source = $assay['reference_source'];
            $assayObj->save();
        }                           
        
        $this->_deactiveProfile($id);

    }
    
    private function bulkDrop($id, $drop){

        $cloneProfile = $this->loadProfile($id);

        if($cloneProfile == false)
        {
            return;
        }        

        $this->startNewCloned($cloneProfile);

     
        //grab assays,and check first if this even needs an update. 
        $assayObj = new AssayProfile();
        $assayObj->where('research_profile', $id);
        $assayObj->where('hidden', '0');
        $assays = $assayObj->search();

        //does not have the to-drop assay, so no need to update.
        $currentAssays = array_column($assays, 'assay');
        if(!in_array($drop,  $currentAssays))
        {
            return;
        }
        
        $this->ResearchProfile->save();            
        $goTo = $this->ResearchProfile->lastInsertId;


        foreach($assays as $assay)
        {

            if($assay['assay'] == $drop)
            {
                continue;
            }

            $assayObj = new AssayProfile();
            $assayObj->research_profile =  $goTo;
            $assayObj->assay = $assay['assay'];
            $assayObj->dillutions = $assay['dillutions'];
            $assayObj->replicates = $assay['replicates'];
            $assayObj->reference = $assay['reference'];
            $assayObj->hidden = '0';
            $assayObj->project_order = $assay['project_order'];
            $assayObj->conf_trip = $assay['conf_trip'];
            $assayObj->reference_source = $assay['reference_source'];
            $assayObj->save();
        }                           
        
        $this->_deactiveProfile($id);

    }

    private function bulkAdd($id, $addition)
    {

      
        $cloneProfile = $this->loadProfile($id);

        if($cloneProfile == false)
        {
            return;
        }        

        $this->startNewCloned($cloneProfile);

            
        $assayObj = new AssayProfile();
        $assayObj->where('research_profile', $id);
        $assayObj->where('hidden', '0');
        $assays = $assayObj->search();        
        
        $assayIds = array_column($assays, 'assay');
                        
        $this->ResearchProfile->save();            
        $goTo = $this->ResearchProfile->lastInsertId;
               
        $didReplacement = False; 
        $replacementN = False;
        $order = 0; 

        
        foreach($assays as $assay)
        {

            if($assay['assay'] == $addition['assay_id'])
            {
                
                $didReplacement = True; 
                $replacementN = $assay['project_order'];
                $order = $order + 1; 
                continue; 
            }

            $assayObj = new AssayProfile();
            $assayObj->research_profile =  $goTo;
            $assayObj->assay = $assay['assay'];
            $assayObj->dillutions = $assay['dillutions'];
            $assayObj->replicates = $assay['replicates'];
            $assayObj->reference = $assay['reference'];
            $assayObj->hidden = '0';
            $assayObj->project_order = $assay['project_order'];
            $assayObj->conf_trip = $assay['conf_trip'];
            $assayObj->reference_source = $assay['reference_source'];
            $assayObj->save();
            $order = $order + 1; 
        }            


        
        //process addition  
        $dillutionArr = dillutionTextToArray($addition['dillution']);

        $refValueArr = array();

        foreach($addition as $varName => $varValue){
            if(substr($varName, 0, 4) == 'ref_'){
                $refValueArr[$varName] = $varValue;
            }
        }

        $assayObj = new AssayProfile();
        $assayObj->research_profile =  $goTo;
        $assayObj->assay = $addition['assay_id'];
        $assayObj->dillutions = json_encode($dillutionArr, JSON_FORCE_OBJECT);
        $assayObj->replicates = $addition['replicates'];
        $assayObj->reference = json_encode($refValueArr, JSON_FORCE_OBJECT);
        $assayObj->hidden = '0';
        $assayObj->project_order = ($didReplacement) ? $replacementN : $order + 1;
        $assayObj->conf_trip = $addition['conf_trip'];
        $assayObj->reference_source = $addition['reference_source'];
        $assayObj->save();
        
       
        $this->_deactiveProfile($id);
   

    }



    
}
