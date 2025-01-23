<?php

class Attachment {
  protected $name;
  protected $base_model_instance;
  protected $options;

  protected $file_name;
  protected $content_type;
  protected $file_size;

  public function __construct($name, &$base_model_instance, $options = []) {
    $this->name = $name;
    $this->base_model_instance = &$base_model_instance;
    $this->options = $options;

    $this->file_name = $base_model_instance->get($name.'_file_name');
    $this->content_type = $base_model_instance->get($name.'_content_type');
    $this->file_size = $base_model_instance->get($name.'_file_size');
  }

  public function url() {
    return 'files/'.$this->base_model_instance->get_class_label().'/'.$this->name.'/'.$this->base_model_instance->get_id().'/'.urlencode($this->file_name);
  }
  public function urll() {
    return 'files/'.$this->base_model_instance->get_class_label().'/'.$this->name.'/'.$this->base_model_instance->get_id().'/'.$this->file_name;
  }
  public function file_name() {
    return $this->file_name;
  }
  public function content_type() {
    return $this->content_type;
  }
  public function file_size() {
    return $this->file_size;
  }

  // Aliases
  public function get_url() {
    return $this->url();
  }
  public function get_file_name() {
    return $this->file_name();
  }
  public function get_content_type() {
    return $this->content_type();
  }
  public function get_file_size() {
    return $this->file_size();
  }

  public function __toString() {
    return "['file_name' => '{$this->file_name}', 'content_type' => '{$this->content_type}', 'file_size' => '{$this->file_size}']";
  }

  public function get_field_name() {
    return $this->name;
  }
}
