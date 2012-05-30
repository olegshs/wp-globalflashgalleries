<?php
require_once 'wp-load.php';
require_once ABSPATH.'wp-admin/admin.php';

if ( !current_user_can('install_plugins') )
	wp_die(__('You do not have sufficient permissions to install plugins on this blog.'));

require_once 'config.php';

function showResult( $result, &$wpdb )
{
	$query = htmlspecialchars($wpdb->last_query);
	if ( $result !== false )
	{
		echo "<div><div style='color:gray; margin:0.3em 0 0.2em;'><code>{$query}</code></div>\n<div style='color:green;'>OK</div></div>\n<br/>\n";
	}
	else
	{
		$error = htmlspecialchars($wpdb->last_error);
		echo "<div><div style='color:gray; margin:0.3em 0 0.2em;'><code>{$query}</code></div>\n<div style='color:red;'>{$error}</div></div>\n<br/>\n";
	}
}

$charset_collate = '';
if ( $wpdb->supports_collation() )
{
	if ( !empty($wpdb->charset) )
		$charset_collate = " DEFAULT CHARACTER SET {$wpdb->charset}";

	if ( !empty($wpdb->collate) )
		$charset_collate .= " COLLATE {$wpdb->collate}";
}

/*
 *	Albums
 */
$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_ALBUMS;
echo "<h3 style='margin:0;'>Creating table '{$table_name}'</h3>\n";
require_once 'inc/db.albums.php';
$res = $wpdb->query($query);
showResult($res, $wpdb);

/*
 *	Galleries
 */
$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_GALLERIES;
echo "<h3 style='margin:0;'>Creating table '{$table_name}'</h3>\n";
require_once 'inc/db.galleries.php';
$res = $wpdb->query($query);
showResult($res, $wpdb);

/*
 *	Images
 */
$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_IMAGES;
echo "<h3 style='margin:0;'>Creating table '{$table_name}'</h3>\n";
require_once 'inc/db.images.php';
$res = $wpdb->query($query);
showResult($res, $wpdb);

/*
 *	Settings
 */
$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_SETTINGS;
echo "<h3 style='margin:0;'>Creating table '{$table_name}'</h3>\n";
require_once 'inc/db.settings.php';
$res = $wpdb->query($query);
showResult($res, $wpdb);

?>