<?php

class flgalleryGalleryPopup
{
	function render()
	{
		global $flgalleryPlugin;

		$gallery_id = (int)($_REQUEST['gallery_id'] ?? 0);
		if (empty($gallery_id)) {
			exit;
		}

		$gallery = new flgalleryGallery($gallery_id);
		if (!$gallery->id || !$flgalleryPlugin->isGalleryType($gallery->type)) {
			status_header(404);
			exit;
		}

		if (!file_exists($gallery->xmlFilePath)) {
			$gallery->getXml();
		}

		$gallery->width = '100%';
		$gallery->height = '100%';


		// A minimal standalone page: no theme header/footer, only the gallery and its scripts
		add_filter('show_admin_bar', '__return_false');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_html($gallery->name); ?></title>
<style type="text/css">
body, * html body {
	margin: 0 !important;
}
html, body {
	height: 100%;
}
html {
	margin: 0 !important;
}
#wpadminbar {
	display: none !important;
}
.flgallery-popup {
	display: flex;
	flex-direction: column;
	height: 100%;
}
.flgallery-popup-gallery {
	flex: 1;
	min-height: 0;
}
.flgallery-embed {
	height: 100%;
}
</style>
<?php
		$flgalleryPlugin->registerScripts();
		wp_print_scripts(array('altgallery'));
?>
</head>

<body>
<div class="flgallery-popup">
	<div class="flgallery-popup-gallery"><?php echo $gallery->getHtml(); ?></div>
</div>
</body>
</html>
<?php
		exit;
	}
}
