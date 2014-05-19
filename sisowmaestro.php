<?php
/*
Plugin Name: WooCommerce Sisow Maestro
Plugin URI: http://www.sisow.nl
Description: The Sisow Maestro Plugin for WooCommerce
Version: 3.5.1
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_maestro_init', 0);

function woocommerce_maestro_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_maestro extends SisowBase
	{
		function __construct() 
		{
			$this->paymentcode 	= 'maestro';
			$this->paymentname 	= 'Sisow Maestro';
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
            
            $text .= '<img alt="maestro" title="" src="'.plugins_url() . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/logo/maestro.jpg'.'" width="67" height="42" border="0" />';
			if ($paymentfee_total > 0) {
				$text .= '</br></br>&nbsp;&nbsp;<b>' . $this->paymentfeelabel . ': ' . woocommerce_price($paymentfee_total) . '</b></br>';
			} 
				
			echo wpautop(wptexturize($text));
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_maestro_gateway' );
	
	function add_sisow_maestro_gateway($methods)
	{
		$temp = 'WC_Sisow_maestro';
		$methods[] = $temp;
		return $methods;
	}
}