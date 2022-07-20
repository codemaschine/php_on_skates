<?php
require_once __DIR__.'/bramus_router.php';


class SkatesRouter extends \Bramus\Router\Router
{

  public function resources($name, $fn = null, $path = null, $only = ['index', 'new', 'create', 'show', 'edit', 'update', 'destroy']) {
    $this->mount($path ?? "/$name", function() use ($name, $only, $fn) {
      if (is_callable($fn)) {
        $this->mount('/{'.$name.'_id}', function () use ($fn) {
          $fn();
        });
      }
      if (in_array('index', $only)) {
        $this->get('/', "$name#index");
      }
      if (in_array('new', $only)) {
        $this->get('/new', "$name#new");
      }
      if (in_array('create', $only)) {
        $this->post('/', "$name#create");
      }
      if (in_array('show', $only)) {
        $this->get('/{id}', "$name#show");
      }
      if (in_array('edit', $only)) {
        $this->get('/{id}/edit', "$name#edit");
      }
      if (in_array('update', $only)) {
        $this->post('/{id}', "$name#update");
      }
      if (in_array('destroy', $only)) {
        $this->get('/{id}/destroy', "$name#destroy");
      }
    });
  }

  protected function invoke($fn, $params = array()) {
    global $_FRAMEWORK;
    $_FRAMEWORK['router_params'] = $params;
    if (is_string($fn)) {
      $matches = [];
      if (preg_match('/(?<controller>\w+)[#\/](?<action>\w+)/', $fn, $matches) !== false) {
        $_FRAMEWORK['controller'] = $matches['controller'] . '.php';
        $_GET['action'] = $matches['action'];
        $_FRAMEWORK['view'] = $matches['action'];
        $_FRAMEWORK['format'] = 'php';
        foreach ($params as $key => $param) {
          $params[$key] = urldecode($param);
        }
        $_GET = array_merge($_GET, $params);
      }
    } else {
      parent::invoke($fn, [$params]);
    }
  }

  protected function patternMatches($pattern, $uri, &$matches, $flags = 0) {

    $pattern = str_replace(['[', ']'], ['(?:', ')?'], $pattern);

    // Replace all curly braces matches {} into word patterns (like Laravel)
    $pattern = preg_replace('/\/{([^\/]*?)}/', '/(?<$1>[^/]*?)', $pattern);

    return boolval(preg_match('#^' . $pattern . '$#', $uri, $matches));
  }

  protected function handle($routes, $quitAfterRun = false) {
    // Counter to keep track of the number of routes we've handled
    $numHandled = 0;

    // The current page URL
    $uri = $this->getCurrentUri();

    // Loop all routes
    foreach ($routes as $route) {

      // get routing matches
      $is_match = $this->patternMatches($route['pattern'], $uri, $matches);

      // is there a valid match?
      if ($is_match) {

        // Rework matches to only contain the matches, not the orig string
        $matches = array_slice($matches, 1);

        $params = [];

        foreach ($matches as $key => $match) {
          if (!is_int($key)) {
            $params[$key] = trim($match, '/');
          }
        }

        // Call the handling function with the URL parameters if the desired input is callable
        $this->invoke($route['fn'], $params);

        ++$numHandled;

        // If we need to quit, then quit
        if ($quitAfterRun) {
          break;
        }
      }
    }

    // Return the number of routes handled
    return $numHandled;
  }






  protected $routes = [];
  protected $all_routes = [];

  public function print_routes() {
    $max_methods = 6;
    $max_name = 10;
    foreach ($this->all_routes as $route) {
      $max_methods = strlen($route['methods']) > $max_methods ? strlen($route['methods']) : $max_methods;
      $max_name = strlen($route['name']) > $max_name ? strlen($route['name']) : $max_name;
    }
    $mask = "%-".$max_methods."s | %-".$max_name."s | %s\n";
    echo "\n";
    printf($mask, 'Method', 'Route Name', 'Route');
    echo "\n";
    foreach ($this->all_routes as $route) {
      printf($mask, $route['methods'], $route['name'], $route['pattern']);
    }
  }

  /**
   * @return false|string
   */
  public function get_url(string $route_name, &$params = []) {
    if ($this->routes[$route_name] ?? null) {
      $route = $this->routes[$route_name];
      if ($this->has_optional($route)) {
        $route = $this->process_optional($route, $params);
      }
      $route = $this->replace_vars($route, $params);
      return trim($route, '/');
    }
    return false;
  }

  public function has_route(string $route_name) {
    return boolval($this->routes[$route_name] ?? null);
  }

  protected function has_optional($pattern) {
    return preg_match('/(\[(?:[^\[\]]+|(?R))*\])/', $pattern) !== false;
  }

  protected function process_optional($pattern, &$params) {
    return preg_replace_callback('/(\[(?:[^\[\]]+|(?R))*\])/', function ($matches) use (&$params) {
      $match = substr($matches[1], 1, -1);
      if ($this->has_optional($match)) {
        $match = $this->process_optional($match, $params);
      }
      return $this->replace_vars($match, $params, true);
    }, $pattern);
  }

  protected function replace_vars($pattern, &$params, $return_nothing_if_param_missing = false) {
    global $_FRAMEWORK;
    $missing_param = false;
    $pattern = preg_replace_callback('/\/{([^\/]*?)}/', function ($matches) use (&$params, $_FRAMEWORK, &$missing_param) {
      $param = $params[$matches[1]] ?? $_FRAMEWORK['router_params'][$matches[1]] ?? null;
      if ($param) {
        unset($params[$matches[1]]);
        return '/'.$param;
      }
      $missing_param = true;
      return '/';
    }, $pattern);
    return $missing_param && $return_nothing_if_param_missing ? '' : $pattern;
  }

  public function match($methods, $pattern, $fn, ?string $route_name = null) {
    if (is_string($fn)) {
      $fn = str_replace('/', '#', $fn);
      if (!$route_name) {
        $route_name = $fn;
      }
    }
    parent::match($methods, $pattern, $fn);
    if ($route_name) {
      $pattern = $this->baseRoute . '/' . trim($pattern, '/');
      $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

      $this->routes[$route_name] = $pattern;
    }
    if (php_sapi_name() === 'cli') {
      $this->all_routes[] = ['pattern' => $pattern, 'name' => $route_name ?? '', 'methods' => $methods];
    }
  }

  public function all($pattern, $fn, ?string $route_name = null) {
    $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $fn, $route_name);
  }

  public function get($pattern, $fn, ?string $route_name = null) {
    $this->match('GET', $pattern, $fn, $route_name);
  }

  public function post($pattern, $fn, ?string $route_name = null) {
    $this->match('POST', $pattern, $fn, $route_name);
  }

  public function patch($pattern, $fn, ?string $route_name = null) {
    $this->match('PATCH', $pattern, $fn, $route_name);
  }

  public function delete($pattern, $fn, ?string $route_name = null) {
    $this->match('DELETE', $pattern, $fn, $route_name);
  }

  public function put($pattern, $fn, ?string $route_name = null) {
    $this->match('PUT', $pattern, $fn, $route_name);
  }

  public function options($pattern, $fn, ?string $route_name = null) {
    $this->match('OPTIONS', $pattern, $fn, $route_name);
  }
}
