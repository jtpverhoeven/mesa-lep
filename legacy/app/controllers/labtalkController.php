<?PHP

class labtalkController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', 'active');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    function wall(){
        $this->_template->set('wall_message', '{MESA_PRO_WALLPLACEHOLDERYOU}') ;
        $userInfo = getUserProfile(getUserId());
        $this->_template->set('real_name', $userInfo['first_name'] . ' ' . $userInfo['last_name']);
        $this->_template->set('user_name', $userInfo['username']);
        $this->_template->set('user_avatar', generateAvatar(getUserId(), False, 'small', False, True));
        $this->_template->set('user_id', getUserId());
    }


    function projectWall(){

    }

    function project($id){

        //get standard info
        $pInfo = upa('projects', 'fetch', array($id), False );
        $pInfo['client_name'] = customerIdToName($pInfo['client']);
        $pInfo['subclient_name'] = subclientIdToName($pInfo['subclient']);
        $pInfo['number_of_samples'] = upa('samples', 'countSamplesInProject', array($id), False);
        $pInfo['number_of_followers'] = upa('bookmarks', 'countFollowers', array($id, 'PROJECT'));
        $pInfo['start_date'] = date('d-m-Y', $pInfo['project_date']);
        $pInfo['edit_date'] =  date('d-m-Y', $pInfo['last_edit']);
        $pInfo['attached_samples'] = '';

        //get contributors
        $contributors = upa('revisions', 'getProjectContributors', array($id), False);
        $contributionRender = '';
        foreach($contributors as $contributor){
            $contributor['avatar_img_src'] = generateAvatar($contributor['id'], $contributor['avatar_uri'], 'small', False, True);
            $contributionRender .= generateHTML('projectPage/contributorLine', $contributor);
        }

        $samples = upa('samples', 'fetchSamplesInProject', array($id), False);
        foreach($samples as $attSample){
           $pInfo['attached_samples'] .= generateHTML('projectPage/sampleLine', $attSample);
        }

        if($pInfo['attached_samples']  == ''){
            $pInfo['attached_samples']  = '{MESA_LPP_NOSAMPLES}';
        }


        //get timeline
        $tlArr = array();
        $tlArr['point_0'] = 'progtrckr-done';
        $tlArr['point_1'] = 'progtrckr-done';

        if($pInfo['auth_status'] == '1'){
            //authorized, fill all
            $tlArr['point_2'] = 'progtrckr-done';
            $tlArr['point_3'] = 'progtrckr-done';
        } else{
            //check where we are
            #$endPoint = upa('projects', 'projectEndTime', array($id));
            #$cTime = time();

            #if($cTime < $endPoint){
            #    $tlArr['point_2'] = 'progtrckr-todo';
            #} else{
            #    $tlArr['point_2'] = 'progtrckr-done';
            #}

            if($pInfo['is_ready'] == 0){
              $tlArr['point_2'] = 'progtrckr-todo';
              $tlArr['point_3'] = 'progtrckr-todo';
            } else{
              $tlArr['point_2'] = 'progtrckr-done';
              $tlArr['point_3'] = 'progtrckr-todo';
            }




        }


        $this->_template->set('timeline', generateHTML('projectPage/timeline', $tlArr));
        $this->_template->set('contributors', $contributionRender);
        $this->_template->set('wall_message', '{MESA_PJP_WALLPLACEHOLDERPROJECT}') ;
        $this->_template->setByArray($pInfo);
    }

}
