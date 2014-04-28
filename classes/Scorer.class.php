<?php

Class Scorer {
  
  public function __construct($di) {
    if (!isset($di['logger'])) {
      print "No logger injected";
      exit;
    }
    $this->logger = $di['logger'];
  }
  
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
      $this->logger->log('Empty score-response: ' . $call, E_USER_WARNING);
      return FALSE;
    }
    $scores = json_decode($json);
    $article['scores'] = $scores;
    return $article;

  }
}

