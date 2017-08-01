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

if (!is_logged_in() && $_COOKIE['user_identcode'] && $_COOKIE['user_cookie_login_code']) { // Refresh Login
  $log->debug('Refresh login by cookie!');
  login_by_identcode($_COOKIE['user_identcode'], $_COOKIE['user_cookie_login_code']);
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
  $user = User::find_first_by(array('email' => $login));
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



function login_by_rmcode($rmcode, $userid) {
  $user = User::find_first_by(array('id' => $userid));
  if ($user){
    if($user->get('rm_code')!==NULL){
      	if($user->get('rm_code')===$rmcode && login($user, "on")){
          return TRUE;
        }else{
          sleep(5); // SECURITY: gegen Brute-Force-Attaken schützen!!!!
          return FALSE;
        }
    }else{
        sleep(5); // SECURITY: gegen Brute-Force-Attaken schützen!!!!
        return FALSE;
    }
  }else {
  	sleep(5); // SECURITY: gegen Brute-Force-Attaken schützen!!!!
  	return FALSE;
  }
}

function login(User $user, $remember_me = "off") {
  global $J_USER, $current_user;
  
  if (!$user->id())
    return false;
    
  $_SESSION['user_id'] = $user->id();
  if ($remember_me=="on") {
    setcookie("user_id", $user->id(),time()+86400*3650);
    if($user->get('rm_code')!==NULL){
        setcookie("user_rmcode", $user->get('rm_code'),time()+86400*365);
        $rm_code = $user->get('rm_code');
    }else{
        $rm_code=substr(md5(mt_rand()), 0, 16);
        setcookie("user_rmcode", $rm_code,time()+86400*365);
    }
  }
  
  $user->attr['previous_login'] = $user->attr['last_login'];
  $user->attr['last_login'] = gmdate('Y-m-d h:i:s \G\M\T');
  $user->set('rm_code', $rm_code);
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
  
  $current_user->set('rm_code', NULL);
  
  $J_USER = $current_user = NULL;
}



function is_logged_in() {
  return $_SESSION['user_attr'] != NULL;
}


function is_admin() {
  global $current_user;
  if(is_logged_in() && $current_user->get('is_admin')==1){
      return TRUE;
  }
  return FALSE;
}


?>