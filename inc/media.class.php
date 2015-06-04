<?php

class flgalleryMedia extends flgalleryBaseClass
{
	var
		$files = array();

	function manage()
	{
		include FLGALLERY_GLOBALS;
		$plugin->getUserInfo();

		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		if ( !empty($_REQUEST['doaction2']) )
			$action = empty($_REQUEST['action2']) ? '' : $_REQUEST['action2'];

		$album_id = empty($_REQUEST['album_id']) ? 0 : (int)$_REQUEST['album_id'];

		switch ($action)
		{

// Albums
			case 'addNewAlbum':
				$media->addNewAlbum();
				break;

			case 'createAlbum':
				if ( !empty($_REQUEST['OK']) ) {
					$album_id = $media->createAlbum($_REQUEST['album']);
					if ($album_id) {
						$func->locationReset('&action=editAlbum&album_id='.$album_id);
						break;
					}
				}
				$func->locationReset('&tab=albums');
				$media->mainPage();
				break;

			case 'editAlbum':
				if ( !empty($album_id) )
					$media->editAlbum($album_id);
				else
					$media->mainPage('albums');
				break;

			case 'updateAlbum':
				if ( !empty($_REQUEST['update']) )
				{
					if ( !empty($album_id) )
					{
						$data = $_REQUEST['album'];
						$data['modified'] = $now = $func->now();
						$wpdb->update( $plugin->dbAlbums, $data, array('id' => $album_id) );
					}
					$func->locationReset('&action=editAlbum&album_id='.$album_id);
					$media->editAlbum($album_id);
				}
				else
					$media->mainPage('albums');
				break;

			case 'deleteAlbum':
				$media->deleteAlbum($album_id);
				$func->locationReset('&tab=albums');
				$media->mainPage();
				break;

// Pictures
			case 'editImage':
				$image_id = (int)$_REQUEST['image_id'];
				$admpage->head('Picture Properties', 'image-properties');
				$admpage->editImage($image_id);
				break;

			case 'saveImage':
				if ( !empty($_REQUEST['OK']) && !empty($_REQUEST['image']) )
				{
					$image_id = (int)$_REQUEST['image_id'];
					$applyToCopies = !empty($_REQUEST['applyToCopies']);
					$media->saveImage($image_id, $_REQUEST['image'], $applyToCopies);
				}
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'downloadImage':
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'deleteImages':
				if ( !empty($_REQUEST['image_id']) && is_array($_REQUEST['image_id']) )
				{
					foreach ($_REQUEST['image_id'] as $image_id)
						$media->deleteImage( (int)$image_id );
				}
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'deleteImage':
				if ( !empty($_REQUEST['image_id']) )
				{
					$image_id = (int)$_REQUEST['image_id'];
					$media->deleteImage($image_id);
				}
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'moveImages':
				$destAlbum = empty($_REQUEST['doaction2']) ? (int)$_REQUEST['destAlbum'] : (int)$_REQUEST['destAlbum2'];

				foreach ($_REQUEST['image_id'] as $image_id)
					$media->moveImage( (int)$image_id, $destAlbum );

				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

// Add Media
			case 'addMediaPage':
				$media->addMediaPage( array('album_id'=>$album_id) );
				break;

			case 'addMedia':
				if ( !empty($_REQUEST['OK']) )
				{
					if ( !empty($album_id) )
					{
						$media->addMedia( array('album_id' => $album_id) );
						$func->locationReset('&action=editAlbum&album_id='.$album_id);
						$media->editAlbum($album_id);
					}
				}
				else
				{
					if ( !empty($album_id) )
						$tab = 'albums';

					$func->locationReset('&tab='.$tab);
					$media->mainPage($tab);
				}
				break;

// Galleries
			case 'albumToGallery':
				$media->albumToGallery($album_id);
				break;

			case 'createGallery':
				if ( !empty($_REQUEST['OK']) )
				{
					$gallery_id = $admin->createGallery($_REQUEST['gallery']);

					if ( !empty($album_id) && $gallery_id )
					{
						$images = $wpdb->get_results("
							SELECT *
							FROM `{$plugin->dbImages}`
							WHERE
								`album_id` = '{$album_id}' AND
								`gallery_id` = '0'
							ORDER BY `order` ASC
						");
						if ($images !== false && count($images))
						{
							foreach ($images as $image)
							{
								unset($image->id);
								$image->gallery_id = $gallery_id;
								$image->album_id = $album_id;

								$wpdb->insert( $plugin->dbImages, get_object_vars($image) );
							}
						}
						$admpage->head('Gallery Options', 'gallery-options');
						$admpage->galleryOptions( new flgalleryGallery($gallery_id), array('noExportXML' => true) );
					}
				}
				else
					$media->mainPage('albums');
				break;

			case 'changeGalleryOptions':
				if ( !empty($_REQUEST['OK']) || !empty($_REQUEST['update']) )
				{
					if ( !empty($_REQUEST['gallery']) )
					{
						$gallery = new flgalleryGallery($_REQUEST['gallery_id']);
						foreach ($_REQUEST['gallery'] as $key => $value)
						{
							if ( isset($gallery->$key) )
								$gallery->$key = $value;
						}
						$gallery->save();
					}
					if ( !empty($_REQUEST['settings']) )
					{
						$gallery->getSettings();
						foreach ($gallery->settingsInfo as $key => $param)
						{
							if ( isset($_REQUEST['settings'][$key]) )
								$gallery->settings[$key] = trim($_REQUEST['settings'][$key]);
							else
							{
								if ( (string)$param->input['type'] == 'checkbox' )
								{
									$values = explode( '|', (string)$param->input['value'] );
									$gallery->settings[$key] = trim($values[1]);
								}
							}
						}
						$gallery->saveSettings();
					}
					if ( !empty($_REQUEST['update']) )
					{
						$admpage->head('Gallery Options', 'gallery-options');
						$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
						$admpage->galleryOptions($gallery);
						break;
					}
				}
				if ( !empty($_REQUEST['resetOptions']) )
				{
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

		if (!$tab)
			$tab = empty($_REQUEST['tab']) ? 'albums' : $_REQUEST['tab'];

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
		switch ($tab)
		{
			case 'albums':
				$media->albums();
				break;
		}
		echo "</div>\n";
	}

	function selectPictures($a = NULL)
	{
		include FLGALLERY_GLOBALS;
		include FLGALLERY_INCLUDE.'/media.selectPictures.php';
	}

	function albums()
	{
		include FLGALLERY_GLOBALS;

		echo $admpage->actionButton(array('+ New Album', true), 'addNewAlbum');

		if ( empty($_REQUEST['orderBy']) )
		{
			$cookie = unserialize( base64_decode($plugin->userCookie) );
			$_REQUEST['orderBy'] = &$cookie['albumsList']['orderBy'];
			$_REQUEST['order'] = &$cookie['albumsList']['order'];
		}

		$orderDefault = 'created';

		$orderBy = empty($_REQUEST['orderBy']) ? $orderDefault : strtolower((string)$_REQUEST['orderBy']);
		$order = empty($_REQUEST['order']) ? ($orderBy == 'created' || $orderBy == 'modified' ? 'desc' : 'asc') : strtolower((string)$_REQUEST['order']);
		if ($order == 'desc')
		{
			$orderR = 'asc';
			$orderU = 'DESC';
		}
		else
		{
			$orderR = 'desc';
			$orderU = 'ASC';
		}

		$orderCols = array(
			'title' =>		'Title',
			'author' =>		'Author',
			'created' =>	'Created',
			'modified' =>	'Modified',
			'size' =>		'Size',
		);
		if ( !array_key_exists($orderBy, $orderCols) )
			$order = $orderDefault;

		$albums = $wpdb->get_results("
			SELECT
				a.`id`,
				a.`title`,
				a.`description`,
				a.`created`,
				a.`modified`,
				a.`preview`,
				u.`display_name` as `author`,
				SUM(i.`size`) as `size`
			FROM
				`{$plugin->dbAlbums}` a LEFT JOIN
				`{$plugin->dbImages}` i ON
					a.`id` = i.`album_id` AND
					i.`gallery_id` = '0',
				`{$wpdb->users}` u
			WHERE
				a.`author` = u.`ID` AND
				(a.`author` = '{$plugin->userID}' OR {$plugin->userLevel} >= 5)
			GROUP BY
				a.`id`
			ORDER BY
				`{$orderBy}` {$orderU},
				a.`title` ASC
		");
		if ($albums === false)
		{
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $this->errorN));
		}

		if ( $albums !== false && count($albums) )
		{
			foreach ($orderCols as $key => $value)
			{
				if ($key == $orderBy)
				{
					$arrow = $order == 'asc' ? '&nbsp;&#9650;' : '&nbsp;&#9660;';
					$th[$key] = "<a href='{$admpage->href}&amp;tab=albums&amp;orderBy={$key}&amp;order={$orderR}'>{$value}{$arrow}</a>";
				}
				else
				{
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

			foreach ($albums as $album)
			{
				$album->count = 0;

				$images = $wpdb->get_results("
					SELECT *
					FROM
						`{$plugin->dbImages}`
					WHERE
						`album_id` = '{$album->id}' AND
						`gallery_id` = 0
					ORDER BY RAND()
				");
				if ( $images !== false )
				{
					$album->count = count($images);

					$img = current($images);

					if ( $album->count && empty($album->preview) )
					{
						$previewImage = new flgalleryImage($img);
						$album->preview = $previewImage->resized(array('height' => 80), true);
					}
				}

				if ( !empty($album->preview) )
					$album->preview = "<img src='{$album->preview}' alt='{$album->title}' title='' height='80' />";

				$album->href = $admpage->href;

				if ($album->size) {
					$album->sizeK = round($album->size / 1024) .'&nbsp;KB';
					$createAtts = array();
				}
				else {
					$album->sizeK = '&mdash;&nbsp;';
					$createAtts = array('disabled' => 'disabled');
				}

				$album->addPictures = $admpage->actionButton( 'Add Pictures', 'addMediaPage', array('album_id'=>$album->id) );
				$album->createGallery = $admpage->actionButton( 'Create Gallery', 'albumToGallery', array('album_id'=>$album->id), NULL, $createAtts );
				$album->delete = $admpage->actionButton( 'Delete Album', 'deleteAlbum', array('album_id'=>$album->id), 'Delete Album?\n\n"'.$album->title.'"\n' );

				$album->created =
					mysql2date( $plugin->dateFormat, get_date_from_gmt($album->created) ). "<br />".
					mysql2date( $plugin->timeFormat, get_date_from_gmt($album->created) );

				$album->modified =
					mysql2date( $plugin->dateFormat, get_date_from_gmt($album->modified) ). "<br />".
					mysql2date( $plugin->timeFormat, get_date_from_gmt($album->modified) );

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
			WHERE
				`album_id` = '{$album_id}' AND
				`gallery_id` = 0
			ORDER BY `order` ASC
		");

		ob_start();
		foreach ($images as $image)
		{
			$imageObject = new flgalleryImage($image);

			$previewURL = $imageObject->resized(array('height' => 64), true);
?>
			<li style="float:left; width:150px; height:90px; overflow:hidden; margin:15px;">
				<label for="select-image-<?php echo $image->id; ?>">
					<span class="select-image-preview" style="display:block;">
						<img src="<?php echo $previewURL; ?>" alt="<?php echo $image->name; ?>" />
					</span>
					<span class="select-image" style="white-space:nowrap;">
						<input type="checkbox" class="select-image" id="select-image-<?php echo $image->id; ?>" name="images[]" value="<?php echo $image->id; ?>" />
						<small title="<?php echo $image->name; ?>" style="font-size:10px;"><?php echo $func->shortFilename($image->name, 20, ''); ?></small>
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

		$album = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
		");
		if ($album !== false)
		{
			$admpage->head('New Gallery', 'new-gallery');
			$admpage->newGallery( array('name' => $album->title), array('album_id' => $album_id) );
		}
	}

	function addNewAlbum()
	{
		include FLGALLERY_GLOBALS;

		$admpage->head('Create', 'new-album');
		$plugin->tpl->t( 'media/new-album', array('href'=>$admpage->href) );
	}

	function createAlbum($a)
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`)
			FROM `{$plugin->dbAlbums}`
		");
		$order = $order === false ? 0 : (int)$order + 1;

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
		if ($res !== false)
		{
			$album_id = $wpdb->insert_id;
			return $album_id;
		}
		else {
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $media->errorN));
			return false;
		}
	}

	function editAlbum($album_id)
	{
		include FLGALLERY_GLOBALS;

		$album = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
			LIMIT 1
		");
		if ($album !== false)
		{
			$admpage->head('Album Properties', 'album');

			$album->selectAlbum = '';

			$picturesHTML = '';
			$pictures = $wpdb->get_results("
				SELECT *
				FROM `{$plugin->dbImages}`
				WHERE
					`album_id` = '{$album_id}' AND
					`gallery_id` = '0'
				ORDER BY `order` ASC
			");
			if ( $pictures !== false && count($pictures) )
			{
				foreach ($pictures as $picture)
				{
					$image = new flgalleryImage($picture);

					$picture->size = round($picture->size / 1024) .'&nbsp;KB';
					$picture->url = $plugin->imgURL .'/'. $picture->path;
					$thumbnail = $image->resized(array('height' => 80));
					$picture->previewURL = $thumbnail ? $func->url($thumbnail) : $picture->url;
					$picture->href = $admpage->href .'&amp;album_id='. $album_id;
					$picturesHTML .= $tpl->parse('album/picture', $picture);
				}

				$albums = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbAlbums}`
					WHERE `id` <> '{$album_id}'
					ORDER BY `title` ASC
				");
				if ( $albums !== false && count($albums) )
				{
					foreach ($albums as $row)
						$album->selectAlbum .= "\n\t\t\t<option value='{$row->id}'>{$row->title}</option>";
				}
			}
			$album->jsURL = $plugin->jsURL;
			$album->href = $admpage->href;
			$album->addPictures = $admpage->actionButton( array('Add Pictures', true), 'addMediaPage', array('album_id'=>$album_id) );

			$createAtts = $picturesHTML ? array() : array('disabled'=>'disabled');
			$album->createGallery = $admpage->actionButton( 'Create Flash Gallery', 'albumToGallery', array('album_id'=>$album_id), NULL, $createAtts );

			$album->pictures = $picturesHTML;
			$tpl->t('album/album', $album);
		}
	}

	function deleteAlbum($album_id)
	{
		include FLGALLERY_GLOBALS;

		$wpdb->query("
			DELETE FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
		");

		$images = $wpdb->get_results("
			SELECT `id`
			FROM `{$plugin->dbImages}`
			WHERE
				`album_id` = '{$album_id}' AND
				`gallery_id` = 0
		");
		if ($images !== false)
		{
			foreach ($images as $image)
				$this->deleteImage($image->id);
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

		if ($applyToCopies)
		{
			return $wpdb->update( $plugin->dbImages, $data, array('path' => $image->path) );
		}
		else
		{
			$image->set($data);
			return $image->save();
		}
	}

	function deleteImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image !== false)
		{
			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbImages}`
				WHERE `id` = '{$image_id}'
			");
			if ($res !== false)
			{
				$copies = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbImages}`
					WHERE `path` = '{$image->path}'
				");
				if ( $copies !== false && count($copies) == 0 )
				{
					// Delete image
					unlink( $plugin->imgDir.'/'.$image->path );
					// Delete thumbnails
					preg_match('/(.*)(\..*)/', $image->path, $fname);
					$func->recurse( $plugin->tmpDir, '#^img-'.preg_quote($fname[1]).'\..+#i', 'unlink' );
				}
			}
			else
				return false;
		}
		else
			return false;

		return $image->id;
	}

	function copyImage( $image_id, $a )
	{
		include FLGALLERY_GLOBALS;

		$image_id = (int)$image_id;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image !== false)
		{
			unset($image->id);

			$image->album_id = isset($a['album_id']) ? $a['album_id'] : $image->album_id;
			$image->gallery_id = isset($a['gallery_id']) ? $a['gallery_id'] : $image->gallery_id;

			if ( isset($a['order']) )
				$image->order = $a['order'];

			$wpdb->insert( $plugin->dbImages, get_object_vars($image) );
		}
	}

	function moveImage( $image_id, $album_id )
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
		if ( empty($startText) )
			$startText = "Start Upload";

		$admpage->head('Add Pictures', 'addMediaPage');

		if ( !empty($a['album_id']) )
			$objectID = '&amp;album_id='.( (int)$_REQUEST['album_id'] );

		if ( !empty($a['gallery_id']) )
			$objectID = '&amp;gallery_id='.( (int)$_REQUEST['gallery_id'] );

		$tab = empty($_REQUEST['tab']) ? 'swfupload' : $_REQUEST['tab'];
		$admpage->tabmenu(
			array(
				'swfupload' =>	'Flash Uploader',
				'stdupload' =>	'Browser Uploader',
				'url' =>		'Add URLs',
				'archive' =>	'Upload Archive',
				'directory' =>	'Import from Folder',
				'wpmedia' =>	'Import from Media Library',
			),
			$tab, '&amp;action=addMediaPage'.$objectID
		);

		switch ($tab)
		{
			case 'swfupload':
				$addMediaPage = $media->swfUploader($a, false);
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

		$tpl->t( 'media/add', array(
			'href' => $admpage->href . $objectID,
			'content' => $addMediaPage,
			'max_image_dimensions' => '2880&times;1440',
			'start' => $startText
		));
	}

	/**
	 * "Flash Uploader" tab
	 */
	function swfUploader(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;

		$upload_id = mt_rand();

		$a['contentURL'] = $plugin->contentURL;
		$a['pluginURL'] = $plugin->url;
		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;
		$a['uploadsPath'] = preg_replace('#^'.preg_quote(FLGALLERY_SITE_URL).'/(.*)#', '$1', $plugin->uploadsURL).'/'.$upload_id;
		$a['contentPath'] = preg_replace('#^'.preg_quote(FLGALLERY_CONTENT_URL).'/(.*)#', '$1', $plugin->uploadsURL).'/'.$upload_id;
		$a['auth_cookie'] = is_ssl() ? $_COOKIE[SECURE_AUTH_COOKIE] : $_COOKIE[AUTH_COOKIE];
		$a['file_size_limit'] = wp_max_upload_size().'b';

		$out = $tpl->parse('media/add-swfupload', $a);

		if ($echo)
			echo $out;

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
		$a['upload_max_filesize'] = $func->bytesToM( $func->mToBytes(ini_get('upload_max_filesize')), ' MB' );
		$a['post_max_size'] = $func->bytesToM( $func->mToBytes(ini_get('post_max_size')), ' MB' );

		$a['max_size'] = ini_get('max_file_uploads');

		$out = $tpl->parse('media/add-stdupload', $a);

		if ($echo)
			echo $out;

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

		if ($echo)
			echo $out;

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

		if ($echo)
			echo $out;

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
		$a['uploadsPath'] = preg_replace('#^'.preg_quote(FLGALLERY_SITE_URL).'/(.*)#', '$1', $plugin->uploadsURL);
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-directory', $a);

		if ($echo)
			echo $out;

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

		if ($echo)
			echo $out;

		return $out;
	}

	/**
	 * Add images to albums and galleries
	 */
	function addMedia($a)
	{
		include FLGALLERY_GLOBALS;

		$album_id = empty($a['album_id']) ? 0 : $a['album_id'];
		$gallery_id = empty($a['gallery_id']) ? 0 : $a['gallery_id'];

		$order = $wpdb->get_var("
			SELECT MAX(`order`)
			FROM `{$plugin->dbImages}`
			WHERE
				`album_id` = '{$album_id}' AND
				`gallery_id` = '{$gallery_id}'
		");
		if ( $order === false )
		{
			$order = 0;
			$this->warning($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Warning', $this->warningN));
		}

		$added = $media->addFiles($plugin->imgDir);
		if ( count($added) )
		{
			foreach ($added as $key => $path)
			{
				if ( !empty($path) )
				{
					if (preg_match('#^(/|[a-z]:)#i', $path)) {
						$fullPath = $path;
						$path = '/'.preg_replace('/^'.preg_quote(ABSPATH, '/').'/', '', $path);
					}
					else {
						$fullPath = $plugin->imgDir.'/'.$path;
					}


					list($width, $height) = $imageSize = getimagesize($fullPath);

					$insert = $wpdb->insert(
						$plugin->dbImages,
						array(
							'album_id' =>	$album_id,
							'gallery_id' =>	$gallery_id,
							'order' =>		++$order,
							'type' =>		$imageSize['mime'],
							'path' =>		$path,
							'name' =>		basename( $this->files[$key] ),
							'title' =>		$this->filesInfo[$key]['title'],
							'description'=>	$this->filesInfo[$key]['description'],
							'link' =>		'',
							'target' =>		'',
							'width' =>		$width,
							'height' =>		$height,
							'size' =>		filesize($fullPath)
						)
					);
					if ($insert !== false)
					{
						$this->error($wpdb->last_error);
						$this->debug($wpdb->last_query, array('Error', $media->errorN));
					}
				}
			}
			if ( !empty($album_id) )
			{
				$wpdb->update( $plugin->dbAlbums, array('modified' => $func->now()), array('id' => $album_id) );
			}
		}
	}

	function addFiles($destDir)
	{
		include FLGALLERY_GLOBALS;

		$added = array();
		$tmpDirs = array();
		$destNames = array();

		// Browser Uploader
		if ( !empty($_FILES['stdUpload_file']) )
		{
			foreach ( $_FILES['stdUpload_file']['name'] as $key => $name )
			{
				if ( !empty($name) )
				{
					$this->addFile( $name, $_POST['stdUpload_title'][$key], $_POST['stdUpload_description'][$key] );
					$ext = $func->fileExtByMime( $func->fileMime($_FILES['stdUpload_file']['name'][$key]) );
					$destNames[$key] = basename( $func->uniqueFile($destDir."/%s{$ext}") );
				}
			}

			$files = $func->upload('stdUpload_file', $destDir, $destNames);
			if (!empty($files))
				$added = array_merge($added, $files);
		}

		// Add URLs
		if ( !empty($_POST['addFromURL_file']) && is_array($_POST['addFromURL_file']) )
		{
			$URLs = array();
			foreach ( $_POST['addFromURL_file'] as $key => $url )
			{
				if ( preg_match('#^(http[s]{0,1}://|)(.*)#i', $url, $m) && !empty($m[2]) )
				{
					$this->addFile( $url, $_POST['addFromURL_title'][$key], $_POST['addFromURL_description'][$key] );
					$ext = $func->fileExtByMime( $func->fileMime($url) );
					$destNames[$key] = basename( $func->uniqueFile($destDir."/%s{$ext}") );
				}
			}

			$files = $func->copyURLs($this->files, $destDir, $destNames);
			if (!empty($files))
				$added = array_merge($added, $files);
		}

		// Upload Archive
		if ( !empty($_FILES['zipUpload_file']) )
		{
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

			$data = &$_FILES['zipUpload_file'];
			foreach ( $data as $key => $value )
			{
				if ( !is_array($value) )
					$data[$key] = (array)$value;
			}

			foreach ( $data['name'] as $key => $name )
			{
				if ( !$data['error'][$key] )
				{
					$archiveName = &$data['tmp_name'][$key];
					$tmpDirs[] = $tmpDir = $plugin->uploadsDir.'/'.mt_rand();

					$archive = new PclZip( $archiveName );
					$archive->extract(PCLZIP_OPT_PATH, $tmpDir);
					unset($archive);
				}
			}

			$importFolder_path = preg_replace('#^'.preg_quote(ABSPATH).'(.*)#', '$1', $plugin->uploadsDir);
			$importFolder_delete = true;
		}

		// Import from Folder
		if ( !isset($importFolder_path) )
			$importFolder_path = &$_POST['importFolder_path'];

		if ( !isset($importFolder_delete) )
			$importFolder_delete = &$_POST['importFolder_delete'];

		if ( !empty($importFolder_path) )
		{
			$path = ABSPATH . $importFolder_path;

			$func->recurse( $path, '#.+#', array(&$this, 'addFile') );

			foreach ($this->files as $key => $path)
			{
				$ext = $func->fileExtByMime( $func->fileMime($path) );
				$destNames[$key] = basename( $func->uniqueFile($destDir."/%s{$ext}") );
			}

			$files = $func->copyFiles($this->files, $destDir, $destNames, !empty($importFolder_delete));
			if (!empty($files))
				$added = array_merge($added, $files);

			if (!empty($_POST['importFolder_delete_dir'])) {
				$tmpDirs[] = ABSPATH . $importFolder_path;
			}
		}

		// Import from Media Library
		$wpmedia_ids =& $_POST['wpmedia_id'];
		if ( !empty($wpmedia_ids) && is_array($wpmedia_ids) )
		{
			foreach ($wpmedia_ids as $key => $id)
			{
				$file = get_attached_file($id);
				$this->addFile($file, get_the_title($id), '');
				$added[] = $file;
			}
		}

		// Delete temporary directories
		if ( !empty($tmpDirs) )
		{
			foreach ($tmpDirs as $dir)
			{
				if ( is_dir($dir) )
					@rmdir($dir);
			}
		}

		return $added;
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
			SELECT ID
			FROM `{$wpdb->prefix}posts`
			WHERE `post_author` = 1
			AND `post_type` = 'attachment'
			AND `post_mime_type` IN ('image/gif', 'image/jpeg', 'image/png')
			ORDER BY `post_date` DESC
			LIMIT {$offset}, {$limit}
		");

		foreach ($items as $key => $item) {
			$thumbnail = wp_get_attachment_image_src($item->ID, array(300, 300));
			$items[$key]->thumbnail = array(
				'src' => $thumbnail[0],
				'width' => $thumbnail[1],
				'height' => $thumbnail[2]
			);
		}

		@header("Content-Type: application/json; charset=utf-8");
		echo json_encode($items);
	}
}

?>