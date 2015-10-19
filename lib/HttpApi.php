<?php

namespace Ponticlaro\Bebop\Media;

class HttpApi {

  /**
   * Holds this class instance
   *
   * @var Ponticlaro\Bebop\Media\HttpApi
   */
  protected static $instance;

  /**
   * Holds the HTTP API instance
   *
   * @var Ponticlaro\Bebop\HttpApi
   */
  protected $http_api;

  /**
   * Instantiates HTTP API
   */
  protected function __construct()
  {
    // Instantiate HTTP API
    $this->http_api = new \Ponticlaro\Bebop\HttpApi(Config::HTTP_API_REWRITE_TAG, Config::HTTP_API_BASE_URL);

    $this->__addRoutes();
  }

  /**
   * Adds all routes to the existing HTTP API instance
   */
  protected function __addRoutes()
  {
    // Regenerate all sizes for all images
    $this->http_api->post('regenerate-all(/)', function() {

      // Set time limit of 30 minutes
      set_time_limit(60 * 30);

      $response = [];
      $query    = new \WP_Query([
        'fields'      => 'ids',
        'post_type'   => 'attachment',
        'post_status' => 'inherit',
        'numberposts' => -1
      ]);

      if ($query->posts) {
        foreach ($query->posts as $id) {
          if (strpos(get_post_mime_type($id), 'image/') !== false) {

            $image = new Image($id);

            $response[] = [
              'id'     => $id,
              'status' => $image->generateAllSizes()
            ];
          }
        }
      }

      return $response;
    });

    // Returns status about all sizes or a single one, for a single image
    $this->http_api->get(':id/status(/:size)(/)', function($id, $size = null) {

      $image = new Image($id);

      return $image->getStatus($size);
    });

    // Generate all sizes for a single image
    $this->http_api->post(':id/generate-all(/)', function($id) {

      $image = new Image($id);

      return $image->generateAllSizes();
    });

    // Generate a single image size
    $this->http_api->post(':id/generate/:size(/)', function($id, $size) {

      $image = new Image($id);

      return $image->generateSize($size);
    });
  }

  /**
   * Returns HTTP API instance
   *
   * @return Ponticlaro\Bebop\HttpApi HTTP API instance
   */
   public function getHttpApiObject()
   {
     return $this->http_api;
   }

  /**
   * Returns this class instance
   *
   * @return Ponticlaro\Bebop\Media\HttpApi This class instance
   */
  public static function getInstance()
  {
    if (is_null(static::$instance))
      static::$instance = new static;

    return static::$instance;
  }
}
