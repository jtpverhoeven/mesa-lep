<?PHP

class keyringsController extends controller{

    const SAMPLE = 0;
    const PROJECT = 1;
    const EXPORT = 2;
    const EXTERN = 3;

    function _sweep(){        
        $this->Keyring->lessThan('expires', time());
        $result = $this->Keyring->search();

        if(!empty($result)){
            foreach($result as $keyring){
                $this->Keyring->id = $keyring['id'];
                $this->Keyring->delete();
            }
        }

        $this->Keyring->free();
        unset($this->Keyring->id);
    }

    private function _setLock($type, $typeId){
        
        $lockObj = array();

        $issued = time();
        $expires = $issued + ALPC_SESSION_LIFETIME;

        $this->Keyring->type = constant('self::' . $type);
        $this->Keyring->type_id = $typeId;
        $this->Keyring->issued = $issued;
        $this->Keyring->issued_to = getUserId();
        $this->Keyring->expires = $expires;
        $this->Keyring->save();

        $lockObj['locked'] = False;
        $lockObj['lock_placed'] = True;
        $lockObj['lock_id'] = $this->Keyring->lastInsertId;

        return $lockObj;
    }

     function _checkLocked($type, $typeId){        
        $this->Keyring->where('type', constant('self::' . $type));
        $this->Keyring->where('type_id', $typeId);
        $result = $this->Keyring->search();
        $lockObj = array();

        if(!empty($result)){
            //self
            if($result['0']['issued_to'] == getUserId()){
                $lockObj['locked'] = False;
                $lockObj['lock_placed'] = True;
                $lockObj['lock_id'] = $result['0']['id'];
           
            } else{
                $lockObj['locked'] = True;
                $lockObj['lock_placed'] = False;
                $lockObj['locked_by_id'] = $result['0']['issued_to'];
                $lockObj['locked_by_name'] = getUserName($result['0']['issued_to']);
                $lockObj['locked_by_avatar'] = generateAvatar($result['0']['issued_to'], False, 'Small', False, True);
                $lockObj['lock_id'] = $result['0']['id'];
            }
        } else{
             $lockObj['locked'] = False;
             $lockObj['lock_placed'] = False;     
        }

        return $lockObj;

    }

    function removeOwnLockById($id){
                
        $this->render = False;
        $this->Keyring->where('issued_to', getUserId());        
        $this->Keyring->where('id', $id);

        $result = $this->Keyring->search();

        if(!empty($result)){
            foreach($result as $keyring){
                $this->Keyring->id = $keyring['id'];
                $this->Keyring->delete();
            }
        }

        unset($this->Keyring->id);
    }


    function removeOwnLock($type, $typeId){

        $this->render = False;
        $this->Keyring->where('issued_to', getUserId());
        $this->Keyring->where('type', constant('self::' . $type));
        $this->Keyring->where('type_id', $typeId);
        #$this->Keyring->order('issued', 'asc');
        #$this->Keyring->limit(1);

        $result = $this->Keyring->search();

        if(!empty($result)){
            foreach($result as $keyring){
                $this->Keyring->id = $keyring['id'];
                $this->Keyring->delete();
            }
        }

        unset($this->Keyring->id);
    }

    function removeLockForUser($user){
        $this->render = 0;
        $this->Keyring->where('issued_to', $user);
        $result = $this->Keyring->search();

        if(!empty($result)){
            foreach($result as $keyring){
                $this->Keyring->id = $keyring['id'];
                $this->Keyring->delete();
            }
        }
    }

    function requestLockAndStatus($type, $typeId ){
        $this->render = 0;
        $this->_sweep();

        //check if it is locked
        $lockObj = $this->_checkLocked($type, $typeId);

        
            
        //if($lockObj['locked'] == False && $lockObj['lock_placed'] != True){
        
        if($lockObj['locked'] == False && $lockObj['lock_placed'] != True){
            $lockObj =  $this->_setLock($type, $typeId);
        }
        
        return $lockObj;
    }




}

/*      When requesting a lock, set time requested
 *      When checkin locks, do a sweep first, checking for timeouts, and if the requesting user is still online.
 *
 *      set sample lock on sample scan
 *      on sample lock set, check if the user had a previous sample lock
 *
 */
