<?php
/**
 *
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Control extends FAB_Controller {
	
	public function index(){
		
	}
	
	/**
	 *  reboot fabtotum
	 */
	public function reboot()
	{
		session_destroy();
		shell_exec('sudo reboot');
	}
	
	/**
	 * shutdown fabtotum
	 */
	public function poweroff()
	{
	   $this->load->helper('fabtotum_helper');
	   
	   $shutDownResult = doMacro('shutdown'); 
	   
	   shell_exec('sudo poweroff');
	}
	
	/**
	 * emergency action, stop all operations
	 */
	public function emergency()
	{
		//load helper
		$this->load->helper('fabtotum_helper');
		emergency();
	}
	
	/**
	 * reset controller board
	 */
	public function resetController()
	{
		//load helpers
		$this->load->helper('fabtotum_helper');
		$this->session->settings = loadSettings();
		$this->output->set_content_type('application/json')->set_output(json_encode(resetController()));
	}
	
	/**
	 * @param string {activate | deactivate}
	 */
	function setRecovery($mode)
	{
		$this->load->helper('fabtotum_helper');
		$this->output->set_content_type('application/json')->set_output(json_encode(setRecovery($mode)));
	}
	
	/**
	 *  set secure from error alert
	 */
	public function setSecure($mode)
	{
		//if is called from fabui
		if($this->input->is_ajax_request()){
			$this->load->model('Tasks', 'tasks');
			$tasks = $this->tasks->getRunning();
			if(!$tasks){
				$this->load->helper('fabtotum_helper');
				setSecure($mode);
			}
			$this->output->set_content_type('application/json')->set_output(json_encode(true));
		}
	}
	
	/**
	 * Task flow control used by javascript ajax calls.
	 * @param $action {abort | pause | resume...}
	 * @param $value action related value
	 */
	public function taskAction($action, $value = '')
	{
		$this->load->helper('fabtotum_helper');
		$this->output->set_content_type('application/json')->set_output(json_encode($action($value)));
	}
	
	/**
	 * 
	 */
	public function trigger($name)
	{
		$this->load->helper('fabtotum_helper');
		$value = $this->input->post('data');
		
		$this->output->set_content_type('application/json')->set_output(json_encode(trigger($name, $value )));
	}
	
	/**
	 * 
	 */
	public function ws_fallback()
	{
		$method = $this->input->method(true);
		$reponse = array(
			'type' => 'unknown',
			'data' => ''
		);
		
		if($method == 'GET'){ //for get calls
			
			$request     = $this->input->get('data');
			$requestData = json_decode($request, true);
			
			//load config file
			$this->load->config('fabtotum');
			//load helpers
			$this->load->helper(array('fabtotum_helper', 'os_helper'));
			
			$response['type'] = 'poll';
			$response['data'] = array(
				'notify' => array(
					'data' => json_decode(file_get_contents( $this->config->item('notify_file') ), true),
					'type' => 'trace'
				),
				'trace' => array(
					'data' => file_get_contents($this->config->item('trace')),
					'type' => 'trace'
				),
				'task' => array(
					'data' => json_decode(file_get_contents( $this->config->item('task_monitor') ), true),
					'type' => 'trace'
				),
				'usb' => array(
					'data' => array('status' => getUsbStatus(), 'alert' => false),
					'type' => 'usb'
				)
			);
		}
		else if($method == 'POST'){ // for post calls
			
			$request     = $this->input->post('data');
			$requestData = json_decode($request, true);
			
			//load config file
			$this->load->config('fabtotum');
			
			if(isset($requestData['function'])){
				
				$function       = $requestData['function'];
				$functionParams = isset($requestData['params']) ? $requestData['params'] : '';
				
				switch($function)
				{
					case 'serial':
						$method      = $functionParams['method'];
						$methodParam = $functionParams['value'];
						$methodStamp = $functionParams['stamp'];
						
						unset($functionParams['method']);
						unset($functionParams['value']);
						unset($functionParams['stamp']);
						
						$this->load->library('JogFactory', $functionParams, 'jogFactory');
						$jogFactory = $this->jogFactory;
						
						if(method_exists($jogFactory, $method)){ //if method exists than do it
							$response['data'] = $jogFactory->$method($methodParam, $methodStamp);
							$response['type'] = $jogFactory->getResponseType();
						}
						break;
						
					case 'fabBusy':
						$response['data'] = array('message' => 'FABtotum is busy');
						$response['type'] = 'alert';
						break;
						
					case 'usbInserted':
						$response['type'] = 'usb';
						$response['data'] = array('status' => file_exists($this->config->item('usb_file')), 'alert' => false);
						break;
						
					case 'getUpdates':
						$response['data'] = $this->_getUpdatesData();
						$response['type'] = "updates";
						break;
					
					case 'getHardwareSettings':
						$response['data'] =  $this->_getSettingsData();
						$response['type'] = "hardware-settings";
						break;
						
					case 'getNetworkInfo':
						$response['data'] = $this->_getNetworkData();
						$response['type'] = "network-info";
						break;
				}
			}
		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode($response));
	}
	
	/**
	 * get settings
	 */
	public function getSettings()
	{
		$data = $this->_getSettingsData();
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}
	/**
	 *
	 */
	public function getNetworkInfo()
	{
		$networkData = $this->_getNetworkData();
		$this->output->set_content_type('application/json')->set_output(json_encode($networkData));
	}
	/**
	 * scan wifi networks
	 * @return json all scanned networks
	 */
	public function scanWifi($interface = 'wlan0')
	{
		//load helpers
		$this->load->helper('os_helper');
		$nets = scanWlan($interface);
		$this->output->set_content_type('application/json')->set_output(json_encode($nets));
	}
	/**
	 *
	 */
	public function saveNetworkSettings($action = 'connect')
	{
		//get data from post
		$this->load->helper(array('os_helper', 'social_helper', 'shop_helper'));
		$postData = $this->input->post();
		$result = true;
		$net_type = $postData['net_type'];
		switch($net_type)
		{
			case "eth":
				$address = $postData['ipv4'];
				$netmask = $postData['netmask'];
				$gateway = $postData['gateway'];
				$mode = $postData['address-mode'];
				$iface = $postData['active'];
				configureEthernet($iface, $mode, $address, $netmask, $gateway);
				storeNetworkSettings($net_type, $iface, $mode, $address, $netmask, $gateway);
				
				//update social, shop feeds
				downloadAllFeeds();
				downloadAllFilamentsFeeds();
				break;
			case "wlan":
				if($action == 'connect')
				{
					$address     = isset($postData['ipv4'])    ? $postData['ipv4']    : '0.0.0.0';
					$netmask     = isset($postData['netmask']) ? $postData['netmask'] : '255.255.0.0';
					$gateway     = isset($postData['gateway']) ? $postData['gateway'] : '0.0.0.0';
					$mode        = $postData['address-mode'];
					$iface       = $postData['active'];
					$ap_ssid     = $postData['ap-ssid'];
					$ap_pass     = $postData['ap-password'];
					$ap_channel  = isset($postData['ap-channel']) ? $postData['ap-channel'] : 1;
					$hidden_ssid = $postData['hidden-ssid'];
					$hidden_pass = $postData['hidden-passphrase'];
					$psk = $postData['hidden-psk'];
					
					if($mode == 'static-ap')
					{
						$ssid = $ap_ssid;
						$password = $ap_pass;
						$psk = '';
					}
					else
					{
						$ssid = $hidden_ssid;
						$password = $hidden_pass;
					}
					configureWireless($iface, $ssid, $password, $psk, $mode, $address, $netmask, $gateway, $ap_channel);
					storeNetworkSettings($net_type, $iface, $mode, $address, $netmask, $gateway, $ssid, $password, $psk);
					//update social, shop feeds
					downloadAllFeeds();
					downloadAllFilamentsFeeds();	
				}
				else if($action == 'disconnect')
				{
					$iface = $postData['active'];
					disconnectFromWireless($iface);
				}
				break;
			case "dnssd":
				$hostname = $postData['dnssd-hostname'];
				$name = $postData['dnssd-name'];
				// TODO: error handling
				setHostName($hostname, $name);
				storeNetworkSettings($net_type, '', '', '', '', '', '', '', '', $hostname, $name);
				break;
			case "dns":
				// TODO
				$dns = $postData['dns'];
				configureDNS($dns);
			default:
				$result = false;
		}
		writeNetworkInfo();
		$this->output->set_content_type('application/json')->set_output(json_encode(getInterfaces()));
	}
	/**
	 *
	 */
	public function runningTasks()
	{
		
		$this->load->model('Tasks', 'tasks');
		$this->load->model('Files', 'files');
		
		$tasks = $this->tasks->getRunning();
		
		if($tasks)
		{
			/* used to define gettext versions of those words for task status*/
			$_running    = _("running");
			$_paused     = _("paused");
			$_aborted    = _("aborted");
			$_aborting   = _("aborting");
			$_completed  = _("completed");
			$_completing = _("completing");
			/* do not remove lines above */
			$task_status = _($tasks['status']);
			
			$task_type       = $tasks['type'];
			$task_controller = $tasks['controller'];
			$task_file_id    = $tasks['id_file'];
			
			$task_url = $task_controller;
			//if($task_type)
				//$task_url .= '/' . $task_type;
				
			$task_filename = '';
			
			if($task_file_id != ""){
				$file = $this->files->get($task_file_id, 1);
				if($file){
					if($task_type == 'scan')
						$task_filename = _("Being generated") . '...';
						else
							$task_filename = $file['client_name'];
				}
			}
			
			$task_label = _(ucfirst($task_type)).' '._("task");
			
			switch($task_type){
				case 'pid_tune':
					$task_label = 'PID tune';
					//$task_url   = $task_controller.'/nozzle-pid-tune';
					break;
				case 'update':
					$task_label = 'Update';
					//$task_url   = $task_controller;
					break;
			}
			
			echo '<ul class="notification-body">';
			echo '
				<li>
					<span class="padding-10 unread">
						<em class=" padding-5 no-border-radius  pull-left margin-right-5 ">
							<i class="fa fa-tablet fa-2x "></i>
						</em>
						<span>
							<strong><a class="display-normal" href="#'.$task_url.'">'.$task_label.' <i class="font-xs txt-color-orangeDark">('.$task_status.')</i></a></strong>
							<p>'.$task_filename.'</p>
						</span>
					</span>
				</li>
			';
			echo '</ul>';
		}
		else
		{
			echo '
				<div class="alert alert-transparent">
					<h4 class="text-center">'._("No running tasks").'</h4>
				</div>
			';
		}
		
	}
	
	/**
	 *
	 */
	public function notifications()
	{
		echo '
				<div class="alert alert-transparent">
					<h4>Click a button to show messages here</h4>
					This blank page message helps protect your privacy, or you can show the first message here automatically.
				</div>
				<i class="fa fa-lock fa-4x fa-border"></i>
			';
	}
	
	/***
	 *
	 */
	public function minify($folder = '')
	{
		$this->load->library('Minifier', null, 'minifier');
		$this->load->config('layout');
		
		if(count( func_get_args()) > 0){
			$folder = '/'.implode('/', func_get_args());
		}
		
		
		$javascript = $this->config->item('javascript');
		$css        = $this->config->item('css');
		
		foreach($javascript['mandatory'] as $script){
			$this->minifier->addJS(FCPATH.$script);
		}
		
		foreach($css['mandatory'] as $script){
			$this->minifier->addCSS(FCPATH.$script);
		}
		
		$exportPathJS  = $folder != '' ? $folder : FCPATH.'/assets/js/';
		$exportPathCSS = $folder != '' ? $folder : FCPATH.'/assets/css/';
		
		$this->minifier->minifyJS($exportPathJS.'/mandatory.js');
		$this->minifier->minifyCSS($exportPathCSS.'/mandatory.css');
	}
	/**
	 * 
	 */
	public function firstSetup($action = '')
	{
		if($action == ''){
			$exists = file_exists($this->config->config['wizard_file']);
			$this->output->set_content_type('application/json')->set_output(json_encode(array('exists' => $exists)));
		}
		else if($action == 'finalize'){
			$this->load->helper('fabtotum_helper');
			if(file_exists($this->config->config['wizard_file'])){
				doCommandLine('sudo rm ', $this->config->config['wizard_file']);
			}	
		}
	}
	/**
	 *
	 */
	function saveDateTime()
	{
		$postData = $this->input->post();
		$this->load->helper('os_helper');
		
		setTimeZone($postData['timezone']);
		//set system date format = YYYY-MM-DD HH:mm:ss
		setSystemDate($postData['year'].'-'.$postData['month'].'-'.$postData['day'].' '.$postData['hour'].':'.$postData['minute']);
		restartLighttpd();
	}
	/**
	 * 
	 */
	function saveSystemInfo($column, $value)
	{
		$this->load->model('Configuration', 'configuration');
		$this->configuration->store($column,  $value);
		$this->load->helper('myfabtotum_helper');
		reload_myfabtotum();
		$this->output->set_content_type('application/json')->set_output(true);
	}
	/**
	 * 
	 */
	function updates()
	{
		$data = $this->_getUpdatesData();
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}
	
	/**
	 * 
	 */
	function setLanguage($lang)
	{
	    $this->load->helper(array('language_helper'));
	    setLanguage($lang, true);
	}
	
	/**
	 * 
	 */
	private function _getNetworkData()
	{
		$this->load->helper('os_helper');
		writeNetworkInfo();
		$networkInfo = getNetworkInfo();
		return $networkInfo;
	}
	
	/**
	 * 
	 */
	private function _getUpdatesData()
	{
		$this->load->config('fabtotum');
		
		if(!file_exists($this->config->item('updates_json_file'))){
			
			$this->load->helper('update_helper');
			$this->load->helper('file');
			$updateJSON = json_encode(getUpdateStatus());
			write_file($this->config->item('updates_json_file'), $updateJSON);
		}
		return json_decode(file_get_contents($this->config->item('updates_json_file'), true));
	}
	/**
	 * 
	 */
	private function _getSettingsData()
	{
		$this->load->helpers('fabtotum_helper');
		$settings = loadSettings();
		return $settings;
		
	}
	
}

?>
