<?PHP

class sample extends Model{
 

    public function notEmpty()
    {

    }

    public function notInnoculated()
    {

        $this->lessThan('sample_innoculated', 100);

    }

    public function unlock()
    {
        $this->raw('UNLOCK TABLES;');        

    }

    public function lock()
    {        
        $this->raw('LOCK TABLE samples WRITE;'); 
    }



}