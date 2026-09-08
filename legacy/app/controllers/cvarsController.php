<?PHP

class cvarsController extends controller{

    protected $_publicActions = array( 'switchProd' => True);

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function loadConfig(){
        $this->render = 0;
        $cvars = $this->Cvar->search();
        return $cvars;
    }

   function listing(){
        $cvars = $this->Cvar->search();
        $tF = new tableFactory();
        $tF->setTableId('cvarTable');
        $tF->loadTemplate('cvarTable');
        $tF->loadValues($cvars);
        $this->_template->set('cvar_table', $tF->renderTable());

        $printers = upa('printers', 'listPrinters', array());
        $this->_template->set('print_hw', $printers);

        //$calcForm =

        $printerForm = upa('printers', 'editPrinterForm', array());
        $this->_template->set('add_printer_form', $printerForm);

   }

   function cvarSave(){
       $this->render = False;
       $id = $_POST['id'];
       $value = $_POST['value'];
       $this->Cvar->id = $id;
       $this->Cvar->value = $value;
       $this->Cvar->save();
   }

   function cvarSaveName($name, $value){

       $this->render = False;
       $this->Cvar->where('cvar', $name);
       $result =  $this->Cvar->search();

       if(!empty($result)){
           $id = $result[0]['id'];
       }
       $this->Cvar->id = $id;
       $this->Cvar->value = $value;
       $this->Cvar->save();
   }

    function grabCvar($name){
        $this->render = False;
        $this->Cvar->where('cvar', $name);
        $result =  $this->Cvar->search();

        if(!empty($result)){
            return $result[0]['value'];
        }
    }

    function switchProd($status = 0){
      $this->render = False;
      $this->cvarSaveName('dev_active', $status);
    }


}
