********************************************************************
FRESHIZER - > WP resizing script


USAGE
********************************************************************
PHP:

require_once 'freshizer.php';
// fImg::resize( $url, $width, $height = false, $crop = false );
$url_of_resized_image = fImg::resize( 'http://yourserver.com/banana', 200, 200, true );