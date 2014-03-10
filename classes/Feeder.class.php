<?php

Class Feeder {

  public $feeds;
  private $saver;

  public function __construct($saver) {
    $this->saver = $saver;
    $handle = fopen("data/feeds.txt", "r");
    $feeds = array();
    if ($handle) {
        while (($url = fgets($handle)) !== false) {
          $feeds[] = $url;
        }
    } else {
        // error opening the file.
    }
    if (!empty($feeds)) {
      $this->feeds = $feeds;
    }
  }
  public function saveFeedData() {
    foreach ($this->feeds AS $feed_url) {
      $contents = @file_get_contents($feed_url);
      if ($contents) {
        $xml = simplexml_load_string($contents);
        foreach ($xml->channel->item AS $feed_item) {
          $prepared_feed_item = $this->prepareFeedItemFile((array) $feed_item);
          $this->saver->createFeedItemFile($prepared_feed_item);

          $article = $this->prepareArticle($prepared_feed_item);
          $this->saver->createArticle($article);
        };
      }
    }
  }
  private function prepareArticle($feed_item) {
    date_default_timezone_set('Europe/Copenhagen');
    $feed_item['pubDate_parsed'] = strtotime($feed_item['pubDate']);
    $feed_item['updated'] = time();
    $feed_item['status'] = 'feed_item';
    return $feed_item;
  }
  private function prepareFeedItemFile($feed_item) {
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
      'feed_description' => isset($feed_item['description']) ? $feed_item['description'] : '',
      'feed_pubDate' => isset($feed_item['pubDate']) ? $feed_item['pubDate'] : '',
      'feed_author' => isset($feed_item['author']) ? $feed_item['author'] : '',
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

