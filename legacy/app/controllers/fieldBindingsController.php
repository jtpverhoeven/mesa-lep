<?php


class fieldBindingsController extends Controller{
    
    private $_resultsArray;
    
    
    public function removeBinding($analytical, $bindingId){
        $this->FieldBinding->id = $bindingId;
        $this->FieldBinding->delete();
        $this->reRoute('assayTypeFields/edit/' .$analytical, True);  
    }
    
    public function listBindings($analytical){
        
        $this->FieldBinding->where('analytical', $analytical);
        $binds = $this->FieldBinding->search();
        
        if(empty($binds)){
            $response = generateHTML('alertWarning', array('alert_title'=>'{MESA_AST_NOBINDINGS}', 'alert_message' => ''));
        } else {
             $tF = new tableFactory();
             $tF->loadTemplate('fieldbindListing');
             $tF->loadValues($binds);         
             $response = $tF->renderTable();
        }
        
        return $response;
    }
    
    public function saveBinding(){        
        $this->render = 0;
        $this->FieldBinding->postToModel($_POST);
        $this->FieldBinding->save();        
        $this->reRoute('analyticals/edit/' . $_POST['analyticalId'], True);        
    }
    
    public function bindingHandler($triggered, $analytical, $resultArray, $triggerType = 'onChange'){
        
        $this->_resultsArray = $resultArray;
        $binds = $this->checkBinding($triggered, $analytical, $triggerType);
                
        //no bindings, can return results as they were
        if(empty($binds)){
            return $resultArray;
        }
        
        foreach($binds as $binding){            
            if($binding['type'] == 'math'){
                $this->runMath($binding['instructions']);
            }            
        }
      
       
        return $this->_resultsArray;        
    }
    
    public function checkBinding($triggered, $analytical, $triggerType){
        
        $this->render = 0;         
        $this->FieldBinding->trigger_by = $triggered;
        $this->FieldBinding->analytical = $analytical;
        $this->FieldBinding->trigger_type = $triggerType;
        $this->FieldBinding->order_by = 'exec_order';
        $this->FieldBinding->order = 'ASC';
        
        return $this->FieldBinding->search();                
    }
 
    private function runMath($instructions){
        
        //instanciate EOS
        $eos = new eqEOS();
        
        //find target field
        $splice = explode('=', $instructions);
        $targetField = trim($splice[0]);
    
        //fetch instructions
        $instructions = $this->replace($splice[1], $this->_resultsArray);
        
        //run math
        $result = $eos->solveIF($instructions, null);
        //set result array
        $this->_resultsArray[$targetField] = $result;
        
        unset($eos);                           
    }
    
    private function replace($str, $arr){
        
        if (preg_match_all("!\{(\w+)\}!", $str, $matches)) {                        
            foreach ($matches[0] as $hitId => $hitTag) {
                $deCurledTag = $matches[1][$hitId];
                $tagExists = array_key_exists($deCurledTag, $arr);
                
                if ($tagExists === True) {
                    $tagContent = $arr[$deCurledTag];   // dump tag content here
                } else {                    
                    $tagContent = '';  
                }
         
                $str = str_replace($hitTag, $tagContent, $str, $noReps);                
            }
         }
        return $str;
    }
    
}