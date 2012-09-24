<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Admin
{
	//
	// Constructor ….
	//
	function __construct()
	{
		get_instance()->data['data'] = array();
		$this->_check_auth();
	}
	
	//
	// Generic listview....
	//
	function index()
	{
		$view = str_ireplace('cms_', '', strtolower(get_class($this)));
		$model = strtolower(get_class($this)) . '_model';
		$this->load->model($model);
		get_instance()->data['data'] = $this->{$model}->get();
		$this->load->view('cms/templates/app-header', $this->data);
		$this->load->view("cms/$view/listview", $this->data);
		$this->load->view('cms/templates/app-footer', $this->data);
	}
	
	//
	// Generic delete operations.
	//
	function delete()
	{	
		$model = strtolower(get_class($this)) . '_model';
		$base = str_ireplace('cms_', '', strtolower(get_class($this)));
		$this->load->model($model);		
		$this->{$model}->delete(end($this->uri->segment_array()));
		$this->output->set_output('success');
		//redirect($this->data['cms']['cp_base'] . '/' . $base);
	}
	
	//
	// If we have cache clearing in place clear the cache dir.
	//
	function clear_ci_cache_check()
 	{
		// If we set CI page caching to clear we clear it.	
		if($this->data['cms']['cp_clear_ci_page_cache'])
		{
			$this->load->helper('file');
			delete_files($this->config->item('cache_path'));
			write_file($this->config->item('cache_path') . 'index.html', $this->_get_contents_no_listing());
		}
 	}
				
	// --------------- Private Helper Functions ------------ //
	
 	//
 	// Get the contents of a no listing file.
 	//
 	private function _get_contents_no_listing()
 	{
	 	return '<html>
	 		<head>
	 			<title>403 Forbidden</title>
	 		</head>
	 		<body>
	 		
	 		<p>Directory access is forbidden.</p>
	 		
	 		</body>
	 		</html>';
 	}
	
	//
	// Make sure the user is logged in. If not kick the user out.
	//
	private function _check_auth()
	{
		if(! $user = $this->session->userdata('CmsLoggedIn'))
		{
			redirect($this->data['cms']['cp_base']);
		}
		
		// Refresh the session.
		$this->load->model('cms_users_model');
		get_instance()->data['me'] = $this->cms_users_model->get_by_id($user['UsersId']);
	}
				
	//
	// Access to the super object.
	//
	function __get($key)
	{
		$CI =& get_instance();
		return $CI->$key;
	}
}

/* End File */