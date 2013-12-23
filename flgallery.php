<?php
/*
Plugin Name: Global Flash Gallery
Plugin URI: http://flash-gallery.com/wordpress-plugin/
Description: Global Flash Galleries plugin is designed for quick and easy creating and publishing flash galleries and slideshows. There are convenient tools for uploading and managing images. This plugin includes 11 different galleries. Each gallery can be customized according to your preferences.
Version: 0.13.3
Author: Flash Gallery Team
Author URI: http://flash-gallery.com/
*/

define( 'FLGALLERY_VERSION', '0.13.3' );
define( 'FLGALLERY_JS_VERSION', '0.9.2' );

require_once dirname(__FILE__).'/config.php';

define( 'FLGALLERY_URL_SCHEME', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://'	);

define( 'FLGALLERY_FILE', __FILE__ );
define( 'FLGALLERY_HREF', admin_url('admin.php').(empty($_REQUEST['page']) ? '' : '?page='.$_REQUEST['page']) );

// Site directory
define( 'FLGALLERY_SITE_DIR', realpath(ABSPATH) );
define( 'FLGALLERY_SITE_URL', get_option('siteurl') );
// Plugin directory
define( 'FLGALLERY_PLUGIN_DIR', dirname(__FILE__) );
define( 'FLGALLERY_PLUGIN_URL', plugins_url('', __FILE__) );
// Include
define( 'FLGALLERY_INCLUDE', FLGALLERY_PLUGIN_DIR.'/inc' );
// Global variables
define( 'FLGALLERY_GLOBALS', FLGALLERY_INCLUDE.'/globals.php' );
// Templates
define( 'FLGALLERY_TPL_DIR', FLGALLERY_PLUGIN_DIR.'/tpl' );
// Content, images, etc.
define( 'FLGALLERY_CONTENT_DIR', WP_CONTENT_DIR.'/'.FLGALLERY_CONTENT );
define( 'FLGALLERY_CONTENT_URL', WP_CONTENT_URL.'/'.FLGALLERY_CONTENT );

define( 'FLGALLERY_LOG', FLGALLERY_CONTENT_DIR.'/log.txt' );

require_once FLGALLERY_INCLUDE.'/functions.php';
require_once FLGALLERY_INCLUDE.'/base.class.php';
require_once FLGALLERY_INCLUDE.'/plugin.class.php';

$flgalleryPlugin = new flgalleryPlugin();

register_activation_hook( __FILE__, array(&$flgalleryPlugin, 'activate') );

?>