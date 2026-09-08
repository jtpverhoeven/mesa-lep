<?PHP

class revisionsController extends controller{

    //sample results
    const SCOPE_SAMPLERESULT_CHANGE = 'SMP_RES';
    const SCOPE_SAMPLERESULT_CONFIRMATION_CHANGE = 'SMP_CONF';

    //samples themselves
    const SCOPE_SAMPLEINFO_CHANGE = 'SMP_INF';
    const SCOPE_SAMPLEINFO_ASSAYADD = 'SMP_ANAADD';
    const SCOPE_SAMPLEINFO_PROFILEADD = 'SMP_PRFADD';
    const SCOPE_SAMPLEINFO_ASSAYREM = 'SMP_ANAREM';
    const SCOPE_SAMPLEINFO_VETOSET = 'SMP_VETOSET';
    const SCOPE_SAMPLEINFO_VETOREM = 'SMP_VETOREM';

    //projectsPRJ_SMPREM
    const SCOPE_PROJECT_START = 'PRJ_ADD';
    const SCOPE_PROJECTINFO_CHANGE = 'PRJ_INF';
    const SCOPE_PROJECTINFO_AUTH = 'PRJ_AUTH';
    const SCOPE_PROJECTINFO_AUTH_PREV = 'PRJ_AUTH_PREV';
    const SCOPE_PROJECT_SAMPLEADD = 'PRJ_SMPADD';
    const SCOPE_PROJECT_SAMPLEREM = 'PRJ_SMPREM';

    const SCOPE_ASSURANCE_CHANGE = 'ASR_CHANGE';

    //borgings formulieren



    function registerRevision( $scope, $scopeId, $changed = False, $from = False, $to = False, $saId = 0, $resultId = 0){
        $this->render = False;
        $this->Revision->user = getUserId();
        $this->Revision->time = time();
        $this->Revision->scope = constant('self::'. $scope);
        $this->Revision->scopeId = $scopeId;
        $this->Revision->changed = $changed;
        $this->Revision->from = $from;
        $this->Revision->to = $to;
        $this->Revision->sa_id = $saId;
        $this->Revision->result_id = $resultId;
        $this->Revision->save();
    }

    function assuranceformRevision($assuranceFormId, $hard = False){
      $this->doNotRenderHeader = True;
      $this->Revision->where('scope', self::SCOPE_ASSURANCE_CHANGE);
      $this->Revision->where('scopeId', $assuranceFormId);
      $this->Revision->order('time', 'DESC');
      $results = $this->Revision->search();

      if(empty($results)){
        $results = '{MESA_REV_NOREVS}';
      } else {

        $tF = new tableFactory();
        $tF->loadTemplate('revisionTableAssurance');
        $tF->loadValues($results);
        $tF->specifyMod('user', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('time', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

      }

      if($hard == True){
        return $this->hardRender();
      }

    }

    function analysisRevisionReport($sampleId, $analysis, $hard = False){

        $this->doNotRenderHeader = True;
        $this->Revision->where('scope', self::SCOPE_SAMPLERESULT_CHANGE);
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('sa_id',  $analysis);
        $this->Revision->insertOR();
        $this->Revision->where('scope', self::SCOPE_SAMPLERESULT_CONFIRMATION_CHANGE);
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('sa_id',  $analysis);

        $this->Revision->order('time', 'DESC');
        $results = $this->Revision->search();

        if(empty($results)){

            if($hard == True){
              $this->render = False;
              return False;
            }

            $results = '{MESA_REV_NOREVS}';
        }

        else{

            $loadedResults = array();
            $loadedProfiles = array();
            $loadedRoaming = array();

            foreach($results as $arrInd => $revision){

                if(!array_key_exists($revision['result_id'], $loadedResults)){
                    $loadedResults[$revision['result_id']] = upa('results', 'fetch', array($revision['result_id']));
                }
                $profileId = $loadedResults[$revision['result_id']]['profile'];
                $roamingId = $loadedResults[$revision['result_id']]['roaming_id'];

                if(!array_key_exists($profileId, $loadedProfiles)){
                    $loadedProfiles[$profileId] = upa('assayProfiles', 'fetch', array($profileId), False);
                }

                if($roamingId != 0 && !array_key_exists($roamingId, $loadedRoaming)){
                    $loadedRoaming[$roamingId] = upa('roamingAnalysis', 'fetch', array($roamingId), 0);
                }

                //dserialize
                if($roamingId != 0){
                    $dillutionArr = json_decode($loadedRoaming[$roamingId]['dillutions']);
                } else{
                    $dillutionArr = json_decode($loadedProfiles[$profileId]['dillutions']);
                }

                $results[$arrInd]['dillution_visual'] = $loadedResults[$revision['result_id']]['df'];
                $results[$arrInd]['replicate'] = $loadedResults[$revision['result_id']]['rep'];
            }
        }

        $tF = new tableFactory();
        $tF->loadTemplate('revisionTable');
        $tF->loadValues($results);
        $tF->specifyMod('user', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('time', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

        if($hard == True){
          return $this->hardRender();
        }
    }

    function sampleRevisionReport($sampleId, $hard = False){

        $this->doNotRenderHeader = True;

        $cFields = upa('sampleFields', 'fetchCustomFields', array(), 0);
        $CFieldsArr = array();

        if(!empty($cFields)){
            foreach($cFields as $field){
                $CFieldsArr[$field['name']] = $field['alias'];
            }
        }

        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_CHANGE);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_ASSAYADD);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_ASSAYREM);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_VETOREM);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_VETOSET);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLERESULT_CONFIRMATION_CHANGE);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLERESULT_CHANGE);

        $this->Revision->order('time', 'DESC');
        $results = $this->Revision->search();

        if(empty($results)){
            $results = '{MESA_REV_NOREVS}';
        } else{

          $loadedResults = array();
          $loadedProfiles = array();
          $loadedRoaming = array();

            foreach($results as $arrInd => $revision){

                //add label flag
                $results[$arrInd]['label'] = 'blarp';
                //$results[$arrInd]['user'] = userIdToName($revision['user']);

                if($revision['scope'] == self::SCOPE_SAMPLERESULT_CHANGE){
                  if(!array_key_exists($revision['result_id'], $loadedResults)){
                      $loadedResults[$revision['result_id']] = upa('results', 'fetch', array($revision['result_id']));
                  }
                  $profileId = $loadedResults[$revision['result_id']]['profile'];
                  $roamingId = $loadedResults[$revision['result_id']]['roaming_id'];

                  if(!array_key_exists($profileId, $loadedProfiles)){
                      $loadedProfiles[$profileId] = upa('assayProfiles', 'fetch', array($profileId), False);
                  }

                  if($roamingId != 0 && !array_key_exists($roamingId, $loadedRoaming)){
                      $loadedRoaming[$roamingId] = upa('roamingAnalysis', 'fetch', array($roamingId), 0);
                  }

                  //dserialize
                  if($roamingId != 0){
                      $dillutionArr = json_decode($loadedRoaming[$roamingId]['dillutions']);
                  } else{
                      $dillutionArr = json_decode($loadedProfiles[$profileId]['dillutions']);
                  }

                  $results[$arrInd]['label'] = $results[$arrInd]['changed'] . ' - Verdunning: ' . $loadedResults[$revision['result_id']]['df'] . ' - Replica:' .  $loadedResults[$revision['result_id']]['rep'];

                  //$results[$arrInd]['dillution_visual'] = $loadedResults[$revision['result_id']]['df'];
                  //$results[$arrInd]['replicate'] = $loadedResults[$revision['result_id']]['rep'];
                }

                //check what the change was
                if($revision['scope'] == self::SCOPE_SAMPLEINFO_CHANGE){

                    if($revision['changed'] == 'description'){
                        $results[$arrInd]['label'] = '{MESA_SAD_SAMPLEDESCRIPTION}';
                    } elseif($revision['changed'] == 'sampling_method' ){
                        $results[$arrInd]['label'] = '{MESA_SAD_SAMPLEMETHOD}';
                    } else{


                        if(array_key_exists($revision['changed'], $CFieldsArr)){
                            $results[$arrInd]['label'] = $CFieldsArr[$revision['changed']];
                        } else{
                            $results[$arrInd]['label'] = $revision['changed'];
                        }
                    }
                }

                if($revision['scope'] == self::SCOPE_SAMPLEINFO_ASSAYADD){
                    $results[$arrInd]['label'] = '{MESA_REV_ASSAYADDED} (' . $revision['changed'] . ')';
                }

                if($revision['scope'] == self::SCOPE_SAMPLEINFO_ASSAYREM){
                    $results[$arrInd]['label'] = '{MESA_REV_ASSAYREMOVED} (' . $revision['changed'] . ')';
                }

                if($revision['scope'] == self::SCOPE_SAMPLEINFO_VETOREM){
                    $results[$arrInd]['label'] = '{MESA_REV_VETOREM} {MESA_REV_FOR}: ' . $revision['changed'];
                }

                if($revision['scope'] == self::SCOPE_SAMPLEINFO_VETOSET){
                    $results[$arrInd]['label'] = '{MESA_REV_VETOSET} {MESA_REV_FOR}: ' . $revision['changed'];
                }
            }
        }

        $tF = new tableFactory();
        $tF->loadTemplate('sampleRevisionTable');
        $tF->loadValues($results);
        $tF->specifyMod('user', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('time', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

        if($hard == True){
          return $this->hardRender();
        }
    }


    function projectRevisionReport($projectId, $hard = False){

        global $lang;

        $this->doNotRenderHeader = True;
        $this->Revision->where('scopeId', $projectId);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_CHANGE);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $projectId);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_AUTH);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $projectId);
        $this->Revision->where('scope', self::SCOPE_PROJECT_SAMPLEADD);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $projectId);
        $this->Revision->where('scope', self::SCOPE_PROJECT_SAMPLEREM);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $projectId);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_AUTH_PREV);
        $this->Revision->order('time', 'DESC');



        $results = $this->Revision->search();

        if(empty($results)){
            $results = '{MESA_REV_NOREVS}';
        }

        else{

            $cFields = upa('projectFields', 'fetchProjectFields', array(), False);
            $cFieldsArr = array();

            foreach($cFields as $projectField){
                $cFieldsArr[$projectField['name']] = $projectField['alias'];
            }

            foreach($results as $arrInd => $revision){

                $results[$arrInd]['id'] = $revision['id'];
                $results[$arrInd]['label'] = '';
                if($revision['scope'] == self::SCOPE_PROJECTINFO_CHANGE){

                    if($revision['changed'] == 'project_name'){
                        $results[$arrInd]['label'] = $lang['MESA_PLU_PROJECTNAME'];
                    }

                    elseif($revision['changed'] == 'project_notes'){
                        $results[$arrInd]['label'] = $lang['MESA_PLU_PROJECTNOTES'];
                    }

                    else{
                        if(array_key_exists($revision['changed'], $cFieldsArr)){
                          $results[$arrInd]['label'] = $cFieldsArr[$revision['changed']];
                        } else{
                              $results[$arrInd]['label'] = $revision['changed'];
                        }
                    }
                }

                if($revision['scope'] == self::SCOPE_PROJECTINFO_AUTH){
                    if($revision['to'] == '1'){
                        //TODO: link to generated PDF's here
                        $linkRev = ALPC_BASEPATH . '/revisions/fetchProjectRevision/' . $projectId . '/' . $revision['result_id'];
                        $results[$arrInd]['label'] = $lang['MESA_REV_PROJECTAUTH'];
                        $results[$arrInd]['from'] = "<i class='icon-thumbs-down'></i>";
                        $results[$arrInd]['to'] = "<a href='#' onClick='popUp(\"" . $linkRev . "\", \"revision\", 100, 100)'><i class='icon-thumbs-up'></i></a>";
                    }
                    elseif($revision['to'] == '0'){
                        $results[$arrInd]['label'] = $lang['MESA_REV_PROJECTDEAUTH'];
                        $results[$arrInd]['from'] ="<i class='icon-thumbs-up'></i>";
                        $results[$arrInd]['to'] = "<i class='icon-thumbs-down'></i>";
                    }
                }

                if($revision['scope'] == self::SCOPE_PROJECTINFO_AUTH_PREV){

                    if($revision['to'] == '0'){
                        //$results[$arrInd]['label'] = $lang['MESA_REV_PROJECTDEAUTH'];
                        $results[$arrInd]['label'] = $lang['MESA_REV_PROJECTDEAUTH_PREV'];
                        $results[$arrInd]['from'] ="<i class='icon-thumbs-up'></i>";
                        $results[$arrInd]['to'] = "<i class='icon-thumbs-down'></i>";
                    }
                }

                if($revision['scope'] == self::SCOPE_PROJECT_SAMPLEADD){
                    $results[$arrInd]['label'] = $lang['MESA_REV_SAMPLEADD'];
                    $results[$arrInd]['from'] = $revision['changed'];
                    $results[$arrInd]['to'] = "<i class='icon-plus'></i>";
                }

                if($revision['scope'] == self::SCOPE_PROJECT_SAMPLEREM){
                    $results[$arrInd]['label'] = $lang['MESA_REV_SAMPLEREMOVED'];
                    $results[$arrInd]['from'] = $revision['changed'];
                    $results[$arrInd]['to'] = "<i class='icon-trash'></i>";
                }
            }
        }

        $tF = new tableFactory();
        $tF->loadTemplate('sampleRevisionTable');
        $tF->loadValues($results);
        $tF->specifyMod('user', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('time', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

        if($hard == True){
          return $this->hardRender();
        }

    }

    function fetchProjectRevision($pid, $rev){

        global $lang;
        $this->render = 0;
        $fileNameHash = md5($pid . $rev);
        $fileName = $fileNameHash . '.pdf';

        if(!file_exists(ROOT . '/app/private/reportStorage/' . $fileName)){
            print $lang['MESA_REV_PROJREVNOTFOUND'];
        } else {
            // We'll be outputting a PDF
             header('Content-type: application/pdf');

            // It will be called downloaded.pdf
            header('Content-Disposition: attachment; filename="project_' . $pid . '_rev_' . $rev .'.pdf"');

            // The PDF source is in original.pdf
            readfile(ROOT . '/app/private/reportStorage/' . $fileName);
        }

    }

    function getProjectContributors($pid){

        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECT_START);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_CHANGE);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_AUTH);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECT_SAMPLEADD);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECT_SAMPLEREM);

        $results = $this->Revision->search();
        $userArray = array();


        foreach($results as $revision){
            if(!array_key_exists($revision['user'], $userArray)){
                $userArray[$revision['user']] = upa('profiles', 'fetch', array($revision['user']));
            }
        }

        return $userArray;
    }


    function removeProjectRevisions($pid){

        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECT_START);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_CHANGE);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECTINFO_AUTH);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECT_SAMPLEADD);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $pid);
        $this->Revision->where('scope', self::SCOPE_PROJECT_SAMPLEREM);

        $results  = $this->Revision->search();

        foreach($results as $rev){
            $this->Revision->free();
            $this->Revision->id = $rev['id'];
            $this->Revision->remove();
        }
    }


    function removeSampleRevisions($sampleId){

        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_CHANGE);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_ASSAYADD);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_ASSAYREM);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_VETOREM);
        $this->Revision->insertOR();
        $this->Revision->where('scopeId', $sampleId);
        $this->Revision->where('scope', self::SCOPE_SAMPLEINFO_VETOSET);

        $results  = $this->Revision->search();

        foreach($results as $rev){
            $this->Revision->free();
            $this->Revision->id = $rev['id'];
            $this->Revision->remove();
        }

    }

    function removeSampleAssayRevisions($said){

        $this->Revision->where('scope', self::SCOPE_SAMPLERESULT_CHANGE);
        $this->Revision->where('sa_id', $said);
        $results  = $this->Revision->search();

        foreach($results as $rev){
            $this->Revision->free();
            $this->Revision->id = $rev['id'];
            $this->Revision->remove();
        }
    }

    function removeRevision($revId){
        $this->render = false;
        $this->Revision->id = $revId;
        $this->Revision->remove();
    }

}
