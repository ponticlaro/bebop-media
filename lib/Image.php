<?php

namespace Ponticlaro\Bebop\Media;

class Image {

  /**
   * Contains environment data collected
   * on the first instantiation of this class
   *
   * @var array
   */
  protected static $env;

  /**
   * Contains the attachment ID
   *
   * @var int
   */
  protected $id;

  /**
   * Contains the relative path to the attachment original file
   *
   * @var string
   */
  protected $partial_path;

  /**
   * Contains the built-in WordPress attachment meta data
   *
   * @var array
   */
  protected $wordpress_meta;

  /**
   * Contains plugin attachment meta data
   *
   * @var array
   */
  protected $plugin_meta;

  /**
   * Instantiates this class
   *
   * @param int $id ID of the attachment
   */
  public function __construct($id)
  {
    // Check if post exists
    if(!is_string(get_post_status($id)))
      throw new \Exception("Post does not exist");

    // Check if post is an attachment
    if (get_post_type($id) != 'attachment')
      throw new \Exception("Target post is not an attachment");

    // Check if attachment have a file path to use
    $this->partial_path = get_post_meta($id, '_wp_attached_file', true);

    if (!$this->partial_path)
      throw new \Exception("Target post does not have a file path to be used");

    // Collect environment information if not already available
    if (is_null(static::$env))
      static::$env = static::__getEnvInfo();

    // Get WordPress meta data
    $default_wordpress_meta = ['sizes' => []];
    $this->wordpress_meta   = get_post_meta($id, '_wp_attachment_metadata', true) ?: $default_wordpress_meta;

    // Get plugin meta data
    $default_plugin_meta = ['sizes' => []];
    $plugin_meta_json    = get_post_meta($id, Config::ATTACHMENT_META_KEY, true);
    $this->plugin_meta   = $plugin_meta_json ? json_decode($plugin_meta_json, true) : $default_plugin_meta;

    // Store post id
    $this->id = $id;
  }

  /**
   * Returns relative path to attachment files
   *
   * @param  string $size Set the size to get the path to it, otherwise it will return path to original file
   * @return string       The relative path for the target size or original file
   */
  public function getPartialPath($size = null)
  {
    $path = $this->partial_path;

    if ($size) {

      if ($this->sizePresetIsDefined($size) && $this->sizeExists($size)) {
        $path = dirname($path) .'/'. $this->wordpress_meta['sizes'][$size]['file'];
      }

      else {
        $path = null;
      }
    }

    return $path;
  }

  /**
   * Returns absolute path to attachment files
   *
   * @param  string $size Set the size to get the path to it, otherwise it will return path to original file
   * @return string       The absolute path for the target size or original file
   */
  public function getAbsolutePath($size = null)
  {
    $path = static::$env['uploads_path'] .'/'. $this->partial_path;

    if ($size) {

      if ($this->sizePresetIsDefined($size) && $this->sizeExists($size)) {
        $path = dirname($path) .'/'. $this->wordpress_meta['sizes'][$size]['file'];
      }

      else {
        $path = null;
      }
    }

    return $path;
  }

  /**
   * Returns relative url for attachment files
   *
   * @param  string $size Set the size to get the url for it, otherwise it will return url for original file
   * @return string       The relative url for the target size or original file
   */
  public function getRelativeURL($size = null)
  {
    $config = Config::getInstance();
    $url    = $this->partial_path;

    if ($size) {

      if ($this->sizePresetIsDefined($size) && $this->sizeExists($size)) {
        $url = dirname($path) .'/'. $this->wordpress_meta['sizes'][$size]['file'];
      }

      else {
        $url = null;
      }
    }

    if ('aws_s3' == $config->get('storage.provider'))
      $url = Utils::getCleanAWSS3Key($url);

    return '/'. $url;
  }

  /**
   * Returns absolute url for attachment files
   *
   * @param  string $size Set the size to get the url for it, otherwise it will return url for original file
   * @return string       The absolute url for the target size or original file
   */
  public function getAbsoluteURL($size = null)
  {
    $config = Config::getInstance();
    $url    = Utils::getMediaBaseUrl() .'/'. $this->partial_path;

    if ($size) {

      if ($this->sizePresetIsDefined($size) && $this->sizeExists($size)) {
        $url = dirname($url) .'/'. $this->wordpress_meta['sizes'][$size]['file'];
      }

      else {
        $url = null;
      }
    }

    if ('aws_s3' == $config->get('storage.provider'))
      $url = Utils::getCleanAWSS3Key($url);

    return $url;
  }

  /**
   * Returns all image size presets defined on the current WordPress environment
   *
   * @return array List of image size presets
   */
  public function getAllSizePresets()
  {
    return static::$env['presets'];
  }

  /**
   * Checks if the target image size preset is defined
   *
   * @param string $size Name of the image size preset
   */
  public function sizePresetIsDefined($size)
  {
    return array_key_exists($size, static::$env['presets']);
  }

  /**
   * Gets the width of the target image size preset
   *
   * @param  string $size Name of the image size preset
   * @return int          Width of the image size preset
   */
  public function getSizePresetWidth($size)
  {
    return $this->sizePresetIsDefined($size) ? static::$env['presets'][$size]['width'] : null;
  }

  /**
   * Gets the height of the target image size preset
   *
   * @param  string $size Name of the image size preset
   * @return int          Height of the image size preset
   */
  public function getSizePresetHeight($size)
  {
    return $this->sizePresetIsDefined($size) ? static::$env['presets'][$size]['height'] : null;
  }

  /**
   * Gets the crop value of the target image size preset
   *
   * @param  string $size Name of the image size preset
   * @return bool         True if it should crop, false otherwise
   */
  public function getSizePresetCrop($size)
  {
    return $this->sizePresetIsDefined($size) ? static::$env['presets'][$size]['crop'] : null;
  }

  /**
   * Checks if an image size was previously generated
   *
   * @param  string $size Name of the image size
   * @return bool         True if exists, false otherwise
   */
  public function sizeExists($size)
  {
    return is_array($this->wordpress_meta['sizes']) ? array_key_exists($size, $this->wordpress_meta['sizes']) : false;
  }

  /**
   * Gets the width of the original image
   *
   * @return int Width of the original image
   */
  public function getOriginalWidth()
  {
    return isset($this->wordpress_meta['width']) ? $this->wordpress_meta['width'] : null;
  }

  /**
   * Gets the height of the original image
   *
   * @return int Height of the original image
   */
  public function getOriginalHeight()
  {
    return isset($this->wordpress_meta['height']) ? $this->wordpress_meta['height'] : null;
  }

  /**
   * Gets the width of the existing image size
   *
   * @param  string $size Name of the image size
   * @return int          Width of the image size
   */
  public function getSizeWidth($size)
  {
    return $this->sizeExists($size) && isset($this->wordpress_meta['sizes'][$size]['width']) ? $this->wordpress_meta['sizes'][$size]['width'] : null;
  }

  /**
   * Gets the height of the existing image size
   *
   * @param  string $size Name of the image size
   * @return int          Height of the image size
   */
  public function getSizeHeight($size)
  {
    return $this->sizeExists($size) && isset($this->wordpress_meta['sizes'][$size]['height']) ? $this->wordpress_meta['sizes'][$size]['height'] : null;
  }

  /**
   * Gets the crop value of the existing image size
   *
   * @param  string $size Name of the image size
   * @return bool         True if it was croped, false otherwise
   */
  public function getSizeCrop($size)
  {
    return $this->sizeExists($size) && isset($this->wordpress_meta['sizes'][$size]['crop']) ? $this->wordpress_meta['sizes'][$size]['crop'] : null;
  }

  /**
   * Checks if the original image can generate the target size
   *
   * @param  string $size Name of the image size
   * @return bool         True if it can, false otherwise
   */
  public function canGenerateSize($size)
  {
    $original_width  = $this->getOriginalWidth();
    $original_height = $this->getOriginalHeight();
    $preset_width    = $this->getSizePresetWidth($size);
    $preset_height   = $this->getSizePresetHeight($size);

    return $original_width && $preset_width !== 0 && $original_width >= $preset_width || $original_height && $preset_height !== 0 && $original_height >= $preset_height ? true : false;
  }

  /**
   * Checks if the currently existing image size does not match the image size preset
   *
   * @param string $size Name of the image size
   */
  public function isSizeMismatchingPreset($size)
  {
    // Return null if the preset for target size does not exist
    // OR the size itself was never generated
    if (!$this->sizePresetIsDefined($size) || !$this->sizeExists($size))
      return null;

    $size_width    = $this->getSizeWidth($size);
    $size_height   = $this->getSizeHeight($size);
    $preset_width  = $this->getSizePresetWidth($size);
    $preset_height = $this->getSizePresetHeight($size);
    $preset_crop   = $this->getSizePresetCrop($size);

    if (($preset_crop && $size_width == $preset_width && $size_height == $preset_height) ||
        (!$preset_crop && ($size_width == $preset_width || $size_height == $preset_height))) {

        return false;
    }

    return true;
  }

  /**
   * Generates an image size
   *
   * @param  string $size Name of the image size
   * @return array        Status for the target image size
   */
  public function generateSize($size)
  {
    // Pull remote file to local filesystem, if not available
    do_action('po_bebop_media.pull_file_to_local', $this->getPartialPath());

    // Store current file path
    $old_absolute_path = $this->getAbsolutePath($size);

    // Generate new size
    $result = image_make_intermediate_size(
      $this->getAbsolutePath(),
      $this->getSizePresetWidth($size),
      $this->getSizePresetHeight($size),
      $this->getSizePresetCrop($size));

    if ($result) {

      $this->__updateWordpressMeta([
        $size => $result
      ]);

      $this->__updatePluginMeta([
        $size => $result
      ]);

      // Send new local file to remote filesystem
      do_action('po_bebop_media.push_file_to_remote', $this->getPartialPath($size));

      // Delete local and remote file for the target size,
      // but only if the new path is different from the old one
      if ($old_absolute_path != $this->getAbsolutePath($size))
        apply_filters('wp_delete_file', $old_absolute_path);

      // Using the 'wp_delete_file' hook should be enough,
      // but currently it is not deleting the local file
      if ($old_absolute_path != $this->getAbsolutePath($size) && file_exists($old_absolute_path))
        unlink($old_absolute_path);
    }

    return $this->getStatus($size);
  }

  /**
   * Generates all image sizes for the current attachment
   *
   * @return array List of image size status for all image sizes that are defined on the current environment
   */
  public function generateAllSizes()
  {
    $data = [];

    foreach (static::$env['presets'] as $size => $size_data) {
        $data[$size] = $this->canGenerateSize($size) ? $this->generateSize($size) : $this->getStatus($size);
    }

    return $data;
  }

  /**
   * Gets status for all image sizes or only a single image size
   *
   * @param  string $size Optionally set a size name to get the status only for that size
   * @return array        List with all the details about image size status
   */
  public function getStatus($size = null)
  {
    if ($size) {
      return $this->__getSizeStatus($size);
    }

    else {

      $presets = $this->getAllSizePresets();
      $data    = ['sizes' => []];

      // Sort alphabetically
      ksort($presets);

      foreach ($presets as $size => $size_data) {
        $data['sizes'][$size] = $this->__getSizeStatus($size);
      }

      return $data;
    }
  }

  /**
   * Get the attachment status for a given size
   *
   * @param  string $size Image size name
   * @return array        List with all the details about the image size status
   */
  protected function __getSizeStatus($size)
  {
    return [
      'name'            => $size,
      'url'             => $this->sizeExists($size) ? $this->getAbsoluteURL($size) : null,
      'width'           => $this->sizeExists($size) ? $this->getSizeWidth($size) : null,
      'height'          => $this->sizeExists($size) ? $this->getSizeHeight($size) : null,
      'can_generate'    => $this->canGenerateSize($size),
      'preset_mismatch' => $this->sizeExists($size) ? $this->isSizeMismatchingPreset($size) : null,
      'preset_width'    => $this->getSizePresetWidth($size),
      'preset_height'   => $this->getSizePresetHeight($size),
    ];
  }

  /**
   * Updates WordPress attachment meta data
   *
   * @param  array  $data Data to merge with existing meta data
   * @return void
   */
  protected function __updateWordpressMeta(array $data)
  {
    if ($data) {
      foreach ($data as $size => $size_data) {
        $this->wordpress_meta['sizes'][$size]['file']   = $size_data['file'];
        $this->wordpress_meta['sizes'][$size]['width']  = $size_data['width'];
        $this->wordpress_meta['sizes'][$size]['height'] = $size_data['height'];
      }

      update_post_meta($this->id, '_wp_attachment_metadata', $this->wordpress_meta);
    }
  }

  /**
   * Updates attachment meta data
   *
   * @param  array  $data Data to merge with existing meta data
   * @return void
   */
  protected function __updatePluginMeta(array $data)
  {
    if ($data) {
      foreach ($data as $size => $size_data) {
        $this->plugin_meta['sizes'][$size]['width']  = $size_data['width'];
        $this->plugin_meta['sizes'][$size]['height'] = $size_data['height'];
      }

      update_post_meta($this->id, Config::ATTACHMENT_META_KEY, json_encode($this->plugin_meta));
    }
  }

  /**
   * Collects environment information useful to handle image manipulation
   *
   * @return array Environment information
   */
  protected function __getEnvInfo()
  {
    global $_wp_additional_image_sizes;

    $default_sizes = ['thumbnail', 'medium', 'large'];
    $all_sizes     = array_merge($default_sizes, get_intermediate_image_sizes());
    $presets_data  = [];

    foreach ($all_sizes as $size) {

      $builtin_size_width  = isset($_wp_additional_image_sizes[$size]) ? $_wp_additional_image_sizes[$size]['width'] : null;
      $builtin_size_height = isset($_wp_additional_image_sizes[$size]) ? $_wp_additional_image_sizes[$size]['height'] : null;
      $builtin_size_crop   = isset($_wp_additional_image_sizes[$size]) ? $_wp_additional_image_sizes[$size]['crop'] : null;

      $presets_data[$size] = [
        'width'  => in_array($size, $default_sizes) ? (int)get_option($size .'_size_w') : $builtin_size_width,
        'height' => in_array($size, $default_sizes) ? (int)get_option($size .'_size_h') : $builtin_size_height,
        'crop'   => in_array($size, $default_sizes) ? (bool)get_option($size .'_crop') : $builtin_size_crop,
      ];
    }

    $uploads_info = wp_upload_dir();

    return [
      'uploads_path' => $uploads_info['basedir'],
      'uploads_url'  => $uploads_info['baseurl'],
      'presets'      => $presets_data
    ];
  }
}
