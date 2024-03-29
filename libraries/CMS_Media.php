<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
//

class CMS_Media extends CMS_Admin
{	
	//
	// Constructor …
	//
	function __construct()
	{
		parent::__construct();
		$this->load->helper('array');
		$this->load->helper('form');
		$this->load->model('cms_media_model');
		$this->load->helper('text');
	}
	
	//
	// Add new media to the system.
	//
	function add()
	{
		$this->load->view('cms/media/add-edit', $this->data);
	}
	
	//
	// Now we crop an image that we upload.
	// We pass in a media id.
	//
	function crop()
	{
		get_instance()->data['up_media'] = $this->input->post('media');
		if(! get_instance()->data['media'] = $this->cms_media_model->get_by_id($this->data['up_media']['id']))
		{
			die('Media asset not found.');
		}
		
		// Make sure our image is wide enough for resizing.
		if($this->data['up_media']['image_width'] < $this->input->post('width'))
		{
			die('Sorry, the image you uploaded is not wide enough to crop. Must be at least ' . $this->input->post('width') . 'px wide.');
		}
		
		// Make sure our image is tall enough for resizing.
		if($this->data['up_media']['image_height'] < $this->input->post('height'))
		{
			die('Sorry, the image you uploaded is not tall enough to crop. Must be at least ' . $this->input->post('height') . 'px tall.');
		}
		
		// Make sure our image is at least 400px wide. (we scale the image to 400px in the browser.
		if($this->data['up_media']['image_width'] < $this->input->post('width'))
		{
			die('Sorry, the image you uploaded is not wide enough to crop. Must be at least 400px tall.');
		}
		
		// Pass post vars to view.
		get_instance()->data['aspect'] = $this->input->post('aspect');
		get_instance()->data['height'] = $this->input->post('height');
		get_instance()->data['width'] = $this->input->post('width');
		
/*
		// Download the image so we have a local copy.
		$tmpfile = $this->data['cms']['cp_tmp_dir'] . '/' . $this->data['up_media']['MediaHash'] . '_' . $this->data['up_media']['MediaFile']; 
		$image = file_get_contents($this->data['up_media']['url']);
		file_put_contents($tmpfile, $image);
		
		// Resize the image to something smaller so we can display it without scrolling.
		$this->load->spark('wideimage-ci/11.02.19');
		$this->wideimage->load($tmpfile)->resize(500, 500, 'inside')->saveToFile($this->data['cms']['cp_tmp_dir'] . '/' . $newfile);
*/
		
		$this->load->view('cms/media/crop-image', $this->data);	
	}
	
	// ------------------ Ajaxy Calls ------------------------------ //
	
	//
	// We pass in the cordinates to the new images that we need to crop. 
	// We crop the image and upload it to the media table.
	//
	function crop_now()
	{
		if(! get_instance()->data['media'] = $this->cms_media_model->get_by_id($this->input->post('MediaId')))
		{
			die('Media asset not found.');
		}
		
		// Load wide image.
		$this->load->spark('wideimage-ci/11.02.19');
		
		// Download the image so we have a local copy.
		$tmpfile = $this->data['cms']['cp_tmp_dir'] . '/' . 'cropped_' . $this->data['media']['MediaFile'];			
		$image = file_get_contents($this->data['media']['url']);
		file_put_contents($tmpfile, $image);
		
		// Crop the image from the original image.
		$oinfo = getimagesize($tmpfile);
		$crop_w = $oinfo[0] * ($this->input->post('w') / $this->input->post('view_width'));
		$crop_h = $oinfo[1] * ($this->input->post('h') / $this->input->post('view_height'));
		$crop_x = $this->input->post('x1') + ($this->input->post('x1') * ($oinfo[0] / $this->input->post('view_width')));
		$crop_y = $this->input->post('y1') + ($this->input->post('y1') * ($oinfo[1] / $this->input->post('view_height')));		
		
		// Crop and / or resize the image.
		if($this->input->post('target_width') && $this->input->post('target_height') && $this->input->post('target_aspect'))
		{
			$this->wideimage
						->load($tmpfile)
						->crop($crop_x, $crop_y, $crop_w, $crop_h)
						->resize($this->input->post('target_width'), $this->input->post('target_height'), 'fill')
						->saveToFile($tmpfile);	
		} else if($this->input->post('target_width') && $this->input->post('target_height'))
		{
			$this->wideimage
						->load($tmpfile)
						->crop($crop_x, $crop_y, $crop_w, $crop_h)
						->resize($this->input->post('target_width'), $this->input->post('target_height'), 'inside')
						->saveToFile($tmpfile);			
		} else if($this->input->post('target_width'))
		{
			$this->wideimage
						->load($tmpfile)
						->crop($crop_x, $crop_y, $crop_w, $crop_h)
						->resize($this->input->post('target_width'), null, 'fill')
						->saveToFile($tmpfile);	
		} else if($this->input->post('target_height'))
		{
			$this->wideimage
						->load($tmpfile)
						->crop($crop_x, $crop_y, $crop_w, $crop_h)
						->resize(null, $this->input->post('target_height'), 'fill')
						->saveToFile($tmpfile);	
		} else
		{
			$this->wideimage
						->load($tmpfile)
						->crop($crop_x, $crop_y, $crop_w, $crop_h)
						->saveToFile($tmpfile);	
		}
		
		// Store the new file away. We build a json blob to return to the browser.
		$json = array('status' => 0, 'data' => array(), 'errors' => array());
		$json['status'] = 1;
		$json['data']['is_image'] = 1;
		$json['data']['full_path'] = $tmpfile;
		$json['data']['file_ext'] = $this->_get_extension($tmpfile);
		$json['data']['raw_name'] = str_replace($json['data']['file_ext'], '', basename($tmpfile));
			
		// Setup media table information.
		$type = mime_content_type($tmpfile);
		$type = explode('/', $type);
		$q['MediaIsImage'] = 1;
		$q['MediaType'] = $type[1];
		$q['MediaSize'] = round(filesize($tmpfile)/1024, 2);
		$q['MediaHash'] = md5_file($tmpfile); 
		
		// Uplaod the file.	
		$json = $this->_do_upload($json, $q);
		
		// Return the json that we uploaded the file.
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($json));
	}
	
	//
	// Upload media assets.
	//
	function upload()
	{
		$json = array('status' => 0, 'data' => array(), 'errors' => array());
		$config['upload_path'] = $this->data['cms']['cp_tmp_dir'];
		$config['allowed_types'] = $this->data['cms']['cp_media_file_types'];
		$config['max_size']	= $this->data['cms']['cp_media_file_max_size'];
		$this->load->library('upload', $config);

		// If upload was successful
		if($this->upload->do_upload('file'))
		{
			$json['status'] = 1;
			$json['data'] = $this->upload->data();
			
			// Setup media table information.
			$q['MediaIsImage'] = $json['data']['is_image'];
			$q['MediaType'] = $json['data']['image_type'];
			$q['MediaSize'] = $json['data']['file_size'];
			$q['MediaHash'] = md5_file($json['data']['full_path']); 
			
			$json = $this->_do_upload($json, $q);
		} else
		{
			$json['errors'][] = $this->upload->display_errors('', '');
		}
		
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($json));
	}
	
	// ------------------ Internal Helper Functions ---------------- //
		
	//
	// Get file extension
	//
	private function _get_extension($filename)
	{
		$x = explode('.', $filename);
		return '.' . end($x);
	}
		
	//
	// Generic function for uploading files. Figure out what driver to upload
	// the file too. Also returns a json file to return to the browser or something.
	//
	private function _do_upload($json, $q)
	{
		// Upload the file to the storage driver.
		switch($this->data['cms']['cp_media_driver'])
		{
		  // Upload file to rackspace
		  case 'rackspace-cloud-files':
		  	$json = $this->_rackspace_cf_upload($json, $q);
		  break;
		  
		  // Upload file to amazon S3
		  case 'amazon-web-services-s3':
		  	$json = $this->_amazon_s3_upload($json, $q);				
		  break;
		  
		  // Upload file to the local file system
		  case 'local-files':
		  	$json = $this->_local_upload($json, $q);				
		  break;
		  
		  default:
		  	$json['status'] = 0;
		  	$json['errors'][] = 'No storage driver set.';
		  break;
		}
		
		return $json;
	}
		
	//
	// Upload a file locally.
	//
	private function _local_upload($json, $q)
	{		
		// Insert the file into the media database.
		$q['MediaStore'] = 'local-files';
		$q['MediaPath'] = $this->data['cms']['cp_media_local_path'];
		$json['data']['id'] = $id = $this->cms_media_model->insert($q);
		$d['MediaFile'] = $json['data']['raw_name'] . "_$id" . $json['data']['file_ext'];
		
		// If the file is an image build thumbnail & upload to rs.
		if($json['data']['is_image'])
		{
		  $d['MediaFileThumb'] = str_ireplace($json['data']['file_ext'], '_thumb' . $json['data']['file_ext'], $d['MediaFile']);
		  $thumb = $this->_build_thumb_nail($json['data']['full_path'], $d['MediaFileThumb']);
		  $d['MediaPathThumb'] = $this->data['cms']['cp_media_local_path'];
		  
		  // Install file locally (we copy and unlink because of https://bugs.php.net/bug.php?id=50676)
			copy($this->data['cms']['cp_tmp_dir'] . '/' . $thumb, $this->data['cms']['cp_media_local_dir'] . $d['MediaFileThumb']);
			unlink($this->data['cms']['cp_tmp_dir'] . '/' . $thumb);
		
		  // Build FQDN
		  $json['data']['thumburl'] = $this->data['cms']['cp_media_local_url'] .  $d['MediaPathThumb'] . $d['MediaFileThumb'];
		  $json['data']['thumbsslurl'] = $this->data['cms']['cp_media_local_ssl_url'] . $d['MediaPathThumb'] . $d['MediaFileThumb'];
		}
		
		// We have to insert and then update because of the id in the file name.
		$this->cms_media_model->update($d, $id);
		
		// Install file locally
		copy($json['data']['full_path'], $this->data['cms']['cp_media_local_dir'] . $d['MediaFile']);
		unlink($json['data']['full_path']);
		  													
		// Build FQDN
		$json['data']['url'] = $this->data['cms']['cp_media_local_url'] . $q['MediaPath'] . $d['MediaFile'];
		$json['data']['sslurl'] = $this->data['cms']['cp_media_local_ssl_url'] . $q['MediaPath'] . $d['MediaFile'];
				
		return $json;
	}

	//
	// Update the file to amazon s3.
	//
	private function _amazon_s3_upload($json, $q)
	{
		$this->load->spark('cloudmanic-storage/1.0.2');
		$this->storage->load_driver('amazon-s3');
		
		// Insert the file into the media database.
		$q['MediaStore'] = 'amazon-web-services-s3';
		$q['MediaPath'] = $this->data['cms']['cp_media_amazon_s3_path'];
		$json['data']['id'] = $id = $this->cms_media_model->insert($q);
		$d['MediaFile'] = $json['data']['raw_name'] . "_$id" . $json['data']['file_ext'];
		
		// If the file is an image build thumbnail & upload to rs.
		if($json['data']['is_image'])
		{
		  $d['MediaFileThumb'] = str_ireplace($json['data']['file_ext'], '_thumb' . $json['data']['file_ext'], $d['MediaFile']);
		  $thumb = $this->_build_thumb_nail($json['data']['full_path'], $d['MediaFileThumb']);
		  $d['MediaPathThumb'] = $this->data['cms']['cp_media_amazon_s3_path'];
		  
		  // Upload to rackspace
		  $this->storage->upload_file($this->data['cms']['cp_media_amazon_s3_container'], 
		  														$this->data['cms']['cp_tmp_dir'] . '/' . $thumb, $d['MediaPathThumb'] . $d['MediaFileThumb'], NULL, 'public');
			unlink($this->data['cms']['cp_tmp_dir'] . '/' . $thumb);
		
		  // Build FQDN
		  $json['data']['thumburl'] = $this->data['cms']['cp_media_amazon_s3_url'] . $d['MediaPathThumb'] . $d['MediaFileThumb'];
		  $json['data']['thumbsslurl'] = $this->data['cms']['cp_media_amazon_s3_ssl_url'] . $d['MediaPathThumb'] . $d['MediaFileThumb'];
		}
		
		// We have to insert and then update because of the id in the file name.
		$this->cms_media_model->update($d, $id);
		
		// Upload the file to Rackspace
		$this->storage->upload_file($this->data['cms']['cp_media_amazon_s3_container'], 
		  													$json['data']['full_path'], $q['MediaPath'] . $d['MediaFile'], NULL, 'public');
		unlink($json['data']['full_path']);		
		  													
		// Build FQDN
		$json['data']['url'] = $this->data['cms']['cp_media_amazon_s3_url'] . $q['MediaPath'] . $d['MediaFile'];
		$json['data']['sslurl'] = $this->data['cms']['cp_media_amazon_s3_ssl_url'] . $q['MediaPath'] . $d['MediaFile'];
				
		return $json;
	}
	
	//
	// Update the file to rackspace cloud files.
	//
	private function _rackspace_cf_upload($json, $q)
	{
		$this->load->spark('cloudmanic-storage/1.0.2');
		$this->storage->load_driver('rackspace-cf');
		
		// Insert the file into the media database.
		$q['MediaStore'] = 'rackspace-cloud-files';
		$q['MediaPath'] = $this->data['cms']['cp_media_rackspace_path'];
		$json['data']['id'] = $id = $this->cms_media_model->insert($q);
		$d['MediaFile'] = $json['data']['raw_name'] . "_$id" . $json['data']['file_ext'];
		
		// If the file is an image build thumbnail & upload to rs.
		if($json['data']['is_image'])
		{
		  $d['MediaFileThumb'] = str_ireplace($json['data']['file_ext'], '_thumb' . $json['data']['file_ext'], $d['MediaFile']);
		  $thumb = $this->_build_thumb_nail($json['data']['full_path'], $d['MediaFileThumb']);
		  $d['MediaPathThumb'] = $this->data['cms']['cp_media_rackspace_path'];
		  
		  // Upload to rackspace
		  $this->storage->upload_file($this->data['cms']['cp_media_rackspace_container'], 
		  														$this->data['cms']['cp_tmp_dir'] . '/' . $thumb, $d['MediaPathThumb'] . $d['MediaFileThumb']);
			unlink($this->data['cms']['cp_tmp_dir'] . '/' . $thumb);
		
		  // Build FQDN
		  $json['data']['thumburl'] = $this->data['cms']['cp_media_rackspace_url'] . $d['MediaPathThumb'] . $d['MediaFileThumb'];
		  $json['data']['thumbsslurl'] = $this->data['cms']['cp_media_rackspace_ssl_url'] . $d['MediaPathThumb'] . $d['MediaFileThumb'];
		}
		
		// We have to insert and then update because of the id in the file name.
		$this->cms_media_model->update($d, $id);
		
		// Upload the file to Rackspace
		$this->storage->upload_file($this->data['cms']['cp_media_rackspace_container'], 
		  													$json['data']['full_path'], $q['MediaPath'] . $d['MediaFile']);
		unlink($json['data']['full_path']);		
		  													
		// Build FQDN
		$json['data']['url'] = $this->data['cms']['cp_media_rackspace_url'] . $q['MediaPath'] . $d['MediaFile'];
		$json['data']['sslurl'] = $this->data['cms']['cp_media_rackspace_ssl_url'] . $q['MediaPath'] . $d['MediaFile'];
				
		return $json;
	}
	
	//
	// Build thumb nail.
	//
	private function _build_thumb_nail($file, $newfile)
	{
		$this->load->spark('wideimage-ci/11.02.19');
		$width = $this->data['cms']['cp_thumb_width'] * 1.10;
		$height = $this->data['cms']['cp_thumb_height'] * 1.10;
		$this->wideimage->load($file)->resize($width, $height, 'outside')->crop('center', 'center', $this->data['cms']['cp_thumb_width'], $this->data['cms']['cp_thumb_height'])->saveToFile($this->data['cms']['cp_tmp_dir'] . '/' . $newfile);
		return $newfile;
	}
	
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
				
				redirect($this->data['cms']['cp_base'] . '/' . $this->data['cms']['cp_base'] . '/blocks');
			}
		}
		
		$this->load->view('cms/templates/app-header', $this->data);
		$this->load->view('cms/media/add-edit', $this->data);
		$this->load->view('cms/templates/app-footer', $this->data);
	}
}

/* End File */