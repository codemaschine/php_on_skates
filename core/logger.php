<?php

class Logger {
  
  private $fileName;
  private $outputLevel;
  private $printLevel;
  
  function __construct($fileName = "log.txt", $outputLevel = 0, $printLevel = true) {
    $this->fileName = $fileName;
    $this->outputLevel = $outputLevel;
    $this->printLevel = $printLevel;
  }
  
  public function setLevel($level) {
    $this->outputLevel = $level;
  }
  public function debug($message) {
    $this->log(0,$message);
  }
  public function info($message) {
    $this->log(1,$message);
  }
  public function warning($message) {
    $this->log(2,$message);
  }
  public function error($message) {
    $this->log(3,$message);
  }
  
  // ----
  
  private function log($level, $message = "") {
    global $_FRAMEWORK;

    if ($level < $this->outputLevel)
      return;
    $msg = '';
      
    if ($this->printLevel) {
      $msg .= '[';
      switch ($level) {
        case 0: $msg .= 'DEBUG'; break;
        case 1: $msg .= 'INFO'; break;
        case 2: $msg .= 'WARNING'; break;
        case 3: $msg .= 'ERROR'; break;
        default: $msg .= 'INFO'; break;
      }
      $msg .= '] ';
    }

    if (!is_string($message)) $message = var_inspect($message);

    if (empty($_FRAMEWORK['docker'])) {
      $msg .= $message."\r\n";

      if ($fp = fopen($this->fileName, "a+")) {
        flock($fp, LOCK_EX);
        fputs($fp, $msg);
        flock($fp, LOCK_UN);
        fclose($fp);
      } else {
        echo 'Logger Fehler: Keine Schreibrechte auf die Log-Datei '.$this->fileName.'!'.getcwd();
      }
    } else {
      $logger_type = $this->fileName == ROOT_DIR.'log/fwlog.txt' ? 'FRAMEWORK' : 'APP';
      $msg = "[$logger_type]".$msg.$message;
      // Check if user is root - only root can write to /proc
      if (exec('whoami') == 'root') {
        $msg = preg_replace('/\\\\\'/', '\'', addslashes($msg));
        $fd = $level >= 3 ? '2' : '1';
        exec('echo "'.$msg.'" >> /proc/1/fd/'.$fd);
      } else {
        // user != root - write logs to stdout and stderr
        try {
          if ($level >= 3) {
            $std = fopen('php://stderr', 'w');
          } else {
            $std = fopen('php://stdout', 'w');
          }
          fwrite($std, $msg);
          fclose($std);
        } catch (\Throwable $th) {
          // Ignore error
        }
      }
    }
  }

}
