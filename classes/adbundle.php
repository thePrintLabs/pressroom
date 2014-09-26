<?php

	/**
	 * TPL_Adb_Package class.
	 */
	class TPL_ADBundle {

		/**
		 * TPL_Adb_Package function.
		 * Class constructor, add action and filter
		 * @access public
		 * @return void
		 */
		public function TPL_Adb_Package() {
			if ( is_admin() ) {
				add_action( 'init', array( $this, 'add_adb_package_post_type'), 1 );
				add_action('post_edit_form_tag', array($this,'update_edit_form'));
				add_filter( 'add_meta_boxes', array( $this, 'create_metaboxes' ), 40 );
				add_action( 'save_post_'.TPL_ADB_PACKAGE, array($this, 'metabox_save_data'), 40);
			}
		}

		/**
		 * add_Adb_package_post_type function.
		 * Add custom post type edition to worpress
		 * @access public
		 * @return void
		 */
		public function add_adb_package_post_type() {
			$labels = array(
				'name'                => _x( 'Adb Package', 'Adb Package General Name', 'edition' ),
				'singular_name'       => _x( 'Adb Package', 'Adb Package Singular Name', 'edition' ),
				'menu_name'           => __( 'Adb Package', 'edition' ),
				'parent_item_colon'   => __( 'Parent Adb Package:', 'edition' ),
				'all_items'           => __( 'All Adb package ', 'edition' ),
				'view_item'           => __( 'View Adb package', 'edition' ),
				'add_new_item'        => __( 'Add New Adb package', 'edition' ),
				'add_new'             => __( 'Add New', 'edition' ),
				'edit_item'           => __( 'Edit Adb package', 'edition' ),
				'update_item'         => __( 'Update Adb package', 'edition' ),
				'search_items'        => __( 'Search Adb package', 'edition' ),
				'not_found'           => __( 'Not found', 'edition' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'edition' ),
			);
			$args = array(
				'label'               => __( 'Adb_package_type', 'edition' ),
				'description'         => __( 'Pressroom Adb package', 'edition' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'author', 'thumbnail', 'custom-fields', ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-admin-page',
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
			);
			register_post_type( TPL_ADB_PACKAGE, $args );
		}

		/**
		* Define the metabox and field configurations.
		*
		* @param  array $meta_boxes
		* @return array
		*/
		public function create_metaboxes() {
			$meta_boxes['adv_package_metabox'] = array(
				'id'         => 'adv_package_metabox',
				'title'      => __( 'Adv Package Metabox', 'edition' ),
				'pages'      => array( TPL_ADB_PACKAGE ), // Post type
				'context'    => 'normal',
				'priority'   => 'high',
				'show_names' => true, // Show field names on the left
			);
			add_meta_box($meta_boxes['adv_package_metabox']['id'], $meta_boxes['adv_package_metabox']['title'], array($this, 'create_metaboxes_callback'), TPL_ADB_PACKAGE, $meta_boxes['adv_package_metabox']['context'], $meta_boxes['adv_package_metabox']['priority']);
		}

		/**
		 * callback for metaboxes, print html input field
		 * @return {[type]} [description]
		 */
		public function create_metaboxes_callback() {
			global $post;
			echo '<input type="hidden" name="tpl_adb_nonce" value="', wp_create_nonce('tpl_adb_nonce'), '" />';

			echo '<table class="form-table">';

					$meta_zip = get_post_meta($post->ID, '_tpl_zip', true);
					$meta_html = get_post_meta($post->ID, '_tpl_html_file', true);
					echo '<tr>'.
									'<th style="width:20%"><label for="_tpl_html_file">Html file</label></th>'.
									'<td>';
									echo '<input type="text" name="_tpl_html_file" id="_tpl_html_file" value="'. ($meta_html ? $meta_html : 'index.html') . '" size="30" style="width:97%" />'. '<br />'. 'The HTML file from within the ZIP that will be used in the edition.';
									echo '</td>';
					echo '<tr>';
					echo '<tr>'.
									'<th style="width:20%"><label for="_tpl_zip">Zip File</label></th>'.
									'<td>';
									echo '<input type="file" name="_tpl_zip" id="_tpl_zip"' . ' /><br/>';
									if($meta_zip) {
										$url = wp_get_attachment_url($meta_zip);
										if($url) {
											echo '<a href="'.$url.'">Download</a>';
										}
									}
									echo '</td>';
					echo '<tr>';
			echo '</table>';
		}

		/**
		* add enctype to form for fileupload
		*/
		function update_edit_form() {
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
				//Verify nonce
				if($post->post_type != TPL_ADB_PACKAGE)
        	return;

				if (!wp_verify_nonce($_POST['tpl_adb_nonce'], 'tpl_adb_nonce')) {
						return $post_id;
				}

				//Check autosave
				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
						return $post_id;
				}

				//Check permissions
				if ('tpl_adb_nonce' == $_POST['post_type']) {
						if (!current_user_can('edit_page', $post_id)) {
								return $post_id;
						}
				} elseif (!current_user_can('edit_post', $post_id)) {
						return $post_id;
				}
				$html = '_tpl_html_file';
				if($_POST[$html]) {
					$old = get_post_meta($post_id, $html, true);
					$new = $_POST[$html];


					if ($new && $new != $old) {
						update_post_meta($post_id, $html, $new);
					}

				}
				$zip = '_tpl_zip';
				if(!empty($_FILES[$zip]['name'])) {

				// Setup the array of supported file types. In this case, it's just PDF.
				$supported_types = get_allowed_mime_types();
				//var_dump($supported_types); die();
				// Get the file type of the upload
				$arr_file_type = wp_check_filetype(basename($_FILES[$zip]['name']));
				$uploaded_type = $arr_file_type['type'];

				// Check if the type is supported. If not, throw an error.
				if(in_array($uploaded_type, $supported_types)) {
					$upload_overrides = array( 'test_form' => false );
					$uploaded = wp_handle_upload($_FILES[$zip], $upload_overrides);
					//var_dump($_FILES['_tpl_cover']);
					if(isset($uploaded['file'])) {
						$file_name_and_location = $uploaded['file'];
						$attachment = array(
								'post_mime_type' 	=> $uploaded_type,
								'post_title' 			=> $_FILES[$zip]['name'],
								'post_content' 		=> '',
								'post_status' 		=> 'inherit'
						);
						$attach_id = wp_insert_attachment( $attachment, $file_name_and_location, $post_id );
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );
						wp_update_attachment_metadata($attach_id,  $attach_data);
						$existing_uploaded_image = (int) get_post_meta($post_id,$zip, true);
						if(is_numeric($existing_uploaded_image)) {
								wp_delete_attachment($existing_uploaded_image);
						}
						update_post_meta($post_id,$zip,$attach_id);
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
		 * edition_styles function.
		 * Add custom stylesheet
		 * @access public
		 * @return void
		 */
		public function edition_styles() {
			wp_enqueue_style( 'pressroom', TPL_PLUGIN_ASSETS . 'css/pressroom.css' );
		}
	}
