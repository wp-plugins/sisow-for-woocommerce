<?php
class Sisow
{
	protected static $issuers;
	protected static $lastcheck;

	private $response;

	// Merchant data
	private $merchantId;
	private $merchantKey;
	private $shopId;

	// Transaction data
	public $payment;	// empty=iDEAL; sofort=DIRECTebanking; mistercash=MisterCash; ...
	public $issuerId;	// mandatory; sisow bank code
	public $purchaseId;	// mandatory; max 16 alphanumeric
	public $entranceCode;	// max 40 strict alphanumeric (letters and numbers only)
	public $description;	// mandatory; max 32 alphanumeric
	public $amount;		// mandatory; min 0.45
	public $notifyUrl;
	public $returnUrl;	// mandatory
	public $cancelUrl;
	public $callbackUrl;
	private $locale;

	// Status data
	public $status;
	public $timeStamp;
	public $consumerAccount;
	public $consumerIban;
	public $consumerBic;
	public $consumerName;
	public $consumerCity;
	
	// Invoice data
	public $invoiceNo;
	public $documentId;
	public $documentUrl;
	
	// Klarna Factuur/Account
	public $pendingKlarna;
	public $monthly;
	public $pclass;
	public $intrestRate;
	public $invoiceFee;
	public $months;
	public $startFee;

	// Result/check data
	public $trxId;
	public $issuerUrl;

	// Error data
	public $errorCode;
	public $errorMessage;

	// Status
	const statusSuccess = "Success";
	const statusCancelled = "Cancelled";
	const statusExpired = "Expired";
	const statusFailure = "Failure";
	const statusOpen = "Open";
	const statusReversed = "Reversed";
	const statusRefunded = "Refunded";

	public function __construct($merchantid, $merchantkey, $shopid = '') {
		$this->merchantId = $merchantid;
		$this->merchantKey = $merchantkey;
		$this->shopId = $shopid;
		
		$this->locale = '';
	}

	private function error() {
		$this->errorCode = $this->parse("errorcode");
		$this->errorMessage = urldecode($this->parse("errormessage"));
	}

	private function parse($search, $xml = false) {
		if ($xml === false) {
			$xml = $this->response;
		}
		if (($start = strpos($xml, "<" . $search . ">")) === false) {
			return false;
		}
		$start += strlen($search) + 2;
		if (($end = strpos($xml, "</" . $search . ">", $start)) === false) {
			return false;
		}
		return substr($xml, $start, $end - $start);
	}

	public function send($method, array $keyvalue = NULL, $return = 1) {
		$url = "https://www.sisow.nl/Sisow/iDeal/RestHandler.ashx/" . $method;
		$options = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => $return,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POSTFIELDS => $keyvalue == NULL ? "" : http_build_query($keyvalue, '', '&'));
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$this->response = curl_exec($ch);
		if (!$this->response) {
			$this->errorMessage = curl_error($ch);
		}
		curl_close($ch); 
		if (!$this->response) {
			return false;
		}
		return true;
	}

	private function getDirectory() {
		$diff = 24 * 60 *60;
		if (self::$lastcheck)
			$diff = time() - self::$lastcheck;
		if ($diff < 24 *60 *60)
			return 0;
		if (!$this->send("DirectoryRequest"))
			return -1;
		$search = $this->parse("directory");
		if (!$search) {
			$this->error();
			return -2;
		}
		self::$issuers = array();
		$iss = explode("<issuer>", str_replace("</issuer>", "", $search));
		foreach ($iss as $k => $v) {
			$issuerid = $this->parse("issuerid", $v);
			$issuername = $this->parse("issuername", $v);
			if ($issuerid && $issuername) {
				self::$issuers[$issuerid] = $issuername;
			}
		}
		self::$lastcheck = time();
		return 0;
	}

	// DirectoryRequest
	public function DirectoryRequest(&$output, $select = false, $test = false) {
		if ($test === true) {
			// kan ook via de gateway aangevraagd worden, maar is altijd hetzelfde
			if ($select === true) {
				$output = "<select id=\"sisowbank\" name=\"issuerid\">";
				$output .= "<option value=\"99\">Sisow Bank (test)</option>";
				$output .= "</select>";
			}
			else {
				$output = array("99" => "Sisow Bank (test)");
			}
			return 0;
		}
		$output = false;
		$ex = $this->getDirectory();
		if ($ex < 0) {
			return $ex;
		}
		if ($select === true) {
			$output = "<select id=\"sisowbank\" name=\"issuerid\">";
		}
		else {
			$output = array();
		}
		foreach (self::$issuers as $k => $v) {
			if ($select === true) {
				$output .= "<option value=\"" . $k . "\">" . $v . "</option>";
			}
			else {
				$output[$k] = $v;
			}
		}
		if ($select === true) {
			$output .= "</select>";
		}
		return 0;
	}

	// TransactionRequest
	public function TransactionRequest($keyvalue = NULL) {
		$this->trxId = $this->issuerUrl = "";
		if (!$this->merchantId) {
			$this->errorMessage = "No merchantid";
			return -1;
		}
		if (!$this->merchantKey) {
			$this->errorMessage = "No merchantkey";
			return -2;
		}
		if (!$this->purchaseId) {
			$this->errorMessage = "No purchaseid";
			return -3;
		}
		if ($this->amount < 0.45) {
			$this->errorMessage = "Amount < 0.45";
			return -4;
		}
		if (!$this->description) {
			$this->errorMessage = "No description";
			return -5;
		}
		if (!$this->returnUrl) {
			$this->errorMessage = "No returnurl";
			return -6;
		}
		if (!$this->issuerId && !$this->payment) {
			$this->errorMessage = "No issuer or payment";
			return -7;
		}
		if (!$this->entranceCode)
			$this->entranceCode = $this->purchaseId;
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["shopid"] = $this->shopId;
		$pars["payment"] = $this->payment;
		$pars["issuerid"] = $this->issuerId;
		$pars["purchaseid"] = $this->purchaseId; 
		$pars["amount"] = round($this->amount * 100);
		$pars["description"] = $this->description;
		$pars["entrancecode"] = $this->entranceCode;
		$pars["returnurl"] = $this->returnUrl;
		$pars["cancelurl"] = $this->cancelUrl;
		$pars["callbackurl"] = $this->callbackUrl;
		$pars["notifyurl"] = $this->notifyUrl;
		
		if($this->payment == "focum" || $this->payment == "klarna" || $this->payment == "klarnaacc")
		{
			if(!$this->validateFocum($keyvalue))
				return -10;
		}
		
		if(strlen($keyvalue['billing_countrycode']) == 2)
			$pars["locale"] = $this->setLocale($keyvalue['billing_countrycode']);
		else
			$pars["locale"] = $this->setLocale("");
		
		if($this->locale != '')
			$pars["locale"] = $this->locale;
			
		$pars["sha1"] = sha1($this->purchaseId . $this->entranceCode . round($this->amount * 100) . $this->shopId . $this->merchantId . $this->merchantKey);
		if ($keyvalue) {
			foreach ($keyvalue as $k => $v) {
				$pars[$k] = $v;
			}
		}
		if (!$this->send("TransactionRequest", $pars)) {
			if (!$this->errorMessage) {
				$this->errorMessage = "No transaction";
			}
			return -8;
		}
		$this->trxId = $this->parse("trxid");
		$url = $this->parse("issuerurl");
		$this->issuerUrl = urldecode($url);
		
		$this->documentId = $this->parse("documentid");
		/*
		if($this->payment == 'klarna' || $this->payment == 'klarnaacc')
			$sha = sha1($this->trxId . $this->merchantId . $this->merchantKey);
		else
			$sha = sha1($this->trxId . $url . $this->merchantId . $this->merchantKey);
		
		if($this->parse("sha1") != $sha)
		{
			$this->errorMessage = 'Invalid SHA returned';
			return -9;
		}
		*/
		$this->pendingKlarna = $this->parse("pendingklarna") == "true";
		if (!$this->issuerUrl) {
			$this->error();
			return -9;
		}
		return 0;
	}

	// StatusRequest
	public function StatusRequest($trxid = false) {
		if ($trxid === false)
			$trxid = $this->trxId;
		if (!$this->merchantId)
			return -1;
		if (!$this->merchantKey)
			return -2;
		if (!$trxid)
			return -3;
		$this->trxId = $trxid;
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["shopid"] = $this->shopId;
		$pars["trxid"] = $this->trxId;
		$pars["sha1"] = sha1($this->trxId . $this->shopId . $this->merchantId . $this->merchantKey);
		if (!$this->send("StatusRequest", $pars))
			return -4;
		$this->status = $this->parse("status");
		if (!$this->status) {
			$this->error();
			return -5;
		}
		$this->timeStamp = $this->parse("timestamp");
		$this->amount = $this->parse("amount") / 100.0;
		$this->consumerAccount = $this->parse("consumeraccount");
		$this->consumerIban = $this->parse("consumeriban");
		$this->consumerBic = $this->parse("consumerbic");
		$this->consumerName = $this->parse("consumername");
		$this->consumerCity = $this->parse("consumercity");
		$this->purchaseId = $this->parse("purchaseid");
		$this->description = $this->parse("description");
		$this->entranceCode = $this->parse("entrancecode");
		
		if( $this->parse("sha1") != sha1($this->trxId . $this->status . $this->amount * 100.0 . $this->purchaseId . $this->entranceCode . $this->consumerAccount . $this->merchantId . $this->merchantKey) )
		{
			$this->errorMessage = "Invalid SHA returned";
			return -6;
		}
		
		return 0;
	}

	// FetchMonthlyRequest
	public function FetchMonthlyRequest($amt = false) {
		if (!$amt) $amt = round($this->amount * 100);
		else $amt = round($amt * 100);
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["amount"] = $amt;
		$pars["sha1"] = sha1($amt . $this->merchantId . $this->merchantKey);
		if (!$this->send("FetchMonthlyRequest", $pars))
			return -1;
		$this->monthly = $this->parse("monthly");
		$this->pclass = $this->parse("pclass");
		$this->intrestRate = $this->parse("intrestRate");
		$this->invoiceFee = $this->parse("invoiceFee");
		$this->months = $this->parse("months");
		$this->startFee = $this->parse("startFee");
		return $this->monthly;
	}
	
	// RefundRequest
	public function RefundRequest($trxid) {
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["trxid"] = $trxid;
		$pars["sha1"] = sha1($trxid . $this->merchantId . $this->merchantKey);
		if (!$this->send("RefundRequest", $pars))
			return -1;
		$id = $this->parse("id");
		if (!$id) {
			$this->error();
			return -2;
		}
		if( $this->parse("sha1") != sha1($id . $this->merchantId . $this->merchantKey) )
		{
			$this->errorMessage = "Invalid SHA returned";
			return -6;
		}
		
		return $id;
	}

	// InvoiceRequest
	public function InvoiceRequest($trxid, $keyvalue = NULL) {
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["trxid"] = $trxid;
		$pars["sha1"] = sha1($trxid . $this->merchantId . $this->merchantKey);
		if ($keyvalue) {
			foreach ($keyvalue as $k => $v) {
				$pars[$k] = $v;
			}
		}
		if (!$this->send("InvoiceRequest", $pars))
			return -1;
		$this->invoiceNo = $this->parse("invoiceno");
		if (!$this->invoiceNo) {
			$this->error();
			return -2;
		}
		$this->documentId = $this->parse("documentid");
		$this->documentUrl = $this->parse("documenturl");
		
		if( $this->parse("sha1") != sha1($this->invoiceNo . $this->documentId . $this->merchantId . $this->merchantKey) )
		{
			$this->errorMessage = "Invalid SHA returned";
			return -6;
		}
		
		return 0;
	}

	// CreditInvoiceRequest
	public function CreditInvoiceRequest($trxid) {
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["trxid"] = $trxid;
		$pars["sha1"] = sha1($trxid . $this->merchantId . $this->merchantKey);
		if (!$this->send("CreditInvoiceRequest", $pars))
			return -1;
		$this->invoiceNo = $this->parse("invoiceno");
		if (!$this->invoiceNo) {
			$this->error();
			return -2;
		}
		$this->documentId = $this->parse("documentid");
		$this->documentUrl = $this->parse("documenturl");
		
		if( $this->parse("sha1") != sha1($this->invoiceNo . $this->documentId . $this->merchantId . $this->merchantKey) )
		{
			$this->errorMessage = "Invalid SHA returned";
			return -6;
		}
		
		return 0;
	}

	// CancelReservationRequest
	public function CancelReservationRequest($trxid) {
		$pars = array();
		$pars["merchantid"] = $this->merchantId;
		$pars["trxid"] = $trxid;
		$pars["sha1"] = sha1($trxid . $this->merchantId . $this->merchantKey);
		if (!$this->send("CancelReservationRequest", $pars))
			return -1;
		
		if( $this->parse("sha1") != sha1($trxid . $this->merchantId . $this->merchantKey) )
		{
			$this->errorMessage = "Invalid SHA returned";
			return -6;
		}
		return 0;
	}
	
	public function setLocale($countryIso)
	{
		$supported = array("US");
		
		switch($this->payment)
		{
			case "paypalec":
				$supported = array('AU','AT','BE','BR','CA','CH','CN','DE','ES','GB','FR','IT','NL','PL','PT','RU','US');
				break;
			case "mistercash":
				$supported = array('NL', 'BE', 'DE', 'IT', 'ES', 'PT', 'BR', 'SE', 'FR');
				break;
			case "creditcard":
				$supported = array('NL', 'BE', 'DE', 'IT', 'ES', 'PT', 'BR', 'SE', 'FR');
				break;
			case "maestro":
				$supported = array('NL', 'BE', 'DE', 'IT', 'ES', 'PT', 'BR', 'SE', 'FR');
				break;
			case "mastercard":
				$supported = array('NL', 'BE', 'DE', 'IT', 'ES', 'PT', 'BR', 'SE', 'FR');
				break;
			case "visa":
				$supported = array('NL', 'BE', 'DE', 'IT', 'ES', 'PT', 'BR', 'SE', 'FR');
				break;
			default:
				return "NL";
				break;
		}
		
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		$lang = strtoupper($lang);
		
		$lang = (!isset($lang) || $lang == "") ? $countryIso : $lang;
		
		if($lang == "")
			return "US";
		if(in_array($lang, $supported))
			return $lang;
		else
			return 'US';
	}	
	
	private function validateFocum($pars)
	{
		if(!is_array($pars))
		{
			$this->errorCode = "emptyarray";
			$this->errorMessage = "No values";
			return false;
		}
		else if(!array_key_exists('billing_firstname', $pars) || empty($pars['billing_firstname']))
		{
			$this->errorCode = "billing_firstname";
			$this->errorMessage = "billing_firstname empty";
			return false;
		}
		else if(!array_key_exists('billing_lastname', $pars) || empty($pars['billing_lastname']))
		{
			$this->errorCode = "billing_lastname";
			$this->errorMessage = "billing_lastname empty";
			return false;
		}
		else if(!array_key_exists('billing_mail', $pars) || empty($pars['billing_mail']))
		{
			$this->errorCode = "billing_mail";
			$this->errorMessage = "billing_mail empty";
			return false;
		}
		else if(!array_key_exists('billing_address1', $pars) || empty($pars['billing_address1']))
		{
			$this->errorCode = "billing_address1";
			$this->errorMessage = "billing_address1 empty";
			return false;
		}
		else if(!array_key_exists('billing_zip', $pars) || empty($pars['billing_zip']))
		{
			$this->errorCode = "billing_zip";
			$this->errorMessage = "billing_zip empty";
			return false;
		}
		else if(!array_key_exists('billing_city', $pars) || empty($pars['billing_city']))
		{
			$this->errorCode = "billing_city";
			$this->errorMessage = "billing_city empty";
			return false;
		}
		else if(!array_key_exists('billing_phone', $pars) || empty($pars['billing_phone']))
		{
			$this->errorCode = "billing_phone";
			$this->errorMessage = "billing_phone empty";
			return false;
		}
		else if(!array_key_exists('billing_countrycode', $pars) || empty($pars['billing_countrycode']))
		{
			$this->errorCode = "billing_countrycode";
			$this->errorMessage = "billing_countrycode empty";
			return false;
		}
		else if(!array_key_exists('shipping_firstname', $pars) || empty($pars['shipping_firstname']))
		{
			$this->errorCode = "shipping_firstname";
			$this->errorMessage = "shipping_firstname empty";
			return false;
		}
		else if(!array_key_exists('shipping_lastname', $pars) || empty($pars['shipping_lastname']))
		{
			$this->errorCode = "shipping_lastname";
			$this->errorMessage = "shipping_lastname empty";
			return false;
		}
		else if(!array_key_exists('shipping_address1', $pars) || empty($pars['shipping_address1']))
		{
			$this->errorCode = "shipping_address1";
			$this->errorMessage = "shipping_address1 empty";
			return false;
		}
		else if(!array_key_exists('shipping_zip', $pars) || empty($pars['shipping_zip']))
		{
			$this->errorCode = "shipping_zip";
			$this->errorMessage = "shipping_zip empty";
			return false;
		}
		else if(!array_key_exists('shipping_city', $pars) || empty($pars['shipping_city']))
		{
			$this->errorCode = "shipping_city";
			$this->errorMessage = "shipping_city empty";
			return false;
		}
		else if(!array_key_exists('shipping_phone', $pars) || empty($pars['shipping_phone']))
		{
			$this->errorCode = "shipping_phone";
			$this->errorMessage = "shipping_phone empty";
			return false;
		}
		else if(!array_key_exists('shipping_countrycode', $pars) || empty($pars['shipping_countrycode']))
		{
			$this->errorCode = "shipping_countrycode";
			$this->errorMessage = "shipping_countrycode empty";
			return false;
		}
		else if(!array_key_exists('birthdate', $pars) || empty($pars['birthdate']))
		{
			$this->errorCode = "birthdate";
			$this->errorMessage = "birthdate empty";
			return false;
		}
		else if(!array_key_exists('iban', $pars) || empty($pars['iban']))
		{
			$this->errorCode = "iban";
			$this->errorMessage = "iban empty";
			return false;
		}
		else if($this->amount < 100)
		{
			$this->errorCode = "amount";
			$this->errorMessage = "incorrect amount";
			return false;
		}
		
		if(!$this->checkIBAN($pars['iban']))
		{
			$this->errorCode = "IBAN";
			$this->errorMessage = "Invalid IBAN";
			return false;
		}
		
		$productexists = true;
		$productid = 1;
		$producttotal = 0;
		
		while($productexists)
		{
			if(!array_key_exists('product_id_' . $productid, $pars))
			{
				$productexists = false;
				continue;
			}
			else if(empty($pars['product_id_' . $productid]))
				$pars['product_id_' . $productid] = "artikel" . $productid;
			
			if(!array_key_exists('product_description_' . $productid, $pars) || empty($pars['product_description_' . $productid]))
			{
				$this->errorCode = "product_description_" . $productid;
				$this->errorMessage = "product_description_" . $productid . " is missing";
				return false;
			}
			
			if(!array_key_exists('product_quantity_' . $productid, $pars) || empty($pars['product_quantity_' . $productid]))
			{
				$this->errorCode = "product_quantity_" . $productid;
				$this->errorMessage = "product_quantity_" . $productid . " is missing";
				return false;
			}
			else if($pars['product_quantity_' . $productid] < 1)
			{
				$this->errorCode = "low product_quantity_" . $productid;
				$this->errorMessage = "product_quantity_" . $productid . " is lower than 1";
				return false;
			}
			
			if(!array_key_exists('product_netprice_' . $productid, $pars) || empty($pars['product_netprice_' . $productid]))
			{
				$this->errorCode = "product_netprice_". $productid;
				$this->errorMessage = "product_netprice_" . $productid . " is missing";
				return false;
			}
			else if($pars['product_netprice_' . $productid] < 1)
			{
				$this->errorCode = "low product_netprice_". $productid;
				$this->errorMessage = "product_netprice_" . $productid . " is lower than 1";
				return false;
			}
			
			if(!array_key_exists('product_total_' . $productid, $pars) || empty($pars['product_total_' . $productid]))
			{
				$this->errorCode = "product_total_". $productid;
				$this->errorMessage = "product_total_" . $productid . " is missing";
				return false;
			}
			else if($pars['product_total_' . $productid] < 1)
			{
				$this->errorCode = "low product_total_". $productid;
				$this->errorMessage = "product_total_" . $productid . " is lower than 1";
				return false;
			}
			else 
				$producttotal += $pars['product_total_' . $productid];
			
			if(!array_key_exists('product_nettotal_' . $productid, $pars) || empty($pars['product_nettotal_' . $productid]))
			{
				$this->errorCode = "product_nettotal_" . $productid;
				$this->errorMessage = "product_nettotal_" . $productid . " is missing";
				return false;
			}
			else if($pars['product_nettotal_' . $productid] < 1)
			{
				$this->errorCode = "low product_nettotal_". $productid;
				$this->errorMessage = "product_nettotal_" . $productid . " is lower than 1";
				return false;
			}
			
			if(!array_key_exists('product_tax_' . $productid, $pars) || empty($pars['product_tax_' . $productid]))
			{
				$this->errorCode = "product_tax_" . $productid;
				$this->errorMessage = "product_tax_" . $productid . " is missing";
				return false;
			}
			else if($pars['product_tax_' . $productid] < 1)
			{
				$this->errorCode = "low product_tax_" . $productid;
				$this->errorMessage = "product_tax_" . $productid . " is lower than 1";
				return false;
			}
			
			if(!array_key_exists('product_taxrate_' . $productid, $pars) || empty($pars['product_taxrate_' . $productid]))
			{
				$this->errorCode = "product_taxrate_" . $productid;
				$this->errorMessage = "product_taxrate_" . $productid . " is missing";
				return false;
			}
			
			$productid++;
		}
		
		$different = $producttotal - round($this->amount * 100.0);
		if($different != -1 && $different != 0 && $different != 1)
		{
			$this->errorCode = "cartamount";
			$this->errorMessage = "Cart total don't match amount";
			return false;
		}
		
		return true;
	}
	
	private function checkIBAN($iban)
	{
		$iban = strtolower($iban);
		$Countries = array(
			'al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,
			'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,
			'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,
			'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,
			'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24
		  );
		  $Chars = array(
			'a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,
			'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35
		  );

		  if (strlen($iban) != $Countries[ substr($iban,0,2) ]) { return false; }

		  $MovedChar = substr($iban, 4) . substr($iban,0,4);
		  $MovedCharArray = str_split($MovedChar);
		  $NewString = "";

		  foreach ($MovedCharArray as $k => $v) {

			if ( !is_numeric($MovedCharArray[$k]) ) {
			  $MovedCharArray[$k] = $Chars[$MovedCharArray[$k]];
			}
			$NewString .= $MovedCharArray[$k];
		  }
		  if (function_exists("bcmod")) { return bcmod($NewString, '97') == 1; }

		  $x = $NewString; $y = "97";
		  $take = 5; $mod = "";

		  do {
			$a = (int)$mod . substr($x, 0, $take);
			$x = substr($x, $take);
			$mod = $a % $y;
		  }
		  while (strlen($x));

		  return (int)$mod == 1;
	}
}
?>
