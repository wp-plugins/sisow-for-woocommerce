<?php
class SisowBase extends WC_Payment_Gateway
{
	function _start($code, $name)
	{
		global $woocommerce;
		
		$this->paymentcode = $code;
		$this->paymentname = $name;
				
		$this->id				= 'sisow' . $this->paymentcode;
		$this->method_title 	= __($this->paymentname, 'woothemes');
		$this->has_fields 		= true;
		
		// Load the form fields
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Get setting values
		$this->title 			= $this->settings['title'];
		$this->merchantId		= $this->settings['merchantid'];
		$this->merchantKey		= $this->settings['merchantkey'];
		$this->omschrijving		= $this->settings['omschrijving'];
		$this->testmode			= $this->settings['testmode'];
		$this->paymentfeelabel	= $this->settings['paymentfeelabel'];
		$this->paymentfee		= $this->settings['paymentfee'];
		$this->paymentfeetax	= $this->settings['paymentfeetax'];
		$this->notify_url   	= add_query_arg( 'wc-api', 'WC_sisow_' . $this->paymentcode, home_url( '/' ) );
		
		if($this->paymentcode == 'ecare')
		{
			$this->mailinvoice		= $this->settings['mailinvoice'];
			$this->directinvoice	= $this->settings['directinvoice'];
		}
		elseif($this->paymentcode == 'overboeking' || $this->paymentcode == 'ebill')
		{
			$this->includelink		= $this->settings['includelink'];
			$this->days				= $this->settings['days'];
		}
		elseif($this->paymentcode == 'klarna' || $this->paymentcode == 'klarnaacc')
		{
			$this->klarnaid			= $this->settings['klarnaid'];
		}
		
		add_action( 'woocommerce_api_wc_sisow_' . $this->paymentcode, array( $this, 'check_notify' ) );
		
		if(!isset($woocommerce->version) || $woocommerce->version < 2)
		{
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		}
		else
		{
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		
		//payment fee actions        
		add_action( 'wp_enqueue_scripts', array(&$this, 'add_script') );
	}
	
	public function add_script() {
		wp_enqueue_script( 'sisow', plugins_url('checkout.js', __FILE__), array('jquery') );
    }
	
	function init_form_fields()
	{
		$velden = array();
		
		$velden['enabled'] = array(
									'title' => __( 'Enable/Disable', 'woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable '.$this->paymentname, 'woocommerce' ), 
									'default' => 'yes'
								);
						
		$velden['title'] = array(
									'title' => __( 'Title', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ), 
									'default' => __( $this->paymentname, 'woocommerce' )
								);
		$velden['merchantid'] = array(
									'title' => __( 'Sisow Merchant ID', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'This is your Merchant ID which you can find in your Sisow profile on <a href="http://www.sisow.nl/">www.sisow.nl</a>.', 'woocommerce' ), 
									'default' => __("", 'woocommerce')
								);
		$velden['merchantkey'] = array(
									'title' => __( 'Sisow Merchant Key', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'This is your Merchant Key which you can find in your Sisow profile on <a href="http://www.sisow.nl/">www.sisow.nl</a>.', 'woocommerce' ), 
									'default' => __("", 'woocommerce')
								);
		
		if($this->paymentcode == 'klarna' || $this->paymentcode == 'klarnaacc')
		{
			$velden['klarnaid'] = array(
									'title' => __( 'Klarna ID', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'This is your Klarna ID which you can find in your Sisow profile on <a href="http://www.sisow.nl/">www.sisow.nl</a>.', 'woocommerce' ), 
									'default' => __("", 'woocommerce')
								);
		}
		
		$velden['omschrijving'] = array(
									'title' => __( 'Description', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( "This is the description your customer will see on his bank statement ", 'woocommerce' ), 
									'default' => __("", 'woocommerce')
								);
		$velden['testmode'] = array(
									'title' => __( 'Testmode', 'woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable the testmode to test your connection', 'woocommerce' ), 
									'description' => __( 'Test the connection between Sisow and your Webshop.', 'woocommerce' ),
									'default' => 'yes'
								);
	
		if($this->paymentcode == 'ebill' || $this->paymentcode == 'overboeking')
		{
			$velden['days'] = array(
								'title' => __( 'Days', 'woocommerce' ), 
								'type' => 'text', 
								'description' => __( "Number of days payment is valid.", 'woocommerce' ), 
								'default' => __("", 'woocommerce')
							);
			if($this->paymentcode == 'ebill')
			{
				$velden['includelink'] = array(
									'title' => __( 'Include bank info', 'woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Add Sisow bank account information, the customer can also pay through a bank transfer.', 'woocommerce' ), 
									'default' => 'yes'
								);
			}
			else
			{
				$velden['includelink'] = array(
									'title' => __( 'Include paymentlink', 'woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Add an iDEAL link in the mail, the customer can also pay with iDEAL.', 'woocommerce' ), 
									'default' => 'yes'
								);
			}
		}
		
		$velden['paymentfeelabel'] = array(
									'title' => __( 'Payment fee label:', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'Set the order total text for the payment fee', 'woocommerce' ), 
									'default' => __("", 'woocommerce')
								);
		
		$velden['paymentfee'] = array(
									'title' => __( 'Payment fee:', 'woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'Set the payment fee amount (negative amount is %)', 'woocommerce' ), 
									'default' => __("", 'woocommerce')
								);
		
		$classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
		$classes_options = array();
		$classes_options[''] = __('Standard', 'woocommerce');
		if ($classes) foreach ($classes as $class) :
			$classes_options[sanitize_title($class)] = $class;
		endforeach;
		
		$velden['paymentfeetax'] = array(
										'title' => __( 'Payment fee:', 'woocommerce' ), 
										'type' => 'select', 
										'options' => $classes_options,
										'description' => __( 'Tax class for the payment fee.', 'woocommerce' ), 
										'default' => __("", 'woocommerce')
									);
                                
  
		
		$this->form_fields = $velden;
    } // End init_form_fields()
	
	public function payment_fields() 
	{
		global $woocommerce;
		
		$paymentfee_subtotal = $this->calculate_fee_for($this->settings, $woocommerce->cart->subtotal);
		$paymentfee_total = $paymentfee_subtotal + $this->calculate_tax($this->settings, $paymentfee_subtotal);
		$this->paymentfeelabel = ($this->paymentfeelabel != '') ? $this->paymentfeelabel : 'Payment Fee' ;		
		
		if($this->paymentcode == 'ideal')
		{
			$text = '<img src="https://www.sisow.nl/Sisow/images/ideal/idealklein.gif" height="24" alt="Sisow iDEAL" />';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'. $this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
			$text .= '&nbsp;&nbsp;Kies uw bank&nbsp;&nbsp;<select name="issuerid">';
			$text .= '<option value="">Kies uw bank...</option>';
			if($this->settings['testmode'] == 'yes')
			{
				$text .= '<option value="99">Sisow Bank (test)</option>';
			}
			else
			{
				$sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
				$options = '';
				$sisow->DirectoryRequest($options, false, false);
				foreach($options as $value => $bank)
				{
					$text .= '<option value="'.$value.'">'.$bank.'</option>';
				}
			}
			
			$text .= '</select>';
		}
		elseif($this->paymentcode == 'klarna')
		{
			$text = '<img src="https://cdn.klarna.com/public/images/NL/badges/v1/invoice/NL_invoice_badge_std_blue.png?height=50&eid='.$this->klarnaid.'" alt="Klarna" /><br/>';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
				$text .= 'Klarna Factuur - Betaal binnen 14 dagen.<br/>
				<p>Aanhef: <br/>
				<select name="sisow_gender_'.$this->paymentcode.'" id="sisow_gender_'.$this->paymentcode.'">
					<option value=""></option>
					<option value="m">De heer</option>
					<option value="f">Mevrouw</option>
				</select>

				<p>Telefoonnummer: <br/>
				<input type="text" name="sisow_phone_'.$this->paymentcode.'" id="sisow_phone_'.$this->paymentcode.'" size="13" maxlength="13" value="" />

				<p>Geboortedatum (DD MM JJJJ): <br/>
				<input type="text" name="sisow_dag_'.$this->paymentcode.'" id="sisow_dag_'.$this->paymentcode.'" size="2" maxlength="2" value=""/>
				&nbsp&nbsp <input type="text" name="sisow_maand_'.$this->paymentcode.'" id="sisow_maand_'.$this->paymentcode.'" size="2" maxlength="2" value=""/>
				&nbsp&nbsp <input type="text" name="sisow_jaar_'.$this->paymentcode.'" id="sisow_jaar_'.$this->paymentcode.'" size="4" maxlength="4" value=""/>
				</p>
				
				<a href="https://online.klarna.com/villkor_nl.yaws?eid='.$this->klarnaid.'&charge=0" target="_blank">Klarna factuurvoorwaarden</a>
			';
		}
		elseif($this->paymentcode == 'klarnaacc')
		{			
			$sisow = new Sisow($this->merchantId, $this->merchantKey);
			$sisow->FetchMonthlyRequest($woocommerce->cart->total);

			$monthly = 'Klarna Account - vanaf <b>' . woocommerce_price($sisow->monthly / 100.0) . '</b> per maand. </br>
						<input type="hidden" name="payment_info[sisow_pclass]" id="sisow_pclass" value="'.$sisow->pclass.'" />';;
		
			$text =
			'
				<img src="https://cdn.klarna.com/public/images/NL/badges/v1/account/NL_account_badge_std_blue.png?height=50&eid='.$this->klarnaid.'" alt="Klarna" /><br/><br/>
	
				'.$monthly.'
				
				<br/>
				<img src="https://www.sisow.nl/images/betaallogos/lenenkostgeld.jpg" alt="geld lenen kost geld"/>
				<br/>
				<br/>
				<p>Aanhef: <br/>
				<select name="sisow_gender">
					<option value=""></option>
					<option value="m">De heer</option>
					<option value="f">Mevrouw</option>
				</select>

				<p>Telefoonnummer: <br/>
				<input type="text" name="sisow_phone" id="sisow_phone" size="13" maxlength="13" value="" />

				<p>Geboortedatum (DD MM JJJJ): <br/>
				<input type="text" name="sisow_dag_'.$this->paymentcode.'" id="sisow_dag_'.$this->paymentcode.'" size="2" maxlength="2" value=""/>
				&nbsp&nbsp <input type="text" name="sisow_maand_'.$this->paymentcode.'" id="sisow_maand_'.$this->paymentcode.'" size="2" maxlength="2" value=""/>
				&nbsp&nbsp <input type="text" name="sisow_jaar_'.$this->paymentcode.'" id="sisow_jaar_'.$this->paymentcode.'" size="4" maxlength="4" value=""/>
				</p>
				
				<a href="https://online.klarna.com/account_nl.yaws?eid='.$this->klarnaid.'" target="_blank">Lees meer!</a>
			';
		}
		elseif($this->paymentcode == 'sofort')
		{
			$text = '<img src="https://www.sisow.nl/Sisow/images/ideal/payment_small.png" alt="Sisow SofortBanking" />';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
		}
		elseif($this->paymentcode == 'fijncadeau')
		{
			$text = '<img src="https://www.sisow.nl/Sisow/images/ideal/fijncadeau.gif" alt="Sisow Fijncadeau" />';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
		}
		elseif($this->paymentcode == 'mistercash')
		{
			$text = '<img height="30" src="https://www.sisow.nl/Sisow/images/ideal/mister.gif" alt="Sisow MisterCash" />';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
		}
		elseif($this->paymentcode == 'overboeking')
		{
			$text = '<a href="http://www.sisow.nl" target="_blank"><img src="https://www.sisow.nl/Sisow/images/mail/sisowklein.jpg" alt="Sisow OverBoeking" /></a><br/>';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
			
			$text .= 'U heeft ervoor gekozen om uw bestelling per bank/giro over te maken.<br/>
				De verwerking hiervan is uitbesteed aan Sisow B.V.<br/>
				<br/>
				U ontvangt een e-mail met daarin informatie hoe u uw betaling kunt voltooien.';
		}
		elseif($this->paymentcode == 'paypalec')
		{
			$text = '<img src="https://www.sisow.nl/Sisow/images/ideal/paypal.gif" alt="Sisow PayPal" />';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
		}
		elseif($this->paymentcode == 'giftcard')
		{
			$text = '<img src="https://www.sisow.nl/Sisow/images/ideal/logowsgc.gif" alt="Sisow Webshop Giftcard" />';
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
		}
		else
		{
			$text = '&nbsp;&nbsp;' . $this->paymentname;
			if($paymentfee_total > 0)
				$text .= '&nbsp;&nbsp;<b>'.$this->paymentfeelabel . ': '.woocommerce_price($paymentfee_total).'</b></br>';
		}
		echo $text;
	}
	
	public function process_payment($order_id)
	{
		global $woocommerce;
			
		$fout = false;
		if($this->paymentcode == 'ecare')
		{
			if($_POST['sisow_initials_'.$this->paymentcode] == '')
			{
				$woocommerce->add_error(__('Vul uw inita(a)l(en) in.', 'woothemes'));
				$fout = true;
			}
		}

		if($this->paymentcode == 'klarna' || $this->paymentcode == 'klarnaacc')
		{
			if($_POST['sisow_phone_'.$this->paymentcode] == '')
			{
				$woocommerce->add_error(__('Geen telefoonnummer ingegeven.', 'woothemes'));
				$fout = true;
			}
		}
		
		if($this->paymentcode == 'ecare' || $this->paymentcode == 'klarna' || $this->paymentcode == 'klarnaacc')
		{
			if($_POST['sisow_gender_'.$this->paymentcode] == '')
			{
				$woocommerce->add_error(__('Geen aanhef gekozen.', 'woothemes'));
				$fout = true;
			}

			if($_POST['sisow_dag_'.$this->paymentcode] < 1 || $_POST['sisow_dag_'.$this->paymentcode] > 31 || $_POST['sisow_maand_'.$this->paymentcode] < 1 || $_POST['sisow_maand_'.$this->paymentcode] > 12 || !isset($_POST['sisow_jaar_'.$this->paymentcode]))
			{
				$woocommerce->add_error(__('Geboortedatum niet (juist) ingevuld.', 'woothemes'));
				$fout = true;
			}
		}
		
		if($this->paymentcode == 'ideal' && (!isset($_POST['issuerid']) || $_POST['issuerid'] == ""))
		{
			$fout = true;
			$woocommerce->add_error(__('Kies uw bank.', 'woothemes'));
		}
		
		if($fout == false)
		{	
			$order = new WC_Order( $order_id );
		
			$order_number = ltrim( $order->get_order_number(), '#' );

			$sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
			$sisow->purchaseId = 	$order_number;
			$sisow->description = 	$this->settings['omschrijving'] . ' ' . $order_number;
			$sisow->amount = 		$order->order_total;
			$sisow->payment = 		$this->paymentcode;
			$sisow->entranceCode = 	$order_id;
			$sisow->returnUrl = 	$this->get_return_url($order);
			$sisow->cancelUrl = 	$order->get_cancel_order_url();
			$sisow->notifyUrl = 	$this->notify_url;
			
			if($this->settings['testmode'] == 'yes')
			{
				$sisow->issuerId = '99';
			}
			else
			{
				$sisow->issuerId = $_POST['issuerid'];
			}
		
			if(($ex = $sisow->TransactionRequest($this->prep($order_id))) < 0)
			{
				if($this->paymentcode == 'ecare')
				{
					$order->add_order_note( __('Payment with ' . $this->paymentname . ' had failed.', 'woothemes') );
				}
				
				if($this->paymentcode == 'klarna' || $this->paymentcode == 'klarnaacc')
				{
					$woocommerce->add_error($sisow->errorMessage);
				}
				else
				{
					$woocommerce->add_error(__('Betalen met ' . $this->paymentname . ' is nu niet mogelijk ('.$ex.';'.$sisow->errorCode.'). Kies een andere betaalmethode.', 'woothemes'));
				}
			}
			else
			{
				if($this->paymentcode == 'ecare' || $this->paymentcode == 'overboeking' || $this->paymentcode == 'ebill' || $this->paymentcode == 'klarna' || $this->paymentcode == 'klarnaacc')
				{
					if($sisow->pendingKlarna)
					{
						$order->update_status('on-hold', __($this->paymentname . ' waiting for Klarna', 'woocommerce') );
						$woocommerce->add_error(__('Voor uw betaling met Klarna is een extra controle nodig. U ontvangt binnen 24 uur bericht.', 'woothemes'));
					}
					else
					{
						$this->orderid = $order_id;
						$this->trxid = $sisow->trxId;
						$this->sisow = $sisow;
						$this->notify();
					}
				}
				else
				{
					return array(
						'result' => 'success',
						'redirect' => $sisow->issuerUrl);
				}
			}
		}
	}
	
	private function prep($order_id)
	{
		$order = new WC_Order( $order_id );

		$arg = array();
		$arg['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		$arg['shipping_firstname'] = $order->shipping_first_name;
		$arg['shipping_lastname'] = $order->shipping_last_name;
		//$arg['shipping_mail'] = $order->;
		$arg['shipping_company'] = $order->shipping_company;
		$arg['shipping_address1'] = $order->shipping_address_1;
		$arg['shipping_address2'] = $order->shipping_address_2;
		$arg['shipping_zip'] = $order->shipping_postcode;
		$arg['shipping_city'] = $order->shipping_city;
		//$arg['shipping_country'] = $order->;
		$arg['shipping_countrycode'] = $order->shipping_country;
		$arg['shipping_phone'] = $order->billing_phone;
		$arg['billing_firstname'] = $order->billing_first_name;
		$arg['billing_lastname'] = $order->billing_last_name;
		$arg['billing_mail'] = $order->billing_email;
		$arg['billing_company'] = $order->billing_company;
		$arg['billing_address1'] = $order->billing_address_1;
		$arg['billing_address2'] = $order->billing_address_2;
		$arg['billing_zip'] = $order->billing_postcode;
		$arg['billing_city'] = $order->billing_city;
		//$arg['billing_country'] = $order->billing_country;
		$arg['billing_countrycode'] = $order->billing_country;
		if(isset($_POST['sisow_phone_'.$this->paymentcode]))
		{
			$arg['billing_phone'] = $_POST['sisow_phone_'.$this->paymentcode];
		}
		else
		{
			$arg['billing_phone'] = $order->billing_phone;
		}
		if(isset($_POST['sisow_dag_'.$this->paymentcode]) & isset($_POST['sisow_maand_'.$this->paymentcode]) && isset($_POST['sisow_jaar_'.$this->paymentcode]))
		{
			$jaar = ($_POST['sisow_jaar_'.$this->paymentcode] < 100) ? $_POST['sisow_jaar_'.$this->paymentcode] + 1900 : $_POST['sisow_jaar_'.$this->paymentcode];
						
			$arg['birthdate'] = sprintf('%02d%02d%04d', $_POST['sisow_dag_'.$this->paymentcode], $_POST['sisow_maand_'.$this->paymentcode], $jaar);
		}
		if(isset($_POST['sisow_initials']))
			$arg['initials'] = $_POST['sisow_initials'];
		if(isset($_POST['sisow_gender_'.$this->paymentcode]))
			$arg['gender'] = $_POST['sisow_gender'];
			
		//print_r($this->settings); exit;
		if(isset($this->settings['directinvoice']))
			$arg['makeinvoice'] = $this->settings['directinvoice'];
		if(isset($this->settings['mailinvoice']))
			$arg['mailinvoice'] = $this->settings['mailinvoice'];
		$arg['amount'] = round($order->order_total * 100.0, 0);
		$arg['tax'] = round(($order->order_tax + $order->order_shipping_tax) * 100.0, 0);
		$arg['currency'] = get_woocommerce_currency();
		
		//producten
		$item_loop = 0;
		if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
			if ($item['qty']) :
				
				$item_loop++;
				
				$product = $order->get_product_from_item($item);
					
				$_tax = new WC_Tax();
				
				foreach($_tax->get_shop_base_rate($product->tax_class) as $line_tax)
				{
					$tax = $line_tax['rate'];
				}
		
				$arg['product_id_'.$item_loop] = $item['product_id'];
				$arg['product_description_'.$item_loop] = $item['name'];
				$arg['product_quantity_'.$item_loop] = $item['qty'];
				$arg['product_netprice_'.$item_loop] = round($product->get_price_excluding_tax(), 2) * 100;
				$arg['product_total_'.$item_loop] = round($item['line_total'] + $item['line_tax'], 2) * 100;
				$arg['product_nettotal_'.$item_loop] = round($item['line_total'], 2) * 100;
				$arg['product_tax_'.$item_loop] = round($item['line_tax'], 2) * 100;
				$arg['product_taxrate_'.$item_loop] = (!isset($tax)) ? 0 : round($tax, 2) * 100;
				$arg['product_weight_'.$item_loop] = round($product->weight, 2) * 100;
			endif;
		endforeach; endif;
		
		if(isset($order->order_shipping))
		{
			if($order->order_shipping > 0)
			{
				$item_loop++;
				$arg['product_id_'.$item_loop] = 'Shipping';
				$arg['product_description_'.$item_loop] = 'Verzendkosten';
				$arg['product_quantity_'.$item_loop] = '1';
				$arg['product_netprice_'.$item_loop] = round($order->order_shipping, 2) * 100;
				$arg['product_total_'.$item_loop] = round($order->order_shipping + $order->order_shipping_tax, 2) * 100;
				$arg['product_nettotal_'.$item_loop] = round($order->order_shipping, 2) * 100;
				$arg['product_tax_'.$item_loop] = round($order->order_shipping_tax, 2) * 100;
				$arg['product_taxrate_'.$item_loop] = round($tax, 2) * 100;
			}
		}
		
		if($this->settings['testmode'] == 'yes')
		{
			$arg['testmode'] = 'true';
		}
		else
		{
			$arg['testmode'] = 'false';
		}
		
		if(isset($this->includelink) && $this->includelink == 'yes')
			$arg['including'] = 'true';
		if(isset($this->days) && $this->days > 0)
			$arg['days'] = $this->days;
		
		if(isset($this->mailinvoice) && $this->mailinvoice == 'yes')
			$arg['mailinvoice'] = 'true';
		if(isset($this->directinvoice) && $this->directinvoice > 0)
			$arg['directinvoice'] = 'true';
		
		return $arg;
	}
	
	public function check_notify()
	{
		if(!(isset($_GET['notify']) || isset($_GET['callback'])))
			return;
		$this->orderid = $_GET['ec'];
		$this->trxid = $_GET['trxid'];	
		$this->notify();
	}
	
	public function notify()
	{
		$order = new WC_Order( $this->orderid );
		$sisow = new Sisow($this->settings['merchantid'], $this->settings['merchantkey']);
							
		if(($ex = $sisow->StatusRequest($this->trxid)) < 0)
		{
			echo $ex;
			exit;
		}
		else
		{
			if($order->status != 'processing' && $order->status != 'completed')
			{
				//else
				{
					switch($sisow->status)
					{
						case 'Success':
							$order->add_order_note( __($this->paymentname . ' transaction Success', 'woocommerce') );
							$order->payment_complete();
							break;
						case 'Reservation':
							$order->add_order_note( __('Reservation made for ' . $this->paymentname, 'woocommerce') );
							$order->payment_complete();
							break;
						case 'Cancelled':
							$order->add_order_note( __($this->paymentname . ' transaction was cancelled.', 'woocommerce') );
							break;
						case 'Expired':
							$order->add_order_note( __($this->paymentname . ' transaction has expired.', 'woocommerce') );
							break;
						case 'Failure':
							$order->add_order_note( __($this->paymentname . ' transaction request failure.', 'woocommerce') );
							break;
						default:
							$order->update_status('on-hold', __($this->paymentname . ' transaction Pending', 'woocommerce') );
							break;
					}
				}
			}
			
			if($this->paymentcode == 'ecare' || $this->paymentcode == 'overboeking' || $this->paymentcode == 'ebill' || $this->paymentcode == 'klarna'|| $this->paymentcode == 'klarnaacc')
			{	
				add_post_meta($this->orderid, 'status', $sisow->status);
				add_post_meta($this->orderid, 'trxid', $sisow->trxId);
				if($this->settings['directinvoice'] == 'true')
					add_post_meta($this->orderid, 'factuur', $sisow->documentId);
					
				wp_redirect($this->get_return_url($order) ); exit;
			}
			exit;
		}
	}
	
	private function calculate_fee_for($settings, $total) {
		global $woocommerce;
		$charge     = 0;
		
		if($settings['paymentfee'] > 0)
		{
			$charge = $settings['paymentfee'];
		}
		else
		{
			$charge = $total * (($settings['paymentfee'] * -1) / 100.0);
		}

		return $charge;
	}

	private function calculate_tax( $settings, $charge ) {
		$amount     = 0;

		$prices_include_tax = get_option( 'woocommerce_prices_include_tax' ) == 'yes' ? true : false;

		$_tax       = new WC_Tax();
		$tax_rates  = $_tax->get_shop_base_rate( $settings['paymentfeetax'] );
		$taxes      = $_tax->calc_tax( $charge, $tax_rates, $prices_include_tax );
		$amount     = $_tax->get_tax_total( $taxes );

		return $amount;
	}
	
}
//payment fee
//sisow
//added 06-08-2013
add_action( 'woocommerce_checkout_update_order_meta', 'process_checkout', 10, 2 );
add_action( 'woocommerce_calculate_totals','sisow_payment_fee' );
add_action( 'woocommerce_admin_order_totals_after_shipping', 'admin_order_totals' );
add_action( 'woocommerce_process_shop_order_meta', 'process_shop_order', 100, 2 );

function sisow_payment_fee($cart) {
	global $woocommerce;
	 
	if ( isset($_POST['payment_method']) ) {
		$gw = $_POST['payment_method'];
	} elseif ( isset($_POST['post_data']) ) {
		$parsed = array();
		parse_str($_POST['post_data'], $parsed);

		if ( isset($parsed['payment_method']) ) {
			$gw = $parsed['payment_method'];
		}

	} elseif ( isset($woocommerce->checkout->posted) && !empty($woocommerce->checkout->posted) ) {
		$gw = $woocommerce->checkout->posted['payment_method'];
	}

	if(strpos($gw, 'sisow') !== false)
	{	
		$class = 'WC_Sisow_'.str_replace('sisow', '', $gw);
		
		if(!class_exists($class))
			return;
			
		$payment = new $class;
		
		$settings		= $payment->settings;
		$paymentFee    	= 0;
		$cart_total 	= $cart->subtotal;
		$add_note   	= false;
		
		$paymentFee = calculate_fee_for($settings, $cart_total);
		
		if ( $paymentFee > 0 ) {
			$tax_amount = calculate_tax( $settings, $paymentFee );
			
			if ( get_option( 'woocommerce_tax_display_cart' ) == 'incl' ) {
				$paymentFee += $tax_amount;
			}
			
			if ( $tax_amount > 0 ) {
				
				if ( get_option('woocommerce_prices_include_tax') == 'yes' ) {
					$paymentFee -= $tax_amount;
					
					if ( get_option('woocommerce_tax_display_cart') == 'excl' ) {
						$add_note = true;
					}

				}

				if ( get_option('woocommerce_tax_display_cart') == 'excl' ) {
					$cart_taxes = $cart->taxes;
					$keys       = array_keys($cart_taxes);
					
					if ( isset($keys[0]) ) {
						$cart_taxes[$keys[0]] += $tax_amount;
					} else {
						$cart_taxes[0] = $tax_amount;
					}
					
					$cart->taxes       = $cart_taxes;
					$cart->tax_total  += $tax_amount;
				}
			}

			if ( function_exists('get_product') ) {
				$cart->fee_total = $cart->fee_total + $paymentFee;
			}

			if (! function_exists('get_product') ) {
				$cart->cart_contents_total = $cart->cart_contents_total + $paymentFee;
			}
			
			$paymentfeelabel = (isset($settings['paymentfeelabel']) && $settings['paymentfeelabel'] != '') ? $settings['paymentfeelabel'] : 'Payment Fee'; 

			if ( method_exists($woocommerce->cart, 'add_fee') ) {
				$cart->add_fee( $paymentfeelabel, $paymentFee, true, $settings['paymentfeetax'] );
			}
			
			if ( isset($woocommerce->checkout->posted) && !empty($woocommerce->checkout->posted) ) {
				$woocommerce->checkout->posted['processing_fee_label']  = $paymentfeelabel;
				$woocommerce->checkout->posted['processing_fee']        = $paymentFee;
				$woocommerce->checkout->posted['processing_fee_tax']    = $tax_amount;
			}
		}
	}
}

function process_checkout($order_id, $posted) {
	global $woocommerce;

	if ( isset($posted['processing_fee']) ) {
		update_post_meta( $order_id, '_processing_fee', woocommerce_format_total( $posted['processing_fee'] ) );
	}

	if ( isset($posted['processing_fee_label']) ) {
		update_post_meta( $order_id, '_processing_fee_label', $posted['processing_fee_label'] );
	}

	if ( isset($posted['processing_fee_tax']) ) {
		update_post_meta( $order_id, '_processing_fee_tax', woocommerce_format_total( $posted['processing_fee_tax'] ) );
	}

	$new_taxes      = array();
	$order_taxes    = get_post_meta( $order_id, '_order_taxes', true );

	if ( $order_taxes && is_array($order_taxes) ) {
		foreach ( $order_taxes as $tax ) {
			$label = $tax['label'];
			if ( isset($new_taxes[$label]) ) {
				$new_taxes[$label]['cart_tax'] += $tax['cart_tax'];
				$new_taxes[$label]['shipping_tax'] += $tax['shipping_tax'];
			} else {
				$new_taxes[$label] = $tax;
			}
		}
	}

	$i = 0;
	foreach ( $new_taxes as $key => $tax ) {
		$new_taxes[$i] = $tax;
		unset($new_taxes[$key]);
	}

	update_post_meta( $order_id, '_order_taxes', $new_taxes );
	update_post_meta( $order_id, '_old_order_taxes', $order_taxes );
}

function calculate_fee_for($settings, $total) {
	global $woocommerce;
	$charge     = 0;
	
	if($settings['paymentfee'] > 0)
	{
		$charge = $settings['paymentfee'];
	}
	else
	{
		$charge = $total * (($settings['paymentfee'] * -1) / 100.0);
	}

	return $charge;
}

function calculate_tax( $settings, $charge ) {
	$amount     = 0;

	$prices_include_tax = get_option( 'woocommerce_prices_include_tax' ) == 'yes' ? true : false;

	$_tax       = new WC_Tax();
	$tax_rates  = $_tax->get_shop_base_rate( $settings['paymentfeetax'] );
	$taxes      = $_tax->calc_tax( $charge, $tax_rates, $prices_include_tax );
	$amount     = $_tax->get_tax_total( $taxes );

	return $amount;
}

function process_shop_order( $post_id, $post ) {
	$old_fee        = get_post_meta( $post_id, '_processing_fee', true );
	$new_fee        = stripslashes( $_POST['_processing_fee'] );
	$order_total    = get_post_meta( $post_id, '_order_total', true );

	if ( $old_fee != '' ) {
		if ( $new_fee == 0 || $new_fee == '' ) {
			// if there is a processing fee which is now being removed
			$order_total -= $old_fee;
		} else {
			// adding or subtracting of fee
			if ( $old_fee > $new_fee ) {
				// fee lessened
				$diff = $old_fee - $new_fee;
				$order_total -= $diff;
			} elseif ($old_fee < $new_fee) {
				// fee added
				$diff = $new_fee - $old_fee;
				$order_total += $diff;
			}
		}

		update_post_meta( $post_id, '_order_total', $order_total );
		update_post_meta( $post_id, '_processing_fee', $new_fee );
	}
}

function admin_order_totals( $order_id ) {
	global $woocommerce;

	$label          = get_post_meta( $order_id, '_processing_fee_label', true );
	$processing_fee = get_post_meta( $order_id, '_processing_fee', true );

	if (! $processing_fee) return;

	?>
	<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php echo $label; ?></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e('Amount:', 'woocommerce'); ?></label>
				<input type="text" id="_processing_fee" name="_processing_fee" value="<?php echo $processing_fee; ?>" class="first" />
			</li>
		</ul>
	<?php
}


?>