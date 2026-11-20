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
		$items = NULL,
		$itemsCount = 0,
		$auth,
		$signature,
		$typeTitle,
		$xmlFilePath,
		$xmlFileURL;

	// Number of galleries rendered on the current page (to keep DOM ids unique)
	static $renderCount = array();

	function init($a)
	{
		include FLGALLERY_GLOBALS;

		if ($plugin->userLevel >= 5) {
			$this->auth = ' 1 ';
		} else {
			$this->auth = " `{$plugin->dbGalleries}`.`author`='{$plugin->userID}' ";
		}

		if (is_object($a)) {
			$a = get_object_vars($a);
		} elseif (!is_array($a)) {
			$a = array('id' => $a);
		}

		if (!empty($a['id'])) {
			$this->id = (int)$a['id'];
			if (!$this->load()) {
				$this->id = 0;
			}
		} else {
			$this->create($a);
		}

		if ($this->id) {
			$filename = "{$this->id}.xml";
			$this->xmlFilePath = "{$plugin->xmlDir}/{$plugin->blogID}/{$filename}";
			$this->xmlFileURL = "{$plugin->xmlURL}/{$plugin->blogID}/{$filename}";
		}
	}

	function create($a)
	{
		include FLGALLERY_GLOBALS;

		$this->id = 0;
		$this->type = !empty($a['type']) && $plugin->isGalleryType($a['type']) ? $a['type'] : 'Art';
		$this->name = empty($a['name']) ? __('New Gallery', $plugin->name) : $a['name'];
		$this->author = $plugin->userID;
		$this->modified = $this->created = date('Y-m-d H:i:s');
	}

	function load()
	{
		if ($this->id) {
			include FLGALLERY_GLOBALS;

			$gallery = $wpdb->get_row("
				SELECT *
				FROM `{$plugin->dbGalleries}`
				WHERE `id` = '{$this->id}'
			");
			if (empty($gallery)) {
				$this->error(sprintf(__('Unable to load Gallery #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error));
				$this->debug("SQL: {$wpdb->last_query}", array('Error', $this->errorN));
				return false;
			} else {
				foreach ($gallery as $key => $val) {
					if (isset($this->$key)) {
						$this->$key = $val;
					}
				}
				$author = get_userdata((int)$this->author);
				$this->authorName = $author ? $author->display_name : '';
			}
		} else {
			return false;
		}

		return $this->id;
	}

	function save()
	{
		if ($this->id) {
			include FLGALLERY_GLOBALS;

			$conditions = array('id' => $this->id);
			if ($plugin->userLevel < 5) {
				$conditions['author'] = $plugin->userID;
			}

			if (!strlen(trim($this->name))) {
				$this->name = "Gallery #{$this->id}";
			}

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
			if ($update === false) {
				$this->error($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Error', $this->errorN));
				return false;
			} else {
				$this->clearXmlCache();
				return $this->id;
			}
		} else {
			return $this->saveNew();
		}
	}

	function saveNew()
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`) as `order`
			FROM `{$plugin->dbGalleries}`
		");
		if ($order === false) {
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
		if ($insert === false) {
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $this->errorN));
			return false;
		} else {
			return $wpdb->insert_id;
		}

	}

	function delete()
	{
		if ($this->id) {
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbGalleries}`
				WHERE `id` = '{$this->id}' AND {$this->auth}
			");
			if ($res !== false) {
				$wpdb->query("
					DELETE FROM `{$plugin->dbSettings}`
					WHERE `gallery_id` = '{$this->id}'
				");

				$images = $wpdb->get_results("
					SELECT `id`
					FROM `{$plugin->dbImages}`
					WHERE `gallery_id` = '{$this->id}'
				");
				if ($images !== false) {
					foreach ($images as $image) {
						$media->deleteImage($image->id);
					}
				}

				return true;
			} else {
				$this->error(sprintf(__('Unable to delete Gallery #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error));
				$this->debug("SQL: {$wpdb->last_query}", array('Error', $this->errorN));
			}
		}
		return false;
	}

	function arrange($order)
	{
		if ($this->id) {
			include FLGALLERY_GLOBALS;

			if (is_array($order)) {
				$order = array_map('intval', $order);

				$IDs = "`id`='" . implode("' OR `id`='", $order) . "'";

				$images = $wpdb->get_results("
					SELECT `id`, `order`
					FROM `{$plugin->dbImages}`
					WHERE ({$IDs})
					ORDER BY `id`
				");
				if ($images !== false) {
					$minOrder = (int)$wpdb->get_var("
						SELECT MIN(`order`)
						FROM `{$plugin->dbImages}`
						WHERE ({$IDs})
					");

					$result = array();

					foreach ($images as $image) {
						$imageOrder = array_search($image->id, $order) + $minOrder;
						$upd = $wpdb->update(
							$plugin->dbImages,
							array('order' => $imageOrder),
							array('id' => $image->id)
						);
						if ($upd !== false) {
							$result[$image->id] = $imageOrder;
						}
					}

					return $result;
				}
			} elseif (is_string($order)) {
				switch ($order) {
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
				if ($images !== false) {
					$result = array();

					$n = 0;
					foreach ($images as $image) {
						$upd = $wpdb->update(
							$plugin->dbImages,
							array('order' => $n++),
							array('id' => $image->id)
						);
						if ($upd !== false) {
							$result[$image->id] = $order;
						}
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

		if (!$plugin->isGalleryType($this->type)) {
			return false;
		}

		if (empty($plugin->galleryInfo[$this->type]['settings'])) {
			$settingsDir = $this->isLegacy() ? 'settings.legacy' : 'settings';
			$settingsFile = FLGALLERY_PLUGIN_DIR . "/{$settingsDir}/{$this->type}.xml";

			$plugin->galleryInfo[$this->type]['settings'] = file_exists($settingsFile) ? simplexml_load_file($settingsFile) : false;
		}

		return $plugin->galleryInfo[$this->type]['settings'];
	}

	function getSettings($reload = false)
	{
		include FLGALLERY_GLOBALS;

		if ($reload || empty($this->settings)) {
			$results = $wpdb->get_results($wpdb->prepare("
				SELECT `name`, `value`
				FROM `{$plugin->dbSettings}`
				WHERE `gallery_id` = %d
				AND `gallery_type` = %s
			", $this->id, $this->type));
			if (is_array($results)) {
				$this->settings = array();
				foreach ($results as $res) {
					$this->settings[$res->name] = esc_html($res->value);
				}

				$settingsXml = $this->getSettingsXml();

				if (!empty($settingsXml->group)) {
					$groups = $settingsXml->group;

					foreach ($groups as $group) {
						$groupAtt = $group->attributes();
						foreach ($group->items->param as $param) {
							if (is_object($param)) {
								$paramAtt = $param->attributes();
							} else {
								$paramAtt = (object)$param;
							}

							$name = (string)$groupAtt->name . '.' . (string)$paramAtt->name;

							if (!isset($this->settings[$name])) {
								$this->settings[$name] = (string)$paramAtt->default;
							}

							if (defined('WP_ADMIN')) {
								$this->settingsInfo[$name] = $param;

								$this->settingsForm[$name] = array(
									'title' => esc_html((string)$param->title),
									'description' => isset($param->description) ? esc_html((string)$param->description) : '',
									'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
								);
							}
						}
					}
				}

				if (!empty($settingsXml->param)) {
					$params = $settingsXml->param;

					foreach ($params as $param) {
						if (is_object($param)) {
							$paramAtt = $param->attributes();
						} else {
							$paramAtt = (object)$param;
						}

						$name = (string)$paramAtt->name;

						if (!isset($this->settings[$name])) {
							$this->settings[$name] = (string)$paramAtt->default;
						}

						if (defined('WP_ADMIN')) {
							$this->settingsInfo[$name] = $param;

							$this->settingsForm[$name] = array(
								'title' => esc_html((string)$param->title),
								'description' => isset($param->description) ? esc_html((string)$param->description) : '',
								'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
							);
						}
					}
				}
			} else {
				$this->error(sprintf(__('Unable to load Gallery #%s Settings (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error));
				$this->debug("SQL: {$wpdb->last_query}", array('Error', $this->errorN));
				return false;
			}
		}
		return $this->settings;
	}

	function saveSettings()
	{
		if ($this->getSettings(false)) {
			include FLGALLERY_GLOBALS;

			$keys = array();
			$results = $wpdb->get_results($wpdb->prepare("
				SELECT `id`, `name`
				FROM `{$plugin->dbSettings}`
				WHERE `gallery_id` = %d
				AND `gallery_type` = %s
			", $this->id, $this->type));
			if ($results) {
				foreach ($results as $res) {
					$keys[$res->name] = $res->id;
				}
			}

			foreach ($this->settings as $name => $value) {
				if (isset($keys[$name])) {
					$wpdb->update(
						$plugin->dbSettings,
						array(
							'value' => $value
						),
						array(
							'id' => $keys[$name]
						)
					);
				} else {
					$wpdb->insert(
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
		if ($this->id) {
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			$this->settings = NULL;

			return $wpdb->query($wpdb->prepare("
				DELETE FROM `{$plugin->dbSettings}`
				WHERE `gallery_id` = %d
				AND `gallery_type` = %s
			", $this->id, $this->type));
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
		if (empty($this->itemsCount)) {
			include FLGALLERY_GLOBALS;

			$this->itemsCount = (int)$wpdb->get_var("
				SELECT COUNT(*)
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$this->id}'
			");
		}
		return $this->itemsCount;
	}

	function getPopupUrl()
	{
		return admin_url('admin-ajax.php') . "?action=flgalleryPopup&gallery_id={$this->id}";
	}

	function getPopupJs()
	{
		include FLGALLERY_GLOBALS;

		$url = $this->getPopupUrl();

		$width = (int)$this->width;
		$height = (int)$this->height;

		if (!is_admin()) {
			$url .= '&frontend=1';
		} else {
			$height += 25;
		}

		$url = esc_js($url);

		return "window.open('{$url}', '{$plugin->name}', 'location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,left='+(screen.availWidth-{$width})/2+',top='+(screen.availHeight-{$height})/2+',width={$width},height={$height}'); return false;";
	}

	function getPopupLink($text)
	{
		$url = esc_html($this->getPopupUrl());
		$onclick = $this->getPopupJs();

		if (preg_match('/(.*?)#(.*?)#(.*)/s', html_entity_decode($text, ENT_QUOTES), $m)) {
			$text_before = $m[1];
			$text = $m[2];
			$text_after = $m[3];
		} else {
			$text_before = '';
			$text_after = '';
		}

		$onclick = esc_attr($onclick);

		return "{$text_before}<a href=\"{$url}\" onclick=\"{$onclick}\">{$text}</a>{$text_after}";
	}

	/**
	 * URL of a picture stored in the images table (path is either relative to the plugin's
	 * images directory or, for pictures imported from elsewhere, absolute from the site root).
	 */
	function getImageUrl($path)
	{
		include FLGALLERY_GLOBALS;

		if (strpos($path, '/') === 0) {
			return FLGALLERY_SITE_URL . $path;
		}
		return $plugin->imgURL . '/' . $path;
	}

	/**
	 * Gallery dimension for CSS/JS: a number of pixels, or a percentage such as "100%".
	 */
	function cssSize($value, $default)
	{
		if (is_string($value) && preg_match('/^\d+(\.\d+)?%$/', trim($value))) {
			return trim($value);
		}
		$value = (int)$value;
		return ($value > 0 ? $value : $default) . 'px';
	}

	/**
	 * HTML code of the gallery: a container that the JavaScript gallery engine (js/altgallery.js)
	 * renders the gallery into, using the gallery's XML file as configuration.
	 */
	function getHtml()
	{
		include FLGALLERY_GLOBALS;

		if (!$this->id || !$plugin->isGalleryType($this->type)) {
			return '';
		}

		if (!file_exists($this->xmlFilePath)) {
			$this->getXml();
		}

		$plugin->enqueueScripts();

		// Unique DOM id, even if the same gallery is shown more than once on a page
		$n = isset(self::$renderCount[$this->id]) ? ++self::$renderCount[$this->id] : (self::$renderCount[$this->id] = 1);
		$domId = $plugin->name . '-' . $this->id . ($n > 1 ? '-' . $n : '');

		// Plain list of pictures for visitors without JavaScript and for search engines
		$noscript = '';
		$items = $this->getItems();
		if (!empty($items)) {
			foreach ($items as $item) {
				$image = new flgalleryImage($item);
				$thumbnail = $image->resized(array('height' => 120), true);

				$noscript .=
					'<li style="display:inline;">' .
						'<a href="' . esc_url($this->getImageUrl($item->path)) . '">' .
							'<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr($item->title) . '" />' .
						'</a>' .
						esc_html($item->description) .
					'</li>';
			}
			$noscript = '<noscript><ol style="list-style:none;">' . $noscript . '</ol></noscript>';
		}

		$width = $this->cssSize($this->width, 550);
		$height = $this->cssSize($this->height, 400);

		$options = array(
			'type' => $this->type,
			'width' => $width,
			'height' => $height,
			'images' => array('folder' => $plugin->url . '/img/'),
			'config' => $this->xmlFileURL,
			'configType' => 'xml',
			'fullscreen' => 'never',
		);

		$altgallery = $tpl->parse('altgallery', array(
			'options' => wp_json_encode($options),
			'selector' => wp_json_encode('#' . $domId . ' .flgallery-altcontent'),
		));

		$style = "width: {$width}; height: {$height}; overflow: hidden;";

		$this->getSettings();
		if (isset($this->settings['background.transparent']) && $this->settings['background.transparent'] == 'false') {
			$background = '';

			if (!empty($this->settings['background.color']) && preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $this->settings['background.color'], $m)) {
				$background .= " #{$m[1]}";
			}

			if (!empty($this->settings['background.image'])) {
				$background .= ' url(' . esc_url(html_entity_decode($this->settings['background.image'], ENT_QUOTES)) . ') no-repeat center';
			}

			if (!empty($background)) {
				$style .= " background:{$background};";
			}
		}

		$html =
			'<div class="flgallery-' . $this->id . ' flgallery-' . esc_attr(strtolower($this->type)) . ' flgallery-embed" style="' . esc_attr($style) . '">' .
				'<div id="' . esc_attr($domId) . '" class="flgallery-container" style="width:100%; height:100%;">' .
					'<div class="flgallery-altcontent" style="width:100%; height:100%;">' . $noscript . '</div>' .
				'</div>' .
				$altgallery .
			'</div>';

		// Keep the markup on one line, so that wpautop() and similar filters don't break it
		$html = preg_replace('/[\r\n\s\t]+/', ' ', $html);
		$html = preg_replace('/\s*([<>])\s*/', '$1', $html);

		return $html;
	}

	function getXml()
	{
		if ($this->id) {
			include FLGALLERY_GLOBALS;

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
			if ($images !== false) {
				$a = $this->getSettings();
				$a['loader'] = 'true';
				$a['items'] = '';

				if ($this->type == 'PhotoFlow' && isset($a['colorScheme']) && $a['colorScheme'] == 'custom') {
					$a['custom'] = 'true';
				}

				$albumItems = array();
				if ($this->type == 'Zen' && isset($a['initialState']) && $a['initialState'] == 'Show Albums') {
					$a['multipleAlbums'] = true;
				}

				$maxImageWidth = 2880;
				$maxImageHeight = $this->type == 'PhotoFlow' ? 1440 : 2880;

				$thumbSize = $this->getThumbSize();

				if ($thumbSize['width'] >= $thumbSize['height']) {
					$thumbSize['height'] = 0;
				} else {
					$thumbSize['width'] = 0;
				}

				$thumbSize['width'] *= 2;
				$thumbSize['height'] *= 2;

				$galleryWidth = (int)$this->width;
				$galleryHeight = (int)$this->height;

				foreach ($images as $img) {
					$img->source = $this->getImageUrl($img->path);

					$img->thumbnail = $img->source;

					if (!$plugin->stats->deadline() && $img->width > 0 && $img->height > 0 && $galleryWidth > 0 && $galleryHeight > 0) {
						$image = new flgalleryImage($img);

						$size = $image->scale('fill',
							array(
								'width' => $img->width,
								'height' => $img->height
							),
							array(
								'width' => min($galleryWidth * 2, $maxImageWidth),
								'height' => min($galleryHeight * 2, $maxImageHeight)
							)
						);

						if ($size['width'] < $img->width || $size['height'] < $img->height) {
							$img->source = $image->resized($size, true, true);
						}

						if (($thumbSize['width'] || $thumbSize['height']) && ($thumbSize['width'] < $img->width && $thumbSize['height'] < $img->height)) {
							$thumbnail = $image->resized($thumbSize, true, true);
							$img->thumbnail = empty($thumbnail) ? $img->source : $thumbnail;
						}
					}

					if (!empty($img->description)) {
						$description = (!empty($img->title) ? rtrim($img->title, '. ') . '. ' : '') . $img->description;
					} else {
						if (!empty($img->title)) {
							$description = $img->title;
						} else {
							$description = '';
						}
					}

					if (
						isset($this->settings['lightbox.useLightbox']) && $this->settings['lightbox.useLightbox'] == 'true' &&
						(empty($img->link) || (isset($this->settings['lightbox.overrideLinks']) && $this->settings['lightbox.overrideLinks'] == 'true'))
					) {
						$img->link = "javascript:jQuery.altbox('{$img->source}',{images:{folder:'{$plugin->url}/img/'}});";
						$img->target = "_self";
					}

					$item = array(
						'source' => esc_html($img->source),
						'thumbnail' => esc_html($img->thumbnail),
						'description' => esc_html($description),
						'link' => esc_html($img->link),
						'target' => esc_html($img->target)
					);

					if (!empty($a['multipleAlbums'])) {
						if (!isset($albumItems[$img->album_id])) {
							$albumItems[$img->album_id] = '';
						}
						$albumItems[$img->album_id] .= $plugin->tpl->parse($itemTemplate, $item);
					} else {
						$a['items'] .= $plugin->tpl->parse($itemTemplate, $item);
					}
				}

				if (!empty($a['multipleAlbums'])) {
					$a['albums'] = '';
					foreach ($albumItems as $album_id => $items) {
						$description = (string)$wpdb->get_var($wpdb->prepare("
							SELECT `title`
							FROM `{$plugin->dbAlbums}`
							WHERE `id` = %d
						", $album_id));

						$a['albums'] .= $plugin->tpl->parse($albumTemplate, array(
							'icon' => '',
							'thumbnailsFolder' => esc_html($plugin->imgURL . '/'),
							'imagesFolder' => esc_html($plugin->imgURL . '/'),
							'description' => esc_html($description),
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
		$s = is_array($this->settings) ? $this->settings : array();
		$get = function ($name) use ($s) {
			return isset($s[$name]) ? (int)$s[$name] : 0;
		};

		switch ($this->type) {
			case 'Art':
				if (isset($s['preview.usePreview']) && $s['preview.usePreview'] == 'true') {
					$width = $get('preview.width');
					$height = $get('preview.height');
				} else {
					$width = $get('thumbnail.width');
					$height = $get('thumbnail.height');
				}
				break;

			case 'Cubic':
				$width = (int)$this->width / 3.5;
				$height = (int)$this->height / 3.0;
				break;

			case 'PhotoFlow':
				$width = $get('maxImageWidth');
				$height = 0;
				break;

			case 'StackPhoto':
				$width = $get('image.width');
				$height = $get('image.height');
				break;

			case 'Zen':
				$width = $get('iconWidth');
				$height = $get('iconHeight');
				break;

			default:
				$width = $get('thumbnail.width');
				$height = $get('thumbnail.height');
				break;
		}

		return array('width' => (int)$width, 'height' => (int)$height);
	}

	/**
	 * Key of the gallery type in $flgalleryProducts (defined by the commercial add-on).
	 * It is based on the name of the former Flash movie and must not be changed.
	 */
	function getSignature()
	{
		if (empty($this->signature)) {
			$this->signature = md5("{$this->type}.swf");
		}

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
		if ($this->id) {
			$xmlDir = dirname($this->xmlFilePath);
			if (!file_exists($xmlDir)) {
				wp_mkdir_p($xmlDir);
			}

			if ($fp = fopen($this->xmlFilePath, 'w')) {
				$w = fwrite($fp, $xml);
				fclose($fp);
				return $w;
			}
		}
		return false;
	}

	function clearXmlCache()
	{
		if ($this->id) {
			if (file_exists($this->xmlFilePath)) {
				return unlink($this->xmlFilePath);
			}
		}
		return false;
	}
}
