<?PHP

class analysis extends model{
    
    function listAnalysis(){       
        $results = $this->search();
        return $results;
    }
    
    
}