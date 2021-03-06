<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
 class Raspicam extends FAB_Controller {
 	
	public function index()
	{
		//load libraries, helpers, model
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->library('Camera', null, 'camera');
		
		//data
		$data = array();
		$data['camera_enabled'] = isCameraPresent();
		$data['params']   = $this->camera->getParameterList();
		$data['settings'] = $this->camera->get_default_settings();
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widgeFooterButtons = '';

		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-head-installation';
		$widget->header = array('icon' => 'fa-video-camera', "title" => "<h2>"._("Camera")."</h2>");
		$widget->body   = array('content' => $this->load->view('camera/main_widget', $data, true ), 'class'=>'no-padding');

		$this->addJsInLine($this->load->view('camera/js', $data, true));
		$this->content = $widget->print_html(true);
		$this->view();
	}

	public function takePicture()
	{
		$postData = $this->input->post();
		
		$this->load->library('Camera', $postData, 'camera');
		
		foreach($postData as $key => $value)
		{
			if($key == 'size')
			{
				$tmp = explode('x', $value);
				$this->camera->setValue('width', $tmp[0]);
				$this->camera->setValue('height', $tmp[1]);
			}
			else
			{
				$this->camera->setValue($key, $value);
			}
		}
		
		$filename = $this->camera->takePhoto();
		
		$this->output->set_content_type('application/json')->set_output(json_encode( array('filename' => $filename) ));
	}
	
	public function downloadPicture()
	{
		$this->load->library('Camera', '', 'camera');
		$this->load->helper('download');
		
		$filename = $this->camera->getPermalink();
		
		if( !file_exists( $filename ) )
		{
			$filename = $this->camera->takePhoto();
		}
		
		$filename = realpath($filename);
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$_file_name = 'raspicam.'.$ext;
		$_data      = file_get_contents($filename);
		
		force_download($filename, NULL);
	}
	
	public function getPicture($time = 0)
	{
		$this->load->library('Camera', '', 'camera');
		
		$filename = $this->camera->getPermalink();
		
		if( !file_exists( $filename ) )
		{
			$filename = $this->camera->takePhoto();
		}
		
		header('Content-type: ' . $this->camera->getMimeType() );
		echo readfile($filename);
	}
	
	public function doDetectCamera()
	{
		$this->load->helper('fabtotum_helper');
		detectCamera();
		$result = isCameraPresent();
		$this->output->set_content_type('application/json')->set_output(json_encode( array('camera' => $result) ));
	}
 }
 
?>