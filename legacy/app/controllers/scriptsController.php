<?PHP

class scriptsController extends Controller {
    
    protected $_publicActions = array('load' => True);    
    protected $_noRerouteActions = array('load' => True);    
    
    function dload($scriptname){
        
        $this->render = 1;
        $this->doNotRenderHeader = 1;
                        
        $bad[1] = '/';
        $bad[2] = '\\';
        
        $scriptname = str_replace($bad, '', $scriptname);
        $scriptLoc = ROOT . DS . 'public' . DS . 'js' . DS . $scriptname . '.js';
                                
        $etag = md5( gmdate('D, d M Y H:i:s ', time()) . 'GMT');
        
        session_cache_limiter('private_no_expire '); //Aim at 'public'
        session_cache_expire(180);
        
        header('content-type:application/javascript');
        header('Cache-Control: max-age=28800');        
        header('Vary: Accept');                        
        header('Expires: Thu, 19 Nov 2200 08:52:00 GMT');
        
        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
        $reServe = False;
       
        if($if_modified_since != False){            
            
            $timeTilRefresh = time() - strtotime($if_modified_since);             
            if($timeTilRefresh < 3600 ){
                $reServe = False;
                header('HTTP/1.1 304 Not Modified');
                exit();
            } else{
                $reServe = True;
            }
                                
        }
        else
        {            
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', time()) . 'GMT');    
            header("ETag: \"{$etag}\"");
            $reServe = True;                       
        }
        
     
        if($reServe == True){
            
            print '/* reserving --- */';
            
            if (file_exists($scriptLoc) == True){
                $scripty = file_get_contents($scriptLoc);                
                $this->_template->set('alpc_load_script', $scripty);        
            } else {            
                $this->_template->set('alpc_load_script', '');        
            }
        }
               
    }
    
     function load($scriptname){
        
        $this->render = 1;
        $this->doNotRenderHeader = 1;        
        header('content-type:application/x-javascript');
        
        $bad[1] = '/';
        $bad[2] = '\\';
        
        $scriptname = str_replace($bad, '', $scriptname);
        $scriptLoc = ROOT . DS . 'public' . DS . 'js' . DS . $scriptname . '.js';
                                
                    
        if (file_exists($scriptLoc) == True){
             $scripty = file_get_contents($scriptLoc);                
             $this->_template->set('alpc_load_script', $scripty);        
        } else {            
             $this->_template->set('alpc_load_script', '');        
        }
        
               
    }
       
}   