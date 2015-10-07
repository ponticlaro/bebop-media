<?php

namespace Ponticlaro\Bebop\Media;

class Filesystem {

  /**
   * This class instance
   *
   * @var Ponticlaro\Bebop\Media\Filesystem
   */
  protected static $instance;

  /**
   * Object that handles local and remote files
   *
   * @var League\Flysystem\Filesystem
   */
  protected $filesystem;

  /**
   * ID of the default remote filesystem
   *
   * @var string
   */
  protected $default_remote_filesystem_id = 's3';

  /**
   * Instantiates this class
   */
  protected function __construct()
  {
    $config = Config::getInstance();

    // Init local filesystem
    $local_adapter     = new \League\Flysystem\Adapter\Local($config->get('local.base_dir'));
    $local_filesystem  = new \League\Flysystem\Filesystem($local_adapter, [
        'visibility' => \League\Flysystem\AdapterInterface::VISIBILITY_PUBLIC
    ]);

    // Init S3 filesystem
    $s3_client = \Aws\S3\S3Client::factory([
        'credentials' => [
            'key'    => $config->get('storage.s3.key'),
            'secret' => $config->get('storage.s3.secret'),
        ],
        'region'  => $config->get('storage.s3.region'),
        'scheme'  => $config->get('url_scheme'),
        'version' => 'latest'
    ]);

    $s3_adapter    = new \League\Flysystem\AwsS3v3\AwsS3Adapter($s3_client, $config->get('storage.s3.bucket'), $config->get('storage.s3.prefix'));
    $s3_filesystem = new \League\Flysystem\Filesystem($s3_adapter, [
        'visibility' => \League\Flysystem\AdapterInterface::VISIBILITY_PUBLIC
    ]);

    // Set filesystem manager
    $this->filesystem = new \League\Flysystem\MountManager([
        'local' => $local_filesystem,
        's3'    => $s3_filesystem
    ]);
  }

  /**
   * Sets the ID for the default remote filesystem
   *
   * @param string $filesystem_id ID of the remote filesystem to be set as default
   */
  public function setDefaultRemoteFilesystem($filesystem_id)
  {
    $this->default_remote_filesystem_id = $filesystem_id;
  }

  /**
   * Checks if local filesystem has a file
   *
   * @param  string $path Partial path to file
   * @return bool         True if exists, false otherwise
   */
  public function localHas($path)
  {
    return $this->filesystem->has('local://'. trim($path, '/'));
  }

  /**
   * Checks if default remote filesystem has a file
   *
   * @param  string $path Partial path to file
   * @return bool         True if exists, false otherwise
   */
  public function remoteHas($path)
  {
    return $this->filesystem->has($this->default_remote_filesystem_id .'://'. trim($path, '/'));
  }

  /**
   * Pulls a file from target remote filesystem into local filesystem
   *
   * @param  string $filesystem_id ID of the remote filesystem
   * @param  string $path          Partial path to file
   * @return bool                  True on success, false otherwise
   */
  public function pullFrom($filesystem_id, $path)
  {
    $content = $this->filesystem->read($filesystem_id .'://'. trim($path, '/'));

    return $this->filesystem->put('local://'. trim($path, '/'), $content);
  }

  /**
   * Pulls a file from default remote filesystem into local filesystem
   *
   * @param  string $path Partial path to file
   * @return bool         True on success, false otherwise
   */
  public function pull($path)
  {
    return $this->pullFrom($this->default_remote_filesystem_id, $path);
  }

  /**
   * Pushes a file from local filesystem into target remote filesystem
   *
   * @param  string $filesystem_id ID of the remote filesystem
   * @param  string $path          Partial path to file
   * @return bool                  True on success, false otherwise
   */
  public function pushTo($filesystem_id, $path)
  {
    $content = $this->filesystem->read('local://'. trim($path, '/'));

    return $this->filesystem->put($this->default_remote_filesystem_id .'://'. $path, $content);
  }

  /**
   * Pushes a file from local filesystem into default remote filesystem
   *
   * @param  string $path Partial path to file
   * @return bool         True on success, false otherwise
   */
  public function push($path)
  {
    return $this->pushTo($this->default_remote_filesystem_id, $path);
  }

  /**
   * Deletes file from target remote filesystem
   *
   * @param  string $filesystem_id ID of the remote filesystem
   * @param  string $path          Partial path to file
   * @return bool                  True on success, false otherwise
   */
  public function deleteFrom($filesystem_id, $path)
  {
    return $this->filesystem->delete($filesystem_id .'://'. trim($path, '/'));
  }

  /**
   * Deletes file from local filesystem
   *
   * @param  string $path Partial path to file
   * @return bool         True on success, false otherwise
   */
  public function deleteLocal($path)
  {
    return $this->deleteFrom('local', $path);
  }

  /**
   * Deletes file from default remote filesystem
   *
   * @param  string $path Partial path to file
   * @return bool         True on success, false otherwise
   */
  public function deleteRemote($path)
  {
    return $this->deleteFrom($this->default_remote_filesystem_id, $path);
  }

  /**
   * Returns this class instance
   *
   * @return Ponticlaro\CloudMedia\Filesystem This class instance
   */
  public static function getInstance()
  {
    if (is_null(static::$instance))
      static::$instance = new static;

    return static::$instance;
  }
}
