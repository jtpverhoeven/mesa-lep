<?PHP

class assaysController extends controller{


    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }


    function returnAllConfirmationAssays(){
      $this->Assay->where('confirmation', '1');
      $result = $this->Assay->search();
      return $result;
    }

    function fetchRevisionNumber($id){

      $this->Assay->where('id', $id);
      $this->Assay->limit(1);
      $result = $this->Assay->search();

      if(empty($result)){
          return false;
      } else{
          $oriId = $result[0]['original_id'];
          $this->Assay->free();
          //$this->Assay->limit(1);
          $this->Assay->where('original_id', $oriId);
          $this->Assay->order('id', 'ASC');
          $result = $this->Assay->search();
          $rev = 0;
          foreach($result as $revResult){
            $rev = $rev + 1;
            if($revResult['id'] == $id){
              return $rev;
            }
          }
      }
    }


    function fetchTip($id){

        $this->Assay->where('id', $id);
        $this->Assay->limit(1);
        $result = $this->Assay->search();

        if(empty($result)){
            return false;
        } else{
            $oriId = $result[0]['original_id'];
            $this->Assay->free();
            $this->Assay->limit(1);
            $this->Assay->where('original_id', $oriId);
            $this->Assay->order('id', 'DESC');
            $tipResult = $this->Assay->search();
            return $tipResult[0]['id'];
        }
    }

    function returnMetaInSet($set){

      $metas = $this->returnAllMetas();
      foreach($metas as $meta){
        if(in_array($meta['id'], $set)){
          return $meta['id'];
        }
      }
    }

    function returnAllMetas(){
      $this->Assay->where('type', '4');
      $results = $this->Assay->search();
      return $results;
    }

    function remove($id){
        //remove (hide) from assay profiles
        upa('assayProfiles', 'removeByAssay', array($id));
        $this->Assay->id = $id;
        $this->Assay->active = 0;
        $this->Assay->save();
    }

    function fetchSingle($id){
        $this->Assay->where('id', $id);
        $results = $this->Assay->search();

        if(empty($results)){
            return False;
        } else {
            return $results['0'];
        }
    }

    function fetchBulk($ids)
    {

      $implodedIds =  implode(",", $ids);
      
      $sql = "SELECT * FROM `assays` WHERE `id` IN ( " . $implodedIds . ")";

      $results = $this->Assay->customQuery($sql, []);

      $keyed = array_column($results, null, 'id');      

      return $keyed;

    }

    function getAssayList($all = false){
        $this->render = false;

        if($all !== True){
            $this->Assay->where('active', 1);
        }

        $results = $this->Assay->search();
        return $results;
    }




    function listing(){

        $table = new tableFactory();
        $table->setTableId('assaysTable');
        $table->loadTemplate('assaysTable');
        $this->Assay->where('active', 1);
        $results = $this->Assay->search();

        if(empty($results)){
            $results = 'No assays defined';
        }

        $options['1'] = '{MESA_ASE_ENUMERATION}';
        $options['2'] = '{MESA_ASE_DETECTIE}';
        $options['3'] = '{MESA_ASE_OTHER}';
        $options['4'] = '{MESA_ASE_META}';

        $assayTypeList = upa('assayTypes', 'analyticalListArray', array(), False);

        #$table->specifyMod('type_base', 'pa', array('assayTypes', 'getTypeName', array(ALPC_TF_SELF)));
        $table->specifyMod('type_base', 'flipIt', array(ALPC_TF_SELF, $assayTypeList) );
        $table->specifyMod('type', 'flipIt', array( ALPC_TF_SELF, $options ));

        $boolAr[0] = 'Nee';
        $boolAr[1] = 'Ja';

        $table->specifyMod('dillution', 'flipIt', array( ALPC_TF_SELF, $boolAr ));
        $table->specifyMod('replicates', 'flipIt', array( ALPC_TF_SELF, $boolAr ));
        $table->specifyMod('confirmation', 'flipIt', array( ALPC_TF_SELF, $boolAr ));

        $table->loadValues($results);
        $this->_template->set('assay_table', $table->renderTable());

        //grab custom fields
        $customFields = pa('assayFields', 'getAssayFields', array());
        $sForm = new formFactory('assays');

        $sForm->setId('addAssayForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/assays/saveAssay');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', '', '{MESA_ASE_ASSAYNAME}', False, False);
        $sForm->addValidation('name', 'NO_DUPLICATE');
        $baseOp = pa('assayTypes', 'analyticalListArray', array(), 0 );

        $sForm->addDropdownField('type_base', '{MESA_ASE_ASSAYBASE}', 'input-block-level', '', $baseOp, False);

        $sForm->addDropdownField('type', '{MESA_ASE_ASSAYTYPEOF}', 'input-block-level', '', $options, False);
        $sForm->addInputField('start_from', False, 'hidden', False, 'r', False, False);
        
        
        $sForm->submitTrough('addAssaySubmit');

        $this->_template->set('addAssayForm', $sForm->render());
    }


    function saveAssay($id = False){

        $this->render = 0;
        $forceChange = False;
        $newAssay = False;

        if($id != False){
            $this->Assay->id = $id;
            if(isset($_POST['forceChanges']) && $_POST['forceChanges'] == 1){
                $forceChange = True;
            }
        } else{
              $newAssay = True;
        }

        

        $this->Assay->postToModel();

        $cFields = array();

        if(isset($_POST['startAnchor']) && $_POST['startAnchor'] == 'r'){
            $this->Assay->start_from = 'r';
        }  elseif(isset($_POST['startAnchor']) && $_POST['startAnchor'] == 'i'){
            $this->Assay->start_from = 'i';
        } elseif(isset($_POST['startAnchor']) &&  $_POST['startAnchor'] == 'p'){
            $startPos = 'p:' . $_POST['startFieldName'];
                $this->Assay->start_from = $startPos;
        } elseif(isset($_POST['startAnchor']) && $_POST['startAnchor'] == 's'){
            $startPos = 's:' . $_POST['startFieldName'];
            $this->Assay->start_from = $startPos;
        }


        //check if we are allowed to save this in its current state (eval danger!)
        if(isset($this->Assay->script) && !empty($this->Assay->script)){
            $saveAble = upa('results', 'debugMs', array($this->Assay->script));
        } else{
            $saveAble = True;
        }
        if($saveAble == False){
            throwError('Invalid result script', 'Script contained offending variables, use debug to check');
            die();
        }


        $fields = pa('assayFields', 'getAssayFields', array());
        foreach($fields as $field){
            if(isset($_POST[$field['name']])){
                $cFields[$field['name']] = $_POST[$field['name']];
            }
        }

        $this->Assay->custom_fields = json_encode($cFields);
        $confList = array();

        $this->Assay->confirmation_script = $_POST['confirmation_script'];
        $this->Assay->confirmation_support = $_POST['confirmation_support'];



        $matrices = json_decode($_POST['matrix'], JSON_FORCE_OBJECT);
        upa('matrixContent', 'setMatrices', array($matrices, $_POST['original_id']), False);

        //save confirmation controls to array

        //convert used media t json
        $usedMedia = explode(",",  $_POST['media_id']);
        foreach($usedMedia as $idx => $usedMediaVar){
          if(!is_numeric($usedMediaVar)){
            unset($usedMedia[$idx]);
          }
        }

        $usedMedia = array_filter($usedMedia);
        $this->Assay->media_id = json_encode(array_values($usedMedia));


        //assay set, run save
        if($forceChange == True && $newAssay == False){
            //save as is now
            $this->Assay->save();
            $goTo = $this->Assay->id;
        }

        if($forceChange == False && $newAssay == False){
            //save as new assay
            $oldId = $this->Assay->id;
            $this->Assay->id = False;
            unset($this->Assay->id);

            $this->Assay->save();
            //fetch new id
            $revisedId = $this->Assay->lastInsertId;
            $goTo = $revisedId;

            //revise packets
            upa('assayProfiles', 'doAssayRevision', array($oldId, $revisedId));

            //revision docgen and portal associations
            upa('docgenAssociations', 'updateAssayAssocs', array($oldId, $revisedId));
            upa('portalAssayContent', 'updateAssayAssocs', array($oldId, $revisedId));

            //upa('assayProfiles', 'doAssayRevision', array($oldId, $revisedId));
            //set old assay as hidden
            $this->hideAssay($oldId);
        }

        //need to check if this triggers by accident?
        if($newAssay == True){
             $this->Assay->save();
             $goTo = $this->Assay->lastInsertId;
             $this->Assay->id = $goTo;
             $this->Assay->original_id = $goTo;
             $this->Assay->save();
        }

        $this->reRoute('assays/edit/' .   $goTo, True);
    }


    function hideAssay($id){
        $this->Assay->deepFreed();
        $this->Assay->id = $id;
        $this->Assay->active = 0;
        $this->Assay->save();
    }

    function fetchAssays(){
        $this->Assay->where('active', '1');
        $results = $this->Assay->search();
        return $results;
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

    function fetchByBase($typeBase){

        $this->Assay->where('type_base', $typeBase);
        $results = $this->Assay->search();

        if(!empty($results)){
            return $results;
        }

        return False;
    }

    function assayDropdown(){

      $this->render = 0;

        $list = array();
        foreach($this->fetchAssays() as $assay){
            $list[$assay['id']] = $assay['name'];
        }
        return $list;
    }

   function renderAssayRequest($assayId, $followNo){

       $this->doNotRenderHeader = 1;
       $this->Assay->where('id', $assayId);
       $info = $this->Assay->search();
       $keyArr['name'] = $info['0']['name'];
       $keyArr['follow_no'] = $followNo;
       $this->_template->set('render', generateHTML('sampleEntry/reqAssay', $keyArr) );
   }

    private function _fetchRevisionInfo($originalId, $thisId){

       $this->Assay->free();
       unset($this->Assay->id);
       $this->Assay->where('original_id', $originalId);
       $this->Assay->order('id', 'DESC');

       $revResults = $this->Assay->search();
       $dropDown = '';
       $numberOfRevisions = count($revResults);

       $isTip = False;
       if($thisId == $revResults[0]['id']){
           $isTip = True;
       }

       foreach($revResults as $revision){
           $selected = False;
           $tipIndicator = '';

           if($revision['id'] == $thisId){
               $selected = 'selected="SELECTED"';
               if($isTip == True){
                   $tipIndicator = '({MESA_ADR_TIP})';
               }
           }


           $dropDown .= '<option ' . $selected . ' value="' . $revision['id']   .'">{MESA_ADR_REVISION}: ' . $numberOfRevisions . ' ' . $tipIndicator . '</option>' ;
           $numberOfRevisions--;
       }

       return array('dropdown'=> $dropDown, 'isTip' => $isTip);

   }


   function edit($assayId){

        $this->Assay->where('id', $assayId);
        $results = $this->Assay->search();

        if(empty($results)){
            $this->reRoute('assays/listing', True);
        }

        $this->Assay->arrayToModel($results['0']);
        $revisionInfo = $this->_fetchRevisionInfo($this->Assay->original_id, $assayId);
        $revDropdown = $revisionInfo['dropdown'];
        $revIsTip = $revisionInfo['isTip'];

        $customFields = pa('assayFields', 'getAssayFields', array());
        $customFieldsVal = json_decode( $this->Assay->custom_fields, True);

        $sForm = new formFactory('assays');

        $sForm->setId('editAssayForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/assays/saveAssay/' . $assayId);
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', '{MESA_ASE_ASSAYNAME}', 'text', 'input-block-level', $this->Assay->name, '{MESA_ASE_ASSAYNAME}', False, False);

        $baseOp = pa('assayTypes', 'analyticalListArray', array(), 0 );
        $sForm->addDropdownField('type_base', '{MESA_ASE_ASSAYBASE}', 'input-block-level', $this->Assay->type_base, $baseOp, False);

        //      $mediaList = upa('media', 'createMediaDropdown', array(False, False), False);
        //      $mediaList[''] = 'Geen media geselecteerd';
        //       $sForm->addDropdownField('media_id', 'Gebruikt media', 'input-block-level', $this->Assay->media_id, $mediaList, False);


        $mediaList = json_decode($this->Assay->media_id);
        $mediaListIds = '';
        if(is_array($mediaList)){
          foreach($mediaList as $mediaId){
            $mediaListIds = $mediaListIds . $mediaId . ',';
          }
        } else{
          //set to just the thing
          $mediaListIds = $this->Assay->media_id;
        }

        $sForm->addInputField('media_id', 'Gebruikt media/materiaal', 'text', 'select2-input select2-default input-block-level', $mediaListIds, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));

        $options['1'] = '{MESA_ASE_ENUMERATION}';
        $options['2'] = '{MESA_ASE_DETECTIE}';
        $options['3'] = '{MESA_ASE_OTHER}';
        $options['4'] = '{MESA_ASE_META}';

        $sForm->addDropdownField('type', '{MESA_ASE_ASSAYTYPEOF}', 'input-block-level', $this->Assay->type, $options, False);

        $multiSelect = generateHTML('metaMultiSelect', array('current_value' => $this->Assay->meta_assays));
        $sForm->addPureHTML('meta_assays', $multiSelect);

        $boolOp['0'] = '{MESA_ASE_NO}';
        $boolOp['1'] = '{MESA_ASE_YES}';

        $sForm->addDropdownField('hide_report', '{MESA_ASE_HIDEREPORT}', 'input-block-level', $this->Assay->hide_report, $boolOp, False);
        $sForm->addInputField('min_count', '{MESA_ASE_COUNTMIN}', 'text', 'input-block-level', $this->Assay->min_count, '{MESA_ASE_COUNTMIN}', False, False);
        $sForm->addInputField('max_count', '{MESA_ASE_COUNTMAX}', 'text', 'input-block-level', $this->Assay->max_count, '{MESA_ASE_COUNTMIN}', False, False);


        $sForm->addDropdownField('dillution', '{MESA_ASE_USES} {MESA_ASE_USESDILUTION}', 'input-block-level', $this->Assay->dillution, $boolOp, False);
        $sForm->addDropdownField('replicates', '{MESA_ASE_USES} {MESA_ASE_USESREPLICATES}', 'input-block-level', $this->Assay->replicates, $boolOp, False);
        $sForm->addDropdownField('confirmation', '{MESA_ASE_USES} {MESA_ASE_USESCONFIRMATION}', 'input-block-level', $this->Assay->confirmation, $boolOp, False);


        $confInitStrategy = array();
        $confInitStrategy[0] = 'Ja';
        $confInitStrategy[1] = 'Nee, standaard aan';
        $confInitStrategy[2] = 'Nee, standaard uit';

        $sForm->addDropdownField('confirmation_init', 'Indien bevestigen actief, vragen om bevestigen?', 'input-block-level', $this->Assay->confirmation_init, $confInitStrategy, False);

        $confType['0'] = '{MESA_ASE_CONFONEFORALL}';
        $confType['1'] = '{MESA_ASE_CONFONEPERPLATE}';
        $sForm->addDropdownField('confirmation_type', '{MESA_ASE_TYPECONFIRMATION}', 'input-block-level', $this->Assay->confirmation_type, $confType, False);

        $sForm->addInputField('confirmation_depth', 'Standaard aantal bevestigingen', 'text', 'input-block-level', $this->Assay->confirmation_depth, 'Standaard aantal bevestigingen', False, False);

        $sForm->addInputField('duration', '{MESA_ASE_ASSAYDURATION}', 'text', 'input-block-level', $this->Assay->duration, '{MESA_ASE_ASSAYDURATION}', False, False);
        $sForm->addValidation('duration', 'ONLY_NUM');

        $startOp['i'] = 'Tijd van inzet-scan';
        $startOp['r'] = '{MESA_ASE_STARTFROMREG}';
        $startOp['p'] = '{MESA_ASE_STARTFROMPROJECTFIELD}';
        $startOp['s'] = '{MESA_ASE_STARTFROMSAMPLEFIELD}';

        //get start anchor bits
        if($this->Assay->start_from == 'r'){
            $startAnchor = 'r';
            $anchorField = false;
            $dropOptions = array();
        } elseif($this->Assay->start_from == 'i'){
            $startAnchor = 'i';
            $anchorField = false;
            $dropOptions = array();
        }else{
            $anchoring = explode(':', $this->Assay->start_from);
            $startAnchor = $anchoring[0];
            $anchorField = $anchoring[1];
        }

        $sForm->addDropdownField('startAnchor', '{MESA_ASE_ASSAYSTARTFROM}', 'input-block-level', $startAnchor , $startOp, False);

        if($startAnchor == 'p'){
            $dropOptions = upa('projectFields', 'dateDropDownMenu', array(True), False);
        } elseif($startAnchor == 's'){
            $dropOptions = upa('sampleFields', 'dateDropDownMenu', array(True), False);
        }


        $sForm->addDropdownField('startFieldName', '{MESA_ASE_STARTSELECTFIELD}', 'input-block-level', $anchorField,  $dropOptions, False);


        if(!empty($customFields)){
            foreach($customFields as $field){
                //$field['standard_value']
                if(array_key_exists($field['name'], $customFieldsVal)){
                    $custoValue = $customFieldsVal[$field['name']];
                } else {
                    $custoValue = $field['standard_value'];
                }

               $sForm->addInputField($field['name'], $field['name'], 'text', 'input-block-level', $custoValue , 'Enter name', False, False);
            }
        }


       $optionsTable = upa('confirmationTables', 'dropdown', array(), False);

        if($this->Assay->show_conf_table == ''){
            $this->Assay->show_conf_table = 'NULL';
        }

       //$sForm->addDropdownField('startAnchor', '{MESA_ASE_ASSAYSTARTFROM}', 'input-block-level', $startAnchor , $startOp, False);
       $sForm->addDropdownField('show_conf_table', 'Toon bevestigings tabel', 'input-block-level', $this->Assay->show_conf_table,  $optionsTable, False);
       $sForm->addTextArea('confirmation_script', '', 'text', 'input-block-level hidden', '', '{MESA_ASE_CONFSCRIPTTIP}', False);
       $sForm->addTextArea('confirmation_support', '', 'text', 'input-block-level hidden', '', '{MESA_ASE_CONFSCRIPTTIP}', False);
       $sForm->addTextArea('matrix', '', 'text', 'input-block-level hidden', '', '', False);


       $sForm->addTextArea('script', '{MESA_ASE_MESASCRIPT}', 'text', 'input-block-level', $this->Assay->script, '', False);

       $sForm->addDropdownField('uses_indicator', 'Weergave van indicator bolletjes (rood/groen) op rapportage', 'input-block-level', $this->Assay->uses_indicator,  ['0' => 'Nee', '1' => 'Ja'], False);
       $sForm->addDropdownField('uses_trip_indicator', 'Weergave van rode indicator op rapportage', 'input-block-level', $this->Assay->uses_trip_indicator,  ['0' => 'Nee', '1' => 'Ja'], False);
      
       $sForm->addInputField('article_code', 'Artikel code', 'text', 'input-block-level', $this->Assay->article_code, 'Artikel code', False, False);

       $sForm->addDropdownField('billable', 'Weergave op facturatie rapport', 'input-block-level', $this->Assay->billable,  ['0' => 'Nee', '1' => 'Ja'], False);

       $sForm->addInputField('forceChanges', False, 'hidden', 'hide', '0', False);

       if($revIsTip == True){
            $this->_template->set('revision_istip', '1');
        } else{
            $this->_template->set('revision_istip', '0');
          }

        $sForm->addInputField('original_id', False, 'hidden', 'hide', $this->Assay->original_id, False);
        $sForm->submitTrough('reviseAssayChangesButton');

        $this->_template->set('edit_form', $sForm->render());
        $this->_template->set('id', $assayId);
        $this->_template->set('original_id', $this->Assay->original_id);
        $this->_template->set('revision_dropdown', $revDropdown);

        if($this->Assay->type == 4){
            $this->_template->set('assay_is_meta', 'true');
        } else{
            $this->_template->set('assay_is_meta', 'false');
        }
   }

    function searchOriginalIds($oriId){
        $this->Assay->where('original_id', $oriId);
        $results = $this->Assay->search();
        return $results;
    }

    function fetchOriginalId($currId){
        $this->Assay->where('id', $currId);
        $result = $this->Assay->search();

        if(!empty($result)){
            $ori = $result[0]['original_id'];
            if(empty($ori)){
                return $result[0]['id'];
            } else{
                return $ori;
            }
        }
    }

   function searchAssays($searchTerm){

       $this->Assay->like('name', $searchTerm);
       $this->Assay->where('active', 1);
       $results = $this->Assay->search();

       return $results;
   }

   function saveOnlyScript($id, $script, $confirmation){

        $this->render = False;
        $this->Assay->id = $id;
        $this->Assay->script = $script;

        $confList = array();
        $text = trim($confirmation);
        $textAr = explode("\n", $text);
        $textAr = array_filter($textAr, 'trim');
        foreach ($textAr as $line) {
            $dInst = explode('=', $line);
                if(isset($dInst['0']) && isset($dInst['1'])){
                    $confList[$dInst['0']] = trim($dInst['1']);
            }
        }

        $this->Assay->confirmation_script = json_encode($confList, JSON_FORCE_OBJECT);
        $this->Assay->save();
   }

   function predict($self = False, $old = False, $activeOnly = True){

        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;
        $q = $_POST['term'];

        $this->Assay->like('name', $q );
        $this->Assay->like('active', 1);
        //$this->Assay->notLike('type', 4);

        $self =  filter_var($self, FILTER_VALIDATE_BOOLEAN);
        $activeOnly =  filter_var($activeOnly, FILTER_VALIDATE_BOOLEAN);
        

        if($self != False){
            $this->Assay->notLike('id', $self);
        }


        $results = $this->Assay->search();

        $assays = array();
        $iAssay = 0;

        if(!empty($results)){
            foreach($results as $thisAssay){
                if($old === 'yes'){
                  $assays[$iAssay]['id'] = $thisAssay['original_id'];
                } else{
                  $assays[$iAssay]['id'] = $thisAssay['id'];
                }

                $assays[$iAssay]['text'] = $thisAssay['name'];
                $iAssay++;
            }
        }

        $ret['more'] = false;
        $ret['results'] = $assays;
        print json_encode($ret);
   }

   function predictInit($ids){

        header('Content-type: text/html; charset=utf-8');
        $this->render = 0;

        $requested = explode(',', $ids);

        //parray($requested);

        $i = 0;
        foreach($requested as $request){

            $this->Assay->where('id', $request);
            $result = $this->Assay->search();
            if(!empty($result)){
                $found[$i]['id'] = $result['0']['id'];
                $found[$i]['text'] = $result['0']['name'];
            }
            $i++;
            $this->Assay->free();
        }


        $ret = array();
        $ret = $found;
        echo json_encode($ret);
   }

    function listAllMedia($customColName = 'media'){
        $this->render = False;
        $assays = $this->Assay->search();

        if(empty($assays)){
            return array();
        }

        $media = array();
        foreach($assays as $assay){
            $customFields = json_decode($assay['custom_fields'], JSON_FORCE_OBJECT);
            if(array_key_exists($customColName, $customFields)){
                if(!in_array($customFields[$customColName], $media) && !empty($customFields[$customColName])){
                    array_push($media, $customFields[$customColName]);
                }
            }
        }
        return $media;
    }

    function assayConfirmationInitStrategy($id){
        $this->Assay->id = $id;
        $result = $this->Assay->search();

        if(!empty($result)){
          return $result[0]['confirmation_init'];
        }
    }

    function fetchMediaAssuranceFormStreamlined($assayList){

      if(empty($assayList)){
        return False;
      }

      $assayBases = array_column($assayList, 'assay_base');
      $assayBases = array_unique($assayBases);
      if(empty($assayBases)){  $assayBases = array();   }
      $assayBaseMap = implode(',', array_map('intval', $assayBases));
      $mediaToGrab = array();

      $sqlAssays = 'SELECT id,duration,media_id,confirmation_script,confirmation_support FROM assays WHERE id IN (' . $assayBaseMap . ') ';
      $assayInfo = $this->Assay->customQuery($sqlAssays, array());
      $unpackedAssayInfo = array();
      $confLinesToFetch = array();

      $confRequested = array_filter($assayList, function ($item) {
        if($item['conf_requested'] === "1"){
          return true;
        }
        return false;
      });

      $grabConfLines = array_column($confRequested, 'id');
      $grabConfLines = array_unique($grabConfLines);
      if(empty($grabConfLines)){
        $grabConfLines = array();
        $confInfo = array();
      } else{
        $grabConfLines = implode(',', array_map('intval', $grabConfLines));
        $sqlConf = 'SELECT * FROM confirmations WHERE said IN (' . $grabConfLines . ') ';
        $confInfo = $this->Assay->customQuery($sqlConf, array());
      }

      $confInfoUnpacked = array();

      foreach($confInfo as $idx => $thisConfInfo){
        $inuse = json_decode($thisConfInfo['in_use'], JSON_FORCE_OBJECT);
        $inUseUnpacked = array();


        foreach($inuse as $df => $dfRep){
          foreach($dfRep as $repId => $mediaItems){
            foreach($mediaItems as $thisMediaId => $thisMediaActive){
              if($thisMediaActive == True){
                array_push($inUseUnpacked, $thisMediaId);
                //array_push($confPerformed[$assay['assay_base']], $thisMediaId);
              }
            }
          }
        }

        $confInfoUnpacked[$thisConfInfo['said']]['in_use'] = $inuse;
        $confInfoUnpacked[$thisConfInfo['said']]['in_use_unpacked'] = $inUseUnpacked;

      }

      foreach($assayInfo as $idx => $thisAssay){
        $unpackedAssayInfo[$thisAssay['id']]['confirmation_script'] = json_decode($thisAssay['confirmation_script'], JSON_FORCE_OBJECT);
        $unpackedAssayInfo[$thisAssay['id']]['confirmation_support'] = json_decode($thisAssay['confirmation_support'], JSON_FORCE_OBJECT);
        $unpackedAssayInfo[$thisAssay['id']]['media_id'] = json_decode($thisAssay['media_id'], JSON_FORCE_OBJECT);
      }

      foreach($assayList as $sa){

          if(!array_key_exists($sa['sample'], $mediaToGrab)){
            $mediaToGrab[$sa['sample']] = array();
          }

          //add normal media
          $mediaForThisAssay = checkKeyOrEmpty($unpackedAssayInfo, $sa['assay_base'], 'media_id');
          if(is_array($mediaForThisAssay)){
            foreach($mediaForThisAssay as $thisAssayMediaId){
                array_push($mediaToGrab[$sa['sample']], $thisAssayMediaId);
            }
          } else{
                array_push($mediaToGrab[$sa['sample']], $mediaForThisAssay);
          }

          //was confirmation requested, if so, add conf media
          if($sa['conf_requested'] === "1"){
            $confMediaInUse = checkKeyOrEmpty($confInfoUnpacked, $sa['id'], 'in_use_unpacked');
            $confScript = checkKeyOrEmpty($unpackedAssayInfo, $sa['assay_base'], 'confirmation_script');
            $supportScript = checkKeyOrEmpty($unpackedAssayInfo, $sa['assay_base'], 'confirmation_support');

            foreach($supportScript as $supportLine){
              if(in_array($supportLine['mediaId'], $confMediaInUse)){
                    array_push($mediaToGrab[$sa['sample']], $supportLine['mediaId']);
              }
            }

            foreach($confScript as $conLine){
              if(in_array($conLine['mediaId'], $confMediaInUse)){
                  array_push($mediaToGrab[$sa['sample']], $conLine['mediaId']);
                }
            }
          }
      }

      $returnArr = array();

      foreach($mediaToGrab as $sample => $media){
        $returnArr['mediaList'][$sample] = array_unique($media);
      }

      foreach($assayInfo as $assay){
        $returnArr['assayList'][$assay['id']] = $assay;
      }

      return $returnArr;

    }

    function fetchMedia($assayList){
        //assaylist can be single var
        //list of vars, or list of assays

        //single var mode
        $grabConfFor = array();
        $confPerformed = array();
        $confInUseMedia = array();

        if(!is_array($assayList)){
           $this->Assay->where('id', $assayList);
           $result = $this->Assay->search();
           if(!empty($result)){
             $mediaList = array($result['0']['assay_base']);
           }
        }

        else{
          //if list of assay objects, convert to list of media id's
          if(empty($assayList)){
            return array();
          }

          if(array_key_exists('0', $assayList) && array_key_exists('id',$assayList[0])){
            $newList = array();

            foreach($assayList as $assay){
              //check normal base media
              if(!in_array($assay['assay_base'], $newList)){
                array_push($newList,$assay['assay_base']);
              }

              //add confirmation media
              if($assay['conf_requested'] == 1){
                  //flag that we might have to include confirmation media
                  array_push($grabConfFor, $assay['assay_base']);
                  $confPerformed[$assay['assay_base']] = array();



                  //check which ones are performed
                  $confData = upa('confirmations', 'fetchLine', array($assay['id']), False);
                  //$supportDataArr = json_decode($assay['confirmation_support'], JSON_FORCE_OBJECT);
                  $confDataArr = json_decode($confData['data'], JSON_FORCE_OBJECT);
                  $inUse = json_decode($confData['in_use'], JSON_FORCE_OBJECT);


                  if(!is_array($inUse)){
                    $inUse = array();
                  }

                  foreach($inUse as $df => $dfRep){
                    foreach($dfRep as $repId => $mediaItems){
                      foreach($mediaItems as $thisMediaId => $thisMediaActive){
                        if($thisMediaActive == True){
                          array_push($confInUseMedia, $thisMediaId);
                          array_push($confPerformed[$assay['assay_base']], $thisMediaId);
                        }
                      }
                    }
                  }


                  // foreach($inUse as $thisMediaId => $thisMediaActive){
                  //   if($thisMediaActive == True){
                  //     array_push($confInUseMedia, $thisMediaId);
                  //   }
                  // }


                  // foreach($confDataArr as $confChain){
                  //   foreach($confChain as $confChainKey => $confChainValue){
                  //       $confExp = explode('_', $confChainKey);
                  //       if(in_array($confExp[0], $inUse) && $inUse[$confExp[0]] == True){
                  //           array_push($confPerformed[$assay['assay_base']], $confExp[0]);
                  //       }
                  //   }
                  // }


              }



            }
            $assayList = $newList;
          }

          foreach($assayList as  $assayId){
              $this->Assay->where('id', $assayId);
              $this->Assay->insertOR();
          }

          $results = $this->Assay->search();
          $mediaList = array();

          foreach($results as $result){

            $jsonArray = json_decode($result['media_id']);

            if(is_array($jsonArray)){
              $mediaResult = $jsonArray;
            } else{
              $mediaResult = array($result['media_id']);
            }


            foreach($mediaResult as $mediaResultId){
              if(!in_array($mediaResultId, $mediaList) && $mediaResultId != NULL){
                array_push($mediaList, $mediaResultId);
              }
            }

            // if(!in_array($result['media_id'], $mediaList) && $result['media_id'] != NULL){
            //   array_push($mediaList, $result['media_id']);
            // }
            //


            if(in_array($result['id'], $grabConfFor)){

              $confScript = json_decode($result['confirmation_script'], JSON_FORCE_OBJECT);
              $confSupportScript = json_decode($result['confirmation_support'], JSON_FORCE_OBJECT);

              if(!is_array($confSupportScript)){
                $confSupportScript = array();
              }

              if(!is_array($confScript)){
                $confScript = array();
              }


              foreach($confSupportScript as $supportLine){

                if(!in_array($supportLine['mediaId'], $mediaList)){
                    //confirmation was started fo this sample, but was the confirmation media "ticked"?
                    if(in_array($supportLine['mediaId'], $confInUseMedia)){
                      array_push($mediaList, $supportLine['mediaId']);
                    }
                }
              }

              foreach($confScript as $conLine){

                if(!in_array($conLine['mediaId'], $mediaList)){
                  //it wasnt there yet.. lets check if it has started for this array though
                  if(in_array($conLine['mediaId'], $confPerformed[$result['id']])){
                    //confirmation was started fo this sample, but was the confirmation media "ticked"?
                    array_push($mediaList, $conLine['mediaId']);
                  }
                }
              }
            }
          }
      }

      return $mediaList;
    }

    public function assayMedia($id){

      $this->Assay->id = $id;
      $result = $this->Assay->search();
      $returnArr = array();

      if(!empty($result)){

        $mediaListed = json_decode($result[0]['media_id'], JSON_FORCE_OBJECT);
        if(is_array($mediaListed)){
          foreach($mediaListed as $media){
              if(!empty($media)){
                $thisMedia = upa('media', 'fetch', array($media), False);
                if(!empty($thisMedia)){
                  if($thisMedia['type'] == 1){
                      $returnArr[$thisMedia['id']] = $thisMedia;
                    }
                }
              }
          }
        } elseif(!empty($result[0]['media_id'])){
            $thisMedia = upa('media', 'fetch', array($result[0]['media_id']), False);
            if(!empty($thisMedia)){
              if($thisMedia['type'] == 1){
                  $returnArr[$thisMedia['id']] = $thisMedia;
                }
            }
        }
      }

      return $returnArr;
    }

    public function assayMaterial($id){
      $this->Assay->id = $id;
      $result = $this->Assay->search();
      $returnArr = array();

      if(!empty($result)){

        $mediaListed = json_decode($result[0]['media_id'], JSON_FORCE_OBJECT);
        if(is_array($mediaListed)){
          foreach($mediaListed as $media){
              if(!empty($media)){
                $thisMedia = upa('media', 'fetch', array($media), False);
                if(!empty($thisMedia)){
                  if($thisMedia['type'] == 3){
                      $returnArr[$thisMedia['id']] = $thisMedia;
                    }
                }
              }
          }
        } elseif(!empty($result[0]['media_id'])){
            $thisMedia = upa('media', 'fetch', array($result[0]['media_id']), False);
            if(!empty($thisMedia)){
              if($thisMedia['type'] == 3){
                  $returnArr[$thisMedia['id']] = $thisMedia;
                }
            }
        }
      }

      return $returnArr;
    }

    function scriptsInUse(){

      $assays = $this->Assay->search();
      $scriptInUse = array();

      foreach($assays as $assay){

        preg_match("/file:(.*)$/i", $assay['script'], $fileMatch);
        $msScriptContents = preg_replace("/file:(.*)$/i", "", $assay['script']);

        if(!empty($fileMatch)){
          // $msScriptFile = ROOT . '/app/private/templateScripts/'  . $fileMatch[1] . '.php';
          $msClassFile =  ROOT . '/app/private/templateScripts/'  . $fileMatch[1] . '.class.php';
          // print $msClassFile;
          if(file_exists($msClassFile)){

            if(!array_key_exists($fileMatch[1], $scriptInUse)){
              $scriptInUse[$fileMatch[1]] = array();
            }

            $detected = array();
            $detected['id'] = $assay['id'];
            $detected['name'] = $assay['name'];
            $detected['active'] = $assay['active'];
            array_push($scriptInUse[$fileMatch[1]], $detected);
          }
        }
      }


      return $scriptInUse;
    }

    function returnAll(){
      $all = $this->Assay->search();
      $idListed = array();

      foreach($all as $idx => $assay){
        $idListed[$assay['id']] = $assay;
      }

      return $idListed;
    }


    function assayListAll(){
      $this->render = 0;
      $this->Assay->where('active', '1');
      $this->Assay->order('name', 'ASC');
      $results = $this->Assay->search();
      $list = $this->printAssayList($results);
      return $list;
    }

    function assayListMatrix($matrix){
      //fetch list of assay BASES!
      $bases = upa('matrixContent', 'listAssays', array($matrix), false);

      if(empty($bases)){
        return;
      }


      $this->Assay->order('name', 'ASC');

      foreach($bases as $assayBase ){
        $this->Assay->where('active', '1');
        $this->Assay->where('original_id', $assayBase);
        $this->Assay->insertOR();
      }

      $results = $this->Assay->search();
      $list = $this->printAssayList($results);
      return $list;
    }

    function printAssayList($assays){
      $list = '';
      foreach($assays as $assay){

          if($assay['type'] != 4){
              $assay['icon'] = 'hide';
          } else{
              $assay['icon'] = '';
          }

          //TODO: customize this in a wrapper so users can set this themselvs dynamically, this is pretty prone to failure. :(
          //search for Q flag
          $qFlag = peekIntoJSON($assay['custom_fields'], 'accred');
          $assay['qFlag']  = $qFlag;
          $assay['color']= 'white';

          if(strtolower($qFlag) == 'q'){
            $assay['color']= '#D4EFDF';
          }

          $list .= generateHTML('sampleEntry/assayLine', $assay);
        }

        return $list;
    }




}
