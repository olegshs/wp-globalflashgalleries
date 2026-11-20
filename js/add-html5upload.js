/**
 * Multi-file uploader ("Add Pictures" page).
 *
 * Selected pictures are queued; "Start Upload" sends them one by one to
 * admin-ajax.php?action=flgalleryUpload and then submits the "Add Pictures" form,
 * which imports the uploaded files into the album or gallery.
 */
jQuery(document).ready(function ($) {
	var container = $('#html5Upload');
	if (!container.length) {
		return;
	}

	var form = $('#flgalleryAddMediaForm');
	var startButton = $('#uploadStart');
	var queueTable = $('#uploadQueue');
	var status = $('#uploadStatus');

	var ajaxURL = container.data('ajax-url');
	var nonce = container.data('nonce');
	var uploadId = container.data('upload-id');
	var maxFileSize = parseInt(container.data('max-file-size'), 10) || 0;
	var allowed = /\.(jpe?g|png|gif)$/i;

	var queue = [];
	var nextId = 0;
	var uploading = false;
	var uploaded = 0;
	var failed = 0;

	$('#flgalleryAddMedia').css('backgroundImage', 'none');
	form.fadeIn(500);
	startButton.prop('disabled', true);

	function formatSize(bytes) {
		return Math.max(1, Math.round(bytes / 1024)) + '&nbsp;KB';
	}

	function updateStartButton() {
		startButton.prop('disabled', uploading || !queue.length);
	}

	function addFiles(files) {
		$.each(files, function (i, file) {
			var id = 'html5Upload-file-' + (++nextId);
			var row = $('<tr class="item"/>').attr('id', id);
			var error = '';

			if (!allowed.test(file.name)) {
				error = 'Not a JPEG, PNG or GIF picture';
			} else if (maxFileSize && file.size > maxFileSize) {
				error = 'File is too big';
			}

			row.append($('<td class="name"/>').text(file.name));
			row.append($('<td class="size"/>').html(formatSize(file.size)));
			row.append($('<td class="progress"/>').text(error));
			row.append($('<td class="cancel"/>').append(
				$('<a href="#" title="Cancel Upload">&times;</a>').on('click', function () {
					if (!uploading) {
						queue = $.grep(queue, function (item) {
							return item.id != id;
						});
						row.fadeOut(300, function () {
							row.remove();
						});
						updateStartButton();
					}
					return false;
				})
			));

			queueTable.find('tbody').append(row);

			if (error) {
				row.addClass('error');
			} else {
				queue.push({ id: id, file: file, row: row });
			}
		});

		queueTable.show();
		updateStartButton();
	}

	function uploadNext() {
		var item = queue.shift();

		if (!item) {
			uploading = false;

			status.text(uploaded + ' file' + (uploaded == 1 ? '' : 's') + ' uploaded.' + (failed ? ' ' + failed + ' failed.' : ''));

			if (uploaded) {
				form.append('<input type="hidden" name="OK" value="OK" />');
				form.get(0).submit();
			} else {
				updateStartButton();
			}
			return;
		}

		var progress = item.row.find('.progress');
		var data = new FormData();
		data.append('action', 'flgalleryUpload');
		data.append('nonce', nonce);
		data.append('upload_id', uploadId);
		data.append('file', item.file, item.file.name);

		progress.text('0%');

		$.ajax({
			url: ajaxURL,
			type: 'POST',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json',
			xhr: function () {
				var xhr = $.ajaxSettings.xhr();
				if (xhr.upload) {
					xhr.upload.addEventListener('progress', function (e) {
						if (e.lengthComputable) {
							progress.text(Math.round(e.loaded * 100 / e.total) + '%');
						}
					});
				}
				return xhr;
			}
		}).done(function (response) {
			if (response && response.success) {
				uploaded++;
				progress.text('100%');
				item.row.addClass('completed');
			} else {
				failed++;
				progress.text(response && response.data && response.data.message ? response.data.message : 'Upload failed');
				item.row.addClass('error');
			}
		}).fail(function (xhr) {
			var response = xhr.responseJSON;
			failed++;
			progress.text(response && response.data && response.data.message ? response.data.message : 'Upload failed (HTTP ' + xhr.status + ')');
			item.row.addClass('error');
		}).always(function () {
			item.row.find('.cancel a').remove();
			uploadNext();
		});
	}

	$('#html5Upload_files').on('change', function () {
		addFiles(this.files);
		this.value = '';
	});

	container
		.on('dragover', function (e) {
			e.preventDefault();
			container.addClass('dragover');
		})
		.on('dragleave drop', function () {
			container.removeClass('dragover');
		})
		.on('drop', function (e) {
			e.preventDefault();
			var dt = e.originalEvent.dataTransfer;
			if (dt && dt.files && dt.files.length && !uploading) {
				addFiles(dt.files);
			}
		});

	startButton.on('click', function () {
		if (!queue.length || uploading) {
			return false;
		}
		uploading = true;
		uploaded = failed = 0;
		updateStartButton();
		uploadNext();
		return false;
	});
});
