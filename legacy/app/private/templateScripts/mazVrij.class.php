<?PHP


class mazVrij extends calculation{


    protected $reportIn = 'kve';
    protected $endResult = NULL;
    protected $prepend = NULL;
    protected $append = NULL;

    protected $indicative = False;
    protected $confirmed = False;

    protected function _reportOutput(){
       return array('kve' => 'kve');
    }

    public function provide(){

      if($this->_isDone() == False){
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          return $this->_dispatch();
      }

      $this->output['output'][$this->reportIn] = $this->results[1]['kve'];
      return $this->_dispatch();
    }


    protected function _isDone(){
        $allDone = True;

          foreach($this->results as $dF => $dFFields ){
              foreach($dFFields as $count){  if($count == ''){ $allDone = False; }}
          }

        return $allDone;
    }

}
