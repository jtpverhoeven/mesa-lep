<?PHP

class groupPrivilegesController extends controller{


    protected $_publicActions = array( 'checkAllowed' => True );
    private $_authRules = array();
    private $_authNames = array();


    function beforeAction($queryString) {

        //1 is controller
        //2 is visual GUI
        $authRules = array();
        $authNames = array();

        //user administration
        $authNames['1'] = '{MESA_PRI_1}';
        $authNames['2'] = '{MESA_PRI_2}';
        $authNames['3'] = '{MESA_PRI_3}';

        //workflow setup
        $authNames['4'] = '{MESA_PRI_4}';
        $authNames['5'] = '{MESA_PRI_5}';
        $authNames['6'] = '{MESA_PRI_6}';
        $authNames['7'] = '{MESA_PRI_7}';

        //base configuration
        $authNames['8'] = '{MESA_PRI_8}';
        $authNames['9'] = '{MESA_PRI_9}';
        $authNames['10'] = '{MESA_PRI_10}';
        $authNames['11'] = '{MESA_PRI_11}';

        //labwork
        $authNames['12'] = '{MESA_PRI_12}';
        $authNames['13'] = '{MESA_PRI_13}';

        $authNames['14'] = '{MESA_PRI_14}';
        $authNames['15'] = '{MESA_PRI_15}';
        $authNames['16'] = '{MESA_PRI_16}';

        $authNames['17'] = '{MESA_PRI_17}';
        $authNames['18'] = '{MESA_PRI_18}';
        $authNames['19'] = '{MESA_PRI_19}';
        $authNames['20'] = '{MESA_PRI_20}';
        $authNames['21'] = '{MESA_PRI_21}';
        $authNames['22'] = '{MESA_PRI_22}';
        $authNames['23'] = '{MESA_PRI_23}';

        //Manage clients
        $authNames['24'] = '{MESA_PRI_24}';
        $authNames['25'] = '{MESA_PRI_25}';


        $authNames['26'] = '{MESA_PRI_26}';
        $authNames['27'] = '{MESA_PRI_27}';
        $authNames['28'] = '{MESA_PRI_28}';
        $authNames['29'] = '{MESA_PRI_29}';
        $authNames['30'] = '{MESA_PRI_30}';
        $authNames['31'] = '{MESA_PRI_31}';

        $authNames['32'] = 'Legionella monsters aanmelden';
        $authNames['33'] = 'Rodac monsters aanmelden';
        $authNames['34'] = 'Analyses aan lege monsters toevoegen';
        $authNames['35'] = 'Monsterlijst openen';
        $authNames['36'] = 'Boringsformulier openen';
        $authNames['37'] = 'Lijsten pagina openen';
        $authNames['38'] = 'Monster revisies inzien';
        $authNames['39'] = 'Project revisies inzien';
        $authNames['40'] = 'Resultaat revisies inzien';
        $authNames['41'] = 'Verdunningen aanpassen';
        $authNames['42'] = 'Media, bevestigingen en materiaal aanpassen';
        $authNames['43'] = 'Parameters op rapportage bewerken';
        $authNames['44'] = 'Datamining functies gebruiken';
        $authNames['45'] = 'Toegang tot klant import';
        $authNames['46'] = 'Toegang tot voorportaal';
        $authNames['47'] = 'Toegang tot THT onderzoek lijst';
        $authNames['48'] = 'Kan algemene zoek functie voor analyes toevoegen gebruiken';
        $authNames['49'] = 'Werklijsten beheren';
        $authNames['50'] = 'Kan klanten lijst exporteren';
        $authNames['51'] = 'Kan bevestigingen resetten';
        $authNames['52'] = 'Kan via data-mining deauthorisatie-reden lijst opvragen';
        $authNames['53'] = 'Kan monster notities aanpassen';
        $authNames['54'] = 'Kan klant categorie asociaties aanpassen';
        $authNames['55'] = 'Kan klant-omschrijving van monsters aanpassen';
        $authNames['56'] = 'Kan project autorisatie blokkeren';
        $authNames['57'] = 'Kan monster foto\'s bewerken';
        //$authNames['55'] = 'Developer paneel openen';


        //Open administration panel
        $authRules['1']['0']['ruleId'] = '1';
        $authRules['1']['0']['scope'] = '2';
        $authRules['1']['0']['controller'] = '';
        $authRules['1']['0']['action'] = '';
        $authRules['1']['0']['DOMName'] = 'adminSectionLink';
        $authRules['1']['1']['ruleId'] = '1';
        $authRules['1']['1']['scope'] = '1';
        $authRules['1']['1']['controller'] = 'adminController';
        $authRules['1']['1']['action'] = '*';
        $authRules['1']['1']['DOMName'] = False;

        //Manage users
        $authRules['2']['0']['ruleId'] = '2';
        $authRules['2']['0']['scope'] = '1';
        $authRules['2']['0']['controller'] = 'userAdminController';
        $authRules['2']['0']['action'] = '*';
        $authRules['2']['0']['DOMName'] = False;
        $authRules['2']['1']['ruleId'] = '2';
        $authRules['2']['1']['scope'] = '2';
        $authRules['2']['1']['controller'] = False;
        $authRules['2']['1']['action'] = False;
        $authRules['2']['1']['DOMName'] = 'userAdminSidebarLink';
        $authRules['2']['2']['ruleId'] = '2';
        $authRules['2']['2']['scope'] = '1';
        $authRules['2']['2']['controller'] = 'profilesController';
        $authRules['2']['2']['action'] = 'saveProfile||removeAvatar';
        $authRules['2']['2']['DOMName'] = False;
        $authRules['2']['3']['ruleId'] = '2';
        $authRules['2']['3']['scope'] = '1';
        $authRules['2']['3']['controller'] = 'usersController';
        $authRules['2']['3']['action'] = 'lockAccount||add||changePassword';
        $authRules['2']['3']['DOMName'] = False;


        //Manage user groups
        $authRules['3']['0']['ruleId'] = '3';
        $authRules['3']['0']['scope'] = '1';
        $authRules['3']['0']['controller'] = 'userGroupsController';
        $authRules['3']['0']['action'] = '*';
        $authRules['3']['0']['DOMName'] = False;

        $authRules['3']['1']['ruleId'] = '3';
        $authRules['3']['1']['scope'] = '2';
        $authRules['3']['1']['controller'] = False;
        $authRules['3']['1']['action'] = False;
        $authRules['3']['1']['DOMName'] = 'groupAdminSidebarLink';

        //Edit research profiles
        $authRules['4']['0']['ruleId'] = '4';
        $authRules['4']['0']['scope'] = '1';
        $authRules['4']['0']['controller'] = 'researchProfilesController';
        $authRules['4']['0']['action'] = 'profileForm||listing||saveProfileInfo||updateProfileInfo||edit||removeByClient||remove';
        $authRules['4']['0']['DOMName'] = False;
        $authRules['4']['1']['ruleId'] = '4';
        $authRules['4']['1']['scope'] = '2';
        $authRules['4']['1']['controller'] = False;
        $authRules['4']['1']['action'] = False;
        $authRules['4']['1']['DOMName'] = 'researchProfilesSidebarLink';

        //Edit assays
        $authRules['5']['0']['ruleId'] = '5';
        $authRules['5']['0']['scope'] = '1';
        $authRules['5']['0']['controller'] = 'assaysController';
        $authRules['5']['0']['action'] = 'remove||saveAssay||edit||listing';
        $authRules['5']['0']['DOMName'] = False;
        $authRules['5']['1']['ruleId'] = '5';
        $authRules['5']['1']['scope'] = '1';
        $authRules['5']['1']['controller'] = 'scriptEditorController';
        $authRules['5']['1']['action'] = '*';
        $authRules['5']['1']['DOMName'] = False;
        $authRules['5']['2']['ruleId'] = '5';
        $authRules['5']['2']['scope'] = '2';
        $authRules['5']['2']['controller'] = False;
        $authRules['5']['2']['action'] = False;
        $authRules['5']['2']['DOMName'] = 'assaysSidebarLink';

        //Edit assay types
        $authRules['6']['0']['ruleId'] = '6';
        $authRules['6']['0']['scope'] = '1';
        $authRules['6']['0']['controller'] = 'assayTypesController';
        $authRules['6']['0']['action'] = 'add||addSubmit||edit||changeName||remove';
        $authRules['6']['0']['DOMName'] = False;
        $authRules['6']['1']['ruleId'] = '6';
        $authRules['6']['1']['scope'] = '2';
        $authRules['6']['1']['controller'] = False;
        $authRules['6']['1']['action'] = False;
        $authRules['6']['1']['DOMName'] = 'assayTypeSidebarLink';

        //Edit sampling procedures
        $authRules['7']['0']['ruleId'] = '7';
        $authRules['7']['0']['scope'] = '1';
        $authRules['7']['0']['controller'] = 'sampleProceduresController';
        $authRules['7']['0']['action'] = 'listing||edit||saveProcedure||remove';
        $authRules['7']['0']['DOMName'] = False;
        $authRules['7']['1']['ruleId'] = '7';
        $authRules['7']['1']['scope'] = '2';
        $authRules['7']['1']['controller'] = False;
        $authRules['7']['1']['action'] = False;
        $authRules['7']['1']['DOMName'] = 'samplingProceduresSidebarLink';

        //Edit proejct fields
        $authRules['8']['0']['ruleId'] = '8';
        $authRules['8']['0']['scope'] = '1';
        $authRules['8']['0']['controller'] = 'projectFieldsController';
        $authRules['8']['0']['action'] = 'listing||edit||saveField||removeField';
        $authRules['8']['0']['DOMName'] = False;
        $authRules['8']['1']['ruleId'] = '8';
        $authRules['8']['1']['scope'] = '2';
        $authRules['8']['1']['controller'] = False;
        $authRules['8']['1']['action'] = False;
        $authRules['8']['1']['DOMName'] = 'projectFieldsSidebarLink';

        //Edit global assay fields
        $authRules['9']['0']['ruleId'] = '9';
        $authRules['9']['0']['scope'] = '1';
        $authRules['9']['0']['controller'] = 'assayFieldsController';
        $authRules['9']['0']['action'] = 'listing||saveField||removeField';
        $authRules['9']['0']['DOMName'] = False;
        $authRules['9']['1']['ruleId'] = '9';
        $authRules['9']['1']['scope'] = '2';
        $authRules['9']['1']['controller'] = False;
        $authRules['9']['1']['action'] = False;
        $authRules['9']['1']['DOMName'] = 'assayFieldsSidebarLink';

        //Edit sample fields
        $authRules['10']['0']['ruleId'] = '10';
        $authRules['10']['0']['scope'] = '1';
        $authRules['10']['0']['controller'] = 'sampleFieldsController';
        $authRules['10']['0']['action'] = 'listing||edit||saveField||removeField';
        $authRules['10']['0']['DOMName'] = False;
        $authRules['10']['1']['ruleId'] = '10';
        $authRules['10']['1']['scope'] = '2';
        $authRules['10']['1']['controller'] = False;
        $authRules['10']['1']['action'] = False;
        $authRules['10']['1']['DOMName'] = 'sampleFieldsSidebarLink';

        //Edit sample procedure fields
        $authRules['11']['0']['ruleId'] = '11';
        $authRules['11']['0']['scope'] = '1';
        $authRules['11']['0']['controller'] = 'sampleProcedureFields';
        $authRules['11']['0']['action'] = 'listing||edit||saveProcedure';
        $authRules['11']['0']['DOMName'] = False;
        $authRules['11']['1']['ruleId'] = '11';
        $authRules['11']['1']['scope'] = '2';
        $authRules['11']['1']['controller'] = False;
        $authRules['11']['1']['action'] = False;
        $authRules['11']['1']['DOMName'] = 'sampleProcedureFieldsSidebarLink';

        //Register samples
        $authRules['12']['0']['ruleId'] = '12';
        $authRules['12']['0']['scope'] = '1';
        $authRules['12']['0']['controller'] = 'samplesController';
        $authRules['12']['0']['action'] = 'add||save';
        $authRules['12']['0']['DOMName'] = False;
        $authRules['12']['1']['ruleId'] = '12';
        $authRules['12']['1']['scope'] = '2';
        $authRules['12']['1']['controller'] = False;
        $authRules['12']['1']['action'] = False;
        $authRules['12']['1']['DOMName'] = 'registerSampleSidebarLink';

        //Attach research to samples
        $authRules['13']['0']['ruleId'] = '13';
        $authRules['13']['0']['scope'] = '2';
        $authRules['13']['0']['controller'] = False;
        $authRules['13']['0']['action'] = False;
        $authRules['13']['0']['DOMName'] = 'reseachToSampleAdd';

        //Sample lookup
        $authRules['14']['0']['ruleId'] = '14';
        $authRules['14']['0']['scope'] = '1';
        $authRules['14']['0']['controller'] = 'samplesController';
        $authRules['14']['0']['action'] = 'lookup';
        $authRules['14']['0']['DOMName'] = False;
        $authRules['14']['1']['ruleId'] = '14';
        $authRules['14']['1']['scope'] = '2';
        $authRules['14']['1']['controller'] = False;
        $authRules['14']['1']['action'] = False;
        $authRules['14']['1']['DOMName'] = 'sampleLookupSidebarLink';

        //Edit sample details
        $authRules['15']['0']['ruleId'] = '15';
        $authRules['15']['0']['scope'] = '2';
        $authRules['15']['0']['controller'] = False;
        $authRules['15']['0']['action'] = False;
        $authRules['15']['0']['DOMName'] = 'editSampleDetails';
        
        $authRules['15']['1']['ruleId'] = '15';
        $authRules['15']['1']['scope'] = '1';
        $authRules['15']['1']['controller'] = 'samplesController';
        $authRules['15']['1']['action'] = 'updateSampleField';
        $authRules['15']['1']['DOMName'] = False;

        //Retrospectivly add or remove assays
        $authRules['16']['0']['ruleId'] = '16';
        $authRules['16']['0']['scope'] = '1';
        $authRules['16']['0']['controller'] = 'samplesController';
        $authRules['16']['0']['action'] = 'removeAnalysis||addNewResearch';
        $authRules['16']['0']['DOMName'] = False;
        $authRules['16']['1']['ruleId'] = '16';
        $authRules['16']['1']['scope'] = '2';
        $authRules['16']['1']['controller'] = False;
        $authRules['16']['1']['action'] = False;
        $authRules['16']['1']['DOMName'] = 'editResearchButtons';

        //Project lookup
        $authRules['17']['0']['ruleId'] = '17';
        $authRules['17']['0']['scope'] = '2';
        $authRules['17']['0']['controller'] = False;
        $authRules['17']['0']['action'] = False;
        $authRules['17']['0']['DOMName'] = 'projectLookupSidebarLink';
        $authRules['17']['1']['ruleId'] = '17';
        $authRules['17']['1']['scope'] = '1';
        $authRules['17']['1']['controller'] = 'samplesController';
        $authRules['17']['1']['action'] = 'search';
        $authRules['17']['1']['DOMName'] = False;

        //Edit project details
        $authRules['18']['0']['ruleId'] = '18';
        $authRules['18']['0']['scope'] = '2';
        $authRules['18']['0']['controller'] = False;
        $authRules['18']['0']['action'] = False;
        $authRules['18']['0']['DOMName'] = 'editProjectInformation';

        //Change authorisation on projects
        $authRules['19']['0']['ruleId'] = '19';
        $authRules['19']['0']['scope'] = '1';
        $authRules['19']['0']['controller'] = 'projectsController';
        $authRules['19']['0']['action'] = 'authoriseProject||deauthoriseProject';
        $authRules['19']['0']['DOMName'] = False;
        $authRules['19']['1']['ruleId'] = '19';
        $authRules['19']['1']['scope'] = '2';
        $authRules['19']['1']['controller'] = False;
        $authRules['19']['1']['action'] = False;
        $authRules['19']['1']['DOMName'] = 'authoriseButtons';

        //Remove samples from project
        $authRules['20']['0']['ruleId'] = '20';
        $authRules['20']['0']['scope'] = '1';
        $authRules['20']['0']['controller'] = 'samplesController';
        $authRules['20']['0']['action'] = 'removeSample||removeProjectSamples';
        $authRules['20']['0']['DOMName'] = False;
        $authRules['20']['1']['ruleId'] = '20';
        $authRules['20']['1']['scope'] = '2';
        $authRules['20']['1']['controller'] = False;
        $authRules['20']['1']['action'] = False;
        $authRules['20']['1']['DOMName'] = 'sampleRemoveButton';

        //Add notes to projects
        $authRules['21']['0']['ruleId'] = '21';
        $authRules['21']['0']['scope'] = '2';
        $authRules['21']['0']['controller'] = False;
        $authRules['21']['0']['action'] = False;
        $authRules['21']['0']['DOMName'] = 'projectNotesAdd';

        //Set veto results
        $authRules['22']['0']['ruleId'] = '22';
        $authRules['22']['0']['scope'] = '1';
        $authRules['22']['0']['controller'] = 'vetoResultsController';
        $authRules['22']['0']['action'] = 'saveVeto||removeVeto||removeVetoSaid';
        $authRules['22']['0']['DOMName'] = False;
        $authRules['22']['1']['ruleId'] = '22';
        $authRules['22']['1']['scope'] = '2';
        $authRules['22']['1']['controller'] = False;
        $authRules['22']['1']['action'] = False;
        $authRules['22']['1']['DOMName'] = 'vetoResultButton';

        //Remove projects
        $authRules['23']['0']['ruleId'] = '23';
        $authRules['23']['0']['scope'] = '1';
        $authRules['23']['0']['controller'] = 'projectsController';
        $authRules['23']['0']['action'] = 'removeProject';
        $authRules['23']['0']['DOMName'] = False;
        $authRules['23']['1']['ruleId'] = '23';
        $authRules['23']['1']['scope'] = '2';
        $authRules['23']['1']['controller'] = False;
        $authRules['23']['1']['action'] = False;
        $authRules['23']['1']['DOMName'] = 'projectRemoveButton';

        //View client info
        $authRules['24']['0']['ruleId'] = '24';
        $authRules['24']['0']['scope'] = '1';
        $authRules['24']['0']['controller'] = 'clientsController';
        $authRules['24']['0']['action'] = 'dashboard||listing||show||edit||remove';
        $authRules['24']['0']['DOMName'] = False;
        $authRules['24']['1']['ruleId'] = '24';
        $authRules['24']['1']['scope'] = '1';
        $authRules['24']['1']['controller'] = 'subClientsController';
        $authRules['24']['1']['action'] = 'edit||doSave||remove||removeAllSubclients';
        $authRules['24']['1']['DOMName'] = False;
        $authRules['24']['2']['ruleId'] = '24';
        $authRules['24']['2']['scope'] = '2';
        $authRules['24']['2']['controller'] = False;
        $authRules['24']['2']['action'] = False;
        $authRules['24']['2']['DOMName'] = 'clientDashboardButton';

        //Create, edit and remove clients
        $authRules['25']['0']['ruleId'] = '25';
        $authRules['25']['0']['scope'] = '1';
        $authRules['25']['0']['controller'] = 'clientsController';
        $authRules['25']['0']['action'] = 'dashboard||listing||show||edit||remove';
        $authRules['25']['0']['DOMName'] = False;
        $authRules['25']['1']['ruleId'] = '25';
        $authRules['25']['1']['scope'] = '1';
        $authRules['25']['1']['controller'] = 'subClientsController';
        $authRules['25']['1']['action'] = 'edit||doSave||remove||removeAllSubclients';
        $authRules['25']['1']['DOMName'] = False;

        $authRules['25']['2']['ruleId'] = '25';
        $authRules['25']['2']['scope'] = '2';
        $authRules['25']['2']['controller'] = False;
        $authRules['25']['2']['action'] = False;
        $authRules['25']['2']['DOMName'] = 'addClientButton';

        $authRules['25']['3']['ruleId'] = '25';
        $authRules['25']['3']['scope'] = '2';
        $authRules['25']['3']['controller'] = False;
        $authRules['25']['3']['action'] = False;
        $authRules['25']['3']['DOMName'] = 'editClientButton';

        $authRules['25']['4']['ruleId'] = '25';
        $authRules['25']['4']['scope'] = '2';
        $authRules['25']['4']['controller'] = False;
        $authRules['25']['4']['action'] = False;
        $authRules['25']['4']['DOMName'] = 'editSubclientButtons';


        $authRules['26']['0']['ruleId'] = '26';
        $authRules['26']['0']['scope'] = '2';
        $authRules['26']['0']['controller'] = False;
        $authRules['26']['0']['action'] = False;
        $authRules['26']['0']['DOMName'] = 'auditAssistSyncButton';


        $authRules['27']['0']['ruleId'] = '27';
        $authRules['27']['0']['scope'] = '2';
        $authRules['27']['0']['controller'] = False;
        $authRules['27']['0']['action'] = False;
        $authRules['27']['0']['DOMName'] = 'labelDesignButtons';

        $authRules['28']['0']['ruleId'] = '28';
        $authRules['28']['0']['scope'] = '2';
        $authRules['28']['0']['controller'] = False;
        $authRules['28']['0']['action'] = False;
        $authRules['28']['0']['DOMName'] = 'labelEventButtons';

        $authRules['29']['0']['ruleId'] = '29';
        $authRules['29']['0']['scope'] = '2';
        $authRules['29']['0']['controller'] = False;
        $authRules['29']['0']['action'] = False;
        $authRules['29']['0']['DOMName'] = 'logbookButtons';

        $authRules['30']['0']['ruleId'] = '30';
        $authRules['30']['0']['scope'] = '2';
        $authRules['30']['0']['controller'] = False;
        $authRules['30']['0']['action'] = False;
        $authRules['30']['0']['DOMName'] = 'cvarButtons';

        $authRules['31']['0']['ruleId'] = '31';
        $authRules['31']['0']['scope'] = '2';
        $authRules['31']['0']['controller'] = False;
        $authRules['31']['0']['action'] = False;
        $authRules['31']['0']['DOMName'] = 'scan2printButtons';

        $authRules['32']['0']['ruleId'] = '32';
        $authRules['32']['0']['scope'] = '2';
        $authRules['32']['0']['controller'] = False;
        $authRules['32']['0']['action'] = False;
        $authRules['32']['0']['DOMName'] = 'registerSampleLegionellaSidebarLink';

        $authRules['33']['0']['ruleId'] = '33';
        $authRules['33']['0']['scope'] = '2';
        $authRules['33']['0']['controller'] = False;
        $authRules['33']['0']['action'] = False;
        $authRules['33']['0']['DOMName'] = 'registerSampleRodacSidebarLink';

        $authRules['34']['0']['ruleId'] = '34';
        $authRules['34']['0']['scope'] = '2';
        $authRules['34']['0']['controller'] = False;
        $authRules['34']['0']['action'] = False;
        $authRules['34']['0']['DOMName'] = 'registerSampleEmptySidebarLink';

        $authRules['35']['0']['ruleId'] = '35';
        $authRules['35']['0']['scope'] = '2';
        $authRules['35']['0']['controller'] = False;
        $authRules['35']['0']['action'] = False;
        $authRules['35']['0']['DOMName'] = 'sampleListSidebarLink';

        $authRules['36']['0']['ruleId'] = '36';
        $authRules['36']['0']['scope'] = '2';
        $authRules['36']['0']['controller'] = False;
        $authRules['36']['0']['action'] = False;
        $authRules['36']['0']['DOMName'] = 'assuranceFormSidebarLink';

        $authRules['37']['0']['ruleId'] = '37';
        $authRules['37']['0']['scope'] = '2';
        $authRules['37']['0']['controller'] = False;
        $authRules['37']['0']['action'] = False;
        $authRules['37']['0']['DOMName'] = 'listsButtonLink';

        $authRules['38']['0']['ruleId'] = '38';
        $authRules['38']['0']['scope'] = '2';
        $authRules['38']['0']['controller'] = False;
        $authRules['38']['0']['action'] = False;
        $authRules['38']['0']['DOMName'] = 'sampRevision';

        $authRules['39']['0']['ruleId'] = '39';
        $authRules['39']['0']['scope'] = '2';
        $authRules['39']['0']['controller'] = False;
        $authRules['39']['0']['action'] = False;
        $authRules['39']['0']['DOMName'] = 'projRevision';

        $authRules['40']['0']['ruleId'] = '40';
        $authRules['40']['0']['scope'] = '2';
        $authRules['40']['0']['controller'] = False;
        $authRules['40']['0']['action'] = False;
        $authRules['40']['0']['DOMName'] = 'assayRevision';

        $authRules['41']['0']['ruleId'] = '41';
        $authRules['41']['0']['scope'] = '2';
        $authRules['41']['0']['controller'] = False;
        $authRules['41']['0']['action'] = False;
        $authRules['41']['0']['DOMName'] = 'dilChange';

        $authRules['42']['0']['ruleId'] = '42';
        $authRules['42']['0']['scope'] = '2';
        $authRules['42']['0']['controller'] = False;
        $authRules['42']['0']['action'] = False;
        $authRules['42']['0']['DOMName'] = 'mediaListing';

        $authRules['42']['1']['ruleId'] = '42';
        $authRules['42']['1']['scope'] = '1';
        $authRules['42']['1']['controller'] = 'mediaController';
        $authRules['42']['1']['action'] = 'listing||add||edit||remove';
        $authRules['42']['1']['DOMName'] = False;

        $authRules['43']['0']['ruleId'] = '43';
        $authRules['43']['0']['scope'] = '1';
        $authRules['43']['0']['controller'] = 'exportsController';
        $authRules['43']['0']['action'] = 'setAssays';
        $authRules['43']['0']['DOMName'] = False;

        $authRules['44']['0']['ruleId'] = '44';
        $authRules['44']['0']['scope'] = '1';
        $authRules['44']['0']['controller'] = 'dataMiningController';
        $authRules['44']['0']['action'] = '*';
        $authRules['44']['0']['DOMName'] = False;

        $authRules['44']['1']['ruleId'] = '44';
        $authRules['44']['1']['scope'] = '2';
        $authRules['44']['1']['controller'] = False;
        $authRules['44']['1']['action'] = False;
        $authRules['44']['1']['DOMName'] = 'dataMining';


        $authRules['45']['0']['ruleId'] = '45';
        $authRules['45']['0']['scope'] = '1';
        $authRules['45']['0']['controller'] = 'sampleBuffersController';
        $authRules['45']['0']['action'] = 'bulkUploader';
        $authRules['45']['0']['DOMName'] = False;

        $authRules['45']['1']['ruleId'] = '45';
        $authRules['45']['1']['scope'] = '2';
        $authRules['45']['1']['controller'] = False;
        $authRules['45']['1']['action'] = False;
        $authRules['45']['1']['DOMName'] = 'bulkUploader';

        $authRules['46']['0']['ruleId'] = '46';
        $authRules['46']['0']['scope'] = '1';
        $authRules['46']['0']['controller'] = 'sampleBuffersController';
        $authRules['46']['0']['action'] = 'portal';
        $authRules['46']['0']['DOMName'] = False;

        $authRules['46']['1']['ruleId'] = '46';
        $authRules['46']['1']['scope'] = '2';
        $authRules['46']['1']['controller'] = False;
        $authRules['46']['1']['action'] = False;
        $authRules['46']['1']['DOMName'] = 'preportal';


        $authRules['47']['0']['ruleId'] = '47';
        $authRules['47']['0']['scope'] = '1';
        $authRules['47']['0']['controller'] = 'sampleBuffersController';
        $authRules['47']['0']['action'] = 'portal';
        $authRules['47']['0']['DOMName'] = False;

        $authRules['47']['1']['ruleId'] = '47';
        $authRules['47']['1']['scope'] = '2';
        $authRules['47']['1']['controller'] = False;
        $authRules['47']['1']['action'] = False;
        $authRules['47']['1']['DOMName'] = 'thtportal';

        $authRules['48']['0']['ruleId'] = '48';
        $authRules['48']['0']['scope'] = '2';
        $authRules['48']['0']['controller'] = False;
        $authRules['48']['0']['action'] = False;
        $authRules['48']['0']['DOMName'] = 'searchAnalysisAdd';

        $authRules['49']['0']['ruleId'] = '49';
        $authRules['49']['0']['scope'] = '2';
        $authRules['49']['0']['controller'] = False;
        $authRules['49']['0']['action'] = False;
        $authRules['49']['0']['DOMName'] = 'worklistadmin';

        $authRules['49']['1']['ruleId'] = '49';
        $authRules['49']['1']['scope'] = '1';
        $authRules['49']['1']['controller'] = 'workListsController';
        $authRules['49']['1']['action'] = 'show||edit||saveWorklist||saveNewWorklist||remove';
        $authRules['49']['1']['DOMName'] = False;

        $authRules['50']['0']['ruleId'] = '50';
        $authRules['50']['0']['scope'] = '2';
        $authRules['50']['0']['controller'] = False;
        $authRules['50']['0']['action'] = False;
        $authRules['50']['0']['DOMName'] = 'clientdumpbuttons';

        $authRules['50']['1']['ruleId'] = '50';
        $authRules['50']['1']['scope'] = '1';
        $authRules['50']['1']['controller'] = 'clientsController';
        $authRules['50']['1']['action'] = 'dump||dump_contact_lists';
        $authRules['50']['1']['DOMName'] = False;
        
        $authRules['51']['0']['ruleId'] = '51';
        $authRules['51']['0']['scope'] = '2';
        $authRules['51']['0']['controller'] = False;
        $authRules['51']['0']['action'] = False;
        $authRules['51']['0']['DOMName'] = 'resetConfirmation';



        $authRules['52']['0']['ruleId'] = '52';
        $authRules['52']['0']['scope'] = '1';
        $authRules['52']['0']['controller'] = 'dataMiningController';
        $authRules['52']['0']['action'] = 'deauth||deauthExport';
        $authRules['52']['0']['DOMName'] = False;

        $authRules['52']['1']['ruleId'] = '52';
        $authRules['52']['1']['scope'] = '2';
        $authRules['52']['1']['controller'] = False;
        $authRules['52']['1']['action'] = False;
        $authRules['52']['1']['DOMName'] = 'deauthList';


        $authRules['53']['0']['ruleId'] = '53';
        $authRules['53']['0']['scope'] = '2';
        $authRules['53']['0']['controller'] = False;
        $authRules['53']['0']['action'] = False;
        $authRules['53']['0']['DOMName'] = 'notes';


        $authRules['54']['0']['ruleId'] = '54';
        $authRules['54']['0']['scope'] = '2';
        $authRules['54']['0']['controller'] = False;
        $authRules['54']['0']['action'] = False;
        $authRules['54']['0']['DOMName'] = 'clientcats';

        $authRules['54']['1']['ruleId'] = '54';
        $authRules['54']['1']['scope'] = '1';
        $authRules['54']['1']['controller'] = 'clientCategoriesController';
        $authRules['54']['1']['action'] = 'list||save||destroy||show';
        $authRules['54']['1']['DOMName'] = False;

        $authRules['55']['0']['ruleId'] = '55';
        $authRules['55']['0']['scope'] = '2';
        $authRules['55']['0']['controller'] = False;
        $authRules['55']['0']['action'] = False;
        $authRules['55']['0']['DOMName'] = 'clientDescription';


        $authRules['56']['0']['ruleId'] = '56';
        $authRules['56']['0']['scope'] = '2';
        $authRules['56']['0']['controller'] = False;
        $authRules['56']['0']['action'] = False;
        $authRules['56']['0']['DOMName'] = 'authBlockButtons';

        $authRules['56']['1']['ruleId'] = '56';
        $authRules['56']['1']['scope'] = '1';
        $authRules['56']['1']['controller'] = 'projectsController';
        $authRules['56']['1']['action'] = 'lockProject';
        $authRules['56']['1']['DOMName'] = False;

        $authRules['57']['0']['ruleId'] = '57';
        $authRules['57']['0']['scope'] = '1';
        $authRules['57']['0']['controller'] = 'samplesController';
        $authRules['57']['0']['action'] = 'scanAndSnap';
        $authRules['57']['0']['DOMName'] = False;

        $authRules['57']['1']['ruleId'] = '57';
        $authRules['57']['1']['scope'] = '2';
        $authRules['57']['1']['controller'] = False;
        $authRules['57']['1']['action'] = False;
        $authRules['57']['1']['DOMName'] = 'fotoMenuItem';

        $this->_authNames = $authNames;
        $this->_authRules = $authRules;

    }

    function checkGUI($DOMName){

        
        $this->GroupPrivilege->where('scope', '2');
        $this->GroupPrivilege->where('DOMName', $DOMName);
        
        $results = $this->GroupPrivilege->search();
        
        $userSuper = upa('userGroups', 'isPartOfSuperGroup', array(getUserId()));

        //this is a super user, allow
        if($userSuper == True){
            return True;
        }


        if(empty($results)){            
            return False;
        } else{
            $userGroups = upa('groupUsers', 'getUserAffiliation', array(), 0);
            $userFallsInAuth = False;

            foreach($results as $pvRule){

                if(in_array($pvRule['groupId'], $userGroups)){
                    $userFallsInAuth = True;
                }

            }

            if($userFallsInAuth == True){
                return True;
            } else {
                return False;
            }
        }
    }

    

    function checkGUIBulk()
    {

        $userSuper = upa('userGroups', 'isPartOfSuperGroup', array(getUserId()));

        if($userSuper)
        {
            return true;
        }
        
        $userGroups = upa('groupUsers', 'getUserAffiliation', array(), 0);

        $accepted = []; 

        if(!empty($userGroups))
        {

            $groupMap = implode(',', array_map('intval', $userGroups));  
                        
            $sql = 'SELECT DOMName FROM groupprivileges WHERE groupId IN (' . $groupMap . ') AND scope = 2';
            
            $foundRules = $this->GroupPrivilege->customQuery($sql, array());    

            $accepted = (array_column($foundRules, 'DOMName'));          
        
        }

        return $accepted;                                   



    }

    function checkAllowed($controller, $action){            

        
        //check if restrictions exists for this action
        $this->GroupPrivilege->where('scope', '1');
        $this->GroupPrivilege->where('controller', $controller);
        $results = $this->GroupPrivilege->search();

        $userSuper = upa('userGroups', 'isPartOfSuperGroup', array(getUserId()));

        if(empty($results)){
            //nothing exists for this
            return True;
        }

        elseif($userSuper == True){
             return True;
        }

        else{

            $userGroups = upa('groupUsers', 'getUserAffiliation', array(), 0);
            $userFallsInAuth = False;
            $foundRestrictedAction = False;

            foreach($results as $pvRule){

                //if this rule concerns the current action check if the user is part of it
                //remember that multiple groups etc can be involved. So while we check all,
                //only 1 True/False needs to eb returned

                $actions = explode('||', $pvRule['action']);

                foreach($actions as $thisAction){
                    if($thisAction == $action || $thisAction == '*'){
                        $foundRestrictedAction = True;
                        if(in_array($pvRule['groupId'], $userGroups)){
                            $userFallsInAuth = True;
                        }
                    }
                }



                }

            if($foundRestrictedAction == True){

                if($userFallsInAuth == True){
                    return True;
                } else {
                    writeLog('User tried to access restricted resource', ALPC_SECURITY);
                    return False;
                }
            } else{
                return True;
            }
        }
    }


    function generatePrivilegeForm($groupId){

        $this->render = 0;
        $tF = new tableFactory();
        $tF->setTableId('privTable');
        $tF->loadTemplate('privilegeTable');
        $tableData = array();


        //only need to check if the rule id gives anthing, since it wll always be insync with each other
        foreach($this->_authRules as $ruleId => $subRules){

            //there = access
            $this->GroupPrivilege->where('groupId', $groupId);
            $this->GroupPrivilege->where('ruleId', $ruleId);
            $this->GroupPrivilege->search();

            if($this->GroupPrivilege->lastQueryCount > 0){
                $tableData[$ruleId]['name'] = $this->_authNames[$ruleId];
                $tableData[$ruleId]['id'] = $ruleId;
                $tableData[$ruleId]['yes'] = ' selected="SELECTED"';
                $tableData[$ruleId]['no'] = '';
            } else{
                $tableData[$ruleId]['name'] = $this->_authNames[$ruleId];
                $tableData[$ruleId]['id'] = $ruleId;
                $tableData[$ruleId]['yes'] = '';
                $tableData[$ruleId]['no'] = ' selected="SELECTED"';
            }

            $this->GroupPrivilege->free();

        }

        $tF->loadValues($tableData);
        $tab = $tF->renderTable();
        return $tab;
    }

    function changePrivilege(){

        $this->render = 0;

        $group = $_POST['group'];
        $privilegeId = $_POST['privilege'];
        $allowed = $_POST['allowed'];

        //check if this is a supergrou
        $superGroup = upa('userGroups', 'isSuperGroup', array($group));
        if($superGroup == True){
            return False;
        }

        if($allowed == '0' || $allowed === 0){

            //disallow this group
            //need to get the ID's referring to this rule
            $this->GroupPrivilege->where('groupId', $group);
            $this->GroupPrivilege->where('ruleId', $privilegeId);
            $results = $this->GroupPrivilege->search();

            foreach($results as $dbLine){
                $this->GroupPrivilege->id = $dbLine['id'];
                $this->GroupPrivilege->delete();
                $this->GroupPrivilege->free();
            }
        }

        if($allowed == '1' || $allowed === 1){

            //need to set all for this group
            foreach($this->_authRules[$privilegeId] as $subRuleId => $subRule){
                $this->GroupPrivilege->arrayToModel($subRule);
                $this->GroupPrivilege->groupId = $group;
                $this->GroupPrivilege->save();
                $this->GroupPrivilege->free();
            }
        }
    }

    function removeGroupPrivileges($groupId){

        $this->GroupPrivilege->where('groupId', $groupId);
        $results = $this->GroupPrivilege->search();

        foreach($results as $priv){
            $this->GroupPrivilege->id = $priv['id'];
            $this->GroupPrivilege->delete();
            $this->GroupPrivilege->free();
        }
    }

    //this should be turned off for production
    function setupRules(){

        // $this->GroupPrivilege->where('groupId', '1');
        // $results = $this->GroupPrivilege->seach();
        //
        // foreacH($results as $row){
        //
        // }

        $sg = 1;
        foreach($this->_authRules as $ruleId => $subRules){

            foreach($subRules as $subRule){

                $this->GroupPrivilege->ruleId = $ruleId;
                $this->GroupPrivilege->groupId = $sg;
                $this->GroupPrivilege->scope = $subRule['scope'];
                $this->GroupPrivilege->controller = $subRule['controller'];
                $this->GroupPrivilege->action = $subRule['action'];
                $this->GroupPrivilege->DOMName = $subRule['DOMName'];
                $this->GroupPrivilege->save();
                $this->GroupPrivilege->free();
            }
        }
    }

}
