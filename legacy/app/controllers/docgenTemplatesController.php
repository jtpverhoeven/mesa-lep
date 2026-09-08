<?PHP

class docgenTemplatesController extends controller{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    function listing(){

      $table = new tableFactory();
      $table->setTableId('docgenTable');
      $table->loadTemplate('docgentemplates');
      
      $this->DocgenTemplate->order('view_order', 'ASC');
      $results = $this->DocgenTemplate->search();

      if(empty($results)){
          $results = 'Geen docgen templates gevonden';
      }

      $table->loadValues($results);
      $this->_template->set('template_table', $table->renderTable());
    }

    function returnOptionsCheckboxes($passed, $preferences = [], $preliminary = false, $specialType = '0'){
          
      //force to int
      $specialType = (int)$specialType;  

      $returnChecks  = '';

      
          
      $this->DocgenTemplate->order('view_order', 'ASC');
      
      $results = $this->DocgenTemplate->search();


      
      $allowed_when_prelim = json_decode(DOCGEN_TEMPLATES_ALLOWED_TEMP);

      $selected = null;

      foreach($results as $result)
      {

          if($result['active'] == '0')
          {
            continue;
          }

          if(!in_array($result['id'], $passed)){
            continue;
          }

          if($preliminary == true && !in_array($result['folder'], $allowed_when_prelim))
          {
            continue;
          }

          if(!$selected)
          {


          
            //normal report, 1 PDF per monster
            if($specialType === 0 && $preferences['pdfTypeSelect'] == '0')
            {

                //the template should not containt he word "overview" and end with the two letters "En" if the language is English
                if(strpos($result['folder'], 'overview') === false && (($preferences['language'] == 'en' && substr($result['folder'], -2) == 'En') || ($preferences['language'] != 'en' && substr($result['folder'], -2) != 'En')))
                {
                
                    $selected = $result['id'];
                }

            }

            
            //normal report, 1 PDF in total,  1 sampel per page = " normal report' 
            else if($specialType === 0 && $preferences['pdfTypeSelect'] == '1' && $preferences['noSamples'] == '1' )
            {

                //the template should containt he word "overview" and end with the two letters "En" if the language is English
                if(strpos($result['folder'], 'overview') === false && (($preferences['language'] == 'en' && substr($result['folder'], -2) == 'En') || ($preferences['language'] != 'en' && substr($result['folder'], -2) != 'En')))
                {
               
                    $selected = $result['id'];
                }

            }

            //normal report, 1 PDF in total,  5 sampel per page = " overview report' 
            else if($specialType === 0 && $preferences['pdfTypeSelect'] == '1' && $preferences['noSamples'] == '5' )
            {

                //the template should containt he word "overview" and end with the two letters "En" if the language is English
                if(strpos($result['folder'], 'Overview') !== false && (($preferences['language'] == 'en' && substr($result['folder'], -2) == 'En') || ($preferences['language'] != 'en' && substr($result['folder'], -2) != 'En')))
                {
               
                    $selected = $result['id'];
                }

            }          

            //legionella -------------------------------- 
            //can only swpa between english and dutch template, because of the specific formatting of the legionella report.

            else if($specialType === 1 && (($preferences['language'] == 'en' && substr($result['folder'], -2) == 'En') || ($preferences['language'] != 'en' && substr($result['folder'], -2) != 'En')))
            {                                          
                $selected = $result['id'];                
            }

            else if($specialType === 2 && (($preferences['language'] == 'en' && substr($result['folder'], -2) == 'En') || ($preferences['language'] != 'en' && substr($result['folder'], -2) != 'En')))
            {                
                $selected = $result['id'];
            }
          
          
          }

        
                  

          if(in_array($result['id'], $passed)){
            
            $checked = ($selected == $result['id']) ? 'checked="checked"' : '';
            $lang = (substr($result['folder'], -2) == 'En') ? 'en' : 'nl';
            $accepts_bulk = (stripos($result['folder'], 'overview') !== false) ? '1' : '0';

            //does result['name'] contain the word "legionella"?
            if(stripos($result['name'], 'legionella') !== false)
            {               
              $accepts_bulk = '2'; //special value for legionella templates, can swap between the two modes. 
            }

            //does result['name'] contain the word "karkas"?
            if(stripos($result['name'], 'karkas') !== false)
            {               
              $accepts_bulk = '2'; //special value for legionella templates, can swap between the two modes. 
            }

            //does result['name'] contain the word "karkas"?
            if(stripos($result['name'], 'rodac') !== false)
            {               
              $accepts_bulk = '2'; //special value for legionella templates, can swap between the two modes. 
            }            
            
            
            $returnChecks .= '<div style="display: flex; align-items: center; gap: 8px; "><input type="radio" id="docgen_' . $result['id'] . '" ' . $checked . ' name="templateSelect" value="' . $result['folder'] . '" data-lang="' . $lang . '" data-accepts_bulk="' . $accepts_bulk . '"><label for="docgen_' . $result['id'] . '" style="margin: 0; margin-bottom: 8px;">' . $result['name'] . '</label></div>';
          } 

      }

    

      return $returnChecks;

    }

    function returnOptions($passed, $preferenceLanguage = 'nl',   $preliminary = false){
          
      $returnList  = '';
      
      $this->DocgenTemplate->order('view_order', 'ASC');
      
      $results = $this->DocgenTemplate->search();

      $allowed_when_prelim = json_decode(DOCGEN_TEMPLATES_ALLOWED_TEMP);

      foreach($results as $result)
      {


          if($result['active'] == '0')
          {
            continue;
          }

          if($preliminary == true && !in_array($result['folder'], $allowed_when_prelim))
          {
            continue;
          }


          if(in_array($result['id'], $passed)){
            
            $style = ''; 


            if($preferenceLanguage !== 'none')
            {
            
              //folder name ends with En?
              if($preferenceLanguage == 'en' && substr($result['folder'], -2) == 'En')
              {
                $style = 'style="font-weight: bold;"';
              }
              
              if($preferenceLanguage == 'nl' && substr($result['folder'], -2) !== 'En')
              {
                $style = 'style="font-weight: bold;"';
              }
            }

            $returnList .= '<option ' . $style . ' value="'. $result['folder'] . '">' . $result['name'] . '</option>';
          } 
                    
      }

      return $returnList;

    }


    function allDocGenTemplates()
    {
      
      $results = $this->DocgenTemplate->search();
      return $results; 

    }

}
