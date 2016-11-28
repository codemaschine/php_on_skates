<?php

class Attachment {
	
	protected $name;
	protected $base_model_instance;
    protected $options;
    
    protected $file_name;
    protected $content_type;
    protected $file_size;
    
	
	public function __construct($name, &$base_model_instance, $options = array()) {
		$this->name = $name;
		$this->base_model_instance = &$base_model_instance;
		$this->options = $options;
		
		$this->file_name = $base_model_instance->get($name.'_file_name');
		$this->content_type = $base_model_instance->get($name.'_content_type');
		$this->file_size = $base_model_instance->get($name.'_file_size');
	}
	
	public function url() {
		return 'files/'.$this->base_model_instance->get_class_label().'/'.$this->name.'/'.$this->base_model_instance->get_id().'/'.$this->file_name;
	}
	
	public function __toString() {
		return "['file_name' => '{$this->file_name}', 'content_type' => '{$this->content_type}', 'file_size' => '{$this->file_size}']";
	}
}