<?php
if (!defined('FLGALLERY_VERSION')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); }

$query = "
	CREATE TABLE `{$table_name}` (
		`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		`gallery_id` BIGINT UNSIGNED NOT NULL,
		`gallery_type` VARCHAR(20) NOT NULL,
		`name` VARCHAR(255) NOT NULL,
		`value` VARCHAR(255) NOT NULL,
		PRIMARY KEY (`id`),
		KEY `gallery_id` (`gallery_id`, `gallery_type`)
	){$charset_collate}
";
