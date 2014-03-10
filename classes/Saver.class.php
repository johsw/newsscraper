<?php
Class Saver {

  public function __construct() {
    //??
  }


  public function createFeedItemFile($article) {
    if (empty($article)) {
      return;
    }
    $parsed_url = parse_url($article['link']);
    $filename   = str_replace('/', '_', $parsed_url['path']);
    $conf       = $GLOBALS['newsscanner_config'];
    $dir        = $conf['file_storage_location'] . 'feeditems/' . $parsed_url['host'] . '/';
    if (!is_dir($dir)) {
      mkdir($dir, 0755, TRUE);
    }
    $file = $dir . $filename . '.json';
    if (!is_file($file)) {
      file_put_contents($file, json_encode($article));
    }
  }


  public function createArticle($article) {
    $counter = $this->elasticQuery(NULL, 'GET', $this->base64url_encode($article['feed_link']));
    if (!$counter['data']->found) {
      $this->elasticQuery($article, 'POST', $this->base64url_encode($article['feed_link']));
    }
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

