<?php

preg_match('|^(.*?/)(wp-content)/|i', str_replace('\\', '/', __FILE__), $_m);
require_once $_m[1].'wp-load.php';

?>