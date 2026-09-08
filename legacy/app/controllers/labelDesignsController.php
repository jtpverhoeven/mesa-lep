<?PHP

class labelDesignsController extends controller {

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function printTestLabel(){

      $patterns = array();
      $replacements = array();

      $i = 0;
      foreach($_POST as $key => $value){

        $patterns[$i] = '/{' . $key . '}/';
        $replacements[$i] = $value;

        $i++;
      }

      $rendered = preg_replace($patterns, $replacements, $_POST['zpl']);
      $this->_template->set('rendered_zpl', $rendered);
      upa('printing', 'testLabel', array($rendered, $_POST['printer_selected']), False);

    }

    function testLabel($id){

      $this->LabelDesign->where('id', $id);
      $result = $this->LabelDesign->search();
      if(empty($result)){

      }

      $this->LabelDesign->arrayToModel($result[0]);
      preg_match_all("/\{[^\}]*\}/", $this->LabelDesign->zpl, $matches);

      $addForm = new formFactory($this->_controller);
      $addForm->setId('addLabelForm');
      $addForm->action('{LB}/labelDesigns/printTestLabel');
      $addForm->method('POST');

      $addForm->addClass('');
      $addForm->setTemplate('generic');

      foreach(array_unique($matches[0]) as $match){
         $stripped = str_replace('{', '', $match);
         $stripped = str_replace('}', '', $stripped);
         $addForm->addInputField($stripped,$stripped, 'text', 'input-block-level', '', 'Inhoud voor deze tag', False);
      }

      $printers = upa('printers', 'listPrintersInArray', array(), False);
      $addForm->addDropdownField('printer_selected', 'Stuur naar printer', 'input-block-level', False  , $printers, False);

      $addForm->addInputField('label_id', '', 'hidden', 'hidden',  $this->LabelDesign->id, False, False);
      $addForm->addInputField('zpl', '', 'hidden', 'hidden',  $this->LabelDesign->zpl, False, False);

      $addForm->submitTrough('printLabel', 'printLabel');
      $this->_template->set('label_form', $addForm->render());
    }

    function labelForm($id = False){
        $this->doNotRenderHeader = True;

        if($id != False){
            $this->LabelDesign->where('id', $id);
            $result = $this->LabelDesign->search();
            if(!empty($result)){
                $this->LabelDesign->arrayToModel($result[0]);
            }
        }

        $addForm = new formFactory($this->_controller);
        $addForm->setId('addLabelForm');
        $addForm->action('{LB}/labelDesigns/saveLabel');
        $addForm->method('POST');

        $addForm->addClass('');
        $addForm->setTemplate('generic');

        $addForm->addInputField('name', '{MESA_LBD_LABELNAME}', 'text', 'input-block-level',  $this->LabelDesign->name, '{MESA_LBD_LABELNAME}', False);
        //$addForm->addValidation('name', 'NO_DUPLICATE');
        $addForm->addTextArea('zpl', '{MESA_LBD_ZPLDESIGN}', '', 'textarea-block-level', $this->LabelDesign->zpl, '{MESA_LBD_ZPLDESIGN}',  array('rows'=>10));

        $boolOp['0'] = '{MESA_LBD_NO}';
        $boolOp['1'] = '{MESA_LBD_YES}';

        $addForm->addDropdownField('default_sample', '{MESA_LBD_DEFAULTSAMPLE}', 'input-block-level',  $this->LabelDesign->default_sample, $boolOp, False);
        $addForm->addDropdownField('default_analysis', '{MESA_LBD_DEFAULTANALYSIS}', 'input-block-level',  $this->LabelDesign->default_analysis, $boolOp, False);

        $printers = upa('printers', 'listPrintersInArray', array(), False);
        $addForm->addDropdownField('default_printer', '{MESA_LBD_DEFAULTPRINTER}', 'input-block-level',  $this->LabelDesign->default_printer, $printers, False);

        $addForm->addInputField('id', '', 'hidden', 'hidden',  $this->LabelDesign->id, False, False);
        $addForm->submitTrough('addLabelSubmit');
        $this->_template->set('render', $addForm->render());

    }

    function listLabels(){

        $results = $this->LabelDesign->search();

        if(empty($results)){
            $results = '{MESA_LBD_NOLABELS}';
        } else{

            foreach($results as $lbId => $label){

                if($label['default_sample'] == 1){
                    $results[$lbId]['std_sample'] = '<i class="icon-check"></i> {MESA_LBD_YES}';
                } else{
                    $results[$lbId]['std_sample'] = '{MESA_LBD_NO}';
                }

                if($label['default_analysis'] == 1){
                     $results[$lbId]['std_analysis'] = '<i class="icon-check"></i> {MESA_LBD_YES}';
                } else{
                     $results[$lbId]['std_analysis'] = '{MESA_LBD_NO}';
                }
            }
        }

        $tF = new tableFactory();
        $tF->setTableId('labelDesignTable');
        $tF->loadTemplate('labelDesignTable');
        $tF->loadValues($results);

        return $tF->renderTable();

    }


    function saveLabel(){

        //remember to set all the defaqults to 0
        $this->render = False;

        if(!empty($_POST['id'])){
            $this->LabelDesign->id = $_POST['id'];
        }

        $this->LabelDesign->name = $_POST['name'];
        $this->LabelDesign->zpl = $_POST['zpl'];
        $this->LabelDesign->default_sample = $_POST['default_sample'];
        $this->LabelDesign->default_analysis = $_POST['default_analysis'];
        $this->LabelDesign->default_printer = $_POST['default_printer'];
        $this->LabelDesign->save();
        $this->reRoute('labelDesigns/listing', True);
    }

    function removeLabel($labelId){
        $this->LabelDesign->id = $labelId;
        $this->LabelDesign->remove();
        $this->reRoute('labelDesigns/listing', True);
    }

   function listing(){

       $labelTbl = $this->listLabels();
       $this->_template->set('label_table', $labelTbl);

   }

   function listLabelsInArray($standard = False){

       $this->render = False;
       $result = $this->LabelDesign->search();

       if(!empty($result)){
           $retArr = array();

           if($standard != False){
               $retArr['default'] = '{MESA_S2P_DEFAULTLABEL}';
           }

           foreach($result as $label){
               $retArr[$label['id']] = $label['name'];
           }
           return $retArr;
       } else{
           return array('NULL' => 'No labels');
       }
   }


   function returnDefaultSampleLabel(){

        $this->render = False;
        $this->LabelDesign->where('default_sample', '1');
        $result = $this->LabelDesign->search();

        if(!empty($result)){
            return $result['0'];
        } else{
            return False;
        }
   }

   function returnAnalysisLabel(){

        $this->render = False;
        $this->LabelDesign->where('default_analysis', '1');
        $result = $this->LabelDesign->search();

        if(!empty($result)){
            return $result['0'];
        } else{
            return False;
        }

   }

}
