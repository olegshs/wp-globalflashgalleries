<?php

class flgalleryGalleryXml
{
	function getXml()
	{
		error_reporting(0);

		$gallery_id = (int)$_REQUEST['gallery_id'];
		$blog_id = (int)$_REQUEST['blog_id'];
		$xmlPath = FLGALLERY_CONTENT_DIR . '/' . FLGALLERY_XML . "/{$blog_id}/{$gallery_id}.xml";

		header('Content-Type: text/xml; charset=utf-8');
		if (isset($_REQUEST['download'])) {
			header('Content-Disposition: attachment; filename="gallery.xml"');
		}

		if (file_exists($xmlPath)) {
			readfile($xmlPath);
		} else {
			$gallery = new flgalleryGallery($gallery_id);
			echo $gallery->getXml();
		}

		exit;
	}
}
