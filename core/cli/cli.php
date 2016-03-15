#!/usr/bin/php
<?php

define('SKATES', 1);


$abs_base_path = substr(dirname(__FILE__),0,strrpos(dirname(dirname(dirname(__FILE__))),'/'));
define('ROOT_DIR', $abs_base_path.'/');
define('SKATES_DIR', $abs_base_path.'/skates/');
define('APP_DIR', $abs_base_path.'/app/');

date_default_timezone_set('UTC');

require_once SKATES_DIR.'core/php_base_ext.php';
require_once SKATES_DIR.'core/inflect.php';


php_sapi_name() === 'cli' or die('Access denied! You must run this script from Command Line!');


if ($argv <= 1)
	echo "ERROR: missing command

Available commands:
		generate,g    Generate a model / Controller / ...

			";




switch ($argv[1]) {
  case 'generate':
  case 'g':
  	require 'generate.php';
	break;
		
		
  case 'migrate':
  case 'm':
  	$argv = array_unshift(array_splice($argv, 2), 'migrate.php');
  	require SKATES_DIR.'core/db/migrate.php';
  	break;
  default:
  	echo "ERROR: Unknown command {$argv[1]}";

}

echo "
";

?>
