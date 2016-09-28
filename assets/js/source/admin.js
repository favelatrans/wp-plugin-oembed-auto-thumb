// admin js
(function($) {

  // TODO: check if on post-edit screen
  // TODO: get post id in a nachhaltig way

  // hack: acf sets the post id in the post create admin screen
  var post_id = acf.post_id;

  // Hook into the heartbeat-send: if post_thumbnail isn't set yet, check for new thumbnail via heartbeat
  $(document).on('heartbeat-send', function(e, data) {
    var $thumbnail_id_field = $('input#_thumbnail_id');
    if ($thumbnail_id_field && (!$thumbnail_id_field.attr('value') || $thumbnail_id_field.attr('value') === '-1')) {
      data['brnjna_heartbeat_check_auto_thumb'] = post_id;
    }
  });

  // Listen for the custom event "heartbeat-tick" on $(document) and set the new post thumbnail
  $(document).on('heartbeat-tick', function(e, data) {
    if (data['brnjna_auto_thumb_post_id'] == post_id && data['brnjna_auto_thumb_attachment_id']) {
      var auto_thumb_attachment_id = data['brnjna_auto_thumb_attachment_id']

      // set post thumb via ajax call
      // inspired / copied from the wordpress core js file /wp/wp-admin/js/set-post-thumbnail.js
      $.post(ajaxurl, {
        action: 'set-post-thumbnail',
        post_id: post_id,
        thumbnail_id: auto_thumb_attachment_id,
        _ajax_nonce: data['brnjna_auto_thumb_nonce'],
        cookie: encodeURIComponent(document.cookie)
      }, function(str) {
          var win = window.dialogArguments || opener || parent || top;
          if ( str == '0' ) {
              console.log('no thumbnail set');
          } else {
              $('#postimagediv .inside').html(str);
              $('#postimagediv .inside #plupload-upload-ui').hide();
              win.WPSetThumbnailID(auto_thumb_attachment_id);
              win.WPSetThumbnailHTML(str);
          }
      }
      );



    }
  });


})(jQuery);
