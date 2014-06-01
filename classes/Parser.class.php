<?php
Class Parser {

  public function parseArticle($article) {
    $url             = $article['feed_link'];
    $conf            = $GLOBALS['newsscanner_config'];
    $readability_url = 'https://www.readability.com/api/content/v1/parser';
    $call            = $readability_url . '?url=' . urlencode($url) . '&token=' . $conf['readability_api_key'];
    $json            = Fetcher::fetch($call);

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

  public function getArticleMetadata($article) {
    $url = $article['feed_link'];

    $html_string = file_get_contents($url);

    $html = new DOMDocument();
    @$html->loadHTML($html_string);
    $tags = array();

    foreach ($html->getElementsByTagName('meta') as $meta) {
      //If the property attribute of the meta tag is og:image
      if (in_array($meta->getAttribute('property'), array('og:title', 'og:description', 'og:image'))) {
        $converted_tag_name = str_replace(':', '_', $meta->getAttribute('property'));
        $tags[$converted_tag_name] = $meta->getAttribute('content');
      }
    }

    if (empty($tags)) {
      Logger::log('Empty og:tag-parse-response: ' . $call, E_USER_WARNING);
      return FALSE;
    }
    $article = array_merge($article, $tags);
    return $article;
  }
}

