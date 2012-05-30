jQuery('.bulk-actions select.destAlbum').hide();

jQuery('.bulk-actions select.action').change(function() {
	if (this.value == 'moveImages') {
		//jQuery('.bulk-actions select.destAlbum').fadeIn(500);
		jQuery('.bulk-actions select.destAlbum').animate({ width:'show', opacity:1 }, 500);
	}
	else {
		//jQuery('.bulk-actions select.destAlbum').fadeOut(500);
		jQuery('.bulk-actions select.destAlbum').animate({ width:'hide', opacity:0 }, 500);
	}
});

jQuery(document).ready(function($) {
	var
		isShift = false,
		isCtrl = false;

	$(document)
		.keyup(function (e) {
			if (e.which == 16) isShift=false;
			if (e.which == 17) isCtrl=false;
		})
		.keydown(function (e) {
			if (e.which == 16) isShift=true;
			if (e.which == 17) isCtrl=true;
		});


	$('#album_pictures thead .check-column input, #album_pictures tfoot .check-column input').click( function() {
		$('#album_pictures .check-column input').attr('checked', this.checked);
	});

	$('#album_pictures .picture .picture-delete a').click(function() {
		var image_id = this.href.match(/image_id=(\d+)/)[1];
		$('#picture-'+image_id).fadeOut(500, function() {
			$(this).remove();
		});
		$.ajax({
			url: flgallery.adminAjax,
			type: 'GET',
			data: {
				action: 'flgalleryAdmin',
				ajax_action: 'deleteImage',
				image_id: image_id
			}
		});
		return false;
	});

	$('.picture-edit a').click(function() {
		if ( !isShift && !isCtrl ) {
			var image_id = this.href.match(/image_id=(\d+)/)[1];

			$(document.body).addClass('wait');
			$('div.flgallery').fadeOut(500);

			$.ajax({
				url: flgallery.adminAjax,
				type: 'GET',
				data: {
					action: 'flgalleryAdmin',
					ajax_action: 'editImage',
					image_id: image_id
				},
				success: function(data) {
					$('div.flgallery')
						.hide()
						.html(data)
						.fadeIn(500);

					$(document.body).removeClass('wait');
				}
			});

			return false;
		}
	});
});
