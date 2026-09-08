<?PHP

class confKeyStoreController extends controller{


  function store($innocdate, $media, $param, $value){


    if(empty($innocdate) || $innocdate == False){
      return False;
    }

    $id = $this->check($innocdate, $media, $param);

    if($id != False){
      $this->ConfKeyStore->id = $id;
    } else{
      $this->ConfKeyStore->innocdate = $innocdate;
      $this->ConfKeyStore->media = $media;
      $this->ConfKeyStore->param = $param;
    }


    $this->ConfKeyStore->value = $value;
    $this->ConfKeyStore->save();
  }

  function check($innocdate, $media, $param){

    $this->ConfKeyStore->where('innocdate', $innocdate);
    $this->ConfKeyStore->where('media', $media);  
    $this->ConfKeyStore->where('param', $param);
    $this->ConfKeyStore->limit(1);
    $result = $this->ConfKeyStore->search();
    $this->ConfKeyStore->deepFreed();

    if(empty($result)){
      return False;
    } else{
      return $result[0]['id'];
    }

  }

  function fetchValue($innocDate, $media, $param){

    if(empty($innocDate) || $innocDate == False){
      return False;
    }

    $this->ConfKeyStore->where('innocdate', $innocDate);
    $this->ConfKeyStore->where('media', $media);
    $this->ConfKeyStore->where('param', $param);
    $this->ConfKeyStore->limit(1);
    $result = $this->ConfKeyStore->search();
    $this->ConfKeyStore->deepFreed();

    if(empty($result)){
      return NULL;
    } else{
      $value = trim($result[0]['value']);


      //if its a zero, also return it
      if($value === '0'){
          return $value;
      }

      else{
        //if its empty however, return NULL
        if(empty($value)){
          return NULL;
        } else{
          return $value;
        }
      }





    }
  }




}
