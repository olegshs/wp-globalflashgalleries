(function ($) {
	document.getElementById('gallery-options').reset();

	function setBackground() {
		var style = {
			backgroundRepeat: 'no-repeat',
			backgroundPosition: 'center'
		};
		style.backgroundColor = '#' + $('#background\\.color').val();
		style.backgroundImage = 'url("' + $('#background\\.image').val() + '")';

		if ($('#background\\.transparent').prop('checked')) {
			style.background = 'none';
			$('#flashSettings .background').css({ opacity: 0.4 });
			$('#flashSettings .background input').prop('disabled', true);
		}
		else {
			$('#flashSettings .background').css({ opacity: 1 });
			$('#flashSettings .background input').prop('disabled', false);
		}
		$('#flash-preview .flgallery-embed').css(style);
	}

	// Background is previewed immediately; other settings are applied by the "Update" button
	function applyChanges(e) {
		var m = e.id.match(/(.+)\.(.+)/);
		if (m && m[1] == 'background') {
			setBackground();
		}
	}

	jQuery(document).ready(function ($) {
		$('#flashSettings .objectProperties').each(function () {
			$(this).parent().hide();
		});

		$('#flashSettings input, #flashSettings select, #flashSettings textarea').change(
			function () {
				applyChanges(this);
			}
		);

		$('#flashSettings input').keypress(
			function (e) {
				if (e.which == 13) {
					applyChanges(this);
					return false;
				}
			}
		);

		$('#flashSettings input.int').keypress(
			function (e) {
				if (e.which != 0 && e.which != 8 && String.fromCharCode(e.which).match(/\D/)) {
					return false;
				}
			}
		);

		$('#flashSettings .objectName label').on('click', function () {
			if ($(this).hasClass('opened')) {
				$(this).removeClass('opened');
				$('#' + this.htmlFor).parent().slideUp();
			} else {
				$('#flashSettings .objectName .opened').not(this).trigger('click');
				$(this).addClass('opened');
				$('#' + this.htmlFor).parent().slideDown();
			}
			return false;
		});

		setBackground();


		$('#flash-preview').draggable({ handle: '.hndle', cursor: 'move' /*, containment: '.wrap'*/ });

		function flashPreviewResizable() {
			var resizeDeltaX = 0, resizeDeltaY = 0;

			$('#flash-preview').resizable({
				autoHide: true,
				alsoResize: '#flash-preview .flgallery-embed',
				handles: 'e, se, s, sw, w',
				start: function (event, ui) {
					resizeDeltaX = ui.size.width - $('#gallery\\.width').val();
					resizeDeltaY = ui.size.height - $('#gallery\\.height').val();

					$('#gallery-options .gallery, #gallery-options .settings, #gallery-options .submit').css('z-index', 0);
					$(this).resizable('option', 'minWidth', 300 + resizeDeltaX);
					$(this).resizable('option', 'minHeight', 200 + resizeDeltaY);

					$('#flash-preview .inside3').hide();
					$('#flash-preview .inside2').css('background-image', 'url(' + flgallery.pluginURL + '/img/transparent.gif)');
				},
				resize: function (event, ui) {
					var w = ui.size.width - resizeDeltaX, h = ui.size.height - resizeDeltaY;
					$('#gallery\\.width').val(w);
					$('#gallery\\.height').val(h);
				},
				stop: function () {
					$('#flash-preview .inside3').show();
					$('#flash-preview .inside2').css('background-image', 'url(' + flgallery.pluginURL + '/img/transparent-50.gif)');
					$('#gallery-options .gallery, #gallery-options .settings, #gallery-options .submit').css('z-index', 20);
				}
			});
		}

		flashPreviewResizable();

		$('#flash-preview .postbox-header .handlediv').on('click', function () {
			var preview = $('#flash-preview');
			var button = $(this);
			if (!preview.hasClass('closed')) {
				button.attr('aria-expanded', 'false');
				preview.resizable('destroy');
				preview.height('auto');
				$('#flash-preview .inside').slideUp(250, function () {
					preview.addClass('closed');
				});
			} else {
				preview.removeClass('closed');
				button.attr('aria-expanded', 'true');
				$('#flash-preview .inside').hide().slideDown(500);
				flashPreviewResizable();
			}
		});
	});
})(jQuery);
