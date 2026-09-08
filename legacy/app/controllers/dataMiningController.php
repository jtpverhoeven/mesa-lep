<?PHP

class dataMiningcontroller extends controller{

  protected $csv = False;
  protected $projects = array();
  protected $samples = array();
  protected $clientInfo = array();
  protected $clientId;
  protected $assaySelection = array();
  protected $analysis = array();
  protected $assayInfo = array();
  protected $assayColCoord = array();
  protected $samplingMethods = array();
  protected $version = 'v1';
  protected $productGroups = array(); 
  protected $referenceSources = array(); 

  //protected $zip;
  //protected $users = array();

  //protected $assays = array();
  //protected $zipFileName;

  function beforeAction($queryString) {
    $this->_template->set('MESA_LIMS_ACTIVE', 'active');
    $this->_template->set('MESA_SOCIAL_ACTIVE', '');
    $this->_template->set('MESA_ADMIN_ACTIVE', '');
  }

  private function loadProductGroupNames($client)
  {
    
      $productGroupOptions = upa('productGroups', 'productGroupsArray', array($client), False);

      $this->productGroups = $productGroupOptions;
    
  }

  private function loadReferenceSources()
  {
    
      $sources = upa('referenceSources', 'list', array(False), False);

      $this->referenceSources = $sources;      
  
  }

  public function deauth()
  {

  }

  public function deauthExport()
  {

    $this->render = False; 

    $this->seperator = $_POST['seperator'];
    
    $this->startCSV();    

    $start = DateTime::createFromFormat('d-m-Y', $_POST['startDate']); 
    $end = DateTime::createFromFormat('d-m-Y', $_POST['endDate']); 

    $project = new Project; 

    $project->greaterThan('project_date', $start->getTimestamp()); 
    $project->lessThan('project_date', $end->getTimestamp()); 
    $project->greaterThanHard('revision', 1);

    $projects = $project->search();

    $ids = array_column($projects, 'id');

    $idMap = implode(',', array_map('intval', $ids));        

    $sql = 'SELECT * FROM changetracker WHERE `type` = 9 AND `from` = 1 AND `project` IN (' . $idMap . ') ORDER BY id desc';
  
    $changetrack = $this->DataMining->customQuery($sql, array());    
    $changeReasons = array(); 

    $this->fputcsv_eol($this->csv,[ 'project', 'revisie', 'oorzaak', 'reden', 'project notities', 'monster notities--> ' ], ',' , "\"" );
  

    foreach($changetrack as $idx=>$change)
    {


      if(!array_key_exists($change['project'], $changeReasons))
      {
        $changeReasons[$change['project']] = array();        
      }

      $eventExplode = explode('reden:', $change['event']);
      $reason = array();      

      if(count($eventExplode) < 2)
      {
        $reason['type'] = 'NTB';
        $reason['comment'] = $change['event'];
      }

      else
      {
        $reason['type'] = (strpos(strtolower($eventExplode[0]), 'intern') !== false) ? 'Intern (oorzaak/fout bij MAZ)' : 'Extern (oorzaak/fout bij klant)';                  
        $reason['comment'] = $eventExplode[1];
      }

      //array_push($changeReasons[$change['project']], $reason);
      array_unshift($changeReasons[$change['project']], $reason);
        
    }

    

    foreach($projects as $project)
    {

      
      for($i = 2; $i <= (int)$project['revision']; $i++)
      {
      
        $thisReason = checkKeyOrFalse($changeReasons, $project['id'], ($i -2));        
        $comments = json_decode($project['project_notes'], JSON_FORCE_OBJECT);

        $row = [
          $project['reference'],
          $i,                      
          ($thisReason) ? $thisReason['type'] : 'Geen reden bekend',
          ($thisReason) ? $thisReason['comment'] : 'Geen reden bekend',
          checkKeyOrBlank($comments, 'project')
        ]; 

        if(is_array($comments)){
          
          foreach($comments as $idx=> $comment)
          {
            if($idx == 'project')
            {
              continue; 
            }
  
  
            array_push($row, '[' . $idx . '] ' . $comment );
  
          }
        }


                  
        $this->fputcsv_eol($this->csv,$row, ',' , "\"" );
      }
      
    }

    
    header('Content-Encoding: UTF-8');
    header('Content-type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"test.csv\"");

    rewind($this->csv);
    print "\xEF\xBB\xBF"; // UTF-8 BOM
    print stream_get_contents($this->csv);
    fclose($this->csv);
        
    
  }

  public function export($id = False){
    $sForm = new formFactory($this->_controller);
    $sForm->setId('sampleForm');
    $sForm->action('#');
    $sForm->method('POST');
    $sForm->addClass('');
    $sForm->setTemplate('generic');
    $sForm->returnAsFieldArray();

      $project = array();
      if($id != False){
        $project = upa('projects', 'fetch', array($id), false);      
      }

      $client =  checkKeyOrBlank($project, 'client');

      //client & project
      $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', checkKeyOrBlank($project, 'client'), False,
          array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

      $sForm->addInputField('project_selector', False, 'text', 'select2-input select2-default input-block-level', checkKeyOrBlank($project, 'id'), False,
          array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

      $sForm->addInputField('date_start', False, 'text', 'input-block-level', False, False, False, False);
      $sForm->addInputField('date_end', False, 'text', 'input-block-level', False, False, False, False);

      $exportTypes = array();
      $exportTypes['all'] = 'Alle analyses (excl. RODAC en Legionella)';
      $exportTypes['selection'] = 'Selectie van analyses';
      $exportTypes['legionella'] = 'Legionella';
      $exportTypes['rodac'] = 'RODAC';

      $sForm->addDropdownField('export_selector', False, 'input-block-level', False, $exportTypes, False, False);

      $multiSelect = generateHTML('datamining/assayselector', array('current_value' => ''));
      $sForm->addPureHTML('assay_selector', $multiSelect);

      $formats = array();
      //$formats['short'] = 'Beknopt (standaard)';
      //$formats['long'] = 'Uitgebreid';
      $formats['advanced'] = 'Geavanceerd';

      //labojuice switch 
      $default = 'advanced';
      if($client == 68){
        $default = 'advanced';
      }

      $sForm->addDropdownField('export_format', False, 'input-block-level',  $default, $formats, False, False);

      $hideOptions = array();
      $hideOptions['0'] = 'Nee, hou verborgen (standaard)';
      $hideOptions['1'] = 'Ook uitvoeren';

      $sForm->addDropdownField('show_hidden', False, 'input-block-level', False, $hideOptions, False, False);


      $scopeOptions = array();
      $scopeOptions['date'] = 'Bemonster datum bereik';
      $scopeOptions['date_arrival'] = 'Ontvangst datum bereik';
      $scopeOptions['project'] = 'Specifiek project';

      $defaultScope = 'date';
      if($id != False){
        $defaultScope = 'project';
      }
      
      $sForm->addDropdownField('scope_selector', False, 'input-block-level', $defaultScope, $scopeOptions, False, False);
      
      $sForm->addInputField('seperator', False, 'text', 'input-block-level', ';', False, False, False);


      $versions = array();
      $versions['v1'] = 'Versie 1';
      $versions['v2'] = 'Versie 2: Voegt Type onderzoek, Productgroep &amp; referentiewaarden toe';

      $defaultVersion = 'v2';
      

      $sForm->addDropdownField('version_selector', False, 'input-block-level', $defaultVersion, $versions, False, False);
      
      $formFields = $sForm->render();

      $this->_template->setByArray($formFields);

      $this->_template->set('old_version_clients', CLIENT_USES_OLD_EXPORT);
    }

    public function doExport(){
      $this->render = False;      
      
      //store var
      $this->clientId = $_POST['client_name'];
      $this->dateStart = $_POST['date_start'];
      $this->dateEnd = $_POST['date_end'];
      $this->exportType = $_POST['export_selector'];
      $this->showHidden = $_POST['show_hidden'];
      $this->format = $_POST['export_format'];
      $this->scopeSelector = $_POST['scope_selector'];
      $this->projectId = $_POST['project_selector'];
      $this->seperator = $_POST['seperator'];
      $this->version = $_POST['version_selector'];

      //set assay selection
      if($this->exportType == 'selection'){
        $this->assaySelection = explode(',', $_POST['assay_selector']);
      }
      

      //grab client
      $this->clientInfo = upa('clients', 'fetch', array($this->clientId), False);

      //get applicable projects
      $this->projects = $this->loadProjects($this->clientId, $this->scopeSelector, $this->dateStart,$this->dateEnd, $this->projectId);

      
      //grab samples from these projects
      $this->samples = $this->loadSamples();

      if(empty($this->samples)){
        print 'Deze export opdracht leverde geen uitvoerbare monsters op.';
        return;
      }

      $metaInfo = $this->loadMeta();

      $this->meta = $metaInfo[0];
      $this->metaColumns = $metaInfo[1];


      //grab analysis for these samples and analysis information
      $this->analysis = $this->loadAnalysisRows();      

      

      $this->sortedAnalysis = $this->sortAnalysisRows();

        
      $this->loadReferenceSources();

      $this->assayInfo = $this->loadAssayInfo($this->format);

      $this->loadSamplingMethods();    
      
      $this->startCSV();    

      $this->loadProductGroupNames($this->clientId);      

      if($this->format == 'advanced'){

        $this->renameAssayArray();        

        if($this->version == 'v2'){
          
          $this->writeAdvancedHeaderV2();
          $this->writeAdvancedResultsV2();
        } else{
          $this->writeAdvancedHeader();
          $this->writeAdvancedResults();
        }
        
        

        
        //$this->writeAdvancedHeader();
        //$this->writeAdvancedResults();
      } else{        
        dd('This function has been deprecated');

        //01-05-2023 - JV
        //i think these can not longer be accessed.. 
        //we hardcoded the advanced format.         
        //$this->writeAssayBlock();
        //$this->writeResults();
      }


      header('Content-Encoding: UTF-8');
      header('Content-type: text/csv; charset=UTF-8');
      header("Content-Disposition: attachment; filename=\"test.csv\"");

      rewind($this->csv);
      print "\xEF\xBB\xBF"; // UTF-8 BOM
      print stream_get_contents($this->csv);
      fclose($this->csv);
    }


    private function writeAdvancedHeader(){

        $headers = array();
        $headers[0] = 'Opdrachtgever';
        $headers[1] = 'Monstername door';
        $headers[2] = 'Datum bemonstering';
        $headers[3] = 'Datum ontvangst';
        $headers[4] = 'Datum start analyse';
        $headers[5] = 'Referentie opdrachtgever';
        $headers[6] = 'Referentie MAZ';
        $headers[7] = 'Monsternummer';
        $headers[8] = 'Omschrijving opdrachtgever';
        $headers[9] = 'Monster details';        
        
        foreach($this->metaColumns as $metaColumn){
          $i = count($headers);        
          $headers[$i] = $metaColumn;
        }

        $rowNames = array();
        $rowNames[0] = 'Analyse';        
        $rowNames[1] = 'Eenheid';
        $rowNames[2] = 'Techniek';
        $rowNames[3] = 'Geaccrediteerde verrichting';        
        $rowNames[4] = 'Conformiteit';
        $rowNames[5] = 'Referentiemethode';

        foreach($rowNames as $idx => $rowName){
          $i = count($headers);        
          $headers[$i] = $rowName;
        }

        $results = array();
        $results[0] = 'kleiner dan';
        $results[1] = 'groter dan';
        $results[2] = 'resultaat';
        $results[3] = 'indicatief';
        $results[4] = 'bevestigd';
              
        foreach($results as $idx => $resultRowName){
          $i = count($headers);        
          $headers[$i] = $resultRowName;
        }
        

        $this->fputcsv_eol($this->csv,$headers, ',' , "\"" );
    }


      private function writeAdvancedHeaderV2(){



        $headers = array();
        $headers[0] = 'Opdrachtgever';
        $headers[1] = 'Monstername door';
        $headers[2] = 'Datum bemonstering';
        $headers[3] = 'Datum ontvangst';
        $headers[4] = 'Datum start analyse';

        $headers[5] = 'Type onderzoek';
        $headers[6] = 'Productgroep';

        $headers[7] = 'Referentie opdrachtgever';
        $headers[8] = 'Referentie MAZ';
        $headers[9] = 'Monsternummer';
        $headers[10] = 'Omschrijving opdrachtgever';
        $headers[11] = 'Monster details';        

        
        foreach($this->metaColumns as $metaColumn){
          $i = count($headers);        
          $headers[$i] = $metaColumn;
        }

        $rowNames = array();
        $rowNames[0] = 'Analyse';        
        $rowNames[1] = 'Eenheid';
        $rowNames[2] = 'Techniek';
        $rowNames[3] = 'Geaccrediteerde verrichting';        
        $rowNames[4] = 'Conformiteit';
        $rowNames[5] = 'Referentiemethode';

        foreach($rowNames as $idx => $rowName){
          $i = count($headers);        
          $headers[$i] = $rowName;
        }

        $results = array();
        $results[0] = 'kleiner dan';
        $results[1] = 'groter dan';
        $results[2] = 'resultaat';
        $results[3] = 'indicatief';
        $results[4] = 'bevestigd';
        $results[5] = 'Referentiewaarde';
        $results[6] = 'Referentie bron';
              
        foreach($results as $idx => $resultRowName){
          $i = count($headers);        
          $headers[$i] = $resultRowName;
        }
        

        $this->fputcsv_eol($this->csv,$headers, ',' , "\"" );
    }

    private function renameAssayArray(){
      $sorted = array_column($this->assayInfo, NULL, 'id');      
      $this->assayInfo = $sorted;      
    }

    private function writeAdvancedResults(){

    

      foreach($this->samples as $sample){
        
        $lengthAssays = count($this->assayInfo);

        $sampleAssayInfo = checkKeyOrFalse($this->sortedAnalysis, $sample['id'] );               
        
        if(!$sampleAssayInfo)
        {
          continue;
        }

        foreach($sampleAssayInfo as $thisAnalysis){
                     
            $assayInfo = checkKeyOrFalse($this->assayInfo, $thisAnalysis['assay_base']);
          
            if($assayInfo['hide_report'] == 1 && $this->showHidden == 0){                
              continue;
            }

            $assayDetails = json_decode($assayInfo['custom_fields'], JSON_FORCE_OBJECT);                                              
            $thisRow = array();

            $thisRow[0] = $this->clientInfo['name'];
            $thisRow[1] = checkKeyOrFalse($this->samplingMethods, $sample['sampling_method'], 'door');
            $thisRow[2] = checkKeyOrFalse($this->projects, $sample['project'], 'custom_fields', 'project_monster');
            $thisRow[3] = checkKeyOrFalse($this->projects, $sample['project'], 'custom_fields', 'project_ontvangst');
    
            if(empty($sample['sample_innoculated'])){
              $thisRow[4] = 'Onbekend';
            } else{
              $thisRow[4] = date('d-m-Y', $sample['sample_innoculated']);
            }
    
            if($this->exportType == 'legionella'){
              $clientRefValue = checkKeyOrFalse($this->projects, $sample['project'], 'project_extra', 'client_reference');
            } else{
              $projectReference = checkKeyOrFalse($this->projects, $sample['project'], 'reference');
              $projectName = checkKeyOrFalse($this->projects, $sample['project'], 'project_name');
              if($projectReference === $projectName){
                $clientRefValue = '';
              } else{
                $clientRefValue = $projectName;
              }
            }
    
            $thisRow[5] = $clientRefValue;
            $thisRow[6] = checkKeyOrFalse($this->projects, $sample['project'], 'reference');
            $thisRow[7] = $sample['barcode'];

            $thisRow[8] = $sample['client_description'];

            $customFields = json_decode($sample['custom_fields'], JSON_FORCE_OBJECT);
            $thisRow[9] = checkKeyOrBlank($customFields, 'details');

            $colI = 10;

            foreach($this->metaColumns as $metaColumn){
              $metaValue = checkKeyOrFalse($this->meta,  $sample['id'], $metaColumn );
              $thisRow[$colI] = $metaValue;
              $colI++;
            }
                        
            $template[0] = 'raportnaam';
            //$template[1] = 'id';
            $template[1] = 'resultin';
            $template[2] = 'techniek';
            $template[3] = 'accred';
            //$template[5] = 'internrefnummer';
            $template[4] = 'conform';
            $template[5] = 'referentiemethode';

            foreach($template as $tIdx => $tKey){
             
                
              if(array_key_exists($template[$tIdx], $assayDetails)){
                $assayRowVal = $assayDetails[$template[$tIdx]];
              } elseif(array_key_exists($template[$tIdx], $assayInfo)){
                $assayRowVal = $assayInfo[$template[$tIdx]];
              } else{
                $assayRowVal = 'NB';
              }

              array_push($thisRow, $assayRowVal);
              $colI++;                           
            }

            $storedResults = json_decode($thisAnalysis['storedResult'], JSON_FORCE_OBJECT);              
              
            if($storedResults == False){
              //forcibly get result..
              $said = checkKeyOrFalse($this->sortedAnalysis, $sample['id'], $thisAnalysis['assay_base'], 'id' );
              $storedResults = upa('results', 'msProcess', array($said), False);
            }

            $storedOutput = checkKeyOrFalse($storedResults, 'output', 'kve');
            //bit of a hack for legionella
            $aanvullingLegionella = checkKeyOrFalse($storedResults, 'output', 'aanvulling');
            $strippedOutput = $this->stripTags($storedOutput);
            
            $resultParts = $this->returnResultPartsBoolean($strippedOutput);
            
            $thisRow[$colI] = var_export($resultParts['smaller_t'], True);
            $colI++;
            
            $thisRow[$colI] = var_export($resultParts['larger_t'], True);
            $colI++;

            $thisRow[$colI] = $resultParts['result'];
            
            //bit of a hack for legionella
            if($aanvullingLegionella){
              $thisRow[$colI] =  $thisRow[$colI] . ' ' . $aanvullingLegionella;
            }
            $colI++;

            $thisRow[$colI] = var_export($resultParts['indicative'], True);
            $colI++;

            //does not use confirmation, no need to check
            if($assayInfo['confirmation'] == 0 )
            {
              $thisRow[$colI] = var_export(NULL, True);
            } 
            
            else
            {
             
              //conf was never active
              if($thisAnalysis['conf_requested'] == '0')
              {
                $thisRow[$colI] = var_export(False, True);
              }
              
              //conf was disabled
              if($thisAnalysis['conf_requested'] == '2')
              {
                $thisRow[$colI] = var_export(False, True);
              }

              //conf was enabled
              if($thisAnalysis['conf_requested'] == '1')
              {
                if($resultParts['n_confirmed'] == True){
                  $thisRow[$colI] = var_export(False, True);
                } 

                else
                {
                  $thisRow[$colI] = var_export(True, True);
                }             
              }

            }

            $colI++;           
                      
            
            $this->fputcsv_eol($this->csv,$thisRow, ',' , "\"" );
        }        
      }
    }

    private function writeAdvancedResultsV2(){
          

      foreach($this->samples as $sample){
        
        $lengthAssays = count($this->assayInfo);

        $sampleAssayInfo = checkKeyOrFalse($this->sortedAnalysis, $sample['id'] );               
        
        if(!$sampleAssayInfo)
        {
          continue;
        }
        

        foreach($sampleAssayInfo as $thisAnalysis){
                     
            $assayInfo = checkKeyOrFalse($this->assayInfo, $thisAnalysis['assay_base']);
          
            if($assayInfo['hide_report'] == 1 && $this->showHidden == 0){                
              continue;
            }

            $assayDetails = json_decode($assayInfo['custom_fields'], JSON_FORCE_OBJECT);                                              
            $thisRow = array();

            $thisRow[0] = $this->clientInfo['name'];
            $thisRow[1] = checkKeyOrFalse($this->samplingMethods, $sample['sampling_method'], 'door');
            $thisRow[2] = checkKeyOrFalse($this->projects, $sample['project'], 'custom_fields', 'project_monster');
            $thisRow[3] = checkKeyOrFalse($this->projects, $sample['project'], 'custom_fields', 'project_ontvangst');
    
            if(empty($sample['sample_innoculated']))
            {
              $thisRow[4] = 'Onbekend';
            } else{
              $thisRow[4] = date('d-m-Y', $sample['sample_innoculated']);
            }


            $sampleType = 'Unknown';

            if($sample['sample_type'] == 'S')
            {
              $sampleType = 'Direct onderzoek';
            }

            if($sample['sample_type'] == 'S' && !empty($sample['tht_code']))
            {
              $sampleType = 'THT onderzoek';
            }

            if($sample['sample_type'] == 'L')
            {
              $sampleType = 'Legionella onderzoek';
            }

            if($sample['sample_type'] == 'R')
            {
              $sampleType = 'RODAC onderzoek';
            }
 

            $thisRow[5] = $sampleType;

            //does the sample product_group exist in this->productGroups?
            if(array_key_exists($sample['portal_product_group_id'], $this->productGroups)){
              $thisRow[6] = $this->productGroups[$sample['portal_product_group_id']]['name'];
            } else{
              $thisRow[6] = 'Onbekend';
            }

                
            if($this->exportType == 'legionella'){
              $clientRefValue = checkKeyOrFalse($this->projects, $sample['project'], 'project_extra', 'client_reference');
            } else{
              $projectReference = checkKeyOrFalse($this->projects, $sample['project'], 'reference');
              $projectName = checkKeyOrFalse($this->projects, $sample['project'], 'project_name');
              if($projectReference === $projectName){
                $clientRefValue = '';
              } else{
                $clientRefValue = $projectName;
              }
            }
    
            $thisRow[7] = $clientRefValue;
            $thisRow[8] = checkKeyOrFalse($this->projects, $sample['project'], 'reference');
            $thisRow[9] = $sample['barcode'];

            $thisRow[10] = $sample['client_description'];

            $customFields = json_decode($sample['custom_fields'], JSON_FORCE_OBJECT);
            $thisRow[11] = checkKeyOrBlank($customFields, 'details');

            $colI = 12;

            foreach($this->metaColumns as $metaColumn){
              $metaValue = checkKeyOrFalse($this->meta,  $sample['id'], $metaColumn );
              $thisRow[$colI] = $metaValue;
              $colI++;
            }
                        
            $template[0] = 'raportnaam';            
            $template[1] = 'resultin';
            $template[2] = 'techniek';
            $template[3] = 'accred';            
            $template[4] = 'conform';
            $template[5] = 'referentiemethode';

            foreach($template as $tIdx => $tKey)
            {
             
                
              if(array_key_exists($template[$tIdx], $assayDetails)){
                $assayRowVal = $assayDetails[$template[$tIdx]];
              } elseif(array_key_exists($template[$tIdx], $assayInfo)){
                $assayRowVal = $assayInfo[$template[$tIdx]];
              } else{
                $assayRowVal = 'NB';
              }

              array_push($thisRow, $assayRowVal);
              $colI++;                           
            }

            $storedResults = json_decode($thisAnalysis['storedResult'], JSON_FORCE_OBJECT);              
              
            if($storedResults == False){
              //forcibly get result..
              $said = checkKeyOrFalse($this->sortedAnalysis, $sample['id'], $thisAnalysis['assay_base'], 'id' );
              $storedResults = upa('results', 'msProcess', array($said), False);
            }

            $storedOutput = checkKeyOrFalse($storedResults, 'output', 'kve');
            //bit of a hack for legionella
            $aanvullingLegionella = checkKeyOrFalse($storedResults, 'output', 'aanvulling');
            $strippedOutput = $this->stripTags($storedOutput);
            
            $resultParts = $this->returnResultPartsBoolean($strippedOutput);
            
            $thisRow[$colI] = var_export($resultParts['smaller_t'], True);
            $colI++;
            
            $thisRow[$colI] = var_export($resultParts['larger_t'], True);
            $colI++;

            $thisRow[$colI] = $resultParts['result'];
            
            //bit of a hack for legionella
            if($aanvullingLegionella){
              $thisRow[$colI] =  $thisRow[$colI] . ' ' . $aanvullingLegionella;
            }
            $colI++;

            $thisRow[$colI] = var_export($resultParts['indicative'], True);
            $colI++;

            //does not use confirmation, no need to check
            if($assayInfo['confirmation'] == 0 )
            {
              $thisRow[$colI] = var_export(NULL, True);
            } 
            
            else
            {
             
              //conf was never active
              if($thisAnalysis['conf_requested'] == '0')
              {
                $thisRow[$colI] = var_export(False, True);
              }
              
              //conf was disabled
              if($thisAnalysis['conf_requested'] == '2')
              {
                $thisRow[$colI] = var_export(False, True);
              }

              //conf was enabled
              if($thisAnalysis['conf_requested'] == '1')
              {
                if($resultParts['n_confirmed'] == True){
                  $thisRow[$colI] = var_export(False, True);
                } 

                else
                {
                  $thisRow[$colI] = var_export(True, True);
                }             
              }

            }            

            $colI++;           



            
            $referenceValue = '';
          
                        
            if((int)$thisAnalysis['roaming_id'] != 0)
            {
              $roamingReference = json_decode($thisAnalysis['roaming_reference'], JSON_FORCE_OBJECT);              

              $referenceValue = checkKeyOrBlank($roamingReference, 'ref_kve');

            }

            if((int)$thisAnalysis['roaming_id'] == 0)
            {

              $profileReference = json_decode($thisAnalysis['profile_reference'], JSON_FORCE_OBJECT);

              $referenceValue = checkKeyOrBlank($profileReference, 'ref_kve');
            }

                             
            $thisRow[$colI] = $referenceValue;

            $colI++;           

            $referenceSource = 'N.V.T.'; 

            if($referenceValue)
            {
                if((int)$thisAnalysis['roaming_id'] != 0)
                {
      

                  if(array_key_exists($thisAnalysis['roaming_reference_source'], $this->referenceSources))
                  {
                    $referenceSource = $this->referenceSources[$thisAnalysis['roaming_reference_source']];
                  } 
                  
                  else
                  {
                    $referenceSource = 'Geen bron';
                  }

                }

                if((int)$thisAnalysis['roaming_id'] == 0)
                {


                  if(array_key_exists($thisAnalysis['profile_reference_source'], $this->referenceSources))
                  {
                    $referenceSource = $this->referenceSources[$thisAnalysis['profile_reference_source']];
                  } 
                  
                  else
                  {
                    $referenceSource = 'Geen bron';
                  }
                
                }
            }
            
          

            $thisRow[$colI] = $referenceSource;
                      
            
            $this->fputcsv_eol($this->csv,$thisRow, ',' , "\"" );
        }        
      }

      
    }



    private function returnResultPartsBoolean($result){
    
      $flags = array();
      $flags['smaller_t'] = False;
      $flags['larger_t'] = False;
      $flags['indicative'] = False;
      $flags['n_confirmed'] = False; 
      $flags['result'] = '';

      if (strpos($result, '<') !== False) {
        $flags['smaller_t'] = True; 
        $result = str_replace('<', '', $result);
      }

      if (strpos($result, '>') !== False) {
        $flags['larger_t'] = True;
        $result = str_replace('<', '', $result);
      }
      
      if (strpos($result, '**') !== False) {
        $flags['n_confirmed'] = True; 
        $result = str_replace('**', '', $result);
      }

      if (strpos($result, '*') !== False) {
        $flags['indicative'] = True;
        $result = str_replace('*', '', $result);
      }
      
      $flags['result'] = trim($result);
      return $flags;
    }

   private function returnResultParts($result){
        $parts = array(0 => '', 1 => '', 2 => '');

        //prepend
        if (strpos($result, '<') !== False) {
          $parts[0] = '<';
          $result = str_replace('<', '', $result);
        }

        if (strpos($result, '>') !== False) {
          $parts[0] = '>';
          $result = str_replace('<', '', $result);
        }

        //append
        if (strpos($result, '**') !== False) {
          $parts[2] = '**';
          $result = str_replace('**', '', $result);
        }

        if (strpos($result, '*') !== False) {
          if(!empty($parts[2])){
            $parts[2] .= ' *';
          } else{
            $parts[2] = '*';
          }

          $result = str_replace('*', '', $result);
        }

        $parts[1] = trim($result);
        return $parts;
   }

   private function stripTags($input){
        $rep = array();
        $rep[0] = '<sup>';
        $rep[1] = '</sup>';
        $replaces = str_replace($rep,  '',   $input);
        return trim($replaces);
   }

    private function writeAssayBlock(){
      $template = array();

      $template[0] = 'raportnaam';
      $template[1] = 'id';
      $template[2] = 'resultin';
      $template[3] = 'techniek';
      $template[4] = 'accred';
      $template[5] = 'internrefnummer';
      $template[6] = 'conform';
      $template[7] = 'referentiemethode';

      $rowNames = array();
      $rowNames[0] = 'Analyse';
      $rowNames[1] = 'Analyse ID LIMS';
      $rowNames[2] = 'Eenheid';
      $rowNames[3] = 'Techniek';
      $rowNames[4] = 'Geaccrediteerde verrichting';
      $rowNames[5] = 'Intern referentienummer';
      $rowNames[6] = 'Conformiteit';
      $rowNames[7] = 'Referentiemethode';

      $colStart = 8;
      $assayRowWidth = 0;


      foreach($rowNames as $idx => $headerRow){
        $thisRow = array();

        //write buffer
        for($i = 1; $i <= $colStart; $i++){
          array_push($thisRow, '');
        }

        //write name
        array_push($thisRow, $rowNames[$idx]);

        $i = 0;
        $assayRowWidth = 0;

        foreach($this->assayInfo as $assayIdx => $assayInfo){

          //check if this is hidden?
          if($assayInfo['hide_report'] == 1 && $this->showHidden == 0){
            unset($this->assayInfo[$assayIdx]);
            continue;
          }

          $assayDetails = json_decode($assayInfo['custom_fields'], JSON_FORCE_OBJECT);

          $this->assayColCoord[$i] = $assayInfo['original_id'];

          if(array_key_exists($template[$idx], $assayDetails)){
            $assayRowVal = $assayDetails[$template[$idx]];
          } elseif(array_key_exists($template[$idx], $assayInfo)){
            $assayRowVal = $assayInfo[$template[$idx]];
          } else{
            $assayRowVal = 'NB';
          }

          array_push($thisRow, $assayRowVal);

          //add two extra rows if long format
          if($this->format == 'long'){
            array_push($thisRow, '');
            array_push($thisRow, '');
            $assayRowWidth = $assayRowWidth + 3;
          } else{
            $assayRowWidth++;
          }


          $i++;
        }

        $this->fputcsv_eol($this->csv,$thisRow, ',' , "\"" );
      }

      //buffer
      $rowBuffer = 2;
      for($i = 1; $i <= $rowBuffer; $i++){
        $thisRow = array(' ', '');
        $this->fputcsv_eol($this->csv,$thisRow, ',' , "\"" );
      }

      //col titles
      $colTitles = array();
      $colTitles[0] = 'Opdrachtgever';              //A
      $colTitles[1] = 'Monstername door'; //B
      $colTitles[2] = 'Datum bemonstering'; //C
      $colTitles[3] = 'Datum ontvangst'; //D
      $colTitles[4] = 'Datum start analyse'; //E
      $colTitles[5] = 'Referentie opdrachtgever'; //F
      $colTitles[6] = 'Referentie MAZ'; // G
      $colTitles[7] = 'Monsternummer'; //H       
      $colTitles[8] = 'Monster details';       

      $thisRow = array();
      foreach($colTitles as $colTitle){
        array_push($thisRow, $colTitle);
      }

      //space as much as assays are
      for($i = 0; $i < $assayRowWidth; $i++){
        array_push($thisRow, '');
      }

      foreach($this->metaColumns as $metaColumn){
        array_push($thisRow, $metaColumn);
      }

      $this->fputcsv_eol($this->csv,$thisRow, ',' , "\"" );
    }

    private function startCSV(){
      $this->csv = fopen('php://temp', 'r+');
    }

    private function sortAnalysisRows(){

      $sorted = array();

      foreach($this->analysis as $analysis){
          $thisAnalysisSample = $analysis['sample'];
          $thisAnalysisBase = $analysis['assay_base'];
          $thisAnalysisOriginal = $analysis['original_assay_base'];

          if(!array_key_exists($thisAnalysisSample, $sorted)){
            $sorted[$thisAnalysisSample] = array();
          }

          //$sorted[$thisAnalysisSample][$thisAnalysisBase] = $analysis;
          //changed
          $sorted[$thisAnalysisSample][$thisAnalysisOriginal] = $analysis;
      }

      return $sorted;

    }

    private function loadAnalysisRows(){

      

      $sampleIDs = array_column($this->samples, 'id');

      if(empty($sampleIDs)){
        return array();
      }

      $idMap = implode(',', array_map('intval', $sampleIDs));

      $sql = 'SELECT sa.*, ra.reference AS roaming_reference, ra.reference_source AS roaming_reference_source, ap.reference AS profile_reference, ap.reference_source AS profile_reference_source FROM `sampleanalysis` sa
          LEFT JOIN `roaminganalysis` ra ON sa.roaming_id = ra.id
          LEFT JOIN `assayprofiles` ap ON sa.assay = ap.id
          WHERE sa.sample IN (' . $idMap . ') ';

      

      if($this->exportType == 'selection'){

      
        #$assayMap = implode(',', $this->assaySelection);

        $sql .= 'AND ( ';

        foreach($this->assaySelection as $assayId){
          $sql .= 'sa.assay_base = ' . $assayId . ' OR sa.original_assay_base = ' . $assayId . ' OR ';
        }

        $sql = substr($sql, 0, -3 );
        $sql .= ')';
        #$sql .= ' AND  ' . $assayMap . ' IN(assay_base, original_assay_base)';
      }

      return $this->DataMining->customQuery($sql, array());
    }
    // private function loadAnalysisRows(){

    //   $sampleIDs = array_column($this->samples, 'id');

    //   if(empty($sampleIDs)){
    //     return array();
    //   }

    //   $idMap = implode(',', array_map('intval', $sampleIDs));
    //   $sql = 'SELECT * FROM `sampleanalysis` WHERE sample IN (' . $idMap . ') ';

    //   if($this->exportType == 'selection'){
    //     #$assayMap = implode(',', $this->assaySelection);

    //     $sql .= 'AND ( ';

    //     foreach($this->assaySelection as $assayId){
    //       $sql .= 'assay_base = ' . $assayId . ' OR original_assay_base = ' . $assayId . ' OR ';
    //     }

    //     $sql = substr($sql, 0, -3 );
    //     $sql .= ')';
    //     #$sql .= ' AND  ' . $assayMap . ' IN(assay_base, original_assay_base)';
    //   }

    //   return $this->DataMining->customQuery($sql, array());
    // }

    private function loadSamplingMethods(){
      $methods = upa('sampleProcedures', 'getProceduresList', array(), False);
      foreach($methods as $method){
        $this->samplingMethods[$method['id']] = $method;
        $custom = json_decode($method['fields'], JSON_FORCE_OBJECT);
        foreach($custom as $customField => $customFieldValue){
          $this->samplingMethods[$method['id']][$customField] = $customFieldValue;
        }
      }
    }

    private function loadAssayInfo($type){

      if($type === 'advanced'){
          $assayId = array_column($this->analysis, 'assay_base');
      } else{
          $assayId = array_column($this->analysis, 'original_assay_base');
      }

      #$assayId = array_column($this->analysis, 'assay_base');
      

      if(empty($assayId)){
        return array();
      }

      $idmap =  implode(',', array_map('strval', $assayId));

      $sql = 'SELECT * FROM `assays` WHERE `id` IN (' . $idmap . ') ORDER BY `original_id` ASC';
      return $this->DataMining->customQuery($sql, array());
    }

    private function loadMeta(){
      $sampleIDs = array_column($this->samples, 'id');

      if(empty($sampleIDs)){
        return array(0 => array(), 1=> array());
      }

      $idMap = implode(',', array_map('intval', $sampleIDs));
      $sql = 'SELECT * FROM `metadata` WHERE sample IN (' . $idMap . ');';
      $result = $this->DataMining->customQuery($sql, array());
      $columns = array_column($result, 'name');
      $columns = array_unique($columns);

      //setup array for easy fetch
      $metaData = array();
      foreach($result as $metaRow){
        $metaData[$metaRow['sample']][$metaRow['name']] = $metaRow['value'];
      }

      return array($metaData, $columns);

    }

    private function loadSamples(){
      $projectIDs = array_column($this->projects, 'id');

      if(empty($projectIDs)){
        return array();
      }

      $idMap = implode(',', array_map('intval', $projectIDs));
      $sql = 'SELECT * FROM `samples` WHERE project IN (' . $idMap . ');';
      //cphp('Sample sql:'  . $sql);
      return  $this->DataMining->customQuery($sql, array());
    }

    private function loadProjects($client, $scopeSelector, $rangeStart, $rangeEnd, $projectId){
        $specialType = False;

        if($scopeSelector == 'project'){
            $specialType = 'id' ;
        }  else{


          if($this->exportType == 'all'){
              $specialType = 0;
          } elseif($this->exportType == 'legionella'){
              $specialType = 1;
          } elseif($this->exportType == 'rodac'){
              $specialType = 2;
          } elseif($this->exportType == 'selection'){
              $specialType = 'all';
              //$specialType = 0;
          }
        }

        return upa('projects', 'dataMiningGrab', array($this->clientId, $rangeStart, $rangeEnd, $specialType, $projectId, $scopeSelector), False);
    }

    private function  fputcsv_eol($handle, $array, $delimiter = ',', $enclosure = '"', $eol = PHP_EOL) {
      $delimiter = $this->seperator;
      $return = fputcsv($handle, $array, $delimiter, $enclosure);
      if($return !== FALSE && "\n" != $eol && 0 === fseek($handle, -1, SEEK_CUR)) {
        fwrite($handle, $eol);
      }
      return $return;
    }





}
