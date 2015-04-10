<?php
/*
  Plugin Name: WooCommerce Sisow EPS
  Plugin URI: http://www.sisow.nl
  Description: The Sisow EPS Plugin for WooCommerce
  Version: 4.3.1
  Author: Sisow
  Author URI: http://www.sisow.nl
 */

add_action('plugins_loaded', 'woocommerce_eps_init', 0);

function woocommerce_eps_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/sisow/sisow.cls5.php');
    require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/sisow/base.php');

    class WC_Sisow_eps extends SisowBase {

        function __construct() {
            $this->paymentcode = 'eps';
            $this->paymentname = 'Sisow EPS';
            $this->redirect = true;
			
			wp_enqueue_script( "sisow_giropay_script", "https://www.sisow.nl/Sisow/scripts/giro-eps.js", array('jquery'));
			wp_enqueue_style( "sisow-eps-css", "https://bankauswahl.giropay.de/eps/widget/v1/style.css");
			
            parent::__construct();
        }

        public function payment_fields() {
            $paymentfee_total = $this->getFee();
            $testmode = ($this->testmode == 'yes') ? true : false;
            
            $sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
			
			$text = '<script>( function($) {
        $(document).ready(function() {
	$(\'#eps_widget\').eps_widget({\'return\': \'bic\'});
});
    } ) ( jQuery );</script>';
	
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b></br>';
			
			if($this->testmode == 'yes')
				$text .= '<b>Let op Testmodus ingeschakeld!</b></br>';
            
            $text .= '<p><img src="https://www.girosolution.de/fileadmin/Downloads/Logos/eps_logo.png" width="60px" style="float:left" /><br/>';
			
			if ($paymentfee_total > 0) {
                $text .= '<b>' . $this->paymentfeelabel. ': ' . woocommerce_price($paymentfee_total) . '</b></br></br>';
            }
			
			$text .= 'Bankleitzahl<br/>';
			$text .= '<input id="eps_widget" autocomplete="off" name="sisow_eps_bic" class="input-text required-entry" />';
			$text .= '</p>';

            echo $text;//wpautop(wptexturize($text));
        }

        public function validate_fields() {
            global $woocommerce;

            $this->bic = $_POST["sisow_eps_bic"];

            if (!$this->bic) {
				wc_add_notice( __( 'Please enter your bankleitzahl.', 'woocommerce' ), 'error' );
                return false;
            } else {
                return true;
            }
        }

    }

    add_filter('woocommerce_payment_gateways', 'add_sisow_eps_gateway');

    function add_sisow_eps_gateway($methods) {
        $temp = 'WC_Sisow_eps';
        $methods[] = $temp;
        return $methods;
    }

}