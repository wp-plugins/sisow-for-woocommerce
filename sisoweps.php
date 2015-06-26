<?php
/*
  Plugin Name: WooCommerce Sisow EPS
  Plugin URI: http://www.sisow.nl
  Description: The Sisow EPS Plugin for WooCommerce
  Version: 4.3.8
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
	$(\'#eps_widget\').eps_widget({\'return\': \'bic\'});
});
    } ) ( jQuery );</script>';
			
			$text .= '<b>'.__('Betalen met') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '</br><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '</br><b>Let op Testmodus ingeschakeld!</b>';
            			
			if ($paymentfee_total > 0) {
                $text .= '</br><b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b>';
            }
			
			$text .= '<br/>Bankleitzahl<br/>';
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