<?php

Class Parser {

  private $saver;
  private $number_pr_batch;


  public function __construct($saver) {
    $this->saver = $saver;
    $conf  = $GLOBALS['newsscanner_config'];
    $this->number_pr_batch = $conf['parsed_articles_per_batch'];
  }
  public function saveParsedArticles() {
    $count = 0;
    $conf  = $GLOBALS['newsscanner_config'];
    $dir   = $conf['file_storage_location'] . $this->saver->phases['feedread'];
    $remaining = TRUE;
    if (!is_dir($dir)) {
      return FALSE;
    }

    $sub_dirs = array_diff(scandir($dir), array('..', '.'));
    while ($count < $this->number_pr_batch && $remaining) {
      $remaining = FALSE;
      foreach ($sub_dirs AS $sub_dir) {
        $files = array_diff(scandir($dir . '/' . $sub_dir), array('..', '.'));
        if (count($files) > 0) {
          $remaining = TRUE;
          $count++;
          $file = current($files);
          $article = $this->saver->getArticleFromFile($dir . '/' . $sub_dir . '/' . $file);
          $parsed_article = $this->parseArticle($article);
          if (!empty($parsed_article)) {
            $article->parsed_body = $parsed_article->content;
            $article->parsed_title = $parsed_article->title;
            $article->parsed_lead_image_url = $parsed_article->lead_image_url;
            $this->saver->saveFile($article, 'articleparse');
            $this->saver->deleteFile($article, 'feedread');
            $this->saver->updateArticle($article);
          }
        }
      }
    }
    print "Parsed $count feed-items.\n";
  }
  private function parseArticle($article) {
    $url = $article->feed_link;
    $conf  = $GLOBALS['newsscanner_config'];
    $readability_url = 'https://www.readability.com/api/content/v1/parser';
    $json =  @file_get_contents($readability_url . '?url=' . $url . '&token=' . $conf['readability_api_key']);
    if (empty($json)) {
      return FALSE;
    }
    $parsed_article = json_decode($json);
    return $parsed_article;


  }
}

