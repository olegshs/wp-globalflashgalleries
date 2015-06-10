<?php

if (!function_exists('flgallery_versionValue')) :
function flgallery_versionValue($version)
{
	$value = 0;
	$ver = explode('.', $version);
	$l = count($ver) - 1;
	for ($i = $l; $i >= 0; $i--) {
		$value += $ver[$i] * pow(100, 3 - $i);
	}

	return $value;
}
endif;

if (!function_exists('flgallery_clearXmlCache')) :
function flgallery_clearXmlCache( $name = '' )
{
	$xmlDir = WP_CONTENT_DIR . '/' . FLGALLERY_CONTENT . '/' . FLGALLERY_XML;

	$path =& $xmlDir;
	if (is_dir($path) && ($dir = opendir($path))) {
		while (false !== ($filename = readdir($dir))) {
			if ($filename != '.' && $filename != '..') {
				$file = $path . '/' . $filename;
				if (is_file($file)) {
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

if (!function_exists('file_put_contents')) :
function file_put_contents($filename, $data)
{
	$fp = @fopen($filename, 'wb');
	$res = @fwrite($fp, $data);
	@fclose($fp);

	return $res;
}
endif;

if (!function_exists('memory_get_usage')) :
function memory_get_usage($real_usage = false)
{
	return false;
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
