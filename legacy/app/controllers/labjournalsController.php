<?PHP

class labJournalsController extends controller{
    
   
    function show($date = False){
                  
        $now_rounded = strtotime(date("d-m-Y", time()));
        
        //get needed dates
        if ($date == False || $date == '') {            
            $now = time();
            $niceStamp = date("d-m-Y", $now);
            $pageDate = strtotime($niceStamp);
        } else {

            //fisrt we need to check if $date is a valid unix timestamp, if not convert it
            $valid = $this->isValidTimeStamp($date);

            if (!$valid){
                $date = strtotime($date);
            }

            $niceStamp = date("d-m-Y", $date);
            $pageDate = $date;
        }
        
        //check if the requested date = today, if so, hide to next button      
        if($pageDate == $now_rounded){
            $hideNextButton = 'hidden';
        } else{
            $hideNextButton = '';
        }
        
        //generate the previous and next dates buttons                
        $previousDay = date('d-m', strtotime('-1 day', $pageDate));
        $nextDay = date('d-m', strtotime('+1 day', $pageDate));
        $stampNextDay = strtotime('+1 day', $pageDate);
        $stampPreviousDay = strtotime('-1 day', $pageDate);
        
        
        //check if the loaded date has any active pages
        //if not, just display empty page, else load the page from DB into thingy
        $this->LabJournal->where('user', getUserId());
        $this->LabJournal->where('page_date', $pageDate);
        
        $pageResult = $this->LabJournal->search();
        if(!empty($pageResult)){
            $pageData = $pageResult[0]['contents'];
        } else{
            $pageData = '';
        }
        
        
        //images might need to be done by eh.. imagedate:base64 stuff        
        $this->_template->set('page_data', $pageData);
        $this->_template->set('page_date', $pageDate);
        $this->_template->set('neat_page_date', $niceStamp);        
        $this->_template->set('neat_next_day', $nextDay);
        $this->_template->set('neat_previous_day', $previousDay);        
        $this->_template->set('stamp_previous_day', $stampPreviousDay);
        $this->_template->set('stamp_next_day', $stampNextDay);                        
        $this->_template->set('hide_next', $hideNextButton);                        
    }
    
    function printPage($dateStamp){
        
        //$this->doNotRenderHeader = True;
        $this->LabJournal->where('user', getUserId());
        $this->LabJournal->where('page_date', $dateStamp);
        
        $pageResult = $this->LabJournal->search();
        if(!empty($pageResult)){
            $pageData = $pageResult[0]['contents'];
        } else{
            $pageData = '';
        }
                
        $userInfo = getUserProfile(getUserId());        
        $name = $userInfo['first_name'] . ' ' . $userInfo['last_name'] . ' (' . $userInfo['function'] . ')';       
        
        $this->_template->set('owner', $name);
        $this->_template->set('page_date', date('d-m-Y', $dateStamp));
        $this->_template->set('print_date', date('d-m-Y', time()));        
        $this->_template->set('page', $pageData);
        
    }
    
    
    function test(){                
        $this->LabJournal->page_date = '111111';
        $this->LabJournal->user = '1';
        $this->LabJournal->contents = 'Derp';
        $this->LabJournal->save();        
    }
    
    
    
    function save(){
        
        $this->render = 0;        
        $this->LabJournal->where('user', getUserId());
        $this->LabJournal->where('page_date', $_POST['page_date']);
        $results = $this->LabJournal->search();
        
        if(!empty($results)){                        
            $id = $results[0]['id'];            
            $this->LabJournal->id = $id;
        } else{
            print 'no result';
        }
        
        $this->LabJournal->page_date = $_POST['page_date'];
        $this->LabJournal->user = getUserId();
        $this->LabJournal->contents = $_POST['contents'];                
        $this->LabJournal->save();
        
    }
    
    function loadImage($fileName){    
        $this->render = 0;
        //$size = filesize(ROOT .  DS .  'app' . DS . 'private' . DS . 'labUploads' . DS . $fileName);
        //header("Content-Length: " . $size);                
        header("Content-type: image/png");        
        $img = file_get_contents( ROOT .  DS .  'app' . DS . 'private' . DS . 'labUploads' . DS . $fileName );        
        print $img;                                
    }
    
    function saveImage(){             
        
        $this->render = 0;
        $name = md5(rand(100, 200) . $_FILES['file']['name'] . time());
        $ext = explode('.',$_FILES['file']['name']);        
        $filename = $name;
        $destination = ROOT . DS . 'app' . DS . 'private' . DS . 'labUploads' . DS . $filename . '.png';
        $location =  $_FILES["file"]["tmp_name"];        
        imagepng(imagecreatefromstring(file_get_contents($location)), $destination);                     
        echo ALPC_BASEPATH  . '/labJournals/loadImage/'. $filename . '.png'; 
    }
 
    private function  isValidTimeStamp($timestamp){    
    return ((string) (int) $timestamp === $timestamp) 
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }
    
}
