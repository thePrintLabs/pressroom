<?php
  add_action('print_media_templates', function(){

  // define your backbone template;
  // the "tmpl-" prefix is required,
  // and your input field should have a data-setting attribute
  // matching the shortcode name
  ?>
  <script type="text/html" id="tmpl-pr-gallery-name">
    <label class="setting">
      <span class="name"><?php _e('Name'); ?></span>
      <input style="float:none" class="size" type="text" data-setting="name">
    </label>
  </script>

  <script>

    jQuery(document).ready(function(){

      // add your shortcode attribute and its default value to the
      // gallery settings list; $.extend should work as well...
      _.extend(wp.media.gallery.defaults, {
        name: ''
      });

      // merge default gallery settings template with yours
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
