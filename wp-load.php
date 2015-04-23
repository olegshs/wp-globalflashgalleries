<?php

if (preg_match('|^(.*?/)(wp-content)/|i', str_replace('\\', '/', __FILE__), $_m) && file_exists($_m[1].'wp-load.php')) {
	require_once $_m[1].'wp-load.php';
} else {
	require_once dirname(__FILE__) . '/../../../wp-load.php';
}

?>