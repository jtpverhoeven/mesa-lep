<?PHP

class contactgroupMembersController extends controller{


    public function getGroupMembers($cgroup){
        $this->ContactgroupMember->where('contactgroup_id', $cgroup);
        $results = $this->ContactgroupMember->search();
        return $results;
    }

    public function destroy($group, $id){
        //$this->ContactgroupMember->id = $id;
        //$this->ContactgroupMember->remove();
        upa('portal', 'dropContact', array($id), False );
        $this->reRoute('contactGroups/edit/' . $group , True);
    }

    public function add(){        
        $this->render = False;
        //$this->ContactgroupMember->name = $_POST['name'];
        //$this->ContactgroupMember->email = $_POST['email'];
        //$this->ContactgroupMember->contactgroup_id = $_POST['cgroup'];
        //$this->ContactgroupMember->save();

        upa('portal', 'addContact', array($_POST['name'], $_POST['email'], $_POST['cgroup']), false);

        $this->reRoute('contactGroups/edit/' .  $_POST['cgroup'], True);
    }

    public function members($cgroup){

        $this->ContactgroupMember->where('contactgroup_id', $cgroup);
        $results =   $this->ContactgroupMember->search();

        $members = '';
        foreach($results as $result){
            $members .= $result['email'] . '(' . $result['name'] . ') -';
        }
        return $members;
    }

    public function getEmails($groups){                               
        if(empty($groups)){
          return array();
        }
  
        $idMap = implode(',', array_map('intval', $groups));
        $sql = 'SELECT DISTINCT `email` FROM `contactgroupmembers` WHERE `contactgroup_id` IN (' . $idMap . ') ';
        return $this->ContactgroupMember->customQuery($sql, array());
    }
     

}