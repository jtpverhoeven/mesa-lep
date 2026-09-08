<?PHP

class flowConnectionsController extends controller{
    
    const DIRECTION_OUT = 'OUT';
    const DIRECTION_IN = 'IN';
    
    function getConnections($flowId, $component, $direction = self::DIRECTION_OUT){
                        
        if($direction == self::DIRECTION_OUT){
            $this->FlowConnection->where('flow', $flowId );
            $this->FlowConnection->where('component', $component );
            $this->FlowConnection->where('dir', $direction);
            $results = $this->FlowConnection->search();
        } else {
            $this->FlowConnection->where('flow', $flowId );
            $this->FlowConnection->where('target', $component );
            $results = $this->FlowConnection->search();
        }                
        
        
        return $results;
        
    }
    
    function addConnection($flow, $component, $target, $dir = self::DIRECTION_OUT){
        
        $this->render = 0;
        $this->FlowConnection->flow = $flow;
        $this->FlowConnection->component = $component;
        $this->FlowConnection->target = $target;
        $this->FlowConnection->dir = $dir;
        $this->FlowConnection->save();
        
        
        
    }
}