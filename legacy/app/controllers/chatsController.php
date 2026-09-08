<?PHP

class chatsController extends controller {
        
    private $_chatHistory = array();
    private $_openChatBoxes = array();
    private $_timeStampedBoxes = array();
       
    
    
    function beforeAction($queryString) {
        
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', 'active');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');                
        
        if(isset($_SESSION['chatHistory'])){
            $this->_chatHistory = $_SESSION['chatHistory'];
        }        
        if(isset($_SESSION['openChatBoxes'])){
            $this->_openChatBoxes = $_SESSION['openChatBoxes'];
        }          
        
        if(isset($_SESSION['timeStampedBoxes'])){
            $this->_timeStampedBoxes = $_SESSION['timeStampedBoxes'];
        }        
    }
    
    function afterAction($queryString) {
        $_SESSION['openChatBoxes'] = $this->_openChatBoxes;
        $_SESSION['chatHistory'] = $this->_chatHistory;
    }

    function chatBoxContents($chatReceiverId) {	
        /* This function should
         * return the contents of a given chatbox
         */
        
        if(isset($this->_chatHistory[$chatReceiverId])){
            return $this->_chatHistory[$chatReceiverId];
        } else{
            return array();
        }                
    }
    
    function startChatSession(){
        
        /* This should get called on each page when its refreshed
         * and this will check the users session stored chat boxes and history. 
         * 
         * Historic messages will always be viewable from the inbox, but in the session
         * context only the newest ones are shown. 
         */
        $this->render = False;
        
        $retObj = array();
        $retObj['onlineUsers'] = $this->_onlineUsers();
        $retObj['recentChats'] = $this->loadRecentChats();
        $retObj['username'] = getUserName(getUserId());        
        $retObj['openBoxes'] = array();
        
        foreach($this->_openChatBoxes as $chatReceiverId => $chatInfo){            
            $retObj['openBoxes'][$chatReceiverId]['chatboxWith'] = getUserName($chatReceiverId);                   
            $retObj['openBoxes'][$chatReceiverId]['contents'] = $this->chatBoxContents(($chatReceiverId));            
        }
        
        print json_encode($retObj, JSON_FORCE_OBJECT);
                               
    }
    
  
        
    function send(){
                
        $this->render = False;
        
        $this->_openChatBoxes[$_POST['receiver']] = time();        
        
        if(!isset($this->_chatHistory[$_POST['receiver']])){
            $this->_chatHistory[$_POST['receiver']] = array();
        }
                
        $this->Chat->sender = getUserId();
        $this->Chat->receiver = $_POST['receiver'];
        $this->Chat->message = $_POST['message'];
        $this->Chat->sent = time();
        $this->Chat->save();
        
        $thisMsg['type'] = 1;
        $thisMsg['author'] = getUserName(getUserId());
        $thisMsg['message'] = $_POST['message'];
        
        array_push($this->_chatHistory[$_POST['receiver']], $thisMsg);
                
    }
    
    
    function heartBeat(){
        
        //header("Connection: keep-alive");
                        
        $this->render = False;
        
        $this->Chat->where('receiver', getUserId());
        $this->Chat->where('recd', 0);
        
        $results = $this->Chat->search();       
        
        $newItems = array();
        $items = array();
        
        $i = 0;
        foreach($results as $chatMsg){
                        
            
            //fetch history if already saved in session
            if(!array_key_exists($chatMsg['sender'], $this->_openChatBoxes) && array_key_exists($chatMsg['sender'], $this->_chatHistory)){
                $items = $this->_chatHistory[$chatMsg['sender']];
            }
            
            //set history key if not done already
            if(!array_key_exists($chatMsg['sender'], $this->_chatHistory)){
                $this->_chatHistory[$chatMsg['sender']] = array();
            }
            
            //this message should be added to history for later heartbeats
            $senderName = getUserName($chatMsg['sender']);
            $thisMsg['type'] = 1;
            $thisMsg['author'] = $senderName;
            $thisMsg['message'] = $chatMsg['message'];                      
            
            //push this msg to history
            array_push($this->_chatHistory[$chatMsg['sender']], $thisMsg);
            
            //push the new message to the new messages array which will be returned to the client
            //$newItems[$chatMsg['sender']]['new_messages'][$i] = array();
            //array_push($newItems[$chatMsg['sender']]['new_messages'][$i], $thisMsg);                        
            $newItems[$chatMsg['sender']]['new_messages'][$i] = $thisMsg;                                    
            $newItems[$chatMsg['sender']]['chatboxWith'] = $senderName;         
                                                           
            //register as being an open chatbox
            $this->_openChatBoxes[$chatMsg['sender']] = $chatMsg['sent'];
            
            //a new message has been sent, this box might need to be timestamped
            //unset($_SESSION['tsChatBoxes'][$chat['from']]);
            unset($this->_timeStampedBoxes[$chatMsg['sender']]);
            
            //set as read
            $this->Chat->id = $chatMsg['id'];
            $this->Chat->recd = 1;
            $this->Chat->save();     
            $i++;
        }
        
        //run timestamping + online func here here
        if(!empty($this->_openChatBoxes)){   
            
            foreach($this->_openChatBoxes as $chatBoxId => $time){
                $newItems[$chatBoxId]['online']  = upa('users', 'checkUserOnline', array($chatBoxId));     
                if(!array_key_exists( 'chatboxWith', $newItems[$chatBoxId])){
                    $newItems[$chatBoxId]['chatboxWith'] = getUserName($chatBoxId);
                }
            }
            
        }
                
        
        //parray($newItems);
        print json_encode($newItems, JSON_FORCE_OBJECT);	
    }
    
    
    function close(){
        $this->render = False;
        unset($this->_openChatBoxes[$_POST['chatboxId']]);      
    }
    
    function loadRecentChats(){
        
        $this->render = False;
        global $lang;
        //needs to load recent messages
        //until 5 reached
        //MESA_CHAT_INBOX_SIZE
        //MESA_CHAT_INBOX_AGE
        //MESA_CHAT_CONVO_AGE
        //recent LIMIT of time also 
        
        $me = getUserId();
        $cutOff = time() - MESA_CHAT_INBOX_AGE;
        $this->Chat->where('sender', $me);        
        $this->Chat->greaterThan('sent', $cutOff);
        $this->Chat->insertOR();
        $this->Chat->where('receiver', $me);
        $this->Chat->greaterThan('sent', $cutOff);
        
               
        $results = $this->Chat->search();
        $chatThreads = array();
        
        foreach($results as $chatLine){
            
            if($chatLine['receiver'] != $me ){
                $chatWith = $chatLine['receiver'];
            } else{
                $chatWith = $chatLine['sender'];
            }
            
            if(!array_key_exists($chatWith, $chatThreads)){
                $chatThreads[$chatWith] = array();
            }
            
            $chatThreads[$chatWith]['message'] = $chatLine['message'];
            $chatThreads[$chatWith]['last_message_from'] = $chatLine['sender'];  
            $chatThreads[$chatWith]['time'] = $chatLine['sent'];                        
        }
        
        $inboxPreviews = '';
                
        
        foreach($chatThreads as $chatWith => $chatTip){
        
            $chatInfo = upa('profiles', 'fetch', array($chatWith));
            
            $msg['avatar_url'] = generateAvatar($chatWith, $chatInfo['avatar_uri'], 'small', False, True);
            $msg['chatbox_name'] = $chatInfo['username'];
            $msg['chatbox_title']  = $chatWith;
            
            if(strlen($chatTip['message']) >35){
                $msg['trimmed_msg'] = substr($chatTip['message'], 0, 35) . '...';
            } else{
                $msg['trimmed_msg'] = substr($chatTip['message'], 0, 35);
            }
            
            if($chatTip['last_message_from'] == $me){
                $msg['icon'] = '';
            } else{
                $msg['icon'] = 'hide';
            }
            
            $msg['time_stamp'] = $chatTip['time'];
            $msg['sent_at'] = date('d-m-Y - H:i:s', $chatTip['time']);            
            $inboxPreviews .= generateHTML('chat/inboxItem', $msg);                        
        }
        
        if($inboxPreviews == ''){
            $inboxPreviews = generateHTML('chat/noRecentMsg', array('no_items_msg' => $lang['MESA_CHT_NOITEMSFOUND']));
        }
        
        return $inboxPreviews;
                
    }
    
    function loadConvoPoint(){
        
        $this->render = False;
        
        $me = getUserId();     
        $myName = getUserName($me);
        $chatWith = getUserName($_POST['chatWith']);
               
        $cutOff = $_POST['timeStamp'] - MESA_CHAT_CONVO_AGE;                
        
        $this->Chat->where('sender', $me); 
        $this->Chat->where('receiver', $_POST['chatWith']);
        $this->Chat->greaterThan('sent', $cutOff);
        $this->Chat->insertOR();
        $this->Chat->where('sender', $_POST['chatWith']);
        $this->Chat->where('receiver', $me);
        $this->Chat->greaterThan('sent', $cutOff);
        
        $results = $this->Chat->search();        
        $history = array();        
        
        if(!empty($results)){
            
            $history['start_of_convo'] = date('d-m-Y - H:i:s', $results['0']['sent']);              
            $i = 0;
            
            foreach($results as $chatLine){
                if($chatLine['sender'] == $me ){
                    $history['messages'][$i]['author'] = $myName;
                } 
                elseif($chatLine['sender'] == $_POST['chatWith']){
                    $history['messages'][$i]['author'] = $chatWith;
                }                                
                $history['messages'][$i]['message'] = $chatLine['message'];                              
                $i++;
            }            
        }         

        
        
        
        print json_encode($history, JSON_FORCE_OBJECT);
    }
    
    private function _onlineUsers(){
    
        global $lang;
        //$this->render = False;
        $online = upa('users', 'checkOnlineUsers', array());
        $chatboxList = '';
                        
        foreach($online as $user){            
            if($user['id'] == getUserId()){
                continue;
            } else{
                $user['avatar_src'] = generateAvatar($user['id'], $user['avatar_uri'], 'small', False, True);
                $user['chatbox_name'] = $user['username'];                        
                $chatboxList .= generateHTML('chat/chatOnlineUser', $user);
            }                                    
        }
        
        if($chatboxList == '')
        {
            $chatboxList = generateHTML('chat/noOnlineUsers', array('message' => $lang['MESA_CHT_NOONLINEUSERS'] ));
        }                                
        
        return $chatboxList;
    }
    
    
    
    function inbox(){
        
        $me = getUserId();        
        $this->Chat->where('sender', $me);                
        $this->Chat->insertOR();
        $this->Chat->where('receiver', $me);
        
        $this->Chat->order('sent', 'DESC' );
                            
        $results = $this->Chat->search();
        $chatThreads = array();

        foreach($results as $chatLine){
            
            if($chatLine['receiver'] != $me ){
                $chatWith = $chatLine['receiver'];
            } else{
                $chatWith = $chatLine['sender'];
            }
            
            if(!array_key_exists($chatWith, $chatThreads)){
                $chatThreads[$chatWith] = array();
                $chatThreads[$chatWith]['message'] = $chatLine['message'];
                $chatThreads[$chatWith]['last_message_from'] = $chatLine['sender'];  
                $chatThreads[$chatWith]['time'] = $chatLine['sent'];                 
            }
            
             
        }
        
        $inboxPreviews = '';
           
        
        foreach($chatThreads as $chatWith => $chatTip){
        
            $chatInfo = upa('profiles', 'fetch', array($chatWith));
            
            $msg['avatar_url'] = generateAvatar($chatWith, $chatInfo['avatar_uri'], 'small', False, True);
            $msg['chatbox_name'] = $chatInfo['username'];
            $msg['chatbox_title']  = $chatWith;
            
            if(strlen($chatTip['message']) >35){
                $msg['trimmed_msg'] = substr($chatTip['message'], 0, 35) . '...';
            } else{
                $msg['trimmed_msg'] = substr($chatTip['message'], 0, 35);
            }
            
            if($chatTip['last_message_from'] == $me){
                $msg['icon'] = '';
            } else{
                $msg['icon'] = 'hide';
            }
            
            $msg['time_stamp'] = $chatTip['time'];
            $msg['sent_at'] = date('d-m-Y', $chatTip['time']);            
            $inboxPreviews .= generateHTML('chat/fullInboxItem', $msg);                        
        }
        
        if($inboxPreviews == ''){
            $inboxPreviews = generateHTML('chat/fullInboxNoItems', array());
        }
    
        
        $this->_template->set('message_list', $inboxPreviews);
        
    }
    
    function loadConversation($convoWith){
        
        $this->render = false;
        
        $me = getUserId();     
                
        $myInfo = upa('profiles', 'fetch', array($me));
        $chatInfo = upa('profiles', 'fetch', array($convoWith));
        
        $myName = $myInfo['username'];
        $chatWith = $chatInfo['username'];
        
        $myAvatar = generateAvatar($me, $myInfo['avatar_uri'], 'small', False, True);
        $chatAvatar = generateAvatar($convoWith, $chatInfo['avatar_uri'], 'small', False, True);
                               
        $this->Chat->where('sender', $me); 
        $this->Chat->where('receiver', $convoWith);        
        $this->Chat->insertOR();
        $this->Chat->where('sender', $convoWith);
        $this->Chat->where('receiver', $me);        
        
        $results = $this->Chat->search();                
        $convo = array();
        
        $i = 0;
        $mI = 0;
        $ts = false;
        $pS = false;
        
        foreach($results as $msg){    
            if($ts == False || ($msg['sent'] - $ts) > MESA_CHAT_MSGBUNDLE || $pS != $msg['sender'] ){                                
                $i++;
                $convo[$i]['timeStamp'] = $msg['sent'];
                $convo[$i]['from'] = $msg['sender'];                                
            }
            
            $convo[$i]['messages'][$mI] = $msg['message'];                      
            $ts = $msg['sent'];
            $pS = $msg['sender'];            
            $mI++;
        }
        
        $dS = false;
        $convoRender = '';
        
        foreach($convo as $convoBlock){
            
            if($dS == False || $dS != date('d-m-Y', $convoBlock['timeStamp'])){               
                $convoRender .= generateHTML('chat/seperator', array('block_date' => date('d-m-Y', $convoBlock['timeStamp']) ));
            }
            
            $blockArray = array();
            $blockArray['timeStamp'] =  date('d-m-Y H:i', $convoBlock['timeStamp']);
            $blockArray['content'] = '';
            
            foreach($convoBlock['messages'] as $msgI => $blockMsg){                
                $blockArray['content'] .= '<p>' . $blockMsg . '</p>';               
            }
            
            if($convoBlock['from'] == $me){
                $blockArray['sender'] = $myName;     
                $blockArray['avatar'] = $myAvatar;
            } else{
                $blockArray['sender'] = $chatWith;
                $blockArray['avatar'] = $chatAvatar;
            }
            
            $convoRender .= generateHTML('chat/messageBlock', $blockArray);
            $dS = date('d-m-Y', $convoBlock['timeStamp']);                        
        }
        
        print $convoRender;         
    }
    
    
}