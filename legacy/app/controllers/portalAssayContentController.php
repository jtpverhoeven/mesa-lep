<?PHP

class portalAssayContentController extends controller{

  function beforeAction($queryString) {
      $this->_template->set('MESA_LIMS_ACTIVE', '');
      $this->_template->set('MESA_SOCIAL_ACTIVE', '');
      $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
  }

 //upa('portalAssayContent', 'renderAssociations', array($this->PortalAssay->id, $showHidden), False    );

  function renderAssociations($commonAssay,$showHidden = False){

    $this->PortalAssayContent->where('common_id', $commonAssay);
    $results = $this->PortalAssayContent->search();
    $assocList = array();

    $this->PortalAssayContent->deepFreed();

    $this->PortalAssayContent->whereNot('common_id', $commonAssay);
    $elseWhereResults = $this->PortalAssayContent->search();

    $inUseElsewhere = array();

    foreach($elseWhereResults as $assocElseWhere)
    {
      $inUseElsewhere[] = $assocElseWhere['assay_id'];
    }    

    //sort them into a searchable array
    foreach($results as $assoc){
      $assocList[$assoc['assay_id']] = $assoc;
    }

    $tickerTable = '<tr>';
    $assays = upa('assays', 'getAssayList', array(True), False);

    $i = 0;

    foreach($assays as $assay){

      $activebool = filter_var($assay['active'], FILTER_VALIDATE_BOOLEAN);
      $showHiddenBool = filter_var($showHidden, FILTER_VALIDATE_BOOLEAN);
      $usedElsewhereBool = in_array($assay['id'], $inUseElsewhere);

      if($activebool == False && $showHiddenBool == False){
        continue;
      }
      

      if($i == 3){
        //close row and open new one
        $tickerTable .=  '</tr><tr>';
        $i = 0;
      }

      $assayArr = array();
      $assayArr['id'] = $assay['id'];
      $assayArr['original_id'] = $assay['original_id'];

      if($assay['active'] == '1'){
        $assayArr['name'] = $assay['name'];
      }

      if($assay['active'] == '0'){
        $assayArr['name'] = '<s>' . $assay['name'] . '</s>';
      }

      if(array_key_exists($assay['id'], $assocList)){
        $assayArr['checked'] = 'checked="checked"';
        $assayArr['assoc_id'] = $assocList[$assay['id']]['id'];
      } else{
        $assayArr['checked'] = '';
      }

      if($usedElsewhereBool == True){
        $assayArr['checked'] = 'disabled="disabled"';
      }

      $tickerTable .= generateHTML('docgenassociation/ticker', $assayArr);
      $i++;
    }

    //close of open row if needed
    if($i != 0){
      for($j = $i; $j < 3; $j++ ){
        $tickerTable .= '<td></td>';
      }
      $tickerTable .= '</tr>';
    }
    return $tickerTable;
  }

  function setAssoc($commondId, $assay, $originalAssayId, $value, $assocId){
      $this->render = False;
      //set
      if($value == '1'){
        $this->saveAssoc($commondId, $assay, $originalAssayId);
      }
      //remove s
      if($value == '0'){
        $this->removeAssoc($assocId);
      }
  }

  function saveAssoc($commonId, $assay, $originalAssayId){
    $this->PortalAssayContent->assay_id = $assay;
    $this->PortalAssayContent->original_id = $originalAssayId;
    $this->PortalAssayContent->common_id = $commonId;
    $this->PortalAssayContent->save();
    }

  function removeAssoc($assocId){
    $this->PortalAssayContent->id = $assocId;
    $this->PortalAssayContent->remove();
  }

  function getAssayAssoc($assay){
    $this->PortalAssayContent->where('assay_id', $assay);
    $result = $this->PortalAssayContent->search();
    $assocArray = array();

    foreach($result as $assoc){
      array_push($assocArray, $assoc['occurs_in']);
    }

    return $assocArray;
  }


  function updateAssayAssocs($old, $new){
    
    #fetch base assay original
    $ori = upa('assays', 'fetchOriginalId', array($old), False);

    $this->PortalAssayContent->where('assay_id', $old);
    $result = $this->PortalAssayContent->search();

    foreach($result as $assoc){
      $this->PortalAssayContent->assay_id = $new;
      $this->PortalAssayContent->original_id = $ori;
      $this->PortalAssayContent->common_id = $assoc['common_id'];
      $this->PortalAssayContent->save();
    }
  }
}
