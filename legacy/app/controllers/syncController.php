<?PHP

class syncController extends controller {


    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function routineSync(){
        //this is just the landing page
        $this->renderAlternateHeader = 'slim';
        writeLog('Routine database sync initiated', ALPC_NOTICE);
    }

    function silentSync(){
        $this->render = False;
        $syncModule = new syncModule();
        $syncModule->silentSync();
    }


    function syncTool(){

    }

    function syncToolSync(){
        $this->doNotRenderHeader = True;
        $syncModule = new syncModule();
        $renderInfo = $syncModule->silentSync(True);
        $this->_template->setByArray($renderInfo);
    }

    function syncToolCommand(){

        $this->render = False;
        $syncModule = new syncModule();
        $syncModule->runSyncCommand($_POST['aa_id'], $_POST['mesa_id'],  $_POST['sync_type'], $_POST['sync_direction']);

    }


    function cloneToMesa(){
        //$this->renderAlternateHeader = 'slim';
        writeLog('Clone to mesa performed ', ALPC_NOTICE);
        $this->render = False;
        $syncModule = new syncModule();
        $syncModule->cloneToMesa();
    }

    function cloneToAA(){
        writeLog('Clone to AA performed ', ALPC_NOTICE);
        $this->render = False;
        $syncModule = new syncModule();
        $syncModule->cloneToAA();
    }



}
