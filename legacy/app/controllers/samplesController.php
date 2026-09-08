<?PHP

//use function PHPSTORM_META\map;
//use Project; 

class samplesController extends Controller
{

    private $projectInfo = False;
    private $newSampleId = False; 

    private function auditTrailIsNotApplicable($value)
    {
        return strtolower(trim((string) $value)) == 'nvt';
    }

    private function auditTrailDateIsOutOfSpec($value, $formDate)
    {
        if(empty($value) || $this->auditTrailIsNotApplicable($value)){
            return False;
        }

        $date = DateTime::createFromFormat('d-m-Y', $value);
        $errors = DateTime::getLastErrors();

        if($date === False || ($errors !== False && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))){
            return False;
        }

        $date->setTime(0, 0, 0);
        return $date->getTimestamp() < $formDate;
    }

    private function auditTrailMaterialIsOutOfSpec($value, $range)
    {
        $value = trim($value);
        $range = trim($range);

        if($value === '' || $range === '' || $this->auditTrailIsNotApplicable($value)){
            return False;
        }

        $numericValue = str_replace(',', '.', $value);
        if(!is_numeric($numericValue)){
            return True;
        }

        $pattern = '/(<=|>=|<|>|=)\s*(-?\d+(?:[.,]\d+)?)/';
        $remaining = preg_replace($pattern, '', $range);
        if(trim($remaining) !== ''){
            return False;
        }

        preg_match_all($pattern, $range, $matches, PREG_SET_ORDER);
        $acceptable = True;

        foreach($matches as $match){
            $bound = (float) str_replace(',', '.', $match[2]);
            $number = (float) $numericValue;

            if($match[1] == '<' && !($number < $bound)){ $acceptable = False; }
            if($match[1] == '<=' && !($number <= $bound)){ $acceptable = False; }
            if($match[1] == '>' && !($number > $bound)){ $acceptable = False; }
            if($match[1] == '>=' && !($number >= $bound)){ $acceptable = False; }
            if($match[1] == '=' && $number != $bound){ $acceptable = False; }
        }

        return !empty($matches) && !$acceptable;
    }

    private function auditTrailWasOutOfDateHere($data, $mediaId, $sampleId)
    {
        return checkKey($data, 5, 'wasOutOfDateHere', $mediaId, $sampleId);
    }

    private function auditTrailFieldNote($data, $fieldId, $isOutOfSpec)
    {
        $note = checkKeyOrFalse($data, 4, $fieldId);
        if(!$isOutOfSpec || $note === False || trim($note) === ''){
            return '';
        }

        return '<br /><em>Uitleg: ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</em>';
    }

    private function auditTrailOutOfSpecStyle($isOutOfSpec)
    {
        return $isOutOfSpec ? 'color: red;' : '';
    }



    /* Orphan function for searching both tests and packets */


    function scanAndSnap()
    {

    }

    function uploadPicture()
    {
    
    }

    
    function drop_tht_flag($sample_id)
    {

    
        $this->Sample->where('id', $sample_id);

        $this->Sample->limit('1');            
        
        $result = $this->Sample->first();
        

        if($result)
        {

            $this->Sample->arrayToModel($result);
            $this->Sample->tht_code = '%null%';
            $this->Sample->save();

            $event = 'THT code verwijderd.';
            upa('changeTracker', 'changed', array(2, $this->Sample->project, $this->Sample->id, False, $event, False, False), False);

            if(!empty($result['portal_sample_id']))
            {
                upa('portal', 'dropTht', array($result['portal_sample_id']), False);
            }

            
            
            
        }                
    }


    function developer_change()
    {
        $this->render = False; 

        $user = getUserId();

        $field = $_POST['field'];
        $value = $_POST['value'];

        if($user == '1' || $user == '9' )
        {
         
                $this->Sample->id = $_POST['id'];
                $this->Sample->$field = $value; 
                $this->Sample->save();
        }

    }

    function developer($sampleId)
    {
        $user = getUserId();
        

        if($user == '1' || $user == '9' )
        {            

        $sample = new Sample();
        $sample->where('id', $sampleId);
        $sample = $sample->first();

        if(!$sample)
        {
            print('did not find sample');
            return;
        }

        $this->_template->set('id', $sampleId);             

        $devForm = new formFactory($this->_controller);
        $devForm->setId('devform');
        $devForm->action('');
        $devForm->method('');
        
        $devForm->addClass('form-horizontal');       
        $devForm->setTemplate('generic');


        foreach($sample as $field => $value)
        {

            $jsonCheck = json_decode($value, JSON_FORCE_OBJECT);


            if(is_array($jsonCheck))
            {                               
                $devForm->addTextArea($field, $field, 'text', 'input-block-level', $value, False);                
            }

            else
            {
                $devForm->addInputField($field, $field, 'text', 'input-block-level', $value, '', False);              
            }
                

        }

        $this->_template->set('dev_form', $devForm->render());             

        $change = new ChangeTracker(); 

        $change->where('sample', $sampleId);
        
        $changes = $change->search(); 

        $changeForm = '';

        // $changeForm = new formFactory('changeTrackerController');
        // $changeForm->setId('changeform');
        // $changeForm->action('');
        // $changeForm->method('');
        
        // $changeForm->addClass('form-horizontal');       
        // $changeForm->setTemplate('generic');

        foreach($changes as $change)
        {
     
            $changeForm .= generateHTML('samples/advanced', $change);
        }
        
         $this->_template->set('chg_form', $changeForm);         
        
        }

        else
        {
            $this->render = False;

            die();
        }
     
    }

    function listOpenSamples()
    {
        $this->doNotRenderHeader = True;
                
        //create a new date object
        $cutoff = new DateTime();

        //set the date to yesterday
        $cutoff->modify('-1 day');

        //set the time to the beginning of the day
        $cutoff->setTime(0, 0, 0);

        //get the unixtimestamp
        $cutoff = $cutoff->getTimestamp();

        $sql =  'SELECT 
                    samples.id, barcode,date_registered, description, sample_innoculated, 
                    clients.name AS client_name
                FROM  
                    samples  
                LEFT JOIN 
                    clients
                ON
                    samples.client = clients.id
                
                WHERE  date_registered < :date_registered AND ( sample_innoculated IS NULL OR  sample_innoculated = "" OR sample_innoculated = 0) ';
        
        $results = $this->Sample->customQuery($sql, array('date_registered' => $cutoff ));              

        $table = new tableFactory();   
        $table->setTableId('nonInnocedSamplesTable');
        $table->loadTemplate('nonInnocSamples');
               
        foreach($results as $idx=>$result)
        {
            //convert the date_registered to a human readable format
            $results[$idx]['date_registered'] = date('d-m-Y', $result['date_registered']);
            $results[$idx]['client'] = '';
        }

        if(empty($results)){ 
            $results = 'Geen monsters gevonden';
        }
     
        
        $table->loadValues($results);
        
        $this->_template->set('table', $table->renderTable());
        
    }

    function countOpenSamples()
    {
        $this->render = False; 
                
        //create a new date object
        $cutoff = new DateTime();

        //set the date to yesterday
        $cutoff->modify('-1 day');

        //set the time to the beginning of the day
        $cutoff->setTime(0, 0, 0);

        //get the unixtimestamp
        $cutoff = $cutoff->getTimestamp();

        //$sql =  'SELECT id, sample_innoculated, CAST(date_registered as UNSIGNED ) as date_unsigned  FROM  samples  WHERE  date_registered < :date_registered AND ( sample_innoculated IS NULL OR  sample_innoculated = "" OR sample_innoculated = 0) LIMIT 10';

        $sql =  'SELECT  COUNT(id) AS NumberOfOpenSamples FROM  samples  WHERE  date_registered < :date_registered AND ( sample_innoculated IS NULL OR  sample_innoculated = "" OR sample_innoculated = 0)';

        $results = $this->Sample->customQuery($sql, array('date_registered' => $cutoff ));                             

        return $results[0]['NumberOfOpenSamples'];
    }

    function analysisSearch()
    {

        $this->render = 0;
        $searchTerm = $_POST['search'];

        //find
        $packetResults = upa('packets', 'findByTags', array($searchTerm));
        $analysisResults = upa('flows', 'findByTags', array($searchTerm));

        //generate html
        $searchResult = '';

        foreach ($packetResults as $packet) {
            $packet['item_type'] = 'packet';
            $packet['icon'] = 'icon-copy';
            $searchResult .= generateHTML('searchLine', $packet);
        }

        foreach ($analysisResults as $analysis) {
            $analysis['item_type'] = 'single';
            $analysis['icon'] = 'icon-code-fork';
            $searchResult .= generateHTML('searchLine', $analysis);
        }
        print $searchResult;
    }


    function searchAvailResearch($excludeProfiles = False)
    {

        $this->render = 0;
        $excludeProfiles = filter_var($excludeProfiles, FILTER_VALIDATE_BOOLEAN);

        $searchTerm = $_POST['search'];
        $customerId = $_POST['customer_id'];

        if(isset($_POST['searchTermQ'])){
          $searchTermQ = $_POST['searchTermQ'];
        } else{
          $searchTermQ = '';
        }

        $searchResult = '';
        
        //search loose assays
        $assays = upa('assays', 'searchAssays', array($searchTerm));

        //generate list
        if(!$excludeProfiles)
        {
            //search researchProfiles ($searchTerm, $customerId)
            //contains key private and global
            $profiles = upa('researchProfiles', 'searchResearchProfiles', array($searchTerm, $customerId));
        
            foreach ($profiles['private'] as $pRes) {
                $pRes['research_type'] = 'profile';
                $pRes['research_id'] = $pRes['id'];
                $pRes['icon'] = 'icon-sitemap';
                $pRes['assaytype'] = '';
                $searchResult .= generateHTML('searchLine', $pRes);
            }

            foreach ($profiles['global'] as $gRes) {
                $gRes['research_type'] = 'profile';
                $gRes['research_id'] = $gRes['id'];
                $gRes['icon'] = 'icon-globe';
                $gRes['assaytype'] = '';
                $searchResult .= generateHTML('searchLine', $gRes);
            }

        }

        foreach ($assays as $assay) {

            $q = peekIntoJSON($assay['custom_fields'], "accred");

            if($searchTermQ == 'q' && strtolower($q) <> 'q'){
              continue;
            }

            if($searchTermQ == 'nq' && strtolower($q) == 'q'){
              continue;
            }

            $assay['research_type'] = 'assay';
            $assay['research_id'] = $assay['id'];
            $assay['icon'] = 'icon-random';
            $assay['assaytype'] = $assay['type'];
            $searchResult .= generateHTML('searchLine', $assay);
        }


        print $searchResult;
    }


    //end of orphan function
    //**********************

    function beforeAction($queryString)
    {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    public function peek($sampleId)
    {
        $this->render = False;
        $this->Sample->where('id', $sampleId);
        $results = $this->Sample->search();
        parray($results);
    }


    function barcodeToId($barcode, $returnSampleAsObject = False)
    {
        $barExp = explode('.', $barcode);
        $sampleBar = $barExp[0];

        $shortHand = $this->isShortHand($sampleBar);

        if($shortHand === False){
            $this->Sample->where('barcode', $sampleBar);   
                 
        } else{
            $this->Sample->where('follow_no', $shortHand['follow_no']);                
            $this->Sample->where('sample_type', $shortHand['sample_type']); 
            $this->Sample->where('leg_type', $shortHand['matrix_type']);                    
        }

        $this->Sample->order('id', 'desc');   
        $this->Sample->limit('1');            
        $result = $this->Sample->search();

        if (!empty($result)) {

            if($returnSampleAsObject){
                return $result[0];
            } 

            else
            {
                return $result[0]['id'];
            }

        } else {
            return False;
        }

    }

    function thttest()
    {
        $this->render = False; 
        print($this->generateBarCode(False, 'THT'));

    }

    function generateBarCode($inParts = False, $prefixer = False, $offset = 0, $matrix = False)
    {

        $parts = array();
        $currentTime = time();
        $day = date('d', $currentTime);
        $month = date('m', $currentTime);
        $year = date('y', $currentTime);



        if($prefixer == 'L'){   //legionella

            if($matrix == 'A'){
                $cQuery = 'SELECT `id`, `date_registered`, `follow_no` FROM `samples` WHERE `sample_type` = \'L\' AND `leg_type` = \'A\' ORDER BY `id` DESC LIMIT 1';
                $results = $this->Sample->customQuery($cQuery, array(), False);
            }

            elseif($matrix == 'B'){
                $cQuery = 'SELECT `id`, `date_registered`, `follow_no` FROM `samples` WHERE `sample_type` = \'L\' AND `leg_type` = \'B\' ORDER BY `id` DESC LIMIT 1';
                $results = $this->Sample->customQuery($cQuery, array(), False);
            }

            elseif($matrix == 'C'){
                $cQuery = 'SELECT `id`, `date_registered`, `follow_no` FROM `samples` WHERE `sample_type` = \'L\' AND `leg_type` = \'C\' ORDER BY `id` DESC LIMIT 1';
                $results = $this->Sample->customQuery($cQuery, array(), False);
            }

            else{
                $cQuery = 'SELECT `id`, `date_registered`, `follow_no` FROM `samples` WHERE `sample_type` = \'L\' ORDER BY `id` DESC LIMIT 1';
                $results = $this->Sample->customQuery($cQuery, array(), False);
            }
            
        } 
        
        elseif($prefixer == 'THT'){
            
            $boy = strtotime('first day of January '.date('Y') );
            $cQuery = 'SELECT `id`, `tht_code`, `date_registered` FROM `samples` WHERE `tht_code` IS NOT NULL AND `date_registered` > :boy ORDER BY `id` DESC LIMIT 100';

            $results = $this->Sample->customQuery($cQuery, ['boy' => $boy], False);            

     
       
            $cQuery = 'SELECT `id`, `tht_code`, `date_registered` FROM `samplebuffers` WHERE `tht` =\'1\' AND `tht_code` IS NOT NULL ORDER BY `id` DESC ';
            $results2 = $this->Sample->customQuery($cQuery, array(), False);
            
          
        
            if(!empty($results2))
            {
                $results2 = array_map(function ($a) { 
                    $a['tht_code'] = substr( $a['tht_code'], 3);   
                    return $a; 
                }, $results2);

                 usort($results2, function($a, $b) {
                     return $b['tht_code'] - $a['tht_code'];
                });
            
            }

        

            if(!empty($results))
            {
                
                $results = array_map(function ($a) { 
                    $a['tht_code'] = (int)substr( $a['tht_code'], 3);   
                    return $a; 
                }, $results);

                usort($results, function($a, $b) {
                     return $b['tht_code'] - $a['tht_code'];
                });
            }

        
            //This is a bit hacky, but we need to consolidate both samplebuffers + samples barcodes
            //to generate a new one. Since some may be commited already as sample, while other remain in the buffer
            //but we also have to generate the date pre-amble etc. 
            $numberBuffer = 0;
            $numberSamples = 0; 
            $stampSamples = 0;
            $stampBuffer = 0;
        
            //sample table
            if(!empty($results))
            {
                $numberSamples = $results[0]['tht_code'];
                $stampSamples = $results[0]['date_registered'];

            }

            //buffer table 
            if(!empty($results2))
            {                
                $numberBuffer = $results2[0]['tht_code'];
                $stampBuffer = $results2[0]['date_registered']; 
            }
         

            if($numberBuffer > $numberSamples )
            {                
                $maxSample = (int)substr($numberBuffer, 4); 
                $maxStamp = $stampBuffer;                
            }

            else
            {             
                $maxSample = (int)substr($numberSamples, 4); 
                $maxStamp = $stampSamples;
            }


                                  
            $faux = array();
            $faux[0]['follow_no'] = $maxSample;
            $faux[0]['date_registered'] = $maxStamp;        
                        

            if(empty($results) && empty($results2))
            {
                $results = array();
            }

            else
            {
                $results = $faux;
            }
            
        }        
        
        else{
            $cQuery = 'SELECT `id`, `date_registered`, `follow_no` FROM `samples` WHERE `sample_type` !=\'L\' ORDER BY `id` DESC LIMIT 1';
            $results = $this->Sample->customQuery($cQuery, array(), False);
        }

        

        //base sample
        $barCode = '';        
        $prefix = $prefixer;

        if($matrix !== False){
            $prefix = $prefix . $matrix;
        }

        //check prefix
        if (MESA_BAR_PREFIX == 'MMYY') {
            $prefix .= $month . $year;
        }

        if (MESA_BAR_PREFIX == 'YYMM') {
            $prefix .= $year . $month;
        }

        $barCode .= $prefix;

        //check if it is a new year / day / month since
        //last insertion and reset the counter if nessecairy, else one up
        if (!empty($results)) {

            //valid? 
            if(empty($results[0]['follow_no'])){
                
            }
            
            $sampleNumber = $results[0]['follow_no'] + 1;

            if (MESA_BAR_RESET == 'Y') {
                $lastYear = date('y', $results[0]['date_registered']);
                if ($lastYear < $year) {
                    $sampleNumber = MESA_BAR_START;
                }
            } elseif (MESA_BAR_RESET == 'M') {
                $lastMonth = date('m', $results[0]['date_registered']);
                if ($lastMonth < $month) {
                    $sampleNumber = MESA_BAR_START;
                }
            } elseif (MESA_BAR_RESET == 'D') {
                $lastDay = date('d', $results[0]['date_registered']);
                if ($lastDay < $day) {
                    $sampleNumber = MESA_BAR_START;
                }
            }

            //setup offset
            $sampleNumber = $sampleNumber + $offset;

        } else {
            $sampleNumber = MESA_BAR_START;
            $sampleNumber = $sampleNumber + $offset;
        }

        $barCode .= $sampleNumber;

        $parts['bar_type'] = '';
        $parts['bar_prefix'] = $prefix;
        $parts['bar_follow'] = $sampleNumber;
        $parts['bar_full'] = $barCode;        

        if ($inParts === True) {
            return $parts;
        } else {                      
            //print $barCode;
            return $barCode;
        }
    }

    function renderEditForm($sampleId)
    {

        $this->doNotRenderHeader = 1;

        $this->Sample->where('id', $sampleId);
        $results = $this->Sample->search();

        if (empty($results)) {
            writeLog('Sample id was empty', ALPC_ERROR);
            return;
        }

        $this->Sample->arrayToModel($results['0']);
        $sampMethodOp = upa('sampleProcedures', 'getProceduresArr', array());
        $customFields = upa('sampleFields', 'renderCustomFields', array($this->Sample->custom_fields));


        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');

        $sForm->addInputField('description', False, 'text', 'input-block-level', $this->Sample->description, '{MESA_SAD_SAMPLEDESCRIPTION}', False, '{MESA_SAD_SAMPLEDESCRIPTION}');
        $sForm->addDropdownField('sampling_method', False, 'input-block-level', $this->Sample->sampling_method, $sampMethodOp, False, '{MESA_SAD_SAMPLEMETHOD}');


        if(!empty($this->Sample->sample_extra)){
            $extraFields = json_decode($this->Sample->sample_extra, JSON_FORCE_OBJECT);
            if(is_array($extraFields)){
                foreach($extraFields as $exFieldKey => $exFieldValue){

                    $intFilter = '';

                    if($exFieldKey == 'follow'){
                        continue;
                        $fieldDescription = 'Volgnummer';
                    }

                    if($exFieldKey == 'type'){
                        $fieldDescription = 'Type';
                    }

                    if($exFieldKey == 'temperature'){
                        $fieldDescription = 'Temperatuur';
                    }

                    if($exFieldKey == 'location'){
                        $fieldDescription = 'Ruimte';
                    }

                    if($exFieldKey == 'filter_volume'){
                        $fieldDescription = 'Onderzocht volume in ml.';
                        $intFilter = ' int-filter';
                    }

                    $sForm->addInputField($exFieldKey, False, 'text', 'input-block-level sample-extra-field' . $intFilter, $exFieldValue, $fieldDescription, False, $fieldDescription);

                }
            }
        }

     

        $this->_template->set('form', $sForm->render());
        $this->_template->set('custom', $customFields);

    }

    function saveRodac($faker = False){

        $this->render = 0;
        $client = $_POST['client'];
        //$legProfile = upa('researchProfiles', 'fetchTip', array(RODAC_BASE_PROFILE), False);
        $printSAids = array();
        $printSampleIds = array();

        if(!isset($_POST['samplePackage'])){
            $nextBar = $this->generateBarCode(True, False);
            $jsonObj = array();
            $jsonObj['nextBar'] = $nextBar['bar_full'];
            $jsonObj['project'] = False;
            print json_encode($jsonObj, JSON_FORCE_OBJECT);
            return;
        }

        $defaultPG = upa('productGroups', 'getClientDefaultGroup', array($client), False);        

        //$lockSQL = 'LOCK TABLE samples WRITE;';
        //$this->Sample->raw($lockSQL); 
       

        foreach($_POST['samplePackage'] as $sampleSubmit)
        {
                            
            unset($this->Sample->id);
            
            $this->Sample->lock();

            $barParts = $this->generateBarCode(True, False);
            
            $this->Sample->barcode = $barParts['bar_full'];
            $this->Sample->follow_no = $barParts['bar_follow'];
            $this->Sample->date_registered = time();       
            $this->Sample->sample_type = 'R';

            $this->Sample->save(); 

            $this->Sample->unlock(); 

            $this->Sample->id = $this->Sample->lastInsertId; 


            if ($_POST['createProject'] == 'True') {
                
                $projectName =  $barParts['bar_full']; 

                if(!empty($_POST['customProjectName']))
                {
                    $projectName = $_POST['customProjectName'];
                }
                
                //removed: 'client_reference' => $_POST['clientReference'],
                $projectExtra = json_encode(array('sample_method' => $_POST['samplingMethod'],  'number_of_blanks' => $_POST['number_of_blanks']), JSON_FORCE_OBJECT);
                
                $otfProjectId = upa('projects', 'createProject', array($client, 0, $projectName, time(), $_POST['projectPreload'], $projectExtra, 2), 0);
                
                $this->Sample->project = $otfProjectId;
                
                $_POST['selected_project_id'] = $otfProjectId;
                $_POST['createProject'] = 'False';
                upa('projects', 'projectEdited', array($this->Sample->project), False);
            } else{
                if(isset($_POST['selected_project_id']) && !empty($_POST['selected_project_id'])){
                    $this->Sample->project = $_POST['selected_project_id'];
                    upa('projects', 'updateProjectExtraField' , array($_POST['selected_project_id'], 'number_of_blanks', $_POST['number_of_blanks']), False);
                } else{
                    writeLog('Selected project id not found, could not continue when createProject == FALSE', ALPC_ERROR);
                    die();
                }
            }

            #$this->Sample->barcode = $barParts['bar_full'];
            #$this->Sample->follow_no = $barParts['bar_follow'];
            #$this->Sample->date_registered = time();            
            $this->Sample->description = RODAC_STD_DESCRIPTION;
            //$this->Sample->client_description = RODAC_STD_DESCRIPTION;
            $this->Sample->client_description = 'Geen omschrijving beschikbaar';
            $this->Sample->portal_product_group_id =  ($defaultPG) ? $defaultPG['portal_id'] : null;

            $custoArr = array();
            $custArr['details']  = $sampleSubmit['thisDescription'];
            $this->Sample->custom_fields = json_encode($custArr, JSON_FORCE_OBJECT);

            $this->Sample->registered_by = getUserId();
            $this->Sample->client = $client;
            $this->Sample->sampling_method = $_POST['samplingMethod'];
            $this->Sample->sample_note = $_POST['sample_note'];
            //$this->Sample->sample_note = $_POST['sampleNotes'];
            #$this->Sample->sample_type = 'R';

            //$extraArray = array('follow'=> $sampleSubmit['thisFollow'], 'type'=> $sampleSubmit['thisType'], 'temperature' => $sampleSubmit['thisTemp']);
            $extraArray = array('follow'=> $sampleSubmit['thisFollow'], 'location'=> $sampleSubmit['thisLocation']);
            $this->Sample->sample_extra = json_encode($extraArray);

            if(!empty($_POST['innocDate'])){
                $date = new DateTime($_POST['innocDate']);

                if(!empty($_POST['innocTime'])){
                    $time = new DateTime($_POST['innocTime']);
                    $merge = new DateTime($date->format('d-m-Y') .' ' .$time->format('H:i:s'));
                } else{
                    $merge = new DateTime($date->format('d-m-Y'));
                }

                $innoculated = $merge->getTimestamp();
                $this->Sample->sample_innoculated = $innoculated;
            }

            $this->Sample->save();


            $projReference = upa('projects', 'updateProjReference', array($this->Sample->project));
            $assignedProject = $this->Sample->project;
            $sampleId = $this->Sample->id;
            
            $this->newSampleId = $sampleId; 

            $event = 'Monster ' . $this->Sample->barcode . ' toegevoegd aan project';
            upa('changeTracker', 'changed', array(2, $this->Sample->project, $sampleId, False, $event, False, False), False);

            array_push($printSampleIds, $sampleId);

            $orderNumber = 0;
           

            //auto trigger innoc if set to yes
            if(RODAC_AUTO_REGISTER_INNOCULATION == '1'){
                upa('samples', 'registerInnoculation', array($this->Sample->barcode,True), False);
            }

        }

        //$lockSQL = 'UNLOCK TABLES;';
        //$this->Sample->raw($lockSQL); 

        upa('printing', 'batchProcess', array($printSampleIds, $printSAids, False), False);

        $nextBar = $this->generateBarCode(True);
        $jsonObj = array();
        $jsonObj['nextBar'] = $nextBar['bar_full'];
        $jsonObj['project'] = $assignedProject;

        if($faker == False)
        {
            print json_encode($jsonObj, JSON_FORCE_OBJECT);
        }

    }

    function addRodac(){
        $nextUp = $this->generateBarCode();
        $customFields = upa('sampleFields', 'renderCustomFields');
        $this->_template->set('custom_fields', $customFields);

        $projectFields = upa('projectFields', 'renderProjectFields', array());
        $projectPreloadFields = upa('projectFields', 'renderProjectFields', array(False, True));

        $this->_template->set('project_fields', $projectFields);
        $this->_template->set('project_preload_fields', $projectPreloadFields);

        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();

        $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', False, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

        $sForm->addInputField('client', False, 'hidden', 'hidden', False, 'NULL', '');
        $sForm->addInputField('subclient', False, 'hidden', 'hidden', False, 'NULL', '');

        $sForm->addInputField('number_of_samples', False, 'text', 'input-block-level', '0', '', '', 'Aantal monsters');
        //$sForm->addInputField('number_of_blanks', False, 'text', 'input-block-level', '1', '', '', 'Blancos');

        $blankOpts = array();
        $blankOpts['1'] = 'Ja';
        $blankOpts['0'] = 'Nee';
        $sForm->addDropdownField('number_of_blanks', False, 'input-block-level', '1', $blankOpts, '', 'Incl. blanco?');

        $sForm->addInputField('client_reference', False, 'text', 'input-block-level', '', '', 'disabled="disabled"', 'Monster / referentienummer');

        //$sForm->addDropdownField('subclient', False, 'input-block-level', '', array('NULL' => 'Select Subclient'), False);
        //$sForm->addValidation('subclient', 'NOT_NULL');

        $newProjButton = '<i id="newProjButton" class="icon-folder-open-alt"></i>';
        $sForm->addDropdownField('project', False, 'input-block-level', '', array('NULL' => '{MESA_SAD_SAMPLEPROJECTSELECT}'), '', $newProjButton);
        $sForm->addValidation('project', 'NOT_NULL');

        $noteField = generateHTML('customFields/notes', array());
        $this->_template->set('sample_note', $noteField);

        $sampMethodOp = pa('sampleProcedures', 'getProceduresArr', array('SMPL_METHOD_AVAIL_RODAC'));

        //$sForm->addDropdownField('sampling_method', False, 'input-block-level', MESA_STD_SMPL_METHOD, $sampMethodOp, False, '{MESA_SAD_SAMPLEMETHOD}');

        $sForm->addDropdownField('sampling_method', False, 'input-block-level', MESA_STD_RODACSMPL_METHOD, $sampMethodOp, False, '{MESA_SAD_SAMPLEMETHOD}');
        $sForm->submitTrough('saveSampleButton', 'saveSample');

        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);
        $this->_template->set('sampling_method_standard', MESA_STD_RODACSMPL_METHOD);
    }

    function renderAddRodacSamples($numberOfSamples, $project = 'CREATE'){

        $this->doNotRenderHeader = True;

        //$template = generateHTML('legionella/sampleAddRow', array());
        //$nextUp = $th is->generateBarCode(False, True);
        //$nextUp = $nextUp -1 ;

        
        //$assaysAvail = upa('assays', 'fetchAssaysList', array(True));
        

        $volgNummerI = 1;
        if($project != 'CREATE'){
            $numberOfSamplesInProjectAlready = upa('projects', 'countSamplesInProject', array($project));
            $volgNummerI = $volgNummerI + $numberOfSamplesInProjectAlready;
        }


        $table = '';
        $firstUp = '';


        for($i = 0; $i <$numberOfSamples; $i++){

            if($i == 0){
                $firstUp =  $this->generateBarCode(False, False);
                $nextUp  = $firstUp;
            } else{
                $offset = $i;
                $nextUp  = $this->generateBarCode(False, False, $offset);
            }

            $arr = array();
            $arr['i'] = $i;
            $arr['barcode'] =$nextUp;
            $arr['follow_number'] = $volgNummerI;        
            $table .=  generateHTML('rodac/sampleAddRow', $arr);
            $volgNummerI = $volgNummerI + 1;
        }


        print json_encode(array('table'=> $table, 'first_up' => $firstUp ));
        //print $table;
    }

    function addLeg2019(){
                
        $nextUp = $this->generateBarCode(False, 'L');
        $customFields = upa('sampleFields', 'renderCustomFields');
        $this->_template->set('custom_fields', $customFields);

        $projectFields = upa('projectFields', 'renderProjectFields', array());
        $projectPreloadFields = upa('projectFields', 'renderProjectFields', array(False, True));
                
        $this->_template->set('matrix_content', upa('matrix', 'matrixDropdown', array(json_decode(LEGIONELLA_ALLOWED_MATRICES), LEGIONELLA_STD_MATRIX ), False));

        $this->_template->set('MAX_LABEL_PRINT', MAX_LABEL_PRINT);
        $this->_template->set('project_fields', $projectFields);
        $this->_template->set('project_preload_fields', $projectPreloadFields);

        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();

        //client & project
        //$sForm->addInputField('client_name', False, 'text', 'ajax-typeahead input-block-level', False, 'Start typing client name', array('autocomplete' => 'off'));
        $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', False, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

        $sForm->addInputField('client', False, 'hidden', 'hidden', False, 'NULL', '');
        $sForm->addInputField('subclient', False, 'hidden', 'hidden', False, 'NULL', '');

        //$sForm->addDropdownField('subclient', False, 'input-block-level', '', array('NULL' => 'Select Subclient'), False);
        //$sForm->addValidation('subclient', 'NOT_NULL');

        $newProjButton = '<i id="newProjButton" class="icon-folder-open-alt"></i>';
        $sForm->addDropdownField('project', False, 'input-block-level', '', array('NULL' => '{MESA_SAD_SAMPLEPROJECTSELECT}'), '', $newProjButton);
        $sForm->addValidation('project', 'NOT_NULL');

        //sample
        $sForm->addInputField('barcode', False, 'text', 'input-block-level', $nextUp, '', 'disabled', '<i class="icon-barcode"></i> ');
        $sForm->addInputField('samplen', False, 'text', 'input-block-level', '1', '', '', 'n=');
        $sForm->addInputField('description', False, 'text', 'hidden input-block-level', LEGIONELLA_STD_DESCRIPTION, False, False, False);
        $sForm->addValidation('description', 'NOT_EMPTY');

        //$sForm->addTextArea('sample_note', False, 'text', 'input-block-level', False, 'Monster notities', False, 'Notities');
        //$sForm->addTextArea('sample_note', 'Notities', 'textarea', 'input-block-level', False, 'Monster notities');

        //buffer, tht or both?
        $registerMethods = array();
        $registerMethods['standard'] = 'Normaal monster';
        $registerMethods['tht'] = 'THT monster';
        $registerMethods['buffer'] = 'Aanmelden in buffer';

        $sForm->addDropdownField('register_as', False, 'hidden input-block-level', 'standard', $registerMethods, False, False);
        $sForm->addInputField('tht_trigger_date', False, 'text', 'input-block-level', False, 'Inzet datum voor THT', False, 'Inzet datum voor THT');


        //sampling method
        $sampMethodOp = pa('sampleProcedures', 'getProceduresArr', array('SMPL_METHOD_AVAIL_NORMAL'));

        $sForm->addDropdownField('sampling_method', False, 'input-block-level', MESA_STD_SMPL_METHOD, $sampMethodOp, False, '{MESA_SAD_SAMPLEMETHOD}');
        //$renderedHtml .= generateHTML('customFields/customFieldTextArea', $field);
        $noteField = generateHTML('customFields/notes', array());
        $this->_template->set('sample_note', $noteField);


        //submit
        //$sForm->addButton('saveSampleButton', 'icon-save', '', 'btn btn-primary', 'Hoi', '');
        $sForm->submitTrough('saveSampleButton', 'saveSample');

        $sForm->addInputField('pj_project_extra_client_reference', False, 'text', 'input-block-level project_extra_field', '', '', ['project_field_name' => 'client_reference'], 'Referentie klant');
        $sForm->addInputField('client_reference', False, 'text', 'input-block-level project_extra_field', '', '', '', 'Referentie klant');

        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);

        $profilesAvail = upa('researchProfiles', 'fetchLegionellaProfiles', array(False, True));
        //$assaysAvail = upa('assays', 'fetchAssaysList', array(True));

        $this->_template->set('global_profile_list', $profilesAvail['html']);        
        $this->_template->set('global_profile_drop_list', $profilesAvail['options']);

        //$this->_template->set('assay_list', $assaysAvail['html']);
        $this->_template->set('assay_list', '');

        

        //$this->_template->set('assay_drop_list', $assaysAvail['options']);
        $this->_template->set('cust_profile_list', '');        

    }

    function addleg(){
      
        $nextUp = $this->generateBarCode();
        $customFields = upa('sampleFields', 'renderCustomFields');
        $this->_template->set('custom_fields', $customFields);

        $projectFields = upa('projectFields', 'renderProjectFields', array());
        $projectPreloadFields = upa('projectFields', 'renderProjectFields', array(False, True));
        
        $this->_template->set('project_fields', $projectFields);
        $this->_template->set('project_preload_fields', $projectPreloadFields);

        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();

        $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', False, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

        $sForm->addInputField('client', False, 'hidden', 'hidden', False, 'NULL', '');
        $sForm->addInputField('subclient', False, 'hidden', 'hidden', False, 'NULL', '');

        $sForm->addInputField('number_of_samples', False, 'text', 'input-block-level', '0', '', '', 'Aantal monsters');

        $newProjButton = '<i id="newProjButton" class="icon-folder-open-alt"></i>';
        $sForm->addDropdownField('project', False, 'input-block-level', '', array('NULL' => '{MESA_SAD_SAMPLEPROJECTSELECT}'), '', $newProjButton);
        $sForm->addValidation('project', 'NOT_NULL');

        $noteField = generateHTML('customFields/notes', array());
        $this->_template->set('sample_note', $noteField);

        $sampMethodOp = pa('sampleProcedures', 'getProceduresArr', array('SMPL_METHOD_AVAIL_LEGIONELLA'));

        $sForm->addDropdownField('sampling_method', False, 'input-block-level hidden', MESA_STD_LEGSMPL_METHOD, $sampMethodOp, False, '');

        $sForm->submitTrough('saveSampleButton', 'saveSample');

        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);
        $this->_template->set('sampling_method_standard', MESA_STD_LEGSMPL_METHOD);
    }

    function renderAddLegSamples($numberOfSamples, $project = 'CREATE'){

        $this->doNotRenderHeader = True;

        //also check how many samples are here already!
        $profilesAvail = upa('researchProfiles', 'fetchLegionellaProfiles', array(False, True));
        $sampMethodOp = upa('sampleProcedures', 'getProcedureDropDown', array(MESA_STD_LEGSMPL_METHOD, 'SMPL_METHOD_AVAIL_LEGIONELLA',False));        

        $volgNummerI = 1;
        if($project != 'CREATE'){
            $numberOfSamplesInProjectAlready = upa('projects', 'countSamplesInProject', array($project));
            $volgNummerI = $volgNummerI + $numberOfSamplesInProjectAlready;
        }        


        //$template = generateHTML('legionella/sampleAddRow', array());
        //$nextUp = $this->generateBarCode(False, True);
        //$nextUp = $nextUp -1 ;
        $table = '';

        for($i = 0; $i <$numberOfSamples; $i++){

            if($i == 0){
                $nextUp  = $this->generateBarCode(False, 'L', False, $profilesAvail['firstType']);
            } else{
                $offset = $i;
                $nextUp  = $this->generateBarCode(False, 'L', $offset, $profilesAvail['firstType']);
            }

            $arr = array();
            $arr['i'] = $i;
            $arr['barcode'] = $nextUp;
            //$arr['follow_number'] = $i + 1;
            $arr['follow_number'] = $volgNummerI;
            $arr['global_profile_drop_list'] = $profilesAvail['options'];
            $arr['sample_methods'] = $sampMethodOp;

            $table .=  generateHTML('legionella/sampleAddRow', $arr);
            $volgNummerI = $volgNummerI + 1;
        }

        print $table;

    }

    function redrawLegBarcodes(){
        $this->render = False;         
        $aCounter = 0;
        $bCounter = 0;
        $cCounter = 0;

        $predicted = array();

        foreach($_POST['samplePackage'] as $sampleSubmit){
            
            if($sampleSubmit['matrixType'] == 'A'){
                $matrixToUse = 'A';
                $counterValue = $aCounter; 
                $aCounter++;
            }

            elseif($sampleSubmit['matrixType'] == 'B'){
                $matrixToUse = 'B';
                $counterValue = $bCounter; 
                $bCounter++;
            }

            elseif($sampleSubmit['matrixType'] == 'C'){
                $matrixToUse = 'C';
                $counterValue = $cCounter; 
                $cCounter++;
            }

            $bar =  $this->generateBarCode(False, 'L', $counterValue, $matrixToUse);
            array_push($predicted, array('sampleI' => $sampleSubmit['sampleI'], 'barcode' => $bar));            
        }

        print json_encode($predicted);

    }

    function predictLegBarcode($matrixType, $offset){
        $this->render = false;
        print $this->generateBarCode(False, 'L', $offset, $matrixType);
    }

    function add()
    {
        $nextUp = $this->generateBarCode();
        $customFields = upa('sampleFields', 'renderCustomFields');
        $this->_template->set('custom_fields', $customFields);

        $projectFields = upa('projectFields', 'renderProjectFields', array());
        $projectPreloadFields = upa('projectFields', 'renderProjectFields', array(False, True));

        $this->_template->set('matrix_content', upa('matrix', 'matrixDropdown', array(), False));

        $this->_template->set('MAX_LABEL_PRINT', MAX_LABEL_PRINT);
        $this->_template->set('project_fields', $projectFields);
        $this->_template->set('project_preload_fields', $projectPreloadFields);

        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();

        //client & project
        //$sForm->addInputField('client_name', False, 'text', 'ajax-typeahead input-block-level', False, 'Start typing client name', array('autocomplete' => 'off'));
        $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', False, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

        $sForm->addInputField('client', False, 'hidden', 'hidden', False, 'NULL', '');
        $sForm->addInputField('subclient', False, 'hidden', 'hidden', False, 'NULL', '');

        //$sForm->addDropdownField('subclient', False, 'input-block-level', '', array('NULL' => 'Select Subclient'), False);
        //$sForm->addValidation('subclient', 'NOT_NULL');

        $newProjButton = '<i id="newProjButton" class="icon-folder-open-alt"></i>';
        $sForm->addDropdownField('project', False, 'input-block-level', '', array('NULL' => '{MESA_SAD_SAMPLEPROJECTSELECT}'), '', $newProjButton);
        $sForm->addValidation('project', 'NOT_NULL');

        //sample
        $sForm->addInputField('barcode', False, 'text', 'input-block-level', $nextUp, '', 'disabled', '<i class="icon-barcode"></i> ');
        $sForm->addInputField('samplen', False, 'text', 'input-block-level', '1', '', '', 'n=');
        $sForm->addInputField('description', False, 'text', 'input-block-level', False, '{MESA_SAD_SAMPLEDESCRIPTION}', False, '{MESA_SAD_DESCRIPTION}');
        $sForm->addValidation('description', 'NOT_EMPTY');

        //$sForm->addTextArea('sample_note', False, 'text', 'input-block-level', False, 'Monster notities', False, 'Notities');
        //$sForm->addTextArea('sample_note', 'Notities', 'textarea', 'input-block-level', False, 'Monster notities');

        //buffer, tht or both?
        $registerMethods = array();
        $registerMethods['standard'] = 'Normaal monster';
        $registerMethods['tht'] = 'THT monster';
        $registerMethods['buffer'] = 'Aanmelden in buffer';

        $sForm->addDropdownField('register_as', False, 'input-block-level', 'standard', $registerMethods, False, 'Aanmelden als');
        $sForm->addInputField('tht_trigger_date', False, 'text', 'input-block-level', False, 'Inzet datum voor THT', False, 'Inzet datum voor THT');

        
        $storageConditions = array();
        $storageConditions['4'] = '+3°C';        
        $storageConditions['1'] = '+7°C';
        $storageConditions['2'] = '-18°C';
        $storageConditions['3'] = 'Kamer temperatuur';

        $sForm->addDropdownField('tht_storage',  False, 'input-block-level', 'standard', $storageConditions, False, 'Bewaar temperatuur');

        //sampling method
        $sampMethodOp = pa('sampleProcedures', 'getProceduresArr', array('SMPL_METHOD_AVAIL_NORMAL'));

        $sForm->addDropdownField('sampling_method', False, 'input-block-level', MESA_STD_SMPL_METHOD, $sampMethodOp, False, '{MESA_SAD_SAMPLEMETHOD}');
        //$renderedHtml .= generateHTML('customFields/customFieldTextArea', $field);
        $noteField = generateHTML('customFields/notes', array());
        $this->_template->set('sample_note', $noteField);


        //submit
        //$sForm->addButton('saveSampleButton', 'icon-save', '', 'btn btn-primary', 'Hoi', '');
        $sForm->submitTrough('saveSampleButton', 'saveSample');

        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);

        $profilesAvail = upa('researchProfiles', 'fetchGlobalProfiles', array(False, True));
        //$assaysAvail = upa('assays', 'fetchAssaysList', array(True));

        $this->_template->set('global_profile_list', $profilesAvail['html']);
        $this->_template->set('global_profile_drop_list', $profilesAvail['options']);

        //$this->_template->set('assay_list', $assaysAvail['html']);
        $this->_template->set('assay_list', '');

        //$this->_template->set('assay_drop_list', $assaysAvail['options']);
        $this->_template->set('cust_profile_list', '');
    }

    function update(){

        $profilesAvail = upa('researchProfiles', 'fetchGlobalProfiles', array(False, True));
        $assaysAvail = upa('assays', 'fetchAssaysList', array(True));

        $this->_template->set('global_profile_list', $profilesAvail['html']);
        $this->_template->set('global_profile_drop_list', $profilesAvail['options']);

        $this->_template->set('assay_list', '');
        $this->_template->set('matrix_content', upa('matrix', 'matrixDropdown', array(), False));

        //$this->_template->set('assay_list', $assaysAvail['html']);
        $this->_template->set('assay_drop_list', $assaysAvail['options']);
        $this->_template->set('cust_profile_list', '');

    }

    function preciseCountEmptys(){
      $this->Sample->where('isEmpty', '1');      
      return $this->Sample->countIds();
    }

    public function checkSampleIsEmpty($id){                    
        $this->Sample->where('id', $id);
        $sample = $this->Sample->search();

        if(!empty($sample)){
            $isEmpty = upa('sampleAnalysis', 'isSampleEmpty', array($id), False);      
                        
            if ($isEmpty == True) {
                $this->Sample->id = $id;
                $this->Sample->isEmpty = 1;
            } else{                
                $this->Sample->id = $id;
                $this->Sample->isEmpty = 0;
            }
    
          $this->Sample->save();
        }
    }

    // function temp(){
    //   $this->Sample->order('id', 'ASC');
    //   $result = $this->Sample->search();
    //   $table  = '';
    //
    //   foreach($result as $thisSampleArrInd => $thisSample){
    //       upa('samples',  'checkSampleIsEmpty', array($thisSample['id']), False);
    //   }
    // }

    function loadUpdateList($loadFrom = 0, $emptyOnly = True, $search = False ){

        global $lang;
        $this->render = False;
        $limit = 150;
        $retObj = array();

        $this->Sample->where('isEmpty', 1);

        if($loadFrom != 0){
            //$this->Sample->lessThanHard('id', $loadFrom);
            $this->Sample->greaterThanHard('id', $loadFrom);
        }

        $this->Sample->limit($limit);
        //$this->Sample->order('id', 'DESC');
        $this->Sample->order('id', 'ASC');
        $result = $this->Sample->search();
        $table  = '';

        $striped = False; 

        $clients = array_column($result, 'client');
        $clients = array_unique($clients);
        $clientNames = []; 

        if(count($clients) > 0)
        {
            $clientObj = new Client();
            $clientSql = 'SELECT * FROM clients WHERE id IN (' . implode(',', $clients) .')';
            $clientNames = $clientObj->customQuery($clientSql, []);
            $clientNames = array_column($clientNames, null, 'id');                
        }

        $projects = array_column($result, 'project');
        $projects = array_unique($projects);
        
        if(count($projects) > 0)
        {
            $projectObj = new Project();
            $projectSql = 'SELECT * FROM projects WHERE id IN (' . implode(',', $projects) .')';
            $projectNames = $projectObj->customQuery($projectSql, []);
            $projectNames = array_column($projectNames, null, 'id');
        }

     

        foreach($result as $thisSampleArrInd => $thisSample){

            //client info            
            $clientInfo = array_key_exists($thisSample['client'], $clientNames) ? $clientNames[$thisSample['client']] : false;            
            
            //fallback
            if($clientInfo === false)
            {
                $clientInfo = upa('clients', 'fetch', array($thisSample['client']), False);
            }

            if(empty($clientInfo))
            {
                continue;
            }

            $thisSample['client_name'] = $clientInfo['name'];
            $thisSample['lb'] = $lang['LB'];


            //innoc date
            if(!empty($thisSample['sample_innoculated'])){
                $thisSample['inzet_datum'] = date('d-m-Y', $thisSample['sample_innoculated']);
            } else{
                $thisSample['inzet_datum'] = 'Niet ingezet';
            }

            
            $project = array_key_exists($thisSample['project'], $projectNames) ? $projectNames[$thisSample['project']] : false; 
            
            //fallback
            if($project === false)
            {
                $project = upa('projects', 'fetch', array($thisSample['project']), False);
            }

            $fields = json_decode($project['custom_fields'], JSON_FORCE_OBJECT);

            $ontvangst = '';
            $ontvangstTijd = '';

            if(is_array($fields) && array_key_exists('project_ontvangst', $fields )){
                $ontvangst = $fields['project_ontvangst'];
            }
            if(is_array($fields) && array_key_exists('project_ontvangst', $fields)){
                $ontvangstTijd = $fields['project_tijd_ontvangst'];
            }

            $thisSample['ontvangst'] = $ontvangst;
            $thisSample['ontvangst_tijd'] = $ontvangstTijd;
            $thisSample['project_id'] = $thisSample['project'];

            //note?
            $thisSample['warning_note'] = '';

            if(!empty($thisSample['sample_note'])){
                $thisSample['warning_note'] = '<i class="icon icon-exclamation-sign" onClick="noteDisplay(\'' . $thisSample['id'] . '\')"></i>';
            }

            //check if attachment array IS an array
            $attachtmentArray = json_decode($clientInfo['attachment'], JSON_FORCE_OBJECT);

            if(!is_array($attachtmentArray)){
                $attachtmentArray = array();
            }

            //note 2?
            if($clientInfo['notes'] != '' || !empty($attachtmentArray)){
                $thisSample['warning_note'] .= '<i class="icon icon-smile" onClick="openClientWishes(\'' . $clientInfo['id'] . '\')"></i>';
            }

            $thisSample['show_requested_analysis'] = 'hide';
            $thisSample['import_requested_analysis'] = False;
            $thisSample['import_other_directions'] =  False;

            //coming from CSV?
            if($thisSample['source'] == 1){
                $importInfo = json_decode($thisSample['analyses_data'], JSON_FORCE_OBJECT);
                $requestedAssays = checkKeyOrFalse($importInfo['analyses_selected']);
                $requestedDirections = checkKeyOrFalse($importInfo['misc_directions']);

                if(!empty($requestedAssays) || !empty($requestedDirections)){
                    $thisSample['show_requested_analysis'] = '';
                    $thisSample['import_requested_analysis'] = $requestedAssays;
                    $thisSample['import_other_directions'] = $requestedDirections;;
                }
            }

            //coming from PORTAL
            if($thisSample['source'] == 3){

              $importInfo = json_decode($thisSample['analyses_data'], JSON_FORCE_OBJECT);
              $portalInfo = json_decode($thisSample['portal_analyses'], JSON_FORCE_OBJECT);

              if(!is_array($portalInfo)){
                continue;
              }

              $portalText = '';

              if($portalInfo['profile_id'] == NULL)
              {

                foreach($portalInfo['assays_all'] as $idx => $allAssays){
                  $portalText = $portalText . ' <span class="label label-info">' . $allAssays . '</span>';
                }
              } 
              
              else{

                $portalText = ' <span class="label" style=" background-color: #f0a843;">'. $portalInfo['profile'] . '</span>';

                foreach($portalInfo['assays_addition'] as $idx => $addedAssay){
                  $portalText = $portalText . ' <span class="label label-success"><i class="icon icon-plus"></i>' . $addedAssay . '</span>';
                }

                foreach($portalInfo['assays_substraction'] as $idx => $subbedAssay){
                  $portalText = $portalText . ' <span class="label label-warning"><i class="icon icon-minus"></i>' . $subbedAssay . '</span>';
                }
              }


              $requestedDirections = checkKeyOrFalse($importInfo['misc_directions']);
              $thisSample['show_requested_analysis'] = '';
              $thisSample['import_requested_analysis'] = $portalText;
              $thisSample['import_other_directions'] = $requestedDirections;;

            }

            $thisSample['rowColour'] = ($striped == True) ? '#bcbcbc' : 'white';	
            $striped = !$striped;

            //make table
            $table .= generateHTML('updateList', $thisSample);
        }

        $retObj['number_of_emptys'] = count($result);
        $retObj['html'] = $table;
        print json_encode($retObj, JSON_FORCE_OBJECT);
    }

    function generateRoamingForm($assayId, $client=False)
    {
        //roaming assay form
        $this->doNotRenderHeader = 1;


        $referenceSources = upa('referenceSources', 'list', array($client), False);

        //defaults
        $defaultDillution = '';
        $defaultReplicates = '0';
        $defaultReferences = array();

        if (isset($_POST['loadInDefaults']) && $_POST['loadInDefaults'] != false && $_POST['loadInDefaults'] != 'false') {
            $assayDefaults = pa('assayProfiles', 'fetchProfileById', array($_POST['loadInDefaults']));
            $defaultDillution = dillutionToText($assayDefaults['0']['dillutions']);
            $defaultReferences = json_decode($assayDefaults['0']['reference'], True);
        }

        $roamF = new formFactory('researchProfiles');
        $roamF->returnAsFieldArray();
        $roamF->setId('roamingForm');
        $roamF->action('');
        $roamF->method('');
        $roamF->addClass('');
        $roamF->setTemplate('generic');
        
        $roamF->addInputField('roam_replicates', '{MESA_SAD_REPLICATESSETUP}', 'text', 'input-block-level', $defaultReplicates, '{MESA_SAD_REPLICATESSETUP}');

        $outputs = upa('results', 'fetchOutputFields', array($assayId), False);

        $restrict = [1,4,6,9];

        $assay = new Assay();
        $assay->select('type_base');
        $assay->where('id', $assayId);
        $typeBase = $assay->first(); 
        
        $refValueFields = '';

        foreach ($outputs as $outputName => $dummyOutput) {
            $thisDefaultRef = '';
            if (array_key_exists('ref_' . $outputName, $defaultReferences)) {
                $thisDefaultRef = $defaultReferences['ref_' . $outputName];
            }
        
            $numFilter = (in_array($typeBase['type_base'], $restrict)) ? 'numerical-only-filter' : '';
            $roamF->addInputField('ref_' . $outputName, 'Referentie waarde voor uitkomst: ' . $dummyOutput, 'text', 'input-block-level input-reference-field ' . $numFilter, $thisDefaultRef, 'Referentie waarde');
            
        }
        
        $roamF->addDropdownField('reference_source', 'Referentie Bron', 'input-block-level', NULL, $referenceSources, False);

        $roamF->submitTrough('saveRoamingButton', 'saveRoamingAssay');        

        $fields = $roamF->render();

        foreach ($outputs as $outputName => $dummyOutput) {
            $refValueFields = $fields['ref_'. $outputName];
        }

        $this->_template->set('roam_dillution', $defaultDillution);
        $this->_template->set('refvalue_fields', $refValueFields);
        $this->_template->set('reference_source', $fields['reference_source']);
        $this->_template->set('roam_replicates', $fields['roam_replicates']);
        $this->_template->set('roam_form_header', $fields['formHeader']);
        $this->_template->set('roam_form_script', $fields['validationScript']);

    }

    function saveLeg($faker=False){

        $this->render = 0;
        $printSAids = array();
        $printSampleIds = array();

        $client = $_POST['client'];
        
        //$legProfile = upa('researchProfiles', 'fetchTip', array(LEGIONELLA_BASE_PROFILE), False);

        if(!isset($_POST['samplePackage'])){
            $nextBar = $this->generateBarCode(True, 'L');
            $jsonObj = array();
            $jsonObj['nextBar'] = $nextBar['bar_full'];
            $jsonObj['project'] = False;
            print json_encode($jsonObj, JSON_FORCE_OBJECT);
            return;
        }

        $defaultPG = upa('productGroups', 'getClientDefaultGroup', array($client), False);                                    

        //$lockSQL = 'LOCK TABLE samples WRITE;';
        //$this->Sample->raw($lockSQL);                 

        foreach($_POST['samplePackage'] as $sampleSubmit){             
            

            unset($this->Sample->id);

            $this->Sample->lock(); 

            $barParts = $this->generateBarCode(True, 'L', False, $sampleSubmit['thisMatrix']);

            $this->Sample->barcode = $barParts['bar_full'];
            $this->Sample->follow_no = $barParts['bar_follow'];
            $this->Sample->date_registered = time();           
            $this->Sample->sample_type = 'L';
            $this->Sample->leg_type = $sampleSubmit['thisMatrix'];             
            
            $this->Sample->save(); 

            $this->Sample->unlock(); 

            $this->Sample->id = $this->Sample->lastInsertId; 
            


            if ($_POST['createProject'] == 'True') {

                $projectName =  $barParts['bar_full']; 

                if(!empty($_POST['customProjectName']))
                {
                    $projectName = $_POST['customProjectName'];
                }

                $projectExtra = json_encode([], JSON_FORCE_OBJECT);                

                $otfProjectId = upa('projects', 'createProject', array($client, 0, $projectName, time(), $_POST['projectPreload'], $projectExtra, 1), 0);
                $this->Sample->project = $otfProjectId;
                $_POST['selected_project_id'] = $otfProjectId;
                $_POST['createProject'] = 'False';
                upa('projects', 'projectEdited', array($this->Sample->project), False);
            } else{
                if(isset($_POST['selected_project_id']) && !empty($_POST['selected_project_id'])){
                    $this->Sample->project = $_POST['selected_project_id'];
                } else{                    
                    die();
                }
            }

            //$this->Sample->barcode = $barParts['bar_full'];
            //$this->Sample->sampling_method = $_POST['samplingMethod'];
            $this->Sample->sampling_method = $sampleSubmit['thisSamplingMethod'];            
            //$this->Sample->follow_no = $barParts['bar_follow'];
            //$this->Sample->date_registered = time();            
            $this->Sample->description = LEGIONELLA_STD_DESCRIPTION;
            $this->Sample->client_description = 'Geen omschrijving beschikbaar';
            //$this->Sample->client_description = LEGIONELLA_STD_DESCRIPTION;
            $this->Sample->portal_product_group_id =   ($defaultPG) ? $defaultPG['portal_id'] : null;

            $custoArr = array();
            //$custArr['details']  = $sampleSubmit['thisDescription'];
            $custArr['details']  = '';
            
            //This injects the faker-set details into this sample. 
            if($faker === True)
            {
                $customFields = array();

                if (isset($_POST['customFields'])) {
                    foreach ($_POST['customFields'] as $customField) {
                        $customFields[$customField['customFieldId']] = $customField['customFieldVal'];
                    }

                }
                $this->Sample->custom_fields = json_encode($customFields, JSON_FORCE_OBJECT);
            }

            else
            {
                $this->Sample->custom_fields = json_encode($custArr, JSON_FORCE_OBJECT);
            }
            

            $this->Sample->registered_by = getUserId();
            $this->Sample->client = $client;
            $this->Sample->sample_note = isset($_POST['sample_note']) ? $_POST['sample_note'] : '' ; 
            //$this->Sample->sample_note = $_POST['sampleNotes'];
            //$this->Sample->sample_type = 'L';
            //$this->Sample->leg_type = $sampleSubmit['thisMatrix'];

            $legVolumeA = (int)LEGIONELLA_FILTER_VOLUME_A;
            $legVolumeB = (int)LEGIONELLA_FILTER_VOLUME_B;
            $legVolumeC = (int)LEGIONELLA_FILTER_VOLUME_C;

            if($sampleSubmit['thisMatrix'] == 'A'){
                $legVolume = $legVolumeA;
            }

            else if($sampleSubmit['thisMatrix'] == 'B'){
                $legVolume = $legVolumeB;                
            }

            else if($sampleSubmit['thisMatrix'] == 'C'){
                $legVolume = $legVolumeC;                
            }

            else{
                //fallback to the most common
                $legVolume = $legVolumeA;
            }


            $extraArray = array('follow'=> $sampleSubmit['thisFollow'], 'type'=> '', 'temperature' => '', 'filter_volume' => $legVolume );
            $this->Sample->sample_extra = json_encode($extraArray);

            if(!empty($_POST['innocDate'])){
                $date = new DateTime($_POST['innocDate']);

                if(!empty($_POST['innocTime'])){
                    $time = new DateTime($_POST['innocTime']);
                    $merge = new DateTime($date->format('d-m-Y') .' ' .$time->format('H:i:s'));
                } else{
                    $merge = new DateTime($date->format('d-m-Y'));
                }

                $innoculated = $merge->getTimestamp();
                $this->Sample->sample_innoculated = $innoculated;
            }

            $this->Sample->save();

            $projReference = upa('projects', 'updateProjReference', array($this->Sample->project));
            $assignedProject = $this->Sample->project;
            $sampleId = $this->Sample->id;
            $this->newSampleId = $sampleId; 

            $event = 'Monster ' . $this->Sample->barcode . ' toegevoegd aan project';
            upa('changeTracker', 'changed', array(2, $this->Sample->project, $sampleId, False, $event, False, False), False);

            $nextGroupNumber = upa('sampleAnalysis', 'nextFreeGroupNumber', array(), False);

            //upa('printing', 'sampleRegFire', array($sampleId), False);
            array_push($printSampleIds, $sampleId);


            $profile = $sampleSubmit['thisProfile'];
            $profile = upa('researchProfiles', 'fetchProfileTip', array($profile), False);
            
            $assaysInProfile = upa('assayProfiles', 'fetchAnalysis', array($profile, True), False);            
            $followNumber = upa('sampleAnalysis', 'nextFreeFollowNumber', array($sampleId), False);
            $projectOrder = 0;

            if(is_array($assaysInProfile))
            {
                foreach ($assaysInProfile as $pAssay) 
                {
                    $thisOrder = $projectOrder + $pAssay['project_order'];
                    $said = upa('sampleAnalysis', 'registerSampleAnalysis', array($sampleId, $nextGroupNumber, $followNumber, $profile, $pAssay['id'], 0, $pAssay['assay'], $this->Sample->project, False, False, $thisOrder), False);
                    upa('results', 'registerResult', array($sampleId, $said, $profile, $pAssay['id']), False);
                    //upa('printing', 'assayAddFire', array($said), False);
                    array_push($printSAids, $said);
                    $followNumber++;
                }
            }                                    
        }        

        upa('printing', 'batchProcess', array($printSampleIds, $printSAids, False), False);
        $nextBar = $this->generateBarCode(True, 'L');
        $jsonObj = array();
        $jsonObj['nextBar'] = $nextBar['bar_full'];
        $jsonObj['project'] = $assignedProject;

        if($faker == False)
        {
            print json_encode($jsonObj, JSON_FORCE_OBJECT);
        }
        
    }


    function commitBuffer($buffer){
        $this->render = 0;

        $legVolumeA = (int)LEGIONELLA_FILTER_VOLUME_A;
        $legVolumeB = (int)LEGIONELLA_FILTER_VOLUME_B;
        $legVolumeC = (int)LEGIONELLA_FILTER_VOLUME_C;


        $printBool = True;
        $portalSend = array();
        $printSampleIds = array();
        $printSAids = array();

        $lockSQL = 'LOCK TABLE samples WRITE;';
        $this->Sample->raw($lockSQL); 

        foreach($buffer as $projectSlug => $project){            

            $projectCreated = False;

            $follow = 1; 

            foreach($project as $sampleIdx => $sample){
             
                unset($this->Sample->id);

                $this->Sample->lock();

                $props = json_decode($sample['sample_properties'], JSON_FORCE_OBJECT);

                if(!is_array($props))
                {
                    $props = array();
                }
                
                $props = array_column($props, null, 'property_name');

                //legionella from portal. 
                if($sample['sample_research_type'] == '3')
                {
                    $matrix = checkKeyOrBlank($props, 'matrixType', 'value');
                    $barParts = $this->generateBarCode(True, 'L', False, $matrix );
                }
             
                else
                {
                    $barParts = $this->generateBarCode(True);
                }



                $this->Sample->client = $sample['client'];
                $this->Sample->subclient = 0;                
                $this->Sample->barcode = $barParts['bar_full'];
                $this->Sample->date_registered = time();
                $this->Sample->follow_no = $barParts['bar_follow'];
                $this->Sample->tht_code = $sample['tht_code'];

                if($sample['sample_research_type'] == '2')
                {
                    $this->Sample->sample_type = 'R';
                }

                if($sample['sample_research_type'] == '3')
                {                    
                    $this->Sample->sample_type = 'L';
                    $this->Sample->leg_type = $matrix;
                }

                $this->Sample->save();

                $this->Sample->unlock();

                $this->Sample->id = $this->Sample->lastInsertId;
           
                if($projectCreated == False)
                {                    
                    $projectName = $barParts['bar_full'];

                    if(!empty(trim($sample['project_name'])))
                    {
                        $projectName = $sample['project_name'];
                    }
                    

                    if($sample['sample_research_type'] == '3')
                    {     
                        
                        //leg
                        $samplingTime = checkKeyOrBlank($props, 'waterSamplingTime', 'value');

                        $preloads = [
                            0 => [
                                'preloadFieldId' => 'preload_project_bemonster_tijd',
                                'preloadFieldVal' => $samplingTime
                            ]
                        ];
                        
                        
                        $otfProjectId = upa('projects', 'createProject', array($this->Sample->client, 0, $projectName, time(), $preloads, False, 1, $sample['portal_project']), 0);
                    }

                    elseif($sample['sample_research_type'] == '2')
                    {     

                        
                        //rodac
                        $samplingTime = checkKeyOrBlank($props, 'rodacSamplingTime', 'value');

                        $projectExtra = json_encode(array('sample_method' => MESA_STD_RODACSMPL_METHOD,  'number_of_blanks' => '0'), JSON_FORCE_OBJECT);

                        $preloads = [
                            0 => [
                                'preloadFieldId' => 'preload_project_bemonster_tijd',
                                'preloadFieldVal' => $samplingTime
                            ]
                        ];
                        
                        
                        $otfProjectId = upa('projects', 'createProject', array($this->Sample->client, 0, $projectName, time(), $preloads, $projectExtra, 2, $sample['portal_project']), 0);
                    }

                    else
                    {
                        $otfProjectId = upa('projects', 'createProject', array($this->Sample->client, $this->Sample->subclient, $projectName, time(), False, False, False, $sample['portal_project']), 0);
                    }
                    

                                                            
                    $this->Sample->project = $otfProjectId;
                    $_POST['createProject'] = 'False';
                }

                upa('projects', 'setSamplingDate', array($this->Sample->project, $sample['sampling_date']), False);


                //set receive date/time to row value, unless this is not a THT sample.
                $thtFlag = checkKeyOrFalse($sample, 'tht');
                $thtFlag = filter_var($thtFlag, FILTER_VALIDATE_BOOLEAN);


                if($thtFlag === True){
                    $thisDate = checkKeyOrFalse($sample, 'receive_date');
                    $thisTime = checkKeyOrFalse($sample, 'receive_time');
                }else{                                        
                    $thisDate = $_POST['commit_receive_date'];
                    $thisTime =  $_POST['commit_receive_time'];
                }

                upa('projects', 'setReceivedDateAndTime', array($this->Sample->project, $thisDate, $thisTime), False);


                $this->Sample->description = $sample['sample_name'];

                if($sample['sample_research_type'] == '3')
                {
                    $this->Sample->description = LEGIONELLA_STD_DESCRIPTION;
                    $this->Sample->sample_type = 'L';
                    $this->Sample->leg_type = $matrix;

                    $temperature = checkKeyOrBlank($props, 'waterTemperature', 'value');
                    $waterLineType = checkKeyOrBlank($props, 'waterLineType', 'value');

                    if($matrix == 'A'){
                        $legVolume = $legVolumeA;
                    }
        
                    else if($matrix == 'B'){
                        $legVolume = $legVolumeB;                
                    }
        
                    else if($matrix == 'C'){
                        $legVolume = $legVolumeC;                
                    }
        
                    else{
                        //fallback to the most common
                        $legVolume = $legVolumeA;
                    }

                    $extraArray = array('follow'=> $follow, 'type'=> $waterLineType, 'temperature' => $temperature, 'filter_volume' => $legVolume );
                    $this->Sample->sample_extra = json_encode($extraArray);
                }
                

                if($sample['sample_research_type'] == '2')
                {
                    $this->Sample->sample_type = 'R';
                    $this->Sample->description = RODAC_STD_DESCRIPTION;

                    $location = checkKeyOrBlank($props, 'rodacRoom', 'value');

                    $extraArray = array('follow'=> $follow, 'location'=> $location);
                    $this->Sample->sample_extra = json_encode($extraArray);
                  
                }
                
                //$this->Sample->barcode = $barParts['bar_full'];
                //$this->Sample->date_registered = time();
                //$this->Sample->follow_no = $barParts['bar_follow'];
                //$this->Sample->tht_code = $sample['tht_code'];
                
                
                $this->Sample->sampling_method = $sample['sampling_method'];
                $this->Sample->registered_by = getUserId();

                $customFields = array('details' => $sample['sample_details']);                
                
                $this->Sample->custom_fields = json_encode($customFields, JSON_FORCE_OBJECT);
                
                $this->Sample->sample_note = '';

                if(!empty($sample['misc_directions']))
                {
                    $this->Sample->portal_notes = $sample['misc_directions'];
                }
                

                
                $this->Sample->source = $sample['source'];
                

                //csv
                if((int)$sample['source'] === 1){
                    $this->Sample->client_description = $sample['sample_name'];
                }                   

                //LIMS, saved as buffer 
                if((int)$sample['source'] === 2){
                    $this->Sample->client_description = 'Geen omschrijving beschikbaar';
                }                   
              
                //portal 
                if((int)$sample['source'] === 3){
                  $this->Sample->client_description = $sample['sample_name'];
                  $this->Sample->portal_sample_id = $sample['portal_id'];
                  $this->Sample->portal_project_id = $sample['portal_project'];
                  $this->Sample->portal_analyses = $sample['portal_analyses'];
                }

                //is a PG set? if not, grab default
                if(!empty($sample['portal_product_group_id']))
                {
                    $this->Sample->portal_product_group_id = $sample['portal_product_group_id'];
                } 

                else
                {
                    $defaultPG = upa('productGroups', 'getClientDefaultGroup', array($this->Sample->client), False);                
                    $this->Sample->portal_product_group_id = ($defaultPG) ? $defaultPG['portal_id'] : null;
                }

                $infoArray = array('misc_directions' => $sample['misc_directions'] , 'analyses_selected' => $sample['analyses_selected'] );
                $this->Sample->analyses_data = json_encode($infoArray, JSON_FORCE_OBJECT);
                $assignedProject = $this->Sample->project;
                $this->Sample->save();
                $follow++;
                                                       
                
                //$lockSQL = 'UNLOCK TABLES;';
                //$this->Sample->raw($lockSQL); 

                $sampleId = $this->Sample->id;


                
                //add legionelal samples if legionella 
                if($sample['sample_research_type'] == '3')
                {

                    $nextGroupNumber = upa('sampleAnalysis', 'nextFreeGroupNumber', array(), False);

                    $profile = checkKeyOrBlank($props, 'matrixBaseProfile', 'value');
                   
                    $profile = upa('researchProfiles', 'fetchProfileTip', array($profile), False);
                    
                    $assaysInProfile = upa('assayProfiles', 'fetchAnalysis', array($profile, True), False);            


                    $followNumber = upa('sampleAnalysis', 'nextFreeFollowNumber', array($sampleId), False);
                    $projectOrder = 0;
        
                    if(is_array($assaysInProfile))
                    {
                        foreach ($assaysInProfile as $pAssay) 
                        {
                            $thisOrder = $projectOrder + $pAssay['project_order'];
                            $said = upa('sampleAnalysis', 'registerSampleAnalysis', array($sampleId, $nextGroupNumber, $followNumber, $profile, $pAssay['id'], 0, $pAssay['assay'], $this->Sample->project, False, False, $thisOrder), False);
                            upa('results', 'registerResult', array($sampleId, $said, $profile, $pAssay['id']), False);
                            //upa('printing', 'assayAddFire', array($said), False);
                            array_push($printSAids, $said);
                            $followNumber++;
                        }
                    }             
                }


                //register meta keys
                $metaData = json_decode($sample['meta'], JSON_FORCE_OBJECT);

                //originating from portal, load in meta_data_key_id
                //and store if it is set
                $portalMeta = array();
                if($sample['source'] == 3){
                  $portalMeta = json_decode($sample['portal_meta'], JSON_FORCE_OBJECT);
                }

                $morder = 1;
                foreach($metaData as $metaName => $metaValue){                    

                    if(is_array($metaValue))
                    {
                        $metaName = $metaValue[0];
                        $metaValue = $metaValue[1];                        
                    }
        

                    $meta_data_key_id = (array_key_exists($metaName,$portalMeta ) ? $portalMeta[$metaName] : NULL);
                    upa('metadata', 'add', array($sampleId, $metaName, $metaValue, $meta_data_key_id, $morder), False);
                    $morder++;
                }

                if($projectCreated == False){
                    $projReference = upa('projects', 'updateProjReference', array($this->Sample->project));
                    $projectCreated = True; //so we dont recreate for the next sample in the project
                }

                $lteArray = array('sample_barcode' => $this->Sample->barcode, 'sample_description' => $this->Sample->description, 'sample_project' => $projReference, 'sample_project_id' => $this->Sample->project);

                //upa('labtalkEvents', 'automation', array('SAMPLEADD', $lteArray), 0);
                
                $event = 'Monster ' . $this->Sample->barcode . ' toegevoegd aan project';
                
                if($thtFlag === True)
                {
                    $event = $event . ' - THT code was: ' . $sample['tht_code'];
                }
                                
                
                upa('changeTracker', 'changed', array(2, $this->Sample->project, $sampleId, False, $event, False, False), False);
                
                upa('projects', 'projectEdited', array($this->Sample->project), False);

                array_push($printSampleIds, $sampleId);

                //add to portal send array
                if($sample['source'] == 3){
                  $thisSampleToSend = array(  'portal_sample_id' => $this->Sample->portal_sample_id,
                                              'portal_project_id' => $this->Sample->portal_project_id,
                                              'mesa_sample_id' => $sampleId,
                                              'mesa_project_id' =>   $this->Sample->project,
                                              'mesa_barcode' => $this->Sample->barcode,
                                              'mesa_project_reference' => $projReference
                                            );
                  array_push($portalSend, $thisSampleToSend);
                }
            
            }
        }

        if($printBool != False){
            upa('printing', 'batchProcess', array($printSampleIds, [], False), False);
        }

        //if any, send to portal
        if(!empty($portalSend)){
          upa('portal', 'acceptSamples', array($portalSend), False);
        }
        
        $this->Sample->subclient = 0;
    }

    function fakerEntryPoint($data, $insertIntoProject = False, $legionella=False, $rodac = False)
    {

        $settings = [
            'printBool' => False, 
            'createProject' => ($insertIntoProject != False) ? False : True,  
            'samplen' => 1, 
            'sampleNotes' => 'Testing sample',
            'innocDate' => null, 
            'innocTime' => null,
            'registerType' => 'standard',
            'bufferSamplingTrack' => 'Onbekend',
            'thtTriggerDate' => null, 
            'thtStorage' => 0,            
            'bufferReceiveDateTrack' => '29-04-2020',

            'customFields' => [
                [
                    'customFieldId' => 'details',
                    'customFieldVal' => (isset($data->details) ? $data->details : '')
                ]
            ],

            'sampleFields' => [
                [
                    'sampleField' => 'client_name',
                    'sampleFieldValue' => $data->client 
                ],

                [
                    'sampleField' => 'client',
                    'sampleFieldValue' => $data->client
                ],

                [
                    'sampleField' => 'project',
                    'sampleFieldValue' =>  ($insertIntoProject != False) ? $insertIntoProject : 'CREATE' 
                ],

                [
                    'sampleField' => 'description',
                    'sampleFieldValue' => $data->description 
                ],

            ],

            'projectPreload' => [

                [
                    'preloadFieldId'  => 'preload_project_monster',
                    'preloadFieldVal' => ''
                ],

                [
                    'preloadFieldId'  => 'preload_project_bemonster_tijd',
                    'preloadFieldVal' => ''
                ],

                [
                    'preloadFieldId'  => 'preload_project_ontvangst',
                    'preloadFieldVal' => '29-04-2020'
                ],

                [
                    'preloadFieldId'  => 'preload_project_tijd_ontvangst',
                    'preloadFieldVal' => ''
                ]

            ]
     
        ];        

        $_POST = $settings;


        if($legionella == False && $rodac == False)
        {
            
            $this->save(True, True);
        }

        if($legionella == True)
        {

            

            $_POST['client'] = $data->client;
            $_POST['clientReference'] = '';

            $samplePackage = array(); 
            $samplePackage[0] = array();

            $samplePackage[0]['thisMatrix'] = $data->matrix;
            $samplePackage[0]['thisSamplingMethod'] = $data->sampmethod;
            $samplePackage[0]['thisFollow'] = $data->follow_no;
            $samplePackage[0]['thisProfile'] = $data->profile;

                        
            $_POST['samplePackage'] = $samplePackage;

            $_POST['selected_project_id'] =  ($insertIntoProject != False) ? $insertIntoProject : 'CREATE' ;

            $this->saveLeg(True);
        }

        if($rodac == True)
        {
            
            $_POST['client'] = $data->client;
            $_POST['clientReference'] = '';
            $_POST['number_of_blanks'] = 0;
            $_POST['samplingMethod'] = $data->sampmethod;
            $_POST['sample_note'] = 'Test';
            
            

            $samplePackage = array(); 
            $samplePackage[0] = array();

            $samplePackage[0]['follow'] = $data->follow_no;            
            $samplePackage[0]['thisFollow'] = $data->follow_no;
            $samplePackage[0]['thisLocation'] = 'test';
            $samplePackage[0]['thisDescription'] =  (isset($data->details) ? $data->details : '');
            #$samplePackage[0]['thisDescription'] = 'test';
            
            $_POST['samplePackage'] = $samplePackage;

            $_POST['selected_project_id'] =  ($insertIntoProject != False) ? $insertIntoProject : 'CREATE' ;

            

            $this->saveRodac(True);

        }
        
        return $this->newSampleId;

    }


    function save($nextBar = False, $faker = False)
    {   
        
        $this->render = 0;
                              
        $bufferCommit = False;

        $printSampleIds = array();
        $printSAids = array();

        //load in sample fields into model
        foreach($_POST['sampleFields'] as $sampleField) {            
            $sampleFieldName = $sampleField['sampleField'];                        
            $this->Sample->$sampleFieldName = $sampleField['sampleFieldValue'];
        }

        //test
        $defaultPG = upa('productGroups', 'getClientDefaultGroup', array($this->Sample->client), False);      

        //put custom fields into array
        $customFields = array();
        if (isset($_POST['customFields'])) {
            foreach ($_POST['customFields'] as $customField) {
                $customFields[$customField['customFieldId']] = $customField['customFieldVal'];
            }
        }

        //COMMIT TO BUFFER NOT TO SAMPLE TABLE
        if(isset($_POST['registerType']) && $_POST['registerType'] != 'standard'){
            $bufferCommit = True; //set buffer flag for downstream project + barcode generation
            $bufferPayload = array();            
            
            
            //project?
            for ($i = 0; $i < $_POST['samplen']; $i++) {
                $bufferPayload[$i]['client'] = $this->Sample->client;
                $bufferPayload[$i]['source'] = 2;
                $bufferPayload[$i]['sampling_date'] = $_POST['bufferSamplingTrack'];
                $bufferPayload[$i]['sampling_method'] = $this->Sample->sampling_method;
                $bufferPayload[$i]['sample_name'] = $this->Sample->description;
                $bufferPayload[$i]['sample_details'] = $customFields['details'];
                $bufferPayload[$i]['tht_date'] = '';
                $bufferPayload[$i]['project_name'] = checkKeyOrEmpty($_POST, 'customProjectName');

                $bufferPayload[$i]['receive_date'] = $_POST['bufferReceiveDateTrack'];
                $bufferPayload[$i]['receive_time'] = $_POST['bufferReceiveTimeTrack'];
                $bufferPayload[$i]['portal_product_group_id'] = ($defaultPG) ? $defaultPG['portal_id'] : null;

                
                if($_POST['registerType'] == 'tht'){
                    $bufferPayload[$i]['tht'] = 1;
                    $bufferPayload[$i]['authorized'] = 1;
                    $bufferPayload[$i]['tht_date'] = $_POST['thtTriggerDate'];     
                    $bufferPayload[$i]['tht_storage']  = $_POST['thtStorage'];
                } else{
                    $bufferPayload[$i]['tht'] = 0;
                }
            }

            upa('sampleBuffers', 'commitFromRegisterScreen', array($bufferPayload), False);
        } //END COMMIT TO BUFFER TABLE

        //COMMIT TO TABLE
        if(isset($_POST['registerType']) && $_POST['registerType'] == 'standard'){
            $printBool = checkKeyOrFalse($_POST, 'printBool');
            $printBool = filter_var($printBool, FILTER_VALIDATE_BOOLEAN);

            if (isset($_POST['requested'])) {
                $requested = $_POST['requested'];
            } else {
                $requested = False;
            }
            if (isset($_POST['roaming'])) {
                $roaming = $_POST['roaming'];
            } else {
                $roaming = False;
            }
            if (isset($_POST['excluded'])) {
                $excluded = $_POST['excluded'];
            } else {
                $excluded = False;
            }

            //project created create samples
            $this->Sample->subclient = 0;

            //$profiler->addCheckpoint('preamble  complete');

            // //lock 
         
            for ($i = 0; $i < $_POST['samplen']; $i++) {


                unset($this->Sample->id);
                
                $this->Sample->lock();
                              
                $barParts = $this->generateBarCode(True);
                
                //Save this and unlock, so we can hopefully get no duplicate barcodes
                $this->Sample->barcode = $barParts['bar_full'];
                $this->Sample->follow_no = $barParts['bar_follow'];
                $this->Sample->date_registered = time();
                $this->Sample->save(False);     //no transaction                
                
                $this->Sample->unlock(); 
                
                $this->Sample->id = $this->Sample->lastInsertId; 
                                                
                $updateProjReference = False;

                //create project
                if ($_POST['createProject'] == 'True') {
                                        
                    $projectName =  $barParts['bar_full']; 

                    if(!empty($_POST['customProjectName']))
                    {
                        $projectName = $_POST['customProjectName'];
                    }

                    $otfProjectId = upa('projects', 'createProject', array($this->Sample->client, $this->Sample->subclient, $projectName, time(), $_POST['projectPreload']), 0);
                    
                    $this->Sample->project = $otfProjectId;
                    
                    $_POST['createProject'] = 'False';
                    
                    $updateProjReference = True; 
                }

                #$this->Sample->barcode = $barParts['bar_full'];
                $this->Sample->client_description = 'Geen omschrijving beschikbaar';
                #$this->Sample->follow_no = $barParts['bar_follow'];
                #$this->Sample->date_registered = time();
                $this->Sample->registered_by = getUserId();
                $this->Sample->custom_fields = json_encode($customFields, JSON_FORCE_OBJECT);
                $this->Sample->sample_note = $_POST['sampleNotes'];
                $this->Sample->portal_product_group_id =  $defaultPG['portal_id'] ?? null; 

                if(!empty($_POST['innocDate'])){
                    $date = new DateTime($_POST['innocDate']);

                    if(!empty($_POST['innocTime'])){
                        $time = new DateTime($_POST['innocTime']);
                        $merge = new DateTime($date->format('d-m-Y') . ' ' .$time->format('H:i:s'));
                    } else{
                        $merge = new DateTime($date->format('d-m-Y'));
                    }

                    $innoculated = $merge->getTimestamp();
                    $this->Sample->sample_innoculated = $innoculated;
                }

                $assignedProject = $this->Sample->project;
                
                $this->Sample->save();

                //unlock
                //$lockSQL = 'UNLOCK TABLES;';
                //$this->Sample->raw($lockSQL); 

                //update project reference                                
                $projReference = upa('projects', 'updateProjReference', array($this->Sample->project));                                

                //create a labtalkEvent
                $lteArray = array('sample_barcode' => $this->Sample->barcode, 'sample_description' => $this->Sample->description, 'sample_project' => $projReference, 'sample_project_id' => $this->Sample->project);
                //upa('labtalkEvents', 'automation', array('SAMPLEADD', $lteArray), 0);

                //$profiler->addCheckpoint('Labtalk done');

                //get sample id and dump selected analysis
                $sampleId = $this->Sample->id;
                $this->newSampleId = $sampleId; 

                $event = 'Monster ' . $this->Sample->barcode . ' toegevoegd aan project';
                upa('changeTracker', 'changed', array(2, $this->Sample->project, $sampleId, False, $event, False, False), False);

                //$profiler->addCheckpoint('Changetracker done');

                //run revisioner
                //upa('revisions', 'registerRevision', array('SCOPE_PROJECT_SAMPLEADD', $this->Sample->project, $this->Sample->barcode), False);
                upa('projects', 'projectEdited', array($this->Sample->project), False);

                //$profiler->addCheckpoint('project edited done');

                //fire sticker event
                if($printBool != False){
                  //upa('printing', 'sampleRegFire', array($sampleId), False);
                  array_push($printSampleIds, $sampleId);
                }

                //save the selected research profiles and assays into sampleAnalysis Table
                //if it is a profile, the underlying assys need to be stored seperatly.
                $orderNumber = 0;
                if (isset($_POST['requested'])) {

                    $followNumber = 1;

                    foreach ($_POST['requested'] as $arrIndex => $request) {

                        if (!isset($request['resType'])) {
                            continue;
                        }

                        if (is_array($excluded) && array_key_exists($arrIndex, $excluded)) {
                            $thisExcluded = $excluded[$arrIndex];
                            //parray($thisExcluded);
                        } else {
                            $thisExcluded = array();
                        }

                        $nextGroupNumber = pa('sampleAnalysis', 'nextFreeGroupNumber', array(), False);

                        if ($request['resType'] == 'profile') {
                            $profile = $request['resId'];

                            //get this ordered by
                            $assaysInProfile = pa('assayProfiles', 'fetchAnalysis', array($profile, True), False);

                            foreach ($assaysInProfile as $pAssay) {
                                //skip if it has been excluded
                                if (in_array($pAssay['id'], $thisExcluded)) {
                                    continue;
                                }

                                $projectOrder = $orderNumber + $pAssay['project_order'];
                                $said = upa('sampleAnalysis', 'registerSampleAnalysis', array($sampleId, $nextGroupNumber, $followNumber, $profile, $pAssay['id'], 0, $pAssay['assay'], $this->Sample->project, False, False, $projectOrder), False);
                                //$orderNumber++;

                                upa('results', 'registerResult', array($sampleId, $said, $profile, $pAssay['id']), False);
                                //fire label print
                                if($printBool != False){
                                  //upa('printing', 'assayAddFire', array($said), False);
                                  array_push($printSAids, $said);
                                }

                                $followNumber++;
                            }

                            $orderNumber = $projectOrder;
                        }

                        if ($request['resType'] == 'assay') {

                            $orderNumber++;
                            $projectOrder = $orderNumber;
                            $said = upa('sampleAnalysis', 'registerSampleAnalysis', array($sampleId, $nextGroupNumber, $followNumber, 0, $request['assayId'], 0, $request['assayId'], $this->Sample->project, False, False, $projectOrder), False);

                            $assay = $_POST['roaming'][$arrIndex]['assay_id'];
                            $dillutions = $_POST['roaming'][$arrIndex]['roam_dillution'];
                            $replicates = $_POST['roaming'][$arrIndex]['roam_replicates'];
                            //$reference = $_POST['roaming'][$arrIndex]['reference'];

                            $refValueArr = array();
                            foreach ($_POST['roaming'][$arrIndex] as $roamFieldId => $roamFieldValue) {
                                if (substr($roamFieldId, 0, 4) == 'ref_') {
                                    $refValueArr[$roamFieldId] = $roamFieldValue;
                                }
                            }

                            //$referenceScope = $_POST['roaming'][$arrIndex]['reference_scope'];
                            $reference = json_encode($refValueArr, JSON_FORCE_OBJECT);

                            //reference source 
                            $referenceSource = $_POST['roaming'][$arrIndex]['reference_source'];

                            $roamId = upa('roamingAnalysis', 'saveRoam', array($said, $assay, $dillutions, $replicates, $reference, $referenceSource), False);
                            upa('sampleAnalysis', 'setRoam', array($said, $roamId), False);
                            upa('results', 'registerResult', array($sampleId, $said, 0, $assay, $roamId), False);

                            //fire label print
                            if($printBool != False){
                              //upa('printing', 'assayAddFire', array($said), False);
                              array_push($printSAids, $said);
                            }

                            $followNumber++;
                        }
                    }
                }
            }
            


        } //END COMMIT TO SAMPLE TABLE


        //release print 
        upa('printing', 'batchProcess', array($printSampleIds, $printSAids, False), False);

        if ($nextBar !== False) {
            $nextBar = $this->generateBarCode(True);
        }

        $jsonObj = array();
        $jsonObj['nextBar'] = $nextBar['bar_full'];

        if(isset($assignedProject)){
            $jsonObj['project'] = $assignedProject;
        } else{
            $jsonObj['project'] = False;
        }

        //caught by screen to avoid loading in project which may not be assigned
        $jsonObj['bufferCommit'] = $bufferCommit;   

        if($faker == False)
        {
            print json_encode($jsonObj, JSON_FORCE_OBJECT);
        }
        


    }



    function sampleUpdateWrapper(){

        $this->render = False;
        $printSAids = array();
        $printWarning = False; 
        $warningSamples = []; 

        if(!isset($_POST['samplesToUpdate']) || count($_POST['samplesToUpdate']) == 0){
            print json_encode(array());
            return;
        }

        $lockSQL = 'LOCK TABLE samples WRITE;';
        $this->Sample->raw($lockSQL); 


        foreach($_POST['samplesToUpdate'] as $sampleItem){

            $_POST['sample'] = $sampleItem['sample'];

            if(isset($_POST['addRequested'])){
                $_POST['requested'] = $_POST['addRequested'];
            }

            if(isset($_POST['addRoaming'])){
                $_POST['roaming'] = $_POST['addRoaming'];
            }

            if(isset($_POST['addExcluded'])){
                $_POST['excluded'] = $_POST['addExcluded'];
            }

            $addedSaids = $this->addNewResearch(True, True);

            if($addedSaids === False)
            {
                $sampleInfoChannel = upa('samples', 'fetch', array($sampleItem['sample']), False);
                //add barcode to warningSamples
                array_push($warningSamples, $sampleInfoChannel['barcode']);
                $printWarning = True; 
            }

            if(is_array($addedSaids))
            {
                $printSAids = array_merge($addedSaids, $printSAids);
            }

            

        }

        $lockSQL = 'UNLOCK TABLES;';
        $this->Sample->raw($lockSQL); 

        upa('printing', 'batchProcess', array(array(), $printSAids, False), False);
        print json_encode(['warning' => $printWarning, 'warning_samples' => implode(',', $warningSamples)]);
    }

    function fakerAddNewResearch($data, $profile = False)
    {


            $settings = [
                'requested' => [
                    [
                        'resType' => ($profile == True) ? 'profile' : 'assay',
                        'assayId' => ($profile == False) ? $data->assay['id'] : '',
                        'resId' => ($profile == True) ? $data->profile : '',
                    ]
                ],
    
                'roaming' => ($profile == False) ? [
                    
                    [
                        'ref_kve' => '',
                        'roam_dillution' => $data->dillution,
                        'reference_source' => '',
                        'roam_replicates' => $data->replicates, 
                        'assay_id' => $data->assay['id']
                    ]

                    
                ] : [],
    
                'sample' => $data->sample['id'],
                'replace' => false
            ];
        

        $_POST = $settings;

        $saids = $this->addNewResearch(True);

        return $saids;

    }

   
    function addNewResearch($defer = False, $checkForEmpty = False)
    {
        
        //check for empty disallows new research to be added if sample already has research
        
        $this->render = 0;
        $addedSAids = array();

        if (isset($_POST['requested'])) {
            $requested = $_POST['requested'];
        } else {
            $requested = False;
        }
        if (isset($_POST['roaming'])) {
            $roaming = $_POST['roaming'];
        } else {
            $roaming = False;
        }
        if (isset($_POST['excluded'])) {
            $excluded = $_POST['excluded'];
        } else {
            $excluded = False;
        }
        if (isset($_POST['replace']) && $_POST['replace'] == true) {
            $replacing = True;
        } else {
            $replacing = False;
        }



        $nextFollowNumber = upa('sampleAnalysis', 'nextFreeFollowNumber', array($_POST['sample']), False);

        if($checkForEmpty == True){
            
            if($nextFollowNumber != 1){
                return False;
            }

        }

        $sampleId = $_POST['sample'];
        $sampleInfoChannel = upa('samples', 'fetch', array($sampleId), False);

        if(empty($sampleInfoChannel)){
            return;
        }

        if (isset($_POST['requested'])) {

            $followNumber = $nextFollowNumber;

            foreach ($_POST['requested'] as $arrIndex => $request) {


                if (!isset($request['resType'])) {
                    writeLog('Restype not found', ALPC_ERROR);
                    continue;
                }

                if (is_array($excluded) && array_key_exists($arrIndex, $excluded)) {
                    $thisExcluded = $excluded[$arrIndex];
                    //parray($thisExcluded);
                } else {
                    $thisExcluded = array();
                }

                $nextGroupNumber = pa('sampleAnalysis', 'nextFreeGroupNumber', array(), False);

                $orderNumber = upa('sampleAnalysis', 'getLastOrderNumber', array($sampleInfoChannel['project']), False );


                if ($request['resType'] == 'profile') {

                    $scope = 'SCOPE_SAMPLEINFO_PROFILEADD';
                    $profile = $request['resId'];
                    $changed = $profile;
                    $assaysInProfile = pa('assayProfiles', 'fetchAnalysis', array($profile, True), False);

                    foreach ($assaysInProfile as $pAssay) {

                        //skip if it has been excluded
                        if (in_array($pAssay['id'], $thisExcluded)) {
                            continue;
                        }

                        $orderNumber++;

                        if ($replacing == True) {
                            //check if exists already, if so, remove current
                            //$pAssay['assay']
                            upa('sampleAnalysis', 'checkForReplacement', array($sampleId, $pAssay['assay']));
                        }


                        $said = upa('sampleAnalysis', 'registerSampleAnalysis', array($sampleId, $nextGroupNumber, $followNumber, $profile, $pAssay['id'], 0, $pAssay['assay'], $sampleInfoChannel['project'], False, False, $orderNumber), False);

                        $event = 'Analyse (ID:' . $pAssay['assay'] .') toegevoegd aan monster, uit profiel:' . $profile;
                        upa('changeTracker', 'changed', array(6, $sampleInfoChannel['project'], $sampleInfoChannel['id'], $said, $event, False, False), False);

                        upa('results', 'registerResult', array($sampleId, $said, $profile, $pAssay['id']), False);
                        //fire label print
                        if($defer !== True){
                            upa('printing', 'assayAddFire', array($said, False), False);                            
                        }

                        array_push($addedSAids, $said);
                        
                        $followNumber++;
                    }


                }

                if ($request['resType'] == 'assay') {

                    $orderNumber++;
                    $projectOrder = $orderNumber;
                    $said = upa('sampleAnalysis', 'registerSampleAnalysis', array($sampleId, $nextGroupNumber, $followNumber, 0, $request['assayId'], 0, $request['assayId'], $sampleInfoChannel['project'], False, False, $projectOrder), False);
                    $assay = $_POST['roaming'][$arrIndex]['assay_id'];

                    $scope = 'SCOPE_SAMPLEINFO_ASSAYADD';
                    $changed = $assay;

                    $dillutions = $_POST['roaming'][$arrIndex]['roam_dillution'];
                    $replicates = $_POST['roaming'][$arrIndex]['roam_replicates'];
                    //$reference = $_POST['roaming'][$arrIndex]['reference'];

                    $refValueArr = array();
                    foreach ($_POST['roaming'][$arrIndex] as $roamFieldId => $roamFieldValue) {
                        if (substr($roamFieldId, 0, 4) == 'ref_') {
                            $refValueArr[$roamFieldId] = $roamFieldValue;
                        }
                    }


                    //$referenceScope = $_POST['roaming'][$arrIndex]['reference_scope'];
                    $reference = json_encode($refValueArr, JSON_FORCE_OBJECT);

                    $referenceSource = $_POST['roaming'][$arrIndex]['reference_source'];                    

                    $roamId = upa('roamingAnalysis', 'saveRoam', array($said, $assay, $dillutions, $replicates, $reference, $referenceSource), False);
                    upa('sampleAnalysis', 'setRoam', array($said, $roamId), False);
                    upa('results', 'registerResult', array($sampleId, $said, 0, $assay, $roamId), False);

                    $event = 'Losse analyse (ID:' . $request['assayId'] .') toegevoegd aan monster';
                    upa('changeTracker', 'changed', array(6, $sampleInfoChannel['project'], $sampleInfoChannel['id'], $said, $event, False, False), False);

                    //fire label print
                    if($defer !== True){
                        upa('printing', 'assayAddFire', array($said, False), False);
                    }

                    array_push($addedSAids, $said);

                    //function registerResult( $sample, $said, $profile, $assay, $roamingId = False){

                    $followNumber++;
                }
            }
        }

        $this->estimateEndPoints($_POST['sample']);
        //upa('revisions', 'registerRevision', array($scope, $sampleId, $changed), False);
        upa('projects', 'projectEdited', array($sampleInfoChannel['project'], $sampleId), False);
        upa('sampleAnalysis', 'checkProjectReady', array($sampleInfoChannel['project']), False);
        return $addedSAids;
    }


    function estimateEndPoints($sampleID)
    {
        $this->Sample->free();
        $this->Sample->where('id', $sampleID);
        $sample = $this->Sample->search();

        if (empty($sample)) {
            return False;
        }

        //needs to find all analysis attached
        //check if innoculation started?

        //if not started return -1?
        //ignore -1 to estimate end points? or set project end for -1 aswell?

        //if started, for each analysis
        //find anchor point for analysis, add duration, write in array
        //at end, pick longest running

        $endPoints = array('0');

        $this->Sample->arrayToModel($sample['0']);

        $projInfo = upa('projects', 'fetchProjectInfo', array($this->Sample->project));
        $projectFields = json_decode($projInfo['custom_fields'], True);
        $customFields = json_decode($this->Sample->custom_fields, True);

        $sampleSa = upa('sampleAnalysis', 'fetchAnalysisArray', array($this->Sample->id));
        $now = time();
        $ifWasNow = -1;
        $canCalculate = True;

        foreach ($sampleSa as $analysis) {

            $assayBase = upa('assays', 'fetchSingle', array($analysis['assay_base']));
            
            //check if this is filled?
            if(empty($assayBase['duration'])){
                $assayBase['duration'] = 0;
            }

            if ($assayBase['start_from'] == 'r') {
                $endPoint = $this->Sample->date_registered + (86400 * $assayBase['duration']);
            } elseif($assayBase['start_from'] == 'i'){
                if($this->Sample->sample_innoculated == ''){
                    //$endPoint = $now + (86400 * $assayBase['duration']);
                    $endPoint = -1;
                    $canCalculate = False;
                } else{
                    $endPoint = $this->Sample->sample_innoculated + (86400 * $assayBase['duration']);
                }
            } else{
                $anchoring = explode(':', $assayBase['start_from']);
                if ($anchoring['0'] == 'p') {
                    if(array_key_exists($anchoring[1], $projectFields)){
                        $offSetTime = strtotime($projectFields[$anchoring['1']]);
                    }
                } elseif ($anchoring['0'] == 's') {
                    if(array_key_exists($anchoring[1], $customFields)){
                        $offSetTime = strtotime($customFields[$customFields['1']]);
                    }
                }
                $endPoint = $offSetTime + (86400 * $assayBase['duration']);
            }
            
            //check if the end point is weekend or holiday, and advance to the next point if it is
            $holidays = new holidays();
            
            if($endPoint > 0)
            {
                $endPoint = $holidays->getReadDateFromTimestamp($endPoint,'timestamp');
            }
                    
            upa('sampleAnalysis', 'setEndPoint', array($analysis['id'], $endPoint));

            array_push($endPoints, $endPoint);
        }

        //save endpoint
        $endPoint = max($endPoints);

        if($canCalculate == False){
          $this->Sample->predicted_end = -1;
        } else{
          if ($endPoint == 0) {
              $this->Sample->predicted_end = $now;
          } else {
              $this->Sample->predicted_end = $endPoint;
          }
        }

        $this->Sample->save();
        upa('projects', 'updateEndPoints', array($this->Sample->project));

        return $this->Sample->predicted_end;

    }

    function returnRecentAdded()
    {

        $this->render = 0;
        $query = 'SELECT * FROM `samples` ORDER BY `id` DESC LIMIT ' . MESA_RECENTLY_ADDED_AMOUNT;
        $result = $this->Sample->customQuery($query, array());

        if (empty($result)) {
            print 'No samples present!';
        } else {
            foreach ($result as $sample) {
                print generateHTML('recentlyAdded', $sample);
            }
        }
    }

    function lookup($entryScan = False)
    {

        if ($entryScan == False) {
            $entryScan = 'null';
        }
        $profilesAvail = pa('researchProfiles', 'fetchGlobalProfiles', array());
        $assaysAvail = pa('assays', 'fetchAssaysList', array());
        $this->_template->set('global_profile_list', $profilesAvail);
        $this->_template->set('assay_list', '');
        $this->_template->set('matrix_content', upa('matrix', 'matrixDropdown', array(), False));
        $this->_template->set('cust_profile_list', '');
        $sampleNoteField =  generateHTML('customFields/notes', array());
        $this->_template->set('sample_notes', $sampleNoteField);

        //roaming assay form
        $roamF = new formFactory('researchProfiles');
        $roamF->setId('roamingForm');
        $roamF->action('');
        $roamF->method('');
        $roamF->addClass('');
        $roamF->setTemplate('generic');

        $roamF->addTextArea('roam_dillution', '{MESA_SAD_ROAMDILLUTIONSETUP}', 'text', 'input-block-level', '0=0', '{MESA_SAD_ROAMDILLUTIONINFO}');
        $roamF->addInputField('roam_replicates', '{MESA_SAD_REPLICATESSETUP}', 'text', 'input-block-level', '0', '{MESA_SAD_REPLICATESSETUP}');
        $roamF->addInputField('roam_reference', '{MESA_ADD_REFERENCEVALUE}', 'text', 'input-block-level', '0', '{MESA_ADD_REFERENCEVALUE}');
        $roamF->submitTrough('saveRoamingButton', 'saveRoamingAssay');
        $this->_template->set('roaming_form', $roamF->render());
        $this->_template->set('entry_scan', $entryScan);
        
    }

    function buildSampleInfoBlock($id, $project)
    {
        $this->doNotRenderHeader = 1;
        $this->render = 0;
        #$this->Sample->where('id', $id);
        $this->Sample->select(['id', 'barcode', 'description', 'sampling_method', 'custom_fields','sample_extra', 'source', 'stored_in', 'diluted_at' ]);
        $this->Sample->where('project', $project);
        $this->Sample->order('id', 'ASC');
        $this->Sample->order('follow_no', 'ASC');
        $results = array();
        $pResults = $this->Sample->search();
         

        $followNo = 1;
        foreach($pResults as $idx => $sample){
          if($sample['id'] == $id ){
              $results['0'] = $sample;
              break;
          } else{
              unset($results[$idx]);
              $followNo++;
          }
        }

        $infoArr['0']['alias'] = '{MESA_SLU_BARCODE}';
        $infoArr['0']['value'] = $results['0']['barcode'];

        $infoArr['0.1']['alias'] = 'Volgnummer';
        $infoArr['0.1']['value'] = $followNo;

        $infoArr['1']['alias'] = '{MESA_SLU_DESCRIPTION}';
        $infoArr['1']['value'] = $results['0']['description'];

        $infoArr['2']['alias'] = '{MESA_SLU_SAMPLEMETHOD}';
        $infoArr['2']['value'] = upa('sampleProcedures', 'procedureIdToName', array($results['0']['sampling_method']));


        //get array of custom fields into mem
        $customValues = json_decode($results['0']['custom_fields'], True);

        //fetch custom fields
        $customFields = upa('sampleFields', 'fetchCustomFields', array());

        $i = count($infoArr);

        foreach ($customFields as $field) {
            //$infoArr[$field['alias']] = $customValues[$field['name']];
            $infoArr[$i]['alias'] = $field['alias'];
            if (isset($customValues[$field['name']])) {
                $infoArr[$i]['value'] = $customValues[$field['name']];
            } else {
                $infoArr[$i]['value'] = '';
            }

            $i++;
        }


        //sample extras
        $sampleExtra = json_decode($results['0']['sample_extra'], JSON_FORCE_OBJECT);
        $i++;
        if(is_array($sampleExtra)){
            foreach($sampleExtra as $extraKey => $extraValue){


                if($extraKey == 'follow'){
                    continue;
                    $extraDescription = 'Volgnummer';
                }

                if($extraKey == 'type'){
                    $extraDescription = 'Tappunt type';
                }

                if($extraKey == 'temperature'){
                    $extraDescription = 'Temperatuur';
                }

                if($extraKey == 'location'){
                    $extraDescription = 'Ruimte';
                }

                if($extraKey == 'filter_volume'){
                    $extraDescription = 'Onderzocht volume in ml.';
                }

                $infoArr[$i]['alias'] = $extraDescription;
                $infoArr[$i]['value'] = $extraValue;
                $i++;
            }
        }

        $infoArr[$i]['alias'] = 'Bron';

        if( $results['0']['source'] == "2" ||  $results['0']['source'] == "0" ){
            $infoArr[$i]['value'] = '<i class="icon icon-keyboard"></i>LIMS';
        }

        if( $results['0']['source'] == "1"){
            $infoArr[$i]['value'] = '<i class="icon icon-cloud"></i> CSV import';
        }

        if( $results['0']['source'] == "3"){
            $infoArr[$i]['value'] = '<i class="icon icon-cloud"></i> Portal ';
        }

        $i++;

        $infoArr[$i]['alias'] = 'In bak vriezer';
        $infoArr[$i]['value'] =  $results['0']['stored_in'];

        $i++;

        $infoArr[$i]['alias'] = 'Afweegstation';
        $infoArr[$i]['value'] =  $results['0']['diluted_at'];

        $tF = new tableFactory();
        $tF->loadTemplate('sampleBlock');
        $tF->loadValues($infoArr);
        $this->_template->set('sample_block_table', $tF->renderTable());


        return $this->hardRender();
    }

    function getProjectInnoculationDate($projectId)
    {
        $samples = new Sample(); 
        $samples->where('project', $projectId);
        $samples->select(['sample_innoculated']);
        $samples->order('sample_innoculated', 'ASC');
        $samples = $samples->search();         
         
        $rawInnoc = False;
 
         if(!empty($samples)){
 
             $first = False;
             $last = False;
 
             foreach($samples as $innocCheck){
 
                 if(!empty($innocCheck['sample_innoculated']) && $first == False){
                     $first = $innocCheck['sample_innoculated'];
                 }
 
                 if(!empty($innocCheck['sample_innoculated']) && $innocCheck['sample_innoculated'] != $first){
                     $last = $innocCheck['sample_innoculated'];
                 }
             }
 
             $firstReset = strtotime(date('d-m-Y', $first));
             $lastReset = strtotime(date('d-m-Y', $last));
 
             if($first == False){                
                 $innocDate = 'Nog niet ingezet';
                 $innocTime = '';
             } elseif($first != False && $last == False){
                 $rawInnoc = $first; 
                 $innocDate = date('d-m-Y', $first);           
                 $innocTime = date('H:i', $first);           
             }
             elseif($last != False && $firstReset == $lastReset){
                 $rawInnoc = $first; 
                 $innocDate = date('d-m-Y', $first);
                 $innocTime = date('H:i', $first);           
             } else{
                 $rawInnoc = $first; 
                 $innocDate = date('d-m-Y H:i', $first);                
                 $innocDate .= '<br />';
                 $innocDate .= date('d-m-Y H:i', $last);
                 $innocTime = 'Tweeledig';                
             }
 
 
         } else{
             $innocDate = 'Nog niet ingezet';
         }     

         return $innocDate; 
    }

    function buildProjectInfoBlock($id, $project = False, $print = False)
    {

        $this->doNotRenderHeader = 1;
        $this->render = 0;
        

        if ($project == False) {
            $this->Sample->where('id', $id);
            $results = $this->Sample->search();
            $projectId = $results['0']['project'];
            $this->Sample->free();
        }

        if ($project !== False) {            
            $projectId = $project;                        
        }

        //grab fringer for innoculation date
        $this->Sample->where('project', $projectId);
        $this->Sample->select(['sample_innoculated']);
        $this->Sample->order('sample_innoculated', 'ASC');
        $samples = $this->Sample->search();
        $rawInnoc = False;

        if(!empty($samples)){

            $first = False;
            $last = False;

            foreach($samples as $innocCheck){

                if(!empty($innocCheck['sample_innoculated']) && $first == False){
                    $first = $innocCheck['sample_innoculated'];
                }

                if(!empty($innocCheck['sample_innoculated']) && $innocCheck['sample_innoculated'] != $first){
                    $last = $innocCheck['sample_innoculated'];
                }
            }

            $firstReset = strtotime(date('d-m-Y', $first));
            $lastReset = strtotime(date('d-m-Y', $last));

            if($first == False){                
                $innocDate = 'Nog niet ingezet';
                $innocTime = '';
            } elseif($first != False && $last == False){
                $rawInnoc = $first; 
                $innocDate = date('d-m-Y', $first);           
                $innocTime = date('H:i', $first);           
            }
            elseif($last != False && $firstReset == $lastReset){
                $rawInnoc = $first; 
                $innocDate = date('d-m-Y', $first);
                $innocTime = date('H:i', $first);           
            } else{
                $rawInnoc = $first; 
                $innocDate = date('d-m-Y H:i', $first);                
                $innocDate .= '<br />';
                $innocDate .= date('d-m-Y H:i', $last);
                $innocTime = 'Tweeledig';                
            }


        } else{
            $innocDate = 'Nog niet ingezet';
        }        

        $pInfo = pa('projects', 'fetchProjectInfo', array($projectId));

        //error
        if (empty($pInfo)) {
            $project404 = generateHTML('alertWarning', array('alert_title' => 'Error', 'alert_message' => 'Project not found'));
            $this->_template->set('project_block_table', $project404);
            return $this->hardRender();
        }

        $this->projectInfo = $pInfo;

        $customFields = upa('projectFields', 'fetchProjectFields', array());
        //$customValues =  json_decode($results['0']['custom_fields'], True);
        $customValues = json_decode($pInfo['custom_fields'], True);

        $infoArr['0.2']['alias'] = '{MESA_SLU_CLIENT}';
        $clientName = upa('clients', 'clientIdToName', array($pInfo['client']));
        $infoArr['0.2']['value'] = '<span id="portalClientStatus"><i id="portalClientIcon" class="icon-circle"></i></span> <a href="{LB}/clients/show/'. $pInfo['client'] .'">' . $clientName . '</a>';


        //$infoArr['0.3']['alias'] = '{MESA_SLU_SUBCLIENT}';
        //$infoArr['0.3']['value'] = pa('subClients', 'subclientIdToName', array(0 => $pInfo['subclient']));

        $infoArr['0.4']['alias'] = '{MESA_SLU_PROJECT}';
        $infoArr['0.4']['value'] = '<a href="{LB}/projects/search/' . $projectId . '">' . $pInfo['project_name'] . '</a>';

        $infoArr['0.45']['alias'] = 'Orderformulier';
        $infoArr['0.45']['value'] = 'Niet beschikbaar';
        
        if ($pInfo['portal_id'] != NULL ) {
            $infoArr['0.45']['value'] = '<a target="_blank" href="{LB}/projects/orderForm/' . $pInfo['portal_id'] . '">Downloaden</a>';
        }
        

        if ($pInfo['print_version'] == 0) {
            $pInfo['print_version'] = 1;
        }

        $infoArr['0.5']['alias'] = '{MESA_SLU_PROJECTREFERENCE}';
        $infoArr['0.5']['value'] = $pInfo['reference'] . '.' . $pInfo['revision'] . '.' . $pInfo['print_version'];

        $infoArr['1']['alias'] = 'Project aangemeld:';
        $infoArr['1']['value'] = date('d-m-Y', $pInfo['project_date']);

        


        $infoArr['1.9']['alias'] = 'Project status';



        if ($pInfo['auth_status'] == 0) {

          if($pInfo['started'] == 0){
            $infoArr['1.9']['value'] = 'Ontvangen';
          }
          elseif($pInfo['is_ready'] == 1){
            $infoArr['1.9']['value'] = 'Afgerond';
          } else{
            $infoArr['1.9']['value'] = 'Lopend';
          }
        } else{
          if($pInfo['rap_stat'] == 1){
            $infoArr['1.9']['value'] = 'PDF gegenereerd';
          } else{
            $infoArr['1.9']['value'] = 'Geautoriseerd';
          }

        }

        $infoArr['2']['alias'] = '{MESA_SLU_PROJECTREVISION}';
        $infoArr['2']['value'] = $pInfo['revision'];

        if ($pInfo['auth_status'] == 0) {
            //unauthorized
            $infoArr['3']['alias'] = '{MESA_SLU_PROJECTAUTHORISATION}';
            $infoArr['3']['value'] = '{MESA_SLU_NOTAUTHORISED}';
        }

        if ($pInfo['auth_status'] == 1) {
            //authorized
            $authUser = pa('profiles', 'getUserFullName', array($pInfo['auth_by']));
            $infoArr['3']['alias'] = '{MESA_SLU_PROJECTAUTHORISATION}';
            $infoArr['3']['value'] = '{MESA_SLU_AUTHORISED} <i class="icon-check icon-green"></i> ';
            $infoArr['4']['alias'] = '{MESA_SLU_AUTHBY}: ';
            $infoArr['4']['value'] = $authUser;
        }

        if ($pInfo['auth_status'] == 2) {
            //authorized
            $infoArr['3']['alias'] = '{MESA_SLU_PROJECTAUTHORISATION}';
            $infoArr['3']['value'] = '{MESA_SLU_AUTHORISATIONPENDING}';
        }

        if ($pInfo['rap_stat'] == 1) {
            //authorized
            $infoArr['3.1']['alias'] = 'PDF gegenereerd op';
            $infoArr['3.1']['value'] = date('d-m-Y', $pInfo['rap_on']);

            $infoArr['3.2']['alias'] = 'PDF gegenereerd door';
            $infoArr['3.2']['value'] = upa('profiles', 'getUserFullName', array($pInfo['rap_by']), false);
        }

        $i = count($infoArr);        

        foreach ($customFields as $field) {
            
            $infoArr[$i]['alias'] = $field['alias'];

            if (isset($customValues[$field['name']])) {
                $infoArr[$i]['value'] = $customValues[$field['name']];
            } else {
                $infoArr[$i]['value'] = '';
            }
            $i++;
        }

        //$inzetI = $i + 1;
        $infoArr[$i]['alias'] = 'Inzet datum:';
        $infoArr[$i]['value'] = $innocDate;
        $i++;

        $infoArr[$i]['alias'] = 'Inzet tijd:';
        $infoArr[$i]['value'] = $innocTime ?? '';
        $i++;

        $samplingDate = checkKeyOrFalse($customValues, 'project_monster');
        $samplingTime = checkKeyOrFalse($customValues, 'project_bemonster_tijd');

        $infoArr[$i]['alias'] = 'Monstername tot analyse:';
        $infoArr[$i]['value'] =  $this->termCalculation($rawInnoc, $samplingDate, $samplingTime );
        $i++;

        if($pInfo['predicted_end'] == -1 || $pInfo['predicted_end'] == 0){
            $expectedDate = 'Nog niet bekend';
        }else{
            $expectedDate = date('d-m-Y', $pInfo['predicted_end']);
        }                

        $infoArr[$i]['alias'] = 'Gereed verwacht:';
        $infoArr[$i]['value'] = $expectedDate;
        $i++;

        //check extra fields
        if(!empty($pInfo['project_extra'])){

            $extraInfo = json_decode($pInfo['project_extra'], JSON_FORCE_OBJECT);
            foreach($extraInfo as $extraInfoKey => $extraInfoValue){

                if($extraInfoKey == 'sample_method' ){
                    $infoArr[$i]['alias'] = 'Bemonster. methode';
                    $infoArr[$i]['value'] = upa('sampleProcedures', 'procedureIdToName', array($extraInfoValue));
                }

                if($extraInfoKey == 'client_reference'){
                    $infoArr[$i]['alias'] = 'Referentie klant:';
                    $infoArr[$i]['value'] = $extraInfoValue;
                }

                $i++;
            }
        }

        $tF = new tableFactory();
        $tF->loadTemplate('sampleBlock');   
        $tF->loadValues($infoArr);
        $this->_template->set('project_block_table', $tF->renderTable());

        if ($print == False) {
            return ['html' => $this->hardRender(), 'project' => $pInfo];
        } else {
            print $this->hardRender();
        }

    }

    private function termCalculation($rawInnocDate, $samplingDate, $samplingTime)
    {
     
        try
        {      
        
            if($rawInnocDate == False || empty($rawInnocDate))
            {
                return 'N.T.B. Geen inzet datum';
            }

            if($samplingDate == False || empty($samplingDate) || strtolower($samplingDate)  == 'onbekend')
            {
                return 'N.T.B. Bemonster-datum onbekend';
            }

            if($samplingTime == False || empty($samplingTime) || strtolower($samplingTime) == 'onbekend')
            {
                return 'N.T.B. Bemonster-tijd onbekend';
            }

            $innocDateTime = new DateTime('@' . $rawInnocDate);

            $samplingDate = new DateTime($samplingDate);
            
            //set time on datetime
            $samplingDate->setTime((int)substr($samplingTime, 0, 2), (int)substr($samplingTime, 2, 2));

            $diff = $samplingDate->diff($innocDateTime);

            $hours = $diff->h;
            $hours = $hours + ($diff->days*24);               
            
            return $hours . ' uur';

        } catch (\Throwable $th) {

            return 'N.T.B. Datum/tijd veld(en) incorrect';

        }
        
    }

    function buildResearchBlock($id)
    {

        $this->doNotRenderHeader = 1;
        $this->render = 0;
            
        $res = new SampleAnalysis();
        $res->where('sample', $id);
        $res->select(['assay_base', 'profile_group', 'roaming_id','profile','is_ready','conf_requested','follow_number', 'id']);
        $res->order('project_order', 'ASC' );
        $res->order('follow_number', 'ASC' );
        $attRes = $res->search();

        

        if(!empty($attRes))
        {            
          
            //load in assays 
            $sql = 'SELECT id,name,type,confirmation FROM assays WHERE id IN (' . implode(',',array_column($attRes, 'assay_base'))  . ')';
            $baseAssays = new Assay();
            $baseAssaysInfo = $baseAssays->customQuery($sql, []);    
            $baseAssaysInfo = array_column($baseAssaysInfo, null, 'id');

            //load in profiles            
            $rp = new ResearchProfile();
            $sql = 'SELECT id,name FROM researchprofiles WHERE id IN (' . implode(',',array_unique(array_column($attRes, 'profile')))  . ')';
            $profilesInfo = $rp->customQuery($sql, []);
            $profilesInfo = array_column($profilesInfo, null, 'id');
            
        }        

        $thisGroup = False;
        $newGroup = True;
        $blockRender = '';
        $resArr = array();
        $currGroup = 0;

        foreach ($attRes as $sa) {            
            
            $assayBaseInfo = $baseAssaysInfo[$sa['assay_base']];

            $activeGroup = $currGroup - 1;

            if ($thisGroup == False) {    //profile group not initialized yet
                $thisGroup = $sa['profile_group'] . $currGroup;
                $currGroup++;
                $newGroup = True;
            } else {
                //do check if this group is still the same
                if ($sa['profile_group'] . $activeGroup  == $thisGroup) {
                    $newGroup = False;
                } else {
                    $thisGroup = $sa['profile_group'] . $currGroup;
                    $currGroup++;
                    $newGroup = True;
                }
            }

            if ($newGroup == True) {
                $resArr[$thisGroup] = '';

                if ($sa['roaming_id'] != 0) {                    
                    $groupName = $assayBaseInfo['name'];
                } else {                    
                    $profileInfo = $profilesInfo[$sa['profile']];
                    $groupName = $profileInfo['name'];
                }
                $blockRender .= generateHTML('lookup/resHeader', array('res_profile' => $groupName, 'profile_group' => $thisGroup));
            }

            $hideStatus = 'hide';
            if($assayBaseInfo['type'] == '4'){
                $hideStatus = '';
            }

            $readyHider = null;
            if($sa['is_ready'] == 1){
                $readyHider = '';
            } else{
                $readyHider = 'hide';
            }

            $confHider = null;
            $confDenyHider = null;

            if($assayBaseInfo['confirmation'] == 0){
              $confHider = 'hide';
              $confDenyHider = 'hide';
            } else {
              if($sa['conf_requested'] == 0){
                  $confHider = 'hide';
                  $confDenyHider = 'hide';
              } elseif($sa['conf_requested'] == 1){
                  $confHider = '';
                  $confDenyHider = 'hide';
              } elseif($sa['conf_requested'] == 2){
                  $confHider = 'hide';
                  $confDenyHider = '';
              }
            }

            $resArr[$thisGroup] .= generateHTML('lookup/resLine', array('conf_hider'  => $confHider, 'conf_deny_hider' => $confDenyHider,
                                                                        'ready_hider' => $readyHider, 'hide_status'=> $hideStatus, 'assay_name' => $assayBaseInfo['name'],
                                                                        'said' => $sa['id'], 'follow_no' => $sa['follow_number']));
        }

        if (empty($attRes)) {
            $this->_template->set('research_info_block', generateHTML('alertWarning', array('alert_title' => 'Geen analyses', 'alert_message' => 'Dit monster heeft geen analyses.')));
        } else {
            $this->_template->set('research_info_block', generateHTML($blockRender, $resArr, True));
        }

        return $this->hardRender();
    }


    function doLookup($barcode)
    {
        
        $this->render = 0;
        $retObj = array();

        /* Release old sample from Keyring */
        if(isset($_POST['previous_sample']))
        {
            upa('keyrings', 'removeOwnLock', array('SAMPLE', $_POST['previous_sample']));
        }
        

        /*
         * First check what kind of barcode was requested
         */
        $barExp = explode('.', $barcode);
        $sampleBar = $barExp[0];
        $sampleAnalysis = False;
        $sampleAnalysisFollow = False;
        $shortHand  = $this->isShortHand($barcode);
        
        if($shortHand === False)
        {
            $this->Sample->where('barcode', $sampleBar);                
            $this->Sample->insertOR();
            $this->Sample->where('tht_code', $sampleBar);                
        } 
        
        else
        {
            $this->Sample->where('follow_no', $shortHand['follow_no']);                
            $this->Sample->where('sample_type', $shortHand['sample_type']); 
            $this->Sample->where('leg_type', $shortHand['matrix_type']); 
            $this->Sample->order('id', 'desc');                        
        }

        $this->Sample->limit(1);

        $results = $this->Sample->search();        


        //check if a match was found
        if (empty($results)) {
            print json_encode(array('error' => 'BAR_WRONG'), JSON_FORCE_OBJECT);
            return;
        }
        //store standard info in model for now
        $this->Sample->arrayToModel($results['0']);


        if (isset($barExp[1]) && $barExp[1] != '') {
            $sampleAnalysis = $barExp[1];
            //convert to sample analysis ID
            $retObj['selected_analysis'] = $barExp[1];            
        } else {
            $retObj['selected_analysis'] = False;
        }

        if (isset($barExp[2]) && $barExp[2] != '') {

            $sampleAnalysisFollow = $barExp[2];            

            $sa = new SampleAnalysis();
            $sa->where('sample', $this->Sample->id);
            $sa->where('follow_number', $sampleAnalysis);
            $sa->select('id');
            $said = $sa->first();

           

            $focusResult = new Result(); 
            $focusResult->where('sample', $this->Sample->id);                        
            $focusResult->where('sa_id', $said['id']);
            $focusResult->select(['id']);
            $focusResult->where('follow_no', $sampleAnalysisFollow);            
            $focusElement = $focusResult->first();           
            
            if(!empty($focusElement))
            {
                $retObj['selected_follow'] = $focusElement['id'];
            }

            else
            {
                $retObj['selected_follow'] = False; 
            }
            
            

        } else {
            $retObj['selected_follow'] = False;
        }
        

        /*
         * Get lock status
         */
        $lockObject = upa('keyrings', 'requestLockAndStatus', array('SAMPLE', $this->Sample->id), False);

        /*
         * Fetch the different "blocks" needed for the sample window
         */
        $retObj['sample_block'] = upa('samples', 'buildSampleInfoBlock', array($this->Sample->id, $this->Sample->project));

        $retObj['metadata_block'] = $this->buildMetadataBlock($this->Sample->id);

        $retObj['productgroup_block'] = $this->buildproductGroupBlock($this->Sample->id);

        $projectBlock = upa('samples', 'buildProjectInfoBlock', array(False, $this->Sample->project));

        $retObj['project_block'] = $projectBlock['html'];        
        $retObj['project_auth'] = ($projectBlock['project']['auth_status'] == 1) ? True : False; 
                
        $retObj['research_block'] = upa('samples', 'buildResearchBlock', array($this->Sample->id));

        $documents = json_decode( upa('sampleFiles', 'listFiles', array($this->Sample->id, True, True) ));

        $retObj['documents_block'] =    $documents->table;

        $retObj['selected_client'] = $this->Sample->client;
        $retObj['selected_sample'] = $this->Sample->id;

        $retObj['sample_note'] = $this->Sample->sample_note;    
        //$retObj['portal_notes'] =  $this->Sample->portal_notes;

        //prepend all except the first occurance of ● with a <br />
        //there must be a better way for this.... but i have no time. 
        $retObj['portal_notes'] = preg_replace('/●/', '<br />●', $this->Sample->portal_notes);
        $retObj['portal_notes'] = preg_replace('/^<br \/>/', '', $retObj['portal_notes']);
       

        $retObj['barcode'] = $this->Sample->barcode;
        $retObj['was_tht'] = ($this->Sample->tht_code === null || empty($this->Sample->tht_code)) ? false : true;
        

        if($retObj['project_auth'] == True)
        {
            $retObj['progress'] = 100;
        } 
        
        else
        {

            if($this->Sample->predicted_end  == -1)
            {
              $roundedPercent = 0;
            } 
            
            else
            {
              $distance = $this->Sample->predicted_end - $this->Sample->date_registered;
              
        
            $left = $this->Sample->predicted_end - time();
        
            if($distance !== 0)
            {
                $percent = (1 - ($left / $distance)) * 100;
            } 

            else
            {
                $percent = 0;
            }
            
        

        
              
              
              if($percent > 100 ){
                  $percent = 100;
              }

              $roundedPercent = ceil($percent);
            }

            $retObj['progress'] = $roundedPercent;
        }

        $retObj['lock_object'] = $lockObject;
        
        
        $reqExcResearch = upa('sampleAnalysis', 'analysisAndProfilesArray', array($this->Sample->id));
                        
        $retObj['reqResearch'] = $reqExcResearch['req'];
        $retObj['excResearch'] = $reqExcResearch['exc'];

        print json_encode($retObj, JSON_FORCE_OBJECT);
    }

    function buildMetadataBlock($sampleId){

        $allowed_detail_edit = upa('groupPrivileges', 'checkGUI', array('editSampleDetails'), False);

        $metadata = upa('metadata', 'forSample', array($sampleId), False);

        array_walk($metadata, function (&$v, $k) use ($allowed_detail_edit){
            $v['value'] = nl2br($v['value']);
            $v['class'] = ($allowed_detail_edit == True) ? '' : 'hide';
        }); 


        $tF = new tableFactory();
        $tF->loadTemplate('metadata');
        $tF->loadValues($metadata);
        return $tF->renderTable();
    }

    function buildproductGroupBlock($sampleId){
        
        $group = upa('productGroups', 'getProductGroup', array($this->Sample->portal_product_group_id), False);
        
        if($group == null)
        {
            return  generateHTML('productGroup', array('id' => 'Onbekend', 'name' => 'Onbekend', 'default' => 'Onbekend'));    
        }

        $defaultGroup = ($group['default'] == 1) ? 'Ja' : 'Nee';

        return  generateHTML('productGroup', array('id' => $this->Sample->portal_product_group_id, 'name' => $group['name'], 'default' => $defaultGroup));

    }

    function updateSampleProductGroup(){

        $this->render = False; 
        $this->Sample->where('id', $_POST['sample_id']);        
        $result = $this->Sample->search();

        if($result){

            

            $this->Sample->arrayToModel($result['0']);

            $this->Sample->portal_product_group_id = $_POST['product_group_id'];

            if($this->Sample->portal_sample_id != null)
            {
                //update portal about this change 
                upa('portal', 'updateProductGroup', array($this->Sample->portal_sample_id, $this->Sample->portal_product_group_id), False);
            }
            
                        
            $this->Sample->save(); 

        }

        



    }

    function estimateProgress($sample)
    {

        return;
        /*
        $analysis = pa('sampleAnalysis', 'fetchAnalysisArray', array($sample));

        if ($this->Sample->id == '') {
            $this->Sample->where('id', $sample);
            $result = $this->Sample->search();
            $this->Sample->arrayToModel($result['0']);
        }

        if ($this->projectInfo == False) {
            $this->projectInfo = pa('projects', 'fetchProjectInfo', array($this->Sample->project));
        }

        $cFields = json_decode($this->Sample->custom_fields, True);
        $pFields = json_decode($this->projectInfo['custom_fields'], True);
        $progress = 0;
        $noAnalysis = count($analysis);

        if ($noAnalysis == 0) {
            return 100; //avoid div by zero
        }

        $percPerAnalysis = 100 / $noAnalysis;
        $currentTime = time();

        foreach ($analysis as $analysis) {

            $assayBase = pa('assays', 'fetchSingle', array($analysis['assay_base']));
            $startFrom = explode(':', $assayBase['start_from']);
            $totalTime = $assayBase['duration'] * 86400;

            if ($totalTime == 0) {
                return 100;
            }

            //from beginning
            if ($startFrom[0] == 'r') {
                $passed = $currentTime - $this->Sample->date_registered;
            }

            if ($startFrom[0] == 's') {
                $passed = $currentTime - strtotime($cFields[$startFrom[1]]);
            }

            if ($startFrom[0] == 'p') {
                $passed = $currentTime - strtotime($pFields[$startFrom[1]]);
            }

            if($startFrom[0] == 'i'){

                if($this->Sample->sample_innoculated == ''){
                    $passed = 0;
                } else{
                    $passed = $currentTime - $this->Sample->sample_innoculated;
                }
            }


            $percentage = $passed / $totalTime;
            if ($percentage > 1) {
                $percentage = 1;
            }

            if ($percentage < 0) {
                $percentage = 0;
            }

            $progress = $progress + ($percPerAnalysis * $percentage);
        }

        return floor($progress); */
    }


    function checkMalformedBar($barcode)
    {

        $this->render = 0;

        //empty
        if ($barcode == '') {
            return False;
        }

        //non A or S
        if ($barcode[0] != 'S') {
            return False;
        }

        return True;
    }

    public function countSamplesInProject($projectId)
    {
        $this->Sample->where('project', $projectId);
        $results = $this->Sample->search();
        return count($results);
    }


    public function countSamplesAndTypesInProject($projectId)
    {
        $this->Sample->where('project', $projectId);
        $results = $this->Sample->search();
        $legFound = False;
        $normalFound = False;
        $rodacFound = False;
        $sourceIsClient = False; 

        if(!empty($results)){

            foreach($results as $sample){
                if($sample['sample_type'] == 'L'){
                    $legFound = True;
                } elseif($sample['sample_type'] == 'R'){
                    $rodacFound = True;
                }
                else{
                    $normalFound = True;
                }

                if((int)$sample['source'] === 1 || (int)$sample['source'] === 3){
                    $sourceIsClient = true; 
                }

            }
        }

        $returnArr = array();
        $returnArr['count'] = count($results);
        $returnArr['leg'] = $legFound;
        $returnArr['normal'] = $normalFound;
        $returnArr['rodac'] = $rodacFound;
        $returnArr['sourceIsClient'] = $sourceIsClient;

        return $returnArr;
    }

    public function projectSamples($projectId, $startBySample = False)
    {

        $this->Sample->where('project', $projectId);
        $result = $this->Sample->search();

        if ($startBySample == True) {
            $stratArr = array();
            foreach ($result as $sampleInd => $sample) {
                $stratArr[$sample['id']] = $sample;
            }
            return $stratArr;
        }

        return $result;
    }

    public function createResultTable($sampleId)
    {


        //fetch sample info
        $this->Sample->where('id', $sampleId);
        $result = $this->Sample->search();

        if (empty($result)) {
            return False;
        }

        //fetch results
        $sampleResults = pa('results', 'getSampleResults', array($sampleId, True));
        $requestedFlows = pa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId));

        //parray($requestedAna);
        //parray($sampleResults);

        $tF = new tableFactory();
        $tF->setTableId('result_table');
        $tF->legoMode();
        $tF->loadTemplate('resultLego');

        $resultsRender = '';
        $flowResultRender = '';


        //loop trough requested and build up table
        foreach ($requestedFlows as $thisFlow) {

            //get flow components
            $flowInfo = pa('flows', 'getFlow', array($thisFlow['flow']));
            $flowComp = pa('flows', 'findInputComponents', array($thisFlow['flow']));


            foreach ($sampleResults[$thisFlow['id']] as $resultRow) {

                $thisFields = pa('analyticalFields', 'fetchFields', array($resultRow['ana_base']));
                $fielResultData = json_decode($resultRow['data'], True);
                $tF->useBrick('th', array('table_title' => $flowComp[$resultRow['component_id']]['name']));

                foreach ($thisFields as $fieldId => $fieldInfo) {
                    $tF->useBrick('resultOnly', array('field_name' => $fieldInfo['alias'], 'field_value' => $fielResultData[$fieldInfo['name']]));
                }
            }

            $flowResultRender .= $tF->returnRender();
            $tF->resetRender();


            //get result fields
            $tF->useBrick('th', array('table_title' => 'Result fields'));
            $resultFields = pa('flows', 'getResultFieldsArray', array($thisFlow['id']));

            foreach ($resultFields as $resName => $resValue) {
                $tF->useBrick('resultOnly', array('field_name' => $resName, 'field_value' => $resValue));
            }


            $flowResultRender .= $tF->returnRender();
            $tF->resetRender();

            $resultsRender .= generateHTML('results/resultsWrap', array('flow_name' => $flowInfo['name'], 'flow_results' => $flowResultRender));
            $flowResultRender = '';
        }

        return $resultsRender;

        /*
    }
    }
    }

        $thisDetail = pa('analysisTests', 'getSingle', array($thisAna['test']));
        $thisFields = pa('analyticalFields', 'fetchFields', array($thisDetail['base_analytical']));

        $tF->useBrick('th', array('table_title' => $thisDetail['name']));

        $noOfRows = count($thisFields);
        $noOfReps = $thisDetail['replicates_number'];
        $repRowSpan = $noOfRows;

        if ($noOfReps == 0) {
            $dilRowSpan = $noOfRows;
        } else {
            //add one because 1 rep, means 2 rows (original sample only is 0 reps)
            $dilRowSpan = ( $noOfReps + 1) * $noOfRows;
        }

        $brickValues['dillution_span'] = $dilRowSpan;
        $brickValues['replicate_span'] = $repRowSpan;

        $currentDil = 'False';
        $currentRep = 'False';

        foreach ($sampleResults[$thisAna['id']] as $resultLines) {

            //parray($resultLines);
            //parray($resultLines);

            foreach ($thisFields as $field) {

                if ($resultLines['dillution'] == $currentDil && $resultLines['rep'] == $currentRep) {
                    $brick = 'resultOnly';
                }

                //new rep and same dillution, start new rep line
                if ($resultLines['rep'] != $currentRep && $resultLines['dillution'] == $currentDil) {
                    $brick = 'newDil';
                    $currentRep = $resultLines['rep'];
                }

                //new dillution? Start a new line
                if ($resultLines['dillution'] != $currentDil) {
                    $brick = 'startOf';
                    $currentDil = $resultLines['dillution'];
                    $currentRep = $resultLines['rep'];
                }

                $brickValues['dillution'] = $resultLines['dillution'];
                $brickValues['replicate'] = $resultLines['rep'];

                $resultsForLine = unserialize($resultLines['fields']);

                $brickValues['field_name'] = $field['alias'];
                $brickValues['field_value'] = $resultsForLine[$field['name']];
                $tF->useBrick($brick, $brickValues);
            }
        }

        $tF->useBrick('tf', array());
        $resultsRender .= $tF->returnRender();
        $tF->resetRender();
    }

    return $resultsRender;
     *
     */
    }

    public function viewResult($sampleId)
    {

        $this->renderAlternateHeader = 'slim';

        //fetch sample info
        $this->Sample->where('id', $sampleId);
        $result = $this->Sample->search();

        if (empty($result)) {
            return;
        }

        $resultTable = $this->createResultTable($sampleId);

        //set rendering
        $clientByName = pa('clients', 'clientIdToName', array($result[0]['client']));
        $this->_template->set('sample_results', $resultTable);
        $this->_template->set('barcode', $result[0]['barcode']);
        $this->_template->set('description', $result[0]['description']);
        $this->_template->set('client_by_name', $clientByName);
    }

    function removeSample($sampleId, $recursive = True, $supressRevisionAdditions = False, $projectDelete = False)
    {
        //remove from main
        $this->render = 0;
        $result = $this->fetch($sampleId);
        $this->Sample->free();

        if ($recursive == True) {
            upa('sampleAnalysis', 'removeForSample', array($sampleId));
        }

        $this->Sample->id = $sampleId;
        $this->Sample->delete();

        if($supressRevisionAdditions == False){
            //upa('revisions', 'registerRevision', array('SCOPE_PROJECT_SAMPLEREM', $result['project'], $result['barcode']), False);
            //upa('revisions', 'removeSampleRevisions', array($sampleId), False);
            $event = 'Monster ' . $result['barcode'] . ' verwijderd uit project';
            upa('changeTracker', 'changed', array(10, $result['project'], $sampleId, False, $event, False, False), False);
            upa('projects', 'projectEdited', array($result['project']), False);
            upa('projects', 'updateEndPoints', array($result['project']), False);
        } else{
            upa('revisions', 'removeSampleRevisions', array($sampleId), False);
        }

        if(!empty($result['sample_innoculated'])){
          upa('assuranceForms', 'updateFormByInnocDate', array($result['sample_innoculated']), False);
        }

        if($result['portal_sample_id'] !== NULL && $projectDelete === False){
            upa('portal', 'destroySample', array($result['portal_sample_id']), False );
        } else{
            cphp('skipping portal notif');
        }

    }

    function authoriseAllInProject($projectId = False, $fillHoles = False)
    {

        $this->render = 0;

        if ($projectId == False) {
            if (!isset($_POST['projectId'])) {
                return;
            } else {
                $projectId = $_POST['projectId'];
            }
        }

        $this->Sample->where('project', $projectId);
        $results = $this->Sample->search();

        foreach ($results as $sample) {
            if ($fillHoles == False) {
                pa('sampleAnalysis', 'authoriseAll', array($sample['id']));
            } else {
                pa('sampleAnalysis', 'authoriseAll', array($sample['id']), False, True);
            }

        }
    }

    function deAuthoriseAllInProject($projectId = False)
    {

        $this->render = 0;

        if ($projectId == False) {
            if (!isset($_POST['projectId'])) {
                return;
            } else {
                $projectId = $_POST['projectId'];
            }
        }

        $this->Sample->where('project', $projectId);
        $results = $this->Sample->search();

        foreach ($results as $sample) {
            pa('sampleAnalysis', 'authoriseAll', array($sample['id'], True));
        }
    }



    function partOfAuthProject($sampleId, $project = False)
    {
        
        $this->render = 0;
     
        if($project == False){
            $this->Sample->where('id', (int)$sampleId);
            $result = $this->Sample->search();                                  
            $project = $result[0]['project'];
        }
        
        $projectInfo = upa('projects', 'fetchProjectInfo', array($project));

        if ($projectInfo['auth_status'] == 1) {            
            return True;
        } else {
            return False;
        }

    }

    function removeProjectSamples($projectId, $recursive = True)
    {

        $this->Sample->where('project', $projectId);
        $result = $this->Sample->search();

        foreach ($result as $sample) {

            $this->removeSample($sample['id'], True, True, True);


            //$this->Sample->id = $sample['id'];
            //$this->Sample->delete();
            //if($recursive == True){
            //    pa('sampleAnalysis', 'removeForSample', array($sample['id']) );
            //    pa('results', 'removeResultSample', array($sample['id']));
            //
            //}
        }

        return $result;
    }

    function removeAnalysis($sampleAnalysis, $sampleId)
    {

        $this->render = 0;
        $saInfo = upa('sampleAnalysis', 'fetch', array($sampleAnalysis), 0);
        upa('sampleAnalysis', 'removeById', array($sampleAnalysis,$sampleId));
        upa('results', 'removeResults', array($sampleAnalysis));
        upa('roamingAnalysis', 'removeRoamBySA', array($sampleAnalysis));
        upa('confirmations', 'removeConfirmationBySA', array($sampleAnalysis));
        upa('vetoResults', 'removeVetoSaid', array($sampleAnalysis));

        $sampleInfo = upa('samples', 'fetch', array($sampleId), False);

        //update project order
        upa('sampleAnalysis', 'reorderOrderList', array($sampleId), False);

        //run revision
        $assayInfo = upa('assays', 'fetch', array($saInfo['assay_base']), False);

        //upa('revisions', 'registerRevision', array('SCOPE_SAMPLEINFO_ASSAYREM', $assayInfo['name'], $sampleId, ), False);
        upa('projects', 'projectEdited', array(False, $sampleId), False);
        upa('sampleAnalysis', 'checkProjectReady', array($saInfo['project']), False);

        $sampleInfo = upa('samples', 'fetch', array($sampleId), False);
        $event = 'Analyse ' . $assayInfo['name'] .' verwijderd van monster ' . $sampleInfo['barcode'];
        upa('changeTracker', 'changed', array(7, $saInfo['project'], $saInfo['sample'], $saInfo['id'], $event, False, False), False);
        $this->estimateEndPoints($sampleId);

        if(!empty($sampleInfo['sample_innoculated'])){
          upa('assuranceForms', 'updateFormByInnocDate', array($sampleInfo['sample_innoculated']), False);
        }

        //SCOPE_SAMPLEINFO_ASSAYREM
        // $scope, $scopeId, $changed = False, $from = False, $to = False, $saId = 0, $resultId = 0
    }

    function sampleInProjectOverview($id)
    {
        global $lang;
        $this->render = 0;

        $sampMethodOp = upa('sampleProcedures', 'getProceduresArr', array());
        $this->Sample->where('id', $id);
        $results = $this->Sample->search();
        $this->Sample->arrayToModel($results['0']);

        $projectStatus = upa('projects', 'authStatus', array($this->Sample->project));

        //release previous lock, request lock status
        upa('keyrings', 'removeOwnLock', array('SAMPLE', $_POST['previous_sample']));
        $lockObject = upa('keyrings', 'requestLockAndStatus', array('SAMPLE', $id), False);

        $allowed_detail_edit = upa('groupPrivileges', 'checkGUI', array('editSampleDetails'), False);

        if ($projectStatus == True || $lockObject['locked'] == True || $allowed_detail_edit == False) {
            $disabled = 'disabled';
            $projectStatus = True;
        } else {
            $disabled = '';
        
        }
              
        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();
        $sForm->addDropdownField('sampling_method', False, 'input-block-level', $this->Sample->sampling_method, $sampMethodOp, $disabled, $lang['MESA_SAD_SAMPLEMETHOD']);
        $sForm->addInputField('description', False, 'text', 'input-block-level ', $this->Sample->description, 'Monster omschrijving LIMS', $disabled, 'Monster omschrijving LIMS');
        
        $followInfo = upa('samples', 'sampleGetProjectFollowNo', array( $this->Sample->id,  $this->Sample->project), False);
        $sForm->addInputField('prj_follow_no', False, 'text', 'input-block-level ', $followInfo['db_follow'], 'Volgnummer', 'disabled="disabled"', 'Volgnummer');

            
        $sForm->addInputField('client_description', False, 'text', 'input-block-level ', $this->Sample->client_description, 'Monster omschrijving klant', 'readonly style="background: #efefef"', 'Monster omschrijving klant');

        $displayExtraFields = False;

        if(!empty($this->Sample->sample_extra)){

            $extraFields = json_decode($this->Sample->sample_extra, JSON_FORCE_OBJECT);
            if(is_array($extraFields)){

                foreach($extraFields as $exFieldKey => $exFieldValue){
                    $displayExtraFields = True;
                    $extaFieldClass = '';
                    $extraFieldTags  = '';
                    $intFilter = '';                    

                    if($exFieldKey == 'follow'){
                        continue;
                        $fieldDescription = 'Volgnummer';
                        $extaFieldClass =  'disabled';
                        $extraFieldTags = 'disabled="disabled"';
                    }

                    if ($projectStatus == True || $lockObject['locked'] == True) {
                          $extraFieldTags = 'disabled="disabled"';
                    }

                    if($exFieldKey == 'type'){
                        $fieldDescription = 'Type';
                    }

                    if($exFieldKey == 'temperature'){
                        $fieldDescription = 'Temperatuur';
                    }

                    if($exFieldKey == 'location'){
                        $fieldDescription = 'Ruimte';
                    }

                    if($exFieldKey == 'filter_volume'){
                        $fieldDescription = 'Onderzocht volume in ml.';
                        $intFilter = ' int-filter';
                    }

                    if($exFieldKey == 'type'){
                      //addDropdownField($name, $label, $classes, $value, $options, $tags, $prepend = False){
                      $fieldDescription = 'Leiding';
                      $leidingOptions = array();
                      $leidingOptions['Koud'] = 'Koud';
                      $leidingOptions['Warm'] = 'Warm';
                      $leidingOptions['Mengwater'] = 'Mengwater';
                      $leidingOptions[''] = '';
                      $sForm->addDropdownField($exFieldKey, False, 'input-block-level sample-extra-field ' . $extaFieldClass, $exFieldValue, $leidingOptions, $extraFieldTags, $fieldDescription );
                    } else{
                      //addInputField($name, $label, $fieldType, $classes, $value, $placeHolder, $tags = False, $prepend = False) {
                      $sForm->addInputField($exFieldKey, False, 'text',  'input-block-level sample-extra-field ' . $intFilter,  $exFieldValue, $fieldDescription, $extraFieldTags, $fieldDescription);
                    }

                    if($exFieldKey == 'filter_volume'){
                        $fieldDescription = 'Onderzocht volume in ml.';
                    }
                }
            }
        }

        $form = $sForm->render();
        $retObj = array();

        //get custom fields
        $customFields = upa('sampleFields', 'renderCustomFields', array($this->Sample->custom_fields, $projectStatus));
        $retObj['sampleInfo'] = $form['sampling_method'] . $form['description'] . $form['prj_follow_no'] . $form['client_description'] . $customFields;

        if($displayExtraFields == True){
            foreach($extraFields as $exFieldKey => $exFieldValue){
                if(array_key_exists($exFieldKey, $form)){
                    $retObj['sampleInfo'] = $retObj['sampleInfo'] . $form[$exFieldKey];
                }                
            }
        }

        //get assay results, grouped on
        $metaDataInfo = upa('metadata', 'metdataEditForm', array($id, $projectStatus), False);
        $metaDataFound = checkKeyOrFalse($metaDataInfo, 'found');
        
        if($metaDataFound === True){
            $retObj['metadata_found'] =  '!';
        } else{
            $retObj['metadata_found'] =  '';
        }

        $retObj['metadata_info'] =  checkKeyOrFalse($metaDataInfo, 'table');        
        $retObj['assay_results'] = upa('sampleAnalysis', 'fetchResultSummary', array($id));        
        $retObj['sample_notes'] = $this->Sample->sample_note;

        $retObj['portal_notes'] = $this->Sample->portal_notes; 
        $retObj['portal_notes'] = preg_replace('/●/', '<br />●', $retObj['portal_notes']);
        $retObj['portal_notes'] = preg_replace('/^<br \/>/', '', $retObj['portal_notes']);

        $retObj['sample_source'] = $this->Sample->source;

        print json_encode($retObj, JSON_FORCE_OBJECT);

    }
    

    function updateSampleField2($id, $field, $value, $extra){
      $_POST['id'] = $id;
      $_POST['field'] = $field; 
      $_POST['value'] = $value; 
      $_POST['extra'] = $extra;
      //$this->updateSampleField();
    }

    function updateSampleNoteField()
    {
        $_POST['extra'] = False;
        $this->updateSampleField();
    }
    

    function updateSampleField()
    {

        $this->render = 0;
        $id = $_POST['id'];
        $field = $_POST['field'];
        $value = $_POST['value'];
        $extra = $_POST['extra'];

        $from = False;

        $this->Sample->where('id', $id);
        $result = $this->Sample->search();
        
        //sample was nto found
        if(empty($result)){
            return;
        }

        $this->Sample->arrayToModel($result['0']);

        $cArr = json_decode($this->Sample->custom_fields, True);
        $eArr = json_decode($this->Sample->sample_extra, JSON_FORCE_OBJECT);

        if(!is_array($eArr)){
            $eArr = array();
        }

        if(!is_array($cArr)){
            $cArr = array();
        }

        if ($field == 'description') {
            $from = $this->Sample->description;
            $this->Sample->description = $value;
        } 

        elseif ($field == 'client_description') {     
            $from = $this->Sample->client_description;      
            $this->Sample->client_description = $value;
        }
        
        elseif ($field == 'sampling_method') {
            $sampMethodOp = upa('sampleProcedures', 'getProceduresArr', array());
            //$from = $this->Sample->sampling_method;
            $from = checkKeyOrFalse($sampMethodOp, $this->Sample->sampling_method);
            $this->Sample->sampling_method = $value;
            $value = checkKeyOrFalse($sampMethodOp, $value);
        } elseif ($field == 'sample_notes') {
            $from = $this->Sample->sample_note;
            $this->Sample->sample_note = $value;
        } elseif(filter_var($extra, FILTER_VALIDATE_BOOLEAN)){
            $eArr[$field] = $value;
        }
        else {
            if(array_key_exists($field, $cArr)){
                $from = $cArr[$field];
            } else{
                $from = 'Onbekend';
            }

            $cArr[$field] = $value;
        }

        //repack and save
        $this->Sample->custom_fields = json_encode($cArr, JSON_FORCE_OBJECT);
        $this->Sample->sample_extra = json_encode($eArr, JSON_FORCE_OBJECT);
        $this->Sample->save();

        //registerRevision( $scope, $scopeId, $changed, $from, $to, $saId = 0, $resultId = 0){
        //run revision
        //upa('revisions', 'registerRevision', array('SCOPE_SAMPLEINFO_CHANGE', $id, $field, $from, $value), False);

        if($field == 'description'){
            $eventLabel = 'Omschrijving';
        } elseif($field == 'sampling_method' ){
            $eventLabel = 'Bemonster methode';
        } else{
            $eventLabel = $field;
        }

        $event = 'Monster info gewijzigd: '  . $eventLabel;
        upa('changeTracker', 'changed', array(5,  $this->Sample->project, $this->Sample->id, False, $event, $from, $value), False);
        upa('projects', 'projectEdited', array($this->Sample->project, $id), False);
    }

    function fetchSampleProject($sample, $barcode = False)
    {

        if ($barcode == False) {
            $this->Sample->where('id', $sample);
        } else {
            //need to account for shorthands here


            if(strlen($barcode) <= 5){

              $shortHandIdentifier = substr($barcode, 0,1);
              if($shortHandIdentifier == 'L'){
                $this->Sample->where('sample_type', 'L'); //make sure we only select legionella samples for shorthand
                $this->Sample->where('follow_no', substr($barcode, 1));
              } else{
                $this->Sample->where('sample_type', 'S'); //make sure we only select legionella samples for shorthand
                $this->Sample->where('follow_no', $barcode);
              }

                //$this->Sample->where('follow_no', $barcode);
                //$this->Sample->where('sample_type', 'L'); //make sure we only select legionella samples for shorthand
                $this->Sample->order('id', 'desc');
                $this->Sample->limit('1');
                $results = $this->Sample->search();
            } else{
                $this->Sample->where('barcode', $barcode);
                $this->Sample->insertOR();
                $this->Sample->where('tht_code', $barcode);                
            }



        }

        $results = $this->Sample->search();

        if (!empty($results)) {
            return $results['0']['project'];
        } else {
            return False;
        }


    }

    function fetchSamplesInProject($project)
    {
        $this->Sample->where('project', $project);
        $results = $this->Sample->search();
        return $results;
    }

    function fetchSpecificSamplesInProject($project, $sampleArray)
    {

        if(!is_array($sampleArray)){
          $sampleArray = array();
        }

        $numSamples = count($sampleArray);
        $i = 1;

        foreach ($sampleArray as $sample) {


            $this->Sample->where('project', $project);
            $this->Sample->where('id', $sample);


            if ($i < $numSamples) {
                $this->Sample->insertOR();
            }

            $i++;
        }

        $results = $this->Sample->search();
        return $results;
    }

    function fetchSamplesInProjectDropdown($project)
    {

        $this->render = 0;
        $dropArr = array();
        $samples = $this->fetchSamplesInProject($project);

        foreach ($samples as $sample) {
            $dropArr[$sample['id']] = $sample['barcode'] . ' - ' . $sample['description'];
        }
        return $dropArr;
    }


    function readyToday()
    {

        $this->render = 0;

        $beginOfDay = strtotime("midnight", time());
        $endOfDay = strtotime("tomorrow", $beginOfDay);

        $this->Sample->lessThan('predicted_end', $endOfDay);
        $this->Sample->greaterThan('predicted_end', $beginOfDay);

        $results = $this->Sample->search();
        return $results;
    }

    function fetchFirstSampleInProject($project, $barcode = False)
    {
        $this->Sample->where('project', $project);
        $this->Sample->order('id', 'ASC');
        $this->Sample->order('follow_no', 'ASC');
        $this->Sample->limit('1');
        $results = $this->Sample->search();

        if (empty($results)) {
            return False;
        } else {
            if ($barcode == False) {
                return $results['0']['id'];
            } else {
                return $results['0']['barcode'];    
            }
        }
        return $results;
    }

    function findNumberOfSaplesPerClient($client)
    {

        $this->Sample->where('client', $client);
        $this->Sample->search();
        $noSamples = $this->Sample->lastQueryCount;
        return $noSamples;
    }

    function checkCompletionPercentage($projectId)
    {

        $this->Sample->where('project', $projectId);
        $samples = $this->Sample->search();

        $retObj = array();
        $retObj['n'] = count($samples);
        $retObj['running'] = 0;
        $retObj['completion'] = 100;
        $maxTime = 0;

        if (empty($samples)) {
            $retObj['expected'] = date('d-m', time());
        } else {

            foreach ($samples as $sample) {

                $percentage = $this->estimateProgress($sample['id']);

                if ($sample['predicted_end'] > time()) {
                    $retObj['running'] = $retObj['running'] + 1;
                }

                if ($sample['predicted_end'] > $maxTime) {
                    $maxTime = $sample['predicted_end'];
                }

                if ($percentage < $retObj['completion']) {
                    $retObj['completion'] = $percentage;
                }

                $retObj['expected'] = date('d-m', $maxTime);
            }
        }

        return $retObj;
    }

    function checkSampleComplete($sampleId)
    {
        $saids = upa('sampleAnalysis', 'fetchAnalysisArray', array($sampleId), False);
        $complete = True;

        if(empty($saids)){
          $complete = False;
        } else{
          foreach ($saids as $said) {
              //$thisSAcompleted = upa('results', 'saidIsComplete', array($said['id']), False);
              $thisSAcompleted = filter_var($said['is_ready'], FILTER_VALIDATE_BOOLEAN);

              if ($thisSAcompleted == False) {
                  $complete = False;
              }
          }
        }

        return $complete;
    }

    function register(){
        $this->_template->set('scroll_speed', MESA_SAMPLELIST_SCROLL);

        $binObject = json_decode(MESA_CURRENT_BIN, JSON_FORCE_OBJECT);
        $binObject = checkArrayOrEmpty($binObject);
        $userBin = checkKeyOrFalse($binObject, getUserId());

        $dilObject = json_decode(MESA_CURRENT_DILUTION, JSON_FORCE_OBJECT);
        $dilObject = checkArrayOrEmpty($dilObject);
        $dilBin = checkKeyOrFalse($dilObject, getUserId());

        if($userBin === False || (empty($userBin) && $userBin !== '0')){
          $userBin = 'A';
        }

        if($dilBin === False || (empty($dilBin) && $dilBin !== '0')){
            $dilBin = '1';
          }

        $this->_template->set('default_dilution', $dilBin);
        $this->_template->set('default_bin', $userBin);
    }

    function findInitialLoadRange(){
        $sql = 'SELECT id,sample_innoculated FROM samples WHERE sample_innoculated = "" ORDER BY id ASC LIMIT 1;';
        $result = $this->Sample->customQuery($sql, array());

        if(empty($result)){
            return False;
        }

        $bottom = $result[0]['id'];
        $sql = 'SELECT id FROM samples ORDER BY id DESC LIMIT 1;';
        $result = $this->Sample->customQuery($sql, array());
        $top = $result[0]['id'];

        $sql = 'SELECT id FROM samples WHERE id <=' . $top . ' AND id  >=' . $bottom . ';';
        $result = $this->Sample->customQuery($sql, array());
        $size = count($result);

        return array('top' => $top, 'bottom' => $bottom, 'size' => $size);
    }

    function loadRegisterList($loadFrom = 0, $search = False, $listType = '1', $topup = False){

        $this->render = False;
        $limit = 50;
        $doNotImposeLimit = False;
        $retObj = array();

        if($search == False){

            if($listType == 1){
                $this->Sample->where('sample_type', 'S');
            }

            if($listType == 2){
                $this->Sample->where('sample_type', 'L');
            }

            if($listType == 3){
                $this->Sample->where('sample_type', 'R');
            }

        }

        if($loadFrom == 'topup'){            
            //check if all the list samples where still there.
            $this->Sample->greaterThanHard('id', $topup);
        }
        elseif($loadFrom != 0){            
            $this->Sample->lessThanHard('id', $loadFrom);
        }

        elseif($search != False || $search != '0'){
            $barExp = explode('.', $search);
            $sampleBar = $barExp[0];

            $shortHand  = $this->isShortHand($search);
            if($shortHand === False){
                $this->Sample->where('barcode', $sampleBar);                
            } else{
                $this->Sample->where('follow_no', $shortHand['follow_no']);                
                $this->Sample->where('sample_type', $shortHand['sample_type']); 
                $this->Sample->where('leg_type', $shortHand['matrix_type']); 
                $this->Sample->order('id', 'desc');                        
            }
            
            //$this->Sample->where('barcode', $sampleBar);
        } else{
            //normal range
            $initalLoadRange = $this->findInitialLoadRange();                        
            if($initalLoadRange !== False){
                if($initalLoadRange['size'] > $limit){
                    $limit = $initalLoadRange['size'];
                }
            }
        }
        

        //check to cull this a bit 
        if($limit > 500){
            $limit = 500;
        }

        if($doNotImposeLimit == False){
            $this->Sample->limit($limit);
        }
        

        $this->Sample->order('id', 'DESC');
        $result = $this->Sample->search();

        if($loadFrom == 'topup'){
          $firstId = $topup;
        } else{
          $firstId = False;
        }


        if(empty($result)){
            $retObj['barcode'] = 'BAR_WRONG';
        }

        $retObj['n_results'] = count($result);

        $table = '';
        $projects = array();
        $clients = array();
        $firstInNewTable = False;

        $listAna = json_decode(MAZ_LISTERIAALL_IDS);
        $salmAna =  json_decode(MAZ_SALM_IDS);
        $campyAna = json_decode(MAZ_CAMPYLO_IDS);
        $stecAna = json_decode(MAZ_STEC_IDS);
        $sampleTagArray = array('listeria' => $listAna, 'salmonella' => $salmAna, 'campylobacter' => $campyAna, 'stec' => $stecAna);

        $sampleIdList = array_column($result, 'id');
        $checkForSampleAnalysis = upa('sampleAnalysis', 'batchCheckIfSampleHas', array($sampleIdList, $sampleTagArray), False);

        $clientIds =  array_unique(array_column($result, 'client'));
        $projectIds =  array_unique(array_column($result, 'project'));
        
        if(count($result) > 0){
            $idMap = implode(',', array_map('intval', $clientIds));        
            $sql = 'SELECT `id`,`name` FROM `clients` WHERE id IN (' . $idMap . ');';
            $clientresults = $this->Sample->customQuery($sql, array());
            $clients = array_column($clientresults, NULL, 'id'); 
    
            $idMap = implode(',', array_map('intval', $projectIds));
            $sql = 'SELECT `id`,`custom_fields` FROM `projects` WHERE id IN (' . $idMap . ');';
            $projectResults = $this->Sample->customQuery($sql, array());        
            $projects = array_column($projectResults, NULL, 'id'); 
        }
        
  
        foreach($result as $sample){

            //if($firstId == False || $loadFrom == 'topup'){
            if($firstInNewTable == False ){
              $firstInNewTable = $sample['id'];
            }

            if(!array_key_exists($sample['project'], $projects)){
                //$projects[$sample['project']] = upa('projects', 'fetch', array($sample['project']), False);
            }

            if(!array_key_exists($sample['client'], $clients)){
                //$clients[$sample['client']] = upa('clients', 'fetch', array($sample['client']), False);
            }

            if($sample['project'] == '0' || $sample['project'] == NULL){
                continue;
            }

            $project_details = json_decode($projects[$sample['project']]['custom_fields'], True);


            $sa = array();

            $sa['sample_id'] = $sample['id'];
            $sa['client_id'] = $sample['client'];

            if(isset($project_details['project_ontvangst'])){
                $sa['received_date'] =  $project_details['project_ontvangst'];
            } else{
                $sa['received_date'] =  'Onbekend';
            }

            if(isset($project_details['project_tijd_ontvangst'])){
                $sa['received_time'] = $project_details['project_tijd_ontvangst'];
            } else{
                $sa['received_time'] =  'Onbekend';
            }

            $sa['warning_note'] = '';
            if(!empty($sample['sample_note'])){
                $sa['warning_note'] = '<i class="icon icon-exclamation-sign" onClick="noteDisplay(\'' . $sample['id'] . '\')"></i>';

                //<a href="http://127.0.0.1/alpaca/samples/lookup/16101004" class="btn btn-primary btn-mini" title="Ga naar monster"><i class="icon icon-beaker"></i></a>


            }


            if($sample['sample_innoculated'] != ''){
                $sa['analysis_date'] = date('d-m-Y', $sample['sample_innoculated']);

                $innocTime = date('H:i:s', $sample['sample_innoculated']);

                if($innocTime != '00:00:00'){
                    $sa['analysis_time'] = date('H:i', $sample['sample_innoculated']);
                }else{
                    $sa['analysis_time'] = '';
                }
                $sa['started'] = '1';
            } else{
                $sa['analysis_date'] = '';
                $sa['analysis_time'] = '';
                $sa['started'] = '0';
            }

            $sa['LB'] = ALPC_BASEPATH;
            $sa['sample_no'] = $sample['barcode'];
            
    
                        
            if(array_key_exists($sample['client'], $clients)){                
                $sa['client'] = $clients[$sample['client']]['name'];
            } else{
                $sa['client'] = 'Onbekend';
            }                                   
            
            $sa['description'] = $sample['description'];

            //$listAna = array('16', '60', '25', '36');
            //$salmAna = array('23', '46', '39');


            //$listeria = upa('sampleAnalysis', 'checkIfSampleHas', array($sample['id'], $listAna), False);
            //$salmonella = upa('sampleAnalysis', 'checkIfSampleHas', array($sample['id'], $salmAna), False);
            //$campy  = upa('sampleAnalysis', 'checkIfSampleHas', array($sample['id'], $campyAna), False);
            //$stec  = upa('sampleAnalysis', 'checkIfSampleHas', array($sample['id'], $stecAna), False);



            $listeria = checkKeyOrFalse($checkForSampleAnalysis, $sample['id'],'listeria');
            $salmonella = checkKeyOrFalse($checkForSampleAnalysis, $sample['id'],'salmonella');
            $campy  = checkKeyOrFalse($checkForSampleAnalysis, $sample['id'],'campylobacter');
            $stec  = checkKeyOrFalse($checkForSampleAnalysis, $sample['id'],'stec');

            $sa['ana_listeria'] = '';
            $sa['ana_salmonella'] = '';
            $sa['ana_campy'] = '';
            $sa['ana_stec'] = '';

            if($listeria == True){
                $sa['ana_listeria'] = 'X';
            }
            if($salmonella == True){
                $sa['ana_salmonella'] = 'X';
            }
            if($campy == True){
                $sa['ana_campy'] = 'X';
            }
            if($stec == True){
                $sa['ana_stec'] = 'X';
            }

            $sa['stored_in'] = $sample['stored_in'];
            $sa['diluted_at'] = $sample['diluted_at'];
            $table .= generateHTML('registerList', $sa);

        }

        $retObj['firstListId'] = $firstInNewTable;
        $retObj['html'] = $table;
        $retObj['listType'] = $listType;

        if($search != False){

            //reset to the currently selected list type if no results found
            if(count($result) == 0){
                $retObj['listType'] = $listType;
            } else{

                $foundSampleType = $result[0]['sample_type'];

                $sampelTypeLink = array();
                $sampelTypeLink['S'] = '1';
                $sampelTypeLink['L'] = '2';
                $sampelTypeLink['R'] = '3';
                $retObj['listType'] = $sampelTypeLink[$foundSampleType];
            }
        }

        if($loadFrom == 'topup'){

            if(isset($_POST['currentlyInList'])){


                $currentList = json_decode($_POST['currentlyInList']);
                $sqlList = '';
                $previousList = array();

                foreach($currentList as $sampleIdInList){
                    if(!empty($sampleIdInList)){
                        $sqlList = $sqlList . $sampleIdInList . ',';
                        $previousList[$sampleIdInList] = $sampleIdInList;
                    }
                }

                $this->Sample->deepFreed();
                $query = "SELECT `id` FROM `samples` WHERE `id` IN (" . substr($sqlList, 0, -1) . ")";
                $params = array();
                $sql = $this->Sample->customQuery($query, $params);
                foreach($sql as $sampleResult){
                    unset($previousList[$sampleResult['id']]);
                }


                $retObj['toDelete'] = $previousList;

            }
        }

        print json_encode($retObj, JSON_FORCE_OBJECT);
    }

    private function sampleTypeAndMatrix($barcode){
        
        $barExp = explode('.', $barcode);
        $sampleBar = $barExp[0];        
        
        //remove legionella associated things
        $sampleBar = str_ireplace('LA', '',$sampleBar, $isA);
        $sampleBar = str_ireplace('LB', '',$sampleBar, $isB);
        $sampleBar = str_ireplace('LC', '',$sampleBar, $isC);
        $sampleBar = str_ireplace('L', '',$sampleBar, $isL);

        //set sample type and matrix 
        if($isA > 0)
        {
            $sampleType = 'L';
            $matrixType = 'A';
        }

        else if($isB > 0)
        {
            $sampleType = 'L';
            $matrixType = 'B';
        }

        else if($isC > 0)
        {
            $sampleType = 'L';
            $matrixType = 'C';
        }

        else if($isL > 0)
        {
            //old legionella samples 
            $sampleType = 'L';
            $matrixType = '-';
        }

        else
        {
            $sampleType = 'S';
            $matrixType = '-';
        }

        return[
            'sampleBar' =>  $barExp[0],
            'follow_no' => $sampleBar,
            'sampleType' => $sampleType,
            'matrixType' => $matrixType
        ];
        
        
    }

    private function isShortHand($barcode){

        $barInfo = $this->sampleTypeAndMatrix($barcode);
                
        //check length 
        if(strlen($barInfo['follow_no']) < 8) {
            
            return
            [
                'barcode' => $barcode,
                'follow_no' => $barInfo['follow_no'], 
                'sample_type' => $barInfo['sampleType'], 
                'matrix_type' =>  $barInfo['matrixType']
            ];
                        
        }

        else 
        {
            return False; 
        }

    }

    function registerInnoculation($barcode, $quiet = False){
        $this->render = False;

        if(isset($_POST['storage'])){
            $storage = $_POST['storage'];
        } else{
            $storage = '';
        }

        if(isset($_POST['dilution_at'])){
            $dilution_at = $_POST['dilution_at'];
        } else{
            $dilution_at = '';
        }

        $barExp = explode('.', $barcode);
        $sampleBar = $barExp[0];
        $sampleAnalysis = False;
        $sampleAnalysisFollow = False;


        $shortHand = $this->isShortHand($barcode);
        
        if($shortHand !== False){
            $this->Sample->where('follow_no', $shortHand['follow_no']);
            $this->Sample->where('sample_type', $shortHand['sample_type']); 
            $this->Sample->where('leg_type', $shortHand['matrix_type']); 
            $this->Sample->order('id', 'desc');
            $this->Sample->limit('1');
            $results = $this->Sample->search();

        }


        else{
            $this->Sample->where('barcode', $sampleBar);
            $results = $this->Sample->search();
        }


        //check if a match was found
        if (empty($results)) {
            if($quiet == False){
                print json_encode(array('barcode' => 'BAR_WRONG'), JSON_FORCE_OBJECT);
            }
            return;
        }

        $authProject = $this->partOfAuthProject($results[0]['id']);

        if ($authProject === true) {
            if($quiet == False){
                print json_encode(array('barcode' => 'ALREADY_AUTHORIZED'), JSON_FORCE_OBJECT);
            }
            return;
        }

        //check if this was already started? If so, deny save and request confirmation
        $overwriteConfirmed = (isset($_POST['overwrite'])) ? true : false;

        if(!empty($results[0]['sample_innoculated']) && $overwriteConfirmed == False){
            print json_encode(array('barcode' => 'ALREADY_STARTED'), JSON_FORCE_OBJECT);
            return;
        }

        $this->Sample->id = $results[0]['id'];
        $this->Sample->sample_innoculated = time();
        
        $this->Sample->stored_in = $storage;
        $this->Sample->diluted_at = $dilution_at; 

        $arr = array();
        $arr['id'] = $results[0]['id'];
        $arr['date'] = date('d-m-Y', time());
        $arr['time'] = date('H:i', time());
        $arr['storage'] = $storage;
        $arr['diluted_at'] = $dilution_at;

        $this->Sample->save();
        $estimatedEnd = $this->estimateEndPoints($results[0]['id']);

        //assurance form started?
        upa('assuranceForms', 'checkFormOrCreate', array(), False);
        upa('assuranceForms', 'updateFormByInnocDate', array(time()), False);
        upa('projects', 'setStartedFlag', array($results[0]['project']), False);

        $event =  'Inzet datum / tijd en opslag bak geregistreerd: ' .  $arr['date'] . '/' . $arr['time'] . ' Opslag:' . $arr['storage'];
        upa('changeTracker', 'changed', array(4, $results[0]['project'], $results[0]['id'], False, $event, False, False), False);

        //update portal
        if(!empty($results[0]['portal_sample_id'])){
          //need to send innoc date to portal
          upa('portal', 'sampleStart', array($results[0]['portal_sample_id'], time(), $estimatedEnd), False);
        }
 
        

        if($quiet == False){
            print json_encode($arr, JSON_FORCE_OBJECT);
        }
    }
    

    function getSampleInnoculationDate($project){

        $this->Sample->where('project', $project);
        $this->Sample->notLike('sample_innoculated', '');
        $this->Sample->order('sample_innoculated', 'ASC');
        $this->Sample->limit(1);

        $result = $this->Sample->search();
        if(!empty($result) && !empty($result[0]['sample_innoculated'])){
            return $result[0]['sample_innoculated'];
        } else{
            return False;
        }
    }

    function getSampleSpecificInnoculationDate($sample){

        $this->Sample->where('id', $sample);
        $this->Sample->limit(1);

        $result = $this->Sample->search();
        if(!empty($result) && !empty($result[0]['sample_innoculated'])){
            return $result[0]['sample_innoculated'];
        } else{
            return False;
        }
    }


    function editSampleListLoad($sampleId){
        $this->render = False;

        $this->Sample->where('id', $sampleId);
        $result = $this->Sample->search();


        if(!empty($result)){
            $projectInfo =  upa('projects', 'fetchProjectInfo', array($result['0']['project']));        

            if ($projectInfo['auth_status'] == 1) {
                print json_encode(False, JSON_FORCE_OBJECT);
                return;
            }

            $time = date('d-m-Y H:i', $result[0]['sample_innoculated']);
            $store = $result[0]['stored_in'];
            $diluted_at = $result[0]['diluted_at'];
            print json_encode(array('time' => $time, 'store' => $store,  'diluted_at' => $diluted_at), JSON_FORCE_OBJECT);
        } else{
            print json_encode(False, JSON_FORCE_OBJECT);
        }
    }

    function editSampleListSave(){
        $this->render = False;        
        $this->Sample->where('id', $_POST['id']);
        $result = $this->Sample->search();

        if(empty($result)){
            return;
        }
        
        //part of auth? 
        $projectInfo =  upa('projects', 'fetchProjectInfo', array($result[0]['project']));

        if ($projectInfo['auth_status'] == 1) {
            return; 
        }

        $this->Sample->deepFreed();
        $this->Sample->id = $result['0']['id'];
        $this->Sample->stored_in = $_POST['storage'];
        $this->Sample->diluted_at = $_POST['diluted_at'];
        $this->Sample->sample_innoculated = strtotime($_POST['innoc']);
        $this->Sample->save();

        $msg = array();
        $newTime = strtotime($_POST['innoc']);

        upa('assuranceForms', 'checkFormOrCreate', array( $newTime), False);
        upa('assuranceForms', 'updateFormByInnocDate', array(time()), False);

        $msg['id'] = $_POST['id'];
        $msg['date'] = date('d-m-Y', $newTime);
        $msg['time'] = date('H:i', $newTime);
        $msg['storage'] = $_POST['storage'];
        $msg['diluted_at'] = $_POST['diluted_at'];

        $event =  'Inzet datum / tijd, opslag bak en afweegstation  manueel gewijzigd naar: ' .  $msg['date'] . '/' . $msg['time'] . ' Opslag:' . $msg['storage'] . ' afweegstation : ' . $msg['diluted_at'] ;

        $sampleInfo = upa('samples', 'fetch', array($_POST['id']), False);
        upa('changeTracker', 'changed', array(4, $sampleInfo['project'], $_POST['id'], False, $event, False, False), False);

       
        unset($this->Sample->id);
        $this->Sample->deepFreed();
        $estimatedEnd = $this->estimateEndPoints( $result['0']['id']);

        if(!empty($result[0]['portal_sample_id'])){
            //need to send innoc date to portal
            upa('portal', 'sampleStart', array($result[0]['portal_sample_id'], $newTime, $estimatedEnd), False);
        }

        print(json_encode($msg, JSON_FORCE_OBJECT));
    }

    function updateBinSetting(){
        $this->render = false;

        $binObject = json_decode(MESA_CURRENT_BIN, JSON_FORCE_OBJECT);
        $binObject = checkArrayOrEmpty($binObject);
        $user = getUserId();
        $binObject[$user] =  (string)$_POST['bin'];
        $binEncode = json_encode($binObject, JSON_FORCE_OBJECT);

        #upa('cvars', 'cvarSaveName', array('name' => 'MESA_CURRENT_BIN' , 'value' => $_POST['bin'] ), False);
        upa('cvars', 'cvarSaveName', array('MESA_CURRENT_BIN', $binEncode ), False);
        print json_encode(array(), JSON_FORCE_OBJECT);
    }

    function updateDilutionSetting(){
        $this->render = false;

        $dilObject = json_decode(MESA_CURRENT_DILUTION, JSON_FORCE_OBJECT);
        $dilObject = checkArrayOrEmpty($dilObject);
        $user = getUserId();
        $dilObject[$user] =  (string)$_POST['bin'];
        $dilEncode = json_encode($dilObject, JSON_FORCE_OBJECT);
        
        upa('cvars', 'cvarSaveName', array('MESA_CURRENT_DILUTION', $dilEncode ), False);
        print json_encode(array(), JSON_FORCE_OBJECT);
    }


    function getSamplesInRoughDate($start, $end, $field = False, $innoc = False)
    {

        $yearEnd = $end + (365 * 82800);
        $yearStart = $start - (365 * 82800);

        $this->Sample->greaterThan('date_registered', $yearStart);
        $this->Sample->lessThan('date_registered', $yearEnd);

        $preSelection = $this->Sample->search();
        $selectedSamples = array();

        foreach ($preSelection as $preSelectedSample) {

            if($field == False && $innoc == False){
                $dateForSample = $preSelectedSample['date_registered'];
                if ($dateForSample >= $start && $dateForSample <= $end) {
                    array_push($selectedSamples, $preSelectedSample);
                }
            } elseif($field == False && $innoc == True){
                $dateForSample = $preSelectedSample['sample_innoculated'];
                if ($dateForSample >= $start && $dateForSample <= $end) {
                    array_push($selectedSamples, $preSelectedSample);
                }
            }else{
                $customFields = json_decode($preSelectedSample['custom_fields'], JSON_FORCE_OBJECT);
                if (array_key_exists($field, $customFields)) {
                    $dateForSample = strtotime($customFields[$field]);
                    if ($dateForSample >= $start && $dateForSample <= $end) {
                        array_push($selectedSamples, $preSelectedSample);
                    }
                }
            }
        }

        return $selectedSamples;
    }

    function getSamplesForInnocDate($date){
        $beginOfDay = strtotime("midnight", $date);
        $endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
        $this->Sample->greaterThan('sample_innoculated', $beginOfDay);
        $this->Sample->lessThan('sample_innoculated', $endOfDay);
        $result = $this->Sample->search();
        return $result;
    }

    function grabBetweenBarcodes($barStart, $barStop){

        $this->render = false;

        //check if not empty
        if(empty($barStart) || empty($barStop))
        {
            return array(); 
        }

        #704: we need to check if this bar start is actually
        #a multipart barcode, and only use the first part if it is.
        $barStartExplode = explode('.', $barStart);
        $barStopExplode = explode('.', $barStop);

        $barStart = $barStartExplode[0];
        $barStop = $barStopExplode[0];

        $startShortHand = $this->isShortHand($barStart);
        $stopShortHand = $this->isShortHand($barStop);

        

        //return empty, this can't work 
        //we dont allow mixing short-hands and full barcodes
        if(($startShortHand === False && is_array($stopShortHand) ) ||  ($stopShortHand === False && is_array($startShortHand)) )
        {
            return array();            
        }

        //normal lookup 
        if($startShortHand === False && $stopShortHand === false)
        {                        
            
            $parsedStartBar = $this->sampleTypeAndMatrix($barStart);
            $parsedStopBar = $this->sampleTypeAndMatrix($barStop);            

            $sampleTypes = array_unique([$parsedStartBar['sampleType'], $parsedStopBar['sampleType']]);
            $sampleLegTypes = array_unique([$parsedStartBar['matrixType'], $parsedStopBar['matrixType']]);

            //we dont allow mixing of sample types, or matrix types
            if(count($sampleTypes) > 1 || count($sampleLegTypes) > 1){
                return array();
            }

            //find beginning and end point
            $sql = "SELECT * FROM `samples` WHERE (`barcode` = :barcode OR  `barcode` = :barcode2) AND `sample_type` = :sample_type AND `leg_type` = :leg_type  ORDER BY `id` DESC LIMIT 2";
            $results = $this->Sample->customQuery($sql, array('barcode' => $barStart, 'barcode2' => $barStop, 'sample_type' => $parsedStartBar['sampleType']  , 'leg_type' => $parsedStartBar['matrixType'] ));
                       
            //did not find one of the endpoints, can't determine range 
            if($barStart !== $barStop && count($results) < 2){
                return array();
            }


            //account for the fact that start & stop might be the same
            if($barStart !== $barStop){
                $minID = min($results[0]['id'], $results[1]['id']);
                $maxID = max($results[0]['id'], $results[1]['id']);
            } else{
                $minID = $results[0]['id'];
                $maxID = $minID;
            }
            
            //grab samples
            $sql = "SELECT * FROM `samples` WHERE (`id` >= :minID AND  `id` <= :maxID) AND `sample_type` = :sample_type AND `leg_type` = :leg_type  ORDER BY `id` ASC";
            $results = $this->Sample->customQuery($sql, array('minID' => $minID, 'maxID' => $maxID, 'sample_type' => $parsedStartBar['sampleType']  , 'leg_type' => $parsedStartBar['matrixType'] ));
                                    
        }

        //shorthand lookup 
        else 
        {            
            $sampleTypes = array_unique([$startShortHand['sample_type'], $stopShortHand['sample_type']]);
            $sampleLegTypes = array_unique([$startShortHand['matrix_type'], $stopShortHand['matrix_type']]);

            //we dont allow mixing of sample types, or matrix types
            if(count($sampleTypes) > 1 || count($sampleLegTypes) > 1){
                return array();
            }

            

            //find beginning and end point
            $sql = "SELECT * FROM `samples` WHERE (`follow_no` = :follow_no OR  `follow_no` = :follow_no2) AND `sample_type` = :sample_type AND `leg_type` = :leg_type  ORDER BY `id` DESC LIMIT 2";
            $results = $this->Sample->customQuery($sql, array(  'follow_no' => $startShortHand['follow_no'], 
                                                                'follow_no2' => $stopShortHand['follow_no'], 
                                                                'sample_type' => $startShortHand['sample_type'], 
                                                                'leg_type' => $startShortHand['matrix_type'] ));
            
            //did not find one of the endpoints, can't determine range 
            if($startShortHand['follow_no'] !== $stopShortHand['follow_no'] && count($results) < 2){
                return array();
            }

            //account for the fact that start & stop might be the same
            if($startShortHand['follow_no'] !== $stopShortHand['follow_no']){
                $minID = min($results[0]['id'], $results[1]['id']);
                $maxID = max($results[0]['id'], $results[1]['id']);
            } else{
                $minID = $results[0]['id'];
                $maxID = $minID;
            }            

            //grab samples
            $sql = "SELECT * FROM `samples` WHERE (`id` >= :minID AND  `id` <= :maxID) AND `sample_type` = :sample_type AND `leg_type` = :leg_type  ORDER BY `id` ASC";
            $results = $this->Sample->customQuery($sql, array(  'minID' => $minID, 
                                                                'maxID' => $maxID, 
                                                                'sample_type' => $startShortHand['sample_type'], 
                                                                'leg_type' => $startShortHand['matrix_type'] ));            

        }        
        
        return $results;
    }

    function fetchNote($sampleId = False){

        if($sampleId == False && isset($_POST['sampleId'])){
            $sampleId = $_POST['sampleId'];
        }

        $this->render = False;
        $this->Sample->where('id', $sampleId);
        $result = $this->Sample->search();

        $arr = array();
        $arr['note'] = '';

        if(!empty($result)){
            $arr['note'] = $result[0]['sample_note'];
        }
        print json_encode($arr, JSON_FORCE_OBJECT);
    }

    function auditTrail(){
    }

    private function isValidTimeStamp($timestamp)
    {
      return ((string) (int) $timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }

    function sampleAuditTrail($barcode = False){

        if(isset($_POST['barcode'])){
          $this->doNotRenderHeader = True;
          $barcode = $_POST['barcode'];
        } else{
          //using the print viewing thing
          $this->renderAlternateHeader = 'slim';
        }

        //$barcode = $_POST['barcode'];

        $barExp = explode('.', $barcode);
        $sampleBar = $barExp[0];

        $shortHand = $this->isShortHand($sampleBar);

        if($shortHand === False){
            $this->Sample->where('barcode', $sampleBar);   
                 
        } else{
            $this->Sample->where('follow_no', $shortHand['follow_no']);                
            $this->Sample->where('sample_type', $shortHand['sample_type']); 
            $this->Sample->where('leg_type', $shortHand['matrix_type']);                    
        }

        $this->Sample->order('id', 'desc');   
        $this->Sample->limit('1');            
        $results = $this->Sample->search();
        $sampleAnalysis = False;
        $sampleAnalysisFollow = False;


        //check if a match was found
        if (empty($results)) {
            $this->render = False;
            print 'Foute barcode';
            return;
        } else{
          $result = $results[0];
          $sample = $result;
        }

        //sample info
        $this->_template->set('barcode', $sampleBar);

        //grab clietn info
        $clientInfo = upa('clients', 'fetch', array($result['client']), False);

        $title = checkKeyOrFalse($clientInfo, 'title');
        $first = checkKeyOrFalse($clientInfo, 'fname');
        $middle = checkKeyOrFalse($clientInfo, 'mname');
        $last = checkKeyOrFalse($clientInfo, 'lname');
        $contact = $title . ' ' . $first . ' ' . $middle . ' ' . $last;

        $this->_template->set('client_id', $result['client']);
        $this->_template->set('opdrachtgever', checkKeyOrFalse($clientInfo, 'name'));
        $this->_template->set('contactpersoon', $contact);
        $this->_template->set('street', checkKeyOrFalse($clientInfo, 'street_name'));
        $this->_template->set('number', checkKeyOrFalse($clientInfo, 'street_number'));
        $this->_template->set('place', checkKeyOrFalse($clientInfo, 'place'));

        //grab project info
        $projectInfo = upa('projects', 'fetch', array($result['project']), False);
        $numberOfSamples =  upa('samples', 'countSamplesInProject', array($projectInfo['id']));

        $this->_template->set('numberofsamples', checkKeyOrFalse($numberOfSamples));
        
        $this->_template->set('projectid', checkKeyOrFalse($projectInfo, 'reference'));
        $this->_template->set('projectName', checkKeyOrFalse($projectInfo, 'project_name'));

        //projectid??


        $this->_template->set('revision', checkKeyOrFalse($projectInfo, 'revision'));

        if($projectInfo['auth_on'] == NULL){
          $this->_template->set('last_auth', 'Nog niet geauthoriseerd geweest');
          $this->_template->set('last_auth_by', 'Nog niet geauthoriseerd geweest');
        } else{
          $this->_template->set('last_auth', date('d-m-Y', $projectInfo['auth_on']));
          $authName = upa('profiles', 'getUserFullName', array($projectInfo['auth_by']), False);
          $this->_template->set('last_auth_by', $authName);
        }

        if($projectInfo['rap_on'] == NULL){
          $this->_template->set('last_rap', 'Nog niet gerapporteerd geweest');
          $this->_template->set('last_rap_by', 'Nog niet gerapporteerd geweest');
        } else{
          $this->_template->set('last_rap', date('d-m-Y', $projectInfo['rap_on']));
          $authName = upa('profiles', 'getUserFullName', array($projectInfo['rap_by']), False);
          $this->_template->set('last_rap_by', $authName);
        }

        $this->_template->set('project_notes', $projectInfo['project_notes']);

        //algemene monster gegevens
        $pcf = json_decode($projectInfo['custom_fields'], JSON_FORCE_OBJECT);
        $pef = json_decode($projectInfo['project_extra'], JSON_FORCE_OBJECT);

        $this->_template->set('date_sampling', checkKeyOrFalse($pcf, 'project_monster'));

        $this->_template->set('date_receive', checkKeyOrFalse($pcf, 'project_ontvangst'));
        $this->_template->set('time_receive', checkKeyOrFalse($pcf, 'project_tijd_ontvangst'));

        //innoculation data
        if(empty($sample['sample_innoculated'])){
          $innoc = 'Nog niet ingezet';
        } else{
          $innoc = date('d-m-Y H:i', $sample['sample_innoculated']);
        }

        $this->_template->set('timedate_innoc', $innoc);
        $this->_template->set('stored_in', $sample['stored_in']);
        $this->_template->set('diluted_at', $sample['diluted_at']);
        $this->_template->set('tht_code', $sample['tht_code']);

        $scf = json_decode($sample['custom_fields'], JSON_FORCE_OBJECT);

        $this->_template->set('sample_description', $sample['description']);
        $this->_template->set('sample_details', checkKeyOrFalse($scf, 'details'));
        $this->_template->set('sample_notes', $sample['sample_note']);
        $this->_template->set('portal_notes',  $sample['portal_notes']);



        $sampleMethod = array();

        //is this a legionella sample if so retreive the sample method from project
        //if not, retreive from sample
        //
        if($sample['sample_type'] == 'L'){
          $method = checkKeyOrFalse($pef, 'sample_method');
          //use sample table as fallback
          if($method == False){
            $method = $sample['sampling_method'];
          }
        } else{
          $method = $sample['sampling_method'];
        }

        $sampleMethod = upa('sampleProcedures', 'fetch', array($method), False);
        $smf = json_decode($sampleMethod['fields'], JSON_FORCE_OBJECT);

        $this->_template->set('sampling_by', checkKeyOrFalse($smf, 'door'));
        $this->_template->set('sampling_method', checkKeyOrFalse($sampleMethod, 'name'));

        //grab borgingsformulier
        $validStamp = $this->isValidTimeStamp($sample['sample_innoculated']);

        if($validStamp != False){
          $beginOfDay = strtotime("midnight", $sample['sample_innoculated']);
          $bf = upa('assuranceForms', 'fetchByDate', array($beginOfDay), False);
        } else{
          $bf = False;
        }



        if($bf !== False){
            $this->_template->set('borgHider', '');
            $this->_template->set('borgMsg', 'hide');
            $bdf = json_decode($bf['data'], JSON_FORCE_OBJECT);

            $userAfweeg = checkKeyOrFalse($bdf, '0', 'afgewogen');
            $userInzet = checkKeyOrFalse($bdf, '0', 'ingezet');
            $userGegoten = checkKeyOrFalse($bdf, '0', 'gegoten');

            $this->_template->set('bf_afweeg', userIdToName($userAfweeg));
            $this->_template->set('bf_inzet',  userIdToName($userInzet));
            $this->_template->set('bf_gegoten',  userIdToName($userGegoten));

            $borgRevisions = upa('changeTracker', 'changesForAssuranceForm', array($bf['id'], True), False);
            $this->_template->set('borg_revisions', $borgRevisions);

            //grab analysis
            $ana = upa('sampleAnalysis', 'fetchAnalysisArray', array($sample['id']), False);
            $av = array();
            foreach($ana as $follow => $thisAna){
              $volgNo = $follow + 1;
              $av[$volgNo] = $thisAna;
            }
            unset($ana);

            //build ophopings table_title
            $ophopingsTable = '';
            //$opHopings = array('pfz' => 'PFZ', 'pfz_buizen' => 'PFZ Buizen', 'citraat' => 'Citraat', 'bpw' => 'BPW');
            $opHopings = array('pfz' => 'PFZ', 'pfz_buizen' => 'PFZ Buizen', 'bpw' => 'BPW');
            foreach($opHopings as $opMedia => $readableName){
              $tht = checkKeyOrFalse($bdf, '2', 'tht_' . $opMedia);
                            $fieldId = 'b2_tht_' . $opMedia;
                            $isOutOfSpec = $this->auditTrailDateIsOutOfSpec($tht, $bf['date']);
                            $thisOpArray = array(
                                'media' => $readableName,
                                'tht'=> $tht,
                                'tht_style' => $this->auditTrailOutOfSpecStyle($isOutOfSpec),
                                'tht_note' => $this->auditTrailFieldNote($bdf, $fieldId, $isOutOfSpec)
                            );
              $ophopingsTable .= generateHTML('audittrail/ophopingline', $thisOpArray);
            }

            $this->_template->set('ophopings_table', $ophopingsTable);
            $assayInfo = array();

            $analysisTable = '';
            $assayTable = '';
            $mediaTable = '';
            $materialTable = '';
            $confTable = '';
            $confMediaTable = '';
            $noteTable = '';
            $revTable = '';
            $assayResultTable = '';
            $confResultTable = '';

            foreach($av as $volg => $ana){

              $thisAssayInfo =  upa('assays', 'fetch', array($ana['assay_base']), False);
         
              $thisAssayRevision = upa('assays', 'fetchRevisionNumber', array($ana['assay_base']), False);
              $thisAssayInfoCustom = json_decode($thisAssayInfo['custom_fields'], JSON_FORCE_OBJECT);


              if(is_array($thisAssayInfoCustom)){
                  foreach($thisAssayInfoCustom as $cF => $cV){
                    $thisAssayInfo['custom_' . $cF] = $cV;
                  }
              }
              $assayInfo[$volg] = $thisAssayInfo;

              $rep = array();
              $rep['volg'] = $volg;
              $rep['name'] = checkKeyOrFalse($assayInfo, $volg, 'name');
              $rep['date_in'] = date('d-m-Y', $bf['date']);
              $rep['time_in'] = checkKeyOrFalse($bdf, '0', 'instoof');


              $blockForOut = checkKeyOrFalse($bdf, '1', $assayInfo[$volg]['duration']);
              $rep['date_out'] = checkKeyOrBlank($blockForOut, 'datum_uitstoof');
              $rep['time_out'] = checkKeyOrBlank($blockForOut, 'tijd_uitstoof');
              $rep['date_read'] = checkKeyOrBlank($blockForOut, 'datum_aflezen');
              $rep['time_read'] = checkKeyOrBlank($blockForOut, 'tijd_aflezen');
              $rep['read_by'] = userIdToName( checkKeyOrBlank($blockForOut, 'afgelezen_door'));

              $analysisTable .= generateHTML('audittrail/analysisrow', $rep);

              $repA = array();
              $repA['volg'] = $volg;
              $repA['name'] = checkKeyOrFalse($assayInfo, $volg, 'name');
              $repA['q'] = checkKeyOrFalse($assayInfo, $volg, 'custom_accred');
              $repA['techniek'] = checkKeyOrFalse($assayInfo, $volg, 'custom_techniek');
              $repA['ref'] = checkKeyOrFalse($assayInfo, $volg, 'custom_internrefnummer');
              $repA['conf'] = checkKeyOrFalse($assayInfo, $volg, 'custom_conform');
              $repA['refmethod'] = checkKeyOrFalse($assayInfo, $volg, 'custom_referentiemethode');
              $repA['assay_id'] = checkKeyOrFalse($assayInfo, $volg, 'original_id');
              $repA['revision'] = $thisAssayRevision;

              $assayTable .= generateHTML('audittrail/assayline', $repA);

              $media = upa('assays', 'assayMedia', array($ana['assay_base']), false);

              foreach($media as $thisMediaId => $thisMedia){
                $repM = array();
                $repM['volg'] = $volg;
                $repM['name'] = $thisMedia['name'];
                $repM['media_tht'] = checkKeyOrFalse($bdf, '3', $thisMedia['id']);
                $mediaFieldId = 'b3_' . $thisMedia['id'];
                $mediaIsOutOfSpec = !$this->auditTrailIsNotApplicable($repM['media_tht']) && ($this->auditTrailDateIsOutOfSpec($repM['media_tht'], $bf['date']) || $this->auditTrailWasOutOfDateHere($bdf, $thisMedia['id'], $sample['id']));
                $repM['media_tht_style'] = $this->auditTrailOutOfSpecStyle($mediaIsOutOfSpec);
                $repM['media_tht_note'] = $this->auditTrailFieldNote($bdf, $mediaFieldId, $mediaIsOutOfSpec);

                $suppTxt = '';
                $suppThtTxt = '';
                $supplements = json_decode($thisMedia['supplements'], JSON_FORCE_OBJECT);

                if(is_array($supplements)){
                  foreach($supplements as $supplement){
                    //$suppTHT = checkKeyOrFalse();

                    $suppTxt .= $supplement['name'];
                    $keyName = 'extra_' . $thisMedia['id'] . '_' . $supplement['supplementId'];
                    $suppTht = checkKeyOrFalse($bdf, '3', $keyName);
                    $suppFieldId = 'b3_' . $keyName;
                    $suppIsOutOfSpec = !$this->auditTrailIsNotApplicable($suppTht) && ($this->auditTrailDateIsOutOfSpec($suppTht, $bf['date']) || $this->auditTrailWasOutOfDateHere($bdf, $thisMedia['id'], $sample['id']));
                    $suppThtTxt .= '<span style="' . $this->auditTrailOutOfSpecStyle($suppIsOutOfSpec) . '">' . $suppTht . $this->auditTrailFieldNote($bdf, $suppFieldId, $suppIsOutOfSpec) . '</span>';

                    //add nbsp and br
                    $suppTxt .= '&nbsp; <br />';
                    $suppThtTxt .= '&nbsp; <br />';

                  }
                }

                $repM['supptxt'] = $suppTxt;
                $repM['suppthttxt'] = $suppThtTxt;

                $mediaTable .= generateHTML('audittrail/medialine', $repM);
              }

              $material = upa('assays', 'assayMaterial', array($ana['assay_base']), false);

              foreach($material as $thisMaterialId => $thisMaterial){
                $repMat = array();
                $repMat['volg'] = $volg;
                $repMat['name'] = $thisMaterial['name'];
                $repMat['value']  = checkKeyOrFalse($bdf, '3', $thisMaterialId);
                $repMat['value']  = $repMat['value'] . $thisMaterial['supplements'];
                $materialRange = checkKeyOrFalse($thisMaterial, 'acceptable_range');
                $materialIsOutOfSpec = !$this->auditTrailIsNotApplicable(checkKeyOrFalse($bdf, '3', $thisMaterialId)) && ($this->auditTrailMaterialIsOutOfSpec(checkKeyOrFalse($bdf, '3', $thisMaterialId), $materialRange) || $this->auditTrailWasOutOfDateHere($bdf, $thisMaterialId, $sample['id']));
                $repMat['value_style'] = $this->auditTrailOutOfSpecStyle($materialIsOutOfSpec);
                $repMat['value_note'] = $this->auditTrailFieldNote($bdf, 'b3_' . $thisMaterialId, $materialIsOutOfSpec);
                $materialTable .= generateHTML('audittrail/matline', $repMat);
              }

              $conf = array();
              $conf['volg'] = $volg;
              $conf['name'] = checkKeyOrFalse($assayInfo, $volg, 'name');
              $confOption = '';


              if($thisAssayInfo['confirmation'] == 0){
                $confOption = 'Nee';
              } else {
                if($ana['conf_requested'] == 0){
                  $confOption = 'Nee';
                }
                if($ana['conf_requested'] == 1){
                    $confOption = 'Ja';
                }
                if($ana['conf_requested'] == 2){
                    $confOption = 'Nee';
                }

                if($thisAssayInfo['confirmation_type'] == '0'){
                  $globalConf = True;
                } else{
                  $globalConf = False;
                }
              }


              $conf['conf'] = $confOption;
              $confTable .= generateHTML('audittrail/confrow', $conf);

              $confRows = upa('confirmations', 'fetchLine', array($ana['id']), False);
              $confScript = json_decode($thisAssayInfo['confirmation_script'], JSON_FORCE_OBJECT);
              $supportScript = json_decode($thisAssayInfo['confirmation_support'], JSON_FORCE_OBJECT);
              $confData = json_decode($confRows['data'], JSON_FORCE_OBJECT);
              $raceTracks = json_decode($confRows['racetrack'], JSON_FORCE_OBJECT) ;
              $metaData =  json_decode($confRows['metadata'], JSON_FORCE_OBJECT) ;


              if(!is_array($confRows)){
                $confRows = array();
              }

              if(!is_array($confScript)){
                $confScript = array();
              }

              if(!is_array($confData)){
                $confData = array();
              }


              if($ana['conf_requested'] == 1){
                $inuse = json_decode($confRows['in_use'], JSON_FORCE_OBJECT);

                // if(!is_array($inuseDf)){
                //   $inuse = array();
                // } else{
                //   $inuse = array();
                //   //we need to consolidate this to a unique thingy
                //   foreach($inuseDf as $mediaDf => $mediaReps){
                //     foreach($mediaReps as $mediasInuse){
                //       $keys = array_keys($mediasInuse, True);
                //       $inuse = array_merge($inuse, $keys);
                //     }
                //   }
                // }

                //foreach($confScript as $confRow){

                  $assayResults = upa('results', 'getTestResults', array($ana['id'], True), False);

                  foreach($assayResults as $asRes){
                  #$status = checkKeyOrFalse($inuse, $confRow['mediaId']);

                  $thisDf = $asRes['df'];
                  $thisRep = $asRes['rep'];

                  if($globalConf == True){
                    $thisDf = 'global';
                  }

                  $raceTrackExists = checkKeyOrFalse($raceTracks, $thisDf, $thisRep);

                  if($raceTrackExists == False){
                    continue;
                  }

                  foreach($confScript as $confRow){

                  $status = checkKeyOrFalse($inuse, $thisDf, $thisRep, $confRow['mediaId']);

                  if($status === True){

                    $confResultRow = array();
                    $cm = array();

                    $cm['df'] = $thisDf;

                    $cm['rep'] = $thisRep;
                    if($globalConf == True){
                      $cm['df'] = 'Globaal';
                    }


                    $thisConfMedia = upa('media', 'fetch' , array($confRow['mediaId']), False);
                    $cm['volg'] =  $volg;
                    $cm['name'] =  $thisConfMedia['name'];
                    $cm['tht'] = checkKeyOrFalse($bdf, '3', $confRow['mediaId']);

                    //$cm['ingezet'] = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_inzet');
                    //$cm['afgelezen'] = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_aflees');

                    $cm['ingezet'] = checkKeyOrFalse($metaData, $thisDf, $thisRep, $confRow['chainId'] - 1, $confRow['mediaId'] . '_inzet');
                    $cm['afgelezen'] = checkKeyOrFalse($metaData,$thisDf, $thisRep, $confRow['chainId'] - 1, $confRow['mediaId'] . '_aflees');

                    $inzetId = checkKeyOrFalse($metaData, $thisDf, $thisRep, $confRow['chainId'] -1, $confRow['mediaId'] . '_inzet_user');
                    $afleesId = checkKeyOrFalse($metaData,  $thisDf, $thisRep, $confRow['chainId'] -1, $confRow['mediaId'] . '_aflees_user');

                    $inzetBy = userIdToName($inzetId);
                    $afleesBy = userIdToName($afleesId);

                    $cm['inzetby'] =  $inzetBy;
                    $cm['afleesby'] =  $afleesBy;

                    $confResultRow['volg'] = $volg;
                    $confResultRow['name'] =  $thisConfMedia['name'];
                    $confResultRow['df'] =   $cm['df'] ;
                    $confResultRow['rep'] = $cm['rep'];


                    //$thisConfGetest = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_n');
                    //$thisConfPos = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_pos');

                    $thisConfGetest = count($raceTracks[$thisDf][$thisRep]);
                    $ratio = checkKeyOrFalse($metaData, $thisDf, $thisRep, 'ratio');
                    $thisConfPos = round($thisConfGetest * $ratio);

                    $confResultRow['kve1'] = checkKeyOrFalse($raceTracks, $thisDf, $thisRep, 0, $confRow['chainId'] - 1);
                    $confResultRow['kve2'] = checkKeyOrFalse($raceTracks, $thisDf, $thisRep, 1, $confRow['chainId'] - 1);
                    $confResultRow['kve3'] = checkKeyOrFalse($raceTracks, $thisDf, $thisRep, 2, $confRow['chainId'] - 1);
                    $confResultRow['kve4'] = checkKeyOrFalse($raceTracks, $thisDf, $thisRep, 3, $confRow['chainId'] - 1);
                    $confResultRow['kve5'] = checkKeyOrFalse($raceTracks, $thisDf, $thisRep, 4, $confRow['chainId'] - 1);

                    #$thisConfPosControl = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_poscontrol');
                    #$thisConfNegControl = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_negcontrol');
                    #$thisConfBlankControl = checkKeyOrFalse($confData, $confRow['chainId'], $confRow['mediaId'] . '_blankcontrol');

                    $thisConfPosControl = upa('confKeyStore', 'fetchValue', array($cm['ingezet'], $confRow['mediaId'], 'poscontrol'), False);
                    $thisConfNegControl = upa('confKeyStore', 'fetchValue', array($cm['ingezet'], $confRow['mediaId'], 'negcontrol'), False);
                    $thisConfBlankControl = upa('confKeyStore', 'fetchValue', array($cm['ingezet'], $confRow['mediaId'], 'blankcontrol'), False);

                    $confResultRow['n'] =  $thisConfGetest;
                    $confResultRow['n_pos'] = $thisConfPos;
                    $confResultRow['pos'] =  $thisConfPosControl;
                    $confResultRow['neg'] =  $thisConfNegControl;
                    $confResultRow['blank'] =  $thisConfBlankControl;


                    $confMediaTable .= generateHTML('audittrail/confmediarow', $cm);
                    $confResultTable .= generateHTML('audittrail/confResultRow', $confResultRow);
                  }
                  //extra closure
                  }



                  foreach($supportScript as $supportRow){
                      $status = checkKeyOrFalse($inuse, $thisDf, $thisRep, $supportRow['mediaId']);

                      if($status === True){
                        $thisConfMedia = upa('media', 'fetch' , array($supportRow['mediaId']), False);
                        $cm['volg'] =  $volg;
                        $cm['name'] =  $thisConfMedia['name'];

                        $cm['ingezet'] = 'NVT';
                        $cm['inzetby'] = 'NVT';
                        $cm['afgelezen'] = 'NVT';
                        $cm['afleesby'] = 'NVT';

                        $cm['tht'] = checkKeyOrFalse($bdf, '3', $supportRow['mediaId']);

                        $confMediaTable .= generateHTML('audittrail/confmediarow', $cm);
                      }
                  }

                  //break out of this loop if needed
                  if($globalConf == True){
                    break;
                  }
                }
              }

              $cn = array();
              $cn['volg'] = $volg;
              $cn['note'] = checkKeyOrFalse($confRows, 'note');

              if(!empty($cn['note'])){
                $noteTable .= generateHTML('audittrail/noterow', $cn);
              }

              $ar = array();
              $ar['volg'] = $volg;
              //$ar['revision'] = upa('revisions', 'analysisRevisionReport', array($sample['id'], $ana['id'], True));
              $ar['revision'] = upa('changeTracker', 'changesForAnalysis', array($sample['id'], $ana['id'], True));
              if($ar['revision'] != False){
                $revTable .= generateHTML('audittrail/assayrev', $ar);
              }

              //get results
              $assayResults = upa('results', 'getTestResultsByDf', array($ana['id'], True), False);
              $assayResultArr = array();
              $assayResultArr['volg'] = $volg;

              $dfI = 0;
              $ctValues = '';

              for($aI = 1; $aI >= 0.0000000001; $aI = $aI / 10 ){

                $resultKey = checkKeyOrFalse($assayResults, (string)$aI);

                if($resultKey != false){
                  $valuesForDf = '';

                  foreach($resultKey as $resultRep => $resultFields){
                      $dataJson = $resultFields['data'];
                      $dataForDf = json_decode($dataJson, JSON_FORCE_OBJECT);
                      $kve =  checkKeyOrFalse($dataForDf, 'kve');
                      $ct =  checkKeyOrFalse($dataForDf, 'Ctwaarde');

                      if($kve !== False){
                        $valuesForDf .= $kve . ',';
                      }
                      if($ct !== False){
                        $ctValues .= $ct;
                      }

                  }
                    $assayResultArr['res_' . $dfI] = rtrim($valuesForDf, ',');
                } else{
                    $assayResultArr['res_' . $dfI] = 'n.u.';
                }

                $dfI++;
              }

              $assayResultArr['ct_value'] = rtrim($ctValues, ',');
              $endResultForAssay = upa('results', 'endResultsAsJson', array($ana['id']), false);
              $endResultJson = json_decode($endResultForAssay, JSON_FORCE_OBJECT);
              $endResultKve = checkKeyOrFalse($endResultJson, 'output', 'kve');
              $assayResultArr['end_result'] = $endResultKve;
              $assayResultTable .= generateHTML('audittrail/assayResultRow', $assayResultArr);

            }

            //proj rev
            //$projectRevision = upa('revisions', 'projectRevisionReport', array($projectInfo['id'], True));
            //$sampleRevision = upa('revisions', 'sampleRevisionReport', array($sample['id'], True));

            $projectRevision = upa('changeTracker', 'changesForProject', array($projectInfo['id'], True));
            $sampleRevision = upa('changeTracker', 'changesForSample', array($sample['id'], True));



            $this->_template->set('analysis_rows', $analysisTable);
            $this->_template->set('assay_rows', $assayTable);
            $this->_template->set('media_rows', $mediaTable);
            $this->_template->set('material_rows', $materialTable);
            $this->_template->set('confirmation_rows', $confTable);
            $this->_template->set('confirmation_media', $confMediaTable);
            $this->_template->set('confirmation_notes', $noteTable);
            $this->_template->set('results_table', $assayResultTable);
            $this->_template->set('conf_result_table', $confResultTable);

            $this->_template->set('project_revisions', $projectRevision);
            $this->_template->set('sample_revisions', $sampleRevision);
            


            $this->_template->set('assay_revisions', $revTable);
        }else{
          $this->_template->set('borgHider', 'hidden');
          $this->_template->set('borgMsg', '');
        }
    }

    function runningConf(){

        $running = upa('sampleAnalysis', 'getRunningConf', array(), False);
        $runningSamples = array();

        foreach($running as $saRunning){
          if(!in_array($saRunning['sample'], $runningSamples)){
            array_push($runningSamples, $saRunning['sample']);
          }
        }

        foreach($runningSamples as $sample){
          $this->Sample->where('id', $sample);
          $this->Sample->insertOR();
        }

        $samples = $this->Sample->search();
        $sampleInfo = array();
        foreach($samples as $sample){
          $sampleInfo[$sample['id']] = $sample;
        }

        foreach($running as $i => $thisRunning){
          if(array_key_exists($thisRunning['sample'], $sampleInfo)){
            $thisSample = $sampleInfo[$thisRunning['sample']];
            $running[$i]['client'] = customerIdToName($thisSample['client']);
            $running[$i]['clientId'] = $thisSample['client'];
            $running[$i]['barcode'] = $thisSample['barcode'];
            $running[$i]['description'] = $thisSample['description'];
            $assay = upa('assays', 'fetchSingle', array($thisRunning['assay_base']), False);
            $running[$i]['assay_name'] = $assay['name'];
            $running[$i]['barcode_specific'] = $thisSample['barcode'] . '.' .  $thisRunning['follow_number'];

          }else {
            //TODO: log this
            unset($running[$i]);
          }
        }

        $tF = new tableFactory();
        $tF->loadTemplate('runningConfTable');
        $tF->loadValues($running);
        $this->_template->set('table', $tF->renderTable());
    }


    function listAllClientSamples($id){

        $table = '';

        $this->Sample->where('client', $id);
        $this->Sample->order('id', 'DESC');

        $result = $this->Sample->search();

        $projects = array();

        foreach($result as $sample){

            $repStack = array();
            $repStack['barcode'] = $sample['barcode'];
            $repStack['sample_id'] = $sample['id'];
            $repStack['project_id'] = $sample['project'];


            if(!array_key_exists($sample['project'], $projects)){
               $projectInfo = upa('projects', 'fetch', array($sample['project']), False);
            } else{
              $projectInfo = $projects[$sample['project']];
            }

            $repStack['project'] = $projectInfo['reference'];
            $repStack['description'] = $sample['description'];

            $scf = json_decode($sample['custom_fields'], JSON_FORCE_OBJECT);
            $repStack['details'] = checkKeyOrFalse($scf, 'details');

            if($sample['sample_innoculated'] == ''){
              $repStack['innoc'] = 'Nog niet ingezet';
            }else{
              $repStack['innoc'] = date('d-m-Y', $sample['sample_innoculated']);
            }


            $table .= generateHTML('samples/sampleAllListRow', $repStack);
        }

        $this->_template->set('table', $table);
    }

    function dataMiningGrab($projects){

      foreach($projects as $project){
        $this->Sample->where('project', $project['id']);
        $this->Sample->insertOR();
      }

      $results = $this->Sample->search();

      $retResults = array();

      foreach($results as $result){
        $retResults[$result['id']] = $result;
      }

      return $retResults;
    }

    function sampleNoteSummaryForProject($project){
      $this->Sample->where('project', $project);
      $samples = $this->Sample->search();

      $sampleNoteList = '';

      foreach($samples as $sample){
        $sampleNoteList .= '<p><strong>' . $sample['barcode'] . '</strong><br/>' . $sample['sample_note'] . '</p>';
      }

      $sampleNoteList .= '';
      return $sampleNoteList;

    }

    function sampleBackward($current){
        $this->render = False;
        $this->Sample->order('id', 'DESC');
        $this->Sample->lessThanHard('id', $current);
        $this->Sample->limit('1');
        $result = $this->Sample->search();

        if($result){
          $ret = array('prev_id' => $result[0]['id'], 'prev_barcode' => $result[0]['barcode'] );
        } else{
          $ret = array('prev_id' => False, 'prev_barcode' => False);
        }

        print(json_encode($ret));
    }

    function sampleForward($current){
        $this->render = False;
        $this->Sample->order('id', 'ASC');
        $this->Sample->greaterThanHard('id', $current);
        $this->Sample->limit('1');
        $result = $this->Sample->search();

        if($result){
          $ret = array('next_id' => $result[0]['id'], 'next_barcode' => $result[0]['barcode'] );
        } else{
          $ret = array('next_id' => False, 'next_barcode' => False);
        }

        print(json_encode($ret));
    }

    function sampleGetProjectFollowNo($id, $project){

          $this->render = False;
          #$this->Sample->where('id', $id);
          $this->Sample->where('project', $project);
          $this->Sample->order('id', 'ASC');
          $this->Sample->order('follow_no', 'ASC');
          $results = array();
          $pResults = $this->Sample->search();

          $followNo = 1;
          foreach($pResults as $idx => $sample){
            if($sample['id'] == $id ){
                $retObj = array();
                //check if this has a custom follow flag?
                $sampleExtra = json_decode($sample['sample_extra'], JSON_FORCE_OBJECT);
                $retObj['db_follow'] = $followNo;

                if( is_array($sampleExtra) && array_key_exists('follow', $sampleExtra)){
                  $retObj['custom_follow'] = $sampleExtra['follow'];
                } else{
                  $retObj['custom_follow'] = $followNo;
                }
                return $retObj;
            } else{
                unset($results[$idx]);
                $followNo++;
            }
          }
    }




}
