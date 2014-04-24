<?php

date_default_timezone_set('Europe/Copenhagen');

include '../configuration.php';
include './include/errors.inc.php';
include './include/bootstrap.inc.php';

$saver  = new Saver();
$logger = new Logger();

$di = array(
  'saver' => $saver,
  'logger' => $logger
);

$process = isset($argv[1]) ? $argv[1] : '';

switch ($process) {

  case 'rescore':
    $scorer  = new Scorer();
    $scorer->rescoreArticles();
    break;

  case 'archive':
    $saver->archiveOutdatedContent();
    break;


  default:
    $parser = new Parser($di);
    $scorer = new Scorer($di);
    
    $di['parser'] = $parser;
    $di['scorer'] = $scorer;
    $feeder = new Feeder($di);
    $feeder->processFeeds();
}


