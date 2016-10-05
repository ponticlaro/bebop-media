<?php

namespace Ponticlaro\Bebop\Media;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem as FlyFilesystem;
use League\Flysystem\MountManager;
use Google\Cloud\Storage\StorageClient;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

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
   * Instantiates this class
   */
  protected function __construct()
  {
    $config   = Config::getInstance();
    $provider = $config->get('storage.provider');

    if ($provider && $provider !== 'none') {

      $filesystems = [
        'local'  => null, 
        'remote' => null
      ];
      
      // Init local filesystem
      $local_adapter     = new \League\Flysystem\Adapter\Local($config->get('local.base_dir'));
      $local_filesystem  = new \League\Flysystem\Filesystem($local_adapter, [
          'visibility' => \League\Flysystem\AdapterInterface::VISIBILITY_PUBLIC
      ]);

      if ($local_filesystem)
        $filesystems['local'] = $local_filesystem;

      switch ($provider) {

        // Handle AWS S3 filesystem
        case 'aws_s3':
          $filesystems['remote'] = $this->__getAWSS3Filesystem();
          break;
        
        // Handle Google Cloud Storage filesystem
        case 'gcs':
          $filesystems['remote'] = $this->__getGCSFilesystem();
          break;
      }

      // Set filesystem manager
      $this->filesystem = new MountManager($filesystems);
    }
  }

  /**
   * Returns AWS S3 filesystem
   * 
   * @return object AWS S3 filesystem
   */
  protected function __getAWSS3Filesystem()
  {
    $config = Config::getInstance();
    $scheme = $config->get('url_scheme');
    $key    = $config->get('storage.s3.key');
    $secret = $config->get('storage.s3.secret');
    $region = $config->get('storage.s3.region');
    $bucket = $config->get('storage.s3.bucket');
    $prefix = $config->get('storage.s3.prefix');
    
    if (!$scheme || !$key || !$secret || !$region || !$bucket)
      return null;

    // Init AWS S3 client
    $client = S3Client::factory([
      'credentials' => [
        'key'    => $key,
        'secret' => $secret,
      ],
      'region'  => $region,
      'scheme'  => $scheme,
      'version' => 'latest'
    ]);

    // Init adapter
    $adapter = new AwsS3Adapter($client, $bucket, $prefix);

    // Return AWS S3 filesystem
    return new FlyFilesystem($adapter, [
      'visibility' => AdapterInterface::VISIBILITY_PUBLIC
    ]);
  }

  /**
   * Returns Google Cloud Storage filesystem
   * 
   * @return object Google Cloud Storage
   */
  protected function __getGCSFilesystem()
  {
    $config      = Config::getInstance();
    $project_id  = $config->get('storage.gcs.project_id');
    $auth_json   = $config->get('storage.gcs.auth_json');
    $bucket_name = $config->get('storage.gcs.bucket');
    $prefix      = $config->get('storage.gcs.prefix');

    if (!$project_id || !$auth_json || !$bucket_name)
      return null;

    // Init Google Cloud Storage client
    $client = new StorageClient([
      'projectId' => $project_id,
      'keyFile'   => json_decode($auth_json, true)
    ]);

    // Get bucket object
    $bucket = $client->bucket($bucket_name);

    // Init adapter
    $adapter = new GoogleStorageAdapter($client, $bucket, $prefix);

    // Return Google Cloud Storage filesystem
    return new FlyFilesystem($adapter, [
      'visibility' => AdapterInterface::VISIBILITY_PUBLIC
    ]);
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
    return $this->filesystem->has('remote://'. trim($path, '/'));
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
    return $this->pullFrom('remote', $path);
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

    return $this->filesystem->put($filesystem_id .'://'. $path, $content);
  }

  /**
   * Pushes a file from local filesystem into default remote filesystem
   *
   * @param  string $path Partial path to file
   * @return bool         True on success, false otherwise
   */
  public function push($path)
  {
    return $this->pushTo('remote', $path);
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
    return $this->deleteFrom('remote', $path);
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
