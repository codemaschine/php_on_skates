<?php

$abs_base_path = mb_substr(dirname(__FILE__),0,mb_strrpos(dirname(__FILE__),'/'));
define('ROOT_DIR', $abs_base_path.'/');
define('SKATES_DIR', $abs_base_path.'/skates/');
define('APP_DIR', $abs_base_path.'/app/');
define('ROOT_PATH', ROOT_DIR);
define('SKATES_PATH', SKATES_DIR);
define('APP_PATH', APP_DIR);

// Include composer packages if exists
if (file_exists(ROOT_DIR.'vendor/autoload.php')) {
  include_once ROOT_DIR.'vendor/autoload.php';
}

chdir('app');

// ---- Do init -----
/**
 * @var array{controller: ?string, format: ?string, route: ?string, redirect: bool, redirect_to: ?string, render_type: ?string, forward: bool, skip_controller: bool}
 */
$_FRAMEWORK = [
  'controller' => null,
  'format' => null,
  'route' => null,
  'redirect' => false,
  'redirect_to' => null,
  'render_type' => null,
  'forward' => false,
  'skip_controller' => false,
];

if (!empty($_GET['frameworkController'])) {
  $_FRAMEWORK['controller'] = $_GET['frameworkController'];
  unset($_GET['frameworkController']);
}

require SKATES_DIR.'core/init.php';

try {
  if (!$_FRAMEWORK['redirect']) {
    do {
      $_FRAMEWORK['forward'] = false;

      require APP_DIR.'commons/pre_routing.php';
      if ($_FRAMEWORK['redirect'] || $_FRAMEWORK['render_type']) { // abort if redirect() or render() is called. Don't go into the controller
        break;
      }
      if (!(($_FRAMEWORK['allow_plain_routing'] ?? false) && file_exists(APP_DIR.'controller/'.$_FRAMEWORK['controller']))) {
        require_once SKATES_DIR.'core/skates_router.php';
        require_once APP_DIR.'routes.php';
        $router = new SkatesRouter();
        $router->set404(function() use ($router) {
          die("Page {$router->getCurrentUri()} not found!");
        });
        load_routes($router);
        $router->run();
      }
      if ($_FRAMEWORK['redirect'] || $_FRAMEWORK['render_type']) { // abort if redirect() or render() is called. Don't go into the controller
        break;
      }
      require APP_DIR.'commons/pre_controller.php';
      if ($_FRAMEWORK['redirect'] || $_FRAMEWORK['render_type']) { // abort if redirect() or render() is called. Don't go into the controller
        break;
      }
      if (!$_FRAMEWORK['skip_controller']) {
        if (!file_exists(APP_DIR.'controller/'.$_FRAMEWORK['controller'])) {
          die('Controller "'.$_FRAMEWORK['controller'].'" not found!');
        }
        require APP_DIR.'controller/'.$_FRAMEWORK['controller']; // execute controller-action
      }
      require APP_DIR.'commons/post_controller.php';
      if ($_FRAMEWORK['redirect']) {
        break;
      }
    } while ($_FRAMEWORK['forward']);
  }

  // ---- Do Redirect or load view
  if ($_FRAMEWORK['redirect']) {
    require APP_DIR.'commons/post_rendering.php';

    $uri = mb_substr($_FRAMEWORK['redirect_to'], 0, 4) == 'http' ? $_FRAMEWORK['redirect_to'] : $c_base_url.$_FRAMEWORK['redirect_to'];
    if (is_json()) {
      $_FRAMEWORK['is_rendering'] = true;
      echo render_json_response(null, 302, 'redirect() called instead of render_json(). This indicates that JSON is not implemented for this request where a HTML-Request would lead to this redirect.', ['location' => $uri]);
    } else {
      header('Location: '.$uri);
    }

    $fwlog->info('Redirect to '.$uri);
    exit();
  } else {
    ob_start();
    if ($_FRAMEWORK['format'] == 'json' && (!isset($_FRAMEWORK['json_pass_http_status']) || !$_FRAMEWORK['json_pass_http_status'])) { // At json-requests always return 200, because the status of request ist handled inside of the JSON-object!
      $_FRAMEWORK['status_code'] = 200;
    }

    $header_type = 'text';
    $header_subtype = 'plain';

    switch ($_FRAMEWORK['format']) {
      case 'xml': $header_subtype = 'xml';
        break;
      case 'js': $header_subtype = 'javascript';
        break;
      case 'php':
      case 'htm':
      case 'html': $header_subtype = 'html';
        break;
      case 'css': $header_subtype = 'css';
        break;
      case 'json': $header_type = 'application';
        $header_subtype = 'json';
        if (!$_FRAMEWORK['render_type'] || $_FRAMEWORK['render_type'] != 'json') {
          render_json(response_with(null, 501, 'JSON-Renderer not called in Controller! This call might not be implemented.'));
        }
        break;
    }

    // security check: is it allowed and possible to render this file?
    if ($_FRAMEWORK['render_type'] != 'text') {
      //if (mb_strpos($_FRAMEWORK['view'], '.') === false)
      //  $_FRAMEWORK['view'] .= '.php';

      if (mb_strpos($_FRAMEWORK['view'], '/') === false) { // add controller path if not specified
        $_FRAMEWORK['view'] = mb_substr($_FRAMEWORK['controller'], 0, mb_strrpos($_FRAMEWORK['controller'], '.')).'/'.$_FRAMEWORK['view'];
      }

      //$_FRAMEWORK['view'] = str_replace('../', '', $_FRAMEWORK['view']);
      //if (!file_exists('views/'.$_FRAMEWORK['view'])) {
      //  throw new ErrorException("View {$_FRAMEWORK['view']} does not exist!");
      //}
    }
    //  ________________

    //  adapt layout and view according to format
    if (mb_substr($_FRAMEWORK['layout'], -4) == '.php') {
      $_FRAMEWORK['layout'] = mb_substr($_FRAMEWORK['layout'], 0, -4);
    }

    if ($_FRAMEWORK['format'] != 'php') {
      $_FRAMEWORK['layout'] .= '.'.$_FRAMEWORK['format'];
    }

    $_FRAMEWORK['layout'] .= '.php';

    //  ________________

    $echoLock=0;

    if ($_FRAMEWORK['render_type'] == 'text') {
      $fwlog->info('Render '.($_FRAMEWORK['render_content'] ? 'Text' : 'Nothing (except flash-messages if XHR)').', Code '.$_FRAMEWORK['status_code']);
      echo $_FRAMEWORK['render_content'];
      if (is_xhr()) {
        show_flash();
      }
    } elseif ($_FRAMEWORK['render_type'] == 'json') {
      $_FRAMEWORK['is_rendering'] = true;
      $fwlog->info('Render JSON, Code '.$_FRAMEWORK['status_code']);
      echo render_json($_FRAMEWORK['render_content'], $_FRAMEWORK['status_code']);
    } elseif ($_FRAMEWORK['render_type'] == 'partial' || !file_exists('views/layout/'.$_FRAMEWORK['layout'])) { // || is_xhr()
      if (!file_exists('views/layout/'.$_FRAMEWORK['layout'])) {
        $fwlog->warning('WARNING: Layout views/layout/'.$_FRAMEWORK['layout'].' not found! Fallback to rendering as partial.');
      }
      $fwlog->info('Render Partial "'.$_FRAMEWORK['view'].'", Format: '.$_FRAMEWORK['format'].', Code '.$_FRAMEWORK['status_code']);
      if (!empty($_FRAMEWORK['view'])) {
        $_FRAMEWORK['is_rendering'] = true;
        if ($_FRAMEWORK['render_type'] == 'partial') {
          render_partial($_FRAMEWORK['view'], is_array($_FRAMEWORK['render_options']) ? $_FRAMEWORK['render_options']['locals'] : '', $_FRAMEWORK['status_code']);
        } else {
          render($_FRAMEWORK['view']);
        }
      }
      show_flash(); // dump the flash messages, if they aren't dumped yet. Usefull for bugfixing, but it can also be turned off
    } else {
      $fwlog->info('Render View "'.$_FRAMEWORK['view'].'", Format: '.$_FRAMEWORK['format'].', Code '.$_FRAMEWORK['status_code']);

      $_FRAMEWORK['is_layouting'] = true;
      include APP_DIR.'views/layout/'.$_FRAMEWORK['layout']; // this also renders the view by calling yieldit();
    }

    require APP_DIR.'commons/post_rendering.php';
    if ($_FRAMEWORK['status_code'] != 200) {
      header('HTTP/1.1 '.$_FRAMEWORK['status_code'].' USER ERROR');
    }

    header("Content-Type: $header_type/$header_subtype; charset=utf-8");
    ob_end_flush();
  }
} catch (Throwable $e) {
  if (empty($_FRAMEWORK['docker'])) {
    $log->error("{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n{$e->getTraceAsString()}");
  }
  $fwlog->error("{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n{$e->getTraceAsString()}");

  if ($e instanceof SkatesException) {
    ob_end_clean();
    header('HTTP/1.1 500 Internal Server Error');
    switch ($e->callback()) {
      case 1:
        if (is_xhr()) {
          $_FRAMEWORK['is_rendering'] = true;
          echo render_json_response(null, 500, "{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n".getExceptionTraceAsString($e));
        } else {
          set_error($e->getMessage());
          redirect_to($e->target());
          header('Location: '.$_FRAMEWORK['redirect_to']);
          $fwlog->info('Redirect to '.$_FRAMEWORK['redirect_to']);
        }
        exit();
        break;
      default:
        throw $e;
        break;
    }
  }

  if (is_json()) {
    $_FRAMEWORK['is_rendering'] = true;
    header('Content-Type: application/json; charset=utf-8');
    echo render_json_response(null, 500, "Exception in File {$e->getFile()} on Line {$e->getLine()}: {$e->getMessage()}");
  } elseif ($environment == 'production') {
    die('<html><head></head><body><p style="margin: 100px 0; text-align: center; font-family: \'Droid Sans\',sans-serif; font-size: 15px; color: #ff0000;"><b>Ohje! Ein Fehler ist aufgetreten. Bitte versuche es nochmal und sag uns bitte Bescheid, wenn das wieder passiert.</b></p></body></html>');
  } else {
    throw $e;
  }
}
