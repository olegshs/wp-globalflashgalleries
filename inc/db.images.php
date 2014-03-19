<?php
if (!defined('FLGALLERY_VERSION')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); }

$query = "
	CREATE TABLE `{$table_name}` (
		`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		`album_id` BIGINT UNSIGNED NOT NULL,
		`gallery_id` BIGINT UNSIGNED NOT NULL,
		`order` BIGINT NOT NULL DEFAULT '0',
		`type` VARCHAR(50) NOT NULL,
		`path` VARCHAR(255) NOT NULL,
		`name` VARCHAR(255) NOT NULL,
		`title` VARCHAR(255) NOT NULL,
		`description` TEXT NOT NULL,
		`link` VARCHAR(255) NOT NULL,
		`target` VARCHAR(50) NOT NULL,
		`width` INT UNSIGNED NOT NULL DEFAULT '0',
		`height` INT UNSIGNED NOT NULL DEFAULT '0',
		`size` INT UNSIGNED NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`),
		KEY `album_id` (`album_id`),
		KEY `gallery_id` (`gallery_id`),
		KEY `order` (`order`),
		KEY `path` (`path`),
		KEY `name` (`name`),
		KEY `title` (`title`),
		KEY `size` (`size`)
	){$charset_collate}
";

?>