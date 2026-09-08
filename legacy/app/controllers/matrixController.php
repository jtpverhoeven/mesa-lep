<?PHP

class matrixController extends controller{

  function beforeAction($queryString) {
      $this->_template->set('MESA_LIMS_ACTIVE', '');
      $this->_template->set('MESA_SOCIAL_ACTIVE', '');
      $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
  }

  function index(){

      $matrices = $this->Matrix->search();

      $table = new tableFactory();
      $table->setTableId('matrixtable');
      $table->loadTemplate('matrixTable');

      if(empty($matrices)){
          $matrices = 'No matrices defined';
      }

      $table->loadValues($matrices);
      $this->_template->set('matrix_table', $table->renderTable());

      $sForm = new formFactory('matrix');

      $sForm->setId('addMatrixForm');
      $sForm->addClass('');
      $sForm->action( ALPC_BASEPATH . '/matrix/create');
      $sForm->method('POST');
      $sForm->setTemplate('generic');

      $sForm->addInputField('name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', '', '{MESA_ASE_ASSAYNAME}', False, False);
      $sForm->submitTrough('addMatrixSubmit');

      $this->_template->set('matrixForm', $sForm->render());
  }

  function edit($id){
    $result = $this->fetch($id);
    $sForm = new formFactory('matrix');
    $sForm->setId('addMatrixForm');
    $sForm->addClass('');
    $sForm->action( ALPC_BASEPATH . '/matrix/update/' . $id);
    $sForm->method('POST');
    $sForm->setTemplate('generic');
    $sForm->addInputField('name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', $result['name'], '{MESA_ASE_ASSAYNAME}', False, False);
    $sForm->submitTrough('addMatrixSubmit');
    $this->_template->set('matrixForm', $sForm->render());
  }

  function update($id){
    $this->render = False;
    $this->Matrix->id = $id;
    $this->Matrix->name = $_POST['name'];
    $this->Matrix->save();
    $this->reRoute('matrix/edit/' . $id, True);
  }

  function create(){
    $this->render = False;
    $this->Matrix->name = $_POST['name'];
    $this->Matrix->save();
    $goTo = $this->Matrix->lastInsertId;
    $this->reRoute('matrix/index', True);
  }

  function listMatrices(){
    $this->render = False;
    $matrices = $this->Matrix->search();
    return $matrices;
  }


  function matrixDropdown($filter = False, $default = False, $ids = False){
      $matrices = $this->listMatrices();
      //$dd = "<option value='0'>Alles</option>";
      $dd = "";
      foreach($matrices as $matrix){

        if(is_array($filter)){
            if(!in_array($matrix['id'], $filter)){
                continue;
            }
        }
        
        $select = '';

        //overall default 
        if($default === False){
            if(DEFAULT_MATRIX == $matrix['id']){
                $select = ' selected="selected" ';
            }
        }

        else{
            if($default == $matrix['id']){
                $select = ' selected="selected" ';
            }
        }

    
        $dd .= "<option value='". $matrix['id'] ."' " . $select . ">" . $matrix['name'] . "</option>";
    

        
      }
      return $dd;
  }





  function destroy($id){
    $this->render = False;
    $this->Matrix->id = $id;
    $this->Matrix->delete();
    upa('matrixContent', 'destroy', array($id), False);
    $this->reRoute('matrix/index', True);
  }


  //dispatch either to ALL in assays controller
  //dispatch to

  function loadMatrix($id){
    
    $this->render = False;
    $id = intval($id);

  

    if($id == 0){
        $list =  '';
    }
        
    else{
        $list =  upa('assays', 'assayListMatrix', array($id), False);      
    }

    print $list;

  }

  function loadAll(){

  }

  function fetchAssaysList($asArray = false){
      $this->render = 0;
      $this->Assay->where('active', '1');
      $this->Assay->order('name', 'ASC');
      $results = $this->Assay->search();
      $list = '';
      $options = '';

      foreach($results as $assay){
          if($assay['type'] != 4){
              $assay['icon'] = 'hide';
          } else{
              $assay['icon'] = '';
          }
          $list .= generateHTML('sampleEntry/assayLine', $assay);
          $options .= generateHTML('sampleEntry/assayLineOption', $assay);
      }

      if($asArray == False){
          return $list;
      } else{
          return array('html'=> $list, 'options' => $options);
      }
  }


}
