<?php

class Tpl_Preview {
  protected $_edition_id;
  protected $_preview_slider;
  protected $_connected_query;
  protected $_edition_post;
  protected $_html_preview;

  public function __construct() {

    add_action( 'admin_enqueue_scripts', array($this,'preview_register_script' ));
    add_action( 'admin_footer', array($this,'preview_script' ));
    add_action( 'wp_ajax_next_slide_ajax', array($this ,'next_slide_ajax_callback' ));
    add_action('wp_loaded', array($this,'get_connected_data' ));
    $this->_theme = new TPL_Themes();


  }

  public function get_preview_slider() {
    $this->run();
    return $this->_preview_slider;
  }

  public function run() {

    $preview_html = array();
    $preview_html[] = $this->get_post_html(1);
    $preview_html[] = $this->get_post_html(2);


    $edition_folder = TPL_Utils::TPL_make_dir(TPL_PREVIEW_DIR, $this->_edition_post->post_title);
    $index = $this->html_write_preview($preview_html, $edition_folder);
    $preview_slider = file_get_contents($index);
    $this->_preview_slider = $preview_slider;

  }


  public function next_slide_ajax_callback() {
    $preview = new self;
    $preview->get_connected_data();
    $slide = $preview->get_post_html(1);
    echo $slide;
    die();
  }

  /**
  * Save the html output into unique file and prepare
  * @param  string $parsed_post    post html parsed
  * @param  string $filename
  */
  public function html_write_preview($html_posts, $edition_folder) {
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
    </body></html>';
    $html_slide = '
      <div class="swiper-slide">
        <div class="swiper-container swiper-in-slider">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <div class="content-slider">[final_post]</div>
            </div>
          </div>
        </div>
        <div class="swiper-scrollbar"></div>
      </div>';
    $index = $edition_folder . DIRECTORY_SEPARATOR . 'index.html';
    $html_replaced_one = str_replace('[final_post]',$html_posts[0], $html_slide );
    $html_replaced_two = str_replace('[final_post]',$html_posts[1], $html_slide );

    file_put_contents($index, $swiper_open . $html_replaced_one. $html_replaced_two . $swiper_close);
    //file_put_contents($index, $swiper_open  . $swiper_close);
    return $index;
  }
  public function preview_script() {
    ?>
    <script>
    function lazy() {
      var data = {
        "action" : "next_slide_ajax",
        "edition_id" : "<?php echo $_GET['edition_id']?>",
      };

      jQuery.get(ajaxurl, data, function(response) {
        if(response) {
          console.log(response);
        }
        else {
          console.log("not good");
        }
      });
    }


    var mySwiper = new Swiper(".swiper-container",{
      mode:"horizontal",
      scrollContainer:false,
      mousewheelControl:false,
      pagination: ".pagination",
      loop:true,
      grabCursor: true,
      paginationClickable: true,
      onSlideNext: function(swiper){
        lazy();
      }
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

    });
    </script>
    <?php
  }
  public function preview_register_script() {
    wp_register_script( 'jquery_tpl', TPL_PLUGIN_ASSETS.'js/jquery-1.10.1.min.js' );
    wp_register_script( 'swiper', TPL_PLUGIN_ASSETS.'js/idangerous.swiper.min.js' );
    wp_register_script( 'swiper_scrollbar', TPL_PLUGIN_ASSETS.'js/idangerous.swiper.scrollbar.js' );
    wp_enqueue_script( 'jquery_tpl');
    wp_enqueue_script( 'swiper');
    wp_enqueue_script( 'swiper_scrollbar');
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
			'connected_type' 			=> 'edition_post',
			'connected_items' 		=> $this->_edition_post,
			'nopaging' 						=> true,
			'connected_orderby' 	=> 'order',
			'connected_order' 		=> 'asc',
			'connected_order_num' => true,
			'connected_meta' 			=> array(
				array(
						'key' 	=> 'state',
						'value' => 1,
						'type' 	=> 'numeric',
				)
			)
		);

		$connected_query = new WP_Query($args);

		$this->_connected_query = $connected_query;
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
  }


  public function get_post_html( $number ) {
    $connected_post = $this->_connected_query->posts[$number];
    $parsed_post = $this->html_parse($connected_post); //get single post html

    if (!has_action('preview_hook_' . $connected_post->post_type ) || $connected_post->post_type == 'post' ) {
      $html_preview = $parsed_post;
    }
    else {
      $post_title = TPL_Utils::TPL_parse_string($connected_post->post_title);
      do_action('preview_hook_' . $connected_post->post_type, $connected_post->ID, $post_title, $this->edition_folder);
    }
    return $html_preview;
  }

}
