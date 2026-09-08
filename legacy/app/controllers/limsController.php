<?PHP

class limsController extends Controller{


    //protected $_publicActions = array('view' => True, 'ajax' => True);
    //protected $_noRerouteActions = array('ajax' => True);

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');

    }

    function landing(){
        $this->doNotRenderHeader = 1;
    }

    function devWarning(){
      $this->renderAlternateHeader = 'slim';
      $this->_template->set('route', ALPC_BASEPATH . '?logout');
    }

   function dashboard(){


    $portalStatus = upa('portal', 'connectionTest', array(True), False);



    $appletRender = upa('applets', 'runAppletsForUser', array(), False);

    $colArrangment[1] = '';
    $colArrangment[2] = '';
    $colArrangment[3] = '';
    $js = '';

    $i = 1;
    foreach($appletRender as $appletId => $appletRender){

    $colArrangment[$i] .= $appletRender['html'];
    $js .= $appletRender['js'];

    if($i == 3){
    $i = 1;
    }

    }

    $this->_template->set('portalwarn', '');
    
    if(strval($portalStatus) !== '200')
    {
        $this->_template->set('portalwarn', generateHTML('portalError', []));
    }

    

    
    $this->_template->set('col1', $colArrangment[1]);
    $this->_template->set('col2', $colArrangment[2]);
    $this->_template->set('col3', $colArrangment[3]);
    $this->_template->set('appletJs', $js);

   }

  function _createActivityWidget(){


       $render = '';

       //select samples that finish completly
       $finishedSamples = pa('samples', 'readyToday', array());
       $finishedAssays = pa('sampleAnalysis', 'readyToday', array());

       $sampleDb = array();

       $rArr = array();
       $sArr = array();

       foreach($finishedSamples as $sample){
           $rArr[$sample['client']]['samples'] = $sample['id'];
           $sArr[$sample['id']] = $sample;
       }


       foreach($rArr as $client => $finished){
           $clientName = customerIdToName($client);
           $nSampl = count($finished['samples']);

           if($nSampl >= MESA_ACTIVITY_FOLD_LIMIT){
               //group them
               $renderArr['link_text'] = 'Multiple samples from ' . $clientName;
               $renderArr['link'] = '#';
               $renderArr['prefix'] = $nSampl;
               $render .= generateHTML('lims/activity', $renderArr);
           } else{
               //show them all
               foreach($finished as $sampleId){
                    $renderArr['link_text'] = $sArr[$sampleId]['description'] . '(' . $clientName .     ')';
                    $renderArr['link'] = '{LB}/samples/lookup/' . $sArr[$sampleId]['barcode'];
                    $renderArr['prefix'] = '<i class="icon-beaker"></i>';
                    $render .= generateHTML('dashboard/activity', $renderArr);
               }

           }
        }

        if(empty($render)){
            return 'none';
        } else {
            return $render;
        }


   }

}
