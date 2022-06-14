<?php
defined('SKATES') or die();  // only execute if included by skates script

require_once 'generators.php';



if (!$argv[2]) {
	echo "ERROR: missing generator

Available generators:
	controller  Generate a controller
	model       Generate a model
    migration   Generate a migration
	

";
  return;
}

if (!$argv[3]) {
  echo "ERROR: missing name for object to generate!";
  return;
}

function unpack_field_defs($field_defs) {
  $fields = array();
  foreach ($field_defs as $def) {
    if (!preg_match('/([_\w]+):([\w]+)/', $def, $matches))
      die("Error: wrong format in field definition '$def'. Use format <field_name>:<type>");
    if (count($matches) > 2)  
      $fields[$matches[1]] = $matches[2];
    else
      $fields[$matches[0]] = $matches[1];
  }
  return $fields;
}



switch ($argv[2]) {
  case 'scaffold':
	
  case 'views':
	
	$name = ucfirst($argv[3]);
	
	$field_defs = array_slice($argv, 4);
	$fields = unpack_field_defs($field_defs);

	views_generator($name, $fields);
	
	if ($argv[2] != 'scaffold')
		break;

  case 'controller':
	
	$name = ucfirst($argv[3]);
	controller_generator($name);

  if (file_exists(APP_DIR.'routes.php')) {
    $routes = file_get_contents(APP_DIR.'routes.php');
    $resources = "\$router->resources('".strtolower($name)."');";
    if (mb_strpos($routes, $resources) === false) {
      $routes = preg_replace('/(load_routes[^{]*{)/', "$1\n  $resources", $routes);
      file_put_contents(APP_DIR.'routes.php', $routes);
      echo "updated file app/routes.php\n";
    }
  }
	
	if ($argv[2] != 'scaffold')
		break;
	
		
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

    migration_generator($migration_name, $fields);

    break;

  default:
    echo "ERROR: unknown generator '{$argv[2]}'

Available generators:
    model      Generate a model
    migration  Generate a migration

";

}
