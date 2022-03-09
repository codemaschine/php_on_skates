#!/usr/bin/php
<?php

define('SKATES', 1);


$abs_base_path = dirname(dirname(dirname(__FILE__)));
define('ROOT_DIR', $abs_base_path.'/');
define('SKATES_DIR', $abs_base_path.'/skates/');
define('CORE_DIR', SKATES_DIR.'core/');
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


  case 'cli':
  case 'c':
    error_reporting(E_ALL ^  E_NOTICE);
    // Include composer packages if exists
    if (file_exists(ROOT_DIR.'vendor/autoload.php')) {
      include_once ROOT_DIR.'vendor/autoload.php';
    }
    require_once CORE_DIR.'date_and_time.php';
    require APP_DIR.'config.php';
    require_once CORE_DIR.'logger.php';
    $log = new Logger(ROOT_DIR.'log/clilog.txt', $debug ? 0 : 1);
    $fwlog = new Logger(ROOT_DIR.'log/fwlog.txt', $debug ? 0 : 1);
    require_once CORE_DIR.'locale.php';
    require_once CORE_DIR.'attachment.php';
    require_once CORE_DIR.'base.php';
    require_once CORE_DIR.'db.php';
    $db_link = db_init();
    require_once CORE_DIR.'abstract_model.php';
    require_once CORE_DIR.'relations.php';
    require_once CORE_DIR.'functions.php';
    $model_files = scandir(APP_DIR.'model');
    foreach ($model_files as $file) {
      // include app/model/*.php
      if (substr($file, -4) === '.php')
        require_once APP_DIR.'model/'.$file;

    }
    require_once CORE_DIR.'mailer.php';
    require_once CORE_DIR.'view_helpers.php';
    require_once CORE_DIR.'class.chip_password_generator.php';
    require_once APP_DIR.'application.php';
    $controller = $argv[2];
    if (!preg_match("/.*\.php/", $controller)) {
      $controller .= ".php";
    }
    $controller_argv = $argv;
    array_shift($controller_argv);
    array_shift($controller_argv);
    array_shift($controller_argv);
    require APP_DIR."controller/".$controller;
    break;
  default:
  	echo "ERROR: Unknown command {$argv[1]}";

}

echo "
";
