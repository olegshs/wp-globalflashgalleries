jQuery(document).ready(function ($) {
	$('#button-addNewAlbum').click(function () {
		$(document.body).addClass('wait');
		$('div.flgallery').fadeOut(500);

		$.ajax({
			url: flgallery.adminAjax,
			type: 'GET',
			data: {
				action: 'flgalleryAdmin',
				ajax_action: 'addNewAlbum'
			},
			success: function (data) {
				$('div.flgallery')
					.hide()
					.html(data)
					.fadeIn(500);

				$(document.body).removeClass('wait');
				$('#album_title').focus();
			}
		});

		return false;
	});
});
