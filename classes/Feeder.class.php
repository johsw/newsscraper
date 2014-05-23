<?php

Class Feeder {

  public $feeds;
  private $saver;
  private $parser;
  private $scorer;

  public function __construct($di) {

    if (!isset($di['saver'])) {
      Logger::log('No saver injected', E_USER_ERROR, TRUE);
    }
    $this->saver = $di['saver'];

    if (!isset($di['parser'])) {
      Logger::log('No parser injected', E_USER_ERROR, TRUE);
    }
    $this->parser = $di['parser'];

    if (!isset($di['scorer'])) {
      Logger::log('No scorer injected', E_USER_ERROR, TRUE);
    }
    
    $this->scorer = $di['scorer'];

    $handle = fopen("data/feeds.txt", "r");
    if (!$handle) {
      Logger::log('No valid feeds-list file', E_USER_ERROR, TRUE);
    }
    $feeds = array();
    if ($handle) {
      while (($url = fgets($handle)) !== false) {
        $feeds[] = $url;
      }
    }
    if (!empty($feeds)) {
      $this->feeds = $feeds;
    }
  }
  public function processFeeds() {
    $feed_count = 0;
    $check_count = 0;
    $created_count = 0;
    foreach ($this->feeds AS $feed_url) {
      $feed_count++;
      $contents = Fetcher::fetch($feed_url);
      if (!$contents || empty($contents)) {
        Logger::log('Non-valid or empty feed: ' . $feed_url, E_USER_WARNING);
      }
      if ($contents) {
        $xml = simplexml_load_string($contents);
        if (!$xml) {
          Logger::log('Non-valid feed: ' . $feed_url, E_USER_WARNING);
        }
        foreach ($xml->channel->item AS $feed_item) {
          $check_count++;
          $feed_item = $this->prepareFeedItemFile($feed_item);
          if (!isset($feed_item['feed_link']) || empty($feed_item['feed_link'])) {
            Logger::log('Non-valid link in: ' . print_r($feed_item, 1), E_USER_WARNING);
          }
          if (!$this->saver->isArticleIndexed($feed_item['feed_link']) && !$this->saver->isArticleArchived($feed_item['feed_link'])) {
            $article = $this->prepareArticle($feed_item);
            $article = $this->parser->parseArticle($article);
            $article = $this->scorer->scoreArticle($article);
            if ($this->saver->createArticle($article)) {
              $created_count++;
            }
          }
        }
      }
    }
    Logger::log("Created $created_count feed-items. $check_count items checked. $feed_count feeds.", E_USER_NOTICE);
  }
  private function getFeedContents($url) {
    //$user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17';
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($curl_handle, CURLOPT_USERAGENT, $user_agent);
    $query = curl_exec($curl_handle);
    $info = curl_getinfo($curl_handle);
    curl_close($curl_handle);
    if ($info['http_code'] != '200' || empty($query)) {
      Logger::log('Non-valid feed: ' . $url, E_USER_WARNING);
      return FALSE;
    }
    return $query;
  }
  private function prepareArticle($feed_item) {
    $feed_item['pubDate_parsed'] = strtotime($feed_item['feed_pubDate']);
    $url_parts = parse_url($feed_item['feed_link']);
    if (isset($url_parts['host'])) {
      $feed_item['domain'] = $url_parts['host'];
    }
    $feed_item['updated'] = time();
    $feed_item['status'] = 'feed_item';
    return $feed_item;
  }
  private function prepareFeedItemFile($feed_item) {
    if (!is_array($feed_item)) {
      $feed_item = (array) $feed_item;
    }
    foreach ($feed_item AS $key => $value) {
      if (is_object($value)) {
        $feed_item[$key] = (string) $value;
      }
    }
    $url_parts = parse_url($feed_item['link']);
    if (isset($url_parts['query']) && !empty($url_parts['query'])) {
      if (
        stripos($url_parts['query'], 'rss') !== FALSE ||
        stripos($url_parts['query'], 'utm_') !== FALSE ||
        stripos($url_parts['query'], 'feed') !== FALSE
      ) {
        unset($url_parts['query']);
      }
    }
    $feed_item['link'] = $this->buildUrl($url_parts);
    if (empty($feed_item['link'])) {
      print_r($feed_item); exit;
    }
    if (isset($feed_item['description']) && gettype($feed_item['description']) == 'object') {
      $feed_item['description'] = $feed_item['description']->__toString();
    }
    if (isset($feed_item['pubDate']) && !empty($feed_item['pubDate'])) {
      $from = array('man', 'tir', 'ons', 'tor', 'fre', 'lør', 'søn');
      $to = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
      $feed_item['pubDate'] = str_ireplace($from, $to, $feed_item['pubDate']);

    }
    $article = array(
      'feed_link' => isset($feed_item['link']) ? $feed_item['link'] : '',
      'feed_title' => isset($feed_item['title']) ? $feed_item['title'] : '',
      'feed_description' => isset($feed_item['description']) ? strip_tags(nl2br($feed_item['description'])) : '',
      'feed_pubDate' => isset($feed_item['pubDate']) ? $feed_item['pubDate'] : '',
    );

    return $article;
  }
  private function buildUrl($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
  }
}

