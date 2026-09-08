<?PHP

class productGroupsController extends Controller{


    public function getClientDefaultGroup($client)
    {

        $this->ProductGroup->where('client_id', $client);
        $this->ProductGroup->where('default', 1);

        $group = $this->ProductGroup->first();

        if($group)
        {
            return $group;
        }

        return false; 


    }

    public function getProductGroup($id)
    {

        $this->ProductGroup->where('portal_id', $id);
        $group = $this->ProductGroup->first();

        if($group)
        {
            return $group;
        }

        return null; 

    }

    public function productGroupsArray($clientId)
    {
        $this->render = False; 

        $this->ProductGroup->where('client_id', $clientId);
        $groups = $this->ProductGroup->search();

        $opts = [];

        foreach($groups as $pg)
        {

            $append  = '';            

            if($pg['visible'] == '0')
            {
                $append = ' [Inactief voor klant]';
            }

            $opts[$pg['portal_id']] = $pg;
            
            $opts[$pg['portal_id']]['name'] = $pg['name'] . $append;
        }

        
        return $opts;

    }

    public function productGroupDropper($clientId, $return = False)
    {
        $this->render = False; 

        $this->ProductGroup->where('client_id', $clientId);
        $groups = $this->ProductGroup->search();

        $opts = '';

        foreach($groups as $pg)
        {

            $append  = '';            

            if($pg['visible'] == '0')
            {
                $append = ' [Inactief voor klant]';
            }

            $opts .= '<option value=' . $pg['portal_id'] .'>' .  $pg['name'] . $append . '</option>';
                                  
        }
        
        if($return)
        {
            return $opts;
        }

        print $opts;


    }
    
    public function updateSamplePG()
    {
        $this->render = False; 

        $sample = new Sample;
        $sample->where('id', $_POST['sample_id']);
        $sample = $sample->first();        
        

        $this->ProductGroup->where('client_id', $sample['client']);
        $groups = $this->ProductGroup->search();
        
        $sForm = new formFactory($this->_controller);
        $sForm->setId('updatePgForm');
        $sForm->action('#');
        $sForm->method('POST');
        $sForm->addClass('');
        $sForm->setTemplate('generic');

        $options = [];

        foreach($groups as $pg)
        {

            $append  = '';            

            if($pg['visible'] == '0')
            {
                $append = ' [Inactief voor klant]';
            }
            
            $options[$pg['portal_id']] = $pg['name'] . $append; 
        }
        
        $sForm->addDropdownField('update_portal_product_group_id', 'Productgroep', 'input-block-level', $sample['portal_product_group_id'], $options, False);
        
        $form = $sForm->render();

        print($form);

        
    }



}