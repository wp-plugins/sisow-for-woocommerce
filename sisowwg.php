<?php
/*
Plugin Name: WooCommerce Sisow Webshop Giftcard
Plugin URI: http://www.sisow.nl
Description: The Sisow Webshop Giftcard Plugin for WooCommerce
Version: 4.0.0
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_webshop_init', 0);

function woocommerce_webshop_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_webshop extends SisowBase
	{
		function __construct() 
		{ 
			$this->paymentcode 	= 'webshop';
			$this->paymentname 	= 'Sisow Webshop Giftcard';
			$this->redirect 	= true;
			parent::__construct();
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_webshop_gateway' );
	
	function add_sisow_webshop_gateway($methods)
	{
		$temp = 'WC_Sisow_webshop';
		$methods[] = $temp;
		return $methods;
	}
}