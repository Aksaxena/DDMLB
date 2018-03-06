<?php

require_once '../vendor/Bitcoin/cryptobox.class.php';


class Payment_PaymentController extends Zend_Controller_Action{
    
    public function init() {

    }
    
    public function paymentAction(){
        
        $objPaypal = Engine_Payment_Paypal_Paypal::getInstance();
        
        $returnURL = "https://".$_SERVER['HTTP_HOST']."/success";
        $cancelURL = "http://".$_SERVER['HTTP_HOST']."/cancel-order";
        
        echo "payment Controller";
//        $paymentAmount = "12.5";
//        $desc = "Testing Payment";
//        $result = $objPaypal->CallShortcutExpressCheckout( $paymentAmount, $returnURL, $cancelURL, $desc);
//        print"<pre>";print_r($result);print"</pre>";
        
        
        /**
         * Desc : Call Mass payment 
         * @param <array> $recipientDetails 
         *                include recipient paypal email & amount
         */
        
        $recepientDetails = array();    
        
        $recepientDetails[0]['email'] = "phptestglobussoft@gmail.com";
        $recepientDetails[0]['amount'] = "10.00";
        
        $massPayResult = $objPaypal->MassPay($recepientDetails);
        
        if($massPayResult['ACK'] == "Success"){
            print"<pre>";print_r($massPayResult);print"</pre>";
            die;
        }else{
            print"<pre>";print_r($massPayResult['L_ERRORCODE0']);print"</pre>";
            print"<pre>";print_r($massPayResult['L_LONGMESSAGE0']);print"</pre>";
            die;
        }
        
    }
    
    public function successAction(){
         $data = $this->getRequest()->getParams();
         
        if(isset($data['mc_gross'])){
            $this->view->amount = $data['mc_gross'];
        }
    }
    
    public function cancelOrderAction(){
        
    }
	
    // writen by prince kumar dwivedi for DFSCoins purchase by BTC
	public function getDfsMarket(){
		$url = "https://api.coinmarketcap.com/v1/ticker/dfscoin/";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch, CURLOPT_HEADER, false);			
		$result = curl_exec($ch);

		if($result === false){
			
		}	
		curl_close($ch);
		
		if(!empty($result)){
			$recent_market = json_decode($result,true);
			$recent_market = call_user_func_array('array_merge',$recent_market);
		}
		return $recent_market;
	}
	
    public function purchaseDfscoinAction() {
				
		$recent_market = $this->getDfsMarket();
		$this->view->bitcoin_value = $recent_market['price_btc'];
		$this->view->recent_market = $recent_market;
    }
    
	public function paymentDfscoinAction() {
		
		$objUsers = Application_Model_Users::getInstance();
		
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		
		$bitcoin_config = $this->getDfsMarket();
		
		
		$userId = $this->view->session->storage->user_id; 
		
		$this->view->users_details = $objUsers->getUserDetailsByUserId($userId);
		
		if ($this->getRequest()->isPost()) { 
			
			$post_data = $this->getRequest()->getPost();
			if(isset($post_data['bonus-radio'])) {
				$total_btc_defined = $this->getRequest()->getPost('bonus-radio');
			
				if($total_btc_defined != -1) {
					
					$total_btc = number_format($this->getRequest()->getPost('bonus-radio'), 8, '.', '');
					
					$total_dfs = $total_btc/$bitcoin_config['price_btc'];
					
					$this->view->session->storage->pay_data['custom_dfs_amt'] = $total_dfs;
					
					$this->view->session->storage->pay_data['custom_btc_amt'] = $total_btc;
					
				} else {
					
					$this->view->session->storage->pay_data['custom_dfs_amt'] = $this->getRequest()->getPost('custom_dfs_amt');
					
					$this->view->session->storage->pay_data['custom_btc_amt'] = $this->getRequest()->getPost('custom_btc_amt');
					
				}
				
				$this->view->session->storage->Invoice_date = date("d-m-Y");
				
				$this->view->session->storage->Invoice_no = rand();
			}
		}
		/**** CONFIGURATION VARIABLES ****/ 
	
		$userID 		= "DDUSER_".$userId;				
		// place your registered userID or md5(userID) here (user1, user7, uo43DC, etc).
													// you don't need to use userID for unregistered website visitors
													// if userID is empty, system will autogenerate userID and save in cookies
		$userFormat		= "COOKIE";					// save userID in cookies (or you can use IPADDRESS, SESSION)
		$orderID 		= $this->view->session->storage->Invoice_no;	// invoice number - 000383
		$amount			= $this->view->session->storage->pay_data['custom_btc_amt'];
	    $amountUSD		= 0; //convert_currency_live("BTC", "USD", $this->view->session->storage->pay_data['custom_btc_amt']);
		
		$period			= "NOEXPIRY";				// one time payment, not expiry
		$def_language	= "en";						// default Payment Box Language
		$public_key		= "13108AAf8l9FBitcoin77BTCPUB2peOGaPOGF20MLcgvHMoXHm"; // from gourl.io
		$private_key	= "13108AAf8l9FBitcoin77BTCPRVMRx8quZcxPwfEc93ArGB6Di"; // from gourl.io

		
		/** PAYMENT BOX **/
		
		$options = array(
			"public_key"  => $public_key,  	// your public key from gourl.io
			"private_key" => $private_key,  // your private key from gourl.io
			"webdev_key"  => "",   			// optional, gourl affiliate key
			"orderID"     => $orderID,   	// order id or product name
			"userID"      => $userID,   	// unique identifier for every user
			"userFormat"  => $userFormat,  	// save userID in COOKIE, IPADDRESS or SESSION
			"amount"      => $amount,    	// product price in coins OR in USD below
			"amountUSD"   => $amountUSD, 	// we use product price in USD
			"period"      => $period,   	// payment valid period
			"iframeID"    => "", 
			"language"   => $def_language  	// text on EN - english, FR - french, etc
		); 
 
		// Initialise Payment Class
		$box = new Cryptobox ($options);
		$this->view->box = $box;
		// coin name
		$coinName = $box->coin_name(); 
		
		// Successful Cryptocoin Payment received
		if ($box->is_paid()) 
		{
			
			if (!$box->is_confirmed()) {
				
				$this->view->message =  "Thank you for order (order #".$orderID.", payment #".$box->payment_id()."). Awaiting transaction/payment confirmation";
				
			} else { // payment confirmed (6+ confirmations)

				// one time action
				if (!$box->is_processed())
				{
					// One time action after payment has been made/confirmed
					// !!For update db records, please use function cryptobox_new_payment()!!
					 
					$this->view->message = "Thank you for order (order #".$orderID.", payment #".$box->payment_id()."). Payment Confirmed. <br>(User will see this message one time after payment has been made)"; 
					
					// Set Payment Status to Processed
					$box->set_status_processed();  
				} else {
					
					$this->view->message = "Thank you for order (order #".$orderID.", payment #".$box->payment_id()."). Payment Confirmed. <br>(User will see this message during ".$period." period after payment has been made)"; // General message
				}
			}
		} else {
			
			$this->view->message = "This invoice has not been paid yet";
		} 
		
		
		// Optional - Language selection list for payment box (html code)
		$this->view->languages_list = display_language_box($def_language); 
		
		//die("xcsccxz");
    }
		
	public function callbackPurchaseDfscoinAction() {
				
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(TRUE);
		//die('AS');
		// a. check if private key valid
		$valid_key = false;
		
		if (isset($_POST["private_key_hash"]) && strlen($_POST["private_key_hash"]) == 128 && preg_replace('/[^A-Za-z0-9]/', '', $_POST["private_key_hash"]) == $_POST["private_key_hash"])
		{
			$keyshash = array();
			$arr = explode("^", CRYPTOBOX_PRIVATE_KEYS);
			foreach ($arr as $v) $keyshash[] = strtolower(hash("sha512", $v));
			if (in_array(strtolower($_POST["private_key_hash"]), $keyshash)) $valid_key = true;
		}


		// b. alternative - ajax script send gourl.io json data
		if (!$valid_key && isset($_POST["json"]) && $_POST["json"] == "1")
		{
			$data_hash = $boxID = "";
			if (isset($_POST["data_hash"]) && strlen($_POST["data_hash"]) == 128 && preg_replace('/[^A-Za-z0-9]/', '', $_POST["data_hash"]) == $_POST["data_hash"]) { $data_hash = $_POST["data_hash"]; unset($_POST["data_hash"]); }
			if (isset($_POST["box"]) && is_numeric($_POST["box"]) && $_POST["box"] > 0) $boxID = intval($_POST["box"]);
			
			if ($data_hash && $boxID)
			{
				$private_key = "";
				$arr = explode("^", CRYPTOBOX_PRIVATE_KEYS);
				foreach ($arr as $v) if (strpos($v, $boxID."AA") === 0) $private_key = $v;
			
				if ($private_key)
				{
					$data_hash2 = strtolower(hash("sha512", $private_key.json_encode($_POST).$private_key));
					if ($data_hash == $data_hash2) $valid_key = true;
				}
				unset($private_key);
			}
			
			if (!$valid_key) die("Error! Invalid Json Data sha512 Hash!"); 
			
		}


		// c.
		if ($_POST) foreach ($_POST as $k => $v) if (is_string($v)) $_POST[$k] = trim($v);



		// d.
		if (isset($_POST["plugin_ver"]) && !isset($_POST["status"]) && $valid_key)
		{
			echo "cryptoboxver_" . (CRYPTOBOX_WORDPRESS ? "wordpress_" . GOURL_VERSION : "php_" . CRYPTOBOX_VERSION);
			die; 
		}


		// e.
		if (isset($_POST["status"]) && in_array($_POST["status"], array("payment_received", "payment_received_unrecognised")) && $_POST["box"] && is_numeric($_POST["box"]) && $_POST["box"] > 0 && $_POST["amount"] && is_numeric($_POST["amount"]) && $_POST["amount"] > 0 && $valid_key)
		{
			
			foreach ($_POST as $k => $v)
			{
				if ($k == "datetime") 						$mask = '/[^0-9\ \-\:]/';
				elseif (in_array($k, array("err", "date", "period")))		$mask = '/[^A-Za-z0-9\.\_\-\@\ ]/';
				else								$mask = '/[^A-Za-z0-9\.\_\-\@]/';
				if ($v && preg_replace($mask, '', $v) != $v) 	$_POST[$k] = "";
			}
			
			if (!$_POST["amountusd"] || !is_numeric($_POST["amountusd"]))	$_POST["amountusd"] = 0;
			if (!$_POST["confirmed"] || !is_numeric($_POST["confirmed"]))	$_POST["confirmed"] = 0;
			
			
			$dt			= gmdate('Y-m-d H:i:s');
			$obj 		= run_sql("select paymentID, txConfirmed from crypto_payments where boxID = ".$_POST["box"]." && orderID = '".$_POST["order"]."' && userID = '".$_POST["user"]."' && txID = '".$_POST["tx"]."' && amount = ".$_POST["amount"]." && addr = '".$_POST["addr"]."' limit 1");
			
			
			$paymentID		= ($obj) ? $obj->paymentID : 0;
			$txConfirmed	= ($obj) ? $obj->txConfirmed : 0; 
			
			// Save new payment details in local database
			if (!$paymentID)
			{
				$sql = "INSERT INTO crypto_payments (boxID, boxType, orderID, userID, countryID, coinLabel, amount, amountUSD, unrecognised, addr, txID, txDate, txConfirmed, txCheckDate, recordCreated)
						VALUES (".$_POST["box"].", '".$_POST["boxtype"]."', '".$_POST["order"]."', '".$_POST["user"]."', '".$_POST["usercountry"]."', '".$_POST["coinlabel"]."', ".$_POST["amount"].", ".$_POST["amountusd"].", ".($_POST["status"]=="payment_received_unrecognised"?1:0).", '".$_POST["addr"]."', '".$_POST["tx"]."', '".$_POST["datetime"]."', ".$_POST["confirmed"].", '$dt', '$dt')";

				$paymentID = run_sql($sql);
				
				$box_status = "cryptobox_newrecord";
			}
			// Update transaction status to confirmed
			elseif ($_POST["confirmed"] && !$txConfirmed)
			{
				$sql = "UPDATE crypto_payments SET txConfirmed = 1, txCheckDate = '$dt' WHERE paymentID = $paymentID LIMIT 1";
				run_sql($sql);
				
				$box_status = "cryptobox_updated";
			}
			else 
			{
				$box_status = "cryptobox_nochanges";
			}
			
			
			/**
			 *  User-defined function for new payment - cryptobox_new_payment(...)
			 *  For example, send confirmation email, update database, update user membership, etc.
			 *  You need to modify file - cryptobox.newpayment.php
			 *  Read more - https://gourl.io/api-php.html#ipn
				 */

			if (in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated"))) { 
				$this->cryptobox_new_payment($paymentID, $_POST, $box_status);
			}
		}  else {
			$box_status = "Only POST Data Allowed";


			
		}
		echo $box_status; // don't delete it 
	}
	
	public function cryptobox_new_payment($paymentID = 0, $payment_details = array(), $box_status = "")
	{	
		
		$objUserAccount = Application_Model_UserAccount::getInstance();    
        $objUserTransactionsModel = Application_Model_UserTransactions::getInstance();     
        //$objUsers = Application_Model_Users::getInstance();
		
		$config = Zend_Controller_Front::getInstance()->getParam('bootstrap');		
		$bitcoin_config = $this->getDfsMarket();;
		
		$transactions = array();
		if(!empty($payment_details)) {
			$userId = str_replace('DDUSER_','',$payment_details["user"]);
			if($payment_details["confirmed"]) {
				
				$isCompleted = $objUserTransactionsModel->getUserTransactionByconfirmationCode($payment_details["tx"]);
				
				if(!($isCompleted)) {
					$total_btc = number_format(floatval($payment_details["amount"]), 4, '.', '');
					$total_btc = number_format($total_btc, 8, '.', '');	
					$total_dfs = $total_btc/$bitcoin_config['price_btc'];
						
					$transactions['user_id'] = $userId;
					$transactions['transaction_type'] = 'Bitcoin';
					$transactions['transaction_amount'] = $total_dfs;
					$transactions['confirmation_code'] = $payment_details["tx"];
					$transactions['description'] = 'Purchase DFSCoin By BTC';
					$transactions['status'] = '1';
					$transactions['request_type'] = '7';
					$transactions['transaction_date'] = date('Y-m-d');
					
					$result = $objUserTransactionsModel->insertUseTransactions($transactions);
					
					$accountData = array('balance_amt' => $total_dfs, 'last_deposite'=>$total_dfs);
					
					$objUserAccount->updatePurchaseBalance($userId, $accountData);
				}				
			}
		}
		
		
		
		
		/** .............
		.............

		PLACE YOUR CODE HERE

		Update database with new payment, send email to user, etc
		Please note, all received payments store in your table `crypto_payments` also
		See - https://gourl.io/api-php.html#payment_history
		.............
		.............
		For example, you have own table `user_orders`...
		You can use function run_sql() from cryptobox.class.php ( https://gourl.io/api-php.html#run_sql )
		
		.............
		// Save new Bitcoin payment in database table `user_orders`
		$recordExists = run_sql("select paymentID as nme FROM `user_orders` WHERE paymentID = ".intval($paymentID));
		if (!$recordExists) run_sql("INSERT INTO `user_orders` VALUES(".intval($paymentID).",'".$payment_details["user"]."','".$payment_details["order"]."',".floatval($payment_details["amount"]).",".floatval($payment_details["amountusd"]).",'".$payment_details["coinlabel"]."',".intval($payment_details["confirmed"]).",'".$payment_details["status"]."')");
		
		.............
		// Received second IPN notification (optional) - Bitcoin payment confirmed (6+ transaction confirmations)
		if ($recordExists && $box_status == "cryptobox_updated")  run_sql("UPDATE `user_orders` SET txconfirmed = ".intval($payment_details["confirmed"])." WHERE paymentID = ".intval($paymentID));
		.............
		.............

		// Onetime action when payment confirmed (6+ transaction confirmations)
		$processed = run_sql("select processed as nme FROM `crypto_payments` WHERE paymentID = ".intval($paymentID)." LIMIT 1");
		if (!$processed && $payment_details["confirmed"])
		{
			// ... Your code ...

			// ... and update status in default table where all payments are stored - https://github.com/cryptoapi/Payment-Gateway#mysql-table
			$sql = "UPDATE crypto_payments SET processed = 1, processedDate = '".gmdate("Y-m-d H:i:s")."' WHERE paymentID = ".intval($paymentID)." LIMIT 1";
			run_sql($sql);
		}

		.............
		
		 */
	 
		// Debug - new payment email notification for webmaster
		// Uncomment lines below and make any test payment
		// --------------------------------------------
		// $email = "....your email address....";
		// mail($email, "Payment - " . $paymentID . " - " . $box_status, " \n Payment ID: " . $paymentID . " \n\n Status: " . $box_status . " \n\n Details: " . print_r($payment_details, true));




		return true;     
	}
    
}


?>
