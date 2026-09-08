<?PHP

class testsController extends Controller {

    
    public function run($id)
    {        
        $printbool = filter_var($_POST['printbool'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $priorPrintSetting = upa('cvars', 'grabCvar', array('MESA_DISABLE_PRINTING'), False);



        if($printbool === True)
        {
            upa('cvars', 'cvarSaveName', array('MESA_DISABLE_PRINTING', "0"), False);
        }

        else
        {
            
            upa('cvars', 'cvarSaveName', array('MESA_DISABLE_PRINTING', "1"), False);
        }
                
        $this->render = False; 
        $testing = new testing($id, $_POST['group'], $_POST['projectId']); 
        $testing->run();      

        upa('cvars', 'cvarSaveName', array('MESA_DISABLE_PRINTING', $priorPrintSetting), False);

        print(json_encode($testing, JSON_FORCE_OBJECT));        
        
    }

    public function runSingle($id, $print = True)    
    {        
        
        $printbool = filter_var($print, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $priorPrintSetting = upa('cvars', 'grabCvar', array('MESA_DISABLE_PRINTING'), False);


        if($printbool === True)
        {
            upa('cvars', 'cvarSaveName', array('MESA_DISABLE_PRINTING', "0"), False);
        } 

        else
        {
            
            upa('cvars', 'cvarSaveName', array('MESA_DISABLE_PRINTING', "1"), False);
        }

        //print( upa('cvars', 'grabCvar', array('MESA_DISABLE_PRINTING'), False));
        //return;
                        
        $testing = new testing($id, False, False); 
        $testing->run();      

        upa('cvars', 'cvarSaveName', array('MESA_DISABLE_PRINTING', $priorPrintSetting), False);

        $this->_template->set('log', $testing->fullLog);
    }

    public function getTestsForTestSet($testset_id)
    {
        $this->Test->where('testset_id', $testset_id);        
        $results = $this->Test->search();
        return $results;

    }

    public function addTest($testset_id, $reroute = True)    
    {
        $this->render = False; 
        $this->Test->name = 'Nieuwe test';
        $this->Test->testset_id = $testset_id;
        $this->Test->save();

        if($reroute === True)
        {
            $this->reRoute('testSets/edit/' . $testset_id, True);
        }
    }

    function changeName()
    {
        $this->render = False; 
        $this->Test->id = $_POST['id'];
        $this->Test->name = $_POST['name'];
        $this->Test->save();
    }

    function destroy($id)
    {
        $this->Test->id = $id;
        $this->Test->delete();
    }

    function destroySelf($id, $testset_id = False)
    {        
        
       
        $actions = upa('testActions', 'getTestActionsForSet', array($id));

        foreach($actions as $action)
        {
            upa('testActions', 'destroy', array($action['id']), False);
        }

        $this->destroy($id);
        
        $this->reRoute('testSets/edit/' . $testset_id, True);
    }


}