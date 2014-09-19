<?php

class Tpl_Preview {
  public function __construct() {

  }

  public function run() {
    $packager = new TPL_Packager();
    $preview_frame = $packager->package_preview();
    $html_preview = file_get_contents($preview_frame);
    echo $html_preview;
  }
}
