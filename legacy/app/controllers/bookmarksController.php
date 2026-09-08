<?PHP

class bookmarksController extends controller {

    const PROJECT = 1;
       
    function isFollowing($id, $type){     
        $this->render = False;
        $this->Bookmark->where('user', getUserId());
        $this->Bookmark->where('type', constant('self::' . $type));        
        $this->Bookmark->where('type_id', $id);
        $this->Bookmark->search();
        
        if($this->Bookmark->lastQueryCount > 0){
            return True;
        } else{
            return False;
        }                
    }
    
    function follow($id, $type){
        
        $this->render = False;
        $alreadyFollowing = $this->isFollowing($id, $type);
        $this->Bookmark->free();
        
        if($alreadyFollowing == True){
            return;
        } else{            
            $this->Bookmark->user = getUserId();
            $this->Bookmark->type = constant('self::' . $type);
            $this->Bookmark->type_id = $id;
            $this->Bookmark->save();
        }                
    }
    
    function unFollow($id, $type){
                
        $this->render = False;
        $this->Bookmark->where('user', getUserId());
        $this->Bookmark->where('type', constant('self::' . $type));        
        $this->Bookmark->where('type_id', $id);
        $result = $this->Bookmark->search();
        
        if(!empty($result)){            
            $this->Bookmark->free();
            $this->Bookmark->id = $result[0]['id'];
            $this->Bookmark->remove();            
        }                        
    }
    
    function getBookmarks($user){        
        $this->Bookmark->where('user', $user);
        $results = $this->Bookmark->search();
        
        if(!empty($results)){
            
            foreach($results as $ind => $following){
                
                //project
                if($following['type'] == 1){
                    $projInfo = upa('projects', 'fetch', array($following['type_id']), 0);                    
                    $results[$ind]['info'] = $projInfo;                    
                }                                
            }            
        }        
        return $results;        
    }
    
    function countFollowers($id, $type){        
        $this->Bookmark->where('type', constant('self::' . $type));        
        $this->Bookmark->where('type_id', $id);
        $this->Bookmark->search();
        return $this->Bookmark->lastQueryCount;        
    }
    
    function removeProjectBookmarks($projectId){
        
        $this->Bookmark->where('type', self::PROJECT);        
        $this->Bookmark->where('type_id', $projectId);
        $results = $this->Bookmark->search();
        
        foreach($results as $bookmark){
            $this->Bookmark->free();
            $this->Bookmark->id = $bookmark['id'];
            $this->Bookmark->remove();
        }
        
        
    }
    
}