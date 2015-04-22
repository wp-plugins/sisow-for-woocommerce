<?php
/*
Plugin Name: WooCommerce Sisow Visa
Plugin URI: http://www.sisow.nl
Description: The Sisow Visa Plugin for WooCommerce
Version: 4.3.3
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_visa_init', 0);

function woocommerce_visa_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_visa extends SisowBase
	{
		function __construct() 
		{
			$this->paymentcode 	= 'visa';
			$this->paymentname 	= 'Sisow Visa';
			$this->redirect 	= true;
			parent::__construct();
		}
		
		public function get_icon(){
			return '<img alt="visa" title="" src="'.plugins_url() . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/logo/visa.jpg'.'"/>';
		}
		
		public function payment_fields() {
			$paymentfee_total = $this->getFee();
			
			$text = '<b>'.__('Betalen met') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<br/><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '<br/><b>Let op Testmodus ingeschakeld!</b>';
            
			if ($paymentfee_total > 0) {
				$text .= '</br><b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b>';
			} 
				
			echo wpautop(wptexturize($text));
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_visa_gateway' );
	
	function add_sisow_visa_gateway($methods)
	{
		$temp = 'WC_Sisow_visa';
		$methods[] = $temp;
		return $methods;
	}
}