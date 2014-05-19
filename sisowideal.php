<?php
/*
  Plugin Name: WooCommerce Sisow iDEAL
  Plugin URI: http://www.sisow.nl
  Description: The Sisow iDEAL Plugin for WooCommerce
  Version: 3.5.1
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

        public function payment_fields() {
            $paymentfee_total = $this->getFee();
            $testmode = ($this->testmode == 'yes') ? true : false;
            
            $sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
			
			$text = '';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '<b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b></br>';
			
			if($this->testmode == 'yes')
				$text .= '<b>Let op Testmodus ingeschakeld!</b></br>';
            
            $text .= '<img src="https://www.sisow.nl/Sisow/images/ideal/idealklein.gif" height="24" alt="Sisow iDEAL" />';

            $testmode = ($this->testmode == 'yes') ? true : false;
            
            $text .= '&nbsp;&nbsp;Kies uw bank&nbsp;&nbsp;<select name="sisow_bank">';
            $text .= '<option value="">Kies uw bank...</option>';
            
            $options = '';
            
			$sisow->DirectoryRequest($options, false, $testmode);

            foreach ($options as $value => $bank) {
                $text .= '<option value="' . $value . '">' . $bank . '</option>';
            }
            $text .= '</select>';
			
			 if ($paymentfee_total > 0) {
                $text .= '&nbsp;&nbsp;<b>' . $this->paymentfeelabel. ': ' . woocommerce_price($paymentfee_total) . '</b></br>';
            }
			
            echo wpautop(wptexturize($text));
        }

        public function validate_fields() {
            global $woocommerce;

            $this->issuerid = filter_input(INPUT_POST, 'sisow_bank');

            if (!$this->issuerid) {
                $woocommerce->add_error(__('Kies uw bank.', 'woothemes'));
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