<?PHP
$lang = array();

/*
 * General parsing keys, do not need to be modified.
 */
$lang['LB'] = ALPC_BASEPATH;
$lang['LP'] = ALPC_BASEPATH . '/public';
$lang['LR'] = ALPC_ROOTPATH;
$lang['STG'] = ALPC_STAGING_CLASS;

//session lifetime?
$lang['ALPC_SESSION_LIFETIME'] = ALPC_SESSION_LIFETIME - 10;
$lang['ALPC_VERSION'] = ALPC_VERSION;

//dates
$lang['today'] = date('d-m-Y');
$lang['yesterday'] = date('d-m-Y', strtotime('-1 day', time()) );
$lang['tommorow'] = date('d-m-Y', strtotime('+1 day', time()) );
$lang['current_time'] = date('H:i', time());
$lang['current_year'] = date('Y', time());
