<?php

namespace Ponticlaro\Bebop\Media;

class Utils {

  /**
   * Returns the correct base URL for
   * attachments depending on the configuration
   *
   * @return string Attachments base URL
   */
  public static function getMediaBaseUrl()
  {
    $config = Config::getInstance();

    if ($config->get('cdn.enabled') && $config->get('cdn.domain')) {
      return self::__getMediaCdnBaseUrl();
    }

    elseif('aws_s3' == $config->get('storage.provider')) {
      return self::__getMediaAWSS3BaseUrl();
    }

    elseif('gcs' == $config->get('storage.provider')) {
      return self::__getMediaGCSBaseUrl();
    }

    return $config->get('local.base_url');
  }

  /**
   * Returns CDN base URL using the current configuration
   *
   * @return string CDN URL
   */
  protected static function __getMediaCdnBaseUrl()
  {
  	$config = Config::getInstance();
  	$url    = ($config->get('url_scheme') ?: 'http') .'://';
  	$url   .= $config->get('cdn.domain');
  	$url   .= $config->get('cdn.prefix') ? '/'. trim($config->get('cdn.prefix'), '/') : '';

    return $url;
  }

  /**
   * Returns AWS S3 base URL using the current configuration
   *
   * @return string AWS S3 base URL
   */
  protected static function __getMediaAWSS3BaseUrl()
  {
  	$config = Config::getInstance();
  	$url    = ($config->get('url_scheme') ?: 'http') .'://s3';
  	$url   .= $config->get('storage.s3.region') != 'us-east-1' ? '.'. $config->get('storage.s3.region') : '';
  	$url   .= '.amazonaws.com/';
  	$url   .= $config->get('storage.s3.bucket');
  	$url   .= $config->get('storage.s3.prefix') ? '/'. trim($config->get('storage.s3.prefix'), '/') : '';

    return $url;
  }

  /**
   * Returns Google Cloud Storage base URL using the current configuration
   *
   * @return string Google Cloud Storage base URL
   */
  protected static function __getMediaGCSBaseUrl()
  {
  	$config = Config::getInstance();
  	$url    = ($config->get('url_scheme') ?: 'http') .'://storage.googleapis.com/';
  	$url   .= $config->get('storage.gcs.bucket');
  	$url   .= $config->get('storage.gcs.prefix') ? '/'. trim($config->get('storage.gcs.prefix'), '/') : '';

    return $url;
  }

	/**
	 * Cleans/Escapes AWS S3 key
	 * 
	 * @param  string $key Raw key
	 * @return string      Escaped/Cleaned key
	 */
	public static function getCleanAWSS3Key($key)
	{
		if (!is_string($key))
		  return $key;

		return str_replace('+', '%2B', $key);
	}
}