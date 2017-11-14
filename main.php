<?php

  namespace Brnjna\OembedAutoThumbs;

  /*
   * Add Thumbnail of embedded Ressource to the post attachments and
   * set it as featured image of the current post - but ONLY if it doesn't already
   * has a featured image.
   *
   * Called only the first time the url is pasted into a post
   * => oembed cache for the post is then set, hence the next time the data doesn't need parsing
   * => so we fetch the thumb for the url and add it as an attachment to the post.
   * <= only when the thumb isnt already part of this posts attachments or any other (get/set oembed_thumbs_cache)
   * => via heartbeat API and the admin.js script the featured image aka post_thumbnail is set, when there's not already one.
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

      // debug_log('check oembed for url ' . $url);
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

    // debug_log('hello, thumburl: '. $thumbnail_url);

    $post = get_post();

    if ($post && $thumbnail_url) {

      $cached_thumb_urls = get_oembed_thumbs_cache();

      // debug_log('cached _oembed_thumb_urls');
      // debug_log($cached_thumb_urls);

      // not in post meta cache => fetch it!
      if (!array_key_exists($thumbnail_url, $cached_thumb_urls) && function_exists('\\media_sideload_image')) {
        // get attachment_id when sideloading:
        //   + http://wordpress.stackexchange.com/a/166190
        //   + anonymous functions: http://stackoverflow.com/a/10304027
        add_action( 'add_attachment',
          function( $att_id ) use (&$cached_thumb_urls, &$thumbnail_url, &$post) {
            // debug_log('missed so we are currently adding attachment "' . $att_id . '" of thumbnail_url "'. $thumbnail_url .'"');

            // update cache
            $cached_thumb_urls[$thumbnail_url] = $att_id;
            update_oembed_thumbs_cache($cached_thumb_urls);
            // remember this last oembed attachment
            set_last_oembed_attachment_id($post, $att_id);
          }
        );

        // loads and attaches the file
        $provider = $data->provider_name ? ' of ' . $data->provider_name : '';
        $title = $data->title ? $data->title : '';
        $attachment_description = 'Autofetched Thumbnail' . $provider . ': ' . $title;
        \media_sideload_image($thumbnail_url, $post->ID, $attachment_description);
        \remove_all_actions( 'add_attachment' );
      } else {
        $att_id = $cached_thumb_urls[$thumbnail_url];
        // debug_log('hit, we already have attachment '.$att_id.' for the thumbnail_url "'. $thumbnail_url .'"');

        set_last_oembed_attachment_id($post, $att_id);
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

  /*
   * When heartbeat data has the flag 'brnjna_heartbeat_check_auto_thumb' set, we
   * look for the last parsed oembed_data and its fetched thumbnail and attachement id
   * so we return it to the client via the heartbeat, so it can set the attachment
   * as the new featured image aka post thumbnail.
   *
   * Heartbeat API intoduction for this idea: https://pippinsplugins.com/using-the-wordpress-heartbeat-api/
   */
  add_filter('heartbeat_received', function($response, $data) {
    // see assets/scripts/admin.js for the data key
    if (array_key_exists('brnjna_heartbeat_check_auto_thumb', $data)) {
      $post_id = $data['brnjna_heartbeat_check_auto_thumb'];
      // debug_log('check auto thumb for post ' . $post_id);

      $last_cached_thumbnail_attachment_id = get_last_oembed_attachment_id($post_id);

      if (!empty($last_cached_thumbnail_attachment_id)) {

        $auto_thumb_nonce = wp_create_nonce('set_post_thumbnail-'. $post_id);

        $response['brnjna_auto_thumb_nonce'] = $auto_thumb_nonce;
        $response['brnjna_auto_thumb_post_id'] = $post_id;
        $response['brnjna_auto_thumb_attachment_id'] = $last_cached_thumbnail_attachment_id;
      }
    }
    return $response;
  }, 10, 2);










  // NOT USED
  // $post: int/object
  // $thumbnail_url: url fetched from oembed_cache
  // $cached_thumb_urls: mapping of $thumbnail_url->attachment_id
  function set_oembed_thumbnail($post, $thumbnail_url, $cached_thumb_urls=array()) {
    if (array_key_exists($thumbnail_url, $cached_thumb_urls)) {
      $attachment_id = $cached_thumb_urls[$thumbnail_url];

      // debug_log('attachment id to set as thumbnail: '. $attachment_id);
      // debug_log($attachment_id);

      // no thumbnail => set just uploaded image as featured image^
      if ($attachment_id) {
        if (!has_post_thumbnail($post)) {

          // debug_log('set post thumbnail for post:' . (is_object($post) ? $post->ID : $post));

          $added = set_post_thumbnail($post, $attachment_id);

          // debug_log('successfully added?' . $added);
        }
      }
    }
  }

  // NOT USED
  // $post: int/object
  function get_oembed_thumbnail_cache($post) {
    $post_id = (is_object($post) ? $post->ID : $post);
    $meta = get_post_meta($post_id, '_oembed_thumb_urls', true);
    // debug_log('$meta');
    // debug_log($meta);
    return ($meta ? $meta : array());
  }



 ?>
