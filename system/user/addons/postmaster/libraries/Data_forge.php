<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Forge
 *
 * A helper classes to make life easy when it comes to updating add-on
 * data tables.
 *
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.1.2
 * @build		20140618
 */

if(!class_exists('Data_forge'))
{
	class Data_forge {

		public function __construct()
		{
			
			ee()->load->dbforge();
		}

		public function add_column($table_name, $fields)
		{
			ee()->dbforge->add_column($table_name, $fields);
		}

		public function add_field($fields)
		{
			ee()->dbforge->add_field($fields);
		}

		public function add_key($field, $primary = TRUE)
		{
			ee()->dbforge->add_key($field, $primary);
		}

		function create_table($table, $fields)
		{
			foreach($fields as $field_name => $field)
			{
				if(isset($field['primary_key']) && $field['primary_key'])
				{
					$this->add_key($field_name, TRUE);
				}
			}

			$this->add_field($fields);

			ee()->dbforge->create_table($table);
		}

		function drop_column($table, $column)
		{
			ee()->dbforge->drop_column($table, $column);
		}

		function drop_table($table)
		{
			ee()->dbforge->drop_table($table);
		}

		public function field_data($table)
		{
			$fields = ee()->db->query('SHOW COLUMNS FROM `'.ee()->db->dbprefix.$table.'`')->result();

			$field_data = array();
			$matches	= array();

			foreach($fields as $field)
			{
				$meta = array();

				if(preg_match('/^(.*) unsigned$/', $field->Type, $matches))
				{
					$meta['unsigned'] = TRUE;

					$field->Type = $matches[1];
				}

				if(preg_match('/^(.*)\((\d+)\)$/', $field->Type, $matches))
				{
					$meta['constraint'] = (int) $matches[2];

					$field->Type = $matches[1];
				}

				if($field->Null == 'YES')
				{
					$meta['null'] = TRUE;
				}

				if(preg_match('/auto_increment/', $field->Extra, $matches) !== FALSE)
				{
					$meta['auto_increment'] = TRUE;
				}

				if($field->Key)
				{
					if($field->Key == 'PRI')
					{
						$meta['primary_key'] = TRUE;
					}
					else
					{
						$meta['key'] = TRUE;
					}
				}

				$meta['type'] = $field->Type;

				$field_data[$field->Field] = $meta;
			}

			return $field_data;
		}

		public function field_exists($field, $table)
		{
			return ee()->db->field_exists($field, $table);
		}

		public function list_fields($table)
		{
			return ee()->db->list_fields($table);
		}

		public function list_tables()
		{
			return ee()->db->list_tables();
		}

		public function modify_column($table, $fields)
		{
			ee()->dbforge->modify_column($table, $fields);
		}

		public function table_exists($table)
		{
			return ee()->db->table_exists($table);
		}

		function update_table($table, $fields)
		{
			$existing_fields 	 = $this->field_data($table);

			foreach($existing_fields as $field_name => $field)
			{
				if(!array_key_exists($field_name, $fields))
				{
					$this->drop_column($table, $field_name);
				}
			}

			foreach($fields as $field_name => $field)
			{
				$column_data = array($field_name => $field);

				if(!isset($existing_fields[$field_name]))
				{
					$this->add_column($table, $column_data);
				}
				else
				{
					$diff = array_diff($field, $existing_fields[$field_name]);

					if(count($diff) > 0)
					{
						$column_data[$field_name]['name'] = '`'.$field_name.'`';

						$this->modify_column($table, $column_data);
					}
				}
			}
		}

		function update_tables($tables)
		{
			foreach($tables as $table => $fields)
			{
				if($this->table_exists($table))
				{
					$this->update_table($table, $fields);
				}
				else
				{
					$this->create_table($table, $fields);
				}
			}
		}
	}
}
