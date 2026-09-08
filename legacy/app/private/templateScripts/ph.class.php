<?PHP


class ph extends calculation{


    protected $reportIn = 'kve';


    protected function _reportOutput(){
       return array('kve' => 'pH waarde');
    }

    public function provide(){

      if($this->_isDone() == False){
          $this->output['output'][$this->reportIn] = 'Niet afgerond';
          $this->output['outputEn'][$this->reportIn] = 'Not completed';
          return $this->_dispatch();
      }

      
      $measurements = []; 

      foreach($this->results[1] as $result)
      {

        //replace comma with dot
        $result = str_replace(',', '.', $result);
        
        $result = (float) $result; 

        if($result > 14)
        {
          $this->output['output'][$this->reportIn] = 'Fout resultaat gevonden';
          $this->readyFailSignal = True;
          return $this->_dispatch();
        }
      

        if(!is_float($result) || $result === '-')
        {
          $this->_log('result is not a float or is -');
          continue; 
        }

        array_push($measurements, $result);

      }
           
      $this->_log('measurements' . parray($measurements, true));

      if(count($measurements) == 0)
      {
        $this->output['output'][$this->reportIn] = 'Geen resultaten gevonden';
        $this->readyFailSignal = True;
        return $this->_dispatch();
      }           

      if(count($measurements) > 1)
      {
        
        //difference between the two numbersin array measurements
        $diff = abs( min($measurements) - max($measurements) ); 

        if($diff > 0.5 )
        {
          $this->output['output'][$this->reportIn] = 'Resultaten lagen meer dan 0.5 af van elkaar';
          $this->readyFailSignal = True;
          return $this->_dispatch();
        }

      }
      
      //average the numbers in array $measurements
      $average = round(array_sum($measurements) / count($measurements), 2);

      $average = number_format($average, 2, ',', '.');

      //replace dot with comma for varibale average      

      $this->output['output'][$this->reportIn] = str_replace('.', ',', $average);

      return $this->_dispatch();
    }

    

    //function to calculate the percentage difference between two numbers
    private function _percentageDifference($num1, $num2)
    {
        $diff = $num1 - $num2;
        $percentage = ($diff / $num1) * 100;

        //return absolute value of percentage
        return abs($percentage);
        
    }





    protected function _isDone(){

      $this->_log($this->results);
      
      $allDone = True;

      foreach($this->results as $dF => $dFFields )
      {

        foreach($dFFields as $count)
        {  
          if($count == '')
          { 
            $allDone = False; 
          }
        }

      }

      return $allDone;
    
    }

  }
