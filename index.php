<?php

/*
  newsscanner
  Key: johsw
  Secret: bnKWmb9YhG9DJyybZtqG5UWxGu9hJsqj
  
*/



//https://www.readability.com/api/content/v1/parser?url=

$url = 'https://www.readability.com/api/content/v1/parser?url=' . $_GET['url'] .'&token=';

$contents = file_get_contents($url);

print_r($contents);

// SELECT name, count(`tid`) AS count FROM tags WHERE vocab != 'nøgleord' GROUP BY tid ORDER BY count DESC