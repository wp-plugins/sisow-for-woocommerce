<?php

class Sisow {

    protected static $issuers;
    protected static $lastcheck;
    private $response;
    // Merchant data
    private $merchantId;
    private $merchantKey;
    // Transaction data
    public $payment; // empty=iDEAL; sofort=DIRECTebanking; mistercash=MisterCash; ...
    public $issuerId; // mandatory; sisow bank code
    public $purchaseId; // mandatory; max 16 alphanumeric
    public $entranceCode; // max 40 strict alphanumeric (letters and numbers only)
    public $description; // mandatory; max 32 alphanumeric
    public $amount;  // mandatory; min 0.45
    public $notifyUrl;
    public $returnUrl; // mandatory
    public $cancelUrl;
    public $callbackUrl;
    // Status data
    public $status;
    public $timeStamp;
    public $consumerAccount;
    public $consumerName;
    public $consumerCity;
    // Invoice data
    public $invoiceNo;
    public $documentId;
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
    const statusSuccess     = "Success";
    const statusCancelled   = "Cancelled";
    const statusExpired     = "Expired";
    const statusFailure     = "Failure";
    const statusOpen        = "Open";
    const statusPending     = "Pending";
    const statusReservation = "Reservation";

    public function __construct($merchantid, $merchantkey) {
        $this->merchantId = $merchantid;
        $this->merchantKey = $merchantkey;
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
        $diff = 24 * 60 * 60;
        if (self::$lastcheck) {
            $diff = time() - self::$lastcheck;
        }
        if ($diff < 24 * 60 * 60) {
            return 0;
        }
        if (!$this->send("DirectoryRequest")) {
            return -1;
        }
        $search = $this->parse("directory");
        if (!$search) {
            $this->error();
            return -2;
        }
        self::$issuers = array();
        $iss = explode("<issuer>", str_replace("</issuer>", "", $search));
        foreach ($iss as $k => $v) {
            $issuerid = $this->parse("issuerid", $k);
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
            } else {
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
        } else {
            $output = array();
        }
        foreach (self::$issuers as $k => $v) {
            if ($select === true) {
                $output .= "<option value=\"" . $k . "\">" . $v . "</option>";
            } else {
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
        if (!$this->entranceCode) {
            $this->entranceCode = $this->purchaseId;
        }
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
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
        $pars["sha1"] = sha1($this->purchaseId . $this->entranceCode . round($this->amount * 100) . $this->merchantId . $this->merchantKey);
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
        $this->issuerUrl = urldecode($this->parse("issuerurl"));
        $this->documentId = $this->parse("documentid");
        $this->pendingKlarna = $this->parse("pendingklarna") == "true";
        if (!$this->issuerUrl) {
            $this->error();
            return -9;
        }
        return 0;
    }

    // StatusRequest
    public function StatusRequest($trxid = false) {
        if ($trxid === false) {
            $trxid = $this->trxId;
        }
        if (!$this->merchantId) {
            return -1;
        }
        if (!$this->merchantKey) {
            return -2;
        }
        if (!$trxid) {
            return -3;
        }
        $this->trxId = $trxid;
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
        $pars["trxid"] = $this->trxId;
        $pars["sha1"] = sha1($this->trxId . $this->merchantId . $this->merchantKey);
        if (!$this->send("StatusRequest", $pars)) {
            return -4;
        }
        $this->status = $this->parse("status");
        if (!$this->status) {
            $this->error();
            return -5;
        }
        $this->timeStamp = $this->parse("timestamp");
        $this->amount = $this->parse("amount") / 100.0;
        $this->consumerAccount = $this->parse("consumeraccount");
        $this->consumerName = $this->parse("consumername");
        $this->consumerCity = $this->parse("consumercity");
        $this->purchaseId = $this->parse("purchaseid");
        $this->description = $this->parse("description");
        $this->entranceCode = $this->parse("entrancecode");
        return 0;
    }

    // FetchMonthlyRequest
    public function FetchMonthlyRequest($amt = false) {
        if (!$amt) {
            $amt = round($this->amount * 100);
        } else {
            $amt = round($amt * 100);
        }
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
        $pars["amount"] = $amt;
        $pars["sha1"] = sha1($amt . $this->merchantId . $this->merchantKey);
        if (!$this->send("FetchMonthlyRequest", $pars)) {
            return -1;
        }
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
        if (!$this->send("RefundRequest", $pars)) {
            return -1;
        }
        $id = $this->parse("id");
        if (!$id) {
            $this->error();
            return -2;
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
        if (!$this->send("InvoiceRequest", $pars)) {
            return -1;
        }
        $this->invoiceNo = $this->parse("invoiceno");
        if (!$this->invoiceNo) {
            $this->error();
            return -2;
        }
        $this->documentId = $this->parse("documentid");
        return 0;
    }

    // CreditInvoiceRequest
    public function CreditInvoiceRequest($trxid) {
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
        $pars["trxid"] = $trxid;
        $pars["sha1"] = sha1($trxid . $this->merchantId . $this->merchantKey);
        if (!$this->send("CreditInvoiceRequest", $pars)) {
            return -1;
        }
        $this->invoiceNo = $this->parse("invoiceno");
        if (!$this->invoiceNo) {
            $this->error();
            return -2;
        }
        $this->documentId = $this->parse("documentid");
        return 0;
    }

    // CancelReservationRequest
    public function CancelReservationRequest($trxid) {
        $pars = array();
        $pars["merchantid"] = $this->merchantId;
        $pars["trxid"] = $trxid;
        $pars["sha1"] = sha1($trxid . $this->merchantId . $this->merchantKey);
        if (!$this->send("CancelReservationRequest", $pars)) {
            return -1;
        }
        return 0;
    }

    public function GetLink($msg = 'hier', $method = '') {
        if (!$method) {
            if (!$msg) {
                $link = 'https://www.sisow.nl/Sisow/Opdrachtgever/download.aspx?merchantid=' .
                        $this->merchantId . '&doc=' . $this->documentId . '&sha1=' .
                        sha1($this->documentId . $this->merchantId . $this->merchantKey);
            } else {
                $link = '<a href="https://www.sisow.nl/Sisow/Opdrachtgever/download.aspx?merchantid=' .
                        $this->merchantId . '&doc=' . $this->documentId . '&sha1=' .
                        sha1($this->documentId . $this->merchantId . $this->merchantKey) . '">' . $msg . '</a>';
            }
            return $link;
        }
        if ($method == 'make') {
            
        }
    }

    public function logSisow($error, $order_id = 0, $dir = '', $act = 'TransactionRequest') {
        $filename = ($dir != '') ? $dir . '/log_sisow.log' : 'log_sisow.log';
        $order_id = (($order_id == 0 || $order_id == '') && $this->purchaseId != '') ? $this->purchaseId : $order_id;

        $f = fopen($filename, 'a+');
        fwrite($f, "\n" . "|***" . date("d-m-Y H:i:s") . "*******" . "\n");
        fwrite($f, "| - " . $act . " - " . "\n");
        fwrite($f, "| Order: " . $order_id . "\n");
        fwrite($f, "| Error: " . $error . "\n");
        fwrite($f, "----------------------------" . "\n");
        fclose($f);
    }

    public function getIdealForm($testmode = false) {
        $banken = array();
        $this->DirectoryRequest($banken, false, $testmode);

        $form = '<img src="http://www.ideal.nl/img/iDEAL-Payoff-1-klein.jpg" height="50px" alt="iDEAL" title="iDEAL"/>'; // style="vertical-align: middle;" />';
        //$form .= '</br>Kies uw bank';
        $form .= '</br><select name="sisow_bank" id="sisow_bank">';
        $form .= '<option value="">Kies uw bank...</option>';
        foreach ($banken as $k => $v) {
            $form .= '<option value="' . $k . '">' . $v . '</option>';
        }
        $form .= '</select>';

        return $form;
    }

    public function getKlarnaForm($type = 'factuur', $klarnaid = 0, $paymentcode = '', $total = 0, $currency = '') {
        $paymentcode = ($paymentcode != '') ? '_' . $paymentcode : $paymentcode;


        //selects genereren
        //dagen
        $dag = '<select name="sisow_dag' . $paymentcode . '" id="sisow_dag' . $paymentcode . '">';
        $dag .= '<option value="">Dag</option>';
        for ($i = 1; $i < 32; $i++) {
            $dag .= '<option value="' . sprintf("%02s", $i) . '">' . sprintf("%02s", $i) . '</option>';
        }
        $dag .= '</select>';

        //maanden
        $maanden = array('1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maart',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'Autustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'December');

        $maand = '<select name="sisow_maand' . $paymentcode . '" id="sisow_maand' . $paymentcode . '">';
        $maand .= '<option value="">Maand</option>';
        foreach ($maanden as $k => $v) {
            $maand .= '<option value="' . sprintf("%02s", $k) . '">' . $v . '</option>';
        }
        $maand .= '</select>';

        //jaren
        $jaartal = date("Y") - 17;
        $jaar_min = $jaartal - 137;

        $jaar = '<select name="sisow_jaar' . $paymentcode . '" id="sisow_jaar' . $paymentcode . '">';
        $jaar .= '<option value="">Jaar</option>';
        for ($jaartal; $jaartal > $jaar_min; $jaartal--) {
            $jaar .= '<option value="' . sprintf("%04s", $jaartal) . '">' . sprintf("%04s", $jaartal) . '</option>';
        }
        $jaar .= '</select>';

        //currency symbol
        switch ($currency) {
            case 'EUR':
                $currency_symbol = '&euro;';
                break;
            case 'DDK':
                $currency_symbol = 'kr ';
                break;
            case 'NOK':
                $currency_symbol = 'kr ';
                break;
            case 'SEK':
                $currency_symbol = 'kr ';
                break;
        }

        $fields = '';

        if ($type == 'factuur') {
            $fields .= '<img src="https://cdn.klarna.com/public/images/NL/badges/v1/invoice/NL_invoice_badge_std_blue.png?height=50&eid=' . $klarnaid . '" alt="Klarna" /><br/>';
            $fields .= 'Klarna Factuur - Betaal binnen 14 dagen.<br/>';
        } else if ($type == 'account') {
            $this->FetchMonthlyRequest($total);
            $fields .= '<img src="https://cdn.klarna.com/public/images/NL/badges/v1/account/NL_account_badge_std_blue.png?height=50&eid=' . $klarnaid . '" alt="Klarna" /><br/><br/>
	
				Klarna Account - vanaf <b>' . $currency_symbol . $this->monthly / 100.0 . '</b> per maand. </br>
						<input type="hidden" name="payment_info[sisow_pclass]" id="sisow_pclass" value="' . $this->pclass . '" />
				
				<br/>
				<img src="https://www.sisow.nl/images/betaallogos/lenenkostgeld.jpg" alt="geld lenen kost geld"/>
				<br/>
				<br/>';
        }

        $fields .= '<p>Aanhef: <br/>
                                    <select name="sisow_gender' . $paymentcode . '">
                                            <option value=""></option>
                                            <option value="m">De heer</option>
                                            <option value="f">Mevrouw</option>
                                    </select>

                                    <p>Telefoonnummer: <br/>
                                    <input type="text" name="sisow_phone' . $paymentcode . '" id="sisow_phone" size="13" maxlength="13" value="" />

                                    <p>Geboortedatum: <br/>
                                    ' . $dag . '&nbsp&nbsp' . $maand . '&nbsp&nbsp' . $jaar . '
                                    </p>';
        if ($type == 'factuur') {
            $fields .= '<a href="https://online.klarna.com/account_nl.yaws?eid=' . $klarnaid . '" target="_blank">Klarna factuurvoorwaarden!</a>';
        } else {
            $fields .= '<a href="https://online.klarna.com/account_nl.yaws?eid=' . $klarnaid . '" target="_blank">Lees meer!</a>';
        }
        return $fields;
    }

}
