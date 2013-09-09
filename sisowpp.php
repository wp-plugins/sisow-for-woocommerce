<?php
/*
Plugin Name: WooCommerce Sisow PayPal
Plugin URI: http://www.sisow.nl
Description: The Sisow PayPal Plugin for WooCommerce
Version: 3.3.5
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_paypalec_init', 0);

function woocommerce_paypalec_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_paypalec extends SisowBase
	{
		
		function __construct() 
		{ 
			$this->_start('paypalec', 'Sisow PayPal', true);
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_paypalec_gateway' );
	
	function add_sisow_paypalec_gateway($methods)
	{
		$temp = 'WC_Sisow_paypalec';
		$methods[] = $temp;
		return $methods;
	}
}