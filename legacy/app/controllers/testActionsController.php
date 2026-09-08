<?PHP

class testActionsController extends Controller {
    
    
    public function getTestActionsForSet($testId)
    {
        $this->TestAction->where('test_id', $testId);
        $this->TestAction->order('order', 'asc');
        $testActions = $this->TestAction->search();
        return $testActions;
    }


    public function addTestAction($testId, $testSetId)
    {
        $this->render = False; 

        $this->TestAction->where('test_id', $testId);
        $result = $this->TestAction->search();

        $this->TestAction->deepFreed();

        $this->TestAction->test_id = $testId;
        $this->TestAction->order = count($result) + 1;
        $this->TestAction->data = '{}';
        $this->TestAction->save();

        $this->reRoute('testSets/edit/' . $testSetId, True);
    }

    public function saveField()
    {
        $this->render = False; 
        
        $this->TestAction->id = $_POST['id'];        
        $this->TestAction->{$_POST['field']} = $_POST['value'];
        $this->TestAction->save();

    }

    public function destroy($id, $testSetId = False)
    {
        
        $this->render = False;
        $this->TestAction->id = $id; 
        $this->TestAction->delete();

        if($testSetId)
        {
            $this->reRoute('testSets/edit/' . $testSetId, True);
        }
        
    }

    public function cloneFromOtherTest($testSetId, $id, $cloneFrom)
    {
        $this->render = False;
        $actionsToClone = upa('testActions', 'getTestActionsForSet', array($cloneFrom), False);

        foreach($actionsToClone as $clone)
        {
            $this->TestAction->test_id = $id;
            $this->TestAction->action = $clone['action'];
            $this->TestAction->data = $clone['data'];
            $this->TestAction->order = $clone['order'];
            $this->TestAction->save();
        }


        $this->reRoute('testSets/edit/' . $testSetId, True);

    }

}