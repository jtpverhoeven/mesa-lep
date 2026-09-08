<?PHP

class mediaController extends controller{

    private $jsonLocation;     
    private $jsonTimeStamp; 

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');

        $this->jsonLocation = ROOT . DS . 'app' . DS . 'private' .DS . 'media.json';
    }


    public function store_media_prediction()
    {

      $mediaPrediction = $this->mediaOverview(True);

      $package = [
        'date' => time(), 
        'media' => $mediaPrediction
      ];
      
      
      file_put_contents($this->jsonLocation,json_encode($package));
                        
    }

    public function load_stored_media()
    {

     

      if(file_exists($this->jsonLocation))
      {
     
        $data = json_decode(file_get_contents($this->jsonLocation), JSON_FORCE_OBJECT);

        if($data)
        {

          $now = new DateTime();          
          $offset = new DateTime();

          $offset->setTimestamp($data['date']);

          $now->setTime(12, 0, 0);
          $offset->setTime(12, 0, 0);

          $this->jsonTimeStamp = $offset; 

          $diff = $offset->diff($now)->format('%a');

          if((int)$diff !== 0)
          {            
            return False; 
          }

          $offset->setTimestamp($data['date']);
          
          return array_column($data['media'], null, 'id');
          
        }

      }

      return False; 


    }

    public function mediaOverview($return = False)
    {
      
      if($return === True)
      {
        $this->render = False; 
      }
      
      else
      {
        $this->doNotRenderHeader = true;
      }

      $cutoff = upa('cvars', 'grabCvar', array('MEDIA_PREDICTION_CUTOFF'), False);

      $cutoffs = explode(':', $cutoff);

      $now = new DateTime('now');

      $cutoffDate = new DateTime('today');
      $cutoffDate->setTime($cutoffs[0], $cutoffs[1]);

      if ( $now  > $cutoffDate )
      {
        $this->_template->set('table', '<p> <h4> Media overzicht niet beschikbaar </h4> Het media overzicht
        is na ' . $cutoff . ' niet meer beschikbaar omdat de media berekening mogelijk niet meer correct is.');
      }


      else
      {

          $innocDate = date('d-M-Y', time());
        
          $media = $this->Media->mediaToday($innocDate); 

          $stored_media = $this->load_stored_media();

          array_walk($media, function(&$value, $key) use (&$media, $stored_media){
            $media[$key]['id'] = $key;

           

            $volume = $value['prediction_default_quant'] * $value['count'];

            $media[$key]['volume'] = $volume / 1000 ;  

            $needed500 = $volume / 500; 
            $needed200 =  $volume / 200; 
            
            $media[$key]['flask500'] = ceil($needed500); 
            $media[$key]['flask200'] = ceil($needed200);

            if($stored_media !== False && array_key_exists($key, $stored_media))
            {
              $media[$key]['stored_value']  = '<i>' .  $stored_media[$key]['volume'] . '</i> / ';
            } 
            
            else
            {
              $media[$key]['stored_value']  = '';
            }
            
       

          });


          if($stored_media !== false){

            foreach($stored_media as $mediaId => $values){

              $displayed_media = array_keys($media);

              if(!in_array($mediaId, $displayed_media))
              {

                $media[$mediaId]['count'] = 0;
                $media[$mediaId]['prediction_default_quant'] = false;
                $media[$mediaId]['prediction_qom'] = false;
                
                $media[$mediaId]['name'] = $values['name'];
                $media[$mediaId]['id'] = $mediaId;
                $media[$mediaId]['volume'] = '0'; 
                $media[$mediaId]['flask500'] = '0'; 
                $media[$mediaId]['flask200'] = '0';
                
                $media[$mediaId]['stored_value']  = '<i>' .  $stored_media[$mediaId]['volume'] . '</i> / ';
                
              }

            }

          }

          usort($media, function($a, $b) {
            return strcmp($a["name"], $b["name"]);
          });

          if($return === True)
          {
            return $media; 
          }


          $table = new tableFactory();
          $table->setTableId('mediaPrediction');
          $table->loadTemplate('mediaPrediction');

          if(empty($media)){
              $tableList = 'Geen berekenings scripts gevonden';
          }

          $table->loadValues($media);

          $this->_template->set('table', $table->renderTable());

          if($stored_media == False)
          {
            $this->_template->set('saved_time', '');
            $this->_template->set('timestamp_class', 'hide');

            
          }

          else
          {
            $this->_template->set('saved_time', date_format($this->jsonTimeStamp, 'Y-m-d H:i:s'));
            $this->_template->set('timestamp_class', '');
          }
          
          
          
      }

    
     
    }

    public function predictInit($val){
      $this->render = 0;

      $ids = explode(',', $val);
      $retObj = array();
      $i = 0;
      foreach($ids as $id){
        $this->Media->where('id', $id);
        $result = $this->Media->search();
        if(!empty($result)){
          $newMedia = array('id' => $id, 'text' => $result[0]['name']);
          //array_push($retObj, $newMedia);
          $retObj[$i] = $newMedia;
          $i++;
        }
        $this->Media->free();
      }
      print json_encode($retObj);
    }


    public function predict(){

      header('Content-type: text/html; charset=utf-8');
      $this->render = 0;
      $q = $_POST['term'];


      $this->Media->like('name', $q );
      $this->Media->where('active' , '1');
      $this->Media->insertOR();
      $this->Media->like('short_name', $q );
      $this->Media->where('active' , '1');

      $results = $this->Media->search();

      $assays = array();
      $iAssay = 0;

      if(!empty($results)){
          foreach($results as $thisAssay){
              $assays[$iAssay]['id'] = $thisAssay['id'];
              $assays[$iAssay]['text'] = $thisAssay['name'];
              $iAssay++;
          }
      }

      $ret['more'] = false;
      $ret['results'] = $assays;
      print json_encode($ret);
    }

    public function buildGUIWidget($assayId, $support = False){

        $this->doNotRenderHeader = True;
        /* pull up the setup media and confirmations */
        /* Confirmations can be count or boolean */

        /*  table with ID, Name, and Remove button */
        /* Dropdown with the available confirmation media */

        $support = filter_var($support, FILTER_VALIDATE_BOOLEAN);
        $thisAssay = upa('assays', 'fetch', array($assayId), False);
        $confScript = $thisAssay['confirmation_script'];
        $supportScript = $thisAssay['confirmation_support'];
        $confChainLinks = json_decode($confScript, JSON_FORCE_OBJECT);
        $supportChain = json_decode($supportScript, JSON_FORCE_OBJECT);
        $existingRows = '';


        if($support == False){

          $this->_template->set('widget_title', 'confirmation');
          $this->_template->set('selector_name', 'widgetSelector');
          $this->_template->set('table_title', 'Bevestigingen');

          if(is_array($confChainLinks)){
              $i = 0;
              foreach($confChainLinks as $confChainLink){
                  $existingRows .= $this->addWidgetLine($confChainLink['mediaId'], $i,  $confChainLink['disposition'], True);
                  $i++;
              }
          }
        }

        if($support == True){

          $this->_template->set('widget_title', 'confirmationSupport');
          $this->_template->set('table_title', 'Bevestiging ondersteunende media');
          $this->_template->set('selector_name', 'supportWidgetSelector');

          if(is_array($supportChain)){
              $i = 0;
              foreach($supportChain as $supportChainLink){
                  $existingRows .= $this->addWidgetLine($supportChainLink['mediaId'], $i,  False, True, True);
                  $i++;
              }
          }
        }


        if($support == True){
          $mediaOptions = $this->createMediaDropdown(True, False);
        } else{
          $mediaOptions = $this->createMediaDropdown(True, True);
        }


        $this->_template->set('available_confirmations', $mediaOptions);
        $this->_template->set('existing_rows', $existingRows);

    }


    public function addWidgetLine($id = False, $chainLength = 0, $setting = False, $return = False, $support = False){

        $this->Media->deepFreed();
        $this->Media->where('id', $id);
        $result = $this->Media->search();

        $support = filter_var($support, FILTER_VALIDATE_BOOLEAN);
        $return = filter_var($return, FILTER_VALIDATE_BOOLEAN);


        if(!empty($result)){

            $thisMedia = $result[0];
            $newChainLength = $chainLength + 1;
            $lineVar = '';

            if($support == True){

                $lineVar = '<tr id="support_' . $newChainLength .'">';
                $lineVar .= '<td mediaId="' . $thisMedia['id'] . '">' . $newChainLength   . '</td>';
                $lineVar .= '<td>' . $thisMedia['name'] . '</td>';
                $lineVar .= '<td>  </td>';
                $lineVar .= '<td> </td>';
                $lineVar .= '<td><a onClick="removeConfSupportRow(\'' . $newChainLength .'\');">Verwijderen</a></td>';
                $lineVar .= '</tr>';

            }

            else{

              if($thisMedia['type'] == '1'){ // +/- telling
                  $lineVar = '<tr id="' . $newChainLength .'">';

                  $selectorPlus = '';
                  $selectorNegative = '';
                  $selectorOptional = '';


                  if($setting == '+'){
                      $selectorPlus = 'selected="selected"';
                  }

                  if($setting == '-'){
                      $selectorNegative = 'selected="selected"';
                  }


                  if($setting == '?'){
                      $selectorOptional = 'selected="selected"';
                  }

                  $lineVar .= '<td mediaId="' . $thisMedia['id'] . '">' . $newChainLength   . '</td>';
                  $lineVar .= '<td>' . $thisMedia['name'] . '</td>';
                  $lineVar .= '<td> Positief/Negatief telling </td>';
                  $lineVar .= '<td> <select name="disposition_' . $newChainLength.'" class="dispositionDropper">
                                  <option '. $selectorPlus .' value="+">+ (Verwacht positief)</option>
                                  <option '. $selectorNegative .'value="-">- (Verwacht negatief)</option>
                                  <option '. $selectorOptional .'value="?">Onbekend</option>
                          </select></td>';
                  $lineVar .= '<td><a onClick="removeConfRow(\'' . $newChainLength .'\');">Verwijderen</a></td>';
                  $lineVar .= '</tr>';
              }

              if($thisMedia['type'] == '2'){ // ja/nee
                  $lineVar = '<tr id="' . $newChainLength .'">';

                  $selectorPlus = '';
                  $selectorNegative = '';
                  $selectorOptional = '';

                  if($setting == '+'){
                      $selectorPlus = 'selected="selected"';
                  }

                  if($setting == '-'){
                      $selectorNegative = 'selected="selected"';
                  }

                  if($setting == '?'){
                      $selectorOptional = 'selected="selected"';
                  }


                  $lineVar .= '<td mediaId="' . $thisMedia['id'] . '">' . $newChainLength   . '</td>';
                  $lineVar .= '<td>' . $thisMedia['name'] . '</td>';
                  $lineVar .= '<td> Positief/Negatief telling </td>';
                  $lineVar .= '<td> <select name="disposition_' . $newChainLength.'" class="dispositionDropper">
                                  <option '. $selectorPlus .' value="+">+ (Verwacht positief)</option>
                                  <option '. $selectorNegative .'value="-">- (Verwacht negatief)</option>
                                  <option '. $selectorOptional .'value="?">Onbekend</option>
                          </select></td>';
                  $lineVar .= '<td><a onClick="removeConfRow(\'' . $newChainLength .'\');">Verwijderen</a></td>';
                  $lineVar .= '</tr>';
              }

            }


            if($return == False){
                $this->render = False;
                print $lineVar;
            } else{
                return $lineVar;
            }
        } else{

        }
    }

    public function createMediaDropdown($html = True, $confOnly = True ){

        $this->Media->deepFreed();


        if($confOnly == True){
            $this->Media->where('active' , '1');
            $this->Media->where('confirmation_media' , '1');
        } else{
            $this->Media->where('confirmation_media' , '1');
            $this->Media->where('active' , '1');
            $this->Media->insertOR();
            //$this->Media->where('confirmation_media' , '0');
            $this->Media->where('type' , '3');
            $this->Media->where('active' , '1');
        }

        $mediaFound = $this->Media->search();

        if($html == False){
            $arr = array();
            foreach($mediaFound as $media){
                $arr[$media['id']] = $media['name'];
            }
            return $arr;
        } else{
            $dropper = '<option value="NULL">Beschikbare bevestigingen</option>';
            foreach($mediaFound as $media){
                $dropper .= '<option value="' . $media['id'] . '">' . $media['name'] . '</option>';
            }
            return $dropper;
        }
    }

    public function listing(){

        $table = new tableFactory();
        $table->setTableId('mediaTable');
        $table->loadTemplate('mediaTable');
        $this->Media->where('active', '1');
        $results = $this->Media->search();

        if(empty($results)){
            $results = 'Geen media gevonden';
        }

        $boolAr = array();
        $boolAr[0] = 'Nee';
        $boolAr[1] = 'Ja';

        $typeAr = array();
        $typeAr[1] = 'Telling';
        $typeAr[2] = 'Aanwezigheid';
        $typeAr[3] = 'Materiaal';

        $table->specifyMod('confirmation_media', 'flipIt', array( ALPC_TF_SELF, $boolAr ));
        $table->specifyMod('type', 'flipIt', array( ALPC_TF_SELF, $typeAr ));
        $table->specifyMod('hasDate', 'flipIt', array( ALPC_TF_SELF, $boolAr ));
        $table->specifyMod('supplements', 'neatSupplements', array( ALPC_TF_SELF));

        $table->loadValues($results);

        $sForm = new formFactory('media');

        $sForm->setId('addAssayForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/media/saveMedia');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', 'Naam', 'text', 'input-block-level', '', 'Naam van media', False, False);
        $sForm->addInputField('short_name', 'Korte naam', 'text', 'input-block-level', '', 'Korte naam', False, False);

        $sForm->addDropdownField('confirmation_media', 'Bevestigings media?', 'input-block-level', '', $boolAr, False);
        $sForm->addTextArea('supplements_txt', 'Supplementen in dit medium (1 per regel)', 'text', 'input-block-level', '', 'Supplementen', False);

        $sForm->addDropdownField('hasDate', 'Heeft datum op borgingsformulier?', 'input-block-level', '', $boolAr, False);

        $sForm->submitTrough('addMediaModalSubmit');
        $this->_template->set('addMediaForm', $sForm->render());

        $mForm = new formFactory('media');

        $mForm->setId('addMatForm');
        $mForm->addClass('');
        $mForm->action( ALPC_BASEPATH . '/media/saveMedia');
        $mForm->method('POST');
        $mForm->setTemplate('generic');

        $mForm->addInputField('name', 'Naam', 'text', 'input-block-level', '', 'Naam van materiaal', False, False);
        $mForm->addInputField('short_name', 'Korte naam', 'text', 'input-block-level', '', 'Korte naam', False, False);

        $mForm->addInputField('confirmation_media', False, 'hidden', 'hidden', 0, false, false,false);
        $mForm->addInputField('type', False, 'hidden', 'hidden', 3, false, false,false);

        $mForm->addDropdownField('hasDate', 'Heeft positie op borgingmFormulier?', 'input-block-level', '', $boolAr, False);
        $mForm->addInputField('acceptable_range', 'Acceptabele waarde', 'text', 'input-block-level', '', 'Bijv. >0<=2  of <2 of <=2 of >=1<=20 ', False, False);

        $mForm->submitTrough('addMatModalSubmit');
        $this->_template->set('addMatForm', $mForm->render());


        $this->_template->set('media_table', $table->renderTable());
    }

    public function saveMedia(){
        $this->render = False;

        if(isset($_POST['id'])){
            $this->Media->id = $_POST['id'];
        }


        $this->Media->name = $_POST['name'];
        $this->Media->short_name = $_POST['short_name'];
        $this->Media->confirmation_media = $_POST['confirmation_media'];
        $this->Media->hasDate = $_POST['hasDate'];

        if($_POST['type'] == '3'){
          $acceptableRange = isset($_POST['acceptable_range']) ? trim($_POST['acceptable_range']) : '';
          $this->Media->acceptable_range = ($acceptableRange === '') ? NULL : $acceptableRange;
        } else{
          $this->Media->acceptable_range = NULL;
        }

        $this->Media->used_for_prediction = (isset($_POST['used_for_prediction'])) ? 1 : 0;
        $this->Media->prediction_default_quant = $_POST['prediction_default_quant'];


        $supplements = array();

        if($_POST['type'] == '3'){
          $this->Media->supplements = trim($_POST['supplements_txt']);
        } else{
          $text = trim($_POST['supplements_txt']);
          $textAr = explode("\n", $text);
          $textAr = array_filter($textAr, 'trim');

          $suppI = 0;
          foreach ($textAr as $line) {
              $thisSupplement = array('supplementId' => $suppI, 'name' => trim($line));
              array_push($supplements, $thisSupplement);
              $suppI++;
          }

          $this->Media->supplements = json_encode($supplements);
        }

        $this->Media->type = $_POST['type'];
        $confControls = array();

        if($_POST['confirmation_media'] == '1'){

          if(isset($_POST['conf_enabled_pos'])){
            $confControls['pos'] = True;
          }

          if(isset($_POST['conf_enabled_neg'])){
            $confControls['neg'] = True;
          }

          if(isset($_POST['conf_enabled_blank'])){
            $confControls['blank'] = True;
          }
        }

        $this->Media->confirmation_controls = json_encode($confControls);
        $this->Media->save();
        $this->reRoute('media/listing', True);
    }

    public function remove($id ){
        $this->Media->id = $id;
        $this->Media->active = 0;
        $this->Media->save();
        $this->reRoute('media/listing', True);
    }

    public function fetchAll($setArrayKeys = False, $matOnly = False, $allInclusive = False){

        //$this->Media->where('hasDate', '1');

        if($matOnly == True){
          $this->Media->where('hasDate', '1');
          $this->Media->where('type', '3');
        } elseif($allInclusive == True){
          $this->Media->where('type', '1');
          $this->Media->where('hasDate', '1');
          $this->Media->insertOR();
          $this->Media->where('type', '2');
          $this->Media->where('hasDate', '1');
          $this->Media->insertOR();
          $this->Media->where('hasDate', '1');
          $this->Media->where('type', '3');
        } else{
          $this->Media->where('type', '1');
          $this->Media->where('hasDate', '1');
          $this->Media->insertOR();
          $this->Media->where('type', '2');
          $this->Media->where('hasDate', '1');
        }


        $results = $this->Media->search();
        if(empty($results)){
            return array();
        }

        else{
            $mediaList = array();
            foreach($results as $media){
                if($setArrayKeys == True){
                  $mediaList[$media['id']] = $media;
                }else{
                  array_push($mediaList, $media);
                }

            }
            return $mediaList;
        }
    }

    public function fetchAllNoParams(){
        $results = $this->Media->search();
        $mediaList = [];
        foreach($results as $media){            
            $mediaList[$media['id']] = $media;
        }

        return $mediaList;

    }


    public function edit($id){

        $this->Media->where('id', $id);
        $result = $this->Media->search();

        if(empty($result)){
            $this->reRoute('media/listing', True);
        }

        $boolAr = array();
        $boolAr[0] = 'Nee';
        $boolAr[1] = 'Ja';



        $thisMedia = $result[0];

        //set title
        if($thisMedia['type'] == '1' || $thisMedia['type'] == '2'  ){
            $this->_template->set('title_name', 'Media');
        }
        if($thisMedia['type'] == '3'){
            $this->_template->set('title_name', 'Materiaal');
        }


        $sForm = new formFactory('media');

        $sForm->setId('addAssayForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/media/saveMedia');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', 'Naam', 'text', 'input-block-level', $thisMedia['name'], 'Naam van media', False, False);
        $sForm->addInputField('id', 'id', 'hidden', 'input-block-level', $thisMedia['id'], '', False, False);
        $sForm->addInputField('short_name', 'Korte naam', 'text', 'input-block-level', $thisMedia['short_name'], 'Korte naam', False, False);

        if($thisMedia['type'] == '3'){

          $typeAr = array();
          //$typeAr[1] = 'Telling';
          //$typeAr[2] = 'Aanwezigheid';
          $typeAr[3] = 'Materiaal';

          $sForm->addDropdownField('type', '', 'hidden', $thisMedia['type'], $typeAr, False);
          $sForm->addDropdownField('confirmation_media', '', 'hidden', $thisMedia['confirmation_media'], $boolAr, False);
          $sForm->addDropdownField('hasDate', '', 'hidden', $thisMedia['hasDate'], $boolAr, False);
          $sForm->addInputField('acceptable_range', 'Acceptabele range', 'text', 'input-block-level', $thisMedia['acceptable_range'], 'Bijv. >0<=2', False, False);
        } else{

          $typeAr[1] = 'Telling';
          $typeAr[2] = 'Aanwezigheid';

          if($thisMedia['confirmation_media'] == 1){
              $sForm->addDropdownField('type', 'Type bevestiging/media', 'input-block-level', $thisMedia['type'], $typeAr, False);

              $confControls = json_decode($thisMedia['confirmation_controls'], JSON_FORCE_OBJECT);
              $posEnabled = checkKeyOrFalse($confControls, 'pos');
              $negEnabled = checkKeyOrFalse($confControls, 'neg');
              $blankEnabled = checkKeyOrFalse($confControls, 'blank');

              $posCheckboxClass = NULL;
              $negCheckboxClass = NULL;
              $blankCheckboxClass = NULL;

              if($posEnabled == True){  $posCheckboxClass = 'checked="checked"';   }
              if($negEnabled == True){  $negCheckboxClass = 'checked="checked"';   }
              if($blankEnabled == True){  $blankCheckboxClass = 'checked="checked"';  }
              $sForm->addInputField('conf_enabled_pos', 'Positieve controle wordt uitgevoerd?', 'checkbox', 'input-block-level', '1', '{MESA_AST_FIELDNAME}', $posCheckboxClass);
              $sForm->addInputField('conf_enabled_neg', 'Negatieve controle wordt uitgevoerd?', 'checkbox', 'input-block-level', '1', '{MESA_AST_FIELDNAME}', $negCheckboxClass);
              $sForm->addInputField('conf_enabled_blank', 'Blanko wordt uitgevoerd?', 'checkbox', 'input-block-level', '1', '{MESA_AST_FIELDNAME}', $blankCheckboxClass);


          } else{
              $sForm->addDropdownField('type', '', 'input-block-level hidden', $thisMedia['type'], $typeAr, False);
          }

          $sForm->addDropdownField('confirmation_media', 'Bevestigings media?', 'input-block-level', $thisMedia['confirmation_media'], $boolAr, False);
          $sForm->addDropdownField('hasDate', 'Heeft datum op borgingsformulier?', 'input-block-level', $thisMedia['hasDate'], $boolAr, False);
        }

        if($thisMedia['type'] == '3'){
            $sForm->addTextArea('supplements_txt', 'Eenheid', 'text', 'input-block-level', $thisMedia['supplements'], 'Eenheid', False);
        } else{
          $supplements = json_decode($thisMedia['supplements'], JSON_FORCE_OBJECT);
          $supplementsTxt = '';
          foreach($supplements as $supplement){
              // '&#10;';
              $supplementsTxt .= $supplement['name'] . '&#10;';
          }
          $sForm->addTextArea('supplements_txt', 'Supplementen in dit medium (1 per regel)', 'text', 'input-block-level', $supplementsTxt, 'Supplementen', False);
        }
        
        $prediction = '';

        if($thisMedia['used_for_prediction'] == True){  $prediction = 'checked="checked"';  }
        $sForm->addInputField('used_for_prediction', 'Meenemen in voorspelling media voorraad?', 'checkbox', 'input-block-level', '1', 'Used for prediction', $prediction);
     
        $sForm->addInputField('prediction_default_quant', 'Hoeveelheid media dat gebruikt word', 'text', 'input-block-level', $thisMedia['prediction_default_quant'], 'Korte naam', False, False);



        $sForm->submitTrough('saveMediaButton');
        $this->_template->set('edit_form', $sForm->render());
    }

    public function getMediaName($id, $short = False){
      $this->Media->where('id',$id );
      $results = $this->Media->search();

      if(!empty($results)){
        if($short == False){
          return $results[0]['name'];
        } else{
          return $results[0]['short_name'];
        }
      }
    }


    public function allMedia()
    {        
        $this->render = False; 
        $results = $this->Media->search();

        return $results; 
    }


}
