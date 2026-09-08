<?PHP

class clientCategoriesController extends Controller
{

    function beforeAction($queryString) {
        $this->_template->set('MESA_LIMS_ACTIVE', 'active');
        $this->_template->set('MESA_SOCIAL_ACTIVE', '');
        $this->_template->set('MESA_ADMIN_ACTIVE', '');
    }

    function list()
    {

        $table = new tableFactory();
        $table->setTableId('categoryTable');
        $table->loadTemplate('categoriesTable');        
        $results = $this->ClientCategory->search();

        if(empty($results)){
            $results = 'Geen categorien gevonden';
        }

        $table->loadValues($results);
        $this->_template->set('category_table', $table->renderTable());

        $sForm = new formFactory('clientCategories');

        $sForm->setId('addCategoryForm');
        $sForm->addClass('');
        $sForm->action( ALPC_BASEPATH . '/clientCategories/save');
        $sForm->method('POST');
        $sForm->setTemplate('generic');

        $sForm->addInputField('name', 'Categorie naam', 'text', 'input-block-level', '', 'Categorie naam', False, False);
        $sForm->addValidation('name', 'NO_DUPLICATE');        
        $sForm->submitTrough('addCategorySubmit');

        $this->_template->set('addCategoryForm', $sForm->render());

    }

    public function save()
    {
        $this->render = false;
        $this->ClientCategory->name = $_POST['name'];
        $this->ClientCategory->save();
        $this->reRoute('clientCategories/list' , True);               
    }

    public function destroy($id)
    {
        $this->render = False;  
        $this->ClientCategory->id = $id;
        $this->ClientCategory->delete();        
        $this->removeAllFromPivot($id);
    }

    private function removeAllFromPivot($category)
    {
        
    }

    public function all(){
        $results = $this->ClientCategory->search();
        return $results;
    }

    public function show($id){

        $this->ClientCategory->where('id', $id);
        $cat = $this->ClientCategory->search();

        if(empty($cat)){
            $this->reRoute('clientCategories/list' , True);         
        }

        $clients = upa('clients', 'clientsInCategory', array($id), False);
        
        $table = new tableFactory();
        $table->setTableId('categoryTable');
        $table->loadTemplate('clientsInCatTable');        
        

        if(empty($clients)){
            $clients = 'Geen categorien gevonden';
        }

        $table->loadValues($clients);
        $this->_template->set('category_table', $table->renderTable());

        
        $this->_template->set('name', $cat[0]['name']);

    }

}