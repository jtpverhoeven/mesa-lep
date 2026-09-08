<?PHP

class Client extends Model{
    
    function getAllFirsts(){
        
        
        
    }

    function getAllActive()
    {
        $this->where('active', 1);
        return $this->search();   
    }
    
}