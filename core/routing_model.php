<?php
/**
 * Abstract Routing Model class for pretty URLs
 *
 * @package skates
 * @author Wolfgang Köhler <koehler@bibeltv.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class RoutingModel {
  /**
  * instance
  *
  * static variable containing the only (!) instance of the class
  *
  * @var RoutingModel
  */
  protected static $_instance = null;

  /**
  * get instance
  *
  * If the only instance does not exist, create it now
  * Returns the only instance
  *
  * @return   RoutingModel
  */
  abstract public static function getInstance();
  /*{
      if (null === self::$_instance)
      {
          self::$_instance = new self;
      }
      return self::$_instance;
  }*/

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
  *
  *
  * @param string $controller name of the controller
  * @param array $params array of parameters
  * @return string pretty URL (to be appended to the base path)
  */
  abstract public static function getPrettyURL($controller, $params);

}
?>

