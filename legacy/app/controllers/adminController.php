<?PHP

class adminController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }


    function backupDatabase(){

        $this->render = false;

        //run download  current_prod_for_download.sql 
        $shadowEnd = system('D:\Applications\shadowlims\cmd.exe /C D:\Applications\shadowlims\backup_production.bat', $shadowStatus);

        $stamp = time();
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="mesalims_backup_' . $stamp . '.sql"');

        $backup_file = 'D:\Applications\shadowlims\current_prod_for_download.sql';
        $log = file_get_contents($backup_file);
        print $log;
    }

    function pushDevToProd(){
        $this->render = False;
        $shadowEnd = system('D:\Applications\shadowlims\cmd.exe /C D:\Applications\shadowlims\push_dev_to_prod.bat', $shadowStatus);
        print '1';
    }


    function dashboard(){

    }

    function calculations(){

      $scripts = upa('assays', 'scriptsInUse', array(), false);
      $destination = ROOT . DS . 'app' . DS . 'private' . DS . 'templateScripts';
      $calcFiles = scandir($destination);
      $tableList = array();

      foreach($calcFiles as $calcInd => $calcFile){
        if($calcFile == '.' || $calcFile == '..'){
          unset($calcFiles[$calcInd]);
        }else{
          $tableList[$calcInd]['name'] = $calcFile;
          $tableList[$calcInd]['filename'] = urlencode($calcFile);

          //$listName = explode('.', $calcFile);
          $listName = str_replace('.php', '', $calcFile);
          $listName = str_replace('.class', '', $listName);


          $listContent = checkKeyOrFalse($scripts, $listName);

          if($listContent == False || !empty($listcontent)){
            $tableList[$calcInd]['in_use'] = 'Nee';
          }else{
            $tableList[$calcInd]['in_use'] = 'Ja';
          }
        }
      }

      $table = new tableFactory();
      $table->setTableId('calcTable');
      $table->loadTemplate('calcTable');

      if(empty($tableList)){
          $tableList = 'Geen berekenings scripts gevonden';
      }

      $table->loadValues($tableList);
      $this->_template->set('calc_table', $table->renderTable());
    }

    function checkWhereActive($fileName){
      $this->doNotRenderHeader = true;
      $scripts = upa('assays', 'scriptsInUse', array(), false);
      $listName = explode('.', $fileName);
      $listContent = checkKeyOrFalse($scripts, $listName[0]);

      $table = new tableFactory();
      $table->setTableId('calcTable');
      $table->loadTemplate('calcTableUse');

      if(empty($listContent)){
          $listContent = 'Niet actief';
      }

      $table->loadValues($listContent);

      $this->_template->set('calc_table', $table->renderTable());
    }


    function viewCalc($fileName){

      $fileName = urldecode($fileName);
      $destination = ROOT . DS . 'app' . DS . 'private' . DS . 'templateScripts';

      $baseDir = ROOT .  DS .  'app' . DS . 'private' . DS . 'templateScripts';
      $path = realpath($baseDir .  DS . $fileName);

      //$img = ROOT .  DS .  'app' . DS . 'private' . DS . 'worklistUpload' . DS . $fileName ;
      if (dirname($path) === $baseDir) {
        $this->_template->set('name', $fileName);
        $scriptContent = file_get_contents($path);
        $scriptContent = htmlentities($scriptContent);
        $this->_template->set('content', $scriptContent);

      }
    }

    function removeCalc($calcFile){
      $this->render = False;
      $baseDir = ROOT .  DS .  'app' . DS . 'private' . DS . 'templateScripts';
      $path = realpath($baseDir .  DS . $calcFile);

      if (dirname($path) === $baseDir) {
        mesaUnlink($path);
      }
    }

    function uploadCalc(){

      $this->render = False;
      if(!isset($_FILES['calcFile'])){
          $this->reRoute('admin/calculations', True);
          return;
      }

      $allowed =  array('php');
      $fileName = $_FILES['calcFile']['name'];
      $ext = pathinfo($fileName, PATHINFO_EXTENSION);

      if(!in_array(strtolower($ext),$allowed) ) {
        $this->reRoute('admin/calculations', True);
        return;


      } else{


        $baseDir = ROOT .  DS .  'app' . DS . 'private' . DS . 'templateScripts';
        $path = $baseDir .  DS . $fileName;

        if (move_uploaded_file($_FILES['calcFile']['tmp_name'], $path)){
          $this->reRoute('admin/calculations', True);
          return;
        }

      }

    }

    function debugger(){
      $this->renderAlternateHeader = 'debug';
      $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'debugger.txt';
      $contents = file_get_contents($logLocation);
      $this->_template->set('debug', $contents);

    }

    function refreshDebugger(){
      $this->render = false;
      $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'debugger.txt';
      $contents = file_get_contents($logLocation);
      print $contents;
    }

    function clearDebugger(){
      $this->render = false;
      $logLocation =  ROOT . DS . 'app' . DS . 'private' .DS . 'debugger.txt';
      file_put_contents($logLocation, '');
    }


    function confImport($go = 'no')
    {

      $this->render = False; 

      if($go !== 'go')
      {
        print('no go');
        die();
      }

      $import =  ROOT . DS . 'app' . DS . 'private' .DS . 'bevestig.csv';

      $handle = fopen($import, 'r');

      $header = False; 

      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

        if($header === False)
        {
          $header = True; 
          continue; 
        }


        $profile  = (int)$data[0];
        $analysis = (int)$data[1];
        $kveCheck = (int) str_replace(",", "", $data[2]);
        $conftrip = (int) str_replace(",", "", $data[3]);

        $ap = new AssayProfile; 

        $ap->where('research_profile', $profile);
        $ap->where('assay' , $analysis);

        $row = $ap->first();

        if(empty($row))
        {
          print('Not found:' . $profile . ' - '  . $analysis . ' <br />');
          continue; 
        }

       

        $references = json_decode($row['reference'], JSON_FORCE_OBJECT);


        $kveSet = (int)$references['ref_kve'];
        
        if($kveSet === $kveCheck)
        {
        
          $ap = new AssayProfile; 
          $ap->id = $row['id'];
          $ap->conf_trip = $conftrip;
          $ap->save();

          print('ok <br />');

        }

        else
        {
          print('did not pass reference check:' . $profile . ' - '  . $analysis . ', ref was set at ' . $kveSet . ' expected: ' . $kveCheck . ' <br />' );
        }


      }


    }


}
