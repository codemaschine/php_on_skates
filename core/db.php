<?php


function on_database_do($inter_db_config, $func) {
  global $db_config;
  $main_db_config = clone $db_config;

  db_init($inter_db_config);
  $func();
  db_init($main_db_config);

}

/**
 * initialize/Switch database connection
 *
 * @param string $env_name key to get the connection-object out of the $site_configs. If not specified, the default $db_config is choosen.
 * @throws Exception
 * @return resource the mysql link identifier returned by mysqli_connect.
 */
function db_init($env_name = null) {
  global $site_configs, $db_config, $db_link, $fwlog;


  if ($env_name)
    $new_db_config = $site_configs[$env_name]['db'];
  else
    $new_db_config = $db_config;

  $fwlog->info("Set Database to '{$env_name}' (Host: {$new_db_config->host}, Port: {$new_db_config->port}, User: {$new_db_config->user}, Database: {$new_db_config->name})");

  if($db_link === NULL || $new_db_config->host != $db_config->host || $new_db_config->port != $db_config->port || $new_db_config->user != $db_config->user) {
    $fwlog->info("Close current database connection (Host: {$db_config->host}, Port: {$db_config->port}, User: {$db_config->user}, Database: {$db_config->name}) for new connection.");
    if ($db_link !== NULL) {
      mysqli_close($db_link);
    }

    $db_link = @mysqli_connect($new_db_config->host, $new_db_config->user, $new_db_config->pass, $new_db_config->name, ($new_db_config->port ? $new_db_config->port : ini_get("mysqli.default_port")));
    if (!$db_link) {
      throw new Exception('Keine Datenbank-Verbindung m&ouml;glich: ' . mysqli_connect_error());
    }

    mysqli_set_charset($db_link, 'utf8');
  }

  if (!mysqli_select_db($db_link, $new_db_config->name)) {
    throw new Exception('Datenbank "'.$new_db_config->name.'" nicht verf&uuml;gbar');
  }

  $db_config = $new_db_config; // set new config as current config

  return $db_link;
}

// TODO: Remove parameter $link?
function db_query($query_string, $link = false) {
  global $fwlog, $db_link;

  if (!$link) {
    $link = $db_link;
  }

  if (!$link) {
    throw new Exception('Ung&uuml;ltiger Aufruf: Ein DB-Link muss angegeben werden.');
  }

  $fwlog->info("SQL-Query: $query_string");
  $res = mysqli_query($link, $query_string);

  if (!$res) {
    throw new Exception('Ung&uuml;ltige Abfrage: ' . mysqli_error($link) . ". SQL-Query: {$query_string}");
  }

	return $res;
}

function db_insert($table, $entries) {
  global $db_link;

	$keys = '`'.implode('`, `', array_keys($entries)).'`';

	$current_time = date('Y-m-d H:i');

	foreach ($entries as $key => &$value) {
		if ($key == 'created_at' || $key == 'updated_at')
		  $value = $current_time;
		if ($value === NULL)
		  $value = 'NULL';
		elseif (is_bool($value))
      $value = $value ? '1' : '0';
    elseif (is_string($value))
	    $value = "'".mysqli_real_escape_string($db_link, $value)."'";
	  elseif (!is_integer($value))
	    $value = 'NULL'; // Default: drop other values
	}
	$values_ary = array_values($entries);
	$values = implode(', ', $values_ary);

	$query = "INSERT INTO `$table` ( $keys )
            VALUES ( $values )";

	db_query($query);
	return mysqli_insert_id($db_link);
}



function db_update($table, $entries) {
  global $db_link;

	if (!isset($entries['id']) || !$entries['id'])
	  throw new Exception("Missing ID for update entry table '$table'!", E_USER_ERROR);

  $record_id = (int) $entries['id'];
  unset($entries['id']);

  $entries['updated_at'] = date('Y-m-d H:i');

  $update_args = array();
  foreach ($entries as $key => &$value) {
    if ($value === NULL)
      $value = 'NULL';
    elseif (is_bool($value))
      $value = $value ? '1' : '0';
    elseif (is_string($value))
      $value = "'".mysqli_real_escape_string($db_link, $value)."'";
    elseif (!is_integer($value))
      $value = 'NULL'; // Default: drop other values

    array_push($update_args, "`$key` = $value");
  }

  $query = "UPDATE `$table` SET ".implode(", ", $update_args)."
            WHERE id = $record_id";

  db_query($query);
  return mysqli_affected_rows($db_link);
}

?>
