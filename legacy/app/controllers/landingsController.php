<?PHP

class landingsController extends controller {

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }


    private function cloneToDev(){

        //this is now handled int he controller

        //        $dbHandle = new PDO('mysql:host=' . ALPC_DB_HOST . ';dbname=' . ALPC_DB_NAME . ';charset=utf8;', ALPC_DB_USER, ALPC_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
//        $dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//
//        $dbHandleDev = new PDO('mysql:host=' . ALPC_DB_HOST . ';dbname=' . ALPC_DB_NAME_DEV . ';charset=utf8;', ALPC_DB_USER, ALPC_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
//        $dbHandleDev->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//
//        $query = 'SHOW TABLES';
//
//        try {
//            $dbHandle->beginTransaction();
//            $sth = $dbHandle->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
//            $sth->execute(array());
//            $queryResult = $sth->fetchAll();
//            $sth->closeCursor();
//        } catch (PDOException $e) {
//            print $e->getMessage();
//            return False;
//        }
//
//        unset($sth);
//
//        try {
//
//        foreach($queryResult as $tableName){
//
//            if($onlySessions == True && $tableName[0] != 'users'){
//                continue;
//            }
//
//            $query = 'TRUNCATE TABLE ' . $tableName[0];
//            $sth = $dbHandleDev->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
//            $sth->execute(array());
//
//            $queryFetch = 'SELECT * FROM ' . $tableName[0];
//            $tableData = $dbHandle->prepare($queryFetch, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
//            $tableData->execute(array());
//            $tableResults = $tableData->fetchAll(PDO::FETCH_ASSOC);
//
//            if(!empty($tableResults)){
//
//                $dbHandleDev->beginTransaction();
//
//                $sqlInsert = '';
//                $sqlValues = '';
//
//                $tableParams = array();
//                foreach ($tableResults[0] as $fieldName => $fieldValue) {
//                    $sqlInsert .= '`' . $fieldName . '`,';
//                    $sqlValues .= ':' . $fieldName . ',';
//                    $tableParams[$fieldName] = NULL;
//                }
//
//                $sqlInsert = substr($sqlInsert, 0, -1);
//                $sqlValues = substr($sqlValues, 0, -1);
//                $sql = 'INSERT INTO `' . ALPC_DB_NAME_DEV . '`.`' . $tableName[0] . '` ( ' . $sqlInsert . ' ) VALUES ( ' . $sqlValues . ')';
//
//                $sth = $dbHandleDev->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
//
//                foreach($tableResults as $row){
//
//                    foreach($row as $fieldName => $fieldValue){
//                        $tableParams[$fieldName] = $fieldValue;
//                    }
//                    $sth->execute($tableParams);
//                }
//                $sth->closeCursor();
//                $dbHandleDev->commit();
//            }
//        }
//        } catch (PDOException $e) {
//            print $e;
//            return False;
//        }
        return True;
    }

    function loginLanding(){
        $this->doNotRenderHeader = 1;

        if(isset($_SESSION['DEVELOPMENT']) && $_SESSION['DEVELOPMENT'] == True){
            $this->_template->set('route', ALPC_BASEPATH . '/lims/dashboard');
        } else{

            if($_SESSION['SHOULD_RUN_SYNC'] == True){
                $_SESSION['SHOULD_RUN_SYNC'] = False;
                $this->_template->set('route', ALPC_BASEPATH . '/sync/routineSync');
            } else{

                $devInUse = upa('cvars', 'grabCvar', array('dev_active'), False);

                if($devInUse == '1'){
                  $this->_template->set('route', ALPC_BASEPATH . '/lims/devWarning');
                } else{
                  $this->_template->set('route', ALPC_BASEPATH . '/lims/dashboard');
                }


            }
        }
    }

    function pushDevToProduction(){

        $this->renderAlternateHeader = 'slim';



    }
}
