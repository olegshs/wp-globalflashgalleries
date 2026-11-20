<?php

/**
 * AJAX endpoint of the multi-file uploader ("Add Pictures" > "Multi-file Uploader").
 *
 * Each picture is uploaded separately into wp-content/flgallery/uploads/<upload_id>/;
 * when the form is submitted, flgalleryMedia::addFiles() imports that folder and deletes it.
 */
class flgalleryAjaxUpload
{
	var $extensionWhitelist = array('jpg', 'jpeg', 'gif', 'png');

	function upload()
	{
		if (!current_user_can('upload_files')) {
			$this->error(__('You do not have permission to upload files.'), 403);
		}

		if (!check_ajax_referer('flgallery-upload', 'nonce', false)) {
			$this->error(__('The link you followed has expired.'), 403);
		}

		$upload_id = isset($_REQUEST['upload_id']) ? preg_replace('/\D/', '', (string)$_REQUEST['upload_id']) : '';
		if ($upload_id === '') {
			$this->error('Invalid upload ID');
		}

		$save_path = FLGALLERY_CONTENT_DIR . '/' . FLGALLERY_UPLOADS . '/' . $upload_id;
		$upload_name = 'file';

		$uploadErrors = array(
			UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
			UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
			UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
			UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
		);

		// Validate the upload
		if (!isset($_FILES[$upload_name]) || is_array($_FILES[$upload_name]['name'])) {
			$this->error('No file was uploaded');
		}

		$file = $_FILES[$upload_name];

		if (!empty($file['error'])) {
			$this->error(isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'Upload error');
		}

		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			$this->error('Upload failed is_uploaded_file test.');
		}

		if (!filesize($file['tmp_name'])) {
			$this->error('File is empty');
		}

		// Validate file name and extension
		$file_name = sanitize_file_name(basename((string)$file['name']));
		if (strlen($file_name) == 0 || strlen($file_name) > 255) {
			$this->error('Invalid file name');
		}

		$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		if (!in_array($file_extension, $this->extensionWhitelist, true)) {
			$this->error('Invalid file extension');
		}

		// Validate that it is really a picture
		$imageSize = @getimagesize($file['tmp_name']);
		if (empty($imageSize) || !in_array($imageSize['mime'], array('image/jpeg', 'image/gif', 'image/png'), true)) {
			$this->error('The file is not a JPEG, PNG or GIF picture');
		}

		if (!wp_mkdir_p($save_path)) {
			$this->error('Unable to create upload directory');
		}

		// Don't overwrite files uploaded earlier
		$file_name = wp_unique_filename($save_path, $file_name);

		if (move_uploaded_file($file['tmp_name'], $save_path . '/' . $file_name)) {
			@chmod($save_path . '/' . $file_name, 0666);
		} else {
			$this->error('File could not be saved.', 500);
		}

		wp_send_json_success(array('name' => $file_name));
	}

	function error($message, $status = 400)
	{
		wp_send_json_error(array('message' => $message), $status);
	}
}
