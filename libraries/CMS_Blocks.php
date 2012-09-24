<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Blocks extends CMS_Admin
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
	}
	
	//
	// Add a post ...
	//
	function add()
	{
		get_instance()->data['widgettext'] = 'Add New Block';
		get_instance()->data['helpertext'] = 'To add a new block fill out the field below and click "save"';
		get_instance()->data['type'] = 'add';
		
		$this->_add_edit_shared_func();	
	}

	//
	// Edit a post ...
	//
	function edit()
	{
		get_instance()->data['widgettext'] = 'Edit Block';
		get_instance()->data['helpertext'] = 'To edit this block fill out the field below and click "save"';
		get_instance()->data['type'] = 'edit';
		
		// Get data
		get_instance()->data['id'] = $id = $this->uri->segment(4);
		if(! get_instance()->data['data'] = $this->cms_blocks_model->get_by_id($id)) 
		{
			redirect($this->config->item('cb_cp_url_base') . '/blocks');
		}	
		
		$this->_add_edit_shared_func($id);	
	}
	
	// ------------------ Internal Helper Functions ---------------- //
	
	//
	// Shared functionality between add / edit.
	//
	private function _add_edit_shared_func($update = FALSE)
	{
		// Manage posted data.
		if($this->input->post('submit'))
		{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('BlocksName', 'Name', 'required|trim|strtolower');
			$this->form_validation->set_rules('BlocksBody', 'Body', 'required|trim');
	
			if(get_instance()->form_validation->run() != FALSE)
			{
				$q['BlocksName'] = $this->input->post('BlocksName');
				$q['BlocksBody'] = $this->input->post('BlocksBody');
				
				if($update)
				{
					$this->cms_blocks_model->update($q, $update);
				} else
				{
					$this->cms_blocks_model->insert($q);
				}
				
				redirect($this->data['cms']['cp_base'] . '/blocks');
			}
		}
		
		$this->load->view('cms/templates/app-header', $this->data);
		$this->load->view('cms/blocks/add-edit', $this->data);
		$this->load->view('cms/templates/app-footer', $this->data);
	}
}

/* End File */