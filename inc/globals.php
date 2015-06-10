<?php

global $wpdb, $flgalleryPlugin;

$plugin =& $flgalleryPlugin;
$func =& $flgalleryPlugin->func;
$tpl =& $flgalleryPlugin->tpl;
$site =& $flgalleryPlugin->site;

if (defined('WP_ADMIN')) {
	$admin =& $flgalleryPlugin->admin;
	$admpage =& $flgalleryPlugin->admin->page;
	$media =& $flgalleryPlugin->media;
}
