<?php

use SKATES\DateTime;

function url_for($o, $params = []) {
  if (is_array($o) && !empty($o[0]) && !$params) {
    $params = $o;
    $o = $o[0];
    unset($params[0]);
  }
  global $_FRAMEWORK, $router;
  if (is_string($o)) { // if string, this might be a url or a short notation for action and controller
    if (preg_match('#^https?://#', $o)) { // $o has full url: https://google.com/
      return $o;
    } elseif ($router instanceof SkatesRouter && $router->has_route($o)) { // route with name $o exists
      $params['route_name'] = $o;
    } elseif (preg_match('/^\w+$/', $o)) { // $o contains only letters
      $params['action'] = $o;
    } elseif (preg_match('/^\w+(\/|#)\w+$/', $o)) { // $o contains controller and action separated with / or #: user/index or user#edit
      $parts = mb_strpos($o, '/') !== false ? explode('/', $o) : explode('#', $o);
      $params['controller'] = $parts[0].'.php';
      $params['action'] = $parts[1];
    } elseif ($_FRAMEWORK['allow_plain_routing'] ?? false) { // urls for plain routing: user.php
      return $o;
    }
  } elseif ($o instanceof AbstractModel) {
    $params = array_merge(['controller' => $o->get_class_label(), 'action' => 'show', 'id' => $o->get_id()], $params);
  } else {
    $params = $o;
  }

  if (!is_array($params)) {
    throw new Exception('Wrong arguments. Must be a string containing the url, an object of type AbstactModel or an array with parameters');
  }

  foreach ($params as &$p) {
    if ($p instanceof AbstractModel) {
      $p = $p->get_id();
    }
  }

  if (!isset($params['controller'])) {
    $params['controller'] = $_FRAMEWORK['controller'];
  }
  if (!isset($params['action'])) {
    $params['action'] = $_GET['action'];
  }
  if (mb_strtolower(mb_substr($params['controller'], -4)) != '.php') {
    $params['controller'] = strtolowerunderscore($params['controller']).'.php';
  }
  if (!isset($params['route_name'])) {
    $params['route_name'] = str_replace('.php', '', $params['controller']).'#'.$params['action'];
  }

  $controller = $params['controller'];
  unset($params['controller']);
  $route_name = $params['route_name'];
  unset($params['route_name']);

  //--- add site  ---
  if (!empty($_GET['site'])) {
    $params['site'] = $_GET['site'];
  }
  //---

  if ($router instanceof SkatesRouter) {
    $router_path = $router->get_url($route_name, $params);
    if ($router_path !== false) {
      unset($params['action']);
      $controller = $router_path;
    }
  }

  return $controller.($params ? '?'.http_build_query($params) : '');
}

function full_url_for($o, $params = []) {
  global $c_base_url;
  return $c_base_url.url_for($o, $params);
}

/**
 * @param string $platform
 *                         Possible values:
 *                         - email (default)
 *                         - facebook
 *                         - twitter
 */
function generate_share_link(string $platform, $o, $params = []) {
  $url = urlencode(full_url_for($o, $params));
  switch ($platform) {
    case 'facebook':
      $url = 'https://www.facebook.com/sharer/sharer.php?u='.$url;
      break;
    case 'twitter':
      $url = 'https://twitter.com/intent/tweet?text='.$url;
      break;
    case 'email':
    default:
      $url = 'mailto:?body='.$url;
      break;
  }
  return $url;
}

function text_field_tag($name, $value, $html_options = []) {
  return _input_tag('text', $name, $value, $html_options);
}

function email_field_tag($name, $value, $html_options = []) {
  return _input_tag('email', $name, $value, $html_options);
}

function file_field_tag($name, $value, $html_options = []) {
  return _input_tag('file', $name, $value, $html_options);
}

function text_area_tag($name, $value, $html_options = []) {
  if (empty($html_options['cols'])) {
    $html_options['cols'] = 40;
  }
  if (empty($html_options['rows'])) {
    $html_options['rows'] = 5;
  }
  if (empty($html_options['id'])) {
    $html_options['id'] = _name_to_id($name);
  }
  return '<textarea name="'.$name.'"'._to_html_attributes($html_options).'>'.h($value).'</textarea>';
}

function password_field_tag($name, $value, $html_options = []) {
  return _input_tag('password', $name, $value, $html_options);
}

function check_box_tag($name, $value = 1, $checked = false, $html_options = []) {
  if ($checked) {
    $html_options['checked'] = 'checked';
  }
  return _input_tag('hidden', $name, 0, ['id' => _name_to_id($name).'_hidden'])._input_tag('checkbox', $name, $value, $html_options);
}

function radio_button_tag($name, $value, $checked = false, $html_options = []) {
  if ($checked) {
    $html_options['checked'] = 'checked';
  }
  if (!$html_options['id']) {
    $html_options['id'] = _name_to_id($name).'_'.mb_strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $value));
  }
  return _input_tag('radio', $name, $value, $html_options);
}

function hidden_field_tag($name, $value) {
  return _input_tag('hidden', $name, $value);
}

function select_tag($name, $options_tags_str, $html_options = []) {
  return '<select name="'.$name.'"'._to_html_attributes($html_options).'>'.$options_tags_str.'</select>';
}

function options_for_select($ary, $selected_val = NULL) {
  $options = [];
  if (is_array($selected_val) && (!empty($selected_val['selected']) || !empty($selected_val['disabled']))) {
    $options = $selected_val;
    $selected_val = $options['selected'] ?? null;

    if (!empty($options['disabled']) && !is_array($options['disabled'])) {
      $options['disabled'] = [$options['disabled']];
    }

    if (!empty($options['disabled']) && reset($options['disabled']) instanceof AbstractModel) {
      $options['disabled'] = array_map(function($e) { return $e instanceof AbstractModel ? $e->get_id() : $e; }, $options['disabled']);
    }
  }

  if ($selected_val instanceof AbstractModel) {
    $selected_val = $selected_val->get_id();
  }

  $result = '';
  $is_assoc = is_assoc($ary);
  if (!$is_assoc && is_array($ary) && reset($ary) instanceof AbstractModel) { // reset() returns first value
    foreach ($ary as $o) {
      if (method_exists($o, 'option_name')) {
        $label = $o->option_name();
      } elseif (isset($o->attr['name'])) {
        $label = $o->attr['name'];
      } else {
        $label = $o->get_id();
      }

      $result .= option_tag($label, $o->get_id(), $selected_val, !empty($options['disabled']) && in_array($o->get_id(), $options['disabled']) ? ['disabled' => 'disabled'] : []);
    }
    return $result;
  } else {
    foreach ($ary as $label => $value) {
      $result .= option_tag($is_assoc ? $label : $value, $value, $selected_val, !empty($options['disabled']) && in_array($value, $options['disabled']) ? ['disabled' => 'disabled'] : []);
    }
    return $result;
  }
}

function options_from_collection_for_select($collection, $value_method, $text_method, $selected = []) {
  if (!is_array($selected)) {
    $selected = [$selected];
  }
  $selected = array_map(function($e) use ($value_method) { return $e instanceof AbstractModel ? $e->get($value_method) : $e; }, $selected);
  $o = '';
  foreach ($collection as $c) {
    $o .= option_tag($c->get($text_method), $c->get($value_method), in_array($c->get($value_method), $selected));
  }
  return $o;
}

function option_tag($label, $value, $selected_val, $html_options = []) {
  $selected = false;
  if (is_array($selected_val)) {
    $selected = in_array($value, $selected_val);
  } elseif ($selected_val !== null) {
    $selected = $value == $selected_val;
  }
  return '<option value="'.h($value).'"'.($selected ? ' selected="selected"' : '')._to_html_attributes($html_options).'>'.$label.'</option>';
}

function submit_tag($value, $html_options = []) {
  return '<input type="submit" value="'.h($value).'"'._to_html_attributes($html_options).' />';
}

function _input_tag($type, $name, $value, $attrs = []) {
  if ($type == 'hidden' && empty($attrs['id'])) {
    $attrs['id'] = 'hidden_'._name_to_id($name);
  } elseif (empty($attrs['id'])) {
    $attrs['id'] = _name_to_id($name);
  }

  if ($value instanceof AbstractModel) {
    $value = $value->get_id();
  } elseif ($value instanceof DateTime) {
    $value = $value->__toString();
  }

  return '<input type="'.$type.'" name="'.$name.'" value="'.h($value).'"'._to_html_attributes($attrs).' />';
}

class FieldsBuilder {
  protected $name;
  protected $model;
  protected $closed = false;
  protected $parent_builder;
  static protected $object_index = 0;

  public function __construct($name, AbstractModel &$model, $parent_builder = null) {
    if (!empty($parent_builder)) { // && ) { // is relation on parent object?
      $relations = $parent_builder->get_model()->get_relations();
      if (in_array($name, array_keys($relations)) && !$relations[$name] instanceof RelationHasMany) {
        $this->name = "[$name]";
      } else if ($model->get_id()) {
        $this->name = "[$name][{$model->get_id()}]";
      } else {
        $this->name = "[$name][_new_".static::$object_index.']';
        static::$object_index++;
      }
    } else {
      $this->name = $name;
    }

    $this->model = &$model;
    $this->parent_builder = $parent_builder;
  }

  public function fields_for($name, &$model = null) {
    return new FieldsBuilder($name, $model, $this);
  }

  public function text_field($name, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return text_field_tag($this->w($name), $this->model->get($name), $html_options);
  }
  public function email_field($name, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return email_field_tag($this->w($name), $this->model->get($name), $html_options);
  }
  public function file_field($name, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return file_field_tag($this->w($name), $this->model->get($name), $html_options);
  }
  public function text_area($name, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return text_area_tag($this->w($name), $this->model->get($name), $html_options);
  }
  public function password_field($name, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return password_field_tag($this->w($name), $this->model->get($name), $html_options);
  }
  public function hidden_field($name, $overide_value = NULL) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return hidden_field_tag($this->w($name), $overide_value !== NULL ? $overide_value : $this->model->get($name));
  }
  public function radio_button($name, $value, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return radio_button_tag($this->w($name), $value, $this->model->get($name) == $value, $html_options);
  }
  public function check_box($name, $value = 1, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return check_box_tag($this->w($name), $value, $this->model->get($name) == $value, $html_options);
  }
  public function select($name, $options_for_select, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    if (is_array($options_for_select)) {
      $options_for_select = options_for_select($options_for_select, $this->model->get($name));
    }
    if (!empty($html_options['include_blank'])) {
      $options_for_select = '<option value="">'.(is_string($html_options['include_blank']) ? $html_options['include_blank'] : '').'</option>'.$options_for_select;
      unset($html_options['include_blank']);
    }
    return select_tag($this->w($name), $options_for_select, $html_options);
  }

  public function label($for, $label = NULL, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    if (is_array($label)) {
      $html_options = $label;
      $label = NULL;
    }
    if ($label === NULL) {
      $label = __('models.attributes.'.$this->model->get_class_label().'.'.$for, null, true);
    }
    return '<label for="'._name_to_id($this->w($for)).'"'._to_html_attributes($html_options).'>'.$label.'</label>';
  }

  public function basename() {
    if ($this->parent_builder()) {
      return $this->parent_builder()->basename().$this->name;
    } else {
      return $this->name;
    }
  }

  public function parent_builder() {
    return $this->parent_builder;
  }

  public function get_model() {
    return $this->model;
  }

  private function w($name) {
    return mb_strpos($name, '[') === false ? $this->basename()."[$name]" : $this->basename().'['.mb_substr($name, 0, mb_strpos($name, '[')).']'.mb_substr($name, mb_strpos($name, '['));  // e.g. if $this->name is 'person' and $name is 'age', it becomes 'person[age]'. If $name is 'phone[private]', it becomes 'person[phone][private]
  }
}

class Form extends FieldsBuilder {
  public function __construct($name, AbstractModel &$model, $uri, $options = [], $html_options = []) {
    if (!is_array($options)) {
      $options = [];
    }

    $this->name = $name;
    $this->model = &$model;

    if (empty($options['method'])) {
      $options['method'] = !empty($html_options['method']) ? $html_options['method'] : 'post';
      if (!empty($html_options['method'])) {
        unset($html_options['method']);
      }
    }
    if (empty($html_options['id'])) {
      $html_options['id'] = _name_to_id($name);
    }
    if (empty($html_options['name'])) {
      $html_options['name'] = _name_to_id($name);
    }

    if (empty($options['method'])) {
      $options['method'] = $html_options['method'];
    }

    // output form-tag
    echo form_tag($uri, $options, $html_options);
  }

  public static function open($name, AbstractModel $model, $uri, $options = [], $html_options = []) {
    return new static($name, $model, $uri, $options, $html_options);
  }

  public function close() {
    echo '</form>';
    $this->closed = true;
  }

  public function submit($value, $html_options = []) {
    if (($this->closed)) {
      throw new Exception('Form is already closed!');
    }
    return submit_tag($value, $html_options);
  }
}

class RemoteForm extends Form {
  public function __construct($name, $model, $uri, $options = [], $html_options = []) {
    $options['remote'] = true;
    parent::__construct($name, $model, $uri, $options, $html_options);
  }
}

function remote_link_tag($name, $href, $options = [], $html_options = []) {
  if (!is_array($options)) {
    $options = [];
  }
  $options['remote'] = true;

  return link_tag($name, $href, $options, $html_options);
}

function link_tag($name, $href, $options = [], $html_options = NULL) {
  if ($html_options === NULL) {
    $html_options = $options;
  }

  $href = url_for($href);

  if (!is_array($options)) {
    $options = [];
  }

  $result =  '<a href="'.$href.'"';

  if (!empty($options['remote']) && $options['remote'] == true) {
    $result .= ' onclick="';
    if (!empty($html_options['onclick'])) {
      $result .= 'if ((function(){'.$html_options['onclick'].'}).call(this) === false) return false;';
      $html_options['onclick'] = NULL;
    }
    $result.= _jquery_ajax($href, $options).'"';
  }

  $result .= _to_html_attributes($html_options).'>'.$name.'</a>';

  return $result;
}

/**
 * alias for link_tag
 */
function link_to($name, $href, $options = [], $html_options = NULL) {
  if (is_callable($html_options)) { // is alterntive syntax with callalble: link_to($href, $options, $html_options, $name = function(){})
    $name_result = $html_options();
    $html_options = $options;
    $options = $href;
    $href = $name;
    $name = $name_result;
  }
  return link_tag($name, $href, $options, $html_options);
}

/**
 * alias for remote_link_tag
 */
function remote_link_to($name, $href, $options = [], $html_options = []) {
  if (is_callable($html_options)) { // is alterntive syntax with callalble: link_to($href, $options, $html_options, $name = function(){})
    $name_result = $html_options();
    $html_options = $options;
    $options = $href;
    $href = $name;
    $name = $name_result;
  }
  return remote_link_tag($name, $href, $options, $html_options);
}

function remote_form_tag($uri, $options = [], $html_options = []) {
  $options['remote'] = true;
  return form_tag($uri, $options, $html_options);
}

function form_tag($uri, $options = [], $html_options = []) {
  $uri = url_for($uri);

  if (!is_array($options)) {
    $options = [];
  }

  $result =  '<form method="post" action="'.$uri.'"';

  if (!empty($options['remote'])) {
    $result .= ' onsubmit="';
    if (!empty($html_options['onsubmit'])) {
      $result .= 'if ((function(){'.$html_options['onsubmit'].'})() === false) return false;';
      $html_options['onsubmit'] = NULL;
    }
    $result .= _jquery_ajax($uri, $options, true).'"';
  }

  if (!empty($options['multipart'])) {
    $html_options['enctype'] = 'multipart/form-data';
  }

  $result .= _to_html_attributes($html_options).'>';
  return $result;
}

function _jquery_ajax($uri, $options, $sendAsFormPost = false) {
  $result = '$.ajax({ url: \''.$uri.'\'';

  if (empty($options['dataType'])) {
    $options['dataType'] = 'html';
  }

  if (empty($options['method'])) {
    $options['method'] = $sendAsFormPost ? 'POST' : 'GET';
  }

  if ($options['loadingText'] ?? $options['loading'] ?? $options['loadingJS'] ?? null) {
    $result.= ', beforeSend: function(xhr) { ';
    if (!empty($options['loadingText'])) {
      $result .= "$('".($options['loading'] ? $options['loading'] : $options['update'])."').html('".str_replace("'", "\\'", $options['loadingText'])."'); ";
    }
    if ($options['loadingText'] ?? $options['loading'] ?? null) {
      $result .= "$('".($options['loading'] ? $options['loading'] : $options['update'])."').show(); ";
    }
    if (!empty($options['loadingJS'])) {
      $result .= $options['loadingJS'];
    }
    $result .= ' }';

    if (isset($options['loading']) && $options['loading'] != ($options['update'] ?? null)) {
      $result.= ", complete: function(xhr) { $('".($options['loading'] ? $options['loading'] : $options['update'])."').hide(); }";
    }
  }
  if (!empty($options['update']) || !empty($options['updateJS'])) {
    $result.= ', success: function(data, textStatus, xhr) { ';
    if ($options['update']) {
      $result.= "$('".$options['update']."').";
      switch ($options['updateType'] ?? null) {
        case 'append': $result.= 'append(data);';
          break;
        case 'prepend': $result.= 'prepend(data);';
          break;
        default: $result.= 'html(data);';
      }
    }
    if (!empty($options['updateJS'])) {
      $result.= $options['updateJS'];
    }
    $result.= ' }';
  }
  if ($options['update'] ?? $options['error'] ?? $options['errorJS'] ?? null) {
    $result.= ', error: function(xhr, textStatus, errorThrown) { ';
    if (isset($options['update']) && empty($options['errorJS']) || $options['error']) {
      $elemId = $options['error'] ?? $options['update'];
      $result.= "$('$elemId').html(xhr.responseText); ";  /*
      anscheinend brauchen wir das gar nicht, da der Skriptblock automatisch augeführt wird.
      $('#$elemId').find('script').each(function(i) {
                    try { eval($(this).text()); }
                    catch(e){}
    });*/
    }
    if (!empty($options['errorJS'])) {
      $result.= $options['errorJS'];
    }
    $result.= ' }';
  }
  $result.= ", type: '".$options['method']."'";

  if ($sendAsFormPost) {
    $result.= ', data: $('.(is_string($sendAsFormPost) ? "'$sendAsFormPost'" : 'this').').serializeArray()';
  } elseif (!empty($options['data'])) {
    $result.= ', data: '.$options['data'];
  }

  $result .= ", dataType: '".$options['dataType']."' }); return false;";
  return $result;
}

function pagination($default_per_page, $total, $options = []) { // options: controller,arguments,firstlabel,prevlabel,nextlabel,lastlabel
  global $_FRAMEWORK;
  $controller = $options['controller'] ? $options['controller'] : $_FRAMEWORK['controller'];
  $arguments = $_GET ? $_GET : [];
  if ($options['arguments']) {
    $arguments = array_merge($arguments, $options['arguments']);
  }

  $per_page = $arguments['per_page'] ? $arguments['per_page'] : $default_per_page;
  if ($arguments['page']) {
    $page = $arguments['page'];
  } else {
    $page = $_GET['page'] ? $_GET['page'] : 1;
  }

  $total_pages = ceil($total / $per_page);

  if ($total_pages == 1) {
    return;
  }

  if ($arguments['per_page'] == $default_per_page) {
    unset($arguments['per_page']);
  }
  unset($arguments['page']);

  $res = '';

  // previous
  if ($page > 1) {
    if ($page > 2) {
      $arguments['page'] = $page - 1;
      $res .= '<a href="'.$controller.($arguments ? '?'.http_build_query($arguments) : '').'" class="pagelink">&lt;&lt;</a> ';
    }

    if ($arguments['page']) {
      unset($arguments['page']);
    }
    $res .= '<a href="'.$controller.($arguments ? '?'.http_build_query($arguments) : '').'" class="pagelink">1</a> ';

    if ($page > 2) {
      $res .= ' &hellip; ';
    }
  }

  if (!$options['hide_current']) {
    $res .= ' <span class="pagelink currentpage">'.$page.'</span> ';
  }

  // next
  if ($page < $total_pages) {
    if ($total_pages - $page >= 2) {
      $res .= ' &hellip; ';
    }

    $arguments['page'] = $total_pages;
    $res .= '<a href="'.$controller.($arguments ? '?'.http_build_query($arguments) : '').'" class="pagelink">'.$total_pages.'</a> ';

    $arguments['page'] = $page + 1;
    $res .= '<a href="'.$controller.($arguments ? '?'.http_build_query($arguments) : '').'" class="pagelink">&gt;&gt;</a> ';
  }

  return $res;
}

function _to_html_attributes(&$ary) {
  $result = '';
  foreach ($ary as $key => &$value) {
    if ($value !== NULL) {
      $result .= ' '.$key.'="'.h($value).'"';
    }
  }
  return $result;
}

function _name_to_id($name) {
  return str_replace('[', '_', str_replace(']', '', $name));
}

function cycle(array $values, $id = 'default') {
  static $cycles = [];

  if (empty($values)) {
    throw new ErrorException('No values to cylce!!!');
  }
  if (empty($id)) {
    $id = '__default';
  }

  if ($cycles[$id] === NULL || $cycles[$id] >= sizeof($values)-1) { // not initialized or last value -> take first
    $cycles[$id] = 0;
    return $values[0];
  } else {
    $cycles[$id]++;
    return $values[$cycles[$id]];
  }
}

function truncate($str, $max = 80) {
  return mb_strlen($str) > $max ? mb_substr($str, 0, $max-3 ).'...' : $str;
}

function options_range($low, $high, $step = 1) {
  $r = [];
  foreach (range($low, $high, $step) as $val) {
    $r[strval($val)] = $val;
  }
  return $r;
}

function kw_year_to_time($v, $year = NULL) {
  if ($year === NULL) {
    $year = intval($v / 100);
    $kw = $v % 100;
  } else {
    $kw = $v;
  }

  $firstday_of_year = strtotime('first thursday of January '.$year); // The first Thursday of the year always falls on the first calendar week.

  return $kw <= 1 ? $firstday_of_year : strtotime('+'.($kw - 1).' weeks', $firstday_of_year);
}
