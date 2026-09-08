<?PHP


class mpn extends calculation{


    protected $reportIn = 'kve';


    protected function _reportOutput(){
       return array('kve' => 'kve/100ml');
    }

    public function provide(){

      if($this->_isDone() == False){
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          return $this->_dispatch();
      }


    if(is_numeric($this->results[1]['kve'])){
        $neatResult = number_format($this->results[1]['kve'], 0, '.', '.');
    } else{
      $neatResult = $this->results[1]['kve'];
   }


      $this->output['output'][$this->reportIn] = $neatResult;

      return $this->_dispatch();
    }


    public function _isDone(){
        $allDone = True;

          foreach($this->results as $dF => $dFFields ){
              foreach($dFFields as  $fieldName => $count){

                  if($count == ''){

                    if($fieldName == 'MPNwaarde'){
                      $kveSetting = checkKeyOrFalse($this->results[$dF]['kve']);
                      if($kveSetting == '-' || $kveSetting == '0'){
                        //result was neg, mpn can be zero  or empty
                      } else{
                          $this->readyFailSignal = True;
                          $allDone = False;
                      }
                    } else{
                      $allDone = False;
                    }

                //     $allDone = False;

                 }
              }
          }

        return $allDone;
    }

  }
