<?php
defined('SKATES') or die();  // only execute if included by skates script

require_once 'generators.php';



if (!$argv[2])
	echo "ERROR: missing generator

Available generators:
		model      Generate a model
    migration  Generate a migration

";


switch ($argv[2]) {
  case 'model':

  	$name = ucfirst($argv[3]);
  	$field_defs = array_slice($argv, 4);

    $field_names = array_keys(unpack_field_defs($field_defs));
    if (!in_array('id', $field_names))
      array_unshift($field_defs, 'id:primary_key');
    if (!in_array('created_at', $field_names))
      array_push($field_defs, 'created_at:datetime');
    if (!in_array('updated_at', $field_names))
      array_push($field_defs, 'updated_at:datetime');

    $fields = unpack_field_defs($field_defs);

  	if (model_generator($name, $fields) !== false) // returns false when skipping
      migration_generator('create'.Inflect::pluralize($name), $fields);  //TODO: better pluralization here

    break;

  case 'migration':

    $migration_name = $argv[3];
    $field_defs = array_slice($argv, 4);
    $fields = unpack_field_defs($field_defs);

    migration_generator($migration_name, $field_defs);

    break;

  default:
    echo "ERROR: unknown generator '{$argv[2]}'

Available generators:
    model      Generate a model
    migration  Generate a migration

";

}
