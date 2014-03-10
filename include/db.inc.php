<?php


function doQuery($sql) {
  global $pdo;
  $error = $pdo->errorInfo();
  $query = $pdo->prepare($sql);
  $query->execute();
  return $query;
}