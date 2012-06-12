<?php
/**
 * FRESHIZER 
 * =========
 * 
 * Image Resizing Class directly for wordpress. This class is using built-in wordpress functions to resize images and store them into the cache.
 * 100% working :)
 * 
 * @author freshface
 * @version 1.03
 * @link http://www.freshface.net
 * @link http://github.com/boobslover/freshizer
 * @license GNU version 2
 */

class fImg {
// #############################################################################################################################################
// ## EDIT THIS
// #############################################################################################################################################

/**********************************************************************************************************************************************/	
/**********************************************************************************************************************************************/		
	protected static $caching_interval = 86400;				// [seconds] 86400 sec = 24hr
	protected static $enable_caching = true;				// allow caching
/**********************************************************************************************************************************************/
/**********************************************************************************************************************************************/	

	
// #############################################################################################################################################
// ## VARIABLES AND CONSTANTS
// #############################################################################################################################################		
	protected static $upload_dir = null;			// upload directory, here we store all resized images
// #############################################################################################################################################
// ## INITIALIZATION
// #############################################################################################################################################		
	/**
	 * Initialization of all functions for proper work of this class
	 */
	public static function init() {
		// created the wanted directory
		self::createDir();
		// clear the caching folder
		self::clearCache();
	} 	
// #############################################################################################################################################
// ## RESIZING
// #############################################################################################################################################	
	public static function resize( $url, $width, $height = false, $fixed = false) {
		// should we resize img height ?
		//$fixed = false;
		//if( $height ) $fixed = true;
		
		$rel_path = self::getRelativePath($url);
		$img_size = self::getImageSize( $rel_path );
		
		// if something goes wrong, exit
		if( $img_size === false) {
			echo 'Image : '. $url . ' does not exists ! ';
			return false;
		}
		
		// calculate new dimensions ( cut it if there is some dimension bigger than image)
		$new_img_size = self::getNewDimensions($img_size, $width, $height, $fixed);
		// if the new dimensions are same as the old one, return old image
		if( $img_size['width'] == $new_img_size['width'] && $img_size['height'] == $new_img_size['height'] ) {
			return $url;
		}		
		// compute unique img hash
		$hash = self::getImgHash( $rel_path, $img_size );
		
		// get new img path
		$new_img_path = self::getNewImagePath($url, $hash, $new_img_size['width'], $new_img_size['height']);
		
		// resizing!
		if( !file_exists( $new_image_path ) ) {
			self::image_resize($rel_path, $new_img_size['width'], $new_img_size['height'], true, $hash ); //image_resize($relative_path, 200, 200, true, $new_file_path);
		}
		
		$new_img_name = self::getNewImageName($url, $hash, $new_img_size['width'], $new_img_size['height']);
		return self::$upload_dir['baseurl'].'/'.$new_img_name;
	}



	/**
	 * Scale down an image to fit a particular size and save a new copy of the image.
	 *
	 * The PNG transparency will be preserved using the function, as well as the
	 * image type. If the file going in is PNG, then the resized image is going to
	 * be PNG. The only supported image types are PNG, GIF, and JPEG.
	 *
	 * Some functionality requires API to exist, so some PHP version may lose out
	 * support. This is not the fault of WordPress (where functionality is
	 * downgraded, not actual defects), but of your PHP version.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Image file path.
	 * @param int $max_w Maximum width to resize to.
	 * @param int $max_h Maximum height to resize to.
	 * @param bool $crop Optional. Whether to crop image or resize.
	 * @param string $prefix Optional. File prefix, usually hash.
	 * @return mixed WP_Error on failure. String with new destination path.
	 */
	protected static function image_resize( $file, $max_w, $max_h, $crop = false, $prefix = false) { //$suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
		$img_old = self::loadImage( $file );
		
		if( !is_resource($img_old) ) {
			echo 'Error loading image';
			return;
		}
		$img_size = self::getImageSize( $file );
		$new_dim = self::getNewDimensions($img_size, $max_w, $max_h, $crop);
		
		$img_new = imagecreatetruecolor( $new_dim['width'], $new_dim['height']);
		
		imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $new_dim['width'], $new_dim['height'], $img_size['width'], $img_size['height']);
		$hash = self::getImgHash( $file, $img_size );
		
		$path = self::getNewImageName($file, $hash, $new_dim['width'], $new_dim['height']);
		$path = self::$upload_dir['basedir'].'/'.$path;
		
		self::saveImage($img_new, $path);
		imagedestroy($img_new);
		imagedestroy($img_old);
		
		
	/*	$jpeg_quality = 90;
		
		if( $prefix ) {
			$prefix .= '-';
		}
		
		$image = wp_load_image( $file );
	
		if ( !is_resource( $image ) )
			return new WP_Error( 'error_loading_image', $image, $file );
	
		$size = @getimagesize( $file );
		if ( !$size )
			return new WP_Error('invalid_image', __('Could not read image size'), $file);
		list($orig_w, $orig_h, $orig_type) = $size;
	
		$dims = image_resize_dimensions($orig_w, $orig_h, $max_w, $max_h, $crop);
		if ( !$dims )
			return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
		list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;
	
		$newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );
	
		imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	
		// convert from full colors to index colors, like original PNG.
		if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) )
			imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );
	
		// we don't need the original in memory anymore
		imagedestroy( $image );
	
	
		$suffix = "{$dst_w}x{$dst_h}";
	
		$info = pathinfo($file);
		//$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = wp_basename($file, ".$ext");
		
		$destfilename = self::$upload_dir['basedir']."/{$prefix}{$name}-{$suffix}.{$ext}";
		$filename = "/{$prefix}{$name}-{$suffix}.{$ext}";
		if ( IMAGETYPE_GIF == $orig_type ) {
			if ( !imagegif( $newimage, $destfilename ) )
				return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
		} elseif ( IMAGETYPE_PNG == $orig_type ) {
			if ( !imagepng( $newimage, $destfilename ) )
				return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
		} else {
			// all other formats are converted to jpg
			//$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
			$destfilename =  self::$upload_dir['basedir']."/{$prefix}{$name}-{$suffix}.jpg";
			$filename = "/{$prefix}{$name}-{$suffix}.jpg";
			if ( !imagejpeg( $newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality, 'image_resize' ) ) )
				return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
		}
	
		imagedestroy( $newimage );
	
		// Set correct file permissions
		$stat = stat( dirname( $destfilename ));
		$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
		@ chmod( $destfilename, $perms );
	
		return $filename; */
	}
	
	/**
	 * Get Image size, if the image does not exists, return false
	 * 
	 * @param string Relative path to the image
	 * @return bool / string When fail false, other image dimensions
	 */
	protected static function getImageSize( $relative_image_path ) {
		// does the file exists ? If no, return false
		if( !file_exists($relative_image_path) ) {
			return false;
		}
		
		// get image sizes
		$image_size = getimagesize($relative_image_path);
		$image["url"] = $relative_image_path;
		$image['width'] = $image_size[0];
		$image['height'] = $image_size[1];
		
		return $image;
	}	
	
	/**
	 * Get new image dimensions. If its not possible to resize the image, return false
	 * @param array Image Dimension from function GetImageSize
	 * @param int width
	 * @param int height
	 * @param bool fixed height
	 * 
	 * @return array
	 */
	protected static function getNewDimensions( $image_dim, $new_width, $new_height, $fixed_height ) {
		if( $fixed_height == false ) {
			$old_width = $image_dim['width'];
			$old_height = $image_dim['height'];
			var_dump( $fixed_height );
			$width_ratio = $height_ratio = 1;
			
			if( $old_width > $new_width ) {
				$width_ratio = $new_width / $old_width;
			}
			if( $old_height > $new_height ) {
				$height_ratio = $new_height / $old_height;
			}
			
			$smaller_ratio = min( $width_ratio, $height_ratio);
			$bigger_ratio = max( $width_ratio, $height_ratio );
			
			$to_return['width'] = $new_width;
			$to_return['height'] =round( $old_height * $width_ratio );  
		} else {
			$to_return['width'] = $new_width;
			$to_return['height'] = $new_height;
		}
		return $to_return;
		// fixed height mean that we want to have exactly same dimensions
		/*if( $fixed_height ) {
			$wanted_width = $width;
			$wanted_height = $height;
		
		// we want to adjust the image width and height
		} else {
			$wanted_size = wp_constrain_dimensions( $image_dim['width'], $image_dim['height'], $width);
			$wanted_width = $wanted_size[0];
			$wanted_height = $wanted_size[1];
		}
		
		// if wanted width is higher than the actual image size, cut it back
		if(  $wanted_width > $image_dim['width']) {
			$wanted_width = $image_dim['width'];
		}
		// same with height
		if(  $wanted_height > $image_dim['height'] ) {
			$wanted_height = $image_dim['height'];
		}		
			
		
		$to_return = array();
		$to_return['width'] = $wanted_width;
		$to_return['height'] = $wanted_height;
		
		return $to_return;*/

	}	
	
	
// #############################################################################################################################################
// ## IMAGE LOADING AND SAVING
// #############################################################################################################################################		
	/**
	 * Decide which image type it is and load it
	 * 
	 * @param string path Path to the image
	 * @return resource Image Resource ID
	 */
	protected static function loadImage( $path ) {
		$pinfo = pathinfo( $path );
		$ext = $pinfo['extension'];
		$img = null;
		
		switch( $ext ) {
			case 'jpg':
				$img = imagecreatefromjpeg( $path );
				
				break;
			case 'jpeg':
				$img = imagecreatefromjpeg( $path );
				break;	
			case 'png':
				$img = imagecreatefrompng( $path );
				break;
			
			case 'gif':
				$img = imagecreatefromgif( $path );
				break;
		}
		
		return $img;
	}	
	/**
	 * Decide which image type it is and save it
	 * 
	 * @param resource $image Image resource
	 * @param string $path Path to the image
	 */
	protected static function saveImage( $image, $path ) {
		$pinfo = pathinfo( $path );
		$ext = $pinfo['extension'];
		$return = null;
		
		switch( $ext ) {
			case 'jpg':
				$return = imagejpeg($image, $path );
				break;
			case 'jpeg':
				$return = imagejpeg($image, $path );
				break;	
			case 'png':
				$return = imagepng( $image, $path );
				break;
			
			case 'gif':
				$return = imagegif( $image, $path );
				break;
		}		

		return $return;
		
	}	
// #############################################################################################################################################
// ## PATH HUSTLE
// #############################################################################################################################################	
	/**
	 * Get relative image path, important for PHP opening functions
	 * @param string Url = image url
	 * @return string Relative Image Path;
	 */
	protected static function getRelativePath( $url ) {
		$rel_path = str_replace( $_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'], $url);
		$rel_path = str_replace( 'http://','', $rel_path);
		
		return $rel_path;
	}	
	/** 
	 * Get simple hash created from first and last letters from each folder of the image location - for unique identify every image
	 * 
	 * @return int Hash
	 */
	protected static function getImgHash( $path, $img_size ) {
		$file_size = filesize( $path );
		$hash = $file_size + $img_size['width'] + $img_size['height'];
		return $hash;
	}
	
	/**
	 * Get new image absolute path ( with hash, prefix and other ) to check if the image already exists.
	 * 
	 * @param string $url Url pointing to the image
	 * @param string $hash Hash from custom hashing function
	 * @param int $width Width of the image
	 * @param int $height Height of the image
	 * 
	 * @return string
	 */
	public static function getNewImagePath ( $url, $hash, $width, $height ) {
		$filename = self::getNewImageName($url, $hash, $width, $height);
		$filepath = self::$upload_dir['basedir']."/{$filename}";
		return $filepath;
	}	
	
	/**
	 * Get new image name ( with hash, prefix and other ) to check if the image already exists.
	 * 
	 * @param string $url Url pointing to the image
	 * @param string $hash Hash from custom hashing function
	 * @param int $width Width of the image
	 * @param int $height Height of the image
	 * 
	 * @return string
	 */	
	protected static function getNewImageName( $url, $hash, $width, $height ) {
		$pinfo = pathinfo( $url );
		
		$filename = $pinfo['filename'];
		$ext = $pinfo['extension'];
		$hash .= '-';

		$suffix = "{$width}x{$height}";
		
		$filepath = "{$hash}{$filename}-{$suffix}.{$ext}";
		return $filepath;
	}
	
// #############################################################################################################################################
// ## CACHE & DIRECTORY MANAGING
// #############################################################################################################################################	
	/**
	 * Check if upload directory exists, if not, create it. Then load the directories into local variables
	 */
	protected static function createDir() {
		self::$upload_dir = wp_upload_dir();
		self::$upload_dir['basedir'] .= '/freshizer';
		self::$upload_dir['baseurl'] .='/freshizer';
		// if this directory does not exists, create it;
		if( !is_dir(self::$upload_dir['basedir']))
			mkdir( self::$upload_dir['basedir']);
	}
	
	/**
	 * List all the images in the cache folder, and delete the expired images.
	 */
	protected static function clearCache() {
		if( self::$enable_caching == false ) return;
		// default timeout is one day :)
		$timeout = self::$caching_interval;
				
		// get all images in the folder and delete the expired images :)		
		$list_of_images = self::readCacheFolder();
		foreach($list_of_images as $one_image ) {
			if ( getimagesize( $one_image['path']) === false )	continue;
			
			$expiring_time = $one_image['time'] + $timeout;
			// we have to delete this shit :)
			if( $expiring_time < time() ) {
				unlink( $one_image['path'] );
			}
		}

	}
	
	/**
	 * Go through the whole img store folder and read all files.
	 */
	protected static function readCacheFolder() {
		$list_of_elements = array();				// we will be returning this
		$path = self::$upload_dir['basedir'] . '/';
		// go through all elements in the folder and store them in the array
		if ( is_dir( $path ) ) {		
		    if ( $dh = opendir( $path ) ) {
		        while ( ( $file = readdir($dh) ) !== false) {
		        	if( $file == '.' || $file == '..')	continue;

		        	$filetype = filetype( $path . $file );
					if( $filetype == 'file' ) {
						// store info about element into array, so we dont need to call filetype function again
						$one_element = array( 'path' => $path.$file, 'type' => $filetype, 'time'=>filemtime($path.$file) );
						$list_of_elements[] = $one_element;
					}
					
		        		
				}
		        closedir($dh);
		    }
		}
		// sort the array A-Z
		sort($list_of_elements);
		// return sorted array
		return $list_of_elements;
	}
}
fImg::init();
// #############################################################################################################################################
// ## WRAPPERS
// #############################################################################################################################################
function fs_resize( $url, $width, $height = false, $fixed = false ) {
	fImg::resize($url, $width, $height, $fixed);
}


