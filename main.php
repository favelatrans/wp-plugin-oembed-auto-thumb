<?php

  namespace Brnjna\OembedAutoThumbs;

  /*
   * Add Thumbnail of embedded Ressource to the post attachments and
   * set it as featured image of the current post - but ONLY if it doesn't already
   * has a featured image.
   *
   * Called only the first time the url is pasted into a post
   * => oembed cache for the post is then set, hence the next time the data doesn't need parsing
   * => so fetch the thumb for the url and add it as an attachment to the post, but
   * only when the thumb isnt already part of this posts attachments or any other (get/set oembed_thumbs_cache)
   * Then it sets the attachement as featured image, if there isn't already a featured image set.
   */
  add_filter('oembed_dataparse', __NAMESPACE__.'\\check_oembed', 10, 3);
  function check_oembed($html, $data, $url) {
    // try to get og:image meta flag from embedded resource => better quality url
    $meta_urls = fetch_meta_fields($url, array('og:image'));

    // debug_log('$meta_urls of ' . $url);
    // debug_log($meta_urls);

    if (!empty($meta_urls)) {
      $thumbnail_url = $meta_urls['og:image'];
    } else {

      // debug_log('meta_urls empty => check oembed data for embedding url ' . $url);
      // debug_log($data);

      if (property_exists($data, 'thumbnail_url')) {
        $thumbnail_url = $data->thumbnail_url;
      } elseif (property_exists($data, 'image')) {
        $thumbnail_url = $data->image;
      } else {
        $thumbnail_url = null;
      }
    }

    if (substr($thumbnail_url, 0, 2) == '//') {
      $thumbnail_url = 'https:' . $thumbnail_url;
    }

    // debug_log('finally, thumburl identified: '. $thumbnail_url);

    $post = get_post();

    if ($post && $thumbnail_url) {
      // debug_log('post and thumbnail url present, let\'s go!');

      $cached_thumb_urls = get_oembed_thumbs_cache();

      // debug_log(count($cached_thumb_urls) . 'cached _oembed_thumb_urls');

      // not in post meta cache => fetch it!
      if (!array_key_exists($thumbnail_url, $cached_thumb_urls)) {
        // debug_log('miss, no attachment for the thumbnail_url "'. $thumbnail_url .'", so fetch it...');
        
        // see docs: https://developer.wordpress.org/reference/functions/media_sideload_image/#more-information
        if (!function_exists('\\media_sideload_image')) {
          require_once(ABSPATH . 'wp-admin/includes/media.php');
          require_once(ABSPATH . 'wp-admin/includes/file.php');
          require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        // loads and attaches the file
        $provider = $data->provider_name ? ' of ' . $data->provider_name : '';
        $title = $data->title ? $data->title : '';
        $attachment_description = 'Autofetched Thumbnail' . $provider . ': ' . $title;
        $att_id = \media_sideload_image($thumbnail_url, $post->ID, $attachment_description, 'id');
        
        // debug_log('fetched thumbnail_url "'. $thumbnail_url .'" and attached it with id "'.$att_id.'" to the post');

        // update cache
        $cached_thumb_urls[$thumbnail_url] = $att_id;
        update_oembed_thumbs_cache($cached_thumb_urls);
        // remember this last oembed attachment
        set_last_oembed_attachment_id($post, $att_id);
      } else {
        $att_id = $cached_thumb_urls[$thumbnail_url];
        // debug_log('hit, we already have attachment "'.$att_id.'" for the thumbnail_url "'. $thumbnail_url .'"');
        set_last_oembed_attachment_id($post, $att_id);
      }
      // finally set the embed thumb as featured image, if it doesn't have already one
      if (!has_post_thumbnail($post)) {
        set_post_thumbnail($post, $att_id);
      }
    }

    return $html;
  }

  function get_oembed_thumbs_cache() {
    return get_option('brnjna_oembed_cache', array());
  }

  function update_oembed_thumbs_cache($cache) {
    return update_option('brnjna_oembed_cache', $cache);
  }

  // $post: int/object
  function get_last_oembed_attachment_id($post) {
    $post_id = (is_object($post) ? $post->ID : $post);
    return get_transient('brnjna_last_oembed_attachment_id_'. $post_id);
  }

  // $post: int/object
  function set_last_oembed_attachment_id($post, $attachment_id) {
    $post_id = (is_object($post) ? $post->ID : $post);
    return set_transient('brnjna_last_oembed_attachment_id_'. $post_id, $attachment_id);
  }

?>
