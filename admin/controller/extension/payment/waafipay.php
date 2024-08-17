<?php
class ControllerExtensionPaymentWaafipay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/waafipay');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if(!$this->version_ok()) {
			$this->error['warning'] = "This module is not supported on this version of OpenCart. Please upgrade to OpenCart 3.0.0 or later";
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->request->post['payment_waafipay_defaults']='set';
			$this->model_setting_setting->editSetting('payment_waafipay', $this->request->pot);
			$this->session->data['success'] = $this->language->get('text_sucess');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=payment', true));

		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_live'] = $this->language->get('text_live');
		$data['text_test'] = $this->language->get('text_test');
		
		$data['entry_purdesc'] = $this->language->get('entry_purdesc');

		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_hppkey'] = $this->language->get('entry_hppkey');
		$data['entry_merchantId'] = $this->language->get('entry_merchantId');
		$data['entry_callback'] = $this->language->get('entry_callback');
		$data['entry_test'] = $this->language->get('entry_test');		
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_pend_status'] = $this->language->get('entry_pend_status');
		$data['entry_comp_status'] = $this->language->get('entry_comp_status');
		$data['entry_void_status'] = $this->language->get('entry_void_status');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['help_store'] = $this->language->get('help_store');
		$data['help_hppkey'] = $this->language->get('help_hppkey');
		$data['help_merid'] = $this->language->get('help_merid');
		$data['help_callback'] = $this->language->get('help_callback');
		$data['help_test'] = $this->language->get('help_test');		
		$data['help_total'] = $this->language->get('help_total');
		$data['help_title'] = $this->language->get('help_title');
		$data['help_status'] = $this->language->get('help_status');


		$data['button_save'] = $this->language->get('button_sav');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['purdesc'])) {
			$data['error_purdesc] = $this->error['purdesc'];
		} else {
			$data['error_purdesc'] = '';
		}

		if (isset($this->error['store'])) {
			$data['error_store'] = $this->error['store'];
		} else {
			$data['error_store'] = '';
		}

		if (isset($this->error['hppkey'])) {
			$data['error_hppkey'] = $this->error['hppkey'];
		} else {
			$data['error_hppkey'] = '';
		}
		
		if (isset($this->error['merid'])) {
			$data['error_merid'] = $this->error['merid'];
		} else {
			$data['error_merid'] = '';
		}
		
		
		if (isset($this->error['test_store'])) {
			$data['test_error_store'] = $this->error['test_store'];
		} else {
			$data['test_error_store'] = '';
		}

		if (isset($this->error['test_hppkey') {
			$data['test_error_hppkey'] = $this->error['test_hppkey'];
		} else {
			$data['test_error_hppkey'] = '';
		}
		
		if (isset($this->error['test_merid'])) {
			$data['test_error_merid'] = $this->error['test_merid'];
		} else {
			$data['test_error_merid'] = '';
		}
		
		

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'	=> $this->language->get('text_home'),
			'href'	=> $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text'	=> $this->language->get('text_payment'),
			'href'	=> $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
			'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'	=> $this->language->get('heading_title'),
			'href'	=> $this->url->link('extension/payment/waafipay', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);

		$data['action'] = $this->url->link('extension/payment/waafipay', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

		//$data['callback'] = HTTP_CATALOG . 'index.php?route=payment/waafipay/callback';
		
		if (isset($this->request->post['payment_waafipay_purdesc'])) {
			$data['payment_waafipay_purdesc'] = $this->request->post['payment_waafipay_purdesc'];
		} else {
			$data['payment_waafipay_purdesc'] = $this->config->get('payment_waafipay_purdesc');
		}

		if (isset($this->request->post['payment_waafipay_store'])) {
			$data['payment_waafipay_store'] = $this->request->post['payment_waafipay_store'];
		} else {
			$data['payment_waafipay_store'] = $this->config->get('payment_waafipay_store');
		}

		if (isset($this->request->post['payment_waafipay_hppkey'])) {
			$data['payment_waafipay_hppkey'] = $this->request->post['payment_waafipay_hppkey'];
		} else {
			$data['payment_waafipay_hppkey'] = $this->config->get('payment_waafipay_hppkey');
		}
		
		if (isset($this->request->post['payment_waafipay_merid'])) {
			$data['payment_waafipay_merid'] = $this->request->post['payment_waafipay_merid'];
		} else {
			$data['payment_waafipay_merid'] = $this->config->get('payment_waafipay_merid');
		}
		
		
		
		if (isset($this->request->post['payment_waafipay_test_store'])) {
			$data['payment_waafipay_test_store'] = $this->request->post['payment_waafipay_test_store'];
		} else {
			$data['payment_waafipay_test_store'] = $this->config->get('payment_waafipay_test_store');
		}

		if (isset($this->request->post['payment_waafipay_test_hppkey'])) {
			$data['payment_waafipay_test_hppkey'] = $this->request->post['payment_waafipay_test_hppkey'];
		} else {
			$data['payment_waafipay_test_hppkey'] = $this->config->get('payment_waafipay_test_hppkey');
		}
		
		if (isset($this->request->post['payment_waafipay_test_merid'])) {
			$data['payment_waafipay_test_merid'] = $this->request->post['payment_waafipay_test_merid'];
		} else {
			$data['payment_waafipay_test_merid'] = $this->config->get('payment_waafipay_test_merid');
		}


		if (isset($this->request->post['payment_waafipay_test'])) {
			$data['payment_waafipay_test'] = $this->request->post['payment_waafipay_test'];
		} else {
			$data['payment_waafipay_test'] = $this->config->get('payment_waafipay_test');
			if (empty($data['payment_waafipay_test'])) {
				$data['payment_waafipay_test']='Yes';
			}
		}
		
		if (isset($this->request->post['payment_waafipay_btntext'])) {
			$data['payment_waafipay_btntext'] = $this->request->post['payment_waafipay_btntext'];
		} else {
			$data['payment_waafipay_btntext'] = $this->config->get('payment_waafipay_btntext');
			if (empty($data['payment_waafipay_btntext'])) {
				$data['payment_waafipay_btntext']='Pay Now';
			}
		}
		
		if (isset($this->request->post['payment_waafipay_paymentmethods'])) {
			$data['payment_waafipay_paymentmethods'] = $this->request->post['payment_waafipay_paymentmethods'];
		} else {
			$data['payment_waafipay_paymentmethods'] = $this->config->get('payment_waafipay_paymentmethods');
			//if (empty($data['payment_waafipay_paymentmethods'])) {
				//$data['payment_waafipay_paymentmethods']='';
			//}
		}


		if (isset($this->request->post['payment_waafipay_title'])) {
			$data['payment_waafipay_title'] = $this->request->post['payment_waafipay_title'];
		} else {
			$data['payment_waafipay_title'] = $this->config->get('payment_waafipay_title');
			if (empty($data['payment_waafipay_title'])) {
				$data['payment_waafipay_title']='Waafipay';
			}
		}

		if (isset($this->request->post['payment_waafipay_pend_status_id'])) {
			$data['payment_waafipay_pend_status_id'] = $this->request->post['payment_waafipay_pend_status_id'];
		} else {
			$data['payment_waafipay_pend_status_id'] = $this->config->get('payment_waafipay_pend_status_id');
			if (empty($data['payment_waafipay_pend_status_id'])) {
				$data['payment_waafipay_pend_status_id']='1';
			}
		}
		if (isset($this->request->post['payment_waafipay_comp_status_id'])) {
			$data['payment_waafipay_comp_status_id'] = $this->request->post['payment_waafipay_comp_status_id'];
		} else {
			$data['payment_waafipay_comp_status_id'] = $this->config->get('payment_waafipay_comp_status_id');
			if (empty($data['payment_waafipay_comp_status_id'])) {
				$data['payment_waafipay_comp_status_id']='2';
			}
		}
		if (isset($this->request->post['payment_waafipay_void_status_id'])) {
			$data['payment_waafipay_void_status_id'] = $this->request->post['payment_waafipay_void_status_id'];
		} else {
			$data['payment_waafipay_void_status_id'] = $this->config->get('payment_waafipay_void_status_id');
			if (empty($data['payment_waafipay_void_status_id'])) {
				$data['payment_waafipay_void_status_id']='7';
			}
		}


		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		
		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_waafipay_status'])) {
			$data['payment_waafipay_status'] = $this->request->post['payment_waafipay_status'];
		} else {
			$data['payment_waafipay_status'] = $this->config->get('payment_waafipay_status');
		}

		
		$defaults=$this->config->get('payment_waafipay_defaults');
		if (empty($defaults)) {
			$data['payment_waafipay_title'] = 'Waafipay';	// Module Title
			$data['payment_waafipay_status'] = 'Yes';				// Test mode
			$data['payment_waafipay_pend_status_id'] = '1';			// Pending
			$data['payment_waafipay_comp_status_id'] = '2';			// Processing
			$data['payment_waafipay_void_status_id'] = '7';			// Cancelled
			$data['payment_waafipay_status'] = '1';				// Enabled					
		}
	
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/waafipay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/waafipay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$store = $this->request->post['payment_waafipay_store'];
		$teststore = $this->request->post['payment_waafipay_test_store'];
		$this->request->post['payment_waafipay_test_store'] = (string)$teststore;
		$this->request->post['payment_waafipay_store'] = (string)$store;

		if ($store<=0) {
			$this->error['store'] = $this->language->get('error_store');
		}
		
		if ($teststore<=0) {
			$this->error['test_store'] = $this->language->get('error_store');
		}


		if (!$this->request->post['payment_waafipay_hppkey']) {
			$this->error['hppkey'] = $this->language->get('error_hppkey');
		}
		
		if (!$this->request->post['payment_waafipay_test_hppkey']) {
			$this->error['test_hppkey'] = $this->language->get('error_hppkey');
		}
		
		if (!$this->request->post['payment_waafipay_merid']) {
			$this->error['merid'] = $this->language->get('error_merid');
		}
		
		
		if (!$this->request->post['payment_waafipay_test_merid']) {
			$this->error['test_merid'] = $this->language->get('error_merid');
		}
		
		if (empty($this->request->post['payment_waafipay_purdesc'])) {
			$this->error['purdesc'] = $this->language->get('error_purdesc');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	private function version_ok() {
		if (version_compare(VERSION, '3.0.0.0', '<')) {
			return false;
		}
		return true;
	}

}
?>
