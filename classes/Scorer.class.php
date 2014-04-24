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
    $scorer_url = 'http://api.sharedcount.com/';
    $call = $scorer_url . '?url=' . $url;
    $json =  @file_get_contents($call);

    if (empty($json)) {
      $this->logger->log('Empty score-response: ' . $call, E_USER_WARNING);
      return FALSE;
    }
    $scores = json_decode($json);
    $article['scores'] = $scores;
    return $article;

  }
}

