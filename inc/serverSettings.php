<?php
if (!defined('WP_ADMIN')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); }

if (!function_exists('mToBytes')) :
function mToBytes($size)
{
	$k = array(
		'' => 1,
		'B' => 1,
		'K' => 1024,
		'M' => 1048576,
		'G' => 1073741824,
		'T' => 1099511627776
	);
	if ( preg_match('/([\d\.]+)\s*([BKMGT]|)\w*/i', $size, $m) )
		return (float)$m[1] * $k[strtoupper($m[2])];
	else
		return $size;
}
endif;

if (!function_exists('bytesToM')) :
function bytesToM($size, $units = 'M', $precision = 0)
{
	$k = array(
		'' => 1,
		'B' => 1,
		'K' => 1024,
		'M' => 1048576,
		'G' => 1073741824,
		'T' => 1099511627776
	);
	$units = strtoupper($units[0]);
	return round($size / $k[$units], $precision) . $units;
}
endif;

if (!function_exists('bytesToK')) :
function bytesToK($size, $units = 'K', $precision = 0)
{
	return bytesToM($size, $units, $precision);
}
endif;


// Get information about the operating system PHP is running on
$php_os = php_uname();

// Get MYSQL Version
$sqlversion = $wpdb->get_var("SELECT VERSION() AS version");

// GET SQL Mode
$mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
if (is_array($mysqlinfo)) $sql_mode = str_replace(',', ', ', $mysqlinfo[0]->Value);
if (empty($sql_mode)) $sql_mode = __('Not set', FLGALLERY_NAME);

// Get PHP Version
if ( version_compare(PHP_VERSION, '4.3.0', '<') )
	$php_version = sprintf('<strong class="red">%s</strong>', PHP_VERSION);
elseif ( version_compare(PHP_VERSION, '5.2.6', '<') || version_compare(PHP_VERSION, '5.4.0', '>') )
	$php_version = sprintf('<strong class="orange">%s</strong>', PHP_VERSION);
else
	$php_version = PHP_VERSION;

// Get PHP Safe Mode
if(ini_get('safe_mode')) $safe_mode = '<strong class="red">'.__('On', FLGALLERY_NAME).'</strong>';
else $safe_mode = __('Off', FLGALLERY_NAME);

// Get PHP allow_url_fopen
if(ini_get('allow_url_fopen')) $allow_url_fopen = __('On', FLGALLERY_NAME);
else $allow_url_fopen = __('Off', FLGALLERY_NAME);

// Get PHP Max Upload Size
if(ini_get('max_file_uploads')) $max_file_uploads = ini_get('max_file_uploads');
else $max_file_uploads = __('N/A', FLGALLERY_NAME);

// Get PHP Max Upload Size
if(ini_get('upload_max_filesize')) $upload_max = ini_get('upload_max_filesize');
else $upload_max = __('N/A', FLGALLERY_NAME);

// Get PHP Max Post Size
if(ini_get('post_max_size')) $post_max = ini_get('post_max_size');
else $post_max = __('N/A', FLGALLERY_NAME);

// Get PHP Max execution time
if(ini_get('max_execution_time')) $max_execute = ini_get('max_execution_time');
else $max_execute = __('N/A', FLGALLERY_NAME);

// Get PHP Memory Limit
if(ini_get('memory_limit'))
{
	$mem_limit = mToBytes(ini_get('memory_limit'));
	$memory_limit = bytesToM($mem_limit);
}
else
	$memory_limit = __('N/A', FLGALLERY_NAME);

// Get actual memory_get_usage
if (function_exists('memory_get_usage'))
{
	$mem = memory_get_usage();
	if ($mem_limit - $mem < mToBytes('8M'))
		$memory_usage = '<strong class="orange">'.bytesToM($mem).'</strong>';
	else
		$memory_usage = bytesToM($mem);
}
else
	$memory_usage = __('N/A', FLGALLERY_NAME);

// required for EXIF read
if (is_callable('exif_read_data')) $exif = __('Yes', FLGALLERY_NAME). " ( V" . substr(phpversion('exif'),0,4) . ")" ;
else $exif = __('No', FLGALLERY_NAME);

// required for meta data
if (is_callable('iptcparse')) $iptc = __('Yes', FLGALLERY_NAME);
else $iptc = __('No', FLGALLERY_NAME);

//if (is_callable('xml_parser_create')) $xml = __('Yes', FLGALLERY_NAME);
if (is_callable('simplexml_load_file')) $xml = __('Yes', FLGALLERY_NAME);
else $xml = __('No', FLGALLERY_NAME);

if ( function_exists('gd_info') )
{
	$gd_info = gd_info();
	$gd_version = $gd_info['GD Version'];
}
else
	$gd_version = '<strong class="red">'.__('N/A', FLGALLERY_NAME).'</strong>';
