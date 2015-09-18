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
 * @return resource the mysql link identifier returned by mysql_connect.
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
      mysql_close($db_link);
      //var_dump($new_db_config);
      //$fwlog->info("Changed Database to '{$env_name}' (Host: {$new_db_config->host}, Port: {$new_db_config->port}, User: {$new_db_config->user})");
    }
    
    $db_link = @mysql_connect($new_db_config->host.':'.($new_db_config->port ? $new_db_config->port : '3306'), $new_db_config->user, $new_db_config->pass);
    if (!$db_link) {
        throw new Exception('Keine Datenbank-Verbindung m&ouml;glich: ' . mysql_error());
    }

    mysql_set_charset('utf8', $db_link);
    
    
  }
    
  if (!mysql_select_db($new_db_config->name, $db_link)) {
    throw new Exception('Datenbank "'.$new_db_config->name.'" nicht verf&uuml;gbar');
  }
  
  $db_config = $new_db_config; // set new config as current config
  
  return $db_link;
}

function db_query($query_string, $link = false) {
  global $fwlog;
  
  $fwlog->info("SQL-Query: $query_string");
	if ($link) 
	  $res = mysql_query($query_string, $link);
	else
	  $res = mysql_query($query_string);
	  
	if (!$res) {
	    throw new Exception('Ung&uuml;ltige Abfrage: ' . mysql_error() . ". SQL-Query: {$query_string}");
	}
  
	return $res;
}

function db_insert($table, $entries) {
	
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
	    $value = "'".mysql_real_escape_string($value)."'";
	  elseif (!is_integer($value))
	    $value = 'NULL'; // Default: drop other values
	}
	$values_ary = array_values($entries);
	$values = implode(', ', $values_ary);
	
	$query = "INSERT INTO `$table` ( $keys )
            VALUES ( $values )";
  
	db_query($query);
	return mysql_insert_id();
}



function db_update($table, $entries) {
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
      $value = "'".mysql_real_escape_string($value)."'";
    elseif (!is_integer($value))
      $value = 'NULL'; // Default: drop other values
      
    array_push($update_args, "`$key` = $value");
  }
  
 
  $query = "UPDATE `$table` SET ".implode(", ", $update_args)." 
            WHERE id = $record_id";
  
  db_query($query);
  return mysql_affected_rows();
}



?>