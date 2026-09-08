<?PHP

class roamingAnalysisController extends controller{

    function saveRoam($said, $assay, $dillution, $replicates, $reference, $referenceSource = NULL ){
        $this->render = 0;
        $this->RoamingAnalysis->said = $said;
        $this->RoamingAnalysis->assay = $assay;

        $dillutionArr = array();
        if(!empty($dillution)){
            $textAr = explode("\n", $dillution);
            $textAr = array_filter($textAr, 'trim');
            foreach ($textAr as $line) {
                $dInst = explode('=', $line);
                if(isset($dInst['0']) && isset($dInst['1'])){
                    $dillutionArr[$dInst['0']] = trim($dInst['1']);
                }
            }
        }

        $this->RoamingAnalysis->dillutions = json_encode($dillutionArr, JSON_FORCE_OBJECT);
        $this->RoamingAnalysis->replicates = $replicates;
        $this->RoamingAnalysis->reference = $reference;        
        $this->RoamingAnalysis->reference_source = $referenceSource; 
        $this->RoamingAnalysis->save();

        return $this->RoamingAnalysis->lastInsertId;

        /*
        $refValueArr = array();
        foreach($_POST as $postName => $postValue){
            if(substr($postName, 0, 4) == 'ref_'){
                $refValueArr[$postName] = $postValue;
            }
        }
        $referenceJSON = json_encode($refValueArr, JSON_FORCE_OBJECT); */
    }

    function createRefSettings($assayId){

    }

    function fetchSettingsBySA($said){
        $this->render = 0;
        $this->RoamingAnalysis->where('said', $said);
        $results = $this->RoamingAnalysis->search();
        return $results;
    }

    function fetchSettings($roamId){
        $this->render = 0;
        $this->RoamingAnalysis->where('id', $roamId);
        $results = $this->RoamingAnalysis->search();
        return $results;
    }

    function removeRoamBySA($said){
        $this->render = 0;
        $this->RoamingAnalysis->where('said', $said);
        $results = $this->RoamingAnalysis->search();

        if(!empty($results)){
            $this->RoamingAnalysis->id = $results['0']['id'];
            $this->RoamingAnalysis->remove();
        }

    }

    function changeRoamingSettings($said){


        $saInfo = pa('sampleAnalysis', 'fetchById', array($said) );
        $sampInfo = upa('samples', 'fetch', array($saInfo['sample']), False);

        $thisRoam = $this->fetchSettings($saInfo['roaming_id']);
        $thisRoamDewrap = json_decode($thisRoam['0']['reference'], JSON_FORCE_OBJECT);
        $outputs = pa('results', 'fetchOutputFields', array($saInfo['assay_base']) );

        $assayInfo = upa('assays', 'fetch', array($saInfo['assay_base']));

        $this->render = True;
        $this->doNotRenderHeader = True;

        $roamF = new formFactory('researchProfiles');
        $roamF->setId('roamingEditForm');
        $roamF->action('#');
        $roamF->method('');
        $roamF->addClass('');
        $roamF->setTemplate('generic');

        $restrict = [1,4,6,9];
        $restrictClass = '';

        if(in_array($assayInfo['type_base'], $restrict))
        {
            $restrictClass = ' numerical-only-filter';
        }



        foreach($outputs as $outputName => $dummyOutput){

            $thisRoamSetting = 0;
            if(array_key_exists('ref_' . $outputName, $thisRoamDewrap)){
                $thisRoamSetting = $thisRoamDewrap['ref_' . $outputName];
            }
            $roamF->addInputField('ref_' . $outputName, 'Reference value for ' . $outputName, 'text', 'input-block-level input-reference-field' . $restrictClass, $thisRoamSetting, 'Reference value');
        }

        $referenceSources = upa('referenceSources', 'list', array($sampInfo['client']), False);
        $roamF->addDropdownField('reference_source', 'Referentie Bron', 'input-block-level', $thisRoam[0]['reference_source'], $referenceSources, False);

        $roamF->submitTrough('saveRoamingReferenceBtn', 'saveRoamingRefChange');
        $this->_template->set('roaming_form', $roamF->render());


    }

    function saveRoamingChange($said){
        $this->render = False;
        $saInfo = pa('sampleAnalysis', 'fetchById', array($said) );
        $thisRoam = $this->fetchSettings($saInfo['roaming_id']);
        $thisRoamDewrap = json_decode($thisRoam['0']['reference'], JSON_FORCE_OBJECT);
        $outputs = pa('results', 'fetchOutputFields', array($saInfo['assay_base']) );

        foreach($outputs as $outputName => $dummyOutput){
            if(array_key_exists('ref_' . $outputName, $_POST)){
                $thisRoamDewrap['ref_' . $outputName] = $_POST['ref_' . $outputName];
            }
        }

        $this->RoamingAnalysis->id = $thisRoam[0]['id'];
        $this->RoamingAnalysis->reference = json_encode($thisRoamDewrap, JSON_FORCE_OBJECT);
        $this->RoamingAnalysis->reference_source = $_POST['reference_source'];
        $this->RoamingAnalysis->save();
    }

}
