<?php

namespace Flysystem;

use Finfo;

abstract class Util
{
	public static function pathinfo($path)
	{
		$pathinfo = pathinfo($path) + compact('path');

		if ($pathinfo['dirname'] === '.') {
			$pathinfo['dirname'] = '';
		}

		return $pathinfo;
	}

	public static function map($object, array $map)
	{
		$result = [];

		foreach ($map as $from => $to) {
			if ( ! isset($object[$from]))
				continue;

			$result[$to] = $object[$from];
		}

		return $result;
	}

	public static function normalizePath($path, $separator)
	{
		return ltrim($path, $separator);
	}

	public static function normalizePrefix($prefix, $separator)
	{
		return rtrim($prefix, $separator).$separator;
	}

	public static function contentSize($contents)
	{
		return mb_strlen($contents, '8bit');
	}

	public static function contentMimetype($content)
	{
		$finfo = new Finfo(FILEINFO_MIME_TYPE);

		return $finfo->buffer($content);
	}
}