<?PHP

class Test extends Model{   
    
    
    protected function Actions()
    {
        $actions = upa('testActions', 'getTestActionsForSet', array($this->id), False);

    }
    
    
}