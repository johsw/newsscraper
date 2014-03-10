<?php


function articleFetched($url) {
  global $pdo;
  $sql = "SELECT url FROM articles WHERE url = ". $pdo->quote($url);
  $query = doQuery($sql);
  $row = $query->fetch();
  return (bool)$row;
}

function insertArticleData($url, $title, $tagger) {

  if (empty($url)) {
    return;
  }

  global $pdo;
  $sql = "INSERT INTO articles SET url = ". $pdo->quote($url). ", title = ". $pdo->quote(utf8_decode($title));
  $query = doQuery($sql);
  $aid = $pdo->lastInsertId();

  if (!empty($tagger)) {
    foreach ($tagger as $vocab => $terms) {
      foreach ($terms as $tid => $term) {
          $sql = "
            INSERT INTO tags SET
            aid = ". $aid. ",
            tid = ". $tid. ",
            vocab = ". $pdo->quote(utf8_decode($vocab)). ",
            name = ". $pdo->quote(utf8_decode($term->name)). ",
            rating = ". $term->rating. ";";
          $query = doQuery($sql);
      }
    }
  }
}