<?php
/**
* TPL packager: Book.json
*
*/
abstract class TPL_Packager_Shelf_JSON
{
/**
* [generate_shelf_json description]
* @param  string $folder
*/
public function generate_shelf_json($folder) {
   $terms = wp_get_post_terms( $this->_edition_post->ID, TPL_EDITORIAL_PROJECT ); //get all terms for this edition
   foreach($terms as $term) {
      $args = array(
         'post_type' => TPL_EDITION,
         TPL_EDITORIAL_PROJECT => $term->slug,
         'post_status' => 'publish',
         'posts_per_page' => -1,
      );
      $query = new WP_Query($args); //get all edition with same terms
      $keys = array(
         'post_name' 				=> 'name',
         'post_title' 				=> 'title',
         'post_content' 			=> 'info',
         '_tpl_date'					=> 'date',
         '_tpl_cover' 				=> 'cover',
         // '_tpl_url'					=> 'url',
         '_tpl_product_id'		=> 'product_id'
      );
      foreach($query->posts as $j => $post) {
         $options[$j] = array( 'url' => TPL_HPUB_URI . TPL_Utils::TPL_parse_string($post->post_title.'.hpub' ));
         $meta_fields = get_post_custom($post->ID); /* Edition options */

         foreach($post as $kk => $post_key) {
            if(array_key_exists ( $kk, $keys )) {
               $options[$j][$keys[$kk]] = $post_key;
            }
         }

         foreach($meta_fields as $k => $meta_field) {
            if(array_key_exists ( $k, $keys )) {
               if($k == '_tpl_date') {
                  $options[$j][$keys[$k]] = date('Y-m-d H:s:i',strtotime($meta_field[0]));
               }
               else if($k == '_tpl_cover') {
                  $attachment_id = $meta_field[0];
                  $cover_url = wp_get_attachment_url($attachment_id);
                  $options[$j][$keys[$k]] = $cover_url;
               }
               else {
                  $options[$j][$keys[$k]] = $meta_field[0];
               }

            }
         }
      }
      $this->save_json($options, $term->slug.'_shelf.json', $folder);
   }
}
}
