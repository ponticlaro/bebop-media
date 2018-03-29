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
  
  /**
   * Returns Google Cloud Storage signed URL string
   * 
   * @param string $path Path of file within Google Cloud Storage
   * @return string
   */
  public static function getGCSSignedUrlString( $path ) 
  {
    $config = Config::getInstance();
    $prefix = $config->get('storage.gcs.prefix');
    
    // Get cached signed string
    $clean_path           = trim( $path, '/');
    $cached_signed_string = get_transient( 'bebop_media_'. str_replace( $prefix, '', $clean_path ) );

    // Return cached signed string if we have one
    if ( $cached_signed_string )
      return $cached_signed_string; 
    
    $project_id = $config->get('storage.gcs.project_id');
    $auth_json  = $config->get('storage.gcs.auth_json');
    $bucket     = $config->get('storage.gcs.bucket');
    $expires_in = $config->get('storage.gcs.signed_url_expiration');

    // Fallback to 24 hours expiration
    if ( ! is_integer( $expires_in ) )
      $expires_in = Config::GCS_SIGNED_URL_EXPIRATION;

    // Init Google Cloud Storage client
    $storage = new \Google\Cloud\Storage\StorageClient([
      'projectId' => $project_id,
      'keyFile'   => json_decode($auth_json, true)
    ]);

    // Get signed URL
    $expiration    = time() + $expires_in;
    $bucket        = $storage->bucket( $bucket );
    $object        = $bucket->object( $clean_path );
    $url           = $object->signedUrl( $expiration );
    $signed_string = $url ? parse_url( $url, PHP_URL_QUERY ) : '';

    // Cache signed string
    if ( $signed_string ) {
      set_transient( 'bebop_media_'. str_replace( $prefix, '', $clean_path ), $signed_string, $expires_in );
    }

    return $signed_string;
  }
}