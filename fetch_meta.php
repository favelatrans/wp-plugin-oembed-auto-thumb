<?php

namespace Brnjna\OembedAutoThumbs;

function fetch_meta_fields($url, $fields) {
  $html = file_get_contents_curl($url);

  //parsing begins here:
  $doc = new \DOMDocument();
  @$doc->loadHTML($html);

  // get and display what you need:
  // $nodes = $doc->getElementsByTagName('title');
  // $title = $nodes->item(0)->nodeValue;
  $metas = $doc->getElementsByTagName('meta');

  $meta_fields = array();
  foreach ($fields as $field) {
    $meta_fields[$field] = null;

    for ($i = 0; $i < $metas->length; $i++) {
      $meta = $metas->item($i);
      if($meta->getAttribute('property') == $field) {
        $meta_fields[$field] = $meta->getAttribute('content');
        continue 2;
      }
    }
  }

  return $meta_fields;
}

function file_get_contents_curl($url) {
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

  $data = curl_exec($ch);
  curl_close($ch);

  return $data;
}

?>
