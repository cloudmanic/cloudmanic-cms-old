<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Api extends CMS_Admin
{	
	//
	// Constructor …
	//
	function __construct()
	{
		parent::__construct();
	}
	
	//
	// Returns data based on what we pass in.
	//
	function get()
	{
		$model = '';
		
		// Set which model we are going to get data from.
		switch($this->input->get('type'))
		{
			case 'blocks':
				$this->load->model('cms_blocks_model');
				$model = 'cms_blocks_model';
			break;
			
			case 'bucket':
				$this->load->model('cms_bucketdata_model');
				$this->load->model('cms_buckets_model');
						
				if(! $bucket = $this->cms_buckets_model->get_by_id($this->input->get_post('bucket')))
				{
					die('Nothing to see here.');
				}
				
				$this->cms_bucketdata_model->set_table($bucket['BucketsName']);
		
				// See if we have any relations to add.
				if(isset($bucket['BucketsListview']['joins']) && 
						(is_array($bucket['BucketsListview']['joins'])))
				{
				  foreach($bucket['BucketsListview']['joins'] AS $key => $row)
				  {
				  	$this->cms_bucketdata_model->set_join($row['table'], $row['left'], $row['right'], $row['type']);	
				  }
				}
				
				$model = 'cms_bucketdata_model';			
			break;
			
			case 'media':
				$this->load->model('cms_media_model');
				$model = 'cms_media_model';			
			break;
			
			default:
				die('Nothing to see here.');
			break;
		}
		
		// Set order
		if($this->input->get_post('order'))
		{
			if($this->input->get_post('sort'))
			{
				$this->{$model}->set_order($this->input->get_post('order') . ' ' . $this->input->get_post('sort'));
			} else
			{
				$this->{$model}->set_order($this->input->get_post('order'));				
			}
		}
		
		// Set limit / offset
		if($this->input->get_post('limit'))
		{
			if($this->input->get_post('offset'))
			{
				$this->{$model}->set_limit($this->input->get_post('limit'), $this->input->get_post('offset'));
			} else
			{
				$this->{$model}->set_limit($this->input->get_post('limit'));
			}
		}
		
		// Set search
		if($this->input->get_post('search'))
		{
			$this->{$model}->set_search($this->input->get_post('search'));
		}

		// Return data.
		$data = $this->{$model}->get();
		$this->_return_data($data); 
	}
	
	// ------------------- Private Helper Functions --------------------- //
	
	//
	// Format the data and return it.
	//
	private function _return_data($return)
	{
		$data = array();
		$data['status'] = 1;
		$data['count'] = count($return);
		$data['data'] = $return;
		
		if($this->input->get('format') == 'php')
		{
			echo '<pre>' . print_r($data, TRUE) . '</pre>';
		} else
		{
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode($data));
		}
	}
}

/* End File */