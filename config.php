<?php

@ini_set('memory_limit', '256M');

define('FLGALLERY_NAME', 'flgallery');

// Messages
define('FLGALLERY_DEBUG', isset($_GET['debug']));   // Show debug info,
define('FLGALLERY_WARNINGS', true);                 // warnings,
define('FLGALLERY_ERRORS', true);                   // errors

// Database
define('FLGALLERY_DB_PREFIX', FLGALLERY_NAME.'_');  // Database table prefix
define('FLGALLERY_DB_GALLERIES', 'galleries');      // Table for galleris,
define('FLGALLERY_DB_SETTINGS', 'settings');        // gallery settings,
define('FLGALLERY_DB_IMAGES', 'images');            // images,
define('FLGALLERY_DB_ALBUMS', 'albums');            // albums

// Directory names
define('FLGALLERY_CONTENT', FLGALLERY_NAME);        // Name of directory with content,
define('FLGALLERY_IMAGES', 'images');               // images,
define('FLGALLERY_XML', 'xml');                     // XML,
define('FLGALLERY_TPL', 'tpl');                     // templates,
define('FLGALLERY_UPLOADS', 'uploads');             // uploads,
define('FLGALLERY_TEMP', 'tmp');                    // temporary files

// Settings
define('flgallery_defaultImageQuality', 85);        // JPEG quality of resized images

// PHP version
if (version_compare(PHP_VERSION, '5.1', '>=')) {
	define('FLGALLERY_PHP5', true);
} else {
	define('FLGALLERY_PHP4', true);
}

?>