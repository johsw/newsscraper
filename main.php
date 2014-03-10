<?php

include '../configuration.php';
include './include/bootstrap.inc.php';

$saver  = new Saver();  
$feeder = new Feeder($saver);

$feeder->saveFeedData();


