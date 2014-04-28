<?php

Class Scorer {
  public function scoreArticle($article) {
    $url = $article['feed_link'];
    if (!isset($article['feed_link']) || empty($article['feed_link'])) {
      print_r($article);
    }
    $conf  = $GLOBALS['newsscanner_config'];
    $scorer_url = 'http://free.sharedcount.com/';
    $call = $scorer_url . '?apikey=' . $conf['sharedcount_api_key'] . '&url=' . urlencode($url);
    $json =  Fetcher::fetch($call);

    if (empty($json)) {
      Logger::log('Empty score-response: ' . $call, E_USER_WARNING);
      return FALSE;
    }
    $scores = json_decode($json);
    $article['scores'] = $scores;
    return $article;

  }
}

