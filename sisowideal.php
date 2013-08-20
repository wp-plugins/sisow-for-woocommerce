<?php
/*
Plugin Name: WooCommerce Sisow iDEAL
Plugin URI: http://www.sisow.nl
Description: The Sisow iDEAL Plugin for WooCommerce
Version: 3.3.1
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_ideal_init', 0);

function woocommerce_ideal_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_ideal extends SisowBase
	{
		function __construct() 
		{
			$this->_start('ideal', 'Sisow iDEAL');
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_ideal_gateway' );
	
	function add_sisow_ideal_gateway($methods)
	{
		$temp = 'WC_Sisow_ideal';
		$methods[] = $temp;
		return $methods;
	}
}