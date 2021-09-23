<?php 
class ModelExtensionPaymentWaafipay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/waafipay');
		
		$method_data = array();
		$title=trim($this->config->get('waafipay_title'));
		if (empty($title)) {
			// $title=trim($this->language->get('text_title'));
			// $title=$this->config->get('payment_waafipay_title').' <img src="catalog/view/theme/default/image/waafi.png" style="width:10%" />';
			$title= $this->config->get('payment_waafipay_title');
			if (empty($title)) {
				$title= 'Waafipay';
			}
		}


		$method_data = array( 
			'code'		=> 'waafipay',
			'title'		=> $title,
			'terms' => '',
			'sort_order' => $this->config->get('payment_waafipay_sort_order')
		);
		
		return $method_data;
	}

}
?>