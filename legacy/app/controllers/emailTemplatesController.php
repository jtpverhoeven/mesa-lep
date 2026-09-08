<?PHP

class emailTemplatesController extends Controller
{
    function beforeAction($queryString)
    {
        $this->_template->set('MESA_LIMS_ACTIVE', '');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', 'active');
    }

    public function index()
    {

        $table = new tableFactory();
        $table->setTableId('emailTable');
        $table->loadTemplate('emailTable');
        
        $table->specifyMod('defaultmail', 'flipIt', array( ALPC_TF_SELF, ['0' => 'Nee', '1' => 'Ja'] ));
        $table->specifyMod('defaultmail_with_files', 'flipIt', array( ALPC_TF_SELF, ['0' => 'Nee', '1' => 'Ja'] ));
        $table->specifyMod('defaultmail_for_files', 'flipIt', array( ALPC_TF_SELF, ['0' => 'Nee', '1' => 'Ja'] ));
        
          
        $results = $this->EmailTemplate->search();

        if(empty($results)){
            $results = 'Geen emails ingesteld';
        }

        $table->loadValues($results);

        $this->_template->set('email_table', $table->renderTable());
    }

    public function edit($id = null)        
    {

        if($id !== null)
        {
            $this->EmailTemplate->where('id', $id);
            $results = $this->EmailTemplate->search();
    
            if(empty($results)){
                $this->reRoute('emailTemplates/index', True);
                return; 
            }
            
        
            $this->EmailTemplate->arrayToModel($results['0']);
        }
               

        $sForm = new formFactory('emailtemplate');

        $sForm->setId('emailTemplateForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/emailTemplates/store');
        $sForm->method('POST');
        $sForm->setTemplate('generic');


        $sForm->addInputField('name', 'Naam', 'text', 'input-block-level',  $this->EmailTemplate->name , False);
        $sForm->addTextArea('template', 'Email', 'text', 'input-block-level', $this->EmailTemplate->template, '', 'rows="10"', False);
        $sForm->addTextArea('template_en', 'Email engelse versie', 'text', 'input-block-level', $this->EmailTemplate->template_en, '', 'rows="10"', False);

        $sForm->addInputField('bcc', 'BCC', 'text', 'input-block-level', $this->EmailTemplate->bcc , False);
        $sForm->addInputField('id', False, 'hidden', 'hide',  $id , False);

        $sForm->submitTrough('saveEmailButton');


        $this->_template->set('email_form', $sForm->render());
    }

    public function store()
    {
        
        if(isset($_POST['id']) && !empty($_POST['id']))
        {                        
            $this->EmailTemplate->where('id', $_POST['id']);
            $results = $this->EmailTemplate->search();            
            $this->EmailTemplate->arrayToModel($results['0']);            
        }

        $this->EmailTemplate->template = $_POST['template'];
        $this->EmailTemplate->template_en = $_POST['template_en'];
        $this->EmailTemplate->name = $_POST['name'];
        $this->EmailTemplate->bcc = $_POST['bcc'];
        $this->EmailTemplate->save(); 

        $this->reRoute('emailTemplates/index', True);
        return; 
        
    }

    public function destroy($id)
    {
        $this->EmailTemplate->id = $id;
        $this->EmailTemplate->delete();
        $this->reRoute('emailTemplates/index', True);
    }

    public function setdefault($id, $defaultFor)
    {        
            


        $this->EmailTemplate->where('id', $id);
        
        $results = $this->EmailTemplate->search();

        if(!empty($results)){

            if($defaultFor == 'reports')
            {
                $sql = 'UPDATE `emailtemplates` SET `defaultmail` = 0;';            
                $result = $this->EmailTemplate->customSetQuery($sql, array());

                $this->EmailTemplate->id = $results[0]['id'];
                $this->EmailTemplate->defaultmail = 1;
                $this->EmailTemplate->save();

            }

            if($defaultFor == 'reports_with_files')
            {
                $sql = 'UPDATE `emailtemplates` SET `defaultmail_with_files` = 0;';            
                $result = $this->EmailTemplate->customSetQuery($sql, array());

                $this->EmailTemplate->id = $results[0]['id'];
                $this->EmailTemplate->defaultmail_with_files = 1;
                $this->EmailTemplate->save();
            }

            if($defaultFor == 'for_files')
            {
                $sql = 'UPDATE `emailtemplates` SET `defaultmail_for_files` = 0;';            
                $result = $this->EmailTemplate->customSetQuery($sql, array());

                $this->EmailTemplate->id = $results[0]['id'];
                $this->EmailTemplate->defaultmail_for_files = 1;
                $this->EmailTemplate->save();
            }

                                      
            #$sql = 'UPDATE `emailtemplates` SET `defaultmail` = 0;';                        
            #$result = $this->EmailTemplate->customSetQuery($sql, array());


            
        }

        $this->reRoute('emailTemplates/index', True);
        
    }

    private function isDefaultMail($mail, $defaultType)
    {

        if($defaultType == 'reports' && $mail['defaultmail'] == 1)
        {
            return True;
        }
        
        if($defaultType == 'report_with_files' && $mail['defaultmail_with_files'] == 1)
        {
            return True;
        }
        

        if($defaultType == 'for_files' && $mail['defaultmail_for_files'] == 1)
        {
            return True;
        }

        return  False; 

    }

    public function renderSelector($type = 'reports', $forceSelect = False, $forceSelectId = 0)
    {
        $templates = $this->EmailTemplate->search();
        $options = '';
        $selected = '';
        $selectedBcc = '';
                
        if(count($templates) > 0)
        {
            $default = false;

            foreach($templates as $template)
            {
                if($this->isDefaultMail($template, $type) || ($forceSelect === True && $forceSelectId == $template['id']))
                {
                    $options = $options . '<option value="' . $template['id'] .'" selected="selected">' . $template['name'] . '</option>';
                    $selected = $template['template'];
                    $selectedBcc = $template['bcc'];
                    $default = true;
                }

                else
                {
                    $options = $options . '<option value="' . $template['id'] .'">' . $template['name'] . '</option>';
                }
                
            }

            if($default === false)
            {
                //no default found, set first as seelcted 
                $selected = $templates[0]['template'];
                $selectedBcc = $templates[0]['bcc'];
            }

        }        
        
        return ['options' => $options , 'selected' => $selected, 'selected_bcc' => $selectedBcc];
    }

    public function loadTemplate($id, $lang = 'nl')
    {
        $this->render = false; 
        
        $this->EmailTemplate->where('id', $id);
        
        $temp = $this->EmailTemplate->first();

        if(!empty($temp))
        {
            print(json_encode([
                'template' => ($lang == 'nl') ? $temp['template'] : $temp['template_en'],
                'bcc' => $temp['bcc']
            ]));
        }

    }

}