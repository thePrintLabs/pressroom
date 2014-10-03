<?php

class TPL_Preview {
  protected $_connected_query;
  protected $_edition_post;

  public function __construct() {

      // add_action( 'admin_enqueue_scripts', array( $this, 'preview_register_styles' ) );
      // add_action( 'admin_enqueue_scripts', array( $this,'preview_register_script' ) );
      // add_action( 'admin_footer', array( $this,'preview_script' ) );
      add_action( 'wp_ajax_next_slide_ajax', array( $this ,'next_slide_ajax_callback' ) );
      //add_action( 'wp_loaded', array( $this,'get_connected_data' ), 10 );
      //add_action( 'wp_loaded', array( $this,'init_preview_swiper' ), 20 );
      // add_action( 'admin_menu', array( $this,'init_preview') );
      // add_filter( 'admin_footer_text', array( $this, 'remove_footer') );
      $this->_theme = new TPL_Theme();
  }

  // public function init_preview() {
  //   add_submenu_page( null, 'Preview screen', 'Preview', 'manage_options', 'preview-swiper', array($this, 'init_preview_swiper'));
  // }

  public function init_preview_swiper() {
   $this->get_connected_data();
   $count_data = count($this->_connected_query->posts);

   $preview_html = array();
   for($i=0; $i< $count_data; $i++) {
      array_push($preview_html, $this->get_post_html($i));
   }

   $edition_folder = TPL_Utils::make_dir(TPL_PREVIEW_DIR, $this->_edition_post->post_title);
   $index = $this->html_write_preview($preview_html, $edition_folder, TPL_Utils::parse_string($this->_edition_post->post_title));
   $preview = file_get_contents($index);
   echo $preview;
  }

  /**
   * hack for removing wordpress footer
   * @void
   */
  public function remove_footer() {

  }

  public function next_slide_ajax_callback() {
    $preview = new self;
    $preview->get_connected_data();
    $slide = $preview->get_post_html($_GET['number']);
    if($slide) {
      echo $slide;
    }
    die();
  }

  /**
  * Save the html output into unique file and prepare
  * @param  string $parsed_post    post html parsed
  * @param  string $filename
  */
  public function html_write_preview($html_posts, $edition_folder, $title) {
    $swiper_open= '
      <!DOCTYPE html>
      <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
      <title></title>
      <meta name="description" content="">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" type="text/css" href="' . TPL_PLUGIN_ASSETS . 'css/preview.css">
      <link rel="stylesheet" type="text/css" href="' . TPL_PLUGIN_ASSETS . 'css/idangerous.swiper.css">
      <link rel="stylesheet" type="text/css" href="' . TPL_PLUGIN_ASSETS . 'css/idangerous.swiper.scrollbar.css">
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
      </div>
      <script src="' . TPL_PLUGIN_ASSETS.'js/jquery-2.0.3.min.js"></script>
      <script src="' . TPL_PLUGIN_ASSETS.'js/idangerous.swiper.min.js"></script>
      <script src="' . TPL_PLUGIN_ASSETS.'js/idangerous.swiper.scrollbar.js"></script>
      <script src="' . TPL_PLUGIN_ASSETS.'js/preview.js"></script>
      <script>
      function lazy() {
        var data = {
          "action" : "next_slide_ajax",
          "edition_id" : "' . $_GET['edition_id'] . '",
          "number" : mySwiper.activeIndex,
          "preview": true,

        };

        jQuery.get("'. admin_url("admin-ajax.php") . '", data, function(response) {
          if(response) {
            var slide_init = \'<div class="swiper-container swiper-in-slider swiper-in-slider-new">\'+response+\'</div><div class="swiper-scrollbar"></div>\';
            mySwiper.appendSlide(slide_init);
            mySwiper.resizeFix();
            mySwiper.reInit();
            fixPagesHeight();
          }
          else {
            console.log("all loaded");
          }
        });
      }


      var mySwiper = new Swiper(".swiper-container",{
        mode:"horizontal",
        scrollContainer:false,
        mousewheelControl:false,
        loop:true,
        grabCursor: true,
        roundLengths: true,
        calculateHeight: true,
        paginationClickable: true,
      });


      $(".arrow-left").on("click", function(e){
        e.preventDefault();
        mySwiper.swipePrev();
      })
      $(".arrow-right").on("click", function(e){
        e.preventDefault();
        mySwiper.swipeNext();
      });



      </script>
      </body>
      </html>';
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
    $html_replaced = '';
    foreach( $html_posts as $post) {
      $html_replaced .= str_replace('[final_post]',$post, $html_slide );
   }
   //  $html_replaced_one = str_replace('[final_post]',$html_posts[0], $html_slide );
   //  $html_replaced_two = str_replace('[final_post]',$html_posts[1], $html_slide );

    file_put_contents($index, $swiper_open . $html_replaced . $swiper_close);
    return $index;
  }
  public function preview_script() {
    ?>
    <script>
    function lazy() {
      var data = {
        "action" : "next_slide_ajax",
        "edition_id" : "<?php echo $_GET['edition_id']?>",
        "number" : mySwiper.activeIndex,
        "preview": true,

      };

      jQuery.get(ajaxurl, data, function(response) {
        if(response) {
          var slide_init = '<div class="swiper-container swiper-in-slider swiper-in-slider-new">'+response+'</div><div class="swiper-scrollbar"></div>';
          mySwiper.appendSlide(slide_init);
          mySwiper.resizeFix();
          mySwiper.reInit();
          fixPagesHeight();
        }
        else {
          console.log("all loaded");
        }
      });
    }


    var mySwiper = new Swiper(".swiper-container",{
      mode:"horizontal",
      scrollContainer:false,
      mousewheelControl:false,
      loop:true,
      grabCursor: true,
      roundLengths: true,
      calculateHeight: true,
      paginationClickable: true,
      onSlideNext: function(){
        lazy();
     },
    });


    $(".arrow-left").on("click", function(e){
      e.preventDefault();
      mySwiper.swipePrev();
      fixPagesHeight();
    })
    $(".arrow-right").on("click", function(e){
      e.preventDefault();
      mySwiper.swipeNext();
      fixPagesHeight();
    });

   function fixPagesHeight(){
      $('.device').css({height:$(window).height()})
      $('.swiper-slide').css({height:$(window).height()})
      $('.swiper-wrapper').css({height:$(window).height()})
   }
    $(window).on('resize',function(){fixPagesHeight()})

    </script>
    <?php
  }


  public function preview_register_script() {
    wp_register_script( 'jquery_tpl', TPL_PLUGIN_ASSETS.'js/jquery-2.0.3.min.js' );
    wp_register_script( 'swiper', TPL_PLUGIN_ASSETS.'js/idangerous.swiper.min.js' );
    wp_register_script( 'preview', TPL_PLUGIN_ASSETS.'js/preview.js' );
    //wp_register_script( 'iscroll', TPL_PLUGIN_ASSETS.'js/iscroll.js' );
    wp_enqueue_script( 'jquery_tpl');
    wp_enqueue_script( 'swiper');
    wp_enqueue_script( 'preview');
    //wp_enqueue_script( 'iscroll');
  }

  public function preview_register_styles() {

    wp_register_style( 'preview', TPL_PLUGIN_ASSETS . 'css/preview.css');
    wp_register_style( 'swiper', TPL_PLUGIN_ASSETS . 'css/idangerous.swiper.css');

    wp_enqueue_style( 'preview' );
    wp_enqueue_style( 'swiper' );
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
         'nopaging' 					=> true,
         'connected_orderby' 	   => 'order',
         'connected_order' 		=> 'asc',
         'connected_order_num'   => true,
         'connected_meta' 			=> array(
            array(
                  'key' 	=> 'state',
                  'value'  => 1,
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

    $template = TPL_Theme::get_theme_page( $_GET['edition_id'], $connected_post->p2p_id );
    if ( !$template ) {
      return false;
    }
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

  public function rewrite_url( $html ) {

    if($html) {
      $theme_folder = TPL_Theme::get_theme_uri( $_GET['edition_id'] ); //get current theme folder
      $dom = new domDocument;
      $dom->loadHTML( $html );
      $links = $dom->getElementsByTagName( 'link' );
         foreach( $links as $link ) {
        $href = $link->getAttribute( 'href' );
        $html = str_replace( $href, $theme_folder . $href, $html );
      }
      $scripts = $dom->getElementsByTagName( 'script' );
      foreach( $scripts as $script ) {
        $src = $script->getAttribute( 'src' );
        $html = str_replace( $src, $theme_folder . $src, $html );
      }
    }
    return $html;
  }

  public function get_post_html( $number ) {

    if(!isset($this->_connected_query->posts[$number])){
      return false;
    }
    $connected_post = $this->_connected_query->posts[$number];
    $parsed_post = $this->html_parse($connected_post); //get single post html
    $final_post = $this->rewrite_url($parsed_post);

    if (!has_action('preview_hook_' . $connected_post->post_type ) || $connected_post->post_type == 'post' ) {
      $html_preview = $final_post;
    }
    else {
      $post_title = TPL_Utils::parse_string($connected_post->post_title);
      do_action('preview_hook_' . $connected_post->post_type, $connected_post->ID, $post_title, $this->edition_folder);
    }
    return $html_preview;
  }
}
