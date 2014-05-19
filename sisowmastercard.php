<?php
/*
Plugin Name: WooCommerce Sisow Mastercard
Plugin URI: http://www.sisow.nl
Description: The Sisow Mastercard Plugin for WooCommerce
Version: 3.5.1
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_mastercard_init', 0);

function woocommerce_mastercard_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_mastercard extends SisowBase
	{
		function __construct() 
		{
			$this->paymentcode 	= 'mastercard';
			$this->paymentname 	= 'Sisow Mastercard';
			$this->redirect 	= true;
			parent::__construct();
		}
		
		public function payment_fields() {
			$paymentfee_total = $this->getFee();
			
			$text = '';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b></br>';
			
			if($this->testmode == 'yes')
				$text .= '<b>Let op Testmodus ingeschakeld!</b></br>';
            
            $text .= '<img alt="mastercard" title="" src="http://www.credit-card-logos.com/images/mastercard_credit-card-logos/mastercard_logo_8.gif" width="67" height="42" border="0" />';
			if ($paymentfee_total > 0) {
				$text .= '</br></br>&nbsp;&nbsp;<b>' . $this->paymentfeelabel . ': ' . woocommerce_price($paymentfee_total) . '</b></br>';
			} 
				
			echo wpautop(wptexturize($text));
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_mastercard_gateway' );
	
	function add_sisow_mastercard_gateway($methods)
	{
		$temp = 'WC_Sisow_mastercard';
		$methods[] = $temp;
		return $methods;
	}
}