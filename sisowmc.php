<?php
/*
Plugin Name: WooCommerce Sisow MisterCash
Plugin URI: http://www.sisow.nl
Description: The Sisow MisterCash Plugin for WooCommerce
Version: 3.5.1
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_mistercash_init', 0);

function woocommerce_mistercash_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_mistercash extends SisowBase
	{
		function __construct() 
		{ 
			$this->paymentcode 	= 'mistercash';
			$this->paymentname 	= 'Sisow MisterCash';
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
            
            $text .= '<img src="https://www.sisow.nl/Sisow/images/ideal/mister.gif" alt="Sisow MisterCash" />';
			if ($paymentfee_total > 0) {
				$text .= '</br></br>&nbsp;&nbsp;<b>' . $this->paymentfeelabel . ': ' . woocommerce_price($paymentfee_total) . '</b></br>';
			}
			
			echo wpautop(wptexturize($text));
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_mistercash_gateway' );

	function add_sisow_mistercash_gateway($methods)
	{
		$temp = 'WC_Sisow_mistercash';
		$methods[] = $temp;
		return $methods;
	}
}