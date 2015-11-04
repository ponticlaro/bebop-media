<?php

namespace Ponticlaro\Bebop\Media;

use Ponticlaro\Bebop\Common\Collection;

class RegenerateButton {

	/**
	 * Button configuration
	 * 
	 * @var object Ponticlaro\Bebop\Common\Collection
	 */
	protected $config;

	/**
	 * May contain an image object for the current attachment ID
	 * 
	 * @var Ponticlaro\Bebop\Media\Image
	 */
	protected $image;

	/**
	 * Layout presets
	 * 
	 * @var string
	 */
	protected static $layout_presets;

	/**
	 * Button template path
	 * 
	 * @var string
	 */
	protected static $template;

	/**
	 * Instantiates a new regenerate button
	 * 
	 * @param int|string $id Attachment ID
	 */
	public function __construct($id)
	{
		// Set base configuration
		$this->config = new Collection([
			'api_base_url' => '/'. Config::HTTP_API_BASE_URL,
			'id'           => null,
			'layout'       => [],
			'messages'     => [
				'standby' => 'Regenerate',
				'success' => 'Done!',
				'failure' => 'Failed!',
				'loading' => 'Working...'
			]
		]);

		// Define layout presets if not already defined
		if (is_null(static::$layout_presets)) {

			static::$layout_presets = new Collection([
				'default' => [
					'message'     => true,
					'orientation' => 'horizontal',
					'size'        => 'default'
				],
				'compact' => [
					'message'     => false,
					'orientation' => 'horizontal',
					'size'        => 'default'
				],
			]);
		}

		// Define template path if not already defined
		if (is_null(static::$template))
			static::$template = Config::getInstance()->get('plugin_base_path') .'/templates/regenerate-button.php';
		
		// Set configuration for this instance
		$this->setId($id);
		$this->setLayout('default');
	}

	/**
	 * Sets a single layout preset
	 * 
	 * @param string $name    Layout name/id
	 * @param array  $options Layout options
	 */
	public static function setLayoutPreset($name, array $options)
	{
		static::$layout_presets->set($name, array_merge(static::$layout_presets->get('default'), $options));
	}

	/**
	 * Returns the configuration for a single layout preset
	 * 
	 * @param  string $name Layout preset name/id
	 * @return array        Layout preset configuration
	 */
	public static function getLayoutPreset($name)
	{
		return static::$layout_presets->get($name);
	}

	/**
	 * Sets the attachment ID
	 * 
	 * @param  int|string $id Attachment ID
	 * @return object This class instance
	 */
	public function setId($id)
	{
		$this->config->set('id', (int) $id);

		return $this;
	}

	/**
	 * Returns attachment ID
	 * 
	 * @return int Attachment ID
	 */
	public function getId()
	{
		return $this->config->get('id');
	}

	/**
	 * Defines a layout from presets
	 * 
	 * @param  string $preset_name ID of the preset to use
	 * @return object              This class instance
	 */
	public function setLayout($preset_name)
	{
		if (static::$layout_presets->hasKey($preset_name))
			$this->config->set('layout', static::$layout_presets->get($preset_name));

		return $this;
	}

	/**
	 * Returns current layout options
	 * 
	 * @return array Array with all the current layout options
	 */
	public function getLayout()
	{
		return $this->config->get('layout');
	}

	/**
	 * Defined a single layout option
	 * 
	 * @param  string $name  Option name
	 * @param  mixed  $value Option value
	 * @return object This class instance
	 */
	public function setLayoutOption($name, $value)
	{
		$this->config->set('layout.'. $name, $value);

		return $this;
	}

	/**
	 * Returns a single layout option
	 * 
	 * @param  string $name Option name
	 * @return mixed        Option value
	 */
	public function getLayoutOption($name)
	{
		return $this->config->get('layout.'. $name);
	}

	/**
	 * Sets a single status message 
	 * 
	 * @param string $id      Status message ID
	 * @param string $message Status message text
	 */
	public function setStatusMessage($id, $message)
	{
		$this->config->set('messages.'. $id, $message);

		return $this;
	}

	/**
	 * Returns a single status message
	 * 
	 * @param  string $id Status message ID
	 * @return string     Status message text
	 */
	public function getStatusMessage($id)
	{
		return $this->config->get('messages.'. $id);
	}

	/**
	 * Returns the full configuration
	 * 
	 * @return array Array with all the configuration values for this button instance
	 */
	public function getAllConfig()
	{
		return $this->config->getAll();
	}

	/**
	 * Returns button class that should be displayed on load
	 * 
	 * @return string Button class
	 */
	protected function getButtonClass()
	{
		if (is_null($this->image))
			$this->image = new Image($this->getId());

		$status = $this->image->getStatus();
		$class  = 'status-is-'. $status['code'];

		return $class;
	}

	/**
	 * Renders button based on the current configuration
	 * 
	 * @return object This class instance
	 */
	public function render()
	{	
		$id = $this->getId();

		if ($id) {

			$class   = $this->getButtonClass();
			$message = $this->getStatusMessage('standby');
			$config  = $this->getAllConfig();

			include static::$template;
		}

		return $this;
	}
}