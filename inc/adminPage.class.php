<?php if (defined('WP_ADMIN')) {

class flgalleryAdminPage extends flgalleryBaseClass
{
	var $className = 'flgalleryAdminPage';
	var $galleriesCount = 0;

	var $href = FLGALLERY_HREF;

	function head($name, $class = '')
	{
		include FLGALLERY_GLOBALS;

		if (!empty($_REQUEST)) {
			$this->debug('REQUEST: ' . esc_html(var_export($_REQUEST, true)));
		}

		echo "\n\n<!-- begin {$plugin->name} -->\n";
		echo
			'<div class="wrap">' .
			(!empty($class) ? "<div class='{$plugin->name}'><div class='{$class}'>" : '') .
			"\n\n<h2>" . __($name, $plugin->name) . "</h2>\n\n";
?>
		<script type="text/javascript">//<![CDATA[
			var flgallery = {
				pluginURL: '<?php echo esc_js($plugin->url); ?>',
				adminAjax: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
				requestPage: '<?php if (isset($_REQUEST['page'])) echo esc_js(sanitize_text_field(wp_unslash($_REQUEST['page']))); ?>'
			};
		//]]>
		</script>
<?php
	}

	function actionButton($title, $action, $a = array(), $confirm = NULL, $attributes = array())
	{
		include FLGALLERY_GLOBALS;

		$primary = '';
		if (is_array($title)) {
			if (!empty($title[2]) || $title[1] === true) {
				$primary = '-primary button';
			}

			if (!empty($title[1]) && is_string($title[1])) {
				$description = $title[1];
			}

			$title = $title[0];
		}
		$title = __($title, $plugin->name);

		$out =
			"\n<form class='actionButton' id='action-{$action}' action='{$admpage->href}' method='post'>\n" .
			"\t<input type='hidden' name='action' value='{$action}' />\n";

		if (!empty($a)) {
			if (is_object($a)) {
				$a = get_object_vars($a);
			}

			if (is_array($a)) {
				foreach ($a as $name => $value) {
					$out .= "\t<input type='hidden' name='" . esc_attr($name) . "' value='" . esc_attr($value) . "' />\n";
				}
			}
		}

		$atts = '';
		if (is_array($attributes) && count($attributes)) {
			foreach ($attributes as $name => $value) {
				$atts .= " {$name}='{$value}'";
			}
		}

		$confirmAttr = '';
		if (!empty($confirm)) {
			// The message holds user data (e.g. a gallery name), so build the JS string with
			// wp_json_encode (JS-string escaping) and esc_attr (HTML-attribute escaping) instead
			// of concatenation, so it can't break out of either context.
			// Literal "\n" from the (single-quoted) message strings become real line breaks.
			$confirmJs = wp_json_encode(str_replace('\n', "\n", $confirm));
			$confirmAttr = " onclick='return confirm(" . esc_attr($confirmJs) . ");'";
		}

		$out .=
			"\t<input type='submit' class='button{$primary}' id='button-{$action}' value='" . esc_attr($title) . "'" .
			(!empty($description) ? " title='" . esc_attr($description) . "'" : '') .
			$atts .
			$confirmAttr .
			" />\n" .
			"</form>\n";

		return $out;
	}

	function about()
	{
		include FLGALLERY_GLOBALS;

		$tpl->t('about/plugin-info',
			array(
				'version' => $plugin->version,
				'pluginDir' => $plugin->dir,
				'pluginURL' => $plugin->url,
				'contentDir' => $plugin->contentDir,
				'contentURL' => $plugin->contentURL,
				'imgDir' => $plugin->imgDir,
				'imgURL' => $plugin->imgURL,
				'uninstall' => $this->actionButton('Uninstall Plugin', 'uninstallPlugin', array('_wpnonce' => wp_create_nonce('flgallery-uninstall')), 'All plugin data, galleries, images and other media will be PERMANENTLY REMOVED from server!\n\nAre you sure?'),
				'canUninstall' => current_user_can('delete_plugins')
			)
		);

		include FLGALLERY_INCLUDE.'/serverSettings.php';
?>
		<div class="metabox-holder"><div class="postbox-container" style="width:60%;">
			<div class="postbox" id="server-settings">
			<h3 class="hndle"><span><?php _e('Server Settings', $plugin->name); ?></span></h3>
			<div class="inside">
				<!--<p class="sub"></p>-->
				<table class="table" cellspacing="0" border="0">
					<tbody>
						<tr>
							<th><?php _e('Operating System', $plugin->name); ?><span>:</span></th>
							<td><?php echo $php_os; ?></td>
						</tr>
						<tr class="even">
							<th><?php _e('Server', $plugin->name); ?><span>:</span></th>
							<td><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? ''); ?></td>
						</tr>
						<tr>
							<th><?php _e('PHP Version', $plugin->name); ?><span>:</span></th>
							<td><?php echo $php_version; ?></td>
						</tr>
						<tr class="even">
							<th><?php _e('MySQL Version', $plugin->name); ?><span>:</span></th>
							<td><?php echo $sqlversion; ?></td>
						</tr>
						<tr>
							<th><?php _e('SQL Mode', $plugin->name); ?><span>:</span></th>
							<td><?php echo $sql_mode; ?></td>
						</tr>
						<tr>
							<th><?php _e('PHP Allow URL fopen', $plugin->name); ?><span>:</span></th>
							<td><?php echo $allow_url_fopen; ?></td>
						</tr>
						<tr class="even">
							<th><?php _e('Memory Usage', $plugin->name); ?><span>:</span></th>
							<td><?php echo $memory_usage; ?></td>
						</tr>
						<tr>
							<th><?php _e('PHP Memory Limit', $plugin->name); ?><span>:</span></th>
							<td><?php echo $memory_limit; ?></td>
						</tr>
						<tr class="even">
							<th><?php _e('PHP Max File Uploads', $plugin->name); ?><span>:</span></th>
							<td><?php echo $max_file_uploads; ?></td>
						</tr>
						<tr>
							<th><?php _e('PHP Max Upload Size', $plugin->name); ?><span>:</span></th>
							<td><?php echo $upload_max; ?></td>
						</tr>
						<tr class="even">
							<th><?php _e('PHP Max Post Size', $plugin->name); ?><span>:</span></th>
							<td><?php echo $post_max; ?></td>
						</tr>
						<tr>
							<th><?php _e('PHP Max Script Execute Time', $plugin->name); ?><span>:</span></th>
							<td><?php echo $max_execute; ?>s</td>
						</tr>
						<!-- <tr class="even">
							<th><?php _e('PHP Exif support', $plugin->name); ?><span>:</span></th>
							<td><?php echo $exif; ?></td>
						</tr>
						<tr>
							<th><?php _e('PHP IPTC support', $plugin->name); ?><span>:</span></th>
							<td><?php echo $iptc; ?></td>
						</tr> -->
						<tr class="even">
							<th><?php _e('PHP XML support', $plugin->name); ?><span>:</span></th>
							<td><?php echo $xml; ?></td>
						</tr>
						<tr>
							<th><?php _e('GD Library', $plugin->name); ?><span>:</span></th>
							<td><?php echo $gd_version; ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div></div></div>
		<div class="clear"></div>
<?php
	}

	function manageGalleries()
	{
		include FLGALLERY_GLOBALS;

		$this->head('Galleries', 'manage');

		$this->galleriesList();

		$func->js("flgallery.galleriesCount = {$this->galleriesCount};");
	}

	function newGallery($a = array(), $data = array())
	{
		include FLGALLERY_GLOBALS;
		$galleryInfo =& $plugin->galleryInfo;

		if (empty($a['name'])) {
			$a['name'] = 'New Gallery';
		}

		if (empty($a['type'])) {
			$a['type'] = 'Art';
		}
?>
		<form id="newGalleryForm" action="" method="post">
<?php
		if (!empty($data) && is_array($data)) {
			foreach ($data as $name => $value) {
				echo "<input type='hidden' name='" . esc_attr($name) . "' value='" . esc_attr($value) . "' />\n";
			}
		}
?>
		<table width="100%">
			<tr class="name field" valign="top">
				<td class="label"><label for="galleryName"><?php _e('Name', $plugin->name); ?></label></td>
				<td class="value" width="100%"><input type="text" id="galleryName" name="gallery[name]" value="<?php echo esc_attr($a['name']); ?>" tabindex="10" /></td>
			</tr>
			<tr class="type field" valign="top">
				<td class="label"><label for="galleryType"><?php _e('Type', $plugin->name); ?></label></td>
				<td class="value">
					<div><select id="galleryType" name="gallery[type]" tabindex="20">
<?php
						foreach ($galleryInfo as $type => $gallery) {
							$atts = '';
							if ($type == $a['type']) {
								$atts = " selected='selected'";
							}
							echo "<option value='{$type}'{$atts}>{$gallery['title']}</option>\n";
						}
					?></select></div>
					<div class="navigation" style="visibility:hidden;">
						<input type="button" class="button" value="&laquo; Back" tabindex="50" />
						<input type="button" class="button" value="Next &raquo;" tabindex="40" />
					</div>
					<div class="preview"><a href="#" title="<?php _e('Online Demo', $plugin->name); ?>" target="_blank"><img src="" alt="" /></a></div>
					<div class="description">&nbsp;</div>
				</td>
			</tr>
			<tr class="submit">
				<td></td>
				<td>
					<input type="hidden" name="action" value="createGallery" />
					<input type="submit" class="button-primary" name="OK" value="<?php _e('Create Gallery', $plugin->name); ?>" tabindex="30" />
					<input type="submit" class="button" name="cancel" value="<?php _e('Cancel', $plugin->name); ?>" tabindex="60" />
				</td>
			</tr>
		</table>
		</form>
		<script type="text/javascript">//<![CDATA[
			var galleryInfo = <?php echo wp_json_encode($galleryInfo) ?>;
			jQuery(document).ready(function($) {
				$('#galleryType').change(
					function() {
						$('#newGalleryForm .preview img').attr('src', '<?php echo $plugin->url; ?>/img/galleries/' + unescape(galleryInfo[this.value].preview));
						$('#newGalleryForm .preview a').attr('href', unescape(galleryInfo[this.value].demo));
						$('#newGalleryForm .description').html(galleryInfo[this.value].description);
					}
				);
				$('#galleryType').change();
				$('#galleryName').focus();
			});
		//]]>
		</script>
<?php
	}

	function galleriesList()
	{
		include FLGALLERY_GLOBALS;

		$gallery_id = empty($_REQUEST['gallery_id']) ? NULL : (int)$_REQUEST['gallery_id'];
		$showImgs = empty($_REQUEST['imgs']) ? 0 : (int)$_REQUEST['imgs'];

		$itemsCount = (int)$wpdb->get_var("
			SELECT COUNT(*)
			FROM `{$plugin->dbGalleries}`
			WHERE (`author` = '{$plugin->userID}' OR {$plugin->userLevel} >= 5)
		");
		$itemsPerPage = 10;
		$pagesCount = max(1, (int)ceil($itemsCount / $itemsPerPage));
		$currentPage = !empty($_GET['paged']) ? (int)$_GET['paged'] : 1;
		$currentPage = max(1, min($pagesCount, $currentPage));

		$limit = ($currentPage - 1) * $itemsPerPage;

		$query = "
			SELECT `id`, `author`
			FROM `{$plugin->dbGalleries}`
			WHERE (`author` = '{$plugin->userID}' OR {$plugin->userLevel} >= 5)
			ORDER BY `order` DESC, `created` DESC
			LIMIT {$limit}, {$itemsPerPage}
		";
		$galleries = $wpdb->get_results($query);

		$tpl->t(
			'manage/galleries-panel',
			array(
				'addNewGallery' => $admpage->actionButton(array('+ New Gallery', true), 'addNewGallery'),
				'pagination' => $pagesCount > 1 ? $this->pagination('top', $itemsCount, $pagesCount, $currentPage) : ''
			)
		);

		if (!empty($galleries)) {
			$galleriesHTML = '';
			foreach ($galleries as $gal) {
				$this->galleriesCount++;
				$gallery = new flgalleryGallery($gal->id);
				$gallery->typeTitle = isset($plugin->galleryInfo[$gallery->type]) ? $plugin->galleryInfo[$gallery->type]['title'] : esc_html($gallery->type);
				$galleriesHTML .= $this->galleryPreview($gallery, 'manage/gallery', false);
			}
			$tpl->t('manage/galleries-list', array('galleries' => $galleriesHTML));
		}

		if ($pagesCount > 1) {
			echo $this->pagination('bottom', $itemsCount, $pagesCount, $currentPage);
		}
?>

		<script type="text/javascript" src="<?php echo $plugin->url; ?>/js/manage.js"></script>
<?php
		if ($gallery_id && $showImgs) {
			$func->js("jQuery(document).ready(function($) {
				flgallery.showPictures('gallery-{$gallery_id}');
			});");
		}
	}

	/**
	 * Pagination in the style of WordPress list tables (same markup as WP_List_Table::pagination(),
	 * so it is styled by WordPress and uses its translations).
	 *
	 * @param string $which 'top' (with a page number input) or 'bottom'
	 */
	function pagination($which, $totalItems, $totalPages, $currentPage)
	{
		$url = function ($page) {
			return esc_url($page > 1 ? add_query_arg('paged', $page, $this->href) : $this->href);
		};
		$link = function ($class, $page, $label, $symbol) use ($url) {
			return sprintf(
				'<a class="%s button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
				$class, $url($page), $label, $symbol
			);
		};
		$disabled = function ($symbol) {
			return '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">' . $symbol . '</span>';
		};

		$totalPagesHtml = '<span class="total-pages">' . number_format_i18n($totalPages) . '</span>';

		if ($which == 'bottom') {
			$currentPageHtml = $currentPage;
			$pagingInput =
				'<span class="screen-reader-text">' . __('Current Page') . '</span>' .
				'<span id="table-paging" class="paging-input"><span class="tablenav-paging-text">' .
					sprintf(_x('%1$s of %2$s', 'paging'), $currentPageHtml, $totalPagesHtml) .
				'</span></span>';
		} else {
			$currentPageHtml = sprintf(
				'<label for="current-page-selector" class="screen-reader-text">%s</label>' .
				'<input class="current-page" id="current-page-selector" type="text" name="paged" value="%s" size="%d" aria-describedby="table-paging" />' .
				'<span class="tablenav-paging-text">',
				__('Current Page'),
				$currentPage,
				strlen($totalPages)
			);
			$pagingInput =
				'<span class="paging-input">' .
					sprintf(_x('%1$s of %2$s', 'paging'), $currentPageHtml, $totalPagesHtml) .
				'</span></span>';
		}

		$links = array(
			$currentPage > 1 ? $link('first-page', 1, __('First page'), '&laquo;') : $disabled('&laquo;'),
			$currentPage > 1 ? $link('prev-page', $currentPage - 1, __('Previous page'), '&lsaquo;') : $disabled('&lsaquo;'),
			$pagingInput,
			$currentPage < $totalPages ? $link('next-page', $currentPage + 1, __('Next page'), '&rsaquo;') : $disabled('&rsaquo;'),
			$currentPage < $totalPages ? $link('last-page', $totalPages, __('Last page'), '&raquo;') : $disabled('&raquo;'),
		);

		$html =
			'<div class="tablenav-pages">' .
				'<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $totalItems), number_format_i18n($totalItems)) . '</span>' .
				'<span class="pagination-links">' . implode("\n", $links) . '</span>' .
			'</div>';

		if ($which == 'top') {
			// Typing a page number and pressing Enter opens that page, as in WordPress list tables
			parse_str((string)parse_url($this->href, PHP_URL_QUERY), $query);
			$hidden = '';
			foreach ($query as $name => $value) {
				if (is_string($value)) {
					$hidden .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
				}
			}
			$html = '<form method="get" action="' . esc_url(admin_url('admin.php')) . '">' . $hidden . $html . '</form>';
		}

		return '<div class="tablenav ' . esc_attr($which) . '">' . $html . '<br class="clear" /></div>';
	}

	function galleryPreview(&$gallery, $template, $echo = true)
	{
		if (defined('WP_ADMIN') && $gallery->id) {
			include FLGALLERY_GLOBALS;
			$out = '';

			$imagesHTML = '';
			$showImages = !empty($_REQUEST['imgs']) && (int)($_REQUEST['gallery_id'] ?? 0) == $gallery->id;
			if ($showImages) {
				$imagesHTML = $this->getGalleryItemsHtml($gallery);
			}
			$imagesCount = $gallery->getItemsCount();

			$created = sprintf(__("%s @ %s", $plugin->name),
				mysql2date($plugin->dateFormat, get_date_from_gmt($gallery->created)),
				mysql2date($plugin->timeFormat, get_date_from_gmt($gallery->created))
			);
			$modified = sprintf(__("%s @ %s", $plugin->name),
				mysql2date($plugin->dateFormat, get_date_from_gmt($gallery->modified)),
				mysql2date($plugin->timeFormat, get_date_from_gmt($gallery->modified))
			);

			$out .= $tpl->parse(
				$template,
				array_merge(
					get_object_vars($gallery),
					array(
						'title' => esc_html($gallery->name),
						'url_title' => urlencode($gallery->name),
						'created' => $created,
						'modified' => $modified,
						'authorName' => esc_html($gallery->authorName),
						'deleteGallery' => $admpage->actionButton(
								array('Delete', 'Delete Gallery'),
								'deleteGallery',
								array('gallery_id' => $gallery->id),
								sprintf(__('Delete Gallery? \n\n%s. "%s" by %s', $plugin->name), $gallery->id, $gallery->name, $gallery->authorName)
							),
						'options' => $admpage->actionButton(
								array('Options', 'Customize Gallery'),
								'galleryOptions',
								array('gallery_id' => $gallery->id)
							),
						'pluginURL' => esc_html($plugin->url),
						'imgURL' => esc_html($plugin->imgURL),
						'href' => esc_html($admpage->href),
						'full' => $showImages ? 'full' : NULL,
						'images' => $imagesHTML,
						'galleryInfo' => isset($plugin->galleryInfo[$gallery->type]) ? $plugin->galleryInfo[$gallery->type] : array('preview' => '', 'title' => ''),
						'popupJS' => $gallery->getPopupJs(),
						'hasItems' => $imagesCount > 0,
						'moreThanOneItem' => $imagesCount > 1
					)
				)
			);

			if ($echo) {
				echo $out;
			}
			return $out;
		}
		return false;
	}

	function getGalleryItemsHtml($gallery)
	{
		include FLGALLERY_GLOBALS;

		$imagesHTML = '';

		$images = $gallery ? $gallery->getItems() : array();
		if (!empty($images)) {
			$nonce = wp_create_nonce('deleteImage');

			foreach ($images as $img) {
				$image = new flgalleryImage($img);
				$thumbnail = $image->resized(array('height' => 120));

				$img->galleryID = $gallery->id;
				$img->url = esc_html($plugin->imgURL . '/' . $img->path);
				$img->previewURL = $thumbnail ? esc_html($func->url($thumbnail)) : esc_html($img->url);
				$img->href = esc_html($admpage->href);
				if (empty($img->title)) {
					$img->title = esc_html($func->filenameToTitle($img->name));
				}

				$img->title = esc_html($img->title);
				$img->description = esc_html($img->description);

				$img->nonce = $nonce;

				$imagesHTML .= $tpl->parse('manage/image-preview', $img);
			}
		}

		return $imagesHTML;
	}

	function galleryOptions($gallery, $options = array())
	{
		include FLGALLERY_GLOBALS;

		if ($gallery->getSettings()) {
			$types = '';
			foreach ($plugin->galleryInfo as $key => $value) {
				$selected = $key == $gallery->type ? " selected='selected'" : '';
				$types .= "<option value='{$key}'{$selected}>{$value['title']}</option>\n";
			}

			$preview = $gallery->getHtml();


			$settingsTplDir = $gallery->isLegacy() ? 'settings.legacy' : 'settings';

			$a = array_merge(
				array(
					'preview' => $preview,
					'gallery_id' => $gallery->id,
					'types' => $types,
					'name' => esc_html($gallery->name),
					'width' => (int)$gallery->width,
					'width2' => (int)$gallery->width + 32,
					'height' => (int)$gallery->height,
					'settingsPanel' => $plugin->tpl->parse(
							"{$settingsTplDir}/{$gallery->type}",
							(array)$gallery->settingsForm
						),
					'pluginURL' => esc_html($plugin->url)
				),
				$options
			);
			$tpl->t('options/gallery', $a);
		} else {
			return false;
		}
	}

	function editImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image = new flgalleryImage($image_id);

		if ($image->load()) {
			$image->title = esc_html($image->title);
			$image->description = esc_html($image->description);
			$image->link = esc_html($image->link);
			$image->imgURL = esc_html($plugin->imgURL);
			$image->href = esc_html($admpage->href);
			$image->target_blank = empty($image->target) || $image->target == '_blank';
			$image->target_self = $image->target == '_self';

			if (strpos($image->path, '/') === 0) {
				$image->src = esc_html(FLGALLERY_SITE_URL . $image->path);
			} else {
				$image->src = esc_html($plugin->imgURL . '/' . $image->path);
			}

			if (empty($image->title)) {
				$image->title = esc_html($func->filenameToTitle($image->name));
			}

			$tpl->t('manage/image-edit', $image);

			return $image->id;
		}
		return false;
	}

	function tabmenu($items, $current = '', $query = '')
	{
		include FLGALLERY_GLOBALS;

		echo "<ul class='tab-menu'>\n";
		foreach ($items as $name => $title) {
			$class = $name == $current ? 'selected' : '';
			echo "\t<li class='{$class}'><a href='" . esc_attr($admpage->href) . "{$query}&amp;tab={$name}'>{$title}</a></li>\n";
		}
		echo "</ul>\n";
	}

	function foot()
	{
		include FLGALLERY_GLOBALS;

		$plugin->stats->stop();
		$this->debug("{$plugin->stats->queries} queries, {$plugin->stats->time} seconds");

		echo "<div id='flgallery-info'>\n";
		if ($plugin->printErrors) {
			$plugin->admin->printErrors();
		}
		if ($plugin->printWarnings) {
			$plugin->admin->printWarnings();
		}
		if ($plugin->printDebug) {
			$plugin->admin->printDebug();
		}
		echo "\n</div>\n";

		echo "\n\n</div></div></div><!-- end {$plugin->name} -->\n\n";
	}
}

}
