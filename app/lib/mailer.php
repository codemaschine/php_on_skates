<?php

function include_mail_body($filename, $data) {
  global $c_base_url, $log;
  //$log->debug('mail data: '.var_export($data,true));


  
  $data['base_url'] = $c_base_url;
  
  extract($data);
  $log->debug('mail vorname: '.var_export($vorname,true));
    
  ob_start();
  require 'views/mailer/'.$filename;
  return ob_get_clean(); 
}


function send_mail($mail_name,$to, $data = array()){
  global $c_base_url, $log, $current_user;
  // Hier Zugriff auf die übergebenen Objekte nur mittels $data[objektname] 
  
  	$sitename= sitename();
  
	switch($mail_name){
	  case("admin_add_prayer_confirmation"):
        $subject = "Aktivierungslink für $sitename";
        break;
      case("beter_bestaetigung"):
        $subject = "Aktivierungslink für $sitename";
			break;
	  case("email_reconfirmation"):
  	    $subject = "Bitte bestätige deine neue E-Mail-Adresse";
  	        break;
  	  case("feedback_decline"):
        $subject = "Dein ".($data['feedback']->get('is_author_update') ? 'Update' : 'Feedback')." bei $sitename konnte leider noch nicht freigeschaltet werden";
        Device::notify_user($data['feedback'], $subject);
        break;
      case("gebetsanliegen_bestaetigung"):
  	    $subject = "Bestätige dein Anliegen bei $sitename: \"".truncate($data['prayer']->get('prayer'), 30)."\"";
  	        break;
      case("gebetsanliegen_bestaetigt"):
          $subject = "Dein Anliegen bei $sitename: \"".truncate($data['prayer']->get('prayer'), 30)."\"";
  	        break;
      case("gebetsanliegen_eingereicht"):
  	  $subject = "Dein Anliegen bei $sitename: \"".truncate($data['prayer']->get('prayer'), 30)."\"";
  	        break;
      case("neuer_gebetsplan"):
	  		$namen=array(); foreach($data["prayer_allocs"] as $prayer_alloc){
				if ($prayer_alloc->get('prayer'))
					$namen[] = $prayer_alloc->get('prayer')->get('name');
				
			} 
			
			$namen_string = implode(", ", array_unique($namen));
			if(count($namen)>1)
				$namen_string = substr_replace ($namen_string," und",strrpos ($namen_string, ","), 1);
			$subject = (count($data["prayer_allocs"]) ==1 ? "$namen_string bittet um dein Gebet!" : "$namen_string bitten um dein Gebet!");
			break;  
	  case("password_forgotten"):
      $subject = "Link für neues $sitename-Passwort";
			break;
	  case("prayer_approved"):
        $subject = "Dein Anliegen wurde freigeschaltet: \"".truncate($data['prayer']->get('prayer'), 30)."\"";
        Device::notify_user($data['prayer'], $subject);
        break;
      case("prayer_deadline_expired_update_request"):
        $subject = "Gestern war der große Tag!";
        Device::notify_user($data['prayer'], $subject." Hast du ein Update für deine Beter?");
        break;
      case("prayer_declined"):
        $subject = "Dein Anliegen bei $sitename konnte leider noch nicht freigeschaltet werden: \"".truncate($data['prayer']->get('prayer'), 30)."\"";
        Device::notify_user($data['prayer'], $subject);
        break;
      case("prayer_expired_update_request"):
        $subject = "Dein Anliegen ist ausgelaufen: \"".truncate($data['prayer']->get('prayer'), 30)."\"";
        Device::notify_user($data['prayer'], $subject);
        break;
      case("prayer_feedback_news"):
        $subject = (count($data['new_feedbacks']) == 1 ? '1 kleine Ermutigung' : count($data['new_feedbacks']).' kleine Ermutigungen')." zu \"".truncate($data['prayer']->get('prayer'), 30)."\"";
        Device::notify_user($data['prayer'], $subject);
        break;
      case("prayer_simple_feedback_news"):
        $subject = ($data['prayer']->get('simple_feedback_count') == 1 ? 'Jemand betet' : $data['prayer']->get('simple_feedback_count').' Menschen beten').' für dein Anliegen "'.truncate($data['prayer']->get('prayer'), 30)."\"";
        Device::notify_user($data['prayer'], $subject);
        break;
      case("prayer_update_request"):
        $subject = "Hat Gott etwas getan?";
        Device::notify_user($data['prayer'], $subject." Dann schreibe doch ein Update an deine Beter!");
        break;
      case("prayer_update_notification"): // Variablen in data: prayer, prayer_alloc, feedback, user 
        $subject = "Update zum Anliegen von ".$data['prayer']->get('name');
        Device::notify_user($data['user'], $subject, $data['prayer']);
        break;
      case("prayer_note_created_by_user"):  // braucht: $admin, $note, $prayer // nur an Admins
        $subject = "Antwort von ".$data['prayer']->get('name')." auf die {$site_config['sitename']}-Team Nachricht";
        break;
      case("prayer_note_created_by_admin_to_user"):   // braucht: $note, $prayer
        $subject = "Nachricht des ".$sitename."-Teams auf dein Anliegen"; // ".(is_logged_in() && $current_user->get_id() != $data['prayer']->get('user_id') ? $data['prayer']->get('name').'s' : 'dein').' Anliegen';
        break;
      case("prayer_note_created_by_admin_to_admins"):  // braucht: $admin, $note, $prayer
        $subject = "ADMIN-Info: {$current_user->get('vorname')} hat zum Anliegen von {$data['prayer']->get('name')} eine Nachricht geschrieben";
        break;
      case("prayer_to_adw"):  // braucht: $user, $prayer
        $subject = "Dein Anliegen wird zum Anliegen der Woche";
        Device::notify_user($data['prayer'], $subject);
        break;
      case("send_identcode"):
        $subject = "Login für $sitename";
  			break;
  		case("user_aktiviert"):
      $subject = "Willkommen auf $sitename!";
			break;
  	  case("user_bestaetigung"):
      $subject = "Link zu deinem $sitename-Konto";
			break;
	   
    	case("release_mail"):
    	  $subject = "Dein Zugang zu $sitename ist freigeschaltet";
    	  break;
    	case("erf_benachrichtigung"):
    	  $subject = "[Das Gebet] Neues Gebetsanliegen von ".$data['prayer']->get('name')." via ";
    	  $subject .= $data['prayer']->get('is_erf_popup') ? "erf.de" :"$sitename";
    	  break;
    	
  	  case("notify_counselor"):
  	    $subject = truncate($data['prayer']->get('name'), 30)." bedarf Seelsorge (Anliegen ".date('d.m.Y H:i', $data['prayer']->get('time')).")";
  	    break;
	    case("notify_counselor_reminder"):
	      $subject = "Kleine Erinnerung: ".truncate($data['prayer']->get('name'), 30)." bedarf Seelsorge (Anliegen ".date('d.m.Y H:i', $data['prayer']->get('time')).")";
	      break;
      case("notify_assigned_counselor_reminder"):
        $subject = "Du hast die Seelsorge für ".truncate($data['prayer']->get('name'), 30)." übernommen, aber noch nichts gechrieben.";
        break;
         
    	  
			

		default:
			throw new ErrorException("No Mailing-Options defined for '$mail_name'");
	}
	
	// Push-Nachrichten sind jetzt ggf. gesendet worden.
	// Wenn E-Mail-Adresse vorhanden, dann jetzt auch eine E-Mail senden, sonst nichts machen.
	if (!$to)
	  return ;
	
	try {
	  $text = include_mail_body($mail_name.'.php', $data);
	
	
	  mb_language('en');
  
    // debug for test purposes
    global $environment;
   /* if (($environment == 'beta' || $environment == 'production') && $mail_name != 'neuer_gebetsplan')
     mb_send_mail("beta@$sitename", iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $subject), iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), "From: info@$sitename\nContent-Type: text/plain;charset=iso-8859-1");
	 */
   // Achtung: Wenn mb_internal_encoding('UTF-8') ist, dann funktioniert das Senden von E-Mails mit mb_send_mail() mit ISO-8859-1 nicht!
   // --> Solange ISO-8859-1 E-Mails vesendet werden, nur mail() verwenden statt mb_send_mail()
    $res = mail($to, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $subject), iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), "Return-Path: bounces@".$sitename."\nFrom: \"".$sitename."\" <info@".$sitename.">\nContent-Type: text/plain;charset=iso-8859-1");
    
    Activity::create(array('action' => 'send_mail'.($res ? '' : ' failed').': '.$mail_name, 'user_name' => $to ));
    $log->info("
====================================
Email an $to ".($res ? 'erfolgreich' : 'fehlgeschlagen').", Betreff: $subject
------------------------------------
$text
====================================");
	}
	catch (Exception $e) {
	  $log->warning("Could not send mail $mail_name to $to: Exception in {$e->getFile()}, Line {$e->getLine()}: ".$e->getMessage());
	}
    
	return $res;
}


