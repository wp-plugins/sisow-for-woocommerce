<?php
/*
Plugin Name: WooCommerce Sisow Fijncadeau
Plugin URI: http://www.sisow.nl
Description: The Sisow Fijncadeau Plugin for WooCommerce
Version: 3.3.0
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_fijncadeau_init', 0);

function woocommerce_fijncadeau_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_fijncadeau extends SisowBase
	{
		function __construct() 
		{ 
			$this->_start('fijncadeau', 'Sisow Fijncadeau');
		}
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_fijncadeau_gateway' );
	
	function add_sisow_fijncadeau_gateway($methods)
	{
		$temp = 'WC_Sisow_fijncadeau';
		$methods[] = $temp;
		return $methods;
	}
}