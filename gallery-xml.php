<?php
ob_start();
error_reporting(0);

$now = gmdate('D, j M Y H:i:s').' GMT';
header("Expires: {$now}");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( !empty($_REQUEST['id']) )
{
	require_once 'config.php';

	$gallery_id = (int)$_REQUEST['id'];
	$blog_id = (int)$_REQUEST['blog_id'];

	preg_match('|^(.*?/)(wp-content)/|i', str_replace('\\', '/', __FILE__), $m);
	$abspath = $m[1];
	$content_dir = $m[1].$m[2];

	$xmlPath = $content_dir.'/'.FLGALLERY_CONTENT.'/'.FLGALLERY_XML."/{$blog_id}/{$gallery_id}.xml";
	if ( file_exists($xmlPath) )
	{
		header('Content-Type: text/xml; charset=utf-8');
		if ( isset($_REQUEST['download']) )
			header('Content-Disposition: attachment; filename="gallery.xml"');

		ob_end_clean();

		readfile($xmlPath);
	}
	else
	{
		require_once $abspath.'wp-load.php';

		$gallery = new flgalleryGallery( $gallery_id );

		header('Content-Type: text/xml; charset=utf-8');
		if ( isset($_REQUEST['download']) )
			header('Content-Disposition: attachment; filename="gallery.xml"');

		ob_end_clean();

		echo $gallery->getXml();
	}
}
exit();

?>