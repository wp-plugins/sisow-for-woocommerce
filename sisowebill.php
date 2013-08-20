<?php
/*
Plugin Name: WooCommerce Sisow ebill
Plugin URI: http://www.sisow.nl
Description: The Sisow ebill Plugin for WooCommerce
Version: 3.3.2
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
			$this->_start('ebill', 'Sisow ebill');
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