<?PHP

/*
Handle the sample buffer list for bulk importers and client portal inputs

*/

class sampleBufferscontroller extends controller{
	
	private $importSlugs = array();
	
	private $clientsSeen = array();

	public $alertPrimed = False; 

	public $alertTripped = False; 

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

	

	public function changeTHTTemp()
	{
		$this->render = False; 

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);
		

		foreach($commitIDs as $commitID)
		{
			$sample = new SampleBuffer; 
			$sample->where('id', $commitID );
			$sample = $sample->first();

			$storageConditions = array();
			$storageConditions['4'] = '+3°C';
			$storageConditions['0'] = '+4°C';
			$storageConditions['1'] = '+7°C';
			$storageConditions['2'] = '-18°C';
			$storageConditions['3'] = 'Kamer temperatuur';
	
			$this->SampleBuffer->id = $sample['id'];			
	
			if(strpos($sample['misc_directions'], 'Opslag:') !== False)
			{

				$split = explode('.' , $sample['misc_directions'], 2);

				if(count($split) > 1)
				{
					$misc_directions = 'Opslag: ' . $storageConditions[$_POST['temp']] . '. ' . $split[1];					
				}

				else
				{
					$misc_directions = 'Opslag: ' . $storageConditions[$_POST['temp']] . '. ';
				}

				
											
			}

			else
			{
				$misc_directions = $sample['misc_directions'] . ' Opslag: ' . $storageConditions[$_POST['temp']] . '. ' ;
			}

			$this->SampleBuffer->misc_directions = $misc_directions; 
			$this->SampleBuffer->save();

		}

		$this->reRoute('sampleBuffers/stagedTht', True);
		
	}

	public function rerunTHTPrint()
	{
		$this->render = false; 
		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);
		upa('printing', 'batchTHTReg', array($commitIDs), False);
		$this->reRoute('sampleBuffers/stagedTht', True);
	}

	public function changeToTHT()
	{
		$this->render = False; 
		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);
		$stamp = strtotime($_POST['date']);

		foreach($commitIDs as $commitID){
			$misc_direction = '';

			switch((int)$_POST['storage']){
				case 0:
					$misc_direction = 'Opslag: 4°C';
					break; 
				case 1:
					$misc_direction = 'Opslag: 7°C';
					break; 
				case 2:
					$misc_direction = 'Opslag: -18°C';
					break; 
				case 3:
					$misc_direction = 'Opslag: Kamertemperatuur';
					break; 
				case 4:
					$misc_direction = 'Opslag: 3°C';
					break;
			}
			
			$sb = $this->SampleBuffer->find($commitID);

			if($sb)
			{
				$this->SampleBuffer->arrayToModel($sb);
			
				$this->SampleBuffer->id = $commitID;
				$this->SampleBuffer->tht_date = date('Y-m-d', $stamp);
				$this->SampleBuffer->tht = '1';
				$this->SampleBuffer->misc_directions = $misc_direction;
				$this->SampleBuffer->save();
				
				if(!empty($this->SampleBuffer->portal_id) && !is_null($this->SampleBuffer->portal_id) && $this->SampleBuffer->portal_id != 0)
				{
					upa('portal', 'changeToTHT', array($this->SampleBuffer->portal_id, $stamp,(int)$_POST['storage'] ), False);
				}
				
			}

			


		}

		
	$this->reRoute('sampleBuffers/stagedTht', True);
		
	}

	public function commitFromRegisterScreen($bufferIn){
		
		$thtIds = array();

		foreach($bufferIn as $buffer){
			$thtPush = false;
			$this->SampleBuffer->client = $buffer['client'];
			$this->SampleBuffer->source = 2;
			$this->SampleBuffer->project = $this->getProjectSlug($buffer['sampling_date'], $buffer['client']);

			$samplingTStamp = str_replace('/', '-', $buffer['sampling_date']); //confrom to mysql specs
			$this->SampleBuffer->sampling_date = $samplingTStamp;

			$this->SampleBuffer->sampling_method = $buffer['sampling_method'];
			$this->SampleBuffer->sample_name = $buffer['sample_name'];
			$this->SampleBuffer->sample_details = $buffer['sample_details'];			
			$this->SampleBuffer->project_name = (empty($buffer['project_name'])) ? null : $buffer['project_name'] ;

			if($buffer['tht'] == 1){
				$this->SampleBuffer->tht = 1;
				$tStamp = strtotime(str_replace('/', '-', $buffer['tht_date'])); //confrom to mysql specs
				$this->SampleBuffer->tht_date = date('Y-m-d', $tStamp);
				$thtCode = upa('samples', 'generateBarcode', array(False, 'THT'), False);			
				$this->SampleBuffer->tht_code = $thtCode;

				$storageConditions = array();
				$storageConditions['0'] = '+4°C';
				$storageConditions['1'] = '+7°C';
				$storageConditions['2'] = '-18°C';
				$storageConditions['3'] = 'Kamer temperatuur';
				$storageConditions['4'] = '+3°C';
				$this->SampleBuffer->misc_directions = 'Opslag: ' . checkKeyOrBlank($storageConditions, $buffer['tht_storage']);			
				$thtPush = true;
			} else{
				$this->SampleBuffer->tht = 0;
			}

			$this->SampleBuffer->receive_time = $buffer['receive_time'];
			$this->SampleBuffer->receive_date = $buffer['receive_date'];
			$this->SampleBuffer->date_registered = time();

			$this->SampleBuffer->authorized = checkKeyOrFalse($buffer, 'authorized');
			$this->SampleBuffer->meta = json_encode(array(), JSON_FORCE_OBJECT);
			$this->SampleBuffer->save();

			if($thtPush == true){
				$lastId = $this->SampleBuffer->lastInsertId;
				array_push($thtIds, $lastId);
			}

		}

		upa('printing', 'batchTHTReg', array($thtIds), False);

	}

   	public function bulkUploader(){
		$sForm = new formFactory($this->_controller);
        $sForm->setId('uploadForm');
        $sForm->action('sampleBuffers/uploadBulkCSV');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();
				$sForm->addInputField('client', False, 'hidden', 'hidden', False, 'NULL', '');
        $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', False, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

		$sForm->addInputField('seperator', False, 'text', 'input-block-level', ';', '', '');
       	$sForm->submitTrough('bulkFileUpload', '');
        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);
    }

    private function grabColMapping($colId, $colHeader){

		$columnMapping = array();
		$columnMapping[0] = 'sampling_date';
		$columnMapping[1] = 'sample_name';
		$columnMapping[2] = 'sample_details';
		$columnMapping[3] = 'tht';
		$columnMapping[4] = 'tht_date';

		if(array_key_exists($colId, $columnMapping)){
			return $columnMapping[$colId];
		} else{
			return $colHeader;
		}
    }

    private function getProjectSlug($samplingDate, $client){
    	$slugDate = str_replace(array('/','-'), '', $samplingDate);
    	$slug = 'PRJ' . '-' . $client . '-' . $slugDate;
    	return $slug;
    }

	private function CSVprelimcheck($fields, $sep)
	{
		
		if(count($fields) < 7){
			$this->render = False;
			print('Bestand kon niet gelezen worden, is het kolom scheidings teken "' . $sep . '" wel correct? Parse resultaat van CSV gaf de volgende velden:');
			parray($fields);
			die();
		}
	}

	private function parseCSV($client)
	{
	
		$sep = checkKeyOrFalse($_POST, 'seperator');    	

		$defaultPG = upa('productGroups', 'getClientDefaultGroup', array($client), False);        					
		
		
		if($sep == False)
		{
    		$sep = ',';
    	}

		$headers = NULL;
		$csv = []; 
		$row = 0; 

		if(($handle = fopen($_FILES['bulkFile']['tmp_name'], "r")) !== FALSE) 
		{
			while (($data = fgetcsv($handle, 0, $sep)) !== FALSE) 
			{
				
				if($headers === NULL)
				{
					$this->CSVprelimcheck($data, $sep);			

					$headers = $data; 			

					//prepend 1 cell 
					array_unshift($headers, 'Productgroep');
					
					continue;
				}				

				
				//prepend defaultgroup id to data
				array_unshift($data, $defaultPG['portal_id']);

				

				$data =  array_combine($headers, $data);
				
				array_walk_recursive($data,function(&$item, &$key){
					//$item = utf8_encode($item);					
					$item =  mb_convert_encoding($item, "UTF-8", mb_detect_encoding($item, "UTF-8, ISO-8859-1, ISO-8859-15", true));
				});

				$keys = array_keys($data);
				
			
				$date = checkKeyOrFalse($data, $keys[1]);
				$proj = checkKeyOrFalse($data, $keys[2]);
				$short = checkKeyOrFalse($data, $keys[3]);
				$details = checkKeyOrFalse($data, $keys[4]);
	
				if(empty($desc) && empty($proj) && empty($short) && empty($details))
				{						 
					continue;
				}
	
				$dateStamp = strtotime(str_replace('/', '-', $date));
	
				if($dateStamp === False){
					$corrected = 'Onbekend';
				} 
				else
				{
					$dateStamp = strtotime(str_replace('/', '-', $date));
					$corrected = date('d-m-Y', $dateStamp);
				}
	
				$data[$keys[1]] = $corrected;
			
				
				$csv[$row] = $data; //could also just be array_push? 				

				$row++;

			}
		}


		$colHeaders = '';
		$metaHeaders = [];
		$pos = 0; 
    	foreach($headers as $header){
			if(empty($header)){
				unset($headers[$header]);
				$pos++;
				continue;
			}
			
			if($header != '_ProductGroep')
			{
				$colHeaders .= '<th>' . $header . '</th>';
			}
			

			if($pos > 8){

				if($header != '_ProductGroep')
				{
					$metaHeaders[$pos] = $header;
				}

				//$metaHeaders[$pos] = $header;
			}

			$pos++;
    	}

		return array('csv' => $csv , 'colHeaders' => $colHeaders, 'metarows' => $metaHeaders);

	}


	private function createDropDownWithDefault($productGroups, $selected){

		$dropDown = '';
		
		foreach($productGroups as $pg)
		{

			if($pg['portal_id'] == $selected)
			{
				$selectedText = ' selected="selected"';
			} 
			
			else
			{
				$selectedText = '';
			}
			
			$dropDown .= '<option value="' . $pg['portal_id'] . '"' . $selectedText . '>' . $pg['name'] . '</option>';
			
		}
		return $dropDown; 

	}

	function customClientLogicForProductGroups($analysisText, $default, $productGroups)
	{

		$returnArray = [
			'selected' => $default,
			'alert' => False, 
		];

		$analysisText = trim($analysisText);

		//does the client have any productgroups with the name as defined in analysistext?
		if(!empty($analysisText) && !empty($productGroups))
		{
			foreach($productGroups as $pg)
			{
				
				if(trim($pg['name']) === trim($analysisText))
				{
					
					$returnArray['selected'] = $pg['portal_id'];
					$returnArray['alert'] = False;

					return $returnArray; //return early if found a match
					
					//return $pg['portal_id'];
				}
			}
		}

		if($this->alertPrimed)
		{
			$this->alertTripped = True;

			$returnArray['alert'] = True;
		}
		

		return $returnArray; //return default if no match found

	}


    function createBulkRow($columns, $productGroups, $client){
    	$row = '<tr>';
		

    	foreach($columns as $columnName => $column){
			
    		
			if($columnName == 'product_group'){
				//product group

				$row .= '<td>';

			
				$selected = $columns['product_group']; 
				
				$clientsUsingAssayColumnImport = json_decode(CLIENT_USES_ASSAY_COLUMN_IMPORT) ?? array();
				
				if(in_array((int)$client, $clientsUsingAssayColumnImport))
				{

					$this->alertPrimed = True;

					$infoEntered = $columns['analyses_selected'];

					$selectedReturn = $this->customClientLogicForProductGroups($infoEntered, $selected, $productGroups );

					$selected = $selectedReturn['selected'];

				}

				else
				{
					

					$infoEntered = $columns['user_provided_product_group_name'] ?? null;

					if(!empty($infoEntered))
					{

						$this->alertPrimed = True;
					
						//check if the user provided product group name is in the list of product groups
						$selectedReturn = $this->customClientLogicForProductGroups($infoEntered, $selected, $productGroups );

						$selected = $selectedReturn['selected'];

					} 

				
				}

				$dropDownBackgroundColor = '';

				if(isset($selectedReturn) && $selectedReturn['alert'] == True)
				{
										
					$dropDownBackgroundColor = 'background-color:rgb(171, 64, 64); text-shadow: 1px 1px 2px #000; color: #fff;';
				}
				
				

				$row .= '<select class="pgdropper"  name="product_group" class="input-block-level" style="' . $dropDownBackgroundColor .  '" >';

				$row .= $this->createDropDownWithDefault($productGroups,$selected );

				$row .= '</select>';

				if(isset($selectedReturn) && $selectedReturn['alert'] == True)
				{					
					$row .= '<p style="font-size: 10px;">Klant invoer:' .  $infoEntered . '</small>';
				}
				
				$row .= '</td>';

				continue;
			}
			
			if($columnName != 'meta')
			{

				if($columnName != 'user_provided_product_group_name')
				{
					$row .= '<td> ' . $column . '</td>';	
				}

    			
    		} 
			
			else
			{
    			
				foreach($column as $metaName => $metaVal)
				{
    				$row .= '<td>' .  $metaVal . '</td>';
    			}
    		}
    	}
    	$row .= '</tr>';

    	return $row;
    }

	function uploadBulkCSV(){
		
		$bufferData = array();
		
		$client = upa('clients', 'fetch', array($_POST['client']), False);
		

		if(empty($client)){
			return;
		}

		

		//load in product groups for this client
		$defaultPG = upa('productGroups', 'getClientDefaultGroup', array($client['id']), False);        
		
		

		$csvParse = $this->parseCSV($client['id']);

		$productGroupOptions = upa('productGroups', 'productGroupsArray', array($client['id']), False);
		
		//metarows 
		$metaSelector = '';

		foreach($csvParse['metarows'] as $mdIdx => $mdk){
			$metaSelector .= '<tr><td><input type="checkbox" name="mdk_' .$mdIdx .'" value="' . $mdk . '" checked="checked" /></td><td>' . $mdk .'</td></tr>';
		}

	   	$colData = '';
    	$sampleI = 0;
    	foreach($csvParse['csv'] as $row){

		

			$maxColIndex = count($row) - 1;
			
			if($maxColIndex < 4)
			{
				print('ret');
				return;
			}

			//fixed info
			$bufferData[$sampleI] = array();
			$bufferData[$sampleI]['product_group'] = array_shift($row);
			$bufferData[$sampleI]['sampling_date'] = array_shift($row);
			$bufferData[$sampleI]['project_name'] = array_shift($row);
			$bufferData[$sampleI]['sample_name'] = array_shift($row);
			$bufferData[$sampleI]['sample_details'] = array_shift($row);
			$bufferData[$sampleI]['tht'] = array_shift($row);
			$bufferData[$sampleI]['tht_date'] = array_shift($row);
			$bufferData[$sampleI]['analyses_selected'] = array_shift($row);
			$bufferData[$sampleI]['misc_directions'] = array_shift($row);
			$bufferdata[$sampleI]['user_provided_product_group_name'] = ''; 
			$bufferData[$sampleI]['meta'] = array();

			$metaKeys = array_keys($row);					
			
			foreach($metaKeys as $keyName)
			{
			
				if(!empty($keyName))
				{

					if($keyName == '_ProductGroep')
					{
						$bufferData[$sampleI]['user_provided_product_group_name'] = trim($row[$keyName]);
					}

					else
					{
						$bufferData[$sampleI]['meta'][$keyName] = $row[$keyName];
					}
					
				}
			}

			$colData .= $this->createBulkRow($bufferData[$sampleI], $productGroupOptions, $client['id']);

			//attach client
			$bufferData[$sampleI]['client'] = $client['id'];
			$sampleI++;
    	}

    	$this->_template->set('column_headers', $csvParse['colHeaders']);
    	$this->_template->set('table_data', $colData);
    	$this->_template->set('buffer_data', json_encode($bufferData));
    	$this->_template->set('client_name', $client['name']);
		$this->_template->set('mdk_data', $metaSelector);

		if($this->alertTripped == True)
		{
			$this->_template->set('alert_classes', '');
			$this->_template->set('alert_tripped', 'alert alert-danger');
			$this->_template->set('alert_message', 'Let op: Er zijn productgroepen geselecteerd die niet in de database staan, deze samples worden niet toegevoegd aan het voorportaal.');
		} else{
			$this->_template->set('alert_classes', 'hidden');
			$this->_template->set('alert_tripped', '');
			$this->_template->set('alert_message', '');
		}
	}

	function commitToBuffer(){
		
		
		$bufferData = json_decode($_POST['buffer_data'], JSON_FORCE_OBJECT);

		$pgMapData = json_decode($_POST['pg_map_data'], JSON_FORCE_OBJECT);		

		$index = 0; 

		foreach($bufferData as $sampleToCommit){

			//use pgmapdata to find this sample, otherwise null
			$this->SampleBuffer->portal_product_group_id  = $pgMapData[$index] ?? null;			

			$this->SampleBuffer->client = $sampleToCommit['client'];
			$this->SampleBuffer->source = 1;
			$this->SampleBuffer->project = $this->getProjectSlug($sampleToCommit['sampling_date'], $sampleToCommit['client']);

			$samplingTStamp = str_replace('/', '-', $sampleToCommit['sampling_date']); //confrom to mysql specs
			$this->SampleBuffer->sampling_date = $samplingTStamp;

			$this->SampleBuffer->sampling_method = MESA_STD_SMPL_METHOD;
			
			$this->SampleBuffer->sample_name = $sampleToCommit['sample_name'];
			
			
			$this->SampleBuffer->sample_details = $sampleToCommit['sample_details'];
			$this->SampleBuffer->project_name = $sampleToCommit['project_name'];

			$thtActive = array('JA', 'J', 'Y', 'YES', '1');
			if(in_array(strtoupper($sampleToCommit['tht']), $thtActive)){
				$this->SampleBuffer->tht = 1;
				$tStamp = strtotime(str_replace('/', '-', $sampleToCommit['tht_date'])); //confrom to mysql specs
				$this->SampleBuffer->tht_date = date('Y-m-d', $tStamp);
			} else{
				$this->SampleBuffer->tht = 0;
			}

			$this->SampleBuffer->analyses_selected = $sampleToCommit['analyses_selected'];
			$this->SampleBuffer->misc_directions = $sampleToCommit['misc_directions'];

			foreach($sampleToCommit['meta'] as $metaKey => $metaVal){
				if(empty($metaVal) || !in_array($metaKey, $_POST)){
					unset($sampleToCommit['meta'][$metaKey]);
				}
			}

			$this->SampleBuffer->meta = json_encode($sampleToCommit['meta'], JSON_FORCE_OBJECT);
			$this->SampleBuffer->date_registered = time(); 
			$this->SampleBuffer->save();
			$this->SampleBuffer->deepFreed();

			$index++;
		}
	}

	private function getIcon($source){

		if($source == 1){
			return 'icon-file';
		}

		if($source == 2){
			return 'icon-keyboard';
		}

		if($source == 3){
			return 'icon-cloud';
		}
	}

	private function clientInfo($clientId){
		//clientsSeen
		if(in_array($clientId, $this->clientsSeen)){
			return $this->clientsSeen[$clientId];
		} else{
			$client = upa('clients', 'fetch', array($clientId), False);
			$this->clientsSeen[$clientId] = $client;
			return $client;
		}
	}

	private function thtStateAndDate($tht, $thtDate){

		if($tht == 1){

			$today = date('Y-m-d', time());
			$dateObject = new DateTime($today);
			$today = $dateObject->add( new DateInterval('P1D'))->format('Y-m-d');

			$conflict = False;
			if($thtDate <= $today){
				$conflict = True;
			}

			$date = date_create($thtDate);
			return array('Ja', date_format($date, 'd-m-Y'), $conflict);
		} else{
			return array('Nee', '', '');
		}

	}

	function portal($sort = False, $direction = 'ASC', $tab = 'normal'){

		$holidays = new Holidays();

		//php validate boolean 
		$sort = filter_var($sort, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

		if($sort === False && isset($_SESSION['buffer_sort']))
		{ 		
			$sort = $_SESSION['buffer_sort'];		
			$direction = $_SESSION['buffer_direction'];		
		}

		elseif($sort === False && !isset($_SESSION['buffer_sort']))
		{			
			$sort = 'sampling_date';
			$direction = 'ASC';
		}

		else{			

			if(!in_array($sort, [ 'client', 'project', 'sampling_date', 'project_name'] ))
			{
				$sort = 'sampling_date'; 
			}
	
			if(!in_array($direction, ['ASC', 'DESC']))
			{
				$direction = 'ASC';
			}

		}


		if($sort == 'sampling_date')
		{
			$cQuery = 'select *, date_format(str_to_date(sampling_date, \'%d-%m-%Y\'), \'%Y-%m-%d\') as NewDate from samplebuffers WHERE authorized = \'0\' order by NewDate ' . $direction .  ';';
			$rows = $this->SampleBuffer->customQuery($cQuery, array());
			$_SESSION['buffer_sort'] = 'sampling_date';
			$_SESSION['buffer_direction'] =  $direction;
			$this->_template->set('sort_by_date', 'selected');
			$this->_template->set('sort_by_client', '');
			$this->_template->set('sort_by_project', '');
			$this->_template->set('sort_by_project_name', '');
		}

		if($sort == 'client')
		{
			$this->SampleBuffer->order('client', $direction);
			$this->SampleBuffer->where('authorized', 0);
			$rows = $this->SampleBuffer->search();
			$_SESSION['buffer_sort'] = 'client';
			$_SESSION['buffer_direction'] =  $direction;
			$this->_template->set('sort_by_date', '');
			$this->_template->set('sort_by_client', 'selected');
			$this->_template->set('sort_by_project', '');
			$this->_template->set('sort_by_project_name', '');
		}

		if($sort == 'project')
		{
			$this->SampleBuffer->order('project', $direction);
			$this->SampleBuffer->where('authorized', 0);
			$rows = $this->SampleBuffer->search();
			$_SESSION['buffer_sort'] = 'project';
			$_SESSION['buffer_direction'] =  $direction;
			$this->_template->set('sort_by_date', '');
			$this->_template->set('sort_by_client', '');
			$this->_template->set('sort_by_project', 'selected');
			$this->_template->set('sort_by_project_name', '');
		}

		
		if($sort == 'project_name')
		{
			$this->SampleBuffer->order('project_name', $direction);
			$this->SampleBuffer->where('authorized', 0);
			$rows = $this->SampleBuffer->search();
			$_SESSION['buffer_sort'] = 'project_name';
			$_SESSION['buffer_direction'] =  $direction;
			$this->_template->set('sort_by_date', '');
			$this->_template->set('sort_by_client', '');
			$this->_template->set('sort_by_project', '');
			$this->_template->set('sort_by_project_name', 'selected');
		}

		if($direction == 'ASC')
		{
			$this->_template->set('sort_asc', 'selected');
			$this->_template->set('sort_desc', '');
		}

		else
		{
			$this->_template->set('sort_asc', '');
			$this->_template->set('sort_desc', 'selected');
		}
		
					
		$samplingProcs = upa('sampleProcedures', 'getProceduresArr', array(), False);
		$samplingProcDrop = upa('sampleProcedures', 'getProcedureDropDown', array(False), False);

		$profilesAvail = upa('researchProfiles', 'fetchLegionellaProfiles', array(False, True));

		$this->_template->set('legTypeOptions', $profilesAvail['options']);

		$this->_template->set('procedures', $samplingProcDrop);

		$clientIds = array_column($rows, 'client' );
		$clientIds = array_unique($clientIds);


		$clients = array();

		if(count($clientIds) > 0)
        {
            $clientObj = new Client();
            $clientSql = 'SELECT * FROM clients WHERE id IN (' . implode(',', $clientIds) .')';
            $clientNames = $clientObj->customQuery($clientSql, []);
            $clientNames = array_column($clientNames, null, 'id');                
        }
				
		
		$tablecontent = '';
		$thtTableContent = '';
		$legTableContent = '';
		$rodacTableContent = '';
		$sampleCounts = array();
		$sampleCounts['normal'] = 0;
		$sampleCounts['tht'] = 0;
		$sampleCounts['leg'] = 0; 
		$sampleCounts['rodac'] = 0; 

		$normalStriped = False;
		$thtStriped = False;
		$legStriped = False; 
		$rodacStriped = False;

		foreach($rows as $bufRow){

			//$clientInfo = $this->clientInfo($bufRow['client']);
			$clientInfo = $clientNames[$bufRow['client']];

			if($bufRow['source'] == 3)
			{
				$projectTickName = 'portal_' . $bufRow['portal_project'];
			}
			else
			{
				$projectTickName =  $bufRow['project'];
			}

			$bufRow['projectTickName'] = $projectTickName;
			$bufRow['icon'] = $this->getIcon($bufRow['source']);
			$bufRow['client_name'] = $clientInfo['name'];

			if(array_key_exists($bufRow['sampling_method'], $samplingProcs)){
				$bufRow['sampling_name'] = $samplingProcs[$bufRow['sampling_method']];
			}else{
				$bufRow['sampling_name'] = $samplingProcs['0'];
			}

			$thtStatus = $this->thtStateAndDate($bufRow['tht'], $bufRow['tht_date']);

			$bufRow['tht_state'] = $thtStatus[0];
			$bufRow['tht_date'] = $thtStatus[1];
			$conflict = $thtStatus[2];

			$bufRow['rowColour'] = '';

			$meta = json_decode($bufRow['meta'], JSON_FORCE_OBJECT);

			if(!empty($meta)){
				$bufRow['meta'] = 'Ja';
			} else{
				$bufRow['meta'] = 'Nee';
			}

			$bufRow['import_requested_analysis'] = '';		
			//inject analyhses from portal
			if($bufRow['source'] == 3){

				$portalInfo = json_decode($bufRow['portal_analyses'], JSON_FORCE_OBJECT);

				//$portalText = ' <span class="label">'. $portalInfo['profile'] . '</span>';
				$portalText = '';

				if($portalInfo['profile_id'] == NULL){

					foreach($portalInfo['assays_all'] as $idx => $allAssays){
						$portalText = $portalText . ' <span class="label label-info">' . $allAssays . '</span>';
					}
				} else{

					$portalText = ' <span class="label" style=" background-color: #f0a843;">'. $portalInfo['profile'] . '</span>';

					foreach($portalInfo['assays_addition'] as $idx => $addedAssay){
						$portalText = $portalText . ' <span class="label label-success"><i class="icon icon-plus"></i>' . $addedAssay . '</span>';
					}

					foreach($portalInfo['assays_substraction'] as $idx => $subbedAssay){
						$portalText = $portalText . ' <span class="label label-warning"><i class="icon icon-minus"></i>' . $subbedAssay . '</span>';
					}
				}

				$bufRow['analyses_selected'] = $portalText;

			}

			
			
			//grab tht's regardles of research type 
			if($bufRow['tht'] == 1)
			{
				$markup = $holidays->getTypeOfSpecialDate($bufRow['tht_date']);
				$bufRow['celstyle'] = '';
				$bufRow['rowColour'] = ($thtStriped == True) ? '#bcbcbc' : 'white';	
				$thtStriped = !$thtStriped;
				$sampleCounts['tht'] = $sampleCounts['tht'] + 1;

				$bufRow['celstyle'] = '';
			
				if($markup == 'holiday' || $markup == 'sunday')
				{
					$bufRow['celstyle'] = 'background: red; color: white;';
				}

				if($markup == 'saturday')
				{
					$bufRow['celstyle'] = 'background: orange; color: white;';
				}

				$thtTableContent .= generateHTML('sampleBuffers/portalThtRow', $bufRow);
			} 

			else{

			
			//rodac 
			if($bufRow['sample_research_type'] == '2')
			{

				$props = json_decode($bufRow['sample_properties'], JSON_FORCE_OBJECT);
				$props = array_column($props, null, 'property_name');

				$bufRow['room_desc'] = checkKeyOrBlank($props, 'rodacRoom', 'value');				
				$bufRow['sampling_time'] = checkKeyOrBlank($props, 'rodacSamplingTime', 'value');				

				$bufRow['rowColour'] = ($rodacStriped == True) ? '#bcbcbc' : 'white';	
				$rodacStriped = !$rodacStriped;
				$sampleCounts['rodac'] = $sampleCounts['rodac'] + 1;
				$rodacTableContent .= generateHTML('sampleBuffers/portalRodacRow', $bufRow);
			}

			//legionella
			elseif($bufRow['sample_research_type'] == '3')
			{

				$props = json_decode($bufRow['sample_properties'], JSON_FORCE_OBJECT);
				$props = array_column($props, null, 'property_name');

				
				
				$bufRow['water_type'] = checkKeyOrBlank($props, 'waterType', 'value');
				$bufRow['matrix_type'] = checkKeyOrBlank($props, 'matrixType', 'value');
				$bufRow['sampling_time'] = checkKeyOrBlank($props, 'waterSamplingTime', 'value');				

				$bufRow['rowColour'] = ($legStriped == True) ? '#bcbcbc' : 'white';	
				$legStriped = !$legStriped;
				$sampleCounts['leg'] = $sampleCounts['leg'] + 1;
				$legTableContent .= generateHTML('sampleBuffers/portalLegRow', $bufRow);
			}

			//all else 
			else{

				$bufRow['color'] = ($normalStriped == True) ? '#bcbcbc' : 'white';	
				$normalStriped = !$normalStriped;


				$sampleCounts['normal']= $sampleCounts['normal'] + 1;
				$tablecontent .= generateHTML('sampleBuffers/portalRow', $bufRow);
			}

			}


			
		}

		$this->_template->set('count_tht', $sampleCounts['tht']);
		$this->_template->set('count_samples', $sampleCounts['normal']);
		$this->_template->set('count_leg', $sampleCounts['leg']);
		$this->_template->set('count_rodac', $sampleCounts['rodac']);

		$this->_template->set('rows', $tablecontent);
		$this->_template->set('tht_rows', $thtTableContent);
		$this->_template->set('leg_rows', $legTableContent);
		$this->_template->set('rodac_rows', $rodacTableContent);

		$this->_template->set('normal_active', '');
		$this->_template->set('tht_active', '');
		$this->_template->set('leg_active', '');
		$this->_template->set('rodac_active', '');

		if($tab == 'normal')
		{
			$this->_template->set('normal_active', 'active');
		}

		if($tab == 'tht')
		{
			$this->_template->set('tht_active', 'active');
		}
		
		if($tab == 'leg')
		{
			$this->_template->set('leg_active', 'active');
		}

		if($tab == 'rodac')
		{
			$this->_template->set('rodac_active', 'active');
		}

		

	}

	public function stagedTht($sort = 'tht_date', $dir = 'ASC'){

		$params = array();

		if (preg_match('/[^a-z0-1_]/i', $sort)) {
			die();
		}
		if (!preg_match('/^(asc|desc)$/i', $dir)) {
			die();
		}
		

	
		$sql = "SELECT samplebuffers.*,clients.name AS client_name
			FROM samplebuffers 
			LEFT JOIN clients
			ON samplebuffers.client = clients.id
			WHERE samplebuffers.tht = 1 AND samplebuffers.authorized = 1 
			ORDER BY "  . $sort . " " . $dir;

		$rows = $this->SampleBuffer->customQuery($sql, $params);		

		$samplingProcs = upa('sampleProcedures', 'getProceduresArr', array(), False);
		$samplingProcDrop = upa('sampleProcedures', 'getProcedureDropDown', array(False), False);
		$this->_template->set('procedures', $samplingProcDrop);
		
		$thtTableContent = '';
		$thtStriped = false;

		$holidays = new Holidays();

		foreach($rows as $bufRow){



			$markup = $holidays->getTypeOfSpecialDate($bufRow['tht_date']);


			if($bufRow['source'] == 3)
			{
				$projectTickName = 'portal_' . $bufRow['portal_project'];
			}
			else
			{
				$projectTickName =  $bufRow['project'];
			}

			$bufRow['projectTickName'] = $projectTickName;

			$bufRow['icon'] = $this->getIcon($bufRow['source']);			

			if(array_key_exists($bufRow['sampling_method'], $samplingProcs)){
				$bufRow['sampling_name'] = $samplingProcs[$bufRow['sampling_method']];
			}else{
				$bufRow['sampling_name'] = $samplingProcs['0'];
			}

			$thtStatus = $this->thtStateAndDate($bufRow['tht'], $bufRow['tht_date']);

			$bufRow['tht_state'] = $thtStatus[0];
			$bufRow['tht_date'] = $thtStatus[1];
			$bufRow['celstyle'] = '';
			
			if($markup == 'holiday' || $markup == 'sunday')
			{
				$bufRow['celstyle'] = 'background: red; color: white;';
			}

			if($markup == 'saturday')
			{
				$bufRow['celstyle'] = 'background: orange; color: white;';
			}


						
			//$bufRow['receive_date'] = $bufRow;
			//$bufRow['receive_time'] = '';

			$conflict = $thtStatus[2];

			$bufRow['rowColour'] = ($thtStriped == True) ? '#bcbcbc' : 'white';	
			
			if($conflict == True){
				$bufRow['rowColour'] = ($thtStriped == True) ? '#00e773' : 'springgreen';									
			} 

			$thtStriped = !$thtStriped;

			$meta = json_decode($bufRow['meta'], JSON_FORCE_OBJECT);

			if(!empty($meta))
			{
				$bufRow['meta'] = 'Ja';
			} 
			
			else
			{
				$bufRow['meta'] = 'Nee';
			}

			$bufRow['import_requested_analysis'] = '';			
			if($bufRow['source'] === '3'){
				
				$portalInfo = json_decode($bufRow['portal_analyses'], JSON_FORCE_OBJECT);				
				$portalText = ' <span class="label">'. $portalInfo['profile'] . '</span>';
				
				if(is_array($portalInfo)){
								
				if($portalInfo['profile_id'] == NULL){

					foreach($portalInfo['assays_all'] as $idx => $allAssays)
					{
					  $portalText = $portalText . ' <span class="label label-info"><i class="icon icon-plus"></i>' . $allAssays . '</span>';
					}
				} 
				
				else{
	
					foreach($portalInfo['assays_addition'] as $idx => $addedAssay){
					  $portalText = $portalText . ' <span class="label label-success"><i class="icon icon-plus"></i>' . $addedAssay . '</span>';
					}
	
					foreach($portalInfo['assays_substraction'] as $idx => $subbedAssay){
					  $portalText = $portalText . ' <span class="label label-warning"><i class="icon icon-minus"></i>' . $subbedAssay . '</span>';
					}
				  }
						  
				  $thisSambufRowple['show_requested_analysis'] = '';
				  $bufRow['import_requested_analysis'] = $portalText;				  

				}

			}

			$thtTableContent .= generateHTML('sampleBuffers/authThtRow', $bufRow);
		}

		$this->_template->set('tht_rows', $thtTableContent);

	}

	public function changeReceiveDate()
	{
		$this->render = False; 

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);

		foreach($commitIDs as $commitID)
		{
			$sample = new SampleBuffer; 
			$sample->where('id', $commitID );
			$sample = $sample->first();

		
			$this->SampleBuffer->id = $sample['id'];			
			$this->SampleBuffer->receive_time = $_POST['commit_receive_time'];
			$this->SampleBuffer->receive_date = $_POST['commit_receive_date'];			
			$this->SampleBuffer->save();

		}

		$this->reRoute('sampleBuffers/stagedTht', True);
		
	}


	private function sortBySlug($buffer){
		$orderBuffer = array();
		foreach($buffer as $bufferSample){

			if((int)$bufferSample['tht'] == 1 )
			{
				$slug = $bufferSample['project'] . $bufferSample['tht_date'];
			}

			else
			{
				$slug = $bufferSample['project'];
			}
			

			if(!array_key_exists($slug,	$orderBuffer)){
				$orderBuffer[$slug] = array();
			}

			$index = count($orderBuffer[$slug]);
			$orderBuffer[$slug][$index]  = $bufferSample;
		}

		return $orderBuffer;

	}

	private function checkFutureSamples($buffer){
		foreach($buffer as $projectSlug => $projectContent){

			//cull any future THT samples
			foreach($projectContent as $sampleIdx => $sample){
				if($sample['tht'] == 1 && (time() < strtotime($sample['tht_date']) )){
					unset($buffer[$projectSlug][$sampleIdx]);
				}
			}

			//check if there is anything left?
			$slugSize = count($buffer[$projectSlug]);
			if($slugSize < 1){
				unset($buffer[$projectSlug]);
			}
		}
		return $buffer;
	}

	private function removeFromBuffer($buffer){
		foreach($buffer as $projectSlug => $projectContent){
			//cull any future THT samples
			foreach($projectContent as $sampleIdx => $sample){
				$this->SampleBuffer->id = $sample['id'];
				$this->SampleBuffer->remove();
			}
		}
	}

	/* Set sample buffers as now registered */ 
	private function setRegistered(){
		
	}

	/**
	 * setAuth
	 * 
	 * sets the authorized flag of a THT samples to true
	 *
	 * @return void
	*/		
	public function setAuth(){		
		
		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);				
		
		//sort ids from small to large
		asort($commitIDs);
			
		foreach($commitIDs as $commitID){
			
			$this->SampleBuffer->where('id', $commitID);
			$this->SampleBuffer->limit('1');			
			
			$result = $this->SampleBuffer->first();

			$this->SampleBuffer->deepFreed();

			if($result)
			{
				
				if((string)$result['authorized'] === '1')
				{
					//cant authorize twice
					continue; 
				}
				
				$this->SampleBuffer->deepFreed();

				$this->SampleBuffer->id = $commitID;
				$this->SampleBuffer->authorized = 1;
				
				//attach tht_code 
				$thtCode = upa('samples', 'generateBarcode', array(False, 'THT'), False);			
				$this->SampleBuffer->tht_code = $thtCode;

				//set rec date and time to indicated date/time
				$this->SampleBuffer->receive_time = $_POST['commit_receive_time'];
				$this->SampleBuffer->receive_date = $_POST['commit_receive_date'];
				$this->SampleBuffer->save();

				unset($this->SampleBuffer->id);
				$this->SampleBuffer->deepFreed();

			}


			
		}

		$this->SampleBuffer->deepFreed();
		

		upa('printing', 'batchTHTReg', array($commitIDs), False);

		//notify portal about changes  		
		$sql = 'SELECT `id`,`portal_id` FROM `samplebuffers` WHERE `id` IN (' . implode(',', $commitIDs) . ') AND `portal_id` IS NOT NULL ';		
		$result = $this->SampleBuffer->customQuery($sql, array());				
		$portalIds =  array_column($result, 'portal_id');

		if(!empty($portalIds)){
			upa('portal', 'setTHTflag', array($portalIds), False );
		}

 		$this->reRoute('sampleBuffers/portal', True);
	}

	public function destroy(){

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);
		$portalIDs = array();

		foreach($commitIDs as $commitID){
			
			$this->SampleBuffer->deepFreed();
			$this->SampleBuffer->where('id', $commitID);
			$this->SampleBuffer->limit('1');
			$result = $this->SampleBuffer->search();

			if(!empty($result)){
				if($result[0]['portal_id'] !== NULL){
					array_push($portalIDs, $result[0]['portal_id']);
				}

				$this->SampleBuffer->deepFreed();
				$this->SampleBuffer->id = $commitID;
	 			$this->SampleBuffer->remove();
			}
		}

		//need to notify portal about this.
		upa('portal', 'destroySample', array($portalIDs), False );

 		if(isset($_POST['thtFlag'])){
			$this->reRoute('sampleBuffers/stagedTht', True);
		} else{
			$this->reRoute('sampleBuffers/portal', True);
		}
	}

	public function changeInnocTarget(){

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);
		$stamp = strtotime($_POST['date']);

		foreach($commitIDs as $commitID){
			$this->SampleBuffer->id = $commitID;
 			$this->SampleBuffer->tht_date = date('Y-m-d', $stamp);
 			$this->SampleBuffer->save();
		}

		if(isset($_POST['thtFlag'])){
			$this->reRoute('sampleBuffers/stagedTht', True);
		} else{
			$this->reRoute('sampleBuffers/portal', True);
		}

	}

	public function updateWaterType()
	{

		$this->render = False; 

		parray($_POST);

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);



		foreach($commitIDs as $commitID){
			
			$thisBuffer = upa('sampleBuffers', 'fetch', array($commitID), False);

			$props = json_decode($thisBuffer['sample_properties'], JSON_FORCE_OBJECT);			

			//a bit shitty, but it works. there will never be that many props to start with
			//so we can just do a quick-n-dirty loop to update the right keys
			foreach($props as $idx => $prop)
			{
			
				if($prop['property_name'] == 'waterType')
				{
					$props[$idx]['value'] = $_POST['watertype'];
				}

				if($prop['property_name'] == 'matrixType')
				{
					$props[$idx]['value'] = $_POST['matrixtype'];
				}

				if($prop['property_name'] == 'matrixBaseProfile')
				{
					$props[$idx]['value'] = $_POST['matrixBaseProfile'];
				}
			}

						
			$this->SampleBuffer->id = $commitID;
			$this->SampleBuffer->sample_properties = json_encode($props, JSON_FORCE_OBJECT);
 			$this->SampleBuffer->save();
		}

 		$this->reRoute('sampleBuffers/portal/0/0/leg', True);
	}

	public function updateSamplingDate(){

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);

		foreach($commitIDs as $commitID){
			$thisBuffer = upa('sampleBuffers', 'fetch', array($commitID), False);
			$newSlug = $this->getProjectSlug($_POST['date'], $thisBuffer['client']);
			$this->SampleBuffer->id = $commitID;
 			$this->SampleBuffer->sampling_date = $_POST['date'];
 			$this->SampleBuffer->project = $newSlug;
 			$this->SampleBuffer->save();
		}

 		$this->reRoute('sampleBuffers/portal', True);
	}
	

	public function commitFromBuffer(){

	

		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);


		if(is_array($commitIDs) && !empty($commitIDs))
		{
			foreach($commitIDs as $commitID){
				$this->SampleBuffer->where('id', $commitID);
				$this->SampleBuffer->insertOR();
			}
	
			$buffer = $this->SampleBuffer->search();
				
			
	
			if(!empty($buffer)){
				$buffer = $this->sortBySlug($buffer);
				//$buffer = $this->checkFutureSamples($buffer);
	
				//commit buffer
				upa('samples', 'commitBuffer', array($buffer), False);
						
				//remove current from buffer
				$this->removeFromBuffer($buffer);
			}
	
		}

				
		if(isset($_POST['thtFlag'])){
			$this->reRoute('sampleBuffers/stagedTht', True);
		} else{
			$this->reRoute('sampleBuffers/portal', True);
		}
	}

	public function updateSamplingMethod(){
		$commitIDs = json_decode($_POST['commit'], JSON_FORCE_OBJECT);

		foreach($commitIDs as $commitID){
			$this->SampleBuffer->id = $commitID;
 			$this->SampleBuffer->sampling_method = $_POST['sampling'];
 			$this->SampleBuffer->save();
		}

 		$this->reRoute('sampleBuffers/portal', True);
	}

	public function countBuffer(){
		$this->SampleBuffer->where('authorized', 0);		
		return $this->SampleBuffer->countIds();
	}

	public function countTHT(){
		#$today = date('Y-m-d', time());
		$today = date('Y-m-d', time());
		$dateObject = new DateTime($today);
		$today = $dateObject->add( new DateInterval('P1D'))->format('Y-m-d');

		$this->SampleBuffer->where('authorized', 1);
		$this->SampleBuffer->lessThan('tht_date', $today);
		return $this->SampleBuffer->countIds();		
	}

	public function openTHT(){
		$count = $this->SampleBuffer->search();
		$tF = new tableFactory();
        $tF->loadTemplate('runningConfTable');
        $tF->loadValues($running);
        $this->_template->set('table', $tF->renderTable());
	}

	public function editBufferedMetadata($id = False){
        $this->doNotRenderHeader = True;

		if($id == False && isset($_POST['id'])){
			$id = $_POST['id'];
		}

		$this->SampleBuffer->where('id', $id);
		$result = $this->SampleBuffer->search();


		if(empty($result)){
			return;
		}

		$result = $result[0];
		$meta = json_decode($result['meta']);
		$metaNumbered = array();
		$fakeNumber = 0;
		$saveByIndex = 0;

		if(!empty($meta))
		{
			foreach($meta as $metaKey => $metaVal){

				//new method, accounting for metadata-key order as provided from client portal 
				//$meta is now a array of arrays			
				$saveByIndex = 0;
	
				if(is_array($metaVal))
				{
					$metaKey = $metaVal[0];
					$metaVal = $metaVal[1];
					$saveByIndex = 1;
				}
	
				
				array_push($metaNumbered, array('name' => $metaKey, 'value' => $metaVal, 'brvalue' => nl2br($metaVal), 'id' => $id, 'index' => $fakeNumber, 'saveByIndex' => $saveByIndex));						
				$fakeNumber++;
			}
		}

		$tF = new tableFactory();
        $tF->loadTemplate('bufferMetadataTable');
        $tF->loadValues($metaNumbered);
        $this->_template->set('meta_table', $tF->renderTable());
	}

	public function changeBufferedMeta(){

	  //data: { 'id' : id, 'original': originalValue, 'newValue' : newValue, 'changeType' : changeType, saveByIndex},
		$this->render = false;
		$id = $_POST['id'];
		$saveByIndex = filter_var($_POST['saveByIndex'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

		$this->SampleBuffer->where('id', $id);
		$result = $this->SampleBuffer->search();

		if(empty($result)){
			return;
		}

		$result = $result[0];
		$meta = json_decode($result['meta']);


		if($_POST['changeType'] == 'name')
		{

			//check if found, get value for that key
			if($saveByIndex)
			{
				$meta = $this->changeByIndex($meta, $_POST['index'], False, $_POST['newValue'] );				
			}

			else
			{
				$meta = $this->changeByKey($meta, $_POST['changeKey'], False, $_POST['newValue']);
			}

		}

		if($_POST['changeType'] == 'value')
		{

			if($saveByIndex)
			{
				$meta = $this->changeByIndex($meta, $_POST['index'], $_POST['newValue'], False );				
			}

			else
			{
				$meta = $this->changeByKey($meta, $_POST['changeKey'],  $_POST['newValue'], False);
			}		

		}

		$this->SampleBuffer->deepFreed();
		$this->SampleBuffer->id = $id;
		$this->SampleBuffer->meta = json_encode($meta);
		$this->SampleBuffer->save();


	}

	private function changeByKey($meta, $key, $value, $newKeyName = False)
	{

		if(!property_exists($meta, $key))
		{
			return $meta;
		}

		//key change
		if($newKeyName !== False)
		{
			$previousValueForKey = $meta->$key;
			$meta->$newKeyName = $previousValueForKey;
			unset($meta->$key);			
			return $meta;
		}

		//value change
		else
		{
			$meta->$key = $value; 
			return $meta; 
		}


	}

	private function changeByIndex($meta, $index, $value, $newKeyName = False)
	{

		$slice = $meta[$index];
		
		if($newKeyName !== False)
		{
			$keyValue = $slice[1];
			$updatedSlice = array();
			$updatedSlice[0] = $newKeyName;
			$updatedSlice[1] = $keyValue; 
			$meta[$index] = $updatedSlice;
			return $meta; 
		}

		else
		{
			$slice[1] = $value; 
			$meta[$index] = $slice;
			return $meta; 
		}		

	}

}

