<?php

/*
 * Functions that should be part of PHP core, but the aren't.
 * 
 */

// http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-numeric
function is_assoc($array) {
  return (bool)count(array_filter(array_keys($array), 'is_string'));
}

function strtolowerunderscore($str) {
  return mb_strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
}

function strtouppercamelcase($str) {
  return preg_replace_callback('/(?:^|_)(.?)/',function($matches) {return mb_strtoupper($matches[1]); },$str);
}

function export_backtrace() {
  $bt = debug_backtrace();
  $str = '';
  $i = count($bt);
  $first = true;
  foreach ($bt as $caller) {
    if (!$first) {
      $str .= sprintf("   #%2d, line %3s, %s,    %s()\r\n", $i, $caller['line'] ?? null, $caller['file'] ?? null, ($caller['class'] ?? '').($caller['type'] ?? '').$caller['function']);
    } else {
      $first = false;
    }
    $i--;
  }
  return $str;
}

function get_max_upload_size() { // in Bytes
  return min(let_to_num(ini_get('post_max_size')), let_to_num(ini_get('upload_max_filesize')));
}

function array_remove_keys(array &$array, array $keys) {
  $array = array_diff_key($array, array_flip($keys));
}

// http://stackoverflow.com/questions/6795621/how-to-run-array-filter-recursively-in-a-php-array
function array_filter_recursive($input, $callback = null) {
  foreach ($input as &$value) {
    if (is_array($value)) {
      $value = array_filter_recursive($value, $callback);
    }
  }

  return array_filter($input, $callback);
}

// http://www.sitepoint.com/forums/showthread.php?622616-General-function-to-recursively-array_map%28%29-for-any-callback-function
function array_map_recursive($array, $callback) {
  $new = [];
  if ( is_array($array) ) {
    foreach ($array as $key => $val) {
      if (is_array($val)) {
        $new[$key] = array_map_recursive($val, $callback);
      } else {
        $new[$key] = call_user_func($callback, $val);
      }
    }
  } else {
    $new = call_user_func($callback, $array);
  }
  return $new;
}

function stripslashes_recursive($var) {
  return is_array($var) ? array_map('stripslashes_recursive', $var) : stripslashes($var);
}

function array_record_ids(array &$records) {
  $keys = [];
  foreach ($records as &$rec) {
    if ($rec instanceof AbstractModel) {
      array_push($keys, $rec->get_id());
    }
  }
  return $keys;
}

/**
 * Check if the variable $var is set and is not empty
 * @var mixed $var the variable to check
 * @deprecated Use !empty direct - Causes problems with undefined array keys, it initializes the array key with null
 * @return bool
 */
function present(&$var) {
  return !empty($var);
}
