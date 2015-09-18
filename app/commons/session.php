<?php

if ($_FRAMEWORK['format'] == 'json') {						// allow Session-ID in URL if JSON-Request
	ini_set('session.use_cookies', '0');
	ini_set('session.use_only_cookies', '0');
	session_set_cookie_params(null,null,null,null,false);
}

session_name('session_id'); // for access in URL in JSON-Requests
session_start();


$current_user = NULL;
//$log->debug('cookie: '.var_export($_COOKIE, true));
if (!is_logged_in() && $_GET['ident']) {
  $log->debug('Login by identcode!');
  login_by_identcode($_GET['ident']);

	// Wird umgeleitet zur selben URI, damit der identcode verschwindet
	$uri=str_replace("ident=".$_GET['ident'],"",$_SERVER['REQUEST_URI']);  
	header("Location: ".$uri);
	exit;
}
if (!is_logged_in() && $_COOKIE['user_identcode'] && $_COOKIE['user_cookie_login_code']) { // Refresh Login
  $log->debug('Refresh login by cookie!');
  login_by_identcode($_COOKIE['user_identcode'], $_COOKIE['user_cookie_login_code']);
}
elseif (!is_logged_in() && $_SESSION['ade_hash']) {
  $jde_login = JdeLogin::find_first_by(array('ade_hash' => $_SESSION['ade_hash']), array('conditions' => 'jde_response_time > 0'));
  if ($jde_login) {
    if ($jde_login->get('is_valid') && $jde_login->get('user')) {
      // TODO: JdeLogin löschen, wenn die Logins nicht getrackt werden sollen.
      // $jde_login->destroy();
      $log->debug('Eingeloggen über jesus.de erfolgreich!');
      $user = $jde_login->get('user');
      login($jde_login->get('user'));
    }
    unset($_SESSION['ade_hash']);
    $_SESSION['logged_in_by_jesus_de'] = true;
  }
}
elseif (is_logged_in()) {  // if user is logged in
  $log->debug('SESSION valid!');
  $J_USER = $_SESSION['user_attr'];
  $current_user = User::find($_SESSION['user_id'], null, false); // unserialize($_SESSION['user']);  // 'unserialize' NICHT verwenden, da 'logout all' sonst nicht funktioniert. Die 'logout_all_time' muss immer frisch geholt werden.
  if (!$current_user) // User könnte gelöscht sein.
    logout();
  else {
    
    $current_user->set('last_seen', time()); // wird für Push-Notifications benötigt.
    
    // Wenn auf allen Geräten abgemeldet
    if ($_SESSION["device_login_session_time"] < $current_user->get('logout_all_time')) {
      logout();
      set_flash('Deine Sitzung ist abgelaufen. Bitte melde dich erneut an.');
      $_FRAMEWORK['redirect'] = 'index.php';
    }
  }
}




// ----- functions -----

function login_by_password($login, $password, $remember_me = false) {
  $user = User::find_first_by(array('login' => $login));
  if (empty($user)) { // User nicht gefunden
    setcookie("user_id", '',0);
    setcookie("user_identcode", '',0);
    setcookie("user_cookie_login_code", '',0);
    sleep(5); // SECURITY: gegen Brute-Force-Attaken schützen!!!!
    return false;
  }
  if ($user->check_password_with($password))
    return login($user, $remember_me);
  else {// Logindaten falsch
  	sleep(5); // SECURITY: gegen Brute-Force-Attaken schützen!!!! 
    return false;
  }
}



function login_by_identcode($identcode, $cookie_login_code = false) {
  if ($cookie_login_code === false)
    $user = User::find_first_by(array('identcode' => $identcode));
  else
    $user = User::find_first_by(array('identcode' => $identcode, 'cookie_login_code' => $cookie_login_code));
  if ($user && login($user))
  	return true;
  else {
  	sleep(5); // SECURITY: gegen Brute-Force-Attaken schützen!!!!
  	return false;
  }
}

function login(User $user, $remember_me = false) {
  global $J_USER, $current_user;
  
  if (!$user->id())
    return false;
    
  $_SESSION['user_id'] = $user->id();
  if ($remember_me) {
    if (!$user->get('cookie_login_code'))
      $user->set('cookie_login_code', time());
    
    setcookie("user_id", $user->id(),time()+86400*3650);
    setcookie("user_identcode", $user->attr['identcode'],time()+86400*3650);
    setcookie("user_cookie_login_code", $user->attr['cookie_login_code'],time()+86400*3650);
  }
  if ($user->get('status') == 'init')
    $user->attr['status'] = 'active';
  $user->attr['previous_login'] = $user->attr['last_login'];
  $user->attr['last_login'] = time();
  $user->save(true);
  
  
  $J_USER = $_SESSION['user_attr'] = $user->attr;
  $current_user = $user;
  
  $_SESSION['device_login_session_time'] = time();
  return true;
}


function logout($logout_all = false) {
  global $J_USER, $current_user;
  
  unset($_SESSION['user_id']);
  unset($_SESSION['user_attr']);
  
  setcookie("user_id", '',0);
  setcookie("user_identcode", '',0);
  setcookie("user_cookie_login_code", '',0);
  
  if ($logout_all) {
    $current_user->set('logout_all_time', time());
    $current_user->set('cookie_login_code', 0);
    $current_user->save(true);
  }
  
  $J_USER = $current_user = NULL;
}



function is_logged_in() {
  return $_SESSION['user_attr'] != NULL;
}


function is_admin() {
  global $current_user;
  return is_logged_in() && $current_user->is_admin();
}


?>