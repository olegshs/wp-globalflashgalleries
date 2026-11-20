<?php

class flgalleryGalleryPopup
{
	function render()
	{
		global $flgalleryPlugin, $flgalleryProducts;

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

		if (empty($_REQUEST['frontend']) && empty($flgalleryProducts[$gallery->getSignature()]) && isset($flgalleryPlugin->limitations[$gallery->type])) {
			$trialNotice = sprintf(
				__('Order the full version of %s to make it possible to display more than %d&nbsp;pictures.', 'flgallery'),
				'<a href="http://flash-gallery.com/wordpress-plugin/order/" target="_blank">' .
				$flgalleryPlugin->galleryInfo[$gallery->type]['title'] .
				'</a>',
				$flgalleryPlugin->limitations[$gallery->type]
			);
		} else {
			$trialNotice = '';
		}

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
<?php if (!empty($trialNotice)): ?>
.trial {
	flex: none;
	font-family: Tahoma, sans-serif;
	font-size: 11px;
	height: 25px;
	padding: 0;
	border: none;
	overflow: hidden;
	white-space: nowrap;
	background: #ffc;
	color: #000;
}
.trial div {
	padding: 5px 8px;
}
.trial a {
	color: #03c;
}
<?php endif; ?>
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
<?php if (!empty($trialNotice)): ?>
	<div class="trial"><div><?php echo $trialNotice; ?></div></div>
<?php endif; ?>
	<div class="flgallery-popup-gallery"><?php echo $gallery->getHtml(); ?></div>
</div>
</body>
</html>
<?php
		exit;
	}
}
