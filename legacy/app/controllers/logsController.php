<?PHP

class logsController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function listing(){

        $logArr = array();
        //$i = 0;
        $dir = new DirectoryIterator(ALPC_LOG_PATH);
        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDot()) {

                $i = $fileInfo->getMTime();
                $split = explode('_', $fileInfo->getFilename());
                $logArr[$i]['id'] = $i;
                $logArr[$i]['name']  = $fileInfo->getFilename();
                $logArr[$i]['size'] = $fileInfo->getSize();
                $logArr[$i]['date'] = $split[0];
                //$i++;
            }
        }

        krsort($logArr);

        $table = new tableFactory();
        $table->setTableId('loglisttable');
        $table->loadTemplate('loglisttable');

        if(!empty($logArr)){
            $table->loadValues($logArr);
        } else{
            $table->loadValues('{MESA_LOG_NOLOGFILES}');
        }


        $this->_template->set('file_list', $table->renderTable());
    }

    function load($fileName){

        $this->doNotRenderHeader = True;
        $path = ALPC_LOG_PATH . DS . $fileName;
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $logFile = False; //place holder
        $renderTable = False;

        if($ext === 'txt'){
            $contents = file_get_contents($path);
            $logFile = $contents;

            // $logArray = array();
            // $i = 0;
            // $contents = file($path);
            // foreach ($contents as $line) {
            //     $chunk = explode('-->', trim($line));
            //
            //     if(array_key_exists(0, $chunk)){
            //         $parts = explode(' ', trim($chunk[0]));
            //
            //         if(!array_key_exists(6, $parts)){
            //             continue;
            //         }
            //
            //         $logArray[$i]['date'] = $parts[0];
            //         $logArray[$i]['time'] = $parts[1];
            //         $logArray[$i]['ip'] = $parts[3];
            //
            //
            //         $userId = str_replace(array('(', ')'),  array('', ''), $parts[4]);
            //         if($userId == '?'){
            //             $userName = 'Unknown';
            //         } else{
            //             $userName = getUserName($userId);
            //         }
            //
            //         $logArray[$i]['user_name'] = $userName;
            //         $logArray[$i]['user_id'] = $userId;
            //
            //
            //         $logArray[$i]['level'] = $parts[6];
            //         $logArray[$i]['message'] = $chunk['1'];
            //
            //         switch ($parts[6]){
            //
            //             case ALPC_DEBUG:
            //                 $logArray[$i]['color'] = '#ABAEAE';
            //                 break;
            //
            //             case ALPC_NOTICE:
            //                 $logArray[$i]['color'] = '#FFFFFF';
            //                 break;
            //
            //             case ALPC_SECURITY:
            //                 $logArray[$i]['color'] = '#FAF8AE';
            //                 break;
            //
            //             case ALPC_ERROR:
            //                 $logArray[$i]['color'] = '#FCEB6F';
            //                 break;
            //
            //             case ALPC_CRIT:
            //                 $logArray[$i]['color'] = '#FFCC25';
            //                 break;
            //
            //         }
            //
            //         $i++;
            //     }
            // }
            //
            // $table = new tableFactory();
            // $table->setTableId('logFileTable');
            // $table->loadTemplate('logFileTable');
            // $table->loadValues($logArray);
            // $renderTable = $table->renderTable();
        }

        $this->_template->set('log_file', $logFile);
    }

}
