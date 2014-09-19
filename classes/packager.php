<?php

class TPL_Packager {
	protected $_edition_post;
	protected $_connected_query;
	protected $_theme;
	protected $_post_ids;
	protected $_attachments;
	protected $_posts_urls;
	protected $_array_order = array();
	public 		$json_options;
	public		$verbose = true;
	public 		$edition_folder;
	public		$html_preview;

	/**
	* TPL_Packager function.
	*
	* @access public
	*/

	public function __construct() {
		add_action('packager_attachment_hook_' . TPL_ADB_PACKAGE, array($this,'adb_hook'), 10, 4);
		add_action('packager_bookjson_hook_' . TPL_ADB_PACKAGE, array($this,'add_adb_bookjson'), 10, 3);
		add_action('packager_hook_' . TPL_ADB_PACKAGE, array($this,'adb_package'), 10, 3);
		add_action('preview_hook_' . TPL_ADB_PACKAGE, array($this,'preview_adb_package'), 10, 3);
		$this->get_connected_data();
		$this->_theme = new TPL_Themes();
	}

	/**
	* get_connected_data function.
	*
	* @access public
	* @return array
	*/

	public function get_connected_data() {
		if (isset($_GET['edition_id'])) {
			$this->_edition_post = get_post($_GET['edition_id']);
		}
		$args = array(
			'connected_type' 		=> 'edition_post',
			'connected_items' 		=> $this->_edition_post,
			'nopaging' 				=> true,
			'connected_orderby' 	=> 'order',
			'connected_order' 		=> 'asc',
			'connected_order_num' 	=> true,
			'connected_meta' 		=> array(
				array(
						'key' 	=> 'state',
						'value' => 1,
						'type' 	=> 'numeric',
				)
			)
		);

		$connected_query = new WP_Query($args);

		$this->_connected_query = $connected_query;

		$posts_ids = array();
		foreach($this->_connected_query->posts as $post_id) {
			$posts_ids[] = $post_id->ID;
		}
		$this->_post_ids = $posts_ids;
	}
	/**
	* Parsing html
	* @param  object $connected_post wordpress $post
	* @return string	html string
	*/

	public function html_parse($connected_post) {
		$template = $this->_theme->get_template_file_per_page($connected_post->p2p_id);
		if($template) {
			ob_start();
			global $post;
			$post = $connected_post;
			setup_postdata($post);
			require($template);
			$output = ob_get_contents();
			wp_reset_postdata();
			ob_end_clean();
			return $output;
		}
		else {
			$this->print_line(sprintf(__('You have to select a template for %s', 'edition'), $connected_post->post_title), 'error');
		}
	}

	/**
	* Parse index file
	* @return string html string
	*/

	public function cover_parse() {
		$cover = $this->_theme->get_cover($this->_edition_post->ID);
		ob_start();
		$posts = $this->_connected_query;
		require_once($cover);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	* Get all url from the html string and replace with internal url of the package
	* @param  string $html
	* @param  string $ext  = 'html' extension file output
	* @return string or false
	*/
	public function rewrite_url($html, $ext = 'html') {
		if($html) {
			$host = site_url();
			$urls = TPL_Utils::TPL_getUrls($html);
			foreach($urls as $url) {
				if(strpos($url,$host)!==false) {
					$post_id = url_to_postid($url);
					if($post_id) {
						if(in_array($post_id, $this->_post_ids)){
							$path = TPL_Utils::TPL_parse_string(get_the_title($post_id)). '.' . $ext;
							$this->add_url($url, $path);
						}
					}
					else {
						$this->get_attachment($url);
					}
				}
			}
			if($this->_posts_urls) {
				$html = str_replace(array_keys($this->_posts_urls), $this->_posts_urls, $html);
			}
		}
		// $this->_dom_document->loadHTML($html);
		// $this->_dom_document->encoding='UTF-8';
		// return $this->_dom_document->saveHTML();
		return $html;
	}

	/**
	* Save the html output into file
	* @param  string $parsed_post    post html parsed
	* @param  string $filename
	*/
	public function html_write($parsed_post, $filename) {
		file_put_contents($this->edition_folder . DIRECTORY_SEPARATOR . TPL_Utils::TPL_parse_string( $filename ) .'.html', $parsed_post);
	}

	/**
	* Get an attachment and metadata by url
	* @param  string $url [description]
	* @return {[type]}      [description]
	*/
	public function get_attachment($url) {
		$attachment_id = self::TPL_get_attachment_from_url($url);
		if($attachment_id) {
			//$attachment = get_post( $attachment_id );
			$file = pathinfo($url);
			$filename = $file['basename'];
			$path = TPL_EDITION_MEDIA . $filename;
			$this->add_url($url, $path); //add attachment url to array urls for rewriting
			//$filepath = pathinfo($url);
			$this->_attachments[$file['basename']] = $url;

			//$this->get_attachment_metadata($url, $attachment_id);
		}
	}
	/**
	* Add url to urls array for rewriting
	* @param string $url
	* @param string $path internal path
	*/
	public function add_url($url, $path){
		$this->_posts_urls[$url] = $path;
	}
	/**
	* Return an array of attachment with metadata
	* @param  string $guid          attachment url
	* @param  int $attachment_id
	*/
	public function get_attachment_metadata($url, $attachment_id) {
		$metadata = wp_get_attachment_metadata($attachment_id, true);
		$attached = get_attached_file($attachment_id);
		$path = pathinfo($attached);
		if($metadata) {
			$this->_attachments[$path['basename']] = $attached;
			if(array_key_exists('sizes', $metadata)){ //image
				foreach($metadata['sizes'] as $k => $size) {
					$filepath = $path['dirname'] . DIRECTORY_SEPARATOR . $size['file'];
					$this->_attachments[$size['file']] = $filepath;
				}
			}
		}
		else {
			//not an image
			$this->_attachments[$path['basename']] = $attached;
		}
	}
	/**
	* Get all attachment linked to the post
	* @param  int $parent_id post id
	*/
	public function get_linked_attachment($parent_id, $edition_folder, $verbose) {

		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'post_parent' => $parent_id,
		) );

		$parent = get_post($parent_id);

		if ($attachments) {
			foreach ($attachments as $attachment) {
				if ($parent->post_type == 'post') {
					$this->get_attachment_metadata($attachment->guid,$attachment->ID);
				} else {
					do_action('packager_attachment_hook_' . $parent->post_type, $attachment, $edition_folder, $parent_id, $verbose);
				}
			}
		}
	}

	/**
	* get attachment id by url
	* @param string $attachment_url
	*/
	public static function TPL_get_attachment_from_url($attachment_url) {
		global $wpdb;
		$attachment_url = preg_replace( '/-[0-9]{1,4}x[0-9]{1,4}\.(jpg|jpeg|png|gif|bmp)$/i', '.$1', $attachment_url ); //get resized attachment url
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid RLIKE '%s' LIMIT 1;", $attachment_url ));
		if($attachment)
					return $attachment[0];
				else
					return false;
	}
	/**
	* Copy all attachment in the package folder
	* @param  array $attachments
	* @param  string $media_folder path of the package folder
	*/
	public function save_attachments($attachments, $media_folder) {
		if($attachments) {
			$attachments = array_unique($attachments);
			foreach($attachments as $k => $attachment) {
				if(copy($attachment, $media_folder . DIRECTORY_SEPARATOR . $k)){
					$this->print_line(__('Copied ', 'edition') . $attachment, 'success');
				}
				else {
					$this->print_line(__('Failed to copy ', 'edition') . $attachment, 'error');
				}
			}
		}

	}
	/**
	* Downloal assets folder to package folder
	* @param  	string $assets_folder
	*/
	public function download_assets($assets_folder){
		$new_assets = TPL_Utils::TPL_make_dir($this->edition_folder, 'assets' );
		if($new_assets) {
			$this->print_line(__('Create folder ', 'edition') . $new_assets, 'success');
		}
		else {
			$this->print_line(__('Failed to create folder ', 'edition') . TPL_TMP_DIR . DIRECTORY_SEPARATOR . 'assets', 'error');
		}
		if(is_dir($assets_folder)) {
			$count_files = TPL_Utils::TPL_recursive_copy($assets_folder, $new_assets);
			if(is_numeric($count_files)){
				$this->print_line(sprintf(__('Copy assets folder with %s files ', 'edition'), $count_files), 'success');
			}
			else if(is_array($count_files)) {
					foreach($count_files as $file){
							$this->print_line(sprintf(__('Error : Can\'t copy file %s ', 'edition'), $file), 'error');
					}
			}
			else {
				$this->print_line(__('Error : Generic error during assets download ', 'edition'), 'error');
			}
		}
		else {
			$this->print_line(__('Error : Can\'t read assets folder ', 'edition') .$assets_folder, 'error');
		}
	}


	public function get_options($id_edition, $shelf = false) {
		global $tpl_pressroom;
		if(!$shelf) {
			$book_url = str_replace(array('http://','https://'), 'book://', TPL_HPUB_URI ); //replace protocol for hpub compatibility
		}
		else {
			$book_url = TPL_HPUB_URI;
		}

		$options = array( 'hpub' => true, 'url' => $book_url . TPL_Utils::TPL_parse_string($this->_edition_post->post_title.'.hpub' ));

		$keys = array(
			'tpl-orientation' 			=> 'orientation',
			'tpl-zoomable' 					=> 'zoomable',
			'opt-color-background' 	=> '-baker-background',
			'tpl-vertical-bounce' 	=> '-baker-vertical-bounce',
			'tpl-index-bounce' 			=> '-baker-index-bounce',
			'tpl-index-height' 			=> '-baker-index-height',
			'tpl-media-autoplay'	 	=> '-baker-media-autoplay',
			'_tpl_author'						=> 'author',
			'_tpl_creator'					=> 'creator',
			// '_tpl_url'							=> 'url',
			'_tpl_cover'						=> 'cover',
			'_tpl_date'							=> 'date',
			'post_title' 						=> 'title',
		);
		foreach ($tpl_pressroom->_configs as $k => $option) { /* General options */
			if(array_key_exists ( $k, $keys )){
				switch ($k) {
					case 'tpl-index-height':
						$options[$keys[$k]] = (int)$option;
						break;
					case 'tpl-orientation':
						$options[$keys[$k]] = strtolower($option);
						break;
					case 'tpl-zoomable':
					case 'tpl-vertical-bounce':
					case 'tpl-vertical-bounce':
					case 'tpl-index-bounce':
					case 'tpl-media-autoplay':
						$options[$keys[$k]] = (bool)$option;
						break;
					default:
						$options[$keys[$k]] = ($option == '0' || $option == '1' ? (int)$option : $option);
						break;
				}
			}
		}
		$post = get_post($id_edition);
		foreach($post as $kk => $post_key) {
			if(array_key_exists ( $kk, $keys )){
				$options[$keys[$kk]] = $post_key;
			}
		}
		$meta_fields = get_post_custom($id_edition); /* Edition options */
		foreach($meta_fields as $k => $meta_field) {
			if(array_key_exists ( $k, $keys )){
				if($k == '_tpl_cover') {
					$attachment_id = $meta_field[0];
					$path = get_attached_file($attachment_id);
					$file = pathinfo($path);
					$options[$keys[$k]] =  TPL_EDITION_MEDIA  . $file['basename'];
					if(!$shelf){
						if(copy($path, $this->edition_folder . DIRECTORY_SEPARATOR . TPL_EDITION_MEDIA . $file['basename'])){
							$this->print_line(sprintf(__('Copied cover image %s ', 'edition'), $path), 'success');
						}
						else {
							$this->print_line(sprintf(__('Can\'t copy cover image %s ', 'edition'), $path), 'error');
						}
					}
				}
				else if($k == '_tpl_author' || $k == '_tpl_creator' ){
					$splitted = explode(',',$meta_field[0]);
					foreach($splitted as  $split) {
						$options[$keys[$k]][] = $split;
					}
				}
				else {
					$options[$keys[$k]] = $meta_field[0];
				}
			}
		}
		return $options;
	}

	/**
	* get all option and html file and put them in an array
	* @param  string $edition_folder
	*/
	public function generate_book_json(){
		$options = $this->get_options($this->_edition_post->ID);
		foreach($this->_connected_query->posts as $post) {
			$post_title = TPL_Utils::TPL_parse_string($post->post_title);
			if (!has_action('packager_bookjson_hook_' . $post->post_type ) || $post->post_type == 'post' ) {
			//if($post->post_type != TPL_ADB_PACKAGE) {
				if(is_file($this->edition_folder . DIRECTORY_SEPARATOR . $post_title . '.html')){
						$options['contents'][] = $post_title . '.html';
				}
				else {
					$this->print_line(sprintf(__('Can\'t find file %s. It won\'t add to book.json ', 'edition'), $this->edition_folder . $post_title . '.html'), 'error');
				}
			}
			else {
				do_action('packager_bookjson_hook_' . $post->post_type, $post, $post_title, $this->edition_folder);
			}
		}

		$this->json_options = $options;
		//var_dump($this->json_options);
		$this->save_json($this->json_options, 'book.json', $this->edition_folder);
	}
	/**
	* Create json file
	* @param  array $content all option
	* @param  string $path
	*/
	public function save_json($content, $name, $directory) {
		$content = json_encode($content);
		$file = $directory . DIRECTORY_SEPARATOR . $name;
		if(file_put_contents($file, $content )){
			$this->print_line(__('Generating ', 'edition') . $name, 'success');
		}
		else {
			$this->print_line(__('Failed to generate book.json ', 'edition'), 'error');
		}
	}

	/**
	* Hpub creation
	* @param  string $folder the folder where generate the hpub
	* @param  string $name
	*/
	public function generate_hpub($name){
		if(TPL_Utils::zipFile($this->edition_folder, TPL_HPUB_DIR . $name.'.hpub', false)){
			$this->print_line(__('Generate hpub ', 'edition').$name, 'success');
		}
	}

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

	public function adb_hook($attachment, $edition_folder, $parent_id, $verbose) {
		if( $attachment->post_mime_type == 'application/zip') { //check for zip adb_package file
			$this->extract_adb($attachment, $edition_folder, $parent_id, $verbose);
		}
	}

	/**
	* extract .zip file
	* @param  array $attachment     wordpress $post type attachment
	* @param  string $edition_folder
	*/
	public function extract_adb($attachment, $edition_folder, $adb_id, $verbose = true) {
		$this->verbose = $verbose;
		$attached = get_attached_file($attachment->ID);
		$zip = new ZipArchive;
		if ($zip->open($attached) === true) {
				$adb = get_post($adb_id);
				$adb_title = TPL_Utils::TPL_parse_string($adb->post_title);
				$adb_folder = $edition_folder . DIRECTORY_SEPARATOR . TPL_EDITION_ADB;
				$zip->extractTo($adb_folder . $adb_title);
				$zip->close();
				$this->print_line(__('Unzipped file ', 'edition') . $attached, 'success');
		}
		else {
				$this->print_line(__('Failed to unzip file', 'edition') . $attached, 'error');
		}
	}

	public function adb_package($post_id, $edition_folder) {
		$this->get_linked_attachment($post_id, $edition_folder, true);
	}

	public function preview_adb_package($post_id, $post_title, $edition_folder) {
		$this->get_linked_attachment($post_id, $edition_folder, false);
		$indexfile = get_post_meta( $post_id, '_tpl_html_file' );
		$path_index = $edition_folder . DIRECTORY_SEPARATOR . TPL_EDITION_ADB  . $post_title . DIRECTORY_SEPARATOR .$indexfile[0];
		if(is_file($path_index)) {
			$final_post = file_get_contents($path_index);
			$this->html_preview .= '
				<div class="swiper-slide">
					<div class="swiper-container swiper-in-slider">
						<div class="swiper-wrapper">
							<div class="swiper-slide">
								<div class="content-slider">'.$final_post.'</div>
							</div>
						</div>
					</div>
					<div class="swiper-scrollbar"></div>
				</div>';
		}
	}

	public function add_adb_bookjson($post, $post_title, $edition_folder) {
		$indexfile = get_post_meta( $post->ID, '_tpl_html_file' );
		$path_index = $edition_folder . DIRECTORY_SEPARATOR . TPL_EDITION_ADB  . $post_title . DIRECTORY_SEPARATOR .$indexfile[0];
		if(is_file($path_index)){
			$this->json_options['contents'][] = TPL_EDITION_ADB . $post_title . DIRECTORY_SEPARATOR . $indexfile[0];

		}
		else {
			$this->print_line(sprintf(__('Can\'t find file %s. It won\'t add to book.json. See the wiki to know how to make an add bundle', 'edition'), $path_index), 'error');
		}
	}


/**
* live output function
* @param  string $output
*/
	public function print_line($output, $class = 'info') {
		if($this->verbose) {
			echo '<p class="liveoutput '.$class.'"><span class="label">'.$class.':</span> '.$output.'</p>';
			ob_flush();
			flush();
			//usleep(100000);
		}
	}
	/**
	* run packager
	*/
	public function run () {
		ob_start();
		$output = array();
		$this->edition_folder = TPL_Utils::TPL_make_dir(TPL_TMP_DIR, $this->_edition_post->post_title);
		$media_folder = TPL_Utils::TPL_make_dir($this->edition_folder, TPL_EDITION_MEDIA );
		if($this->edition_folder) {
			$this->print_line(__('Create folder ', 'edition') . $this->edition_folder, 'success');
		}
		else {
			$this->print_line(__('Failed to create folder ', 'edition') . TPL_TMP_DIR . TPL_Utils::TPL_parse_string($this->_edition_post->post_title), 'error');
		}
		$theme_folder = $this->_theme->get_template_path($this->_edition_post->ID); //get current theme folder
		$this->download_assets($theme_folder . DIRECTORY_SEPARATOR . 'assets'); //duplicate asset in new folder
		$parsed_cover = $this->cover_parse(); //parse html of cover index.php file
		$final_cover = $this->rewrite_url($parsed_cover); //rewrite url of cover html
		$this->html_write($final_cover, 'index'); //write html in a new file index.html
		foreach($this->_connected_query->posts as $k => $connected_post) {

			$parsed_post = $this->html_parse($connected_post); //get single post html
			$final_post = $this->rewrite_url($parsed_post); //rewrite all contained url
			//$this->get_linked_attachment($connected_post->ID, $edition_folder); //inutile: se l'attachment non è richiamato nell'html, è inutile includerlo nel pacchetto
			if (!has_action('packager_hook_' . $connected_post->post_type ) || $connected_post->post_type == 'post' ) {
				$this->html_write($final_post, $connected_post->post_title);
			}
			else {
				do_action('packager_hook_' . $connected_post->post_type, $connected_post->ID, $this->edition_folder);
			}
			$this->print_line(__('Adding ', 'edition').$connected_post->post_title);

		}
		$this->save_attachments($this->_attachments, $media_folder); //duplicate all attachment in tmp folder
		$this->generate_book_json();
		$this->generate_hpub( TPL_Utils::TPL_parse_string($this->_edition_post->post_title ) );
		$this->generate_shelf_json(TPL_SHELF_DIR);
		$this->print_line(__('Done', 'edition'), 'success');
		ob_end_flush();
	}

	public function package_preview() {
		$this->verbose = false;
		$this->edition_folder = TPL_Utils::TPL_make_dir(TPL_PREVIEW_DIR, $this->_edition_post->post_title);
		$media_folder = TPL_Utils::TPL_make_dir($this->edition_folder, TPL_EDITION_MEDIA );
		$theme_folder = $this->_theme->get_template_path($this->_edition_post->ID); //get current theme folder
		$this->download_assets($theme_folder . DIRECTORY_SEPARATOR . 'assets'); //duplicate asset in new folder
		$parsed_cover = $this->cover_parse(); //parse html of cover index.php file
		$final_cover = $this->rewrite_url($parsed_cover); //rewrite url of cover html
		$this->html_write($final_cover, 'index', true); //write html in a new file index.html
		$this->html_preview = '';
		foreach($this->_connected_query->posts as $k => $connected_post) {
			$parsed_post = $this->html_parse($connected_post); //get single post html
			$final_post = $this->rewrite_url($parsed_post); //rewrite all contained url
			if (!has_action('preview_hook_' . $connected_post->post_type ) || $connected_post->post_type == 'post' ) {
				$this->html_preview .= '
					<div class="swiper-slide">
						<div class="swiper-container swiper-in-slider">
							<div class="swiper-wrapper">
								<div class="swiper-slide">
									<div class="content-slider">'.$final_post.'</div>
								</div>
							</div>
						</div>
						<div class="swiper-scrollbar"></div>
					</div>';
			}
			else {
				$post_title = TPL_Utils::TPL_parse_string($connected_post->post_title);
				do_action('preview_hook_' . $connected_post->post_type, $connected_post->ID, $post_title, $this->edition_folder);
			}
		}
		$index = $this->html_write_preview($this->html_preview);
		$this->save_attachments($this->_attachments, $media_folder); //duplicate all attachment in tmp folder

		return $index;
	}

	/**
	* Save the html output into unique file and prepare
	* @param  string $parsed_post    post html parsed
	* @param  string $filename
	*/
	public function html_write_preview($html_posts) {
		$swiper_open= '
		<!DOCTYPE html>
		<head>
			<meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
			<title></title>
			<meta name="description" content="">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<link href="'.TPL_PLUGIN_ASSETS.'css/preview.css" rel="stylesheet">
			<link rel="stylesheet" href="'.TPL_PLUGIN_ASSETS.'css/idangerous.swiper.css">
			<link rel="stylesheet" href="'.TPL_PLUGIN_ASSETS.'css/idangerous.swiper.scrollbar.css">
		</head>
		<body>
		<div class="device">
			<a class="arrow-left" href="#"></a>
			<a class="arrow-right" href="#"></a>
			<div class="swiper-container">
				<div class="swiper-wrapper">';
		$swiper_close = '
				</div>
			</div>
			<div class="pagination"></div>
		</div>
		</body></html>
		<script src="'.TPL_PLUGIN_ASSETS.'js/jquery-1.10.1.min.js"></script>
		<script src="'.TPL_PLUGIN_ASSETS.'js/idangerous.swiper.min.js"></script>
		<script src="'.TPL_PLUGIN_ASSETS.'js/idangerous.swiper.scrollbar.js"></script>
		<script>
		var mySwiper = new Swiper(".swiper-container",{
			mode:"horizontal",
			scrollContainer:false,
			mousewheelControl:false,
			pagination: ".pagination",
			loop:true,
			grabCursor: true,
			paginationClickable: true,
		});
		$(".swiper-in-slider").each(function(){
			$(this).swiper({
				mode:"vertical",
				scrollContainer:true,
				mousewheelControl:true,
				scrollbar: {
					container : ".swiper-scrollbar",
					draggable : true,
					hide: false,
					snapOnRelease: true
				}
			})
		});

		$(".arrow-left").on("click", function(e){
			e.preventDefault();
			mySwiper.swipePrev();
		})
		$(".arrow-right").on("click", function(e){
			e.preventDefault();
			mySwiper.swipeNext();
		})
		</script>';
		$index = $this->edition_folder . DIRECTORY_SEPARATOR . 'index.html';
		file_put_contents($index, $swiper_open . $html_posts . $swiper_close);

		return $index;
	}
}
