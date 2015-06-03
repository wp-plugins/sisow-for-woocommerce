<?php
/*
Plugin Name: WooCommerce Sisow Focum AchterafBetalen
Plugin URI: http://www.sisow.nl
Description: The Sisow Focum AchterafBetalen Plugin for WooCommerce
Version: 4.3.7
Author: Sisow
Author URI: http://www.sisow.nl
*/

add_action('plugins_loaded', 'woocommerce_sisowfocum_init', 0);

function woocommerce_sisowfocum_init() 
{			
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/sisow.cls5.php');
	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/base.php');
	
	class WC_Sisow_focum extends SisowBase
	{
		function __construct() 
		{ 
			$this->paymentcode 	= 'focum';
			$this->paymentname 	= 'Sisow Focum AchterafBetalen';
			$this->redirect 	= false;
			
			parent::__construct();
		}
		
		public function get_icon(){
			if($this->displaylogo == 'yes')
				return '<img alt="'.$this->paymentname.'" title="" src="'.plugins_url() . "/" . plugin_basename( dirname(__FILE__)) . '/sisow/logo/'.$this->paymentcode.'.png'.'"';
		}
		
		public function payment_fields() {
			global $woocommerce;
					
			$paymentfee_total = $this->getFee();
			
			$text = '<b>'.__('Betalen met') . ' ' . $this->title . '</b>';
			if($this->merchantId == '' || $this->merchantKey == '')
				$text .= '</br><b>Let op MerchantID/MerchantKey niet ingevuld, controleer de instellingen!</b>';
			
			if($this->testmode == 'yes')
				$text .= '</br><b>Let op Testmodus ingeschakeld!</b>';
            			
			$text .= '</br><label for="'.$this->paymentcode.'_gender">Geslacht:&nbsp;</label>';
			$text .= '</br><select name="'.$this->paymentcode.'_gender" id="'.$this->paymentcode.'_gender">';
			$text .= '<option value="">Maak uw keuze..</option>';
			$text .= '<option value="M">Man</option>';
			$text .= '<option value="F">Vrouw</option>';
			$text .= '</select></br></br>';
			
			$text .= '<label for="'.$this->paymentcode.'_iban">IBAN:&nbsp;</label>';
			$text .= '</br><input class="input-text" type="text" name="'.$this->paymentcode.'_iban" id="'.$this->paymentcode.'_iban"/></br></br>';
			
			$text .= '<label for="'.$this->paymentcode.'_phone">Telefoon:&nbsp;</label>';
			$text .= '</br><input class="input-text" type="text" name="'.$this->paymentcode.'_phone" id="'.$this->paymentcode.'_phone"/></br></br>';
			
			$text .= "<b>&nbsp;&nbsp;Geboortedatum:</b>";
			$text .= '</br><select name="'.$this->paymentcode.'_day" id="'.$this->paymentcode.'_day">';
			$text .= '<option value="">Dag</option>';
			for($i=1;$i<32;$i++)
				$text .= '<option value="'.sprintf('%02d', $i).'">'.sprintf('%02d', $i).'</option>';
			$text .= '</select>';
			
			$text .= '<select name="'.$this->paymentcode.'_month" id="'.$this->paymentcode.'_month">';
			$text .= '<option value="">Maand</option>';
			$text .= '<option value="01">Januari</option>';
			$text .= '<option value="02">Februari</option>';
			$text .= '<option value="03">Maart</option>';
			$text .= '<option value="04">April</option>';
			$text .= '<option value="05">Mei</option>';
			$text .= '<option value="06">Juni</option>';
			$text .= '<option value="07">Juli</option>';
			$text .= '<option value="08">Augustus</option>';
			$text .= '<option value="09">September</option>';
			$text .= '<option value="10">Oktober</option>';
			$text .= '<option value="11">November</option>';
			$text .= '<option value="12">December</option>';
			$text .= '</select>';
						
			$text .= '<select name="'.$this->paymentcode.'_year" id="'.$this->paymentcode.'_year">';
			$text .= '<option value="">Jaar</option>';
			for($i=date("Y")-17;$i>date("Y")-117;$i--)
				$text .= '<option value="'.$i.'">'.$i.'</option>';
			$text .= '</select></br>';
									
			if ($paymentfee_total > 0) {
				$text .= '&nbsp;&nbsp;<b>' . $this->paymentfeelabel . woocommerce_price($paymentfee_total) . '</b></br>';
			}
			
			echo wpautop(wptexturize($text));
		}
		
		public function validate_fields() {
            global $woocommerce;
			
			if(empty($_POST["focum_gender"]))
			{
				wc_add_notice( __( 'Selecteer uw geslacht.', 'woocommerce' ), 'error' );
				return false;
			}
			else
				$this->gender = $_POST["focum_gender"];
			
			if(empty($_POST["focum_iban"]))
			{
				wc_add_notice( __( 'Voer uw IBAN in.', 'woocommerce' ), 'error' );
				return false;
			}
			else
				$this->iban = $_POST["focum_iban"];
				
			if(empty($_POST["focum_phone"]))
			{
				wc_add_notice( __( 'Voer uw telefoonnummer in', 'woocommerce' ), 'error' );
				return false;
			}
			else
				$this->phone = $_POST["focum_phone"];

			if(empty($_POST["focum_day"]))
			{
				wc_add_notice( __( 'Selecteer "dag" bij geboortedatum.', 'woocommerce' ), 'error' );
				return false;
			}
			else
				$day = $_POST["focum_day"];
				
			if(empty($_POST["focum_month"]))
			{
				wc_add_notice( __( 'Selecteer "maand" bij geboortedatum.', 'woocommerce' ), 'error' );
				return false;
			}
			else
				$month = $_POST["focum_month"];
				
			if(empty($_POST["focum_year"]))
			{
				wc_add_notice( __( 'Selecteer "jaar" bij geboortedatum.', 'woocommerce' ), 'error' );
				return false;
			}
			else
				$year = $_POST["focum_year"];
			
			$this->dob = $day . $month . $year;
        }
	}
	
	add_filter('woocommerce_payment_gateways', 'add_sisow_focum_gateway' );

	function add_sisow_focum_gateway($methods)
	{
		$temp = 'WC_Sisow_focum';
		$methods[] = $temp;
		return $methods;
	}
}