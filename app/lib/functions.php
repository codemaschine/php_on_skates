<?php 

define('MAX_TODAYS_AMOUNT', 3);

// Globale add_activity-Möglichkeit ohne Objekt
function add_activity($action) {
global $current_user;
if (is_logged_in())
  $user_name = $current_user->get('vorname');
else
  $user_name = '';

Activity::create(array('user_name' => $user_name, 'action' => $action));
}


function nice_num($num){
	
	return number_format($num,0,",",".");	
	
}
function commons_nice_time($time,$with_hours = 0, $with_ca = 0, $date_prefix = '', $with_seconds = 0 ){

	if($with_ca) $ca ="ca. ";
	
	if($with_hours==-1)
		$days_only=1;
		
	if($with_hours==1)
		$hours=", $ca".date("G:i",$time)." Uhr";
	else if($with_hours==2)
		$hours=", $ca".date("G",$time)." Uhr";
	else if($with_hours==3)
		$hours=" um $ca".date("G:i",$time)." Uhr";
	else
		$hours="";
	
	$now = time();
	$today = datehash($now);
	$date = datehash($time);
	
	if ($date_prefix)
		$date_prefix .= ' ';
	
	if ($now >= $time) { // Vergangenheit?
	
	 if(!$days_only){
	  if ($now - $time < ONE_MINUTE / 6)
	    return "gerade eben";
	  if ($now - $time < ONE_MINUTE * 1.6 && $with_seconds )
	    return "vor ".(round((floor(($now - $time) / ONE_MINUTE * 60)+$with_seconds/$with_seconds)/$with_seconds)*$with_seconds)." Sekunden"; 
	  if ($now - $time < ONE_MINUTE)
	    return "gerade eben";
	  if ($now - $time < ONE_MINUTE * 2)
	    return "vor 1 Minute";
	  if ($now - $time <=  100 * ONE_MINUTE)
	    return "vor ".floor(($now - $time) / ONE_MINUTE)." Minuten"; 
	 }
	  if ($today == datehash($time))
	    return "heute".$hours;
	  if ($date == datehash(time() - ONE_DAY))
	    return "gestern".$hours;
	  if ($date == datehash(time() - ONE_DAY * 2))
	    return "vorgestern".$hours;
	  if ($date >= datehash(time() - ONE_DAY * 6))
	    return strftime("%A",$time).$hours;
	  else
	    return $date_prefix.date("d.m.Y",$time).$hours;
	}
	else { // Zukunft
	  if ($today == datehash($time))
	    return "heute".$hours;
	  if ($date == datehash(time() + ONE_DAY))
	    return "morgen".$hours;
	  if ($date == datehash(time() + ONE_DAY * 2))
	    return "übermorgen".$hours;
	  if ($date <= datehash(time() + ONE_DAY * 6))
	    return strftime("%A",$time).$hours;
	  else
	    return $date_prefix.date("d.m.Y",$time).$hours;
	}	
}

function datehash($time) {
  return date("ymd",$time);
}



function commons_do_login(){
global $J_USER;

	// E-Mail reinigen
	$_POST["do_login"] = filter_var($_POST["do_login"], FILTER_SANITIZE_EMAIL);
	$_POST["do_login_name"] = addslashes($_POST["do_login_name"]);

	// E-Mail-Adresse valide?
	if(filter_var($_POST["do_login"], FILTER_VALIDATE_EMAIL)){
		$err_text[]="<br>do_login=".$_POST[do_login];

		$err_text[]="<br>Versuche Login...";

 		// Abfrage, ob diese Mail-Adresse vorhanden ist
		$r = mysql_query("select * from japps_user where login = '".addslashes($_POST["do_login"])."'");
		$err_text[]= "select * from japps_user where login = '".addslashes($_POST["do_login"])."' ".mysql_error()." | ";						
		
		// E-Mail vorhanden >> dann hole die Daten
		if(mysql_num_rows($r)){


			$err_text[]="| Mail vohanden ";
		
			$r = mysql_query("select * from japps_user where login='".$_POST["do_login"]."'");
			$J_USER["id"] = mysql_result($r,0,"id");
			$J_USER["email"] = mysql_result($r,0,"email");
			$J_USER["identcode"] = mysql_result($r,0,"identcode");

		// E-Mail nicht vorhanden >> erstelle User
		}else{

			// Code erstellen
			$J_USER["identcode"] = commons_create_identcode();

			// Neues Mitglied in user speichern - status ist "init", weil die Mail noch nicht bestätigt ist
			$r = mysql_query("insert into japps_user set 
														status = 'init',
														login ='".$_POST['do_login']."',
														email ='".$_POST['do_login']."',
														vorname ='".$_POST['do_login_name']."',
														inittime=".time().",
														identcode='".$J_USER["identcode"]."',
														identcode_time=".time()."
														");
			$err_text[]="| keine Mail vohanden ";
			$err_text[]= mysql_error();
			
			// User-ID-holen, um sie in die Session einzutragen
			$J_USER["id"]=mysql_insert_id();
			$J_USER["email"]=$_POST['do_login'];

		}
	
		// Save login in session
		$r = mysql_query("update japps_sessions set 
													user_id='".$J_USER["id"]."',
													status = 'INIT_MEMBER'
													where id='".$_COOKIE['sid']."'
													");

		$err_text[]= mysql_error();

		// Read session again
		$r_session = mysql_query("select * from japps_sessions where id='".$_COOKIE['sid']."'");

		// Sende Mail mit Identifikationslink
		if($message = commons_send_mail(
						"do_login",
						array("email" => $_POST["do_login"],
						"return_url" => "http://apps.jesus.de/gebet/",
//						"return_url" => "http://".$_SERVER['HTTP_HOST'].$_SERVER["SCRIPT_NAME"],
						)
						)){
			commons_display_message($message);
			$err_text[]="| Mail mit Link gesendet  ";
		}else{
			commons_display_message("Es ist was schief gelaufen","error");
			$err_text[]="| Mail mit Link NICHT gesendet  ";
		}
	

	}else{ // nicht valide?

		commons_display_message($_POST["do_login"]." ist eine ungÃ¼ltige E-Mail-Adresse.","error");
	
	}


}

// orignially from: http://stackoverflow.com/questions/4356289/php-random-string-generator 
function commons_generate_random_hash($length = 10) {
  $characters = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';  // 0 und O entfernt. I und l entfernt.
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}


function commons_create_identcode($salt = 'defaultsalt123'){
    return(md5($salt.$_SERVER['REMOTE_ADDR'].microtime().rand()));
}


function ajax_decode_formvars($var)
{
   return preg_replace('#%u([a-f0-9]{4,4})#ie', 'utf8_decode(\'&#x\\1;\')', $var);
}

// Diese Funktion nutzt alle vorangegangenen Funktionen, und wandelt somit den Rohtext in einen voll gefilterten Text
// mit Links, Smileys und Schriftformatierungen
function parse($text)
{
 $text = h($text);
 $text = preg_replace('/(\r|\n)/i', '', $text);
 $text = ajax_decode_formvars($text);
 return $text;
}


// GEBETE AUF BETER VERTEILEN
function allocate_prayers($user_id = NULL) {
    global $log, $fwlog;

	// Nimm alle Gebete jünger als 30 Tage, sortiert nach Anzahl der bestehenden Zuweisungen, wenigste zuerst (ergo auch solche mit Zuweisungszahl = 0, also ganz frische), zweitranging sortiert nach Alter, da ältere Anliegen, die noch nicht zugewiesen sind, dringender zugewiesen werden müssen. 
	// 14.01.2015: Anliegen der Woche werden nicht zugewiesen.
    
    
	// //////// --- Folgendes entfernt wegen zweitranginger Sortierung nach Alter /////////// (jdinse, 31.12.2012)
	// In das Scoring wird (nicht) noch das Alter des Gebetsanliegens mit einberechnet - frische Gebetsanliegen haben mehr Chancen.
	// 						(UNIX_TIMESTAMP() - japps_prayer.time)/86400/15
  // //////////////////////////////////////////////////////////////////////////////////////
  
  
	// WICHTIG: Autoren können bei UPDATES angeben, ob das Anliegen damit erledigt ist. Wenn ja, dann wird es nicht mehr neu verteilt. Auch: Angabe, ob veröffentlicht werden darf (oder auf Nachfrage bei Freischaltung).
	
	// Aufräumen: Wir löschen alle Activities älter als 40 Tage...
	db_query("delete from japps_activities where created_at < UNIX_TIMESTAMP()-(86400*40)"); 
	
	// Auf einen User beschränken?
	
	if($user_id)
		$q_user=" and user_id=".addslashes($user_id);
	else
		$q_user="";
	
	// Timestamp für neuestes Paket festlegen
	$last_package=time();
	$user_count=0;
	$prayer_count=0;
	$messages = array();
	
	// Wieviele aktive Gebetsanliegen hat jeder User?
	db_query('update japps_praying set japps_praying.active_prayers = (select count(*) from japps_prayer_alloc join japps_prayer on japps_prayer.id = japps_prayer_alloc.prayer_id where japps_prayer.expiration_time > UNIX_TIMESTAMP() and japps_praying.user_id = japps_prayer_alloc.user_id)');
	
	// Wieviele davon wurden heute schon zugewiesen?
	db_query('update japps_praying set japps_praying.delivered_today = (select count(*) from japps_prayer_alloc join japps_prayer on japps_prayer.id = japps_prayer_alloc.prayer_id where japps_prayer.expiration_time > UNIX_TIMESTAMP() and japps_praying.user_id = japps_prayer_alloc.user_id and japps_prayer_alloc.created_at > '.beginning_of_day().')');
	
	// Beter raussuchen, die gemäß ihrer Frequenz mal wieder dran sind
	// $r = db_query("select user_id,frequency,amount from japps_praying where last_package<UNIX_TIMESTAMP()-(frequency*86400)".$q_user);
	$prayings = Praying::find_all(array('include' => 'user', 'joins' => 'JOIN japps_user on japps_user.id = japps_praying.user_id', 'conditions' => 
	        "japps_user.status = 'active' AND 
	             (frequency != -1 AND last_delivery_date + (frequency*86400) + delivery_time < UNIX_TIMESTAMP() OR 
	              frequency = -1 AND japps_praying.active_prayers < japps_praying.max_active_amount AND japps_praying.delivered_today < ".MAX_TODAYS_AMOUNT.")"
	        .$q_user, 'limit' => 100));
		
	// Jeden Treffer durchgehen und darauf je nach Einstellung die Anliegen verteilen
	foreach ($prayings as $praying) {
	    if (!$praying->get('user')) {
	      $log->warning("No User with ID {$praying->get('user_id')} for Praying {$praying->get_id()}");
	      $fwlog->warning("No User with ID {$praying->get('user_id')} for Praying {$praying->get_id()}");
	      continue;
	    }


		$user_id = $praying->get('user_id');
		$user_count++;

		
		db_query("update japps_prayer_alloc set status='not seen' where status='none' and user_id=".$user_id); // Alle alten als nicht gesehen markieren
		
		if ($praying->get('frequency') == -1)
		  $amount_to_get = ($praying->get('max_active_amount') - $praying->get('active_prayers')) > MAX_TODAYS_AMOUNT ? MAX_TODAYS_AMOUNT : $praying->get('max_active_amount') - $praying->get('active_prayers');
		else
		  $amount_to_get = $praying->get('amount');
		
		// Gebetsanliegen zum Beten für diesen Beter holen. Anliegen dürfen nicht abgelaufen (expiration_time). Keine Gebetsanligen vom eigenen User! Kein Gebetsanliegen, dass dem User bereits zugewiesen ist (japps_prayer_alloc.user_id is null)
		//    keine Anliegen der Woche
		$r2 = db_query("select japps_prayer.*, stats.hits as hits
							from japps_prayer LEFT JOIN (select * from japps_prayer_alloc where user_id = ".$user_id.") as user_allocs ON japps_prayer.id = user_allocs.prayer_id
		                    left join (select prayer_id, count(*) as hits from japps_prayer_alloc group by prayer_id) as stats on japps_prayer.id = stats.prayer_id
		          where japps_prayer.status='ok' AND japps_prayer.is_adw = 0 AND japps_prayer.expiration_time > UNIX_TIMESTAMP() and japps_prayer.user_id != ".$user_id." and user_allocs.user_id is null
		          order by hits ASC, japps_prayer.time DESC limit ".$amount_to_get); // aus order by entfernt: japps_prayer.time ASC 
		$prayer_allocs = array();
		
		$prayers_for_user_cnt = mysql_num_rows($r2);
		
		if ($prayers_for_user_cnt) {
		    
    		while($data2 = mysql_fetch_array($r2)){
    		    $pa = PrayerAlloc::new_instance(array('user_id' => $user_id, 'prayer_id' => $data2["id"], 'created_at' => $last_package));
    			$pa->save(true);
    			array_push($prayer_allocs, $pa);
    			$prayer_count++;
    		}
    		
    		$step_time_range = $praying->get('frequency') * ONE_DAY;
    		
    		$steps = floor(($last_package - $praying->get('last_delivery_date')) / $step_time_range);
    
 // BUGGY! RK, 9.10.13,10:55  		db_query("update japps_praying set last_package=".$last_package.", last_delivery_date = ".beginning_of_day(($praying->get('last_delivery_date')) + $steps * ONE_DAY)." where user_id=".addslashes($user_id));
    		db_query("update japps_praying set last_package=".$last_package.", last_delivery_date = ".beginning_of_day($last_package).", delivered_today = delivered_today + $prayers_for_user_cnt where user_id=".addslashes($user_id));
    		
    		send_mail('neuer_gebetsplan', $praying->get('user')->get('email'), array('user' => $praying->get('user'), 'prayer_allocs' => $prayer_allocs, 'packet_time' => $last_package));
		}
	}
	
	
  return '['.date('d.m.Y H:i', $last_package)." / $last_package] $user_count Beter frei, $prayer_count Anliegen verteilt";
}


function last_viewed(Prayer $prayer, $prayer_alloc = NULL, $view_feedbacks = false, $limited = true) {
  global $current_user, $log;
  $log->debug("last_viewed(prayer: {$prayer->get_id()}, prayer_alloc: ".($prayer_alloc ? $prayer_alloc->get_id() : 'nichts').', view_feedbacks: '.($view_feedbacks ? 'true' : 'false').')');
  $is_author = is_logged_in() && $prayer->get('user_id') == $current_user->get_id();  // Ist der aktuelle Nutzer auch Autor dieses Feedbacks?
  
  
  if ($is_author) {  // Ist der aktuelle Nutzer Autor des Anliegens?  Der Aufruf erfolgte über 'meine_anliegen' (oder andere Ansicht als eigloggter Nutzer) oder als anonymer user_über edit_hash:
    $last_viewed = $view_feedbacks ? $prayer->get('last_viewed_new_feedbacks_by_author') : $prayer->get('last_viewed_by_author');
    $log->debug("last_viewed (Autor): $last_viewed (".date('d.m.Y H:i', $last_viewed).')');
    
  }
  elseif (is_logged_in()) { // Angemeldeter Nutzer. Nur ggf. als neu / berarbeitet markieren, wenn die Ermutigung nicht von einem selbst stammt.
    if ($prayer_alloc) {  // Aufruf über Gebetsplan bzw. Einzelansicht mit Referenz zum Gebetsplan
      $last_viewed = $view_feedbacks ? $prayer_alloc->get('last_viewed_new_feedbacks') : $prayer_alloc->get('last_viewed');
      $log->debug("last_viewed (Eingeloggt, Beter): $last_viewed (".date('d.m.Y H:i', $last_viewed).')');
    }
    else {
      $last_viewed = $current_user->get('previous_login');  // Ohne Referenz zum Gebetsplan (sollte eigentlich selten / nicht auftreten): Ermutigung ist neu / bearbeitet seit dem letzten Login?
      $log->debug("last_viewed (Eingeloggt, kein Beter): $last_viewed (".date('d.m.Y H:i', $last_viewed).')');
    }
  }
  else
    $last_viewed = NULL;    // In allen übrigen Fällen (aktuller Nutzer ist Verfasser der Ermutigung || aktueller Nutzer ist nicht eingeloggt) ist die aktuelles-Meldung nicht interessant.
  
  
  // Obergrenze definieren: Anliegen / Feedbacks sollen nach spätestens 30 Tagen nicht mehr als "Neu" markiert werden. Daher last-viewed auf 30 Tage setzen, wenn älter.
  if ($limited && $last_viewed < time() - THIRTY_DAYS)
    $last_viewed = time() - THIRTY_DAYS;
  
  
  return $last_viewed;
}



function response_with($content = null, $status_code = 200, $message = null, $additional_params = array()) {
	if ($message === null) {
		if (is_string($content))
			$message = $content;
		elseif ($status_code == 200)
			$message = 'ok';
	}
	
	if ($additional_params === null) $additional_params = array();
		
	
	$o = array_merge(array('status' => $status_code, 'error' => ($status_code >= 400 ? $message : null), 'message' => $message), $additional_params);
	if ($content) {
		$o['content'] = $content;
	}
	if (is_flash())
		$o['flash'] = pop_flash();
	
	return $o;
}

function render_json_response($data = null, $status_code = 200, $message = null, array $options = array()) {
	return render_json(response_with($data, $status_code, $message, $options['additional_params']), ($status_code >= 300 && $status_code < 400 ? 501 : $status_code), $options);
}


?>