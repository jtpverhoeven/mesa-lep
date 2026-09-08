<?PHP

class printersController extends controller{
    
    
    function listPrinters(){
     
        $this->render = False;
        $results = $this->Printer->search();
        
        if(empty($results)){
            $results = '{MESA_PRN_NOPRINTERS}';
        }
        
        $tF = new tableFactory();        
        $tF->setTableId('printHw');
        $tF->loadTemplate('printHwTable');                
        $tF->loadValues($results);         

        return $tF->renderTable();
        
    }
    
 
    function editPrinterForm($id = False){
        
        
        if($id != False){
            $this->Printer->where('id', $id);
            $result = $this->Printer->search();
            
            //if(empty($result)){
            //    $this->reRoute(ALPC_BASEPATH . '/cvars/listing', True);
            //} 
            
            if(!empty($result)){
                $this->Printer->arrayToModel($result[0]);
            }
        }
        
        
        $addForm = new formFactory($this->_controller);
        
        $addForm->setId('addPrinterForm');
        $addForm->action('{LB}/printers/savePrinter');
        $addForm->method('POST');
        
        $addForm->addClass('form-horizontal');       
        $addForm->setTemplate('generic');
        
        $addForm->addInputField('name', '{MESA_PRN_NAME}', 'text', 'input-block-level',  $this->Printer->name, '{MESA_PRN_NAME}', False);                
        $addForm->addValidation('name', 'NO_DUPLICATE');        
                
        $printTypes = array('0' => '{MESA_PRN_TYPESHARED}', '1' => '{MESA_PRN_TYPEPRINTSERVER}');
        $addForm->addDropdownField('type', '{MESA_PRN_TYPE}', 'input-block-level',  $this->Printer->type, $printTypes, False);
        
        $addForm->addInputField('adres', '{MESA_PRN_ADRES}', 'text', 'input-block-level',  $this->Printer->adres, '{MESA_PRN_ADRES}', False);        
        $addForm->addValidation('adres', 'NOT_EMPTY');        
        $addForm->addInputField('port', '{MESA_PRN_PORT}', 'text', 'input-block-level',  $this->Printer->port, '{MESA_PRN_PORT}', False);                                                               
        $addForm->addInputField('id', '', 'hidden', 'hidden',  $this->Printer->id, False, False);                                                       
        
        //$addForm->addButton('submitButton', 'icon-plus-sign', False, 'btn btn-primary btn-small', '{MESA_PRN_ADDPRINTER}', False);        
        $addForm->submitTrough('addPrinterSubmit');
          
        if($id != False){
            $this->doNotRenderHeader = True;
            $this->_template->set('render', $addForm->render());
            
        } else{
            return $addForm->render();
        }                            
    }
    
    function savePrinter(){     
        
        $this->render = False;
        
        if(!empty($_POST['id'])){
            $this->Printer->id = $_POST['id'];
        }        
        $this->Printer->name = $_POST['name'];
        $this->Printer->type = $_POST['type'];
        $this->Printer->adres = $_POST['adres'];
        $this->Printer->port = $_POST['port'];
        $this->Printer->save();        
        $this->reRoute('cvars/listing', True);
    }
    
    function removePrinter($id){
        
        $this->Printer->id = $id;
        $this->Printer->remove();
        $this->reRoute('cvars/listing', True);        
    }
    
    function listPrintersInArray(){
        $this->render = False;
        $result = $this->Printer->search();
        
        if(!empty($result)){           
           $retArr = array();
           foreach($result as $printer){
               $retArr[$printer['id']] = $printer['name'];
           }
           return $retArr;
       } else{
           return array('NULL' => 'No printers');
       }
    }
    
}