<?php

if (!function_exists('flgallery_versionValue')) :
function flgallery_versionValue($version)
{
	// "0.15.2b" => 150200 (non-numeric suffixes are ignored)
	$value = 0;
	$ver = explode('.', (string)$version);
	$l = count($ver) - 1;
	for ($i = $l; $i >= 0; $i--) {
		$value += (int)$ver[$i] * pow(100, 3 - $i);
	}

	return $value;
}
endif;

if (!function_exists('flgallery_clearXmlCache')) :
/**
 * Deletes all cached gallery XML files (wp-content/flgallery/xml/<blog_id>/<gallery_id>.xml).
 */
function flgallery_clearXmlCache( $path = '' )
{
	if ($path === '') {
		$path = WP_CONTENT_DIR . '/' . FLGALLERY_CONTENT . '/' . FLGALLERY_XML;
	}

	if (is_dir($path) && ($dir = opendir($path))) {
		while (false !== ($filename = readdir($dir))) {
			if ($filename != '.' && $filename != '..') {
				$file = $path . '/' . $filename;
				if (is_dir($file)) {
					flgallery_clearXmlCache($file);
				} elseif (is_file($file)) {
					unlink($file);
				}
			}
		}
		closedir($dir);
		return true;
	} else {
		return false;
	}
}
endif;

if (!function_exists('print_pre')) :
function print_pre($var)
{
	print '<pre>';
	print_r($var);
	print '</pre>';
}
endif;
