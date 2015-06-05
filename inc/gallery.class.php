<?php

class flgalleryGallery extends flgalleryBaseClass
{
	var
		$id = 0,
		$type = 'default',
		$name = '',
		$author = 0,
		$authorName,
		$created = '',
		$modified = '',
		$order = 0,
		$width = 550,
		$height = 400,
		$settings = NULL,
		$settingsInfo = NULL,
		$settingsForm = NULL,
		$swfURL,
		$xmlFilePath,
		$xmlFileURL;

	function init($a)
	{
		include FLGALLERY_GLOBALS;

		if ($plugin->userLevel >= 5)
			$this->auth = ' 1 ';
		else
			$this->auth = " `{$plugin->dbGalleries}`.`author`='{$plugin->userID}' ";

		if ( is_object($a) )
			$a = get_object_vars($a);
		elseif ( !is_array($a) )
			$a = array('id' => $a);

		if ( !empty($a['id']) )
		{
			$this->id = (int)$a['id'];
			if ( !$this->load() )
				$this->id = 0;
		}
		else
		{
			$this->create($a);
		}

		if ($this->id)
		{
			$filename = "{$this->id}.xml";
			$this->xmlFilePath = "{$plugin->xmlDir}/{$plugin->blogID}/{$filename}";
			$this->xmlFileURL = "{$plugin->xmlURL}/{$plugin->blogID}/{$filename}";
		}
	}

	function create($a)
	{
		include FLGALLERY_GLOBALS;

		$this->id = 0;
		$this->type = empty($a['type']) ? 'default' : $a['type'];
		$this->name = empty($a['name']) ? __('New Gallery', $plugin->name) : $a['name'];
		$this->author = $plugin->userID;
		$this->modified = $this->created = date('Y-m-d H:i:s');
	}

	function load()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$gallery = $wpdb->get_row("
				SELECT *
				FROM `{$plugin->dbGalleries}`
				WHERE `id` = '{$this->id}'
			");
			if ( empty($gallery) )
			{
				$this->error( sprintf(__('Unable to load Gallery #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
			else
			{
				foreach ($gallery as $key => $val)
				{
					if ( isset($this->$key) )
						$this->$key = htmlspecialchars(stripslashes($val));
				}
				$author = get_userdata($this->author);
				$this->authorName = $author->display_name;
			}
		}
		else
			return false;

		return $this->id;
	}

	function save()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$conditions = array( 'id' => $this->id );
			if ($plugin->userLevel < 5)
				$conditions['author'] = $plugin->userID;

			$update = $wpdb->update(
				$plugin->dbGalleries,
				array(
					'order' => $this->order,
					'author' => $this->author,
					'type' => $this->type,
					'name' => $this->name,
					'width' => $this->width,
					'height' => $this->height,
					'created' => $this->created,
					'modified' => date('Y-m-d H:i:s'),
				),
				$conditions
			);
			if ($update === false)
			{
				$this->error($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Error', $this->errorN));
				return false;
			}
			else
			{
				$this->clearXmlCache();
				return $this->id;
			}
		}
		else
			return $this->saveNew();
	}

	function saveNew()
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`) as `order`
			FROM `{$plugin->dbGalleries}`
		");
		if ( $order === false )
		{
			$order = 0;
			$this->warning($wpdb->last_error);
			$this->debug($wpdb->last_query);
		}

		$this->order = $order + 1;

		$insert = $wpdb->insert(
			$plugin->dbGalleries,
			array(
				'order' => $this->order,
				'type' => $this->type,
				'name' => $this->name,
				'author' => $this->author,
				'created' => $this->created,
				'modified' => date('Y-m-d H:i:s'),
				'width' => $this->width,
				'height' => $this->height
			)
		);
		if ( $insert === false )
		{
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $this->errorN));
			return false;
		}
		else
			return $wpdb->insert_id;

	}

	function delete()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbGalleries}`
				WHERE `id` = '{$this->id}' AND {$this->auth}
			");
			if ($res !== false)
			{
				$delImages = $wpdb->query("
					DELETE FROM `{$plugin->dbImages}`
					WHERE `gallery_id` = '{$this->id}'
				");

				$delSettings = $wpdb->query("
					DELETE FROM `{$plugin->dbSettings}`
					WHERE `gallery_id` = '{$this->id}'
				");

				return $delImages !== false && $delSettings !== false;
			}
			else
			{
				$this->error( sprintf(__('Unable to delete Gallery #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
			}
		}
		return false;
	}

	function arrange($order)
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			if ( is_array($order) )
			{
				$IDs = "`id`='". implode("' OR `id`='", $order). "'";

				$images = $wpdb->get_results("
					SELECT `id`, `order`
					FROM `{$plugin->dbImages}`
					WHERE ({$IDs})
					ORDER BY `id`
				");
				if ( $images !== false )
				{
					$minOrder = (int)$wpdb->get_var("
						SELECT MIN(`order`)
						FROM `{$plugin->dbImages}`
						WHERE ({$IDs})
					");

					$result = array();

					foreach ($images as $image)
					{
						$imageOrder = array_search($image->id, $order) + $minOrder;
						$upd = $wpdb->update(
							$plugin->dbImages,
							array('order' => $imageOrder),
							array('id' => $image->id)
						);
						if ( $upd !== false)
							$result[$image->id] = $imageOrder;
					}

					return $result;
				}
			}
			else if ( is_string($order) )
			{
				switch ($order)
				{
					case 'desc':
						$order = 'DESC';
						break;
					default:
						$order = 'ASC';
				}
				$images = $wpdb->get_results("
					SELECT `id`
					FROM `{$plugin->dbImages}`
					WHERE `gallery_id` = '{$this->id}'
					ORDER BY `title` {$order}, `name` {$order}
				");
				if ($images !== false)
				{
					$result = array();

					$n = 0;
					foreach ($images as $image)
					{
						$upd = $wpdb->update(
							$plugin->dbImages,
							array('order' => $n++),
							array('id' => $image->id)
						);
						if ( $upd !== false)
							$result[$image->id] = $order;
					}

					return $result;
				}
			}
		}
		return false;
	}

	function getSettingsXml()
	{
		include FLGALLERY_GLOBALS;

		if ( empty($plugin->galleryInfo[$this->type]['settings']) )
		{
			$settingsDir = $this->isLegacy() ? 'settings.legacy' : 'settings';

			if ( defined('FLGALLERY_PHP5') )
			{
				// PHP 5
				$settings = simplexml_load_file(FLGALLERY_PLUGIN_DIR."/{$settingsDir}/{$this->type}.xml");
			}
			else
			{
				// PHP 4
				require_once FLGALLERY_INCLUDE.'/simplexml.class.php';
				$simplexml = new simplexml();
				$settings = $simplexml->xml_load_file(FLGALLERY_PLUGIN_DIR."/{$settingsDir}/{$this->type}.xml");
			}

			$plugin->galleryInfo[$this->type]['settings'] = $settings;
		}

		return $plugin->galleryInfo[$this->type]['settings'];
	}

	function getSettings($reload = false)
	{
		include FLGALLERY_GLOBALS;

		if ( $reload || empty($this->settings) )
		{
			$results = $wpdb->get_results("
				SELECT *
				FROM
					`{$plugin->dbGalleries}`,
					`{$plugin->dbSettings}`
				WHERE
					`{$plugin->dbGalleries}`.`id` = {$plugin->dbSettings}.`gallery_id` AND
					`{$plugin->dbSettings}`.`gallery_id` = '{$this->id}' AND
					`{$plugin->dbSettings}`.`gallery_type` = '{$this->type}'
			");
			if ( $results !== false )
			{
				foreach ($results as $res)
					$this->settings[$res->name] = htmlspecialchars(stripslashes($res->value));

				$settingsXml = $this->getSettingsXml();

				if ( !empty($settingsXml->group) )
				{
					if ( defined('FLGALLERY_PHP5') )
					{
						$groups = &$settingsXml->group;
					}
					else
					{
						if ( !is_array($settingsXml->group) )
							$groups = array( $settingsXml->group );
						else
							$groups = $settingsXml->group;
					}

					foreach ( $groups as $group )
					{
						$groupAtt = $group->attributes();
						foreach ( $group->items->param as $param )
						{
							if ( is_object($param) )
								$paramAtt = $param->attributes();
							else
								$paramAtt = (object)$param;

							$name = (string)$groupAtt->name . '.' . (string)$paramAtt->name;

							if ( !isset($this->settings[$name]) )
								$this->settings[$name] = (string)$paramAtt->default;

							if ( defined('WP_ADMIN') )
							{
								$this->settingsInfo[$name] = $param;

								$this->settingsForm[$name] = array(
									'title' => htmlspecialchars( (string)$param->title ),
									'description' => isset($param->description) ? htmlspecialchars( (string)$param->description ) : '',
									'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
								);
							}
						}
					}
				}

				if ( !empty($settingsXml->param) )
				{
					if ( defined('FLGALLERY_PHP5') )
					{
						$params = &$settingsXml->param;
					}
					else
					{
						if ( !is_array($settingsXml->param) )
							$params = array( $settingsXml->param );
						else
							$params = $settingsXml->param;
					}

					foreach ( $params as $param )
					{
						if ( is_object($param) )
							$paramAtt = $param->attributes();
						else
							$paramAtt = (object)$param;

						$name = (string)$paramAtt->name;

						if ( !isset($this->settings[$name]) )
							$this->settings[$name] = (string)$paramAtt->default;

						if ( defined('WP_ADMIN') )
						{
							$this->settingsInfo[$name] = $param;

							$this->settingsForm[$name] = array(
								'title' => htmlspecialchars( (string)$param->title ),
								'description' => isset($param->description) ? htmlspecialchars( (string)$param->description ) : '',
								'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
							);
						}
					}
				}
			}
			else
			{
				$this->error( sprintf(__('Unable to load Gallery #%s Settings (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
		}
		return $this->settings;
	}

	function saveSettings()
	{
		if ( $this->getSettings(false) )
		{
			include FLGALLERY_GLOBALS;

			$results = $wpdb->get_results("
				SELECT `id`, `name`
				FROM `{$plugin->dbSettings}`
				WHERE
					`gallery_id` = '{$this->id}' AND
					`gallery_type` = '{$this->type}'
			");
			if ($results)
			{
				foreach ($results as $res)
				{
					$keys[$res->name] = $res->id;
				}
			}

			foreach ($this->settings as $name => $value)
			{
				if ( isset($keys[$name]) )
				{
					$update = $wpdb->update(
						$plugin->dbSettings,
						array(
							'value' => $value
						),
						array(
							'id' => $keys[$name]
						)
					);
				}
				else
				{
					$insert = $wpdb->insert(
						$plugin->dbSettings,
						array(
							'gallery_id' => $this->id,
							'gallery_type' => $this->type,
							'name' => $name,
							'value' => $value
						)
					);
				}
			}
			$this->clearXmlCache();
			return true;
		}
		return false;
	}

	function resetSettings()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			return $wpdb->query("
				DELETE FROM `{$plugin->dbSettings}`
				WHERE
					`gallery_id` = '{$this->id}' AND
					`gallery_type` = '{$this->type}'
			");
		}
	}

	function getItems()
	{
		include FLGALLERY_GLOBALS;

		$this->items = $wpdb->get_results("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `gallery_id` = '{$this->id}'
			ORDER BY `order` ASC
		");
		$this->itemsCount = $this->items !== false ? count($this->items) : 0;

		return $this->items;
	}

	function getItemsCount()
	{
		if ( empty($this->itemsCount) )
		{
			include FLGALLERY_GLOBALS;

			$this->itemsCount = (int)$wpdb->get_var("
				SELECT COUNT(*)
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$this->id}'
			");
		}
		return $this->itemsCount;
	}

	function getSwf()
	{
		if ( empty($this->swfURL) )
		{
			include FLGALLERY_GLOBALS;

			$src = $plugin->galleryInfo[$this->type]['src'];

			if ( function_exists('flgallery_commercial_getSWF') )
				return $this->swfURL = flgallery_commercial_getSWF($src);

			$c = $this->getSignature();
			if ( !empty($plugin->points[$c]) )
			{
				$path = '/swf/'.$src;
				$stat = stat( FLGALLERY_PLUGIN_DIR.$path );
				if ( md5((string)$stat[7]) != $plugin->points[$c] )
					return '';

				return $this->swfURL = FLGALLERY_PLUGIN_URL.$path;
			}
		}
		return $this->swfURL;
	}

	function getPopupUrl()
	{
		include FLGALLERY_GLOBALS;

		return $plugin->url."/popup.php?id={$this->id}";
	}

	function getPopupJs()
	{
		include FLGALLERY_GLOBALS;

		$url = $this->getPopupUrl();

		$height = $this->height;

		if ( !defined('WP_ADMIN') )
			$url .= '&frontend=1';
		else
			$height += 25;

		return "window.open('{$url}', '{$plugin->name}', 'location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,left='+(screen.availWidth-{$this->width})/2+',top='+(screen.availHeight-{$height})/2+',width={$this->width},height={$height}'); return false;";
	}

	function getPopupLink( $text )
	{
		$url = $this->getPopupUrl();
		$onclick = $this->getPopupJs();

		if ( preg_match('/(.*?)#(.*?)#(.*)/s', $text, $m) )
		{
			$text_before = $m[1];
			$text = $m[2];
			$text_after = $m[3];
		}
		else
		{
			$text_before = '';
			$text_after = '';
		}

		return "{$text_before}<a href=\"{$url}\" onclick=\"{$onclick}\">{$text}</a>{$text_after}";
	}

	function getHtml()
	{
		include FLGALLERY_GLOBALS;

		$items = $this->getItems();
		if ( $items !== false )
		{
			$altContent = "\n";
			foreach ($items as $item)
			{
				$image = new flgalleryImage($item);
				$thumbnail = $image->resized(array('height' => 120), true);

				$item->title = htmlspecialchars($item->title);
				$item->description = htmlspecialchars($item->description);

				$altContent .= "\t\t<li style=\"display:inline;\"><a href=\"{$plugin->imgURL}/{$item->path}\"><img src=\"{$thumbnail}\" alt=\"{$item->title}\" /></a>{$item->description}</li>\n";
			}
			$altContent = '<div class="flgallery-altcontent"><ol style="list-style:none;">'.$altContent."\t</ol></div>";
		}
		else
		{
			$altContent = '<a class="flgallery-altcontent" href="http://www.adobe.com/go/getflashplayer" rel="nofollow"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a>';
		}

		$xmlFile = $plugin->url."/gallery-xml.php?id={$this->id}&blog_id={$plugin->blogID}";

		$flash = $func->flash(
			$plugin->name.'-'.$this->id,	// id
			$this->getSwf(),				// url
			$this->width,					// dimensions
			$this->height,
			array(
				'flashVars' => 'XMLFile='.rawurlencode($xmlFile),
				'allowFullScreen' => 'true',
				'allowScriptAccess' => 'always',
				'quality' => 'high',
				'wmode' => 'transparent',
				'swfversion' => '9.0.45.0',
			),
			$altContent,
			false
		);

		$altgallery = $tpl->parse('altgallery', array_merge(get_object_vars($this), array(
			'pluginURL' => $plugin->url,
			'xmlFile' => $xmlFile
		)));

		$style = "width: {$this->width}px; height: {$this->height}px; overflow: hidden;";
		$style = str_replace('%px', '%', $style);

		$this->getSettings();
		if (isset($this->settings['background.transparent']) && $this->settings['background.transparent'] == 'false') {
			$background = '';
			if ( !empty($this->settings['background.color']) )
				$background .= " #{$this->settings['background.color']}";

			if ( !empty($this->settings['background.image']) )
				$background .= " url({$this->settings['background.image']}) no-repeat center";

			if ( !empty($background) )
				$style .= " background:{$background};";
		}

		$html = '<div class="flgallery-'.$this->id.' flgallery-'.strtolower($this->type).' flgallery-embed" style="'.$style.'">'.$flash.$altgallery.'</div>';

		$html = preg_replace('/[\r\n\s\t]+/', ' ', $html);
		$html = preg_replace('/\s*([<>])\s*/', '$1', $html);

		return $html;
	}

	function getXml()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$plugin->lastGalleryType = $this->type;

			$xmlDir = $this->isLegacy() ? 'xml.legacy' : 'xml';

			$galleryTemplate = "{$xmlDir}/{$this->type}.xml";
			$albumTemplate = "{$xmlDir}/{$this->type}-album.xml";
			$itemTemplate = "{$xmlDir}/{$this->type}-item.xml";

			$images = $wpdb->get_results("
				SELECT *
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$this->id}'
				ORDER BY `order`
			");
			if ($images !== false)
			{
				$a = $this->getSettings();
				$a['loader'] = 'true';
				$a['items'] = '';

				if ( $this->type == 'PhotoFlow' && $a['colorScheme'] == 'custom' )
					$a['custom'] = 'true';

				if ( $this->type == 'Zen' && $a['initialState'] == 'Show Albums' )
					$a['multipleAlbums'] = true;

				$maxImageWidth = 2880;
				$maxImageHeight = $this->type == 'PhotoFlow' ? 1440 : 2880;

				$thumbSize = $this->getThumbSize();

				if ($thumbSize['width'] >= $thumbSize['height'])
					$thumbSize['height'] = 0;
				else
					$thumbSize['width'] = 0;

				$thumbSize['width'] *= 2;
				$thumbSize['height'] *= 2;

				foreach ($images as $img)
				{
					if (strpos($img->path, '/') === 0) {
						$img->source = FLGALLERY_SITE_URL.$img->path;
					}
					else {
						$img->source = $plugin->imgURL.'/'.$img->path;
					}

					$img->thumbnail = $img->source;

					if ( !$plugin->stats->deadline() )
					{
						$image = new flgalleryImage($img);

						$size = $image->scale('fill',
							array(
								'width' => $img->width,
								'height' => $img->height
							),
							array(
								'width' => ($w = $this->width * 2) > $maxImageWidth ? $maxImageWidth : $w,
								'height' => ($h = $this->height * 2) > $maxImageHeight ? $maxImageHeight : $h
							)
						);

						if ($size['width'] < $img->width || $size['height'] < $img->height) {
							$img->source = $image->resized($size, true, true);
						}

						if (($thumbSize['width'] || $thumbSize['height']) && ($thumbSize['width'] < $img->width && $thumbSize['height'] < $img->height))
						{
							$thumbnail = $image->resized($thumbSize, true, true);
							$img->thumbnail = empty($thumbnail) ? $img->source : $thumbnail;
						}
					}

					if ( !empty($img->description) )
					{
						$description = ( !empty($img->title) ? rtrim($img->title, '. ') . '. ' : '' ) . $img->description;
					}
					else
					{
						if ( !empty($img->title) )
							$description = $img->title;
						else
							$description = '';
							//$description = $func->filenameToTitle($img->name);
					}

					if (
						isset($this->settings['lightbox.useLightbox']) && $this->settings['lightbox.useLightbox'] == 'true' &&
						(empty($img->link) || (isset($this->settings['lightbox.overrideLinks']) && $this->settings['lightbox.overrideLinks'] == 'true'))
					) {
						$img->link = "javascript:jQuery.altbox('{$img->source}',{images:{folder:'{$plugin->url}/img/'}});";
						$img->target = "_self";
					}

					$item = array(
						'source' => $img->source,
						'thumbnail' => $img->thumbnail,
						'description' => htmlspecialchars(stripslashes( $description )),
						'link' => htmlspecialchars(stripslashes( $img->link )),
						'target' => $img->target
					);

					if ( !empty($a['multipleAlbums']) )
						$albumItems[$img->album_id] .= $plugin->tpl->parse($itemTemplate, $item);
					else
						$a['items'] .= $plugin->tpl->parse($itemTemplate, $item);
				}

				if ( !empty($a['multipleAlbums']) )
				{
					$a['albums'] = '';
					foreach ( $albumItems as $album_id => $items )
					{
						$description = $wpdb->get_var("
							SELECT `title`
							FROM `{$plugin->dbAlbums}`
							WHERE `id` = '{$album_id}'
						");

						$a['albums'] .= $plugin->tpl->parse($albumTemplate, array(
							'icon' => '',
							'thumbnailsFolder' => $plugin->imgURL.'/',
							'imagesFolder' => $plugin->imgURL.'/',
							'description' => htmlspecialchars(stripslashes( $description )),
							'items' => $items
						));
					}
				}

				$xml = $plugin->tpl->parse($galleryTemplate, $a);

				if ($this->isLegacy()) {
					$xml = str_replace("\x6c\x6f\x61\x64\x65\x72>", "\x49\x6f\x61\x64\x65\x72>", $xml);
				}

				$this->cacheXml($xml);

				return $xml;
			}
		}
		return false;
	}

	function getThumbSize()
	{
		switch ($this->type)
		{
			case 'Art':
				if ($this->settings['preview.usePreview'] == 'true') {
					$width = $this->settings['preview.width'];
					$height = $this->settings['preview.height'];
				}
				else {
					$width = $this->settings['thumbnail.width'];
					$height = $this->settings['thumbnail.height'];
				}
				break;

			case 'Cubic':
				$width = $this->width / 3.5;
				$height = $this->height / 3.0;
				break;

			case 'PhotoFlow':
				$width = $this->settings['maxImageWidth'];
				$height = 0;
				break;

			case 'StackPhoto':
				$width = $this->settings['image.width'];
				$height = $this->settings['image.height'];
				break;

			case 'Zen':
				$width = $this->settings['iconWidth'];
				$height = $this->settings['iconHeight'];
				break;

			default:
				$width = isset($this->settings['thumbnail.width']) ? $this->settings['thumbnail.width'] : 0;
				$height = isset($this->settings['thumbnail.height']) ? $this->settings['thumbnail.height'] : 0;
				break;
		}

		return array('width' => (int)$width, 'height' => (int)$height);
	}

	function getSignature()
	{
		if ( empty($this->signature) )
			$this->signature = md5("{$this->type}.swf");

		return $this->signature;
	}

	function isLegacy()
	{
		global $flgalleryProducts;
		$s = $this->getSignature();
		return !empty($flgalleryProducts[$s]) && $flgalleryProducts[$s] !== true;
	}

	function cacheXml($xml)
	{
		if ($this->id)
		{
			$xmlDir = dirname($this->xmlFilePath);
			if ( !file_exists($xmlDir) )
			{
				mkdir($xmlDir, 0777, true);
			}

			if ( $fp = fopen($this->xmlFilePath, 'w') )
			{
				$w = fwrite($fp, $xml);
				fclose($fp);
				return $w;
			}
		}
		return false;
	}

	function clearXmlCache()
	{
		if ($this->id)
		{
			if ( file_exists($this->xmlFilePath) )
				return unlink($this->xmlFilePath);
		}
		return false;
	}

}

?>