#!/usr/bin/php
<?php

define('SKATES', 1);

date_default_timezone_set('UTC');

require_once 'lib/php_base_ext.php';
require_once 'lib/inflect.php';


php_sapi_name() === 'cli' or die('Access denied! You must run this script from Command Line!');


if ($argv <= 1)
	echo "ERROR: missing command

Available commands:
		generate,g    Generate a model / Controller / ...

			";




switch ($argv[1]) {
  case 'generate':
  case 'g':
  	require 'lib/cli/generate.php';
		break;
  default:
  	echo "ERROR: Unknown command {$argv[1]}";

}

echo "
";

?>
