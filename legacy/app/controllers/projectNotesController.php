<?PHP

class projectNotesController extends controller{
    
    function addNote(){
        $this->render = 0;
        $noteTitle = $_POST['noteTitle'];
        $noteContent = $_POST['noteContent'];        
        $this->ProjectNote->name = $noteTitle;
        $this->ProjectNote->content = $noteContent;
        $this->ProjectNote->save();        
    }
    
    function removeNote($id){
        $this->render = 0;
        $this->ProjectNote->id = $id;
        $this->ProjectNote->delete();
    }
    
    function fetchNote($id){
        $this->render = 0;
        $this->ProjectNote->where('id', $id);
        $result = $this->ProjectNote->search();
        
        if(!empty($result)){
            print $result['0']['content'];
        }        
    }

    function fetchNoteJSON($id){
        $this->render = 0;
        $this->ProjectNote->where('id', $id);
        $result = $this->ProjectNote->search();

        if(!empty($result)){
            print json_encode(array('id' => $result[0]['id'], 'name' => $result[0]['name'], 'content' => $result[0]['content'] ));
        }
    }

    function editNote($id){
        $this->ProjectNote->id = $id;
        $this->ProjectNote->name = $_POST['name'];
        $this->ProjectNote->content = $_POST['content'];
        $this->ProjectNote->save();
    }
    
    function listNote($return = False){
        
        $this->render = 0;
        $results = $this->ProjectNote->search();
        $selectors = '<option value="NULL"></option>';
                
        if(!empty($results)){
            foreach($results as $note){
                $selectors .= '<option value="' . $note['id'] .'">' . $note['name'] . '</option>';
            }            
        }                        
        
        if($return != False){
            return $selectors;
        }
        
        else{
            print $selectors;
        }
        
    }
    
}