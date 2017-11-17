<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Postmaster_installer {

	public function __construct()
	{
		
		ee()->load->library('postmaster_lib');

		ee()->load->library('postmaster_hook', array(
			'base_path' => PATH_THIRD . 'postmaster/hooks/'
		));

		ee()->load->library('postmaster_notification', array(
			'base_path' => PATH_THIRD . 'postmaster/notifications/'
		));

		ee()->load->library('postmaster_service', array(
			'base_path' => PATH_THIRD . 'postmaster/services/'
		));

		ee()->load->library('postmaster_task', array(
			'base_path' => PATH_THIRD . 'postmaster/tasks/'
		));
	}

	public function version_update($version)
	{
		// Version Specific Update Routines

		if(version_compare($version, '1.1.99.4', '<'))
		{
			if(!class_exists('Postmaster_lib'))
			{
				require_once(PATH_THIRD.'postmaster/libraries/Postmaster_lib.php');
			}

			ee()->postmaster_lib = new Postmaster_lib();
			ee()->postmaster_model->assign_site_id();
		}

		if(version_compare($version, '1.3.2.1', '<'))
		{
			ee()->db->where('date', '0000-00-00 00:00:00');

			$update_queue = ee()->db->get('postmaster_queue');

			foreach($update_queue->result() as $row)
			{
				$data['date']      = date('Y-m-d H:i:s', $row->gmt_date);
				$data['send_date'] = date('Y-m-d H:i:s', $row->gmt_send_date);

				ee()->db->where('id', $row->id);
				ee()->db->update('postmaster_queue', $data);
			}
		}

		if(version_compare($version, '1.4.1', '>'))
		{
			ee()->db->where('class', 'Postmaster_ext');
			ee()->db->where('method', 'route_hook');
			ee()->db->update('extensions', array(
				'method' => 'trigger_hook'
			));

			ee()->db->where('class', 'Postmaster_ext');
			ee()->db->where('method', 'route_task_hook');
			ee()->db->update('extensions', array(
				'method' => 'trigger_task_hook'
			));
		}
	}

	public function install_action($class, $method)
	{
		$action = array(
			'class'  => $class,
			'method' => $method
		);

		ee()->db->where(array(
			'class'  => $action['class'],
			'method' => $action['method']
		));

		$existing = ee()->db->get('actions');

		if($existing->num_rows() == 0)
		{
			ee()->db->insert('actions', $action);
		}
	}

	public function install_hook($class, $method, $hook, $priority = 10, $settings = '')
	{
		ee()->db->where(array(
			'class'  => $class,
			'method' => $method,
			'hook' 	 => $hook
		));

		$existing = ee()->db->get('extensions');

		if($existing->num_rows() == 0)
		{
			ee()->db->insert(
				'extensions',
				array(
					'class' 	=> $class,
					'method' 	=> $method,
					'hook' 		=> $hook,
					'settings' 	=> $settings,
					'priority' 	=> $priority,
					'version' 	=> POSTMASTER_VERSION,
					'enabled' 	=> 'y',
				)
			);
		}
	}

	public function install()
	{
		return $this->run('install');
	}

	public function update($version)
	{
		return $this->run('update', $version);
	}

	public function uninstall()
	{
		return $this->run('uninstall');
	}

	private function run($method, $version = FALSE)
	{
		$services      = ee()->postmaster_service->get_services();
		$hooks         = ee()->postmaster_hook->get_hooks();
		$notifications = ee()->postmaster_notification->get_notifications();
		$tasks 		   = ee()->postmaster_task->get_tasks();

		foreach(array_merge($services, $hooks, $notifications, $tasks) as $obj)
		{
			if(is_object($obj))
			{
				$obj->$method($version);
			}
		}
	}
}
