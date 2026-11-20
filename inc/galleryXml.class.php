<?php

class flgalleryGalleryXml
{
	/**
	 * admin-ajax.php?action=flgalleryXml&gallery_id=N&blog_id=M[&download]
	 * Serves the gallery's cached XML configuration (generating it if needed).
	 */
	function getXml()
	{
		$gallery_id = (int)($_REQUEST['gallery_id'] ?? 0);
		$blog_id = (int)($_REQUEST['blog_id'] ?? get_current_blog_id());
		$xmlPath = FLGALLERY_CONTENT_DIR . '/' . FLGALLERY_XML . "/{$blog_id}/{$gallery_id}.xml";

		if (file_exists($xmlPath)) {
			$xml = file_get_contents($xmlPath);
		} else {
			$gallery = new flgalleryGallery($gallery_id);
			$xml = $gallery->id ? $gallery->getXml() : false;
		}

		if ($xml === false) {
			status_header(404);
			exit;
		}

		header('Content-Type: text/xml; charset=utf-8');
		if (isset($_REQUEST['download'])) {
			header('Content-Disposition: attachment; filename="gallery.xml"');
		}

		echo $xml;
		exit;
	}
}
