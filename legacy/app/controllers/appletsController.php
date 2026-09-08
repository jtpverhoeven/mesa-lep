<?PHP

class appletsController extends controller{
    
    private $applets = array();
    private $_appletStore = NULL;
   
    
    function beforeAction($queryString) {
        global $lang;
        
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', 'active');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');   
                
        $this->applets[0]['name'] = $lang['MESA_APL_USERSONLINETITLE'];
        $this->applets[0]['description'] = $lang['MESA_APL_USERSONLINEDESC'];
        $this->applets[0]['applet'] = 'onlineUsers';         
        $this->applets[0]['icon'] = 'icon-user';         
        
        $this->applets[1]['name'] = $lang['MESA_APL_DATETIMETITLE'];
        $this->applets[1]['description'] = $lang['MESA_APL_DATETIMEDESC'];
        $this->applets[1]['applet'] = 'dateAndTime';      
        $this->applets[1]['icon'] = 'icon-time';         
        
        $this->applets[2]['name'] = $lang['MESA_APL_CONNEXINFOTITLE'];
        $this->applets[2]['description'] = $lang['MESA_APL_CONNEXINFODESC'];
        $this->applets[2]['applet'] = 'connectionInfo';      
        $this->applets[2]['icon'] = 'icon-signal';         
        
        $this->applets[3]['name'] = $lang['MESA_APL_CLTINFOTITLE'];
        $this->applets[3]['description'] = $lang['MESA_APL_CLTINFODESC'];
        $this->applets[3]['applet'] = 'clientInfo';      
        $this->applets[3]['icon'] = 'icon-map-marker';

        $this->applets[4]['name'] = 'Project bladwijzers';
        $this->applets[4]['description'] = 'Toont de projecten met een bladwijzer';
        $this->applets[4]['applet'] = 'bookmarks';
        $this->applets[4]['icon'] = 'icon-bookmark';
        
        //$this->applets[4]['name'] = $lang['MESA_APL_PROJECTTOP'];
        //$this->applets[4]['description'] = $lang['MESA_APL_PROJECTTOPDESC'];
        //$this->applets[4]['applet'] = 'projectProgress';      
        //$this->applets[4]['icon'] = 'icon-tasks';         
        
    }
    
    
    function setupDash(){    
        
        //get user applets
        $activeApplets = array();        
        $this->Applet->where('user', getUserId());
        $results = $this->Applet->search();
               
        foreach($results as $thisApplet ){
            $activeApplets[$thisApplet['applet']] = True;
        }
               
        $appListRendered = '';
        
        foreach($this->applets as $applet){
            
         
          
            if(array_key_exists($applet['applet'], $activeApplets)){                
                $selector['on'] = 'selected="selected"';
                $selector['off'] = '';
                $selector['applet'] = $applet['applet'];
            } else{              
                $selector['on'] = '';
                $selector['off'] = 'selected="selected"';                
                $selector['applet'] = $applet['applet'];
            }
            
            
            $selectorHTML = generateHTML('appletSelector', $selector);            
            $renderArr = array_merge(array('applet_selector' => $selectorHTML), $applet);            
            $listingHTML = generateHTML('appletListing', $renderArr);           
            $appListRendered .= $listingHTML;            
        }
                
        $this->_template->set('applet_list', $appListRendered);
    }
    
    function toggleApplet(){
        
        $this->render = false;
            
        //remove cookie & profile setting
        setcookie("mesaDashboard", "", time()-3600, '/');
        

        //check if it was active        
        if($_POST['appletSet'] == '0'){            
            //if off, find ID and remove from table
            $this->Applet->where('user', getUserId());
            $this->Applet->where('applet', $_POST['applet']);        
            $result = $this->Applet->search();
            
            if(count($result) > 0){
                $this->Applet->free();
                $this->Applet->id = $result[0]['id'];
                $this->Applet->remove();
            }                        
        } else{            
            $this->Applet->applet = $_POST['applet'];
            $this->Applet->user = getUserId();
            $this->Applet->save();
        }                        
    }
    
    
    function runAppletsForUser(){
        
        $this->render = False;
        $appletRender = array();        
        $this->Applet->where('user', getUserId()); 
        $result = $this->Applet->search();
        
        $i = 0;
        foreach($result as $applet){         
            $this->_appletStore = json_decode($applet['appletStore'], True);
            $appletReturn = $this->_runApplet($applet['applet'], $applet['id']);            
            $appletRender[$i]['html'] = $appletReturn['html'];
            $appletRender[$i]['js'] = $appletReturn['js'];            
            $i++;
        }
                
        return $appletRender;        
    }


    private function _runApplet($applet, $appletId){           
        $appReturn =  call_user_func(array($this, '_'. $applet));   
        $html =  generateHTML('applets/appletWrapper', array('applet_id' => $appletId, 'applet_html' => $appReturn['html']));
        return array('html' => $html, 'js' => $appReturn['js']);        
        //$appHTML = call_user_func(array($this, '_'. $applet));        
        //return generateHTML('applets/appletWrapper', array('applet_id' => $appletId, 'applet_html' => $appHTML));
    }

    private function _bookmarks(){
        $bookmarks = upa('bookmarks', 'getBookmarks', array(getUserId()), False);
        $bookmarksRendered = '';

        foreach($bookmarks as $bookmark){
            $bookmark['href'] = ALPC_BASEPATH . '/projects/search/' . $bookmark['info']['id'];
            $bookmark['client_name'] = customerIdToName($bookmark['info']['client']);
            $bookmark['predicted_end_readable'] = date('d-m-Y', $bookmark['info']['predicted_end']);
            $bookmark['reference'] = $bookmark['info']['reference'];
            $bookmarksRendered .= generateHTML('applets/bookmarks/bookmarkLine', $bookmark);
        }

        $html =  generateHTML('applets/bookmarks/panel', array('bookmark_list' => $bookmarksRendered));
        return array('html' => $html, 'js' => '');
    }
    
    private function _onlineUsers(){
        $onlineUsers = upa('users', 'checkOnlineUsers', array(), False);        
        $usersRendered = '';        
        foreach($onlineUsers as $user){
              $user['avatar'] = generateAvatar($user['id'], $user['avatar_uri'], 'small', False, True);
              $usersRendered .= generateHTML('applets/onlineUsers/userLine', $user);
        }        
        $list = generateHTML('applets/onlineUsers/userList', array('user_list' => $usersRendered));        
        $html =  generateHTML('applets/onlineUsers/panel', array('user_list' => $list));        
        return array('html' => $html, 'js' => '');
    }
    
    private function _dateAndTime(){        
        $html =  generateHTML('applets/dateAndTime/panel', array('date' => date('d-m-Y')));                        
        $js = generateHTML('applets/dateAndTime/js', array());
        return array('html'=>$html, 'js'=> $js);        
    }
    
    
    private function _connectionInfo(){
        
        $proxy_headers = array(
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED_FOR_IP',
            'VIA',
            'X_FORWARDED_FOR',
            'FORWARDED_FOR',
            'X_FORWARDED',
            'FORWARDED',
            'CLIENT_IP',
            'FORWARDED_FOR_IP',
            'HTTP_PROXY_CONNECTION'
        );
        foreach($proxy_headers as $x){
            if (isset($_SERVER[$x])){
                $proxy  = 'MESA_APL_PROXYYES';
            } else{
                $proxy = 'MESA_APL_PROXYNO';
            }
        }
        
        return array('html' => generateHTML('applets/connectionInfo/panel', array('ip_adres' => getIpAddres(), 'user_name' => getUserName(getUserId()), 'proxy' => $proxy)), 'js' => '');         
    }
    
    private function _clientInfo(){              
        $html = generateHTML('applets/clientInfo/panel', array() );
        $js = generateHTML('applets/clientInfo/js', array() );
        return array('html'=>$html, 'js'=> $js);                
    }
    
    function clientInfoFetch(){
        
        $this->doNotRenderHeader = True;
        
        $clientInfo = upa('clients', 'fetch', array($_POST['cid']));
        //$subClientInfo = upa('subClients', 'fetch', array($_POST['scid']));
        
        $this->_template->setByArray($clientInfo);
        //$this->_template->setByArray($subClientInfo);

    }
    
    private function _projectProgress(){
        
        
        
         return array('html'=>$html, 'js'=> $js);      
        
    }
    
}