<?php
class TPL_Metabox
{
   public $id;
   public $title;
   public $context;
   public $priority;
   public $fields;

   public $post_id;

   public function __construct( $id, $title, $context, $priority, $post_id, $fields = array() ) {

      $this->id = $id;
      $this->title = $title;
      $this->context = $context;
      $this->priority = $priority;
      $this->fields = $fields;
      $this->post_id = $post_id;
   }

   /**
    * Add a metabox field
    * @param array $f - metabox field
    * @void
    */
   public function add_field( $id, $name, $description, $type, $default, $extra = array() ) {

      $params = array(
         'id'  	 => $id,
         'name'	 => $name,
         'desc' 	 => $description,
         'type'	 => $type,
         'default' => $default
      );
      $field = array_merge( $params, $extra );
      array_push( $this->fields, $field );
   }

   /**
    * Save the fields value ​​of the metabox
    * @void
    */
   public function save_values() {

      foreach( $this->fields as $field ) {

         $field_id = $field['id'];
         $current_value = get_post_meta( $this->post_id, $field_id, true );
         $new_value = '';

         switch ( $field['type'] ) {

            default:
               if ( isset( $_POST[$field_id] ) ) {
                  $new_value = $_POST[$field_id];
               }
               break;
            case 'file':
               if ( !empty( $_FILES[$field_id]['name'] ) ) {

                  $supported_types = get_allowed_mime_types();
                  $file_types = wp_check_filetype( basename( $_FILES[$field_id]['name'] ) );
                  $uploaded_type = $file_types['type'];

                  // Check if the type is supported. If not, throw an error.
                  if ( in_array( $uploaded_type, $supported_types ) ) {

                     $upload_overrides = array( 'test_form' => false );
                     $uploaded = wp_handle_upload( $_FILES[$field_id], $upload_overrides );

                     if ( isset( $uploaded['file'] ) ) {

                        $file = $uploaded['file'];
                        $attachment = array(
                           'post_mime_type' 	=> $uploaded_type,
                           'post_title' 		=> $_FILES[$field_id]['name'],
                           'post_content' 	=> '',
                           'post_status' 		=> 'inherit'
                        );

                        $attach_id = wp_insert_attachment( $attachment, $file, $this->post_id );

                        require_once( ABSPATH . 'wp-admin/includes/image.php' );

                        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                        wp_update_attachment_metadata( $attach_id, $attach_data );

                        if ( $current_value ) {
                           wp_delete_attachment( $current_value );
                        }

                        $new_value = $attach_id;
                     }
                  }
               } else {
                  $new_value = $current_value;
               }
               break;
            case 'date':
               if ( isset( $_POST[$field_id] ) ) {
                  $new_value = date('Y-m-d', strtotime($_POST[$field_id]));
               }
               break;
         }

         if ( $new_value && $new_value != $current_value ) {
            update_post_meta( $this->post_id, $field_id, $new_value );
         }
         elseif ( '' == $new_value && $current_value ) {
            delete_post_meta( $this->post_id, $field_id, $current_value );
         }
      }
   }

   /**
    * Covert a metabox field configuration to html element
    * @return string html field
    */
   public function fields_to_html() {

      $html = '';
      foreach( $this->fields as $field ) {

         $meta = get_post_meta( $this->post_id, $field['id'], true);

         $html.= '<tr>
         <th style="width:20%"><label for="' . $field['id'] . '">' . $field['name'] . '</label></th>
         <td>';

         switch ($field['type']) {

            case 'text':
               $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . ( $meta ? $meta : $field['default'] ) . '" size="30" style="width:97%" /><br>'. $field['desc'];
               break;

            case 'textarea':
               $html.= '<textarea name="' . $field['id'] . '" id="' . $field['id'] . '" cols="60" rows="4" style="width:97%">' . ( $meta ? $meta : $field['default'] ) . '</textarea><br>'. $field['desc'];
               break;

            case 'select':
               $html.= '<select name="' . $field['id'] . '" id="' . $field['id'] . '">';
               foreach ( $field['options'] as $option ) {
                  $html.= '<option value="'. $option['value'] .'" '. ( $meta == $option['value'] ? 'selected="selected"' : '' ) . '>'. $option['text'] . '</option>';
               }
               $html.= '</select>';
               break;

            case 'select_multiple':
               $html.= '<select multiple name="' . $field['id'] . '[]" id="' . $field['id'] . '">';
               foreach ( $field['options'] as $option ) {
                  $html.= '<option value="'. $option['value'] .'" '. ( !empty( $meta ) && in_array( $option['value'], $meta ) ? 'selected="selected"' : '' ) . '>'. $option['text'] . '</option>';
               }
               $html.= '</select>';
               break;

            case 'radio':
               foreach ( $field['options'] as $i => $option ) {
                  $html.= ' <input type="radio" id="' . $field['id'] . '_' . $i .'" name="' . $field['id'] . '" value="' . $option['value'] . '" ' . ( $meta == $option['value'] ? 'checked="checked"' : '' ) . ' />
                  <label for="' . $field['id'] . '_' . $i . '">' . $option['name'] . '</label>';
               }
               break;

            case 'checkbox':
               $html.= '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'] . '" ' . ( $meta ? 'checked="checked"' : '' ) . ' />';
               break;

            case 'file':
               $html.= '<input type="file" name="' . $field['id'] . '" id="' . $field['id'] . '" /><br>';
               if ( $meta ) {
                  $img = wp_get_attachment_image( $meta );
                  if ( $img ) {
                     $html.= $img;
                  }
                  else {
                     $url = wp_get_attachment_url( $meta );
                     if ( $url ) {
                        $html.= '<a href="' . $url . '">' . __( 'Download' ). '</a>';
                     }
                  }
               }
               break;

            case 'date':
               $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta ? $meta : $field['default'] ) . '" size="30" style="width:30%" /><br>'. $field['desc'];
               break;
         }
      }

      $html.= '<td></tr>';
      return $html;
   }
}
