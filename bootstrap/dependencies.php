<?php

namespace Brnjna\OembedAutoThumbs;

function check_php_version() {
  $php_min_version = '5.3';
  $correct_php_version = version_compare(phpversion(), $php_min_version, '>=');
  if ( ! $correct_php_version ) {
    echo PLUGIN_NAME . ' requires <strong>PHP '.$php_min_version.'</strong> or higher.<br>';
    echo 'You are running PHP ' . phpversion();
    exit;
  }
}
