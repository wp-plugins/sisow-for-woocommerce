<?php
/*
Plugin Name: WooCommerce Sisow Maestro
Plugin URI: http://www.sisow.nl
Description: The Sisow Maestro Plugin for WooCommerce
Version: 4.3.9
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
		
		public function get_icon()
		{
			if($this->displaylogo == 'yes')
				return '<img alt="'.$this->paymentname.'" title="" src="'.plugins_url() . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/logo/'.$this->paymentcode.'.png'.'"';
		}
		
		public function payment_fields() {
			$paymentfee_total = $this->getFee();
			
			$text = '<b>'.__('Betalen met', 'sisow') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<br/><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '<br/><b>Let op Testmodus ingeschakeld!</b>';
            
			if ($paymentfee_total > 0) {
				$text .= '</br><b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b></br>';
			} 
				
			echo $text;
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