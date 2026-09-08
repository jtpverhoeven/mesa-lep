<?PHP

class vetoResultsController extends controller{

  function fetchVeto(){

    $this->render = False;
    $this->VetoResult->where('said', $_POST['said']);
    $this->VetoResult->where('parameter',$_POST['parameter']);    

    $saidInfo = upa('sampleAnalysis', 'fetch', array($_POST['said']), False);
    $assayInfo = upa('assays', 'fetch', array($saidInfo['assay_base']), False );

    $result = $this->VetoResult->search();
    $retArr = array();
    $retArr['result'] = '';
    $retArr['reason'] = '';
    //$retArr['border'] = ((int)$assayInfo['type_base'] === 3 || (int)$assayInfo['type_base'] === 5 ) ? True : False;    

    $retArr['border'] = ((int)$assayInfo['type'] === 2 || (int)$assayInfo['type'] === 4 ) ? True : False;    
    $retArr['disposition'] = 'null';

    if(!empty($result))
    {
      $retArr['result'] = $result[0]['result'];
      $retArr['reason'] = $result[0]['reason'];
      $retArr['disposition'] = $result[0]['disposition'];
    }

    print json_encode($retArr, JSON_FORCE_OBJECT);
  }

  function checkVeto($said, $parameter, $reasons = False, $json = False){

    $this->render = 0;

    $this->VetoResult->where('said', $said);
    $this->VetoResult->where('parameter', $parameter);

    $result = $this->VetoResult->search();
    if(!empty($result)){

      if($reasons == False){
        return $result['0']['result'];
      } else{

        if($json == False){
          return array('result' => $result['0']['result'], 'reason' =>  $result['0']['reason'], 'disposition' => $result['0']['disposition']);
        } else{
          print json_encode($result[0], JSON_FORCE_OBJECT);
        }
      }
    } else{
      return False;
    }
  }

  function saveVeto(){

    $this->render = 0;

    $said = $_POST['said'];
    $param = $_POST['parameter'];
    $value = $_POST['value'];
    $disposition = null;
    $reason  = '';
    
    if(isset($_POST['reason'])){
      $reason = $_POST['reason'];
    }

    if(isset($_POST['disposition']) && $_POST['disposition'] !== 'null' ){
      $disposition = $_POST['disposition'];
    }

    $this->VetoResult->where('said', $said);
    $this->VetoResult->where('parameter', $param);
    $result = $this->VetoResult->search();
    $fromResult = upa('results', 'msProcess', array($said), False);
    $from = checkKeyOrFalse($fromResult, 'output', $param);

    if(!empty($result)){
      $this->VetoResult->id = $result['0']['id'];
      $this->VetoResult->said = $said;
      $this->VetoResult->parameter = $param;
      $this->VetoResult->reason = $reason;
      $this->VetoResult->result = $value;
      $this->VetoResult->disposition = $disposition;
      $this->VetoResult->save();
    } else{
      $this->VetoResult->said = $said;
      $this->VetoResult->parameter = $param;
      $this->VetoResult->result = $value;
      $this->VetoResult->reason = $reason;
      $this->VetoResult->disposition = $disposition;
      $this->VetoResult->save();
      //return False;
    }

    //$scope, $scopeId, $changed = False, $from = False, $to = False
    $saInfo = upa('sampleAnalysis', 'fetch', array($said));
    $anaInfo = upa('assays', 'fetch', array($saInfo['assay_base']));
    $sampleInfo = upa('samples', 'fetch' , array($saInfo['sample']), False);
    $revisionText = $anaInfo['name'] . ', aangegeven reden: ' . $reason;

    // $scope, $scopeId, $changed = False, $from = False, $to = False, $saId = 0, $resultId = 0
    // upa('revisions', 'registerRevision', array( 'SCOPE_SAMPLEINFO_VETOSET',
    //                                             $saInfo['sample'],
    //                                             $revisionText,
    //                                             $param . ':' . $from,
    //                                             $param . ':' . $value  ), False);

    $event = 'Veto resultaat aangepast voor monster ' . $sampleInfo['barcode'] . ' in analyse ' . $anaInfo['name'] . '. Reden: ' . $reason;
    upa('changeTracker', 'changed', array(11, $sampleInfo['project'], $sampleInfo['id'], $said, $event, $param . ':' . $from, $param . ':' . $value ), False);

    upa('projects', 'projectEdited', array(False, $saInfo['sample']), False);
  }


  function removeVeto(){
    $this->render = 0;
    $said = $_POST['said'];
    $param = $_POST['parameter'];

    $this->VetoResult->where('said', $said);
    $this->VetoResult->where('parameter', $param);

    $fromResult = upa('results', 'msProcess', array($said), False);
    $from = checkKeyOrFalse($fromResult, 'output', $param);
    //this is abit weird, but we store the overwrite already in the calculation object
    //so we can fetch it from here instead of just reloading it after
    $to = checkKeyOrFalse($fromResult, 'vetoOverWrite', $param);

    $result = $this->VetoResult->search();
    if(!empty($result)){
      $this->VetoResult->id = $result['0']['id'];
      # $from = $this->VetoResult->result;
      $this->VetoResult->remove();
    }

    $saInfo = upa('sampleAnalysis', 'fetch', array($said));
    $anaInfo = upa('assays', 'fetch', array($saInfo['assay_base']));
    $sampleInfo = upa('samples', 'fetch' , array($saInfo['sample']), False);
    //upa('revisions', 'registerRevision', array('SCOPE_SAMPLEINFO_VETOREM',
    //                                            $saInfo['sample'], $anaInfo['name'],
    //                                            $param . ':' . $from,
    //                                            $param . ':' . $to   ), False);

    $event = 'Veto resultaat verwijderd voor monster ' . $sampleInfo['barcode'] . ', analyse '  . $anaInfo['name'];
    upa('changeTracker', 'changed', array(11, $sampleInfo['project'], $sampleInfo['id'], $said, $event, $param . ':' . $from, $param . ':' . $to ), False);

    upa('projects', 'projectEdited', array(False, $saInfo['sample']), False);
  }



  function removeVetoSaid($said){

    $this->render = 0;
    $this->VetoResult->where('said', $said);

    $result = $this->VetoResult->search();
    if(!empty($result)){
      $this->VetoResult->id = $result['0']['id'];
      $this->VetoResult->remove();
    }
  }


}
