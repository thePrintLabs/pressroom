<?php
class TPL_Utils
{
	protected static $_excluded_files = array( ".", "..", ".git", ".gitignore", ".pressignore", ".DS_Store", "_notes", "Thumbs.db", "__MACOSX" );

	public function __construct() {}

	// public static function readDirectory($directory, $recursive = true, $found = array()) {
	// 	$bad = array(".", "..", ".DS_Store", "_notes", "Thumbs.db");
	// 	if(is_dir($directory) === false) {
	// 		return false;
	// 	}
	// 	try {
	// 		$resource = opendir($directory);
	// 		while(false !== ($item = readdir($resource))) {
	// 			if(in_array($item, $bad)) {
	// 					continue;
	// 			}
	// 			$item_dir = $directory . DIRECTORY_SEPARATOR . $item;
	// 			if($recursive === true && is_dir($item_dir)) {
	// 				echo "aaa".$item_dir;
	// 				$found[] = self::readDirectory($item_dir, true, $found);
	// 			}
	// 			else {
	// 				$found[] = $item_dir;
	// 			}
	// 		}
	// 	}
	// 	catch(Exception $e) {
	// 		error_log( 'Caught exception: ',  $e->getMessage(), "\n",0);
	// 		return false;
	// 	}
	// 	return $found;
	// }


	/**
	 * Read directory files
	 * @param string $directory
	 * @return array
	 */
	/*
	public static function read_files( $directory ) {

		try {
			$files = scandir( $directory );
			$files = array_diff( $files, self::$_excluded_files );
		}
		catch(Exception $e) {
			return false;
		}
		return $files;
	}
	*/

	public static function get_press_ignore( $dir ) {

		$entries = array();
		if ( substr( $dir, -1 ) === DIRECTORY_SEPARATOR ) {
			$dir = substr( $dir, 0, -1 );
		}

		if ( file_exists( $dir . DIRECTORY_SEPARATOR . '.pressignore' ) ) {
			$entries = file( $dir . DIRECTORY_SEPARATOR . '.pressignore' );
		}

		return $entries;
	}

	/**
	 * Check if a file is allowed in context
	 * @param  string  $dir
	 * @param  string  $file
	 * @return boolean
	 */
	public static function is_allowed_files( $dir, $file ) {

		foreach ( self::$_excluded_files as $excp ) {
			$excp = trim( $excp );
			if ( strpos( $excp, '*' ) === false ) {
				if ( $file == $excp || $file . '/' == $excp || '/' . $file == $excp || '/' . $file. '/' == $excp ) {
					return false;
				}
			} else {
				$regex = str_replace( '*', '[_a-z0-9-]+(\.[_a-z0-9-]+)*', $excp );
				if ( preg_match( '/' . $regex . '/', $file ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	* Read php files into a directory
	* @param string $directory
	* @return array
	*/
	public static function read_php_files( $directory ) {

		try {
			$out = array();
			$files = scandir( $directory );
			$files = array_diff( $files, self::$_excluded_files );
			foreach ( $files as $file ) {
				if ( is_file( $directory . DIRECTORY_SEPARATOR . $file ) ) {
					$info = pathinfo( $directory . DIRECTORY_SEPARATOR . $file );
					if ( $info['extension'] == 'php' ) {
						array_push( $out, $file );
					}
				}
			}
			return $out;
		}
		catch(Exception $e) {
			error_log( 'Caught exception: ',  $e->getMessage(), "\n",0);
			return false;
		}
	}

	/*
	public static function read_Html_Files($directory) {
		try {
			$files = array();
			$scans = scandir($directory);
			$html = array('html');
			foreach($scans as $scan) {
				if(is_file($directory. DIRECTORY_SEPARATOR . $scan)){
					$scan = pathinfo($directory. DIRECTORY_SEPARATOR . $scan);
					if(in_array($scan['extension'], $html)){
						$files[] = $scan['filename'];
					}
				}
			}
		}
		catch(Exception $e) {
			error_log( 'Caught exception: ',  $e->getMessage(), "\n",0);
			return false;
		}
		return $files;
	}
	*/

	public static function parse_string($str) {
		$str = sanitize_file_name($str);
		$str = strtolower($str);
		$str = str_replace(" ","-",$str);
		$str = str_replace("'","-",$str);
		$str = str_replace("\"","",$str);
		$str = str_replace("_","-",$str);
		$str = str_replace("à","a",$str);
		$str = str_replace("è","e",$str);
		$str = str_replace("ì","i",$str);
		$str = str_replace("ò","o",$str);
		$str = str_replace("ù","u",$str);
		$str = str_replace("á","a",$str);
		$str = str_replace("é","e",$str);
		$str = str_replace("í","i",$str);
		$str = str_replace("ó","o",$str);
		$str = str_replace("ú","u",$str);
		$str = str_replace("ü","ue",$str);
		$str = str_replace("ä","ae",$str);
		$str = str_replace("ö","oe",$str);
		$str = str_replace("ï","i",$str);
		$str = str_replace("ë","e",$str);
		$str = str_replace("ß","ss",$str);
		$str = str_replace("ã","a",$str);
		$str = str_replace("î","i",$str);
		$str = str_replace("û","u",$str);
		$str = str_replace("ñ","n",$str);
		$str = str_replace("ê","e",$str);
		$str = str_replace("â","a",$str);
		$str = preg_replace("/[^a-zA-Z0-9\-\.]/", "", $str);
		$str = trim($str,'-');
		$str = preg_replace("/\-(\-)+/", "-", $str);
		return $str;
	}

	/**
	 * Create a new folder
	 * @param  string $basepath - parent folder
	 * @param  string $dir - new directory
	 * @return string or false - new directory path
	 */
	public static function make_dir( $basepath, $dir ) {

		if ( substr( $basepath, -1 ) === DIRECTORY_SEPARATOR ) {
			$basepath = substr( $basepath, 0, -1 );
		}

		$path = $basepath . DIRECTORY_SEPARATOR . TPL_Utils::parse_string( $dir );
		if ( is_dir( $path ) ) {
			return $path;
		}

		if ( is_writable( $basepath ) ) {
			if ( mkdir( $path, 0755 ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Copy files recursively
	 * @param string  $source_folder
	 * @param string  $destination_folder
	 * @param integer $count
	 * @param array   $not_copied
	 */
	public static function recursive_copy( $source_folder, $destination_folder, &$count = 0, &$not_copied = array() ) {

		try {

			if ( is_writable( dirname( $destination_folder ) ) ) {
				if ( !is_dir( $destination_folder ) ) {
					mkdir( $destination_folder, 0755 );
				}

				$dir = opendir( $source_folder );
				self::$_excluded_files = array_merge( self::$_excluded_files, self::get_press_ignore( $source_folder ) );

				while ( false !== ( $file = readdir( $dir ) ) ) {

					if ( !self::is_allowed_files( $source_folder, $file ) ) {
						continue;
					}

					$dir_src = $source_folder . DIRECTORY_SEPARATOR . $file;
					$dir_dst = $destination_folder . DIRECTORY_SEPARATOR . $file;

					if ( is_dir( $dir_src ) ) {
						self::recursive_copy( $dir_src, $dir_dst, $count, $not_copied );
					}
					else {
						if ( copy( $dir_src, $dir_dst ) ) {
							$count++;
						}
						else {
							array_push( $not_copied, $file );
						}
					}
				}
				closedir($dir);

				if ( !empty( $not_copied ) ) {
					return $not_copied;
				}
				else {
					return $count;
				}
			}
		}
		catch(Exception $e){
			error_log( 'Caught exception: ',  $e->getMessage(), "\n",0);
			return false;
		}
	}

	/**
	 * Extracts all url from an html string
	 * @param string $string
	 */
	public static function get_urls($string) {

		$regex = '/https?\:\/\/[^\" ]+/i';
		preg_match_all( $regex, $string, $matches );
		return $matches[0];
	}

	/**
	 * Create a zip file
	 * @param  string $source
	 * @param  string $destination
	 * @param  string $flag
	 * @return boolean
	 */
	public static function create_zip_file( $source, $destination, $flag = false ) {

		if ( !extension_loaded( 'zip' ) || !file_exists( $source ) ) {
	        return false;
		}

		$zip = new ZipArchive();
		if ( !$zip->open( $destination, ZIPARCHIVE::OVERWRITE ) ) {
			return false;
		}

		$source = str_replace( '\\', '/', realpath( $source ) );
		if ( !$flag ) {
			$flag = basename( $source ) . '/';
		}

		if ( is_dir( $source ) ) {

			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
			foreach ( $files as $file ) {

				$info = pathinfo( $file );
				if ( !in_array( $info['filename'], self::$_excluded_files ) ) {
					$file = str_replace('\\', '/', realpath( $file ));
	            if ( is_dir( $file ) ) {
						$zip->addEmptyDir( str_replace( $source . '/', '', $flag . $file . '/' ) );
	            }
	            elseif ( is_file( $file ) ) {
						$zip->addFromString( str_replace( $source . '/', '', $flag . $file ), file_get_contents( $file ) );
	            }
				}
			}
		}
		elseif ( is_file( $source ) ) {
			$zip->addFromString( $flag . basename( $source ), file_get_contents( $source ) );
		}

		return $zip->close();
	}
}
