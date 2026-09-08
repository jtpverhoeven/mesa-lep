<?PHP

//Time debugging tool

class Profiler {

  protected $timeStart;
  protected $checkPoints = array();
  protected $checkPointCounter = 0;
  //protected $startDbCon = 0;
  //protected $endDbCon = 0;
  //protected $dbConConsumed = 0;

  public function __construct(){
    $this->timeStart = microtime(true);
    //$this->startDbCon = upa('cvars', 'grabCvar', array('db_counter'), False);
    $_SESSION['PROFILER'] = True;
  }

  public function __destruct(){
    $_SESSION['PROFILER'] = False;
    //$this->endDbCon = upa('cvars', 'grabCvar', array('db_counter'), False);
    //$this->dbConConsumed = $this->endDbCon -  $this->startDbCon;
    $this->serve();
  }

  public function addCheckpoint($name){
    $thisTime = microtime(true);
    $this->checkPoints[$this->checkPointCounter] = array();
    $this->checkPoints[$this->checkPointCounter]['name'] = $name;
    $this->checkPoints[$this->checkPointCounter]['time'] = $thisTime;
    $this->checkPoints[$this->checkPointCounter]['elapsed'] =  ($thisTime - $this->timeStart);
    $previous = $this->checkPointCounter - 1;
    if($previous < 0){$previous = 0; }
    $this->checkPoints[$this->checkPointCounter]['delta'] = (($thisTime - $this->timeStart) ) - ($this->checkPoints[$previous]['elapsed']);
    $this->checkPointCounter += 1;
  }

  public function serve(){
    $this->addCheckpoint('end');
    $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'profiler.txt';
    $profilerLog =  fopen($logLocation,"a+");
    fwrite($profilerLog, var_export($this->checkPoints, true));
  }

}
