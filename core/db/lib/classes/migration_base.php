<?php
/**
 * This file houses the MpmMigration class.
 *
 * @package    mysql_php_migrations
 * @subpackage Controllers
 * @license    http://www.opensource.org/licenses/bsd-license.php  The New BSD License
 * @link       http://code.google.com/p/mysql-php-migrations/
 */

/**
 * The MpmMigrationBase includes base functions for all migrations
 *
 * @package    mysql_php_migrations
 * @subpackage Controllers
 */
class MpmMigrationBase
{
	protected $dbObj;
	protected $default_types;

	public function setDbObj(&$dbObj) {
	  $this->dbObj = &$dbObj;
	}


	public function __construct() {
	  $this->default_types = array(
	    'primary_key' => "int(11) NOT NULL auto_increment PRIMARY KEY",
	    'string' => array( 'name' => "varchar", 'limit' => 255, 'null' => true ),
	    'text' => array( 'name' => "text", 'null' => true ),
	    'integer' => array( 'name' => "int", 'limit' => 4, 'null' => false, 'default' => 0), // 'null' => false is important, because otherwise the select-statement has to check if value = 0 OR value IS NULL
	    'int' => array( 'name' => "int", 'limit' => 4, 'null' => false, 'default' => 0),
	    'double' => array( 'name' => "double", 'null' => true ),
	    'float' => array( 'name' => "float", 'null' => true ),
	    'decimal' => array( 'name' => "decimal", 'null' => true ),
	    'datetime' => array( 'name' => "datetime", 'null' => true ),
	    'timestamp' => array( 'name' => "int", 'limit' => 4, 'unsigned' => true, 'default' => 0 ),
	    'time' => array( 'name' => "time", 'null' => true ),
	    'date' => array( 'name' => "date", 'null' => true ),
	    'binary' => array( 'name' => "blob", 'null' => true ),
	    'boolean' => array( 'name' => "tinyint", 'limit' => 1, 'null' => false, 'default' => 0), // 'null' => false is important, because otherwise the select-statement has to check if value = 0 OR value IS NULL
	    'bool' => array( 'name' => "tinyint", 'limit' => 1, 'null' => false, 'default' => 0),
	    'set' => array( 'name' => 'set', 'null' => true)
	  );
	}


	public function create_table($table, $column_defs) {

	  if (!in_array('primary_key', $column_defs))
	    $column_defs = array_merge(array('id' => 'primary_key'), $column_defs);

	  $columns_sql = array();
	  foreach ($column_defs as $column => $options) {
	    $part = "`$column` ";
	    if (gettype($options) == 'string') {
	      if (!isset($this->default_types[$options]))
	        throw new Exception("'$options' is not a valid datatype!");
	      $part .= $this->getTypeSql($options, $this->default_types[$options]);

	    }
	    else {
	      //var_dump($options);
	      $type = $options[0] ? $options[0] : $options['type'];

	      if (!isset($this->default_types[$type]))
	        throw new Exception("'$type' is not a valid datatype!");
	      $part .= $this->getTypeSql($type, array_merge($this->default_types[$type], $options));
	    }
	    $columns_sql[]= $part;
	  }

	  $sql = "CREATE TABLE `$table` (".implode(', ', $columns_sql).") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	  $this->exec($sql);
	}

	public function drop_table($table) {
	  $this->exec("DROP TABLE `$table`");
	}

	public function add_column($table, $column, $type, $options = array()) {
	  if ($type === 'attachment') {
		$this->add_column($table, $column.'_file_name', 'string');
		$this->add_column($table, $column.'_content_type', 'string');
		$this->add_column($table, $column.'_file_size', 'integer');
		$this->add_column($table, $column.'_updated_at', 'datetime');
		return;
	  }
	  $options = array_merge($this->default_types[$type], $options);
	  $sql = "ALTER TABLE `$table` ADD `$column` ".$this->getTypeSql($type, $options);
	  if ($options['after'])
	    $sql .= " AFTER `{$options['after']}`";
	  $this->exec($sql);
	}

	public function change_column($table, $column, $type, $options = array()) {
		if ($type === 'attachment') {
			throw new Exception('Cannot change type "attachment"!');
		}
	  $options = array_merge($this->default_types[$type], $options);
	  $this->exec("ALTER TABLE `$table` CHANGE `$column` `$column` ".$this->getTypeSql($type, $options));
	}

	public function remove_column($table, $column) {
		$col = $this->query_with_first_row("show columns from `$table` like '$column%'");
		
		if (strtolower($col['Field']) !== strtolower($column)) { // ... indicates that it is an attachment
			$this->remove_column($table, $column.'_file_name');
			$this->remove_column($table, $column.'_content_type');
			$this->remove_column($table, $column.'_file_size');
			$this->remove_column($table, $column.'_updated_at');
			return;
		}
		$this->exec("ALTER TABLE `$table` DROP `$column`");
	}


	public function rename_column($table, $column, $new_column) {
	  $row = $this->query_with_first_row("show columns from `$table` LIKE '$column'");

	  $this->exec("ALTER TABLE `$table` CHANGE `$column` `$new_column` ".$row['Type']);
	}

	public function add_index($table, $columns, $options = array()) {
	  if (is_array($columns)) {
	    $columns = join(', ', $columns);
	    $index_name = $options['name'] ? $options['name'] : substr(join('_', $columns), 0, 50);
	  }
	  else
	    $index_name = $options['name'] ? $options['name'] : $columns;

	  $sql = "ALTER TABLE `$table` ADD ".($options['unique'] ? 'UNIQUE ' : '')."INDEX `$index_name` (".$this->bquotes($columns).")";
	  $this->exec($sql);
	}

	public function remove_index($table, $columns, $options = array()) {
	  if ($options['name'])
	    $index_name = $options['name'];
	  elseif (is_array($columns)) {
	    $index_name = substr(join('_', $columns), 0, 50);
	  }
	  else
	    $index_name = $columns;

	  $sql = "ALTER TABLE `$table` DROP INDEX `$index_name`";
	  $this->exec($sql);
	}







	// ----------------------


	private function query_with_first_row($sql) {
	  if ($this->dbObj  instanceof PDO) {
	    $res = $this->dbObj->query($sql);
	    return $res->fetch(PDO::FETCH_ASSOC);
	  }
	  else {
	    $this->dbObj->query($sql);
	    $res = $this->dbObj->use_result();
	    return $res->fetch_assoc();
	  }
	}


	private function exec($sql) {
	  echo "\r\n".$sql."\r\n";
	  if ($this->dbObj  instanceof PDO) {
	    return $this->dbObj->exec($sql);
	  }
	  else {
	    $test = new ExceptionalMysqli();
	    return $test->query($sql);
	  }
	}


	private function getTypeSql($type, $options) {
	  if (is_array($options))
	    $options = array_merge($this->default_types[$type], $options);

	  //echo "__________";
	  //var_dump($options);
	  switch($type) {
	    case 'primary_key':
	      return $this->default_types['primary_key'];
	      break;
	    case 'integer':
	      switch ($options['limit']) {
	        case 1: $sql = 'TINYINT'; break;
	        case 2: $sql = 'SMALLINT'; break;
	        case 3: $sql = 'MEDIUMINT'; break;
	        case 5: case 6: case 7: case 8: $sql = 'BIGINT'; break;
	        default: $sql = $options['name'].'(11)';
	      }
  	    if ($options['unsigned'])
  	      $sql .= ' UNSIGNED';
  	    break;
	    case 'double':
	    case 'float':
	      $sql .= $options['name'];
	      if ($options['precision']) {
	        $sql .= '('.$options['precision'];
	        if ($options['scale'])
	          $sql .= ','.$options['scale'];
	        $sql .= ')';
	      }
	      break;
	    case 'string':
	      $sql .= $options['name'];
	      if ($options['limit']) {
	        $sql .= '('.$options['limit'].')';
	      }
	      break;
	    case 'set':
	      $sql .= $options['name']."(".(is_array($options['options']) ? join(', ',$this->quotes($options['options'])) : $options['options']).")";
	      break;
	    default:
	      $sql .= $options['name'];
	  }

	  $sql .= $options['null'] ? ' NULL' : ' NOT NULL';
	  if ($options['default'] !== null) {
	    $sql .= ' DEFAULT ';
	    if ($type == 'string' || $type == 'text' || $type == 'set')
	      $sql .= "'".mysql_escape_string($options['default'])."'";
	    else
	      $sql .= $options['default'] ? $options['default'] : 0;
	  }
	  if ($options['unique'])
	    $sql .= ' UNIQUE';

	  return $sql;
	}


	private function bquotes($o) {
	  return $this->quotes($o, '`');
	}

	private function quotes($o, $sym = '"') {
	  if (is_array($o)) {
	    $n = array();

	    foreach ($o as $value)
	      array_push($n, $sym.$value.$sym);

	    return $n;
	  }
	  else
	    return $sym.$o.$sym;
	}
}


?>
