<?php

Class Fetcher {

  public static function fetch($url, $retries = 3, $is_retry = FALSE) {
    static $count;
    if (!$is_retry) {
      $count = 0;
    }
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    $query = curl_exec($curl_handle);
    $info = curl_getinfo($curl_handle);
    curl_close($curl_handle);
    if ($info['http_code'] != '200' || empty($query)) {
      if ($retries > $count) {
        $count++;
        Logger::log('Retrying ('. $count .'). Could not fetch: ' . $url, E_USER_WARNING);
        return Fetcher::fetch($url, $retries, TRUE);
      }
      Logger::log('Aborting. Could not fetch: ' . $url, E_USER_WARNING);
      return FALSE;
    }
    return $query;
  }
}

