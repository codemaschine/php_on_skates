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
class MpmMigrationBase {
  protected $dbObj;
  protected $default_types;

  public function setDbObj(&$dbObj) {
    $this->dbObj = &$dbObj;
  }

  public function __construct() {
    $this->default_types = [
      'primary_key' => 'int(11) NOT NULL auto_increment PRIMARY KEY',
      'string' => ['name' => 'varchar', 'limit' => 255, 'null' => true],
      'tinytext' => ['name' => 'tinytext', 'null' => true],
      'text' => ['name' => 'text', 'null' => true],
      'mediumtext' => ['name' => 'mediumtext', 'null' => true],
      'longtext' => ['name' => 'longtext', 'null' => true],
      'integer' => ['name' => 'int', 'limit' => 4, 'null' => false, 'default' => 0], // 'null' => false is important, because otherwise the select-statement has to check if value = 0 OR value IS NULL
      'int' => ['name' => 'int', 'limit' => 4, 'null' => false, 'default' => 0],
      'double' => ['name' => 'double', 'null' => true],
      'float' => ['name' => 'float', 'null' => true],
      'decimal' => ['name' => 'decimal', 'null' => true],
      'datetime' => ['name' => 'datetime', 'null' => true],
      'timestamp' => ['name' => 'int', 'limit' => 4, 'unsigned' => true, 'default' => 0],
      'time' => ['name' => 'time', 'null' => true],
      'date' => ['name' => 'date', 'null' => true],
      'binary' => ['name' => 'blob', 'null' => true],
      'boolean' => ['name' => 'tinyint', 'limit' => 1, 'null' => false, 'default' => 0], // 'null' => false is important, because otherwise the select-statement has to check if value = 0 OR value IS NULL
      'bool' => ['name' => 'tinyint', 'limit' => 1, 'null' => false, 'default' => 0],
      'set' => ['name' => 'set', 'null' => true],
      'attachment' => 'attachment' // will be substituted
    ];
  }

  public function create_table($table, $column_defs) {
    global $db_config;

    if (!in_array('primary_key', $column_defs)) {
      $column_defs = array_merge(['id' => 'primary_key'], $column_defs);
    }

    $columns_sql = [];
    foreach ($column_defs as $column => $options) {
      if ($options === 'attachment') {
        $part = '';
        $part .= "`{$column}_file_name` ".$this->getTypeSql('string', $this->default_types['string']).', ';
        $part .= "`{$column}_content_type` ".$this->getTypeSql('string', $this->default_types['string']).', ';
        $part .= "`{$column}_file_size` ".$this->getTypeSql('integer', $this->default_types['integer']).', ';
        $part .= "`{$column}_updated_at` ".$this->getTypeSql('datetime', $this->default_types['datetime']);
        $columns_sql[]= $part;
        continue;
      }

      $part = "`$column` ";
      if (gettype($options) == 'string') {
        if (!isset($this->default_types[$options])) {
          throw new Exception("'$options' is not a valid datatype!");
        }
        $part .= $this->getTypeSql($options, $this->default_types[$options]);
      } else {
        //var_dump($options);
        $type = $options[0] ? $options[0] : $options['type'];

        if (!isset($this->default_types[$type])) {
          throw new Exception("'$type' is not a valid datatype!");
        }
        $part .= $this->getTypeSql($type, array_merge($this->default_types[$type], $options));
      }
      $columns_sql[]= $part;
    }

    $engine = !empty($db_config->engine) ? $db_config->engine : 'MyISAM';

    $sql = "CREATE TABLE `$table` (".implode(', ', $columns_sql).") ENGINE=$engine DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $this->exec($sql);
  }

  public function drop_table($table) {
    $this->exec("DROP TABLE `$table`");
  }

  public function add_column($table, $column, $type, $options = []) {
    if ($type === 'attachment') {
      $this->add_column($table, $column.'_file_name', 'string');
      $this->add_column($table, $column.'_content_type', 'string');
      $this->add_column($table, $column.'_file_size', 'integer');
      $this->add_column($table, $column.'_updated_at', 'datetime');
      return;
    }
    $options = array_merge($this->default_types[$type], $options);
    $sql = "ALTER TABLE `$table` ADD `$column` ".$this->getTypeSql($type, $options);
    if (!empty($options['after'])) {
      $sql .= " AFTER `{$options['after']}`";
    }
    $this->exec($sql);
  }

  public function change_column($table, $column, $type, $options = []) {
    if ($type === 'attachment') {
      throw new Exception('Cannot change type "attachment"!');
    }
    $options = array_merge($this->default_types[$type], $options);
    $this->exec("ALTER TABLE `$table` CHANGE `$column` `$column` ".$this->getTypeSql($type, $options));
  }

  public function remove_column($table, $column) {
    $row = $this->query_with_first_row("show columns from `$table` like '$column'");
    if (!$row) {
      $rows = $this->exec("show columns from `$table` LIKE '$column%'");
      $fields = [];
      foreach ($rows as $r) {
        $fields[] = $r['Field'];
      }
    }

    if (!$row && in_array($column.'_file_name', $fields) && in_array($column.'_file_size', $fields)) { // ... indicates that it is an attachment
      $this->remove_column($table, $column.'_file_name');
      $this->remove_column($table, $column.'_content_type');
      $this->remove_column($table, $column.'_file_size');
      $this->remove_column($table, $column.'_updated_at');
      return;
    }
    $this->exec("ALTER TABLE `$table` DROP `$column`");
  }

  public function rename_column($table, $column, $new_column) {
    $row = $this->query_with_first_row("show columns from `$table` like '$column'");
    if (!$row) {
      $rows = $this->exec("show columns from `$table` LIKE '$column%'");
      $fields = [];
      foreach ($rows as $r) {
        $fields[] = $r['Field'];
      }
    }

    if (!$row && in_array($column.'_file_name', $fields) && in_array($column.'_file_size', $fields)) { // ... indicates that it is an attachment
      $this->rename_column($table, $column.'_file_name', $new_column.'_file_name');
      $this->rename_column($table, $column.'_content_type', $new_column.'_content_type');
      $this->rename_column($table, $column.'_file_size', $new_column.'_file_size');
      $this->rename_column($table, $column.'_updated_at', $new_column.'_updated_at');
      return;
    }
    $this->exec("ALTER TABLE `$table` CHANGE `$column` `$new_column` ".$row['Type']);
  }

  public function add_index($table, $columns, $options = []) {
    if (is_array($columns)) {
      $index_name = !empty($options['name']) ? $options['name'] : mb_substr(join('_', $columns), 0, 50);
    } else {
      $index_name = !empty($options['name']) ? $options['name'] : $columns;
    }

    $sql = "ALTER TABLE `$table` ADD ".(!empty($options['unique']) ? 'UNIQUE ' : '')."INDEX `$index_name` (".(is_array($columns) ? join(',', $this->bquotes($columns)) : $columns).')';
    $this->exec($sql);
  }

  public function remove_index($table, $columns, $options = []) {
    if (!empty($options['name'])) {
      $index_name = $options['name'];
    } elseif (is_array($columns)) {
      $index_name = mb_substr(join('_', $columns), 0, 50);
    } else {
      $index_name = $columns;
    }

    $sql = "ALTER TABLE `$table` DROP INDEX `$index_name`";
    $this->exec($sql);
  }

  // ----------------------

  private function query_with_first_row($sql) {
    if ($this->dbObj  instanceof PDO) {
      $res = $this->dbObj->query($sql);
      return $res->fetch(PDO::FETCH_ASSOC);
    } else {
      $this->dbObj->query($sql);
      $res = $this->dbObj->use_result();
      return $res->fetch_assoc();
    }
  }

  private function exec($sql) {
    echo "\r\n".$sql."\r\n";
    if ($this->dbObj  instanceof PDO) {
      return $this->dbObj->exec($sql);
    } else {
      $test = new ExceptionalMysqli();
      return $test->query($sql);
    }
  }

  private function getTypeSql($type, $options) {
    if (is_array($options)) {
      $options = array_merge($this->default_types[$type], $options);
    }

    $sql = '';
    switch($type) {
      case 'primary_key':
        return $this->default_types['primary_key'];
        break;
      case 'integer':
        switch ($options['limit'] ?? 0) {
          case 1: $sql = 'TINYINT';
            break;
          case 2: $sql = 'SMALLINT';
            break;
          case 3: $sql = 'MEDIUMINT';
            break;
          case 5: case 6: case 7: case 8: $sql = 'BIGINT';
            break;
          default: $sql = $options['name'].'(11)';
        }
        if (!empty($options['unsigned'])) {
          $sql .= ' UNSIGNED';
        }
        break;
      case 'double':
      case 'float':
        $sql = $options['name'];
        if (!empty($options['precision'])) {
          $sql .= '('.$options['precision'];
          if ($options['scale']) {
            $sql .= ','.$options['scale'];
          }
          $sql .= ')';
        }
        break;
      case 'string':
        $sql = $options['name'];
        if ($options['limit']) {
          $sql .= '('.$options['limit'].')';
        }
        break;
      case 'set':
        $sql = $options['name'].'('.(is_array($options['options']) ? join(', ',$this->quotes($options['options'])) : $options['options']).')';
        break;
      default:
        $sql = $options['name'];
    }

    $sql .= !empty($options['null']) ? ' NULL' : ' NOT NULL';
    if (($options['default'] ?? null) !== null) {
      $sql .= ' DEFAULT ';
      if ($type == 'string' || $type == 'tinytext' || $type == 'text' || $type == 'mediumtext' || $type == 'longtext' || $type == 'set') {
        $sql .= "'".addslashes($options['default'])."'";
      } else {
        $sql .= $options['default'] ? $options['default'] : 0;
      }
    }
    if (!empty($options['unique'])) {
      $sql .= ' UNIQUE';
    }

    return $sql;
  }

  private function bquotes($o) {
    return $this->quotes($o, '`');
  }

  private function quotes($o, $sym = '"') {
    if (is_array($o)) {
      $n = [];

      foreach ($o as $value) {
        array_push($n, $sym.$value.$sym);
      }

      return $n;
    } else {
      return $sym.$o.$sym;
    }
  }
}
