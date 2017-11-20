<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Postmaster
 *
 * @package		Postmaster
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/postmaster
 * @version		1.2.0
 * @build		20121217
 */

require 'config/postmaster_config.php';

class Postmaster_ext {

    public $name       		= 'Postmaster';
    public $version        	= POSTMASTER_VERSION;
    public $description    	= 'Easily create e-mail template and automatically generated emails every time an entry is submitted.';
    public $settings_exist 	= 'n';
  	public $docs_url       	= 'http://www.objectivehtml.com';
	public $settings 		= array();
	public $required_by 	= array('module');

	public $new_entry		= TRUE;

	public function __construct()
	{
        $this->settings = array();

        if(isset(ee()->extensions->in_progress) && !empty(ee()->extensions->in_progress))
        {
			ee()->session->set_cache('postmaster', 'in_progress', ee()->extensions->in_progress);
	    }
    }

	public function settings()
	{
		return '';
	}

	public function trigger_task_hook()
	{
		ee()->load->library('postmaster_lib');
		ee()->load->library('postmaster_hook', array(
			'base_path' => PATH_THIRD.'postmaster/hooks/'
		));

		$hook      = ee()->postmaster_lib->get_hook_in_progress();
		$args      = func_get_args();
		$responses = ee()->postmaster_lib->trigger_task_hook($hook, $args);
		$return    = ee()->postmaster_hook->return_data($responses);

		ee()->extensions->end_script = ee()->postmaster_hook->end_script($responses);

		if($return != 'Undefined')
		{
			return $return;
		}

		return;
	}

	public function trigger_hook()
	{
		ee()->load->library('postmaster_lib');
		ee()->load->library('postmaster_hook', array(
			'base_path' => PATH_THIRD.'postmaster/hooks/'
		));

		$hook      = ee()->postmaster_lib->get_hook_in_progress();
		$args      = func_get_args();
		$responses = ee()->postmaster_lib->trigger_hook($hook, $args);
		$return    = ee()->postmaster_hook->return_data($responses);

		ee()->extensions->end_script = ee()->postmaster_hook->end_script($responses);

		if($return !== 'Undefined')
		{
			return $return;
		}

		return;
	}

	public function route_hook()
	{
		return $this->trigger_hook();
	}

	public function entry_submission_start($channel_id, $autosave)
	{

	}

	public function before_channel_entry_save($model, $data)
	{
		$trigger = 'new';

		if(isset($data['entry_id']) && (int) $data['entry_id'] > 0)
		{
			$trigger = 'edit';
		}

		ee()->session->set_cache('postmaster', 'entry_trigger', $trigger);
	}

	public function after_channel_entry_save($model, $data)
	{
    //print_r($data);
    //die();
		ee()->load->library('postmaster_lib');

		//ee()->postmaster_lib->validate_channel_entry($entry_id, $meta, $data);
    ee()->postmaster_lib->validate_channel_entry($data['entry_id'], $data, $data);

		return $data;
	}

	public function trigger_task_hook_hook()
	{
		return $this->trigger_hook();
	}

	public function route_task_hook()
	{
		return $this->trigger_task_hook();
	}

	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @return void
	 */
	function activate_extension()
	{
	    return TRUE;
	}

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return  mixed   void on update / false if none
	 */
	function update_extension($current = '')
	{
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }

	    if ($current < '1.0')
	    {
	        // Update to version 1.0
	    }

	    ee()->db->where('class', __CLASS__);
	    ee()->db->update('extensions', array('version' => $this->version));
	}

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
	    ee()->db->where('class', __CLASS__);
	    ee()->db->delete('extensions');
	}

}
