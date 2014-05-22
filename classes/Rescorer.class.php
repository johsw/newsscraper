<?php

Class Rescorer {

  private $saver;
  private $scorer;

  public function __construct($di) {
    if (!isset($di['saver'])) {
      Logger::log("No saver injected", E_USER_ERROR, TRUE);
    }
    $this->saver = $di['saver'];
    if (!isset($di['scorer'])) {
      Logger::log("No scorer injected", E_USER_ERROR, TRUE);
    }
    $this->scorer = $di['scorer'];
  }

  public function rescoreArticles() {
    $articles = $this->fetchRescoreArticles();
    foreach ($articles AS $article) {
      $source = (array)$article->_source;
      $rescored_article = $this->scorer->scoreArticle($source);
      $rescored_article['updated'] = time();
      $this->saver->elasticQuery($rescored_article, 'POST', $article->_id);
    }
  }

  private function fetchRescoreArticles() {
    $conf = $GLOBALS['newsscanner_config'];
    $query = array(
      'size' => $conf['items_per_rescore_batch'],
      'sort' => array(
         'updated' => array("order" => 'asc'),
      ),
    );
    $articles_response = $this->saver->elasticQuery($query, 'GET', '_search');
    if ($articles_response['code'] != 200) {
      Logger::log("Failed article fetch", E_USER_ERROR, TRUE);
      return FALSE;
    }
    return $articles_response['data']->hits->hits;
  }
}

