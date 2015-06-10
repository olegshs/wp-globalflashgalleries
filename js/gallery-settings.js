(function ($) {
	document.getElementById('gallery-options').reset();

	function flashAPI(swf, callback, timeout) {
		if (swf.parentNode.parentNode.style.display != 'none') {
			if (timeout == undefined || --timeout) {
				if (swf.setParameterValue == undefined) {
					setTimeout(function () {
						flashAPI(swf, callback, timeout);
					}, 1000);
				} else {
					//try {
					callback(swf);
					//} catch (e) {
					//	alert(e);
					//}
				}
			}
		}
	}

	function getValue(e) {
		if (e.type == 'checkbox') {
			return e.value = e.checked ? true : false;
		} else if (e.className.match(/\bcolor\b/)) {
			return '0x' + e.value.match(/[0-9A-Fa-f]+/);
		} else {
			return e.value;
		}
	}

	function getValuesByTagName(tagName, e) {
		var result = [];
		if (e) {
			var elements = e.getElementsByTagName(tagName);
			var m;
			for (var i = 0; i < elements.length; i++) {
				if (m = elements[i].id.match(/(.+)\.(.+)/)) {
					result.push(m[2] + ': "' + getValue(elements[i]) + '"');
				}
			}
		}
		return result;
	}

	function setBackground() {
		var style = {
			backgroundRepeat: 'no-repeat',
			backgroundPosition: 'center'
		};
		style.backgroundColor = '#' + $('#background\\.color').val();
		style.backgroundImage = 'url("' + $('#background\\.image').val() + '")';

		if ($('#background\\.transparent').attr('checked')) {
			style.background = 'none';
			$('#flashSettings .background').css({ opacity: 0.4 });
			$('#flashSettings .background input').attr('disabled', 'disabled');
		}
		else {
			$('#flashSettings .background').css({ opacity: 1 });
			$('#flashSettings .background input').removeAttr('disabled');
		}
		$('#flash-preview .flgallery-embed').css(style);
	}

	function applyChanges(e) {
		var m;
		if (m = e.id.match(/(.+)\.(.+)/)) {
			if (m[1] != 'lightbox') {
				if (m[1] == 'background') {
					setBackground();
				} else {
					var obj = document.getElementById(m[1]);
					var elements = [];

					elements = elements.concat(getValuesByTagName('input', obj));
					elements = elements.concat(getValuesByTagName('select', obj));
					elements = elements.concat(getValuesByTagName('textarea', obj));

					var objValues = '';
					for (var i = 0; i < elements.length; i++) {
						objValues += elements[i] + ', ';
					}
					objValues = objValues.substring(0, objValues.length - 2);

					flashAPI(flgallery.swf,
						function ($) {
							document.getElementById('debug').innerHTML = '$.setParameterValue( "' + m[1] + '", { ' + objValues + ' } );';
							eval('$.setParameterValue( "' + m[1] + '", { ' + objValues + ' } );');
						},
						30
					);
				}
			}
		} else {
			flashAPI(flgallery.swf,
				function ($) {
					document.getElementById('debug').innerHTML = '$.setParameterValue( "' + e.id + '", "' + getValue(e) + '" );';
					$.setParameterValue(e.id, getValue(e));
				},
				30
			);
		}
	};

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

		$('#flashSettings .objectName label').toggle(
			function () {
				$('#flashSettings .objectName .opened').click();
				$(this).addClass('opened');
				$('#' + this.htmlFor).parent().slideDown();
			},
			function () {
				$(this).removeClass('opened');
				$('#' + this.htmlFor).parent().slideUp();
			}
		);

		setBackground();


		$('#flash-preview').draggable({ handle: '.hndle', cursor: 'move' /*, containment: '.wrap'*/ });

		function flashPreviewResizable() {
			var resizeDeltaX = 0, resizeDeltaY = 0;

			$('#flash-preview').resizable({
				autoHide: true,
				alsoResize: '#flash-preview .inside2',
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
					$('#flash-preview object').css({ width: w, height: h });
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

		$('#flash-preview .handlediv').toggle(
			function () {
				$('#flash-preview').resizable('destroy');
				$('#flash-preview').height('auto');
				$('#flash-preview .inside').slideUp(250);
			},
			function () {
				$('#flash-preview .inside').slideDown(500);
				flashPreviewResizable();
			}
		);

	});
})(jQuery);
