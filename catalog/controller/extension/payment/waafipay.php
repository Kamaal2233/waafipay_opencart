<?php
class ControllerExtensionPaymentWaafipay extends Controller {

	public function index() {
		header('Set-Cookie: ' . $this->config->get('session_name') . '=' . $this->session->getId() . '; SameSite=None; Secure');
		$this->write_log($this->session->data['order_id'], "Opencart initiated Waafi Payment for order");

		$data['button_confirm'] = $this->config->get('payment_waafipay_btntext');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$testmode=$this->_testmode($this->config->get('payment_waafipay_test'));
		$amount= $this->currency->format(
			$order_info['total'],
			$order_info['currency_code'],
			$order_info['currency_value'],
			false);
		
		$order_id = trim($order_info['order_id']);
		$cart_id = $order_id.'~'.(string)time();				
		//Billing details
		$post_data['bill_fname'] = trim($order_info['payment_firstname']);
		$post_data['bill_sname'] = trim($order_info['payment_lastname']);
		$post_data['bill_addr1'] = trim($order_info['payment_address_1']);
		$post_data['bill_addr2'] = trim($order_info['payment_address_2']);
		$post_data['bill_addr3'] = '';
		$post_data['bill_city'] = trim($order_info['payment_city']);
		$post_data['bill_region'] = trim($order_info['payment_zone']);
		$post_data['bill_zip'] = trim($order_info['payment_postcode']);
		$post_data['bill_ctry'] = trim($order_info['payment_iso_code_2']);
		$post_data['bill_email'] = trim($order_info['email']);
		$post_data['bill_phone1'] = trim($order_info['telephone']);
		$data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['currency'] = $order_info['currency_code'];
		$data['order_id'] = $this->session->data['order_id'];
		$data['payment_methods'] = $this->config->get('payment_waafipay_paymentmethods');
		$data['payment_description'] = $this->config->get('payment_waafipay_purdesc');
		$inputhtml = "";
		if(is_array($this->config->get('payment_waafipay_paymentmethods')) && count($this->config->get('payment_waafipay_paymentmethods')) > 1){
			foreach($this->config->get('payment_waafipay_paymentmethods') as $waafi_payment_types){
				
				if($waafi_payment_types == "CREDIT_CARD"){
					$txt_waafi = "Credit Card";
				}elseif($waafi_payment_types == "MWALLET_ACCOUNT"){
					$txt_waafi = "MWALLET ACCOUNT";
				}elseif($waafi_payment_types == "MWALLET_BANKACCOUNT"){
					$txt_waafi = "MWALLET BANKACCOUNT";
				}
				$inputhtml .=  '<div class="form-row form-row-wide">
									<input id="waafi_pay_from_macc" name="waafi_pay_from" value="'.$waafi_payment_types.'" class="input-radio"  type="radio" />
									<span style="font-size: 16px;margin-left: 12px;">'.$txt_waafi.'</span>
								</div>';
			}
		}elseif(count($this->config->get('payment_waafipay_paymentmethods')) == 1){
			foreach($this->config->get('payment_waafipay_paymentmethods') as $waafi_payment_types){
				$inputhtml .=  '<div class="form-row form-row-wide">
									<input id="waafi_pay_from_macc" name="waafi_pay_from" value="'.$waafi_payment_types.'"  type="hidden" />
								</div>';
			}
		}elseif(empty($this->config->get('payment_waafipay_paymentmethods'))){
			$inputhtml .=  '<div class="form-row form-row-wide">
									<input id="waafi_pay_from_macc" name="waafi_pay_from" value="CREDIT_CARD"   type="hidden" />
								</div>';
		}
		
		$data['inputhtml'] = $inputhtml;
		
		return $this->load->view('extension/payment/waafipay',$data);
		
	}
	
	public function send() {
		header('Set-Cookie: ' . $this->config->get('session_name') . '=' . $this->session->getId() . '; SameSite=None; Secure');
		$testmode=$this->_testmode($this->config->get('payment_waafipay_test'));		
		$apiurl = $testmode ? 'https://sandbox.safarifoneict.com/asm' : 'https://sandbox.safarifoneict.com/asm';
		$this->load->model('checkout/order');

		if(!isset($this->session->data['order_id'])) {
			return false;
		}

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$orderid = $this->session->data['order_id'];
		
		if(!empty($this->request->post['waafi_pay_from'])){
			$pay_from = $this->request->post['waafi_pay_from'];
		}elseif(!empty($this->request->post['waafi_pay_fromm'])){
			$pay_from = $this->request->post['waafi_pay_fromm'];
		}else{
			$pay_from = "CREDIT_CARD";
		}
		
		$order_amount = $this->request->post['amount'];
		$currency_code = $order_info['currency_code'];
		
		$requestId = $this->generateRandomString();
		
		if($testmode){
			$storeId = $this->config->get('payment_waafipay_test_store');
			$hppKey = $this->config->get('payment_waafipay_test_hppkey');
			$merchantUid = $this->config->get('payment_waafipay_test_merid');
		}else{
			$storeId = $this->config->get('payment_waafipay_store');
			$hppKey = $this->config->get('payment_waafipay_hppkey');
			$merchantUid = $this->config->get('payment_waafipay_merid');
		}
		$timestamp = time();
		$referenceId = $timestamp.$orderid;
		$invoiceId = $timestamp.$orderid;
		
		$cust_redirecturlsuc = $this->url->link('extension/payment/waafipay/waafisuccess', array('cart_id' => $orderid), true);
		$cust_redirecturlfail = $this->url->link('extension/payment/waafipay/waafifail', array('cart_id' => $orderid), true);
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $apiurl,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
			"schemaVersion"     : "1.0",
			"requestId"         : "'.$requestId.'",
			"timestamp"         : "'.$timestamp.'",
			"channelName"       : "WEB",
			"serviceName"       : "HPP_PURCHASE",
			"serviceParams": {
					"storeId"               : "'.$storeId.'",
					"hppKey"                : "'.$hppKey.'",  
					"merchantUid"           : "'.$merchantUid.'",
					"hppSuccessCallbackUrl" : "'.$cust_redirecturlsuc.'",
					"hppFailureCallbackUrl" : "'.$cust_redirecturlfail.'",
					"hppRespDataFormat"     : "4",  
					"paymentMethod"         : "'.$pay_from.'",
					"transactionInfo"       : {
							"referenceId"   : "'.$referenceId.'",
							"invoiceId"     : "'.$invoiceId.'",
							"amount"        : "'.$order_amount.'",
							"currency"      : "'.$currency_code.'",
							"description"   : "Opencart Order Payment"
					}
			}
		}',
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		  ),
		));
		$returnData = json_decode(curl_exec($curl),true);
		
		$json = array();
		
		
		if($returnData['errorCode'] == 0 && $returnData['responseMsg'] == "RCS_SUCCESS"){
			$returnurl = $returnData['params']['hppUrl'];
			$hppRequestId = $returnData['params']['hppRequestId'];
			$referenceId = $returnData['params']['referenceId'];
			
			
			
			$json['returnurl'] = $returnurl;
			$json['hppRequestId'] = $hppRequestId;
			$json['referenceId'] = $referenceId;
			$json['pay_from'] = $pay_from;
			
			$this->db->query("UPDATE " . DB_PREFIX . "order SET payment_custom_field = '" .$json['pay_from']. "' WHERE order_id = " . $orderid);
		}else{
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);
			$this->log->write('Waafipay ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));		
		}
		curl_close($curl);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}
	
	public function waafisuccess(){
		header('Set-Cookie: ' . $this->config->get('session_name') . '=' . $this->session->getId() . '; SameSite=None; Secure');
		$testmode=$this->_testmode($this->config->get('payment_waafipay_test'));		
		$apiurl = $testmode ? 'https://sandbox.waafipay.net/asm' : 'https://api.waafipay.net/asm';
		
		if($testmode){
			$storeId = $this->config->get('payment_waafipay_test_store');
			$hppKey = $this->config->get('payment_waafipay_test_hppkey');
			$merchantUid = $this->config->get('payment_waafipay_test_merid');
		}else{
			$storeId = $this->config->get('payment_waafipay_store');
			$hppKey = $this->config->get('payment_waafipay_hppkey');
			$merchantUid = $this->config->get('payment_waafipay_merid');
		}
		// $storeId = $this->config->get('payment_waafipay_store');
		// $hppKey = $this->config->get('payment_waafipay_hppkey');
		// $merchantUid = $this->config->get('payment_waafipay_merid');
		
		$explodedid = explode("?",$_REQUEST['cart_id']);
		$order_id = $explodedid[0];
		$explodedresponse = explode("=",$explodedid[1]);
		if($explodedresponse[0] == "hppResultToken"){
			$hppResultToken = $explodedresponse[1];
		}
		if(!empty($hppResultToken)){
			$timestamp = time();
			$requestId = $this->generateRandomString();
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => $apiurl,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>'{
				"schemaVersion"     : "1.0",            
				"requestId"         : "'.$requestId.'",
				"timestamp"         : "'.$timestamp.'",
				"channelName"       : "WEB",
				"serviceName"       : "HPP_GETRESULTINFO",
				"serviceParams"     : {
					"storeId"       : "'.$storeId.'",
					"hppKey"        : "'.$hppKey.'",
					"merchantUid"   : "'.$merchantUid.'", 
					"hppResultToken": "'.$hppResultToken.'"
				}
			}   ',
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);
			$error = curl_errno($curl);
			curl_close($curl);
			$responsarr = json_decode($response,TRUE);
			
			
			
			if($responsarr['responseCode'] == 2001){
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_id = '".$order_id."'");
				$orderreturn = $query->row;
				
				$existingjson = $orderreturn['payment_custom_field'];
				if($existingjson == "CREDIT_CARD"){
					$order_paymnttyp_name = " ( Credit Card )";
				}elseif($existingjson == "MWALLET_ACCOUNT"){
					$order_paymnttyp_name = " ( MWALLET ACCOUNT )";
				}elseif($existingjson == "MWALLET_BANKACCOUNT"){
					$order_paymnttyp_name = " ( MWALLET BANKACCOUNT )";
				}
				$newmethodname = $orderreturn['payment_method'].$order_paymnttyp_name;
				
				$newjsonarr= array();
				$newjsonarr['method'] = $existingjson;
				$newjsonarr['hppResultToken'] = $hppResultToken;
				$newjsonarr['procCode'] = $responsarr['params']['procCode'];
				$newjsonarr['procDescription'] = $responsarr['params']['procDescription'];
				$newjsonarr['transactionId'] = $responsarr['params']['transactionId'];
				$newjsonarr['issuerTransactionId'] = $responsarr['params']['issuerTransactionId'];
				$newjsonarr['txAmount'] = $responsarr['params']['txAmount'];
				$newjsonarr['state'] = $responsarr['params']['state'];
				
				$this->db->query("UPDATE " . DB_PREFIX . "order SET payment_method = '".$newmethodname."' , payment_custom_field = '" .json_encode($newjsonarr). "' WHERE order_id = " . $order_id);
				
							
				$this->payment_authorised($order_id,$responsarr['params']['procCode']);
				$this->response->redirect($this->url->link('checkout/success'));
				exit;
			}
			
			
		}
		
	}
	
	public function waafifail(){
		header('Set-Cookie: ' . $this->config->get('session_name') . '=' . $this->session->getId() . '; SameSite=None; Secure');
		$testmode=$this->_testmode($this->config->get('payment_waafipay_test'));		
		$apiurl = $testmode ? 'https://sandbox.safarifoneict.com/asm' : 'https://sandbox.safarifoneict.com/asm';
		
		if($testmode){
			$storeId = $this->config->get('payment_waafipay_test_store');
			$hppKey = $this->config->get('payment_waafipay_test_hppkey');
			$merchantUid = $this->config->get('payment_waafipay_test_merid');
		}else{
			$storeId = $this->config->get('payment_waafipay_store');
			$hppKey = $this->config->get('payment_waafipay_hppkey');
			$merchantUid = $this->config->get('payment_waafipay_merid');
		}
		
		// $storeId = $this->config->get('payment_waafipay_store');
		// $hppKey = $this->config->get('payment_waafipay_hppkey');
		// $merchantUid = $this->config->get('payment_waafipay_merid');
		$explodedid = explode("?",$_REQUEST['cart_id']);
		$order_id = $explodedid[0];
		$explodedresponse = explode("=",$explodedid[1]);
		if($explodedresponse[0] == "hppResultToken"){
			$hppResultToken = $explodedresponse[1];
		}
		if(!empty($hppResultToken)){
			$timestamp = time();
			$requestId = $this->generateRandomString();
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => $apiurl,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>'{
				"schemaVersion"     : "1.0",            
				"requestId"         : "'.$requestId.'",
				"timestamp"         : "'.$timestamp.'",
				"channelName"       : "WEB",
				"serviceName"       : "HPP_GETRESULTINFO",
				"serviceParams"     : {
					"storeId"       : "'.$storeId.'",
					"hppKey"        : "'.$hppKey.'",
					"merchantUid"   : "'.$merchantUid.'", 
					"hppResultToken": "'.$hppResultToken.'"
				}
			}   ',
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);
			$error = curl_errno($curl);
			curl_close($curl);
			$responsarr = json_decode($response,TRUE);
			if($responsarr['responseCode'] == 2001){
				//$this->payment_pending($order_id,$responsarr['params']['procCode']);
				$this->session->data['error'] = 'Payment Failed';
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
				exit;
			}
		}
		
	}
	
	public function generateRandomString($length = 15) {
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public function payment_authorised($order_id,$txref) {
		$this->load->model('checkout/order');
		$message='Payment Completed: '.$txref;
		$order_status = $cart_desc=trim($this->config->get('payment_waafipay_comp_status_id'));
		if (empty($order_status)) {
			$order_status='2'; // Order status 2 = Processing
		}
		$this->model_checkout_order->addOrderHistory(
			$order_id,		// Order ID
			$order_status,		// New order status
			$message,		// Message text to add to order history
			true);			// Notify customer
	}

	

	public function payment_cancelled($order_id,$txref) {
		$this->load->model('checkout/order');
		$message='Payment Cancelled: '.$txref;
		$order_status = $cart_desc=trim($this->config->get('payment_waafipay_void_status_id'));
		if (empty($order_status)) {
			$order_status='7'; // Order status 2 = Cancelled
		}

		$pendingStatusId = $this->config->get('payment_telr_pend_status_id');
		$failedStatusId = $this->config->get('payment_telr_void_status_id');
		$currentStatusId = $this->get_order_status_id($order_id);

		if($currentStatusId == $pendingStatusId || $currentStatusId == $failedStatusId){
			$this->model_checkout_order->addOrderHistory(
				$order_id,		// Order ID
				$order_status,		// New order status
				$message,		// Message text to add to order history
				true);			// Notify customer
		}
	}

	public function payment_pending($order_id,$txref) {
		$this->load->model('checkout/order');
		$message='Payment Pending: '.$txref;
		$order_status = $cart_desc=trim($this->config->get('payment_waafipay_pend_status_id'));
		if (empty($order_status)) {
			$order_status='1'; // Order status 1 = Pending
		}

		$pendingStatusId = $this->config->get('payment_telr_pend_status_id');
		$failedStatusId = $this->config->get('payment_telr_void_status_id');
		$currentStatusId = $this->get_order_status_id($order_id);

		if($currentStatusId == $pendingStatusId || $currentStatusId == $failedStatusId){
			$this->model_checkout_order->addOrderHistory(
				$order_id,		// Order ID
				$order_status,		// New order status
				$message,		// Message text to add to order history
				true);			// Notify customer
		}
	}

	private function get_order_status_id($order_id){
		$query = $this->db->query("SELECT order_status_id FROM " . DB_PREFIX . "order WHERE order_id = " . $order_id);
		$orders = $query->rows;
		if(count($orders) > 0){
			return $orders[0]['order_status_id'];
		}else{
			return 0;
		}
	}


	
	
	private function _testmode($waafipay_testmode) {
		if (strcasecmp($waafipay_testmode,'live')==0) { return 0; }
		if (strcasecmp($waafipay_testmode,'no')==0) { return 0; }
		return 1;
	}

	private function write_log($orderId, $string){
		return true;
	}
}
?>
