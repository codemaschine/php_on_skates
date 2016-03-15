<?php
 
// Set local configuration here

date_default_timezone_set("Europe/Berlin");
setlocale(LC_ALL, "de_DE.UTF-8");

mb_internal_encoding('UTF-8'); // Available Encondings at http://php.net/manual/en/mbstring.supported-encodings.php

$_FRAMEWORK['default_locale'] = 'de';


// !!!!!!  Warning !!!!!!!!
// Do not use $_SERVER to detect an environment here! It will not work for your database migrations!!!

$environment = 'development';
if (file_exists(dirname(__FILE__).'/development.txt')) {
	$environment = 'development';
	$debug = 1;
}
else {
	$environment = 'production';
}





$site_config = array();
$site_config['layout_prefix'] = '';
$site_config['presite'] = false;
$site_config['sitename'] = 'my_site.de';
$site_config['sitename_short'] = 'my_sitede';
$site_config['view_prefix'] = '';
$site_config['locale_prefix'] = '';
$site_config['contact_email'] = "info@my_site.de";
$site_config['base_url'] = 'https://my_site.de';


$db_configs = array(
	'production' => (object) array(),
	'development' => (object) array() 
);



$db_configs['production']->host = 'localhost';
$db_configs['production']->port = '3306';
$db_configs['production']->user = '';
$db_configs['production']->pass = '';
$db_configs['production']->name = '';
// $db_configs['production']->mysqlsocket = ''; // optional

$db_configs['development']->host = '__DEV_DB_HOST__';
$db_configs['development']->port = '3306';
$db_configs['development']->user = '__DEV_DB_USER__';
$db_configs['development']->pass = '__DEV_DB_PASS__';
$db_configs['development']->name = '__DEV_DB_NAME__';
// $db_configs['development']->mysqlsocket = ''; // optional



$db_config = $db_configs[$environment];
$db_config->method = 1; // PDO

?>
