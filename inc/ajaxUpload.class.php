<?php

class flgalleryAjaxUpload
{
	function upload()
	{
		error_reporting(0);

		global $current_user;

		// Flash often fails to send cookies with the POST or upload, so we need to pass it in GET or POST instead
		if (is_ssl() && empty($_COOKIE[SECURE_AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie'])) {
			$_COOKIE[SECURE_AUTH_COOKIE] = $_REQUEST['auth_cookie'];
			$current_user = null;
		} elseif (empty($_COOKIE[AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie'])) {
			$_COOKIE[AUTH_COOKIE] = $_REQUEST['auth_cookie'];
			$current_user = null;
		}

		if (!current_user_can('upload_files')) {
			$this->error(__('You do not have permission to upload files.'));
		}

		// Settings
		$path = $_GET['upload_path'];
		if (preg_match('#[/\\\\]\.#', $path)) {
			$this->error('Invalid path');
		}

		$save_path = FLGALLERY_CONTENT_DIR.'/'.$path.'/';
		$upload_name = "Filedata";
		$max_file_size_in_bytes = 2147483647;				// 2GB in bytes
		$extension_whitelist = array("jpg", "gif", "png");	// Allowed file extensions

		$MAX_FILENAME_LENGTH = 260;
		$file_name = "";
		$file_extension = "";
		$uploadErrors = array(
				0 => "There is no error, the file uploaded with success",
				1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
				2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
				3 => "The uploaded file was only partially uploaded",
				4 => "No file was uploaded",
				6 => "Missing a temporary folder"
		);

		// Validate the upload
		if (!isset($_FILES[$upload_name])) {
			$this->error("No upload found in \$_FILES for " . $upload_name);
		} else if (isset($_FILES[$upload_name]["error"]) && $_FILES[$upload_name]["error"] != 0) {
			$this->error($uploadErrors[$_FILES[$upload_name]["error"]]);
		} else if (!isset($_FILES[$upload_name]["tmp_name"]) || !is_uploaded_file($_FILES[$upload_name]["tmp_name"])) {
			$this->error("Upload failed is_uploaded_file test.");
		} else if (!isset($_FILES[$upload_name]['name'])) {
			$this->error("File has no name.");
		}

		// Validate the file size (Warning: the largest files supported by this code is 2GB)
		$file_size = filesize($_FILES[$upload_name]["tmp_name"]);
		if (!$file_size || $file_size > $max_file_size_in_bytes) {
			$this->error("File exceeds the maximum allowed size");
		}
		if ($file_size <= 0) {
			$this->error("File size outside allowed lower bound");
		}

		// Validate file name (for our purposes we'll just remove invalid characters)
		$file_name = basename($_FILES[$upload_name]['name']);
		if (strlen($file_name) == 0 || strlen($file_name) > $MAX_FILENAME_LENGTH) {
			$this->error("Invalid file name");
		}

		// Validate that we won't over-write an existing file
		if (file_exists($save_path.$file_name)) {
			$this->error("File with this name already exists");
		}

		// Validate file extension
		$path_info = pathinfo($_FILES[$upload_name]['name']);
		$file_extension = $path_info["extension"];
		$is_valid_extension = false;
		foreach ($extension_whitelist as $extension) {
			if (strcasecmp($file_extension, $extension) == 0) {
				$is_valid_extension = true;
				break;
			}
		}
		if (!$is_valid_extension) {
			$this->error("Invalid file extension");
		}

		if (!file_exists($save_path)) {
			mkdir($save_path, 0777, true);
		}

		if (move_uploaded_file($_FILES[$upload_name]["tmp_name"], $save_path.$file_name)) {
			chmod($save_path.$file_name, 0666);
		}
		else {
			$this->error("File could not be saved.");
		}
	}

	function error($message)
	{
		@header("HTTP/1.1 500 Internal Server Error");
		echo $message;
		exit;
	}
}
