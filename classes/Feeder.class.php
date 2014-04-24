<?php

Class Feeder {

  public $feeds;
  private $saver;
  private $logger;
  private $parser;
  private $scorer;

  public function __construct($di) {
    if (!isset($di['logger'])) {
      print "No logger injected";
      exit;
    }
    $this->logger = $di['logger'];

    if (!isset($di['saver'])) {
      $this->logger->log('No saver injected', E_USER_ERROR, TRUE);
    }
    $this->saver = $di['saver'];

    if (!isset($di['parser'])) {
      $this->logger->log('No parser injected', E_USER_ERROR, TRUE);
    }
    $this->parser = $di['parser'];

    if (!isset($di['scorer'])) {
      $this->logger->log('No scorer injected', E_USER_ERROR, TRUE);
    }
    
    $this->scorer = $di['scorer'];

    $handle = fopen("data/feeds.txt", "r");
    if (!$handle) {
      $this->logger->log('No valid feeds-list file', E_USER_ERROR, TRUE);
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
    $count = 0;
    foreach ($this->feeds AS $feed_url) {
      $contents = @file_get_contents($feed_url);
      if (!$contents || empty($contents)) {
        $this->logger->log('Non-valid or empty feed: ' . $feed_url, E_USER_WARNING);
      }
      if ($contents) {
        $xml = simplexml_load_string($contents);
        if (!$xml) {
          $this->logger->log('Non-valid feed: ' . $feed_url, E_USER_WARNING);
        }
        foreach ($xml->channel->item AS $feed_item) {
          $feed_item = (array) $feed_item;
          if (!isset($feed_item['link']) || empty($feed_item['link'])) {
              $this->logger->log('Non-valid link in: ' . print_r($feed_item, 1), E_USER_WARNING);
          }
          if (!$this->saver->isArticleIndexed($feed_item['link']) && !$this->saver->isArticleSaved($feed_item['link'])) {
            $prepared_feed_item = $this->prepareFeedItemFile((array) $feed_item);
            $article = $this->prepareArticle($prepared_feed_item);
            $article = $this->parser->parseArticle($article);
            $article = $this->scorer->scoreArticle($article);
            if ($this->saver->createArticle($article)) {
              $count++;
            }
          }
        }
      }
    }
    $this->logger->log("Created $count feed-items.", E_USER_NOTICE);
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
      'feed_description' => isset($feed_item['description']) ? $feed_item['description'] : '',
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

