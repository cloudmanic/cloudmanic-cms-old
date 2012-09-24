<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Init
{
	public $data = array();
	private $_pkg_path = '';

	//
	// Constructor …
	//
	function __construct()
	{	
		$this->_pkg_path = SPARKPATH . 'cloudmanic-cms/' . CMSVERSON . '/';
		include_once(BASEPATH . 'core/Model.php');
		include_once($this->_pkg_path . 'libraries/CMS_Model.php');
		$this->load->config('cms', TRUE);
		get_instance()->data['cms'] = $this->config->item('cms');
		$this->load->library('CMS_Tables');
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