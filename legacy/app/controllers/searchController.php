<?PHP

class searchController extends Controller {

    function beforeAction($queryString)
    {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }


    function search(){
        
        $sForm = new formFactory($this->_controller);
        $sForm->setId('sampleForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');
        $sForm->returnAsFieldArray();

        $sForm->addInputField('client_name', False, 'text', 'select2-input select2-default input-block-level', False, False,
            array('autocomplete' => 'off', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'aria-activedescendant' => 'select2-result-label-351'));
            
        
        $assaysOpt = '';

        $assayObj = new Assay();
        $assayObj->where('active', 1 );
        $assays = $assayObj->search();


        foreach($assays as $assay)
        {
            $assaysOpt .= '<option value="'.$assay['original_id'] .'">'.$assay['name'] .'</option>';
        }

        #samplign options 
        $samplingOpts = upa('sampleProcedures', 'getProcedureDropDown', array(null));
   
        #user opts
        $userObj = new User();
        $users = $userObj->search();

        $userOpts = '';
        foreach($users as $user)
        {
            $userOpts .= '<option value="'.$user['id'] .'">'.$user['username'] .'</option>';
        }



        $formFields = $sForm->render();
        $this->_template->setByArray($formFields);
        $this->_template->set('assayOpts', $assaysOpt);
        $this->_template->set('userOpts', $userOpts);
        $this->_template->set('samplingOpts', $samplingOpts);

    }

    function doSearch(){

        $this->render = False; 

        $searchType = $_POST['searchType'];        

        if($searchType == 'project'){
            
            $data = array();
            $data['client'] = $_POST['client_id'];

            $data['filter_project_name'] = filter_var($_POST['filter_project_name'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['project_name_value'] = $_POST['project_name_value'];

            $data['filter_project_notes'] = filter_var($_POST['filter_project_notes'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['project_notes_value'] = $_POST['project_notes_value'];

            $data['filter_auth_status'] = filter_var($_POST['filter_auth_status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['auth_status_value'] = $_POST['auth_status_value'];

            $data['filter_auth_user'] = filter_var($_POST['filter_auth_user'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['auth_user_value'] = $_POST['auth_user_value'];

            $data['filter_sampling_date'] = filter_var($_POST['filter_sampling_date'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['sampling_date_start'] = $_POST['sampling_date_start'];
            $data['sampling_date_end'] = $_POST['sampling_date_end'];

            $data['filter_arrival_date'] = filter_var($_POST['filter_arrival_date'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['arrival_date_start'] = $_POST['arrival_date_start'];
            $data['arrival_date_end'] = $_POST['arrival_date_end'];

            $data['max_records'] = $_POST['max_records'];
            $data['max_n'] = $_POST['max_n'];

            $result = $this->searchForProjects($data);
            
            //dd($result);

            print($this->createProjectsResultTable($result['results']));            
                    


        }

        if($searchType == 'sample'){

            $data = array();
            $data['client'] = $_POST['client_id'];
            
            $data['filter_analysis'] = filter_var($_POST['filter_analysis'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['filter_assays'] = $_POST['filter_assays'];

            $data['filter_metadata'] = filter_var($_POST['filter_metadata'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['metadata_value'] = $_POST['metadata_value'];

            $data['filter_description'] = filter_var($_POST['filter_description'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['description_value'] = $_POST['description_value'];

            $data['filter_details'] = filter_var($_POST['filter_details'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['details_value'] = $_POST['details_value'];

            $data['filter_innoc'] = filter_var($_POST['filter_innoc'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['innoc_start'] = $_POST['innoc_start'];
            $data['innoc_end'] = $_POST['innoc_end'];

            $data['filter_client_description'] = filter_var($_POST['filter_client_description'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['client_description_value'] = $_POST['client_description_value'];

            $data['filter_sample_type'] = filter_var($_POST['filter_sample_type'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['sample_type_value'] = $_POST['sample_type_value'];

            $data['filter_on_sampling_method'] = filter_var($_POST['filter_on_sampling_method'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['sampling_method_value'] = $_POST['sampling_method_value'];

            $data['filter_on_product_groups'] = filter_var($_POST['filter_on_product_groups'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $data['product_groups_value'] = $_POST['product_groups_value'];
            


            $data['max_records'] = $_POST['max_records'];
            $data['max_n'] = $_POST['max_n'];
            
            $results = $this->searchForSamples($data);
            
            print($this->createSampleResultTable($results['results']));            
          
        } 

    }

    private function searchForProjects($data){

        $sql = $this->startProjectQuery();

        $params['client_id'] = $data['client'];

        //add additional filters
        $sql .= ' WHERE projects.client = :client_id';

        if($data['filter_project_name'])
        {
            $sql .= ' AND projects.project_name LIKE :project_name';
            $params['project_name'] = $data['project_name_value'];
        }

        if($data['filter_project_notes'])
        {
            $sql .= ' AND projects.project_notes LIKE :project_notes';
            $params['project_notes'] = '%' . $data['project_notes_value'] . '%';
        }
        
        if($data['filter_auth_status'])
        {
            $sql .= ' AND projects.auth_status = :auth_status';
            $params['auth_status'] = $data['auth_status_value'];
        }

        if($data['filter_auth_user'])
        {
            $sql .= ' AND projects.auth_by = :auth_user';
            $params['auth_user'] = $data['auth_user_value'];
        }

        if($data['filter_sampling_date'])
        {         

            //transform d-m-Y to mysql date format
            try{
                $params['sampling_date_start'] = date('Y-m-d', strtotime($data['sampling_date_start']));
                $params['sampling_date_end'] = date('Y-m-d', strtotime($data['sampling_date_end']));
            }

            catch(Exception $e){
              
                $data['filter_sampling_date'] = false; 
            }
            
        }

        if($data['filter_arrival_date'])
        {         

            //transform d-m-Y to mysql date format
            try{
                $params['arrival_date_start'] = date('Y-m-d', strtotime($data['arrival_date_start']));
                $params['arrival_date_end'] = date('Y-m-d', strtotime($data['arrival_date_end']));
            }

            catch(Exception $e){
              
                $data['filter_arrival_date'] = false; 
            }
           
        }

        $sql .= $this->closeProjectQuery($data);

        $projectObj = new Project();
        return [
            'results' => $projectObj->customQuery($sql, $params),
            'query' => $sql
        ];

    }

    private function closeProjectQuery($data)
    {

        $tail = '';

        if($data['filter_sampling_date'])
        {
            $tail .= ' HAVING (project_sampling_date BETWEEN :sampling_date_start AND :sampling_date_end) ';         
        }

        if($data['filter_arrival_date'])
        {

            if($data['filter_sampling_date'])
            {
                $tail .= ' AND ';
            }

            if(!$data['filter_sampling_date'])
            {
                $tail .= ' HAVING ';
            }

            $tail .= '  (project_arrival_date BETWEEN :arrival_date_start AND :arrival_date_end) ';         
        }

        $tail .= ' GROUP BY projects.id ';

        if($data['max_records'])
        {
            $tail .= ' LIMIT ' . (int)$data['max_n'];
        }
        
        return $tail; 
    }

    private function startProjectQuery()
    {
        $sql = 'SELECT projects.*,  count(exports.id) AS report_count,';

        $sql .= ' STR_TO_DATE( JSON_UNQUOTE( JSON_EXTRACT(projects.custom_fields, "$.project_monster")), "%d-%m-%Y") AS project_sampling_date, ';

        $sql .= ' STR_TO_DATE( JSON_UNQUOTE( JSON_EXTRACT(projects.custom_fields, "$.project_ontvangst")), "%d-%m-%Y") AS project_arrival_date, ';

        $sql .= ' JSON_UNQUOTE( JSON_EXTRACT(projects.custom_fields, "$.project_monster")) AS original_project_sampling_date, ';

        $sql .= ' JSON_UNQUOTE( JSON_EXTRACT(projects.custom_fields, "$.project_ontvangst")) AS original_project_arrival_date ';
     
        
        $sql .= ' FROM projects  LEFT JOIN exports ON  exports.project = projects.id ';

        return $sql; 
        
    }

    private function createProjectsResultTable($results)
    {

        $rows = '';

        foreach($results as $result)
        {

            $projectLink = ALPC_BASEPATH . '/projects/search/' . $result['id'];

            $projectLink = ALPC_BASEPATH . '/projects/search/' . $result['id'];
          

            $rows .= '<tr>';

            $rows .= '<td><a href="'. $projectLink.'">'.$result['id'].'</a></td>';

            $rows .= '<td><a href="'. $projectLink.'">'.$result['reference'].'</a></td>';

            $rows .= '<td><a href="'. $projectLink.'">'.$result['project_name'].'</a></td>';

            $rows .= '<td>'.$result['original_project_sampling_date'].'</td>';

            $rows .= '<td>'.$result['original_project_arrival_date'].'</td>';

            $auth =  ($result['auth_status'] == 1) ? 'Ja' : 'Nee';
         
            $rows .= '<td>' . $auth . '</td>';

            $reports = ($result['report_count'] > 0) ? 'Ja (' . $result['report_count'] .')' : 'Nee';

            $reportLink = ALPC_BASEPATH . '/exports/project/' . $result['id'];

            $rows .= '<td><a href="'. $reportLink.'">'.$reports.'</a></td>';
          
            $rows .= '</tr>';
        }

        return $rows;

    }

    private function createSampleResultTable($results)
    {
        
        $rows = '';

        foreach($results as $result)
        {

            $projectLink = ALPC_BASEPATH . '/projects/search/' . $result['project'];
            $sampleLink =  ALPC_BASEPATH . '/samples/lookup/' . $result['barcode'];

            $rows .= '<tr>';
            $rows .= '<td>'.$result['id'].'</td>';
            $rows .= '<td><a href="'. $sampleLink.'">'.$result['barcode'].'</a></td>';
            $rows .= '<td>'.$result['readable_date'].'</td>';
            $rows .= '<td>'.$result['description'].'</td>';
            $rows .= '<td>'.$result['client_description'].'</td>';                     
            $rows .= '<td>'.$result['product_group_name'].'</td>';
            $rows .= '<td><a href="'. $projectLink.'">'.$result['project_reference']. '</a><br />' . $result['project_name'] . '</td>';     
            $rows .= '</tr>';
        }

        return $rows;
    }

    private function searchForSamples($data)
    {
                
        $sql = $this->startSampleQuery($data['filter_analysis'], $data['filter_metadata'], $data['filter_assays'], False);

        $params = array();
        $params['client_id'] = $data['client'];

        //add additional filters
        $sql .= ' WHERE samples.client = :client_id';

        if($data['filter_on_product_groups'])
        {
            $injectFilter = array();

            foreach($data['product_groups_value'] as $pgid)
            {
                array_push($injectFilter, $pgid);
            }

            $sql .= ' AND samples.portal_product_group_id IN ('.implode(',', $injectFilter).') ';
         
        }

        if($data['filter_on_sampling_method'])
        {
            $sql .= ' AND samples.sampling_method = :sampling_method_value ';
            $params['sampling_method_value'] = $data['sampling_method_value'];
        }

        if($data['filter_sample_type'])
        {

            if($data['sample_type_value'] == 'T')
            {
                $sql .= ' AND samples.tht_code IS NOT NULL  ';                
            }

            else
            {
                $sql .= ' AND samples.sample_type = :sample_type_value ';
                $params['sample_type_value'] = $data['sample_type_value'];
            }

        }

        if($data['filter_client_description'] == true)
        {
            $sql .= ' AND samples.client_description LIKE :client_description_value ';
            $params['client_description_value'] = $data['client_description_value'];
        }

        if($data['filter_innoc'])
        {
            
            //convert dd-mm-YYYY to unix timestamp begin of day       
            $innoc_start =  strtotime("midnight", strtotime($data['innoc_start']));                 
            $innoc_end =  strtotime("tomorrow", strtotime($data['innoc_end']));

            $params['innoc_start'] = $innoc_start;
            $params['innoc_end'] = $innoc_end;

            $sql .= ' AND samples.sample_innoculated BETWEEN :innoc_start AND :innoc_end ';

        }

        if($data['filter_description'] == true)
        {
            $sql .= ' AND  samples.description LIKE :description_value ';
            $params['description_value'] = $data['description_value'];
        }

        if($data['filter_details'] == true)
        {
            $sql .= ' AND samples.`custom_fields` LIKE :details_value ';
            $params['details_value'] = $data['details_value'];
        }

        if($data['filter_metadata'])
        {            
            $params['mdk_value'] = $data['metadata_value'];
            $sql .= ' AND   matching_metadata_n > 0 ';
        }

        if($data['filter_analysis'])
        {                        
            $sql .= ' AND   matching_analysis_n > 0 ';
        }

        $sql .= $this->closeSampleQuery($data['filter_analysis'], $data['filter_metadata'], ($data['max_records']) ? $data['max_n'] : False);       

        $sampleObj = new Sample();
        
        return [        
            'results' => $sampleObj->customQuery($sql, $params),
            'query' => $sql
        ];


    }

    private function closeSampleQuery($assayConstrain, $metadataConstrain, $maxN)
    {


        $tail = ' ' ;
        //$tail = ' GROUP BY samples.id';

        // if($assayConstrain)
        // {
        //     $tail .= ' HAVING n_analysis > 0 ';
        // }


        if($maxN !== false)
        {
            $tail .= ' LIMIT ' . (int)$maxN;
        }

        

        return $tail; 

    }


    private function startSampleQuery($assayConstrain, $metadataConstrain, $assays, $metadataValues)    
    {

        $sql = 'SELECT samples.*, productgroups.name AS product_group_name, projects.project_name AS project_name, projects.reference AS project_reference, FROM_UNIXTIME(samples.sample_innoculated, \'%d-%m-%Y\') AS readable_date ';

        if($assayConstrain)
        {
            //$sql .= ', sampleanalysis.original_assay_base, COUNT(sampleanalysis.id) AS n_analysis';
            $sql .= ', 	matching_analysis_n AS matching_analysis_n ';
        }

        if($metadataConstrain)
        {
            $sql .= ', matching_metadata_n AS matching_metadata_n ';
        }

        $sql .= ' FROM samples ';

        $sql .= ' LEFT JOIN productgroups ON productgroups.portal_id = samples.portal_product_group_id  ';
        
        $sql .= ' LEFT JOIN projects ON projects.id = samples.project ';

        if($assayConstrain)
        {   
            //$sql .= 'LEFT JOIN sampleanalysis ON sampleanalysis.sample = samples.id AND sampleanalysis.original_assay_base IN (' . implode(',', $assays) . ') ';
            $sql .=  'LEFT JOIN ( SELECT sample, COUNT(*) AS matching_analysis_n FROM sampleanalysis WHERE sampleanalysis.original_assay_base IN (' . implode(',', $assays) . ')  GROUP BY sample  ) AS matching_analysis_n ON  matching_analysis_n.sample = samples.id ';            
        }

        if($metadataConstrain)
        {
           // $sql .= ' LEFT JOIN metadata ON metadata.sample = samples.id AND metadata.`value` LIKE :mdk_value';
           $sql .= 'LEFT JOIN ( SELECT  sample, COUNT(*) AS matching_metadata_n FROM metadata  WHERE metadata.value LIKE :mdk_value GROUP BY sample ) AS sample_metadata ON sample_metadata.sample = samples.id ';
        }




        return $sql;

    }


    

}
