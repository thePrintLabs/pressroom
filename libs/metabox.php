<?php
class TPL_Metabox
{
   public $id;
   public $title;
   public $context;
   public $priority;
   public $fields;
   public $post_id;

   /**
    * constructor
    *
    * @param  int $id
    * @param  string $title
    * @param  string $context
    * @param  int $priority
    * @param  int $post_id
    * @param  array $fields
    * @void
    */
   public function __construct( $id, $title, $context, $priority, $post_id, $fields = array() ) {

      $this->id = $id;
      $this->title = $title;
      $this->context = $context;
      $this->priority = $priority;
      $this->fields = $fields;
      $this->post_id = $post_id;

      add_action( 'admin_enqueue_scripts', array( $this, 'add_chosen_script' ) );
      add_action( 'admin_footer', array( $this, 'add_custom_script' ) );
   }

   /**
    * Add field to metabox
    *
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
    * Used in post or custom post types
    *
    * @void
    */
   public function save_values() {

      foreach( $this->fields as $field ) {

        $field_id = $field['id'];
        $current_value = get_post_meta( $this->post_id, $field_id, true );
         $new_value = false;
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
            case 'repeater':

                if ( isset( $_POST[$field_id] ) ) {
                  foreach ( $_POST[$field_id] as $key => $single ) {
                    $new_value[$key] = serialize( $single );
                  }
                }
                else {
                  $new_value[$key] = $_POST[$field_id][0];
                }

              break;
        }

        if ( $new_value !== false && $new_value != $current_value ) {
          update_post_meta( $this->post_id, $field_id, $new_value );
        }
        elseif ( '' == $new_value && $current_value ) {
          delete_post_meta( $this->post_id, $field_id, $current_value );
        }
      }
    }

    /**
     * Save the fields value ​​metabox
     * Used in taxonomy terms
     *
     * @void
     */
    public function save_term_values() {

      $term_meta = get_option( 'taxonomy_term_' . $this->post_id );
      $term_keys = array_keys( array_merge( $_POST, $_FILES ) );
      foreach( $this->fields as $field) {

        $field_id = $field['id'];

        switch ( $field['type'] ) {

          default:
              $term_meta[$field_id] = ( isset( $_POST[$field_id] ) ? $_POST[$field_id] : '' );
            break;

          case 'repeater':
            $term_meta[$field_id] = serialize( array_filter( $_POST[$field_id] ) );
            break;
          case 'repeater_with_radio':

            $term_meta[$field_id] = serialize( array_filter( $_POST[$field_id] ) );
            if( isset( $_POST['_pr_subscription_method'] ) )
              $term_meta['_pr_subscription_method'] = serialize( array_filter( $_POST['_pr_subscription_method'] ) );
            break;

          case 'date':
            $term_meta[$field_id] = date('Y-m-d', strtotime($_POST[$field_id]));
            break;

          case 'file':
            $current_value = (isset($term_meta[$field_id]) ? $term_meta[$field_id] : null);

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

                  $term_meta[$field_id] = $attach_id;
                }
              }
            }
            else {
              $term_meta[$field_id] = $current_value;
            }

            break;

        }
      }

      update_option( 'taxonomy_term_' . $this->post_id, $term_meta );

    }

   /**
    * Covert added fields to html elements
    *
    * @return string html field
    */
  public function fields_to_html( $term = false ) {

    $html = '';
    $img_add = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAOElEQVRIx2NgGAWjYCAAP60tqBi1YPhY4AjEGVjwehzisuQkRwksuAWHONtoJA8fCxJHi+NRgAIACRMLT1NmIO8AAAAASUVORK5CYIIbd6c9de163cea60b462a8c6cd83a93e7"/>';
    $img_remove = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAARBAMAAAA1VnEDAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAF3AAABdwE7iaVvAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAA9QTFRF////AAAAAAAAAAAAAAAAUTtq8AAAAAR0Uk5TADVCUDgXPZIAAAAaSURBVAhbY2CgKVA2BgIjCJvRBQwEMGVoBQCxXAPsAZwyyQAAAABJRU5ErkJggg60a8c977b5851eb7a101a51c617fd8ad"/>';

    foreach( $this->fields as $field ) {

      if ( $term ) {
        $meta = get_option( 'taxonomy_term_' . $this->post_id );
      }
      else {
        $meta = get_post_meta( $this->post_id, $field['id'], true);
      }

      if ( $field['type'] != 'repeater' && $field['type'] != 'repeater_with_radio' )
        $meta = ( isset($meta[$field['id']]) ? esc_attr( $meta[$field['id']]) : '' );

      $html.= '<tr>
      <th style="width:20%"><label for="' . $field['id'] . '">' . $field['name'] . '</label></th>
      <td>';

      switch ( $field['type'] ) {

        case 'text':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . ( $meta ? $meta : $field['default'] ) . '" size="20" style="width:100%" /><br>'. $field['desc'] . '<br/>';
          break;

        case 'text_autocompleted':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . ( $meta ? $meta : $field['default'] ) . '" size="20" style="width:100%" /><div class="tpl_autocompleted"></div>';
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
          $html.= '<select multiple name="' . $field['id'] . '[]" id="' . $field['id'] . '" class="chosen-select" style="width:100%;">';
          foreach ( $field['options'] as $key => $group ) {
            $html.= '<optgroup label="'.$key.'">';
            foreach ( $group as $option ) {
                $html.= '<option value="'. $option['value'] .'" '. ( !empty( $meta ) && in_array( $option['value'], $meta ) ? 'selected="selected"' : '' ) . '>'. $option['value'] . '</option>';
            }
            $html.= '</optgroup>';
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

        case 'checkbox_list':
          foreach ( $field['options'] as $i => $option ) {
            $html.= '<input type="checkbox" name="' . $field['id'] . '[]" id="' . $field['id'] . '_' . $i . '" value="'. $option['value'] .'" '. ( !empty( $meta ) && in_array( $option['value'], $meta ) ? 'checked="checked"' : '' ) . ' />
            <label for="' . $field['id'] . '_' . $i . '">'. $option['text'] . '</label><br/>';
          }
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
        case 'color':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta ? $meta : $field['default'] ) . '" class="tpl-color-picker" data-default-color="#ffffff" />';
          break;
        case 'number':
          $html.= '<input type="number" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta ? $meta : $field['default'] ) . '" />';
          break;
        case 'decimal':
          $html.= '<input type="number" min="0" max="1" step="0.1" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta ? $meta : $field['default'] ) . '" />';
          break;
        case 'textnode':
            $html.= $field['desc'];
          break;
        case 'repeater':
          if ( $meta[$field['id']] ) {
            $i = 0;
            $repetitions = unserialize( $meta[$field['id']] );
            foreach ( $repetitions as $value ) {

            $html.= '
            <div class="tpl_repeater" id="tpl_repeater" data-index="'.$i.'">
            <input style="width:85%;" type="text" name="' . $field['id'] . '['.$i.']" id="' . $field['id'] . '" value="'. ( $value ? $value : $field['default'] ) . '">
            <a href="#" ' . ( $i == 0 ? "id=\"add-field\" class=\"add-field\"" : "id=\"remove-field\" class=\"remove-field\"" ). '">' . ( $i == 0 ? $img_add : $img_remove ). '</a>
            <div class="repeater-completer" style="width:84%;"></div>
            </div>';
            $i++;
            }
          }
          else {
            $html.= '<div class="tpl_repeater" id="tpl_repeater" data-index="0">
            <input style="width:85%;" type="text" name="' . $field['id'] . '[0]" id="' . $field['id'] . '" value="'.$field['default'] . '">
            <a href="#" id="add-field">' . $img_add . '</a>
            <div class="repeater-completer" style="width:84%;"></div>
            </div>';
          }
          break;
        case 'repeater_with_radio':
          if ( isset( $meta[$field['id']] ) ) {
            $i = 0;
            $repetitions = unserialize( $meta[$field['id']] );
            $types = unserialize( $meta['_pr_subscription_method'] );
            foreach ( $repetitions as $value ) {
              $html.= '
              <div class="tpl_repeater subscription" id="tpl_repeater" data-index="'. $i .'">
              <a href="#" ' . ( $i == 0 ? "id=\"add-field\" class=\"add-field\"" : "id=\"remove-field\" class=\"remove-field\"" ). '">' . ( $i == 0 ? $img_add : $img_remove ). '</a>
              <input style="width:55%;" type="text" name="' . $field['id'] . '['. $i .']" id="' . $field['id'] . '" value="'. ( $value ? $value : $field['default'] ) . '">';
              $html .= '<div class="subscription_method">';
              foreach( $field['options'] as $option) {
                $html.= '<input type="radio" id="checkbox-' . $option['value'].'" name="_pr_subscription_method['. $i .']" '.( $option['value'] == $types[$i] ? 'checked="checked"' : '' ).' value="'.$option['value'].'" />
                <label for="checkbox-' . $option['value'] . '_' . $i . '">' . $option['name'] . '</label>';
              }

              $html.='</div><div class="repeater-completer"></div>
              </div>';
              $i++;
            }
          }
          else {
            $html.= '<div class="tpl_repeater subscription" id="tpl_repeater" data-index="0">
            <a href="#" id="add-field">' . $img_add . '</a>
            <input style="width:55%;" type="text" name="' . $field['id'] . '[0]" id="' . $field['id'] . '" value="'.$field['default'] . '">';
            $html .= '<div class="subscription_method">';
            foreach( $field['options'] as $option) {
              $html.= '<input style="margin-right:5px" type="radio" id="checkbox-' . $option['value'].'" name="_pr_subscription_method[0]' . '" value="'.$option['value'].'" ' . ' />
              <label for="checkbox-' . $option['value'] . '">' . $option['name'] . '</label>';
            }

            $html.='</div><div class="repeater-completer"></div>
            </div>';
          }
          break;

        }
    }

    $html.= '<td></tr>';

    return $html;
  }

   /**
    * add chosen.js to metabox
    *
    * @void
    */
  function add_chosen_script() {

    wp_enqueue_style( 'chosen', TPL_ASSETS_URI . 'css/chosen.min.css' );

    wp_register_script( 'chosen', TPL_ASSETS_URI . '/js/chosen.jquery.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'chosen' );
  }

  /**
   * add custom script to metabox
   *
   * @void
   */
  function add_custom_script() {

    wp_register_script( 'editorial_project', TPL_ASSETS_URI . '/js/metabox.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
    wp_enqueue_script( 'editorial_project' );

    // Css rules for Color Picker
    wp_enqueue_style( 'wp-color-picker' );
  }
}
