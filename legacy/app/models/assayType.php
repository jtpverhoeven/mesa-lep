<?PHP

class AssayType extends Model{
    
//var $_relations = array('id' => 'testFields');
    
 function listAssayTypes(){
     $results = $this->search();
     return $results;
 }

 
 
}