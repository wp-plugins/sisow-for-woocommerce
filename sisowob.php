<?php
/*
Plugin Name: WooCommerce Sisow OverBoeking
Plugin URI: http://www.sisow.nl
Description: The Sisow OverBoeking Plugin for WooCommerce
Version: 3.2.2
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
			$this->_start('overboeking', 'Sisow OverBoeking');
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