<?PHP

class ReferenceSource extends model{
 
    
    public function castFromArray()
    {
        $arr = json_decode($this->name, JSON_FORCE_OBJECT);
        return $arr;
    }
    
}