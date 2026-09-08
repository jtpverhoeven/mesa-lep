<?PHP

class billingController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }


    function scan2bill()
    {

        
    }    

    function createBillingOverview()
    {

        $this->render = False; 

        $projectsSelected = $_POST['projects'];
        
        //conver to array
        $projectsSelected = explode(',', $projectsSelected);        
        

        //remove empty values
        $projectsSelected = array_filter($projectsSelected);
               

        $returnObject = [];

        //check if $_POST['incoming_barcode'] is set and not empty, else default to null
        $incomingBarcode = isset($_POST['barcode']) && !empty($_POST['barcode']) ? $_POST['barcode'] : null;
              
        //use barcode to find project       
        if($incomingBarcode)
        {
            $projectObject = upa('projects', 'clientProjectsList', array(False, False, False, False, $incomingBarcode, False, False, True), False);            

            if(isset($projectObject['found']))
            {                                
                $project = new Project();
                $project->where('id', $projectObject['found_id']);
                $project = $project->first();                
                             
                //is it already in the selected projects?
                if(!in_array($project['id'], $projectsSelected))
                {
                    $projectsSelected[] = $project['id'];
                }                
            }

            else
            {
                $returnObject['status'] =  'barcode_not_found';
            }
        }
        
        //load existing ones so we can do a client check         
        if(count($projectsSelected) > 0)
        {
          
            $selectedProjects = new Project();
            $placeholders = str_repeat('?,', count($projectsSelected) - 1) . '?';
            $sql = "SELECT client FROM projects WHERE id IN ($placeholders)";
            $stmt = $selectedProjects->getPdo()->prepare($sql);
            $stmt->execute($projectsSelected);                        
            $clients = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $uniqueClients = array_unique($clients);
            
            
            if(count($uniqueClients) > 1)
            {
                //todo some protection here
                print(json_encode(array('status' => 'client_mismatch')));
                return;
            }

            $client = new Client();
            $client->where('id', $uniqueClients[0]);
            $client = $client->first();

            $returnObject['client_name'] = $client['name'];
            $returnObject['debit_number'] = 'Debiteur nummer: ' . $client['debit_number'];

            $cats = upa('clients','clientCategories', array($client['id']), False); 
            
            $returnObject['client_cats'] = 'Klant categorie: ' .  implode(',', array_column($cats, 'name'));


        }             
  


        //no projects yet? 
        if(count($projectsSelected) == 0)
        {
            $returnObject['projects'] = [];
            $returnObject['billingTypeTable'] = '';       
            $returnObject['projectSearchTableBody'] = '';
            $returnObject['billedArticlesTable'] = '';
            $returnObject['client_name'] = '';
            $returnObject['client_cats'] = '';
            print(json_encode($returnObject));
            return;
        }



        //get all projects
        $billingData = $this->generateBillingData($projectsSelected);
          
        $tF = new tableFactory();
        $tF->setTableId('projectBillingTypesTable');
        $tF->loadTemplate('projectBillingTypesTable');
        $tF->loadValues($billingData['sampleTypes']);                       

        $returnObject['projects'] = $projectsSelected;
        $returnObject['billingTypeTable'] = $tF->renderTable();            
        $returnObject['projectSearchTableBody'] = $billingData['scanTable'];
        $returnObject['billedArticlesTable'] = $billingData['billedArticlesTable'];
        

        print(json_encode($returnObject));

    }

    private function generateBillingData($projectsSelected)
    {

        $sampleTypes = $this->getSampleTypes($projectsSelected);        

        $scanTable = $this->buildScannedTable($projectsSelected);

        $billedArticlesTable = $this->getBilledArticles($projectsSelected);

        return compact('sampleTypes', 'scanTable', 'billedArticlesTable');
        

    }

    private function buildScannedTable($projects)
    {

        $trs = '';

        foreach($projects as $project)
        {
            $tr = upa('projects', 'createProjectTableLine', array($project), False);
            $trs .= $tr;
        }

        return $trs;



    }

    public function getSampleTypes($projects)
    {

      
        $counts = [];
        
        $counts['S']['header'] = 'Normaal';
        $counts['S']['count'] = 0;
        $counts['S']['count50'] = 0;
        $counts['S']['count100'] = 0;

        $counts['T']['header'] = 'THT';
        $counts['T']['count'] = 0;
        $counts['T']['count50'] = 0;
        $counts['T']['count100'] = 0;

        $counts['R']['header'] = 'Rodac';
        $counts['R']['count'] = 0;
        $counts['R']['count50'] = 0;
        $counts['R']['count100'] = 0;

        $counts['L']['header'] = 'Legionella';
        $counts['L']['count'] = 0;
        $counts['L']['count50'] = 0;
        $counts['L']['count100'] = 0;

        $holidays = new Holidays();

        foreach($projects as $project)
        {

            $query = 'SELECT * FROM samples where project = ' . $project;
            $sa = new Sample();
            $results = $sa->customQuery($query, []);
    
            
            
            foreach($results as $result)
            {
                
                $markup = 'normal';
                
                if($result['sample_innoculated'])
                {
                    $meltedDate = date('Y-m-d', $result['sample_innoculated']);
                    $markup = $holidays->getTypeOfSpecialDate($meltedDate);
                }
                                                                                                            
                $st = $result['sample_type'];
                $tht = $result['tht_code'];
    
                if($st == 'S' && empty($tht))
                {
                    $counts['S']['count']++;
                    $counts['S']['count50'] = $markup == 'saturday' ? $counts['S']['count50'] + 1 : $counts['S']['count50'];
                    $counts['S']['count100'] = $markup == 'sunday' || $markup == 'holiday' ? $counts['S']['count100'] + 1 : $counts['S']['count100'];
                    $counts['S']['count50flair']  = '';
                    $counts['S']['count100flair']  = '';
                }   
                
                if($st == 'S' && !empty($tht))
                {
                    $counts['T']['count']++;
                    $counts['T']['count50'] = $markup == 'saturday' ? $counts['T']['count50'] + 1 : $counts['T']['count50'];
                    $counts['T']['count100'] = $markup == 'sunday' || $markup == 'holiday' ? $counts['T']['count100'] + 1 : $counts['T']['count100'];
                    $counts['T']['count50flair']  = '';
                    $counts['T']['count100flair']  = '';
                }
    
                if($st == 'R')
                {
                    $counts['R']['count']++;
                    $counts['R']['count50'] = $markup == 'saturday' ? $counts['R']['count50'] + 1 : $counts['R']['count50'];
                    $counts['R']['count100'] = $markup == 'sunday' || $markup == 'holiday' ? $counts['R']['count100'] + 1 : $counts['R']['count100'];
                    $counts['R']['count50flair']  = '';
                    $counts['R']['count100flair']  = '';
                }
    
                if($st == 'L')
                {
                    $counts['L']['count']++;
                    $counts['L']['count50'] = $markup == 'saturday' ? $counts['L']['count50'] + 1 : $counts['L']['count50'];
                    $counts['L']['count100'] = $markup == 'sunday' || $markup == 'holiday' ? $counts['L']['count100'] + 1 : $counts['L']['count100'];
                    $counts['L']['count50flair']  = '';
                    $counts['L']['count100flair']  = '';
                }
    
    
            }

        }        

        //drop empty types
        foreach($counts as $key => $count)
        {
            if($count['count'] == 0)
            {
                unset($counts[$key]);
            }


            //set tablestyles to background yellow text black if > 0

            if($count['count50'] > 0)
            {
                $counts[$key]['count50flair'] = 'background-color: yellow; color: black;';
            }

            if($count['count100'] > 0)
            {
                $counts[$key]['count100flair'] = 'background-color: yellow; color: black;';
            }


        }

        return $counts; 

    }   


    function getBilledArticles($projectIds)
    {
        
        $validatedInput = array_map(function($value) {
            return filter_var($value, FILTER_VALIDATE_INT);
        }, $projectIds);


        $selectedProjects = new Project();


        $sql = "SELECT sampleanalysis.assay_base, sampleanalysis.id, assays.article_code, assays.billable, assays.name, COUNT(*) as total, 
        SUM(CASE WHEN sampleanalysis.conf_requested = '1' THEN 1 ELSE 0 END) as conf_requested_count,
        GROUP_CONCAT(sampleanalysis.id) as ids
        FROM sampleanalysis 
        JOIN assays ON sampleanalysis.assay_base = assays.id 
        WHERE sampleanalysis.project IN (" . implode(',', $validatedInput) .  ") 
        GROUP BY sampleanalysis.assay_base, assays.name
        ORDER BY assays.name ";
        
        $stmt = $selectedProjects->getPdo()->prepare($sql);
        $stmt->execute();   

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

      
        
        foreach ($results as $idx =>  &$result) {

            $legDataFound = False; 
          
            $positives = 0; 

            //was conf_requested?
            if($result['conf_requested_count'] > 0)
            {
                
                //explode the ids column
                $ids = explode(',', $result['ids']);

              

                foreach($ids as $id)
                {
                    $sa = new SampleAnalysis();
                    $sa->where('id', $id);
                    $sa = $sa->first();
                
                    $storedResult = json_decode($sa['storedResult'], True);

                    //is this an array?
                    if(!is_array($storedResult))
                    {
                        continue;
                    }

                    $detected = checkKeyOrFalse($storedResult, 'hidden', 'detected');

                    if($detected)
                    {
                        $legDataFound = True;
                        $positives++;
                    }
                    
                }
                

                

            }


            

            //drop if not billable
            if($result['billable'] == 0)
            {
                unset($results[$idx]);
                continue;
            }

            if (!isset($result['article_code']) || empty($result['article_code'])) {
                $result['article_code'] = '-';
            }

            if ($result['conf_requested_count'] > 0  && $legDataFound)
            {
                $result['leg_positive'] = ' - Waarvan positief:' . $positives;
            }

            else
            {
                $result['leg_positive'] = '';
            }

        }

        unset($result); // Unset reference after loop

        if (empty($results))
        {
            return generateHTML('alertWarning', array('alert_title' => 'Geen resultaten', 'alert_message' => 'Geen resultaten gevonden voor dit project'));
        }

        $tF = new tableFactory();
        $tF->setTableId('projectBillingTable');
        $tF->loadTemplate('projectBillingTable');
        $tF->loadValues($results);        
        
        
        return  $tF->renderTable();            
    }


}