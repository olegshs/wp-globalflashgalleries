<?php

class flgalleryPlugin extends flgalleryBaseClass
{
	var
		// Plugin
		$name = FLGALLERY_NAME,
		$title = 'Global Flash Galleries',
		$shortTitle = 'Flash Galleries',
		$version = FLGALLERY_VERSION,
		// Gallery info from galleries.xml
		$galleryInfo = array(),
		// Plugin directory
		$dir = FLGALLERY_PLUGIN_DIR,
		$url = FLGALLERY_PLUGIN_URL,
		// JavaScript
		$jsDir,
		$jsURL,
		// Helpers
		$func,
		$admin,
		$media,
		// Templates
		$tpl,
		$tplDir = FLGALLERY_TPL_DIR,
		// Content
		$contentDir = FLGALLERY_CONTENT_DIR,
		$contentURL = FLGALLERY_CONTENT_URL,
		// Images
		$imgDir,
		$imgURL,
		// Blacklist of images processed with Fatal Error
		$imgBlacklistPath,
		// Gallery settings
		$xmlDir,
		$xmlURL,
		// Uploads
		$uploadsDir,
		$uploadsURL,
		// Temporary files
		$tmpDir,
		$tmpURL,
		// Site info
		$site,
		$blogID,
		$dateFormat,
		$timeFormat,
		// User
		$userInfo,
		$userID = 0,
		$userLevel = 0,
		$userLogin,
		$userName,
		$userDomain,
		// Database
		$dbPrefix,
		$dbAlbums,
		$dbGalleries,
		$dbGal,
		$dbImages,
		$dbImg,
		$dbSettings,
		// Cookie (base64-encoded JSON with per-user admin UI preferences)
		$userCookie = false,
		// Error messages, debug info
		$printDebug = FLGALLERY_DEBUG,
		$printWarnings = FLGALLERY_WARNINGS,
		$printErrors = FLGALLERY_ERRORS,
		// Runtime statistics
		$stats;

	function init()
	{
		require_once FLGALLERY_INCLUDE . '/stats.class.php';
		$this->stats = new flgalleryStats();
		$this->stats->start();

		require_once FLGALLERY_INCLUDE . '/functions.class.php';
		$this->func = new flgalleryFunctions();

		require_once FLGALLERY_INCLUDE . '/site.class.php';
		$this->site = new flgallerySite();

		$this->blogID = (int)get_current_blog_id();

		require_once FLGALLERY_INCLUDE . '/templates.class.php';
		$this->tpl = new flgalleryTemplates($this->tplDir, array('plugin' => &$this));

		require_once FLGALLERY_INCLUDE . '/gallery.class.php';
		require_once FLGALLERY_INCLUDE . '/image.class.php';

		if (defined('WP_ADMIN')) {
			require_once FLGALLERY_INCLUDE . '/admin.class.php';
			$this->admin = new flgalleryAdmin();

			require_once FLGALLERY_INCLUDE . '/media.class.php';
			$this->media = new flgalleryMedia();
		}

		global $wpdb;

		$this->dbPrefix = $wpdb->prefix . FLGALLERY_DB_PREFIX;
		$this->dbAlbums = $this->dbPrefix . FLGALLERY_DB_ALBUMS;
		$this->dbGalleries = $this->dbGal = $this->dbPrefix . FLGALLERY_DB_GALLERIES;
		$this->dbImages = $this->dbImg = $this->dbPrefix . FLGALLERY_DB_IMAGES;
		$this->dbSettings = $this->dbPrefix . FLGALLERY_DB_SETTINGS;

		$this->jsURL = $this->url . '/js';
		$this->jsDir = str_replace(str_replace('\\', '/', ABSPATH), '/', str_replace('\\', '/', FLGALLERY_PLUGIN_DIR)) . '/js';

		$this->imgDir = $this->contentDir . '/' . FLGALLERY_IMAGES;
		$this->imgURL = $this->contentURL . '/' . FLGALLERY_IMAGES;

		$this->xmlDir = $this->contentDir . '/' . FLGALLERY_XML;
		$this->xmlURL = $this->contentURL . '/' . FLGALLERY_XML;

		$this->uploadsDir = $this->contentDir . '/' . FLGALLERY_UPLOADS;
		$this->uploadsURL = $this->contentURL . '/' . FLGALLERY_UPLOADS;

		$this->tmpDir = $this->contentDir . '/' . FLGALLERY_TEMP;
		$this->tmpURL = $this->contentURL . '/' . FLGALLERY_TEMP;

		$this->imgBlacklistPath = $this->tmpDir . '/imgBlacklist.txt';

		$this->dateFormat = get_option('date_format');
		$this->timeFormat = get_option('time_format');

		// Check directories
		$this->checkDir($this->contentDir);
		$this->checkDir($this->imgDir);
		$this->checkDir($this->xmlDir);
		$this->checkDir($this->uploadsDir);
		$this->checkDir($this->tmpDir);

		// Upgrade
		$this->upgrade();

		$this->initGalleryInfo();

		add_action('init', array($this, 'getUserInfo'));
		add_action('wp_enqueue_scripts', array($this, 'registerScripts'));
		add_action('admin_enqueue_scripts', array($this, 'registerScripts'));

		require_once FLGALLERY_INCLUDE . '/widget.class.php';
		add_action('widgets_init', function () {
			register_widget('flgalleryWidget');
		});

		add_shortcode($this->name, array($this, 'flashGallery'));

		require_once FLGALLERY_INCLUDE . '/galleryXml.class.php';
		$galleryXml = new flgalleryGalleryXml();
		add_action('wp_ajax_flgalleryXml', array($galleryXml, 'getXml'));
		add_action('wp_ajax_nopriv_flgalleryXml', array($galleryXml, 'getXml'));

		require_once FLGALLERY_INCLUDE . '/galleryPopup.class.php';
		$galleryPopup = new flgalleryGalleryPopup();
		add_action('wp_ajax_flgalleryPopup', array($galleryPopup, 'render'));
		add_action('wp_ajax_nopriv_flgalleryPopup', array($galleryPopup, 'render'));

		// Multi-file uploader (logged-in users only)
		require_once FLGALLERY_INCLUDE . '/ajaxUpload.class.php';
		$ajaxUpload = new flgalleryAjaxUpload();
		add_action('wp_ajax_flgalleryUpload', array($ajaxUpload, 'upload'));
	}

	function initGalleryInfo()
	{
		$galleries = simplexml_load_file(FLGALLERY_PLUGIN_DIR . '/galleries.xml');

		foreach ($galleries->gallery as $gallery) {
			$galleryAtt = $gallery->attributes();
			$galleryPreviewAtt = $gallery->preview->attributes();
			$galleryDemoAtt = $gallery->demo->attributes();

			$this->galleryInfo[(string)$galleryAtt->name] = array(
				'title' => esc_html((string)$gallery->title),
				'description' => esc_html((string)$gallery->description),
				'preview' => esc_html((string)$galleryPreviewAtt->src),
				'demo' => esc_html((string)$galleryDemoAtt->href)
			);
		}

	}

	/**
	 * Is $type one of the gallery types defined in galleries.xml?
	 */
	function isGalleryType($type)
	{
		return is_string($type) && $type !== '' && isset($this->galleryInfo[$type]);
	}

	/**
	 * [flgallery id="N" popup="1" preview="URL" text="..."] shortcode handler.
	 * The name is kept for backward compatibility with themes and add-ons calling it directly.
	 */
	function flashGallery($a, $content = NULL)
	{
		$a = shortcode_atts(
			array(
				'id' => 0,
				'popup' => '',
				'preview' => '',
				'text' => '',
			),
			$a,
			$this->name
		);

		$gallery_id = (int)$a['id'];
		if (!$gallery_id) {
			return '';
		}

		$gallery = new flgalleryGallery($gallery_id);

		if (!$gallery->id || !$this->isGalleryType($gallery->type)) {
			return '';
		}

		if (!empty($a['popup'])) {
			$title = esc_attr($gallery->name);

			if (!empty($a['preview'])) {
				if (preg_match('#(https?://.*\.)(gif|jpg|jpeg|png|webp)#i', $a['preview'], $m)) {
					$previewURL = $m[1] . $m[2];
				} else {
					$previewURL = $a['preview'];
				}

				$previewURL = esc_url($previewURL);

				$text = "<img src='{$previewURL}' alt='{$title}' title='{$title}' />";
			} else {
				if (!empty($a['text'])) {
					$text = wp_kses_post($a['text']);
				} else {
					$text = esc_html($gallery->name);
				}
			}

			return $gallery->getPopupLink($text);
		}

		return $gallery->getHtml();
	}

	function checkDir($path)
	{
		if (is_dir($path)) {
			if (is_readable($path) && is_writable($path)) {
				return true;
			} else {
				if (@chmod($path, 0777)) {
					return true;
				} else {
					$this->error(sprintf(__('Directory <strong>%s</strong> is not writeable. Please set directory permissions to 777.'), $path));
					return false;
				}
			}
		} else {
			if (wp_mkdir_p($path)) {
				return true;
			} else {
				$this->error(sprintf(__('Unable to create directory <strong>%s</strong>. Please create directory with permissions 777 manually.'), $path));
				return false;
			}
		}
	}

	function getUserInfo()
	{
		if (empty($this->userInfo)) {
			$user = wp_get_current_user();

			if ($user && $user->exists()) {
				$this->userID = (int)$user->ID;

				// Legacy numeric user level (10 = administrator, 7 = editor, 2 = author, 1 = contributor),
				// derived from the level_N capabilities that WordPress still grants to every role.
				$this->userLevel = 0;
				for ($level = 10; $level > 0; $level--) {
					if ($user->has_cap("level_{$level}")) {
						$this->userLevel = $level;
						break;
					}
				}

				$this->userLogin = $user->user_login;
				$this->userName = $user->display_name;

				$this->userDomain = $this->name . '_user-' . $this->userID;
				$this->userCookie = isset($_COOKIE[$this->userDomain]) ? (string)$_COOKIE[$this->userDomain] : false;

				$this->userInfo = array(
					'id' => $this->userID,
					'login' => $this->userLogin,
					'name' => $this->userName
				);
			} else {
				$this->userInfo = array();
			}
		}

		return $this->userInfo;
	}

	/**
	 * Per-user admin UI preferences stored in a cookie.
	 */
	function getUserCookie()
	{
		if (!empty($this->userCookie)) {
			$data = json_decode(base64_decode((string)$this->userCookie, true) ?: '', true);
			if (is_array($data)) {
				return $data;
			}
		}
		return array();
	}

	function setUserCookie($data)
	{
		if (empty($this->userDomain)) {
			return false;
		}

		$this->userCookie = base64_encode(wp_json_encode($data));

		if (!headers_sent()) {
			return setcookie($this->userDomain, $this->userCookie, time() + 86400 * 24, '/', '', is_ssl(), true);
		}
		return false;
	}

	/**
	 * Registers the JavaScript gallery engine. It is enqueued only when a gallery is rendered.
	 */
	function registerScripts()
	{
		if (!wp_script_is('altgallery', 'registered')) {
			wp_register_script('altgallery', $this->getJsGalleryUrl(), array('jquery'), null, true);

			// altgallery.js uses jQuery methods removed in jQuery 3.0. WordPress restores them with
			// jQuery Migrate, but sites often disable it, so provide them if they are missing.
			wp_add_inline_script('altgallery',
				'(function($){' .
					'if(!$.fn.size){$.fn.size=function(){return this.length;};}' .
					'if(!$.fn.andSelf){$.fn.andSelf=$.fn.addBack;}' .
				'})(jQuery);',
				'before'
			);
		}
	}

	function enqueueScripts()
	{
		$this->registerScripts();
		wp_enqueue_script('altgallery');
	}

	/**
	 * Kept for backward compatibility.
	 */
	function scripts()
	{
		$this->enqueueScripts();
	}

	function getJsGalleryUrl()
	{
		if (function_exists('flgallery_commercial_getJS') && ($url = flgallery_commercial_getJS())) {
			$file = preg_replace('/^' . preg_quote(FLGALLERY_SITE_URL, '/') . '/', FLGALLERY_SITE_DIR, $url);
			if (file_exists($file) && ($mtime = filemtime($file))) {
				$url .= '?ver=' . date('Ymd', $mtime);
			}
			return $url;
		} else {
			return $this->jsURL . '/altgallery.js' . '?ver=' . FLGALLERY_JS_VERSION;
		}
	}

	function activate()
	{
		$this->createTables();

		$this->log('Activated ' . FLGALLERY_NAME . ' ' . FLGALLERY_VERSION);
	}

	function deactivate()
	{
		if (current_user_can('activate_plugins')) {
			return deactivate_plugins(plugin_basename(FLGALLERY_FILE), true);
		}
		return false;
	}

	function upgrade()
	{
		$prevVersion = get_option(FLGALLERY_NAME . '_version', 0);
		$prevVersionValue = flgallery_versionValue($prevVersion);
		$currentVersionValue = flgallery_versionValue(FLGALLERY_VERSION);
		if (empty($prevVersionValue) || $currentVersionValue != $prevVersionValue) {
			if (!empty($prevVersionValue)) // Upgrade old version
			{
				if ($prevVersionValue < 80500) // 0.8.5
				{
					flgallery_clearXmlCache();
				}
				if ($prevVersionValue < 140100) // 0.14.1
				{
					$this->upgradeTables();
				}
				if ($prevVersionValue < 150200) // 0.15.2
				{
					$this->dbStripSlashes();
				}
				if ($prevVersionValue < 160000) // 0.16.0: Flash support removed
				{
					$this->migrateRemovedGalleryTypes();
					flgallery_clearXmlCache();
				}

				$this->log("Upgraded from {$prevVersion} to " . FLGALLERY_VERSION);
			}

			update_option(FLGALLERY_NAME . '_version', FLGALLERY_VERSION);
		}
	}

	/**
	 * 3D Slideshow existed only as a Flash movie. Without Flash the JavaScript engine has always
	 * displayed such galleries as Box galleries, so they are converted to the Box type.
	 * Their old settings stay in the settings table (they are stored per gallery type).
	 */
	function migrateRemovedGalleryTypes()
	{
		global $wpdb;

		$res = $wpdb->query("UPDATE `{$this->dbGalleries}` SET `type` = 'Box' WHERE `type` = '3dSlideshow'");
		if ($res) {
			$this->log("Converted {$res} 3D Slideshow galleries to Box galleries");
		}
	}

	/**
	 * CREATE TABLE statements from inc/db.*.php, keyed by table name.
	 */
	function getTableSchemas()
	{
		global $wpdb;

		$charset_collate = ' ' . $wpdb->get_charset_collate();

		$schemas = array();
		foreach (array(FLGALLERY_DB_ALBUMS, FLGALLERY_DB_GALLERIES, FLGALLERY_DB_IMAGES, FLGALLERY_DB_SETTINGS) as $table) {
			$table_name = $wpdb->prefix . FLGALLERY_DB_PREFIX . $table;
			$query = '';
			include FLGALLERY_INCLUDE . "/db.{$table}.php";
			$schemas[$table_name] = $query;
		}

		return $schemas;
	}

	function createTables()
	{
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ($this->getTableSchemas() as $table_name => $query) {
			if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name))) != $table_name) {
				dbDelta($query);
			}
		}
	}

	function upgradeTables()
	{
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ($this->getTableSchemas() as $query) {
			dbDelta($query);
		}
	}

	function dbStripSlashes()
	{
		$this->dbStripSlashesInTable($this->dbAlbums, array('title', 'description'));
		$this->dbStripSlashesInTable($this->dbGalleries, array('name'));
		$this->dbStripSlashesInTable($this->dbImages, array('name', 'title', 'description', 'link'));
		$this->dbStripSlashesInTable($this->dbSettings, array('value'));
	}

	function dbStripSlashesInTable($table, $fields)
	{
		global $wpdb;

		foreach ($fields as $field) {
			$wpdb->query("UPDATE `{$table}` SET `{$field}` = REPLACE(`{$field}`, '\\\\\\'', '\\'') WHERE `{$field}` LIKE '%\\\\\\'%'");    // \' -> '
			$wpdb->query("UPDATE `{$table}` SET `{$field}` = REPLACE(`{$field}`, '\\\\\"', '\"') WHERE `{$field}` LIKE '%\\\\\"%'");       // \" -> "
			$wpdb->query("UPDATE `{$table}` SET `{$field}` = REPLACE(`{$field}`, '\\\\\\\\', '\\\\') WHERE `{$field}` LIKE '%\\\\\\\\%'"); // \\ -> \
		}
	}

	function dropTables()
	{
		if (current_user_can('delete_plugins')) {
			global $wpdb;

			$wpdb->query("DROP TABLE IF EXISTS `{$this->dbAlbums}`");
			$wpdb->query("DROP TABLE IF EXISTS `{$this->dbGalleries}`");
			$wpdb->query("DROP TABLE IF EXISTS `{$this->dbImages}`");
			$wpdb->query("DROP TABLE IF EXISTS `{$this->dbSettings}`");
		}
	}

	function uninstall()
	{
		if (current_user_can('delete_plugins')) {
			include FLGALLERY_GLOBALS;

			$this->dropTables();

			$func->unlinkRecurse($this->contentDir);

			delete_option(FLGALLERY_NAME . '_version');

			$this->deactivate();

			$menuId = 'toplevel_page_flgallery';
?>
			<h1 style='font-size:24px; line-height:50px; text-align:center; margin:5em 0;'>
				<?php echo esc_html($this->title); ?><br>
				<big style='color:#900; font-size:30px;'>Uninstalled.</big>
			</h1>
			<script type="text/javascript">//<![CDATA[
				var menu = document.getElementById('<?php echo $menuId; ?>');
				if (menu != null) menu.style.display = 'none';
				setTimeout(function () { location.href = './plugins.php'; }, 3000);
			//]]></script>
<?php
			return true;
		}
		return false;
	}

	function log($text)
	{
		if ($log = @fopen(FLGALLERY_LOG, 'a')) {
			fwrite($log, date('Y-m-d H:i:s') . "\t{$text}\n");
			fclose($log);
		}
	}
}
