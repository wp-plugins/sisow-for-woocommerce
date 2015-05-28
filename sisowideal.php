<?php
/*
  Plugin Name: WooCommerce Sisow iDEAL
  Plugin URI: http://www.sisow.nl
  Description: The Sisow iDEAL Plugin for WooCommerce
  Version: 4.3.6
  Author: Sisow
  Author URI: http://www.sisow.nl
 */

add_action('plugins_loaded', 'woocommerce_ideal_init', 0);

function woocommerce_ideal_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/sisow/sisow.cls5.php');
    require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/sisow/base.php');

    class WC_Sisow_ideal extends SisowBase {

        function __construct() {
            $this->paymentcode = 'ideal';
            $this->paymentname = 'Sisow iDEAL';
            $this->redirect = true;
            parent::__construct();
        }
		
		public function get_icon(){
			if($this->displaylogo == 'yes')
				return '<img alt="'.$this->paymentname.'" title="" src="'.plugins_url() . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/logo/'.$this->paymentcode.'.png'.'"';
		}

        public function payment_fields() {
            $paymentfee_total = $this->getFee();
            $testmode = ($this->testmode == 'yes') ? true : false;
            
            $sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
			$options = '';
			$sisow->DirectoryRequest($options, false, $testmode);
			
			$text = '<b>'.__('Betalen met') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<br/><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '<br/><b>Let op Testmodus ingeschakeld!</b>';
            			
				
			$text .= '<p>Kies hieronder uw bank<br/>';
			$text .= '<select name="sisow_bank">';
			$text .= '<option value="">Kies uw bank...</option>';
			foreach ($options as $value => $bank) {
                $text .= '<option value="' . $value . '">' . $bank . '</option>';
            }
			$text .= '</select></p>';
			
			if ($paymentfee_total > 0) {
                $text .= '<br/><b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b>';
            }
			
            echo wpautop(wptexturize($text));
        }

        public function validate_fields() {
            global $woocommerce;

            $this->issuerid = filter_input(INPUT_POST, 'sisow_bank');

            if (!$this->issuerid) {
				wc_add_notice( __( 'Kies uw bank.', 'woocommerce' ), 'error' );
                return false;
            } else {
                return true;
            }
        }

    }

    add_filter('woocommerce_payment_gateways', 'add_sisow_ideal_gateway');

    function add_sisow_ideal_gateway($methods) {
        $temp = 'WC_Sisow_ideal';
        $methods[] = $temp;
        return $methods;
    }

}