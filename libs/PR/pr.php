<?php
$files = PR_Utils::search_files( __DIR__ , 'php', true );
if ( !empty( $files ) ) {
  foreach ( $files as $file ) {
    require_once $file;
  }
}
