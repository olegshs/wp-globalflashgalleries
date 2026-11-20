<?php

class flgalleryMedia extends flgalleryBaseClass
{
	var
		$files = array(),
		$filesInfo = array();

	// Picture types that can be added to albums and galleries
	var $imageExtensions = '#\.(jpe?g|png|gif)$#i';

	function manage()
	{
		include FLGALLERY_GLOBALS;
		$plugin->getUserInfo();

		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		if (!empty($_REQUEST['doaction2'])) {
			$action = empty($_REQUEST['action2']) ? '' : $_REQUEST['action2'];
		}

		$album_id = empty($_REQUEST['album_id']) ? 0 : (int)$_REQUEST['album_id'];

		switch ($action) {

// Albums
			case 'addNewAlbum':
				$media->addNewAlbum();
				break;

			case 'createAlbum':
				if (!empty($_POST['OK'])) {
					$data = array(
						'title' => sanitize_text_field(wp_unslash($_POST['album']['title'] ?? '')),
						'description' => sanitize_text_field(wp_unslash($_POST['album']['description'] ?? ''))
					);
					$album_id = $media->createAlbum($data);
					if ($album_id) {
						$func->locationReset('&action=editAlbum&album_id=' . $album_id);
						break;
					}
				}
				$func->locationReset('&tab=albums');
				$media->mainPage();
				break;

			case 'editAlbum':
				if (!empty($album_id)) {
					$media->editAlbum($album_id);
				} else {
					$media->mainPage('albums');
				}
				break;

			case 'updateAlbum':
				if (!empty($_POST['update'])) {
					if (!empty($album_id)) {
						$data = array(
							'title' => sanitize_text_field(wp_unslash($_POST['album']['title'] ?? '')),
							'description' => sanitize_text_field(wp_unslash($_POST['album']['description'] ?? '')),
							'modified' => $func->now()
						);

						if (!strlen(trim($data['title']))) {
							$data['title'] = 'Untitled';
						}

						$wpdb->update($plugin->dbAlbums, $data, array('id' => $album_id));
					}
					$func->locationReset('&action=editAlbum&album_id=' . $album_id);
					$media->editAlbum($album_id);
				} else {
					$media->mainPage('albums');
				}
				break;

			case 'deleteAlbum':
				if (!empty($_POST['album_id'])) {
					$media->deleteAlbum($album_id);
				}
				$func->locationReset('&tab=albums');
				$media->mainPage();
				break;

// Pictures
			case 'editImage':
				$image_id = (int)($_REQUEST['image_id'] ?? 0);
				$admpage->head('Picture Properties', 'image-properties');
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
					$media->saveImage($image_id, $data, $applyToCopies);
				}
				$func->locationReset('&action=editAlbum&album_id=' . $album_id);
				$media->editAlbum($album_id);
				break;

			case 'downloadImage':
				$func->locationReset('&action=editAlbum&album_id=' . $album_id);
				$media->editAlbum($album_id);
				break;

			case 'deleteImages':
				if (!empty($_POST['image_id']) && is_array($_POST['image_id'])) {
					foreach ($_POST['image_id'] as $image_id) {
						$media->deleteImage((int)$image_id);
					}
				}
				$func->locationReset('&action=editAlbum&album_id=' . $album_id);
				$media->editAlbum($album_id);
				break;

			case 'deleteImage':
				if (!empty($_REQUEST['image_id']) && wp_verify_nonce($_REQUEST['nonce'] ?? '', 'deleteImage')) {
					$image_id = (int)$_REQUEST['image_id'];
					$media->deleteImage($image_id);
				}
				$func->locationReset('&action=editAlbum&album_id=' . $album_id);
				$media->editAlbum($album_id);
				break;

			case 'moveImages':
				if (!empty($_POST['image_id']) && is_array($_POST['image_id'])) {
					$destAlbum = empty($_POST['doaction2']) ? (int)($_POST['destAlbum'] ?? 0) : (int)($_POST['destAlbum2'] ?? 0);

					foreach ($_POST['image_id'] as $image_id) {
						$media->moveImage((int)$image_id, $destAlbum);
					}
				}
				$func->locationReset('&action=editAlbum&album_id=' . $album_id);
				$media->editAlbum($album_id);
				break;

// Add Media
			case 'addMediaPage':
				$media->addMediaPage(array('album_id' => $album_id));
				break;

			case 'addMedia':
				if (!empty($_POST['OK'])) {
					if (!empty($album_id)) {
						$media->addMedia(array('album_id' => $album_id));
						$func->locationReset('&action=editAlbum&album_id=' . $album_id);
						$media->editAlbum($album_id);
					}
				} else {
					$tab = 'albums';

					$func->locationReset('&tab=' . $tab);
					$media->mainPage($tab);
				}
				break;

// Galleries
			case 'albumToGallery':
				$media->albumToGallery($album_id);
				break;

			case 'createGallery':
				if (!empty($_POST['OK'])) {
					$data = array(
						'name' => sanitize_text_field(wp_unslash($_POST['gallery']['name'] ?? '')),
						'type' => sanitize_text_field(wp_unslash($_POST['gallery']['type'] ?? ''))
					);
					$gallery_id = $admin->createGallery($data);

					if (!empty($album_id) && $gallery_id) {
						$images = $wpdb->get_results("
							SELECT *
							FROM `{$plugin->dbImages}`
							WHERE `album_id` = '{$album_id}'
							AND `gallery_id` = '0'
							ORDER BY `order` ASC
						");
						if (!empty($images)) {
							foreach ($images as $image) {
								unset($image->id);
								$image->gallery_id = $gallery_id;
								$image->album_id = $album_id;

								$wpdb->insert($plugin->dbImages, get_object_vars($image));
							}
						}
						$admpage->head('Gallery Options', 'gallery-options');
						$admpage->galleryOptions(new flgalleryGallery($gallery_id), array('noExportXML' => true));
					}
				} else {
					$media->mainPage('albums');
				}
				break;

			case 'changeGalleryOptions':
				$gallery = new flgalleryGallery((int)($_POST['gallery_id'] ?? 0));
				if (!$gallery->id) {
					$media->mainPage('albums');
					break;
				}

				if (!empty($_POST['OK']) || !empty($_POST['update'])) {
					$admin->updateGalleryFromRequest($gallery);

					if (!empty($_POST['update'])) {
						$admpage->head('Gallery Options', 'gallery-options');
						$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
						$admpage->galleryOptions($gallery);
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
				$func->redirect("?page=flgallery&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			default:
				$media->mainPage();
				break;
		}

		$admpage->foot();
	}

	function mainPage($tab = false)
	{
		include FLGALLERY_GLOBALS;

		$admpage->head('Media', 'media');

		echo "<script type='text/javascript' src='{$plugin->jsURL}/albums-list.js'></script>\n";

		if (!$tab) {
			$tab = empty($_REQUEST['tab']) ? 'albums' : $_REQUEST['tab'];
		}

		$admpage->tabmenu(
			array(
				'albums' => 'Albums',
				//'pictures' => 'Pictures',
				//'backgrounds' => 'Backgrounds',
				//'sounds' => 'Sounds'
			),
			$tab
		);

		echo "<div class='tab-content'>\n";
		switch ($tab) {
			case 'albums':
				$media->albums();
				break;
		}
		echo "</div>\n";
	}

	function selectPictures($a = NULL)
	{
		include FLGALLERY_GLOBALS;
		include FLGALLERY_INCLUDE . '/media.selectPictures.php';
	}

	function albums()
	{
		include FLGALLERY_GLOBALS;

		echo $admpage->actionButton(array('+ New Album', true), 'addNewAlbum');

		if (empty($_REQUEST['orderBy'])) {
			$cookie = $plugin->getUserCookie();
			if (isset($cookie['albumsList']) && is_array($cookie['albumsList'])) {
				$_REQUEST['orderBy'] = isset($cookie['albumsList']['orderBy']) ? (string)$cookie['albumsList']['orderBy'] : '';
				$_REQUEST['order'] = isset($cookie['albumsList']['order']) ? (string)$cookie['albumsList']['order'] : '';
			}
		}

		$orderDefault = 'created';

		$orderBy = empty($_REQUEST['orderBy']) ? $orderDefault : strtolower((string)$_REQUEST['orderBy']);
		$order = empty($_REQUEST['order']) ? ($orderBy == 'created' || $orderBy == 'modified' ? 'desc' : 'asc') : strtolower((string)$_REQUEST['order']);
		if ($order == 'desc') {
			$orderR = 'asc';
			$orderU = 'DESC';
		} else {
			$orderR = 'desc';
			$orderU = 'ASC';
		}

		$orderCols = array(
			'title' => 'Title',
			'author' => 'Author',
			'created' => 'Created',
			'modified' => 'Modified',
			'size' => 'Size',
		);
		if (!array_key_exists($orderBy, $orderCols)) {
			$orderBy = $orderDefault;
		}

		$albums = $wpdb->get_results("
			SELECT
				a.`id`,
				a.`title`,
				a.`description`,
				a.`created`,
				a.`modified`,
				a.`preview`,
				SUM(i.`size`) as `size`
			FROM `{$plugin->dbAlbums}` a
			LEFT JOIN `{$plugin->dbImages}` i ON i.`album_id` = a.`id` AND i.`gallery_id` = 0
			WHERE (a.`author` = '{$plugin->userID}' OR {$plugin->userLevel} >= 5)
			GROUP BY a.`id`
			ORDER BY `{$orderBy}` {$orderU}, a.`title` ASC
		");
		if ($albums === false) {
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $this->errorN));
		}

		if (!empty($albums)) {
			$th = array();
			foreach ($orderCols as $key => $value) {
				if ($key == $orderBy) {
					$arrow = $order == 'asc' ? '&nbsp;&#9650;' : '&nbsp;&#9660;';
					$th[$key] = "<a href='{$admpage->href}&amp;tab=albums&amp;orderBy={$key}&amp;order={$orderR}'>{$value}{$arrow}</a>";
				} else {
					$arrow = '';
					$th[$key] = "<a href='{$admpage->href}&amp;tab=albums&amp;orderBy={$key}' title='Sort by {$value}'>{$value}{$arrow}</a>";
				}
			}

			echo
				"<table class='albums-list widefat' width='100%' cellspacing='0' border='0'>\n" .
				"<thead>\n" .
				"	<tr>\n" .
				"		<th class='album-preview'>Preview</th>\n" .
				"		<th class='album-title'>{$th['title']}</th>\n" .
				"		<th class='album-author'>{$th['author']}</th>\n" .
				"		<th class='album-date'>{$th['created']}</th>\n" .
				"		<th class='album-date'>{$th['modified']}</th>\n" .
				"		<th class='album-size'>{$th['size']}</th>\n" .
				"		<th class='album-operation last'>Operation</th>\n" .
				"	</tr>\n" .
				"</thead>\n" .
				"<tbody>\n";

			foreach ($albums as $album) {
				$albumRawTitle = $album->title; // plain title for the JS confirm message (see delete button below)
				$album->title = esc_html($album->title);
				$album->description = esc_html($album->description);

				$album->count = 0;

				$images = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbImages}`
					WHERE `album_id` = '{$album->id}'
					AND `gallery_id` = 0
					ORDER BY RAND()
				");
				if (is_array($images)) {
					$album->count = count($images);

					$img = current($images);

					if ($album->count && empty($album->preview)) {
						$previewImage = new flgalleryImage($img);
						$album->preview = $previewImage->resized(array('height' => 80), true);
					}
				}

				if (!empty($album->preview)) {
					$album->preview = "<img src='{$album->preview}' alt='{$album->title}' title='' height='80' />";
				}

				$album->href = $admpage->href;

				if ($album->size) {
					$album->sizeK = round($album->size / 1024) . '&nbsp;KB';
					$createAtts = array();
				} else {
					$album->sizeK = '&mdash;&nbsp;';
					$createAtts = array('disabled' => 'disabled');
				}

				$album->addPictures = $admpage->actionButton('Add Pictures', 'addMediaPage', array('album_id' => $album->id));
				$album->createGallery = $admpage->actionButton('Create Gallery', 'albumToGallery', array('album_id' => $album->id), NULL, $createAtts);
				$album->delete = $admpage->actionButton('Delete Album', 'deleteAlbum', array('album_id' => $album->id), 'Delete Album?\n\n"' . $albumRawTitle . '"\n');

				$album->created =
					mysql2date($plugin->dateFormat, get_date_from_gmt($album->created)) . "<br />" .
					mysql2date($plugin->timeFormat, get_date_from_gmt($album->created));

				$album->modified =
					mysql2date($plugin->dateFormat, get_date_from_gmt($album->modified)) . "<br />" .
					mysql2date($plugin->timeFormat, get_date_from_gmt($album->modified));

				$plugin->tpl->t('media/album', $album);
			}
			echo
				"</tbody>\n" .
				"</table>\n";
		}
	}

	function getAlbumItemsHtml($album_id)
	{
		include FLGALLERY_GLOBALS;

		$album_id = (int)$album_id;

		$images = $wpdb->get_results("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `album_id` = '{$album_id}'
			AND `gallery_id` = 0
			ORDER BY `order` ASC
		");

		ob_start();
		foreach ((array)$images as $image) {
			$imageObject = new flgalleryImage($image);

			$previewURL = $imageObject->resized(array('height' => 64), true);

			if ($l = strlen($image->title)) {
				$imageNameTitle = esc_html($image->title);
				if ($l > 20) {
					$imageNameShort = esc_html(substr($image->title, 0, 19)) . '&hellip;';
				} else {
					$imageNameShort = esc_html($image->title);
				}
			} else {
				$imageNameTitle = esc_html($image->name);
				$imageNameShort = esc_html($func->shortFilename($image->name, 20, ''));
			}
			?>
			<li style="float:left; width:150px; height:90px; overflow:hidden; margin:15px;">
				<label for="select-image-<?php echo $image->id; ?>">
					<span class="select-image-preview" style="display:block;">
						<img src="<?php echo esc_html($previewURL); ?>" alt="<?php echo $imageNameTitle; ?>"/>
					</span>
					<span class="select-image" style="white-space:nowrap;">
						<input type="checkbox" class="select-image" id="select-image-<?php echo $image->id; ?>" name="images[]" value="<?php echo $image->id; ?>"/>
						<small title="<?php echo $imageNameTitle; ?>" style="font-size:10px;"><?php echo $imageNameShort; ?></small>
					</span>
				</label>
			</li>
		<?php
		}
		return ob_get_clean();
	}

	function albumToGallery($album_id, $images = array())
	{
		include FLGALLERY_GLOBALS;

		$album_id = (int)$album_id;

		$album = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
		");
		if ($album) {
			$admpage->head('New Gallery', 'new-gallery');
			$admpage->newGallery(array('name' => $album->title), array('album_id' => $album_id));
		}
	}

	function addNewAlbum()
	{
		include FLGALLERY_GLOBALS;

		$admpage->head('Create', 'new-album');
		$plugin->tpl->t('media/new-album', array('href' => $admpage->href));
	}

	function createAlbum($a)
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`)
			FROM `{$plugin->dbAlbums}`
		");
		$order = $order === false ? 0 : (int)$order + 1;

		if (!strlen(trim((string)$a['title']))) {
			$a['title'] = 'Untitled';
		}

		$res = $wpdb->insert(
			$plugin->dbAlbums,
			array(
				'order' => $order,
				'author' => $plugin->userID,
				'title' => $a['title'],
				'description' => $a['description'],
				'preview' => '',
				'created' => $now = $func->now(),
				'modified' => $now,
			)
		);
		if ($res !== false) {
			$album_id = $wpdb->insert_id;
			return $album_id;
		} else {
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $media->errorN));
			return false;
		}
	}

	function editAlbum($album_id)
	{
		include FLGALLERY_GLOBALS;

		$album_id = (int)$album_id;

		$album = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
			LIMIT 1
		");
		if ($album) {
			$admpage->head('Album Properties', 'album');

			$album->title = esc_html($album->title);
			$album->description = esc_html($album->description);

			$album->selectAlbum = '';

			$picturesHTML = '';
			$pictures = $wpdb->get_results("
				SELECT *
				FROM `{$plugin->dbImages}`
				WHERE `album_id` = '{$album_id}'
				AND `gallery_id` = '0'
				ORDER BY `order` ASC
			");
			if (!empty($pictures)) {
				$nonce = wp_create_nonce('deleteImage');

				foreach ($pictures as $picture) {
					$image = new flgalleryImage($picture);

					$picture->title = esc_html($picture->title);
					$picture->description = esc_html($picture->description);
					$picture->size = round($picture->size / 1024) . '&nbsp;KB';
					$picture->url = esc_html($plugin->imgURL . '/' . $picture->path);
					$thumbnail = esc_html($image->resized(array('height' => 80)));
					$picture->previewURL = $thumbnail ? esc_html($func->url($thumbnail)) : esc_html($picture->url);
					$picture->href = esc_html($admpage->href . '&amp;album_id=' . $album_id);
					$picture->nonce = $nonce;

					$picturesHTML .= $tpl->parse('album/picture', $picture);
				}

				$albums = $wpdb->get_results("
					SELECT `id`, `title`
					FROM `{$plugin->dbAlbums}`
					WHERE `id` <> '{$album_id}'
					ORDER BY `title` ASC
				");
				if (!empty($albums)) {
					foreach ($albums as $row) {
						$row->title = esc_html($row->title);
						$album->selectAlbum .= "\n\t\t\t<option value='{$row->id}'>{$row->title}</option>";
					}
				}
			}
			$album->jsURL = $plugin->jsURL;
			$album->href = $admpage->href;
			$album->addPictures = $admpage->actionButton(array('Add Pictures', true), 'addMediaPage', array('album_id' => $album_id));

			$createAtts = $picturesHTML ? array() : array('disabled' => 'disabled');
			$album->createGallery = $admpage->actionButton('Create Gallery', 'albumToGallery', array('album_id' => $album_id), NULL, $createAtts);

			$album->pictures = $picturesHTML;
			$tpl->t('album/album', $album);
		}
	}

	function deleteAlbum($album_id)
	{
		include FLGALLERY_GLOBALS;

		$album_id = (int)$album_id;

		$wpdb->query("
			DELETE FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
		");

		$images = $wpdb->get_results("
			SELECT `id`
			FROM `{$plugin->dbImages}`
			WHERE `album_id` = '{$album_id}'
			AND `gallery_id` = 0
		");
		if ($images !== false) {
			foreach ($images as $image) {
				$this->deleteImage($image->id);
			}
		}
	}

	function saveImage($image_id, $a, $applyToCopies = false)
	{
		include FLGALLERY_GLOBALS;

		$data = array(
			'name' => $a['name'],
			'title' => $a['title'],
			'description' => $a['description'],
			'link' => $a['link'],
			'target' => $a['target']
		);

		$image = new flgalleryImage($image_id);
		$image->load();

		if ($applyToCopies) {
			return $wpdb->update($plugin->dbImages, $data, array('path' => $image->path));
		} else {
			$image->set($data);
			return $image->save();
		}
	}

	function deleteImage($image_id)
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

				if ($copies !== false && count($copies) == 0) {
					preg_match('/(.*)(\..*)/', $image->path, $fname);

					if (strpos($image->path, '/') === 0) {
						$fname[1] = md5($fname[1]);
					} else {
						// Delete image
						@unlink($plugin->imgDir . '/' . $image->path);
					}

					// Delete thumbnails
					$func->recurse($plugin->tmpDir, '#^img-' . preg_quote($fname[1], '#') . '\..+#i', 'unlink');
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

		return $image->id;
	}

	function copyImage($image_id, $a)
	{
		include FLGALLERY_GLOBALS;

		$image_id = (int)$image_id;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image) {
			unset($image->id);

			$image->album_id = isset($a['album_id']) ? $a['album_id'] : $image->album_id;
			$image->gallery_id = isset($a['gallery_id']) ? $a['gallery_id'] : $image->gallery_id;

			if (isset($a['order'])) {
				$image->order = $a['order'];
			}

			$wpdb->insert($plugin->dbImages, get_object_vars($image));
		}
	}

	function moveImage($image_id, $album_id)
	{
		include FLGALLERY_GLOBALS;

		return $wpdb->update($plugin->dbImages, array('album_id' => $album_id), array('id' => $image_id));
	}

	/**
	 * "Add Pictures" page
	 */
	function addMediaPage($a)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		if (empty($startText)) {
			$startText = "Start Upload";
		}

		$admpage->head('Add Pictures', 'addMediaPage');

		$objectID = '';

		if (!empty($a['album_id'])) {
			$objectID = '&amp;album_id=' . ((int)$a['album_id']);
		}

		if (!empty($a['gallery_id'])) {
			$objectID = '&amp;gallery_id=' . ((int)$a['gallery_id']);
		}

		$tabs = array(
				'html5upload' => 'Multi-file Uploader',
				'stdupload' => 'Browser Uploader',
				'url' => 'Add URLs',
				'archive' => 'Upload Archive',
				'directory' => 'Import from Folder',
				'wpmedia' => 'Import from Media Library',
		);

		$tab = empty($_REQUEST['tab']) || !isset($tabs[$_REQUEST['tab']]) ? 'html5upload' : $_REQUEST['tab'];
		$admpage->tabmenu($tabs, $tab, '&amp;action=addMediaPage' . $objectID);

		switch ($tab) {
			case 'html5upload':
				$addMediaPage = $media->html5Uploader($a, false);
				break;
			case 'stdupload':
				$addMediaPage = $media->browserUploader($a, false);
				break;
			case 'url':
				$addMediaPage = $media->addFromURL($a, false);
				break;
			case 'archive':
				$addMediaPage = $media->uploadArchive($a, false);
				break;
			case 'directory':
				$addMediaPage = $media->importFolder($a, false);
				break;
			case 'wpmedia':
				$addMediaPage = $media->importWpMedia($a, false);
				break;
			default:
				$addMediaPage = '';
		}

		$tpl->t('media/add', array(
			'href' => $admpage->href . $objectID,
			'content' => $addMediaPage,
			'max_image_dimensions' => '2880&times;1440',
			'start' => $startText
		));
	}

	/**
	 * "Multi-file Uploader" tab (see js/add-html5upload.js and flgalleryAjaxUpload)
	 */
	function html5Uploader(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;

		$maxFileSize = wp_max_upload_size();

		$a['jsURL'] = esc_url($plugin->jsURL);
		$a['ajaxURL'] = esc_url(admin_url('admin-ajax.php'));
		$a['nonce'] = wp_create_nonce('flgallery-upload');
		$a['upload_id'] = mt_rand(1, mt_getrandmax());
		$a['max_file_size'] = (int)$maxFileSize;
		$a['max_file_size_text'] = esc_html(size_format($maxFileSize));

		$out = $tpl->parse('media/add-html5upload', $a);

		if ($echo) {
			echo $out;
		}

		return $out;
	}

	/**
	 * "Browser Uploader" tab
	 */
	function browserUploader(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;

		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;

		$a['max_file_uploads'] = ($max_file_uploads = ini_get('max_file_uploads')) ? (int)$max_file_uploads : 20;
		$a['upload_max_filesize'] = $func->bytesToM($func->mToBytes(ini_get('upload_max_filesize')), ' MB');
		$a['post_max_size'] = $func->bytesToM($func->mToBytes(ini_get('post_max_size')), ' MB');

		$a['max_size'] = ini_get('max_file_uploads');

		$out = $tpl->parse('media/add-stdupload', $a);

		if ($echo) {
			echo $out;
		}

		return $out;
	}

	/**
	 * "Add URLs" tab
	 */
	function addFromURL(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		$startText = 'Add URLs';

		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-url', $a);

		if ($echo) {
			echo $out;
		}

		return $out;
	}

	/**
	 * "Upload Archive" tab
	 */
	function uploadArchive(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;
		//global $startText;
		//$startText = 'Upload';

		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-archive', $a);

		if ($echo) {
			echo $out;
		}

		return $out;
	}

	/**
	 * "Import from Folder" tab
	 */
	function importFolder(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		$startText = 'Import';

		$a['jsURL'] = $plugin->jsURL;
		$a['uploadsPath'] = preg_replace('#^' . preg_quote(FLGALLERY_SITE_URL, '#') . '/#', '', $plugin->uploadsURL);
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-directory', $a);

		if ($echo) {
			echo $out;
		}

		return $out;
	}

	/**
	 * "Import from Media Library" tab
	 */
	function importWpMedia(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		$startText = 'Import';

		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-wpmedia', $a);

		if ($echo) {
			echo $out;
		}

		return $out;
	}

	/**
	 * Add images to albums and galleries
	 */
	function addMedia($a)
	{
		include FLGALLERY_GLOBALS;

		$album_id = isset($a['album_id']) ? (int)$a['album_id'] : 0;
		$gallery_id = isset($a['gallery_id']) ? (int)$a['gallery_id'] : 0;

		$order = (int)$wpdb->get_var("
			SELECT MAX(`order`)
			FROM `{$plugin->dbImages}`
			WHERE
				`album_id` = '{$album_id}' AND
				`gallery_id` = '{$gallery_id}'
		");

		$added = $media->addFiles($plugin->imgDir);
		if (count($added)) {
			foreach ($added as $file) {
				$path = $file['path'];

				if (preg_match('#^(/|[a-z]:)#i', $path)) {
					// File outside of the plugin's images directory (WordPress Media Library)
					$fullPath = $path;
					$path = '/' . preg_replace('/^' . preg_quote(ABSPATH, '/') . '/', '', $path);
					$ownFile = false;
				} else {
					$fullPath = $plugin->imgDir . '/' . $path;
					$ownFile = true;
				}

				$imageSize = @getimagesize($fullPath);
				if (empty($imageSize) || !in_array($imageSize['mime'], array('image/jpeg', 'image/gif', 'image/png'), true)) {
					$this->warning(sprintf(__('%s is not a JPEG, PNG or GIF picture and was skipped.', $plugin->name), esc_html($file['name'])));
					if ($ownFile) {
						@unlink($fullPath);
					}
					continue;
				}

				$insert = $wpdb->insert(
					$plugin->dbImages,
					array(
						'album_id' => $album_id,
						'gallery_id' => $gallery_id,
						'order' => ++$order,
						'type' => $imageSize['mime'],
						'path' => $path,
						'name' => sanitize_file_name($file['name']),
						'title' => $file['title'],
						'description' => $file['description'],
						'link' => '',
						'target' => '',
						'width' => (int)$imageSize[0],
						'height' => (int)$imageSize[1],
						'size' => (int)filesize($fullPath)
					)
				);
				if ($insert === false) {
					$this->error($wpdb->last_error);
					$this->debug($wpdb->last_query, array('Error', $media->errorN));
				}
			}
			if (!empty($album_id)) {
				$wpdb->update($plugin->dbAlbums, array('modified' => $func->now()), array('id' => $album_id));
			}
			if (!empty($gallery_id)) {
				$gallery = new flgalleryGallery($gallery_id);
				$gallery->save();
			}
		}
	}

	/**
	 * Moves an uploaded picture into $destDir under a new random name.
	 * Returns the new file name, or false if the file is not a JPEG, PNG or GIF picture.
	 */
	function moveUploadedImage($tmpName, $name, $destDir)
	{
		include FLGALLERY_GLOBALS;

		if (empty($tmpName) || !is_uploaded_file($tmpName) || !preg_match($this->imageExtensions, (string)$name)) {
			return false;
		}

		$imageSize = @getimagesize($tmpName);
		if (empty($imageSize) || !in_array($imageSize['mime'], array('image/jpeg', 'image/gif', 'image/png'), true)) {
			return false;
		}

		$ext = $func->fileExtByMime($imageSize['mime']);
		$destName = basename($func->uniqueFile($destDir . "/%s{$ext}"));

		if (move_uploaded_file($tmpName, $destDir . '/' . $destName)) {
			@chmod($destDir . '/' . $destName, 0666);
			return $destName;
		}
		return false;
	}

	/**
	 * Collects pictures from the "Add Pictures" form and copies them into $destDir.
	 * Returns a list of array('path', 'name', 'title', 'description'); 'path' is relative
	 * to $destDir, or absolute for pictures from the WordPress Media Library.
	 */
	function addFiles($destDir)
	{
		include FLGALLERY_GLOBALS;

		$added = array();

		$post = function ($name, $key) {
			return isset($_POST[$name][$key]) ? sanitize_text_field(wp_unslash($_POST[$name][$key])) : '';
		};

		// Browser Uploader
		if (!empty($_FILES['stdUpload_file']['name']) && is_array($_FILES['stdUpload_file']['name'])) {
			$f = $_FILES['stdUpload_file'];
			foreach ($f['name'] as $key => $name) {
				if (empty($name)) {
					continue;
				}
				if (!empty($f['error'][$key])) {
					$this->error(esc_html($name) . ' : ' . __('Upload failed.', $plugin->name) . ' (' . (int)$f['error'][$key] . ')');
					continue;
				}

				$destName = $this->moveUploadedImage($f['tmp_name'][$key], $name, $destDir);
				if ($destName) {
					$added[] = array(
						'path' => $destName,
						'name' => $name,
						'title' => $post('stdUpload_title', $key),
						'description' => $post('stdUpload_description', $key),
					);
				} else {
					$this->warning(sprintf(__('%s is not a JPEG, PNG or GIF picture and was skipped.', $plugin->name), esc_html($name)));
				}
			}
		}

		// Add URLs
		if (!empty($_POST['addFromURL_file']) && is_array($_POST['addFromURL_file'])) {
			foreach ($_POST['addFromURL_file'] as $key => $url) {
				$url = trim(wp_unslash($url));
				if (preg_match('#^(https?://|)(.+)#i', $url, $m)) {
					$ext = $func->fileExtByMime($func->fileMime(parse_url($url, PHP_URL_PATH)));
					$destName = basename($func->uniqueFile($destDir . "/%s{$ext}"));

					$files = $func->copyURLs(array($url), $destDir, array($destName));
					if (!empty($files[0])) {
						$added[] = array(
							'path' => $files[0],
							'name' => basename((string)parse_url($url, PHP_URL_PATH)),
							'title' => $post('addFromURL_title', $key),
							'description' => $post('addFromURL_description', $key),
						);
					}
				}
			}
		}

		// Folders to import: array(absolute path, delete imported files, delete the folder itself)
		$folders = array();

		// Upload Archive
		if (!empty($_FILES['zipUpload_file']['name'])) {
			$data = $_FILES['zipUpload_file'];
			foreach ($data as $key => $value) {
				if (!is_array($value)) {
					$data[$key] = (array)$value;
				}
			}

			foreach ($data['name'] as $key => $name) {
				if (!$data['error'][$key] && is_uploaded_file($data['tmp_name'][$key])) {
					$tmpDir = $plugin->uploadsDir . '/' . mt_rand();
					wp_mkdir_p($tmpDir);

					if ($this->extractZip($data['tmp_name'][$key], $tmpDir)) {
						$folders[] = array($tmpDir, true, true);
					} else {
						$this->error(sprintf(__('Unable to extract %s', $plugin->name), esc_html($name)));
						$func->unlinkRecurse($tmpDir);
					}
				}
			}
		}

		// Multi-file Uploader (files were uploaded into uploads/<upload_id>/ by flgalleryAjaxUpload)
		if (!empty($_POST['html5Upload_id'])) {
			$upload_id = preg_replace('/\D/', '', (string)$_POST['html5Upload_id']);
			if ($upload_id !== '' && is_dir($plugin->uploadsDir . '/' . $upload_id)) {
				$folders[] = array($plugin->uploadsDir . '/' . $upload_id, true, true);
			}
		}

		// Import from Folder
		if (!empty($_POST['importFolder_path']) && is_string($_POST['importFolder_path'])) {
			$folder = $this->resolveImportFolder(wp_unslash($_POST['importFolder_path']));
			if ($folder) {
				$folders[] = array($folder, !empty($_POST['importFolder_delete']), false);
			} else {
				$this->error(__('Invalid folder.', $plugin->name));
			}
		}

		foreach ($folders as $folder) {
			list($path, $deleteFiles, $deleteFolder) = $folder;

			$this->files = array();
			$func->recurse($path, $this->imageExtensions, array($this, 'addFile'));

			foreach ($this->files as $file) {
				$ext = $func->fileExtByMime($func->fileMime($file));
				$destName = basename($func->uniqueFile($destDir . "/%s{$ext}"));

				$copied = $func->copyFiles(array($file), $destDir, array($destName), $deleteFiles);
				if (!empty($copied[0])) {
					$added[] = array(
						'path' => $copied[0],
						'name' => basename($file),
						'title' => $func->filenameToTitle($file),
						'description' => '',
					);
				}
			}

			if ($deleteFolder) {
				$func->unlinkRecurse($path);
			}
		}

		// Import from Media Library
		if (!empty($_POST['wpmedia_id']) && is_array($_POST['wpmedia_id'])) {
			foreach ($_POST['wpmedia_id'] as $id) {
				$id = (int)$id;
				if ($id && wp_attachment_is_image($id) && ($file = get_attached_file($id)) && file_exists($file)) {
					$added[] = array(
						'path' => $file,
						'name' => basename($file),
						'title' => get_the_title($id),
						'description' => '',
					);
				}
			}
		}

		return $added;
	}

	/**
	 * Absolute path of a folder to import pictures from ("Import from Folder" tab; the path is
	 * relative to the WordPress directory). Imported files may be deleted, so the folder must be
	 * inside wp-content, and not inside plugins, themes or the plugin's own content directories.
	 */
	function resolveImportFolder($relativePath)
	{
		include FLGALLERY_GLOBALS;

		$path = realpath(ABSPATH . ltrim((string)$relativePath, '/\\'));

		if (!$path || !is_dir($path)) {
			return false;
		}

		$inside = function ($path, $dir) {
			$dir = realpath($dir);
			return $dir && ($path === $dir || strpos($path, rtrim($dir, '/\\') . DIRECTORY_SEPARATOR) === 0);
		};

		if (!$inside($path, WP_CONTENT_DIR) || $path === realpath(WP_CONTENT_DIR)) {
			return false;
		}

		$forbidden = array(WP_PLUGIN_DIR, WPMU_PLUGIN_DIR, get_theme_root(), $plugin->imgDir, $plugin->xmlDir, $plugin->tmpDir);
		foreach ($forbidden as $dir) {
			if ($inside($path, $dir)) {
				return false;
			}
		}

		return $path;
	}

	function extractZip($archive, $destDir)
	{
		if (class_exists('ZipArchive')) {
			$zip = new ZipArchive();
			if ($zip->open($archive) === true) {
				$result = $zip->extractTo($destDir);
				$zip->close();
				return $result;
			}
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		$zip = new PclZip($archive);
		return $zip->extract(PCLZIP_OPT_PATH, $destDir) !== 0;
	}

	function addFile($name, $title = '', $description = '')
	{
		$this->files[] = $name;
		$this->filesInfo[] = array(
			'name' => $name,
			'title' => $title,
			'description' => $description
		);
	}

	function getWpMediaLibraryJson($offset = 0, $limit = 30)
	{
		include FLGALLERY_GLOBALS;

		$offset = (int)$offset;
		$limit = (int)$limit;

		$items = $wpdb->get_results("
			SELECT `ID`
			FROM `{$wpdb->posts}`
			WHERE (`post_author` = '{$plugin->userID}' OR {$plugin->userLevel} >= 5)
			AND `post_type` = 'attachment'
			AND `post_mime_type` IN ('image/gif', 'image/jpeg', 'image/png')
			ORDER BY `post_date` DESC
			LIMIT {$offset}, {$limit}
		");

		$items = is_array($items) ? $items : array();
		foreach ($items as $key => $item) {
			$thumbnail = wp_get_attachment_image_src($item->ID, array(300, 300));
			$items[$key]->thumbnail = array(
				'src' => $thumbnail ? $thumbnail[0] : '',
				'width' => $thumbnail ? $thumbnail[1] : 0,
				'height' => $thumbnail ? $thumbnail[2] : 0
			);
		}

		@header("Content-Type: application/json; charset=utf-8");
		echo wp_json_encode($items);
	}
}
