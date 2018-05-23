<?php

abstract class Relation {

  protected $cached = false;
  protected $modified = false;

  protected $model_classname;
  protected $relation_name;
  protected $base_model_instance;
  protected $options;
  protected $foreign_key;
  protected $validate;

  protected $obj;



  public function __construct($relation_name, $model_classname, &$base_model_instance, $options = array()) {
    $this->relation_name = $relation_name;
    $this->model_classname = $model_classname;
    $this->base_model_instance = &$base_model_instance;
    $this->options = $options;
    $this->validate = $options['validate'] === NULL ? true : $options['validate'];
    $this->foreign_key = $options['foreign_key'] ? $options['foreign_key'] : strtolowerunderscore(get_class($base_model_instance)).'_id';
  }

  public function get_model_classname() {
    return $this->model_classname;
  }
  public function get_options() {
    return $this->options;
  }


  /**
   * helper to set cache, used for the include-option. ONLY FOR INTERNAL USE!!! Don't use this function unless you're exactly know what you are doing
   * @param mixed $obj
   */
  public function _set_cache(&$obj) {
    //global $log;
    //$log->debug('put into rel cache: '.(is_array($obj) ? 'array' : $obj));
    $this->obj = $obj;
    $this->cached = true;
  }

  /**
   * helper to set cache, used for the include-option. ONLY FOR INTERNAL USE!!! Don't use this function unless you're exactly know what you are doing
   * @param mixed $obj
   */
  public function is_cached() {
    return $this->cached;
  }

  protected function _write_include_log_message() {
    global $fwlog;
    $relation_caller_name = strtolowerunderscore(substr(get_class($this), 8));

    $relation_log_message = get_class($this->base_model_instance)." --($relation_caller_name)--> {$this->relation_name}";
    if (empty($recs_to_cache)) {
      $fwlog->info('CACHE: '.$relation_log_message);
      return;
    }
    else
      $fwlog->info('INCLUDE: '.$relation_log_message);
  }



  abstract public function get();

  abstract public function _get_for_all_of(&$records, $subinclude);

  abstract public function get_errors();

  abstract public function is_valid();

  abstract public function toArray($options = array());


  public function set($obj) {
    $this->obj = $obj;
    $this->modified = true;
  }



  protected function createHashOf(&$ary) {
    $hash = array();
    foreach ($ary as $element)
      $hash[strval($element->get_id())] = $element;

    return $hash;
  }

  public function delete() {
    throw new Exception("Not implemented!");
  }

  public function destroy() {
    throw new Exception("Not implemented!");
  }

  public function has_dependency() {
    return $this->options['dependent'];
  }

  public function _get_options() {
    return $this->options;
  }

}



// =========================================
// -----------------------------------------

class RelationBelongsTo extends Relation {
  public function __construct($relation_name, $model_classname, &$base_model_instance, $options = array()) {
    if (!$options['foreign_key'])
      $options['foreign_key'] = strtolowerunderscore($relation_name).'_id';
    parent::__construct($relation_name, $model_classname, $base_model_instance, $options);
  }

  public function get($force_reload = false) {
    global $log;
    $this->cached == $this->cached && !$force_reload;
    $classname = $this->model_classname;

    if (!$this->cached) {
      if ($this->base_model_instance->attr[$this->foreign_key]) {  // currently bound to a parent object? Or is foreign_id == 0?
        //$log->debug('get belongs_to object '.$this->base_model_instance->attr[$this->foreign_key].' of: '.$this->base_model_instance);
        $this->obj = $classname::find($this->base_model_instance->attr[$this->foreign_key], NULL, false);
      }
      else $this->obj = NULL;
      $this->cached = true;
    }
    return $this->obj;
  }

  /**
   * ONLY FOR INTERNAL USE!!
   * all $records must belong to the same relation_name!!!
   *
   * @param unknown $records
   */
  public function _get_for_all_of(&$records, $subinclude = NULL) {
    $ids = array();
    $subrec_hash = array();
    $recs_to_cache = array();
    foreach ($records as $rec) {
      if (!$rec->get_relation($this->relation_name)->is_cached()) {
        $ids[] = $rec->get($this->foreign_key);
        $recs_to_cache[] = $rec;
      }
    }
    $this->_write_include_log_message();
    if (empty($recs_to_cache))
      return;

    $ids = array_unique($ids);
    $options = $this->options;
    $options['include'] = $subinclude;

    $classname = $this->model_classname;
    $all_subrecords = $classname::find($ids, $options, false);

    // now dispatch to subrecord-hash-cache
    foreach ($all_subrecords as $subrecord)
      $subrec_hash[$subrecord->get_id()] = $subrecord;

    // ... and then fill caches of all record's relations
    foreach ($records as $rec) {
      $rec->get_relation($this->relation_name)->_set_cache($subrec_hash[$rec->get($this->foreign_key)]);
    }
  }


  public function set($element) {
    $classname = $this->model_classname;
    if ($element instanceof $classname && !$element->is_new()) {
      $this->base_model_instance->attr[$this->foreign_key] = $element->get_id();
      $this->obj = $element;
      return true;
    }
    elseif(!$element) {
      $this->base_model_instance->attr[$this->foreign_key] = 0;
      $this->obj = NULL;
      return true;
    }
    else return false;
  }

  public function is_valid() {
    return !$this->get() || $this->get()->is_valid();
  }

  public function get_errors() {
    return $this->get() ? $this->get()->get_errors() : array();
  }

  public function toArray($options = array()){
  	return $this->get() ? $this->obj->toArray($options) : null;
  }
}



// =========================================
// -----------------------------------------

class RelationHasMany extends Relation {
  public function get($force = false) {
    $this->cached == $this->cached && !$force;

    $classname = $this->model_classname;

    if (!$this->cached) {
      $this->obj = $classname::find_all_by(array(strval($this->foreign_key) => $this->base_model_instance->get_id()), $this->options);

      $this->cached = true;
    }
    return $this->obj;
  }


  /**
   * ONLY FOR INTERNAL USE!!
   *
   * @param unknown $records
   */
  public function _get_for_all_of(&$records, $subinclude = NULL) {
    global $log;
    $log->debug('__ '.$this->relation_name);

    $ids = array();
    $subrec_hash = array();
    $recs_to_cache = array();
    foreach ($records as $rec) {
      if (!$rec->get_relation($this->relation_name)->is_cached()) {
        $ids[] = $rec->get_id();
        $subrec_hash[$rec->get_id()] = array();
        $recs_to_cache[] = $rec;
      }
    }
    $this->_write_include_log_message();
    if (empty($recs_to_cache))
      return;


    $options = $this->options;
    $options['include'] = $subinclude;

    $classname = $this->model_classname;
    $all_subrecords = $classname::find_all_by(array(strval($this->foreign_key) => $ids), $options, false);

    // now dispatch to subrecord-hash-cache
    foreach ($all_subrecords as $subrecord)
      $subrec_hash[$subrecord->get(strval($this->foreign_key))][] = $subrecord;

    // ... and then fill caches of all record's relations
    foreach ($recs_to_cache as $rec) {
      $rec->get_relation($this->relation_name)->_set_cache($subrec_hash[$rec->get_id()]);
    }
  }




  public function set($obj) {
  	if (!is_array($obj))
      throw new Exception('Cannot set collection of '.$this->model_classname.' because $obj is not an array!');
  	
    foreach ($obj as &$e) {
    	$classname = $this->model_classname;
    	if (is_array($e))
    		$e = new $classname($e);
    }

    $this->obj = $obj;
    $this->modified = true;
  }


  /**
   * Adds an Element to the collection AND SAVES IT immediately. You don't need to call save() after that!
   * @param AbstractModel $element
   * @throws Exception
   */
  public function add(&$element) {
    if (!($element instanceof $this->model_classname))
      throw new Exception('Cannot add '.get_class($element).' to collection of '.$this->model_classname);
    $element->attr[$this->foreign_key] = $this->base_model_instance->get_id();
    $this->obj[]= &$element;
    return $element->save(!$this->validate);
  }


  /**
   * Removes an Element from the collection AND ALSO FROM THE DATABASE immediately. You don't need to call save() after that!
   * @param unknown_type $element
   * @throws Exception
   */
  public function remove(&$element, $destroy = true) {
    if (!($element instanceof $this->model_classname))
      throw new Exception('Cannot add '.get_class($element).' to collection of '.$this->model_classname);

    $key_to_delete = NULL;

    if ($element->is_new()) {
      foreach ($this->obj as $key => $e) {
        if ($e == $element) {
          $key_to_delete = $key;
          break;
        }
      }

    }
    else {
      foreach ($this->obj as $key => $e) {
        if ($e->get_id() == $element->get_id()) {
          $key_to_delete = $key;
          if ($destroy)
            $e->destroy();
          else
            $e->delete();
          break;
        }
      }
    }
    if ($key_of_elem !== NULL) {
      unset($this->obj[$key_of_elem]);
      return true;
    }
    else return false;
  }

  public function removeAll($destroy = true) {
    if ($destroy)
      $this->destroy();
    else
      $this->delete();
  }



  public function save($force = false) {
    if (!$this->modified)
      return true;

    $classname = $this->model_classname;

    if (!$force && $this-validate && !$this->is_valid()) {
      $this->base_model_instance->add_property_error($this->relation_name, ' enthält Fehler!');
      return false;
    }


    $old_obj = $classname::find_all_by(array(strval($this->foreign_key) => $this->base_model_instance->get_id()));
    $old_obj_hash = $this->createHashOf($old_obj);

    // save
    foreach ($this->obj as $element) {
      if (!($element instanceof $this->model_classname))
        throw new Exception('Cannot save '.get_class($element).' in collection of '.$this->model_classname);

      $element->attr[$this->foreign_key] = $this->base_model_instance->get_id();
      $element->save(true);

      if ($old_obj_hash[strval($element->get_id())])
        unset($old_obj_hash[strval($element->get_id())]);
    }

    // old_obj_hash now contains only elements that are not in the collection anymore and have to be delete
    foreach ($old_obj_hash as $key => $element)
      $element->delete();


    $this->modified = false;
    return true;
  }

  public function is_valid() {
    $this->get();
    foreach ($this->obj as $element) {
      if (!($element instanceof $this->model_classname))
        throw new Exception('Cannot validate '.get_class($element).' in collection of '.$this->model_classname);

      if (!$element->is_valid())
        return false;
    }
    return true;
  }


  public function get_errors() {
    $this->get();
    $errors = array();

    foreach ($this->obj as $element) {
      $errors = array_merge($errors, $element->get_errors());
    }
    return $errors;
  }

  public function delete() {
    $this->obj = array();
    $this->cached = true;
    $classname = $this->model_classname;

    if ($classname::has_soft_delete()) {
      $deleted_column = is_string($classname::get_soft_delete()) ? $classname::get_soft_delete() : 'deleted_at';
      db_query("UPDATE ".$classname::get_table_name().' SET '.$deleted_column.' = '.($classname::get_soft_delete_type() == 'datetime' || $classname::get_soft_delete_type() == 'time' ? ($classname::get_soft_delete_type() == 'datetime' ? 'NOW()' : time()) : 1).' WHERE `'.$this->foreign_key.'` = '.$this->base_model_instance->get_id());
    }
    else
      db_query("DELETE FROM ".$classname::get_table_name().' WHERE `'.$this->foreign_key.'` = '.$this->base_model_instance->get_id());
  }

  public function destroy() {
    $this->get();

    // check if we can use delete() instead if there are no sub-relations with destroy/delete-dependency
    if (empty($this->obj))
      return;
    else {
      $has_subdependency = false;
      foreach ($this->obj[0]->get_relations() as $relation) {
        if ($relation->has_dependency()) {
          $has_subdependency = true;
          break;
        }
      }
      if (!$has_subdependency)
        return $this->delete();  // no sub-relations with destroy/delete-dependency, so we can use delete(). It's much quicker!
    }

    foreach ($this->obj as &$o) {
      $o->destroy();
    }
    $this->obj = array();
  }

  public function toArray($options = array()) {

  	$jsonObjects = $this->get();
  	foreach ($jsonObjects as $obj) {
  		$obj = $obj->toArray($options);
  	}

  	return $jsonObjects;
  	//return '['.join(',',$jsonObjects).']';  // no, this is for JSON, not for array
  }

}





// =========================================
// -----------------------------------------

class RelationHasOne extends Relation {

  public $objId;

  public function get($attr = NULL, $force_reload = false) {
    $classname = $this->model_classname;
    $this->cached = $this->cached && !$force_reload;

    if (!$this->cached) {
      if (!$this->base_model_instance->is_new()) {
        $this->obj = $classname::find_first_by(array(strval($this->foreign_key) => $this->base_model_instance->get_id()));
        if ($this->obj)
          $this->objId = $this->obj->get_id();
      }
      $this->cached = true;
    }
    if (!is_string($attr) || $this->obj == NULL )
      return $this->obj;
    else
      return $this->obj->get($attr);

  }


  /**
   * ONLY FOR INTERNAL USE!!
   *
   * @param unknown $records
   */
  public function _get_for_all_of(&$records, $subinclude = NULL) {
    $ids = array();
    $subrec_hash = array();
    $recs_to_cache = array();
    foreach ($records as $rec) {
      if (!$rec->get_relation($this->relation_name)->is_cached()) {
        $ids[] = $rec->get_id();
        $recs_to_cache[] = $rec;
      }
    }
    $this->_write_include_log_message();
    if (empty($recs_to_cache))
      return;


    $options = $this->options;
    $options['include'] = $subinclude;

    $classname = $this->model_classname;
    $all_subrecords = $classname::find_all_by(array(strval($this->foreign_key) => $ids), $options);

    // now dispatch to subrecord-hash-cache
    foreach ($all_subrecords as $subrecord)
      $subrec_hash[$subrecord->get(strval($this->foreign_key))] = $subrecord;  // only one record for key. record with same foreign key overwrites the previous one.

    // ... and then fill caches of all record's relations
    foreach ($recs_to_cache as $rec) {
      $rec->get_relation($this->relation_name)->_set_cache($subrec_hash[$rec->get_id()]);
    }
  }


  public function set($element) {
    //if (!$this->cached)
    //  $this->get();
    $this->get();

    if (is_array($element)) {
      if ($this->obj)
        $this->obj->secure_merge($element);
      else {
        $element = new $this->model_classname($element);
        $this->obj = $element;
      }
      $this->obj->attr[$this->foreign_key] = $this->base_model_instance->get_id();
    }
    elseif ($element != NULL && !($element instanceof $this->model_classname))
      throw new Exception('Element is not an instance of '.$this->model_classname);
    else
      $this->obj = $element; // TODO: correct???


    $this->modified = true;
  }





  public function save($force = false) {
    //global $log;

    if (!$this->modified)
      return true;

    $classname = $this->model_classname;

    if ($this->obj) {
      if ($this->obj->_is_dirty()) {
        if ($this->cached && $this->obj->get_id() != $this->objId)
          throw new Exception("Security violation! Cannot update a nested object that doesn't belong this object!");
        elseif (!$this->cached) {
          $current = $classname::find_first_by(array(strval($this->foreign_key) => $this->base_model_instance->get_id()));
          if (!$current)
            throw new Exception("Security violation! There is no nested object of ".$this->model_classname." with ID ".$this->obj->get_id());
          elseif ($this->obj->get_id() != $current->get_id())
            throw new Exception("Security violation! Cannot update a nested object that doesn't belong this object!");
        }

        $this->obj->_set_clean();
      }

      $this->obj->attr[$this->foreign_key] = $this->base_model_instance->get_id();

      if ($this->obj->save(!$this->validate || $force)) {
        //$this->modified = false;
        return true;
      }
      else return false;
    }
    else {
    	if ($classname::has_soft_delete()) {
    	  $deleted_column = is_string($classname::get_soft_delete()) ? $classname::get_soft_delete() : 'deleted_at';
    	  db_query("UPDATE ".$classname::get_table_name().' SET '.$deleted_column.' = '.($classname::get_soft_delete_type() == 'datetime' || $classname::get_soft_delete_type() == 'time' ? ($this->attr_defs[$deleted_column] == 'datetime' ? 'NOW()' : time()) : 1).' WHERE `'.$this->foreign_key.'` = '.$this->base_model_instance->get_id());
    	}
    	else
        db_query("DELETE FROM ".$classname::get_table_name().' WHERE `'.$this->foreign_key.'` = '.$this->base_model_instance->get_id());
      $this->modified = false;
      return true;
    }

  }

  public function delete() {
    $this->obj = NULL;
    $this->cached = true;
    $classname = $this->model_classname;

    if ($classname::has_soft_delete()) {
      $deleted_column = is_string($classname::get_soft_delete()) ? $classname::get_soft_delete() : 'deleted_at';
      db_query("UPDATE ".$classname::get_table_name().' SET '.$deleted_column.' = '.($classname::get_soft_delete_type() == 'datetime' || $classname::get_soft_delete_type() == 'time' ? ($this->attr_defs[$deleted_column] == 'datetime' ? 'NOW()' : time()) : 1).' WHERE `'.$this->foreign_key.'` = '.$this->base_model_instance->get_id());
    }
    else
      db_query("DELETE FROM ".$classname::get_table_name().' WHERE `'.$this->foreign_key.'` = '.$this->base_model_instance->get_id());
  }

  public function destroy() {
    $this->get();
    if ($this->obj)
      $this->obj->destroy();
    $this->obj = NULL;
  }

  public function is_valid() {
    return !$this->get() || $this->get()->is_valid();
  }

  public function get_errors() {
    return $this->get() ? $this->get()->get_errors() : array();
  }

  public function toArray($options = array()){
  	return $this->get() ? $this->obj->toArray($options) : null;
  }
}

