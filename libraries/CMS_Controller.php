<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Controller extends CI_Controller
{
	public $data = array();
	private $_pkg_path = '';

	//
	// Constructor …
	//
	function __construct()
	{	
		// Boot strap. Order of the following is important
		parent::__construct();
		$this->load->spark('cloudmanic-cms/' . CMSVERSON);
		$this->_pkg_path = SPARKPATH . 'cloudmanic-cms/' . CMSVERSON . '/';
		$this->load->model('cms_nav_model');
		
		// Set system wide vars.
		$this->data['state'] = array();
		$this->data['page_title'] = $this->data['cms']['site_name'] . ' // Admin Only';
		$this->data['nav'] = $this->cms_nav_model->get_nav();
		$this->_cont_init();

		// Setup segments based on what the cp base is.
		$this->data['seg1'] = $this->uri->segment($this->data['cms']['cp_base_seg'] + 1);
		$this->data['seg2'] = $this->uri->segment($this->data['cms']['cp_base_seg'] + 2);
		$this->data['seg3'] = $this->uri->segment($this->data['cms']['cp_base_seg'] + 3);
		$this->data['seg4'] = $this->uri->segment($this->data['cms']['cp_base_seg'] + 4);
		$this->data['seg5'] = $this->uri->segment($this->data['cms']['cp_base_seg'] + 5);
		
		// Check if we have to force SSL for control panel access.
		if($this->data['cms']['cp_force_ssl'] && ($_SERVER['SERVER_PORT'] == '80'))
		{
			$url = site_url($this->data['cms']['cp_base']);
			redirect(str_ireplace('http://', 'https://', $url));
		}
	}
	
	//
	// Remaping function route the request.
	//
	function _remap()
	{		
		// Controll panel request?
		if(stripos($this->uri->uri_string(), $this->data['cms']['cp_base']) === 0)
		{
			$t = str_ireplace($this->data['cms']['cp_base'], '', $this->uri->uri_string());
			$f = explode('/', $t);
			$lib = (! empty($f[1])) ? ucfirst(strtolower($f[1])) : 'Login';
			$func = (! empty($f[2])) ? $f[2] : 'index';
			
			// Make sure this is a library that we intend to let the user access.
			if(! is_file($this->_pkg_path . 'libraries/CMS_' . $lib . '.php'))
			{
				show_404();
			}
			
			// Can't load the Admin controller directly.
			if($lib == 'Admin')
			{
				show_404();
			}
			
			// Load library and call function 
			include_once($this->_pkg_path . 'libraries/CMS_Admin.php');
			$this->load->library('CMS_' . $lib);
			
			if(! method_exists($this->{'cms_' . strtolower($lib)}, $func))
			{
				show_404();
			}
			
			$this->{'cms_' . strtolower($lib)}->{$func}();	
		}
	}
	
	
	// ----------------- Private Helper Functions --------------- //
	
	//
	// Controller Init.
	//
	private function _cont_init()
	{
		// Set defaults.
		//$this->data['state']['limit'] = ($this->input->get('limit')) ? $this->input->get('limit') : 200;
		//$this->data['state']['offset'] = ($this->input->get('offset')) ? $this->input->get('offset') : 0;
		$this->data['state']['search'] = ($this->input->get('search')) ? $this->input->get('search') : '';
		//$this->data['state']['order'] = ($this->input->get('order')) ? $this->input->get('order') : '';
		//$this->data['state']['sort'] = ($this->input->get('sort')) ? $this->input->get('sort') : 'asc';
	}
}

/* End File */