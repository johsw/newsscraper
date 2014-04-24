<?php

Class Logger {
  
  public $feeds;
  private $saver;
  

  public static function log($message, $type, $print = FALSE) {
    if ($print) {
      print $message;
    }
    trigger_error($message, $type);
  }
}

