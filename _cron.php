<?php
set_time_limit(0);

$pdo = new PDO ("mysql:host=localhost;dbname=newsscraper","root","root");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



include './include/db.inc.php';
include './include/utils.inc.php';

$readbility_token = 'a0cd2303be9f69868365ed2d1fd1f0d4c7328c0b';
$readbility_url   = 'https://www.readability.com/api/content/v1/parser?token='. $readbility_token .'&url=';
$tagger_url = 'http://api.tagger.dk/v1/tag?n_vocab_ids=13,15,17&k_vocab_ids=16&linked_data=true&text=';


$opts = array('http' =>
  array(
    'method'  => 'POST',
  )
);                        
$post_context  = stream_context_create($opts);


$counter = 0;

//$sql = "SELECT * FROM feeds ORDER BY RAND();";
$sql = "SELECT * FROM feeds";
$query = doQuery($sql);

while ($row = $query->fetch()) {
  $url = $row['url'];
  $contents = @file_get_contents($url);
  if ($contents) {
    $xml = simplexml_load_string($contents);
    foreach ($xml->channel->item AS $item) {
      if (!empty($item->link) && !articleFetched((string)trim($item->link))) {
        $contents = file_get_contents($readbility_url . $item->link);
        $article = json_decode($contents);
        $markedup = '<h1>' . $article->title . '</h1>'. $article->content;
        //Shortening to prevent 414
        $tagger = json_decode(file_get_contents(substr($tagger_url . urlencode($markedup), 0, 4096)));
        //$tagger = file_get_contents($tagger_url . urlencode($markedup), false, $post_context);
        
        insertArticleData(trim($item->link), trim($item->title), $tagger);
        $counter++;
      }
    }
  }
}
