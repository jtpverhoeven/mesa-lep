<?PHP

class labtalkEventsController extends controller{
    
   // const WALLPOST = 'WALLPOST';
  //  const FOREIGNPOST = 'FOREIGNPOST';
  //  const REPLYPOST = 'REPLYPOST';
  //  const AUTOMATIONPOST = 'AUTOMATIONPOST';    
    
  //  const SAMPLEADD = 'SAMPLEADD';
  //  const PROJECTADD = 'PROJECTADD'; 
  //  const PROJECTAUTH = 'PROJAUTH';
    
    const WALLPOST = 0;
    const FOREIGNPOST = 1;
    const REPLYPOST = 2;
    const AUTOMATIONPOST = 3;     
    const PROJECTPOST = 7;
    
    
    
    const SAMPLEADD = 4;
    const PROJECTADD = 5; 
    const PROJECTAUTH = 6;
    
    
    
    
    function automation($context, $payloadArray){
        
        $user = getUserId();
        $time = time();
        $content = array();        
        $previousPost = $this->_checkInRange($time, $user, $context);
                    
        if(is_array($previousPost) && !empty($previousPost)){
            //going to connect to older, load to model
            $this->LabtalkEvent->arrayToModel($previousPost);            
            $content = json_decode($this->LabtalkEvent->payload, JSON_FORCE_OBJECT);                                    
            $nArr = count($content);
            $content[$nArr] = $payloadArray;
        } else{
            //new, set params        
            $this->LabtalkEvent->user = $user;
            $this->LabtalkEvent->receiver = $user;
            $this->LabtalkEvent->time = time();
            $this->LabtalkEvent->context = self::AUTOMATIONPOST;
            $this->LabtalkEvent->automation_context = constant('self::' . $context);
            $content[0] =  $payloadArray;
        }                
        $this->LabtalkEvent->payload = json_encode($content, JSON_FORCE_OBJECT );
        $this->LabtalkEvent->save();
        
    }
    
    private function _checkInRange($time, $user, $context){        
        $minTime = $time - MESA_WALL_POSTGROUP_LIMIT;
        $this->LabtalkEvent->where('user', $user);
        $this->LabtalkEvent->where('receiver', $user);
        $this->LabtalkEvent->where('automation_context', constant('self::' . $context));
        $this->LabtalkEvent->greaterThan('time', $minTime);
        $result = $this->LabtalkEvent->search();
        $this->LabtalkEvent->free();
        
        if(empty($result)){
            return False;
        } else{
            return $result[0];
        }                
    }
    
    function wallPost($user, $receiver, $reply = False){        
        $this->render = False;
        $payload = $_POST['payload'];
        $this->LabtalkEvent->user = $user;
        $this->LabtalkEvent->receiver = $receiver;
        $this->LabtalkEvent->time = time();
        
        if($receiver == getUserId()){
            $this->LabtalkEvent->context = self::WALLPOST;
        } else{
            $this->LabtalkEvent->context = self::FOREIGNPOST;
        }
                        
        $this->LabtalkEvent->reply_to = $reply;
        $this->LabtalkEvent->payload = $payload;
        $this->LabtalkEvent->save();        
    }
    
    function projectPost($project){
        
        $this->render = False;
        $payload = $_POST['payload'];        
        $this->LabtalkEvent->user = getUserId();        
        $this->LabtalkEvent->context = self::PROJECTPOST;
        $this->LabtalkEvent->receiver = $project;
        $this->LabtalkEvent->time = time();                                
        $this->LabtalkEvent->reply_to = $reply;
        $this->LabtalkEvent->payload = $payload;
        $this->LabtalkEvent->save();        
    }
    
    function replyPost(){
        $this->doNotRenderHeader = True;
        
        $oriPost = $this->fetch($_POST['replyTo']);
        
        $this->LabtalkEvent->free();
        $this->LabtalkEvent->user = getUserId();
        $this->LabtalkEvent->receiver = $oriPost['receiver'];
        $this->LabtalkEvent->time = time();
        $this->LabtalkEvent->context = self::REPLYPOST;
        $this->LabtalkEvent->reply_to = $_POST['replyTo'];
        $this->LabtalkEvent->payload = $_POST['payload']; 
        $this->LabtalkEvent->save();
        
        $userInfo = getUserProfile(getUserId());
        $postArray['post_username'] = $userInfo['username'];
        $postArray['post_realname'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
        $postArray['post_date'] = date('d-m-Y', time());
        $postArray['post_time'] = date('H:i', time());
        $postArray['post_content'] =  $_POST['payload'];
        $postArray['post_avatar'] = generateAvatar(getUserId(), False, 'small', False, True);        
        $this->_template->set('reply', generateHTML('wallBits/reply', $postArray));
                        
    } 
    
    function renderProjectWall($projectId, $fromHistory = False){
        
        global $lang;        
        $this->doNotRenderHeader = True;
        
        if($fromHistory != False){
             $this->LabtalkEvent->lessThan('id', $fromHistory);
        }
        
        $this->LabtalkEvent->where('context', self::PROJECTPOST);
        $this->LabtalkEvent->where('receiver', $projectId);                
        $this->LabtalkEvent->order('time', 'DESC');
        $this->LabtalkEvent->limit(MESA_WALL_INITIAL_LOAD);  
        
        $result = $this->LabtalkEvent->search();
        $numberOfPosts = count($result);
        
        $render = '';
        
        if(empty($result)){
            $render = generateHTML('wallBits/noPosts', array());
        }
        
        else{
                        
            foreach($result as $post){  
                                         
            if(!isset($historyPoint)){
                    $historyPoint = $post['id'];
            } else{
                if($post['id'] < $historyPoint){
                    $historyPoint = $post['id'];
                }
            }
                        
            if($post['context'] == self::PROJECTPOST){
                $postArray['post_avatar'] = generateAvatar($post['user'], False, 'small', False, True);
                $userInfo = getUserProfile($post['user']);
                $postArray['post_username'] = $userInfo['username'];
                $postArray['post_realname'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                $postArray['post_date'] = date('d-m-Y', $post['time']);
                $postArray['post_time'] = date('H:i', $post['time']);
                $postArray['post_content'] = $post['payload'];
                $postArray['post_action'] = '';
                $postArray['post_id'] = $post['id'];
                $postArray['post_replies'] = $this->_renderReplys($post['id']);
                $render .= generateHTML('wallBits/post', $postArray);         
            }            
            }
        }
            
        $this->_template->set('wall', $render);
        
    }
    
    
    function renderWall($user = False, $fromHistory = False){
        
        global $lang;
        
        $this->doNotRenderHeader = True;
        
        //set this here because javascript sets it as a text       
        if($user == 'False'){
            $user = False;
        }
        
        
        if($user != False){
            $this->LabtalkEvent->where('user', $user);
            $this->LabtalkEvent->where('reply_to', 0);            
            if($fromHistory != False){                
                $this->LabtalkEvent->lessThan('id', $fromHistory);
            }            
            $this->LabtalkEvent->insertOR();
            $this->LabtalkEvent->where('receiver', $user);
            $this->LabtalkEvent->where('reply_to', 0);            
            if($fromHistory != False){
                $this->LabtalkEvent->lessThan('id', $fromHistory);
            }            
        } else{
            $this->LabtalkEvent->where('reply_to', 0);        
            if($fromHistory != False){
                $this->LabtalkEvent->lessThan('id', $fromHistory);
            }
        }   
        
                                       
        //limit to iniial load number order by time        
        $this->LabtalkEvent->order('time', 'DESC');
        $this->LabtalkEvent->limit(MESA_WALL_INITIAL_LOAD);        
        $result = $this->LabtalkEvent->search();
        $numberOfPosts = count($result);
        
        $render = '';
        
        if(empty($result)){
            $render = generateHTML('wallBits/noPosts', array());
        }
        
        else{
            
            foreach($result as $post){                                                   
                
                if(!isset($historyPoint)){
                    $historyPoint = $post['id'];
                } else{
                    if($post['id'] < $historyPoint){
                        $historyPoint = $post['id'];
                    }
                }                
                                
                
                //coming from user self
                if($post['context'] == self::WALLPOST){
                    $postArray['post_avatar'] = generateAvatar($post['receiver'], False, 'small', False, True);
                    $userInfo = getUserProfile($post['receiver']);
                    $postArray['post_username'] = $userInfo['username'];
                    $postArray['post_realname'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                    $postArray['post_date'] = date('d-m-Y', $post['time']);
                    $postArray['post_time'] = date('H:i', $post['time']);
                    $postArray['post_content'] = $post['payload'];
                    $postArray['post_action'] = '';
                    $postArray['post_id'] = $post['id'];
                    $postArray['post_replies'] = $this->_renderReplys($post['id']);
                    $render .= generateHTML('wallBits/post', $postArray);         
                } 
                //somebody else 
                elseif($post['context'] == self::FOREIGNPOST){
                    $postArray['post_avatar'] = generateAvatar($post['user'], False, 'small', False, True);                                         
                    $userInfo = getUserProfile($post['user']);
                    $receiverInfo = getUserProfile($post['receiver']);                    
                    $postArray['post_username'] = $userInfo['username'];
                    $postArray['post_realname'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                    $postArray['post_date'] = date('d-m-Y', $post['time']);
                    $postArray['post_time'] = date('H:i', $post['time']);
                    $postArray['post_content'] = $post['payload'];
                    $postArray['post_action'] = '<i class="icon-caret-right"></i> <a href="{LB}/profiles/view/' . $receiverInfo['username'] . '">' . $receiverInfo['first_name'] . ' ' . $receiverInfo['last_name'] . '</a>';
                    $postArray['post_id'] = $post['id'];
                    $postArray['post_replies'] = $this->_renderReplys($post['id']);
                    $render .= generateHTML('wallBits/post', $postArray);   
                }         
                
                //sample add
                elseif($post['context'] == self::AUTOMATIONPOST){
                                         
                    $userInfo = getUserProfile($post['user']);
                    $postArray['post_avatar'] = generateAvatar($post['user'], False, 'small', False, True);        
                    $postArray['post_username'] = $userInfo['username'];
                    $postArray['post_realname'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                    $postArray['post_date'] = date('d-m-Y', $post['time']);
                    $postArray['post_time'] = date('H:i', $post['time']);
                    $postArray['post_id'] = $post['id'];
                    $postArray['post_replies'] = $this->_renderReplys($post['id']);
                    
                    if($post['automation_context'] == self::SAMPLEADD){
                        
                        $samples = json_decode($post['payload'], JSON_FORCE_OBJECT);
                        $nSamples = count($samples);                        
                        if($nSamples == 1){
                            $postArray['post_action'] = $lang['MESA_AUT_ADDSAMPLE'];
                        }  else{                            
                            $postArray['post_action'] = generateHTML($lang['MESA_AUT_ADDSAMPLES'], array('no_samples' => $nSamples), True );                            
                        }                                               
                        $postArray['post_content'] = $this->_generateSampleTable($samples, $post['id']);                       
                    } elseif($post['automation_context'] == self::PROJECTADD){
                                                
                        $projects = json_decode($post['payload'], JSON_FORCE_OBJECT);
                        $nProjects = count($projects); 
                        
                        if($nProjects == 1){
                            $postArray['post_action'] = $lang['MESA_AUT_ADDPROJECT'];
                        }  else{                            
                            $postArray['post_action'] = generateHTML($lang['MESA_AUT_ADDPROJECTS'], array('no_projects' => $nProjects), True );                            
                        }                  
                        
                        $postArray['post_content'] = $this->_generateProjectTable($projects, $post['id']);                                                        
                    }
                    
                    
                    
                    $render .= generateHTML('wallBits/action', $postArray);                       
                }                
            }                        
        }
        
        //if last query count == max init load, then assume there COULD be more
        //if last query count < max init load, no load more button is needed        
        if( $numberOfPosts >= MESA_WALL_INITIAL_LOAD){
            $render .= generateHTML('wallBits/loadMoreButton', array('history_point' => $historyPoint - 1,  ));
        }
        
        
        $this->_template->set('wall', $render);        
    }
    
    
    public function getFullPost($postId, $type){
        
        $this->doNotRenderHeader = True;
        $result = $this->fetch($postId);
        $payload = json_decode($result['payload'], JSON_FORCE_OBJECT);
        
        if($type == 'project'){
            $render = $this->_generateProjectTable($payload, $postId, True);
        } elseif($type == 'sample'){
            $render = $this->_generateSampleTable($payload, $postId, True);
        }
        
      $this->_template->set('full_post', $render);
    }
    
    private function _generateSampleTable($samples, $postId, $full = False){
        
        $nSamples = count($samples);
        
        $table = new tableFactory();
        $table->setTableId('walltable_' . $postId);
        $table->loadTemplate('wallSampleTable');
        
        if($full == True){
            $load = $samples;
        } else{
            $split = array_chunk($samples, MESA_WALL_ADD_LIMIT);
            $load = $split[0];
        }
        
        $table->loadValues($load);                
        $render =  $table->renderTable();
        
        if($full == False && $nSamples > MESA_WALL_ADD_LIMIT){
            
            $leftToLoad = $nSamples - MESA_WALL_ADD_LIMIT;
            
            $render .= ' <center>
                        <a href="#" class="socialLoadMore" postType="sample" postId="' . $postId . '"  style="color: #ccc;"><i class="icon-angle-down"></i> {MESA_AUT_SHOWMORE} ( {MESA_AUT_EXPECTOSHOW} '. $leftToLoad . ') </a>
                        </center>';
        }
        
        return $render;
    }
    
    private function _generateProjectTable($projects, $postId, $full = False){
        
        $nProjects = count($projects);
        
        $table = new tableFactory();
        $table->setTableId('walltable_' . $postId);
        $table->loadTemplate('wallProjectTable');
        
        if($full == True){
            $load = $projects;
        } else{
            $split = array_chunk($projects, MESA_WALL_ADD_LIMIT);
            $load = $split[0];
        }
        
        $table->loadValues($load);                
        $render =  $table->renderTable();
        
        if($full == False && $nProjects > MESA_WALL_ADD_LIMIT){
            
            $leftToLoad = $nProjects - MESA_WALL_ADD_LIMIT;
            
            $render .= ' <center>
                        <a href="#" class="socialLoadMore" postType="project" postId="' . $postId . '" style="color: #ccc;"><i class="icon-angle-down"></i> {MESA_AUT_SHOWMORE} ( {MESA_AUT_EXPECTOSHOW} '. $leftToLoad . ') </a>
                        </center>';
        }
        
        return $render;
    }
    
     private function _renderReplys($id){
        
        $this->LabtalkEvent->free();
        $this->LabtalkEvent->where('reply_to', $id);
        $this->LabtalkEvent->where('context', self::REPLYPOST);
        $this->LabtalkEvent->order('time', 'ASC');
        $results = $this->LabtalkEvent->search();
                   
        if(empty($results)){
            return '';            
        }
        
        else{            
            $render = '';            
            foreach($results as $reply){                     
                $userInfo = getUserProfile($reply['user']);
                $postArray['post_username'] = $userInfo['username'];
                $postArray['post_realname'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                $postArray['post_date'] = date('d-m-Y', $reply['time']);
                $postArray['post_time'] = date('H:i', $reply['time']);
                $postArray['post_content'] =  $reply['payload'];
                $postArray['post_avatar'] = generateAvatar($userInfo['id'], $userInfo['avatar_uri'], 'small', False, True);                   
                $render .= generateHTML('wallBits/reply', $postArray);
                unset($postArray);
            }                        
            return $render;
        }                        
    }
    
    
    function removeWallPost($postId){
        
        //can only remov eown posts
        //or if user = superUser
        
        
        
        
    }
    
}