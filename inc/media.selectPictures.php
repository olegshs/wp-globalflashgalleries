<?php if (!defined('WP_ADMIN')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); } ?>

<script type="text/javascript">//<![CDATA[
	jQuery('#selectPictures').css({ background:'none' });
	jQuery('#select-images-form').fadeIn(500);
//]]></script>

<?php

$albums = $wpdb->get_results("
	SELECT *
	FROM `{$plugin->dbAlbums}`
	ORDER BY `title` ASC
");

?>
<form id="select-images-form" action="" method="post" style="display:none;">
<input type="hidden" name="action" value="addImages" />
<input type="hidden" name="gallery_id" value="<?php echo $a['gallery_id']; ?>" />
<input type="hidden" name="order" value="<?php echo $a['order']; ?>">

<div style="margin:1em 0 2em 0;"><a class="button" href="?page=flgallery/media&amp;action=addNewAlbum">+ New Album</a></div>

<ul id="select-albums">
<?php
	foreach ($albums as $album)
	{
		$imagesCount = (int)$wpdb->get_var("
			SELECT COUNT(*)
			FROM `{$plugin->dbImages}`
			WHERE
				`album_id` = '{$album->id}' AND
				`gallery_id` = 0
			ORDER BY `order` ASC
		");

		if ($imagesCount)
		{
?>
	<li class="select-album" id="select-album-<?php echo $album->id; ?>" style="clear:left; margin:15px 0 20px;">
		<input class="select-album" type="checkbox" name="albums[]" value="<?php echo $album->id; ?>" style="vertical-align:baseline;" />
		<span>
			<a class="select-album" href="#select-album-<?php echo $album->id; ?>" style="font-size:18px; text-decoration:none; border-bottom:1px dotted; color:#21759b;"><?php echo $album->title; ?></a>
			<span class="album-count" style="font-size:14px; color:#555;">(<?php echo $imagesCount; ?>)</span>
			<small style="margin:0 0.5em;"><a href="?page=flgallery/media&amp;action=addMediaPage&amp;album_id=<?php echo $album->id; ?>" style="color:#777;">Add Pictures</a></small>
		</span>

		<ul id="select-album-<?php echo $album->id; ?>-images" style="margin:5px 5px 10px; display:none;"></ul>
		<div style="clear:left;"></div>
	</li>
<?php
		}
	}
?>
</ul>
<div id="selected-images-count" style="position:absolute; bottom:1.5em;"></div>
</form>

<script type="text/javascript">//<![CDATA[
jQuery(document).ready(function($) {
	function loadImages(element, complete) {
		var images = $('#' + $(element).parents('li.select-album').attr('id') + '-images');

		function compl() {
			if ( typeof(complete) == 'function' )
				complete(images);
		}

		if ( !images.html() ) {
			$(document.body).addClass('wait');

			var album_id = images.attr('id').match(/(\d+)-images$/)[1];
			$.ajax({
				url: flgallery.adminAjax,
				data: {
					action: 'flgalleryAdmin',
					ajax_action: 'getAlbumItemsHtml',
					album_id: album_id
				},
				success: function(data) {
					images.html(data);

					images.find('.select-image input[type=checkbox]').click(function() {
						updateImagesCount();
					});

					if ($.browser.msie && $.browser.version < 8) {
						images.find('.select-image-preview img').click(function() {
							$(this).parents('label').click();
							updateImagesCount();
						});
					}

					compl();
				},
				complete: function() {
					$(document.body).removeClass('wait');
				}
			});
		}
		else {
			compl();
		}

		return images;
	}

	function updateImagesCount() {
		selectedImagesCount = 0;
		$('#select-albums input.select-image[type=checkbox]').each(function() {
			if (this.checked)
				selectedImagesCount++;
		});
		if (selectedImagesCount)
			$('#selected-images-count').html('You have selected '+ selectedImagesCount +' picture'+ (selectedImagesCount > 1 ? 's.' : '.'));
		else
			$('#selected-images-count').html('You have not selected pictures.');
	}

	$('#select-albums input.select-album[type=checkbox]').click(function() {
		var checked = this.checked;

		loadImages(this, function(images) {
			images.find('input[type=checkbox]').each(function() {
				this.checked = checked;
			});
			updateImagesCount();
		});
	});

	$('#select-albums input.select-image[type=checkbox]').click(function() {
		updateImagesCount();
	});

	$('#select-albums a.select-album').toggle(
		function() {
			var images = loadImages(this);
			images.css({ display:'block' });

			return false;
		},
		function() {
			var images = loadImages(this);
			images.css({ display:'none' });

			return false;
		}
	);

});
//]]></script>
