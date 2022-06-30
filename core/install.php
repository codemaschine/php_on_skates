<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

$abs_base_path = substr(dirname(__FILE__),0,strrpos(dirname(dirname(__FILE__)),'/'));
define('ROOT_DIR', $abs_base_path.'/');
define('SKATES_DIR', $abs_base_path.'/skates/');
define('APP_DIR', $abs_base_path.'/app/');



if (file_exists(APP_DIR))
  die ('An app directory exists. Skates seems to be initialized already!');

$message = '';
$initialized = false;


function copy_recursive($source, $dest) {
	if (substr($dest,-1) == '/')
		$dest = substr($dest,0,strlen($dest) - 1);

	global $message;
	if (!file_exists($dest) && !mkdir($dest, 0775))
		return false;
	else
		@chmod($dest, 0775);
	foreach (
	$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST) as $item
	) {
		/** @var RecursiveDirectoryIterator $iterator */
		if ($item->isDir()) {
			if (mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0775)) {
				$message .= "Created dir: ".$dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName()."<br>\n";
			    chmod($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0775);
			}

		} else {
			if (copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
				$message .= "Created file: ".$dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName()."<br>\n";
				chmod($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0664);
			}
		}
	}
	return true;
}

// Here's a startsWith function
function startsWith($haystack, $needle){
  $length = strlen($needle);
  return (substr($haystack, 0, $length) === $needle);
}


?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Skates initialization</title>
<style type="text/css">
body { font-family: sans-serif; }

label { display: inline-block; width: 100px; }

.notice { color: #ff0000; }

</style>
</head>
<body>
<h1>Skates Initialization</h1>



<?php



while (!empty($_POST)) { // eigentlich eine if-Abfrage, aber hier wird while verwendet, um mit break herausspringen zu können. Da es nicht als Schleife verwendet werden soll, muss am Ende auf jeden Fall ein break stehen.
  if (!($_POST['dbhost'] && $_POST['dbuser'] && $_POST['database'])) {
    $message = 'Host, user and database are required!';
    break;
  }

  extract($_POST);


  // check database connection
  try {
  	$pdo = new PDO("mysql:host=$dbhost;dbname=$database;charset=utf8mb4", $dbuser, $dbpassword);
  } catch (PDOException $e) {
  	echo 'Connection failed: ' . $e->getMessage();
  }



  // -- create the app-directory
  if (!copy_recursive(SKATES_DIR.'boilerplates/app', APP_DIR)) {
  	$message = "Error: Cannot create app directory and it's files: ".APP_DIR;
  	break;
  }

  // -- create the app-directory
  if (!copy_recursive(SKATES_DIR.'static', ROOT_DIR)) {
  	$message = "Error: Cannot static files (css and js files) in root dir!";
  	break;
  }

  if (copy(SKATES_DIR.'boilerplates/.htaccess', ROOT_DIR.'.htaccess')) {
  	$message .= "Created file: ".ROOT_DIR.'.htaccess';
  	chmod(ROOT_DIR.'.htaccess', 0664);
  }
  else
  	$message .= "Error: Could not create file: ".ROOT_DIR.'.htaccess';

  chdir(ROOT_DIR);

  /*
  $skates_path = substr(dirname(__FILE__),strrpos(dirname(__FILE__),'/') + 1);
  if (!$skates_path != 'skates') { // in case that it's skates_[version]: create a symbolic link;
    if (!symlink($skates_path, 'skates'))
    	$message .= "WARNING: Could not create symbolic link to path to '$skates_path' with name 'skates'. You have to create this link manually or rename '$skates_path' to 'skates'";
  }

  if (!symlink('skates/skates.php', 'skates.php'))
  	$message .= "WARNING: Could not create symbolic link to file 'skates/skates.php' with name 'skates.php' . You have to create this link manually or copy the file.";
  */

  $config_file = file_get_contents(SKATES_DIR.'boilerplates/config.php');
  foreach (array('DEV_DB_HOST' => $dbhost, 'DEV_DB_USER' => $dbuser, 'DEV_DB_PASS' => $dbpassword, 'DEV_DB_NAME' => $database) as $name => $value) {
  	$config_file = str_replace("__{$name}__", $value, $config_file);
  }
  if (@file_put_contents(APP_DIR."config.php", $config_file) === false) {
  	$message = 'Could not create file "app/config.php". Please check file permissions!';
  	break;
  }

  chmod(APP_DIR."config.php", 0664);

  copy(SKATES_DIR.'boilerplates/development.txt',APP_DIR.'development.txt');
  chmod(APP_DIR.'development.txt', 0664);

  copy(SKATES_DIR.'boilerplates/routes.php',APP_DIR.'routes.php');
  chmod(APP_DIR.'routes.php', 0664);


  // create bin folder
  mkdir(ROOT_DIR.'bin');
  chmod(ROOT_DIR.'bin', 0775);
  copy(SKATES_DIR.'boilerplates/app/.htaccess',ROOT_DIR.'bin/.htaccess');
  chmod(ROOT_DIR.'bin/.htaccess', 0664);
  chdir(ROOT_DIR.'bin');
  symlink('../skates/cli/cli.php', 'skates.php');
  //chmod('./skates.php', 0775);
  symlink('../skates/core/db/migrate.php', 'migrate.php');
  //chmod('./migrate.php', 0775);

  mkdir(ROOT_DIR.'log');
  touch(ROOT_DIR.'log/.keep');

  chdir(ROOT_DIR);
  symlink('skates/skates.php', 'skates.php');
  unlink('install.php');



  //$message .=

  $initialized = true;
  break;
}

if ($message)
  echo "<div class=\"notice\">$message</div>";



if ($initialized) {
  echo "Skates initialized! Now you can start scaffolding on your console with 'php skates.php generate scaffold field1:type field2:type ...'";
}
else {

?>



<form action="" method="post">
<p>
  <label for="dbhost">
    DB-Host
  </label>
  <input id="dbhost" name="dbhost" type="text" size="50" value="<?php echo isset($_POST['dbhost']) ? $_POST['dbhost'] : '127.0.0.1'; ?>" />
</p>
<p>
  <label for="dbuser">
    DB-User
  </label>
  <input id="dbuser" name="dbuser" type="text" size="50" value="<?php echo isset($_POST['dbuser']) ? $_POST['dbuser'] : 'root'; ?>" />
</p>
<p>
  <label for="dbpassword">
    DB-Password
  </label>
  <input id="dbpassword" name="dbpassword" type="text" size="50" value="<?php echo isset($_POST['dbpassword']) ? $_POST['dbpassword'] : 'root'; ?>" />
</p>
<p>
  <label for="database">
    Database
  </label>
  <input id="database" name="database" type="text" size="50" value="<?php echo isset($_POST['database']) ? $_POST['database'] : ''; ?>" />
</p>
<p>
  <input id="submit" name="submit" type="submit" value="Installation starten" />
</p>
</form>

<?php
}

?>

</body>
</html>





