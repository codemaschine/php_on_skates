<?php
 
// Set local configuration here

date_default_timezone_set("Europe/Berlin");
setlocale(LC_ALL, "de_DE.UTF-8");
// Achtung: Wenn mb_internal_encoding('UTF-8') ist, dann funktioniert das Senden von E-Mails mit mb_send_mail() mit ISO-8859-1 nicht!
// nötig ist dies aber, damit mit strlen die richte Maximallänge für Strings ermittelt werden kann.
mb_internal_encoding('UTF-8'); // Available Encondings at http://php.net/manual/en/mbstring.supported-encodings.php

$_FRAMEWORK['default_locale'] = 'de';

if ($_GET['l'])
  $_FRAMEWORK['locale'] = $_GET['l'];
else
  $_FRAMEWORK['locale'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : $_FRAMEWORK['default_locale'];


$c_host;
$c_base_url;
$debug = 0;

$site_ident = 'my_site';



$environment = 'development';
if (strpos(dirname(__FILE__), 'beta') !== FALSE)
  $environment = 'beta';
elseif (strpos(dirname(__FILE__), 'live') !== FALSE)
  $environment = 'production';
else {
	$environment = 'development';
	$debug = 1;
}





$site_configs = array(
  'my_site' => array(),
  'other_website' => array()
);


/* ***** my_site ****** */

$site_configs['my_site']['layout_prefix'] = '';
$site_configs['my_site']['presite'] = false;
$site_configs['my_site']['sitename'] = 'my_site.de';
$site_configs['my_site']['sitename_short'] = 'my_sitede';
$site_configs['my_site']['view_prefix'] = '';
$site_configs['my_site']['locale_prefix'] = '';
$site_configs['my_site']['db'] = (object) array();
$site_configs['my_site']['db']->method = 1; // PDO
$site_configs['my_site']['contact_email'] = "info@my_site.de";
$site_configs['my_site']['base_url'] = 'https://my_site.de';


if ($environment == 'beta') {  // Beta-Seite auf Rolfs Server
  $site_configs['my_site']['db']->host = 'localhost';
  $site_configs['my_site']['db']->port = '3306';
  $site_configs['my_site']['db']->user = '';
  $site_configs['my_site']['db']->pass = '';
  $site_configs['my_site']['db']->name = '';
  // $site_configs['my_site']['db']->mysqlsocket = ''; // optional
  $site_configs['my_site']['base_url'] = 'http://beta.my_site.de';
}
elseif ($environment == 'production') {  // Produktionsumgebung
  $site_configs['my_site']['db']->host = 'localhost';
  $site_configs['my_site']['db']->port = '3306';
  $site_configs['my_site']['db']->user = '';
  $site_configs['my_site']['db']->pass = '';
  $site_configs['my_site']['db']->name = '';
  // $site_configs['my_site']['db']->mysqlsocket = ''; // optional
}
elseif ($environment == 'development') { // development
  $site_configs['my_site']['db']->host = '127.0.0.1';
  $site_configs['my_site']['db']->port = '3306';
  $site_configs['my_site']['db']->user = 'root';
  $site_configs['my_site']['db']->pass = '';
  $site_configs['my_site']['db']->name = 'skates_demo';
  // $site_configs['my_site']['db']->mysqlsocket = ''; // optional
  $site_configs['my_site']['base_url'] = 'http://localhost/my_site';
}





// !!!!!!  Warning !!!!!!!!
// Do not use $_SERVER to detect an environment here! It will not work for your database migrations!!!


$c_host;
$c_base_url;


if ($_SERVER !== NULL) {     // if the config file is include by db/migrate.php then there is no $_SERVER variable
  $c_host = $_SERVER['HTTP_HOST'];
  if($_SERVER['HTTPS'])
	  $c_base_url = 'https://'.$c_host.substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1);
  else
	  $c_base_url = 'http://'.$c_host.substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1);

}


$site_config = $site_configs[$site_ident];
$site_config['site_ident'] = $site_ident;
$db_config = $site_config['db'];

?>
