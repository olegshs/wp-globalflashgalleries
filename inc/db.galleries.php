<?php
if (!defined('FLGALLERY_VERSION')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); }

$query = "
	CREATE TABLE `{$table_name}` (
		`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		`order` BIGINT NOT NULL DEFAULT '0',
		`author` BIGINT UNSIGNED NOT NULL,
		`type` VARCHAR(20) NOT NULL,
		`name` VARCHAR(255) NOT NULL,
		`width` INT UNSIGNED NOT NULL DEFAULT '0',
		`height` INT UNSIGNED NOT NULL DEFAULT '0',
		`created` DATETIME NOT NULL,
		`modified` DATETIME NOT NULL,
		PRIMARY KEY (`id`),
		KEY `order` (`order`),
		KEY `author` (`author`),
		KEY `created` (`created`),
		KEY `modified` (`modified`)
	){$charset_collate}
";
