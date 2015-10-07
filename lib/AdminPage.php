<?php

namespace Ponticlaro\Bebop\Media;

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
    $this->admin_page = new \Ponticlaro\Bebop\Cms\AdminPage(Config::ADMIN_PAGE_MAIN_TITLE);

    $this->__addMainConfigTab();
    $this->__addCdnConfigTab();
    $this->__addAwsElasticTranscoderConfigTab();
  }

  /**
   * Adds tab with main configuration options
   */
  protected function __addMainConfigTab()
  {
    $this->admin_page->addTab('Main', function($data) {

        $config             = Config::getInstance();
        $enabled            = $config->get('enabled');
        $current_url_scheme = $config->get('url_scheme') ?: 'http';
        $s3_key             = $config->get('storage.s3.key');
        $s3_secret          = $config->get('storage.s3.secret');
        $s3_region          = $config->get('storage.s3.region') ?: 'us-east-1';
        $s3_bucket          = $config->get('storage.s3.bucket');
        $s3_prefix          = $config->get('storage.s3.prefix');

        $url_scheme_options = [
            'http',
            'https'
        ];

        $s3_region_options  = [
            'us-east-1'      => 'US Standard (us-east-1)',
            'us-west-1'      => 'US West - N. California (us-west-1)',
            'us-west-2'      => 'US West - Oregon (us-west-2)',
            'eu-east-1'      => 'EU - Ireland (eu-west-1)',
            'eu-central-1'   => 'EU - Frankfurt (eu-central-1)',
            'ap-southeast-1' => 'Asia Pacific - Singapore (ap-southeast-1)',
            'ap-southeast-2' => 'Asia Pacific - Sydney (ap-southeast-2)',
            'ap-northeast-1' => 'Asia Pacific - Tokyo (ap-northeast-1)',
            'sa-east-1'      => 'South America - Sao Paulo (sa-east-1)',
        ];

        ?>

        <br><br>
        <input type="checkbox" name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[enabled]" value="1" <?php if($enabled) echo 'checked="checked"'; ?> <?php if ($config->hasEnv('enabled')) echo 'disabled="disabled"' ?>>
        <label for="">Enabled</label>
        <br>
        <span class="description">Check this to enabled this plugin</span>
        <br><br>

        <label>URL Scheme</label><br>
        <select name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[url_scheme]" <?php if ($config->hasEnv('url_scheme')) echo 'disabled="disabled"' ?>>
            <?php foreach ($url_scheme_options as $scheme) { ?>
                <option <?php if ($scheme == $current_url_scheme) echo 'selected="selected"'; ?> value="<?php echo $scheme; ?>">
                    <?php echo $scheme; ?>
                </option>
            <?php } ?>
        </select>
        <br><br>

        <h3>Storage</h3>
        <h4>Amazon S3</h4>

        <label>Key</label><br>
        <input type="text" class="regular-text" name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[storage][s3][key]" value="<?php echo $s3_key; ?>" <?php if ($config->hasEnv('storage.s3.key')) echo 'disabled="disabled"' ?>>

        <br><br>
        <label>Secret</label><br>
        <input type="password" class="regular-text" name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[storage][s3][secret]" value="<?php echo $s3_secret; ?>" <?php if ($config->hasEnv('storage.s3.secret')) echo 'disabled="disabled"' ?>>

        <br><br>
        <label>Region</label><br>
        <select class="regular-text" name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[storage][s3][region]" <?php if ($config->hasEnv('storage.s3.region')) echo 'disabled="disabled"' ?>>
            <?php foreach ($s3_region_options as $value => $label) { ?>
                <option <?php if ($value == $s3_region) echo 'selected="selected"'; ?> value="<?php echo $value; ?>">
                    <?php echo $label; ?>
                </option>
            <?php } ?>
        </select>

        <br><br>
        <label>Bucket</label><br>
        <input type="text" class="regular-text" name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[storage][s3][bucket]" value="<?php echo $s3_bucket; ?>"  <?php if ($config->hasEnv('storage.s3.bucket')) echo 'disabled="disabled"' ?>>

        <br><br>
        <label>Prefix</label><br>
        <input type="text" class="regular-text" name="<?php echo Config::MAIN_CONFIG_OPTION_KEY ?>[storage][s3][prefix]" value="<?php echo $s3_prefix; ?>" <?php if ($config->hasEnv('storage.s3.prefix')) echo 'disabled="disabled"' ?>>

        <br><br>
        <button class="button button-primary">Save</button>

    <?php });
  }

  /**
   * Adds tab with configuration options for CDN
   */
  protected function __addCdnConfigTab()
  {
    $this->admin_page->addTab('CDN', function($data) {

      $config  = Config::getInstance();
      $enabled = $config->get('cdn.enabled');
      $domain  = $config->get('cdn.domain');
      $prefix  = $config->get('cdn.prefix');

      ?>

      <br>
      <input type="checkbox" name="<?php echo Config::CDN_CONFIG_OPTION_KEY; ?>[enabled]" value="1" <?php if($enabled) echo 'checked="checked"'; ?> <?php if ($config->hasEnv('cdn.enabled')) echo 'disabled="disabled"' ?>>
      <label for="">Enabled</label>
      <br>
      <span class="description">Check this to enabled CDN URLs</span>

      <br><br>
      <label>Domain</label><br>
      <input type="text" class="regular-text" name="<?php echo Config::CDN_CONFIG_OPTION_KEY; ?>[domain]" value="<?php echo $domain; ?>" <?php if ($config->hasEnv('cdn.domain')) echo 'disabled="disabled"' ?>>
      <br>
      <span class="description">Do not add URL scheme (e.g. http:// or https://)</span>

      <br><br>
      <label>Prefix</label><br>
      <input type="text" class="regular-text" name="<?php echo Config::CDN_CONFIG_OPTION_KEY; ?>[prefix]" value="<?php echo $prefix; ?>" <?php if ($config->hasEnv('cdn.prefix')) echo 'disabled="disabled"' ?>>

      <br><br>
      <button class="button button-primary">Save</button>

    <?php });
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
