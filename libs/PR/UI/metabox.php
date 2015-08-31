<?php
class PR_Metabox
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
   * @param  string $id
   * @param  string $name
   * @param  string $description
   * @param  string $type
   * @param  string $default
   * @param  array  $extra
   * @return array
   */
  public function add_field( $id, $name, $description, $type, $default, $extra = array() ) {

    $field = $this->_make_field( $id, $name, $description, $type, $default, $extra );
    array_push( $this->fields, $field );
  }

  /**
   * Prepend field to metabox
   * @param  string $id
   * @param  string $name
   * @param  string $description
   * @param  string $type
   * @param  string $default
   * @param  array  $extra
   * @return array
   */
  public function prepend_field( $id, $name, $description, $type, $default, $extra = array() ) {

    $field = $this->_make_field( $id, $name, $description, $type, $default, $extra );
    array_unshift( $this->fields, $field );
  }

  /**
   * Remove field from metabox
   * @param string $id
   * @void
   */
  public function remove_field( $id ) {

    foreach ( $this->fields as $k => $field ) {
      if ( $field['id'] == $id ) {
        unset( $this->fields[$k] );
      }
    }
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
          } else {
            $new_value = $field['default'];
          }
          break;
        case 'color_clear':
          if ( isset( $_POST[$field_id . '_clear'] ) ) {
            $new_value = 'clear';
          } elseif ( isset( $_POST[$field_id] ) ) {
            $new_value = $_POST[$field_id];
          } else {
            $new_value = $field['default'];
          }
          break;
        case 'checkbox':
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
                   'post_mime_type'   => $uploaded_type,
                   'post_title'     => $_FILES[$field_id]['name'],
                   'post_content'   => '',
                   'post_status'     => 'inherit'
                );

                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attach_id = wp_insert_attachment( $attachment, $file, $this->post_id );
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
              $new_value[$key] = $single;
            }
          } else {
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
  public function save_term_values( $updated = false ) {

    $term_meta = get_option( 'taxonomy_term_' . $this->post_id );
    $term_keys = array_keys( array_merge( $_POST, $_FILES ) );
    foreach ( $this->fields as $field ) {
      $field_id = $field['id'];
      $default = $updated ? '' : $field['default'];
      switch ( $field['type'] ) {

        default:
          $term_meta[$field_id] = isset( $_POST[$field_id] ) ? $_POST[$field_id] : $default;
          break;
        case 'color_clear':
          if ( isset( $_POST[$field_id . '_clear'] ) ) {
            $term_meta[$field_id] = 'clear';
          } elseif ( isset( $_POST[$field_id] ) ) {
            $term_meta[$field_id] = $_POST[$field_id];
          } else {
            $term_meta[$field_id] = $field['default'];
          }
          break;
        case 'double_text':
          $term_meta[$field_id][0] = isset( $_POST[$field_id][0] ) ? $_POST[$field_id][0] : $default;
          $term_meta[$field_id][1] = isset( $_POST[$field_id][1] ) ? $_POST[$field_id][1] : $default;
          break;
        case 'repeater':
          $term_meta[$field_id] = array_filter( $_POST[$field_id] );
          break;
        case 'repeater_with_radio':
          if ( isset( $_POST[$field_id] ) ) {
            $term_meta[$field_id] = array_filter( $_POST[$field_id] );
          }
          if ( isset( $_POST[$field['radio_field']] ) ) {
            $term_meta[$field['radio_field']] = array_filter( $_POST[$field['radio_field']] );
          }
          break;
        case 'date':
          $term_meta[$field_id] = date( 'Y-m-d', strtotime( $_POST[$field_id] ) );
          break;
        case 'file':
          $current_value = isset( $term_meta[$field_id] ) ? $term_meta[$field_id] : '';
          if ( !empty( $_FILES[$field_id]['name'] ) ) {
            $supported_types = get_allowed_mime_types();
            $file_types = wp_check_filetype( basename( $_FILES[$field_id]['name'] ) );
            $uploaded_type = $file_types['type'];
            // Check if the type is supported. If not, throw an error.
            if ( in_array( $uploaded_type, $supported_types ) ) {
              $upload_overrides = array( 'test_form' => false );
              $uploaded = wp_handle_upload( $_FILES[$field_id], $upload_overrides );
              if ( isset( $uploaded['file'] ) ) {
                $attachment = array(
                  'post_mime_type'  => $uploaded_type,
                  'post_title'      => $_FILES[$field_id]['name'],
                  'post_content'    => '',
                  'post_status'     => 'inherit'
                );

                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], $this->post_id );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                if ( strlen( $current_value ) ) {
                  wp_delete_attachment( $current_value );
                }
                $term_meta[$field_id] = $attach_id;
              }
            }
          } else {
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
  public function fields_to_html( $term = false, $class = '', $render_type = 'table' ) {

    $html = '';
    $img_add = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAOElEQVRIx2NgGAWjYCAAP60tqBi1YPhY4AjEGVjwehzisuQkRwksuAWHONtoJA8fCxJHi+NRgAIACRMLT1NmIO8AAAAASUVORK5CYIIbd6c9de163cea60b462a8c6cd83a93e7"/>';
    $img_remove = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAARBAMAAAA1VnEDAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAF3AAABdwE7iaVvAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAA9QTFRF////AAAAAAAAAAAAAAAAUTtq8AAAAAR0Uk5TADVCUDgXPZIAAAAaSURBVAhbY2CgKVA2BgIjCJvRBQwEMGVoBQCxXAPsAZwyyQAAAABJRU5ErkJggg60a8c977b5851eb7a101a51c617fd8ad"/>';

    foreach( $this->fields as $field ) {

      if ( $term ) {
        $tax_options = get_option( 'taxonomy_term_' . $this->post_id );
        if( isset ( $tax_options[$field['id']] ) ) {
          $meta_value = $tax_options[$field['id']];
        }
        else {
          $meta_value = false;
        }
      }
      else {
        $meta_value = get_post_meta( $this->post_id, $field['id'], true);
      }

      if ( !is_array( $meta_value ) ) {
        $meta_value = esc_attr( $meta_value );
      }

      $html.= $render_type == 'table' ? '<tr ' : '<div ';
      $html.= 'class="' . $class . '">';
      $html.= $render_type == 'table' ? '<th ' . ( $field['type'] == 'textnode' ? 'colspan="2"' : '' ) . '>' : ' ';
      $html.= '<label for="' . $field['id'] . '">' . $field['name'] . '</label>';
      $html.= $render_type == 'table' ? '</th><td>' : '<br>';
      switch ( $field['type'] ) {
        case 'text':
          $html.= '<input type="text" placeholder="'.( isset( $field['placeholder'] ) ? $field['placeholder'] : '') .'" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . ( $meta_value ? $meta_value : $field['default'] ) . '" size="20" style="width:100%" '.( isset( $field['required'] ) ? 'required="'.$field["required"].'"' : '') .' /><p class="description">'. $field['desc'] . '</p>';
          break;
        case 'password':
          $html.= '<input type="password" placeholder="'.( isset( $field['placeholder'] ) ? $field['placeholder'] : '') .'" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . ( $meta_value ? $meta_value : $field['default'] ) . '" size="20" style="width:100%" /><p class="description">'. $field['desc'] . '</p>';
          break;
        case 'double_text':
          $html.= '<input type="text" placeholder="'.( isset( $field['placeholder'] ) ? $field['placeholder'] : '') .'" name="' . $field['id'] . '[0]" id="' . $field['id'] . '_0" value="' . ( $meta_value ? $meta_value[0] : $field['default'] ) . '" size="20" style="width:94%" />';
          $html.= '<input type="text" placeholder="'.( isset( $field['placeholder'] ) ? $field['placeholder'] : '') .'" name="' . $field['id'] . '[1]" id="' . $field['id'] . '_1" value="' . ( $meta_value ? $meta_value[1] : $field['default'] ) . '" size="20" style="width:5%" /><p class="description">'. $field['desc'] . '</p>';
          break;
        case 'text_autocompleted':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . ( $meta_value ? $meta_value : $field['default'] ) . '" size="20" style="width:100%" /><div class="pr_autocompleted"></div>';
          break;
        case 'textarea':
          $html.= '<textarea name="' . $field['id'] . '" id="' . $field['id'] . '" cols="60" rows="4" style="width:97%">' . ( $meta_value ? $meta_value : $field['default'] ) . '</textarea><p class="description">'. $field['desc'] . '</p>';
          break;
        case 'select':
          $html.= '<select name="' . $field['id'] . '" id="' . $field['id'] . '">';
          foreach ( $field['options'] as $option ) {
            $html.= '<option value="'. $option['value'] .'" '. ( $meta_value == $option['value'] ? 'selected="selected"' : '' ) . '>'. $option['text'] . '</option>';
          }
          $html.= '</select><p class="description">'. $field['desc'] . '</p>';
          break;
        case 'select_multiple':
          $html.= '<select multiple name="' . $field['id'] . '[]" id="' . $field['id'] . '" class="chosen-select" style="width:100%;">';
          foreach ( $field['options'] as $key => $group ) {
            $html.= '<optgroup label="'.$key.'">';
            foreach ( $group as $option ) {
                $html.= '<option value="'. $option['value'] .'" '. ( !empty( $meta_value ) && in_array( $option['value'], $meta_value ) ? 'selected="selected"' : '' ) . '>'. $option['value'] . '</option>';
            }
            $html.= '</optgroup>';
          }
          $html.= '</select>';
          break;
        case 'radio':
          foreach ( $field['options'] as $i => $option ) {
            $checked = ( ( $meta_value && $meta_value == $option['value'] ) || ( !$i && !$meta_value ) );
            $html.= ' <input type="radio" id="' . $field['id'] . '_' . $i .'" name="' . $field['id'] . '" value="' . $option['value'] . '" ' . ( $checked ? 'checked="checked"' : '' ) . ' />
            <label for="' . $field['id'] . '_' . $i . '">' . $option['name'] . '</label>';
          }
          $html.= '<p class="description">'. $field['desc'] . '</p>';
          break;
        case 'checkbox':
          $html.= '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'] . '" ' . ( $meta_value ? 'checked="checked"' : '' ) . ' /><p class="description">'. $field['desc'] . '</p>';
          break;
        case 'checkbox_list':
          foreach ( $field['options'] as $i => $option ) {
            $html.= '<input type="checkbox" name="' . $field['id'] . '[]" id="' . $field['id'] . '_' . $i . '" value="'. $option['value'] .'" '. ( !empty( $meta_value ) && in_array( $option['value'], $meta_value ) ? 'checked="checked"' : '' ) . ' />
            <label for="' . $field['id'] . '_' . $i . '">'. $option['text'] . '</label><br/>';
          }
          break;
        case 'file':
          $html.= '<input type="file" name="' . $field['id'] . '" id="' . $field['id'] . '" /><br>';
          if ( $meta_value ) {
            $img = wp_get_attachment_image( $meta_value );
            if ( $img ) {
              $html.= '<div class="pr-image-container">';
              $html.= $img;
              $html.= '<a href="#" class="remove-file" '.( $term ? 'data-attachment="' . $meta_value . '" data-term="' . $this->post_id . '"' : "" ).' data-field="' . $field['id'] . '" ></a>';
              $html.= '</div>';
            }
            else {
               $url = wp_get_attachment_url( $meta_value );
               if ( $url ) {
                  $html.= '<a href="' . $url . '">' . __( 'Download' ). '</a>';
               }
            }
          }
          $html.= '<p class="description">'. $field['desc'] . '</p>';
          break;
        case 'date':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta_value ? $meta_value : $field['default'] ) . '" size="30" style="width:100%" />
          <p class="description">'. $field['desc'] . '</p>';
          break;
        case 'color':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta_value ? $meta_value : $field['default'] ) . '" class="pr-color-picker" data-default-color="#ffffff" />
          <p class="description">'. $field['desc'] . '</p>';
          break;
        case 'color_clear':
          $html.= '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( $meta_value == 'clear' ? '' : ( $meta_value ? $meta_value : $field['default'] ) ) . '" class="pr-color-picker" data-default-color="#ffffff" />
          <label for="' . $field['id'] . '_clear" class="pr-picker-clear"><input type="checkbox" name="' . $field['id'] . '_clear" id="' . $field['id'] . '_clear" ' . ( $meta_value == 'clear' ? 'checked="checked"' : '' ) . ' />' . __("Transparent", 'pr_metabox') . '</label>
          <p class="description">'. $field['desc'] . '</p>';
          break;
        case 'number':
          $html.= '<input type="number" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( strlen( $meta_value ) ? $meta_value : $field['default'] ) . '" />
          <p class="description">'. $field['desc'] . '</p>';
          break;
        case 'decimal':
          $html.= '<input type="number" min="0" max="1" step="0.1" name="' . $field['id'] . '" id="' . $field['id'] . '" value="'. ( strlen( $meta_value ) ? $meta_value : $field['default'] ) . '" />
          <p class="description">'. $field['desc'] . '</p>';
          break;
        case 'textnode':
            $html.= $field['desc'];
          break;
        case 'custom_html':
          $html.= $field['desc'];
          break;
        case 'repeater':
          $i = 0;
          $values = array('');
          if ( isset( $meta_value ) && !empty( $meta_value ) ) {
            $values = $meta_value;
          }

          foreach ( $repetitions as $value ) {

            $html.= '<div class="pr_repeater" id="pr_repeater" data-index="'.$i.'">
            <input style="width:85%;" type="text" name="' . $field['id'] . '['.$i.']" id="' . $field['id'] . '" value="'. ( $value ? $value : $field['default'] ) . '">
            <a href="#" ' . ( $i == 0 ? "id=\"add-field\" class=\"add-field\"" : "id=\"remove-field\" class=\"remove-field\"" ). '">' . ( $i == 0 ? $img_add : $img_remove ). '</a>
            <div class="repeater-completer" style="width:84%;"></div>
            </div>';
            $i++;
          }
          break;
        case 'repeater_with_radio':
          $i = 0;
          $radio_values = array();
          $values = array('');
          if ( isset( $meta_value ) && !empty( $meta_value ) ) {
            $values = $meta_value;
          }

          if ( isset( $tax_options[$field['radio_field']] ) && !empty( $tax_options[$field['radio_field']] ) ) {
            $radio_values = $tax_options[$field['radio_field']];
          }

          foreach ( $values as $value ) {

            $html.= '<div class="pr_repeater subscription" id="pr_repeater" data-index="'. $i .'">
            <a href="#" ' . ( $i == 0 ? "id=\"add-field\" class=\"add-field\"" : "id=\"remove-field\" class=\"remove-field\"" ). '">' . ( $i == 0 ? $img_add : $img_remove ). '</a>
            <input style="width:55%;" type="text" name="' . $field['id'] . '['. $i .']" id="' . $field['id'] . '" value="'. ( $value ? $value : $field['default'] ) . '">';
            $html .= '<div class="subscription_method">';
            foreach( $field['radio_options'] as $k => $option) {

              $checked = ( ( isset( $radio_values[$i] ) && $option['value'] == $radio_values[$i] ) || ( !$k && empty( $radio_values ) ) );
              $html.= '<input type="radio" id="checkbox-' . $option['value'].'" name="_pr_subscription_method['. $i .']" '. ( $checked ? 'checked="checked"' : '' ) .' value="'.$option['value'].'" />
              <label for="checkbox-' . $option['value'] . '_' . $i . '">' . $option['name'] . '</label>';
            }

            $html.='</div>
            <div class="repeater-completer"></div>
            </div>';
            $i++;
          }
          break;
      }
      $html.= $render_type == 'table' ? '</td></tr>' : '</div>';
    }
    return $html;
  }

  /**
   * add chosen.js to metabox
   *
   * @void
   */
  public function add_chosen_script() {

    wp_enqueue_style( 'chosen', PR_ASSETS_URI . 'css/chosen.min.css' );
    wp_register_script( 'chosen', PR_ASSETS_URI . '/js/chosen.jquery.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'chosen' );
  }

  /**
   * add custom script to metabox
   *
   * @void
   */
  public function add_custom_script() {

    wp_enqueue_style( 'wp-color-picker' );
    wp_register_script( 'metabox', PR_ASSETS_URI . '/js/pr.metabox.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
    wp_enqueue_script( 'metabox' );
  }

  /**
   * set options for field
   * @param  string $id
   * @param  string $name
   * @param  string $description
   * @param  string $type
   * @param  string $default
   * @param  array  $extra
   * @return array
   */
  protected function _make_field( $id, $name, $description, $type, $default, $extra = array() ) {
    $params = array(
       'id'           => $id,
       'name'         => $name,
       'desc'         => $description,
       'type'         => $type,
       'default'      => $default,
    );
    $field = array_merge( $params, $extra );
    return $field;
  }
}
