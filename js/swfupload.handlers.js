function fileDialogComplete(numFilesSelected, numFilesQueued)
{
	try {
		if (numFilesSelected > 0) {
			jQuery('#uploadQueue').show();
			jQuery('#uploadStart').click(function() {
				this.disabled = true;
				swfu.startUpload();
				return false;
			});
		}
	} catch (ex) {
        this.debug(ex);
	}
}

function fileQueued(file)
{
	try {
		filesize = Math.round(file.size / 1024) + "&nbsp;KB";
		jQuery('#uploadQueue tbody').append(
			"<tr id='" + file.id + "' class='item' style='display:none;'>" +
				"<td class='name'>" + file.name + "</td>" +
				"<td class='size'>" + filesize + "</td>" +
				"<td class='cancel'><a href='javascript:removeFile(\"" + file.id + "\");' title='Cancel Upload'>&times;</a>&nbsp;</td>" +
			"</tr>"
		);
		jQuery('#'+file.id).fadeIn(500);

	} catch (ex) {
		this.debug(ex);
	}

}

function removeFile(fileID)
{
	swfu.cancelUpload(fileID);
	jQuery("#"+fileID).fadeOut( 300, function() { jQuery("#"+fileID).remove(); } );
}

function fileQueueError(file, errorCode, message)
{
	try {
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
			alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
			return;
		}

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			this.debug("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			this.debug("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			this.debug("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		default:
			if (file !== null) {
			}
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function uploadStart(file)
{
	try {
		jQuery(document.body).addClass('wait');
	} catch (ex) {
		this.debug(ex);
	}

	return true;
}

function uploadProgress(file, bytesLoaded, bytesTotal)
{
	try {
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);

		jQuery('#'+file.id+' .cancel a').css({ visibility: 'hidden' });

		if (percent == 100) {
			jQuery('#'+file.id).addClass('completed');
			jQuery('#'+file.id+' *').animate( { opacity: 0.4 }, 500 );
		}
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadSuccess(file, serverData)
{
	try {

	} catch (ex) {
		this.debug(ex);
	}
}

function uploadError(file, errorCode, message)
{
	try {
		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			this.debug("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			if (this.getStats().files_queued === 0) {
				document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			}
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			break;
		default:
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function uploadComplete(file)
{
	if (this.getStats().files_queued === 0) {
		document.getElementById(this.customSettings.cancelButtonId).disabled = true;
	}
}

function queueComplete(numFilesUploaded)
{
	var status = document.getElementById("uploadStatus");
	status.innerHTML = numFilesUploaded + " file" + (numFilesUploaded === 1 ? "" : "s") + " uploaded.";

	jQuery('#flgalleryAddMediaForm').append("<input type='hidden' name='OK' value='OK' />");
	document.getElementById('flgalleryAddMediaForm').submit();
}
