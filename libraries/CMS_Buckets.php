<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Buckets extends CMS_Admin
{	
	//
	// Constructor …
	//
	function __construct()
	{
		parent::__construct();
		$this->load->helper('array');
		$this->load->helper('form');
		$this->load->model('cms_blocks_model');
		$this->load->model('cms_media_model');
		$this->load->model('cms_bucketdata_model');
		
		// Load bucket
		$this->load->model('cms_buckets_model');
		if(! $bucket = $this->cms_buckets_model->get_by_id($this->uri->segment(4)))
		{
			show_404();
		}
		get_instance()->data['bucket'] = $bucket;
		
		// Make sure the bucket table exists
		get_instance()->data['table'] = $this->data['cms']['table_base'] . ucfirst($bucket['BucketsName']);
		if(! $this->db->table_exists($this->data['table']))
		{
			show_404();
		} 
		get_instance()->data['prefix'] = str_ireplace($this->data['cms']['table_base'], '', $this->data['table']); 
	}
	
	//
	// List view for a bucket.
	//
	function listview()
	{					
		$this->load->view('cms/templates/app-header', $this->data);
		$this->load->view("cms/buckets/listview", $this->data);
		$this->load->view('cms/templates/app-footer', $this->data);
	}
	
	//
	// Delete.
	//
	function delete()
	{	
		$this->db->where($this->data['prefix'] . 'Id', $this->uri->segment(5));
		$this->db->delete($this->data['table']);
		$this->_delete_relations($this->uri->segment(5), NULL, get_instance()->data['bucket']['BucketsName']);
		$this->_delete_relations(NULL, get_instance()->data['bucket']['BucketsName'], NULL, $this->uri->segment(5));
		redirect($this->data['cms']['cp_base'] . '/buckets/listview/' . $this->uri->segment(4));
	}
	
	//
	// Add a bucket entry.
	//
	function add()
	{			
		get_instance()->data['widgettext'] = 'Add New ' . cms_depluralize($this->data['prefix']);
		get_instance()->data['helpertext'] = 'To add a new ' . cms_depluralize($this->data['prefix']) . ' fill out the field below and click "save"';
		get_instance()->data['type'] = 'add';
		
		$this->_add_edit_shared_func();
	}
	
	//
	// Edit a bucket entry.
	//
	function edit()
	{
		get_instance()->data['widgettext'] = 'Edit ' . cms_depluralize($this->data['prefix']);
		get_instance()->data['helpertext'] = 'To edit a the ' . cms_depluralize($this->data['prefix']) . ' fill out the field below and click "save"';
		get_instance()->data['type'] = 'edit';
		
		// Get data
		$this->db->where($this->data['prefix'] . 'Id', $this->uri->segment(5));
		get_instance()->data['data'] = $this->db->get($this->data['table'])->row_array();
		
		// Add formating to the data.
		if(isset($this->data['data'][$this->data['prefix'] . 'Extra']))
		{
			get_instance()->data['data'][$this->data['prefix'] . 'Extra'] = json_decode(get_instance()->data['data'][$this->data['prefix'] . 'Extra'], TRUE);
		} else
		{
			get_instance()->data['data'][$this->data['prefix'] . 'Extra'] = array();
		}
		
		// See if we need to include any media info as well
		foreach($this->data['bucket']['BucketsFields'] AS $key => $row)
		{
			if(isset($this->data['data'][$key]) && 
					(($row['type'] == 'cms-image') || ($row['type'] == 'cms-image-crop')))
			{
				get_instance()->data['data'][$key . '_media'] = $this->cms_media_model->get_by_id($this->data['data'][$key]);
			}
		}
		
		$this->_add_edit_shared_func(true);
	}
	
	//
	// Move an entry up.
	//
	function move_up()
	{
		$id = $this->uri->segment(5);
		$this->db->order_by($this->data['prefix'] . 'Order');
		$data = $this->db->get($this->data['table'])->result_array();
		$c = 0;
		$last = false;
		foreach($data AS $key => $row)
		{
			$q[$this->data['prefix'] . 'Order'] = $c;
			$this->db->where($this->data['prefix'] . 'Id', $row[$this->data['prefix'] . 'Id']);
			$this->db->update($this->data['table'], $q);
			
			if($row[$this->data['prefix'] . 'Id'] == $id)
			{
				$c++; 
				$q[$this->data['prefix'] . 'Order'] = $c;
				$this->db->where($this->data['prefix'] . 'Id', $last[$this->data['prefix'] . 'Id']);
				$this->db->update($this->data['table'], $q);
			}
			
			$last = $row;
			$c++;
		}
		redirect($this->data['cms']['cp_base'] . '/buckets/listview/' . $this->uri->segment(4));
	}
	
	// ------------------ Internal Helper Functions ---------------- //
	
	//
	// Shared functionality between add / edit.
	//
	private function _add_edit_shared_func($update = FALSE)
	{
		// Get the fields for table.
		get_instance()->data['fields'] = $this->db->field_data($this->data['table']);
		get_instance()->data['skip'] = array('Id', 'UpdatedAt', 'CreatedAt', 'Status', 'Order');
		get_instance()->data['relations'] = array();
	
		// Detect Enums.
		foreach($this->data['fields'] AS $key => $row)
		{
			// Deal with look ups. 
			if(isset(get_instance()->data['bucket']['BucketsLookUps'][$row->name]))
			{
				$this->_do_looksup(get_instance()->data['bucket']['BucketsLookUps'][$row->name], $row, $key);
			}
			
			// Deal with Enums
			if(($row->type == 'enum') && ($row->name != $this->data['prefix'] . 'Status'))
			{
				$sql = "SHOW COLUMNS FROM " . $this->data['table'] . " WHERE Field = '" . $row->name . "'";
				$d = $this->db->query($sql)->row_array();
				$e = $d['Type'];
				$e = str_ireplace('enum(', '', $e);
				$e = str_ireplace(')', '', $e);
				$e = str_ireplace("'", '', $e);
				$e = explode(',', $e);
				get_instance()->data['fields'][$key]->enums = array();
				foreach($e AS $key2 => $row2)
				{
					get_instance()->data['fields'][$key]->enums[$row2] = $row2; 
				}
			}
		}
	
		// Manage bucket relations.
		$relations = get_instance()->data['bucket']['BucketsRelations'];
		if(! empty($relations))
		{
			get_instance()->data['relations'] = json_decode($relations, TRUE);
			foreach($this->data['relations'] AS $key => $row)
			{
				// Get options to relationship.
				get_instance()->data['relations'][$key]['options'] = array();
				get_instance()->db->order_by($row['table'] . 'Title');
				$o = get_instance()->db->get($this->data['cms']['table_base'] . $row['table'])->result_array();
				foreach($o AS $key2 => $row2)
				{ 
					get_instance()->data['relations'][$key]['options'][$row2[$row['table'] . 'Id']] = $row2[$row['table'] . 'Title'];
					get_instance()->data['relations'][$key]['tags'][] = $row2[$row['table'] . 'Title'];
				}
				
				// Get selected
				get_instance()->data['relations'][$key]['selected'] = array(); 
				if(get_instance()->data['type'] == 'edit')
				{
					$this->load->model('cms_relations_model');
					$this->cms_relations_model->set_bucket(get_instance()->data['bucket']['BucketsName']);
					$this->cms_relations_model->set_table($row['table']);
					$this->cms_relations_model->set_entry($this->uri->segment(5));
					$d = $this->cms_relations_model->get();
					foreach($d AS $key2 => $row2)
					{
						get_instance()->data['relations'][$key]['selected'][] = $row2['RelationsTableId'];
					}
				}
			}
		}
	
		// Manage posted data.
		if($this->input->post('submit'))
		{
			$this->load->library('form_validation');
			
			// Set validation
			foreach($this->data['fields'] AS $key => $row)
			{	
				if(in_array(str_ireplace($this->data['prefix'], '', $row->name), $this->data['skip'])) { continue; }
				$q[$row->name] = $this->input->post($row->name);
				
				if($row->name == $this->data['prefix'] . 'Title')
				{
					$this->form_validation->set_rules($row->name, str_ireplace($this->data['prefix'], '', $row->name), 'trim|required');
				} else
				{
					$this->form_validation->set_rules($row->name, str_ireplace($this->data['prefix'], '', $row->name), 'trim');
				}
			}
				
			// Set validation on any relations.
			foreach($this->data['relations'] AS $key => $row)
			{
			  $this->form_validation->set_rules($row['table'], $row['name'], '');
			}
			
			$this->form_validation->set_rules($this->data['prefix'] . 'Status', 'Status', 'trim|required');
			
			// Deal with any date options.
			if($this->input->post('dates') && is_array($_POST['dates']))
			{
			  foreach($_POST['dates'] AS $key => $row)
			  {
			  	if(! isset($_POST[$row]))
			  	{
			  		continue;
			  	}
			  	
			  	$q[$row] = date('Y-m-d', strtotime($_POST[$row]));
			  }
			}
			
			// Deal with any pre validation formatting. 
			$q = $this->_do_pre_validation_formatting($q);
			
			// Validate the post.
			if(get_instance()->form_validation->run() != FALSE)
			{		
				$q[$this->data['prefix'] . 'Status'] = $this->input->post($this->data['prefix'] . 'Status');
						
				// Do a few special things for users.
				if($this->data['bucket']['BucketsName'] == 'Users')
				{
					$q = $this->_do_users($q);
				}
				
				// Deal with an extra col. Make it Json.
				if(isset($q[$this->data['prefix'] . 'Extra']))
				{
					$q[$this->data['prefix'] . 'Extra'] = json_encode($q[$this->data['prefix'] . 'Extra']);
				}
					
				if($update)
				{
					// Hook just before insert.
					if(isset($this->data['cms']['cp_hooks']['bucket_before_update']))
					{
						if(! empty($this->data['cms']['cp_hooks']['bucket_before_update']['library']))
						{
							get_instance()->load->library($this->data['cms']['cp_hooks']['bucket_before_update']['library']);
							$q = get_instance()->{strtolower($this->data['cms']['cp_hooks']['bucket_before_update']['library'])}->{$this->data['cms']['cp_hooks']['bucket_before_update']['method']}($this->data['prefix'], $q, $this->uri->segment(5));
						}
					}
				
					$this->db->where($this->data['prefix'] . 'Id', $this->uri->segment(5));
					$this->db->update($this->data['table'], $q);
					$this->_do_relation($this->uri->segment(5));
					$this->_do_tags($this->uri->segment(5));
					$this->clear_ci_cache_check();
				} else
				{
					$this->db->select_max($this->data['prefix'] . 'Order', 'max');
					$m = $this->db->get($this->data['table'])->result_array();
					$q[$this->data['prefix'] . 'Order'] = (isset($m[0]['max'])) ? $m[0]['max'] + 1 : 0;
					$q[$this->data['prefix'] . 'CreatedAt'] = date('Y-m-d G:i:s');
					$q[$this->data['prefix'] . 'UpdatedAt'] = date('Y-m-d G:i:s');
					
					// Hook just before insert.
					if(isset($this->data['cms']['cp_hooks']['bucket_before_insert']))
					{
						if(! empty($this->data['cms']['cp_hooks']['bucket_before_insert']['library']))
						{
							get_instance()->load->library($this->data['cms']['cp_hooks']['bucket_before_insert']['library']);
							$q = get_instance()->{strtolower($this->data['cms']['cp_hooks']['bucket_before_insert']['library'])}->{$this->data['cms']['cp_hooks']['bucket_before_insert']['method']}($this->data['prefix'], $q);
						}
					}
					
					$this->db->insert($this->data['table'], $q);
					$id = $this->db->insert_id();
					$this->_do_relation($id);
					$this->_do_tags($id);
					$this->clear_ci_cache_check();
				}
				
				redirect($this->data['cms']['cp_base'] . '/buckets/listview/' . $this->uri->segment(4));
			}
		}
		
		$this->load->view('cms/templates/app-header', $this->data);
		$this->load->view('cms/buckets/add-edit', $this->data);
		$this->load->view('cms/templates/app-footer', $this->data);
	}

	//
	// Deal with any relations that are tags.
	//
	private function _do_tags($id)
	{
		foreach($this->data['relations'] AS $key => $row)
		{
		  // Delete old relations.
		  if(isset($_POST['tags'][$row['table']]))
		  {
				$this->_delete_relations($id, $row['table'], get_instance()->data['bucket']['BucketsName']);
			}
			
			// Make sure we have a post.
		  if((! isset($_POST['tags'][$row['table']])) || (! is_array($_POST['tags'][$row['table']]))) 
		  { 
		  	continue; 
		  }
		  
		  // Insert relations.
		  foreach($_POST['tags'][$row['table']] AS $key2 => $row2)
		  {
				// See if the tag is already in the system.
				get_instance()->db->where($row['table'] . 'Title', $row2);
				$t = get_instance()->db->get($this->data['cms']['table_base'] . $row['table'])->row_array();
				if(! $t)
				{
					$p = array();
					$p[$row['table'] . 'Title'] = $row2;
					$p[$row['table'] . 'CreatedAt'] = date('Y-m-d G:i:s');
					get_instance()->db->insert($this->data['cms']['table_base'] . $row['table'], $p);
					$tagid = get_instance()->db->insert_id();
				}	else
				{
					$tagid = $t[$row['table'] . 'Id'];
				}				

		  	$r['RelationsBucket'] = get_instance()->data['bucket']['BucketsName'];
		  	$r['RelationsTable'] = $row['table'];
		  	$r['RelationsTableId'] = $tagid; 
		  	$r['RelationsEntryId'] = $id;
		  	$this->cms_relations_model->insert($r);
		  }
		}
	}
	
	//
	// Deal with any relations.
	//
	private function _do_relation($id)
	{
		foreach($this->data['relations'] AS $key => $row)
		{
		  // Delete old relations.
			if(isset($_POST[$row['table']]))
			{
				$this->_delete_relations($id, $row['table'], get_instance()->data['bucket']['BucketsName']);
			}
			
			// Make sure we have a post.
		  if((! isset($_POST[$row['table']])) || (! is_array($_POST[$row['table']]))) 
		  { 
		  	continue; 
		  }
		  
		  // Insert relations.
		  foreach($_POST[$row['table']] AS $key2 => $row2)
		  {
		  	$r['RelationsBucket'] = get_instance()->data['bucket']['BucketsName'];
		  	$r['RelationsTable'] = $row['table'];
		  	$r['RelationsTableId'] = $row2; 
		  	$r['RelationsEntryId'] = $id;
		  	$this->cms_relations_model->insert($r);
		  }
		}
	}
	
	//
	// Delete relations.
	//
	private function _delete_relations($id = NULL, $table = NULL, $bucket = NULL, $tableid = NULL)
	{
		$this->load->model('cms_relations_model');
		
		if(! is_null($bucket))
		{
			$this->cms_relations_model->set_bucket($bucket);
		}
		
		if(! is_null($table))
		{
			$this->cms_relations_model->set_table($table);
		}
		
		if(! is_null($id))
		{
			$this->cms_relations_model->set_entry($id);
		}
		
		if(! is_null($tableid))
		{
			$this->cms_relations_model->set_table_id($tableid);
		}
		
		$this->cms_relations_model->delete_all();
	}
	
	//
	// Speical magic just for the users bucket.
	//
	private function _do_users($q)
	{
		$this->load->helper('string');
		
		if($this->data['type'] == 'add')
		{
			$q['UsersSalt'] = random_string('alnum', 15);
			$q['UsersPassword'] = md5($q['UsersPassword'] . $q['UsersSalt']);
		}	
		
		if($this->data['type'] == 'edit')
		{
			if($q['UsersPassword'] != 'cms-edit')
			{
				$q['UsersSalt'] = random_string('alnum', 15);
				$q['UsersPassword'] = md5($q['UsersPassword'] . $q['UsersSalt']);
			} else
			{
				unset($q['UsersPassword']);
				unset($q['UsersSalt']);
			}
		}
		
		return $q;
	}
	
	//
	// Manage lookups. 
	//
	private function _do_looksup($r, $row, $key)
	{	
		// Set name
		$this->db->select($r['tablevalue'] . " AS value");
		$this->db->select($r['tablename'] . " AS name");
		
		// Set where
		if(! empty($r['tablewhere']))
		{
		  $this->db->where($r['tablewhere']);
		}
		
		// Set order
		if(! empty($r['tableorder']))
		{
		  $this->db->order_by($r['tableorder']);
		}
		
		// Set group by
		if(! empty($r['tablegroup']))
		{
		  $this->db->group_by($r['tablegroup']);
		}
		
		// Make query and get look up array.
		$d = $this->db->get($this->data['cms']['table_base'] . $r['table'])->result_array();
		get_instance()->data['fields'][$key]->select_options = array();
		foreach($d AS $key2 => $row2)
		{
		  get_instance()->data['fields'][$key]->select_options[$row2['value']] = $row2['name'];
		}
	}
	
	//
	// Manage pre-validation custom formatting before inserting or updating data.
	//
	private function _do_pre_validation_formatting($data)
	{
		foreach($data AS $key => $row)
		{
		  if(isset($data[$key . 'Format']) && ($data[$key . 'Format'] == 'auto'))
		  {
		  	$this->load->library('typography');
		  	$data[$key] = $this->typography->auto_typography($data[$key]);
		  }
		}
		
		return $data;
	}
}

/* End File */