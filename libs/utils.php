<?php
class TPL_Utils
{
	public static $excluded_files = array( ".", "..", ".git", ".gitignore", ".pressignore", ".DS_Store", "_notes", "Thumbs.db", "__MACOSX" );

	/**
	 * constructor
	 *
	 * @void
	 */
	public function __construct() {}

	/**
	 * Check .pressignore in a directory
	 * @param  string  $dir
	 * @param  string  $file
	 * @return boolean
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
	 *
	 * @param  string  $dir
	 * @param  string  $file
	 * @return boolean
	 */
	public static function is_allowed_files( $dir, $file ) {

		foreach ( self::$excluded_files as $excp ) {
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
	* Search files in a directory
	*
	* @param string $directory
	* @param string $extension
	* @return array
	*/
	public static function search_files( $directory, $extension = '*', $recursive = false, &$out = array() ) {

		try {
			$files = scandir( $directory );
			$files = array_diff( $files, self::$excluded_files );
			foreach ( $files as $file ) {

				if ( is_file( $directory . DIRECTORY_SEPARATOR . $file ) ) {
					if ( strlen( $extension ) && $extension != '*' ) {
						$info = pathinfo( $directory . DIRECTORY_SEPARATOR . $file );
						if ( isset( $info['extension'] ) && strtolower( $info['extension'] ) == strtolower( $extension ) ) {
							array_push( $out, $directory . DIRECTORY_SEPARATOR . $file );
						}
					}
					else {
						array_push( $out, $directory . DIRECTORY_SEPARATOR . $file );
					}
				}
				else if( is_dir( $directory . DIRECTORY_SEPARATOR . $file ) && $recursive ) {
					self::search_files( $directory . DIRECTORY_SEPARATOR . $file, $extension, true, $out );
				}
			}
		}
		catch(Exception $e) {
			error_log( 'Pressroom error: ' . $e->getMessage() );
		}

		return $out;
	}

	/**
	 * Create a new folder
	 *
	 * @param  string $basepath - parent folder
	 * @param  string $dir - new directory
	 * @return string or false - new directory path
	 */
	public static function make_dir( $basepath, $dir ) {

		if ( substr( $basepath, -1 ) === DIRECTORY_SEPARATOR ) {
			$basepath = substr( $basepath, 0, -1 );
		}

		$path = $basepath . DIRECTORY_SEPARATOR . TPL_Utils::sanitize_string( $dir );
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
	 * Remove a directory
	 *
	 * @param string $dir
	 * @void
	 */
	public static function remove_dir( $dir ) {

		if ( is_dir( $dir ) ) {

			$objects = scandir( $dir );

			foreach ( $objects as $object ) {

				if ( $object != "." && $object != ".." ) {

					if ( filetype( $dir . DIRECTORY_SEPARATOR . $object ) == "dir" ) {
						TPL_Utils::remove_dir( $dir . DIRECTORY_SEPARATOR . $object );
					}
					else {
						unlink( $dir . DIRECTORY_SEPARATOR . $object );
					}
				}
			}

			reset( $objects );
			rmdir( $dir );
		}
	}

	/**
	 * Copy files recursively
	 *
	 * @param string  $source_dir
	 * @param string  $destination_dir
	 * @param integer $count
	 * @param array   $not_copied
	 */
	public static function recursive_copy( $source_dir, $destination_dir, &$count = 0, &$not_copied = array() ) {

		try {

			if ( is_writable( dirname( $destination_dir ) ) ) {
				if ( !is_dir( $destination_dir ) ) {
					mkdir( $destination_dir, 0755 );
				}

				$dir = opendir( $source_dir );
				self::$excluded_files = array_merge( self::$excluded_files, self::get_press_ignore( $source_dir ) );

				while ( false !== ( $file = readdir( $dir ) ) ) {

					if ( !self::is_allowed_files( $source_dir, $file ) ) {
						continue;
					}

					$dir_src = $source_dir . DIRECTORY_SEPARATOR . $file;
					$dir_dst = $destination_dir . DIRECTORY_SEPARATOR . $file;

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
	 * Create a zip file
	 *
	 * @param  string $source
	 * @param  string $destination
	 * @param  string $basepath - null to auto create
	 * @return boolean
	 */
	public static function create_zip_file( $source, $destination, $basepath = null ) {

		if ( !extension_loaded( 'zip' ) || !file_exists( $source ) ) {
	        return false;
		}

		$zip = new ZipArchive();
		if ( !$zip->open( $destination, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE ) ) {
			return false;
		}

		$source = str_replace( '\\', '/', realpath( $source ) );
		if ( is_null( $basepath ) ) {
			$basepath = basename( $source ) . '/';
		}

		if ( is_dir( $source ) ) {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
			foreach ( $files as $file ) {

				$info = pathinfo( $file );
				if ( !in_array( $info['basename'], self::$excluded_files ) ) {
					$file = str_replace('\\', '/', realpath( $file ));
	            if ( is_dir( $file ) ) {
						$zip->addEmptyDir( str_replace( $source . '/', '', $basepath . $file . '/' ) );
	            }
	            elseif ( is_file( $file ) ) {
						$zip->addFromString( str_replace( $source . '/', '', $basepath . $file ), file_get_contents( $file ) );
	            }
				}
			}
		}
		elseif ( is_file( $source ) ) {
			$zip->addFromString( $basepath . basename( $source ), file_get_contents( $source ) );
		}

		return $zip->close();
	}

	/**
	 * Extends sanitize wp method replacing latin1 characters
	 *
	 * @param  string $str
	 * @return string
	 */
	public static function sanitize_string($str) {

		$str = sanitize_file_name($str);
		$str = strtolower($str);

		$replacements = array(
			' ' 	=> '-',
			'\'' 	=> '-',
			'"' 	=> '',
			'_'	=> '-',
			'à'	=> 'a',
			'è'	=> 'e',
			'ì'	=> 'i',
			'ò'	=> 'o',
			'ù'	=> 'u',
			'á'	=> 'a',
			'é'	=> 'e',
			'í'	=> 'i',
			'ó'	=> 'o',
			'ú'	=> 'u',
			'ü'	=> 'ue',
			'ä'	=> 'ae',
			'ö'	=> 'oe',
			'ï'	=> 'i',
			'ë'	=> 'e',
			'ß'	=> 'ss',
			'ã'	=> 'a',
			'î'	=> 'i',
			'û'	=> 'u',
			'ñ'	=> 'n',
			'ê'	=> 'e',
			'â'	=> 'a',
		);
		$str = str_replace( array_keys( $replacements ), $replacements, $str);
		$str = preg_replace("/[^a-zA-Z0-9\-\.]/", "", $str);
		$str = trim($str,'-');
		$str = preg_replace("/\-(\-)+/", "-", $str);

		return $str;
	}
}
