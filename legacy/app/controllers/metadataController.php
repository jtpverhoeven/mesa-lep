<?PHP

class metadataController extends controller{

	function beforeAction($queryString) {
     $this->_template->set('MESA_LIMS_ACTIVE', 'active');
     $this->_template->set('MESA_SOCIAL_ACTIVE', '');
     $this->_template->set('MESA_ADMIN_ACTIVE', '');
	}

	function postSave()
	{
		$order = $this->grabNewOrder($_POST['sample']);
		$this->add($_POST['sample'], $_POST['name'], $_POST['value'], NULL, $order);
	}

	private function grabNewOrder($sample)
	{
		$this->Metadata->where('sample', $sample);
		$metaPresent = $this->Metadata->search(); 
		$this->Metadata->deepFreed();		
		$lastOrder = count($metaPresent);
		return ($lastOrder === 0) ? 1 : $lastOrder + 1; 
	}

	function add($sample, $name, $value, $meta_data_key_id = NULL, $order = NULL)
	{
		$this->render = false;
		$this->Metadata->sample = $sample;
		$this->Metadata->name = $name;
		$this->Metadata->value = $value;
		$this->Metadata->meta_data_key_id = $meta_data_key_id;
		$this->Metadata->meta_order = $order; 
		$this->Metadata->save();
		$event = 'Metadata toegevoegd';
		upa('changeTracker', 'changed', array(18, False, $sample, False, $event, $name, $value), False);
	}

	function delete($id){
		$metaDatInfo = upa('metadata', 'fetch', array($id), False);
		if(!empty($metaDatInfo)){
			$this->Metadata->id = $id;
			$this->Metadata->delete();
			$event = 'Metadata verwijderd';
			upa('changeTracker', 'changed', array(19, False, $metaDatInfo['sample'], False, $event, $metaDatInfo['name'], $metaDatInfo['value']), False);
		}
	}

	function forSample($id){
		$this->Metadata->where('sample', $id);
				
		return $this->Metadata->search();
	}

	function metdataEditForm($sample, $disable = False){
		$this->render = false;
		$this->Metadata->where('sample', $sample);
		$metadata = $this->Metadata->search();

		if(empty($metadata)){
			return array('found' => False, 'table' => '');
		}

		$sampleInfo = upa('samples', 'fetch', array($sample), False);
    	$partOfAuth = upa('samples', 'partOfAuthProject', array($sample));

    	$class = '';
    	if($partOfAuth === True || $disable === True){
    		$class = 'disabled';
    	}

    	$metadata = array_map(function($arr) use (&$class){
    		return $arr + ['class' => $class];
		}, $metadata);

    	$tF = new tableFactory();
        $tF->loadTemplate('metadataProject');
        $tF->loadValues($metadata);
    	return array('found' => True, 'table' => $tF->renderTable());
	}

	function change(){
		$this->render = False;
		$partOfAuth = upa('samples', 'partOfAuthProject', array($_POST['sample']));

		if($partOfAuth == True){
			return;
		}

		$this->Metadata->id = $_POST['id'];

		if($_POST['type'] == 'key'){
			$this->Metadata->name = $_POST['value'];
		}

		if($_POST['type'] == 'value'){
			$this->Metadata->value = $_POST['value'];
		}

		$this->Metadata->save();
	}
}
