<?php

$use_smtp_mailer = !empty($site_config['smtp']['host']);
if ($use_smtp_mailer && !class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
  throw new Exception('Cannot send E-Mail with SMTP: Class PHPMailer is missing. Please install it with "composer require phpmailer/phpmailer"');
}

function include_mail_body($filename, $data) {
  global $c_base_url;
  $data['base_url'] = $c_base_url;
  extract($data);
  ob_start();
  require APP_DIR.'views/mailer/'.$filename;
  return ob_get_clean();
}

$generate_subject = function ($mail_name, $to, $data) {
  $sitename = sitename();
  $subject = '';
  switch ($mail_name) {
    case ('reset_code'):
      $subject = "Passwort zuruecksetzen fuer $sitename";
      break;
    case ('forgot_success'):
      $subject = "Passwort zurueckgesetzt fuer $sitename";
      break;
    default:
      throw new ErrorException("No Mailing-Options defined for '$mail_name'");
  }
  return $subject;
};

$after_send_mail = function ($mail_name, $to, $data, $res) {
};

function send_mail($mail_name, $to, $data = [], $subject = '') {
  global $log, $site_config, $generate_subject, $after_send_mail, $use_smtp_mailer;
  // Hier Zugriff auf die übergebenen Objekte nur mittels $data[objektname]

  //$mailconfig=Config::find_first();
  if ($subject == '') {
    $subject = $generate_subject($mail_name, $to, $data);
  }

  // Push-Nachrichten sind jetzt ggf. gesendet worden.
  // Wenn E-Mail-Adresse vorhanden, dann jetzt auch eine E-Mail senden, sonst nichts machen.
  if (!$to) {
    return;
  }

  try {
    $text = include_mail_body($mail_name.'.php', $data);

    if ($use_smtp_mailer) {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

      try {
        //Server settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->SMTPDebug = $site_config['smtp']['debug'] ? \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER : \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = $site_config['smtp']['host'];                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = $site_config['smtp']['username'];                     //SMTP username
        $mail->Password   = $site_config['smtp']['password'];                               //SMTP password
        $mail->SMTPSecure = $site_config['smtp']['port'] == 587 ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = $site_config['smtp']['port'];                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom($site_config['noreply_email'], $site_config['sitename']);
        $mail->addAddress($to);

          //Content
        $mail->isHTML(false);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $text;

        $mail->send();
        $res = true;
      } catch (Exception $e) {
        $res = false;
      }
    } else {
      mb_language('en');
      /* if (($environment == 'beta' || $environment == 'production') && $mail_name != 'neuer_gebetsplan')
      mb_send_mail("beta@$sitename", iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $subject), iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), "From: info@$sitename\nContent-Type: text/plain;charset=iso-8859-1");
    */
      // Achtung: Wenn mb_internal_encoding('UTF-8') ist, dann funktioniert das Senden von E-Mails mit mb_send_mail() mit ISO-8859-1 nicht!
      // --> Solange ISO-8859-1 E-Mails vesendet werden, nur mail() verwenden statt mb_send_mail()
      $res = mb_send_mail($to,  $subject, $text, 'Return-Path: '.$site_config['contact_email']."\nFrom: \"".$site_config['sitename'].'" <'.$site_config['noreply_email'].">\nContent-Type: text/html; charset=\"utf-8\"");
    }
    $after_send_mail($mail_name, $to, $data, $res);

    $log->info('
====================================
Email (via '.($use_smtp_mailer ? 'SMTP' : 'Sendmail').") an $to ".($res ? 'erfolgreich' : 'fehlgeschlagen').", Betreff: $subject
".($use_smtp_mailer && !$res ? "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n" : '')."------------------------------------
$text
====================================");
  } catch (Exception $e) {
    $log->warning("Could not send mail $mail_name to $to: Exception in {$e->getFile()}, Line {$e->getLine()}: ".$e->getMessage());
  }

  return $res;
}
