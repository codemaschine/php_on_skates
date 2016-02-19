<?php

require_once 'lib/php_base_ext.php';

//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR | E_COMPILE_WARNING | E_CORE_ERROR | E_CORE_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE );
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);


// --- magic_qoutes on? Then remove slashes  (http://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php)
if (get_magic_quotes_gpc()) {
  $_GET = stripslashes_recursive($_GET);
  $_POST = stripslashes_recursive($_POST);
  $_COOKIE = stripslashes_recursive($_COOKIE);
}
// ----------------------

register_shutdown_function("status_500_on_error");
function status_500_on_error() {
  $error = error_get_last();
  if ($error !== NULL && $error['type'] === E_ERROR) {
    if (!headers_sent())
      header('HTTP/1.1 500 Internal Server Error');
  }
}


$echoLock=1; // Verhindert ausgabe durch commons_display_message();
$displayMessage = array();

if (strrpos($_FRAMEWORK['controller'], '.') === false) {
  $_FRAMEWORK['format'] = 'php';
  $_FRAMEWORK['controller'] .= 'php'; // TODO: Nicht '.php'
}
else {
  $_FRAMEWORK['format'] = strtolower(substr($_FRAMEWORK['controller'], strrpos($_FRAMEWORK['controller'], '.') + 1));
  $_FRAMEWORK['controller'] = substr($_FRAMEWORK['controller'], 0, strrpos($_FRAMEWORK['controller'], '.')).'.php';
  if ($_FRAMEWORK['format'] == 'html' || $_FRAMEWORK['format'] == 'htm')
    $_FRAMEWORK['format'] = 'php';
}


$_FRAMEWORK['view'] = $_FRAMEWORK['controller']; // Default view name is action name or controllers name
if ($_GET['action']) {
  if (strpos($_GET['action'], '/') !== false)
    $_FRAMEWORK['view'] = substr($_FRAMEWORK['controller'], 0, strrpos($_FRAMEWORK['controller'], '.')).'/'.$_GET['action'].($_FRAMEWORK['format'] == 'php' ? '.php' : ".{$_FRAMEWORK['format']}.php");
  else
    $_FRAMEWORK['view'] = $_GET['action'].'.php';
}


require 'commons/config.php';

$_FRAMEWORK['is_rendering'] = false;
$_FRAMEWORK['layout'] = $site_config['layout_prefix'].($site_config['presite'] ? 'pre_site.php' : 'default.php');
$_FRAMEWORK['status_code'] = 200;


// load lib functions

function is_xhr() {
  return @$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

require_once 'lib/logger.php';
$log = new Logger('log/log.txt', $debug ? 0 : 1);
$fwlog = new Logger('log/fwlog.txt', $debug ? 0 : 1, false);

$_SERVER['SCRIPT_NAME'] = str_replace('framework.php', 'controller/'.$_FRAMEWORK['controller'], $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace('framework.php', 'controller/'.$_FRAMEWORK['controller'], $_SERVER['SCRIPT_FILENAME']);
$_SERVER['PHP_SELF'] = str_replace('framework.php', 'controller/'.$_FRAMEWORK['controller'], $_SERVER['PHP_SELF']);

$log->info("\r\n".(is_xhr() ? 'XmlHttpRequest' : 'HttpRequest').' '.$_SERVER['REQUEST_METHOD'].' "'.$_SERVER['REQUEST_URI'].'" at '.date("Y-m-d H:i:s O"));
$fwlog->info("\r\n".(is_xhr() ? 'XmlHttpRequest' : 'HttpRequest').' '.$_SERVER['REQUEST_METHOD'].' "'.$_SERVER['REQUEST_URI'].'" at '.date("Y-m-d H:i:s O"));
$fwlog->info("  Use Controller ".$_FRAMEWORK['controller']);
if (!empty($_GET))
  $fwlog->info("  GET Parameter: ".var_export($_GET, true));
if (!empty($_POST))
  $fwlog->info("  POST Parameter: ".var_export($_POST, true));



/* untested
function framework_exception_wrapper($e) {
  global $log;
  
  $log->error("{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}
{$e->getTraceAsString()}");
  //throw $e;
  return false;
}
set_exception_handler('framework_exception_wrapper');
*/




$log->debug("Error reporting level: ".error_reporting());




function framework_error_wrapper($errno, $errstr, $errfile, $errline) {
  $log = new Logger(dirname(__FILE__).'/../log/log.txt', $debug ? 0 : 1);
  $fwlog = new Logger(dirname(__FILE__).'/../log/fwlog.txt', $debug ? 0 : 1);
  
  $backtrace = debug_backtrace();
  $log->error("{$errstr} in {$errfile} on line {$errline}
".export_backtrace());
  
  $fwlog->error("{$errstr} in {$errfile} on line {$errline}
".export_backtrace());
  
  return false; // continue with the normal error handler;
}
set_error_handler('framework_error_wrapper', error_reporting());


function fatal_handler($debug = 0) {
  $errfile = "unknown file";
  $errstr  = "shutdown";
  $errno   = E_CORE_ERROR;
  $errline = 0;

  $error = error_get_last();

  if( $error !== NULL) {
    $errno   = $error["type"];
    $errfile = $error["file"];
    $errline = $error["line"];
    $errstr  = $error["message"];
    
    if ($errno & error_reporting()) {
      
      $backtrace = debug_backtrace();
      
      $log = new Logger(dirname(__FILE__).'/../log/log.txt', $debug ? 0 : 1);
      $log->error("{$errstr} in {$errfile} on line {$errline}
      ".export_backtrace());
      
      $fwlog = new Logger(dirname(__FILE__).'/../log/fwlog.txt', $debug ? 0 : 1);
      $fwlog->error("{$errstr} in {$errfile} on line {$errline}
      ".export_backtrace());
    }
  }
}
register_shutdown_function('fatal_handler', $debug);


if (!extension_loaded('yaml')) {
  require_once 'lib/spyc/Spyc.php';
}
$_FRAMEWORK['translations'] = array();
require_once 'lib/locale.php';

require_once 'lib/base.php';
require_once 'lib/db.php';
$db_link = db_init();
require_once 'lib/abstract_model.php';
require_once 'lib/relations.php';
require_once 'lib/functions.php';


$model_files = scandir('model');
foreach ($model_files as $file) {
	// include app/model/*.php
	if (substr($file, -4) === '.php')
		require_once 'model/'.$file;
}

require_once 'lib/mailer.php';
require_once 'lib/view_helpers.php';

require_once 'lib/class.chip_password_generator.php';

// init session
require_once 'commons/session.php';

$additionalHeaderData = array();



$today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));



?>