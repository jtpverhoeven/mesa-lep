<?PHP

class groupUsersController extends controller {
    
    protected $_publicActions = array( 'getUserAffiliation' => True );
    
    function getUserAffiliation($id = False){
        
        
        if($id == False){
            $this->GroupUser->where('userId', getUserId());
        } else{
            $this->GroupUser->where('userId', $id);
        }
        
        $results = $this->GroupUser->search();
        $affGroups = array();
        
        foreach($results as $group){
            array_push($affGroups, $group['groupId']);
        }
        
        return $affGroups;
    }
    
    
    
    function countUsersInGroup($groupId){        
        
        $this->GroupUser->where('groupId', $groupId);
        $this->GroupUser->search();
        
        return $this->GroupUser->lastQueryCount;        
    }
    
    function usersInGroup($groupId){
        
        
        $this->GroupUser->where('groupId', $groupId);
        $results = $this->GroupUser->search();
        
        return $results;
        
    }
    
    function addToGroup(){

        $userId = $_POST['userAdd'];
        $groupId = $_POST['addToGroup'];

        $this->render =  0;
        $this->GroupUser->where('groupId', $groupId);
        $this->GroupUser->where('userId', $userId);
        $this->GroupUser->search();
        
        if($this->GroupUser->lastQueryCount > 0){
            return;
        } else{
            $this->GroupUser->free();
            $this->GroupUser->userId = $userId;
            $this->GroupUser->groupId = $groupId;
            $this->GroupUser->save();
        }
        
    }
    
    function removeFromGroup(){
        $userId = $_POST['userAdd'];
        $groupId = $_POST['addToGroup'];
        
        $this->GroupUser->where('groupId', $groupId);
        $this->GroupUser->where('userId', $userId);
        $results = $this->GroupUser->search();
        
        if(!empty($results)){
            $this->GroupUser->id = $results[0]['id'];
            $this->GroupUser->remove();
        }
        
    }
    
    function removeGroupUsers($id){
        
        $this->GroupUser->where('groupId', $id);
        $results = $this->GroupUser->search();
        
        foreach($results as $priv){            
            $this->GroupUser->id = $priv['id'];
            $this->GroupUser->delete();
            $this->GroupUser->free();
        }      
        
    }
    
}