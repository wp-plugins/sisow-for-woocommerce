<?php
/*
  Plugin Name: WooCommerce Sisow Giropay
  Plugin URI: http://www.sisow.nl
  Description: The Sisow Giropay Plugin for WooCommerce
  Version: 4.3.0
  Author: Sisow
  Author URI: http://www.sisow.nl
 */

add_action('plugins_loaded', 'woocommerce_giropay_init', 0);

function woocommerce_giropay_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/sisow/sisow.cls5.php');
    require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/sisow/base.php');

    class WC_Sisow_giropay extends SisowBase {

        function __construct() {
            $this->paymentcode = 'giropay';
            $this->paymentname = 'Sisow Giropay';
            $this->redirect = true;
			
			wp_enqueue_script( "sisow_giropay_script", "https://www.sisow.nl/Sisow/scripts/giro-eps.js", array('jquery'));
			wp_enqueue_style( "sisow-giropay-css", "https://bankauswahl.giropay.de/widget/v1/style.css");
			
            parent::__construct();
        }

        public function payment_fields() {
            $paymentfee_total = $this->getFee();
            $testmode = ($this->testmode == 'yes') ? true : false;
            
            $sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
			
			$text = '<script>( function($) {
        $(document).ready(function() {
	$(\'#giropay_widget\').giropay_widget({\'return\': \'bic\',\'kind\': 1});
});
    } ) ( jQuery );</script>';
	
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b></br>';
			
			if($this->testmode == 'yes')
				$text .= '<b>Let op Testmodus ingeschakeld!</b></br>';
            
            $text .= '<p><img src="https://www.girosolution.de/fileadmin/Downloads/Logos/giropay_200px_color_rgb.png" width="60px" style="float:left" /><br/>';
			
			if ($paymentfee_total > 0) {
                $text .= '<b>' . $this->paymentfeelabel. ': ' . woocommerce_price($paymentfee_total) . '</b></br></br>';
            }
			
			$text .= 'Bankleitzahl<br/>';
			$text .= '<input id="giropay_widget" autocomplete="off" name="sisow_giropay_bic" class="input-text required-entry" />';
			$text .= '</p>';

            echo $text;//wpautop(wptexturize($text));
        }

        public function validate_fields() {
            global $woocommerce;

            $this->bic = $_POST["sisow_giropay_bic"];

            if (!$this->bic) {
				wc_add_notice( __( 'Please enter your bankleitzahl.', 'woocommerce' ), 'error' );
                return false;
            } else {
                return true;
            }
        }

    }

    add_filter('woocommerce_payment_gateways', 'add_sisow_giropay_gateway');

    function add_sisow_giropay_gateway($methods) {
        $temp = 'WC_Sisow_giropay';
        $methods[] = $temp;
        return $methods;
    }

}