<?php
/*******************************************************************************
 * FRESHIZER -> WordPress Image Resizer Script
 * =============================================================================
 * 
 * @license GNU version 2
 * @author freshface
 * @version 2.1
 * @link http://github.com/boobslover/freshizer
/*******************************************************************************
 * SETTINGS, PLEASE CHANGE ONLY THESE 3 CONSTANTS
 ******************************************************************************/
// NOTE
// ====
// please notice, that the time is in SECONDS. There are not allowed math
// operations in the definition. So instead of writing:
// = 60(sec) * 60(min) * 24(hr) * 7(days); you have to write:
// = 604800; // seconds in 7 days

// CACHE TIME
// ==========
// When the new (cached) file is older than this time, script automatically
// checks, if the old file has been changed. If not, then ve serve cached file
// again. If yes, cached file is deleted and resized again.
define('CACHE_TIME', 604800);

// CACHE DELETE FILES AFTER
// ========================
// Hard delete files ( not only compare if the original file has been changed,
// but hardly delete from caching folder ), every X seconds. Please fill a large
// number, because cached files runs much more speedy
define('CACHE_DELETE_FILES_AFTER', 10000000);

// CACHE DELETE FILES - check every X hits
// =======================================
// How often do we check if there are files which should be hard deleted ?
// Optimal is approx 400 - 500 hits
define('CACHE_DELETE_FILES_check_every_x_hits', 150);

class blFile {
	CONST POINTER_END = 'pend';
	
	private $_handle = null;
	private $_fileSize = null;
	private $_path = null;
	private $_writeBuffer = '';

/*----------------------------------------------------------------------------*/
/* FUNCTIONS PUBLIC
/*----------------------------------------------------------------------------*/

	public function __construct( $handle, $path ) {
		$this->_setHandle( $handle );
		$this->_setPath( $path );
	}

	public function readAll() {
		if( $this->getFileSize() > 0 )
			return (fread( $this->getHandle(), $this->getFileSize() ));
		else 
			return null;
	}
	public function read( $size ) {
		if( $this->getFileSize() > 0 )
			return fread( $this->getHandle(), $size );
		else
			return null;
	}
	public function readAllAndClose() {
		$fileContent = $this->readAll();
		$this->closeFile();

		return $fileContent;
	}
	
	/**
	 * @param string $content
	 * @return blFile
	 */
	public function write( $content ) {
		fwrite( $this->getHandle(), ( $content ) );
		return $this;
	}
	
	public function writeBuffered( $content ) {
		$this->_setWriteBuffer( $content );
		return $this;
	}
	
	/*public function __destruct() {
		var_dump($this);
		if( $this->_writeBuffer != '' ) {
			$this->write( $this->_writeBuffer ) ;
		}
		
		$this->closeFile();
	}*/
	
	/**
	 * @return blFile
	 */
	public function truncate() {
		ftruncate( $this->getHandle(), 0);
		$this->pointerStart();
		return $this;
	}

	public function closeFile() {
		if( $this->getHandle() !== null ) {
			fclose( $this->getHandle() );
			$this->_setHandle(null);
		}
	}
	
	/**
	 * @return blFile
	 */
	public function pointerStart() {
		$this->_movePointer( 0 );
		return $this;
	}
	
	/**
	 * @return blFile
	 */
	public function pointerEnd() {
		$this->_movePointer( self::POINTER_END );
		return $this;
	}
	
	/**
	 * @param int $where
	 * @return blFile
	 */
	public function pointerTo( $where ) {
		return $this->_movePointer( $where );
		return $this;
	}

	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS PRIVATE
/*----------------------------------------------------------------------------*/
	private function _movePointer( $where ) { 
		if( $where == self::POINTER_END ) {
			fseek( $this->getHandle(), 0, SEEK_END);
		} else {
			fseek( $this->getHandle(), $where, SEEK_SET);
		}
	}
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/

	private function _setWriteBuffer( $content ) {
		$this->_writeBuffer = $content;
	}
	
	private function _getWriteBuffer() {
		return $this->_writeBuffer;
	}
	
	public function getHandle() {
		return $this->_handle;
	}

	private function _setHandle( $handle ) {
		$this->_handle = $handle;
	}

	public function getPath() {
		return $this->_path;
	}

	private function _setPath( $path ) {
		$this->_path = $path;
	}

	public function getFileSize() {
		if( $this->_fileSize == null ) {
			$this->_fileSize = filesize( $this->getPath() );
		}

		return $this->_fileSize;
	}
}


class blFileSystem {
	private $_errors = array();
/*----------------------------------------------------------------------------*/
/* FUNCTIONS PUBLIC
/*----------------------------------------------------------------------------*/	
	
	/**
	 * Trying to open file. If neccessary, creates dir and file automatically 
	 * 
	 * @param string $path
	 * @param bool $writing
	 * @return blFile
	 */
	public function openFile( $path, $writing = false ) {
	if( file_exists( $path ) ) {
			$mode = ( $writing ) ? 'r+' : 'r';
		} else {
			$mode = ( $writing ) ? 'c+' : 'c';
		}
		return $this->_openFile( $path, $mode );
	}
	
	/**
	 * Open file, if exists, truncate
	 * @param string $path
	 * @param bool $writing
	 * @return blFile
	 */
	public function createFile( $path, $writing = false ) {
		$mode = ( $writing ) ? 'c+' : 'c';
		return $this->_openFile( $path, $mode );	
	}
	
	public function deleteFile( $path ) {}
	
	public function createDir( $path ) {
		if( mkdir( $path, 0777, true ) === false ) {
			$this->_addError( 'Unable to create DIR :'. $path );
		}
	}
	
	public function saveImage( $image, $path ) {
		$pinfo = pathinfo( $path );
		$ext = $pinfo['extension'];
		$return = null;
	
		switch( $ext ) {
			case 'jpg':
				$return = imagejpeg($image, $path, 90 );
				break;
			case 'jpeg':
				$return = imagejpeg($image, $path, 90 );
				break;
			case 'png':
				$return = imagepng( $image, $path, 0 );
				break;
					
			case 'gif':
				$return = imagegif( $image, $path );
				break;
		}
	
		return $return;
	
	}	

/*----------------------------------------------------------------------------*/
/* FUNCTIONS PRIVATE
/*----------------------------------------------------------------------------*/	
	
	private function _openFile( $path, $mode ) {
		$pathInfo = pathinfo( $path );
		$dirname = $pathInfo['dirname'];
		$file = null;
		
		if( !is_dir( $dirname ) ) {
			$this->createDir( $dirname );
		}
		
		$fileHandler = fopen( $path, $mode);
		$file = new blFile( $fileHandler, $path);
	
		return $file;
	}

/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	public function getErorrs() {
		return $this->_errors;
	}
	
	private function _addError( $error ) {
		$this->_errors[] = $error;
	}
	
}

interface blIConnection {
	public function getContent( $url );
}

class blConnectionAdapteur implements blIConnection {
	/**
	 * @var blIConnection
	 */
	private $_connectionMethod = null;
	
/*----------------------------------------------------------------------------*/
/* PUBLIC FUNCTIONS
/*----------------------------------------------------------------------------*/	
	
	public function getContent( $url ) {
		return $this->_getConnectionMethod()->getContent($url);
	}

/*----------------------------------------------------------------------------*/
/* PRIVATE FUNCTIONS
/*----------------------------------------------------------------------------*/	
	
	private function _createProperConnection() {
		if( ini_get('allow_url_fopen') ) {
			$this->_setConnectionMethod( new blConnectionFopen() );
		} else if( function_exists( 'curl_init') ) {
			$this->_setConnectionMethod( new blConnectionCurl() );	
		}
	}
	
/*----------------------------------------------------------------------------*/
/* GETTERS AND SETTERS
/*----------------------------------------------------------------------------*/
	
	private function _setConnectionMethod( blIConnection $connectionMethod ) {
		$this->_connectionMethod = $connectionMethod;
	}
	
	/**
	 * @return blIConnection
	 */
	private function _getConnectionMethod() {
		if( $this->_connectionMethod == null ) {
			$this->_createProperConnection();
		}
		return $this->_connectionMethod;
	}
}


class blConnectionCurl implements blIConnection {
	public function getContent( $url ) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;		
	}
}


class blConnectionFopen implements blIConnection {
	public function getContent( $url ) {
		
		$handle = fopen( $url, 'rb' );
		$fileContent = '';
		if( $handle !== false ) {
			while (!feof($handle)) {
				$fileContent .= fread($handle, 8192);
			}
			fclose( $handle );
		}
		return $fileContent;
	}
}


class blDownloader {
	/**
	 * @var blIConnection
	 */
	private $_connectionMethod = null;
	
	public function getContent( $url ) {
		return $this->_getConnectionMethod()->getContent($url);
               
	}
	/**
         * 
         * @param blIConnection $connectionMethod
         */
	private function _setConnectionMethod( blIConnection $connectionMethod ) {
		$this->_connectionMethod = $connectionMethod;
	}
	
	/**
	 * @return blIConnection
	 */
	private function _getConnectionMethod() {
		if( $this->_connectionMethod == null ) {
			$this->_setConnectionMethod( new blConnectionAdapteur() );
		}
		return $this->_connectionMethod;
	}
}


class blImgCache {
	CONST CACHE_FILENAME = 'img_caching_info.frs';
	
	
	/**
	 * @var blFileSystem
	 */
	private $_fileSystem = null;
	
	/**
	 * @var blFile
	 */
	private $_cacheFile = null;
	
	private $_cacheFileUnparsed = null;
	
	private $_cacheFileParsed = null;
	
	private $_cacheFileDir = null;
	
	public function __construct( blFileSystem $fileSystem, $cacheFileDir ) {
		
		$this->_setFileSystem($fileSystem);
		$this->_setCacheFileDir($cacheFileDir . self::CACHE_FILENAME );
		
		$this->_loadCacheFile();
		$this->_unparseCacheFile();
		
	}
	
	public function addCachedFileRemote( $urlNew, $pathNew, $urlOld ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		
		if( $this->_cacheFileUnparsed == null ) {
			$this->_cacheFileUnparsed = new stdClass();
		}
		if( !isset( $cacheFileUnparsed->remoteDataHolder[ $urlOld ] ) ) {
			$cachedFile = new stdClass();
			$cachedFile->urlNew = $urlNew;
			$cachedFile->pathNew = $pathNew;
			$cachedFile->urlOld = $urlOld;
			$cachedFile->timestamp = time();
			
			$cacheFileUnparsed->remoteDataHolder[ $cachedFile->urlOld] = $cachedFile;
		}
	}
	
	public function addCachedFile( $urlNew, $urlOld, $pathNew, $pathOld, $remote = false ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		
		if( $this->_cacheFileUnparsed == null ) {
			$this->_cacheFileUnparsed = new stdClass();
		}
		if( !isset( $cacheFileUnparsed->dataHolder[ $urlNew ] ) ) {
			$cachedFile = new stdClass();
			$cachedFile->urlNew = $urlNew;
			$cachedFile->urlOld = $urlOld;
			$cachedFile->pathNew = $pathNew;
			$cachedFile->pathOld = $pathOld;
			$cachedFile->remote = $remote;
			$cachedFile->timestamp = time();
			
			$cacheFileUnparsed->dataHolder[ $cachedFile->urlNew] = $cachedFile;
		}
		
		
	} 
	
	public function deleteCacheInfo( $urlNew ) {
		
		
		unset( $this->_getCacheFileUnparsed()->dataHolder[ $urlNew ] );
	}
	
	public function deleteRemoteCacheInfo( $url ) {
		unset( $this->_getCacheFileUnparsed()->remoteDataHolder[ $url ] );
	}
	
	public function getCacheInfo( $urlNew ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		if( isset( $cacheFileUnparsed->dataHolder[ $urlNew ] ) ) {
			$cachedImageInfo = $cacheFileUnparsed->dataHolder[ $urlNew ];
			$cachedImageInfo->valid = $this->_checkExpiration($cachedImageInfo);	
			return $cacheFileUnparsed->dataHolder[ $urlNew ];
		} else {
			return null;
		}
	}
	
	public function touchCachedFile( $urlNew ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		$cacheFileUnparsed->dataHolder[ $urlNew ]->valid = true;
		$cacheFileUnparsed->dataHolder[ $urlNew ]->timestamp = time();
	}
	
	public function getRemoteCacheInfo( $urlOld ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		if( isset( $cacheFileUnparsed->remoteDataHolder[ $urlOld ] ) ) {
			$cachedImageInfo = $cacheFileUnparsed->remoteDataHolder[ $urlOld ];
			$cachedImageInfo->valid = $this->_checkExpiration($cachedImageInfo);
			return $cacheFileUnparsed->remoteDataHolder[ $urlOld ];
		} else {
			return null;
		}
	}
	
	private function _checkExpiration( stdClass $cachedImageInfo ) {
		$currentTimestamp = time();
		$oldTimestamp = $cachedImageInfo->timestamp;
		
		if( ( $oldTimestamp + CACHE_TIME ) < $currentTimestamp ) {
			return false;
		} else {
			return true;
		}
	}
	
	/*public function deleteCacheInfo( $urlNew ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		unset( $cacheFileUnparsed->dataHolder[ $urlNew ] );
		
	}*/
	
	public function __destruct() {
		
		$this->saveCacheFile();
	}
	
	public function saveCacheFile() {
		
		$this->_parseCacheFile();
		$this->_saveCacheFile();
	}
	
	private function _parseCacheFile() {
		$cacheFileParsed = serialize( $this->_getCacheFileUnparsed() );
		$this->_setCacheFileParsed( $cacheFileParsed );
	}
	
	private function _saveCacheFile() {
		if( $this->_getCacheFile()->getHandle() == null ) return ;
		$this->_getCacheFile()
				->truncate()
				->write( $this->_getCacheFileParsed() )
				->closeFile();
	}
	
	
	private function _loadCacheFile() {
		$cacheFile = $this->_getFileSystem()->openFile( $this->_getCacheFileDir(), true);
		
		$this->_setCacheFile( $cacheFile );
		$this->_setCacheFileParsed( $cacheFile->readAll() );
		
	}
	
	private function _unparseCacheFile() {
		if( $this->_getCacheFileParsed() != '') {
			$cacheFileContentUnparsed = unserialize( $this->_getCacheFileParsed() );
			$cacheFileContentUnparsed->hitsAfterLastDelete++;
			
			$this->_setCacheFileUnparsed( $cacheFileContentUnparsed );
		} else {
			$cacheFileContentUnparsed = new stdClass();
			$cacheFileContentUnparsed->hitsAfterLastDelete = 1;
			$this->_setCacheFileUnparsed( $cacheFileContentUnparsed );
		}
		
		if( $cacheFileContentUnparsed->hitsAfterLastDelete >= CACHE_DELETE_FILES_check_every_x_hits ) {
			$this->_hardDeleteCache();
		}
	}
	
	private function _hardDeleteCache() {
		//var_dump(CACHE_DELETE_FILES_check_every_x_hits);
		$unsetArray = array();
		if( !empty( $this->_getCacheFileUnparsed()->dataHolder ) ) {
			foreach( $this->_getCacheFileUnparsed()->dataHolder as $url => $fileData ) {
				//pathNew, timestamp
				if( ( $fileData->timestamp  + CACHE_DELETE_FILES_AFTER ) <= time() ) {
					$unsetArray[] = $url;
					unlink( $fileData->pathNew);
					$this->_deleteRetinaFile( $fileData->pathNew );
				}
			}
		}
		foreach( $unsetArray as $oneUrl ) {
			unset ($this->_getCacheFileUnparsed()->dataHolder[ $oneUrl ] );
		}
		
		$unsetArray = array();
		if( !empty( $this->_getCacheFileUnparsed()->remoteDataHolder ) ) {
			foreach( $this->_getCacheFileUnparsed()->remoteDataHolder as $url => $fileData ) {
				//pathNew, timestamp
				if( ( $fileData->timestamp  + CACHE_DELETE_FILES_AFTER ) <= time() ) {
					$unsetArray[] = $url;
					unlink( $fileData->pathNew);
					$this->_deleteRetinaFile( $fileData->pathNew );
				}
			}
		}
		foreach( $unsetArray as $oneUrl ) {
			unset ($this->_getCacheFileUnparsed()->remoteData[ $oneUrl ] );
		}
		$this->_getCacheFileUnparsed()->hitsAfterLastDelete = 0;
	}
	
	public function _deleteRetinaFile( $pathOld ) {
		$pathInfo = pathinfo($pathOld);
        $retinaFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '@2x' . '.' . $pathInfo['extension'];
        if (file_exists($retinaFile)) {
            unlink($retinaFile);
        }
	}
	
	
	private function _setCacheFileParsed( $cacheFileParsed ) {
		$this->_cacheFileParsed = $cacheFileParsed;
	}
	
	private function _getCacheFileParsed() {
		return $this->_cacheFileParsed;
	}
	
	private function _setCacheFileUnparsed( $cacheFileUnparsed ) {
		$this->_cacheFileUnparsed = $cacheFileUnparsed;
	}
	
	private function _getCacheFileUnparsed() {
		return $this->_cacheFileUnparsed;
	}
	
	private function _setCacheFileDir( $cacheFileDir) {
		$this->_cacheFileDir = $cacheFileDir;
	}
	
	private function _getCacheFileDir() {
		return $this->_cacheFileDir;
	}
	
	private function _setCacheFile( blFile $cacheFile ) {
		$this->_cacheFile = $cacheFile;
	}
	
	/**
	 * @return blFile
	 */
	private function _getCacheFile() {
		return $this->_cacheFile;
	}
	
	private function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	
	/**
	 * 
	 * @return blFileSystem
	 */
	private function _getFileSystem() {
		return $this->_fileSystem;
	}
}


class blImgDownloader {
	/**
	 * @var blFileSystem
	 */
	private $_fileSystem = null;
	
	/**
	 * @var blInputStreamAdapteour
	 */
	private $_inputStream = null;
	
	public function __construct( blFileSystem $fileSystem, blInputStreamAdapteour $inputStreamAdapter ) {
		$this->_setFileSystem( $fileSystem );
		$this->_setInputStream( $inputStreamAdapter );
	}
	
	public function downloadImage( $originalPath, $newPath ) {
		$img = $this->_getInputStream()->open( $originalPath )->readAll();
		if( $img != null ) {
			$this->_getFileSystem()->createFile( $newPath, true)->write( $img )->closeFile();
		}
	}
	
	private function _setInputStream( blInputStreamAdapteour $inputStreamAdapter ) {
		$this->_inputStream = $inputStreamAdapter;
	}
	
	/**
	 * @return blInputStreamAdapteour
	 */
	private function _getInputStream() {
		return $this->_inputStream;
	}
	
	private function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	/**
	 * @return blFileSystem
	 */
	private function _getFileSystem() {
		return $this->_fileSystem;
	}
}


interface blIInputStream {
	public function open( $path );
	public function readAll();
	
}


class blInputStreamFile implements blIInputStream {
	/**
	 * @var blFileSystem
	 */
	private $_fileSystem = null;
	
	/**
	 * @var blFile
	 */
	private $_openedFile = null;
	
	public function __construct() {
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see blIInputStream::open()
	 */
	public function open( $path ) {
		$file = $this->_getFileSystem()->openFile($path);
		$this->_setFile( $file );
		return $this;
	}
	
	public function readAll() { 
		return $this->_getFile()->readAllAndClose();
	}
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	private function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	
	/**
	 * @return blFileSystem
	 */
	private function _getFileSystem() {
		if( $this->_fileSystem == null ) {
			$this->_setFileSystem( new blFileSystem() );
		}
		
		return $this->_fileSystem;
	}
	
	private function _setFile( blFile $file ) {
		$this->_openedFile = $file;
	}
	
	/**
	 * @return blFile
	 */
	private function _getFile() {
		return $this->_openedFile;
	}
}


class blInputStreamHttp implements blIInputStream {
	/**
	 * @var blDownloader
	 */
	private $_downloader = null;
	private $_pageContent = '';
	
	public function open( $path ) {
		$content = $this->_getDownloader()->getContent( $path );
		$this->_setPageContent( $content );
	}
	
	public function readAll() {
		return $this->_getPageContent();
	}
	
	/**
	 * @return blDownloader
	 */
	private function _getDownloader() {
		if( $this->_downloader == null ) {
			$this->_downloader = new blDownloader();
		}
		
		return $this->_downloader;
	}
	
	private function _setPageContent( $pageContent ) {
		$this->_pageContent = $pageContent;
	}
	
	private function _getPageContent() {
		return $this->_pageContent;
	}
}


class blInputStreamAdapteour implements blIInputStream {
	/**
	 * 
	 * @var blIInputStream
	 */
	private $_inputStream = null;
	
/*----------------------------------------------------------------------------*/
/* PUBLIC FUNCTIONS
/*----------------------------------------------------------------------------*/
		
	public function open( $path ) {
		$this->_createInputStream( $path );
		$this->_getInputStream()->open($path);
		return $this;
	}
	
	public function readAll() {
		return $this->_getInputStream()->readAll();
	}
	
/*----------------------------------------------------------------------------*/
/* PRIVATE FUNCTIONS
/*----------------------------------------------------------------------------*/	
	private function _createInputStream( $path ) {
		if( strpos( $path, 'http://') !== false ) {
			$this->_setInputStream( new blInputStreamHttp() );
		} else {
			$this->_setInputStream( new blInputStreamFile() );
		}
	}
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	
	private function _setInputStream(  blIInputStream $inputStream ) {
		$this->_inputStream = $inputStream;
	}
	
	private function _getInputStream() {
		return $this->_inputStream;
	}
}


class fImgOneData {
	public $path = null;
	public $url = null;
	public $filename = null;
	public $width = null;
	public $height = null;
	public $timestamp = null;
	public $crop = null;
}

class fImgData {
	/**
	 * 
	 * @var fImgOneData
	 */
	public $new = null;
	
	/**
	 * 
	 * @var fImgOneData
	 */
	public $old = null;
	
	public $remote = false;
	public $ready = false;
	
	public function __construct() {
		$this->new = new fImgOneData();
		$this->old = new fImgOneData();
	}
	
}




class fImgDeliverer {
	/**
	 * @var blFileSystem
	 */
	private $_fileSystem = null;
	
	
	/**
	 * 
	 * @var blInputStreamAdapteour
	 */
	private $_inputStream = null;
	
	/**
	 * @var blImgCache
	 */
	private $_imgCache = null;
	
	/**
	 * 
	 * @var fImgPathPredictor
	 */
	private $_imgPredictor = null;
	
	
	/**
	 * 
	 * @var blImgDownloader
	 */
	private $_imgDownloader = null;
	
	private $_uploadDir = null;
	
	private $_uploadUrl = null;
	
	/**
	 * 
	 * @var fImgNamer
	 */
	private $_imgNamer = null;
	
	public function __construct( blFileSystem $fileSystem,  $inputStream, blImgCache $imgCache, $uploadDir, $uploadUrl ) {
		$this->_setFileSystem( $fileSystem );
		$this->_setInputStream( $inputStream );
		$this->_setImgCache( $imgCache);
		$this->_setUploadDir( $uploadDir );
		$this->_setUploadUrl( $uploadUrl );
	}
	
	/**
	 *
	 * @param fImgData $imgData
	 * @return  fImgData
	 */
	public function deliveryImage( fImgData $imgData ) {
		$result = $this->_deliveryFromCache( $imgData );
		//$result = false;
		if( $result === false )
			$result = $this->_deliveryFromLocal( $imgData );
		if( $result === false )
			$result = $this->_deliveryFromRemote( $imgData );
		
		return $result;
	}
	
	private function _deliveryFromCache( fImgData $imgData ) {
		
		$cacheInfo = $this->_getImgCache()->getCacheInfo( $imgData->new->url );
		
		if( $cacheInfo == null ) return false;
		
		$imgData->new->path = $this->_getUploadDirPath( $imgData->new->filename );
		$cacheValidity = $this->_checkCacheValidity( $imgData, $cacheInfo );
		if( $cacheValidity === false ) return false;
		
		$imgData->ready = true;
		
		return $imgData;
	}
	
	private function _checkCacheValidity( fImgData $imgData, $cacheInfo ) {
		if( $cacheInfo->valid == true ) return true;
		
		if( !file_exists( $cacheInfo->pathOld) ) {
			$this->_getImgCache()->deleteCacheInfo( $imgData->new->url );
			$this->_getFileSystem()->deleteFile( $imgData->new->path );
			return false;
		}
		$newTS = $cacheInfo->timestamp;
		$oldTS = filemtime( $cacheInfo->pathOld );
		if( $newTS > $oldTS ) {
			$this->_getImgCache()->touchCachedFile( $imgData->new->url );
			return true;
		} else {
			$this->_getImgCache()->deleteCacheInfo( $imgData->new->url );
			$this->_getFileSystem()->deleteFile( $imgData->new->path );			
			return false;
		}
	} 
	
	private function _deliveryFromLocal( fImgData $imgData ) {
		$path = $this->_getImgPredictor()->predictPath( $imgData->old->url );
		
		if( $path != null ) {
			$imgData->old->path = $path;
			if ($this->_doesntHasAlphaLayer($path) && false ) {
				$imgData->new->filename = $this->_getImgNamer()->renameToJpg( $imgData->new->filename );
				$imgData->new->url = $this->_getImgNamer()->renameToJpg( $imgData->new->url );
			}
			$imgData->new->path = $this->_getUploadDirPath( $imgData->new->filename );
			$imgData->ready = false;
			
			return $imgData;
		} else {
			return false;
		}
	}	
	
	private function _doesntHasAlphaLayer( $path ) {
		if( file_exists( $path )) {
			$pathInfo = pathInfo($path);
			
			if( $pathInfo['extension'] != 'png') {
				return false;
			}
			
			$firstChars = $this->_getFileSystem()->openFile($path)->read(26);//->pointerTo(25)->read(1);
			//var_dump($firstChars);
			$char = $firstChars[25];
			$pngType = (ord($char));
		//	var_dump($pngType);
		}
		if( $pngType == 6 ) {
			return false;
		} else {
			return true;
		}
	}
	
	private function _deliveryFromRemote( fImgData $imgData ) {

		$remoteFilename = $this->_getImgNamer()->getRemoteImageName( $imgData->old->url );
		$remotePath = $this->_getUploadDirPath('remote/' . $remoteFilename);
		$remoteUrl = $this->_getUploadUrlPath('remote/' . $remoteFilename);
		
		$remoteFileCacheInfo = $this->_getImgCache()
										->getRemoteCacheInfo($imgData->old->url);
										//->getCacheInfo( $remotePath);
	
		if( $remoteFileCacheInfo != null && $remoteCacheInfo->valid == false ) {
		
			$remoteFileCacheInfo = null;
			$this->_getImgCache()->deleteRemoteCacheInfo( $remoteUrl );
			$this->_getFileSystem()->deleteFile( $remotePath );
		}
			
		if( $remoteFileCacheInfo == null ) {
	
			$this->_getImgDownloader()->downloadImage($imgData->old->url, $remotePath );
			if( file_exists( $remotePath) ) {
				$this->_getImgCache()->addCachedFileRemote($remoteUrl, $remotePath, $imgData->old->url);
				//$this->_getImgCache()->addCachedFile($urlNew, $urlOld, $pathNew, $pathOld, $remote)
			}
		}
		$imgData->new->path = $this->_getUploadDirPath( $imgData->new->filename );
		$imgData->old->url = $remoteUrl;
		$imgData->old->path = $remotePath;
		$imgData->ready = false;
		$imgData->remote = true;

		return $imgData;
	}

	
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	private function _getUploadDirPath( $path ) {
		return $this->_getUploadDir() . $path;
	}
	
	private function _getUploadUrlPath ( $path ) {
		return $this->_getUploadUrl() .'/'. $path;
	}
		
	
	private function _getUploadUrl() {
		return $this->_uploadUrl;
	}
	
	private function _setUploadUrl( $uploadUrl ) { 
		$this->_uploadUrl = $uploadUrl;
	}
	private function _getImgDownloader() {
		if( $this->_imgDownloader == null ) {
			$this->_imgDownloader = new blImgDownloader( $this->_getFileSystem(), $this->_getInputStream() );
		}
		
		return $this->_imgDownloader;
	}
	
	private function _getImgNamer() { 
		if( $this->_imgNamer == null ) { 
			$this->_imgNamer = new fImgNamer();
		}
		
		return $this->_imgNamer;
	}
	
	private function _setUploadDir( $uploadDir ) {
		$this->_uploadDir = $uploadDir;
	}
	
	private function _getUploadDir() {
		return $this->_uploadDir;
	}
	
	/**
	 * @return fImgPathPredictor
	 */
	private function _getImgPredictor() {
		if( $this->_imgPredictor == null ) {
			$this->_imgPredictor = new fImgPathPredictor();
		}
		
		return $this->_imgPredictor;
	}
	
	private function _setImgCache( blImgCache $imgCache ) {
		$this->_imgCache = $imgCache;
	}
	
	/**
	 * 
	 * @return blImgCache
	 */
	private function _getImgCache(){
		return $this->_imgCache;
	}
	private function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	
	/**
	 * @return blFileSystem
	 */
	private function _getFileSystem() { 
		return $this->_fileSystem;
	}
	
	private function _setInputStream( blInputStreamAdapteour $inputStream ) {
		$this->_inputStream = $inputStream;
	}
	
	/**
	 * @return blInputStreamAdapteour
	 */
	private function _getInputStream() {
		return $this->_inputStream;
	}
}


class fImgNamer {
	private $_defaultUrl = null;
	private $_temporaryPathInfo = null;
	
	public function __construct( $defaultUrl = null ) {
		$this->_setDefaultUrl( $defaultUrl ); 
	}
	public function getNewImageName( $oldUrl, $width, $height = false, $crop = false, $remote = false) {
		$newUrl =  '';
		
		$partRemote = ( $remote ) ? 'remote/' : '';
		$partWidth = '-'.$width;
		$partHeight = ( $height ) ? '-'.$height : '';
		$partCrop =   ( $crop )   ? '-c' : '';
		
		//$newUrl .= $this->_getDefaultUrl() .'/';
		$newUrl .= $partRemote;
		$newUrl .= $this->_getUrlHash( $oldUrl ) . '_' ;
		$newUrl .= $this->_getImgName( $oldUrl );
		$newUrl .= $partWidth;
		$newUrl .= $partHeight;
		$newUrl .= $partCrop;
		$newUrl .= $this->_getImgExtension();
		
		return $newUrl;
	}
	public function renameToJpg( $path ) {
		$pathInfo = ( pathinfo($path));
		//var_dump( $pathInfo['dirname'] == '.');
		( $pathInfo['dirname'] == '.' ) ? $dirname = '' : $dirname = $pathInfo['dirname'] .'/';
		$toReturn =$dirname . $pathInfo['filename'] . '.jpg';
		//var_dump($toReturn);
		return $toReturn;
	}
	public function getNewImageUrl( $oldUrl, $width, $height = false, $crop = false, $remote = false ) {
		/**
		 * http://defaulturl(freshizer)/[remote]/$oldUrlHash_imgFilename-width[-height][-c(rop)].ext
		 */
		$newUrl =  '';
		
		$partRemote = ( $remote ) ? 'remote/' : '';
		$partWidth = '-'.$width;
		$partHeight = ( $height ) ? '-'.$height : '';
		$partCrop =   ( $crop )   ? '-c' : '';
		
		$newUrl .= $this->_getDefaultUrl() .'/';
		$newUrl .= $partRemote;
		$newUrl .= $this->_getUrlHash( $oldUrl ) . '_' ;
		$newUrl .= $this->_getImgName( $oldUrl );
		$newUrl .= $partWidth;
		$newUrl .= $partHeight;
		$newUrl .= $partCrop;
		$newUrl .= $this->_getImgExtension();
		
		
		return $newUrl;
 	}
 	
 	public function getRemoteImageName( $url ) {
 		$pathInfo = pathinfo( $url );
 		$newName = '';
 		$newName .= $this->_getUrlHash( $url );
 		$newName .= '-'.$pathInfo['filename'].'.'.$pathInfo['extension'];
 		
 		return $newName;
 	}
 	
 	private function _getImgExtension() {
 		$pathInfo = $this->_getTemporaryPathInfo();
 		return '.'.$pathInfo['extension'];
 	}
 	
 	private function _getImgName( $oldUrl ) {
 		$pathInfo = pathinfo( $oldUrl );
 		$this->_setTemporaryPathInfo( $pathInfo );
 		return $pathInfo['filename'];
 	}
 	
 	private function _setTemporaryPathInfo( $pathInfo ) {
 		$this->_temporaryPathInfo = $pathInfo;
 	}
 	
 	private function _getTemporaryPathInfo() {
 		return $this->_temporaryPathInfo;
 	}
 	
 	private function _getUrlHash( $url ) {
 		return md5($url);
 	}
	
	private function _setDefaultUrl( $defaultUrl ) {
		$this->_defaultUrl = $defaultUrl;
	}
	
	private function _getDefaultUrl() {
		return $this->_defaultUrl;
	}
}


interface fIImgPathPredictor {
	public function predictPath( $url );
	
	/*private function _predictionJunction();
	private function _predictUploads();
	private function _predictThemes();
	private function _predictPlugins();*/
}

class fAImgPathPredictor implements fIImgPathPredictor { 
	public function predictPath( $url ) {}
	
	protected function _predictUploads() {}
	protected function _predictThemes() {}
	protected function _predictPlugins() {}
}


class fImgPathPredictor {
	
	/**
	 * 
	 * @var fIImgPathPredictor
	 */
	private $_predictor = null;
	
	public function predictPath( $url ) {
		return $this->_getPredictor()->predictPath( $url );
	}
	
	
	private function _initializePredictor() {
		global $blog_id;
		
		if( is_multisite() && $blog_id != 1) { 
			$this->_setPredictor( new fImgPathPredictor_Multisite() );
		} else {
			$this->_setPredictor( new fImgPathPredictor_Single() );
		}
	}
	
	private function _setPredictor( fIImgPathPredictor $predictor) {
		$this->_predictor = $predictor;
	}
	
	/**
	 * @return fIImgPathPredictor
	 */
	private function _getPredictor() {
		if( $this->_predictor == null ) {
			$this->_initializePredictor();
		}
		
		return $this->_predictor;
	}
}
class fImgPathPredictor_Multisite extends fAImgPathPredictor implements fIImgPathPredictor {
	private $_imgUrl = null;
	private $_predictedPath = null;

	public function predictPath( $url ) {
		$this->_setImgUrl( $url );
		$this->_predictionJunction();

		return $this->_getPredictedPath();
	}

	protected function _predictionJunction() {
		//echo $this->_getImgUrl().'xxxx';
		//return;
		$uploadDir = wp_upload_dir();

		if( strpos( $this->_getImgUrl(), $uploadDir['baseurl']) !== false ) {
			$this->_predictUploads();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictThemes();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictPlugins();
		}

	}

	protected function _predictUploads() {
		$uploadDir = wp_upload_dir();
		$uploadSubpath = str_replace( $uploadDir['baseurl'],'', $this->_getImgUrl());

		$newRelPath = $uploadDir['basedir'].$uploadSubpath;

		if( file_exists( $newRelPath) ) {
			$this->_setPredictedPath( $newRelPath );
		}


	}
	protected function _predictThemes() {
		$splitedUrl = explode('themes/', $this->_getImgUrl() ); //explode() $this->_getImgUrl();
		$splitedPath = explode('themes/', get_template_directory() );
		$newRelPath = $splitedPath[0].'themes/'.$splitedUrl[1];

		if( file_exists( $newRelPath )) {
			$this->_setPredictedPath( $newRelPath );
		}
	}
	protected function _predictPlugins() {
		$imgPluginDirSplitted = explode('wp-content/plugins', $this->_getImgUrl() );
		$imgAfterPluginDir = $imgPluginDirSplited[1];

		$pluginDir = WP_PLUGIN_DIR;
		$newRelPath = $pluginDir . $imgAfterPluginDir;

		if( file_exists( $newRelPath ) ) {
			$this->_setPredictedPath( $newRelPath );
		}
                
               

	}


	private function _getPredictedPath() {
		return $this->_predictedPath;
	}

	private function _setPredictedPath( $predictedPath ) {
		$this->_predictedPath = $predictedPath;
	}

	private function _setImgUrl( $imgUrl ) {
		$this->_imgUrl = $imgUrl;
	}

	private function _getImgUrl() {
		return $this->_imgUrl;
	}
}

class fImgPathPredictor_Single extends fAImgPathPredictor implements fIImgPathPredictor {
	private $_imgUrl = null;
	private $_predictedPath = null;
	
	public function predictPath( $url ) {
		$this->_setImgUrl( $url );
		$this->_predictionJunction();
		return $this->_getPredictedPath();
	}
	
	protected function _predictionJunction() {
		if( strpos( $this->_getImgUrl(), 'wp-content/uploads') !== false ) {
			$this->_predictUploads();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictThemes();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictPlugins();
		}		
		
	}
	
	protected function _predictUploads() {
		$imgUploadDirSplited = explode('wp-content/uploads', $this->_getImgUrl() );
		$imgAfterUploadDir = $imgUploadDirSplited[1];
		$wpUploadDir = wp_upload_dir();
		$baseDir = $wpUploadDir['basedir'];
		
		$newRelPath = $baseDir . $imgAfterUploadDir;
		
		if( file_exists( $newRelPath) ) {
			$this->_setPredictedPath( $newRelPath );
		}
		
		
	}
	protected function _predictThemes() {
		$imgThemeDirSplited = explode('wp-content/themes', $this->_getImgUrl() );
		$imgAfterThemeDir = $imgThemeDirSplited[1];
		
		$currentThemeDirSplited = explode( 'wp-content/themes', get_template_directory());
		$currentThemeFolder = $currentThemeDirSplited[0];
		
		$newRelPath = $currentThemeFolder . 'wp-content/themes' . $imgAfterThemeDir;
		
		if( file_exists( $newRelPath )) {
			$this->_setPredictedPath( $newRelPath );
		}
	}
	protected function _predictPlugins() {
		$imgPluginDirSplitted = explode('wp-content/plugins', $this->_getImgUrl() ); 
		$imgAfterPluginDir = $imgPluginDirSplited[1];
		
		$pluginDir = WP_PLUGIN_DIR;
		$newRelPath = $pluginDir . $imgAfterPluginDir;
		
		if( file_exists( $newRelPath ) ) {
			$this->_setPredictedPath( $newRelPath );
		}

	}
	
	
	private function _getPredictedPath() {
		return $this->_predictedPath;
	}
	
	private function _setPredictedPath( $predictedPath ) {
		$this->_predictedPath = $predictedPath;
	}
	
	private function _setImgUrl( $imgUrl ) {
		$this->_imgUrl = $imgUrl;
	}
	
	private function _getImgUrl() {
		return $this->_imgUrl;
	}
}


class fImgResizer {
	
	/**
	 * 
	 * @var blFileSystem
	 */
	private $_fileSystem = null;
	
	/**
	 * @var fImgResizerCalculator
	 */
	private $_imgResizerCalculator = null;
	
	private $_imgHasAlfaChannel = null;
	
	public function __construct( blFileSystem $fileSystem ) {
		$this->_setFileSystem($fileSystem);
	}
	
	public function resize( fImgData $imgData ) {// stdClass $pathInfo, stdClass $imgInfo ) {
		//var_dump($imgData);
		$imageOld = $this->_openImage( $imgData->old->path );
		$orig = $this->_getImgDimensions( $imgData->old->path );
		
	
		$newDimensions = $this->_getImgResizerCalculator()->calculateNewDimensions( $orig->width, 
																					$orig->height, 
																					$imgData->new->width, 
																					$imgData->new->height, 
																					$imgData->new->crop );
		
		$imageNew = $this->_createImage( $newDimensions['dst']['w'],$newDimensions['dst']['h']);
		$imageNewRetina = $this->_createImage( $newDimensions['dst']['w'] * 2,$newDimensions['dst']['h'] * 2);
		
		imagecopyresampled($imageNew, $imageOld, $newDimensions['dst']['x'],
												 $newDimensions['dst']['y'],
												 $newDimensions['src']['x'],
												 $newDimensions['src']['y'],
												 $newDimensions['dst']['w'],
												 $newDimensions['dst']['h'],
												 $newDimensions['src']['w'],
												 $newDimensions['src']['h']);
		
		imagecopyresampled($imageNewRetina, $imageOld, $newDimensions['dst']['x'],
			$newDimensions['dst']['y'],
			$newDimensions['src']['x'],
			$newDimensions['src']['y'],
			$newDimensions['dst']['w']*2,
			$newDimensions['dst']['h']*2,
			$newDimensions['src']['w'],
			$newDimensions['src']['h']);
				
		if(function_exists('imageantialias') ) {
        	imageantialias( $imageNew, true );
        	imageantialias( $imageNewRetina, true );
        }
		$pathInfo = pathinfo($imgData->old->path);
		
        if ( $pathInfo['extension'] == 'png' && function_exists('imageistruecolor') && !imageistruecolor( $imageOld ) ) {
                imagetruecolortopalette( $imageNew, false, imagecolorstotal( $imageOld ) );
        }
		$this->_getFileSystem()->saveImage( $imageNew, $imgData->new->path );
        $this->_getFileSystem()->saveImage($imageNewRetina, $this->_getRetinaPath($imgData->new->path));
		
		
		imagedestroy($imageOld);
		imagedestroy($imageNew);
		imagedestroy($imageNewRetina);
		$this->_imgHasAlfaChannel = null;
		$dimToReturn = array();
		
	}
	
	private function _openImage( $path ) {
		$imageString = $this->_getFileSystem()->openFile( $path )->readAllAndClose();
		$pathInfo = pathinfo($path);
		if( $pathInfo['extension'] == 'png' && ord($imageString[25]) == 6 ) 
			$this->_imgHasAlfaChannel = true;
		//return (ord(@file_get_contents($fn, NULL, NULL, 25, 1)) == 6);
		@ini_set( 'memory_limit', '256M' );
		$image = imagecreatefromstring( $imageString );
		return $image;
	}
	
	
	private function _getImgDimensions( $path ) {
		$dim = getimagesize( $path );
		$result = new stdClass();
		$result->width = $dim[0];
		$result->height = $dim[1];
		return $result;
	}
	private function _createImage ($width, $height) {
		$img = imagecreatetruecolor($width, $height);
		if ( is_resource($img) && $this->_imgHasAlfaChannel && function_exists('imagealphablending') && function_exists('imagesavealpha') ) {
			imagealphablending($img, false);
			imagesavealpha($img, true);
		}
		return $img;
	}	
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/

    private function _getRetinaPath($path) {
        $pathInfo = pathinfo($path);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '@2x' . '.' . $pathInfo['extension'];
    }

	private function _getImgResizerCalculator() {
		if( $this->_imgResizerCalculator == null ) { 
			$this->_imgResizerCalculator = new fImgResizerCalculator();
		}
		
		return $this->_imgResizerCalculator;
	}
	
	/**
	 * @return blFileSystem
	 */
	private function _getFileSystem() {
		return $this->_fileSystem;
	}
	
	private function _setFileSystem( $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
}


class fImgResizerCalculator {
	public function calculateNewDimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false) {
		
		if ( $crop ) {
			
			// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
			$aspect_ratio = $orig_w / $orig_h;
			$new_w =$dest_w;// min($dest_w, $orig_w);
			$new_h =$dest_h;// min($dest_h, $orig_h);
	
			if ( !$new_w ) {
				$new_w = intval($new_h * $aspect_ratio);
			}
	
			if ( !$new_h ) {
				$new_h = intval($new_w / $aspect_ratio);
			}
			
			$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);
	
			$crop_w = round($new_w / $size_ratio);
			$crop_h = round($new_h / $size_ratio);
	
			$s_x = floor( ($orig_w - $crop_w) / 2 );
			$s_y = floor( ($orig_h - $crop_h) / 2 );
		} else {
			// don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
			
			$crop_w = $orig_w;
			$crop_h = $orig_h;
	
			$s_x = 0;
			$s_y = 0;
	
	
			list( $new_w, $new_h ) = $this->constrainNewDimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}
		$to_return = array();
		$to_return['src']['x'] = (int)$s_x;
		$to_return['src']['y'] = (int)$s_y;
		$to_return['src']['w'] = (int)$crop_w;
		$to_return['src']['h'] = (int)$crop_h;
	
		$to_return['dst']['x'] = 0;
		$to_return['dst']['y'] = 0;
		$to_return['dst']['w'] = (int)$new_w;
		$to_return['dst']['h'] = (int)$new_h;
	
		return $to_return;
	}
	
	/**
	 * This function has been take over from wordpress core. It calculate the best proportion to uncropped image
	 */
	public function constrainNewDimensions( $current_width, $current_height, $max_width=0, $max_height=0 ) {
		
		if ( !$max_width and !$max_height )
			return array( $current_width, $current_height );
		
	
		$width_ratio = $height_ratio = 1.0;
		$did_width = $did_height = false;
	
		if ( $max_width > 0 && $current_width > 0 )
		{
			$width_ratio = $max_width / $current_width;
			$did_width = true;
		}
	
		if ( $max_height > 0 && $current_height > 0 )
		{
			$height_ratio = $max_height / $current_height;
			$did_height = true;
		}
	
		// Calculate the larger/smaller ratios
		$smaller_ratio = min( $width_ratio, $height_ratio );
		$larger_ratio  = max( $width_ratio, $height_ratio );
		
		if ( intval( $current_width * $larger_ratio ) > $max_width || intval( $current_height * $larger_ratio ) > $max_height )
			// The larger ratio is too big. It would result in an overflow.
			$ratio = $smaller_ratio;
		else
			// The larger ratio fits, and is likely to be a more "snug" fit.
			$ratio = $larger_ratio;
		//echo $current_width;
		if( $max_width > $current_width && $max_height == 0 ) {
			$ratio = $larger_ratio;	
		}
		
		$w = intval( $current_width  * $ratio );
		$h = intval( $current_height * $ratio );
		
		// Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
		// We also have issues with recursive calls resulting in an ever-changing result. Constraining to the result of a constraint should yield the original result.
		// Thus we look for dimensions that are one pixel shy of the max value and bump them up
		if ( $did_width && $w == $max_width - 1 )
			$w = $max_width; // Round it up
		if ( $did_height && $h == $max_height - 1 )
			$h = $max_height; // Round it up
	
		return array( $w, $h );
	}
}

class fImg {
	/**
	 * @var fImg
	 */
	private static $_instance = null;

	/**
	 * @var fImgNamer
	 */
	private $_imgNamer = null;
	
	/**
	 * @var blFileSystem
	 */
	private $_fileSystem = null;

	/**
	 * @var blImgCache
	 */
	private $_imgCache = null;
	
	/**
	 * @var fImgDeliverer
	 */
	private $_imgDeliverer = null;
	
	/**
	 * @var blInputStreamAdapteour
	 */
	private $_inputStream = null;
	
	/**
	 * @var fImgResizer
	 */
	private $_imgResizer = null;
	
	
	private $_defaultUrl = null;
	
	private $_defaultDir = null;
	
/*----------------------------------------------------------------------------*/
/* PUBLIC FUNCTIONS
/*----------------------------------------------------------------------------*/
		
	public function __construct() {
		$this->_createDefaultUrlAndDir();
		$this->_setImgCache( new blImgCache( $this->_getFileSystem(), $this->_getDefaultDir() ) );
		$this->_setImgDeliverer( new fImgDeliverer( $this->_getFileSystem(), $this->_getInputStream(), $this->_getImgCache(), $this->_getDefaultDir(), $this->_getDefaultUrl() ) );
		$this->_setImgResizer( new fImgResizer( $this->_getFileSystem() ) );
	}
	
	public function getInstance() {
		if( self::$_instance == null ) {
			self::$_instance = new fImg();
		}
		
		return self::$_instance;
	}

	
	public static function ResizeC( $url, $width, $height = false, $crop = false, $returnImgSize = false ) {
		$width = (int)$width;
		$height = (int)$height;
		
		return self::getInstance()->_resize($url, $width, $height, $crop, $returnImgSize);
	}
	

	public static function resize( $url, $width, $height = false, $crop = false, $returnImgSize = false ) {
		$width = (int)$width;
		$height = (int)$height;
		
		return self::getInstance()->_resize($url, $width, $height, $crop, $returnImgSize = false);
	}	
	
	public static function getImgSize( $url ) {
		return self::getInstance()->_getImgSize( $url );
	}
	
	private function _getImgSize( $url ) {
		$imgData = $this->_getImgData($url, 0, false, false);
		$imgData = $this->_getImgDeliverer()->deliveryImage($imgData);
		return $imgData;
	}
	
	private function _resize( $url, $width, $height = false, $crop = false, $returnImgSize = false ) {
		if( empty($url) ) return null;
		
		$imgData = $this->_getImgData($url, $width, $height, $crop);
		$imgData = $this->_getImgDeliverer()->deliveryImage( $imgData );
		
		if( $imgData == false ) {
			echo 'Image :'.$url.' cannot be opened';
			return false; 
		}
		
		if( $imgData->ready == false ) {
			
			$this->_getImgResizer()->resize( $imgData );
			$this->_getImgCache()
					->addCachedFile( $imgData->new->url, $imgData->old->url, $imgData->new->path, $imgData->old->path, $imgData->remote);
		}
		if( $returnImgSize ) {
			$size = getimagesize($imgData->new->path);
			
			$toReturn = array();
			$toReturn['width'] = $size[0];
			$toReturn['height'] = $size[1];
			$toReturn['url'] = $imgData->new->url;
			
			return $toReturn;
		} else {
			return $imgData->new->url;
		}
		
	}	
/*----------------------------------------------------------------------------*/
/* PRIVATE FUNCTIONS
/*----------------------------------------------------------------------------*/
	/**
	 * @return fImgData
	 */
	private function _getImgData( $url, $width, $height, $crop ) {
		$imgData = $this->_getImageInfoAsClass($url, $width, $height, $crop);
		$imgData->new->url = $this->_getImgNamer()->getNewImageUrl( $url, $width, $height, $crop);
		$imgData->new->filename = $this->_getImgNamer()->getNewImageName($url, $width, $height, $crop);
	
		return $imgData;
	}	
	
	/**
	 * 
	 * @return fImgData
	 */
	private function _getImageInfoAsClass( $url, $width, $height,$crop) {
		$imgData = new fImgData();
		$imgData->old->url = $url;
		$imgData->new->width = (int)$width;
		$imgData->new->height = (int)$height;
		$imgData->new->crop = $crop;

		return $imgData;
	}	
	
	private function _createDefaultUrlAndDir() {
		$wpUploadDir = wp_upload_dir();
		$this->_setDefaultUrl( $wpUploadDir['baseurl'].'/freshizer');
		$this->_setDefaultDir( $wpUploadDir['basedir'].'/freshizer/');
	}
		
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	private function _setImgResizer( fImgResizer $imgResizer ) {
		$this->_imgResizer = $imgResizer;
	}
	/**
	 * @return fImgResizer
	 */
	private function _getImgResizer() {
		return $this->_imgResizer;
	}
	
	private function _setImgDeliverer( fImgDeliverer $imgDeliverer ) {
		$this->_imgDeliverer = $imgDeliverer;
	}
	
	/**
	 * @return fImgDeliverer
	 */
	private function _getImgDeliverer() {
		return $this->_imgDeliverer;
	}
	
	private function _setInputStream( blInputStreamAdapteour $inputStream ) {
		$this->_inputStream = $inputStream;
	}
	
	/**
	 * 
	 * @return blInputStreamAdapteour
	 */
	private function _getInputStream() {
		if( $this->_inputStream == null ) {
			$this->_inputStream =  new blInputStreamAdapteour() ;
		}
		return $this->_inputStream;
	}
	
	private function _setDefaultUrl( $defaultUrl ) {
		$this->_defaultUrl = $defaultUrl;
	}
	private function _getDefaultUrl() {
		return $this->_defaultUrl;
	}
	
	private function _getImgNamer() {
		if( $this->_imgNamer == null ) {
			$this->_imgNamer = new fImgNamer($this->_getDefaultUrl());
		}
		return $this->_imgNamer;
	}
	
	private function _getImgCache() {
		return $this->_imgCache;
	}
	
	private function _setImgCache( blImgCache $imgCache ) {
		$this->_imgCache = $imgCache;
	}
	
	private function _getFileSystem() {
		if( $this->_fileSystem == null ) {
			$this->_fileSystem = new blFileSystem();
		}
		return $this->_fileSystem;
	}
	
	private function _setDefaultDir( $defaultDir) { 
		$this->_defaultDir = $defaultDir;
	}
	
	private function _getDefaultDir() { 
		return $this->_defaultDir;
	}
	
	public static function DeleteCache() {
		
	}
}
