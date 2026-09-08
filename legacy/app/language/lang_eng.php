<?PHP

global $lang;

/*  
 * ALPACA language keys
 */

$lang['ALPC_BRUTE_LOCKOUT'] = 'Too many tries, your account has been suspended';
$lang['ALPC_ACCOUNT_LOCKED'] = 'Account locked';

/* 
 * Form validation
 */
$lang['ALPC_FORM_NOT_EMPTY'] = 'Illegal input, field can not be empty';
$lang['ALPC_FORM_NO_DUP'] = 'Illegal input, duplicate key entered, trie a different value';
$lang['ALPC_FORM_ALPHANUM_NOTEMPTY'] = 'Illegal input, only alphanumerical characters allowed and field can not be empty';
$lang['ALPC_FORM_ALPHANUM_NOTEMPTY_NODUP'] = 'Illegal input, only alphanumerical characters allowed and field can not be empty, no duplicate entires allowed';
$lang['ALPC_FORM_NUM_ONLY'] = 'Illegal input, only numerical characters allowed';
$lang['ALPC_FORM_NOT_VALID'] = 'Not a valid input';

/*  
 * App language keys
 */
$lang['MESA_VERSION'] = MESA_VERSION;

//user


//user Dropdown
$lang['MESA_MY_ACOUNT'] = 'Configure my acount';

$lang['MESA_LOGOUT'] = 'Exit Session';

//left side menu
$lang['MESA_LSB_FLOW'] = 'Workflow setup';
$lang['MESA_LSB_ANALYTICAL'] = 'Assays';
$lang['MESA_LSB_TESTS'] = 'Tests';
$lang['MESA_LSB_ANALYSIS'] = 'Analysis';
$lang['MESA_LSB_PACKAGES'] = 'Analysis Packages';
$lang['MESA_LSB_SAMPLES'] = 'Samples';
$lang['MESA_LSB_SAMPLE_SETUP'] = 'Sample Setup';
$lang['MESA_LSB_WORK'] = 'Work';
$lang['MESA_LSB_REGISTER'] = 'Register samples';
$lang['MESA_LSB_LOOKUP'] = 'Sample lookup';
$lang['MESA_LSB_CLIENT'] = 'Clients';
$lang['MESA_LSB_SAMPFIELDS'] = 'Sample Fields';

//dash
$lang['MESA_DSH_GD_GOOD'] = 'Good';
$lang['MESA_DSH_GD_MORNING'] = 'morning';
$lang['MESA_DSH_GD_AFTERNOON'] = 'afternoon';
$lang['MESA_DSH_GD_EVENING'] = 'evening';
$lang['MESA_DSH_GD_NIGHT'] = 'night';

//Analyticals
$lang['MESA_ANA_LISTING_TITLE'] = 'Available assays';
$lang['MESA_ANA_NONE'] = 'No assays are currently available, you can create one by pressing &quot;add assay&quot; ';
$lang['MESA_ANA_NOFIELDS'] = 'This assay does not have any input fields defined';


$lang['MESA_ANA_RSB_ADD'] = 'Add assay';
$lang['MESA_ANA_RSB_EDIT'] = 'Edit selected';
$lang['MESA_ANA_RSB_DEL'] = 'Delete selected';
$lang['MESA_ANA_RSB_ACTIONS'] = 'Assay actions';
$lang['MESA_ANA_RSB_BACK'] = 'Back to list';

$lang['MESA_ANA_ADD_TITLE'] = 'New Assay';
$lang['MESA_ANA_ALERT_SELECT_FIRST'] = 'Select a analytical from the table first';


$lang['MESA_ANA_SELECTFIELD'] = 'A field needs to be selected before delete operation can continue';
$lang['MESA_ANA_FIELD_DELETE_TITLE'] = 'Delete field';
$lang['MESA_ANA_FIELD_DELETE_CONFIRM'] = 'Are you sure you want to delete the following field:';

//Analysis
$lang['MESA_ALS_NONE'] = 'No analysis are currentcly available, you can create one by pressing &quot;Add analysis&quot; ';
$lang['MESA_ALS_EDITNAME_TITLE'] = 'Change analysis name';
$lang['MESA_ALS_EDITNAME_MSG'] = 'Enter a new name for the analysis below';

//Analysis Tests
$lang['MESA_ALT_NONE'] = 'No tests are currently availble within this analysis, you have to create at least one to be able to use this analysis';

//result fields
$lang['MESA_RFL_ONLYFOR'] = 'Only for';
$lang['MESA_RFL_NONE'] = 'No results field added to this analysis';
$lang['MESA_CST_NONE'] = 'No constants added for this analysis';


//Packets
$lang['MESA_PCK_TITLE'] = 'Packets';
$lang['MESA_PCK_RSB_ACTIONS'] = 'Packet actions';
$lang['MESA_PCK_NONE'] = 'No packages are currentcly available, you can create one by pressing &quot;Add packet&quot; ';
$lang['MESA_PCK_ADD'] = 'Add Packet';
$lang['MESA_PCK_NON_BOUND'] = 'No tests are bound yet to this package';
$lang['MESA_PCK_REM_TITLE'] = 'Remove package';
$lang['MESA_PCK_REM_BODY'] = 'Are you sure you want to remove this package?';

//Authorisation
$lang['MESA_CONFIRM_AUTH_TITLE'] = 'Authorise sample';
$lang['MESA_CONFIRM_AUTH_MSG'] = 'Are you sure you want to authorise this sample? It will become unedit-able unless somebody issues a deauthorisation request';

$lang['MESA_CONFIRM_DEAUTH_TITLE'] = 'Deauthorise sample';
$lang['MESA_CONFIRM_DEAUTH_MSG'] = 'Are you sure you want to deauhtorise this sample? It will become editable for all users.';

$lang['MESA_CONFIRM_PROJ_AUTH_TITLE'] = 'Authorise project, proceed?';
$lang['MESA_CONFIRM_PROJ_AUTH_MSG'] = 'Any non authorised sample analysis results will automatically be authorised, and the project will be closed for editing.';

$lang['MESA_CONFIRM_PROJ_DEAUTH_TITLE'] = 'Deauthorise project, proceed?';
$lang['MESA_CONFIRM_PROJ_DEAUTH_MSG'] = 'All samples will be deauthorised and the projec revision will be increased by 1.';

//Authorisation error
$lang['MESA_AUTH_ERROR_LOCKED_TITLE'] = 'Can not perform this operation';
$lang['MESA_AUTH_ERROR_LOCKED_MSG'] = 'This action can not be performed as the project has been authorised. Deauthorise the project before sample adjustments are made.';

$lang['MESA_AUTH_ERROR_LOCKED_SMP_TITLE'] = 'Can not perform this operation';
$lang['MESA_AUTH_ERROR_LOCKED_SMP_MSG'] = 'This action can not be performed as the project to which this analysis and sample belongs is authorised. Deauthorise the project before sample adjustments are made.';


//Project 
$lang['MESA_CONFIRM_PROJ_DELETE_TITLE'] = 'Delete project and samples, are you sure?';
$lang['MESA_CONFIRM_PROJ_DELETE_MSG'] = 'You are about to perform the <em>destructive</em> operation of removing a project and all bound samples, are you sure? To continue type the security verification code into the text field and click &quot; ok &quot;.';

$lang['MESA_ERROR_PROJ_DELETE_TITLE'] = 'Error deleting project';
$lang['MESA_ERROR_PROJ_DELETE_MSG'] = 'Incorrect security token';

$lang['MESA_REMOVE_PROJ_SAMPLE_TITLE'] = 'Remove project sample';
$lang['MESA_REMOVE_PROJ_SAMPLE_MSG'] = 'You are about to remove a sample from this project, are you sure you want to continue?';

//Sample Fields
$lang['MESA_SPF_DELETE_TITLE'] = 'Remove sample field';
$lang['MESA_SPF_DELETE_MSG'] = 'Are you sure you want to remove the selected sample field?';


//Flow
$lang['MESA_FLW_NONE'] = 'No workflows have been defined';
$lang['MESA_FLW_SUM_ANSWER'] = 'Sum result';
$lang['MESA_FLW_SENDS_FIELDS'] = 'Assay fields';
$lang['MESA_FLW_INPUTS_NO_START'] = 'This component has no inputs, as it is the start of the workflow';
$lang['MESA_FLW_INPUTS_SENDS_LINK'] = 'Is connected to the rest of the workflow';
$lang['MESA_FLW_INPUTS_RECEIVES_LINK'] = 'Is connected to the previous sections of the workflow';
$lang['MESA_FLW_END_OF_FLOW'] = 'No data is being send, end of workflow';

//
$lang['smaller_than'] = 'Smaller than';
$lang['greater_than'] = 'Greater than';
$lang['dillution_level'] = 'Dilution';


$lang['rep_1'] = 'Duplo';
$lang['rep_2'] = 'Triplo';
$lang['rep_3'] = 'Replicate ';

$lang['undiluted'] = 'Original sample'; 

//social system texts
$lang['MESA_LAB_SAMPLE_ADDED'] = 'Sample {sample_description} was added to project {project_name} by <em> {user_full_name}</em> ';

$lang['no_assays_attched'] = 'No assays attached to this profile';