<?php
/*
Plugin Name: WooCommerce Sisow CreditCard
Plugin URI: http://www.sisow.nl
Description: The Sisow CreditCard Plugin for WooCommerce
Version: 3.3.11
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_creditcard_init', 0);

function woocommerce_creditcard_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_creditcard extends SisowBase
	{
		function __construct() 
		{
			$this->paymentcode 	= 'creditcard';
			$this->paymentname 	= 'Sisow CreditCard';
			$this->redirect 	= true;
			parent::__construct();
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_creditcard_gateway' );
	
	function add_sisow_creditcard_gateway($methods)
	{
		$temp = 'WC_Sisow_creditcard';
		$methods[] = $temp;
		return $methods;
	}
}