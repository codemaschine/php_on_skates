<?php

require dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/app/config.php';   /// NICHT require_once() nehmen!!! Es werden sonst variablen nicht in anderen Geltungsbereichen definiert!!!!

// do not edit these lines unless you are absolutely sure what you are doing
$db_config->db_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/app/migrations/';
$db_config->method = 1;
$db_config->migrations_table = 'db_migrations';
