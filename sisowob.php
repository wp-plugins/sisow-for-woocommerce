<?php
/*
Plugin Name: WooCommerce Sisow OverBoeking
Plugin URI: http://www.sisow.nl
Description: The Sisow OverBoeking Plugin for WooCommerce
Version: 3.5.1
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_overboeking_init', 0);

function woocommerce_overboeking_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_overboeking extends SisowBase
	{
		function __construct() 
		{ 
			$this->paymentcode 	= 'overboeking';
			$this->paymentname 	= 'Sisow OverBoeking';
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
            
            $text .= 'U heeft ervoor gekozen om uw bestelling per bank/giro over te maken.
				De verwerking hiervan is uitbesteed aan Sisow B.V.<br/>
				U ontvangt een e-mail met daarin informatie hoe u uw betaling kunt voltooien.';
				
			if ($paymentfee_total > 0) {
                $text .= '&nbsp;&nbsp;<b>' . $this->paymentfeelabel. ': ' . woocommerce_price($paymentfee_total) . '</b></br>';
            }
			
			echo wpautop( wptexturize($text));
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_overboeking_gateway' );
	
	function add_sisow_overboeking_gateway($methods)
	{
		$temp = 'WC_Sisow_overboeking';
		$methods[] = $temp;
		return $methods;
	}
}