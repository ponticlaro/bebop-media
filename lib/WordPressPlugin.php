<?php

namespace Ponticlaro\Bebop\Media;

use Ponticlaro\Bebop\ScriptsLoader\Css;
use Ponticlaro\Bebop\ScriptsLoader\Js;

class WordPressPlugin {

    /**
     * This class current and only instance
     *
     * @var object Ponticlaro\Bebop\Media\WordPressPlugin
     */
    protected static $instance;

    /**
     * Boots plugin
     *
     * @param  string $caller_file Absolute path a file inside the root directory of the plugin
     * @return object              Ponticlaro\Bebop\Media\WordPressPlugin instance
     */
    public static function boot($caller_file)
    {
        if (is_null(static::$instance))
            static::$instance = new static($caller_file);

        return static::$instance;
    }

    /**
     * Returns the single instance of the plugin class
     *
     * @return object Ponticlaro\WordPress\Bebop\Media\Plugin
     */
    public static function getInstance($caller_file = null)
    {
        if (is_null(static::$instance))
            static::boot($caller_file);

        return static::$instance;
    }

    /**
     * Instantiates WordPress plugin
     */
    protected function __construct($caller_file)
    {
        if (!is_readable($caller_file))
            throw new \Exception('$caller_file needs to be readable. Could not read '. $caller_file .'"');

        // Instantiate configuration
        $config = Config::getInstance();

        // Store base URL and path
        $config->set('plugin_base_url', plugin_dir_url($caller_file));
        $config->set('plugin_base_path', plugin_dir_path($caller_file));

        // Register activation/deactivation/uninstallation hook actions
        register_activation_hook(__FILE__, [__CLASS__, 'onActivation']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'onDeactivation']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'onUninstallation']);

        // Instantiate administration page
        AdminPage::getInstance();

        // If enabled, configure plugin to handle uploads
        if ($config->get('enabled'))
            $this->__enableFeatures();
    }

    /**
     * Enabled plugin features
     *
     * @return void
     */
    protected function __enableFeatures()
    {
      // Instantiate HTTP API
      HttpApi::getInstance();

      // Register scripts
      $this->__registerScripts();

      // Render javascript templates
      $this->__renderJavascriptTemplates();

      // Modify attachment JSON so that it contains plugin data
      add_filter('wp_prepare_attachment_for_js', [$this, '__modifyAttachmentJson'], 10, 3);

      // Manipulate media URLs
      add_filter('wp_get_attachment_url', array($this, '__handleAttachmentUrl'), 9, 2);

      // Handle media update, including remote uploads
      add_filter('wp_update_attachment_metadata', array($this, '__updateAttachment'), 9, 2);

      // Handle remote file deletion
      add_filter('wp_delete_file', array($this, '__deleteRemoteFile'));

      // Handle pushing local files to remote filesystem
      add_filter('po_bebop_media.push_file_to_remote', array($this, '__pushFileToRemote'), 1, 1);

      // Handle pulling remote files to local filesystem
      add_filter('po_bebop_media.pull_file_to_local', array($this, '__pullFileToLocal'), 1, 1);
      
      // Adds Media Library List View column
      add_action('admin_init', [$this, '__addMediaLibraryListViewColumn']);
    }

    /**
     * Runs whenever the plugin is activated
     *
     * @return void
     */
    public static function onActivation()
    {
        static::getInstance();
        flush_rewrite_rules();

        // Store plugin configuration version if not present
        if (!get_option(Config::CONFIG_VERSION_OPTION_KEY))
            add_option(Config::CONFIG_VERSION_OPTION_KEY, Config::CONFIG_VERSION);
    }

    /**
     * Runs whenever the plugin is deactivated
     *
     * @return void
     */
    public static function onDeactivation()
    {
        flush_rewrite_rules();
    }

    /**
     * Runs whenever the plugin is uninstalled
     *
     * @return void
     */
    public static function onUninstallation()
    {
        flush_rewrite_rules();
    }

    /**
     * Modifies attachment data so that it contains plugin data
     *
     * @param  array       $response    Array of prepared attachment data
     * @param  int|object  $attachment  Attachment ID or object
     * @param  array       $meta        Array of attachment meta data
     * @return array                    Modified response
     */
    public function __modifyAttachmentJson($response, $attachment, $meta)
    {   
        $file_type = get_post_mime_type($attachment->ID);

        // If the file is a image
        if (strpos($file_type, 'image') !== false) {
            $image                      = new Image($attachment->ID);
            $response['po_bebop_media'] = $image->getStatus();
        }

        return $response;
    }

    /**
     * Register all CSS and JS
     *
     * @return void
     */
    public function __registerScripts()
    {
      $config = Config::getInstance();

      // Register JS
      $js = Js::getInstance();

      // Base JS dependencies
      $js_dependencies = [
          'jquery',
          'backbone',
          'underscore'
      ];

       // Check if development environment is enabled and load dev scripts
      if ($config->get('dev_env_enabled')) {
          
          // Add development dependencies
          $js_dependencies[] = 'bebop-media--regenerate-button';

          $js->getHook('back')
             ->register('bebop-media--spin', $config->get('plugin_base_url') .'assets/js/vendor/spin.js')
             ->register('bebop-media--regenerate-button', $config->get('plugin_base_url') .'assets/js/modules/regenerate-button.js', ['bebop-media--spin'])
             ->register('bebop-media', $config->get('plugin_base_url') .'assets/js/bebop-media.js', $js_dependencies)
             ->enqueue('bebop-media');
      }

      else {

        $js->getHook('back')
           ->register('bebop-media', $config->get('plugin_base_url') .'assets/js/bebop-media.min.js', $js_dependencies)
           ->enqueue('bebop-media');
      }

      // Register CSS
      $css = Css::getInstance();

      $css->getHook('back')->register('bebop-media', $config->get('plugin_base_url') .'assets/css/bebop-media.css')
          ->enqueue('bebop-media');
    }

    /**
     * Renders templates for javascript, in the footer
     *
     * @return void
     */
    public function __renderJavascriptTemplates()
    {
      // Add plugin templates
      add_action('admin_footer', function() { ?>

        <div id="bebop-media-config" bebop-media--api-url="<?php echo Config::HTTP_API_BASE_URL; ?>"></div>

        <script bebop-media-regenerate-button-template="main" type="text/template" style="display:none">
            <?php echo file_get_contents(Config::getInstance()->get('plugin_base_path') .'templates/regenerate-button.html'); ?>
        </script>

      <?php });
    }

    /**
     * Tweaks WordPress media templates so that we can
     * display HTML we need for plugin features
     *
     * @return void
     */
    public function __modifyWordpressMediaTemplates()
    {
        ob_start();
        wp_print_media_templates();
        $original_html = ob_get_clean();
        $search = '<div class="settings">';

        ob_start();
        include Config::getInstance()->get('plugin_base_path') .'templates/attachment-editor/module.php';
        $module_html = ob_get_clean();

        echo str_replace($search, $module_html . $search, $original_html);
    }

    /**
     * Modifies URLs for all attachments depending on configuration
     *
     * @param  string $url     Absolute URL for the local file
     * @param  int    $post_id ID of the attachment in the database
     * @return string          Modified URL
     */
    public function __handleAttachmentUrl($url, $post_id)
    {
        return (new Image($post_id))->getAbsoluteUrl();
    }

    /**
     * Handles file uploads & meta data updates
     * whenever an attachment gets updated
     *
     * @param  array  $data    Data with the main file, thumbnails and image info
     * @param  int    $post_id ID of the attachment in the database
     * @return array           The exact same data passed on the first argument of this function
     */
    public function __updateAttachment($data, $post_id)
    {
        $config = Config::getInstance();
        $fs     = Filesystem::getInstance();

        // Get file (YYYY/MM/filename.extension) from database if not present in the $data array
        if (!isset($data['file']) || ! $data['file'])
            $data['file'] = trim(str_replace($config->get('local.base_dir'), '', get_attached_file($post_id, true)), '/');

        if ($fs->localHas($data['file'])) {

            // Upload original file
            if ($fs->push($data['file'])) {

                $file_type = get_post_mime_type($post_id);

                // If the file is a video, try to use the AWS Elastic Transcoder
                if (class_exists('ponticlaro\encoding\Encoder') && 
                    strpos($file_type, 'video') !== false &&
                    $config->get('elastic_transcoder.enabled') !== '' && 
                    is_readable(TEMPLATEPATH .'/'. trim($config->get('elastic_transcoder.config_file'), '/'))) {

                    $encoder       = new \ponticlaro\encoding\Encoder(require TEMPLATEPATH .'/'. trim($config->get('elastic_transcoder.config_file'), '/'));
                    $media_encoder = new \Ponticlaro\Bebop\Media\MediaEncoder($encoder);
                    $media_encoder->encodeAndSave($post_id, 's3://'. $config->get('storage.s3.bucket') .'/'. $config->get('storage.s3.prefix') .'/'. $data['file'], 's3://'. $config->get('storage.s3.bucket') .'/'. trim($config->get('storage.s3.prefix'), '/'));
                }

                $sizes = [];

                // Upload intermediate image sizes
                if (isset($data['sizes']) && $data['sizes']) {

                    $base_path = dirname($data['file']);

                    foreach ($data['sizes'] as $size_name => $size_data) {

                        if ($fs->push($base_path .'/'. $size_data['file'])) {

                            $sizes[$size_name] = [
                                'height' => $size_data['height'],
                                'width'  => $size_data['width'],
                            ];
                        }
                    }
                }

                // Update metadata
                update_post_meta($post_id, Config::ATTACHMENT_META_KEY, json_encode(array(
                  'sizes' => $sizes
                )));
            }
        }

        return $data;
    }

    /**
     * Used to push a local file to a remote filesystem
     * Used with the custom 'po_bebop_media.push_file_to_remote' hook
     *
     * @param  string $file Absolute or partial path to file, relative to uploads directory
     * @return void
     */
    public function __pushFileToRemote($file)
    {
      // Remove uploads base path so that we end up
      // with the "/YYYY/MM/filename.extension" format
      $path = str_replace(Config::getInstance()->get('local.base_dir'), '', Utils::getCleanS3Key($file));

      // Push local file to remote filesystem
      $fs = Filesystem::getInstance();

      if ($fs->localHas($path))
          $fs->push($path);

      // This is a hook function, so we should return $file
      return $file;
    }

    /**
     * Used to pull a remote file to the local filesystem
     * Used with the custom 'po_bebop_media.pull_file_to_local' hook
     *
     * @param  string $key Partial path to file, relative to uploads directory
     * @return void
     */
    public function __pullFileToLocal($key)
    {
      // Pull remote file into local filesystem
      $fs = Filesystem::getInstance();

      if ($fs->remoteHas($key))
          $fs->pull($key);

      // This is a hook function, so we should return $key
      return $key;
    }

    /**
     * Used to delete the remote version of the
     * local file being deleted via the 'wp_delete_file' filter
     *
     * The 'wp_delete_file' filter is called for all image sizes,
     * so all the right files will be deleted by using it
     *
     * @param  string $file Absolute path to the file being deleted
     * @return void
     */
    public function __deleteRemoteFile($file)
    {
        // Remove uploads base path so that we end up
        // with the "/YYYY/MM/filename.extension" format
        $path = str_replace(Config::getInstance()->get('local.base_dir'), '', $file);

        // Delete remote file
        $fs = Filesystem::getInstance();

        if ($fs->remoteHas($path))
            $fs->deleteRemote($path);

        // We must return the absolute path,
        // otherwise the local file won't be deleted
        return $file;
    }

    /**
     * Adds Media Library List View column
     * https://codex.wordpress.org/Media_Library_Screen#Media_Library_List_View
     * 
     * @return void
     */
    public function __addMediaLibraryListViewColumn() 
    {
        // Register column
        add_filter('manage_media_columns', function($columns) {

            $columns['thumbnails'] = 'Thumbnails';

            return $columns;
        });

        // Displays column content
        add_action('manage_media_custom_column', function($column, $post_id) {

            if ($column == 'thumbnails') {
              
                $button = new RegenerateButton($post_id);
                $button->setlayout('compact')->render();
            }

        }, 10, 2);
    }
}
