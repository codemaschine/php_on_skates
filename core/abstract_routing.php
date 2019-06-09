<?php
/**
 * Abstract Routing Model class for pretty URLs
 *
 * @package skates
 * @author Wolfgang Köhler <koehler@bibeltv.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractRouting {
  /**
  * instance
  *
  * static variable containing the only (!) instance of the class
  *
  * @var AbstractRouting
  */
  protected static $_instance = null;

  /**
  * get instance
  *
  * If the only instance does not exist, create it now
  * Returns the only instance
  *
  * @return   AbstractRouting
  */
  public static function getInstance()
  {
      // This needs to be implemented in a non-abstract class:
      /*if (null === self::$_instance)
      {
          self::$_instance = new self;
      }*/
      return self::$_instance;
  }

  /**
  * check, if an instance of the class exists.
  *
  *
  * @return boolean  true if instance exists. false, otherwise.
  */
  public static function hasInstance()
  {
      return (null !== self::$_instance);
  }

  /**
  * clone
  *
  * Prohibit copying the instance from outside
  */
  protected function __clone() {}

  /**
  * constructor
  *
  * prohibit external instantiation
  */
  protected function __construct() {}

  /**
  * returns a pretty URL for a given controller and parameters
  * The viewhelper url_for also passes the parameter 'pathname' in $params which
  * provides the path to the main directory. This parameter may need to be unset.
  *
  *
  * @param string $controller name of the controller
  * @param array $params array of parameters
  * @return string pretty URL (to be appended to the base path)
  */
  abstract public function getPrettyURL($controller, $params);

}
?>

