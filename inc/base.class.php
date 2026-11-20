<?php

global $flgalleryErrors, $flgalleryErrorN, $flgalleryWarnings, $flgalleryWarningN, $flgalleryDebug, $flgalleryDebugN;

$flgalleryErrors = array();
$flgalleryErrorN = 0;

$flgalleryWarnings = array();
$flgalleryWarningN = 0;

$flgalleryDebug = array();
$flgalleryDebugN = 0;

/**
 * Base class of all plugin classes.
 *
 * The constructor passes its arguments to the subclass's init() method, so subclasses
 * implement init() instead of a constructor. init() is intentionally not declared here:
 * subclasses use different signatures, which PHP 8 would reject as incompatible.
 *
 * Plugin code (and the external commercial add-on) attaches ad-hoc properties to these
 * objects, hence #[AllowDynamicProperties] (inherited by subclasses).
 */
#[\AllowDynamicProperties]
class flgalleryBaseClass
{
	var $className = 'flgalleryBaseClass';

	var
		$errorN = 0,
		$warningN = 0,
		$debugN = 0;

	function preInit()
	{
		$this->className = get_class($this);
	}

	function __construct(...$args)
	{
		$this->preInit();

		if (method_exists($this, 'init')) {
			$this->init(...$args);
		}
	}

	function set($a, $b = false)
	{
		if (is_object($a)) {
			$a = get_object_vars($a);
		}

		if ($b === false && is_array($a) && count($a)) {
			foreach ($a as $key => $value) {
				$this->$key = $value;
			}
		} else {
			if (is_string($a) && !empty($a)) {
				$this->$a = $b;
			} else {
				if (is_array($a) && count($a)) {
					foreach ($a as $key) {
						$this->$key = $b;
					}
				}
			}
		}
	}

	function error($msg)
	{
		if (!empty($msg)) {
			global $flgalleryErrors, $flgalleryErrorN;

			$this->errorN = ++$flgalleryErrorN;
			$flgalleryErrors[$this->className][] = "{$flgalleryErrorN}. {$msg}";

			return $msg;
		}
	}

	function warning($msg)
	{
		if (!empty($msg)) {
			global $flgalleryWarnings, $flgalleryWarningN;

			$this->warningN = ++$flgalleryWarningN;
			$flgalleryWarnings[$this->className][] = "{$flgalleryWarningN}. {$msg}";

			return $msg;
		}
	}

	function debug($msg, $for = NULL)
	{
		if (!empty($msg)) {
			global $flgalleryDebug, $flgalleryDebugN;

			$this->debugN = ++$flgalleryDebugN;

			if (is_array($for)) {
				$for = $for[0] . ' ' . $for[1];
			}
			$flgalleryDebug[$this->className][] = "{$flgalleryDebugN}. " . ($for ? "($for) " : '') . $msg;

			return $msg;
		}
	}
}
