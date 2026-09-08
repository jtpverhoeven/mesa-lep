<?PHP

class referenceSourcesController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }



    public function index()
    {
        $sources = $this->ReferenceSource->search();         
        
        if(empty($sources)){
            $sources = 'Geen bronnen ';
        }         
        
        else{
            foreach($sources as $idx=>$source)
            {
                $names = json_decode($source['name'], JSON_FORCE_OBJECT);
                $sources[$idx]['name']  = $names['nl'];
                if(!is_null($source['client']))
                {
                    $sources[$idx]['client'] = customerIdToName($source['client']); 
                }
            }
        }



        $tF = new tableFactory();
        $tF->setTableId('refTable');
        $tF->loadTemplate('referenceSourceTable');
        $tF->loadValues($sources);            
        
        $this->_template->set('table', $tF->renderTable() );
        

    }


    public function edit($id = False)
    {
        
        if($id !== False)
        {
            $this->ReferenceSource->where('id', $id);
            $results = $this->ReferenceSource->search(); 
            if(!$results)
            {
                $this->reRoute('referenceSources/index', True);
        
            }

            $this->ReferenceSource->arrayToModel($results['0']);
            
        }
        

        $sForm = new formFactory('referenceSources');

        $sForm->setId('editSourceForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/referenceSources/save');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $names = $this->ReferenceSource->castFromArray();         


        $boolOp['1'] = '{MESA_ERP_GLOBAAL}';
        $boolOp['0'] = '{MESA_ERP_CLIENTSPECIFIC}';
         
        
        $global = ($this->ReferenceSource->client == null) ? 1 : 0;
        
        $sForm->addDropdownField('global', '{MESA_ERP_PROFILETYPE}', 'input-block-level', $global, $boolOp, False);

        $sForm->addInputField('client_name', '{MESA_ERP_FORCLIENT}', 'text', 'ajax-typeahead input-block-level ', $this->ReferenceSource->client, 'Start typing client name', array('autocomplete' => 'off'));
        $sForm->addInputField('client', False, 'hidden', 'hidden', False, $this->ReferenceSource->client, '');

        $sForm->addInputField('name_nl', 'Bron naam NL', 'text', 'input-block-level', checkKeyOrBlank($names, 'nl'), '{MESA_ASE_ASSAYNAME}', False, False);
        $sForm->addInputField('name_en', 'Bron naam ENG', 'text', 'input-block-level', checkKeyOrBlank($names, 'en'), '{MESA_ASE_ASSAYNAME}', False, False);

        $sForm->submitTrough('saveRefSource');   

        if ($this->ReferenceSource->client != 0) 
        {
            $clientName = customerIdToName($this->ReferenceSource->client);            
        } 
        
        else         
        {
            $clientName = False;
        }

        $sForm->addInputField('id', False, 'hidden', 'hide', $this->ReferenceSource->id, False);                        
        $this->_template->set('editFieldForm', $sForm->render() );
        
    }

    public function save() 
    {
        $this->render = 0;

        $names = [
            'nl' => $_POST['name_nl'],
            'en' => $_POST['name_en']        
        ];

        if(!empty($_POST['id']))
        {

            $this->ReferenceSource->id = $_POST['id'];
        }

        $client = (!empty($_POST['client'])) ? $_POST['client'] : null;

        $this->ReferenceSource->name = json_encode($names, JSON_FORCE_OBJECT);
        $this->ReferenceSource->client = $client;
        $this->ReferenceSource->save();

        //bit of hack, since alpaca does not allow us to store an actual NULL value.
        if(!empty($_POST['id']) && is_null($client))
        {
            $sql = 'UPDATE referencesources SET client = NULL WHERE id = :id ';
            $params = array(); 
            $params['id'] = $_POST['id'];
            $null = $this->ReferenceSource->customSetQuery($sql, $params);
        }


        $this->reRoute('referenceSources/index', True);
    }

   
    public function list($client = False, $asArray = True)
    {
        $this->render = False; 

        $sql = "SELECT * FROM referencesources WHERE client IS NULL";
        $params = array(); 

        if($client !== False)
        {
            
            $sql = $sql . " OR client = :client ";
            $params['client'] = $client; 
        }

        $results = $this->ReferenceSource->customQuery($sql, $params); 

        $op = ['' => 'Geen bron'];
        $keyedArray = [];
        foreach($results as $result)
        {
            $names = json_decode($result['name'], JSON_FORCE_OBJECT); 
            $op[$result['id']] = checkKeyOrBlank($names, 'nl'); 
            $keyedArray[$result['id']] = $result; 
        }

        return ($asArray) ? $op : $keyedArray;
    }   

    public function dumpAll()
    {
        $this->render = false; 
        $sources =$this->ReferenceSource->search();         
        $sourcesArr = [];

        foreach($sources as $source)
        {
            $names = json_decode($source['name'], JSON_FORCE_OBJECT); 
            $sourcesArr[$source['id']] = checkKeyOrBlank($names, 'nl');             
        }

        return $sourcesArr; 
    }

    public function destroy($id)
    {

        //uncouple from profiles

        $sql = "UPDATE assayprofiles SET reference_source = NULL WHERE reference_source = :id";
        $params = array(); 
        $params['id'] = $id;

        $unlink = $this->ReferenceSource->customSetQuery($sql, $params);

        $this->ReferenceSource->id = $id;
        $this->ReferenceSource->remove();

        $this->reRoute('referenceSources/index', True);

    }

}
