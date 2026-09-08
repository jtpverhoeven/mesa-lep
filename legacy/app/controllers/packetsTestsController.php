<?PHP

class packetsTestsController extends controller{
    
    function get($packetId){        
        $this->render = 0;
        $this->PacketsTest->where('packet', $packetId);
        $results = $this->PacketsTest->search();        
        return $results;
        
    }
    
    function addToPack($packetId = False, $testId = False){
        
        $this->render = 0;
        
        if($packetId == False && $testId == False){
            $testId = $_POST['test'];
            $packetId = $_POST['packet_id'];
        }
                        
        $this->PacketsTest->packet = $packetId;
        $this->PacketsTest->flow_id = $testId;
        $this->PacketsTest->save();
        
        $this->reRoute('packets/edit/' . $packetId, True );        
    }
    
    function removeFromPack($ptId){
        $this->render = 0;
        $this->PacketsTest->id = $ptId;
        $this->PacketsTest->remove();
    }
    
    function removeAll($packetId){
        
        $this->render = 0;
        $this->PacketsTest->where('packet', $packetId);
        $result = $this->PacketsTest->search();
        
        foreach($result as $foundTest){
            $this->PacketsTest->id = $foundTest['id'];
            $this->PacketsTest->remove();
        }
        
    }
    
    function getPacketTests($packetId){                
        $this->render = 0;
        $this->PacketsTest->where('packet', $packetId);
        $results = $this->PacketsTest->search();        
        return $results;        
    }
    
}