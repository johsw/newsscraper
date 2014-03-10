<?php

Class Logger {
  
  public $feeds;
  private $saver;
  

  public static function log($message) {
    print "LOG: " . $message;
  }
}

