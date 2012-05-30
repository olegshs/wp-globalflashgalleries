<?php

class flgalleryImage extends flgalleryBaseClass
{
	var
		$id,
		$album_id,
		$gallery_id,
		$order,
		$type,
		$path,
		$name,
		$title,
		$description,
		$link,
		$target,
		$width,
		$height,
		$size;

	function init($a)
	{
		if ( is_object($a) )
			$a = get_object_vars($a);
		elseif ( !is_array($a) )
			$a = array('id' => $a);

		$this->set($a);
	}

	function check()
	{
		include FLGALLERY_GLOBALS;

		$fullPath = $plugin->imgDir .'/'. $this->path;
		if ( file_exists($fullPath) )
		{
			$save = false;
			if ( empty($this->type) || empty($this->width) || empty($this->height) )
			{
				if ( $imagesize = getimagesize($fullPath) )
				{
					list($this->width, $this->height) = $imagesize;
					$this->type = $imagesize['mime'];

					$save = true;
				}
			}
			if ( empty($this->size) )
			{
				$this->size = filesize($fullPath);
				$save = true;
			}
			if ($save)
				$this->save();

			return true;
		}
		else
		{
			$this->error("File not exists: ".$fullPath);
			return false;
		}
	}

	function load()
	{
		if ( $this->id = (int)$this->id )
		{
			include FLGALLERY_GLOBALS;

			$image = $wpdb->get_row("
				SELECT * FROM `{$plugin->dbImages}`
				WHERE `id` = '{$this->id}'
			");
			if ($image !== false)
			{
				$this->set($image);
			}
			else
			{
				$this->error( sprintf(__('Unable to load Image #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
		}
		else
			return false;

		return $this->id;
	}

	function save()
	{
		if ( $this->id = (int)$this->id )
		{
			include FLGALLERY_GLOBALS;

			$a = array();

			if ( isset($this->album_id) )
				$a['album_id'] = (int)$this->album_id;

			if ( isset($this->gallery_id) )
				$a['gallery_id'] = (int)$this->gallery_id;

			if ( isset($this->order) )
				$a['order'] = (int)$this->order;

			if ( isset($this->type) )
				$a['type'] = $this->type;

			if ( isset($this->path) )
				$a['path'] = $this->path;

			if ( isset($this->name) )
				$a['name'] = $this->name;

			if ( isset($this->title) )
				$a['title'] = $this->title;

			if ( isset($this->description) )
				$a['description'] = $this->description;

			if ( isset($this->link) )
				$a['link'] = $this->link;

			if ( isset($this->target) )
				$a['target'] = $this->target;

			if ( isset($this->width) )
				$a['width'] = (int)$this->width;

			if ( isset($this->height) )
				$a['height'] = (int)$this->height;

			if ( isset($this->size) )
				$a['size'] = (int)$this->size;

			$update = $wpdb->update( $plugin->dbImg, $a, array('id' => $this->id) );
			if ($update === false)
			{
				$this->error( sprintf(__('Unable to save Image #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
		}
		else
			return false;

		return $this->id;
	}

	function addToBlacklist($path)
	{
		include FLGALLERY_GLOBALS;

		$blacklist = explode("\n", @file_get_contents($plugin->imgBlacklistPath));

		$key = array_search($path, $blacklist);
		if ($key === false)
		{
			$blacklist[] = $path;
			return file_put_contents( $plugin->imgBlacklistPath, trim(implode("\n", $blacklist)) );
		}
	}

	function removeFromBlacklist($path)
	{
		include FLGALLERY_GLOBALS;

		$blacklist = explode("\n", @file_get_contents($plugin->imgBlacklistPath));

		$key = array_search($path, $blacklist);
		if ($key !== false)
		{
			unset( $blacklist[$key] );
			return file_put_contents( $plugin->imgBlacklistPath, trim(implode("\n", $blacklist)) );
		}
	}

	function inBlacklist($path)
	{
		include FLGALLERY_GLOBALS;

		$blacklist = explode("\n", @file_get_contents($plugin->imgBlacklistPath));

		return array_search($path, $blacklist);
	}

	function scale($mode, $size, $area)
	{
		$k = $size['width'] / $size['height'];

		switch ($mode)
		{
			default:
			case 'fit':
				$w = $area['width'];
				$h = $area['width'] / $k;

				if ($h > $area['height']) {
					$h = $area['height'];
					$w = $h * $k;
				}

				if ($w > $size['width'] || $h > $size['height']) {
					$w = $size['width'];
					$h = $size['height'];
				}

				$x = ($area['width'] - $w) / 2;
				$y = ($size['height'] - $h) / 2;

				return array(
					'left' => (int)$x,
					'top' => (int)$y,
					'width' => (int)$w,
					'height' => (int)$h
				);

			case 'fill':
				$w = $area['width'];
				$h = $area['width'] / $k;

				if ($h < $area['height']) {
					$h = $area['height'];
					$w = $h * $k;
				}

				if ($w > $size['width'] || $h > $size['height']) {
					$w = $size['width'];
					$h = $size['height'];
				}

				$x = ($area['width'] - $w) / 2;
				$y = ($area['height'] - $h) / 2;

				return array(
					'left' => (int)$x,
					'top' => (int)$y,
					'width' => (int)$w,
					'height' => (int)$h
				);
		}
	}

	function resized($size, $url = false, $ignoreDeadline = false)
	{
		include FLGALLERY_GLOBALS;

		$newPath = $this->_resized($size);
		if ($newPath == false)
			$newPath = $plugin->imgDir.'/'.$this->path;

		return $url ? $func->url($newPath) : $newPath;
	}

	function _resized($size, $ignoreDeadline = false)
	{
		if (empty($size['width']) && empty($size['height']))
			return false;

		include FLGALLERY_GLOBALS;

		if ( $this->inBlacklist($this->path) !== false )
			return false;

		if ( !$this->check() )
			return false;

		$newWidth = round( empty($size['width']) ? $this->width * ($size['height'] / $this->height) : $size['width'] );
		$newHeight = round( empty($size['height']) ? $this->height * ($size['width'] / $this->width) : $size['height'] );

		preg_match('/(.*)(\..*)$/', $this->path, $fname);
		$newPath = $plugin->tmpDir."/img-{$fname[1]}.{$newWidth}x{$newHeight}{$fname[2]}";

		if ( !file_exists($newPath) )
		{
			if ($plugin->stats->deadline())
				return false;

			switch ($this->type)
			{
				case 'image/gif':
					$imagecreate = 'imagecreatefromgif';
					$imageout = 'imagegif';
					$quality = 100;
					break;
				case 'image/jpeg':
					$imagecreate = 'imagecreatefromjpeg';
					$imageout = 'imagejpeg';
					$quality = empty($size['quality']) ? flgallery_defaultImageQuality : $size['quality'];
					break;
				case 'image/png':
					$imagecreate = 'imagecreatefrompng';
					$imageout = 'imagepng';
					$quality = 7;
					break;
				default:
					return false;
			}

			if ( function_exists($imagecreate) && function_exists($imageout) )
			{
				// Check available memory
				$memoryNeed = ($this->width * $this->height * 5) + ($newWidth * $newHeight * 5) + 1048576;
				if ( $func->getFreeMemory() < $memoryNeed )
				{
					$currentLimit = $func->mToBytes( @ini_get('memory_limit') );
					$newLimit = $memoryNeed + memory_get_usage() + 1048576;
					if ( $newLimit > $currentLimit )
						@ini_set('memory_limit', $newLimit);
				}
				if ( $func->getFreeMemory() < $memoryNeed )
					return false;

				$this->addToBlacklist($this->path);

				$srcImage = $imagecreate($plugin->imgDir.'/'.$this->path);
				$dstImage = imagecreatetruecolor($newWidth, $newHeight);

				if ($this->type == 'image/png') {
					// Transparent background
					imagesavealpha($dstImage, true);
					imagefill($dstImage, 0, 0, imagecolorallocatealpha($dstImage, 255, 255, 255, 127));
				}
				else {
					// White background
					imagefill($dstImage, 0, 0, imagecolorallocate($dstImage, 0xFF, 0xFF, 0xFF) );
				}

				// Copy
				imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);

				imagedestroy($srcImage);

				$res = $imageout($dstImage, $newPath, $quality);
				chmod($newPath, 0666);

				imagedestroy($dstImage);

				if (!$res)
					return false;

				$this->removeFromBlacklist($this->path);
			}
			else
				return false;
		}

		return $newPath;
	}
}

?>