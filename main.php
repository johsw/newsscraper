<?php

include '../configuration.php';
include './include/bootstrap.inc.php';

$saver  = new Saver();
switch ($argv[1]) {

  case 'parser':
    $feeder = new Parser($saver);
    $feeder->saveParsedArticles();
    break;

  default:
    $feeder = new Feeder($saver);
    $feeder->saveFeedData();
}


