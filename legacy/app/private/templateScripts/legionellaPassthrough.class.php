<?PHP


class legionellaPassthrough extends calculation{


    protected $reportIn = 'kve';


    protected function _reportOutput(){
        return array('kve' => 'dummy');
    }

    public function provide(){

      $this->_log('In provide of legionellaPassthrough');
        //trigger meta analysis recalculation        
        $this->_checkMetaConfirmationNeeded();
        return $this->_dispatch();
    }


    protected function _checkMetaConfirmationNeeded(){
        
        $metaId = $this->_findMetaParent();             

        $this->_log('Touching parent meta-analyses said:' . $metaId);
        
        $ret = upa('results', 'msProcess', array($metaId), False);

        $this->_log('Meta analysis touch result:');
        $this->_log($ret);

        $saidResults = upa('sampleAnalysis', 'fetch', array($metaId), False);

        $this->output['meta_said'] = $saidResults;

        $disposition = checkKeyOrFalse($ret, 'disposition', 'kve');
        $this->disposition['kve'] =  $disposition;
        $this->_log('Passthrough found disposition for kve: ' . $disposition);        
        
        $this->output['meta_parent'] = $metaId;


        if(isset($ret['confirmation']['enabled']) && $ret['confirmation']['enabled'] == True)
        {

        if($saidResults['conf_requested'] == 0 && $disposition == "+")
        {
          $this->askForMetaParentConfirmation = checkKeyOrFalse($ret, 'askForMetaConfirmation');
        } 
        
        else 
        {
          $this->askForMetaParentConfirmation = False;
        }

      }


    }

}
