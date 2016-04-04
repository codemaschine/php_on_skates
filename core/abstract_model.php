<?php

use SKATES\DateTime;
use SKATES\Date;

require_once 'db.php';
require_once 'base.php';


/**
 * Abstract Model class for SQL database table
 *
 * @package skates
 * @author Jannes Dinse <jannes.dinse@codemaschine.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractModel {

  // -- attributes

  protected static $table_name = NULL;
  protected static $default_find_options = array();

  protected static $soft_delete = false; // if true, a record will not really be deleted but marked as 'deleted'. NOTICE: Requires a column 'deleted_at'
  protected static $soft_delete_type = 'datetime'; // or 'boolean'

  protected static function default_scope() {
    return array();
  }

  public static function get_table_name() {
    return static::$table_name;
  }

  public static function has_soft_delete() {
  	return static::$soft_delete;
  }


  protected $mass_assignable = array(); // mass assignable fields. If empty, the protection is switched of
  protected $public_fields = array(); // fields that can safely returned to public on an API-call (JSON-API)
  protected $private_fields = array(); // fields that can additionally returned to the records owner on an API-call (JSON-API)
  protected $attr_defs;


  public $attr = array();
  private $attr_loaded = array(); // peristed (clean) attributes.
  public function get_clean_attr() {	return $this->attr_loaded; }
  public function get_persisted_attr() { return $this->get_clean_attr(); }  // alias for get_clean_attr()


  private $id;
  private $dirty; // set if the id is set manually! It can't be saved!!


  protected $validators = array();
  protected $errors = array();
  protected $relations = array();


  // filters;
  protected function before_save() { return true; }
  protected function after_save() { return true; }
  protected function before_create() { return true; }
  protected function after_create() { return true; }
  protected function before_update() { return true; }
  protected function after_update() { return true; }

  protected function before_validate() { return true; }




  const FIRST = 0;
  const ALL = 100;




  /**
   * This method should return an array with the attribute definitions:
   *   - the array keys are the field names
   *   - the array values defining the data type. possible values:
   *
   *   "boolean"
   *   "integer"
   *   "float"
   *   "string"
   *
   * @return array with attribute definitions
   */
  abstract public static function attribute_definitions();

  /**
   * Optional. In this method you can do other configurations, it's called by the constructor.
   */
  protected function configuration() {}


  /**
   * Write your custom validations here
   */
  protected function validate() {}




  public function __construct($attr = array(), $secure_merge = true) {
    $this->attr_defs = static::attribute_definitions();
    $this->configuration();

    if (is_array($attr)) {
    	if ($this->mass_assignable && $secure_merge)
    		$attr = array_intersect_key($attr, array_flip($this->mass_assignable));

      foreach ($attr as $key => $value) {
        $this->set($key, $value);
      }
    }
  }

  protected function set_attr_default($name, $value) {
    $this->attr[$name] = $value;
  }



  protected function has_many($name, $options = array()) {
    if (!$options['class_name'])
      $options['class_name'] = is_plural($name) ? strtouppercamelcase(singularize($name)) : strtouppercamelcase($name);

    $this->relations[$name] = new RelationHasMany($name, $options['class_name'], $this, $options);
  }

  protected function belongs_to($name, $options = array()) {
    if (!$options['class_name'])
      $options['class_name'] = strtouppercamelcase($name);

    $this->relations[$name] = new RelationBelongsTo($name, $options['class_name'], $this, $options);
  }

  protected function has_one($name, $options = array()) {
    if (!$options['class_name'])
      $options['class_name'] = strtouppercamelcase($name);

    $this->relations[$name] = new RelationHasOne($name, $options['class_name'], $this, $options);
  }

  public function get_relations() {
    return $this->relations;
  }

  public function& get_relation($name) {
    if (!$this->relations[$name])
      throw new ErrorException('There is no relation named "'.$name.'" in model '.get_class($this));

    return $this->relations[$name];
  }



  // -- instance methods
  
  public function get_class_label() {
  	return strtolowerunderscore(get_class($this));
  }

  public function get_id() {
    return $this->id;
  }
  public function id() {
    return $this->id;
  }
  public function is_new() {
    return $this->id == NULL;
  }


  public function get($name) {
    if ($name == 'id')
      return $this->get_id();
    if (array_key_exists($name, $this->relations))
      return($this->relations[$name]->get());
    else return $this->attr[$name];
  }

  public function set($name, $value) {
    if (array_key_exists($name, $this->relations))
      return ($this->relations[$name]->set($value));
    else {
      $this->attr[$name] = $value;
      // $this->cast_attribute($name);  // blöde Idee! Gibt man bei deinem Integer-Feld einen String an, wird noch vor dem validieren gecastet. Dadurch wird entweder ein Wert gespeichert, den man gar nicht eingegeben hat, oder es wird ein Fehler zurückgeliefert, aber die ursprüngliche Eingabe ist verloren.
      return true;
    }
  }


  public function save($skip_validation = false) {
    return $this->_save($skip_validation);
  }

  private function _save($skip_validation = false, $update_fields = NULL) {
    global $log;
    $is_new = $this->is_new();

    if ($this->dirty || !$skip_validation && !$this->is_valid() || $is_new && $this->before_create() === false || !$is_new && $this->before_update() === false || $this->before_save() === false)
      return false;

    //$log->debug('noch mal kurz in abstract_model gecheckt: '.$this);

    $this->cast_attributes();

    $entries = $this->attr; // make a copy of attributes

    if (empty($entries))
  	  throw new Exception("Define some fields in '$table'!", E_USER_ERROR);

  	if ($this->is_new())
  	  $entries['created_at'] = $this->attr_defs['created_at'] === 'datetime' ? gmdate(DB_DATETIME_FORMAT) : time();

    $entries['updated_at'] = $this->attr_defs['updated_at'] === 'datetime' ? gmdate(DB_DATETIME_FORMAT) : time();

    $assignments = array();
    foreach ($entries as $key => &$value) {
      if (!array_key_exists($key, $this->attr_defs)) // || $update_fields && !in_array($key, $update_fields))   // this is a very bad idea to save only the the fields defined by $update_fields because it will discard changes of the before-filters!
  	    continue;

  	  //$log->debug("Value of $key is ".var_export($value, true));
      //if ($value === NULL)   // do nothing to not overwrite the database value
      //  continue;


      if ($this->is_new() || $this->attr_loaded[$key] !== $value) { // only update if the the attribute is really dirty!!!
      	if ($value === NULL)
      		$value = 'NULL';
      	elseif (is_bool($value))
      	  $value = $value ? '1' : '0';
      	elseif (is_string($value))
      	  $value = "'".mysql_real_escape_string($value)."'";
      	elseif (is_float($value))
      	  $value = var_export($value, true); // avoid the usage of , instead of . as decimal separator caused by setlocale.
      	elseif ($value instanceof DateTime) { // works for Date-class, too!
      	  $value = "'".$value->toDbFormat()."'";
      	}
      	elseif (!is_numeric($value))
      	  $value = 'NULL'; // Default: drop other values

        $assignments[] = "`$key` = $value";
        //$log->debug('attribut zum neu: '.var_inspect($this->attr_loaded[$key]).' !== '.var_inspect($value));
      }
      //else
      	//$log->debug('attribut zum gleich '.var_inspect($this->attr_loaded[$key]).' === '.var_inspect($value));
    }
    if (!empty($assignments)) {

      //$log->debug("---- call update here: \r\n".export_backtrace());

      $query = ($this->is_new() ? 'INSERT INTO' : 'UPDATE').' `'.static::$table_name.'` SET '.implode(", ", $assignments);
      if (!$this->is_new())
        $query .= " WHERE id = $this->id";

      db_query($query);

      if ($is_new)
        $this->id = mysql_insert_id();

      // update in cache
      static::insert_into_cache($this);
    }


    // save nested models
    foreach ($this->relations as &$relation) {
      if ($relation instanceof RelationHasMany || $relation instanceof RelationHasOne) {
        $relation->save(true);
      }
    }
    $this->attr_loaded = $this->attr;  // copy pesisted attributes

    $this->after_save();

    if ($is_new)
      $this->after_create();
    else
      $this->after_update();

    return true;
  }


  public function destroy() {
    foreach ($this->relations as &$relation) {
      $relation_options = $relation->_get_options();
      switch ($relation_options['dependent']) {
        case 'delete': $relation->delete(); break;
        case 'destroy': $relation->destroy(); break;
      }
    }
    $this->delete();
  }

  public function delete() {
  	if (!$this->is_new()) {
  		if (static::$soft_delete) {
  			$deleted_column = is_string(static::$soft_delete) ? static::$soft_delete : 'deleted_at';
    		db_query('UPDATE `'.static::$table_name.'` SET '.$deleted_column.' = '.(static::$soft_delete_type == 'datetime' || static::$soft_delete_type == 'time' ? ($this->attr_defs[$deleted_column] == 'datetime' ? 'NOW()' : time()) : 1).' WHERE id = '.$this->id);
  		} else
        db_query('DELETE FROM `'.static::$table_name."` WHERE id = $this->id");
      $this->id = NULL;
    }
  }

  /**
   * Merge assign the new attibutes and save the model
   * @param array $new_attrs The new attributes
   * @param boolean $skip_validation Skip the validation?
   * @return boolean any validation errors that aborts the saving?
   */
  public function update_attributes($new_attrs, $skip_validation = false) {
    $this->secure_merge($new_attrs);
    return $this->_save($skip_validation, array_keys($new_attrs));
  }

  /**
   * Update an attribute and save the model
   * @param string $key attribute's key
   * @param mixed $value attribute's value
   * @param boolean $skip_validation Skip validation?
   * @return boolean any validation errors that aborts the saving?
   */
  public function update_attribute($key, $value, $skip_validation = false) {
    $this->attr[$key] = $value;
    return $this->_save($skip_validation, array($key));
  }


  // -- Validation functions



  public function is_valid() {
    global $log;

    $this->before_validate();

    $log->debug('validators: '.var_export($this->validators, true));

    foreach ($this->validators as $validator) {
      $attr_name = $validator['attr_name'];
      $attr = $this->attr[$attr_name];

      /*
      $log->debug('using validator: '.var_export($validator, true));
      $log->debug('validator attr_name: '.var_export($attr_name, true));
      $log->debug('validator attr: '.var_export($attr, true));
      */
      if ($validator['name'] != 'presence_of' && $validator['allow_blank'] && empty($attr))
        continue;

      if (isset($validator['if']) && !$validator['if']($this->attr))
        continue;

      switch ($validator['name']) {
        case 'presence_of':
          if (!$attr) {
            $this->errors[$attr_name] = $validator['message'];
          }
          break;
        case 'uniqueness_of':
          $condition_str = $validator['case_sensitive'] ? "binary $attr_name = ?" : "$attr_name = ?";
          if ($this->get_id())
            $condition_str .= ' AND id != '.intval($this->get_id());

          if ($validator['scope']) {
            $fields = is_array($validator['scope']) ? $validator['scope'] : array($validator['scope']);
            $field_cond = array();
            foreach ($fields as $field)
              $field_cond[$field] = $this->attr[$field];
            $condition_str .= ' AND '.self::build_conditions($field_cond, static::$table_name);
          }

          //$log->debug('hier!!!'.var_export($validator, true));
          if (static::find_first(array('conditions' => array($condition_str, $attr)))) {
            $this->errors[$attr_name] = $validator['message'];
          }
          break;
        case 'numericality_of':
          if ($attr && (!is_numeric($attr) || $validator['only_integer'] && !is_integer($attr))) {
            $this->errors[$attr_name] = $validator['message'];
          }
          break;
        case 'length_of':
          if ($validator['is'] && strlen($attr) != $validator['is'] ||
              $validator['minimum'] && strlen($attr) < $validator['minimum'] ||
              $validator['maximum'] && strlen($attr) > $validator['maximum']) {
            $this->errors[$attr_name] = $validator['message'];
          }
          break;
        case 'format_of':
          if ($validator['with'] && !preg_match($validator['with'], $attr) ||
              $validator['without'] && preg_match($validator['without'], $attr)) {
            $this->errors[$attr_name] = $validator['message'];
          }
          break;
        case 'confirmation_of':
          if ($attr != $this->attr[$validator['with']]) {
            $this->errors[$attr_name] = $validator['message'];
          }
          break;
        case 'associated':
          if (!$this->relations[$attr_name]->is_valid()) {
            $this->errors = array_merge($this->errors, $this->relations[$attr_name]->get_errors());
            if ($validator['message'])
              $this->errors[$attr_name] = $validator['message'];

          }
          break;

        default:
          throw new Exception('Unknown validator "'.$validator['name'].'"');

      }
    }
    $this->validate();

    return empty($this->errors);
  }



  public function get_errors() {
    return $this->errors;
  }

  public function get_errors_as_message() {
    $html = '<div id="error_explanation"><h2>Bitte überprüfen Sie Ihre Eingaben!</h2><ul>';
    foreach ($this->errors as $key => $value) {
      $html .= '<li class="error_message">';
      if (gettype($key) == 'integer')
        $html .= $value;
      else
        $html .= '<span class="property">'.ucfirst($key)."</span> $value</li>";
      $html .= '</ul></div>';
    }
    return $html.'</div>';
  }

  public function add_property_error($property, $message) {
    $this->errors[$property] = $message;
  }

  public function add_error($message) {
    array_push($this->errors, $message);
  }


  // private helper function
  private function add_new_validator($name, $attr_name, $default_message, $options) {
    if (!isset($options['message']) || $options['message'] === null)
      $options['message'] = $default_message;
    global $log;
    //$log->debug("füge validator $name hinzu");
    $this->validators[] = array_merge($options, array('name' => $name, 'attr_name' => $attr_name));
  }


  protected function validates_presence_of($attr_name, $options = array()) {
    $this->add_new_validator('presence_of', $attr_name, ' darf nicht leer sein.', $options);
  }

  protected function validates_uniqueness_of($attr_name, $options = array()) {
    $this->add_new_validator('uniqueness_of', $attr_name, ' ist bereits vergeben.', $options);
  }

  protected function validates_numericality_of($attr_name, $options = array()) {
    $this->add_new_validator('numericality_of', $attr_name, $options['only_integer'] ? ' ist keine Ganzzahl.' : ' ist keine Zahl.', $options);
  }

  protected function validates_length_of($attr_name, $options = array()) {
    $this->add_new_validator('length_of', $attr_name, $options['is'] ? ' muss genau '.$options['is'].' Zeichen lang sein.' : ' hat eine ungültige Länge.', $options);
  }

  protected function validates_format_of($attr_name, $options = array()) {
    $this->add_new_validator('format_of', $attr_name, ' hat ein ungültiges Format.', $options);
  }

  protected function validates_email_of($attr_name, $options = array()) {
    $options['with'] = '/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/';
    $this->add_new_validator('format_of', $attr_name, ' ist keine gültige E-Mailadresse.', $options);
  }

  protected function validates_confirmation_of($attr_name, $with_attr, $options) {
    $options['with'] = $with_attr;
    $this->add_new_validator('confirmation_of', $attr_name, ' stimmt mit der Bestätigung nicht überein.', $options);
  }

  protected function validates_associated($attr_name, $options = array('message' => false)) {
    $this->add_new_validator('associated', $attr_name, '', $options);
  }



  // -- class functions (helpers)


  public static function new_instance($attributes = array(), $secure_merge = true) {
    return new static($attributes, $secure_merge);
  }


  public static function create($attributes = array(), $secure_merge = true) {
    $obj = new static($attributes, $secure_merge);
    return $obj->save();
  }



  // is only a private template method!!!
  private static function find_by($fields = NULL, $options = array(), $exception_if_not_found = false) {

    //--- Check if we can use cache
    if (is_array($fields) && is_assoc($fields) && count(array_keys($fields)) == 1 && array_key_exists('id', $fields) &&    // looking only for 'id's
        count(array_diff(array_keys($options), array('take'))) == 0) { // only 'take' is allowed as option

      $temp_ids = is_array($fields['id']) ? $fields['id'] : array($fields['id']);
      $ids = array();
      foreach ($temp_ids as $id)
        $ids[] = strval($id);     // guarantee that every id is a string



      if ($options['take'] == self::FIRST) {
        $id = array_shift($ids);
        $rec = static::get_from_cache($id);
        if ($rec)
          return $rec;
      }
      elseif ($options['take'] == self::ALL) {
        $recs = static::get_from_cache($ids);   // parameter is an array -> returns an array or NULL
        if ($recs !== NULL)  // if all records found in cache
          return $recs;
      }
    }




    //---



    if (empty(static::$table_name))
      self::set_table_name();

    $def_options = array();
    $def_options['select'] = '`'.static::$table_name.'`.*';
    $def_options['from'] = static::$table_name;
    $def_options['take'] = self::ALL;

    $def_options = array_merge($def_options, static::$default_find_options);
    $options =     array_merge($def_options, $options);

    $query = 'SELECT '.$options['select'].' FROM `'.$options['from'].'`';
    if ($options['joins'])
      $query .= ' '.$options['joins'];

    $query .= self::build_conditions_sql($fields, $options);

    if ($options['group'])
      $query .= ' GROUP BY '.$options['group'];


    if ($options['order'])
      $query .= ' ORDER BY '.$options['order'];


    if ($options['take'] == self::ALL) {
      if ($options['per_page']) {  // paginate!!!
        $page = intval($options['page']) < 2 ? 1 : intval($options['page']);

        if ($options['limit'])
          $query .= ' LIMIT '.intval($options['per_page']);

        if ($options['offset'])
          $query .= ' OFFSET '.(($page - 1) * intval($options['per_page']));
      }
      else {
        if ($options['limit'])
          $query .= ' LIMIT '.$options['limit'];

        if ($options['offset'])
          $query .= ' OFFSET '.$options['offset'];
      }

    }
    elseif ($options['take'] == self::FIRST)
      $query .= ' LIMIT 1';


    $res = self::find_by_sql($query, $options['take'], $exception_if_not_found);    // now get the record(s)


    if ($options['include'] && $res) { // process include findings only if we have a collection.
      $records = is_array($res) ? $res : array($res);
      $rec_hash = array();
      foreach ($records as $rec)
        $rec_hash[$rec->get_id()] = $rec;
      $ids = array_keys($rec_hash);


      if (is_string($options['include'])) { // only one relation to include
        self::include_relation($options['include'], $records);
      }
      elseif (is_array($options['include'])) {
        foreach ($options['include'] as $key => $value) {
          if (is_string($key)) {
            self::include_relation($key, $records, $value);  // last one is subinclude
          }
          else {
            self::include_relation($value, $records);
          }
        }
      }

      $res = is_array($res) ? $records : array_shift($records);

    }

    return $res;
  }


  /**
   * load objects of a relation of all $records
   *
   * @param string $relation_name
   * @param array $records
   * @param mixed $subinclude
   */
  private static function include_relation($relation_name, &$records, $subinclude = NULL) {
    global $log;

    if (!$records)
      return;

    reset($records);
    $rec = current($records);
    $relations = $rec->get_relations();

    if (!$relations[$relation_name])
      throw new ErrorException("There is no relation with name '$relation_name' in class ".get_class($rec)." to include!");
    $relation = $relations[$relation_name];


    // first: get all objects of the relation for ALL $records to load
    $relation->_get_for_all_of($records, $subinclude);

  }


  public static function count_by($fields = NULL, $options = array()) {
    if (empty(static::$table_name))
      self::set_table_name();

    $def_options = array();
    $def_options['from'] = static::$table_name;

    $options = array_merge($def_options, $options);

    $query = 'SELECT '.($options['group'] ? '1' : 'count(*) as total').' FROM `'.$options['from'].'`'; // if there is 'group by' in the query then count(*) would return counts for each group. So in that case we return 1 and count these result rows later.
    if ($options['joins'])
      $query .= ' '.$options['joins'];


    $query .= self::build_conditions_sql($fields, $options);

    if ($options['group']) {
      $query = "select count(*) as total from ($query group by {$options['group']}) as sq";
    }

    $res = db_query($query);
    if ($row = mysql_fetch_row($res))
      return $row[0];
    else
      throw new Exception(mysql_error());
  }

  public static function count_all($options = array()) {
    return self::count_by(NULL, $options);
  }



  private static function build_conditions_sql($fields, $options) {
    global $log;

    $elements = array();

    if (!empty($fields))
      array_push($elements, self::build_conditions($fields, $options['from']));


    if ($options['conditions']) {
      if (is_string($options['conditions'])) {
        $elem = $options['conditions'];
      }
      elseif (!is_array($options['conditions']))
        throw new Exception("condition is neither an array nor a string");
      elseif (is_assoc($options['conditions'])) {
        $elem = self::build_conditions($options['conditions'], $options['from']);
      }
      else {


        $cond_stmt = array_shift($options['conditions']);

        //$log->debug('cond_stmt: '.var_export($cond_stmt, true));
        //$log->debug('zu ersetztende vars: '.var_export($options['conditions'], true));

        if ($options['conditions']) { // gibt es noch zu ersetzende variablen?
          if (strpos($cond_stmt, '?') === false) {
            //$log->debug("Keine Fragezeichen in conditions-string gefunden");
            $cond_stmt = vprintf($cond_stmt, array_map(create_function('$s','return mysql_real_escape_string($s);'), $options['conditions']));
          }
          else {
            $i = 0;
            foreach($options['conditions'] as $value) {

              $value =  self::sanitize_value($value);
              //$log->debug('replace ? in condition string with '.var_export($value, true));
              $cond_stmt = preg_replace('/\?/', $value, $cond_stmt, 1);
            }
          }
        }  // Variablen erstetzt!!

        $elem = $cond_stmt;
      }

      array_push($elements, '('.$elem.')');
    }  // conditions ende

    if (static::$soft_delete && !$options['unscoped']) {
      $attr_defs = static::attribute_definitions();
      $delete_col = is_string(static::$soft_delete) ? static::$soft_delete : 'deleted_at';
        if (static::$soft_delete_type == 'datetime' && $attr_defs[$delete_col] == 'datetime')
          array_push($elements, "$delete_col IS NULL");
        else
    	  array_push($elements, "($delete_col = 0 OR $delete_col IS NULL)");
    }

    if ((static::default_scope()) && !$options['unscoped'] && !$options['escape_default_scope']) {
      if (is_string(static::default_scope()))
        array_push($elements, '('.static::default_scope().')');
      elseif (is_array(static::default_scope()))
        array_push($elements, self::build_conditions(static::default_scope(), $options['from']));
      else
        throw new Exception("default scope is neither an array nor a string");
    }

    if ($options['scope']) {
      if (is_string($options['scope']))
        array_push($elements, '('.$options['scope'].')');
      elseif (is_array($options['scope']))
        array_push($elements, self::build_conditions($options['scope'], $options['from']));
      else
        throw new Exception("scope is neither an array nor a string");
    }


    return $elements ? ' WHERE '.join(' AND ', $elements) : '';
  }



  public static function find_by_sql($query, $take = self::ALL, $exception_if_not_found = false) {
    $res = db_query($query);

    if ($take == self::FIRST) {
      if (mysql_num_rows($res)) {
        $row = mysql_fetch_assoc($res);
        $obj = static::new_instance($row, false);
        $obj->cast_attributes();

        $obj->id = $row['id'];
        $obj->attr_loaded = $obj->attr; // copy persisted attributes
        // put into cache
        static::insert_into_cache($obj);

        return $obj;
      }
      elseif($exception_if_not_found)
        throw new Exception("Record not found");
      else
        return NULL;
    }
    else {
      $return_array = array();
      if ($exception_if_not_found && mysql_num_rows($res) != $exception_if_not_found)
        throw new Exception("Not all Records were found");

      while ($row = mysql_fetch_assoc($res)) {
        $obj = static::new_instance($row, false);
        $obj->cast_attributes();

        $obj->id = $row['id'];
        $obj->attr_loaded = $obj->attr; // copy persisted attributes
        // put into cache
        static::insert_into_cache($obj);

        array_push($return_array, $obj);
      }
      return $return_array;
    }
  }



  public static function find($ids, $options = array(), $exception_if_not_found = true) {
    if (empty($ids))
      throw new ErrorException('Missing id/ids for records to find of class "'.get_called_class().'"');
    elseif (gettype($ids) == 'array') {
      $options['take'] =  self::ALL;
      $exception_if_not_found = $exception_if_not_found ? count($ids) : false;
    }
    else
      $options['take'] =  self::FIRST;
    return self::find_by(array('id' => $ids), $options, $exception_if_not_found);

  }

  public static function find_first_by($fields, $options = array(), $exception_if_not_found = false) {
    $options['take'] = self::FIRST;
    return self::find_by($fields, $options, $exception_if_not_found);
  }


  public static function find_all_by($fields, $options = array(), $exception_if_not_found = false) {
    $options['take'] = self::ALL;
    return self::find_by($fields, $options, $exception_if_not_found);
  }

  public static function paginate_all_by($fields, &$total_cnt, $options = array(), $exception_if_not_found = false) {
    $options['take'] = self::ALL;
    $per_page = $options['per_page'] ? intval($options['per_page']) : 20;
    $offset = intval($options['page']) > 1 ? intval($options['page']) * ($per_page - 1) : 0;
    $options['limit'] = $per_page;
    $options['offset'] = $offset;

    $total_cnt = self::count_by($fields, $options);
    if ($total_cnt)
      return self::find_by($fields, $options, $exception_if_not_found);
    else {
      if ($exception_if_not_found)
        throw new Exception("Not all Records were found");
      return array();
    }
  }


  public static function find_first($options = array(), $exception_if_not_found = false) {
    return self::find_first_by(NULL, $options, $exception_if_not_found);
  }
  public static function find_all($options = array(), $exception_if_not_found = false) {
    return self::find_all_by(NULL, $options, $exception_if_not_found);
  }
  public static function paginate_all(&$total_cnt, $options = array(), $exception_if_not_found = false) {
    return self::paginate_all_by(NULL, $total_cnt, $options, $exception_if_not_found);
  }



  // -- other helper functions
  public static function sanitize_value($value) {
    if ($value instanceof AbstractModel)
      $value = $value->get_id();
    elseif (is_array($value)) {
      $value = array_map(array('AbstractModel','sanitize_value'), $value);  // WARNING: recursive call, but finally nested arrays will be flatten!!!
      $value = implode(',', $value);
    }
    elseif ($value === NULL)
      $value = 'NULL';
    elseif (is_bool($value))
      $value = $value ? '1' : '0';
    elseif (is_string($value))
      $value = "'".mysql_real_escape_string($value)."'";
    elseif (!is_numeric($value))
      $value = 'NULL'; // Default: drop other values
    else
      $value = strval($value);

    return $value;
  }

  public static function build_conditions($conditions_array = NULL, $table_name = NULL) {
    $sql = '';

    if ($conditions_array === NULL)
      $conditions_array = array();

    $first = true;
    foreach ($conditions_array as $key => $value) {
      if (!$first)
        $sql .= ' AND ';
      else
        $first = false;

      if (strpos($key, '.') !== FALSE) {
        $sep = strpos($key, '.');
        $cur_table_name = substr($key, 0, $sep);
        $field = substr($key, $sep + 1);
        $sql .= "`$cur_table_name`.`$field` ";
      }
      elseif ($table_name)
        $sql .= "`$table_name`.`$key` ";
      else
        $sql .= "`$key` ";

      if (is_array($value))
        $sql .= "IN (".self::sanitize_value($value).")";
      else
        $sql .= "= ".self::sanitize_value($value);

    }
    return $sql;
  }


  public function secure_merge($attrs) {
    if ($attrs['id']) {
      if ($this->id)
        unset ($attrs['id']);
      else {
        $this->id = $attrs['id'];  // when assigning id manually, the object is dirty and can't be saved anymore for security reasons. You should check permissions before mark it as clean!!!
        $this->dirty = true;
      }
    }


    if ($this->mass_assignable) {

      // assign nested objects
      foreach ($this->relations as $key => $v) {
        if ($attrs[$key])
          $this->relations[$key]->set($attrs[$key]);
        unset($attrs[$key]);
      }

      $attrs = array_intersect_key($attrs, array_flip($this->mass_assignable));
    }
    $this->attr = array_merge($this->attr, $attrs);
  }




  // -- helpers

  private static function set_table_name() {
    self::$table_name = strtolowerunderscore(get_called_class()).'s';  // Default init the table name with the class name in lowercase and pluralized
  }

  public function cast_attributes() {
    foreach ($this->attr as $key => &$value) {
      $this->cast_attribute($key);
    }
  }

  public function cast_attribute($key) {
    global $log;

    if (!array_key_exists($key, $this->attr_defs))
	    return;

    $default_type = $this->attr_defs[$key];

    $value = $this->attr[$key];
    if ($value === NULL && $default_type !== 'boolean' && $default_type !== 'bool')      // if current value is NULL then do not do any casting!!! Otherwise, e.g in case of a string NULL will be casted to '' which overwrites the current database value to an empty string. Only in case of a boolean we must ensure the value to be true of false, because NULL is forbidden.
      return;

    $default_type = $this->attr_defs[$key];
    $cur_type = gettype($value);
    if ($cur_type == 'double') $cur_type = 'float';
    
    switch ($default_type) {
    	case 'decimal': $default_type = 'float'; break;
    	case 'text':
    	case 'binary':
    	case 'time': $default_type = 'string'; break;
    	case 'primary_key': $default_type = 'integer'; break;
    }
    
    if ($cur_type != $default_type) {
      //$log->debug("$key => $value: has type $cur_type, should be $default_type");
      switch ($default_type) {
        case 'int':
        case 'integer': $value = intval($value); break; // filter_var($value, FILTER_SANITIZE_NUMBER_INT); break;   // Notice that neither settype() nor filter_var() work here correctly, they can still return a string!!!
        case 'float': $value = floatval($value); break;
        case 'date':
        case 'datetime': $value = new DateTime($value, DateTime::getUTCTimeZone()); break;
        case 'bool':
        case 'boolean': $value = is_string($value) ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value == true; break; // if string, evaluate also string content like "false", "0", "off" and alike. If other type, cast to boolean
        default:
          if (!settype($value, $default_type))
            throw new Exception("Cannot cast attribute '$key' into a $default_type in instance of ".get_class($this)."!", E_USER_ERROR);
      }
      //$log->debug("$key => ".var_inspect($value)." is now of type ".gettype($value));
    }

    $this->attr[$key] = $value;
  }



  public function __toString() {
    return "['id' => ".var_export($this->id, true).", 'attr' => ".var_export($this->attr, true).']';
  }


  public function toJson(array $options = array()) {
  	global $log;
  	//$log->debug('-----testttttttttttttt----');
  	//$log->debug(var_export($this->toArray($options), true));
  	return skates_json_encode($this->toArray($options));
  }



  /**
   * Preprocessor for JSON and Array Export. The parameter $attr is reference to a clone of the models attributes which can be manipulates or extended for the later export
   *
   * Keep in mind that some attributes might be removed by restrictions of $this->public_fields and can be removed after the preprocessor by $options['only'] and $options['except']
   *
   * @param attr $attr
   * @return void
   */
  protected function export_preprocessor(&$attr){

  }


  /**
   *
   * @param array $options Options: 'include' => (string|array) associated models to include, 'only' => (string|array) only include these attributes, 'except' => (string|array) exclude these attributes, 'include_private_fields' => (bool) include the private fields that should only be seen by the owner of the record and be hidden to the public
   * @return array
   */
  public function toArray(array $options = array()) {
  	$associations = array();

  	global $log;
  	$log->debug('toArray options: '.var_inspect($options));

  	if ($options['include'] && (is_string($options['include']) || is_array($options['include']))) {
  		if (is_string($options['include']))
  			$options['include'] = array($options['include'] => array());

  		foreach ($options['include'] as $i => $sub) {
  			if ($this->relations[$i]) {
  			    $sub_options = array('include' => $sub);

  			    if (is_array($options['only']) && $options['only'][$i])
  			      $sub_options['only'] = $options['only'][$i];

  			    if (is_array($options['except']) && $options['except'][$i])
  			      $sub_options['except'] = $options['except'][$i];

  				$associations[$i] = $this->relations[$i]->toArray($sub_options);
  			}
  		}
  	}
  	else {  // Falls kein include-Parameter angegeben, werden einfach die assozierten Objekte mitgeliefert, die bereits geladen wurden. Dies sollte sich in der Regel damit decken, was durch $model->find_by() im include-Parameter angegeben wurde.
  		foreach ($this->relations as $i => $sub) {
  			if ($sub->is_cached()) {
  				$associations[$i] = $this->relations[$i]->toArray();
  			}
  		}
  	}

  	$attr = array_merge(array('id' => $this->id), $this->attr);

  	// Wenn einem User der Datensatz gehört, dann automatisch private_fields inkludieren, wenn diese Option nicht anders gesetzt ist.
  	if ($options['include_private_fields'] === NULL && $this->attr['user_id'] && is_logged_in() && $this->attr['user_id'] == $current_user->get_id())
  	  $options['include_private_fields'] = true;


  	if (!$options['only'] && $this->public_fields)
  		$attr = array_intersect_key($attr, array_flip($options['include_private_fields'] ? array_merge($this->public_fields, $this->private_fields) : $this->public_fields));

  	$this->export_preprocessor($attr);

  	if ($options['only']) {
  		if (is_string($options['only']))
  			$options['only'] = array($options['only']);

  		$attr = array_intersect_key($attr, array_flip($options['only']));
  	}
  	elseif ($options['except']) {
  		if (is_string($options['except']))
  			$options['except'] = array($options['except']);

  		$attr = array_diff_key($attr, array_flip($options['except']));
  	}


  	return array_merge($associations, $attr);  // ACHTUNG: Hier überschreiben entgegen dem normalen Verhalten die $attr die $associations, damit man mit export_processor die Associations überschreiben kann.
  }


  /**
   * Use with care for security/safety reasons!!!
   *
   * @param unknown_type $id
   */
  public function _set_id($id) {
    $this->id = $id;
    $this->dirty = true;
  }

  public function _is_dirty() {
    return $this->dirty;
  }


  public function _set_clean() {
    $this->dirty = false;
  }


  // caching structure

  private static $cache = array();

  private static function get_from_cache($ids, $limit = 1) {
    global $log, $fwlog;

    if (!self::$cache[get_called_class()])
      return NULL;

    if (is_array($ids)) {
      $temp_ids = $ids;
      $ids = array();
      foreach ($temp_ids as $id)
        $ids[] = strval($id);     // guarantee that every id is a string



      if (count(array_diff($ids, self::$cache[get_called_class()])) == 0) {  // are all requested records in cache?
        $fwlog->info('CACHE: Collection of '.get_called_class().' ('.join(', ', $ids).')');
        $recs = array();
        foreach ($ids as $id)
          $recs[$id] = clone self::$cache[get_called_class()][$id];
        return $recs;
      }
    }
    elseif (self::$cache[get_called_class()][$ids]) {
      $fwlog->info('CACHE: '.get_called_class().' ('.$ids.')');
      //$log->debug('CACHE: FIRST of '.get_called_class().' ('.$id.') is: '.self::$cache[$id]);
      return clone self::$cache[get_called_class()][$ids];
    }
  }

  private static function insert_into_cache(AbstractModel $record) {
    if (!self::$cache[get_called_class()])
      self::$cache[get_called_class()] = array();

    if ($record->is_new())
      throw new ErrorException('Record is new and cannot be put into Cache!');

    self::$cache[get_called_class()][$record->get_id()] = clone $record;
  }



}


?>
