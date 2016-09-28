<?php
/*
Plugin Name:      OEmbed Auto-Thumbs
Plugin URI:       https://bitbucket.org/snrbrnjna/wp-plugin-oembed-auto-thumbs
Description:      Set thumbnail of (o)embedded content as featured image in the current post.
Version:          0.0.1
Author:           snrbrnjna
Author URI:       https://github.com/snrbrnjna
License:          MIT License
License URI:      http://opensource.org/licenses/MIT
Text Domain:      oembed-auto-thumbs
*/

namespace Brnjna\OembedAutoThumbs;

// global plugin constants
define(__NAMESPACE__.'\\PLUGIN_VERSION'    , '0.0.1');
define(__NAMESPACE__.'\\PLUGIN_NAME'       , 'OEmbed Auto-Thumbs');
define(__NAMESPACE__.'\\PLUGIN_FILE'       , __FILE__);
define(__NAMESPACE__.'\\PLUGIN_PATH'       , realpath(plugin_dir_path(PLUGIN_FILE)) . '/');
define(__NAMESPACE__.'\\PLUGIN_URL'        , plugin_dir_url(PLUGIN_FILE));

// check php version
require_once('bootstrap/dependencies.php');
check_php_version();

// Plugin Options
// Get Basic Release Data (add <release uuid>)
// define(__NAMESPACE__.'\\RELEASE_DATA_BASE_URL', 'https://api.morrmusic.com/json/');


// Install / Uninstall Hooks
function install() {}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\install' );

function uninstall() {}
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\\uninstall' );


// init plugin by loading includes
function init() {
  require_once('main.php');
}
add_action('plugins_loaded', __NAMESPACE__.'\\init');


// assets
add_action('admin_enqueue_scripts', function($hook) {

  debug_log('hook: '. $hook);

  if ($hook == 'post-new.php' || $hook == 'post.php') {
    wp_enqueue_script('oembed-auto-thumbs/admin.js', PLUGIN_URL .'assets/js/admin.min.js', ['jquery'], null, true);
  }
}, 200);
