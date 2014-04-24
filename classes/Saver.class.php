<?php
Class Saver {

  public $phases = array();

  public function __construct() {
    $this->phases = array(
      'feedread' => 'feeditems',
      'articleparse' => 'parsedarticles',
    );
  }

  public function isArticleSaved($url) {
    $conf = $GLOBALS['newsscanner_config'];
    $parsed_url = parse_url($url);
    if (empty($parsed_url['path'])) {
      print_r($article); exit;
    };
    $filename   = $this->generateFilename($parsed_url['path']);
    foreach ($this->phases AS $phase => $path) {
      $dir = $conf['file_storage_location'] . $path .'/' . $parsed_url['host'] . '/';
      $file = $dir . $filename . '.json';
      if (is_file($file)) {
        return TRUE;
      }
    }
    return FALSE;
  }
  public function isArticleIndexed($url) {
    $counter = $this->elasticQuery(NULL, 'GET', $this->base64url_encode($url));
    if (!isset($counter['data']->found) || !$counter['data']->found) {
      return FALSE;
    }
    return TRUE;
  }

  public function getArticleFromFile($filepath, $phase = 'feedread') {
    if (is_file($filepath)) {
      $json = file_get_contents($filepath);
      if ($json) {
        $article = json_decode($json);
        return $article;
      }
    }
    return FALSE;
  }

  private function generateFilename($path) {
    return str_replace('/', '_', $path);
  }

  public function saveFile($article, $phase) {
    if (is_array($article)) {
      $article = (object) $article;
    }
    $parsed_url = parse_url($article->feed_link);
    $filename   = $this->generateFilename($parsed_url['path']);
    $conf       = $GLOBALS['newsscanner_config'];
    $dir        = $conf['file_storage_location'] . $this->phases[$phase] . '/' . $parsed_url['host'] . '/';
    if (!is_dir($dir)) {
      mkdir($dir, 0755, TRUE);
    }
    $file = $dir . $filename . '.json';
    if (!is_file($file)) {
      file_put_contents($file, json_encode($article));
    }
  }

  public function deleteFile($article, $phase) {
    $parsed_url = parse_url($article->feed_link);
    $filename   = $this->generateFilename($parsed_url['path']);
    $conf       = $GLOBALS['newsscanner_config'];
    $dir        = $conf['file_storage_location'] . $this->phases[$phase] . '/' . $parsed_url['host'] . '/';
    $file = $dir . $filename . '.json';
    if (is_file($file)) {
      unlink($file);
    }
  }

  public function createFeedItemFile($article) {
    if (empty($article)) {
      return;
    }
    if (!$this->isArticleSaved($article)) {
      $this->saveFile($article, 'feedread');
    }
  }


  public function createArticle($article) {
    $counter = $this->elasticQuery(NULL, 'GET', $this->base64url_encode($article['feed_link']));
    if (!isset($counter['data']->found) || !$counter['data']->found) {
      $this->elasticQuery($article, 'POST', $this->base64url_encode($article['feed_link']));
      return TRUE;
    }
    return FALSE;
  }

  public function updateArticle($article) {
    if (is_array($article)) {
      $article = (object) $article;
    }
    $this->elasticQuery($article, 'POST', $this->base64url_encode($article->feed_link));
  }


  public function elasticQuery($query, $method = 'POST', $path = '') {

    $url = 'http://localhost:9200/articles/article/' . $path;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (isset($query)) {
      $data = json_encode($query);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    // Don't print result.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('data' => json_decode($result), 'code' => $http_code);
  }



  public function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }
}

