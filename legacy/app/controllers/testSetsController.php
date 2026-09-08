<?PHP

class testSetsController extends Controller {
    
    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    //list of tests
    function listing()
    {
        $this->TestSet->order('name', 'ASC');
        $this->TestSet->search();
        $results = $this->TestSet->search();

        $table = new tableFactory();
        $table->setTableId('testsetTable');
        $table->loadTemplate('testTable');

        if(empty($results)){
            $results = 'Nog geen tests aangemaakt';
        }

        $table->loadValues($results);
        
        $sForm = new formFactory('testSets');
        $sForm->setId('addTestSetForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/testSets/save');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', '', '{MESA_ASE_ASSAYNAME}', False, False);

        $sForm->submitTrough('addTestSubmit');

        $this->_template->set('addTestForm', $sForm->render());
        $this->_template->set('test_table', $table->renderTable());

    }

    public function save()
    {
        $this->render = False; 

        $this->TestSet->name = $_POST['name'];
        $this->TestSet->save();
        $id = $this->TestSet->lastInsertId;

        $this->reRoute('testSets/edit/' . $id, True);

    }

    
    function edit($id)
    {

        $this->render = True; 

        $this->TestSet->where('id', $id);
        $set = $this->TestSet->search();

        if(empty($set))
        {
            $this->reRoute('testSets/listing', True);
        }

        $tests = upa('tests', 'getTestsForTestSet', array($id), False);        

        $testRender = '';
        
        
        foreach($tests as $test)        
        {
            $actionRender = '';

            $actions = upa('testActions', 'getTestActionsForSet', array($test['id']));

            foreach($actions as $action)
            {
                $action['testset_id'] = $test['testset_id'];
                $actionRender .= generateHTML('testaction', $action);
            }

            $testRender .= generateHTML('testcontainer', array('name' => $test['name'], 'id' => $test['id'], 'testset_id' => $test['testset_id'], 'actions' => $actionRender));

        }

        $this->_template->set('render', $testRender );
        $this->_template->set('id', $id);
        $this->_template->set('name', $set[0]['name']);
        

    }

    //splash screen for running a test-set 
    function run($id)
    {
        $this->TestSet->where('id', $id);
        $set = $this->TestSet->search();

        if(empty($set))
        {
            $this->reRoute('testSets/listing', True);
        }

        $tests = upa('tests', 'getTestsForTestSet', array($id), False);        
        $set = $set[0];

        $table = new tableFactory();
        $table->setTableId('testsetTable');
        $table->loadTemplate('testRunTable');

    

        foreach($tests as $idx =>$test)        
        {         

            $actions = upa('testActions', 'getTestActionsForSet', array($test['id']));
            $tests[$idx]['actionCount'] = count($actions);


        }

        if(empty($tests)){
            $tests = 'Nog geen tests aangemaakt';
        }

        $table->loadValues($tests);


        $this->_template->set('run', $table->renderTable());
        $this->_template->set('id', $id);
        $this->_template->set('name', $set['name']);
        $this->_template->set('tests', json_encode($tests));
    }

  
    
    //remove set + tests + actions
    function destroy($id)
    {
        $tests = upa('tests', 'getTestsForTestSet', array($id), False);        
        
        foreach($tests as $idx =>$test)        
        {         

            $actions = upa('testActions', 'getTestActionsForSet', array($test['id']));

            foreach($actions as $action)
            {
                upa('testActions', 'destroy', array($action['id']), False);
            }

            upa('tests', 'destroy', array($test['id']), False);

        }

        $this->TestSet->id = $id;
        $this->TestSet->delete();
        
    }
    

}