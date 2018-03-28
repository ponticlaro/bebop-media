<?php

namespace Ponticlaro\Bebop\Media;

use Ponticlaro\Bebop\Common\Collection;

class Config {

  /**
   * Plugin title
   */
  const PLUGIN_TITLE = 'Ponticlaro Media';

  /**
   * Admin page main heading
   */
  const ADMIN_PAGE_MAIN_TITLE = 'Ponticlaro Media';

  /**
   * Plugin configuration version
   */
  const CONFIG_VERSION = 'v1';

  /**
   * Key for wordpress option that contains the plugin configuration version
   */
  const CONFIG_VERSION_OPTION_KEY = 'po_bebop_media_version';

  /**
   * Key for wordpress option that contains the main plugin configuration
   */
  const MAIN_CONFIG_OPTION_KEY = 'po_bebop_media_main';

  /**
   * Key for wordpress option that contains the storage plugin configuration
   */
  const STORAGE_CONFIG_OPTION_KEY = 'po_bebop_media_storage';

  /**
   * Key for wordpress option that contains the CDN plugin configuration
   */
  const CDN_CONFIG_OPTION_KEY = 'po_bebop_media_cdn';

  /**
   * Key for wordpress option that contains the AWS elastic transcoder plugin configuration
   */
  const ELASTIC_TRANSCODER_CONFIG_OPTION_KEY = 'po_bebop_media_elastic_transcoder';

  /**
   * Meta data key for cloudmedia details about each attachment
   */
  const ATTACHMENT_META_KEY = '_po_bebop_media_info';

  /**
   * HTTP API unique rewrite tag
   */
  const HTTP_API_REWRITE_TAG = '_po_bebop_media';

  /**
   * HTTP API base URL for all its resources
   */
  const HTTP_API_BASE_URL = '_bebop-media/api/';

  /**
   * Allowed visibilities for remote filesystems
   */
  const ALLOWED_UPLOAD_VISIBILITIES = [
    'public', 'private'
  ];

  /**
   * This class instance
   *
   * @var Ponticlaro\Bebop\Media\Config
   */
  protected static $instance;

  /**
   * Map matching configuration keys with their environment variables
   *
   * @var array
   */
  protected $env_config_map = [

    // Main
    'dev_env_enabled' => 'PO_BEBOP_MEDIA__DEV_ENV_ENABLED',
    'enabled'         => 'PO_BEBOP_MEDIA__ENABLED',
    'url_scheme'      => 'PO_BEBOP_MEDIA__URL_SCHEME',

    // Storage
    'storage.provider'   => 'PO_BEBOP_MEDIA__STORAGE_PROVIDER',
    'storage.visibility' => 'PO_BEBOP_MEDIA__STORAGE_VISIBILITY',

    // Storage: AWS S3
    'storage.s3.key'    => 'PO_BEBOP_MEDIA__STORAGE_S3_KEY',
    'storage.s3.secret' => 'PO_BEBOP_MEDIA__STORAGE_S3_SECRET',
    'storage.s3.region' => 'PO_BEBOP_MEDIA__STORAGE_S3_REGION',
    'storage.s3.bucket' => 'PO_BEBOP_MEDIA__STORAGE_S3_BUCKET',
    'storage.s3.prefix' => 'PO_BEBOP_MEDIA__STORAGE_S3_PREFIX',

    // Storage: Google Cloud Storage
    'storage.gcs.project_id'             => 'PO_BEBOP_MEDIA__STORAGE_GCS_PROJECT_ID',
    'storage.gcs.bucket'                 => 'PO_BEBOP_MEDIA__STORAGE_GCS_BUCKET',
    'storage.gcs.prefix'                 => 'PO_BEBOP_MEDIA__STORAGE_GCS_PREFIX',
    'storage.gcs.auth_json'              => 'PO_BEBOP_MEDIA__STORAGE_GCS_AUTH_JSON',
    'storage.gcs.signed_url_expiration'  => 'PO_BEBOP_MEDIA__STORAGE_GCS_SIGNED_URL_EXPIRATION',

    // CDN
    'cdn.enabled' => 'PO_BEBOP_MEDIA__CDN_ENABLED',
    'cdn.domain'  => 'PO_BEBOP_MEDIA__CDN_DOMAIN',
    'cdn.prefix'  => 'PO_BEBOP_MEDIA__CDN_PREFIX',

    // AWS Elastic Transcoder
    'elastic_transcoder.enabled'     => 'PO_BEBOP_MEDIA__ELASTIC_TRANSCODER_ENABLED',
    'elastic_transcoder.config_file' => 'PO_BEBOP_MEDIA__ELASTIC_TRANSCODER_CONFIG_FILE',
  ];

  /**
   * Configuration data
   *
   * @var object Ponticlaro\Bebop\Common\Collection
   */
  protected $data;

  /**
   * Instantiates class
   *
   */
  protected function __construct()
  {
    // Initialize configuration object
    $this->data = new Collection(array_merge(
        get_option(static::MAIN_CONFIG_OPTION_KEY) ?: [],
        ['storage'            => get_option(static::STORAGE_CONFIG_OPTION_KEY) ?: []],
        ['cdn'                => get_option(static::CDN_CONFIG_OPTION_KEY) ?: []],
        ['elastic_transcoder' => get_option(static::ELASTIC_TRANSCODER_CONFIG_OPTION_KEY) ?: []]
    ));

    // Get WordPress uploads directory
    $uploads_info = wp_upload_dir();
    $this->data->set('local.base_dir', $uploads_info['basedir']);
    $this->data->set('local.base_url', $uploads_info['baseurl']);
  }

  /**
   * Returns class instance
   *
   * @return Ponticlaro\Bebop\Media\Config
   */
  public static function getInstance()
  {
    if (is_null(static::$instance))
      static::$instance = new static;

    return static::$instance;
  }

  /**
   * Returns a single configuration value
   *
   * @param  string $key Configuration key
   * @return mixed       Configuration value, otherwise null
   */
  public function get($key)
  {
    $value = $this->hasEnv($key) ? $this->getEnv($key) : $this->data->get($key);

    if (!$value) {
      
      // Fallback to AWS S3 as storage provider
      if ($key == 'storage.provider' &&
          $this->get('storage.s3.key') && 
          $this->get('storage.s3.secret') &&
          $this->get('storage.s3.region') &&
          $this->get('storage.s3.bucket')) {
        
        $value = 'aws_s3';

        if ($this->hasEnv('storage.s3.key') && 
            $this->hasEnv('storage.s3.secret') && 
            $this->hasEnv('storage.s3.region') && 
            $this->hasEnv('storage.s3.bucket') && 
            getenv('PO_BEBOP_MEDIA__STORAGE_PROVIDER') === false) {

          putenv("PO_BEBOP_MEDIA__STORAGE_PROVIDER=$value");
        }
      }
    }

    return $value;
  }

  /**
   * Sets a single configuration value
   *
   * @param string $key   Configuration key
   * @param mixed  $value Configuration value
   */
  public function set($key, $value)
  {
    $this->data->set($key, $value);

    return $this;
  }

  /**
   * Returns configuration value from a constant
   *
   * @param  string $key Configuration key
   * @return mixe        The constant value, otherwise null
   */
  public function getEnv($key)
  {
    return isset($this->env_config_map[$key]) && getenv($this->env_config_map[$key]) ? getenv($this->env_config_map[$key]) : null;
  }

  /**
   * Checks if the target configuration constant was defined
   *
   * @param  string $key Configuration key
   * @return mixed       True if defined, otherwise false
   */
  public function hasEnv($key)
  {
    return isset($this->env_config_map[$key]) && getenv($this->env_config_map[$key]) ? true : false;
  }
}