<?php

Class Parser {
  
  public function parseArticle($article) {
    $url = $article['feed_link'];
    $conf  = $GLOBALS['newsscanner_config'];
    $readability_url = 'https://www.readability.com/api/content/v1/parser';
    $call = $readability_url . '?url=' . urlencode($url) . '&token=' . $conf['readability_api_key'];
    $json =  Fetcher::fetch($call);

    if (empty($json)) {
      Logger::log('Empty parse-response: ' . $call, E_USER_WARNING);
      return FALSE;
    }
    $parsed_article = json_decode($json);
    if (!empty($parsed_article)) {
      $article['parsed_body'] = strip_tags($parsed_article->content);
      $article['parsed_title'] = $parsed_article->title;
      $article['parsed_lead_image_url'] = $parsed_article->lead_image_url;
      $article['status'] = 'parsed-article';
    }
    return $article;
  }
}

