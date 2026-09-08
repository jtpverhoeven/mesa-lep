<?PHP

class changeTrackerController extends controller{

    /* Change types

        1: Result change
        2: Sample created
        3: Project created
        4: Sample innoc set / change
        5: sampleInfo change
        6: Add assay
        7: remove assay
        8: Set conf flag
        9: change project auth status
        10: Report has been exported  / retracted
        11: Sample removed
        12: Veto added / removed
        13: Assurance forms
        14: Conf results
        15: conf meta data
        16: confKey Store
        17: Project Info
        18: Meta toevoegen
        19: Meta verwijderd 
        20: Project note changed 
        21: Proect exported to portal        
        22: Project locked
        23: Project unlocked 
        24: Project client changed,

        25: File uploaded
        26: File deleted
        27: File visibility changes
        

    */

    function developer_change()
    {
        $this->render = False; 

        $user = getUserId();

        if($user == '1' || $user == '9' )
        {

                $field = $_POST['colname'];
                $this->ChangeTracker->id = $_POST['id'];
                $this->ChangeTracker->$field = $_POST['value']; 
                $this->ChangeTracker->save();
        }

    }    

      function changed($type, $project = False, $sample = False, $said = False, $event = False, $from = False, $to = False, $assuranceForm = false){
        
        $this->render = False;

        //attmept to grab project if false and sample true 
        if($project == False && $sample !== False){
          $sampleInfo = upa('samples', 'fetch', array($sample), False);
           $this->ChangeTracker->project = checkKeyOrFalse($sampleInfo, 'project');
        } 

        $this->ChangeTracker->user_id = getUserId();
        $this->ChangeTracker->timestamp = time();
        $this->ChangeTracker->type = $type;
        $this->ChangeTracker->project = $project;
        $this->ChangeTracker->sample = $sample;
        $this->ChangeTracker->said = $said;
        $this->ChangeTracker->event = $event;
        $this->ChangeTracker->from = $from;
        $this->ChangeTracker->to = $to;
        $this->ChangeTracker->assurance_form = $assuranceForm;
        $this->ChangeTracker->save();
      }

      function changesForProject($project, $hard = False){

        global $lang;
        $this->doNotRenderHeader = True;
        $this->ChangeTracker->where('project', $project);
        $results = $this->ChangeTracker->search();

        $tF = new tableFactory();
        $tF->loadTemplate('sampleRevisionTable');
        $tF->loadValues($results);
        $tF->specifyMod('user_id', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('timestamp', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

        if($hard == True){
          return $this->hardRender();
        }

      }

      function changesForSample($sampleId, $hard = False){
        global $lang;
        $this->doNotRenderHeader = True;
        $this->ChangeTracker->where('sample', $sampleId);
        $results = $this->ChangeTracker->search();

        $tF = new tableFactory();
        $tF->loadTemplate('sampleRevisionTable');
        $tF->loadValues($results);
        $tF->specifyMod('user_id', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('timestamp', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

        if($hard == True){
          return $this->hardRender();
        }
      }



      function changesForAnalysis($sampleId, $analysis, $hard = False){
        global $lang;
        $this->doNotRenderHeader = True;
        $this->ChangeTracker->where('sample', $sampleId);
        $this->ChangeTracker->where('said', $analysis);
        $results = $this->ChangeTracker->search();

        $tF = new tableFactory();
        $tF->loadTemplate('sampleRevisionTable');
        $tF->loadValues($results);
        $tF->specifyMod('user_id', 'userIdToName', array(ALPC_TF_SELF));
        $tF->specifyMod('timestamp', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
        $this->_template->set('revisions', $tF->renderTable());

        if($hard == True){
          return $this->hardRender();
        }
      }

      function changesForAssuranceForm($assuranceFormId, $hard = False){
        $this->doNotRenderHeader = True;
        $this->ChangeTracker->where('assurance_form', $assuranceFormId);
        $this->ChangeTracker->order('timestamp', 'DESC');
        $results = $this->ChangeTracker->search();

        if(empty($results)){
          $results = '{MESA_REV_NOREVS}';
        } else {

          $tF = new tableFactory();
          $tF->loadTemplate('revisionTableAssurance');
          $tF->loadValues($results);
          $tF->specifyMod('user_id', 'userIdToName', array(ALPC_TF_SELF));
          $tF->specifyMod('timestamp', 'date', array('d-m-Y H:i:s', ALPC_TF_SELF));
          $this->_template->set('revisions', $tF->renderTable());

        }

        if($hard == True){
          return $this->hardRender();
        }

      }




      function removeRevision($revId){
          $this->render = false;
          $this->ChangeTracker->id = $revId;
          $this->ChangeTracker->remove();
      }

}
