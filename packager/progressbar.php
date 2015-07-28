<?php
class ProgressBar {

  protected $percentDone = 0;
  protected $pbid;
  protected $pbarid;
  protected $textid;
  protected $decimals = 1;

  public function __construct( $percentDone = 0 ) {

    $this->pbid = 'pb';
    $this->pbarid = 'progress-bar';
    $this->textid = 'pb_text';
    $this->pprogressid = 'progress-percentage';
    $this->percentDone = $percentDone;
  }

  public function render() {
    echo $this->_getContent();
    $this->_flush();
    sleep(1);
  }

  public function setProgress( $percentDone, $text = '' ) {

    $this->percentDone = $percentDone;
    $percentage = number_format( $this->percentDone, $this->decimals, '.', '' ).'%';
    echo '<script type="text/javascript">
    if ( document.getElementById("'.$this->pbarid.'") ) {
      document.getElementById("'.$this->pbarid.'").style.width = "'.$percentDone.'%";
      document.getElementById("'.$this->pprogressid.'").innerHTML = "'.htmlspecialchars( $percentage ).'";';

    if ( $percentDone == 100 ) {
      echo 'document.getElementById("'.$this->pbarid.'").style.background = "#7DC42A";';
      echo 'document.getElementById("'.$this->pbarid.'").className = document.getElementById("'.$this->pbarid.'").className + " complete";';
    }
    elseif ( $percentDone >= 75 ) {
      echo 'document.getElementById("'.$this->pbarid.'").style.background = "#f2d31b";';
    }
    elseif ( $percentDone >= 50 ) {
      echo 'document.getElementById("'.$this->pbarid.'").style.background = "#f2b01e";';
    }
    elseif ( $percentDone >= 25 ) {
      echo 'document.getElementById("'.$this->pbarid.'").style.background = "#f27011";';
    }
    else {
      echo 'document.getElementById("'.$this->pbarid.'").style.background = "#f63a0f";';
    }

    if ($text) {
      echo 'document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars( $text ).'";';
    }
    echo '}</script>'."\n";
    $this->_flush();
  }

  protected function _getContent() {

    $this->percentDone = floatval($this->percentDone);
    $percentDone = number_format($this->percentDone, $this->decimals, '.', '') .'%';
    $content = '<div id="'.$this->pbid.'" class="pb_container">
    <div id="'.$this->textid.'" class="'.$this->textid.'"></div>
    <div class="progress">
      <div id="'.$this->pprogressid.'" class="'.$this->pprogressid.'">'.$percentDone.'</div>
      <div id="'.$this->pbarid.'" class="progress-bar" style="width: '.$percentDone.';"></div>
    </div>
    <br>
    </div>
    <style style="text/css">
      .pb_container {
        position: relative;
        font: 13px/20px Tahoma, Verdana, sans-serif;
        color: #404040;
        width: 100%;
      }

      .progress {
        background-color: #f5f5f5;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) inset;
        height: 20px;
        overflow: hidden;
      }

      .progress-bar {
        position: relative;
        height: 19px;
        -webkit-transition: 0.4s linear;
        -moz-transition: 0.4s linear;
        -o-transition: 0.4s linear;
        transition: 0.4s linear;
        -webkit-transition-property: width, background-color;
        -moz-transition-property: width, background-color;
        -o-transition-property: width, background-color;
        transition-property: width, background-color;
        -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.25), inset 0 1px rgba(255, 255, 255, 0.1);
        box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.25), inset 0 1px rgba(255, 255, 255, 0.1);

      }

      .progress-bar:before, .progress-bar:after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
      }

      .progress-bar:before {
        bottom: 0;
        background-image: -webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
        background-image: -o-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
        background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
        -webkit-background-size: 40px 40px;
        background-size: 40px 40px;

        -webkit-animation: progress-bar-stripes 2s linear infinite;
        -o-animation: progress-bar-stripes 2s linear infinite;
        animation: progress-bar-stripes 2s linear infinite;
      }

      .progress-bar:after {
        z-index: 2;
        bottom: 45%;
        border-radius: 4px;
      }

      @keyframes progress-bar-stripes{
        from{background-position:40px 0}
        to{background-position:0 0}
      }

      @-webkit-keyframes progress-bar-stripes{
        from{background-position:40px 0}
        to{background-position:0 0}
      }

      .pb_text {
        font-weight:300;
        color: #333;
      }

      .progress-percentage {
        width: 60px;
        color: #fff;
        font-weight:bold;
        position: absolute;
        left: 50%;
        margin-left: -30px;
        top: 20px;
        z-index: 10;
      }

      #progress-bar.complete:before {
        background: #7DC42A;
      }

    </style>'."\r\n";
    return $content;
  }

  protected function _flush() {
    echo str_pad('', intval(ini_get('output_buffering')))."\n";
    //ob_end_flush();
    flush();
    usleep(1000000*0.1);
  }

}
