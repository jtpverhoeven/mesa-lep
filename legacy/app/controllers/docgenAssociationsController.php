<?PHP

class docgenAssociationsController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function view($template, $showHidden = False){

      $docgenTemplate = upa('docgenTemplates', 'fetch', array($template), False);
      $this->_template->set('template_name', $docgenTemplate['name']);
      $this->_template->set('template_id', $template);

      $this->DocgenAssociation->where('occurs_in', $template);      
      $results = $this->DocgenAssociation->search();
      $assocList = array();

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

      $templates = upa('docgenTemplates', 'allDocGenTemplates', array(), False);
      $dropper = '';
      foreach($templates as $atemplate)
      {
        $dropper .= '<option value="' . $atemplate['id'] . '"> ' . $atemplate['name'] . '</option>';
      }

      $this->_template->set('id',  $template);
      $this->_template->set('ticker_table', $tickerTable);
      $this->_template->set('template_select', $dropper );

    }

    function setAssoc($template, $assay, $originalAssayId, $value, $assocId){
        $this->render = False;
        //set
        if($value == '1'){
          $this->saveAssoc($template, $assay, $originalAssayId);
        }
        //remove s
        if($value == '0'){
          $this->removeAssoc($assocId);
        }
    }

    function saveAssoc($template, $assay, $originalAssayId){
      $this->DocgenAssociation->assay_id = $assay;
      $this->DocgenAssociation->original_assay_id = $originalAssayId;
      $this->DocgenAssociation->occurs_in = $template;
      $this->DocgenAssociation->save();
      }

    function removeAssoc($assocId){
      $this->DocgenAssociation->id = $assocId;
      $this->DocgenAssociation->remove();
    }

    function getAssayAssoc($assay){
      $this->DocgenAssociation->where('assay_id', $assay);
      $result = $this->DocgenAssociation->search();
      $assocArray = array();

      foreach($result as $assoc){
        array_push($assocArray, $assoc['occurs_in']);
      }

      return $assocArray;
    }

    function updateAssayAssocs($old, $new){

      #fetch base assay original
      $ori = upa('assays', 'fetchOriginalId', array($old), False);

      $this->DocgenAssociation->where('assay_id', $old);
      $result = $this->DocgenAssociation->search();

      foreach($result as $assoc){
        $this->DocgenAssociation->assay_id = $new;
        $this->DocgenAssociation->original_assay_id = $ori;
        $this->DocgenAssociation->occurs_in = $assoc['occurs_in'];
        $this->DocgenAssociation->save();
      }
    }


    function cloneFromOther()
    {
    
    
      $this->DocgenAssociation->where('occurs_in', $_POST['target']);
      $result = $this->DocgenAssociation->search();

      foreach($result as $assoc)
      {
        $this->DocgenAssociation->id = $assoc['id'];
        $this->DocgenAssociation->remove();
      }

      $this->DocgenAssociation->deepFreed(); 

      $this->DocgenAssociation->where('occurs_in', $_POST['copy_from']);
      $result = $this->DocgenAssociation->search();

      unset($this->DocGenAssociation->id);
      $this->DocgenAssociation->deepFreed(); 

      foreach($result as $assoc)
      {
        $this->DocgenAssociation->assay_id = $assoc['assay_id']; 
        $this->DocgenAssociation->original_assay_id = $assoc['original_assay_id'];
        $this->DocgenAssociation->occurs_in =$_POST['target'];
        $this->DocgenAssociation->save();
      }


      $this->reRoute('docgenAssociations/view/' . $_POST['target'], true);

    }


}
