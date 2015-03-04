<?php
  add_action('print_media_templates', function(){

  ?>
  <script type="text/html" id="tmpl-pr-gallery-name">
    <label class="setting">
      <span class="name"><?php _e('Name'); ?></span>
      <input style="float:none" class="size" type="text" data-setting="name">
    </label>
  </script>

  <script>

    jQuery(document).ready(function(){

      _.extend(wp.media.gallery.defaults, {
        name: ''
      });

      wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
        template: function(view){
          return wp.media.template('gallery-settings')(view)
               + wp.media.template('pr-gallery-name')(view);
        }
      });

    });

  </script>
  <?php

});

  add_action('print_media_templates', function(){

  ?>
  <script type="text/html" id="tmpl-pr-playlist-name">
    <label class="setting">
      <span class="name"><?php _e('Name'); ?></span>
      <input style="float:none" class="size" type="text" data-setting="name">
    </label>
  </script>

  <script>

    jQuery(document).ready(function(){

      _.extend(wp.media.gallery.defaults, {
        name: ''
      });

      wp.media.view.Settings.Playlist = wp.media.view.Settings.Playlist.extend({
        template: function(view){
          return wp.media.template('playlist-settings')(view)
               + wp.media.template('pr-playlist-name')(view);
        }
      });

    });

  </script>
  <?php

});
