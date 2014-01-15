<?php
/*
Plugin Name: WooCommerce Sisow SofortBanking
Plugin URI: http://www.sisow.nl
Description: The Sisow SofortBanking Plugin for WooCommerce
Version: 3.3.13
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_sofort_init', 0);

function woocommerce_sofort_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_sofort extends SisowBase
	{
		function __construct() 
		{ 
			$this->paymentcode 	= 'sofort';
			$this->paymentname 	= 'Sisow SofortBanking';
			$this->redirect 	= true;
			parent::__construct();
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_sofort_gateway' );
	
	function add_sisow_sofort_gateway($methods)
	{
		$temp = 'WC_Sisow_sofort';
		$methods[] = $temp;
		return $methods;
	}
}