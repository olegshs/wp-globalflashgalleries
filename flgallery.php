<?php
/*
Plugin Name: Global Flash Gallery
Plugin URI: http://flash-gallery.com/wordpress-plugin/
Description: Global Flash Galleries plugin is designed for quick and easy creating and publishing galleries and slideshows. There are convenient tools for uploading and managing images. This plugin includes 10 different JavaScript galleries. Each gallery can be customized according to your preferences.
Version: 0.16.0
Requires at least: 6.0
Requires PHP: 8.2
Author: Flash Gallery Team
Author URI: http://flash-gallery.com/
*/

if (!defined('ABSPATH')) {
	exit;
}

define( 'FLGALLERY_VERSION', '0.16.0' );
define( 'FLGALLERY_JS_VERSION', '0.13.0' );

require_once dirname(__FILE__).'/config.php';

define( 'FLGALLERY_URL_SCHEME', is_ssl() ? 'https://' : 'http://' );

define( 'FLGALLERY_FILE', __FILE__ );
define( 'FLGALLERY_HREF', admin_url('admin.php').(empty($_REQUEST['page']) || !is_string($_REQUEST['page']) ? '' : '?page='.preg_replace('#[^\w/-]#', '', $_REQUEST['page'])) );

// Site directory
define( 'FLGALLERY_SITE_DIR', realpath(ABSPATH) );
define( 'FLGALLERY_SITE_URL', get_option('siteurl') );
// Plugin directory
define( 'FLGALLERY_PLUGIN_DIR', dirname(__FILE__) );
define( 'FLGALLERY_PLUGIN_URL', plugins_url(basename(dirname(__FILE__))) );
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

// Explicitly global: WP-CLI and some other loaders include plugin files from inside a function
global $flgalleryPlugin;
$flgalleryPlugin = new flgalleryPlugin();

register_activation_hook( __FILE__, array(&$flgalleryPlugin, 'activate') );

