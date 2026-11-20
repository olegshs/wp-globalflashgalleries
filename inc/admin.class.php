<?php if (defined('WP_ADMIN')) {

class flgalleryAdmin extends flgalleryBaseClass
{
	var $page;

	function init()
	{
		require_once FLGALLERY_INCLUDE . '/adminPage.class.php';
		$this->page = new flgalleryAdminPage();

		if (!empty($_REQUEST['page']) && in_array($_REQUEST['page'], array('flgallery', 'flgallery/media', 'flgallery/about'), true)) {
			add_action('admin_init', array($this, 'wpInit'));
			add_action('admin_enqueue_scripts', array($this, 'scripts'));
		}

		add_action('admin_menu', array($this, 'menu'));
		add_action('wp_ajax_flgalleryAdmin', array($this, 'ajax'));
	}

	function wpInit()
	{
		include FLGALLERY_GLOBALS;

		if (!empty($_REQUEST['orderBy']) || !empty($_REQUEST['order'])) {
			$cookie = $plugin->getUserCookie();
			$cookie['albumsList'] = array(
				'orderBy' => sanitize_key($_REQUEST['orderBy'] ?? ''),
				'order' => sanitize_key($_REQUEST['order'] ?? ''),
			);
			$plugin->setUserCookie($cookie);
		}

		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		switch ($action) {
			case 'downloadImage':
				$image_id = (int)($_REQUEST['image_id'] ?? 0);

				// gallery_id = 0 for pictures in Media Library albums
				$gallery_id = (int)($_REQUEST['gallery_id'] ?? 0);
				$gallery = new flgalleryGallery($gallery_id);
				if ($gallery->id == $gallery_id) {
					$admin->downloadImage($image_id);
				}

				break;

			default:
				break;
		}
	}

	function menu()
	{
		include FLGALLERY_GLOBALS;

		add_menu_page(
			$plugin->title,
			$plugin->shortTitle,
			'edit_posts',
			$plugin->name,
			array($this, 'manage'),
			FLGALLERY_PLUGIN_URL.'/img/gallery-icon-16x16.png'
		);
		add_submenu_page(
			$plugin->name,
			__('Manage Galleries', $plugin->name),
			__('Galleries', $plugin->name),
			'edit_posts',
			$plugin->name,
			array($this, 'manage')
		);
		add_submenu_page(
			$plugin->name,
			__('Media Library', $plugin->name),
			__('Media', $plugin->name),
			'edit_posts',
			$plugin->name.'/media',
			array($plugin->media, 'manage')
		);
		add_submenu_page(
			$plugin->name,
			__('About', $plugin->name).' '.$plugin->title,
			__('About', $plugin->name),
			'edit_posts',
			$plugin->name.'/about',
			array($this, 'about')
		);
	}

	function scripts()
	{
		include FLGALLERY_GLOBALS;

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-widget');
		wp_enqueue_script('jquery-ui-mouse');
		wp_enqueue_script('jquery-ui-position');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('jquery-ui-resizable');
		wp_enqueue_script('jquery-ui-slider');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script($plugin->name . '-farbtastic', $plugin->jsURL . '/farbtastic.js', array('jquery'), '1.2');

		// JavaScript gallery engine for the gallery preview
		$plugin->registerScripts();

		wp_enqueue_style($plugin->name . '-jquery-ui', $plugin->url . '/css/jquery/jquery-ui.css', array(), FLGALLERY_VERSION);
		wp_enqueue_style($plugin->name . '-farbtastic', $plugin->url . '/css/farbtastic/farbtastic.css', array(), FLGALLERY_VERSION);
		wp_enqueue_style($plugin->name . '-admin', $plugin->url . '/css/admin.css', array(), FLGALLERY_VERSION);
	}

	/**
	 * Gallery name, type, size and settings from the "Gallery Options" form.
	 */
	function updateGalleryFromRequest($gallery)
	{
		include FLGALLERY_GLOBALS;

		$typeChanged = false;

		if (!empty($_POST['gallery']) && is_array($_POST['gallery'])) {
			$data = wp_unslash($_POST['gallery']);
			$prevType = $gallery->type;

			if (isset($data['name'])) {
				$gallery->name = sanitize_text_field($data['name']);
			}
			if (isset($data['type']) && $plugin->isGalleryType($data['type'])) {
				$gallery->type = $data['type'];
			}
			if (isset($data['width'])) {
				$gallery->width = max(0, (int)$data['width']);
			}
			if (isset($data['height'])) {
				$gallery->height = max(0, (int)$data['height']);
			}

			$gallery->save();

			$typeChanged = $gallery->type != $prevType;
		}

		if (!empty($_POST['settings']) && is_array($_POST['settings']) && !$typeChanged) {
			$settings = wp_unslash($_POST['settings']);

			$gallery->getSettings();

			foreach ((array)$gallery->settingsInfo as $key => $param) {
				if (isset($settings[$key])) {
					$gallery->settings[$key] = sanitize_text_field($settings[$key]);
				} else {
					$paramInputAtt = $param->input->attributes();
					if ((string)$paramInputAtt->type == 'checkbox') {
						$values = explode('|', (string)$paramInputAtt->value);
						$gallery->settings[$key] = isset($values[1]) ? trim($values[1]) : '';
					}
				}
			}

			$gallery->saveSettings();
		}

		return $typeChanged;
	}

	function manage()
	{
		include FLGALLERY_GLOBALS;
		$plugin->getUserInfo();

		$action = empty($_REQUEST['action']) ? NULL : $_REQUEST['action'];

		if (!empty($_REQUEST['gallery_id'])) // Gallery actions
		{
			$gallery_id = (int)$_REQUEST['gallery_id'];
			$gallery = new flgalleryGallery($gallery_id);
			if ($gallery->id == $gallery_id) {
				$admin->galleryAction($gallery, $action);
			} else {
				echo '<h1>' . __('Access Denied.') . '</h1>';
			}
		} else {
			switch ($action) {
				case 'addNewGallery':
					$admpage->head('Create', 'new-gallery');
					$admpage->newGallery();
					break;

				case 'createGallery':
					if (!empty($_POST['OK'])) {
						$data = array(
							'name' => sanitize_text_field(wp_unslash($_POST['gallery']['name'] ?? '')),
							'type' => sanitize_text_field(wp_unslash($_POST['gallery']['type'] ?? ''))
						);
						if ($admin->createGallery($data)) {
							$func->locationReset();
						}
					}
					$admpage->manageGalleries();
					break;

				default:
					$admpage->manageGalleries();
			}
		}

		$admpage->foot();
	}

	function galleryAction($gallery, $action = '')
	{
		include FLGALLERY_GLOBALS;

		switch ($action) {
			case 'arrangeGallery':
				$gallery->arrange(isset($_POST['order']) ? (string)$_POST['order'] : 'asc');
				$admpage->manageGalleries();
				break;

			case 'galleryOptions':
				$admpage->head('Gallery Options', 'gallery-options');
				$admpage->galleryOptions($gallery);
				break;

			case 'changeGalleryOptions':
				if (!empty($_POST['OK']) || !empty($_POST['update']) || !empty($_POST['exportXML'])) {
					$admin->updateGalleryFromRequest($gallery);

					if (!empty($_POST['update'])) {
						$admpage->head('Gallery Options', 'gallery-options');
						$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
						$admpage->galleryOptions($gallery);
						break;
					}

					if (!empty($_POST['exportXML'])) {
						$admpage->manageGalleries();
						$xmlUrl = admin_url('admin-ajax.php') . "?action=flgalleryXml&gallery_id={$gallery->id}&blog_id={$plugin->blogID}&download";
						$func->redirect($xmlUrl);
						break;
					}
				}

				if (!empty($_POST['resetOptions'])) {
					$gallery->resetSettings();

					$admpage->head('Gallery Options', 'gallery-options');
					$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
					$admpage->galleryOptions($gallery);
					break;
				}

				$func->locationReset("&gallery_id={$gallery->id}#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'uploadImage':
				$admin->uploadImage();
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'editImage':
				$admpage->head('Picture Properties', 'image-properties');
				$image_id = (int)($_REQUEST['image_id'] ?? 0);
				$admpage->editImage($image_id);
				break;

			case 'saveImage':
				if (!empty($_POST['OK']) && !empty($_POST['image'])) {
					$image_id = (int)($_POST['image_id'] ?? 0);
					$applyToCopies = !empty($_POST['applyToCopies']);
					$data = array(
						'name' => sanitize_file_name(wp_unslash($_POST['image']['name'] ?? '')),
						'title' => sanitize_text_field(wp_unslash($_POST['image']['title'] ?? '')),
						'description' => sanitize_text_field(wp_unslash($_POST['image']['description'] ?? '')),
						'link' => esc_url_raw(wp_unslash($_POST['image']['link'] ?? '')),
						'target' => sanitize_text_field(wp_unslash($_POST['image']['target'] ?? ''))
					);
					if ($media->saveImage($image_id, $data, $applyToCopies)) {
						$gallery->save();
					}
				}
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'downloadImage':
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'deleteGallery':
				if (!empty($_POST['gallery_id'])) {
					$gallery->delete();
				}
				$func->locationReset('');
				$admpage->manageGalleries();
				break;

			case 'deleteImage':
				if (wp_verify_nonce($_REQUEST['nonce'] ?? '', 'deleteImage')) {
					$image_id = (int)($_REQUEST['image_id'] ?? 0);
					$admin->deleteImage($image_id, $gallery);
				}
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'uploadPage':
				$media->uploadPage(array('gallery_id' => $gallery->id));
				break;

			case 'upload':
				if (!empty($_POST['OK'])) {
					$media->uploadPictures(array('gallery_id' => $gallery->id));
					$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
					$admpage->manageGalleries();
				} else {
					$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
					$admpage->manageGalleries();
				}
				break;

			case 'addImages':
				$images = isset($_POST['images']) && is_array($_POST['images']) ? array_map('intval', $_POST['images']) : array();

				switch ($_POST['order'] ?? '') {
					case 'before':
						$order = (int)$wpdb->get_var("
							SELECT MIN(`order`)
							FROM `{$plugin->dbImages}`
							WHERE `gallery_id` = '{$gallery->id}'
						");
						$orderInc = -1;
						$images = array_reverse($images);
						break;

					default:
						$order = (int)$wpdb->get_var("
							SELECT MAX(`order`)
							FROM `{$plugin->dbImages}`
							WHERE `gallery_id` = '{$gallery->id}'
						");
						$orderInc = 1;
						break;
				}

				foreach ($images as $image_id) {
					$order += $orderInc;
					$media->copyImage($image_id, array('gallery_id' => $gallery->id, 'order' => $order));
				}

				$gallery->save();

				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			default:
				$admpage->manageGalleries();
				break;
		}
	}

	function ajax()
	{
		include FLGALLERY_GLOBALS;
		$plugin->getUserInfo();

		if (!current_user_can('edit_posts')) {
			wp_die('', '', array('response' => 403));
		}

		$request = array_merge($_GET, $_POST);

		if (isset($request['gallery_id']) && is_array($request['gallery_id']))
			$request['gallery_id'] = $request['gallery_id'][0];

		switch ($request['ajax_action'] ?? '') {
			case 'getGalleryItemsHtml':
				if (!empty($request['gallery_id'])) {
					$gallery = new flgalleryGallery((int)$request['gallery_id']);
				} else {
					$gallery = null;
				}

				echo $admpage->getGalleryItemsHtml($gallery);
				break;

			case 'getAlbumItemsHtml':
				echo $media->getAlbumItemsHtml((int)($request['album_id'] ?? 0));
				break;

			case 'sortImages':
				if (!empty($_POST['gallery_id'])) {
					$admin->sortImages($request);
				}
				break;

			case 'deleteImage':
				if (wp_verify_nonce($request['nonce'] ?? '', 'deleteImage')) {
					$gallery = !empty($request['gallery_id']) ? new flgalleryGallery((int)$request['gallery_id']) : null;
					$this->deleteImage((int)($request['image_id'] ?? 0), $gallery);
				}
				break;

			case 'selectPictures':
				$media->selectPictures($request);
				break;

			case 'addNewAlbum':
				$media->addNewAlbum();
				break;

			case 'editAlbum':
				$media->editAlbum((int)($request['album_id'] ?? 0));
				break;

			case 'editImage':
				$image_id = (int)($request['image_id'] ?? 0);
				$admpage->head('Picture Properties', 'image-properties');
				$admpage->editImage($image_id);
				break;

			case 'getWpMediaLibraryJson':
				$offset = (int)($request['offset'] ?? 0);
				$limit = (int)($request['limit'] ?? 30);
				$media->getWpMediaLibraryJson($offset, $limit);
				break;
		}

		exit();
	}

	function about()
	{
		include FLGALLERY_GLOBALS;

		$action = empty($_REQUEST['action']) ? NULL : $_REQUEST['action'];
		switch ($action) {
			case 'uninstallPlugin':
				if (!empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'flgallery-uninstall') && $plugin->uninstall()) {
					return true;
				}
			default:
				$admpage->head($plugin->title, 'about');
				$admpage->about();
				break;
		}
		$admpage->foot();
	}

	function createGallery($data = NULL)
	{
		if (!empty($data)) {
			$gallery = new flgalleryGallery($data);
			if ($id = $gallery->save()) {
				return $id;
			}
		}
		return false;
	}

	function uploadImage()
	{
		include FLGALLERY_GLOBALS;

		if (empty($_FILES['image']['name']) || is_array($_FILES['image']['name'])) {
			return false;
		}

		$path = $media->moveUploadedImage($_FILES['image']['tmp_name'], $_FILES['image']['name'], $plugin->imgDir);
		if (!$path) {
			$this->error(sprintf(__('%s is not a JPEG, PNG or GIF picture and was skipped.', $plugin->name), esc_html($_FILES['image']['name'])));
			return false;
		}

		$gallery_id = (int)($_REQUEST['gallery_id'] ?? 0);
		$album_id = (int)($_REQUEST['album_id'] ?? 0);
		$toTop = ($_REQUEST['to'] ?? '') == 'top';

		$max = $toTop ? 'MIN' : 'MAX';

		$order = (int)$wpdb->get_var("
			SELECT {$max}(`order`)
			FROM `{$plugin->dbImages}`
			WHERE `gallery_id` = '{$gallery_id}'
		");

		$order += $toTop ? -1 : 1;

		$imageSize = getimagesize($plugin->imgDir . '/' . $path);

		$insert = $wpdb->insert(
			$plugin->dbImages,
			array(
				'album_id' => $album_id,
				'gallery_id' => $gallery_id,
				'order' => $order,
				'type' => $imageSize['mime'],
				'path' => $path,
				'name' => sanitize_file_name($_FILES['image']['name']),
				'title' => '',
				'description' => '',
				'link' => '',
				'target' => '',
				'width' => (int)$imageSize[0],
				'height' => (int)$imageSize[1],
				'size' => (int)$_FILES['image']['size']
			)
		);
		if ($insert !== false) {
			$gallery = new flgalleryGallery($gallery_id);
			$gallery->save();
			return $wpdb->insert_id;
		} else {
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $this->errorN));
			return false;
		}
	}

	function downloadImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image_id = (int)$image_id;

		$image = $wpdb->get_row("
			SELECT `type`, `name`, `path`
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image) {
			if (strpos($image->path, '/') === 0) {
				$file = ABSPATH . ltrim($image->path, '/');
			} else {
				$file = $plugin->imgDir . '/' . $image->path;
			}

			if (!is_file($file)) {
				return false;
			}

			$filename = sanitize_file_name($image->name);

			nocache_headers();
			header("Content-Type: {$image->type}");
			header("Content-Disposition: attachment; filename=\"{$filename}\"");
			header('Content-Length: ' . filesize($file));

			readfile($file);
			exit;
		} else {
			return false;
		}
	}

	function sortImages($a)
	{
		$order = explode('&', (string)($a['images_order'] ?? ''));

		$gallery_id = (int)($a['gallery_id'] ?? 0);

		if (!empty($gallery_id)) {
			$gallery = new flgalleryGallery($gallery_id);
			if ($gallery->arrange($order)) {
				$gallery->save();
			}
		}
	}

	function deleteImage($image_id, $gallery = null)
	{
		include FLGALLERY_GLOBALS;

		$image_id = (int)$image_id;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image) {
			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbImages}`
				WHERE `id` = '{$image_id}'
			");
			if ($res !== false) {
				$copies = $wpdb->get_results($wpdb->prepare("
					SELECT *
					FROM `{$plugin->dbImages}`
					WHERE `path` = %s
				", $image->path));

				if (is_array($copies) && count($copies) == 0) {
					if (!preg_match('/(.*)(\..*)/', $image->path, $fname)) {
						$fname = array($image->path, $image->path, '');
					}

					if (strpos($image->path, '/') === 0) {
						$fname[1] = md5($fname[1]);
					} else {
						// Delete image
						@unlink($plugin->imgDir . '/' . $image->path);
					}

					// Delete thumbnails
					$func->recurse($plugin->tmpDir, '#^img-' . preg_quote($fname[1], '#') . '\..+#i', 'unlink');
				}

				if ($gallery) {
					$gallery->save();
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

		return $image->id;
	}

	function printMessages($a, $class)
	{
		if (!empty($a)) {
			foreach ($a as $sender => $events) {
				print
					"<div class='flgallery-{$class}'>" .
					"<h4><em>{$sender}</em> <strong>{$class}</strong>:</h4>\n";

				foreach ($events as $e) {
					echo "\n<div>{$e}</div>";
				}

				print "</div>\n";
			}
		}
	}

	function printErrors()
	{
		global $flgalleryErrors;
		$this->printMessages($flgalleryErrors, 'errors');
	}

	function printWarnings()
	{
		global $flgalleryWarnings;
		$this->printMessages($flgalleryWarnings, 'warnings');
	}

	function printDebug()
	{
		global $flgalleryDebug;
		$this->printMessages($flgalleryDebug, 'debug');
	}
}

}
