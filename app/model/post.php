<?php


class Post extends AbstractModel {
	
	protected static $table_name = 'posts';
  
  public static function attribute_definitions() {
    return array (
      // Define attributes with default values here (do not define id-column!!!)
      'name' => 'string',
    	'message' => 'string',
    	'crated_at' => 'datetime',
    	'category' => 'string'
	    );
  }
  
  protected function configuration() {
  	$this->validates_presence_of('name');
  	
  	$this->validates_presence_of('message');
  }
  
  
}