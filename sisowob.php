<?php
/*
Plugin Name: WooCommerce Sisow OverBoeking
Plugin URI: http://www.sisow.nl
Description: The Sisow OverBoeking Plugin for WooCommerce
Version: 4.3.9
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
			
			$text = '<b>'.__('Betalen met', 'sisow') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '</br><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '</br><b>Let op Testmodus ingeschakeld!</b>';
            
            $text .= '</br>U heeft ervoor gekozen om uw bestelling per bank/giro over te maken.
				De verwerking hiervan is uitbesteed aan Sisow B.V.<br/>
				U ontvangt een e-mail met daarin informatie hoe u uw betaling kunt voltooien.';
				
			if ($paymentfee_total > 0) {
                $text .= '<br/><b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b></br>';
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