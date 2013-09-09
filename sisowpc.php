<?php
/*
Plugin Name: WooCommerce Sisow Podium Cadeaukaart
Plugin URI: http://www.sisow.nl
Description: The Sisow Podium Cadeaukaart Plugin for WooCommerce
Version: 3.3.4
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_podium_init', 0);

function woocommerce_podium_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_podium extends SisowBase
	{
		function __construct() 
		{ 
			$this->_start('podium', 'Sisow Podium Cadeaukaart');
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_podium_gateway' );
	
	function add_sisow_podium_gateway($methods)
	{
		$temp = 'WC_Sisow_podium';
		$methods[] = $temp;
		return $methods;
	}
}