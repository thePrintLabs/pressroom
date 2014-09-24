<?php

class Tpl_Preview {
  protected $_edition_id;
  protected $_preview_slider;
  protected $_packager;
  public function __construct() {

      add_action('wp_ajax_next_slide_ajax', array($this,'next_slide_ajax_callback' ));

      if(is_admin() && isset($_GET['preview'])) {
        add_action('wp_loaded', array($this,'run' ));
      }
  }

  public function run() {
    $this->_packager = new TPL_Packager();
    $preview_html = array();
    $preview_html[] = $this->_packager->package_preview(1);
    $preview_html[] = $this->_packager->package_preview(2);

    $edition_folder = $this->_packager->get_edition_folder();
    $index = $this->html_write_preview($preview_html, $edition_folder);
    $preview_slider = file_get_contents($index);
    $this->_preview_slider = $preview_slider;
  }

  public function get_slide($number) {
    global $tpl_preview;
    var_dump($tpl_preview->_edition_id);
    //var_dump($tpl_preview);
    //$preview_html = $tpl_preview->_packager->package_preview($number);

    return $preview_html;
  }

  public function next_slide_ajax_callback() {
    $slide = $this->get_slide(2);
    echo "banane".$slide;
    die();
  }

  public function get_preview_slider() {
    return $this->_preview_slider;
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
    </body></html>
    <script src="'.TPL_PLUGIN_ASSETS.'js/jquery-1.10.1.min.js"></script>
    <script src="'.TPL_PLUGIN_ASSETS.'js/idangerous.swiper.min.js"></script>
    <script src="'.TPL_PLUGIN_ASSETS.'js/idangerous.swiper.scrollbar.js"></script>
    <script>
    function lazy() {
      var data = {
        "action" : "next_slide_ajax",
        "edition_id" : "'.$_GET['edition_id'].'",
      };

      jQuery.post("'.admin_url("admin-ajax.php").'", data, function(response) {
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
    </script>';
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
}
