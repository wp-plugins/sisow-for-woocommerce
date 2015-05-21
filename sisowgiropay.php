<?php
/*
  Plugin Name: WooCommerce Sisow Giropay
  Plugin URI: http://www.sisow.nl
  Description: The Sisow Giropay Plugin for WooCommerce
  Version: 4.3.5
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
		
		public function get_icon()
		{
			if($this->displaylogo == 'yes')
				return '<img alt="'.$this->paymentname.'" title="" src="'.plugins_url() . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/logo/'.$this->paymentcode.'.png'.'"';
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
			
			$text .= '<b>'.__('Betalen met') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '</br><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '</br><b>Let op Testmodus ingeschakeld!</b>';
            
			$text .= '<br/>Bankleitzahl<br/>';
			$text .= '<input id="giropay_widget" autocomplete="off" name="sisow_giropay_bic" class="input-text required-entry" />';
			
			if ($paymentfee_total > 0) {
                $text .= '</br><b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b>';
            }

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