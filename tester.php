<?php

$user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17';
$handle = fopen("data/feeds.txt", "r");
$feeds = array();
if ($handle) {
  while (($url = fgets($handle)) !== false) {
    
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_USERAGENT, $user_agent);
    $query = curl_exec($curl_handle);
    $info = curl_getinfo($curl_handle);
    if ($info['http_code'] != '200') {
     print '<h1>' . $url . "</h1><br />";
     print_r($info);
     print_r($query);
     print '<hr />';
     
    }
    curl_close($curl_handle);
    
  }
}

/*
{
   "sort" : [{ "scores.Facebook.total_count" : {"order" : "desc"}}  ]
}
*/