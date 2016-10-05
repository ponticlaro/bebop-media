<?php

namespace Ponticlaro\Bebop\Media;

use Ponticlaro\Bebop\UI\Module as UIModule;

class AdminPage {

  /**
   * Holds this class instance
   *
   * @var Ponticlaro\Bebop\Media\AdminPage
   */
  protected static $instance;

  /**
   * Holds the AdminPage instance
   *
   * @var Ponticlaro\Bebop\Cms\AdminPage
   */
  protected $admin_page;

  /**
   * Instantiates AdminPage
   */
  protected function __construct()
  {
    // Init admin page
    $this->admin_page = new \Ponticlaro\Bebop\Cms\AdminPage(Config::ADMIN_PAGE_MAIN_TITLE);

    // Adde admin page tabs
    $this->__addMainConfigTab();
    $this->__addStorageConfigTab();
    $this->__addCdnConfigTab();

    // Conditionally add the AWS Elastic Transcoder admin tab
    if (class_exists('ponticlaro\encoding\Encoder'))
      $this->__addAwsElasticTranscoderConfigTab();
  }

  /**
   * Adds tab with main configuration options
   */
  protected function __addMainConfigTab()
  {
    $this->admin_page->addTab('Main', function($data) {

      $config        = Config::getInstance();
      $enabled_attrs = [];
      $scheme_attrs  = [];

      if ($config->hasEnv('enabled'))
        $enabled_attrs['disabled'] = 'disabled';

      if ($config->hasEnv('url_scheme'))
        $scheme_attrs['disabled'] = 'disabled';

      UIModule::Checkbox([
        'name'        => Config::MAIN_CONFIG_OPTION_KEY .'[enabled]',
        'value'       => $config->get('enabled'),
        'attrs'       => $enabled_attrs,
        'description' => 'Check this to enable this plugin',
        'options'     => [
          [
            'label' => 'Enabled',
            'value' => '1'
          ]
        ]
      ])->render();

      UIModule::Select([
        'label'   => 'URL Scheme',
        'name'    => Config::MAIN_CONFIG_OPTION_KEY .'[url_scheme]',
        'value'   => $config->get('url_scheme') ?: 'http',
        'attrs'   => $scheme_attrs,
        'options' => [
          [
            'label' => 'http',
            'value' => 'http'
          ],
          [
            'label' => 'https',
            'value' => 'https'
          ]
        ]
      ])->render();

      UIModule::Button([
        'text'  => 'Save',
        'attrs' => [
          'class' => 'button button-primary'
        ]
      ])->render();

    });
  }

  /**
   * Adds tab with configuration options for Storage
   */
  protected function __addStorageConfigTab()
  {
    $this->admin_page->addTab('Storage', function($data) { 

      // Get configuration instance
      $config = Config::getInstance();

      $provider       = $config->get('storage.provider');
      $provider_attrs = [
        'id' => 'bebop-media-storage-provider-selector'
      ];

      if ($config->hasEnv('storage.provider'))
        $provider_attrs['disabled'] = 'disabled';

      UIModule::Select([
        'label'   => 'Provider',
        'name'    => Config::STORAGE_CONFIG_OPTION_KEY .'[provider]',
        'value'   => $provider,
        'attrs'   => $provider_attrs,
        'options' => [
          [
            'label' => 'Select a provider',
            'value' => 'none',
          ],
          [
            'label' => 'AWS S3',
            'value' => 'aws_s3',
          ],
          [
            'label' => 'Google Cloud Storage',
            'value' => 'gcs',
          ]
        ]
      ])->render();

      ?> 
      
      <div bebop-media-storage-provider-settings-panel="aws_s3" 
           style="<?php if($provider !== 'aws_s3') echo 'display:none'; ?>">

        <?php 

        $s3_key_attrs = [];

        if ($config->hasEnv('storage.s3.key'))
          $s3_key_attrs['disabled'] = 'disabled';    

        UIModule::Input([
          'label' => 'AWS Key',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[s3][key]',
          'value' => $config->get('storage.s3.key'),
          'attrs' => $s3_key_attrs,
        ])->render();

        $s3_secret_attrs = [
          'type' => 'password' 
        ];

        if ($config->hasEnv('storage.s3.secret'))
          $s3_secret_attrs['disabled'] = 'disabled';    

        UIModule::Input([
          'label' => 'AWS Secret',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[s3][secret]',
          'value' => $config->get('storage.s3.secret'),
          'attrs' => $s3_secret_attrs,
        ])->render();

        $s3_region_attrs = [];

        if ($config->hasEnv('storage.s3.region'))
          $s3_region_attrs['disabled'] = 'disabled';    

        UIModule::Select([
          'label'   => 'AWS Region',
          'name'    => Config::STORAGE_CONFIG_OPTION_KEY .'[s3][region]',
          'value'   => $config->get('storage.s3.region'),
          'attrs'   => $s3_region_attrs,
          'options' => [
            [
              'label' => 'US Standard (us-east-1)',
              'value' => 'us-east-1',
            ],
            [
              'label' => 'US West - N. California (us-west-1)',
              'value' => 'us-west-1',
            ],
            [
              'label' => 'US West - Oregon (us-west-2)',
              'value' => 'us-west-2',
            ],
            [
              'label' => 'EU - Ireland (eu-west-1)',
              'value' => 'eu-east-1',
            ],
            [
              'label' => 'EU - Frankfurt (eu-central-1)',
              'value' => 'eu-central-1',
            ],
            [
              'label' => 'Asia Pacific - Singapore (ap-southeast-1)',
              'value' => 'ap-southeast-1',
            ],
            [
              'label' => 'Asia Pacific - Sydney (ap-southeast-2)',
              'value' => 'ap-southeast-2',
            ],
            [
              'label' => 'Asia Pacific - Tokyo (ap-northeast-1)',
              'value' => 'ap-northeast-1',
            ],
            [
              'label' => 'South America - Sao Paulo (sa-east-1)',
              'value' => 'sa-east-1',
            ],
          ]
        ])->render();

        $s3_bucket_attrs = [];

        if ($config->hasEnv('storage.s3.bucket'))
          $s3_bucket_attrs['disabled'] = 'disabled'; 

        UIModule::Input([
          'label' => 'Bucket',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[s3][bucket]',
          'value' => $config->get('storage.s3.bucket'),
          'attrs' => $s3_bucket_attrs,
        ])->render();

        $s3_prefix_attrs = [];

        if ($config->hasEnv('storage.s3.prefix'))
          $s3_prefix_attrs['disabled'] = 'disabled'; 

        UIModule::Input([
          'label' => 'Prefix',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[s3][prefix]',
          'value' => $config->get('storage.s3.prefix'),
          'attrs' => $s3_prefix_attrs,
        ])->render();

        ?>

      </div>
      
      <div bebop-media-storage-provider-settings-panel="gcs" 
           style="<?php if($provider !== 'gcs') echo 'display:none'; ?>">

        <?php 

        $gcs_project_id_attrs = [];

        if ($config->hasEnv('storage.gcs.project_id'))
          $gcs_project_id_attrs['disabled'] = 'disabled'; 

        UIModule::Input([
          'label' => 'Project ID',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[gcs][project_id]',
          'value' => $config->get('storage.gcs.project_id'),
          'attrs' => $gcs_project_id_attrs,
        ])->render();

        $gcs_bucket_attrs = [];

        if ($config->hasEnv('storage.gcs.bucket'))
          $gcs_bucket_attrs['disabled'] = 'disabled'; 

        UIModule::Input([
          'label' => 'Bucket',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[gcs][bucket]',
          'value' => $config->get('storage.gcs.bucket'),
          'attrs' => $gcs_bucket_attrs,
        ])->render();

        $gcs_prefix_attrs = [];

        if ($config->hasEnv('storage.gcs.prefix'))
          $gcs_prefix_attrs['disabled'] = 'disabled'; 

        UIModule::Input([
          'label' => 'Prefix',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[gcs][prefix]',
          'value' => $config->get('storage.gcs.prefix'),
          'attrs' => $gcs_prefix_attrs,
        ])->render();

        $gcs_auth_json_attrs = [
          'rows' => 16
        ];

        if ($config->hasEnv('storage.gcs.auth_json'))
          $gcs_auth_json_attrs['disabled'] = 'disabled'; 

        UIModule::Textarea([
          'label' => 'Authentication JSON',
          'name'  => Config::STORAGE_CONFIG_OPTION_KEY .'[gcs][auth_json]',
          'value' => $config->get('storage.gcs.auth_json'),
          'attrs' => $gcs_auth_json_attrs,
        ])->render();

        ?>

      </div>
      
      <br><br>

      <?php 

      UIModule::Button([
        'text'  => 'Save',
        'attrs' => [
          'class' => 'button button-primary'
        ]
      ])->render();

    });
  }

  /**
   * Adds tab with configuration options for CDN
   */
  protected function __addCdnConfigTab()
  {
    $this->admin_page->addTab('CDN', function($data) {

      $config        = Config::getInstance();
      $enabled_attrs = [];
      $domain_attrs  = [];
      $prefix_attrs  = [];

      if ($config->hasEnv('cdn.enabled'))
        $enabled_attrs['disabled'] = 'disabled';

      if ($config->hasEnv('cdn.domain'))
        $domain_attrs['disabled'] = 'disabled';

      if ($config->hasEnv('cdn.prefix'))
        $prefix_attrs['disabled'] = 'disabled';

      UIModule::Checkbox([
        'name'        => Config::CDN_CONFIG_OPTION_KEY .'[enabled]',
        'value'       => $config->get('cdn.enabled'),
        'attrs'       => $enabled_attrs,
        'description' => 'Check this to enable CDN URLs',
        'options'     => [
          [
            'label' => 'Enabled',
            'value' => '1'
          ]
        ]
      ])->render();

      UIModule::Input([
        'label'       => 'Domain',
        'name'        => Config::CDN_CONFIG_OPTION_KEY .'[domain]',
        'value'       => $config->get('cdn.domain'),
        'attrs'       => $domain_attrs,
        'description' => 'Do not add URL scheme (e.g. http:// or https://)',
      ])->render();

      UIModule::Input([
        'label'       => 'Prefix',
        'name'        => Config::CDN_CONFIG_OPTION_KEY .'[prefix]',
        'value'       => $config->get('cdn.prefix'),
        'attrs'       => $prefix_attrs,
      ])->render();

      UIModule::Button([
        'text'  => 'Save',
        'attrs' => [
          'class' => 'button button-primary'
        ]
      ])->render();

    });
  }

  /**
   * Adds tab with AWS Elastic Transcoder configuration options
   */
  protected function __addAwsElasticTranscoderConfigTab()
  {
    $this->admin_page->addTab('AWS Elastic Transcoder', function($data) {

      $config      = Config::getInstance();
      $enabled     = $config->get('elastic_transcoder.enabled');
      $config_file = $config->get('elastic_transcoder.config_file');

      ?>

      <br>
      <h3>Amazon Elastic Transcoder</h3>

      <input type="checkbox" name="<?php echo Config::ELASTIC_TRANSCODER_CONFIG_OPTION_KEY; ?>[enabled]" value="1" <?php if($enabled) echo 'checked="checked"'; ?> <?php if ($config->hasEnv('elastic_transcoder.enabled')) echo 'disabled="disabled"' ?>>
      <label for="">Enabled</label>

      <br><br>
      <label>Configuration file</label><br>
      <input type="text" class="regular-text" name="<?php echo Config::ELASTIC_TRANSCODER_CONFIG_OPTION_KEY; ?>[config_file]" value="<?php echo $config_file; ?>" <?php if ($config->hasEnv('elastic_transcoder.config_file')) echo 'disabled="disabled"' ?>>
      <br>
      <span class="description">This should the a relative path inside the active theme</span>

      <br><br>
      <button class="button button-primary">Save</button>

    <?php });
  }

  /**
   * Returns the AdminPage instance
   *
   * @return Ponticlaro\Bebop\Cms\AdminPage Bebop AdminPage instance
   */
  public function getAdminPageObject()
  {
    return $this->admin_page;
  }

  /**
   * Returns this class instance
   *
   * @return Ponticlaro\Bebop\Media\AdminPage This class instance
   */
  public static function getInstance()
  {
    if (is_null(static::$instance))
      static::$instance = new static;

    return static::$instance;
  }
}
