<?PHP

class legionellaPassthrough extends calculation{

    protected $reportIn = 'kve';


    protected function _reportOutput(){
        return array('kve' => 'dummy');
    }

    public function provide(){      
      
    }

}