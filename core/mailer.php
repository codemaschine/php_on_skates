<?php

function include_mail_body($filename, $data) {
  global $c_base_url;
  $data['base_url'] = $c_base_url;
  extract($data);
  ob_start();
  require 'views/mailer/'.$filename;
  return ob_get_clean();
}

$generate_subject = function ($mail_name, $to, $data) {
  $sitename = sitename();
  $subject = '';
  switch ($mail_name) {
    case ("reset_code"):
      $subject = "Passwort zuruecksetzen fuer $sitename";
      break;
    case ("forgot_success"):
      $subject = "Passwort zurueckgesetzt fuer $sitename";
      break;
    default:
      throw new ErrorException("No Mailing-Options defined for '$mail_name'");
  }
  return $subject;
};

$after_send_mail = function ($mail_name, $to, $data, $res) {

};


function send_mail($mail_name, $to, $data = array(), $subject = "") {
  global $log, $site_config, $generate_subject, $after_send_mail;
  // Hier Zugriff auf die übergebenen Objekte nur mittels $data[objektname]

  //$mailconfig=Config::find_first();
  if ($subject == "") {
    $subject = $generate_subject($mail_name, $to, $data);
  }

  // Push-Nachrichten sind jetzt ggf. gesendet worden.
  // Wenn E-Mail-Adresse vorhanden, dann jetzt auch eine E-Mail senden, sonst nichts machen.
  if (!$to)
    return;

  try {
    $text = include_mail_body($mail_name.'.php', $data);

    mb_language('en');
    /* if (($environment == 'beta' || $environment == 'production') && $mail_name != 'neuer_gebetsplan')
     mb_send_mail("beta@$sitename", iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $subject), iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), "From: info@$sitename\nContent-Type: text/plain;charset=iso-8859-1");
   */
    // Achtung: Wenn mb_internal_encoding('UTF-8') ist, dann funktioniert das Senden von E-Mails mit mb_send_mail() mit ISO-8859-1 nicht!
    // --> Solange ISO-8859-1 E-Mails vesendet werden, nur mail() verwenden statt mb_send_mail()
    $res = mail($to, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $subject), iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), "Return-Path: ".$site_config['contact_email']."\nFrom: \"".$site_config['sitename']."\" <".$site_config['noreply_email'].">\nContent-Type: text/html;charset=iso-8859-1");

    $after_send_mail($mail_name, $to, $data, $res);
    $log->info("
====================================
Email an $to ".($res ? 'erfolgreich' : 'fehlgeschlagen').", Betreff: $subject
------------------------------------
$text
====================================");
  } catch (Exception $e) {
    $log->warning("Could not send mail $mail_name to $to: Exception in {$e->getFile()}, Line {$e->getLine()}: " . $e->getMessage());
  }

  return $res;
}
