<?php
class TPL_Utils
{
	private static $_excluded = array( ".", "..", ".git" , ".DS_Store", "_notes", "Thumbs.db" );

	public function __construct() {}

	/**
	 * readDirectory function.
	 *
	 * @access public
	 * @param mixed $directory
	 * @param bool $recursive (default: true)
	 * @return array
	 */
	public static function read_themes() {

		$themes = array();
		if ( is_dir( TPL_THEME_PATH ) === false ) {
			return false;
		}

		try {
			$resource = opendir( TPL_THEME_PATH );
			while ( false !== ( $file = readdir( $resource ) ) ) {

				if ( in_array( $file, self::$_excluded) )	{
					continue;
				}

				$theme = TPL_THEME_PATH . $file;
				if ( is_dir( $theme ) ) {
					array_push( $themes, $theme );
				}
			}

			return $themes;

		} catch(Exception $e) {
			error_log( 'Caught exception: ', $e->getMessage(), "\n", 0);
			return false;
		}
	}

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
	 * readFiles function.
	 *
	 * @access public
	 * @static
	 * @param mixed $directory
	 * @return array
	 */
	public static function readFiles($directory) {
		try {
			$files = scandir($directory);
			$bad = array(".", "..", ".DS_Store", "_notes", "Thumbs.db");
			$files = array_diff($files, $bad);
		}
		catch(Exception $e) {
			return false;
		}
		return $files;
	}

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

	public static function TPL_parse_string($str) {
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

		$path = $basepath . DIRECTORY_SEPARATOR . TPL_Utils::TPL_parse_string( $dir );
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

	public static function TPL_recursive_copy($src, $dst, &$count = 0, &$not_copied = array()) {
			try {
				$dir = opendir($src);
				if(is_writable(dirname($dst))){
					if(!is_dir($dst)){
						mkdir($dst);
					}
					while(false !== ( $file = readdir($dir)) ) {
							if (( $file != '.' ) && ( $file != '..' )) {
									$dir_src = $src . DIRECTORY_SEPARATOR . $file;
									$dir_dst = $dst . DIRECTORY_SEPARATOR . $file;
									if ( is_dir($dir_src) ) {
											self::TPL_recursive_copy($dir_src, $dir_dst, $count, $not_copied);
									}
									else {
											if(copy($dir_src, $dir_dst)){
												$count++;
											}
											else {
												$not_copied[] = $file;
											}
									}
							}
					}
					closedir($dir);
					if($not_copied) {
						return $not_copied;
					}
					else {
						return $count;
					}
				}
			}
	    catch(Exception $e){
				error_log( 'Caught exception: ',  $e->getMessage(), "\n",0);
			}

}

	public static function TPL_getUrls($string) {
		$regex = '/https?\:\/\/[^\" ]+/i';
		preg_match_all($regex, $string, $matches);
		return ($matches[0]);
	}


	public static function zipFile($source, $destination, $flag = '')	{
	    if (!extension_loaded('zip') || !file_exists($source)) {
	        return false;
	    }
	    $zip = new ZipArchive();
	    if (!$zip->open($destination, ZIPARCHIVE::OVERWRITE)) {
	        return false;
	    }
	    $source = str_replace('\\', '/', realpath($source));
	    if($flag){
	        $flag = basename($source) . '/';
	    }
	    if (is_dir($source) === true) {
	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
					//$files = self::readDirectory($source, true);
					$bad = array(".", "..", ".DS_Store", "_notes", "Thumbs.db", "__MACOSX");
	        foreach ($files as $file) {
						$filename = pathinfo($file);
						if(!in_array($filename['filename'], $bad)) {
	            $file = str_replace('\\', '/', realpath($file));
	            if (is_dir($file) === true) {
	                $zip->addEmptyDir(str_replace($source . '/', '', $flag.$file . '/'));
	            }
	            else if (is_file($file) === true) {
	                $zip->addFromString(str_replace($source . '/', '', $flag.$file), file_get_contents($file));
	            }
						}
	        }
	    }
	    else if (is_file($source) === true) {
	        $zip->addFromString($flag.basename($source), file_get_contents($source));
	    }

	    return $zip->close();
	}
}
