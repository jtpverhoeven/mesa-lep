<?PHP

//debug writter 

class Debugger {

    private $logContent;

    public function __construct(){

    }

    public function __destruct(){

    }

    static function log($text, $type='notice'){
        
                
        $logContent = '';
        $logContent .= '<div class="border-solid border-red-lighter border mb-1">';
        $logContent .= Debugger::contentDiv($type);
        
        if(is_array($text)){
            $logContent .= parray($text, true);
        } else{
            $logContent .= $text;
        }
                
        $logContent .= '</div>';
        $logContent .= '</div>';

        $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'debugger.txt';        
        $profilerLog =  fopen($logLocation,"a+");
        fwrite($profilerLog, $logContent);
        //fclose($profilerLog);    
    }

    static function contentDiv($type){

        if($type === 'warn'){
            return '<div class="bg-yellow-lighter">';
        }

        if($type === 'notice'){
            return '<div class="bg-grey-lighter">';
        }

        if($type === 'error'){
            return '<div class="bg-red-lighter">';
        }
        
    }
    
  

}
