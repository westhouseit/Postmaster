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

require_once 'libraries/Email_Parcel.php';
require_once 'libraries/Template_Hook.php';
require_once 'libraries/Template_Task.php';
require_once 'libraries/Template_Notification.php';
require_once 'config/postmaster_config.php';

class Postmaster_mcp {

	public $themes;

	public function __construct()
	{
		ee()->load->helper('url');
		ee()->load->library('postmaster_lib');
		ee()->load->driver('interface_builder');

		if($site_id = ee()->input->post('site_id'))
		{
			ee()->config->set_item('site_id', ($site_id ? $site_id : 1));
		}

		if($site_id = $this->get('site_id'))
		{
			ee()->config->set_item('site_id', ($site_id ? $site_id : 1));
		}

		if(REQ == 'CP')
		{
			ee()->load->library('doctag', array('base_path' => PATH_THIRD.'postmaster/doctags/'));
			ee()->load->library('theme_loader', array(__CLASS__));

			ee()->theme_loader->requirejs = FALSE;
			ee()->theme_loader->css('postmaster');

			ee()->load->driver('channel_data');

			$channels = ee()->channel_data->get_channels()->result_array();

			$statuses      = array();
			$member_groups = array();
			$categories    = array();
			$fields    	   = array();
			$entries 	   = array();

			foreach($channels as $index => $channel)
			{
				$id = $channel['channel_id'];

				$template = new Email_Parcel();

				$template->channel_id = $id;
				$template->channel    = ee()->channel_data->get_channel($id);
				$statuses[$id]        = $template->statuses();
				$fields[$id]          = $template->fields();
				$categories[$id]      = $template->category_tree();
				$member_groups[$id]   = $template->member_groups();
				$entries[$id]   	  = ee()->channel_data->get_channel_entries($id, array(
					'limit' => 100
				))->result_array();
			}

			if(version_compare(APP_VER, '2.8.0', '>='))
			{
				ee()->cp->add_to_foot('
				<script type="text/javascript">
					var Postmaster = {
						channels: '.json_encode($channels).',
						categories: '.json_encode($categories).',
						statuses: '.json_encode($statuses).',
						groups: '.json_encode($member_groups).',
						fields: '.json_encode($fields).',
						entries: '.json_encode($entries).'
					}
				</script>');
			}
			else
			{
				ee()->cp->add_to_head('
				<script type="text/javascript">
					var Postmaster = {
						channels: '.json_encode($channels).',
						categories: '.json_encode($categories).',
						statuses: '.json_encode($statuses).',
						groups: '.json_encode($member_groups).',
						fields: '.json_encode($fields).',
						entries: '.json_encode($entries).'
					}
				</script>');
			}
		}
	}

	public function index()
	{
		ee()->theme_loader->javascript('postmaster');
		ee()->theme_loader->javascript('qtip');
		ee()->theme_loader->css('qtip');

		require_once PATH_THIRD . 'postmaster/libraries/Postmaster_base_delegate.php';

		$delegate = new Postmaster_base_delegate();
		$delegate->suffix   = '_postmaster_delegate';
		$delegate->basepath = PATH_THIRD . 'postmaster/delegates/';

		$hooks = ee()->postmaster_model->get_hooks()->result_array();

		foreach($hooks as $index => $value)
		{
			$hooks[$index]['edit_url']      = $this->cp_url('hook').'&id='.$value['id'];
			$hooks[$index]['delete_url']    = $this->cp_url('delete_hook_action').'&id='.$value['id'];
			$hooks[$index]['duplicate_url'] = $this->cp_url('duplicate_hook_action').'&id='.$value['id'];

			$hooks[$index] = (object) $hooks[$index];
		}

		$tasks = ee()->postmaster_model->get_tasks()->result_array();

		foreach($tasks as $index => $value)
		{
			$tasks[$index]['edit_url']      = $this->cp_url('task').'&id='.$value['id'];
			$tasks[$index]['delete_url']    = $this->cp_url('delete_task_action').'&id='.$value['id'];
			$tasks[$index]['duplicate_url'] = $this->cp_url('duplicate_task_action').'&id='.$value['id'];
			$tasks[$index]['ping_url'] 	    = (int) $value['enable_cron'] == 1 ? $this->current_url('ACT', ee()->channel_data->get_action_id(__CLASS__, 'task_action')).'&id='.$value['id'] : 'N/A';


			$tasks[$index] = (object) $tasks[$index];
		}

		$notifications = ee()->postmaster_model->get_notifications()->result_array();

		foreach($notifications as $index => $value)
		{
			$notifications[$index]['edit_url']      = $this->cp_url('notification').'&id='.$value['id'];
			$notifications[$index]['delete_url']    = $this->cp_url('delete_notification_action').'&id='.$value['id'];
			$notifications[$index]['duplicate_url'] = $this->cp_url('duplicate_notification_action').'&id='.$value['id'];
			$notifications[$index]['ping_url'] 	    = $this->current_url('ACT', ee()->channel_data->get_action_id(__CLASS__, 'notification_action')).'&id='.$value['id'];

			$notifications[$index] = (object) $notifications[$index];
		}

		$vars = array(
			'theme_url' => ee()->theme_loader->theme_url(),
			'themes'  	=> $this->themes,
			'parcels' 	=> ee()->postmaster_model->get_parcels(),
			'hooks'     => $hooks,
			'tasks'     => $tasks,
			'notifications'     => $notifications,
			'create_parcel_url' => $this->cp_url('create_template'),
			'add_hook_url' => $this->cp_url('hook'),
			'add_task_url' => $this->cp_url('task'),
			'edit_hook_action' => $this->current_url('ACT', ee()->channel_data->get_action_id(__CLASS__, 'edit_hook_action')),
			'add_notification_url' => $this->cp_url('notification'),
			'edit_hook_action' => $this->current_url('ACT', ee()->channel_data->get_action_id(__CLASS__, 'edit_notification_action')),
			'delegates'	=> $delegate->get_delegates(FALSE, PATH_THIRD.'postmaster/delegates'),
			'doctag_url' => $this->cp_url('doctag'),
			'ping_url'	=> $this->current_url('ACT', ee()->channel_data->get_action_id(__CLASS__, 'send_email')),
			'lang'		=> array(
				'documentation' => lang('postmaster_documentation')
			)
		);

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', ee()->lang->line('postmaster_module_name'));
		}
		else
		{
			ee()->view->cp_page_title = ee()->lang->line('postmaster_module_name');
		}

		ee()->cp->set_right_nav(array(
			'postmaster_documentation'     => $this->cp_url('doctag').'&id=Core'
			/* 'Text Editor Settings' => $this->cp_url('editor_settings') */
		));

		return ee()->load->view('index', $vars, TRUE);
	}

	public function notification()
	{
		ee()->theme_loader->javascript('postmaster');
		ee()->theme_loader->javascript('codemirror');
		ee()->theme_loader->javascript('modes');
		ee()->theme_loader->javascript('qtip');
		ee()->theme_loader->css('codemirror');
		ee()->theme_loader->css('qtip');

    	setcookie('postmaster_parcel_message', '', strtotime('+1 week'), '/');

    	$saved_data = array();

    	if($notification_id = ee()->input->get('id'))
    	{
	    	$saved_data = ee()->postmaster_model->get_notification($notification_id)->row_array();
	    	$saved_data['settings'] = json_decode($saved_data['settings']);
    	}

		$vars = array(
			'xid'      => XID_SECURE_HASH,
			'ib_path'  => ee()->theme_loader->theme_url().'postmaster/javascript/InterfaceBuilder.js',
			'template' => new Template_Notification($saved_data)
		);

		$notification = $vars['template']->notifications(TRUE);

		$title = 'New Notification';

		if(ee()->input->get('id'))
		{
			$title = 'Edit Notification';
		}

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', $title);
		}
		else
		{
			ee()->view->cp_page_title = $title;
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home'  => $this->cp_url('index'),
			'Text Editor Settings' => $this->cp_url('editor_settings'),
		));

		if(version_compare(APP_VER, '2.8.0', '>='))
		{
			ee()->cp->add_to_foot(
				'<script type="text/javascript">
					Postmaster.editorSettings = '.$vars['template']->editor_settings.';
					Postmaster.settings       = '.json_encode($vars['template']->settings).'
					Postmaster.parser		  = "'.$vars['template']->parser_url.'";
				</script>'
			);
		}

		return ee()->load->view('notification', $vars, TRUE);
	}

	public function hook()
	{
		ee()->theme_loader->javascript('postmaster');
		ee()->theme_loader->javascript('codemirror');
		ee()->theme_loader->javascript('modes');
		ee()->theme_loader->javascript('qtip');
		ee()->theme_loader->css('codemirror');
		ee()->theme_loader->css('qtip');

    	setcookie('postmaster_parcel_message', '', strtotime('+1 week'), '/');

    	$saved_data = array();

    	if($hook_id = ee()->input->get('id'))
    	{
	    	$saved_data = ee()->postmaster_model->get_hook($hook_id)->row_array();
	    	$saved_data['settings'] = json_decode($saved_data['settings']);
    	}

		$vars = array(
			'xid'      => XID_SECURE_HASH,
			'ib_path'  => ee()->theme_loader->theme_url().'postmaster/javascript/InterfaceBuilder.js',
			'template' => new Template_Hook($saved_data)
		);

		$title = 'New Hook';

		if(ee()->input->get('id'))
		{
			$title = 'Edit Hook';
		}

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', $title);
		}
		else
		{
			ee()->view->cp_page_title = $title;
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home'  => $this->cp_url('index'),
			'Text Editor Settings' => $this->cp_url('editor_settings'),
		));

		if(version_compare(APP_VER, '2.8.0', '>='))
		{
			ee()->cp->add_to_foot(
				'<script type="text/javascript">
					Postmaster.editorSettings = '.$vars['template']->editor_settings.';
					Postmaster.settings       = '.json_encode($vars['template']->settings).'
					Postmaster.parser		  = "'.$vars['template']->parser_url.'";
				</script>'
			);
		}

		return ee()->load->view('hook', $vars, TRUE);
	}

	public function task()
	{
		ee()->theme_loader->javascript('postmaster');
		ee()->theme_loader->javascript('qtip');
		ee()->theme_loader->css('qtip');

    	$saved_data = array();

    	if($task_id = ee()->input->get('id'))
    	{
	    	$saved_data = ee()->postmaster_model->get_task($task_id)->row_array();
	    	$saved_data['settings'] = json_decode($saved_data['settings']);
    	}

    	ee()->load->library('postmaster_task', array(
			'base_path' => PATH_THIRD.'postmaster/tasks/'
		));

		$vars = array(
			'xid'      => XID_SECURE_HASH,
			'ib_path'  => ee()->theme_loader->theme_url().'postmaster/javascript/InterfaceBuilder.js',
			'template' => new Template_Task($saved_data)
		);

		$title = 'New Task';

		if(ee()->input->get('id'))
		{
			$title = 'Edit Task';
		}

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', $title);
		}
		else
		{
			ee()->view->cp_page_title = $title;
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home'  => $this->cp_url('index'),
			'Text Editor Settings' => $this->cp_url('editor_settings'),
		));

		if(version_compare(APP_VER, '2.8.0', '>='))
		{
			ee()->cp->add_to_foot(
				'<script type="text/javascript">
					Postmaster.editorSettings = '.$vars['template']->editor_settings.';
					Postmaster.settings       = '.json_encode($vars['template']->settings).'
					Postmaster.parser		  = "'.$vars['template']->parser_url.'";
				</script>'
			);
		}

		return ee()->load->view('task', $vars, TRUE);
	}

	public function doctag()
	{
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', 'Documentation');
		}
		else
		{
			ee()->view->cp_page_title = 'Documentation';
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home' => $this->cp_url('index')
		));

		return ee()->doctag->page(ee()->input->get('id'));
	}

	public function parser()
	{
		ee()->load->library('postmaster_lib');
		ee()->load->driver('channel_data');

		if($message = $this->get('message'))
		{
			$message = ee()->postmaster_model->save_preview($message);
		}
		else
		{
			$message = ee()->postmaster_model->get_preview()->row('data');
		}

		$parcel = (object) array(
			'to_name'            => $this->get('to_name'),
			'to_email'           => $this->get('to_email'),
			'from_name'          => $this->get('from_name'),
			'from_email'         => $this->get('from_email'),
			'cc'                 => $this->get('cc'),
			'bcc'                => $this->get('bcc'),
			'subject'            => $this->get('subject'),
			'message'            => $message,
			'subject'            => $this->get('subject'),
			'post_date_specific' => $this->get('post_date_specific'),
			'post_date_relative' => $this->get('post_date_relative'),
			'send_every'         => $this->get('send_every'),
			'extra_conditionals' => $this->get('extra_conditionals'),
		);

		$entry_id  = $this->get('entry_id');
		$member_id = FALSE;

		if(!empty($entry_id))
		{
			$entries = ee()->channel_data->get_channel_entry($entry_id);

			$parcel->entry = $entries ? $entries->row() : (object) array();
		}
		else
		{
			$parcel->entry = (object) array();
		}

		if(isset($parcel->entry->author_id))
		{
			$member_id = $parcel->entry->author_id;
		}

		$parcel_object = ee()->postmaster_lib->parse($parcel, $member_id, array(), ($this->get('prefix') ? $this->get('prefix') : 'parcel'));

		if(empty($parcel_object->message)) {
			$parcel_object->message = '
				<h2>Postmaster</h2>
				<h3>Sample Preview</h3>
				<p>Enter some code in the text editor below to generate a live preview. Anything you would expect to be able to use in a standard template, will also work here.</p>';
		}

		exit($parcel_object->message);
	}

	public function notification_action()
	{
		ee()->load->library('postmaster_notification', array(
			'base_path' => PATH_THIRD.'postmaster/notifications/'
		));

		$id = ee()->input->get_post('id');

		if(!$id)
		{
			return;
		}

		$notification = ee()->postmaster_model->get_notification($id);

		if($notification->num_rows() == 0)
		{
			return;
		}

		$notification = $notification->row();

		if((int) $notification->enabled == 0)
		{
			return;
		}

		// $notification->settings = json_decode($notification->settings);

		$obj = ee()->postmaster_notification->load($notification->notification, $notification);
		$obj->set_notification($notification);
		$obj->set_settings(json_decode($notification->settings));

		ee()->postmaster_notification->trigger($obj);
	}

	public function task_action()
	{
		ee()->load->library('postmaster_task', array(
			'base_path' => PATH_THIRD.'postmaster/tasks/'
		));

		$id = ee()->input->get_post('id');

		if(!$id)
		{
			return;
		}

		$task = ee()->postmaster_model->get_task($id);

		if($task->num_rows() == 0)
		{
			return;
		}

		$task = $task->row();

		if((int) $task->enabled == 0)
		{
			return;
		}

		$task->settings = json_decode($task->settings);

		// var_dump($notification->settings);exit();

		$obj = ee()->postmaster_task->load($task->task, $task);
		$obj->set_task($task);

		ee()->postmaster_task->ping($obj);
	}

	public function delete_task_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url('index');

		ee()->postmaster_model->delete_task($id);

		ee()->functions->redirect($url);
	}

	public function delete_notification_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url('index');

		ee()->postmaster_model->delete_notification($id);

		ee()->functions->redirect($url);
	}

	public function delete_hook_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url('index');

		ee()->postmaster_model->delete_hook($id);

		ee()->functions->redirect($url);
	}

	public function delete_parcel_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url(ee()->input->get('return'));

		ee()->postmaster_model->delete_parcel($id);

		ee()->functions->redirect($url);
	}

	public function duplicate_hook_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url('index');

		ee()->postmaster_model->duplicate_hook($id);

		ee()->functions->redirect($url);
	}

	public function duplicate_task_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url('index');

		ee()->postmaster_model->duplicate_task($id);

		ee()->functions->redirect($url);
	}

	public function duplicate_notification_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url('index');

		ee()->postmaster_model->duplicate_notification($id);

		ee()->functions->redirect($url);
	}

	public function duplicate_parcel_action()
	{
		$id  = $this->get('id');
		$url = $this->cp_url(ee()->input->get('return'));

		ee()->postmaster_model->duplicate_parcel($id);

		ee()->functions->redirect($url);
	}

	public function save_editor_settings()
	{
		ee()->load->library('postmaster_lib');
		ee()->postmaster_model->save_editor_settings($_POST['setting']);

		ee()->functions->redirect($_POST['return']);
	}

	public function editor_settings()
	{
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', 'Text Editor Configuration');
		}
		else
		{
			ee()->view->cp_page_title = 'Text Editor Configuration';
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home' => $this->cp_url('index'),
			'Create New Template' => $this->cp_url('create_template'),
		));

		$vars = array(
			'xid' => XID_SECURE_HASH,
		);

		$settings = ee()->postmaster_model->get_editor_settings();

		$options = array(
			'true'  => 'True',
			'false' => 'False'
		);

		foreach($settings as $key => $value)
		{
			$vars['settings'][$key] = $value;
			$vars['settings'][$key.'_input'] = '<input type="text" name="setting['.$key.']" id="'.$key.'" value="'.$value.'" />';
			$vars['settings'][$key.'_text'] = '<textarea name="setting['.$key.']" id="'.$key.'">'.$value.'</textarea>';
			$vars['settings'][$key.'_bool'] = form_dropdown('setting['.$key.']', $options, $value);
		}

		$options = array();

		foreach(ee()->postmaster_lib->get_themes() as $theme)
		{
			$options[$theme->value] = $theme->name;
		}

		$vars['settings']['theme_dropdown'] = form_dropdown('setting[theme]', $options, $vars['settings']['theme']);

		$vars['return'] = $this->cp_url('editor_settings');
		$vars['action'] = $this->current_url('ACT', ee()->channel_data->get_action_id('Postmaster_mcp', 'save_editor_settings'));

		$vars['json'] 	= ee()->postmaster_model->get_editor_settings_json();

		return ee()->load->view('editor-settings', $vars, TRUE);
	}

	public function create_template()
	{
		ee()->theme_loader->javascript('postmaster');
		ee()->theme_loader->javascript('codemirror');
		ee()->theme_loader->javascript('modes');
		ee()->theme_loader->javascript('qtip');
		ee()->theme_loader->css('codemirror');
		ee()->theme_loader->css('qtip');

    	setcookie('postmaster_parcel_message', '', strtotime('+1 week'), '/');

		$channels    = ee()->channel_data->get_channels()->result();
		$field_data  = array();
		$status_data = array();
		$member_data = array();

		foreach($channels as $channel)
		{
			$id 	= $channel->channel_id;
			$parcel = new Email_Parcel();

			$field_data[$id]  = $parcel->fields();
			$status_data[$id] = $parcel->statuses();
			$member_data[$id] = $parcel->member_groups();
		}

		$vars = array(
			'xid'           => XID_SECURE_HASH,
			'ib_path'	    => ee()->theme_loader->theme_url().'postmaster/javascript/InterfaceBuilder.js',
			'channels'		=> json_encode($channels),
			'fields'		=> json_encode($field_data),
			'statuses'		=> json_encode($status_data),
			'member_groups' => json_encode($member_data),
		);

		$vars['template'] = new Email_Parcel();

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', 'New Parcel');
		}
		else
		{
			ee()->view->cp_page_title = 'New Parcel';
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home' => $this->cp_url('index'),
			'Text Editor Settings' => $this->cp_url('editor_settings'),
		));

		if(version_compare(APP_VER, '2.8.0', '>='))
		{
			ee()->cp->add_to_foot(
				'<script type="text/javascript">
					Postmaster.editorSettings = '.$vars['template']->editor_settings.';
					Postmaster.settings       = '.json_encode($vars['template']->settings).'
					Postmaster.parser		  = "'.$vars['template']->parser_url.'";
				</script>'
			);
		}

		return ee()->load->view('template', $vars, TRUE);
	}

	public function edit_parcel()
	{
		ee()->theme_loader->javascript('postmaster');
		ee()->theme_loader->javascript('codemirror');
		ee()->theme_loader->javascript('modes');
		ee()->theme_loader->javascript('qtip');
		ee()->theme_loader->css('postmaster');
		ee()->theme_loader->css('codemirror');
		ee()->theme_loader->css('qtip');


		$channels    = ee()->channel_data->get_channels()->result();
		$field_data  = array();
		$status_data = array();
		$member_data = array();
		$categories  = array();

		foreach($channels as $channel)
		{
			$id 	= $channel->channel_id;
			$parcel = new Email_Parcel();

			$field_data[$id]  = $parcel->fields();
			$status_data[$id] = $parcel->statuses();
			$member_data[$id] = $parcel->member_groups();
			$categories[$id]  = $parcel->category_tree();
		}

		$vars = array(
			'xid'      		=> XID_SECURE_HASH,
			'ib_path'	    => ee()->theme_loader->theme_url().'postmaster/javascript/InterfaceBuilder.js',
			'channels'		=> json_encode($channels),
			'fields'		=> json_encode($field_data),
			'statuses'		=> json_encode($status_data),
			'member_groups' => json_encode($member_data),
			'categories'	=> json_encode((array)$categories),
		);

		$parcel = ee()->postmaster_model->get_parcel($this->get('id'));

		$vars['template'] = new Email_Parcel($parcel);

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			ee()->cp->set_variable('cp_page_title', 'Edit Parcel');
		}
		else
		{
			ee()->view->cp_page_title = 'Edit Parcel';
		}

		ee()->cp->set_right_nav(array(
			'&larr; Back to Home' => $this->cp_url('index'),
			'Text Editor Settings' => $this->cp_url('editor_settings'),
		));

		if(version_compare(APP_VER, '2.8.0', '>='))
		{
			ee()->cp->add_to_foot(
				'<script type="text/javascript">
					Postmaster.editorSettings = '.$vars['template']->editor_settings.';
					Postmaster.settings       = '.json_encode($vars['template']->settings).'
					Postmaster.parser		  = "'.$vars['template']->parser_url.'";
				</script>'
			);
		}

		return ee()->load->view('template', $vars, TRUE);
	}

	public function blacklist()
	{
		ee()->postmaster_model->blacklist(ee()->input->get_post('email'));
	}


	public function unsubscribe()
	{
		ee()->postmaster_model->unsubscribe(ee()->input->get_post('email'));
	}

	public function create_hook_action()
	{
		return $this->_hook_action('create');
	}

	public function edit_hook_action()
	{
		return $this->_hook_action('edit');
	}

	private function _hook_action($method)
	{
		if($method == 'add')
		{
			$method = 'create';
		}

		$method .= '_hook';

		ee()->load->library('postmaster_lib');

		$parcel          = array(
			'title'              => $this->post('title', TRUE),
			'to_name'            => $this->post('to_name', TRUE),
			'to_email'           => $this->post('to_email', TRUE),
			'from_name'          => $this->post('from_name', TRUE),
			'from_email'         => $this->post('from_email', TRUE),
			'reply_to'           => $this->post('reply_to', TRUE),
			'priority'           => $this->post('priority', TRUE),
			'cc'                 => $this->post('cc', TRUE),
			'bcc'                => $this->post('bcc', TRUE),
			'subject'            => $this->post('subject', TRUE),
			'message'            => $this->post('message', TRUE),
			'html_message'       => $this->post('message', TRUE),
			'plain_message'      => $this->plain_text($this->post('message', TRUE)),
			'installed_hook'     => $this->post('installed_hook', TRUE),
			'user_defined_hook'  => $this->post('user_defined_hook', TRUE),
			'priority' 			 => $this->post('priority', TRUE),
			'post_date_specific' => $this->post('post_date_specific', TRUE),
			'post_date_relative' => $this->post('post_date_relative', TRUE),
			'send_every'         => $this->post('send_every', TRUE),
			'extra_conditionals' => $this->post('extra_conditionals'),
			'service'            => $this->post('service', TRUE),
			'enabled' 			 => $this->post('enabled') == '1' ? 1 : 0,
			'settings'           => json_encode($this->post('setting', TRUE))
		);

		if(ee()->input->post('id'))
		{
			ee()->postmaster_model->$method(ee()->input->post('id'), $parcel);
		}
		else
		{
			ee()->postmaster_model->$method($parcel);
		}

		if(version_compare(APP_VER, '2.9.0', '>='))
		{
			return ee()->functions->redirect(str_replace('&amp;', '&', cp_url('addons_modules/show_module_cp', array(
				'module' => 'postmaster',
				'method' => 'index'
			))));
		}
		else
		{
			return ee()->functions->redirect($this->post('return'));
		}
	}

	public function create_notification_action()
	{
		return $this->_notification_action('create');
	}

	public function edit_notification_action()
	{
		return $this->_notification_action('edit');
	}

	private function _notification_action($method)
	{
		if($method == 'add')
		{
			$method = 'create';
		}

		$method .= '_notification';

		ee()->load->library('postmaster_lib');

		$parcel = array(
			'site_id'            => config_item('site_id'),
			'title'              => $this->post('title', TRUE),
			'to_name'            => $this->post('to_name', TRUE),
			'to_email'           => $this->post('to_email', TRUE),
			'from_name'          => $this->post('from_name', TRUE),
			'from_email'         => $this->post('from_email', TRUE),
			'reply_to'           => $this->post('reply_to', TRUE),
			'cc'                 => $this->post('cc', TRUE),
			'bcc'                => $this->post('bcc', TRUE),
			'subject'            => $this->post('subject', TRUE),
			'message'            => $this->post('message', TRUE),
			'html_message'       => $this->post('message', TRUE),
			'plain_message'      => $this->plain_text($this->post('message', TRUE)),
			'notification'	     => $this->post('notification', TRUE),
			'post_date_specific' => $this->post('post_date_specific', TRUE),
			'post_date_relative' => $this->post('post_date_relative', TRUE),
			'send_every'         => $this->post('send_every', TRUE),
			'extra_conditionals' => $this->post('extra_conditionals'),
			'service'            => $this->post('service', TRUE),
			'enabled' 			 => $this->post('enabled') == '1' ? 1 : 0,
			'settings'           => json_encode($this->post('setting', TRUE))
		);

		if(ee()->input->post('id'))
		{
			ee()->postmaster_model->$method(ee()->input->post('id'), $parcel);
		}
		else
		{
			ee()->postmaster_model->$method($parcel);
		}

		if(version_compare(APP_VER, '2.9.0', '>='))
		{
			return ee()->functions->redirect(str_replace('&amp;', '&', cp_url('addons_modules/show_module_cp', array(
				'module' => 'postmaster',
				'method' => 'index'
			))));
		}
		else
		{
			return ee()->functions->redirect($this->post('return'));
		}
	}

	public function create_task_action()
	{
		return $this->_task_action('create');
	}

	public function edit_task_action()
	{
		return $this->_task_action('edit');
	}

	private function _task_action($method)
	{
		if($method == 'add')
		{
			$method = 'create';
		}

		$method .= '_task';

		ee()->load->library('postmaster_lib');

		$parcel = array(
			'site_id'            => config_item('site_id'),
			'title'              => $this->post('title', TRUE),
			'task'               => $this->post('task', TRUE),
			//'service'            => $this->post('service', TRUE),
			'enabled' 			 => $this->post('enabled') == '1' ? 1 : 0,
			'settings'           => json_encode($this->post('setting', TRUE))
		);

		if(ee()->input->post('id'))
		{
			ee()->postmaster_model->$method(ee()->input->post('id'), $parcel);
		}
		else
		{
			ee()->postmaster_model->$method($parcel);
		}

		if(version_compare(APP_VER, '2.9.0', '>='))
		{
			return ee()->functions->redirect(str_replace('&amp;', '&', cp_url('addons_modules/show_module_cp', array(
				'module' => 'postmaster',
				'method' => 'index'
			))));
		}
		else
		{
			return ee()->functions->redirect($this->post('return'));
		}
	}

	public function create_parcel_action()
	{
		return $this->_parcel_action('create');
	}

	public function edit_parcel_action()
	{
		return $this->_parcel_action('edit');
	}

	private function _parcel_action($method)
	{
		if($method == 'add')
		{
			$method = 'create';
		}

		$method .= '_parcel';

		ee()->load->library('postmaster_lib');

		//var_dump($_POST['setting']['SendGridConditional']['field_map']);exit();

		$parcel          = array(
			'channel_id'         => $this->post('channel_id'),
			'title'              => $this->post('title'),
			'to_name'            => $this->post('to_name'),
			'to_email'           => $this->post('to_email'),
			'from_name'          => $this->post('from_name'),
			'from_email'         => $this->post('from_email'),
			'reply_to'           => $this->post('reply_to'),
			'cc'                 => $this->post('cc'),
			'bcc'                => $this->post('bcc'),
			'categories'         => $this->post('category') ? implode('|', $this->post('category')) : NULL,
			'member_groups'      => $this->post('member_group') ? implode('|', $this->post('member_group')) : NULL,
			'statuses'           => $this->post('statuses') ? implode('|', $this->post('statuses')) : NULL,
			'subject'            => $this->post('subject'),
			'message'            => $this->post('message'),
			'html_message'       => $this->post('message', TRUE),
			'plain_message'      => $this->plain_text($this->post('message', TRUE)),
			'trigger'            => is_array($this->post('trigger')) ? implode('|', $this->post('trigger')) : $this->post('trigger'),
			'post_date_specific' => $this->post('post_date_specific'),
			'post_date_relative' => $this->post('post_date_relative'),
			'send_every'         => $this->post('send_every'),
			'service'            => $this->post('service'),
			'extra_conditionals' => $this->post('extra_conditionals'),
			'enabled' 			 => $this->post('enabled') == '1' ? 1 : 0,
			'settings'           => json_encode($this->post('setting')),
			'match_explicitly'    => $this->post('match_explicitly') == 'true' ? true : false,
			'send_once'          => (int) $this->post('send_once')
		);

		ee()->postmaster_model->$method($parcel, $this->post('id'));


		if(version_compare(APP_VER, '3.0.0', '>='))
		{
			return ee()->functions->redirect($this->post('return'));
			//return ee()->functions->redirect(ee('CP/URL')->make('addons/settings/postmaster'));
		}
		elseif(version_compare(APP_VER, '2.9.0', '>='))
		{
			return ee()->functions->redirect(str_replace('&amp;', '&', cp_url('addons_modules/show_module_cp', array(
				'module' => 'postmaster',
				'method' => 'index'
			))));
		}
		else
		{
			return ee()->functions->redirect($this->post('return'));
		}
	}

	public function send_email()
	{
		require_once APPPATH.'libraries/Template.php';

		ee()->TMPL = new EE_Template();

		$queue = ee()->postmaster_model->get_email_queue();

		foreach($queue->result() as $row)
		{
			ee()->postmaster_lib->send_from_queue($row);
		}
	}

	public function call()
	{
		$params = array();
		$void   = array('service', 'service_method', 'method', 'ACT', 'S', 'D', 'C', 'M', 'module');

		if(ee()->session->userdata('member_id'))
		{
			$service = $this->get('service', TRUE);
			$method  = $this->get('service_method', TRUE);

			foreach(array_merge($_GET, $_POST) as $name => $value)
			{
				if(!in_array($name, $void))
				{
					$params[$name] = $value;
				}
			}

			$service = ee()->postmaster_lib->load_service($service);

			if(!method_exists($service, $method))
			{
				show_error('Error: <b>'.$method.'</b> is not a valid method.');
			}

			call_user_func_array(array($service, $method), $params);
		}
	}

	public function template()
	{
		$member_id = FALSE;
		$entry_id  = $this->get('entry_id');
		$parcel_id = $this->get('parcel_id');

		if(!$entry_id && !$parcel_id)
		{
			return;
		}

		$parcel        = ee()->postmaster_model->get_parcel($parcel_id);
		$parcel->entry = ee()->channel_data->get_channel_entry($entry_id)->row();

		if(isset($parcel->entry->author_id))
		{
			$member_id = $parcel->entry->author_id;
		}

		$parsed_object = ee()->postmaster_lib->parse($parcel, $member_id);

		if($this->get('strip_tags'))
		{
			$parsed_object->message = strip_tags($parsed_object->message);
		}

		exit($parsed_object->message);
	}

	public function plain_text($message)
	{
		return ee()->postmaster_lib->plain_text($message);
	}

	private function post($name)
	{
		$return = ee()->input->post($name);
		$return = $return !== FALSE ? $return : '';

		return $return;
	}

	private function get($name, $require = FALSE)
	{
		$return = ee()->input->get_post($name);

		if($require && !$return)
		{
			show_error('The <b>'.$name.'</b> parameter is required');
		}

		return $return;
	}

	private function cp_url($method = 'index', $useAmp = FALSE)
	{
		return ee()->postmaster_lib->cp_url($method);
	}

	private function current_url($append = '', $value = '')
	{
		return ee()->postmaster_lib->current_url($append, $value);
	}

}
