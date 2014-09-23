<?php
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	/**
	 * TPL_Edition class.
	 */
	class TPL_Edition {
		protected $_metabox;

		/**
		 * TPL_Edition function.
		 * Class constructor, add action and filter
		 * @access public
		 * @return void
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_action( 'init', array( $this, 'add_edition_post_type'), 1 );
				add_action( 'add_meta_boxes', array( $this, 'add_main_metabox' ), 30 );
				add_action( 'admin_enqueue_scripts', array($this,'add_date_script' ));
				add_action( 'save_post_'.TPL_EDITION, array($this, 'metabox_save_data'), 40);
				add_action( 'post_edit_form_tag', array($this,'update_edit_form'));
				add_action( 'edit_form_advanced', array( $this, 'pressroom_add_meta_box' ), 100 );
				add_action( 'admin_enqueue_scripts', array($this, 'edition_styles' ) );
				add_action( 'edit_form_advanced', array($this, 'init_thickbox') );
				add_action( 'wp_ajax_publishing', array($this, 'publishing_callback' ) );
				// add_action( 'wp_ajax_preview', array($this, 'preview_callback' ) );
			}
		}

		/**
		 * add_edition_post_type function.
		 * Add custom post type edition to worpress
		 * @access public
		 * @return void
		 */
		public function add_edition_post_type() {
			$labels = array(
				'name'                => _x( 'Editions', 'Post Type General Name', 'edition' ),
				'singular_name'       => _x( 'Edition', 'Post Type Singular Name', 'edition' ),
				'menu_name'           => __( 'Edition', 'edition' ),
				'parent_item_colon'   => __( 'Parent edition:', 'edition' ),
				'all_items'           => __( 'All editions', 'edition' ),
				'view_item'           => __( 'View edition', 'edition' ),
				'add_new_item'        => __( 'Add New Edition', 'edition' ),
				'add_new'             => __( 'Add New', 'edition' ),
				'edit_item'           => __( 'Edit edition', 'edition' ),
				'update_item'         => __( 'Update edition', 'edition' ),
				'search_items'        => __( 'Search edition', 'edition' ),
				'not_found'           => __( 'Not found', 'edition' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'edition' ),
			);
			$args = array(
				'label'               => __( 'edition_type', 'edition' ),
				'description'         => __( 'Press room edition', 'edition' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'author', 'thumbnail', 'custom-fields', ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-book',
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
				'register_meta_box_cb' => array($this, 'add_meta_box_side' ),
			);
			register_post_type( TPL_EDITION , $args );
		}

		public function meta_box_content() {
			global $post;
			$metabox[ TPL_EDITION ] = array(
				'id' => 'edition_metabox',
				'title' => 'Edition metabox',
				'context' => 'normal',
				'priority' => 'high',
				'fields'     => array(
					array(
						'name' => __( 'Author', 'edition' ),
						'desc' => __( 'Author', 'edition' ),
						'id'   => '_tpl_author',
						'type' => 'text',
						'default' => ''
					),
					array(
						'name' => __( 'Creator', 'edition' ),
						'desc' => __( 'Creator', 'edition' ),
						'id'   => '_tpl_creator',
						'type' => 'text',
						'default' => ''
					),
					array(
						'name' => __( 'Publisher', 'edition' ),
						'desc' => __( 'Publisher', 'edition' ),
						'id'   => '_tpl_publisher',
						'type' => 'text',
						'default' => ''
					),
					array(
						'name' => __( 'Product id', 'edition' ),
						'desc' => __( 'the product id', 'edition' ),
						'id'   => '_tpl_product_id',
						'type' => 'text',
						'default' => ''
					),
					array(
							'name' => 'Cover',
							'desc' => 'Upload cover image.',
							'id' => '_tpl_cover',
							'type' => 'file',
							'allow' => array( 'url', 'attachment' )
					),
					array(
							'name' => 'Date',
							'desc' => 'Publication date',
							'id' => '_tpl_date',
							'type' => 'date',
							'default' => date('Y-m-d',strtotime('now')),
					),
					array(
							'name'    => 'Edition theme',
							'desc'    => 'Select a theme',
							'id'      => '_tpl_themes_select',
							'type'    => 'select',
							'options' => TPL_Themes::get_themes_name(),
					),
					array(
						'name'    => 'Subscription type',
						'desc'    => 'Select a subscription type',
						'id'      => '_tpl_subscriptions_select',
						'type'    => 'select_multiple',
						'options' => ($post ? self::get_subscription_types($post->ID) : ''),
					)
				)
			);
			return $metabox;
		}
		/**
		 * add metabox for edition custom fields
		 */
		public function add_main_metabox() {
			$metabox = $this->meta_box_content();
	    foreach($metabox as $post_type => $value) {
	        add_meta_box($value['id'], $value['title'], array($this, 'add_main_metabox_callback'), TPL_EDITION, $value['context'], $value['priority']);
	    }
		}

		/**
		 * metabox callback print html input field
		 */
		public function add_main_metabox_callback() {
			global $post;

			echo '<input type="hidden" name="tpl_edition_nonce" value="', wp_create_nonce('tpl_edition_nonce'), '" />';

		  echo '<table class="form-table">';
			$metabox = $this->meta_box_content();
		  foreach ($metabox[ TPL_EDITION ]['fields'] as $field) {

		      $meta = get_post_meta($post->ID, $field['id'], true);
		      echo '<tr>'.
		              '<th style="width:20%"><label for="'. $field['id'] .'">'. $field['name']. '</label></th>'.
		              '<td>';
		      switch ($field['type']) {
		          case 'text':
		              echo '<input type="text" name="'. $field['id']. '" id="'. $field['id'] .'" value="'. ($meta ? $meta : $field['default']) . '" size="30" style="width:97%" />'. '<br />'. $field['desc'];
		              break;
		          case 'textarea':
		              echo '<textarea name="'. $field['id']. '" id="'. $field['id']. '" cols="60" rows="4" style="width:97%">'. ($meta ? $meta : $field['default']) . '</textarea>'. '<br />'. $field['desc'];
		              break;
		          case 'select':
		              echo '<select name="'. $field['id'] . '" id="'. $field['id'] . '">';
		              foreach ($field['options'] as $option) {
		                  echo '<option '. ( $meta == $option ? ' selected="selected"' : '' ) . '>'. $option . '</option>';
		              }
		              echo '</select>';
		              break;
							case 'select_multiple':
									echo '<select multiple name="'. $field['id'] . '[]" id="'. $field['id'] . '">';
									foreach ($field['options'] as $option) {
											echo '<option '. ( in_array($option, $meta) ? ' selected="selected"' : '' ) . '>'. $option . '</option>';
									}
									echo '</select>';
									break;
		          case 'radio':
		              foreach ($field['options'] as $option) {
		                  echo '<input type="radio" name="' . $field['id'] . '" value="' . $option['value'] . '"' . ( $meta == $option['value'] ? ' checked="checked"' : '' ) . ' />' . $option['name'];
		              }
		              break;
		          case 'checkbox':
		              echo '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'] . '"' . ( $meta ? ' checked="checked"' : '' ) . ' />';
		              break;
							case 'file':
									echo '<input type="file" name="' . $field['id'] . '" id="' . $field['id'] . '"' . ' /><br/>';
									if($meta) {
										$url = wp_get_attachment_image_src($meta);
										if($url) {
											echo '<img height="200" src="'.$url[0].'">';
										}
									}
								break;
							case 'date':
									echo '<input type="text" name="'. $field['id']. '" id="'. $field['id'] .'" value="'. ($meta ? $meta : $field['default']) . '" size="30" style="width:30%" />'. '<br />'. $field['desc'];
								break;
		      }
		      			echo '<td>';
					echo '</tr>';
		  }
		  echo '</table>';
		}

		/**
		 * add enctype to form for fileupload
		 */
		public function update_edit_form() {
		   echo ' enctype="multipart/form-data"';
		}

		/**
		 * save form data
		 * @param  int $post_id
		 */
		public function metabox_save_data($post_id) {
	    global $post;
			if(!$post) {
				return;
			}

			if($post->post_type != TPL_EDITION)
      	return;

			$nonce = $_REQUEST['_wpnonce'];
	    //Verify nonce
	    if (!wp_verify_nonce($_POST['tpl_edition_nonce'], 'tpl_edition_nonce')) {
	        return $post_id;
	    }

	    //Check autosave
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
	        return $post_id;
	    }

	    //Check permissions
	    if (TPL_EDITION == $_POST['post_type']) {
	        if (!current_user_can('edit_page', $post_id)) {
	            return $post_id;
	        }
	    } elseif (!current_user_can('edit_post', $post_id)) {
	        return $post_id;
	    }
			$metabox = $this->meta_box_content();
	    foreach ($metabox[ TPL_EDITION ]['fields'] as $field) {
				if($field['id'] != '_tpl_cover') {
					$old = get_post_meta($post_id, $field['id'], true);
					if($field['id'] == '_tpl_date') {
						$new = date('Y-m-d',strtotime($_POST[$field['id']]));
					}
					else {
						$new = $_POST[$field['id']];
					}

					if ($new && $new != $old) {
							update_post_meta($post_id, $field['id'], $new);
					} elseif ('' == $new && $old) {
							delete_post_meta($post_id, $field['id'], $old);
					}
				}
	    }

			if(!empty($_FILES['_tpl_cover']['name'])) {

        // Setup the array of supported file types. In this case, it's just PDF.
        $supported_types = get_allowed_mime_types();
        //var_dump($supported_types); die();
        // Get the file type of the upload
        $arr_file_type = wp_check_filetype(basename($_FILES['_tpl_cover']['name']));
        $uploaded_type = $arr_file_type['type'];

        // Check if the type is supported. If not, throw an error.
        if(in_array($uploaded_type, $supported_types)) {

          // Use the WordPress API to upload the file
          $upload_overrides = array( 'test_form' => false );
          $uploaded = wp_handle_upload($_FILES['_tpl_cover'], $upload_overrides);
					//var_dump($_FILES['_tpl_cover']);
          if(isset($uploaded['file'])) {
        		$file_name_and_location = $uploaded['file'];
						$attachment = array(
                'post_mime_type' 	=> $uploaded_type,
                'post_title' 			=> $_FILES['_tpl_cover']['name'],
                'post_content' 		=> '',
                'post_status' 		=> 'inherit'
            );
						$attach_id = wp_insert_attachment( $attachment, $file_name_and_location, $post_id );
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );
						wp_update_attachment_metadata($attach_id,  $attach_data);
						$existing_uploaded_image = (int) get_post_meta($post_id,'_tpl_cover', true);
            if(is_numeric($existing_uploaded_image)) {
                wp_delete_attachment($existing_uploaded_image);
            }
						update_post_meta($post_id,'_tpl_cover',$attach_id);
						$upload_feedback = false;
          }
					else {
						$upload_feedback = 'There was a problem with your upload.';
					}
	      }
				else {
	        wp_die("The file type that you've uploaded is not supported.");
	      }
			}
		}

		/**
		 * initializate jquery datepicker on my custom date field
		 */
		public function add_date_script() {
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		}

		/**
		 * add_meta_box_side function.
		 *
		 * @access public
		 * @return void
		 */

		public function add_meta_box_side() {

			add_meta_box(
				'edition_metabox_side',
				__( 'Publication', 'edition' ),
				array( $this, 'cmb_render_button' ),
				TPL_EDITION,
				'side',
				'low'
			);
		}

		public static function get_subscription_types($post_id) {
			$terms = get_terms( TPL_EDITORIAL_PROJECT );

			$types = array();
			foreach($terms as $term) {
				$term_meta = get_option( "taxonomy_term_".$term->term_id );
				$term_types = unserialize($term_meta['subscription_type']);
				foreach($term_types as $type) {
					$types[] = $term_meta['prefix_bundle_id']. '.' . $term_meta['subscription_prefix']. '.' . $type;
				}
			}
			return $types;
		}

		/**
		 * cmb_render_button function.
		 *
		 * @access public
		 * @return void
		 */

		public function cmb_render_button() {
		    echo '<a id="publish_edition" href="'.admin_url('admin-ajax.php').'?action=publishing&edition_id='.get_the_id().'&width=800&height=600&TB_iframe=true" class=" thickbox button button-primary button-large">'.__("Publish", "edition").'</a> ';
				echo '<a id="preview_edition" target="_blank" href="'.admin_url().'?page=preview-page&preview=true&edition_id='.get_the_id().'" class="button button-primary button-large">'.__("Preview", "edition").'</a> ';
		}

		/**
		 * init_theme_method function.
		 * Init for wordpress thickbox
		 * @access public
		 * @return void
		 */

		public function init_thickbox() {
		   add_thickbox();
		}

		/**
		 * publishing_callback function.
		 *
		 * @access public
		 * @return void
		 */

		public function publishing_callback() {
			$packager = new TPL_Packager();
			echo '
				<style>
					#publishing_popup .error .label {background: #c22d26;}
					#publishing_popup .success .label {background: #7dc42a;}
					#publishing_popup .info .label {background: #000;}
					#publishing_popup .label {color:#fff; text-transform:capitalize; padding: 2px 5px;}
					#publishing_popup p { font-family: "Open Sans",sans-serif; font-size: 12px; line-height: 20px; margin: 5px 0;}
					#publishing_popup h1 {margin-bottom: 10px}
				</style>';
			echo '<div id="publishing_popup"><h1>'.__( 'Publication progress', 'edition' ).'</h1>';
			$packager->verbose = true;
			$packager->run();
			echo '</div>';
			die() ;
		}

		// public function preview_callback() {
		// 	$preview = new Tpl_Preview();
		// 	$preview->run();
		// 	die();
		// }

		/**
		 * initialize_wp_list function.
		 *
		 * @access public
		 * @return void
		 */

		public function initialize_wp_list() {

			$pressroom_list_table = new Pressroom_List_Table();
			$pressroom_list_table->prepare_items();
			$pressroom_list_table->display();
		}

		/**
		 * pressroom_add_meta_box function.
		 *
		 * @access public
		 * @return void
		 */
		public function pressroom_add_meta_box() {
			add_meta_box( 'pressroom_metaboxe', __( 'Connected posts', 'edition' ),array($this, 'initialize_wp_list'), TPL_EDITION );
		}

		/**
		 * edition_styles function.
		 * Add custom stylesheet
		 * @access public
		 * @return void
		 */
		public function edition_styles() {
			wp_enqueue_style( 'pressroom', TPL_PLUGIN_ASSETS . 'css/pressroom.css' );
		}
	}
