<?php
/*
Plugin Name: WooCommerce Sisow ebill
Plugin URI: http://www.sisow.nl
Description: The Sisow ebill Plugin for WooCommerce
Version: 3.5.1
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_ebill_init', 0);

function woocommerce_ebill_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_ebill extends SisowBase
	{
		function __construct() 
		{
			$this->paymentcode 	= 'ebill';
			$this->paymentname 	= 'Sisow ebill';
			$this->redirect 	= false;
			parent::__construct();
		}
		
		public function payment_fields() 
		{
			global $woocommerce;
			$paymentfee_total = $this->getFee();
			
			$text = '';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b></br>';
			
			if($this->testmode == 'yes')
				$text .= '<b>Let op Testmodus ingeschakeld!</b></br>';
            
            $text .=  'U heeft ervoor gekozen om uw bestelling per digitale acceptgiro over te maken.
				De verwerking hiervan is uitbesteed aan Sisow B.V.<br/>
				U ontvangt een e-mail met daarin informatie hoe u uw betaling kunt voltooien.';
				
			if ($paymentfee_total > 0) {
                $text .= '&nbsp;&nbsp;<b>' . $this->paymentfeelabel. ': ' . woocommerce_price($paymentfee_total) . '</b></br>';
            }
			
			echo wpautop( wptexturize($text));
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_ebill_gateway' );
	
	function add_sisow_ebill_gateway($methods)
	{
		$temp = 'WC_Sisow_ebill';
		$methods[] = $temp;
		return $methods;
	}
}