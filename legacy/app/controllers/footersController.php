<?PHP

class footersController extends controller{

    public function index()
    {
        $results = $this->Footer->search();

        $table = new tableFactory();
        $table->setTableId('footerTable');
        $table->loadTemplate('footersTable');                
        $table->loadValues($results);
        $table->specifyMod('q', 'isQ', array(ALPC_TF_SELF));
        $this->_template->set('footer_table', $table->renderTable());
    }

  

    public function edit($id)
    {

        $footer = $this->Footer->find($id);

        
        $sForm = new formFactory('footers');

        $sForm->setId('editFooterForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/footers/save');
        $sForm->method('POST');
        $sForm->setTemplate('generic');
        
        $sForm->addInputField('id', 'id', 'text', 'input-block-level', $footer['id'], 'id', False, False);

        $sForm->addInputField('lang', 'taal', 'text', 'input-block-level', $footer['lang'], 'taal', False, False);

        $sForm->addInputField('name', 'naam', 'text', 'input-block-level', $footer['name'], 'naam', False, False);

        //addDropdownField($name, $label, $classes, $value, $options, $tags, $prepend = False){

        $sForm->addDropdownField('q', 'In RvA scope?', 'input-block-level', $footer['q'],  array('0' => 'Nee', '1' => 'Ja'),  '' , False);
        
        $sForm->addTextArea('data', 'voettekst', 'textarea', 'input-block-level', $footer['data'], 'voettekst', 'rows="10"');

        $sForm->submitTrough('saveFooterSubmit');

        $this->_template->set('current_footer', $footer['data']);
        $this->_template->set('editFooterForm', $sForm->render());

    }

    public function save()
    {

        $this->Footer->id = $_POST['id'];
        $this->Footer->data = $_POST['data'];
        $this->Footer->lang = $_POST['lang'];
        $this->Footer->q = $_POST['q'];
        $this->Footer->save();
        
        $this->reRoute('footers/index', True);

    }

    public function get($template, $lang, $q)
    {

        

        $this->Footer->where('name', $template);
        $this->Footer->where('lang', $lang);
        $this->Footer->where('q', $q );
        
        $result = $this->Footer->first();

        if($result)
        {
            return $result['data'];
        }

        else
        {
            return false; 
        }

        
    }

}
    