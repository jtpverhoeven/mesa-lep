<?PHP

class exportsController extends controller{

    private $_projectId;
    private $_projectInfo = array();
    private $_projectExtra = array();
    private $_clientInfo = array();
    private $_subclientInfo = array();
    private $_repStack = array();

    private $_nPerPage;
    private $_paginatedSamples = array();
    private $_foundSamples;
    private $_methods = array('sampling' => array(), 'assays' => array());
    private $_results = array();
    private $_hiddenResults = array();
    private $_rodacInd = 0;
    private $_paramSortList;

    private $_pdfHandle;
    private $_pdfContents;
    private $_templateName;
    private $_templateMaxCols = 5;
    private $_assayExclusionList = array();
    private $_highlightViolations = False;
    private $_barcodePrefix = 'MAZ-L';
    private $_reportIsEnglish = False;
    private $_reportIsPreview = False;
    private $_reportIsPreviewTemp = False;
    private $_prelimReport = False;
    private $_RVA = False;
    private $_storagePath = '/app/private/reportStorage/';

    private $_isZip = False;
    private $_transaction = null;
    private $_referenceSources = []; 

    private $_RODACTripValue = False; 
    private $_RODACReferenceSources;
    private $_RODACAssayName = '';

    private $_producedOutput = False; 
    private $_namingStrategy = null;    

    

    function dummy(){
     
    }

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }


    /**
     * Does this report produce a reportable output? 
     */
    private function reportHasReportableOutput()
    {

    }


    private function samplesHasReportableOutput()
    {

    }

    public function nooutput($projectId)
    {

        $projectFiles = upa('sampleFiles', 'visibleProjectFiles', array($projectId), False);    

        $this->_template->set('project_id', $projectId);
        $this->_template->set('show_project_warning_class', 'hidden');   

        if(count($projectFiles) > 0)
        {
            $this->_template->set('show_project_warning_class', '');
        }
        
    }    

    public function fileSender($projectId = False)
    {

        $proj = new Project();
        $project = $proj->find($projectId);

        if(!$project)
        {
            $this->reRoute('projects/viewCompleted', True);
        }

        $clientContactGroups = upa('contactGroups', 'generateGroupList', array($project['client']), False);                               

        
        $emailSettings = upa('emailTemplates', 'renderSelector', array('for_files'), False);
        $table = new tableFactory();
        $table->setTableId('sampleFileExportListing');
        $table->loadTemplate('sampleFileExportListing');

        $attachments = upa('sampleFiles', 'visibleProjectFiles', array($project['id']), False);
        $fileToolTipClasses = '';

        foreach($attachments as $idx => $attachment)
        {
            $attachments[$idx]['sent_label'] = 'Nog niet verstuurd';

            $previouslySentCount = upa('fileHistories', 'previouslySentCount', array($attachment['id']), False);                 

            if($previouslySentCount > 0)
            {
                $attachments[$idx]['sent_label'] = 'Verstuurd, ' . $previouslySentCount . ' keer';
                
                $attachments[$idx]['sent_count'] = $previouslySentCount;
            }


            
        }
                
          
        if(empty($attachments)){
            $fileToolTipClasses = 'hidden';
            $attachments = 'Geen zichtbare bestanden gevonden';
        }
  
        $table->loadValues($attachments);
        
        $this->_template->set('attachment_table', $table->renderTable());
        $this->_template->set('file_tool_tip', $fileToolTipClasses);

        

        $this->_template->set('client_id', $project['client']);
        $this->_template->set('project_id', $projectId);
        $this->_template->set('client_groups', $clientContactGroups);

        $this->_template->set('bcc', $emailSettings['selected_bcc']);
        $this->_template->set('email', $emailSettings['selected']);
        
        $this->_template->set('email_options', $emailSettings['options']);
        
        
    }

    public function search()
    {
        $eForm = new formFactory('researchProfiles');
        $eForm->setId('profileForm');
        $eForm->addClass('');
        $eForm->action('');
        $eForm->method('POST');
        $eForm->setTemplate('generic');
        $eForm->returnAsFieldArray();

        $eForm->addInputField('client_name', '{MESA_ERP_FORCLIENT}', 'text', 'ajax-typeahead input-block-level ', null, 'Start typing client name', array('autocomplete' => 'off'));
        $eForm->addInputField('client', False, 'hidden', 'hidden', False, null, '');

        $now = new DateTime();
        $today = $now->format('d-m-Y');

        $eForm->addInputField('date_from', False, 'text', 'input-block-level', '01-01-2018', 'Van', False, 'Vanaf');
        $eForm->addInputField('date_to', False, 'text', 'input-block-level', $today, 'Tot', False, 'Tot');

        $exOp['0'] = 'Nee';
        $exOp['1'] = 'Ja';
        
        $eForm->addDropdownField('hide_temp', 'Voorlopige certificaten verbergen', 'input-block-level', 1, $exOp, False);

        $reportOp['all'] = 'Alles';
        $reportOp['rodac'] = 'Enkel Rodac';
        $reportOp['legionella'] = 'Enkel Legionella';

        $eForm->addDropdownField('report_type', 'Project type', 'input-block-level', 'all', $reportOp, False);

        $formFields = $eForm->render();

        $this->_template->setByArray($formFields);

        //$this->_template->set('search_form', $eForm->render());

    }

    public function doSearch()
    {
        $this->render = False; 
        
        //$this->Export->where('client', $_POST['client']);
        //$this->Export->where('type', $_POST['type']);
        //$this->Export->greaterThan('date', $dateStart);
        //$this->Export->lessThan('date', $dateEnd);
        
        $dateStart = strtotime($_POST['date_start']);
        $dateEnd = strtotime($_POST['date_end']);

        $params = array();
        $params['client'] = $_POST['client'];
        #$params['exportType'] = $_POST['type'];
        $params['dateStart'] = $dateStart;
        $params['dateEnd'] = $dateEnd;        

        #AND `type` = :exportType AND
        $sql = "SELECT exports.*, projects.reference, projects.project_name, projects.auth_on, projects.special_type, clients.name AS client_name FROM `exports` 
                LEFT JOIN projects
                ON exports.project = projects.id
                LEFT JOIN clients
                ON exports.client = clients.id
                WHERE `exports`.`client` = :client AND                
                `date`  >= :dateStart AND
                `date` <= :dateEnd 
        ";

        if($_POST['hide_temp'] == '1')
        {
            $sql .= " AND `temporary` = '0' ";
        }

        if($_POST['report_type'] == 'legionella')
        {
            $sql .= " AND `special_type` = '1' ";
        }

        if($_POST['report_type'] == 'rodac')
        {
            $sql .= " AND `special_type` = '2' ";
        }

                
        $reports = $this->Export->customQuery($sql, $params);

        $template = '';

        foreach($reports as $report)
        {
            
            $report['temp_label'] = '';

            if($report['temporary'] == '1')
            {
                $report['temp_label'] = '<span class="label label-warning">Voorlopig analysecertificaat</span>';
            }

            $report['auth_date'] = date('d-m-Y', $report['auth_on']);
            $report['real_date'] = date('d-m-Y', $report['date']);
            $report['LB'] = ALPC_BASEPATH;
            $template .= generateHTML('export/reportExportLine', $report);
        }
                               
        print $template;
    }

    private function _templateAllowsTrip()
    {
        $notAllowed = ['mazOverview', 'mazOverviewEn'];

        if(in_array($this->_templateName, $notAllowed))
        {
            return False; 
        }

        return True; 
    }

    private function _templateForces1PerPage()
    {
        
        $need1pp = ['maz2020', 'maz2020En'];

        if(in_array($this->_templateName, $need1pp))
        {
            return true; 
        }

        return False; 

    }

    private function _chromeLog($msg){
     
    }

    public function loadReferenceSources()
    {
        $this->_referenceSources = upa('referenceSources', 'list', array(False, False ), False);
    }

    public function removeSavedProjects($projectId){
      $this->Export->where('project', $projectId);
      $results = $this->Export->search();

      if(!empty($results)){
        foreach($results as $result){
          $this->Export->deepFreed();
          $this->Export->id = $result['id'];
          $this->Export->remove();

          if($result['type'] == '1' || $result['type'] == '2'){
            $filename = ROOT . $this->_storagePath . $result['hash'] . '.pdf';
          }

          if($result['type'] == '3'){
            $filename = ROOT . $this->_storagePath . $result['hash'] . '.zip';
          }

          mesaUnlink($filename);

        }
      }
    }

    private function createHashWithFileName($hash, $type)
    {
        return ($type == '3') ? $hash . '.zip' : $hash . '.pdf';
    }

    /**
     * This is for the bulk-export function
     */
    public function downloadZipCollection()
    {

        $this->render = False; 
        $exportIds = json_decode($_POST['downloadIds'], JSON_FORCE_OBJECT);

        $zip = new ZipArchive();

        $ident = md5($_POST['downloadIds'] . time() );

        $filename =  ROOT . '/app/private/scratch/report_export_' . $ident . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$filename>\n");
        }

        
        $params = array();
        
        $ids =  implode(",", $exportIds);
        //$sql = "SELECT * FROM `exports` WHERE `id` IN ( " . $ids . ")";

        $sql = "SELECT exports.*, projects.reference, clients.name AS client_name FROM `exports` 
        LEFT JOIN projects
        ON exports.project = projects.id
        LEFT JOIN clients
        ON exports.client = clients.id
        WHERE `exports`.`id` IN ( " . $ids . ")";
        

        $results = $this->Export->customQuery($sql, $params);        

        $namesInUse = array();

        foreach($results as $result)
        {

            //check if this may be an old "zip" stored document
            $sfilename = ROOT . $this->_storagePath . $this->createHashWithFileName($result['hash'],  $result['type']);

            $zipFound = False; 

            //this is a stupid way to do this, but it will work for now. 
            if($result['type'] == '3'){

                if(file_exists($sfilename))
                {
                    $zipFound = True;
                }

            }
            
      
            if(!file_exists($sfilename))
            {
                
                //if not, it is probbably just a PDF, the check above is only to ensure    
                $sfilename = ROOT . $this->_storagePath . $this->createHashWithFileName($result['hash'],  '1');
                $result['type'] = 1; //force to PDF instead of zip.

            }
                        
            

            if(file_exists($sfilename))
            {

                $pdfInZipName = fileNameFilter($this->generateReportName($result, True));


                
                if($zipFound)
                {
                    $pdfInZipName = $pdfInZipName . '.zip';
                } 

                else
                {
                    $pdfInZipName = $pdfInZipName . '.pdf';
                }

  
                if(array_key_exists($pdfInZipName, $namesInUse))
                {
                    $fileAdd = '(' . $namesInUse[$pdfInZipName] . ')';
                    $namesInUse[$pdfInZipName] += 1;
                } 
                
                else
                {
                    $fileAdd = '';
                    $namesInUse[$pdfInZipName] = 1;
                }


                $thisSamplePdf = file_get_contents($sfilename);            
                $zip->addFromString(  $fileAdd . $pdfInZipName , $thisSamplePdf);
            }
                       
        }

        $zip->close();
        $downloadName = 'report-download';
                
        header("Content-Type: application/zip");
        header("Content-Length: " . filesize($filename));
        header("Content-Disposition: attachment; filename=\"" . $downloadName . '.zip' . "\"");
        readfile($filename);         
        unlink($filename);
    }

    public function downloadZip($transaction){
        
        $this->render = False;
        //$this->Export->where('transaction', $transaction);        

        $params = array();
        $params['transaction'] = $transaction;
        
        $sql = "SELECT exports.*, projects.reference, clients.name AS client_name FROM `exports` 
                LEFT JOIN projects
                ON exports.project = projects.id
                LEFT JOIN clients
                ON exports.client = clients.id
                WHERE `transaction` = :transaction
         ";
        
        $results = $this->Export->customQuery($sql, $params);
        
        $zip = false;
        $clientInfo = false; 
        $namesInUse = array();
        
        foreach($results as $result){
            

            if($zip === false){                

                $downloadName =  count($results) . ' rapportages uit MAZ-L ' . $result['reference'] . '.' . $result['revision'];                                

                $filename =  ROOT . '/app/private/scratch/' . guidv4() . '.zip';
                                
                $zip = new ZipArchive();
                if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
                    exit("cannot open <$filename>\n");
                }
            }
                        
            $pdfInZipName = $this->generateReportName($result, True);

            if(array_key_exists($pdfInZipName, $namesInUse)){
                $fileAdd = '(' . $namesInUse[$pdfInZipName] . ')';
                $namesInUse[$pdfInZipName] += 1;
            } else{
                $fileAdd = '';
                $namesInUse[$pdfInZipName] = 1;
            }
            
            $sfilename = ROOT . $this->_storagePath . $result['hash'] . '.pdf';
            $thisSamplePdf = file_get_contents($sfilename);            
            $zip->addFromString( $pdfInZipName . $fileAdd . ".pdf" , $thisSamplePdf);
        }
        
        $zip->close();
                
        header("Content-Type: application/zip");
        header("Content-Length: " . filesize($filename));
        header("Content-Disposition: attachment; filename=\"" . $downloadName . '.zip' . "\"");
        readfile($filename);         
    }

    public function downloadRevision($id){

      $this->render = False;

      $params['id'] = $id;
        
      $sql = "SELECT exports.*, projects.reference, clients.name AS client_name FROM `exports` 
              LEFT JOIN projects
              ON exports.project = projects.id
              LEFT JOIN clients
              ON exports.client = clients.id
              WHERE `exports`.`id` = :id
              LIMIT 1
       ";

      //$this->Export->where('id', $id);
      //$this->Export->limit(1);
      //$results = $this->Export->search();
      $results = $this->Export->customQuery($sql, $params);     

      if(empty($results)){
        return;
      } else{
        $result = $results[0];
        

        if(($result['type'] == '1' || $result['type'] == '2') || $result['transaction'] !== null) {

            $filename = ROOT . $this->_storagePath . $result['hash'] . '.pdf';
            $reportExists = file_exists($filename);

          
            if(!$reportExists)
            {
                print('Kon rapport PDF niet vinden in opslag.');
                die();
            }

          $filename = ROOT . $this->_storagePath . $result['hash'] . '.pdf';
          //$clientName = customerIdToName($result['client']);
          //$reportInfo = upa('projects', 'fetch', array($result['project']), False);
          //$trueReference = $reportInfo['reference'];          
          //$downloadName =  $clientName . ' - MAZ-L ' . implode('.', [$trueReference, $result['revision'], $result['print_version']]);        

          $downloadName = $this->generateReportName($result, True);
          header("Content-Type: application/pdf");
          header("Content-Length: " . filesize($filename));
          header("Content-Disposition: attachment; filename=\"" . fileNameFilter($downloadName) . '.pdf' . "\"");
          readfile($filename);
        }

        if($result['type'] == '3' && $result['transaction'] === null){
          $filename = ROOT . $this->_storagePath . $result['hash'] . '.zip';
          $clientName = customerIdToName($result['client']);
          $downloadName =  $clientName . ' - MAZ-L ' . implode('.', [$result['report_reference'], $result['revision'], $result['print_version']]);        
          header("Content-Type: application/zip");
          header("Content-Length: " . filesize($filename));
          header("Content-Disposition: attachment; filename=\"" . $downloadName . '.zip' . "\"");
          readfile($filename);
        }
      }
    }

    public function projectReportOverviewFull($client){
        $this->Export->where('client', $client);
        $results = $this->Export->search();

        if(empty($results)){
          $results = array();
        }else {
          foreach($results as $i => $result){
              if($result['type'] == '1'){
                $results[$i]['type'] = 'Rapport';
              }
              if($result['type'] == '2'){
                $results[$i]['type'] = 'Voorlopig rapport';
              }
              if($result['type'] == '3'){
                $results[$i]['type'] = 'Rapport per monster';
              }
          }
        }

        $tF = new tableFactory();
        $tF->loadTemplate('reportOverviewTable');
        $tF->specifyMod('date',  'date', array('d-m-Y', ALPC_TF_SELF));
        $tF->loadValues($results);
        return $tF->renderTable();
    }

    public function projectReportOverviewCondensed($project){
        
        $this->doNotRenderHeader = True;

        $projectInfo = upa('projects', 'fetch', array($project), False);
        
        $this->Export->where('project', $project);
        $this->Export->order('transaction', 'DESC');
        $results = $this->Export->search();
        $transactions = array_column($results, 'transaction');      
        $transactionUniq = array_unique($transactions);
        $transactionsOrder = array();

        foreach($results as $result){
            $transactionsOrder[$result['transaction']] = $result;
        }


        //<a href="#" onClick="downloadRevision('{id}');" class="btn btn-small btn-default"><i class="icon icon-download"></i></a>
        $table = '';

        if(empty($results)){
            $table = '<tr><td colspan="5">Geen rapportages</td></tr>';
        } 
         
        else
        {
            foreach($transactionUniq as $transaction){
                
                $thisTrans = $transactionsOrder[$transaction];

                if($thisTrans['type'] == '1'){
                   $type = 'Rapport';
                  }
                  if($thisTrans['type'] == '2'){
                    $type = 'Voorlopig rapport';
                  }
                  if($thisTrans['type'] == '3'){
                    $type = 'Rapport per monster';
                  }
                  
                $replacer  = array();
                $replacer['transaction'] =  $thisTrans['transaction'];
                $replacer['type'] = $type;
                $replacer['revision'] = $thisTrans['revision'];
                $replacer['date'] = date('d-m-Y', $thisTrans['date']);

                if($thisTrans['transaction'] === null){
                    $replacer['can_be_exported'] = 'hide';
                } else{                    
                    $replacer['can_be_exported'] = '';
                }    

                $replacer['content'] = $this->projectReportOverviewContent($results, $transaction, $projectInfo);
                $table .= generateHTML('export/versionline', $replacer);
            }
        }

        return $table;

    }

    private function projectReportOverviewContent($revisions, $transaction, $project){

        $content = '';

        foreach($revisions as $revision){
            if($revision['transaction'] === $transaction){
                $name = $project['reference'] . '.' . $revision['revision'] . '.' . $revision['print_version'];
                $content .= '<a href="#" onClick="downloadRevision(\'' . $revision['id'] . '\');">' . $name . '</a><br />';
            }
            
        }

        return $content;
    }


    public function projectReportOverview($project, $return = False){
      $this->doNotRenderHeader = True;

      $this->Export->where('project', $project);
      $this->Export->order('transaction', 'DESC');
      $results = $this->Export->search();

      $transactions = array_column($results, 'transaction');      
      $transactionCount = array_count_values($transactions);
      $transactionsSeen = [];

      if(empty($results)){
        $results = 'Geen rapportages gevonden';
      } else{
        foreach($results as $i => $result)
        {

            if($result['transaction'] === null){
                $results[$i]['exportable'] = $result['id'];
                $results[$i]['can_be_exported'] = 'hide';
            } else{

                $results[$i]['exportable'] = $result['transaction'];
                $results[$i]['can_be_exported'] = '';
            }

            if( $result['date'] != ''){
              $results[$i]['date'] = date('d-m-Y', $result['date']);
            }else{
              $results[$i]['date'] = '';
            }


            if($result['type'] == '1'){
              $results[$i]['type'] = 'Rapport';
            }

            if($result['type'] == '2'){
              $results[$i]['type'] = 'Voorlopig rapport';
            }

            if($result['type'] == '3'){
              $results[$i]['type'] = 'Rapport per monster';
            }

            //rowspan
            $results[$i]['rowspan'] = $transactionCount[$result['transaction']];
        }
      }


      $tF = new tableFactory();
      $tF->loadTemplate('exportTable');
      $tF->loadValues($results);

      $this->_template->set('table', $tF->renderTable());

      if($return === True){
        return $this->hardRender();
      }

    }

    private function _storeHardCopy($zip = False, $prelim = False, $toViewer = True, $sample = null){

        $hashInput = $this->_projectInfo['id'] . $this->_projectInfo['revision'] . $this->_projectInfo['print_version'];
        $hashInput = $hashInput . $this->_projectInfo['auth_status'];

        $hash = hash('sha256', $hashInput);

        //i think we are using this zip variable now also to indicate "report as 1 pdf" 
        if($zip == False)
        {

            if($prelim == False)
            {
                $this->Export->temporary = 0;
                $this->Export->type = 1;
            }

            if($prelim == true)
            {
                $this->Export->temporary = 1;
                $this->Export->type = 2;
            }

        } 
      
        else
        {
            $this->Export->type = 3;
        }

        $storagePath = ROOT . $this->_storagePath . $hash . '.pdf';
        $this->_pdfHandle->Output($storagePath, 'F');

        if($this->_transaction !== null){          
            $this->Export->transaction = $this->_transaction;
        }

        $this->Export->client = $this->_clientInfo['id'];
        $this->Export->revision = $this->_projectInfo['revision'];
        $this->Export->project = $this->_projectInfo['id'];
        $this->Export->print_version = $this->_projectInfo['print_version'];                        
        $this->Export->template =  $this->_templateName;
                
        $singleSample = False; 

        if(count($this->_foundSamples) === 1)
        {
            $sample = $this->_foundSamples[0];            

            $this->Export->sample_id = $sample['id']; 

            $singleSample = True; 
        }

        $sampleIdScope = array_column($this->_foundSamples, 'id');

        $this->Export->sample_id_scope = json_encode($sampleIdScope);

        $this->Export->naming_strategy = $this->determineNameStrategy($this->Export->type, $sample, $singleSample );        
                    
        $this->Export->report_reference =  $this->_projectInfo['project_name'];

        $this->Export->true_reference =  $this->_projectInfo['reference'];

        if($this->Export->naming_strategy === 'description')
        {            
            $this->Export->naming_description = stripPathForbidden($sample['client_description']);
        }

        else
        {
            $this->Export->naming_description = ''; 
        }
        
        $this->Export->hash = $hash;
        $this->Export->date = time();
        $this->Export->was_authorized = $this->_projectInfo['auth_status'];
        $this->Export->save();

        if($this->_transaction === null){
            $transactionId = $this->Export->lastInsertId;
            $this->Export->id = $transactionId;
            $this->Export->transaction = $transactionId;
            $this->_transaction = $transactionId;
            $this->Export->save();
        }

        $this->Export->deepFreed();

        if($toViewer === true)
        {            
            $this->reRoute('exports/extern/' . $this->_transaction, True);
        }      
    
    
    }

    function _listMethodsInProject($projectId){


        $foundSamples = upa('samples', 'fetchSamplesInProject', array($projectId), False);
        $assaysFound = array();

        foreach($foundSamples as $sample){
            $saForSample = upa('sampleAnalysis', 'fetchAnalysisArray', array($sample['id']));
            foreach($saForSample as $sa){
                $assayInfo = upa('assays', 'fetch', array($sa['assay_base']), False);
                if(!array_key_exists($assayInfo['id'],$assaysFound)){
                    $assaysFound[$assayInfo['id']] = $assayInfo;
                }
            }
        }
        return $assaysFound;
    }

    function tempProject($projectId){
        
        //export project
      //get some basic info first to display
      $projInfo = upa('projects', 'fetchProjectInfo', array($projectId));
    
      //set a lock
      
      $lockObject = upa('keyrings', 'requestLockAndStatus', array('EXPORT', $projectId), False);

      $this->_template->set('lock_id', $lockObject['lock_id']);
      
      if($lockObject['locked'] == True){
        $this->_template->set('hideLock', '');
        $this->_template->set('hideExport', 'hidden');
        $this->_template->set('locked_by_name', $lockObject['locked_by_name']);
        $this->_template->set('locked_by_avatar', $lockObject['locked_by_avatar']);
      } else{
        $this->_template->set('hideLock', 'hidden');
        $this->_template->set('hideExport', '');
      }



      $clientInfo = upa('clients', 'fetch', array($projInfo['client']), False);
      $client = $clientInfo['name'];
      

      $pdfForm = new formFactory('exports');
      $pdfForm->setId('pdfExportForm');
      $pdfForm->addClass('');
      $pdfForm->action('{LB}/exports/doExport');
      $pdfForm->method('POST');
      $pdfForm->target('pdfLoaderFrame');
      $pdfForm->setTemplate('target');
      $pdfForm->setName('pdfExportForm');

      $pdfForm->addInputField('projectId', False, 'hidden', 'hide', $projectId, False);

      $noArr['1'] = '1';
      $noArr['2'] = '2';
      $noArr['3'] = '3';
      $noArr['4'] = '4';
      $noArr['5'] = '5';

      $typeArr['projectReport'] = '{MESA_EXP_REPORTFULL}';
      //$typeArr['projectSample'] = '{MESA_EXP_REPORTSELECTION}';

      $pdfForm->addDropdownField('reportType', '{MESA_EXP_REPORTTYPE}', 'input-block-level', 'projectReport', $typeArr, False, False);
      $pdfForm->addDropdownField('noSamples', '{MESA_EXP_NOSAMPLESPAGE}', 'input-block-level', '5', $noArr, False, False);
     
      $sampleArr = upa('samples', 'fetchSamplesInProjectDropdown', array($projectId));

      $pdfForm->addDropdownField('exportSample[]', '{MESA_EXP_REPOTSELECTIONSELECT}', 'input-block-level',  False , $sampleArr, 'multiple', False);
      
      $ynArray['1'] = 'Ja';
      $ynArray['0'] = 'Nee';

      $pdfForm->addDropdownField('showViolation', 'Overschreden waardes in rood?', 'input-block-level', $clientInfo['trip_red'], $ynArray, False, False);

      //$pdfForm->addButton('previewPdf', 'icon-eye-open', '', 'btn-primary btn-mini', '{MESA_EXP_PREVIEW}', False);
      //$pdfForm->addButton('exportPdf', 'icon-file', '', 'btn-primary btn-mini', '{MESA_EXP_EXPORTBUTTON}', False);

      $pdfForm->addInputField('reportExportType', False, 'hidden', 'hide', '', False );
      $pdfForm->returnAsFieldArray();

      $pdfFormElements =  $pdfForm->render();

      //$this->_template->set('pdf_settings_form', $pdfForm->render());
      $sampleNoteSummary = upa('samples', 'sampleNoteSummaryForProject', array($projectId), false);
      $this->_template->set('sample_notes', $sampleNoteSummary);
      $this->_template->set('reportType', $pdfFormElements['reportType']);
      $this->_template->set('noSamples', $pdfFormElements['noSamples']);
      $this->_template->set('exportSample', $pdfFormElements['exportSample[]']);
      $this->_template->set('projectId', $pdfFormElements['projectId']);
      $this->_template->set('reportExportType', $pdfFormElements['reportExportType']);

      $this->_template->set('project_name', $projInfo['project_name']);
      $this->_template->set('project_id', $projectId);
      $this->_template->set('client', $client);

      $this->_template->set('report_notes_for_client', nl2br($clientInfo['report_notes']));        
      $this->_template->set('show_report_notes',  (empty($clientInfo['report_notes'])) ?  'hidden' :  '' );
      $this->_template->set('showViolation', $pdfFormElements['showViolation']);

      $assays = $this->_listMethodsInProject($projectId);


      $assayAssocs = array();
      $callArr = array();

      //get selectin of useable templates
      $i = 0;

      foreach($assays as $pAssay){
        $assayAssocs[$i] = upa('docgenAssociations', 'getAssayAssoc', array($pAssay['id']), False);
        $i++;
      }

      if($i < 2){
        if($i == 0){
          $assayIntersect = array();
        } elseif($i == 1){
          $assayIntersect = $assayAssocs[0];
        }
      } else{
        $assayIntersect = call_user_func_array('array_intersect', $assayAssocs);
      }


      $templatesAvailable = upa('docgenTemplates', 'returnOptions',  array($assayIntersect,  'nl', True), False);
      $this->_template->set('templates_available', $templatesAvailable);


      $paramForm = '';

      foreach($assays as $assayId => $assayInfo){
          $assayInfo['disabled'] ='disabled';
          $paramForm .= generateHTML('export/assayHideLine', $assayInfo);
      }

      $this->_template->set('paramList', $paramForm);
    }

    private function buildSortWidget($projectId){

      $list =  upa('sampleAnalysis', 'brewAnalysisOrderList', array($projectId), False);

      $li = '<ol class="sorterList list">';

      foreach($list as $idx=>$listItem){
        $li .= '<li data-name="assay" data-id="' . $listItem['id'] .'" style="padding-top: 4px; padding-bottom: 4px;"> <i class="icon-move"></i>' . $listItem['name'] . '</li>';
      }

      $li .= '</ol>';
      return $li;

    }

    function error($project){    
        $otherVersions = upa('exports', 'projectReportOverview', array($project, True), False);        
        $this->_template->set('id', $project);
        $this->_template->set('other_versions', $otherVersions);
    }


    function notready($project){    
        $otherVersions = upa('exports', 'projectReportOverview', array($project, True), False);        
        $this->_template->set('id', $project);
        $this->_template->set('other_versions', $otherVersions);
    }

    private function translateReportingPreferences($portalProjectId, $clientInfo, $projectType = 0)    
    {        

        $defaults = [
            'showViolation' => $clientInfo['trip_red'],
            'pdfTypeSelect' => 1,
            'language' => 'nl',
            'foundSettings' => '0',
            'activated_contact_groups' => [],
            'noSamples' => '5',
        ];

                
        $response = upa('portal', 'getReportingPreferenceForProject', array($portalProjectId ), False);
        
        $response = json_decode($response, JSON_FORCE_OBJECT);              

      
        
        if($response['status'] == '404' || $response['preferences']['foundSettings'] == False)
        {  

            //Inject default based on project type here? 
            
            //Normale monsters: individuele monsters 0 
            //Rodac: Verzamel rapport  2 
            //Legionella: Verzamel rapport  1
            //Karkas: Verzamel 

            switch ((int) $projectType ){

                case 0:
                    $defaults['pdfTypeSelect'] = 0;
                    break;
                case 1:
                    $defaults['pdfTypeSelect'] = 1;
                    break;
                case 2:
                    $defaults['pdfTypeSelect'] = 1;
                    break;
                default:
                    //we're doing thisf or karkassen, which have no special_tyep in the project table.
                    //but, this default will break if we ever create more project types that need to be handled differently.
                    $defaults['pdfTypeSelect'] = 1;
                    break;


            }


            return $defaults;
        }

        

        $portalpreferences = $response['preferences'];

        
        //we are moving this back up to the client leve in LIMS. 
        //$defaults['showViolation'] = ($portalpreferences['highlightErrors']) ? 1 : 0;


        $defaults['pdfTypeSelect'] = ($portalpreferences['reportOutputType'] == 'bulk') ? 1 : 0;
        $defaults['language'] = $portalpreferences['language'];
        $defaults['foundSettings'] = '1';

        $defaults['noSamples']  = ($portalpreferences['numberOfSamples'] == 0) ? '5' : $portalpreferences['numberOfSamples'];

        //$defaults['human_readable_type'] = ($defaults['pdfTypeSelect'] == 1) ? 'Rapport als 1 PDF' : '1 PDF per monster';
        $defaults['human_readable_type'] = ($defaults['pdfTypeSelect'] == 1) ? 'Verzamel rapport' : 'Individuele rapporten';
        $defaults['human_readable_violation'] = ($defaults['showViolation'] == 1) ? 'Ja' : 'Nee';
        $defaults['human_readable_language'] = ($defaults['language'] == 'en') ? 'Engels' : 'Nederlands';

        $defaults['activated_contact_groups'] = $portalpreferences['selectedContactLists'];
                
        return $defaults; 
        
    }

    function project($projectId, $conf = 'no')
    {
                    
        $projInfo = upa('projects', 'fetchProjectInfo', array($projectId), False);                           

        if($projInfo['is_ready'] != '1' && $conf === 'no'){
            $this->reRoute('exports/notready/' . $projectId, true);
            return;
        }

        //set a lock
        $lockObject = upa('keyrings', 'requestLockAndStatus', array('EXPORT', $projectId), False);

        
        $this->_template->set('lock_id', $lockObject['lock_id']);

        if($lockObject['locked'] == True){
          $this->_template->set('hideLock', '');
          $this->_template->set('hideExport', 'hidden');
          $this->_template->set('locked_by_name', $lockObject['locked_by_name']);
          $this->_template->set('locked_by_avatar', $lockObject['locked_by_avatar']);
        } else{
          $this->_template->set('hideLock', 'hidden');
          $this->_template->set('hideExport', '');
          $this->_template->set('locked_by_name', null);
          $this->_template->set('locked_by_avatar', '{LB}/public/img/alpaca.png');
        }
        
      
        //export project
        //get some basic info first to display
        
        $clientInfo = upa('clients', 'fetch', array($projInfo['client']), False);

        $preferences = $this->translateReportingPreferences($projInfo['portal_id'], $clientInfo, $projInfo['special_type']);
        
       
        $client = $clientInfo['name'];

        $pdfForm = new formFactory('exports');
        $pdfForm->setId('pdfExportForm');
        $pdfForm->addClass('');
        $pdfForm->action('{LB}/exports/doExport');
        $pdfForm->method('POST');
        $pdfForm->target('pdfLoaderFrame');
        $pdfForm->setTemplate('target');
        $pdfForm->setName('pdfExportForm');

        $pdfForm->addInputField('projectId', False, 'hidden', 'hide', $projectId, False);

        $noArr['1'] = '1';
        $noArr['2'] = '2';
        $noArr['3'] = '3';
        $noArr['4'] = '4';
        $noArr['5'] = '5';

        $typeArr['projectReport'] = '{MESA_EXP_REPORTFULL}';
        $typeArr['projectSample'] = '{MESA_EXP_REPORTSELECTION}';

        $pdfOutputTypeArray = array();
        $pdfOutputTypeArray['1'] = 'Verzamel rapport'; //Rapport als 1 PDF
        $pdfOutputTypeArray['0'] = 'Individuele rapporten'; //1 PDF per monster

        $pdfForm->addDropdownField('PDFtypeSelect', 'PDF uitvoer', 'input-block-level', $preferences['pdfTypeSelect'], $pdfOutputTypeArray, False, False);

        $pdfForm->addDropdownField('reportType', '{MESA_EXP_REPORTTYPE}', 'input-block-level', 'projectReport', $typeArr, False, False);
        
        $pdfForm->addDropdownField('noSamples', '{MESA_EXP_NOSAMPLESPAGE}', 'input-block-level',  $preferences['noSamples'], $noArr, False, False);

        $ynArray['1'] = 'Ja';
        $ynArray['0'] = 'Nee';

        //$clientInfo['trip_red']
        $pdfForm->addDropdownField('showViolation', 'Overschreden waardes in rood?', 'input-block-level', $clientInfo['trip_red'], $ynArray, False, False);

        $langArray = array();
        $langArray['nl'] = 'Nederlands';
        $langArray['en'] = 'Engels';
        $pdfForm->addDropdownField('languageSelect', 'Taal', 'input-block-level', $preferences['language'], $langArray, False, False);

        $sampleArr = upa('samples', 'fetchSamplesInProjectDropdown', array($projectId));

        $pdfForm->addDropdownField('exportSample[]', '{MESA_EXP_REPOTSELECTIONSELECT}', 'input-block-level',  False , $sampleArr, 'multiple', False);

        $pdfForm->addInputField('reportExportType', False, 'hidden', 'hide', '', False );
        $pdfForm->returnAsFieldArray();

        $pdfFormElements =  $pdfForm->render();

        $sampleNoteSummary = upa('samples', 'sampleNoteSummaryForProject', array($projectId), false);
        
        $rodacAlert = 'hidden';
        $noPreferenceAlert = 'hidden';
        $preferencesFoundAlert = 'hidden';

        if($projInfo['special_type'] == '2')
        {
            $rodacAlert = '';
        }
   

        if($preferences['foundSettings'] == 0)
        {
            $noPreferenceAlert = '';
        }

        else{
            $preferencesFoundAlert = '';
        }

        

        $this->_template->set('show_preferences_loaded', $preferencesFoundAlert);
        $this->_template->set('show_no_preference_alert', $noPreferenceAlert );
        $this->_template->set('show_rodac_alert', $rodacAlert );
        $this->_template->set('sample_notes', $sampleNoteSummary);
        $this->_template->set('reportType', $pdfFormElements['reportType']);
        $this->_template->set('noSamples', $pdfFormElements['noSamples']);
        $this->_template->set('exportSample', $pdfFormElements['exportSample[]']);
        $this->_template->set('projectId', $pdfFormElements['projectId']);
        $this->_template->set('reportExportType', $pdfFormElements['reportExportType']);
        $this->_template->set('pdfTypeSelect', $pdfFormElements['PDFtypeSelect']);

        $this->_template->set('showViolation', $pdfFormElements['showViolation']);
        $this->_template->set('languageSelect', $pdfFormElements['languageSelect']);
        $this->_template->set('report_notes_for_client', nl2br($clientInfo['report_notes']));        
        $this->_template->set('show_report_notes',  (empty($clientInfo['report_notes'])) ?  'hidden' :  '' );

        $this->_template->set('project_name', $projInfo['project_name']);
        $this->_template->set('project_id', $projectId);
        $this->_template->set('client', $client);
        $this->_template->set('client_id', $projInfo['client']);

        $assays = $this->_listMethodsInProject($projectId);

        $assayAssocs = array();
        $callArr = array();

        //get selectin of useable templates
        $i = 0;

        foreach($assays as $pAssay){
          $assayAssocs[$i] = upa('docgenAssociations', 'getAssayAssoc', array($pAssay['id']), False);
          $i++;
        }

        if($i < 2){
          if($i == 0){
            $assayIntersect = array();
          } elseif($i == 1){
            $assayIntersect = $assayAssocs[0];
          }
        } else{
          $assayIntersect = call_user_func_array('array_intersect', $assayAssocs);
        }

        


        $templatesAvailable = upa('docgenTemplates', 'returnOptionsCheckboxes', array($assayIntersect, $preferences, False, $projInfo['special_type']), False);



      
        $this->_template->set('templates_available', $templatesAvailable);
        $this->_template->set('preference_language', $preferences['language']);

        $paramForm = '';

        $allowedToEditParams = upa('groupPrivileges', 'checkAllowed', array('exports', 'setAssays'), False);
        foreach($assays as $assayId => $assayInfo){

            $assayInfo['disabled'] ='';
            if($allowedToEditParams == False){
              $assayInfo['disabled'] ='disabled';
              $paramForm .= generateHTML('export/assayHiddenLine', $assayInfo);
            }

            $paramForm .= generateHTML('export/assayHideLine', $assayInfo);
        }

        $this->_template->set('paramList', $paramForm);
        $this->_template->set('param_sorter', $this->buildSortWidget($projectId));
        
        $otherVersions = upa('exports', 'projectReportOverviewCondensed', array($projectId, True), False);        
        $this->_template->set('other_versions', $otherVersions);

        if($preferences['foundSettings'] == '0'){
            $this->_template->set('show_report_pref_client', 'hidden');
            $this->_template->set('show_no_pref_found', '');
        }

        if($preferences['foundSettings'] == '1'){
            $this->_template->set('show_report_pref_client', '');
            $this->_template->set('show_no_pref_found', 'hidden');

            $this->_template->set('human_readable_type', $preferences['human_readable_type']);
            $this->_template->set('human_readable_language', $preferences['human_readable_language']);
            $this->_template->set('human_readable_violation', $preferences['human_readable_violation']);
        }
        
    }

    function doExport()
    {        

        $this->render = False;
        $this->loadReferenceSources();
        
        $this->_projectId = $_POST['projectId'];
        $this->_templateName = $_POST['templateSelect'];
        $this->_nPerPage = $_POST['noSamples'];
        $reportExportType = $_POST['reportExportType'];
        $raw = False; 

        $sorterArray = array();
        
        if(isset($_POST['paramSortString'])){
          $sorterArray = json_decode($_POST['paramSortString'], JSON_FORCE_OBJECT);
        }

        $paramSortList = array();
        $i = 0;
        foreach($sorterArray[0] as $param){
          $paramSortList[$param['id']] = $i;
          $i++;
        }

        $this->_paramSortList = $paramSortList;
 
        //fetch params which should be hidden
        foreach($_POST as $pKey => $pValue){
            $query = 'hide_param_';
            if(substr($pKey, 0, strlen($query)) === $query){
                $this->_assayExclusionList[$pKey] = True;
            }
        }

        if($reportExportType != 'preview') {
            upa('projects', 'setExportFlag', array($this->_projectId), False);
            $event = 'Project uitgevoerd';
            upa('changeTracker', 'changed', array(10,$this->_projectId, False, False, $event, False, False), False);
        }

        $engIdent = substr($this->_templateName, -2);

        if($engIdent == 'En' ){
            $this->_reportIsEnglish = True;
        }

        if($_POST['showViolation'] == '1'){
            $this->_highlightViolations = True;
        }

        $this->_loadProjectInfo();
                

        if($this->_templateName == 'legionella' || $this->_templateName == 'legionellaEn' ){
            $special = "legionella";
        } elseif($this->_templateName == 'legionella2019' || $this->_templateName == 'legionella2019En' || $this->_templateName == 'legionella2020' || $this->_templateName == 'legionella2020En' ){            
            $special = "legionella2019";
        }               
        elseif($this->_templateName == 'rodac' || $this->_templateName == 'rodacEn' ||  $this->_templateName == 'rodac2020' ||  $this->_templateName == 'rodac2020En'){
            $special = "rodac";
        }elseif($this->_templateName == 'karkas' || $this->_templateName == 'karkasEn' || $this->_templateName == 'karkas2020' || $this->_templateName == 'karkas2020En'){
            $special = "karkas";
        }
        else{
            $special = False;
        }

        if($this->_templateForces1PerPage())
        {
            $this->_nPerPage  = 1;
        }



        if($_POST['reportType'] == 'projectReport'){
        
            if($_POST['PDFtypeSelect'] == '0')
            {                
                $this->_chunkReport($special, False, ($reportExportType == 'preview'));
                return;
            } 
            
            else
            {                

                if($reportExportType != 'preview') 
                {                                                  
                    $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
                    $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];
                }

                elseif ($reportExportType == 'preview')
                {
                    $this->_reportIsPreview = True;
                    $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
                    $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];
                }

                if($this->_templateName == 'legionella' || $this->_templateName == 'legionellaEn' ){
                    $this->_fullReport('legionella');
                }
                elseif($this->_templateName == 'legionella2019' || $this->_templateName == 'legionella2019En' || $this->_templateName == 'legionella2020' || $this->_templateName == 'legionella2020En'){
                    
                    $this->_fullReport('legionella2019');
                }                
                elseif($this->_templateName == 'rodac' || $this->_templateName == 'rodacEn' ||  $this->_templateName == 'rodac2020' ||  $this->_templateName == 'rodac2020En'){
                    $this->_fullReport('rodac');
                }elseif($this->_templateName == 'karkas' || $this->_templateName == 'karkasEn' || $this->_templateName === 'karkas2020' || $this->_templateName === 'karkas2020En'){
                    $this->_fullReport('karkas');
                }
                else{
                    $this->_fullReport();
                }


                if($reportExportType != 'preview')
                {
                    if($this->_producedOutput  === True)
                    {
                     
                        upa('projects', 'upProjectPrintVersion', array($this->_projectId), False);
                    }
                
                }

            }
        }

        if($_POST['reportType'] == 'projectSample'){

            if($_POST['PDFtypeSelect'] == '0')
            {                
                $this->_chunkReport($special, $_POST['exportSample'], ($reportExportType == 'preview'));
                return;
            } 
            
            else
            {

                if($reportExportType != 'preview')
                {                                    
                    $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
                    $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];
                } elseif ($reportExportType == 'preview'){
                    $this->_reportIsPreview = True;
                    $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
                    $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];
                }                

                $this->_selectionReport($_POST['exportSample'], $special);

                
                if($reportExportType != 'preview')
                {
                    if($this->_producedOutput  === True)
                    {
                     
                        upa('projects', 'upProjectPrintVersion', array($this->_projectId), False);
                    }
                
                }
            }
        }


        if($this->_producedOutput  === False)
        {
            $this->reRoute('exports/nooutput/' . $this->_projectId, True); 
            return; 
        }


        if($reportExportType == 'preview'){

            if($raw == True)
            {
                $str = $this->_pdfHandle->Output('report_naam', 'S');
                print('<pre>');
                print($str);
                print('</pre>');
            }

            else
            { 
                $this->_pdfHandle->Output('report_naam', 'I');                
            }
            


        } elseif($_POST['PDFtypeSelect'] == '0'){

        } else{
            
            $pdfName = $this->_clientInfo['name'] . ' - MAZ-L ' . $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'] . '.pdf';
            $this->_storeHardCopy();                        
        }
    }



    function doTempExport(){


        $this->render = False;
        $this->_projectId = $_POST['projectId'];
        $this->loadReferenceSources();

        //1 set to authorised
        //2 run export
        //3 deauthorize
        //wrap this in a try except thing
        $this->_prelimReport = True;


        $this->_templateName = $_POST['templateSelect'];
        $this->_nPerPage = $_POST['noSamples'];
        $reportExportType = $_POST['reportExportType'];       

        //fetch params which should be hidden
        foreach($_POST as $pKey => $pValue){
             $query = 'hide_param_';
             if(substr($pKey, 0, strlen($query)) === $query){
                 $this->_assayExclusionList[$pKey] = True;
             }
         }

        $engIdent = substr($this->_templateName, -2);

        if($engIdent == 'En' ){
            $this->_reportIsEnglish = True;
        }

        if($_POST['showViolation'] == '1'){
            $this->_highlightViolations = True;
        }

        $this->_loadProjectInfo();

        if($this->_templateName == 'legionella' || $this->_templateName == 'legionellaEn' ){
            $special = "legionella";
        }
        elseif($this->_templateName == 'legionella2019' || $this->_templateName == 'legionella2019En' || $this->_templateName == 'legionella2020' || $this->_templateName == 'legionella2020En'){
            $special = "legionella2019";
        }
    
        elseif($this->_templateName == 'rodac' || $this->_templateName == 'rodacEn' || $this->_templateName == 'rodac2020' || $this->_templateName == 'rodac2020En' ){
            $special = "rodac";
        }elseif($this->_templateName == 'karkas' || $this->_templateName == 'karkasEn' || $this->_templateName == 'karkas2020'  || $this->_templateName == 'karkas2020En'){
            $special = "karkas";
        }
        else{
            $special = False;
        }


        if($_POST['reportType'] == 'projectReport'){
            if($_POST['PDFtypeSelect'] == '0'){
                $this->_chunkReport($special, False, ($reportExportType == 'preview'));
            } else{
                
                if($reportExportType != 'preview') {
                    //authorize
                    //upa('projects', 'authoriseProject', array($this->_projectId), False);
                    //upa('projects', 'upProjectPrintVersion', array($this->_projectId), False);
                    $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
                    $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];
                }

                elseif ($reportExportType == 'preview'){
                    $this->_reportIsPreview = True;
                    $this->_reportIsPreviewTemp = True;
                    $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
                    $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];
                }

                if($this->_templateName == 'legionella' || $this->_templateName == 'legionellaEn' ){
                    $this->_fullReport('legionella');
                }
                elseif($this->_templateName == 'legionella2019' || $this->_templateName == 'legionella2019En'  || $this->_templateName == 'legionella2020'  || $this->_templateName == 'legionella2020En'){
                    $this->_fullReport('legionella2019');
                }
                elseif($this->_templateName == 'rodac' || $this->_templateName == 'rodacEn' ||  $this->_templateName == 'rodac2020' ||  $this->_templateName == 'rodac2020En'){
                    $this->_fullReport('rodac');
                }elseif($this->_templateName == 'karkas' || $this->_templateName == 'karkasEn' || $this->_templateName == 'karkas2020' || $this->_templateName == 'karkas2020En' ){
                    $this->_fullReport('karkas');
                }
                else{
                    $this->_fullReport();
                }

                if($reportExportType != 'preview')
                {
                    if($this->_producedOutput  === True)
                    {
                     
                        upa('projects', 'upProjectPrintVersion', array($this->_projectId), False);
                    }
                
                }
            }
        }


        if($reportExportType == 'preview'){
            $this->_pdfHandle->Output('report_naam', 'I');
        } elseif($_POST['PDFtypeSelect'] == '0'){

        } else{
            $pdfName = $this->_clientInfo['name'] . ' - MAZ-L ' . $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'] . '.pdf';            
            upa('projects', 'deauthoriseProject', array($this->_projectId, True), False);
            $this->_storeHardCopy(False, True);          
        }
    }


    private function _chunkReport($special, $selection = False, $preview = False){

        $namesInUse = array();
        $this->_isZip = True;
        $hasHadOverallOutput = False; 

        if($selection == False){
            $foundSamples = upa('samples', 'fetchSamplesInProject', array($this->_projectId), False);
        } else{
            $foundSamples = upa('samples', 'fetchSpecificSamplesInProject', array($this->_projectId, $selection), False);
        }

        $this->_foundSamples = $foundSamples;

        // For preview mode: start one PDF and accumulate all samples into it
        if($preview === True){
            $this->_reportIsPreview = True;
            $this->_projectInfo['print_version'] = $this->_projectInfo['print_version'] + 1;
            $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];

            if($special == 'legionella'){
                $this->_startPDF(True);
                $this->_nPerPage = 10;
            } elseif($special == 'rodac'){
                $this->_startPDF();
                $this->_nPerPage = 10;
            } else{
                $this->_startPDF();
            }

            $this->_loadStyle();
            $this->_loadHeaderFooter();

            $combinedHtml = '';
            $sampleIndex = 0;

            foreach($foundSamples as $sample){

                $this->_loadPaginateSamples(array($sample['id']), $special);

                if($this->_producedOutput === True){

                    // Insert "End of Report" separator page between samples
                    if($sampleIndex > 0){
                        $endOfReportLabel = ($this->_reportIsEnglish) ? 'End of Report' : 'Einde rapport';
                        $combinedHtml .= '<pagebreak />';
                        $combinedHtml .= '<div style="text-align: center; padding-top: 250px;"><h1>' . $endOfReportLabel . '</h1></div>';
                        $combinedHtml .= '<pagebreak />';
                    }

                    $hasHadOverallOutput = True;
                    $sampleIndex++;

                    $this->_quantifyMethods();
                    $this->_createReportContents($special);

                    $combinedHtml .= $this->_pdfContents;

                    $this->_pdfContents = '';
                    $this->_paginatedSamples = array();
                    $this->_methods = array('sampling' => array(), 'assays' => array());
                    $this->_results = array();
                }

                $this->_producedOutput = False;
            }

            if($hasHadOverallOutput === False){
                $this->reRoute('exports/nooutput/' . $this->_projectId, True);
                return;
            }

            $this->_producedOutput = True;
            $this->_pdfHandle->WriteHTML($combinedHtml);
            $this->_pdfHandle->Output('report_naam', 'I');
            return;
        }

        // Normal (non-preview) chunk mode: generate separate PDFs per sample
        foreach($foundSamples as $sample){

            if($special == 'legionella'){
                $this->_startPDF(True); //start in landscape mode
                $this->_nPerPage = 10; //override samples per page settings for legionella
            } elseif($special == 'rodac'){
                $this->_startPDF();
                $this->_nPerPage = 10; //override samples per page settings for legionella
            } else{
                $this->_startPDF();
            }            

            $this->_loadProjectInfo();  //reload info
            
            //need to manually set this so there is no mismatch between print number and printed reference
            $this->_projectInfo['print_version'] =  $this->_projectInfo['print_version'] + 1;   //reflect the up
            $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];


            $this->_loadStyle();
            $this->_loadHeaderFooter();
            $this->_loadPaginateSamples(array($sample['id']), $special);

            if($this->_producedOutput === True)
            {

                //this is to signal that one of he PDF's had results. 
                $hasHadOverallOutput = True; 

                upa('projects', 'upProjectPrintVersion', array($this->_projectId), False);

                $this->_quantifyMethods();

                //create report body
                $this->_createReportContents($special);
    
                //output document
                $this->_pdfHandle->WriteHTML($this->_pdfContents);
                $this->_storeHardCopy(True, False, False, $sample);
      
                $this->_pdfContents = '';
                $this->_paginatedSamples = array();
                $this->_methods = array('sampling' => array(), 'assays' => array());
                $this->_results = array();            


            }
            
            
            //reset, because the next sample is going to be a different report again. 
            $this->_producedOutput = False; 
            
            $sample = False;
        }

        //if($this->_producedOutput  === False)
        if($hasHadOverallOutput === False)
        {         
            $this->reRoute('exports/nooutput/' . $this->_projectId, True); 
        }

        else
        {   $this->_producedOutput = True;            
            $this->reRoute('exports/extern/' . $this->_transaction, True);
        }

        
        
   
    }


    private function _fullReport($special = false){

        
        //start document
        if($special == 'legionella'){
            $this->_startPDF(True); //start in landscape mode
            $this->_nPerPage = 10; //override samples per page settings for legionella
        } elseif($special == 'rodac'){
            $this->_startPDF();
            $this->_nPerPage = 10; //override samples per page settings for rodac
        } else{
            $this->_startPDF();
        }

        $this->_loadStyle();
        $this->_loadHeaderFooter();

        //get samples
        $this->_loadPaginateSamples(False, $special);
        $this->_quantifyMethods();

        //create report body
        $this->_createReportContents($special);

        //output document
        $this->_pdfHandle->WriteHTML($this->_pdfContents);
        

    }


    private function _selectionReport($selection, $special = False){

        $this->_startPDF();
        $this->_loadStyle();
        $this->_loadHeaderFooter();

        $this->_loadPaginateSamples($selection, $special);
        $this->_quantifyMethods();

        //create report body
        $this->_createReportContents($special);

        //output document
        $this->_pdfHandle->WriteHTML($this->_pdfContents);

    }


    //======== PDF FUNCTIONS


    private function _loadRequestedAssays(){
    }

    private function _quantifyMethods(){

            $methodId = 1;

            foreach($this->_methods['sampling'] as $id => $sm){
                if($sm['hide'] == 1){
                    $this->_methods['sampling'][$id]['method_follow_id'] = NULL;
                } else{
                    $this->_methods['sampling'][$id]['method_follow_id'] = $methodId;
                    $methodId++;
                }
            }

            foreach($this->_methods['assays'] as $id => $am){
                $this->_methods['assays'][$id]['method_follow_id'] = $methodId;
                $methodId++;
            }
    }

    private function _loadPaginateSamples($foundSamples = False, $special = False){

        if($foundSamples == False){
            $foundSamples = upa('samples', 'fetchSamplesInProject', array($this->_projectId), False);
        }  else{
            $foundSamples = upa('samples', 'fetchSpecificSamplesInProject', array($this->_projectId, $foundSamples), False);
        }

        $this->_foundSamples = $foundSamples;

        $samplesOnThisPage = 0;
        $currentPage = 1;
        $sampleFollowNumber = 1;

        foreach($foundSamples as $sample){

            if($samplesOnThisPage >= $this->_nPerPage){
                $samplesOnThisPage = 0;
                $currentPage++;
            }

            $producedOutput = $this->_loadSampleResults($sample['id']);

            if($producedOutput === False)
            {
                continue; 
            }

            else
            {
                $this->_producedOutput = True;
            }

            //add report follow number
            $sample['int_follow'] = $sampleFollowNumber;
            $sample['barcode_full'] = $this->_barcodePrefix . $sample['barcode'];
            $sampleFollowNumber++;

            //unravel the custom fields
            $customFields = json_decode($sample['custom_fields'], True);
            if(is_array($customFields)){
                foreach($customFields as $cfk => $cfv){
                    //$sample['samplecustom_' . $cfk] = $cfv;
                    $sample['samplecustom_' . $cfk] = nl2br($cfv);
                }
            }

            //add to the sample list
            $this->_paginatedSamples[$currentPage][$samplesOnThisPage + 1] = $sample;

            //load in sampling method for this sample
            if($special != 'rodac'){
                if(!array_key_exists($sample['sampling_method'], $this->_methods['sampling'])){
                    $samplingMethod = $this->_loadSampleMethod($sample['sampling_method']);
                    if($samplingMethod != False){
                        $this->_methods['sampling'][$sample['sampling_method']] = $samplingMethod;
                    }
                }
            }


            //load sample assays and results
           
            
            // print($sample['id']);
            // print('---');
            // print($producedOutput);
            // print('---');

           

            $samplesOnThisPage++;
        }

        //roadc, need to load project wide sampling strategy
        if($special == 'rodac'){
            if(array_key_exists('sample_method', $this->_projectExtra)){
                $samplingMethod = $this->_loadSampleMethod($this->_projectExtra['sample_method']);
                $this->_methods['sampling'][$this->_projectExtra['sample_method']] = $samplingMethod;
            }
        }



        $this->_sortAssayArray();
    }

    private function _loadSampleResults($sid){

        //$this->_producedOutput = False; 
        $sampleProducedOutput = False; 

        $attAna = upa('sampleAnalysis', 'fetchAnalysisArray', array($sid));
        

        foreach($attAna as $analysis){

            //disable for preliminary reports
            if(!array_key_exists('hide_param_' . $analysis['assay_base'], $this->_assayExclusionList) && $this->_prelimReport == False){
                continue;
            }

            //load in method if not loaded in already for general reference
            if(!array_key_exists($analysis['assay_base'], $this->_methods['assays'])){
                $analysisMethod = $this->_loadAssayMethod($analysis['assay_base']);
                if($analysisMethod['hide_report'] != 1){
                    $sortPosition = checkKeyOrFalse($this->_paramSortList, $analysis['assay_base']);
                    $analysisMethod['sortPosition'] = $sortPosition;
                    $this->_methods['assays'][$analysis['assay_base']] = $analysisMethod;                   
                }
                else
                {
                    continue;
                }
            }

       
            $sampleProducedOutput = True; 

            //load in results of this analysis into $this->_results
            if($analysis['roaming_id'] != 0 && $analysis['roaming_id'] != NULL ){
                $resultForProfile = upa('roamingAnalysis', 'fetchSettings', array($analysis['roaming_id']));
            } else {
                $resultForProfile = upa('assayProfiles', 'fetchProfileById', array($analysis['assay']));
            }

            $thisSaResults =  upa('results', 'msProcess', array( $analysis['id']));

            $references = json_decode($resultForProfile[0]['reference'], True);
            $reportIn = $thisSaResults['reportIn'];

            $hiddenResults = checkKeyOrFalse($thisSaResults, 'hidden');
            $this->_hiddenResults[$sid][$analysis['assay_base']] = $hiddenResults;

            //this is rather hackey.. should change templates etc.
            //if($this->_templateName == 'mazEn' && array_key_exists('outputEn', $thisSaResults) && array_key_exists($reportIn, $thisSaResults['outputEn'])){
            if($this->_reportIsEnglish == True && array_key_exists('outputEn', $thisSaResults) && array_key_exists($reportIn, $thisSaResults['outputEn'])){                
                $this->_results[$sid][$analysis['assay_base']]['kve'] = $thisSaResults['outputEn'][$reportIn];
            } elseif($this->_templateName == 'rodac' || $this->_templateName == 'rodacEn' ||  $this->_templateName == 'rodac2020' ||  $this->_templateName == 'rodac2020En'){
                $this->_results[$sid][$analysis['assay_base']]['kve'] = $thisSaResults['output'][$reportIn];
                #$this->_results[$sid][$analysis['assay_base']]['average'] = $thisSaResults['output']['project gemiddelde/1cm'];
                $this->_results[$sid][$analysis['assay_base']]['average'] =   $thisSaResults['output']['project gemiddelde/1cm'];
            }
            else{
                $this->_results[$sid][$analysis['assay_base']]['kve'] = $thisSaResults['output'][$reportIn];
            }

            //even more hacks
            if(array_key_exists('addendum', $thisSaResults['output'])){
                $this->_results[$sid][$analysis['assay_base']]['addendum'] = $thisSaResults['output']['addendum'];
            } else{
                $this->_results[$sid][$analysis['assay_base']]['addendum'] = '';
            }

            //more hacks
            if($this->_templateName == 'legionella' || $this->_templateName == 'legionellaEn' || $this->_templateName == 'legionella2019' || $this->_templateName == 'legionella2019En' || $this->_templateName == 'legionella2020' || $this->_templateName == 'legionella2020En'){
                $this->_results[$sid][$analysis['assay_base']]['addendum'] = ($this->_reportIsEnglish == True) ?  $thisSaResults['outputEn']['aanvulling'] :  $thisSaResults['output']['aanvulling'] ; 
            }

            //attach disposition
            if(array_key_exists('disposition', $thisSaResults) && array_key_exists($reportIn, $thisSaResults['disposition'])){
                $this->_results[$sid][$analysis['assay_base']]['disposition_kve'] = $thisSaResults['disposition'][$reportIn];
            }

            if(is_array($references) && array_key_exists('ref_' . $reportIn , $references) && ( $references['ref_' . $reportIn] !== '' && $references['ref_' . $reportIn] !== '+' && $references['ref_' . $reportIn] !== '-' ) ){
                //check if this is karkar or not..

                if(strpos($references['ref_' . $reportIn], '.') !== false ||  strpos($references['ref_' . $reportIn], ',')){
                  $reference = $references['ref_' . $reportIn];       
                  $referenceNoBrackets =  $references['ref_' . $reportIn]; 
                } else{

                  //$refFormated = format_number_significant_figures( $references['ref_' . $reportIn], 1);
                  if(is_numeric($references['ref_' . $reportIn]))
                  {
                    $refFormated = number_format($references['ref_' . $reportIn], 0, ',', '.');
                  }
                  else{
                    $refFormated = $references['ref_' . $reportIn];
                  }
                  

                  $reference = '(' . $refFormated . ')';
                  $referenceNoBrackets = $refFormated;
                }

            } else{
                $reference = '&nbsp;';
                $referenceNoBrackets = '';
            }

            $this->_results[$sid][$analysis['assay_base']]['kve_reference'] = $reference;
            $this->_results[$sid][$analysis['assay_base']]['kve_reference_no_brackets'] = $referenceNoBrackets;

            //add reference source
            $this->_results[$sid][$analysis['assay_base']]['reference_source'] = checkKeyOrNULL($resultForProfile[0], 'reference_source');
            

            
        }

        return $sampleProducedOutput;

    }

    private function _sortAssayArray(){

      // $assayList = array();
      // foreach($this->_methods['assays'] as $assayBase => $assayBaseInfo){
      //   $assayList[$assayBase] = $assayBaseInfo['sortPosition'];
      // }

      $loadedAssays = $this->_methods['assays'];
      // array_multisort($assayList, SORT_ASC, $loadedAssays);

      //fix index
      $keys = array_keys($loadedAssays);
      $sortPosition = NULL;

      array_multisort(
          array_column($loadedAssays, 'sortPosition'), SORT_ASC, SORT_NUMERIC, $loadedAssays, $keys
      );

      $loadedSortedAssays = array_combine($keys, $loadedAssays);
      $this->_methods['assays'] = $loadedSortedAssays;
    }

    private function _loadAssayMethod($assayBaseId){

        $assay = upa('assays', 'fetchSingle', array($assayBaseId), False);

        if($assay != False){
            $fields = json_decode($assay['custom_fields'], True);
            foreach($fields as $scf => $scv){
                $assay['assaycustom_' . $scf] = $scv;
            }
        }

        return $assay;
    }

    private function _loadSampleMethod($methodId){

        $method = upa('sampleProcedures', 'fetch', array($methodId), False);

        if($method != False){
            $fields = json_decode($method['fields'], True);

            foreach($fields as $scf => $scv){
                $method['samplingcustom_' . $scf] = $scv;
            }
        }

        return $method;
    }


    private function _generateReportInfo($follow = False, $special = False){
        if($follow == True){
            $topInfoTpl = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/report_info_follow.php');
        } else{
            $topInfoTpl = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/report_info.php');
        }

   

        foreach($this->_repStack as $repParam => $repValue){
            $topInfoTpl = str_replace($repParam, $repValue, $topInfoTpl);
        }


        if($special == 'rodac'){

            $customer = False;
            if(array_key_exists('sample_method', $this->_repStack)){

              $fields = checkKeyOrFalse($this->_methods, 'sampling', $this->_repStack['sample_method'], 'fields' );

              if($this->_reportIsEnglish == True){
                $samplingBy = peekIntoJSON($fields, 'doorEng');
              } else{
                $samplingBy = peekIntoJSON($fields, 'door');
              }

                $rodacClntArray = json_decode(RODAC_SAMPLE_MTHDS_BY_CUSTOMER, JSON_FORCE_OBJECT);

                #if($this->_repStack['sample_method'] == '5'){
                if(in_array($this->_repStack['sample_method'], $rodacClntArray)){
                    $this->_repStack['rodac_by_customer'] = True;
                    $customer = True;
                } else{
                    $this->_repStack['rodac_by_customer'] = False;
                    $customer = False;
                }
            } else{
                $this->_repStack['rodac_by_customer'] = True;
                $customer = True;
            }

            $replacer = array();
            if($customer == True){
                #$replacer['sampling_by_nl'] = 'Opdrachtgever';
                $replacer['sampling_by_nl'] = $samplingBy;
                $replacer['hide_extra_rows_start'] = '<!--';
                $replacer['hide_extra_rows_stop'] = '-->';
            } else{
                #$replacer['sampling_by_nl'] = 'M.A.Z.';
                $replacer['sampling_by_nl'] = $samplingBy;
                $replacer['sampling_procedure'] = $this->_repStack['global_sampling_ref'];
                $replacer['method'] = '1';
                $replacer['hide_extra_rows_start'] = '';
                $replacer['hide_extra_rows_stop'] = '';
            }

            $topInfoTpl = generateHTML($topInfoTpl, $replacer, True);

        }

        return $topInfoTpl;
    }

    private function _createSampleListTable($samples){

        $descriptionLabel = ($this->_reportIsEnglish) ? 'Description:' : 'Omschrijving:';
        $detailsLabel =  ($this->_reportIsEnglish) ? 'Additional information:' : 'Aanvullende informatie:';
        $roomLabel  =  ($this->_reportIsEnglish) ? 'Room: ' : 'Ruimte: ';

        //attach meta
        foreach($samples as $idx => $thisSample){

            $details = '';
          
            $meta = upa('metadata', 'forSample', array($thisSample['id']), False );
            
            
            //does sample have a custom client description, either by the client or by maz?
            $trimmedDescription = trim($thisSample['client_description']);

            $sampleExtra = json_decode($thisSample['sample_extra'], True);

            $trimmedDetails = trim($thisSample['samplecustom_details']);

            $hasClientDescription = False; 

            if(strtolower($trimmedDescription) !== 'geen omschrijving beschikbaar')
            {
                $hasClientDescription = True; 
            }

            
            if( !empty($trimmedDetails))
            {

                if(((int)$thisSample['source'] === 1 || (int)$thisSample['source'] === 3))
                {
                    $details = $detailsLabel . ' ' . $thisSample['samplecustom_details'];                
                }
    
                else
                {
                    $details = $thisSample['samplecustom_details'];
                }
              
            }

            $trimTest = trim($details);            

            $room = checkKeyOrFalse($sampleExtra, 'location');

            if($room){
                $details = $details . '<br />' . $roomLabel  . $room . '';
            }
          
            if(!empty($meta)){

          
                if(!empty($trimTest)){
                    $details = $details . '<br />';
                }

                foreach($meta as $thisMeta){
                    $details = $details . trim($thisMeta['name']) . ': '  . nl2br($thisMeta['value']) . '<br />';
                }                        
            }            
            
            $trimmedDescription = trim($thisSample['client_description']);
          
            if(((int)$thisSample['source'] === 1 || (int)$thisSample['source'] === 3) || $hasClientDescription === True ) 
            {            
            
                if(!empty($trimmedDescription)){
                    $details =  $descriptionLabel . ' ' .$trimmedDescription . '<br />' . $details;
                }
            
            }

            $samples[$idx]['samplecustom_details'] =  $details;
        
        }

        $numberOfSamples = count($samples);
        if($numberOfSamples < 5){
            for($i = $numberOfSamples ; $i < 5; $i++){
                array_push($samples, array( 'id' => $i, 'barcode' => '&nbsp;'));
            }
        }

        $sListTbl = new tableFactory();
        $sListTbl->loadTemplate('sampleListing', $this->_templateName);
        $sListTbl->loadValues($samples);
        $thisPageSampleTable = $sListTbl->renderTable();

        unset($sListTbl);
        return $thisPageSampleTable;
    }

    private function _setupRodacReferences($page)
    {
        $sampleOnPage = $this->_paginatedSamples[$page];
        $firstSample = array_shift($sampleOnPage);

        $assaysForSample = $this->_results[$firstSample['id']];

        $assayId = array_keys($assaysForSample);

        if(count($assayId) > 0)
        {
            $rodacMethod = $assayId[0];
            $methodInfo = $this->_methods['assays'][$rodacMethod];
            $this->_RODACAssayName = ($this->_reportIsEnglish) ?  $methodInfo['assaycustom_raportnaamEng'] :  $methodInfo['assaycustom_raportnaam'];            
        }

        else
        {
            $rodacMethod = NULL;
            $this->_RODACAssayName = '';
        }
                                                
        $firstAssay = array_shift($assaysForSample);
        $kveRef = $firstAssay['kve_reference_no_brackets'];        
        $this->_RODACTripValue = (!empty(trim($kveRef))) ? $kveRef : NULL;        

        $refSource = $firstAssay['reference_source'];
        
        if($refSource !== null)
        {
            $refSource = checkKeyOrNULL($this->_referenceSources, $refSource, 'name');
            $refArr = json_decode($refSource, JSON_FORCE_OBJECT);
            
            if($this->_reportIsEnglish)
            {
                $this->_RODACReferenceSources = $refArr['en'];                                    
            }
    
            else
            {
                $this->_RODACReferenceSources = $refArr['nl'];
            }

        }

        else
        {
            $this->_RODACReferenceSources =  NULL;
        }
                
                           
    }

    private function _createResultsTableRodac2020($page, $lastPage = False ){

        $this->_setupRodacReferences($page);

        if(!is_null($this->_RODACTripValue) && !empty( $this->_RODACTripValue) )
        {
            $RODAC_UPPERBOUND_1CMKVE = $this->_RODACTripValue;         
            $RODAC_UPPERBOUND_16CMKVE = $RODAC_UPPERBOUND_1CMKVE * 16;
        }

        else
        {
            $RODAC_UPPERBOUND_1CMKVE = NULL; 
            $RODAC_UPPERBOUND_16CMKVE = NULL;
        }

        
                    
        $resultTable = '';
        
        //
        $this->_RVA = False;

        foreach($this->_methods['assays'] as $am)
        {

            if(array_key_exists('assaycustom_accred', $am  ) && strtoupper(trim($am['assaycustom_accred'])) == 'Q' )
            {
                $this->_RVA = True;
            }

        }



        $replacer = array();
        $replacer['used_assay_name'] = $this->_RODACAssayName;
        if( $this->_repStack['rodac_by_customer']  == True){
            $replacer['sampling_method_id'] = '1';
        } else{
            $replacer['sampling_method_id'] = '2';
        }

        $sampleTableHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/result_table_header.php');
        $resultTable = generateHTML($sampleTableHeader, $replacer, True);

        $resultLine =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/resultRow.php');

        if(!isset($this->_rodacTotalCount)){
            $this->_rodacTotalCount = 0;
        }

        if(!isset($this->_numberOfSamples)){
            $this->_numberOfSamples = 0;
        }

        //$totalCount = 0;
        //$numberOfSamples = 0;


        foreach($this->_paginatedSamples[$page] as $thisSample){

            $replacer = array();
            $replacer = $thisSample;
            $replacer['rowStyle'] = 'noBorder';
            $sampleExpand = json_decode($thisSample['sample_extra'], JSON_FORCE_OBJECT);

            if(is_array($sampleExpand))
            {
                foreach($sampleExpand as $sampleExpandKey => $sampleExpandvalue )
                {
                    $replacer[$sampleExpandKey] = $sampleExpandvalue;
                }
        
            }
            
            $thisResult = $this->_results[$thisSample['id']];
            reset($thisResult);
            $firstKey = key($thisResult);

            $indicatief = checkKeyOrFalse($this->_hiddenResults, $thisSample['id'], $firstKey, 'indicatief' );
            $firstElement = array_pop($thisResult);

            if($indicatief == True){
              $this->_rodacInd++;
            }


            foreach($firstElement as $resKey => $resVal)
            {

                $replacer[$resKey] = $resVal;
                if($resKey == 'kve'){
                    $div16 =  $resVal / 16;
                    $nSig = round($div16, 2);
                    $kvePer1cm = $nSig;
                    $nSig = sprintf("%.2f",round($nSig, 2));
                    $nSig = number_format($nSig, 2 , ',' , '.');

                    if($indicatief == True){
                      $replacer[$resKey . '_div16'] = $nSig . '<sup>*</sup>';
                    } else{
                      $replacer[$resKey . '_div16'] = $nSig;
                    }

                }   

                if($resKey == 'average'){
                    $this->_rodacTotalCount = $resVal;
                }
            }

            $outputValue =  preg_replace("/[^0-9.]/", "", $replacer['kve']);


            // if($outputValue > (int)RODAC_UPPERBOUND_16CMKVE){
            //     if($this->_highlightViolations == True){
            //       $replacer['kve16_span_indicator'] = 'color: red; font-weight: bold;';
            //     }
            // }

            // if($kvePer1cm > (int)RODAC_UPPERBOUND_1CMKVE){
            //     if($this->_highlightViolations == True){
            //       $replacer['div16_span_indicator'] = 'color: red; font-weight: bold;';
            //     }
            // }

            if($outputValue > (int)$RODAC_UPPERBOUND_16CMKVE){
                if($this->_highlightViolations == True && $RODAC_UPPERBOUND_16CMKVE !== NULL ){
                    $replacer['kve16_span_indicator'] = 'color: red; font-weight: bold;';
                }
            }

            if($kvePer1cm > (int)$RODAC_UPPERBOUND_1CMKVE){
                if($this->_highlightViolations == True && $RODAC_UPPERBOUND_16CMKVE !== NULL){
                    $replacer['div16_span_indicator'] = 'color: red; font-weight: bold;';
                }
            }


            //bit of a hack to force follow ID of 1 to zip exported files.
            if($this->_isZip == True){
              $replacer['follow'] = '1';
            }

            $resultLineThisAssay = generateHTML($resultLine, $replacer, True);
            $resultTable .= $this->_stripEmptyTags($resultLineThisAssay);
            //$numberOfSamples++;
            $this->_numberOfSamples++;
        }


        //is this the last page?
        if($lastPage == True){
            
            $endAddendum =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/endcalc.php');
            $replacer = array();
            #$replacer['average'] =  sprintf("%.2f", round($this->_rodacTotalCount,2));
            

            $avg = sprintf("%.2f", round($this->_rodacTotalCount,2));
            $replacer['average'] =      number_format($avg, 2 , ',' , '.');
            $indFlag = ($this->_numberOfSamples - $this->_rodacInd) / $this->_numberOfSamples;

            if($indFlag <= 0.50){
                $replacer['average']  =   $replacer['average']  . '<sup>*</sup>';
            }


            if($avg > (int)$RODAC_UPPERBOUND_1CMKVE )
            {

                $replacer['end_result_span'] = '';

                if($this->_highlightViolations == True && $RODAC_UPPERBOUND_16CMKVE !== NULL)
                {
                    $replacer['end_result_span'] = 'color: red; font-weight: bold;';
                }

                $replacer['result_nl'] = 'Onaanvaardbaar';
                $replacer['result_en'] = 'Unacceptable';
            } 
            
            else
            {
                $replacer['result_nl'] = 'Aanvaardbaar';
                $replacer['result_en'] = 'Acceptable';
            }

            $endResultLine = generateHTML($endAddendum, $replacer, True);
            $cleanedLine = $this->_stripEmptyTags($endResultLine);
            $resultTable .= $this->_addClassToSup($cleanedLine);

            $endInterp =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/endinterp.php');
            if($this->_RODACTripValue != NULL)
            {   
                $endResultLine = generateHTML($endInterp, $replacer, True);
                $resultTable .= $this->_stripEmptyTags($endResultLine);
            }
            
        }

        $resultTable .= '</tbody></table>';

        if( $this->_RODACTripValue !== NULL)
        {
            $legend = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/legend.php');
            $legend  = generateHTML($legend, array('trip_value' => $RODAC_UPPERBOUND_1CMKVE, 'reference_source' => $this->_RODACReferenceSources, 'used_assay_name' =>  $this->_RODACAssayName ), True);    
            $resultTable .= $legend;
        }
        
        return $resultTable;
    }

    private function _createResultsTableRodac($page, $lastPage = False ){
                    
        $resultTable = '';
        $this->_RVA = True;

        $replacer = array();
        if( $this->_repStack['rodac_by_customer']  == True){
            $replacer['sampling_method_id'] = '1';
        } else{
            $replacer['sampling_method_id'] = '2';
        }

        $sampleTableHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/result_table_header.php');
        $resultTable = generateHTML($sampleTableHeader, $replacer, True);


        //$resultTable .= $this->_stripEmptyTags($sampleTableHeader);

        $resultLine =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/resultRow.php');


        if(!isset($this->_rodacTotalCount)){
            $this->_rodacTotalCount = 0;
        }

        if(!isset($this->_numberOfSamples)){
            $this->_numberOfSamples = 0;
        }

        //$totalCount = 0;
        //$numberOfSamples = 0;


        foreach($this->_paginatedSamples[$page] as $thisSample){

            $replacer = array();
            $replacer = $thisSample;
            $replacer['rowStyle'] = 'noBorder';
            $sampleExpand = json_decode($thisSample['sample_extra'], JSON_FORCE_OBJECT);


            foreach($sampleExpand as $sampleExpandKey => $sampleExpandvalue ){
                $replacer[$sampleExpandKey] = $sampleExpandvalue;
            }

            $thisResult = $this->_results[$thisSample['id']];
            reset($thisResult);
            $firstKey = key($thisResult);

            $indicatief = checkKeyOrFalse($this->_hiddenResults, $thisSample['id'], $firstKey, 'indicatief' );
            $firstElement = array_pop($thisResult);

            if($indicatief == True){
              $this->_rodacInd++;
            }


            foreach($firstElement as $resKey => $resVal){

                $replacer[$resKey] = $resVal;
                if($resKey == 'kve'){
                    $div16 =  $resVal / 16;
                    $nSig = round($div16, 2);
                    $kvePer1cm = $nSig;
                    $nSig = sprintf("%.2f",round($nSig, 2));
                    $nSig = number_format($nSig, 2 , ',' , '.');

                    if($indicatief == True){
                      $replacer[$resKey . '_div16'] = $nSig . '<sup>*</sup>';
                    } else{
                      $replacer[$resKey . '_div16'] = $nSig;
                    }

                }

                if($resKey == 'average'){
                    //$totalCount = $resVal;
                    $this->_rodacTotalCount = $resVal;
                }
            }

            $outputValue =  preg_replace("/[^0-9.]/", "", $replacer['kve']);


            if($outputValue > (int)RODAC_UPPERBOUND_16CMKVE){
                if($this->_highlightViolations == True){
                  $replacer['kve16_span_indicator'] = 'color: red; font-weight: bold;';
                }
            }

            if($kvePer1cm > (int)RODAC_UPPERBOUND_1CMKVE){
                if($this->_highlightViolations == True){
                  $replacer['div16_span_indicator'] = 'color: red; font-weight: bold;';
                }
            }


            //bit of a hack to force follow ID of 1 to zip exported files.
            if($this->_isZip == True){
              $replacer['follow'] = '1';
            }

            $resultLineThisAssay = generateHTML($resultLine, $replacer, True);
            $resultTable .= $this->_stripEmptyTags($resultLineThisAssay);
            //$numberOfSamples++;
            $this->_numberOfSamples++;
        }


        //is this the last page?

        if($lastPage == True){
            $endAddendum =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/endcalc.php');
            $replacer = array();
            #$replacer['average'] =  sprintf("%.2f", round($this->_rodacTotalCount,2));

            $avg = sprintf("%.2f", round($this->_rodacTotalCount,2));
            $replacer['average'] =      number_format($avg, 2 , ',' , '.');
            $indFlag = ($this->_numberOfSamples - $this->_rodacInd) / $this->_numberOfSamples;

            if($indFlag <= 0.50){
                $replacer['average']  =   $replacer['average']  . '<sup>*</sup>';
            }


            if($avg > 10){

                $replacer['end_result_span'] = '';

                if($this->_highlightViolations == True){
                    $replacer['end_result_span'] = 'color: red; font-weight: bold;';
                }

                $replacer['result_nl'] = 'Onaanvaardbaar';
                $replacer['result_en'] = 'Unacceptable';
            } else{
                $replacer['result_nl'] = 'Aanvaardbaar';
                $replacer['result_en'] = 'Acceptable';
            }

            $endResultLine = generateHTML($endAddendum, $replacer, True);
            $resultTable .= $this->_stripEmptyTags($endResultLine);
        }

        $resultTable .= '</tbody></table>';

        $legend = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/legend.php');
        $resultTable .= $legend;
        return $resultTable;

    }



      private function _createResultsTableLegionella($page){

        $resultTable = '';
        $this->_RVA = True;
        $sampleTableHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/result_table_header.php');
        $resultTable .= $this->_stripEmptyTags($sampleTableHeader);
        $resultLine =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/resultRow.php');

        foreach($this->_paginatedSamples[$page] as $thisSample){

            $replacer = array();
            $replacer = $thisSample;
            $replacer['rowStyle'] = 'resultBorderBottomFull';
            $sampleExpand = json_decode($thisSample['sample_extra'], JSON_FORCE_OBJECT);

            foreach($sampleExpand as $sampleExpandKey => $sampleExpandvalue ){
                $replacer[$sampleExpandKey] = $sampleExpandvalue;
            }

            $meta = upa('metadata', 'forSample', array($thisSample['id']), False );
            if(!empty($meta)){

              $details = $replacer['samplecustom_details'];
              $trimTest = trim($details);

              if(!empty($trimTest)){
                 $details = $details . '<br />';
              }

              foreach($meta as $thisMeta){
                $details = $details . trim($thisMeta['name']) . ': '  . $thisMeta['value'] . '<br />';
              }

              $replacer['samplecustom_details'] =  $details;

            }

            $thisResult = $this->_results[$thisSample['id']];
            $firstElement = array_pop($thisResult);

            //$thisHiddenResult = $this->_hiddenResults[$thisSample['id']];
            //$firstHiddenElement = array_pop($thisHiddenResult);

            foreach($firstElement as $resKey => $resVal){
                $replacer[$resKey] = $resVal;
            }

            #$outputValue =  preg_replace("/[^0-9.]/", "", $replacer['kve']);
            $outputValue =  preg_replace("/[.]/", "", $replacer['kve']);
            //$value = checkKeyOrFalse($this->_results, $thisSample['id'], 0, 'kve');

            $refValue = checkKeyOrFalse($replacer, 'kve_reference');
            $disposition = checkKeyOrFalse($replacer, 'disposition_kve');
            if($refValue === False){
              $refValue = 100;
            }

            $refValue =  preg_replace("/[^0-9]/", "", $refValue);

            if(($outputValue >= $refValue && $disposition === '+' )&& $this->_highlightViolations == True){
                $replacer['span_indicator'] = 'color: red; font-weight: bold;';
            }

            //bit of a hack to force follow ID of 1 to zip exported files.
            if($this->_isZip == True){
              $replacer['follow'] = '1';
            }

            $resultLineThisAssay = generateHTML($resultLine, $replacer, True);
            $resultTable .= $this->_stripEmptyTags($resultLineThisAssay);
        }


        $resultTable .= '</tbody></table>';
        $legend = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/legend.php');
        $resultTable .= $legend;
        return $resultTable;

    }

    private function _isLastSamplingMethod($thisMethod, $samplesOnPage)
    {

        $found = False; 
        $next = False; 

        //always the last if only 1 sample on a page
        if(count($samplesOnPage) === 1) 
        {
            return True; 
        }

        foreach($this->_methods['sampling'] as $smIdx => $sm)
        {
            
            if($smIdx === $thisMethod)
            {
                $found = True; 
                continue; 
            }

            //if found = true, this means this is the next method 
            if($found === True)
            {
                                

                for($i = 1; $i <= $this->_templateMaxCols; $i++) 
                {
                    if(array_key_exists($i, $samplesOnPage)) 
                    {
                        if($samplesOnPage[$i]['sampling_method'] == $sm['id'])
                        {
                            $next = True; 
                        }                    
                    }
                }
            }

            if($next == True)
            {
                break; 
            }
        }    
        
        return ($next) ? False : True;

    }

    private function _createResultsTable($page){

        
        $resultTable = '';
        $this->_RVA = False;

        //$numberOfSamplingProcedures = count($this->_methods['sampling']);
        $numberOfSamplingProcedures = 0;
        foreach($this->_methods['sampling'] as $sm){
            if($sm['hide'] == 0){
                $numberOfSamplingProcedures++;
            }
        }        

        //create header with sample numbers and barcodes
        $sampleTableHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/result_table_header.php');

        for($i = 1; $i <= $this->_templateMaxCols; $i++){
            if(array_key_exists($i, $this->_paginatedSamples[$page])){
                $sampleReplacer = $this->_prependArray($this->_paginatedSamples[$page][$i], $i);
                $sampleTableHeader = generateHTML($sampleTableHeader,$sampleReplacer, True);
            }
        }

        $resultTable .=  $this->_stripEmptyTags($sampleTableHeader);
        $sampleTakeHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/sampling.php');

        for($i = 1; $i <= $this->_templateMaxCols; $i++) {
            if(array_key_exists($i, $this->_paginatedSamples[$page])) {
                $samplingMethod =  $this->_paginatedSamples[$page][$i]['sampling_method'];
                if(array_key_exists($samplingMethod, $this->_methods['sampling']) && $samplingMethod != 0){
                    $sampling = $this->_prependArray($this->_methods['sampling'][$samplingMethod], $i);
                    $sampleTakeHeader = generateHTML($sampleTakeHeader, $sampling, True);
                }
            }
        }

        $replacer = array();
        if($numberOfSamplingProcedures == 0 ){
            $replacer['rowStyle'] = 'sampleTakeBorder';
        } else{
            $replacer['rowStyle'] = 'samplingNoBorder';
        }

        $sampleTakeHeader = generateHTML($sampleTakeHeader, $replacer, True);

        $resultTable .= $this->_stripEmptyTags($sampleTakeHeader);
        
                
        $sampleTakeHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/samplingMethod.php');
        $newSampleTakeHeader = ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/samplingMethod_2020.php';

        if(file_exists($newSampleTakeHeader))
        {

            $sampleTakeHeader =  file_get_contents($newSampleTakeHeader);
            
            foreach($this->_methods['sampling'] as $idx => $sm)
            {
                
                $last = $this->_isLastSamplingMethod($idx, $this->_paginatedSamples[$page]);       

                $replacer = array();
                $replacer['rowStyle'] = ($last === True) ? 'sampleTakeBorder' : 'samplingNoBorder';                                                                 
                $replacer['method_and_follow_id'] = ($sm['hide'] == 1) ? '' : 'M' . $sm['method_follow_id'];
                $replacer['accred'] = $sm['samplingcustom_accred'];                
                $methodText = ($this->_reportIsEnglish == True) ? $sm['samplingcustom_doorEng'] : $sm['samplingcustom_door'];                        
                $methodWasSeenOnPage = False; 
                                      
                for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                    
                    $replacer[$i . '_is_this_method'] =  '';
                    
                    if (array_key_exists($i, $this->_paginatedSamples[$page])) {
                        if( $this->_paginatedSamples[$page][$i]['sampling_method'] == $sm['id'])
                        {
                            $replacer[$i . '_is_this_method'] =  $methodText;
                            $methodWasSeenOnPage = True; 
                        }                                                
                    }
                }
                
                if($methodWasSeenOnPage)
                {                    
                    $methodSampling = generateHTML($sampleTakeHeader, $replacer, True);
                    $resultTable .= $this->_stripEmptyTags($methodSampling);
                }
                
            }
        }   

        else
        {
            foreach($this->_methods['sampling'] as $sm){

                if($sm['hide'] == 1){
                    continue;
                }
    
                if(array_key_exists('samplingcustom_accred', $sm) && strtoupper(trim($sm['samplingcustom_accred'])) == 'Q' ){
                    //$this->_RVA = True;
                }
    
                $replacer = array();
    
                if($numberOfSamplingProcedures == $sm['method_follow_id'] ){
                    $replacer['rowStyle'] = 'sampleTakeBorder';
                } else{
                    $replacer['rowStyle'] = 'samplingNoBorder';
                }
    
                for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                    if (array_key_exists($i, $this->_paginatedSamples[$page])) {
                        if( $this->_paginatedSamples[$page][$i]['sampling_method'] == $sm['id']){
                            //$replacer[$i . '_procedure'] = $sm['samplingcustom_internrefnummer'];
                            $replacer = array_merge($replacer, $this->_prependArray($sm, $i ) );
                        } else{
                            $replacer = array_merge($replacer, $this->_prependArray(array('samplingcustom_internrefnummer' => '-'), $i));
                        }
                    }
                }
                $methodSampling = generateHTML($sampleTakeHeader, array_merge($sm, $replacer), True);
                $resultTable .= $this->_stripEmptyTags($methodSampling);
            }
        }

        

        //write out all methods and their results
        $resultLine =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/resultRow.php');

        foreach($this->_methods['assays'] as $am){

            if(array_key_exists('assaycustom_accred', $am  ) && strtoupper(trim($am['assaycustom_accred'])) == 'Q' )
            {                
                $this->_RVA = True;
            }

            $resultLineThisAssay = generateHTML($resultLine, $am, True);
            $replacer = array();
            $replacer['rowStyle'] = 'resultBorderBottomFull';
            $rowWasFound = false;

            for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                if (array_key_exists($i, $this->_paginatedSamples[$page])) {

                        $sampId = $this->_paginatedSamples[$page][$i]['id'];
                        
                        //setup the bulb                                                     
                        $replacer[$i . '_bulb_indicator'] = 'color: white; font-weight: bold;';

                        if(is_array($this->_results[$sampId]) && array_key_exists($am['id'], $this->_results[$sampId])){

                            $rowWasFound = True; 
                            
                            $uses_bulb = filter_var($am['uses_indicator'], FILTER_VALIDATE_BOOLEAN);
                            $uses_trip_bulb = filter_var($am['uses_trip_indicator'], FILTER_VALIDATE_BOOLEAN);

                            
                            $refSourceId = $this->_results[$sampId][$am['id']]['reference_source'];
                            $refSource = checkKeyOrNULL($this->_referenceSources, $refSourceId, 'name');

                            if($refSource === NULL)
                            {
                                $refSourceName = '';
                            }

                            else
                            {
                                $refArr = json_decode($refSource, JSON_FORCE_OBJECT);
                                if($this->_reportIsEnglish)
                                {
                                    $refSourceName = $refArr['en'];                                    
                                }

                                else
                                {
                                    $refSourceName = $refArr['nl'];
                                }
                            }
                            
                            $replacer[$i . '_reference_source_name'] = $refSourceName;

                            
                            
                            $refValue =  preg_replace("/[^0-9]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);
                            $outputValueWasSmallerThenZero = ( trim($this->_results[$sampId][$am['id']]['kve']) == '<1') ? True: False; 
                            $outputValue =  preg_replace("/[^0-9,]/", "", $this->_results[$sampId][$am['id']]['kve'], -1, $nonNumericCount);
                            $outputValueStrip = str_replace(',', '.',  $outputValue);
                            $refValueText = $this->replaceNbspWithSpace($this->_results[$sampId][$am['id']]['kve_reference_no_brackets']);
                                                    

                            //$outputValueStrip =  preg_replace("/[^0-9.]/", "", $this->_results[$sampId][$am['id']]['kve']);
                            $refValueStrip = preg_replace("/[^0-9]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);


                            if($uses_bulb == True)
                            {
                                                                

                                if(array_key_exists('disposition_kve', $this->_results[$sampId][$am['id']]) && !is_numeric($outputValueStrip) ){
                                    
                                
                                    if($this->_highlightViolations == True && !empty(trim($refValueText))  )
                                    {                                    
                                        $replacer[$i . '_bulb_indicator'] = 'color: #7bc144; font-weight: bold;';
                                    }
                                    
                                    
                                    if($this->_results[$sampId][$am['id']]['disposition_kve'] == '+' ){
                                        
                                        if($this->_highlightViolations == True && $uses_trip_bulb  == True)
                                        {
                                            $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
                                            $replacer[$i . '_bulb_indicator'] = 'color: #ff0000; font-weight: bold;';
                                        }

                                        else
                                        {
                                            $replacer[$i . '_bulb_indicator'] = 'color: white; font-weight: bold;';
                                        }   


                                    }

                                    if($this->_results[$sampId][$am['id']]['disposition_kve'] == '.' ){
                                        if($this->_highlightViolations == True)
                                        {
                                            $replacer[$i . '_bulb_indicator'] = 'color: white; font-weight: bold;';
                                        }
                                    }


                                }

                                elseif(is_numeric($outputValueStrip)){
                                    
                                                                    

                                    if($outputValueWasSmallerThenZero === True)
                                    {
                                        $outputValueStrip = 0;
                                    }
                            
                                    //workable? 
                                    $workable = $this->_isReferenceParseable($this->_results[$sampId][$am['id']]['kve'], $outputValueStrip, $refValueStrip);                                 
            
                                    if($workable)
                                    {
                                        
                                        
                                        if($refValueStrip != '' &&  
                                        ( (float)$outputValueStrip > (float)$refValueStrip || ($outputValueStrip == $refValueStrip  &&  strpos($outputValueStrip, '>') !== FALSE) ) )
                                        {                                        
                                            
                                            if($this->_highlightViolations == True && $this->_templateAllowsTrip() && $uses_trip_bulb == True )
                                            {                                            
                                                $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
                                                $replacer[$i . '_bulb_indicator'] = 'color: #ff0000; font-weight: bold;';
                                            }    
                                        } 
                                        
                                        else 
                                        {
                                            
                                            if($this->_highlightViolations == True)
                                            {   
                                                if($refValueStrip != '')
                                                {
                                                    $replacer[$i . '_bulb_indicator'] = 'color: #7bc144; font-weight: bold;';
                                                }
                                                
                                            }
                                        }
                                    }                                                            
                                    
                                    else
                                    {                                    
                                        $replacer[$i . '_bulb_indicator'] = 'color: white; font-weight: bold;';
                                    }
                                }                         

                            } //bulb end 

                            foreach($this->_results[$sampId][$am['id']] as $outputName => $outputValue){
                                $replacer[$i . '_' . $outputName] = $outputValue;
                            }
                        } 
                        
                }
            }

            if($rowWasFound === True)
            {
                $resultLineThisAssay = generateHTML($resultLineThisAssay, $replacer, True);
                $resultTable .= $this->_stripEmptyTags($resultLineThisAssay);
            }
            
        }
        
        
        $resultTable .= '</tbody></table>';
        $legend = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/legend.php');
        $resultTable .= $legend;
        return $resultTable;
    }

    private function  replaceNbspWithSpace($content){
        $string = htmlentities($content, null, 'utf-8');
        $content = str_replace("&nbsp;", " ", $string);
        $content = html_entity_decode($content);
        return $content;
    }

    private function _isReferenceParseable($value, $trimmedValue,  $reference)
    {

        $isLessThen = $this->isLessThan($value);
        $isGreaterThan = $this->isGreaterThan($value); 
        
        //is the value less then "above" the reference
        if($isLessThen && $trimmedValue > $reference )
        {
            return False; 
        }

        //is the value more then "below" the reference
        if($isGreaterThan && $trimmedValue < $reference )
        {
            return False;
        }

        //good to go
        return True;

    }

    private function isLessThan($value){
        $composite = $this->stripTags($value);
        if (strpos($composite, '<') !== false) {
          return True;
        } else{
          return False;
        }
      }
    
    private function isGreaterThan($value){
        $composite = $this->stripTags($value);
        if (strpos($composite, '>') !== false) {
            return True;
        } else{
            return False;
        }
    }

    private function stripTags($input){
        $rep = array();
        $rep[0] = '<sup>';
        $rep[1] = '</sup>';
        $replaces = str_replace($rep,  '',   $input);
        return trim($replaces);
    }

    private function _createResultsTableLegionella2019($page){

        

        $resultTable = '';
        $legendArray = array();
        $this->_RVA = False;

        //$numberOfSamplingProcedures = count($this->_methods['sampling']);
        $numberOfSamplingProcedures = 0;
        foreach($this->_methods['sampling'] as $sm){
            if($sm['hide'] == 0){
                $numberOfSamplingProcedures++;
            }
        }      

        

        //create header with sample numbers and barcodes
        $sampleTableHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/result_table_header.php');

        for($i = 1; $i <= $this->_templateMaxCols; $i++){
            if(array_key_exists($i, $this->_paginatedSamples[$page])){
                $sampleReplacer = $this->_prependArray($this->_paginatedSamples[$page][$i], $i);
                $sampleTableHeader = generateHTML($sampleTableHeader,$sampleReplacer, True);
            }
        }

        $resultTable .=  $this->_stripEmptyTags($sampleTableHeader);
        $sampleTakeHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/sampling.php');



        for($i = 1; $i <= $this->_templateMaxCols; $i++) {
            if(array_key_exists($i, $this->_paginatedSamples[$page])) {
                $samplingMethod =  $this->_paginatedSamples[$page][$i]['sampling_method'];                
                if(array_key_exists($samplingMethod, $this->_methods['sampling']) && $samplingMethod != 0){
                                     
                    $sampling = $this->_prependArray($this->_methods['sampling'][$samplingMethod], $i);                  
                    $sampleTakeHeader = generateHTML($sampleTakeHeader, $sampling, True);
                }
            }
        }

        $replacer = array();
        if($numberOfSamplingProcedures == 0 ){
            $replacer['rowStyle'] = 'sampleTakeBorder';
        } else{
            $replacer['rowStyle'] = 'samplingNoBorder';
        }

        $sampleTakeHeader = generateHTML($sampleTakeHeader, $replacer, True);

        $resultTable .= $this->_stripEmptyTags($sampleTakeHeader);
        $sampleTakeHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/samplingMethod.php');
        $newSampleTakeHeader = ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/samplingMethod_2020.php';
        
        if(file_exists($newSampleTakeHeader))
        {

            $sampleTakeHeader =  file_get_contents($newSampleTakeHeader);
            
            foreach($this->_methods['sampling'] as $idx => $sm)
            {
                
                $last = $this->_isLastSamplingMethod($idx, $this->_paginatedSamples[$page]);       

                $replacer = array();
                $replacer['rowStyle'] = ($last === True) ? 'sampleTakeBorder' : 'samplingNoBorder';                                                                 
                $replacer['method_and_follow_id'] = ($sm['hide'] == 1) ? '' : 'M' . $sm['method_follow_id'];
                $replacer['accred'] = $sm['samplingcustom_accred'];                
                $methodText = ($this->_reportIsEnglish == True) ? $sm['samplingcustom_doorEng'] : $sm['samplingcustom_door'];                        
                $methodWasSeenOnPage = False; 
                                      
                for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                    
                    $replacer[$i . '_is_this_method'] =  '';
                    
                    if (array_key_exists($i, $this->_paginatedSamples[$page])) {
                        if( $this->_paginatedSamples[$page][$i]['sampling_method'] == $sm['id'])
                        {
                            $replacer[$i . '_is_this_method'] =  $methodText;
                            $methodWasSeenOnPage = True; 
                        }                                                
                    }
                }
                
                if($methodWasSeenOnPage)
                {                    
                    $methodSampling = generateHTML($sampleTakeHeader, $replacer, True);
                    $resultTable .= $this->_stripEmptyTags($methodSampling);
                }
                
            }
        }   

        else
        {
            foreach($this->_methods['sampling'] as $sm){

                if($sm['hide'] == 1){
                    continue;
                }
    
                if(array_key_exists('samplingcustom_accred', $sm) && strtoupper(trim($sm['samplingcustom_accred'])) == 'Q' ){
                    //$this->_RVA = True;
                }
    
                $replacer = array();
    
                if($numberOfSamplingProcedures == $sm['method_follow_id'] ){
                    $replacer['rowStyle'] = 'sampleTakeBorder';
                } else{
                    $replacer['rowStyle'] = 'samplingNoBorder';
                }
    
                for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                    if (array_key_exists($i, $this->_paginatedSamples[$page])) {
                        if( $this->_paginatedSamples[$page][$i]['sampling_method'] == $sm['id']){
                            //$replacer[$i . '_procedure'] = $sm['samplingcustom_internrefnummer'];
                            $replacer = array_merge($replacer, $this->_prependArray($sm, $i ) );
                        } else{
                            $replacer = array_merge($replacer, $this->_prependArray(array('samplingcustom_internrefnummer' => '-'), $i));
                        }
                    }
                }
                $methodSampling = generateHTML($sampleTakeHeader, array_merge($sm, $replacer), True);
                $resultTable .= $this->_stripEmptyTags($methodSampling);
            }
        }


        //write out all methods and their results
        $resultLine =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/resultRow.php');
        $legend = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/spec_table.php');

        //legionella?
        $methodIds = array_keys($this->_methods['assays']);
        
        //find Q? 
        foreach($this->_methods['assays'] as  $assay){
            
            if(array_key_exists('assaycustom_accred', $assay  ) && strtoupper(trim($assay['assaycustom_accred'])) == 'Q' ){
                $this->_RVA = True;
            }
        }
        
        //start the line 
        $replacer = array();
        $replacer['rowStyle'] = 'resultBorderBottomFull';        
        $replacer['method_follow_id'] = $numberOfSamplingProcedures + 1;        
        $replacer['nextUpId'] = $numberOfSamplingProcedures + 2  ;  
        $resultLineThisAssay = generateHTML($resultLine, $replacer, True);
        unset($replacer['method_follow_id']);

        for($i = 1; $i <= $this->_templateMaxCols; $i++) {

            if (array_key_exists($i, $this->_paginatedSamples[$page])) {            
                $sampId = $this->_paginatedSamples[$page][$i]['id'];                
                
                $sampleExtra = json_decode( $this->_paginatedSamples[$page][$i]['sample_extra'], JSON_FORCE_OBJECT);


                
                //get first analysis of the stack
                if(is_array($this->_results[$sampId])){
                    
                    //$am = array_pop($this->_results[$sampId]);

                    $sampMethds = array_keys($this->_results[$sampId]);
                    $lastMeth = array_pop($sampMethds);                    
                    $am = $this->_methods['assays'][$lastMeth];                     


                    
                    $refValue =  preg_replace("/[^0-9]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);
                    $outputValue =  preg_replace("/[^0-9,]/", "", $this->_results[$sampId][$am['id']]['kve'], -1, $nonNumericCount);
                    $outputValueStrip = str_replace(',', '.',  $outputValue);                            
                    
                    if($refValue === False){
                        $refValue = 100;
                    }
                                            
                    $refValueStrip = preg_replace("/[^0-9]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);

                    if(array_key_exists('disposition_kve', $this->_results[$sampId][$am['id']])){
                            if($this->_results[$sampId][$am['id']]['disposition_kve'] == '+'){
                                if($this->_highlightViolations == True){
                                    $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
                                }
                            }
                    }

                    elseif(is_numeric($outputValueStrip)){
                        if($refValueStrip != '' &&  ( $outputValueStrip > $refValueStrip || ($outputValueStrip == $refValueStrip  &&  strpos($outputValueStrip, '>') !== FALSE) ) ){
                            if($this->_highlightViolations == True){
                            $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
                            }

                        }
                    }

                    foreach($this->_results[$sampId][$am['id']] as $outputName => $outputValue){
                        $replacer[$i . '_' . $outputName] = $outputValue;
                    }
                }
                    
                    
                //inject aditional legend info for report
              

                //hack in the english translation 

                $typeExploded = explode('|', checkKeyOrBlank($am, 'assaycustom_water_type'));
                
                if(is_array($typeExploded))
                {                    
                    $replacer[$i . '_water_type'] = ($this->_reportIsEnglish === True) ? $typeExploded[1] : $typeExploded[0];
                }

                else
                {
                    $replacer[$i . '_water_type'] = $typeExploded;
                }              

                $replacer[$i . '_matrix_type'] = checkKeyOrBlank($am, 'assaycustom_matrix_type');
                $replacer[$i . '_used_media'] = checkKeyOrBlank($am, 'assaycustom_used_leg_media');
                $replacer[$i . '_leg_procedure'] = checkKeyOrBlank($am, 'assaycustom_leg_procedure');
                $replacer[$i . '_leg_accred'] = checkKeyOrBlank($am, 'assaycustom_accred');

                $volumeFound = checkKeyOrFalse($sampleExtra, 'filter_volume');
                
                if($volumeFound === False){
                    $volumeFound = '';
                } else{
                    
                    if(trim(checkKeyOrFalse($am, 'assaycustom_matrix_type')) == 'C'){
                        $volumeFound = $volumeFound; 
                    }

                    else{
                        $volumeFound = $volumeFound * 2; 
                    }
                    
                }
                
                $replacer[$i . '_filter_vol'] = $volumeFound;
                $replacer[$i . '_leiding'] = checkKeyOrBlank($sampleExtra, 'type');

                //need to adjust to english
                if($this->_reportIsEnglish === True)
                {
                    if(trim($replacer[$i . '_leiding']) == 'Warm')
                    {
                        $replacer[$i . '_leiding']  = 'Hot';
                    }

                    if(trim($replacer[$i . '_leiding']) == 'Koud')
                    {
                        $replacer[$i . '_leiding']  = 'Cold';
                    }

                    if(trim($replacer[$i . '_leiding']) == 'Mengwater')
                    {
                        $replacer[$i . '_leiding']  = 'Mixed water';
                    }

                }
                $replacer[$i . '_temp'] = checkKeyOrBlank($sampleExtra, 'temperature');                    
                    
                $resultLineThisAssay = generateHTML($resultLineThisAssay, $replacer, True);
                $legend = generateHTML($legend, $replacer, True);   
                }

            }           
  

        $cleanedLine = $this->_stripEmptyTags($resultLineThisAssay);
        $resultTable .=  $this->_addClassToSup($cleanedLine);

        // foreach($this->_methods['assays'] as $am){

         

        //     if(array_key_exists('assaycustom_accred', $am  ) && strtoupper(trim($am['assaycustom_accred'])) == 'Q' ){
        //         $this->_RVA = True;
        //     }
            
        //     $resultLineThisAssay = generateHTML($resultLine, $am, True);
        //     $replacer = array();
        //     $replacer['rowStyle'] = 'resultBorderBottomFull';
        //     $replacer['nextUpId'] = (int)$am['method_follow_id']  + 1;
            

        //     for($i = 1; $i <= $this->_templateMaxCols; $i++) {
        //         if (array_key_exists($i, $this->_paginatedSamples[$page])) {


        //                 $sampId = $this->_paginatedSamples[$page][$i]['id'];
        //                 $sampleExtra = json_decode( $this->_paginatedSamples[$page][$i]['sample_extra'], JSON_FORCE_OBJECT);


                        
        //                 if(is_array($this->_results[$sampId]) && array_key_exists($am['id'], $this->_results[$sampId])){

        //                     $refValue =  preg_replace("/[^0-9]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);
        //                     $outputValue =  preg_replace("/[^0-9,]/", "", $this->_results[$sampId][$am['id']]['kve'], -1, $nonNumericCount);
        //                     $outputValueStrip = str_replace(',', '.',  $outputValue);                            
                            
        //                     if($refValue === False){
        //                       $refValue = 100;
        //                     }
                                                    
        //                     $refValueStrip = preg_replace("/[^0-9]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);

        //                     if(array_key_exists('disposition_kve', $this->_results[$sampId][$am['id']])){
        //                             if($this->_results[$sampId][$am['id']]['disposition_kve'] == '+'){
        //                                 if($this->_highlightViolations == True){
        //                                   $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
        //                                 }
        //                             }
        //                     }

        //                     elseif(is_numeric($outputValueStrip)){
        //                         if($refValueStrip != '' &&  ( $outputValueStrip > $refValueStrip || ($outputValueStrip == $refValueStrip  &&  strpos($outputValueStrip, '>') !== FALSE) ) ){
        //                           if($this->_highlightViolations == True){
        //                             $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
        //                           }

        //                         }
        //                     }

        //                     foreach($this->_results[$sampId][$am['id']] as $outputName => $outputValue){
        //                         $replacer[$i . '_' . $outputName] = $outputValue;
        //                     }
        //                 }
                        
                        
        //                 //inject aditional legend info for report
        //                 $replacer[$i . '_water_type'] = checkKeyOrBlank($am, 'assaycustom_water_type');
        //                 $replacer[$i . '_matrix_type'] = checkKeyOrBlank($am, 'assaycustom_matrix_type');
        //                 $replacer[$i . '_used_media'] = checkKeyOrBlank($am, 'assaycustom_used_leg_media');
        //                 $replacer[$i . '_leg_procedure'] = checkKeyOrBlank($am, 'assaycustom_leg_procedure');
        //                 $replacer[$i . '_leg_accred'] = checkKeyOrBlank($am, 'assaycustom_accred');


        //                 $replacer[$i . '_filter_vol'] = checkKeyOrBlank($sampleExtra, 'filter_volume');
        //                 $replacer[$i . '_leiding'] = checkKeyOrBlank($sampleExtra, 'type');
        //                 $replacer[$i . '_temp'] = checkKeyOrBlank($sampleExtra, 'temperature');
        //         }


        //     }
        //     $resultLineThisAssay = generateHTML($resultLineThisAssay, $replacer, True);
        //     $resultTable .= $this->_stripEmptyTags($resultLineThisAssay);
        // }

        $resultTable .= '</tbody></table>';
        
        
 
        $resultTable .= $this->_stripEmptyTags($legend);

       

        return $resultTable;
    }

    private function _addClassToSup($input)
    {
        
        return str_replace('<sup>', '<sup class="sup">', $input);

    }

    private function _calculateAveragesKarkas($baseAssay, $param = 'kve'){

        $thisAssayCount = 0;
        $thisAssayN = 0;
        $thisAssayInd = 0;

        foreach($this->_foundSamples as $sid => $sample){

            $value = checkKeyOrFalse($this->_results, $sample['id'], $baseAssay, $param);
            $hiddenValue = checkKeyOrFalse($this->_hiddenResults, $sample['id'], $baseAssay, $param);
            $assayExists = checkKeyOrFalse($this->_results, $sample['id'], $baseAssay);

            //check if this even exists
            if($assayExists == True){

              $thisAssayN++;
              $references = checkKeyOrFalse($this->_hiddenResults, $sample['id'], $baseAssay, 'limits');

              //check if it was indicatief?
              $ind = checkKeyOrFalse($this->_hiddenResults, $sample['id'], $baseAssay, 'indicatief');
              if($ind == True){
                $thisAssayInd++;
              }

              //if a hidden value is set, use this, as it holds the estimate "over maximum" calculation
              if($hiddenValue != False){
                  $lookAtValue = number_format((float)$hiddenValue, 2, '.', '');
                  #$thisAssayCount = $thisAssayCount + $hiddenValue;
              }

              //if not, use the normal output.
              else{
                  $lookAtValue = number_format((float)$value, 2, '.', '');
                  #$thisAssayCount = $thisAssayCount +  preg_replace("/[^0-9,.]/", "", $value);
              }

              $thisAssayCount = $thisAssayCount +  $lookAtValue;
            }
        }

        $avg = $thisAssayCount / $thisAssayN;
        $avg = number_format((float)$avg, 2, '.', '');
        $indicatiefRatio = 0;

        if($thisAssayInd > 0 && $thisAssayN > 0){
          $indicatiefRatio = $thisAssayInd / $thisAssayN;
        }


        $returnAvg = array();
        $returnAvg['value'] = NULL;
        $returnAvg['addendum'] = NULL;
        $returnAvg['failed'] = False;
        //$returnAvg['indicatief'] = False;



        if($references !== False){
          $returnAvg['value'] = $avg;
          $returnAvg['value'] = number_format((float)$avg, 2, '.', '');

          if($indicatiefRatio >= 0.5){
            $returnAvg['value'] = $this->_addClassToSup($returnAvg['value'] . '<sup>*</sup>');
          }

          if($avg > $references['upper'] ){
              #$returnAvg['value'] = $references['upper']
              //$returnAvg['value'] = '> ' . number_format((float)$references['upper'], 2, '.', '');
              
              $returnAvg['addendum'] = ($this->_reportIsEnglish) ? $references['high_text_en'] :  $references['high_text'];
              
              $returnAvg['failed'] = True;
          } elseif($avg >= $references['lower']){
              
            $returnAvg['addendum'] = ($this->_reportIsEnglish) ? $references['medium_text_en'] :  $references['medium_text'];
            // $references['medium_text'];
          } else{
            
            $returnAvg['addendum'] = ($this->_reportIsEnglish) ? $references['lower_text_en'] :  $references['lower_text'];
            //$references['lower_text'];
          }



        }

        return $returnAvg;
    }

    private function _createResultsTableKarkas($page){
        
        $resultTable = '';
        $this->_RVA = False;

        //$numberOfSamplingProcedures = count($this->_methods['sampling']);
        $numberOfSamplingProcedures = 0;
        foreach($this->_methods['sampling'] as $sm){
            if($sm['hide'] == 0){
                $numberOfSamplingProcedures++;
            }
        }

        //create header with sample numbers and barcodes
        $sampleTableHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/result_table_header.php');

        for($i = 1; $i <= $this->_templateMaxCols; $i++){
            if(array_key_exists($i, $this->_paginatedSamples[$page])){
                $sampleReplacer = $this->_prependArray($this->_paginatedSamples[$page][$i], $i);
                $sampleTableHeader = generateHTML($sampleTableHeader,$sampleReplacer, True);
            }
        }

        $resultTable .=  $this->_stripEmptyTags($sampleTableHeader);
        $sampleTakeHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/sampling.php');
        

        for($i = 1; $i <= $this->_templateMaxCols; $i++) {
            if(array_key_exists($i, $this->_paginatedSamples[$page])) {
                $samplingMethod =  $this->_paginatedSamples[$page][$i]['sampling_method'];
                if(array_key_exists($samplingMethod, $this->_methods['sampling']) && $samplingMethod != 0){
                    $sampling = $this->_prependArray($this->_methods['sampling'][$samplingMethod], $i);
                    $sampleTakeHeader = generateHTML($sampleTakeHeader, $sampling, True);
                }
            }
        }

        $replacer = array();
        if($numberOfSamplingProcedures == 0 ){
            $replacer['rowStyle'] = 'sampleTakeBorder';
        } else{
            $replacer['rowStyle'] = 'samplingNoBorder';
        }

        $sampleTakeHeader = generateHTML($sampleTakeHeader, $replacer, True);

        $resultTable .= $this->_stripEmptyTags($sampleTakeHeader);
        $sampleTakeHeader =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/samplingMethod.php');
        $newSampleTakeHeader = ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/samplingMethod_2020.php';

        if(file_exists($newSampleTakeHeader))
        {

            $sampleTakeHeader =  file_get_contents($newSampleTakeHeader);
            
            foreach($this->_methods['sampling'] as $idx => $sm)
            {
                
                $last = $this->_isLastSamplingMethod($idx, $this->_paginatedSamples[$page]);       

                $replacer = array();
                $replacer['rowStyle'] = ($last === True) ? 'sampleTakeBorder' : 'samplingNoBorder';                                                                 
                $replacer['method_and_follow_id'] = ($sm['hide'] == 1) ? '' : 'M' . $sm['method_follow_id'];
                $replacer['accred'] = $sm['samplingcustom_accred'];
                $methodText = ($this->_reportIsEnglish) ? $sm['samplingcustom_doorEng'] : $sm['samplingcustom_door'];                        
                $methodWasSeenOnPage = False; 
                                      
                for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                    
                    $replacer[$i . '_is_this_method'] =  '';
                    
                    if (array_key_exists($i, $this->_paginatedSamples[$page])) {
                        if( $this->_paginatedSamples[$page][$i]['sampling_method'] == $sm['id'])
                        {
                            $replacer[$i . '_is_this_method'] =  $methodText;
                            $methodWasSeenOnPage = True; 
                        }                                                
                    }
                }
                
                if($methodWasSeenOnPage)
                {                    
                    $methodSampling = generateHTML($sampleTakeHeader, $replacer, True);
                    $resultTable .= $this->_stripEmptyTags($methodSampling);
                }
                
            }
        }   

        else
        {
            foreach($this->_methods['sampling'] as $sm){

                if($sm['hide'] == 1){
                    continue;
                }
    
                if(array_key_exists('samplingcustom_accred', $sm) && strtoupper(trim($sm['samplingcustom_accred'])) == 'Q' ){
                    //$this->_RVA = True;
                }
    
                $replacer = array();
    
                if($numberOfSamplingProcedures == $sm['method_follow_id'] ){
                    $replacer['rowStyle'] = 'sampleTakeBorder';
                } else{
                    $replacer['rowStyle'] = 'samplingNoBorder';
                }
    
                for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                    if (array_key_exists($i, $this->_paginatedSamples[$page])) {
                        if( $this->_paginatedSamples[$page][$i]['sampling_method'] == $sm['id']){
                            //$replacer[$i . '_procedure'] = $sm['samplingcustom_internrefnummer'];
                            $replacer = array_merge($replacer, $this->_prependArray($sm, $i ) );
                        } else{
                            $replacer = array_merge($replacer, $this->_prependArray(array('samplingcustom_internrefnummer' => '-'), $i));
                        }
                    }
                }
                $methodSampling = generateHTML($sampleTakeHeader, array_merge($sm, $replacer), True);
                $resultTable .= $this->_stripEmptyTags($methodSampling);
            }
        }
                

        //write out all methods and their results
        $resultLine =  file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/resultRow.php');

        foreach($this->_methods['assays'] as $am){

            //original id within karkas? Get the average for this method to print it onto the report
            $showForRow = False;
            $karkID = json_decode(MESA_KARKAS_IDS);

            if(in_array($am['id'], $karkID) == True){
              $showForRow = True;
              $average = $this->_calculateAveragesKarkas($am['id']);
            }

            if(array_key_exists('assaycustom_accred', $am  ) && strtoupper(trim($am['assaycustom_accred'])) == 'Q' ){
                $this->_RVA = True;
            }

            $resultLineThisAssay = generateHTML($resultLine, $am, True);
            $replacer = array();
            $replacer['rowStyle'] = 'resultBorderBottomFull';

            for($i = 1; $i <= $this->_templateMaxCols; $i++) {
                if (array_key_exists($i, $this->_paginatedSamples[$page])) {

                    $sampId = $this->_paginatedSamples[$page][$i]['id'];
                    if(is_array($this->_results[$sampId]) && array_key_exists($am['id'], $this->_results[$sampId])){

                        $refValue =  preg_replace("/[^0-9.]/", "", $this->_results[$sampId][$am['id']]['kve_reference']);
                        $outputValue =  preg_replace("/[^0-9.]/", "", $this->_results[$sampId][$am['id']]['kve']);


                        //value is not numeric, try to use disposition to set violation flag

                      //  if(!is_numeric($outputValue)){

                            if(array_key_exists('disposition_kve', $this->_results[$sampId][$am['id']])  && $this->_highlightViolations == True){
                                if($this->_results[$sampId][$am['id']]['disposition_kve'] == '+'){
                                    $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
                                }
                            }

                        //}
                        elseif(is_numeric($outputValue)){
                            #$showForRow = True;
                            #$averageRow = $averageRow + $outputValue;
                            #$averageRowN = $averageRowN + 1;

                            if($refValue != '' && ( $outputValue > $refValue || ($outputValue == $refValue &&  strpos($this->_results[$sampId][$am['id']]['kve'], '>') !== FALSE) ) && $this->_highlightViolations == True){
                                $replacer[$i . '_span_indicator'] = 'color: red; font-weight: bold;';
                            }

                        }

                        foreach($this->_results[$sampId][$am['id']] as $outputName => $outputValue){
                            $outputValue = str_replace('.', ',', $outputValue);
                            $replacer[$i . '_' . $outputName] = $outputValue ;
                            #$replacer[$i . '_' . $outputName] = number_format((float)$outputValue, 2, '.', '');
                        }
                    }
                }
            }
            $replacer['average_span_indicator'] = '';
            $replacer['average'] =   '';
            $replacer['average_addendum'] = '';


            if($showForRow == True){
                $replacer['average'] =   str_replace('.', ',',$average['value']);
                $replacer['average_addendum'] = $average['addendum'];


                if($average['failed'] == True){
                  $replacer['average_span_indicator'] = 'color: red; font-weight: bold;';
                }
            } else{
                $replacer['average'] =    '';
            }

            //bit of a hack to force follow ID of 1 to zip exported files.
            // if($this->_isZip == True){
            //   $replacer['follow'] = '1';
            // }

            $resultLineThisAssay = generateHTML($resultLineThisAssay, $replacer, True);
            $cleanedLine = $this->_stripEmptyTags($resultLineThisAssay);
            $resultTable .=  $this->_addClassToSup($cleanedLine);

        }

        $resultTable .= '</tbody></table>';
        $legend = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/legend.php');
        $resultTable .= $legend;
        return $resultTable;
    }



    private function _createReportContents($special = False){        

        //loop through pages
        $lastPage = count($this->_paginatedSamples);
        
        foreach($this->_paginatedSamples as $page => $samplesOnThisPage){

            //check if first page is needed
            if($page == 1){
                //first page, write opening info
                //$this->_pdfContents .= '<div class="firstPageNudge">&nbsp;</div>';
                $this->_pdfContents .= $this->_generateReportInfo(False, $special);

            } else{
                //second page, write page break and follow header
                $this->_pdfContents .= '<pagebreak />';

                if($special == "legionella"){
                //    $this->_pdfContents .= '<div class="followPageNudgeMore">&nbsp;</div>';
                } else{
                  //  $this->_pdfContents .= '<div class="followPageNudge">&nbsp;</div>';
                }



                $this->_pdfContents .= $this->_generateReportInfo(True, $special);
            }

            //write sample table for this page

            if($special == "legionella"){
                $pageSampleTable = ''; // we do not use a sample table for legionella 
            }elseif($special =='rodac'){
                $this->_createSampleListTable($samplesOnThisPage);
            } else{

                $pageSampleTable = $this->_createSampleListTable($samplesOnThisPage);
            }



            $this->_pdfContents .= $pageSampleTable;
            


            //write this pages result table

            if($special == "legionella"){
                $contents = $this->_createResultsTableLegionella($page);
                $contents = $this->_addClassToSup($contents);

                if($this->_RVA == True){
                    $this->_pdfContents .=  '<!--mpdf
                                                <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                            mpdf-->';
                }

                $this->_pdfContents .= $contents;
                
            } 
            
            elseif($special == "legionella2019"){
                $contents = $this->_createResultsTableLegionella2019($page);
                $contents = $this->_addClassToSup($contents);

                if($this->_RVA == True){
                    $this->_pdfContents .=  '<!--mpdf
                                                <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                            mpdf-->';
                }

                $this->_pdfContents .= $contents;
            }
            
                elseif($special =="rodac"){
                    $printLastPage = ((int)$lastPage == (int)$page ? true : false);
                
                    if($this->_templateName == 'rodac2020' || $this->_templateName == 'rodac2020En')
                    {
                        $contents =  $this->_createResultsTableRodac2020($page, $printLastPage);
                    }

                    else
                    {
                        $contents =  $this->_createResultsTableRodac($page, $printLastPage);
                    }
                
              
                    $contents = $this->_addClassToSup($contents);


                if($this->_RVA == True){

                    

                    $this->_pdfContents .=  '<!--mpdf
                                                <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                            mpdf-->';
                }
                $this->_pdfContents .= $contents;                
            }elseif($special =="karkas"){

                $contents =  $this->_createResultsTableKarkas($page);
                $contents = $this->_addClassToSup($contents);

                if($this->_RVA == True){
                    $this->_pdfContents .=  '<!--mpdf
                                                <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                            mpdf-->';
                }

                $this->_pdfContents .= $contents;
                //$this->_pdfContents .= $this->_createResultsTableKarkas($page);
            }else{

                $contents = $this->_createResultsTable($page);                
                $contents = $this->_addClassToSup($contents);

                if($this->_RVA == True){
                    $this->_pdfContents .=  '<!--mpdf
                                                <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                            mpdf-->';
                }

                $this->_pdfContents .= $contents;
            }
        }


        if($special == "legionella"){
            //no need for final page for legionella
            $this->_createLegionellaNotes();
        } elseif($special =='rodac'){
            $this->_createFinalReportPage();
        } elseif($special == 'legionella2019'){
            $this->_createFinalReportPageLegionella2019();
        } else{
            $this->_createFinalReportPage();
        }
    }

    private function _generateNoteText(){
      $sampleIds = array_column($this->_foundSamples, 'id');
      array_push($sampleIds, 'project');
      $samplesIndexed = indexAlpacaArray($this->_foundSamples);

      $noteLine = file_get_contents(ROOT . '/app/docgen/pdf/noteline.php');
      $renderedNotes = '';

      ksort($this->_projectNotes);

      foreach($this->_projectNotes as $sampleId => $sampleNote ){
          if(in_array($sampleId, $sampleIds) && !empty($sampleNote)){

            if($sampleId == 'project'){
              if($this->_reportIsEnglish == True){
                $thisBarcode = 'General';
              } else{
                $thisBarcode = 'Algemeen';
              }

            } else{
              $thisBarcode = checkKeyOrFalse($samplesIndexed, $sampleId, 'barcode');
              $thisBarcode = 'MAZ-L ' . $thisBarcode;
            }

            $renderedNote = generateHTML($noteLine, array('barcode' => $thisBarcode, 'text' => nl2br($sampleNote)), True);

            if($sampleId == 'project'){
              $renderedNotes = $renderedNote . $renderedNotes;
            } else{
              $renderedNotes = $renderedNotes . $renderedNote;
            }
          }
      }

      return $renderedNotes;
    }

    private function _createLegionellaNotes(){
        $reportTable = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/remarkTable.php');
        $notesRendered = $this->_generateNoteText();
        #$this->_pdfContents .= generateHTML($reportTable, array('remarks' => $this->_projectInfo['project_notes']), True);
        $this->_pdfContents .= generateHTML($reportTable, array('remarks' => $notesRendered), True);
    }

    private function _createFinalReportPage($special = False){


        $this->_pdfContents .= '<pagebreak />';
        
        $this->_pdfContents .= $this->_generateReportInfo(True);

        $this->_pdfContents .=  '<!--mpdf
                                            <sethtmlpagefooter name="standardFooter" value="on" show-this-page="1" />
                                    mpdf-->';

        if($this->_RVA == True){
            $this->_pdfContents .=  '<!--mpdf
                                        <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                    mpdf-->';
        }

        $this->_pdfContents .= file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_header.php');
        $smRow = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_sample_row.php');
        $anaRow = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_ana_row.php');

        $tableContents = NULL;

        //$numberOfSamplingProcedures = count($this->_methods['sampling']);
        $numberOfSamplingProcedures = 0;
        foreach($this->_methods['sampling'] as $sm){
            if($sm['hide'] == 0){
                $numberOfSamplingProcedures++;
            }
        }

        foreach($this->_methods['sampling'] as $sm){

                if($sm['hide'] == 1){
                    continue;
                }

                $replacer = array();
                if($numberOfSamplingProcedures >= $sm['method_follow_id']){
                    $replacer['rowStyle'] = 'border';
                } else{
                    $replacer['rowStyle'] = 'noBorder';
                }

                $tableContents .= generateHTML($smRow, array_merge($sm, $replacer), True);
        }

        $numberOfAssays = count($this->_methods['assays']);
        foreach($this->_methods['assays'] as $am){

            $replacer = array();

            if($numberOfAssays >= $am['method_follow_id']){
               $replacer['rowStyle'] = 'sampleTakeBorder';
            } else{
                $replacer['rowStyle'] = 'samplingNoBorder';
            }

            $tableContents .= generateHTML($anaRow, array_merge($am, $replacer), True);
        }

        $this->_pdfContents .= $tableContents;
        $this->_pdfContents .= file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_footer.php');

        //print report notes
        $notesRendered = $this->_generateNoteText();
        $reportTable = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/remarkTable.php');        
        
        $this->_pdfContents .= generateHTML($reportTable, array('remarks' => $notesRendered), True);

        $lang =  ($this->_reportIsEnglish) ? 'en' : 'nl';
        $q = ($this->_RVA) ? 1 : 0; 
        $info = upa('footers', 'get',  array($this->_templateName, $lang, $q ), False);

        if(!$info){
            dd('Could not find footer for this report:' . implode(',', [$this->_templateName, $lang, $q]));
        }
        

        $certInfo = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/certificate_info.php');        
        $this->_pdfContents .= generateHTML($certInfo, array('info' => $info ), True);
        


    }

    private function _createFinalReportPageLegionella2019(){
        
        $this->_pdfContents .= '<pagebreak />';        
        $this->_pdfContents .= $this->_generateReportInfo(True);

        // $this->_pdfContents .=  '<!--mpdf
        //                                     <sethtmlpagefooter name="standardFooter" value="on" show-this-page="1" />
        //                             mpdf-->';

        if($this->_RVA == True){
            $this->_pdfContents .=  '<!--mpdf
                                        <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
                                    mpdf-->';
        }


        $this->_pdfContents .= file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_header.php');
        $smRow = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_sample_row.php');
        $anaRow = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_ana_row.php');
        $lastNumber = 0;

        $tableContents = NULL;        
        $numberOfSamplingProcedures = 0;
        
        foreach($this->_methods['sampling'] as $sm){
            if($sm['hide'] == 0){
                $numberOfSamplingProcedures++;
            }
        }

        foreach($this->_methods['sampling'] as $sm){

                if($sm['hide'] == 1){
                    continue;
                }

                $replacer = array();
                if($numberOfSamplingProcedures >= $sm['method_follow_id']){
                    $replacer['rowStyle'] = 'border';
                } else{
                    $replacer['rowStyle'] = 'noBorder';
                }

                $tableContents .= generateHTML($smRow, array_merge($sm, $replacer), True);
        }

        $numberOfAssays = count($this->_methods['assays']);
        
        
        foreach($this->_methods['assays'] as $am){


            $replacer = array();

            if($numberOfAssays >= $am['method_follow_id']){
               $replacer['rowStyle'] = 'sampleTakeBorder';
            } else{
                $replacer['rowStyle'] = 'samplingNoBorder';
            }

            $lastNumber = (int) $am['method_follow_id'];
            $am['assaycustom_accred'] = '*';
            $tableContents .= generateHTML($anaRow, array_merge($am, $replacer), True);
            break;
        }

        //hack in the serotyping         
        $am = [];
        $am['method_follow_id'] = $lastNumber + 1;
        $am['assaycustom_raportnaam'] = 'Serotypering';
        $am['assaycustom_accred'] = 'Q';
        $am['assaycustom_techniek'] = 'Latex agglutinatie';
        $am['assaycustom_internrefnummer'] = 'MIC03-MAZ007' ;
        $am['assaycustom_conform'] =  'Eigen methode';
        $am['assaycustom_referentiemethode'] = '';

        $am['assaycustom_techniekEng'] = 'Latex agglutination';
        $am['assaycustom_conformEng'] =  'Own method';
        $am['assaycustom_raportnaamEng'] = 'Serotyping';



        $tableContents .= generateHTML($anaRow, array_merge($am, $replacer), True);


        $this->_pdfContents .= $tableContents;
        $this->_pdfContents .= file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/method_table_footer.php');

        //print report notes
        $notesRendered = $this->_generateNoteText();
        $reportTable = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/remarkTable.php');        
        $this->_pdfContents .= generateHTML($reportTable, array('remarks' => $notesRendered), True);

        $lang =  ($this->_reportIsEnglish) ? 'en' : 'nl';
        $q = ($this->_RVA) ? 1 : 0; 

        $info = upa('footers', 'get',  array($this->_templateName, $lang, $q ), False);

        $certInfo = file_get_contents(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/certificate_info.php');        
        $this->_pdfContents .= generateHTML($certInfo, array('info' => $info ), True);
        

    }

    private function __sort($a,$b){
        return strlen($b)-strlen($a);
    }



    private function _loadProjectInfo(){
        $this->_projectInfo = upa('projects', 'fetchProjectInfo', array($this->_projectId));
        $this->_clientInfo = upa('clients', 'fetch', array($this->_projectInfo['client']));
        $this->_projectNotes = checkArrayOrEmpty(json_decode($this->_projectInfo['project_notes'], JSON_FORCE_OBJECT));

        //$this->_subclientInfo = upa('subClients', 'fetch', array($this->_projectInfo['subclient']));
        $this->_subclientInfo = array();

        //create repstack
        $this->_repStack['project_generated'] = date('d-m-Y');
        $this->_repStack['project_reference'] = $this->_projectInfo['reference'] . '.' . $this->_projectInfo['revision'] . '.' . $this->_projectInfo['print_version'];        
        $this->_repStack['project_name'] = $this->_projectInfo['project_name'];


        //project name and reference are not equal, either MAZ set a name, or the client did (i.e.: coming from portal )
        if(trim( $this->_projectInfo['project_name']) !== trim( $this->_projectInfo['reference']))
        {            

            //if string is longer then 46 characters, trim it
            //if(strlen($this->_projectInfo['project_name']) > 46){
                //$this->_repStack['shown_client_reference'] = substr($this->_projectInfo['project_name'], 0, 43) . '...';
            //} else{
              
            //}
            $this->_repStack['shown_client_reference'] = $this->_projectInfo['project_name'];
            
            
            $this->_repStack['show_client_reference'] = '';
        }

        else
        {
            $this->_repStack['shown_client_reference'] = '';
            $this->_repStack['show_client_reference'] = 'display: none';
        }


        $this->_repStack['project_client'] =   customerIdToName($this->_projectInfo['client']);
        $this->_repStack['project_subclient'] =  '';
        $this->_repStack['project_revision'] = $this->_projectInfo['revision'];
                          
        $innocDate = upa('samples', 'getSampleInnoculationDate', array($this->_projectId), False);

        if($innocDate == False){
            if($this->_reportIsEnglish == True){
                $this->_repStack['project_inzet'] =  'Unknown';
            } else{
                $this->_repStack['project_inzet'] =  'Onbekend';
            }
        } else{
            $this->_repStack['project_inzet'] =  date('d-m-Y', $innocDate);
            $this->_repStack['project_tijd_inzet'] =  date('H:i', $innocDate);
        }


        $projectCustomFields = json_decode($this->_projectInfo['custom_fields']);

        foreach($projectCustomFields as $pcf => $pcv){
            $this->_repStack[$pcf] = $pcv;
        }

        foreach($this->_clientInfo as $cf=>$cv){
            $this->_repStack['client_' . $cf] = $cv;
        }

        foreach($this->_subclientInfo as $scf => $scv){
            $this->_repStack['subclient_' . $scf] = $scv;
        }

        if(array_key_exists('project_monster',$this->_repStack)){
            if($this->_repStack['project_monster'] == 'Onbekend' && $this->_reportIsEnglish){
                $this->_repStack['project_monster'] = 'Unknown';
            }
        }


        //inject extension fields
        $extraArr = json_decode($this->_projectInfo['project_extra'], JSON_FORCE_OBJECT);
        if(is_array($extraArr)){
            foreach($extraArr as $extrakey => $extravalue){
                $this->_repStack[$extrakey] = $extravalue;
                $this->_projectExtra[$extrakey] = $extravalue;

                //fetch full info for project wide sampling
                if($extrakey == 'sample_method'){
                    $method = upa('sampleProcedures', 'fetch', array($extravalue), False);
                    if($method != False){
                        $fields = json_decode($method['fields'], True);
                        $this->_repStack['global_sampling_by'] = $fields['door'];
                        $this->_repStack['global_sampling_ref'] = $fields['internrefnummer'];
                    }
                }
            }
        }

     
    }


    private function _startPDF($landscape = false){
        //header('Content-Type: application/pdf');
        require_once( ROOT . '/library/mpdf/mpdf.php');


        //normal report
        if($landscape == False){
            //$this->_pdfHandle = new mPDF('utf-8','A4','7','arial',10,10,10,13,5,5, 'P');
            $this->_pdfHandle = new mPDF('utf-8','A4','7','arial',10,10,35,50,5,5, 'P');
        } else{
            $this->_pdfHandle = new mPDF('utf-8','A4-L','8','arial',2,2,35,40,5,2, 'L');
        }

        //reset timezone induced by mpdf
        date_default_timezone_set('Europe/Amsterdam');
        $this->_pdfHandle->debug = true;
        $this->_pdfHandle->SetProtection(array('copy','print'), '', 'MAZPassword');
        $this->_pdfHandle->AliasNbPages('[pagetotal]');
    }

    private function _loadHeaderFooter(){
        $repStack = array();
        $repStack['logo'] =  ROOT . '/app/docgen/res/followLogo.jpg';
        $repStack['follow_logo'] = ROOT . '/app/docgen/res/followLogo.jpg';
        $repStack['rva_logo'] = ROOT . '/app/docgen/res/rva.jpeg';

        $waterMarkText = '';

        if($this->_projectInfo['auth_status'] == 0){

          $waterMarkText = 'NIET GEAUTHORISEERD';

          if($this->_reportIsPreview == True || $this->_reportIsPreviewTemp == True){

            if($this->_reportIsPreview == True){
                $waterMarkText = 'VOORBEELD - NIET GEAUTHORISEERD';
            }

            if( $this->_reportIsPreviewTemp == True){
                $waterMarkText = 'VOORLOPIG RAPPORT VOORBEELD - NIET GEAUTHORISEERD';
            }
          }

          $this->_pdfHandle->showWatermarkText = true;
          $repStack['usr_sig'] = ROOT . '/public/img/blank.jpg';

        }

        if($this->_projectInfo['auth_status'] == 1){

              if($this->_reportIsPreview == True){
                  $waterMarkText = 'VOORBEELD - NIET VOOR DISTRIBUTIE';
                  $this->_pdfHandle->showWatermarkText = true;
              }

              if($this->_reportIsPreviewTemp == True){
                  $waterMarkText = 'TIJDLIJK RAPPORT VOORBEELD - NIET VOOR DISTRIBUTIE';
                  $this->_pdfHandle->showWatermarkText = true;
              }

              if($this->_reportIsEnglish == True){
                  $signatureFilePath = ROOT. '/app/private/signatures/' . $this->_projectInfo['auth_by'] . 'En.png';
              } else{
                  $signatureFilePath = ROOT. '/app/private/signatures/' . $this->_projectInfo['auth_by'] . '.png';
              }


              if(file_exists($signatureFilePath)){
                  $repStack['usr_sig'] = $signatureFilePath;
              } else{
                  $repStack['usr_sig'] = ROOT . '/public/img/handtekening_fout.jpeg';
              }

        }

        $this->_pdfHandle->SetWatermarkText($waterMarkText);
      
        ob_start();
            include(ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/header_footer.php');
            $headFootContent = ob_get_contents();
        ob_end_clean();

        $headFootContent =  generateHTML($headFootContent, $repStack, True);
        $headFootContent =  generateHTML($headFootContent, $this->_repStack, True);

        $this->_pdfContents =  $headFootContent;
        
    }

    private function _loadStyle(){        
        $stylesheet = file_get_contents( ROOT . '/app/docgen/pdf/'. $this->_templateName .  '/style.css');        
        $this->_pdfHandle->WriteHTML($stylesheet, 1);
        return;
    }

    
    function getImg($file){
        $type = pathinfo( ROOT . '/app/docgen/res/' . $file, PATHINFO_EXTENSION);
        $data = file_get_contents(ROOT . '/app/docgen/res/' . $file);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }

    private function _prependArray($array, $prefix){

        $prefixed = array();
        foreach($array as $ak => $av){
            $prefixed[$prefix . '_' .$ak] =  $av;
        }
        return $prefixed;
    }

    private function _stripEmptyTags($input, $replace=''){
        $output = preg_replace("!\{(\w+)\}!", $replace,$input);
        return $output;
    }


    private function determineNameStrategy($exportType, $sample, $singleSample = False)
    {  
        

        if($sample === null)    //geen monster, normaal rapporteren, enkel de 1 Monster per PDF / er is maar 1 monster in order - speelt een monster door
        {            
            return 'reference';
        }

        if((int)$exportType !== 3 && $singleSample === False)  //normaal rapporteren, Uitvoer is niet 1 Monster per PDF, en singleSample is FALSE
        {
            return 'reference';
        }
        
        if((int)$sample['source'] === 1 || (int)$sample['source'] === 3) //Monster komt uit portal of CSV, gebruik omschrijving
        {            
            return 'description';
        }
                 

        if(strtolower(trim($sample['client_description'])) !== 'geen omschrijving beschikbaar' && trim($sample['client_description']) !== '' )  //MAZ heeft iets ingesteld als omschrijving, ook tonen
        {            
            return 'description';
        }
        
        
        return 'reference';
        
        
    }

    public function generateReportName($report, $full = False)
    {

        
        if($report['naming_strategy'] == 'description')
        {
            $name =  $report['reference'] . '.' . $report['revision'] . '.' . $report['print_version'] . ' - ' . $report['naming_description'];
        }

        else
        {
            $name =  $report['reference'] . '.' . $report['revision'] . '.' . $report['print_version'];
        }


        if($full)
        {
            $name = $report['client_name'] . ' - MAZ-L ' . $name; 
        }

        return stripPathForbidden(truncate($name, 200, ''));
              
    }


    public function checkTransactionIsMixed($id)
    {
        $this->render = False; 
        
        $params = array();

        $params['transaction'] = $id;
        
        $sql = "SELECT exports.*, projects.reference, projects.portal_id, clients.name AS client_name FROM `exports` 
                LEFT JOIN projects
                ON exports.project = projects.id
                LEFT JOIN clients
                ON exports.client = clients.id
                WHERE `transaction` = :transaction";
                
        $results = $this->Export->customQuery($sql, $params);      
        
        $fileStatus = []; 

        $selectedFiles = $_POST['selected_files'] ?? [];

        foreach($results as $result)
        {          
            
            $scopeOfThisReport = json_decode($result['sample_id_scope'], True);

            $reportFiles = upa('sampleFiles', 'visibleProjectFilesInReport', array($result['project'], $scopeOfThisReport), False);

            $fileHashes = array_column($reportFiles, 'hash_name');

            $hashIntersect = array_intersect($fileHashes, $selectedFiles);

            if(count($hashIntersect) > 0)
            {
                array_push($fileStatus, True);
            }

            else
            {
                array_push($fileStatus, False);
            }

        }

        if(count(array_unique($fileStatus)) > 1)
        {
            print json_encode(True);            
        }

        else
        {   
            print json_encode(False);
        }

        


    }

    public function extern($id = False, $exportableId = false){


        $params = array();
        $params['transaction'] = $id;
        
        $sql = "SELECT exports.*, projects.reference, projects.portal_id, clients.name AS client_name FROM `exports` 
                LEFT JOIN projects
                ON exports.project = projects.id
                LEFT JOIN clients
                ON exports.client = clients.id
                WHERE `transaction` = :transaction";
                
        $results = $this->Export->customQuery($sql, $params);        
        
        if(!$results){
            print('Error: Kon rapport-transactie database rij niet vinden.');
            die();            
        }


        //grab the template off of the first results row
        $defaultLang = 'nl';
        $templateName = $results[0]['template'];        
        if(substr($templateName, -2) == "En")
        {
            $defaultLang = 'en';                      
        } 

                
        //set a lock
        //$lockObject = upa('keyrings', 'requestLockAndStatus', array('EXTERN', $id), False);
        $lockObject = upa('keyrings', 'requestLockAndStatus', array('EXPORT', $results[0]['project']), False);
        $this->_template->set('lock_id', $lockObject['lock_id']);

        if($lockObject['locked'] == True){
          $this->_template->set('hideLock', '');
          $this->_template->set('hideExport', 'hidden');
          $this->_template->set('locked_by_name', $lockObject['locked_by_name']);
          $this->_template->set('locked_by_avatar', $lockObject['locked_by_avatar']);
        } else{
          $this->_template->set('hideLock', 'hidden');
          $this->_template->set('hideExport', '');
        }


        $this->_template->set('zip_download_show', 'hide');

        if(count($results) > 1){
            $this->_template->set('zip_download_show', '');            
        }
        
        $selectorBox = false;
        $firstReport = false;
        $outputBox = '';
        $hasUnauth = false;        
        $sampleIds = [];

        //$reportFileStatus = [];

        foreach($results as $result)
        {            
                                           
            $name = $this->generateReportName($result, false);

            if($result['was_authorized'] == '0'){
                $hasUnauth = true;
            }
                    
            if($selectorBox === false){
                $firstReport = $result['id'];                                
                $selectorBox = '<option value="' . $result['id'] . '" selected="selected">' . $name . ' </option>';
            } else{
                $selectorBox .= '<option value="' . $result['id'] . '">' . $name . ' </option>';
            }
           
            
            $filename = ROOT . $this->_storagePath . $result['hash'] . '.pdf';         
         
            $reportExists = file_exists($filename);
            
            if(!$reportExists){
                print('Error: Kon rapport PDF niet vinden in reportStorage ');
                die();
            }

            $scopeOfThisReport = json_decode($result['sample_id_scope'], True);

            //add to the sampleIds
            $sampleIds = array_merge($sampleIds, $scopeOfThisReport ?? []);

        }
                                            
        
        $email = generateHTML('export/email_nl', array());

        $orphanedAttachments = [];

        //check if the project samples have any attachments. 
        if(count($sampleIds) == 0)
        {   
            //backwards compat for when exports did not have a sample scope
            $attachments = upa('sampleFiles', 'visibleProjectFiles', array($results[0]['project']), False);            
        }

        else
        {
            $overallAttachments = upa('sampleFiles', 'visibleProjectFiles', array($results[0]['project']), False);            
            $attachments = upa('sampleFiles', 'visibleProjectFilesInReport', array($results[0]['project'], $sampleIds), False);
            
            //get elements that are in overallAttachments but not in attachments
            $orphanedAttachments = array_udiff($overallAttachments, $attachments, function ($a, $b) {
                return strcmp($a['hash_name'], $b['hash_name']);
            });

            
        }

        
        
        
        $isMixedAttachment = False; 
            
        $clientInfo = upa('clients', 'fetch', array($result['client']), False);
        
        $preferences = $this->translateReportingPreferences($results[0]['portal_id'], $clientInfo);
                
        $clientContactGroups = upa('contactGroups', 'generateGroupList', array($result['client'], $preferences['activated_contact_groups']), False);                               
        $this->_template->set('first_report', $firstReport);
        $this->_template->set('transaction_id', $result['transaction']);
        $this->_template->set('selector_box', $selectorBox);
        $this->_template->set('client_groups', $clientContactGroups);
        $this->_template->set('export_id', $id);        
        $this->_template->set('hash', $result['hash']);
        $this->_template->set('file', $filename);
        $this->_template->set('client_id', $result['client']);
        $this->_template->set('project_id', $results[0]['project']);
        
        //$this->_template->set('language', $preferences['language']);
        //04-03-2026: Changed this because Roy wants the language to be based on what was generated
        //rather then what was set as preference, because the latter can change after generation, and then the email would be in the wrong language.
        $this->_template->set('language', $defaultLang);
        


        $table = new tableFactory();
        $table->setTableId('sampleFileExportListing');
        $table->loadTemplate('sampleFileExportListing');

        $doesThisHaveAttachments = (empty($attachments) ? 'reports' : 'report_with_files');
        
        foreach($attachments as $idx => $attachment)
        {
            $attachments[$idx]['sent_label'] = 'Nog niet verstuurd';

            $previouslySentCount = upa('fileHistories', 'previouslySentCount', array($attachment['id']), False);                 

            if($previouslySentCount > 0)
            {
                $attachments[$idx]['sent_label'] = 'Verstuurd, ' . $previouslySentCount . ' keer';
                
                $attachments[$idx]['sent_count'] = $previouslySentCount;
            }


            
        }
                
        $fileToolTipClasses = '';

        if(empty($attachments)){
            $attachments = 'Geen zichtbare bestanden gevonden';
            $fileToolTipClasses = 'hidden';
        }

        
        

        $table->loadValues($attachments);
        
        $this->_template->set('attachment_table', $table->renderTable());
        $this->_template->set('file_tool_tip', $fileToolTipClasses);

        //lets repeat for orphaned files
        $table = new tableFactory();
        $table->setTableId('orphanedSampleFileExportListing');
        $table->loadTemplate('sampleFileExportListing');

        foreach($orphanedAttachments as $idx => $attachment)
        {
            $orphanedAttachments[$idx]['sent_label'] = 'Nog niet verstuurd';

            $previouslySentCount = upa('fileHistories', 'previouslySentCount', array($attachment['id']), False);                 

            if($previouslySentCount > 0)
            {
                $orphanedAttachments[$idx]['sent_label'] = 'Verstuurd, ' . $previouslySentCount . ' keer';
                
                $orphanedAttachments[$idx]['sent_count'] = $previouslySentCount;
            }
            
        }

        $orphanFileToolTipClasses = ''; 

        if(empty($orphanedAttachments)){
            $orphanedAttachments = 'Geen zichtbare bestanden gevonden';
            $orphanFileToolTipClasses = 'hidden';
        }


        $table->loadValues($orphanedAttachments);
        
        $this->_template->set('orphaned_attachment_table', $table->renderTable());
        
        $this->_template->set('orphan_file_tool_tip', $orphanFileToolTipClasses);


        
        //reports without attachment settings
        $emailSettings = upa('emailTemplates', 'renderSelector', array('reports'), False);            
        $emailSettingsAtt = upa('emailTemplates', 'renderSelector', array('report_with_files'), False);
        $emailSettingsFiles = upa('emailTemplates', 'renderSelector', array('for_files'), False);
                
        $this->_template->set('email_att', $emailSettingsAtt['selected']);
        $this->_template->set('email_options_att', $emailSettingsAtt['options']);
        $this->_template->set('email_files', $emailSettingsFiles['selected']);
        $this->_template->set('email_options_files', $emailSettingsFiles['options']);
        
               
        //$emailSettings = upa('emailTemplates', 'renderSelector', array($doesThisHaveAttachments), False);
        $this->_template->set('bcc', $emailSettings['selected_bcc']);
        $this->_template->set('email', $emailSettings['selected']);
        $this->_template->set('email_options', $emailSettings['options']);
        $this->_template->set('attachment_hider', (empty($attachments) ? 'hide' : ''));

        

        $otherVersions = upa('exports', 'projectReportOverviewCondensed', array($results[0]['project'], True), False);        
        $this->_template->set('other_versions',$otherVersions);

        if($hasUnauth === true){
            $this->_template->set('email_hider', 'hide');
            $this->_template->set('block_hider', '');
        } else{
            $this->_template->set('email_hider', '');
            $this->_template->set('block_hider', 'hide');
        }


        
    }

    public function exportContentViewer($id){
        $this->render = False;
        
        //$this->Export->where('id', $id);
        //$this->Export->limit(1);
        //$result = $this->Export->search();

        $sql = "SELECT exports.*, projects.reference, clients.name AS client_name FROM `exports` 
        LEFT JOIN projects
        ON exports.project = projects.id
        LEFT JOIN clients
        ON exports.client = clients.id
        WHERE `exports`.`id` = :id";

        $params = array();
        $params['id'] = $id; 

        
        $result = $this->Export->customQuery($sql, $params);    

        if(!$result){
            print('Error: Kon export database rij niet vinden.');
            die();            
        }


        $result = $result[0];

        //$filename = ROOT . $this->_storagePath . $result['hash'] . '.pdf';
        //$reportExists = file_exists($filename);

        $sfilename = ROOT . $this->_storagePath . $this->createHashWithFileName($result['hash'],  $result['type']);
        $zipUnfold = False; 

        if(!file_exists($sfilename))
        {
            $sfilename = ROOT . $this->_storagePath . $this->createHashWithFileName($result['hash'],  '1');
            $result['type'] = 1; //force to PDF instead of zip.
            
        }

        else
        {
            //it was actually a zip document. 
            $zipUnfold = True;
        }

        

        if(!file_exists($sfilename))    //couldnt find a hit for either zip or pdf.. aborting
        {
            print('Error: Kon rapport PDF of ZIP niet vinden in opslag medium. <br />');
            print('Bestands naam: ' . $sfilename );
            die();
        }

          
        // if(!$reportExists)
        // {
        //     print('Error: Kon rapport PDF niet vinden in opslag medium. <br />');
        //     print('Bestands naam: ' . $filename );
        //     die();
        // }

        if($result['type'] == '3' && $result['transaction'] == null)
        {
            print('Zip bestand.');
            die();
        }

                
        $clientName = customerIdToName($result['client']);

        $reportInfo = upa('projects', 'fetch', array($result['project']), False);
        $trueReference = $reportInfo['reference'];
        $downloadName = $this->generateReportName($result, True);        

        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($sfilename));
        header("Content-Disposition: inline; filename=\"" . fileNameFilter($downloadName) . '.pdf' . "\"");
        readfile($sfilename);
                
    }

    public function documentMailer(){

        $this->render = False;                

        if(!isset($_POST['includedSampleFileHashes']) || empty($_POST['includedSampleFileHashes'])){
            print json_encode(array('message' => 'empty'));
            return;
        }

        $project  = (new Project)->find($_POST['project']);        
        
        $touchedHashes = []; 

        $receivers = [];
        if(isset($_POST['receivers'])){
            $receivers = $_POST['receivers'];
        } 

        $groups = upa('portal', 'getContactLists', array($project['client']), False);      

        $groups = json_decode($groups, JSON_FORCE_OBJECT);              
        $groups = array_column($groups, null, 'id');

        $extra = $_POST['extraReceiver'];
        $cc = $_POST['cc'];
        $bcc = $_POST['bcc'];

        $groupNames = null;

        foreach($receivers as $receiver)
        {                        
            $thisName = checkKeyOrBlank($groups, $receiver, 'name');
            if($groupNames == null)
            {
                $groupNames = $thisName;
            }

            else
            {
                $groupNames = $groupNames . ';' . $thisName;
            }                        
        }        

        $groupedBySample = upa('sampleFiles', 'groupSampleHashesBySample', array($_POST['includedSampleFileHashes']), False);
        
        

        foreach($groupedBySample as $sample => $fileHashes)
        {
            upa('portal', 'documentMailer', array(
                $receivers,
                $_POST['extraReceiver'],
                $_POST['cc'],
                $_POST['bcc'],
                $_POST['message'],
                $project['client'],                
                $sample,
                $project['id'],
                $fileHashes
            ), False);

            
            //update hashes to "been sent"
            upa('sampleFiles', 'setAsPreviouslySent', array($fileHashes), False);

            //get the name of the files for the event notification                    
            //$applicableFileHashNames = upa('sampleFiles', 'hashArrayToNames', array($fileHashes), False);

            $filesThatWereSent = upa('sampleFiles', 'hashesToFiles', array($fileHashes), False);
            
                                 
            //$event = 'Document verstuurd naar portal-document-mailer. Contact-groepen: ' . $groupNames .', Extra ontvanger: ' . $extra .' , CC: ' . $cc .' , BCC: ' . $bcc . '. Bestanden verstuurd: ' . implode(',', $applicableFileHashNames);
            foreach($filesThatWereSent as $file)
            {
                upa('fileHistories', 'saveFileSentWithSender', array($file, NULL, array('groups' => $groupNames, 'extra' => $extra) ), False); 
            }

            $event = 'Document verstuurd naar portal-document-mailer';
            upa('changeTracker', 'changed', array(21,$project['id'], False, False, $event, False, False), False);
    
        }
            
        print json_encode(array('message' => 'ok'));

    }

    public function mailer(){
        
        $this->render = False;                
       
        $touchedHashes = []; 
        $orphanedSampleFileHashes = $_POST['includeOrphanedSampleFiles'] ?? [];

        $params = array();

        $params['transaction'] = $_POST['transaction'];
        
        $sql = "SELECT exports.*, projects.reference, clients.name AS client_name FROM `exports` 
                LEFT JOIN projects
                ON exports.project = projects.id
                LEFT JOIN clients
                ON exports.client = clients.id
                WHERE `transaction` = :transaction";
                
        $results = $this->Export->customQuery($sql, $params);        
                        
        $receivers = [];
        if(isset($_POST['receivers'])){
            $receivers = $_POST['receivers'];
        } 

        $reportIn = array();
        if(!empty($_POST['selectedReports'])){
            $reportIn = $_POST['selectedReports'];
        }

        $groups = null;
        
        $notificationReports = array_column($results, 'hash');
        $failuresEncountered = '';
        $clientId = NULL;

        //drop out if something was not authorized
        $authStatus = array_column($results, 'was_authorized');
        if(in_array('0', $authStatus)){                
            return;
        }                                


        foreach($results as $report){

            $trueFileName = $this->generateReportName($report, True);

            if(filter_var($_POST['exportAll'], FILTER_VALIDATE_BOOLEAN) === false){                
                
                if(!in_array($report['id'], $reportIn)){
                    continue;
                }
            }

            $pdfPath =  ROOT . $this->_storagePath . $report['hash'] . '.pdf';
            $reportExists = file_exists($pdfPath);

            if($reportExists){

                $md5 = md5_file($pdfPath);                    
                $data = file_get_contents($pdfPath);
                $base64 = base64_encode($data);                            
                $reportInfo = upa('projects', 'fetch', array($report['project']), False);
                $trueReference = $reportInfo['reference'];
                $temporary = $report['temporary'];
                $clientId = $reportInfo['client'];

                //applicable sampleHashes
                $sampleHashesToInclude = $_POST['includeSampleFiles'] ?? [];     
                
                $applicableFileHashes = [];

                $sampleScope = null;

                //does this export have a sample id set? 
                if($report['sample_id_scope'] != null)
                {
                    $sampleScope = json_decode($report['sample_id_scope'], True);
                }

                //fall back on single sample id 
                if($report['sample_id_scope'] == null )
                {
                    if($report['sample_id'] != null)
                    {
                        $sampleScope = [$report['sample_id']];
                    }
                }

                $filesInThisReport = upa('sampleFiles', 'visibleProjectFilesInReport', array($report['project'], $sampleScope ?? []), False);

                $fileHashesInThisReport = array_column($filesInThisReport, 'hash_name');
                
                $applicableFileHashes = array_intersect($fileHashesInThisReport, $sampleHashesToInclude);

                //filesThat were Sent
                //create array be filtering the filesInThisReport based on applicableFileHashes
                $filesThatWereSent = array_filter($filesInThisReport, function($file) use ($applicableFileHashes){
                    return in_array($file['hash_name'], $applicableFileHashes);
                });
                                    
                if($groups === null)
                {
                    $groups = upa('portal', 'getContactLists', array($clientId), False);      
                    $groups = json_decode($groups, JSON_FORCE_OBJECT);              
                    $groups = array_column($groups, null, 'id');
                }
                
                $response = upa('portal', 'reportMailer', array(
                    $receivers,
                    $_POST['extraReceiver'],
                    $_POST['cc'],
                    $_POST['bcc'],
                    (count($applicableFileHashes) > 0 ? $_POST['messageAtt'] : $_POST['message']),                    
                    $base64,
                    $md5,
                    $reportInfo['client'],
                    $report['hash'],
                    $report['project'], 
                    $report['report_reference'], 
                    $report['date'], 
                    $report['revision'], 
                    $report['print_version'], 
                    $trueReference,
                    $temporary,
                    $trueFileName,
                    $applicableFileHashes
                ), False);

                foreach($applicableFileHashes as $hash)
                {
                    $touchedHashes[] = $hash;
                }

                $response = json_decode($response, JSON_FORCE_OBJECT);
                if(array_key_exists('message', $response) && $response['message'] !== 'ok'){
                    $failuresEncountered = $failuresEncountered . ';' . $trueReference;
                } else{

                    $extra = $_POST['extraReceiver'];
                    $cc = $_POST['cc'];
                    $bcc = $_POST['bcc'];

                    $groupNames = null;


                    foreach($receivers as $receiver)
                    {                        
                        $thisName = checkKeyOrBlank($groups, $receiver, 'name');
                        if($groupNames == null)
                        {
                            $groupNames = $thisName;
                        }

                        else
                        {
                            $groupNames = $groupNames . ';' . $thisName;
                        }                        
                    }

                    //update hashes to "been sent"
                    upa('sampleFiles', 'setAsPreviouslySent', array($applicableFileHashes), False);

                    //get the name of the files for the event notification                    
                    $applicableFileHashNames = upa('sampleFiles', 'hashArrayToNames', array($applicableFileHashes), False);

                    foreach($filesThatWereSent as $file)
                    {
                        upa('fileHistories', 'saveFileSentWithReport', array($file, $report['transaction'], array('groups' => $groupNames, 'extra' => $extra) ), False); 
                    }
                    
                    

                                        
                    $event = 'Project verstuurd naar portal-mailer. Contact-groepen: ' . $groupNames .', Extra ontvanger: ' . $extra .' , CC: ' . $cc .' , BCC: ' . $bcc .'  (' .  $report['report_reference'] . '.' . $report['revision'] . '.' . $report['print_version'] . '). Bestanden verstuurd mee-verstuurd: ' . implode(',', $applicableFileHashNames);
                    upa('changeTracker', 'changed', array(21,$report['project'], False, False, $event, False, False), False);
                }
            }
        }        

        if(!empty($orphanedSampleFileHashes))
        {
            $project = (new Project)->find($results[0]['project']);
            $documentGroups = upa('portal', 'getContactLists', array($project['client']), False);
            $documentGroups = json_decode($documentGroups, JSON_FORCE_OBJECT);
            $documentGroups = array_column($documentGroups, null, 'id');
            $groupNames = null;

            foreach($receivers as $receiver)
            {
                $thisName = checkKeyOrBlank($documentGroups, $receiver, 'name');
                if($groupNames == null)
                {
                    $groupNames = $thisName;
                }

                else
                {
                    $groupNames = $groupNames . ';' . $thisName;
                }
            }

            $groupedBySample = upa('sampleFiles', 'groupSampleHashesBySample', array($orphanedSampleFileHashes), False);

            foreach($groupedBySample as $sample => $fileHashes)
            {
                upa('portal', 'documentMailer', array(
                    $receivers,
                    $_POST['extraReceiver'],
                    $_POST['cc'],
                    $_POST['bcc'],
                    $_POST['messageFiles'],
                    $project['client'],
                    $sample,
                    $project['id'],
                    $fileHashes
                ), False);

                upa('sampleFiles', 'setAsPreviouslySent', array($fileHashes), False);

                $filesThatWereSent = upa('sampleFiles', 'hashesToFiles', array($fileHashes), False);
                foreach($filesThatWereSent as $file)
                {
                    upa('fileHistories', 'saveFileSentWithSender', array($file, NULL, array('groups' => $groupNames, 'extra' => $_POST['extraReceiver']) ), False);
                }

                $event = 'Document verstuurd naar portal-document-mailer';
                upa('changeTracker', 'changed', array(21,$project['id'], False, False, $event, False, False), False);
            }
        }
        
        //dispatch notification job 
        if(count($results) !== 0){
            $response = upa('portal', 'reportMailerNotificationDispatch', array(
                $notificationReports, $clientId
            ), False);
        }
        
        
        $status = (empty($failuresEncountered) ? True: False);
        print json_encode(['status' => $status, 'message' => $failuresEncountered]);
    }

}
