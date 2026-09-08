<?PHP

class syncModule {
    
    private $_mesaCustomers = array();
    private $_aaCustomers = array();
    private $_mesaSubcustomers = array();
    private $_aaSubcustomers = array();
            
    //for making the hashes
    private $_mesaCustomerFields = array();
    private $_aaCustomerFields = array();          
    private $_mesaSubcustomerFields = array();
    private $_aaSubcustomerFields = array();    
    
    //linking
    private $_mesaIdent = 'id';
    private $_mesaAALink = 'aa_id';    
    private $_aaIdent = 'customer_id';
    private $_aaMesaLink = 'mesa_id';
    
    //grepping
    private $_aaGrepPattern = '';
    private $_aaGrepArray = array();
    private $_mesaGrepPattern = '';
    private $_mesaGrepArray = array();
    
    private $_mesaSubgrepPattern = '';
    private $_mesaSubgrepArray = array();
    private $_aaSubgrepPattern = '';
    private $_aaSubgrepArray = array();
 
    //stats
    private $_custSame = 0;
    private $_custEditMesa = 0;
    private $_custEditAA = 0;
    private $_custAddMesa = 0;
    private $_custAddAA = 0;
    private $_subcustSame = 0;
    private $_subcustEditMesa = 0;
    private $_subcustEditAA = 0;
    private $_subcustAddMesa = 0;
    private $_subcustAddAA = 0;
    private $_custIssues = 0;
    private $_subcustIssues = 0;
    
    private $_custRemMesa = 0;
    private $_custRemAA = 0;
    private $_subcustRemMesa = 0;
    private $_subcustRemAA = 0;
    
    
    //db's
    private $_aaDb = null;
    private $_mesaDb = null;
    
    //sync up
    private $_syncInstruction = array();
    
    //sync sql
    private $_AACreate = "INSERT INTO `customers` (  `customer_id`, `customer_ref`, `company_name`, `company_adres`, `company_postal`, `company_place`, `company_country`, `company_tel`, `company_fax`, `company_email`, `contact_fname`, `contact_lname`, `contact_title`, `contact_email`, `contact_tel`, `mesa_id`, `last_edit`) VALUES (:mesa_id, :customer_ref, :company_name, :company_adres, :company_postal, :company_place, :company_country, :company_tel, :company_fax, :company_email, :contact_fname, :contact_lname, :contact_title, :contact_email, :contact_tel, :mesa_id, :last_edit );";
    private $_AAUpdate = "UPDATE `customers` SET `customer_ref` = :customer_ref, `company_name` = :company_name, `company_adres` = :company_adres, `company_postal` = :company_postal, `company_place` = :company_place, `company_country` = :company_country, `company_tel` = :company_tel, `company_fax` = :company_fax, `company_email` = :company_email, `contact_fname` = :contact_fname, `contact_lname` = :contact_lname, `contact_title` = :contact_title, `contact_email` = :contact_email, `contact_tel` = :contact_tel, last_edit = :last_edit WHERE `mesa_id` = :mesa_id;";
    private $_AASubcreate = "INSERT INTO `customers_subloc` ( `id`, `cid`, `loc_name`, `loc_adres`, `loc_postal`, `loc_place`, `loc_country`, `loc_phone`, `loc_fax`, `loc_email`, `loc_raploc`, `loc_raptitle`, `loc_rapfun`, `loc_rapfname`, `loc_raplname`, `loc_rapadres`, `loc_rappostal`, `loc_rapplace`, `loc_rapcountry`, `mesa_id`, `last_edit`) VALUES(:mesa_id, :cid, :loc_name, :loc_adres,:loc_postal,:loc_place,:loc_country,:loc_phone,:loc_fax,:loc_email,:loc_raploc,:loc_raptitle,:loc_rapfun,:loc_rapfname,:loc_raplname,:loc_rapadres,:loc_rappostal,:loc_rapplace,:loc_rapcountry, :mesa_id, :last_edit);";
    private $_AASubupdate = "UPDATE `customers_subloc` SET `cid` = :cid, `loc_name` = :loc_name, `loc_adres` = :loc_adres, `loc_postal` = :loc_postal, `loc_place` = :loc_place, `loc_country` = :loc_country, `loc_phone` = :loc_phone, `loc_fax` = :loc_fax, `loc_email` = :loc_email, `loc_raploc` = :loc_raploc, `loc_raptitle` = :loc_raptitle, `loc_rapfun` = :loc_rapfun, `loc_rapfname` = :loc_rapfname, `loc_raplname` = :loc_raplname, `loc_rapadres` = :loc_rapadres, `loc_rappostal` = :loc_rappostal, `loc_rapplace` = :loc_rapplace, `loc_rapcountry` = :loc_rapcountry, `last_edit` = :last_edit WHERE `mesa_id` = :mesa_id;"; 

    private $_mesaCreate = "INSERT INTO `clients` ( `id`, `reference`, `name`, `adres`, `postal`, `place`, `country`, `telephone`, `fax`, `email`, `contact_name`, `contact_surname`, `contact_title`, `contact_email`, `contact_tel`, `aa_id`, `last_edit`) VALUES ( :aa_id, :reference,:name,:adres,:postal,:place,:country,:telephone,:fax,:email,:contact_name,:contact_surname,:contact_title,:contact_email,:contact_tel, :aa_id, :last_edit);";
    private $_mesaUpdate = "UPDATE `clients` SET `reference` = :reference, `name` = :name, `adres` = :adres, `postal` = :postal, `place` = :place, `country` = :country, `telephone` = :telephone, `fax` = :fax, `email` = :email, `contact_name` = :contact_name, `contact_surname` = :contact_surname, `contact_title` = :contact_title, `contact_email` = :contact_email, `contact_tel` = :contact_tel, `last_edit` = :last_edit WHERE `aa_id` = :aa_id  ";
    private $_mesaSubcreate = "INSERT INTO `subclients` ( `id`, `client`, `loc_name`, `loc_adres`, `loc_postal`, `loc_place`, `loc_country`, `loc_phone`, `loc_fax`, `loc_email`, `loc_raploc`, `loc_raptitle`, `loc_rapfun`, `loc_rapfname`, `loc_raplname`, `loc_rapadres`, `loc_rappostal`, `loc_rapplace`, `loc_rapcountry`, `aa_id`, `last_edit`) VALUES (  :aa_id, :client,:loc_name,:loc_adres,:loc_postal,:loc_place,:loc_country,:loc_phone,:loc_fax,:loc_email,:loc_raploc,:loc_raptitle,:loc_rapfun,:loc_rapfname,:loc_raplname,:loc_rapadres,:loc_rappostal,:loc_rapplace,:loc_rapcountry, :aa_id, :last_edit);";
    private $_mesaSubupdate = "UPDATE `subclients` SET  `client` = :client, `loc_name` = :loc_name, `loc_adres` = :loc_adres, `loc_postal` = :loc_postal, `loc_place` = :loc_place, `loc_country` = :loc_country, `loc_phone` = :loc_phone, `loc_fax` = :loc_fax, `loc_email` = :loc_email, `loc_raploc` = :loc_raploc, `loc_raptitle` = :loc_raptitle, `loc_rapfun` = :loc_rapfun, `loc_rapfname` = :loc_rapfname, `loc_raplname` = :loc_raplname, `loc_rapadres` = :loc_rapadres, `loc_rappostal` = :loc_rappostal, `loc_rapplace` = :loc_rapplace, `loc_rapcountry` = :loc_rapcountry,  `last_edit` = :last_edit WHERE `aa_id` = :aa_id ";
    
    //needs id update for both sides
    private $_mesaCustID = "UPDATE `clients` SET `aa_id` = :aa_id WHERE `id` = :mesa_id";
    private $_mesaSubcustID = "UPDATE `subclients` SET `aa_id` = :aa_id WHERE `id` = :mesa_id";
    private $_aaCustId = "UPDATE `customers` SET `mesa_id` = :mesa_id WHERE `customer_id` = :aa_id";
    private $_aaSubcustId = "UPDATE `customers_subloc` SET `mesa_id` = :mesa_id WHERE `id` = :aa_id"; 
    
    //syn instructions
    const SYNC_SAME = 'SYNC_SAME';
    const SYNC_ISSUE = 'SYNC_ISSUE';
    const SYNC_TO_AA = 'SYNC_TO_AA';
    const SYNC_TO_MESA = 'SYNC_TO_MESA';    
    const SYNC_CUSTOMER = 'SYNC_CUSTOMER';
    const SYNC_SUBCUSTOMER = 'SYNC_SUBCUSTOMER';
    const SYNC_DELETE_AA = 'SYNC_DELETE_AA';
    const SYNC_DELETE_MESA = 'SYNC_DELETE_MESA';
        
    function __construct() {     
        
                
        $this->_aaCustomerFields[1] = 'customer_ref';
        $this->_aaCustomerFields[2] = 'company_name';
        $this->_aaCustomerFields[3] = 'company_adres';
        $this->_aaCustomerFields[4] = 'company_postal';
        $this->_aaCustomerFields[5] = 'company_place';
        $this->_aaCustomerFields[6] = 'company_country';
        $this->_aaCustomerFields[7] = 'company_tel';
        $this->_aaCustomerFields[8] = 'company_fax';
        $this->_aaCustomerFields[9] = 'company_email';        
        $this->_aaCustomerFields[10] = 'contact_fname';
        $this->_aaCustomerFields[11] = 'contact_lname';
        $this->_aaCustomerFields[12] = 'contact_title';
        $this->_aaCustomerFields[13] = 'contact_email';
        $this->_aaCustomerFields[14] = 'contact_tel';        
        
        foreach($this->_aaCustomerFields as $fieldId => $field){            
            $this->_aaGrepPattern .= "{" . $field . "}";
            //array_push($this->_aaGrepArray, "/{" . $field . "}/");  
            //$this->_aaGrepArray[$fieldId] = "/{" . $field . "}/";  
            $this->_aaGrepArray[$fieldId] =  $field;  
        }
      
        $this->_mesaCustomerFields[1] = 'reference';
        $this->_mesaCustomerFields[2] = 'name';
        $this->_mesaCustomerFields[3] = 'adres';
        $this->_mesaCustomerFields[4] = 'postal';
        $this->_mesaCustomerFields[5] = 'place';
        $this->_mesaCustomerFields[6] = 'country';
        $this->_mesaCustomerFields[7] = 'telephone';
        $this->_mesaCustomerFields[8] = 'fax';
        $this->_mesaCustomerFields[9] = 'email';
        $this->_mesaCustomerFields[10] = 'contact_name';
        $this->_mesaCustomerFields[11] = 'contact_surname';
        $this->_mesaCustomerFields[12] = 'contact_title';
        $this->_mesaCustomerFields[13] = 'contact_email';
        $this->_mesaCustomerFields[14] = 'contact_tel';  
        
        foreach($this->_mesaCustomerFields as $fieldId => $field){            

            $this->_mesaGrepPattern .= "{" . $field . "}";
            //array_push($this->_mesaGrepArray, "/{" . $field . "}/");                        
             $this->_mesaGrepArray[$fieldId] =  $field;  
        }        
        
        //$this->_aaCustomerFields[0] = 'customer_id';
        //$this->_mesaCustomerFields[15] = 'mesa_id';
        
        
        $this->_mesaSubcustomerFields[1] = 'client';
        $this->_mesaSubcustomerFields[2] = 'loc_name';
        $this->_mesaSubcustomerFields[3] = 'loc_adres';
        $this->_mesaSubcustomerFields[4] = 'loc_postal';
        $this->_mesaSubcustomerFields[5] = 'loc_place';
        $this->_mesaSubcustomerFields[6] = 'loc_country';
        $this->_mesaSubcustomerFields[7] = 'loc_phone';
        $this->_mesaSubcustomerFields[8] = 'loc_fax';
        $this->_mesaSubcustomerFields[9] = 'loc_email';
        $this->_mesaSubcustomerFields[10] = 'loc_raploc';
        $this->_mesaSubcustomerFields[11] = 'loc_raptitle';        
        $this->_mesaSubcustomerFields[12] = 'loc_rapfun';
        $this->_mesaSubcustomerFields[13] = 'loc_rapfname';
        $this->_mesaSubcustomerFields[14] = 'loc_raplname';
        $this->_mesaSubcustomerFields[15] = 'loc_rapadres';
        $this->_mesaSubcustomerFields[16] = 'loc_rappostal';
        $this->_mesaSubcustomerFields[17] = 'loc_rapplace';
        $this->_mesaSubcustomerFields[18] = 'loc_rapcountry';
                
        foreach($this->_mesaSubcustomerFields as $fieldId => $field){                     
            $this->_mesaSubgrepPattern .= "{" . $field . "}";
            //array_push($this->_mesaSubgrepArray, "/{" . $field . "}/");                        
            $this->_mesaSubgrepArray[$fieldId] =  $field;  
        }        

        $this->_aaSubcustomerFields[1] = 'cid';
        $this->_aaSubcustomerFields[2] = 'loc_name';
        $this->_aaSubcustomerFields[3] = 'loc_adres';
        $this->_aaSubcustomerFields[4] = 'loc_postal';
        $this->_aaSubcustomerFields[5] = 'loc_place';
        $this->_aaSubcustomerFields[6] = 'loc_country';
        $this->_aaSubcustomerFields[7] = 'loc_phone';
        $this->_aaSubcustomerFields[8] = 'loc_fax';
        $this->_aaSubcustomerFields[9] = 'loc_email';
        $this->_aaSubcustomerFields[10] = 'loc_raploc';
        $this->_aaSubcustomerFields[11] = 'loc_raptitle';        
        $this->_aaSubcustomerFields[12] = 'loc_rapfun';
        $this->_aaSubcustomerFields[13] = 'loc_rapfname';
        $this->_aaSubcustomerFields[14] = 'loc_raplname';
        $this->_aaSubcustomerFields[15] = 'loc_rapadres';
        $this->_aaSubcustomerFields[16] = 'loc_rappostal';
        $this->_aaSubcustomerFields[17] = 'loc_rapplace';
        $this->_aaSubcustomerFields[18] = 'loc_rapcountry';
        
        foreach($this->_aaSubcustomerFields as $fieldId => $field){   
          
            $this->_aaSubgrepPattern .= "{" . $field . "}";
            //array_push($this->_aaSubgrepArray, "/{" . $field . "}/");                        
            $this->_aaSubgrepArray[$fieldId] =  $field;  
        }  
 
        $this->_aaDb = new PDO('mysql:host=' . AA_DB_HOST . ';dbname=' . AA_DB_NAME . ';charset=utf8;', AA_DB_USER, AA_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $this->_aaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     
        
        $this->_mesaDb = new PDO('mysql:host=' . ALPC_DB_HOST . ';dbname=' . ALPC_DB_NAME . ';charset=utf8;', ALPC_DB_USER, ALPC_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $this->_mesaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     
    }
          
    
    function silentSync($tool = False){
           
        //get both tables        
        $queryA = 'SELECT * FROM `clients`';
        $queryB = 'SELECT * FROM `customers`';
        
        $queryA2 = 'SELECT * FROM `subclients`';
        $queryB2 = 'SELECT * FROM `customers_subloc`';
        
        try {          
            $sth = $this->_mesaDb->prepare($queryA, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultA = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();                                       
            
            $sth = $this->_mesaDb->prepare($queryA2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultA2 = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();                                       
            
            $sth = $this->_aaDb->prepare($queryB, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultB = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();   
            
            $sth = $this->_aaDb->prepare($queryB2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultB2 = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();   
            
        } catch (PDOException $e) {                                             
        }

       
        $this->createArrayOnId($queryResultA, $this->_mesaIdent, $this->_mesaCustomers);
        $this->createArrayOnId($queryResultB, $this->_aaMesaLink, $this->_aaCustomers);
        
        $this->createArrayOnId($queryResultA2, $this->_mesaIdent, $this->_mesaSubcustomers);
        $this->createArrayOnId($queryResultB2, $this->_aaMesaLink, $this->_aaSubcustomers);
      
        $i = 0;
        foreach($this->_mesaCustomers as $mesaCustomer){
            
            if(array_key_exists($mesaCustomer['id'], $this->_aaCustomers)){            
                $aHash = $this->md5signature($mesaCustomer, 'MESA');                
                $bHash = $this->md5signature($this->_aaCustomers[$mesaCustomer['id']], 'AA');
                                
                if($aHash == $bHash){       
                    $this->_custSame++;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_SAME;  
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                    $this->_syncInstruction[$i]['client_info'] = $mesaCustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $mesaCustomer['id'];
                    $this->_syncInstruction[$i]['aa_id'] = $mesaCustomer['aa_id'];
                } else{
                    
                   //last_edit
                    if(abs($mesaCustomer['last_edit'] - $this->_aaCustomers[$mesaCustomer['id']]['last_edit']) > MESA_SYNC_PROXIMITY ){
                        
                        if($this->_aaCustomers[$mesaCustomer['id']]['last_edit'] > $mesaCustomer['last_edit']){
                            $this->_custEditMesa++;
                            $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_MESA;    
                            $this->_syncInstruction[$i]['client_info'] = $this->_aaCustomers[$mesaCustomer['id']];
                        } else{                            
                            $this->_custEditAA++;
                            $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_AA;    
                            $this->_syncInstruction[$i]['client_info'] = $mesaCustomer;
                        }
                        
                        $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                        $this->_syncInstruction[$i]['mesa_id'] = $mesaCustomer['id'];
                        $this->_syncInstruction[$i]['aa_id'] = $mesaCustomer['aa_id'];
                        
                    } else{                    
                        $this->_custIssues++;
                        $this->_syncInstruction[$i]['instruction'] = self::SYNC_ISSUE;    
                        $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                        $this->_syncInstruction[$i]['client_info'] = $mesaCustomer;
                        $this->_syncInstruction[$i]['client_info_b'] = $this->_aaCustomers[$mesaCustomer['id']];
                        
                        $this->_syncInstruction[$i]['mesa_id'] = $mesaCustomer['id'];
                        $this->_syncInstruction[$i]['aa_id'] = $mesaCustomer['aa_id'];
                    }                   
                }                
            } 
            
            else{
                
                
                
                //was this previously linked, if so, it means it must have been deleted from AA
                if($mesaCustomer['aa_id'] != '' || $mesaCustomer['aa_id'] != 0 ){
                    $this->_custRemMesa++;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_DELETE_MESA;    
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                    $this->_syncInstruction[$i]['client_info'] = $mesaCustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $mesaCustomer['id'];
                    $this->_syncInstruction[$i]['aa_id'] = $mesaCustomer['aa_id'];            
                } 
                
                //else, we assume it was  new one created at mesa level
                else{
                    $this->_custAddAA++;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_AA;    
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                    $this->_syncInstruction[$i]['client_info'] = $mesaCustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $mesaCustomer['id'];
                    $this->_syncInstruction[$i]['aa_id'] = NULL;
                }                                
            }    
            
            $i++;
        }  
        
        foreach($this->_aaCustomers as $aaCustomer){
            //check for new entrys into the AA database
            if($aaCustomer['mesa_id'] == '' || $aaCustomer['mesa_id'] == 0){
                $this->_custAddMesa++;
                $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_MESA;    
                $this->_syncInstruction[$i]['client_info'] = $aaCustomer;
                $this->_syncInstruction[$i]['mesa_id'] = NULL;
                $this->_syncInstruction[$i]['aa_id'] = $aaCustomer['customer_id'];
            } 
            //but also check if the customers are still in mesa, if not, delete them from AA
            else{
                if(!array_key_exists($aaCustomer['mesa_id'], $this->_mesaCustomers)){
                    $this->_custRemAA++;
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_CUSTOMER;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_DELETE_AA;    
                    $this->_syncInstruction[$i]['client_info'] = $aaCustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $aaCustomer['mesa_id'];
                    $this->_syncInstruction[$i]['aa_id'] = $aaCustomer['customer_id'];
                }
            }
            
            $i++;
        }
            
       
        
        //subcustomers       
        foreach($this->_mesaSubcustomers as $mesaSubcustomer){
            
            if(array_key_exists($mesaSubcustomer['id'], $this->_aaSubcustomers)){            
                $aHash = $this->md5signature($mesaSubcustomer, 'MESA', True);                
                $bHash = $this->md5signature($this->_aaSubcustomers[$mesaSubcustomer['id']], 'AA', True);
                                
                if($aHash == $bHash){                  
                    $this->_subcustSame++;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_SAME;  
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                    $this->_syncInstruction[$i]['client_info'] = $mesaSubcustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $mesaSubcustomer['id'];
                    $this->_syncInstruction[$i]['aa_id'] = $mesaSubcustomer['aa_id'];
                } else{
                    
                    
                   //last_edit
                    if(abs($mesaSubcustomer['last_edit'] - $this->_aaSubcustomers[$mesaSubcustomer['id']]['last_edit']) > MESA_SYNC_PROXIMITY ){
                        
                        if($this->_aaSubcustomers[$mesaSubcustomer['id']]['last_edit'] > $mesaSubcustomer['last_edit']){
                            $this->_subcustEditMesa++;
                            $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_MESA;    
                            $this->_syncInstruction[$i]['client_info'] = $this->_aaSubcustomers[$mesaSubcustomer['id']];
                        } else{                       
                            $this->_subcustEditAA++;
                            $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_AA;    
                            $this->_syncInstruction[$i]['client_info'] = $mesaSubcustomer;
                        }
                        
                        $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                        $this->_syncInstruction[$i]['mesa_id'] = $mesaSubcustomer['id'];
                        $this->_syncInstruction[$i]['aa_id'] = $mesaSubcustomer['aa_id'];
                        
                    } else{         
                        $this->_subcustIssues++;
                        $this->_syncInstruction[$i]['instruction'] = self::SYNC_ISSUE;    
                        $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                        $this->_syncInstruction[$i]['client_info'] = $mesaSubcustomer;
                        $this->_syncInstruction[$i]['client_info_b'] = $this->_aaSubcustomers[$mesaSubcustomer['id']];
                        $this->_syncInstruction[$i]['mesa_id'] = $mesaSubcustomer['id'];
                        $this->_syncInstruction[$i]['aa_id'] = $mesaSubcustomer['aa_id'];                        
                    }                   
                }                
            } 
            
            else{
                
                 if($mesaSubcustomer['aa_id'] != '' && $mesaSubcustomer['aa_id'] != 0  ){    
                    $this->_subcustRemMesa++;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_DELETE_MESA;    
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                    $this->_syncInstruction[$i]['client_info'] = $mesaSubcustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $mesaSubcustomer['id'];
                    $this->_syncInstruction[$i]['aa_id'] = $mesaSubcustomer['aa_id']; 
                     
                 }else{
                    $this->_subcustAddAA++;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_AA;    
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                    $this->_syncInstruction[$i]['client_info'] = $mesaSubcustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = $mesaSubcustomer['id'];
                    $this->_syncInstruction[$i]['aa_id'] = NULL; 
                 }                               
            }                
            $i++;
        }  
        
        foreach($this->_aaSubcustomers as $aaSubcustomer){
            //check for new entrys into the AA database
            if($aaSubcustomer['mesa_id'] == '' || $aaSubcustomer['mesa_id'] == 0){
                $this->_subcustAddMesa++;
                $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                $this->_syncInstruction[$i]['instruction'] = self::SYNC_TO_MESA;    
                $this->_syncInstruction[$i]['client_info'] = $aaSubcustomer;
                $this->_syncInstruction[$i]['mesa_id'] = NULL;
                $this->_syncInstruction[$i]['aa_id'] = $aaSubcustomer['id'];
            }     
            
            else{
                if(!array_key_exists($aaSubcustomer['mesa_id'], $this->_mesaSubcustomers)){
                    $this->_subcustRemAA++;
                    $this->_syncInstruction[$i]['sync_type'] = self::SYNC_SUBCUSTOMER;
                    $this->_syncInstruction[$i]['instruction'] = self::SYNC_DELETE_AA;    
                    $this->_syncInstruction[$i]['client_info'] = $aaSubcustomer;
                    $this->_syncInstruction[$i]['mesa_id'] = NULL;
                    $this->_syncInstruction[$i]['aa_id'] = $aaSubcustomer['id'];
                }
            }            
            $i++;
        }          
        
       /* print 'cust same: ' . $this->_custSame . '<br />';
        print '_custEditMesa: ' .$this->_custEditMesa . '<br />';
        print '_custEditAA: ' .$this->_custEditAA . '<br />';
        print '_custAddMesa: ' .$this->_custAddMesa . '<br />';
        print 'custAddAA: ' . $this->_custAddAA . '<br />';

        print '_subcustSame: ' .$this->_subcustSame . '<br />';
        print '_subcustEditMesa: ' .$this->_subcustEditMesa . '<br />';
        print '_subcustEditAA: ' .$this->_subcustEditAA . '<br />';
        print '_subcustAddMesa: ' .$this->_subcustAddMesa. '<br />';
        print '_subcustAddAA: ' .$this->_subcustAddAA . '<br />';

        print '_custIssues: ' .$this->_custIssues . '<br />';
        print '_subcustIssues: ' .$this->_subcustIssues . '<br />';
        
        print '_subcustRemAA: ' .$this->_subcustRemAA . '<br />';
        print '_subcustRemMesa: ' .$this->_subcustRemMesa . '<br />';
        print '_custRemAA: ' .$this->_custRemAA . '<br />';
        print '_custRemMesa: ' .$this->_custRemMesa . '<br />';*/

       $this->_runSyncFromArray($this->_syncInstruction, $tool);
       
       if($tool == True){
         return $this->_renderConflictSolutions();
       }
       
    }
    
    private function _renderClient($client, $type, $sub = False){
                
        $clientRender = '';
        
        if($sub == self::SYNC_CUSTOMER){                        
            if($type == 'AA'){                
                $clientRender .= $client['company_name'] . '(' . $client['customer_ref'] . ')<br />';                
                $clientRender .= $client['company_adres'] . '<br />';    
                $clientRender .= $client['company_postal'] . ' ' . $client['company_place'] . '<br />';    
                $clientRender .= $client['company_country'] . '<br />';    
                $clientRender .= $client['company_tel'] . ' - ' . $client['company_fax'] . '<br />';    
                $clientRender .= $client['company_email'] . '<br />';  
                $clientRender .= $client['contact_title'] . ' ' . $client['contact_fname'] . ' ' . $client['contact_lname'] . '<br />';  
                $clientRender .= $client['contact_email'] . ' - ' . $client['contact_tel'] . '<br />';    
            } else{
                $clientRender .= $client['name'] . '(' . $client['reference'] . ')<br />';                
                $clientRender .= $client['adres'] . '<br />';    
                $clientRender .= $client['postal'] . ' ' . $client['place'] . '<br />';    
                $clientRender .= $client['country'] . '<br />';    
                $clientRender .= $client['telephone'] . ' - ' . $client['fax'] . '<br />';    
                $clientRender .= $client['email'] . '<br />';  
                $clientRender .= $client['contact_title'] . ' ' . $client['contact_name'] . ' ' . $client['contact_surname'] . '<br />';  
                $clientRender .= $client['contact_email'] . ' - ' . $client['contact_tel'] . '<br />';            
            }                        
        }
        
        else{       
            $clientRender .= $client['loc_name'] . '<br />';  
            $clientRender .= $client['loc_adres'] . '<br />';        
            $clientRender .= $client['loc_postal'] . ' ' . $client['loc_place'] . '<br />';        
            $clientRender .= $client['loc_country'] . '<br />'; 
            $clientRender .= $client['loc_phone'] . ' - ' . $client['loc_fax'] . '<br />';     
            $clientRender .= $client['loc_email'] . '<br /><br />'; 
            $clientRender .= $client['loc_raploc'] . '<br />';             
            $clientRender .= $client['loc_raptitle'] . ' ' . $client['loc_rapfname'] . ' ' . $client['loc_raplname'] . '<br />';   
            $clientRender .= $client['loc_rapadres'] . '<br />'; 
            $clientRender .= $client['loc_rappostal'] . ' ' . $client['loc_rapplace'] . '<br />';    
            $clientRender .= $client['loc_rapcountry'] . '<br />';                   
        }
        
        if(!empty($client['last_edit'])){
            $lastEdit = date('d-m-Y H:m', $client['last_edit']);
        } else{
            $lastEdit = '{MESA_SYN_UNKNOWNDATE}';
        }
        
        $clientRender .= '<small> <i class="icon-time"></i> {MESA_SYN_LASTEDIT}: ' . $lastEdit . '</small.';       
        return $clientRender;
    }
                   
    private function _renderConflictSolutions(){
        
        $retObj = array();
        $retObj['customers_same'] = $this->_custSame;
        $retObj['customers_synced'] = $this->_custEditMesa + $this->_custEditAA;
        $retObj['customers_added_mesa'] = $this->_custAddMesa;
        $retObj['customers_added_aa'] = $this->_custAddAA;
        
        $retObj['subCustomers_same'] = $this->_subcustSame;
        $retObj['subCustomers_synced'] = $this->_subcustEditMesa + $this->_subcustEditAA;
        $retObj['subCustomers_added_mesa'] = $this->_subcustAddMesa;
        $retObj['subCustomers_added_aa'] = $this->_subcustAddAA;
        
        $i = 0;
        $tblRender = array();
        
        foreach($this->_syncInstruction as $so){
            
            if($so['instruction'] == 'SYNC_ISSUE'){                        
                $tblRender[$i]['a'] = $this->_renderClient($so['client_info'], 'MESA', $so['sync_type']);
                $tblRender[$i]['b'] = $this->_renderClient($so['client_info_b'], 'AA', $so['sync_type']);
                
                $tblRender[$i]['aa_id']  = $so['aa_id'];
                $tblRender[$i]['mesa_id']  = $so['mesa_id'];
                $tblRender[$i]['type'] = $so['sync_type'];
                $tblRender[$i]['id'] = $i;
                
                $i++;                                        
            }
                        
        }
        
        $retObj['number_of_conflicts'] = $i;
        
        $tF = new tableFactory();
        $tF->loadTemplate('syncIssueTable');
        
        if(empty($tblRender)){
            $tblRender = '{MESA_SYN_NOCONFLICTS}';
        }
        
        $tF->loadValues($tblRender);
        
        $retObj['issue_table'] = $tF->renderTable();
        return $retObj;
                
    }
    
    
    public function runSyncCommand($aaId, $mesaId, $syncType, $direction){
        
        // fetch A & B 
        //setup array for SO 
        
        if($syncType == self::SYNC_CUSTOMER){
            
            print 'customer';
            
            if($direction == 'toMesa'){
                
                print 'toMesa';
                //need to fetch AA info
                $instruction = self::SYNC_TO_MESA; 
                $query = 'SELECT * FROM `customers` WHERE `customer_id` = :id';
                $params = array('id' => $aaId);
                $sth = $this->_aaDb->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            } 
            
            elseif($direction == 'toAA'){
                print 'toAA';
                //need mesa info as carrier
                $instruction = self::SYNC_TO_AA; 
                $query = 'SELECT * FROM `clients` WHERE `id` = :id';
                $sth = $this->_mesaDb->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $params = array('id' => $mesaId);
            }
            
            else{
                //print 'error 1';
                die();
            }
           
        } 
        
        elseif ($syncType == self::SYNC_SUBCUSTOMER){
            print 'subcustomer';
            
            if($direction == 'toMesa'){
                print 'toMesa';
                //need to fetch AA info
                $instruction = self::SYNC_TO_MESA; 
                $query = 'SELECT * FROM `customers_subloc` WHERE `id` = :id';
                $sth = $this->_aaDb->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $params = array('id' => $aaId);
            } 
            
            elseif($direction == 'toAA'){
                print 'toAA';
                //need mesa info as carrier
                $instruction = self::SYNC_TO_AA; 
                $query = 'SELECT * FROM `subclients` WHERE `id` = :id';
                $sth = $this->_mesaDb->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $params = array('id' => $mesaId);
            }
            
            else{
                print 'error 2';
                die();
            }            
        } 
        
        else{  die(); }
        print $query;
        
        //get client info        
        $sth->execute($params);
        $queryResult = $sth->fetchAll(PDO::FETCH_ASSOC);            
        $sth->closeCursor();  
        
        parray($queryResult);
        
        if(!empty($queryResult)){
            $i = 0;
            $this->_syncInstruction[$i]['instruction'] = $instruction;    
            $this->_syncInstruction[$i]['sync_type'] = $syncType;
            $this->_syncInstruction[$i]['client_info'] = $queryResult[0];        
            $this->_syncInstruction[$i]['mesa_id'] = $mesaId;
            $this->_syncInstruction[$i]['aa_id'] = $aaId;
            
            parray($this->_syncInstruction);
            $this->_runSyncFromArray($this->_syncInstruction, False);
            
        } else{
            print 'error 3';
            die();
        }
                       
        
    }
    
        
    private function _runSyncFromArray($so, $tool = False){
       
        
        try {     
                  
            $this->_mesaDb->beginTransaction();
           
            $this->_aaDb->beginTransaction();
            
            foreach($so as $sync){
                
                $queryParams = array();

                if($sync['instruction'] == self::SYNC_SAME){
                    continue;
                }

                if($sync['instruction'] == self::SYNC_ISSUE){
                    continue;
                }
                
                //wont implement this as it might be necceasiry to ahve a asynchrnous client datbase in terms of deleting
                if($sync['instruction'] == self::SYNC_DELETE_AA){
                      continue;
                }
                
                if($sync['instruction'] == self::SYNC_DELETE_MESA){
                      continue;
                }      

                if($sync['instruction'] == self::SYNC_TO_AA){
                    
                 
                    
                    //update an existing aa id
                    if($sync['aa_id'] != NULL && $sync['aa_id'] != 0){
                    
                          if($sync['sync_type'] == self::SYNC_CUSTOMER){
                              
                            $sth = $this->_aaDb->prepare($this->_AAUpdate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                            
                            
                            foreach($this->_mesaCustomerFields as $custFieldId => $custFieldName){                                
                                $aaEquiv = $this->_aaCustomerFields[$custFieldId];
                                $queryParams[$aaEquiv] = $sync['client_info'][$custFieldName];                                                                
                            }
                            
                            $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                            $queryParams['mesa_id'] = $sync['mesa_id'];
                            
                            //parray($sync);
                            //parray($queryParams);
                            $sth->execute($queryParams);
                            $sth->closeCursor();
                            unset($sth);
                            unset($queryParams);
                                                            
                          }elseif($sync['sync_type'] == self::SYNC_SUBCUSTOMER){
                                                                                        
                            $sth = $this->_aaDb->prepare($this->_AASubcreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                            foreach($this->_mesaSubcustomerFields as $custFieldId => $custFieldName){                                
                                $aaEquiv = $this->_aaSubcustomerFields[$custFieldId];
                                $queryParams[$aaEquiv] = $sync['client_info'][$custFieldName];                                                                
                            }
                            
                            $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                            $queryParams['mesa_id'] = $sync['mesa_id'];
                            
                            //parray($sync);
                            //parray($queryParams);
                            $sth->execute($queryParams);
                            $sth->closeCursor();
                            unset($sth);
                            unset($queryParams);

                        }                           
                    } 
                    
                    //create a new one
                    //SYNC_TO_AA
                    else{
                        
                                                
                        if($sync['sync_type'] == self::SYNC_CUSTOMER){
                                                        
                            $sth = $this->_aaDb->prepare($this->_AACreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                            
                            foreach($this->_mesaCustomerFields as $custFieldId => $custFieldName){                                
                                $aaEquiv = $this->_aaCustomerFields[$custFieldId];
                                $queryParams[$aaEquiv] = $sync['client_info'][$custFieldName];                                                                
                            }
                            
                            $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                            $queryParams['mesa_id'] = $sync['mesa_id'];
                            
                            //parray($sync);
                            //parray($queryParams);
                            $sth->execute($queryParams);
                            $sth->closeCursor();
                            unset($sth);
                            unset($queryParams);
                            
                            $sti = $this->_mesaDb->prepare($this->_mesaCustID, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $queryParams['mesa_id'] = $sync['mesa_id'];
                            $queryParams['aa_id'] = $this->_aaDb->lastInsertId();                            
                            $sti->execute($queryParams);
                            $sti->closeCursor();
                            unset($sti);
                            unset($queryParams);
                            
                       
                        }elseif($sync['sync_type'] == self::SYNC_SUBCUSTOMER){

                            $sth = $this->_aaDb->prepare($this->_AASubcreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                            //print $this->_AASubcreate;
                            //parray($this->_mesaSubcustomerFields);
                            
                            foreach($this->_mesaSubcustomerFields as $custFieldId => $custFieldName){                                
                                $aaEquiv = $this->_aaSubcustomerFields[$custFieldId];
                                $queryParams[$aaEquiv] = $sync['client_info'][$custFieldName];                                                                
                            }
                            
                            $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                            $queryParams['mesa_id'] = $sync['mesa_id'];
                            
                            //parray($sync);
                            //parray($queryParams);
                            $sth->execute($queryParams);
                            $sth->closeCursor();
                            unset($sth);
                            unset($queryParams);      
                            
                            $sti = $this->_mesaDb->prepare($this->_mesaSubcustID, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            $queryParams['mesa_id'] = $sync['mesa_id'];
                            $queryParams['aa_id'] = $this->_aaDb->lastInsertId();                            
                            $sti->execute($queryParams);
                            $sti->closeCursor();
                            unset($sti);
                            unset($queryParams);
                            
                            
                        }                         
                    }                                                          
                }

                if($sync['instruction'] == self::SYNC_TO_MESA){
                    
                       // parray($sync);
                                      
                       if($sync['mesa_id'] != NULL or $sync['mesa_id'] != 0){                           
                            if($sync['sync_type'] == self::SYNC_CUSTOMER){ 
                        
                                $sth = $this->_mesaDb->prepare($this->_mesaUpdate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                                foreach($this->_aaCustomerFields as $custFieldId => $custFieldName){                                
                                    $mesaEquiv = $this->_mesaCustomerFields[$custFieldId];
                                    $queryParams[$mesaEquiv] = $sync['client_info'][$custFieldName];                                                                
                                }

                                $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                                $queryParams['aa_id'] = $sync['aa_id'];

                                //parray($sync);
                                //parray($queryParams);
                                $sth->execute($queryParams);
                                $sth->closeCursor();
                                unset($sth);
                                unset($queryParams);
                                
                                
                            }
                            
                            elseif($sync['sync_type'] == self::SYNC_SUBCUSTOMER){
                              
                                
                                
                                $sth = $this->_mesaDb->prepare($this->_mesaSubupdate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                                foreach($this->_aaSubcustomerFields as $custFieldId => $custFieldName){                                
                                    $mesaEquiv = $this->_mesaSubcustomerFields[$custFieldId];
                                    $queryParams[$mesaEquiv] = $sync['client_info'][$custFieldName];                                                                
                                }

                                $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                                $queryParams['aa_id'] = $sync['aa_id'];

                                parray($sync);
                                parray($queryParams);
                                $sth->execute($queryParams);
                                $sth->closeCursor();
                                unset($sth);
                                unset($queryParams);
                              
                            }                                        
                       }
                       
                       else{
                            if($sync['sync_type'] == self::SYNC_CUSTOMER){  
                                
                                $sth = $this->_mesaDb->prepare($this->_mesaCreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                                foreach($this->_aaCustomerFields as $custFieldId => $custFieldName){                                
                                    $mesaEquiv = $this->_mesaCustomerFields[$custFieldId];
                                    $queryParams[$mesaEquiv] = $sync['client_info'][$custFieldName];                                                                
                                }

                                $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                                $queryParams['aa_id'] = $sync['aa_id'];

                                //parray($sync);
                                //parray($queryParams);
                                $sth->execute($queryParams);
                                $sth->closeCursor();
                                unset($sth);
                                unset($queryParams);
                                
                                $sti = $this->_aaDb->prepare($this->_aaCustId, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                                $queryParams['mesa_id'] = $this->_mesaDb->lastInsertId();
                                $queryParams['aa_id'] = $sync['aa_id'];                      
                                $sti->execute($queryParams);
                                $sti->closeCursor();
                                unset($sti);
                                unset($queryParams);
                                
                                
                            }elseif($sync['sync_type'] == self::SYNC_SUBCUSTOMER){
                                
                                $sth = $this->_mesaDb->prepare($this->_mesaSubcreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                            
                                foreach($this->_aaSubcustomerFields as $custFieldId => $custFieldName){                                
                                    $mesaEquiv = $this->_mesaSubcustomerFields[$custFieldId];
                                    $queryParams[$mesaEquiv] = $sync['client_info'][$custFieldName];                                                                
                                }

                                $queryParams['last_edit'] = $sync['client_info']['last_edit'];    
                                $queryParams['aa_id'] = $sync['aa_id'];

                                //parray($sync);
                                //parray($queryParams);
                                $sth->execute($queryParams);
                                $sth->closeCursor();
                                unset($sth);
                                unset($queryParams);
                                
                                $sti = $this->_aaDb->prepare($this->_aaSubcustId, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                                $queryParams['mesa_id'] = $this->_mesaDb->lastInsertId();
                                $queryParams['aa_id'] = $sync['aa_id'];                      
                                $sti->execute($queryParams);
                                $sti->closeCursor();
                                unset($sti);
                                unset($queryParams);
                                
                            }                                        
                       }                                        
                }                                                         
            }   
        
        $this->_mesaDb->commit();
        $this->_aaDb->commit();
        $success = True;
    
        } catch (PDOException $e) {       
            parray($e);
            $success = False;
            $this->_mesaDb->rollBack();
            $this->_aaDb->rollBack();
        }
        
        if($success == True){
            upa('cvars', 'cvarSaveName', array('MESA_SYNC_LAST_SYNC', time()), False);
        }
        
        if($tool == False){
            
            $retObj = array();
            $retObj['succes'] = $success;
            $retObj['subcust_issues'] = $this->_custIssues;
            $retObj['cust_issues'] = $this->_subcustIssues;
        
            print json_encode($retObj, JSON_FORCE_OBJECT);
            
        }
        
    }
                
    private function md5signature($customer, $type, $subloc = False){
       
        if($type == 'AA'){
            
            if($subloc == False){                
            
                $repArr = array();
                foreach($this->_aaGrepArray as $grepField){
                    $repArr[$grepField] = $customer[$grepField];
                }
                $custPile = strtr($this->_aaGrepPattern, $repArr);                                          
                //$custPile = preg_replace($this->_aaGrepArray, $customer, $this->_aaGrepPattern);      
              
            } elseif($subloc == True){
                
                $repArr = array();
                foreach($this->_aaSubgrepArray as $grepField){
                    $repArr[$grepField] = $customer[$grepField];
                }
                $custPile = strtr($this->_aaSubgrepPattern, $repArr);       
                
                //$custPile = preg_replace($this->_aaSubgrepArray, $customer, $this->_aaSubgrepPattern);      
            }                       
        }
        
        elseif($type == 'MESA'){
            if($subloc == False){
                
                $repArr = array();
                foreach($this->_mesaGrepArray as $grepField){
                    $repArr[$grepField] = $customer[$grepField];
                }
                
                $custPile = strtr($this->_mesaGrepPattern, $repArr);   
                
                //$custPile = preg_replace($this->_mesaGrepArray, $customer, $this->_mesaGrepPattern);                   
            } elseif($subloc == True){

                $repArr = array();
                foreach($this->_mesaSubgrepArray as $grepField){
                    $repArr[$grepField] = $customer[$grepField];
                }
                
                $custPile = strtr($this->_mesaSubgrepPattern, $repArr);  

                //$custPile = preg_replace($this->_mesaSubgrepArray, $customer, $this->_mesaSubgrepPattern);      
            }
        }
                   
        
        return hash('md5', $custPile);     
    }    
         
    private function createArrayOnId($arr, $index, &$target){       
        foreach($arr as $entry){
            $target[$entry[$index]] = $entry;
        }   
                
    }
    
    
    
    function cloneToMesa(){
     
        $clientSetId = 'UPDATE `customers` SET `mesa_id` = :mesa_id WHERE `customer_id` = :customer_id ';
        $subclientSetId = 'UPDATE `customers_subloc` SET `mesa_id` = :mesa_id WHERE `id` = :id ';
        
         try {                       
            $this->_mesaDb->beginTransaction();
            $this->_aaDb->beginTransaction();
            
            //$this->_mesaDb->exec('LOCK TABLES clients, subclients');
            //$this->_mesaDb->exec('LOCK TABLES customers, customers_subloc');
                         
            //clean tables
            $remSql = 'TRUNCATE `clients`';
            $sth = $this->_mesaDb->prepare($remSql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));                                                      
            $sth->execute();
            $sth->closeCursor();
            unset($sth);
            
            $subRemSql = 'TRUNCATE `subclients`';
            $sth = $this->_mesaDb->prepare($subRemSql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));                                                      
            $sth->execute();
            $sth->closeCursor();
            unset($sth);
            
            $queryB = 'SELECT * FROM `customers`';
            $queryB2 = 'SELECT * FROM `customers_subloc`';
            
            $sth = $this->_aaDb->prepare($queryB, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultB = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();   
            unset($sth);
            
            $sth = $this->_aaDb->prepare($queryB2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultB2 = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();   
            unset($sth);
            
            
            
            foreach($queryResultB as $customer){
                
                $sti = $this->_aaDb->prepare($clientSetId, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));                
                $sth = $this->_mesaDb->prepare($this->_mesaCreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); 
                
                foreach($this->_aaCustomerFields as $custFieldId => $custFieldName){                                
                    $mesaEquiv = $this->_mesaCustomerFields[$custFieldId];
                    $queryParams[$mesaEquiv] = $customer[$custFieldName];                                                                
                }
                
                $queryParams['aa_id'] = $customer['customer_id'];
                $queryParams['last_edit'] = $customer['last_edit'];
                
                
                $sth->execute($queryParams);                
                $sth->closeCursor();
                
                $mesaRid = $this->_mesaDb->lastInsertId();                                
                
                $sti->execute(array('mesa_id' => $mesaRid, 'customer_id' => $customer['customer_id']));
                
                unset($sth);
                unset($queryParams);
                unset($sti);
                
            }
            
            
            foreach($queryResultB2 as $subcustomer){
                
                $sti = $this->_aaDb->prepare($subclientSetId, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));        
                $sth = $this->_mesaDb->prepare($this->_mesaSubcreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); 
                
                foreach($this->_aaSubcustomerFields as $custFieldId => $custFieldName){                                
                    $mesaEquiv = $this->_mesaSubcustomerFields[$custFieldId];
                    $queryParams[$mesaEquiv] = $subcustomer[$custFieldName];                                                                
                }
                
                $queryParams['aa_id'] = $subcustomer['id'];
                $queryParams['last_edit'] = $subcustomer['last_edit'];
                                
                
                $sth->execute($queryParams);                                   
                $sth->closeCursor();
                $mesaRid = $this->_mesaDb->lastInsertId();      
                
                $sti->execute(array('mesa_id' => $mesaRid, 'id' => $subcustomer['id']));
                
                unset($sth);
                unset($sti);
                unset($queryParams);
                
            }
        
        $this->_mesaDb->commit();
        $this->_aaDb->commit();                  
                                
            
            
        } catch (PDOException $e) {     
            parray($e);
            $this->_mesaDb->rollBack();
            $this->_aaDb->rollBack();
        }
                
    }
    
    function cloneToAA(){
        
        $clientSetId = 'UPDATE `clients` SET `aa_id` = :aa_id WHERE `id` = :id ';
        $subclientSetId = 'UPDATE `subclients` SET `aa_id` = :aa_id WHERE `id` = :id ';
        
         try {                       
            $this->_mesaDb->beginTransaction();
            $this->_aaDb->beginTransaction();
            
            //$this->_mesaDb->exec('LOCK TABLES clients, subclients');
            //$this->_mesaDb->exec('LOCK TABLES customers, customers_subloc');
                         
            //clean tables
            $remSql = 'TRUNCATE `customers`';
            $sth = $this->_aaDb->prepare($remSql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));                                                      
            $sth->execute();
            $sth->closeCursor();
            unset($sth);
            
            $subRemSql = 'TRUNCATE `customers_subloc`';
            $sth = $this->_aaDb->prepare($subRemSql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));                                                      
            $sth->execute();
            $sth->closeCursor();
            unset($sth);
            
            $queryB = 'SELECT * FROM `clients`';
            $queryB2 = 'SELECT * FROM `subclients`';
            
            $sth = $this->_mesaDb->prepare($queryB, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultB = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();   
            unset($sth);
            
            $sth = $this->_mesaDb->prepare($queryB2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute(array());
            $queryResultB2 = $sth->fetchAll(PDO::FETCH_ASSOC);            
            $sth->closeCursor();   
            unset($sth);
                                    
            foreach($queryResultB as $customer){
                
                $sti = $this->_mesaDb->prepare($clientSetId, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));                
                $sth = $this->_aaDb->prepare($this->_AACreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); 
                
                foreach($this->_mesaCustomerFields as $custFieldId => $custFieldName){                                
                    $aaEquiv = $this->_aaCustomerFields[$custFieldId];
                    $queryParams[$aaEquiv] = $customer[$custFieldName];                                                                
                }
                
                $queryParams['mesa_id'] = $customer['id'];
                $queryParams['last_edit'] = $customer['last_edit'];
                                
                $sth->execute($queryParams);                
                $sth->closeCursor();
                
                $aaRid = $this->_aaDb->lastInsertId();                                
                
                $sti->execute(array('aa_id' => $aaRid, 'id' => $customer['id']));
                
                unset($sth);
                unset($queryParams);
                unset($sti);
                
            }
            
            
            foreach($queryResultB2 as $subcustomer){
                
                $sti = $this->_mesaDb->prepare($subclientSetId, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));        
                $sth = $this->_aaDb->prepare($this->_AASubcreate, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); 
                
                foreach($this->_mesaSubcustomerFields as $custFieldId => $custFieldName){                                
                    $aaEquiv = $this->_aaSubcustomerFields[$custFieldId];
                    $queryParams[$aaEquiv] = $subcustomer[$custFieldName];                                                                
                }
                
                $queryParams['mesa_id'] = $subcustomer['id'];
                $queryParams['last_edit'] = $subcustomer['last_edit'];
                                                
                $sth->execute($queryParams);                                   
                $sth->closeCursor();
                $aaRid = $this->_aaDb->lastInsertId();                      
                $sti->execute(array('aa_id' => $aaRid, 'id' => $subcustomer['id']));
                
                unset($sth);
                unset($sti);
                unset($queryParams);
                
            }
        
        $this->_mesaDb->commit();
        $this->_aaDb->commit();                  
                                
                        
        } catch (PDOException $e) {     
            parray($e);
            $this->_mesaDb->rollBack();
            $this->_aaDb->rollBack();
        }  
        
    }
    
    
}