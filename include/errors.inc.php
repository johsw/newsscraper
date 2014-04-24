<?php

error_reporting(E_ALL); 
ini_set('log_errors','1'); 
ini_set('display_errors','0');

$conf = $GLOBALS['newsscanner_config'];
if (isset($conf['custom_error_logging']) && $conf['custom_error_logging']) {
  if (isset($conf['log_location'])) {
    ini_set('error_log', $conf['log_location']);
  }
}