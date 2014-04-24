<?php

Class Parser {
  
  public function __construct($di) {
    if (!isset($di['logger'])) {
      print "No logger injected";
      exit;
    }
    $this->logger = $di['logger'];
  }
  
  public function parseArticle($article) {
    $url = $article['feed_link'];
    $conf  = $GLOBALS['newsscanner_config'];
    $readability_url = 'https://www.readability.com/api/content/v1/parser';
    $call = $readability_url . '?url=' . $url . '&token=' . $conf['readability_api_key'];
    $json =  @file_get_contents($call);

    if (empty($json)) {
      $this->logger->log('Empty parse-response: ' . $call, E_USER_WARNING);
      return FALSE;
    }
    $parsed_article = json_decode($json);
    if (!empty($parsed_article)) {
      $article['parsed_body'] = $parsed_article->content;
      $article['parsed_title'] = $parsed_article->title;
      $article['parsed_lead_image_url'] = $parsed_article->lead_image_url;
      $article['status'] = 'parsed-article';
    }
    return $article;
  }
}

