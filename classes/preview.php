<?php

class TPL_Preview {
  protected $_edition_id;
  protected $_preview_slider;
  protected $_connected_query;
  protected $_edition_post;
  protected $_html_preview;

  public function __construct() {
    if(isset($_GET['preview'])) {
      add_action( 'admin_enqueue_scripts', array($this, 'preview_register_styles' ));
      add_action( 'admin_enqueue_scripts', array($this,'preview_register_script' ));
      add_action( 'admin_footer', array($this,'preview_script' ));
      add_action( 'wp_ajax_next_slide_ajax', array($this ,'next_slide_ajax_callback' ));
      add_action('wp_loaded', array($this,'get_connected_data' ));
      add_action('admin_menu', array($this,'init_preview'));
      add_filter('admin_footer_text', array($this, 'remove_footer'));
      $this->_theme = new TPL_Themes();
    }
  }

  public function init_preview() {
    add_submenu_page( null, 'Preview screen', 'Preview', 'manage_options', 'preview-page', array($this, 'init_preview_callback'));
    add_submenu_page( null, 'Preview screen', 'Preview', 'manage_options', 'preview-swiper', array($this, 'init_preview_swiper'));
  }

  public function init_preview_callback() {

    echo '<div class="wrap">';
      echo '<h2>Edition Preview</h2>';
    echo '</div>';
    global $tpl_preview;
    echo $tpl_preview->get_preview_slider();
  }

  public function init_preview_swiper() {
    $preview_html = array();
    $preview_html[] = $this->get_post_html(0);
    $preview_html[] = $this->get_post_html(1);

    $edition_folder = TPL_Utils::TPL_make_dir(TPL_PREVIEW_DIR, $this->_edition_post->post_title);

    $index = $this->html_write_preview($preview_html, $edition_folder, TPL_Utils::TPL_parse_string($this->_edition_post->post_title));
    $preview = file_get_contents($index);
    echo $preview;
  }

  public function get_preview_slider() {
    $this->run();
    return $this->_preview_slider;
  }

  public function run() {

    $edition_folder = TPL_Utils::TPL_make_dir(TPL_PREVIEW_DIR, $this->_edition_post->post_title);
    $src = admin_url( '?page=preview-swiper&preview=true&edition_id='.$_GET['edition_id']);
    $ish = $this->get_ish($src, $edition_folder, TPL_Utils::TPL_parse_string($this->_edition_post->post_title));
    $preview_slider = file_get_contents($ish);
    $this->_preview_slider = $preview_slider;

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
    $html_replaced_one = str_replace('[final_post]',$html_posts[0], $html_slide );
    $html_replaced_two = str_replace('[final_post]',$html_posts[1], $html_slide );

    file_put_contents($index, $swiper_open . $html_replaced_one. $html_replaced_two . $swiper_close);
    //file_put_contents($index, $swiper_open  . $swiper_close);
    //$url = admin_url( '?page=preview-page&preview=true&edition_id='.$_GET['edition_id']);
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
      }
    });

    $(".swiper-in-slider").each(function(){
      $(this).swiper({
        mode:"vertical",
        scrollContainer:true,
        mousewheelControl:false,
        freeModeFluid: true,
        roundLengths: true,
        calculateHeight: true,
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
      fixPagesHeight();
    })
    $(".arrow-right").on("click", function(e){
      e.preventDefault();
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

  public function get_ish( $src, $path, $folder_name ) {
    $html= '
    <!DOCTYPE html>
    <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
      <title></title>
      <meta name="description" content="">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="'.TPL_PLUGIN_ASSETS . 'css/ish.css">
    </head>
    <header class="sg-header" role="banner">
      <div class="sg-controls" id="sg-controls">
        <div class="sg-control-content">
          <ul class="sg-control">
            <li class="sg-size">
              <div class="sg-current-size">
                <form id="sg-form">
                  Width <input type="text" class="sg-input sg-size-px" value="320">
                  Height <input type="text" class="sg-input sg-size-height" value="">
                </form>
              </div><!--end #sg-current-size-->
              <ul class="sg-acc-panel sg-size-options">
                <li id="phone" class="dropdown">
                    <a title="Phone" data-toggle="dropdown" class="dropdown-toggle" href="#phone"><i class="fa fa-2x fa-mobile"></i></a>
                    <ul class="dropdown-menu"><li class="dropdown-header">Apple</li>
                      <li class="tdevice"><a data-width="320" data-height="480" href="#">iPhone <small></small><span>3.5"</span></a></li>
                      <li class="tdevice"><a data-width="640" data-height="960" href="#">iPhone 4 <small></small><span>3.5"</span></a></li>
                      <li class="tdevice"><a data-width="640" data-height="1136" href="#">iPhone 5 <small></small><span>4.0"</span></a></li>
                      <li class="divider"></li>
                      <!--
                      <li class="dropdown-header">BlackBerry</li>
                      <li class="tdevice"><a data-width="480" data-height="640" href="#">Bold 9930 <small></small><span>2.8"</span></a></li>
                      <li class="tdevice"><a data-width="720" data-height="720" href="#">Q10 <small></small><span>3.1"</span></a></li>
                      <li class="tdevice"><a data-width="480" data-height="640" href="#">Torch 9810 <small></small><span>3.2"</span></a></li>
                      <li class="tdevice"><a data-width="400" data-height="800" href="#">Torch 9850 <small></small><span>3.7"</span></a></li>
                      <li class="tdevice"><a data-width="1280" data-height="768" href="#">Z10 <small></small><span>4.2"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Samsung</li>
                      <li class="tdevice"><a data-width="240" data-height="320" href="#">Samsung Y <small></small><span>3.0"</span></a></li>
                      <li class="tdevice"><a data-width="480" data-height="800" href="#">Samsung S &amp; S2 <small></small><span>4.5"</span></a></li>
                      <li class="tdevice"><a data-width="720" data-height="1280" href="#">Samsung S3 <small></small><span>4.8"</span></a></li>
                      <li class="tdevice"><a data-width="1080" data-height="1920" href="#">Samsung S4 <small></small><span>5"</span></a></li>
                      <li class="tdevice"></li>
                      <li class="dropdown-header">HTC</li>
                      <li class="tdevice"><a data-width="320" data-height="480" href="#">Desire 200 <small></small><span>3.5"</span></a></li>
                      <li class="tdevice"><a data-width="480" data-height="800" href="#">Desire X <small></small><span>4.0"</span></a></li>
                      <li class="tdevice"><a data-width="480" data-height="800" href="#">Desire SV <small></small><span>4.3"</span></a></li>
                      <li class="tdevice"><a data-width="540" data-height="960" href="#">Sensation <small></small><span>4.5"</span></a></li>
                      <li class="tdevice"><a data-width="1080" data-height="1920" href="#">One <small></small><span>4.7"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">LG</li>
                      <li class="tdevice"><a data-width="320" data-height="480" href="#">Optimus L5 <small></small><span>4.0"</span></a></li>
                      <li class="tdevice"><a data-width="480" data-height="800" href="#">Optimus 3D <small></small><span>4.3"</span></a></li>
                      <li class="tdevice"><a data-width="720" data-height="1280" href="#">Optimus 4X HD <small></small><span>4.7"</span></a></li>
                      <li class="tdevice"><a data-width="1080" data-height="1920" href="#">Optimus G Pro <small></small><span>5.5"</span></a></li>
                      <li class="tdevice"><a data-width="768" data-height="1280" href="#">Nexus 4 <small></small><span>4.7"</span></a></li>
                      -->
                    </ul>
                </li>
                <li id="tablet" class="dropdown">
                    <a title="Tablet" data-toggle="dropdown" class="dropdown-toggle" href="#tablet"><i class="fa fa-2x fa-tablet"></i></a>
                  <ul class="dropdown-menu">
                    <li class="dropdown-header">Apple</li>
                    <li class="tdevice"><a data-width="1024" data-height="768" href="#">iPad Mini <small></small><span>7.9"</span></a></li>
                    <li class="tdevice"><a data-width="1024" data-height="768" href="#">iPad 1 &amp; 2 <small></small><span>9.7"</span></a></li>
                    <li class="tdevice"><a data-width="2048 data-height="1536" href="#">iPad 3 &amp; 4 <small></small><span>9.7"</span></a></li>
                    <li class="divider"></li>
                    <!--
                    <li class="dropdown-header">Amazon</li>
                    <li class="tdevice"><a data-width="1024" data-height="600" href="#">Kindle Fire <small></small><span>7"</span></a></li>
                    <li class="tdevice"><a data-width="1280" data-height="800" href="#">Kindle Fire HD 7 <small></small><span>7"</span></a></li>
                    <li class="tdevice"><a data-width="1920" data-height="1200" href="#">Kindle Fire HD 8.9 <small></small><span>8.9"</span></a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">Asus</li>
                    <li class="tdevice"><a data-width="1280" data-height="800"  href="#">Google Nexus 7 (2012) <small></small><span>7"</span></a></li>
                    <li class="tdevice"><a data-width="1920" data-height="1200" href="#">Google Nexus 7 (2013) <small></small><span>7.02"</span></a></li>
                    <li class="tdevice"><a data-width="1280" data-height="800"  href="#">Transformer Pad TF300 <small></small><span>10.1"</span></a></li>
                    <li class="tdevice"><a data-width="1920" data-height="1200" href="#">Transformer Pad Infinity <small></small><span>10.1"</span></a></li>
                    <li class="tdevice"><a data-width="2560" data-height="1600" href="#">New Transformer Pad Infinity <small></small><span>10.1"</span></a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">Barnes &amp; Noble</li>
                    <li class="tdevice"><a data-width="1024" data-height="600" href="#">Nook <small></small><span>7"</span></a></li>
                    <li class="tdevice"><a data-width="1440" data-height="900" href="#">Nook HD <small></small><span>7"</span></a></li>
                    <li class="tdevice"><a data-width="1920" data-height="1280" href="#">Nook HD+ <small></small><span>9.0"</span></a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">HP</li>
                    <li class="tdevice"><a data-width="1024" data-height="600" href="#">Slate 7 <small></small><span>8.9"</span></a></li>
                    <li class="tdevice"><a data-width="1024" data-height="768" href="#">Touchpad <small></small><span>9.7"</span></a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">Microsoft</li>
                    <li class="tdevice"><a data-width="1366" data-height="768" href="#">Surface RT <small></small><span>10.6"</span></a></li>
                    <li class="tdevice"><a data-width="1920" data-height="1080" href="#">Surface Pro <small></small><span>10.6"</span></a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">Samsung</li>
                    <li class="tdevice"><a data-width="800" data-height="1280" href="#">Galaxy Note <small></small><span>5.29"</span></a></li>
                    <li class="tdevice"><a data-width="720" data-height="1280", 1)" href="#">Galaxy Note 2 <small></small><span>5.55"</span></a></li>
                    <li class="tdevice"><a data-width="1024" data-height="600" href="#">Galaxy Tab 7.0 <small></small><span>7"</span></a></li>
                    <li class="tdevice"><a data-width="1280" data-height="800" href="#">Galaxy Tab 7.7 <small></small><span>7.7"</span></a></li>
                    <li class="tdevice"><a data-width="1280" data-height="800" href="#">Galaxy Tab 8.9 <small></small><span>8.9"</span></a></li>
                    <li class="tdevice"><a data-width="1280" data-height="800" href="#">Galaxy Tab 10.1 <small></small><span>10.1"</span></a></li>
                    <li class="tdevice"><a data-width="2560" data-height="1600" href="#">Google Nexus 10 <small></small><span>10.1"</span></a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">Sony</li>
                    <li class="tdevice"><a data-width="1280" data-height="800" href="#">Xperia Tablet S <small></small><span>9.4"</span></a></li>
                    <li class="tdevice"><a data-width="1920" data-height="1200" href="#">Xperia Tablet Z <small></small><span>10.1"</span></a></li>
                    -->
                  </ul>
                </li>
                <li id="laptop" class="dropdown">
                    <a title="Laptop" data-toggle="dropdown" class="dropdown-toggle" href="#laptop"><i class="fa fa-2x fa-laptop"></i></a>
                    <ul class="dropdown-menu">
                      <li class="dropdown-header">Apple</li>
                      <li class="tdevice"><a data-width="1366" data-height="768" href="#">11\' Macbook Air <small></small><span>11"</span></a></li>
                      <li class="tdevice"><a data-width="1440" data-height="900" href="#">13\' Macbook Air <small></small><span>13"</span></a></li>
                      <li class="tdevice"><a data-width="1440" data-height="900" href="#">15\' Macbook Pro <small></small><span>15"</span></a></li>
                      <li ><a data-width="2880" data-height="1800"  href="#">15\' Macbook Pro Retina <small></small><span>15"</span></a></li>
                      <li class="divider"></li>
                      <!--
                      <li class="dropdown-header">Acer</li>
                      <li class="tdevice"><a data-width="1366" data-height="768" href="#">Aspire M5-583P <small></small><span>15.6"</span></a></li>
                      <li class="tdevice"><a data-width="1920" data-height="1080"  href="#">Aspire V7 <small></small><span>14"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Asus</li>
                      <li class="tdevice"><a data-width="2560" data-height="1440"  href="#">Zenbook Infinity <small></small><span>13.3"</span></a></li>
                      <li class="tdevice"><a data-width="1920" data-height="1080"  href="#">Taichi <small></small><span>13.3"</span></a></li>
                      <li class="tdevice"><a data-width="1366" data-height="768" href="#">VivoBook S400 <small></small><span>14.1"</span></a></li>
                      <li class="tdevice"><a data-width="1600" data-height="900" href="#">G75VW <small></small><span>17.3"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Dell</li>
                      <li class="tdevice"><a data-width="2560" data-height="1440"  href="#">XPS 11 <small></small><span>11.6"</span></a></li>
                      <li class="tdevice"><a data-width="1920" data-height="1080"  href="#">XPS 12 <small></small><span>12.5"</span></a></li>
                      <li class="tdevice"><a data-width="1366" data-height="768" href="#">Inspiron 15R <small></small><span>15.6"</span></a></li>
                      <li class="tdevice"><a data-width="1600" data-height="900" href="#">Inspiron 17R <small></small><span>17.3"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">HP</li>
                      <li ><a data-width="3200" data-height="1800"  href="#">Envy Touchsmart 14 <small></small><span>14"</span></a></li>
                      <li ><a data-width="1366" data-height="768" href="#">Envy 15 <small></small><span>15.6"</span></a></li>
                      <li ><a data-width="1920" data-height="1080"  href="#">Envy 17 <small></small><span>17.3"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Lenovo</li>
                      <li ><a data-width="1600" data-height="900" href="#">ThinkPad T440s <small></small><span>14"</span></a></li>
                      <li ><a data-width="1366" data-height="768" href="#">IdeaPad S500 Touch <small></small><span>15.6"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Sony</li>
                      <li ><a data-width="1920" data-height="1080"  href="#">VAIO Pro 13 <small></small><span>13.3"</span></a></li>
                      <li ><a data-width="1366" data-height="768" href="#">VAIO E Series 14 <small></small><span>14"</span></a></li>
                      <li ><a data-width="1600" data-height="900" href="#">VAIO Fit E 14 <small></small><span>14"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Toshiba</li>
                      <li ><a data-width="2560" data-height="1440"  href="#">Kirabook <small></small><span>13.3"</span></a></li>
                      <li ><a data-width="1366" data-height="768," href="#">Satellite P845t <small></small><span>14"</span></a></li>
                      <li ><a data-width="1920" data-height="1080"  href="#">Qosmio X75 <small></small><span>17.3"</span></a></li>
                      <li ><a data-width="1600" data-height="900" href="#">Qosmio X870 <small></small><span>17.3"</span></a></li>
                      -->
                    </ul>
                </li>
                <li id="desktop" class="dropdown">
                    <a title="Desktop" data-toggle="dropdown" class="dropdown-toggle" href="#desktop"><i class="fa fa-2x fa-desktop"></i></a>
                    <ul class="dropdown-menu"><li  class="dropdown-header">Apple</li>
                      <li ><a data-width="1920" data-height="1080"  href="#">21.5\' iMac <small></small><span>21.5"</span></a></li>
                      <li ><a data-width="2560" data-height="1440"  href="#">27\' iMac <small></small><span>27"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Acer</li>
                      <li class="tdevice"><a data-width="1600" data-height="900" href="#">Aspire ZC-605 <small></small><span>19.5"</span></a></li>
                      <li class="tdevice"><a data-width="1920" data-height="1080"  href="#">Aspire 7600U <small></small><span>27"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Asus</li>
                      <li class="tdevice"><a data-width="1920" data-height="1080"  href="#">ET2311 <small></small><span>23"</span></a></li>
                      <li class="tdevice"><a data-width="2560" data-height="1440"  href="#">ET2702 <small></small><span>27"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Dell</li>
                      <li class="tdevice"><a data-width="1600" data-height="900," href="#">Inspiron One 20 <small></small><span>20"</span></a></li>
                      <li class="tdevice"><a data-width="1920" data-height="1080"  href="#">Inspiron One 23 <small></small><span>23"</span></a></li>
                      <li class="tdevice"><a data-width="1920" data-height="1200"  href="#">XPS One 24 <small></small><span>24"</span></a></li>
                      <li class="tdevice"><a data-width="2560" data-height="1440"  href="#">XPS One 27 <small></small><span>27"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">HP</li>
                      <li ><a data-width="1600" data-height="900," href="#">Envy Rove 20 <small></small><span>20"</span></a></li>
                      <li ><a data-width="1920" data-height="1080"  href="#">Pavilion 23 <small></small><span>23"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Lenovo</li>
                      <li ><a data-width="1920" data-height="1080"  href="#">IdeaCentre Horizon <small></small><span>27"</span></a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Sony</li>
                      <li ><a data-width="1600" data-height="900" href="#">VAIO Tap 20 <small></small><span>20"</span></a></li>
                      <li ><a data-width="1920" data-height="1080"  href="#">VAIO L <small></small><span>24"</span></a></li>
                  </ul>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <body>
    <!-- Iframe -->
    <div id="sg-vp-wrap">
      <div id="sg-cover"></div>
      <div id="sg-gen-container">
        <iframe id="sg-viewport" src="'.$src.'" sandbox="allow-same-origin allow-scripts allow-top-navigation">
        </iframe>
      </div>
    </div>
    <script src="'.TPL_PLUGIN_ASSETS.'js/jquery-2.0.3.min.js"></script>
    <script src="'.TPL_PLUGIN_ASSETS.'js/url-handler.js"></script>
    <script src="'.TPL_PLUGIN_ASSETS.'js/ish_init.js"></script>
    <!--end iFrame-->
    </body></html>';

    file_put_contents($path. DIRECTORY_SEPARATOR . 'ish.html', $html);

    return TPL_PREVIEW_URI . $folder_name . DIRECTORY_SEPARATOR . 'ish.html';
  }
  public function preview_register_script() {
    wp_register_script( 'jquery_tpl', TPL_PLUGIN_ASSETS.'js/jquery-2.0.3.min.js' );
    wp_register_script( 'swiper', TPL_PLUGIN_ASSETS.'js/idangerous.swiper.min.js' );
    wp_register_script( 'swiper_scrollbar', TPL_PLUGIN_ASSETS.'js/idangerous.swiper.scrollbar.js' );
    wp_register_script( 'ish', TPL_PLUGIN_ASSETS.'js/ish_init.js' );
    wp_register_script( 'bootstrap', TPL_PLUGIN_ASSETS.'js/bootstrap.min.js' );
    wp_enqueue_script( 'jquery_tpl');
    wp_enqueue_script( 'swiper');
    wp_enqueue_script( 'swiper_scrollbar');
    wp_enqueue_script( 'bootstrap');
    //wp_enqueue_script( 'ish');
  }

  public function preview_register_styles() {

    wp_register_style( 'preview', TPL_PLUGIN_ASSETS . 'css/preview.css');
    wp_register_style( 'swiper', TPL_PLUGIN_ASSETS . 'css/idangerous.swiper.css');
    wp_register_style( 'swiper_scrollbar', TPL_PLUGIN_ASSETS . 'css/idangerous.swiper.scrollbar.css');
    wp_register_style( 'bootstrap', TPL_PLUGIN_ASSETS . 'css/bootstrap.min.css');
    wp_register_style( 'font-awesome', TPL_PLUGIN_ASSETS . 'css/font-awesome.min.css');
    wp_register_style( 'ish', TPL_PLUGIN_ASSETS . 'css/ish.css');

    wp_enqueue_style( 'preview' );
    wp_enqueue_style( 'swiper' );
    wp_enqueue_style( 'swiper_scrollbar' );
    //wp_enqueue_style( 'ish' );
    wp_enqueue_style( 'font-awesome' );
    wp_enqueue_style( 'bootstrap' );
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

  public function rewrite_url( $html ) {

    if($html) {
      $theme_folder = $this->_theme->get_template_uri( $_GET['edition_id'] ); //get current theme folder
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
      $post_title = TPL_Utils::TPL_parse_string($connected_post->post_title);
      do_action('preview_hook_' . $connected_post->post_type, $connected_post->ID, $post_title, $this->edition_folder);
    }
    return $html_preview;
  }
}
