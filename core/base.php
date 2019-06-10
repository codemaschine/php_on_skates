<?php

define('ONE_MINUTE', 60);
define('FIVE_MINUTES', 300);
define('TEN_MINUTES', 600);
define('FIFTEEN_MINUTES', 900);
define('TWENTY_MINUTES', 1200);
define('THIRTY_MINUTES', 1800);
define('HALF_HOUR', 1800);
define('FOURTY_MINUTES', 2400);
define('FOURTY_FIVE_MINUTES', 2700);
define('FIFTY_MINUTES', 3000);

define('ONE_HOUR', 3600);
define('TWO_HOURS', 7200);
define('THREE_HOURS', 10800);
define('FOUR_HOURS', 14400);
define('FIVE_HOURS', 18000);
define('SIX_HOURS', 21600);

define('ONE_DAY', 86400);
define('TWO_DAYS', 172800);
define('THREE_DAYS', 259200);
define('FOUR_DAYS', 345600);
define('FIVE_DAYS', 432000);
define('SIX_DAYS', 518400);
define('SEVEN_DAYS', 604800);
define('EIGHT_DAYS', 691200);
define('NINE_DAYS', 777600);
define('TEN_DAYS', 864000);
define('ELEVEN_DAYS', 950400);
define('TWELVE_DAYS', 1036800);
define('THIRTEEN_DAYS', 1123200);
define('FOURTEEN_DAYS', 1209600);

define('ONE_WEEK', 604800);
define('TWO_WEEKS', 1209600);
define('THREE_WEEKS', 1814400);
define('FOUR_WEEKS', 2419200);

define('THIRTY_DAYS', 2592000);
define('ONE_MONTH',   2592000);
define('ONE_YEAR', 31536000);

define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DB_DATE_FORMAT', 'Y-m-d');


class HttpStatusCode {
	const SWITCHING_PROTOCOLS = 101;
	const OK = 200;
	const CREATED = 201;
	const ACCEPTED = 202;
	const NONAUTHORITATIVE_INFORMATION = 203;
	const NO_CONTENT = 204;
	const RESET_CONTENT = 205;
	const PARTIAL_CONTENT = 206;
	const MULTIPLE_CHOICES = 300;
	const MOVED_PERMANENTLY = 301;
	const MOVED_TEMPORARILY = 302;
	const SEE_OTHER = 303;
	const NOT_MODIFIED = 304;
	const USE_PROXY = 305;
	const BAD_REQUEST = 400;
	const UNAUTHORIZED = 401;
	const PAYMENT_REQUIRED = 402;
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const METHOD_NOT_ALLOWED = 405;
	const NOT_ACCEPTABLE = 406;
	const PROXY_AUTHENTICATION_REQUIRED = 407;
	const REQUEST_TIMEOUT = 408;
	const CONFLICT = 408;
	const GONE = 410;
	const LENGTH_REQUIRED = 411;
	const PRECONDITION_FAILED = 412;
	const REQUEST_ENTITY_TOO_LARGE = 413;
	const REQUESTURI_TOO_LARGE = 414;
	const UNSUPPORTED_MEDIA_TYPE = 415;
	const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const EXPECTATION_FAILED = 417;
	const IM_A_TEAPOT = 418;
	const UNPROCESSABLE_ENTITY = 422;
	const LOCKED = 423;
	const FAILED_DEPENDENCY = 424;
	const UPGRADE_REQUIRED = 426;
	const PRECONDITION_REQUIRED = 428;
	const TOO_MANY_REQUESTS = 429;
	const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	const INTERNAL_SERVER_ERROR = 500;
	const NOT_IMPLEMENTED = 501;
	const BAD_GATEWAY = 502;
	const SERVICE_UNAVAILABLE = 503;
	const GATEWAY_TIMEOUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;
}

function time_zone_offset() {
  $dt = new DateTime("now");
  return $dt->getOffset();
}


function now() {
  return time();
}

function time_back_to($interval) {
  return time() - $interval;
}

function time_forward_to($interval) {
  return time() + $interval;
}

function beginning_of_day($time = NULL) {
  if ($time === NULL)
    $time = time();

  return mktime(0,0,0, date('m', $time), date('d', $time), date('Y', $time));
}

function end_of_day($time = NULL) {
  if ($time === NULL)
    $time = time();

  return mktime(23,59,59, date('m', $time), date('d', $time), date('Y', $time));
}



function sanitize($str) {
  global $db_link;
  return mysqli_real_escape_string($db_link, $str);
}

function h($str, $flags = ENT_COMPAT) {
  return htmlentities($str, $flags, 'UTF-8');
}



// file helpers

function validateFile($file_var, $label = "Datei", $allow_empty = false) {
  if (!isset($_FILES[$file_var]) || $_FILES[$file_var]['error'] != UPLOAD_ERR_OK) {
    if (!isset($_FILES[$file_var]))
      set_error("$label darf nicht leer sein.");
    else {
      switch ($_FILES[$file_var]['error']) {
        case UPLOAD_ERR_INI_SIZE:
          set_error("$label: Dateigrï¿½ï¿½e ist zu groï¿½.");
          break;
        case UPLOAD_ERR_PARTIAL:
          set_error("$label wurde nur teilweise hochgeladen.");
          break;
        case UPLOAD_ERR_NO_FILE:
          if (!$allow_empty)
            set_error("$label darf nicht leer sein.");
          break;
        default:
          set_error("$label: Unbekannter Fehler beim Upload. Bitte versuchen Sie es erneut.");
      }
    }
  }
}


function saveFile($file_var, $upload_dir) {
  $filename = basename($_FILES[$file_var]['name']);
  if (($sep_pos = strrpos($filename, '.')) && count($filename) - $sep_pos < 6) {
    $filename_base = substr($filename, 0, $sep_pos); // no dot
    $filename_ext = substr($filename, $sep_pos);     // with dot
  }


  $filename = str_replace($search, $replace, $filename);
  $filename = preg_replace("/[^a-zA-Z0-9\._-]*/", '', $filename);

  if (!move_uploaded_file($_FILES[$file_var]['tmp_name'],$upload_dir.'/'.$filename))
    die ("Die hochgeladene Datei konnte intern nicht verschoben werden. Bitte benachrichtigen Sie den Administrator.");
  return $filename;
}

// routes helpers

//$_view = basename($_SERVER['PHP_SELF']);

function redirect_to($target) {
  global $_FRAMEWORK;
  $_FRAMEWORK['redirect'] = true;
  // Path to skates.php:
  $pathname = dirname($_SERVER['PHP_SELF']);
  $controllername = $_FRAMEWORK['controller'];
  if($controllername[0] == "/")
    $controllername = substr($controllername,1,strlen($controllername));

  if (is_array($target)) {
  	$target = url_for($target);
  }
  elseif (preg_match('/^\w+$/', $target))
  	$target = $pathname . "/" . $controllername.'?action='.$target;
  elseif (preg_match('/^\w+(\/|#)\w+$/', $target)) {
  	$parts = strpos($target, '/') !== false ? explode('/', $target) : explode('#', $target);
  	$target = $pathname . "/" . $parts[0].'.php?action='.$parts[1];
  }

  $_FRAMEWORK['redirect_to'] = $target;
}

function forward_to($controller, $action = NULL, $layout = NULL, $status_code = NULL) {
  global $_FRAMEWORK;
  $_FRAMEWORK['controller'] = $controller;
  if ($layout)
    $_FRAMEWORK['layout'] = $layout;
  $_FRAMEWORK['view'] = $action ? $action.'.php' : $controller; // Default view name is action name or controllers name

  if ($action)
    $_GET['action'] = $action;

  if ($status_code)
    $_FRAMEWORK['status_code'] = $status_code;

  $_FRAMEWORK['forward'] = true;
}


function render($obj, $status_code = NULL) {
  global $_FRAMEWORK, $fwlog, $log, $site_config;
  $locals = array();
  $render_type = 'view';

  // parse paramter $obj, prepare variables
  if (is_array($obj)) {
    if ($obj['action']) {
      $view = $obj['action'];
      if ($obj['controller'])
        $view = $obj['controller'].'/'.$view;
    }
    elseif ($obj['partial']) {
      $view = substr($obj['partial'], 0, 1) != '_' ? '_'.$obj['partial'] : $obj['partial'];
      $log->debug("Partial view ist $view");

      if ($obj['controller'])
        $view = $obj['controller'].'/'.$view;
      $render_type = 'partial';
    }
    elseif ($obj['text'] !== NULL) {
      $render_type = 'text';
    }
    elseif ($obj['json'])
      $render_type = 'json';


    if ($obj['locals'])
      $locals = $obj['locals'];

    if ($obj['addJS'])
      $addJS = $obj['addJS'];
  }
  elseif ($obj === NULL) {
    $render_type = 'text';
  }
  else {
    $view = $obj;
    $addJS = $_FRAMEWORK['addJS'];
  }



  if ($_FRAMEWORK['is_rendering'] || $_FRAMEWORK['is_layouting']) {   // do rendering ...
    extract($GLOBALS, EXTR_REFS);
    if ($render_type == 'text') {
      if ($obj['return_output'])
        return $obj['text'];
      else
        echo $obj['text'];

      return;
    }
    elseif ($render_type == 'json') {
    	$json_render_options = $_FRAMEWORK['render_options'] ? $_FRAMEWORK['render_options'] : array();
    	if ($obj['only'])
    		$json_render_options['only'] = $obj['only'];
    	if ($obj['inlcude'])
    		$json_render_options['include'] = $obj['include'];
    	return skates_json_encode($obj['json'], $json_render_options);
    }


    //  adapt according to format
    if (strpos($view, '.') === false || !file_exists('views/'.$view)) {
      if (strpos($view, '.') !== false)
        $view = substr($view, 0, strrpos($view, '.'));

      if ($_FRAMEWORK['format'] != 'php')
        $view .= '.'.$_FRAMEWORK['format'];

      $view .= '.php';
    }

    if (strpos($view, '/') === false) // add controller path if not specified
      $view = ($_FRAMEWORK['is_layouting'] ? 'layout' : substr($_FRAMEWORK['controller'], 0, strrpos($_FRAMEWORK['controller'], '.'))).'/'.$view;


    // security check: is it allowed and possible to render this file?
    $view = str_replace('../', '', $view);

    if ($site_config['view_prefix']) {
      if (strpos($view, '/_') === false)
        $site_specific_view = substr_replace($view, $site_config['view_prefix'], strpos($view, '/') + 1, 0);
      else
        $site_specific_view = substr_replace($view, $site_config['view_prefix'], strpos($view, '/_') + 2, 0);
    }

    //$log->debug("--------==== existiert ".$site_specific_view);
    if ($site_config['view_prefix'] && file_exists('views/'.$site_specific_view))   // hinzufügen des Prefix bei Praybox, wenn spezielle view dafür vorhanden ist
      $view = $site_specific_view;
    elseif (!file_exists('views/'.$view)) {
      throw new ErrorException("View $view does not exist!");
    }


    foreach ($locals as $key => $value) {
      $$key = $value;
    }

    //$log->debug('$obj: '.var_export($obj, true));
    if (is_array($obj) && $obj['return_output'])
      ob_start();

    require 'views/'.$view;
    if (isset($addJS) && $addJS) {
      echo '<script type="text/javascript">'.$addJS.'</script>';
    }

    if (is_array($obj) && $obj['return_output']) {
      $buffer = ob_get_clean();
      return $buffer;
    }
  }
  else {                                          // ... or save this for rendering later
    if (!$status_code)
      $status_code = is_array($obj) && $obj['status_code']  ? $obj['status_code'] : 200;
    $log->debug("====> View die gerendert werden soll ist $view");
    $_FRAMEWORK['view'] = $view;
    $_FRAMEWORK['status_code'] = $status_code;
    $_FRAMEWORK['render_type'] = $render_type;
    $_FRAMEWORK['render_content'] = '';
    $_FRAMEWORK['render_options'] = array();
    if (is_array($obj)) {
    	$_FRAMEWORK['render_content'] = $obj['json'] ? $obj['json'] : $obj['text'];
    	$_FRAMEWORK['render_options'] = $obj;
    }
    $_FRAMEWORK['addJS'] = $addJS;
  }
}


/**
 *
 * @param string $view the name of the partial to be rendered. If the name does not start with an underscore it will be added automatically. You may also specify the folder for different
 * @param array|integer|bool $obj1 Can be array of arguments to export as variables inside the partial, a HTTP-Status-Code to set (if used in a controller) or a flag if the output should be returned instead of echoed.
 * @param array|integer|bool $obj2 like $obj1
 * @param array|integer|bool $obj3 like $obj1
 * @return Ambigous <void, unknown>
 */
function render_partial($view, $obj1 = NULL, $obj2 = NULL, $obj3 = NULL) {
  global $_FRAMEWORK, $log;

  $locals = array();
  $status_code = NULL;
  $return_output = false;

  if (is_array($obj1))
    $locals = $obj1;
  elseif (is_integer($obj1))
    $status_code = $obj1;
  elseif (is_bool($obj1))
    $return_output = $obj1;

  if (is_array($obj2))
    $locals = $obj2;
  elseif (is_integer($obj2))
    $status_code = $obj2;
  elseif (is_bool($obj2))
    $return_output = $obj2;

  if (is_array($obj3))
    $locals = $obj3;
  elseif (is_integer($obj3))
    $status_code = $obj3;
  elseif (is_bool($obj3))
    $return_output = $obj3;


  $last_slash = strrpos($view, '/');
  if ($last_slash === false)
    $controller = '';
  else {
    $controller = substr($view, 0, $last_slash);
    $view = substr($view, $last_slash + 1);
  }

  return render(array('partial' => $view, 'controller' => $controller, 'locals' => $locals, 'return_output' => $return_output), $status_code);
}

function render_text($text, $status_code = 200, $return_output = false) {
  return render(array('text' => $text, 'status_code' => $status_code, 'return_output' => $return_output));
}


/**
 *
 * @param AbstractModel|array $data one or more models to output in json-format
 * @param integer $status_code
 * @param string $return_output
 * @param array $options
 * @return the json-object
 */
function render_json($data, $status_code = 200, array $options = array()) {
	return render(array_merge($options, array('json' => $data, 'status_code' => $status_code)));
}

function yieldit() {
  global $_FRAMEWORK;
  $_FRAMEWORK['is_layouting'] = false;
  $_FRAMEWORK['is_rendering'] = true;
  render($_FRAMEWORK['view']);
  $_FRAMEWORK['is_layouting'] = true;
  $_FRAMEWORK['is_rendering'] = false;
}

function set_layout($filename) {
  global $_FRAMEWORK, $site_config;
  $_FRAMEWORK['layout'] = (is_array($site_config) && $site_config['layout_prefix'] ? $site_config['layout_prefix'] : '').$filename;
}

// flash helpers

/*
function set_error($message) {
  if (!isset($_SESSION['errors']))
    $_SESSION['errors'] = array();
  array_push($_SESSION['errors'], $message);
}*/
function set_error($message) {
  set_flash($message, 'error');
}

/*
function is_error() {
  return isset($_SESSION['errors']) && !empty($_SESSION['errors']);
}*/

/*
function set_errors($msgArray) {
  if (!isset($_SESSION['errors']))
    $_SESSION['errors'] = $msgArray;
  else
    array_merge($_SESSION['errors'], $msgArray);
}*/
function set_errors($msgArray) {
  foreach ($msgArray as $key => $message) {
    //if (is_string($key))
    //  set_flash(ucfirst($key).' '.$message, 'error');
    //else
      set_flash($message, 'error');
  }
}
/*
function show_errors($error_hash = NULL) {
  $errors = array();
	if (isset($_SESSION['errors'])) {
	  $errors = $_SESSION['errors'];
		unset($_SESSION['errors']);
  }

  if (isset($error_hash))
    array_merge($errors, $error_hash);


	if (!empty($errors)) {
    echo '<div class="errors"><h3>Bitte Ã¼berprÃ¼fe deine Eingaben:</h3><p>';
    foreach ($errors as $errorMsg) {
      echo $errorMsg.'<br/>';
    }
    echo '</p></div>';
  }
}*/

function set_flash($message, $type = 'notice', $duration = NULL) {
  $_SESSION['flash'][$type][] = $message;
}

function is_flash() {
  return isset($_SESSION['flash']) && $_SESSION['flash'];
}

function show_flash($obj = NULL) {
  $type = NULL; $in = '';
  if (is_array($obj)) {
    extract($obj, EXTR_OVERWRITE);
  }
  else
    $type = $obj;

  $flash_message_temp_id = 'ft'.(time() % 1000).rand(0,1000);

  if (is_flash()) {
    echo '<div id="'.$flash_message_temp_id.'" class="alertbox">';
    if ($type) {
      if (isset($_SESSION['flash'][$type])) {
        foreach($_SESSION['flash'][$type] as $m){
      		echo "<div class=\"$type\">".$m."</div>\r\n";
        }
      	unset($_SESSION['flash'][$type]);
      }
    }
    else {
      foreach ($_SESSION['flash'] as $type => $messages) {
        foreach($_SESSION['flash'][$type] as $m){
      		echo "<div class=\"$type\">$m</div>\r\n";
        }
      }
      unset($_SESSION['flash']);
    }
    echo "</div>";
    if (!$keep) {
      echo "
      <script>
      ".(is_xhr() && $in ? "$('#$flash_message_temp_id').appendTo($('$in'));" : '')."
      $('$in .alertbox').effect('highlight',1000).delay(5000).animate({opacity: 0}, 3000, function(){ $('$in .alertbox').slideUp(); });
      </script>";
    }
  }
}

function show_errors() {
  return show_flash('error');
}


function pop_flash($obj = NULL) {
  $type = NULL; $in = '';
  if (is_array($obj)) {
    extract($obj, EXTR_OVERWRITE);
  }
  else
    $type = $obj;

  $r = null;

  if (is_flash()) {
	  if ($type) {
	  	if (isset($_SESSION['flash'][$type])) {
	  		$r = $_SESSION['flash'][$type];
	  		unset($_SESSION['flash'][$type]);
	  	}
	  }
	  else {
	  	$r = $_SESSION['flash'];
	  	unset($_SESSION['flash']);
	  }
  }

  return $r;
}

function set_debug_msg($message) {
  $_SESSION['debug_msg'] = $message;
}

function show_debug_msg() {
  if (isset($_SESSION['debug_msg'])) {
    echo '<p>'.$_SESSION['debug_msg'].'</p>';
    unset($_SESSION['debug_msg']);
  }
}

function date_to_db_format($d) {
  if (!is_string($d))
    return null;

  $d_ary = explode('.', $d);
  if (count($d_ary) !== 3)
    return $d;

  return $d_ary[2].'-'.$d_ary[1].'-'.$d_ary[0];
}

function date_to_readable($d) {
  if (!is_string($d))
    return null;

  $d_ary = explode('-', $d);
  if (count($d_ary) !== 3)
    return $d;

  return $d_ary[2].'.'.$d_ary[1].'.'.$d_ary[0];
}




function is_plural($str) {
  $str = strtolower($str);
  $irregular = irregular_words_ary();
  if (in_array($str, $irregular))
    return true;
  elseif (array_key_exists($str, $irregular))
    return false;
  return substr($str, -1) == 's';
}

function is_singular($str) {
  $str = strtolower($str);
  $irregular = irregular_words_ary();
  if (array_key_exists($str, $irregular))
    return true;
  elseif (in_array($str, $irregular))
    return false;
  return substr($relations_name, -1) != 's';
}

function pluralize($str) {
  $irregular = irregular_words_ary();
  if (array_key_exists(strtolower($str), $irregular))
    return $irregular[strtolower($str)];
  return $str.'s';
}

function singularize($str) {
  $irregular = array_flip(irregular_words_ary());
  if (array_key_exists(strtolower($str), $irregular))
    return $irregular[strtolower($str)];
  return substr($str, 0, strlen($str) - 1);
}


function irregular_words_ary() {
  return array(
    'person' => 'people',
    'fish' => 'fish'
  );
}


// code snippet from a comment from http://php.net/manual/de/ini.core.php
function let_to_num($v){ //This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
    $l = substr($v, -1);
    $ret = substr($v, 0, -1);
    switch(strtoupper($l)){
    case 'P':
        $ret *= 1024;
    case 'T':
        $ret *= 1024;
    case 'G':
        $ret *= 1024;
    case 'M':
        $ret *= 1024;
    case 'K':
        $ret *= 1024;
        break;
    }
    return $ret;
}


function var_inspect($var) {
  if ($var === NULL)
    return 'null';
  elseif (is_string($var))
    return "\"$var\"";
  elseif (is_int($var))
    return $var;
  elseif (is_array($var)) {
    $output = '';
    $cnt = 0;
    foreach ($var as $key => $value) {
      $output .= ($cnt == 0 ? '' : ', ').var_inspect($key).' => '.var_inspect($value);
      $cnt++;
    }
    return "[$output]";
  }
  elseif (is_object($var) && method_exists($var, '__toString'))
    return $var->__toString($var);
  else
    return var_export($var, true);
}

function array_model_extract(array $ary, $field) {
  $values = array();
  foreach ($ary as $model) {
    if ($model instanceof AbstractModel)
      $values []= $model->get($field);
  }
  return $values;
}

function array_model_extract_ids(array $ary) {
  return array_model_extract($ary, 'id');
}



/**
 *
 * @param string $mutex_name unique mutex name
 * @param integer $unlock_timeout Timout to automatically release the lock (in minutes)
 * @throws Exception
 */
function cron_mutex_trylock($mutex_name, $unlock_timeout = NULL) {
	$tmpDir = '/tmp';
	if (!is_writable($tmpDir))
		throw new Exception("Ordner $tmpDir ist nicht beschreibar, Mutex-Lock kann nicht gesetzt werden.");

	$lock_name = "skates_cron_mutex_".preg_replace('/[^a-zA-Z\.-]+/', '_', $mutex_name);

  $lockfile = "$tmpDir/$lock_name.lock";
  $prelockfile = "$tmpDir/{$lock_name}_pre.lock";

  // ---


	$is_free = true;

  $fp = fopen($prelockfile, 'w+');   // Versuche Lock zu reservieren. KRITISCHER ABSCHNITT!! Daher extra Lock dafür
  flock($fp, LOCK_EX);
  if (file_exists($lockfile)) { //
  	$is_free = false;

  	if ($unlock_timeout) {  // prüfen, ob automatisch geunlocked werden muss.
  		$lock_time = intval(file_get_contents($lockfile));

  		if ($lock_time > 0 && time() > $lock_time + ($unlock_timeout * 60))
  			$is_free = true;

  	}
  }

  if ($is_free)
  	file_put_contents($lockfile, time());  // ist frei. --> locken
  flock($fp, LOCK_UN);
  fclose($fp);

  return $is_free;
}


function cron_mutex_unlock($mutex_name) {
	$tmpDir = '/tmp';

	$lock_name = "skates_cron_mutex_".preg_replace('/[^a-zA-Z\.-]+/', '_', $mutex_name);

	$lockfile = "$tmpDir/$lock_name.lock";

	if (file_exists($lockfile))
		unlink($lockfile);

}


function is_json() {
	global $_FRAMEWORK;
	return $_FRAMEWORK['format'] == 'json';
}

function skates_json_encode($o, array $options = array()) {
	if (is_a($o, AbstractModel)) {
		return  $o->toJson($options);
	}
	elseif (is_array($o)) {
		if (is_assoc($o)) {
			$json_o = array();
			foreach ($o as $key => $value)
				array_push($json_o, "\"$key\":".skates_json_encode($value, $options));
			return "{".join(',', $json_o)."}";
		}
		else {
			$json_o = array();
			foreach ($o as $key => $value)
				array_push($json_o, skates_json_encode($value, $options));
			return "[".join(',', $json_o)."]";
		}
	}
	else return json_encode($o);
}


function authenticated(array $actions = array(), $exclude = false) {
	if (empty($actions))
		$condition = true;
	elseif ($exclude == false)
	  $condition = $_GET['action'] && in_array($_GET['action'], $actions); // only
	else
		$condition = !$_GET['action'] || !in_array($_GET['action'], $actions); // except


	if (!is_logged_in() && $condition){
		set_flash("Du wurdest ausgeloggt, weil du ".sitename()." längere Zeit nicht benutzt hast. Bitte logge dich neu ein.");
		if (is_json())
			render_json_response(null, 400, 'login required');
		elseif (is_xhr()) {
			render_partial('shared/show_flash', 418);
		}
		else
			redirect_to('index.php');

		return false;
	}
	else return true;
}

function authenticated_for(array $actions) {
	return authenticated($actions);
}
function authenticated_except(array $actions) {
	return authenticated($actions, true);
}


/**
 *
 * @param array|string $req_set does the request belong to this set of controllers (and optionally of their actions if specified)?
 */
function is_request_of($req_set) {
	global $_FRAMEWORK, $log;

	if (is_string($req_set))
		$req_set = array($req_set);

	$cur_controller = $_FRAMEWORK['controller'];
	$cur_action = $_GET['action'];

	$log->debug('cur_action: '.$cur_action);

	foreach ($req_set as $key => $val) {  // check, if current controller and action is in the set. immediately return true, if yes
		if (is_int($key)) { // if key is numeric, then $val is the controller and there're no actions specified for this controller. So check only for the controller
			if ($val == $cur_controller) return true;
		}
		elseif ($key == $cur_controller) {
			if (is_array($val)) {
				foreach ($val as $action) {
					if ($action == $cur_action) return true;
				}
			}
			else {
				if ($val == $cur_action) return true;
			}
		}
	}

	return false; // not found, so it is not in the set
}



function sitename($short = 0) {
  global $site_config;
  if($short)
   	return $site_config['sitename_short'];
  else
  	return $site_config['sitename'];
}
