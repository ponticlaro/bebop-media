<?php

namespace Ponticlaro\Bebop\Media;

class Utils {
	
	/**
	 * Cleans/Escapes S3 key
	 * 
	 * @param  string $key Raw key
	 * @return string      Escaped/Cleaned key
	 */
	public static function getCleanS3Key($key)
	{
		if (!is_string($key))
		  return $key;

		return str_replace('+', '%2B', $key);
	}
}