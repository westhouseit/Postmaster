<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'Base_class.php';

abstract class Template_Base extends Base_class {

	public  $id,
			$title,
			$to_name,
			$to_email,
			$from_name,
			$from_email,
			$reply_to,
			$channel_id,
			$cc,
			$bcc,
			$subject,
			$message,
			$settings,
			$fields,
			$height,
			$default_theme,
			$button,
			$action,
			$return,
			$service,
			$post_date_specific,
			$post_date_relative,
			$send_every,
			$extra_conditionals,
			$parser_url,
			$editor_settings,
			$site_id;


	public function __construct($params = array())
	{
		parent::__construct($params);

		
		$this->site_id         = config_item('site_id');
		$this->parser_url      = $this->current_url('ACT', ee()->channel_data->get_action_id('Postmaster_mcp', 'parser')) . '&site_id='.config_item('site_id');
		$this->editor_settings = ee()->postmaster_model->get_editor_settings_json();
		$this->default_theme   = ee()->postmaster_model->get_editor_settings('theme');
		$this->height          = ee()->postmaster_model->get_editor_settings('height');
		$this->return          = $this->cp_url('index');
	}

	public function themes()
	{
		$this->themes = ee()->postmaster_lib->get_themes();

		return $this->themes;
	}

	public function default_settings()
	{
		$settings = array();

		foreach($this->services() as $service)
		{
			$settings[$service->name] = $service->default_settings();
		}

		return (object) $settings;
	}

	public function services()
	{
		if(!isset(ee()->postmaster_service))
		{
			ee()->load->library('postmaster_service');
		}

		return ee()->postmaster_service->get_services($this->settings);
	}

	protected function cp_url($method = 'index', $useAmp = FALSE)
	{
		return ee()->postmaster_lib->cp_url($method, $useAmp);
	}

	protected function current_url($append = '', $value = '')
	{
		return ee()->postmaster_lib->current_url($append, $value);
	}
}
