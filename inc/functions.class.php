<?php

class flgalleryFunctions extends flgalleryBaseClass
{
	var $echo = 'auto';	// auto | always | never
	var $templates = array();

	function init()
	{
		$this->preg_pluginDir = preg_quote(str_replace('\\', '/', FLGALLERY_PLUGIN_DIR), '/');
		$this->preg_siteDir = preg_quote(str_replace('\\', '/', FLGALLERY_SITE_DIR), '/');
	}

	function url($path, $plugin = false)
	{
		if ($plugin)
			$path = preg_replace('/^'.$this->preg_pluginDir.'[\\/\\\\]*(.*)$/', FLGALLERY_PLUGIN_URL.'/$1', str_replace('\\', '/', $path));
		else
			$path = preg_replace('/^'.$this->preg_siteDir.'[\\/\\\\]*(.*)$/', FLGALLERY_SITE_URL.'/$1', str_replace('\\', '/', $path));

		return $path;
	}

	function xmlElement($name, $atts = array(), $inner = NULL, $echo = false, $quot = 'double')
	{
		$out = '<'.$name;
		if ( !empty($atts) )
		{
			switch ($quot)
			{
				case 'single':
					$quot = "'";
					break;
				case 'double':
				default:
					$quot = '"';
			}

			if ( is_object($atts) )
				$atts = get_object_vars($atts);

			foreach ( $atts as $key => $value )
				$out .= " {$key}=".$quot.$value.$quot;
		}
		if ( $inner !== NULL )
			$out .= '>'.$inner."</{$name}>";
		else
			$out .= ' />';

		if ( ($echo || $this->echo == 'always') && $this->echo != 'never' )
			echo $out;

		return $out;
	}

	function input($input, $id, $name, $value = '', $args = NULL)
	{
		include FLGALLERY_GLOBALS;

		if ( empty($args['before']) )
			$args['before'] = '';

		if ( is_object($input) )
			$inputAtt = $input->attributes();
		else
			$inputAtt = (object)$input;


		switch ( $type = (string)$inputAtt->type )
		{
			case 'checkbox':
				$values = explode('|', (string)$inputAtt->value );

				$a = array(
					'type' => 'checkbox',
					'class' => 'checkbox',
					'id' => $id,
					'name' => $name,
					'value' => $values[0],
				);
				if ($value == $values[0])
					$a['checked'] = 'checked';

				return $this->xmlElement('input', $a);

			case 'textarea':
				return
					$this->xmlElement('textarea',
						array(
							'id' => $id,
							'name' => $name,
							'cols' => 40,
							'rows' => 3,
						),
						$value
					);

			case 'font':
			case 'select':
				$options = '';
				if ($type == 'font')
				{
					$fonts = array(
						'Arial' => 'Arial',
						'Comic Sans MS' => 'Comic Sans MS',
						'Courier New' => 'Courier New',
						'Georgia' => 'Georgia',
						'Tahoma' => 'Tahoma',
						'Times New Roman' => 'Times New Roman',
						'Trebuchet MS' => 'Trebuchet MS',
						'Verdana' => 'Verdana'
					);
					foreach ($fonts as $font => $title)
					{
						$a = array( 'value' => $font, 'style' => "font-family:'{$font}';" );
						if ($font == $value)
							$a['selected'] = 'selected';

						$options .= $this->xmlElement('option', $a, $title) . "\n";
					}
				}
				else
				{
					foreach ($input->option as $option)
					{
						$optionAtt = $option->attributes();
						$a = array( 'value' => (string)$optionAtt->value );
						if ( (string)$optionAtt->value == $value )
							$a['selected'] = 'selected';

						if ( get_class($option) == 'SimpleXMLElement' )
							$optionContent = $option;
						else {
							$optionContent = $option->content();
							$optionContent = $optionContent->scalar;
						}

						$options .= $this->xmlElement('option', $a, (string)$optionContent);
					}
				}
				return
					$this->xmlElement('select',
						array(
							'id' => $id,
							'name' => $name
						),
						$options
					);

			case 'slider':
				return $tpl->parse(
					'slider',
					array(
						'id' => $id,
						'id2' => str_replace('.', '\\\\.', $id),
						'name' => $name,
						'value' => $value,
						'min' => $input['min'],
						'max' => $input['max'],
						'step' => isset($input['step']) ? $input['step'] : 1,
						'width' => 100
					)
				);

			case 'color':
				return $tpl->parse(
					'color',
					array(
						'id' => $id,
						'id2' => str_replace('.', '\\\\.', $id),
						'name' => $name,
						'value' => $value
					)
				);

			case 'sound':
			case 'image':
				$args['before'] = 'URL ';

			case 'int':
			case 'text':
			default:
				$a = array(
					'type' => 'text',
					'class' => $type,
					'id' => $id,
					'name' => $name,
					'value' => $value,
				);
				switch ($type)
				{
					case 'int':
						$a['maxlength'] = 10;
						break;
				}

				return $args['before'].$this->xmlElement('input', $a);
		}
	}

	function flash($id, $movie, $width, $height, $params = array(), $altContent = '<a href="http://www.adobe.com/go/getflashplayer" rel="nofollow">Get Adobe Flash player</a>', $echo = true)
	{
		include FLGALLERY_GLOBALS;

		$xmlFile = '';

		$paramsHTML = '';
		$atts = '';
		if ( count($params) )
		{
			foreach ($params as $name => $value)
			{
				if (strcasecmp($name, 'flashVars') === 0) {
					parse_str($value, $flashVars);
					if (isset($flashVars['XMLFile']))
						$xmlFile = $flashVars['XMLFile'];
				}

				$paramsHTML .= "\n\t<param name=\"{$name}\" value=\"{$value}\" />";
				$atts .= "\n\t\t{$name}=\"{$value}\"";
			}
		}

		return
			$tpl->t('flash',
				array(
					'id' => $id,
					'movie' => $movie,
					'width' => $width,
					'height' => $height,
					'params' => $paramsHTML,
					'atts' => $atts,
					'pluginURL' => $plugin->url,
					'altContent' => $altContent,
					'xmlFile' => $xmlFile
				),
				$echo
			);
	}

	function js($script, $echo = true)
	{
		$s = "<script type=\"text/javascript\">\n//<![CDATA[\n{$script}\n//]]>\n</script>\n";

		if ( ($echo || $this->echo == 'always') && $this->echo != 'never' )
			echo $s;
		else
			return $s;
	}

	function redirect($href, $exit = false, $timeout = 0, $printLink = false)
	{
		if ( !headers_sent() )
			header("Location: {$href}");

		$href = str_replace('&amp;', '&', $href);
		//$href = str_replace('&', '&amp;', $href);

		if ($timeout)
			$this->js("setTimeout( function() { location.href = '{$href}'; }, {$timeout} );", true);
		else
			$this->js("location.href = '{$href}';");

		if ( $printLink )
			echo "<div class='redirect'><a href='{$href}'>{$href}</a></div>\n";

		if ( $exit )
			exit();
	}

	function locationReset($queryString = '', $exit = false, $echo = true)
	{
		global $flgalleryErrors, $flgalleryWarnings;

		if ( (!FLGALLERY_ERRORS || empty($flgalleryErrors)) && (!FLGALLERY_WARNINGS || empty($flgalleryWarnings)) )
		{
			include FLGALLERY_GLOBALS;

			$out = $this->js("location.href = '{$admpage->href}{$queryString}';", false);

			if ( $exit || ($echo || $this->echo == 'always') && $this->echo != 'never' )
				echo $out;
		}

		if ( $exit )
			exit();

		return $out;
	}

	function locationReload($exit = false, $echo = true)
	{
		$out = $this->js("location.reload(true);", false);

		if ( $exit || ($echo || $this->echo == 'always') && $this->echo != 'never' )
			echo $out;

		if ( $exit )
			exit();

		return $out;
	}

	function randString($length = 12, $chars = '[0-9][a-z][A-Z]')
	{
		$chars = str_replace('[0-9]', '0123456789', $chars);
		$chars = str_replace('[a-z]', 'abcdefghijklmnopqrstuvwxyz', $chars);
		$chars = str_replace('[A-Z]', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $chars);
		$n_chars = strlen($chars) - 1;

		if ( is_array($length) )
		{
			$length = mt_rand($length[0], $length[1]);
		}

		$string = '';
		for ($i = 0; $i < $length; $i++)
		{
			$string .= $chars[mt_rand(0, $n_chars)];
		}

		return $string;
	}

	function uniqueFile($format = '%s', $length = 12, $chars = '[a-z][0-9]')
	{
		do {
			$path = sprintf( $format, $this->randString($length, $chars) );
		} while ( file_exists($path) );

		return $path;
	}

	function filenameToTitle($name)
	{
		preg_match('/(.*)\..*$/', urldecode(basename($name)), $m);
		return preg_replace('#[_-]+#', ' ', $m[1]);
	}

	function now()
	{
		return date('Y-m-d H:i:s');
	}

	function upload($name, $destDir, $destName = NULL)
	{
		include FLGALLERY_GLOBALS;

		$result = false;

		if ( !empty($_FILES[$name]) )
		{
			$f = &$_FILES[$name];

			$uploadErrors = array(
				UPLOAD_ERR_OK =>		'There is no error, the file uploaded with success.',
				UPLOAD_ERR_INI_SIZE =>	'The uploaded file exceeds the upload_max_filesize directive in php.ini',
				UPLOAD_ERR_FORM_SIZE =>	'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
				UPLOAD_ERR_PARTIAL =>	'The uploaded file was only partially uploaded.',
				UPLOAD_ERR_NO_FILE =>	'No file was uploaded.',
				UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
				UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
				UPLOAD_ERR_EXTENSION =>	'File upload stopped by extension.',
			);

			if ( !is_array($f['name']) )	// One file
			{
				if ( $f['error'] == UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name']) )
				{
					$destName = empty($destName) ? $f['name'] : $destName;
					if ( move_uploaded_file($f['tmp_name'], $destDir.'/'.$destName) )
					{
						@chmod($destDir.'/'.$destName, 0666);
						$result = $destName;
					}
					else
						$result = false;
				}
				else
					$this->error( $f['name'] .' : '. __($uploadErrors[$f['error']], $plugin->name) );
			}
			else	// Multiple files
			{
				foreach ( $f['name'] as $key => $name )
				{
					if ( $f['error'][$key] == UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'][$key]) )
					{
						$destName[$key] = empty($destName[$key]) ? $f['name'][$key] : $destName[$key];
						if ( move_uploaded_file($f['tmp_name'][$key], $destDir.'/'.$destName[$key]) )
						{
							@chmod($destDir.'/'.$destName[$key], 0666);
							$result[$key] = $destName[$key];
						}
						else
							$result[$key] = false;
					}
					else
					{
						if ( !empty($f['name'][$key]) )
							$this->error( $f['name'][$key] .' : '. __($uploadErrors[$f['error'][$key]], $plugin->name) );
					}
				}
			}
		}
		return $result;
	}

	function copyFiles($source, $destDir, $destination, $move = false)
	{
		if ( !is_array($destination) )
			$source = (array)$source;

		if ( !is_array($destination) )
			$destination = (array)$destination;

		if ( count($source) && count($destination) && is_dir($destDir) )
		{
			foreach ($source as $key => $path)
			{
				if ( is_file($path) )
				{
					$destName = &$destination[$key];
					if ( copy($path, $destDir. '/'. $destName) )
					{
						$result[$key] = $destName;
						if ($move)
							unlink($path);
					}
					else
						$result[$key] = false;
				}
			}
			return $result;
		}
	}

	function copyURLs($source, $destDir, $destination)
	{
		if ( !is_array($destination) )
			$source = (array)$source;

		if ( !is_array($destination) )
			$destination = (array)$destination;

		if ( count($source) && count($destination) && is_dir($destDir) )
		{
			foreach ($source as $key => $url)
			{
				if ( get_headers($url) )
				{
					$data = file_get_contents($url);
					$destName = &$destination[$key];
					if ( file_put_contents($destDir. '/'. $destName, $data) )
						$result[$key] = $destName;
					else
						$result[$key] = false;
				}
			}
			return $result;
		}
	}

	function fileExt($path)
	{
		$path_info = pathinfo($path);
		return strtolower($path_info['extension']);
	}

	function fileMime($path)
	{
		$ext = $this->fileExt($path);
		switch ( $ext )
		{
			case 'png':
				return 'image/png';

			case 'gif':
				return 'image/gif';

			case 'jpg':
			case 'jpeg':
				return 'image/jpeg';

			case 'swf':
				return 'application/x-shockwave-flash';

			default:
				return 'application/octet-stream';
		}
	}

	function fileExtByMime($mime)
	{
		switch ( $mime )
		{
			case 'image/png':
				return '.png';

			case 'image/gif':
				return '.gif';

			case 'image/jpeg':
				return '.jpg';

			case 'application/x-shockwave-flash':
				return '.swf';

			default:
				return '';
		}
	}

	function recurse( $dir, $pattern, $function, $maxLevel = false, $level = 0 )
	{
		if ( is_callable($function) )
		{
			$dir = rtrim($dir, '/\\');
			if ( $d = opendir($dir) )
			{
				$level++;
				while ( $fName = readdir($d) )
				{
					if ( $fName != '.' && $fName != '..' )
					{
						$fPath = $dir.'/'.$fName;
						if ( is_dir($fPath) && ($maxLevel === false || $level <= $maxLevel) )
							$this->recurse($fPath, $pattern, $function, $maxLevel, $level);
						else
							if ( preg_match($pattern, $fName, $m) )
								call_user_func($function, $fPath);
					}
				}
				closedir($d);
			}
			else
				return false;
		}
		else
			return false;

		return true;
	}

	function unlinkRecurse($path)
	{
		$path = rtrim(str_replace('\\', '/', $path), '/');
		if ( is_dir($path) && ($dir = opendir($path)) )
		{
			while ( false !== ($filename = readdir($dir)) )
			{
				if ($filename != '.' && $filename != '..')
				{
					$file = $path.'/'.$filename;
					if ( is_dir($file) )
						$this->unlinkRecurse($file);
					else
						unlink($file);
				}
			}
			closedir($dir);
			return rmdir($path);
		}
		else
			return unlink($path);

		return false;
	}

	function mToBytes($size)
	{
		$k = array(
			'' => 1,
			'B' => 1,
			'K' => 1024,
			'M' => 1048576,
			'G' => 1073741824,
			'T' => 1099511627776
		);
		if ( preg_match('/([\d\.]+)\s*([BKMGT]|)\w*/i', $size, $m) )
			return (float)$m[1] * $k[strtoupper($m[2])];
		else
			return $size;
	}

	function bytesToM($size, $units = 'M', $precision = 0)
	{
		$k = array(
			'' => 1,
			'B' => 1,
			'K' => 1024,
			'M' => 1048576,
			'G' => 1073741824,
			'T' => 1099511627776
		);
		$u = trim($units);
		$u = $u[0];
		return round($size / $k[$u], $precision) . $units;
	}

	function bytesToK($size, $units = 'K', $precision = 0)
	{
		return bytesToM($size, $units, $precision);
	}

	function getFreeMemory()
	{
		return $this->mToBytes( ini_get('memory_limit') ) - memory_get_usage();
	}

	function shortFilename( $filename, $maxLength = 20, $dots = '..' )
	{
		if ( strlen($filename) > $maxLength )
		{
			if ( preg_match('#^(.*[\\/]|)(.*)(\..*)$#', $filename, $m) )
				return substr( $m[2], 0, $maxLength - strlen($m[3]) - strlen($dots) ). $dots. $m[3];
		}
		return $filename;
	}

}

?>