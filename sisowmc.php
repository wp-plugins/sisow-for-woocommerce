<?php
/*
Plugin Name: WooCommerce Sisow MisterCash
Plugin URI: http://www.sisow.nl
Description: The Sisow MisterCash Plugin for WooCommerce
Version: 3.3.9
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_mistercash_init', 0);

function woocommerce_mistercash_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_mistercash extends SisowBase
	{
		function __construct() 
		{ 
			$this->paymentcode 	= 'mistercash';
			$this->paymentname 	= 'Sisow MisterCash';
			$this->redirect 	= true;
			parent::__construct();
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_mistercash_gateway' );

	function add_sisow_mistercash_gateway($methods)
	{
		$temp = 'WC_Sisow_mistercash';
		$methods[] = $temp;
		return $methods;
	}
}