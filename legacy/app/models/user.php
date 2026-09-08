<?PHP

class user extends Model{
    //var $abstract = True;

    public function grab($id){

      $this->where('id', $id);      
      return $this->search();

    }
}
