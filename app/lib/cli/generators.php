<?php
defined('SKATES') or die();  // only execute if included by skates script


function unpack_field_defs($field_defs) {
  foreach ($field_defs as $def) {
    if (!preg_match('/([\w]+):([\w]+)/', $def, $matches))
      die("Error: wrong format in field definitions. Use format <field_name>:<type>");
    $fields[$matches[1]] = $matches[2];
  }
  return $fields;
}


function model_generator($model_name, $fields){
	if (!$model_name)
		die("ERROR: Missing model name");

	if (!$fields)
		die("ERROR: Missing field defs");

  $dir = dirname(__FILE__).'/';
  $filename = strtolowerunderscore($model_name).'.php';
  if (file_exists($dir.'../../model/'.$filename)) {
    echo "File app/model/$filename exists. Skipping.";
    return false;
  }

  if ($fields['id'])
    unset($fields['id']);

  foreach ($fields as $field => $type) {
    $field_defs_str .= "'$field' => '$type',
      ";
  }

  $mass_assignable_str = join(', ', array_diff(array_keys($fields), array('created_at', 'updated_at')));


	$table_name = strtolowerunderscore($model_name).'s';


	$template = file_get_contents($dir.'model_template.php');
	$template = str_replace('###model_name###', $model_name, $template);
	$template = str_replace('###table_name###', $table_name, $template);
	$template = str_replace('###mass_assignables###', $mass_assignable_str, $template);
  $template = str_replace('###field_definitions###', $field_defs_str, $template);
	file_put_contents($dir.'../../model/'.$filename, $template);
	//file_put_contents($dir.'../../model/_include_models.php', "require_once 'model/{$filename}';\n", FILE_APPEND);

	echo "created file app/model/$filename\n";
}

function migration_generator($migration_name, $fields){
  if (!$migration_name)
    die("ERROR: Missing migration name");

  $indexes = array();

  foreach ($fields as $field => $type) {
    $field_defs_str .= "'$field' => '$type',
            ";

    if (substr($field, -3) === '_id')
      $indexes []= $field;
  }

  $migration_name = strtolowerunderscore($migration_name);  // we only need the underscore-notations. No problem if it is already underscore
  $dir = dirname(__FILE__).'/';
  $date_str = date('Y_m_d_H_i_s');
  $filename = $date_str.'_'.$migration_name.'.php';

  if ($fields && preg_match('/^(add|change|remove)_(column_)?(\w+)_(to|in|from)_(\w+)$/',$migration_name, $matches)) {
    if ($matches[1] === 'add')
      $template = file_get_contents($dir.'migration_add_template.php');
    elseif ($matches[1] === 'change')
      $template = file_get_contents($dir.'migration_change_template.php');
    else
      $template = file_get_contents($dir.'migration_remove_template.php');

    foreach ($fields as $key => $value) {
      $field_name = $key;
      $type = $value;
      break; // only use the first definition of field_name:type
    }
    $table_name = $matches[5];
    $index_def = substr($field_name, -3) === '_id' ? "
        \$this->add_index('$table_name', '$field_name');" :
      '';

    $template = str_replace('###table_name###', $table_name, $template);
    $template = str_replace('###field_name###', $field_name, $template);
    $template = str_replace('###type###', $type, $template);
    $template = str_replace('###index_definition###', $index_def, $template);
  }
  elseif ($fields && preg_match('/^(create)_(table_)?(\w+)$/',$migration_name, $matches)) {
    $table_name = $matches[3];
    $template = file_get_contents($dir.'migration_create_template.php');
    $template = str_replace('###table_name###', $table_name, $template);
    $template = str_replace('###field_definitions###', $field_defs_str, $template);

    $indexes_defs_str = '';
    foreach ($indexes as $field_name) {
      $indexes_defs_str .= "
        \$this->add_index('$table_name', '$field_name');";
    }
    $template = str_replace('###index_definitions###', $indexes_defs_str, $template);
  }
  else
    $template = file_get_contents($dir.'migration_template.php');

  $template = str_replace('###date###', $date_str, $template);

  file_put_contents($dir.'../../db/migrations/'.$filename, $template);

  echo "created file db/migrations/$filename\n";
}
