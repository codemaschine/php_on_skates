<?php

$abs_base_path = substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'/'));
define('ROOT_DIR', $abs_base_path.'/');
define('SKATES_DIR', $abs_base_path.'/skates/');
define('APP_DIR', $abs_base_path.'/app/');
define('ROOT_PATH', ROOT_DIR);
define('SKATES_PATH', SKATES_DIR);
define('APP_PATH', APP_DIR);


chdir('app');

/*
var_dump($_POST);
var_dump($_FILES);
exit();
*/

// ---- Do init -----
$_FRAMEWORK = array();
if ($_GET['frameworkController']) {
  $_FRAMEWORK['controller'] = $_GET['frameworkController'];
  unset($_GET['frameworkController']);
}


require SKATES_DIR.'core/init.php';


try {
  if (!$_FRAMEWORK['redirect']) {
    do {
      $_FRAMEWORK['forward'] = false;
      // ---- Load Controller
      if (!file_exists('controller/'.$_FRAMEWORK['controller'])) {
        if (!file_exists('controller/404.php'))
          die("Page {$_FRAMEWORK['controller']} not found!");
        else
          $_FRAMEWORK['controller'] = '404.php';
      }

      require APP_DIR.'commons/pre_controller.php';
      if ($_FRAMEWORK['redirect'] || $_FRAMEWORK['render_type'])  // abort if redirect() or render() is called. Don't go into the controller
        break;
      require APP_DIR.'controller/'.$_FRAMEWORK['controller'];  // execute controller-action
      require APP_DIR.'commons/post_controller.php';
      if ($_FRAMEWORK['redirect'])
        break;

    }
    while ($_FRAMEWORK['forward']);
  }


  // ---- Do Redirect or load view
  if ($_FRAMEWORK['redirect']) {
    require APP_DIR.'commons/post_rendering.php';

    $uri = substr($_FRAMEWORK['redirect_to'], 0, 4) == 'http' ? $_FRAMEWORK['redirect_to'] : $c_base_url.$_FRAMEWORK['redirect_to'];
    if (is_json()) {
    	$_FRAMEWORK['is_rendering'] = true;
    	echo render_json_response(null, 302, 'redirect() called instead of render_json(). This indicates that JSON is not implemented for this request where a HTML-Request would lead to this redirect.', array('location' => $uri));
    }
    else
      header("Location: ".$uri);


    $fwlog->info("Redirect to ".$uri);
    exit();
  }
  else {
    if ($_FRAMEWORK['format'] == 'json') // At json-requests always return 200, because the status of request ist handled inside of the JSON-object!
      $_FRAMEWORK['status_code'] = 200;

    if ($_FRAMEWORK['status_code'] != 200)
      header("HTTP/1.1 ".$_FRAMEWORK['status_code'].' USER ERROR');


    $header_type = 'text';
    $header_subtype = 'plain';

    switch ($_FRAMEWORK['format']) {
      case 'xml': $header_subtype = 'xml'; break;
      case 'js': $header_subtype = 'javascript'; break;
      case 'php':
      case 'htm':
      case 'html': $header_subtype = 'html'; break;
      case 'css': $header_subtype = 'css'; break;
      case 'json': $header_type = 'application'; $header_subtype = 'json';
        if (!$_FRAMEWORK['render_type'] || $_FRAMEWORK['render_type'] != 'json') {
        	render_json(response_with(null, 501, 'JSON-Renderer not called in Controller! This call might not be implemented.'));
        }
        break;
    }

    header("Content-Type: $header_type/$header_subtype; charset=utf-8");

    // security check: is it allowed and possible to render this file?
    if ($_FRAMEWORK['render_type'] != 'text') {
      //if (strpos($_FRAMEWORK['view'], '.') === false)
      //  $_FRAMEWORK['view'] .= '.php';

      if (strpos($_FRAMEWORK['view'], '/') === false) // add controller path if not specified
        $_FRAMEWORK['view'] = substr($_FRAMEWORK['controller'], 0, strrpos($_FRAMEWORK['controller'], '.')).'/'.$_FRAMEWORK['view'];

      //$_FRAMEWORK['view'] = str_replace('../', '', $_FRAMEWORK['view']);
      //if (!file_exists('views/'.$_FRAMEWORK['view'])) {
      //  throw new ErrorException("View {$_FRAMEWORK['view']} does not exist!");
      //}
    }
    //  ________________

    //  adapt layout and view according to format
    if (substr($_FRAMEWORK['layout'], -4) == '.php')
      $_FRAMEWORK['layout'] = substr($_FRAMEWORK['layout'], 0, -4);

    if ($_FRAMEWORK['format'] != 'php')
      $_FRAMEWORK['layout'] .= '.'.$_FRAMEWORK['format'];

    $_FRAMEWORK['layout'] .= '.php';


    //  ________________

    $echoLock=0;

    if ($_FRAMEWORK['render_type'] == 'text') {
      $fwlog->info('Render '.($_FRAMEWORK['render_content'] ? 'Text' : 'Nothing (except flash-messages if XHR)').', Code '.$_FRAMEWORK['status_code']);
      echo $_FRAMEWORK['render_content'];
      if (is_xhr())
        show_flash();
    }
    elseif ($_FRAMEWORK['render_type'] == 'json') {
    	$_FRAMEWORK['is_rendering'] = true;
    	$fwlog->info('Render JSON, Code '.$_FRAMEWORK['status_code']);
    	echo render_json($_FRAMEWORK['render_content'], $_FRAMEWORK['status_code']);
    }
    elseif ($_FRAMEWORK['render_type'] == 'partial' || !file_exists('views/layout/'.$_FRAMEWORK['layout'])) { // || is_xhr()
      if (!file_exists('views/layout/'.$_FRAMEWORK['layout']))
          $fwlog->warning('WARNING: Layout views/layout/'.$_FRAMEWORK['layout'].' not found! Fallback to rendering as partial.');
      $fwlog->info('Render Partial "'.$_FRAMEWORK['view'].'", Format: '.$_FRAMEWORK['format'].', Code '.$_FRAMEWORK['status_code']);
      if (!empty($_FRAMEWORK['view'])) {
        $_FRAMEWORK['is_rendering'] = true;
        render($_FRAMEWORK['view']);
      }
      show_flash(); // dump the flash messages, if they aren't dumped yet. Usefull for bugfixing, but it can also be turned off
    }
    else {
      $fwlog->info('Render View "'.$_FRAMEWORK['view'].'", Format: '.$_FRAMEWORK['format'].', Code '.$_FRAMEWORK['status_code']);



      $_FRAMEWORK['is_layouting'] = true;
      include APP_DIR.'views/layout/'.$_FRAMEWORK['layout']; // this also renders the view by calling yieldit();

      /*
      if ($debug) {
        echo '<div class="debug">FRAMEWORK: ';
        var_dump($_FRAMEWORK, true);
        echo '</div>';

      }*/

    }

    require APP_DIR.'commons/post_rendering.php';
  }

} catch (ErrorException $e) {
  $log->error("{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n{$e->getTraceAsString()}");
  $fwlog->error("{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n{$e->getTraceAsString()}");
  if (is_json()) {
  	$_FRAMEWORK['is_rendering'] = true;
  	echo render_json_response(null, 500, "Exception in File {$e->getFile()} on Line {$e->getLine()}: {$e->getMessage()}");
  }
  elseif ($environment == 'production')
    die('<html><head></head><body><p style="margin: 100px 0; text-align: center; font-family: \'Droid Sans\',sans-serif; font-size: 15px; color: #ff0000;"><b>Ohje! Ein Fehler ist aufgetreten. Bitte versuche es nochmal und sag uns bitte Bescheid, wenn das wieder passiert.</b></p></body></html>');
  else
    throw $e;
}
