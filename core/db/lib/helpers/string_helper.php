<?php
/**
 * This file houses the MpmStringHelper class.
 *
 * @package    mysql_php_migrations
 * @subpackage Helpers
 * @license    http://www.opensource.org/licenses/bsd-license.php  The New BSD License
 * @link       http://code.google.com/p/mysql-php-migrations/
 */

/**
 * The MpmStringHelper class holds a number of static methods which manipulate strings.
 *
 * @package    mysql_php_migrations
 * @subpackage Helpers
 */
class MpmStringHelper {
  /**
   * Returns a timestamp when given a migration filename.
   *
   * @param string $file the migration filename
   *
   * @return string
   */
  static public function getTimestampFromFilename($file) {
    // strip .php
    $time = mb_substr($file, 0, 19);
    $t = explode('_', $time);
    // Fix for problem when file doesn't exist and comes in as an empty string, then throws undefined offset errors
    if (count($t) != 6) {
      return null;
    }
    $timestamp = $t[0].'-'.$t[1].'-'.$t[2].'T'.$t[3].':'.$t[4].':'.$t[5];
    // validate the date
    if (false === checkdate($t[1], $t[2], $t[0])) {
      return null;
    }
    // validate timestamp
    if (false === strtotime($timestamp)) {
      return null;
    }
    return $timestamp;
  }

  /**
   * Returns a filename when given a migration timestamp.
   *
   * @param string $timestamp the migration timestamp
   *
   * @return string
   */
  static public function getFilenameFromTimestamp($timestamp) {
    return date('Y_m_d_H_i_s', strtotime($timestamp)).'.php';
  }

  /**
   * Coverts a string from this_notation to ThisNotation (CamelCase)
   *
   * @param string $no_camel the string in this_notation
   *
   * @return string
   */
  static public function strToCamel($no_camel) {
    // do not alter string if there are no underscores
    if (mb_stripos($no_camel, '_') == false) {
      return $no_camel;
    }
    $no_camel = mb_strtolower($no_camel);
    $no_camel = str_replace('_', ' ', $no_camel);
    $no_camel = ucwords($no_camel);
    $array = explode(' ', $no_camel);
    $camel = '';
    foreach ($array as $key => $part) {
      if ($key == 0) {
        $camel .= mb_strtolower($part);
      } else {
        $camel .= $part;
      }
    }
    return $camel;
  }

  /**
   * Converts a string from CamelCaps to this_notation.
   *
   * @param string $camel a string in CamelCaps
   *
   * @return string
   */
  static public function camelToLower($camel) {
    // split up the string into an array according to the uppercase characters
    $array = preg_split('/([A-Z][^A-Z]*)/', $camel, (-1), PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $array = array_map('mb_strtolower', $array);
    // create our string
    $lower = '';
    foreach ($array as $part) {
      $lower .= $part.'_';
    }
    $lower = mb_substr($lower, 0, mb_strlen($lower) - 1);
    return $lower;
  }

  /**
   * Adds single quotes and escapes single quotes and backslashes to the given string
   *
   * @param string $arg the string we need to quote
   *
   * @return string
   */
  static public function addSingleQuotes($arg) {
    /* single quote and escape single quotes and backslashes */ 
    return "'".addcslashes($arg, "'\\")."'";
  }
}
