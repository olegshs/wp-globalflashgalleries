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
		$galleryInfo,
		// Plugin directory
		$dir = FLGALLERY_PLUGIN_DIR,
		$url = FLGALLERY_PLUGIN_URL,
		// JavaScript
		$jsDir,
		$jsURL,
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
		// User
		$userID = 0,
		$userLevel = 0,
		$userLogin,
		$userName,
		$userDomain,
		// Database
		$dbPrefix,
		$dbAlbums,
		$dbGalleries,
		$dbImages,
		$dbSettings,
		// Cookie
		$cookie = false,
		$userCookie = false,
		// Error messages, debug info
		$printDebug = FLGALLERY_DEBUG,
		$printWarnings = FLGALLERY_WARNINGS,
		$printErrors = FLGALLERY_ERRORS,
		// Runtime statistics
		$stats;

	function init()
	{
		require_once FLGALLERY_INCLUDE.'/stats.class.php';
		$this->stats = new flgalleryStats();
		$this->stats->start();

		require_once FLGALLERY_INCLUDE.'/functions.class.php';
		$this->func = new flgalleryFunctions();

		require_once FLGALLERY_INCLUDE.'/site.class.php';
		$this->site = new flgallerySite();

		global $blog_id;
		$this->blogID = (int)$blog_id;

		require_once FLGALLERY_INCLUDE.'/templates.class.php';
		$this->tpl = new flgalleryTemplates( $this->tplDir, array('plugin' => &$this) );

		require_once FLGALLERY_INCLUDE.'/gallery.class.php';
		require_once FLGALLERY_INCLUDE.'/image.class.php';

		if ( defined('WP_ADMIN') )
		{
			require_once FLGALLERY_INCLUDE.'/admin.class.php';
			$this->admin = new flgalleryAdmin();

			require_once FLGALLERY_INCLUDE.'/media.class.php';
			$this->media = new flgalleryMedia();
		}

		global $wpdb;

		$this->dbPrefix = $wpdb->prefix. FLGALLERY_DB_PREFIX;
		$this->dbAlbums = $this->dbPrefix. FLGALLERY_DB_ALBUMS;
		$this->dbGalleries = $this->dbGal = $this->dbPrefix. FLGALLERY_DB_GALLERIES;
		$this->dbImages = $this->dbImg = $this->dbPrefix. FLGALLERY_DB_IMAGES;
		$this->dbSettings = $this->dbPrefix. FLGALLERY_DB_SETTINGS;

		$this->cookie = &$_COOKIE[$this->name];

		$this->jsURL = $this->url.'/js';
		$this->jsDir = str_replace(str_replace('\\', '/', ABSPATH), '/', str_replace('\\', '/', FLGALLERY_PLUGIN_DIR)).'/js';

		$this->imgDir = $this->contentDir.'/'.FLGALLERY_IMAGES;
		$this->imgURL = $this->contentURL.'/'.FLGALLERY_IMAGES;

		$this->xmlDir = $this->contentDir.'/'.FLGALLERY_XML;
		$this->xmlURL = $this->contentURL.'/'.FLGALLERY_XML;

		$this->uploadsDir = $this->contentDir.'/'.FLGALLERY_UPLOADS;
		$this->uploadsURL = $this->contentURL.'/'.FLGALLERY_UPLOADS;

		$this->tmpDir = $this->contentDir.'/'.FLGALLERY_TEMP;
		$this->tmpURL = $this->contentURL.'/'.FLGALLERY_TEMP;

		$this->imgBlacklistPath = $this->tmpDir.'/imgBlacklist.txt';

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
		//add_action( 'init', array(&$this, 'initGalleryInfo') );

		//$this->getUserInfo();
		add_action( 'init', array(&$this, 'getUserInfo') );
		add_action( 'wp_print_scripts', array(&$this, 'scripts') );

		if ( class_exists('WP_Widget') )	// WordPress 2.8 and newer
		{
			require_once FLGALLERY_INCLUDE.'/widget.class.php';
			add_action( 'widgets_init', create_function('', 'return register_widget("flgalleryWidget");') );
		}

		add_shortcode( $this->name, array(&$this, 'flashGallery') );
	}

	function initGalleryInfo()
	{
		if ( defined('FLGALLERY_PHP5') )
		{
			// PHP 5
			$galleries = simplexml_load_file(FLGALLERY_PLUGIN_DIR.'/galleries.xml');
		}
		else
		{
			// PHP 4
			require_once FLGALLERY_INCLUDE.'/simplexml.class.php';
			$simplexml = new simplexml();
			$galleries = $simplexml->xml_load_file(FLGALLERY_PLUGIN_DIR.'/galleries.xml');
		}

		foreach ($galleries->gallery as $gallery)
		{
			$galleryAtt = $gallery->attributes();
			$galleryPreviewAtt = $gallery->preview->attributes();
			$galleryDemoAtt = $gallery->demo->attributes();

			$this->galleryInfo[ (string)$galleryAtt->name ] = array(
				'src' => (string)$galleryAtt->src,
				'title' => addslashes( htmlspecialchars( (string)$gallery->title ) ),
				'description' => addslashes( htmlspecialchars( (string)$gallery->description ) ),
				'preview' => urlencode( (string)$galleryPreviewAtt->src ),
				'demo' => urlencode( (string)$galleryDemoAtt->href )
			);
		}

		$this->limitations = array(
			'3dSlideshow' => 15,
			'3dWall' =>		30,
			'Art' =>		15,
			'Aura' =>		15,
			'Box' =>		20,
			'Cubic' =>		20,
			'Line' =>		15,
			'Page' =>		15,
			'PhotoFlow' =>	15,
			'Promo' =>		15,
			'StackPhoto' =>	15,
			'Zen' =>		15
		);
	}

	function flashGallery($a, $content = NULL)
	{
		include FLGALLERY_GLOBALS;

		$gallery = new flgalleryGallery( $a['id'] );

		if ( !empty($this->galleryInfo[$gallery->type]) )
		{
			if ( !empty($a['popup']) )
			{
				$title = $gallery->name;

				if ( !empty($a['preview']) )
				{
					if ( preg_match('#(http[s]{0,1}://.*\.)(gif|jpg|jpeg|png)#', $a['preview'], $m) )
						$previewURL = $m[1].$m[2];
					else
						$previewURL = $a['preview'];

					$text = "<img src='{$previewURL}' alt='{$title}' title='{$title}' />";
				}
				else
				{
					if ( !empty($a['text']) )
						$text = $a['text'];
					else
						$text = &$title;
				}

				return $gallery->getPopupLink($text);
			}
			else
			{
				return $gallery->getHtml();
			}
		}
	}

	function checkDir($path)
	{
		if ( is_dir($path) )
		{
			if ( is_readable($path) && is_writable($path) )
				return true;
			else
			{
				if ( @chmod($path, 0777) )
					return true;
				else
				{
					$this->error( sprintf(__('Directory <strong>%s</strong> is not writeable. Please set directory permissions to 777.'), $path) );
					return false;
				}
			}
		}
		else
		{
			$this->warning( sprintf(__('Directory <strong>%s</strong> does not exists.'), $path) );

			if ( @mkdir($path, 0777) )
			{
				@chmod($path, 0777);
				/*file_put_contents($path.'/index.php', "<?php\n// Silence is golden.\n?>");*/
				return true;
			}
			else
			{
				$this->error( sprintf(__('Unable to create directory <strong>%s</strong>. Please create directory with permissions 777 manually.'), $path) );
				return false;
			}
		}
	}

	function getUserInfo()
	{
		if ( empty($this->userInfo) )
		{
			global $user_ID;
			get_currentuserinfo();

			if ( $user = get_userdata($user_ID) )
			{
				$this->userID = $user->ID;

				if ( !empty($user->user_level) )
					$this->userLevel = $user->user_level;
				else {
					$caps &= $user->{$wpdb->prefix.'capabilities'};
					if ( !empty($caps['administrator']) )
						$this->userLevel = 10;
					else if ( !empty($caps['editor']) )
						$this->userLevel = 7;
					else if ( !empty($caps['author']) )
						$this->userLevel = 4;
					else if ( !empty($caps['contributor']) )
						$this->userLevel = 1;
					else
						$this->userLevel = 0;
				}

				$this->userLogin = $user->user_login;
				$this->userName = $user->display_name;

				$this->userDomain = $this->name.'_user-'.$this->userID;
				$this->userCookie = &$_COOKIE[$this->userDomain];

				$this->userInfo = array(
					'id' => $this->userID,
					'login' => $this->userLogin,
					'name' => $this->userName
				);
			}
			else
				$this->userInfo = array();
		}

		return $this->userInfo;
	}

	function scripts()
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('swfobject', $this->jsDir.'/swfobject/swfobject.js', array(), '2.2');

		if ( function_exists('flgallery_commercial_getJS') && ($url = flgallery_commercial_getJS()) )
			wp_enqueue_script('altgallery', $url, array('jquery', 'swfobject'), null, true);
		else
			wp_enqueue_script('altgallery', $this->jsURL.'/altgallery.js', array('jquery', 'swfobject'), FLGALLERY_JS_VERSION, true);
	}

	function activate()
	{
		$this->createTables();

		$this->log( 'Activated '.FLGALLERY_NAME.' '.FLGALLERY_VERSION );
	}

	function deactivate()
	{
		if ($this->userLevel >= 10)
		{
			return deactivate_plugins(FLGALLERY_FILE, true);
		}
		return false;
	}

	function upgrade()
	{
		include FLGALLERY_GLOBALS;
		if (defined('FLGALLERY_SMODE')) eval(base64_decode(FLGALLERY_SMODE));

		$prevVersion = get_option(FLGALLERY_NAME.'_version', 0);
		$prevVersionValue = flgallery_versionValue($prevVersion);
		$currentVersionValue = flgallery_versionValue(FLGALLERY_VERSION);
		if ( empty($prevVersionValue) || $currentVersionValue != $prevVersionValue )
		{
			if ( !empty($prevVersionValue) )	// Upgrade old version
			{
				if ( $prevVersionValue < 80500 )	// 0.8.5
				{
					flgallery_clearXmlCache();
				}
				if ( $prevVersionValue < 140100 )	// 0.14.1
				{
					$this->upgradeTables();
				}

				$this->log( "Upgraded from {$prevVersion} to ".FLGALLERY_VERSION );
			}

			if ( !update_option(FLGALLERY_NAME.'_version', FLGALLERY_VERSION) )
				add_option(FLGALLERY_NAME.'_version', FLGALLERY_VERSION);
		}

		$this->points = array(
			'702992952378ff59898111d0b02feec5' => '01ccb68a74dd1edfbccbd76d86dbd51f',
			'5d78e83fbe2763d037af00793cd175d2' => '60086a5ff9176f9b3ebbe2d51b18d58f',
			'ed1c9043a107fa898d1c3178b4340514' => '8d2ed8adb7cc3acf598ea69600f2115b',
			'bf5b10c9b3643b627c0c44a1a913b230' => '8257fe03047e82b57406ed493c6e8913',
			'800f268da8c48174ff7c530a4bcdbf90' => '040f417515e6feb554f4e9efe6e38769',
			'e737c6925b1b5a67fd0151a7700df058' => '99fcdec5953f886a86709c16ea3f697d',
			'89ef23e4f1f74a328df21949b3322bf2' => 'fd352c65107ff1f3b22f03aa48bd053b',
			'f8ecc0e0d099d8bf14409ddf3c96bb78' => 'f0d101a2a09b93f2af88f31a5e95f1dd',
			'f9aefc55616dec6f5e21edbae694e3cf' => '3df957ba4928b954b29c025dfc01f315',
			'b9033361548598c115a7d27a01e94fe7' => 'c8abc3a52ed5a8b27aee346a77f4fc4d',
			'eea2f8f7263fb75fbd10dd930dc310a0' => '4e8e9b0fe6c343a2aabf404e39208981',
		);
	}

	function createTables()
	{
		global $wpdb;
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		$charset_collate = '';
		if ( $wpdb->supports_collation() )
		{
			if ( !empty($wpdb->charset) )
				$charset_collate = " DEFAULT CHARACTER SET {$wpdb->charset}";

			if ( !empty($wpdb->collate) )
				$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		// Albums
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_ALBUMS;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once FLGALLERY_INCLUDE.'/db.albums.php';
			dbDelta($query);
		}

		// Galleries
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_GALLERIES;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once FLGALLERY_INCLUDE.'/db.galleries.php';
			dbDelta($query);
		}

		// Images
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_IMAGES;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once FLGALLERY_INCLUDE.'/db.images.php';
			dbDelta($query);
		}

		// Settings
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_SETTINGS;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once FLGALLERY_INCLUDE.'/db.settings.php';
			dbDelta($query);
		}
	}

	function upgradeTables()
	{
		global $wpdb;
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		$charset_collate = '';
		if ( $wpdb->supports_collation() )
		{
			if ( !empty($wpdb->charset) )
				$charset_collate = " DEFAULT CHARACTER SET {$wpdb->charset}";

			if ( !empty($wpdb->collate) )
				$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		// Albums
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_ALBUMS;
		require_once FLGALLERY_INCLUDE.'/db.albums.php';
		dbDelta($query);

		// Galleries
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_GALLERIES;
		require_once FLGALLERY_INCLUDE.'/db.galleries.php';
		dbDelta($query);

		// Images
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_IMAGES;
		require_once FLGALLERY_INCLUDE.'/db.images.php';
		dbDelta($query);

		// Settings
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_SETTINGS;
		require_once FLGALLERY_INCLUDE.'/db.settings.php';
		dbDelta($query);
	}

	function dropTables()
	{
		if ($this->userLevel >= 10)
		{
			global $wpdb;

			$wpdb->query("DROP TABLE `{$this->dbAlbums}`");
			$wpdb->query("DROP TABLE `{$this->dbGalleries}`");
			$wpdb->query("DROP TABLE `{$this->dbImages}`");
			$wpdb->query("DROP TABLE `{$this->dbSettings}`");
		}
	}

	function uninstall()
	{
		if ($this->userLevel >= 10)
		{
			include FLGALLERY_GLOBALS;

			$this->dropTables();

			$func->unlinkRecurse($this->contentDir);

			delete_option(FLGALLERY_NAME.'_version');

			$this->deactivate();

			//$func->unlinkRecurse($this->dir);

			//$menuId = str_replace('.', '\.', str_replace('/', '-', get_plugin_page_hookname(plugin_basename(FLGALLERY_FILE), '') ));
			$menuId = 'toplevel_page_flgallery';
?>
			<h1 style='font-size:24px; line-height:50px; text-align:center; margin:5em 0;'>
				<?php echo $this->title; ?><br>
				<big style='color:#900; font-size:30px;'>Uninstalled.</big>
			</h1>
			<script type="text/javascript">//<![CDATA[
				var menu = document.getElementById('<?php echo $menuId; ?>');
				if (menu != null) menu.style.display = 'none';
				setTimeout('location.href="./plugins.php"', 3000);
			//]]></script>
<?php
			return true;
		}
		return false;
	}

	function log( $text )
	{
		if ( $log = @fopen(FLGALLERY_LOG, 'a') )
		{
			fwrite( $log, date('Y-m-d H:i:s')."\t{$text}\n" );
			fclose( $log );
		}
	}

}

?>