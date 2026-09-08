<?PHP

class Packet extends model {
 
    function listPackets(){
        $results = $this->search();
        return $results;
    }
    
}
