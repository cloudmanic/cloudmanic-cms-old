<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Login
{	
	//
	// Index page….
	//
	function index()
	{
		get_instance()->data['fail'] = 0;
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->load->library('session');
		
		// Check to see if the user is logged in first so we can redirect them.
		if($this->session->userdata('CmsLoggedIn')) 
		{
			redirect($this->data['cms']['cp_base'] . '/'. $this->data['cms']['cp_home']);
		}

		// Are we posting a login?
		if($this->input->post('UsersEmail')) 
		{
			$this->form_validation->set_rules('UsersEmail', 'Email Address', 'required|valid_email|min_length[4]|max_length[32]');
			$this->form_validation->set_rules('UsersPassword', 'Password', 'required|min_length[4]|max_length[32]');
			
			// If we validated now see if there is really an account.
			if($this->form_validation->run() == TRUE)
			{
				$this->load->model('cms_users_model');
				if($user = $this->cms_users_model->get_by_email($this->input->post('UsersEmail')))
				{
					if($user['UsersPassword'] == md5($this->input->post('UsersPassword') . $user['UsersSalt']))
					{
						// Success! Create session and reload the page to redirect.
						unset($user['UsersPassword']);
						unset($user['UsersSalt']);
						$this->session->set_userdata('CmsLoggedIn', $user);
						redirect(current_url());
					}
				}
				
				// We failed to login.			
				get_instance()->data['fail'] = 1;
			}
		}
		
		$this->load->view('cms/templates/login-header', $this->data);
		$this->load->view('cms/login/login-auth', $this->data);
		$this->load->view('cms/templates/login-footer', $this->data);
	}
	
	//
	// This function will log a user out.
	//
	function logout()
	{
		$this->session->unset_userdata('CmsLoggedIn');
		redirect($this->data['cms']['cp_base']);
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