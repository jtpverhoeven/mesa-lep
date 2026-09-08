<?PHP

class matrixContentController extends controller{

  function beforeAction($queryString) {
      $this->_template->set('MESA_LIMS_ACTIVE', '');
      $this->_template->set('MESA_SOCIAL_ACTIVE', '');
      $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
  }

  function listAssays($matrix){
      $this->MatrixContent->where('matrix', $matrix);
      $assays = $this->MatrixContent->search();
      $matrixAssays = array();
      foreach($assays as $assay){
        array_push($matrixAssays, $assay['assay_base']);
      }
      return $matrixAssays;
  }

  function setMatrices($toSet, $assayBase){

      $ids = array_column($toSet, 'matrixId');      
      $this->MatrixContent->where('assay_base', $assayBase);
      $results = $this->MatrixContent->search();

      foreach($results as $result){
        $matrixId = $result['matrix'];
        $rowId = $result['id'];

        //was set, and is set, remove from id list
        if(in_array($matrixId, $ids)){
            $keys = array_keys($ids, $matrixId);
            foreach($keys as $thisKey){
              unset($ids[$thisKey]);
            }
        }

        //was not in list, so must have been removed
        else{
          $this->MatrixContent->deepFreed();
          $this->MatrixContent->id = $rowId;
          $this->MatrixContent->delete();
        }
      }

      //this is what is left and should be saved
      foreach($ids as $idToSave){
        $this->MatrixContent->deepFreed();
        $this->MatrixContent->assay_base = $assayBase;
        $this->MatrixContent->matrix = $idToSave;
        $this->MatrixContent->save();
      }
  }

  function matrices($assayBase){
    $this->MatrixContent->where('assay_base', $assayBase);
    return $this->MatrixContent->search();
  }

  function buildGUIWidget($assayBase){

      cphp('build gui with ' . $assayBase);
      $this->doNotRenderHeader = True;

      $all = upa('matrix', 'listMatrices', array(), False);
      $thisMatrices = $this->matrices($assayBase);
      $existingRows = '';
      $allIndexed = indexAlpacaArray($all);

      $chainlength = 1;
      foreach($thisMatrices as $thisMatrix){
        $existingRows .= $this->addLine($thisMatrix['matrix'], $chainlength, $allIndexed);
        $chainlength = $chainlength + 1;
      }

      $this->_template->set('existing_rows', $existingRows);
      $this->_template->set('available_matrices', $this->createMediaDropdown($all));
      $this->_template->set('widget_title', 'matrixContent');
      $this->_template->set('selector_name', 'matrixSelector');
      $this->_template->set('table_title', 'Deel van analyse matrices');
  }

  function returnWidgetLine($id, $length){

    //inputs an matrix ID

    $this->render = False;
    $all = upa('matrix', 'listMatrices', array(), False);
    $allIndexed = indexAlpacaArray($all);
    $line = $this->addLine($id, $length + 1 , $allIndexed);
    print $line;
  }

  function addLine($thisMatrix, $chainlength, $allIndexed){

    //this matrix should be an ID

    $lineVar = '<tr id="matrix_' . $chainlength .'">';
    $lineVar .= '<td matrixId="' . $thisMatrix . '">' . $chainlength   . '</td>';
    $lineVar .= '<td>' . $allIndexed[$thisMatrix]['name'] . '</td>';
    $lineVar .= '<td>  </td>';
    $lineVar .= '<td> </td>';
    $lineVar .= '<td><a onClick="removeMatrix(\'' . $chainlength .'\');">Verwijderen</a></td>';
    $lineVar .= '</tr>';

    return $lineVar;
  }

  public function createMediaDropdown($all ){
        $dropper = '<option value="NULL">Beschikbare matrices</option>';
        foreach($all as $matrix){
            $dropper .= '<option value="' . $matrix['id'] . '">' . $matrix['name'] . '</option>';
        }
        return $dropper;
    }

  function destroy($matrixId){
    $this->MatrixContent->where('matrix', $matrixId);
    $results = $this->MatrixContent->search();

    foreach($results as $result){
      $id = $result['id'];
      $this->MatrixContent->id = $id;
      $this->MatrixContent->delete();
      $this->MatrixContent->deepFreed();
    }
  }
}
