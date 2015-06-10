<?php

class flgallerySite extends flgalleryBaseClass
{
	var
		$name,
		$title,
		$url,
		$dir;

	function init()
	{
		$this->url = rtrim(get_option('siteurl'), '/');
	}
}
