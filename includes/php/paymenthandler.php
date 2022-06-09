<?php
function cf7_mollie_payment_handler () {
	require __DIR__ . '/initialize.php';
    global $mollie;
	cf7_mollie_setapikey();
	global $wp;
	global $wpdb;
	global $table_name;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	
	//Check if parameters come by post of get
	$requestType = $_SERVER['REQUEST_METHOD'];

	switch ($requestType) {
		case 'GET':
			$formid = !empty($_GET["formid"]) ? $_GET["formid"] : null;
			$amount = !empty($_GET["amount"]) ? $_GET["amount"] : "0";
			$order_id =! empty($_GET["order_id"]) ? $_GET["order_id"] : time();
			$issuer_id =! empty($_GET["issuer"]) ? $_GET["issuer"] : null;
			$method_id =! empty($_GET["paymentoption"]) ? $_GET["paymentoption"] : null;
			$payment_type =! empty($_GET["paymenttype"]) ? $_GET["paymenttype"] : null;
			$clientname =! empty($_GET["clientname"]) ? $_GET["clientname"] : null;
			$email =! empty($_GET["email"]) ? $_GET["email"] : null;
			$frequency =! empty($_GET["frequency"]) ? $_GET["frequency"] : null;
			$startdate =! empty($_GET["chargedate"]) ? $_GET["chargedate"] : null;
			$description =! empty($_GET["description"]) ? $_GET["description"] : "No description given.";
			$webhookUrl =! empty($_GET["redirecturl"]) ? $_GET["redirecturl"] : add_query_arg( $wp->query_vars, home_url( $wp->request ) );			
			break;
		default:
			$formid = !empty($_POST["formid"]) ? $_POST["formid"] : null;
			$amount = !empty($_POST["amount"]) ? $_POST["amount"] : "0";
			$order_id =! empty($_POST["order_id"]) ? $_POST["order_id"] : time();
			$issuer_id =! empty($_POST["issuer"]) ? $_POST["issuer"] : null;
			$method_id =! empty($_POST["paymentoption"]) ? $_POST["paymentoption"] : null;
			$payment_type =! empty($_POST["paymenttype"]) ? $_POST["paymenttype"] : null;
			$clientname =! empty($_POST["clientname"]) ? $_POST["clientname"] : null; 
			$email =! empty($_POST["email"]) ? $_POST["email"] : null;
			$frequency =! empty($_POST["frequency"]) ? $_POST["frequency"] : null;
			$startdate =! empty($_POST["chargedate"]) ? $_POST["chargedate"] : null;
			$description =! empty($_POST["description"]) ? $_POST["description"] : "No description given.";
			$webhookUrl =! empty($_POST["redirecturl"]) ? $_POST["redirecturl"] : add_query_arg( $wp->query_vars, home_url( $wp->request ) );
			break;
	}

	$amount = str_replace(',', '.', $amount);
	$amount = number_format($amount,2,".","");
    $amount = sanitize_text_field($amount);
    $order_id = sanitize_text_field($order_id);
    $issuer_id = sanitize_text_field($issuer_id);   
    $method_id = sanitize_text_field($method_id);
	$payment_type = sanitize_text_field($payment_type);
	$clientname = sanitize_text_field($clientname);
	$email = sanitize_email($email);
	$frequency = sanitize_text_field($frequency);
	$description = sanitize_text_field($description);
	$webhookUrl = esc_url_raw($webhookUrl); 
	cf7_mollie_setapikey($formid);
    $startdate = sanitize_key($startdate);
	
	if (get_post_meta( $formid, "CF7_mollie_redirecturl",true) && get_post_meta( $formid, "CF7_mollie_redirecturl",true) != ""){
		$redirectBaseUrl = get_post_meta( $formid, "CF7_mollie_redirecturl",true);
		$home = home_url();
		if (strpos($redirectBaseUrl, $home) == false) {
			$redirectBaseUrl = $home.$redirectBaseUrl;
		}
    }else{
        $redirectBaseUrl = $webhookUrl;
    }

    if (strpos($redirectBaseUrl, '?') == false){
        $redirectUrl=$redirectBaseUrl."?order_id={$order_id}";
    }else{
        $redirectUrl=$redirectBaseUrl."&order_id={$order_id}";
    }
	
	try{
		/*Create a customer if needed */
		if ($payment_type == "first"){
			$description = $description;
			$customer = $mollie->customers->create([
				  "name" => $clientname,
				  "email" => $email,
			]);
			
			//Store customer
			$customerid = $customer->id;
			
			$wpdb->insert( 
				$table_name,
				array( 
					'time' => current_time( 'mysql' ), 
					'orderid' => "{$order_id}",
					'amount' => "{$amount}",
					'description' => "{$description}",
					'customerid' => "{$customerid}",
					'name' => "{$clientname}",
					'email' => "{$email}",
					'subscriptionid' => "recurring",
					'startdate' => "{$startdate}",
					'frequency' => "{$frequency}"
				)
			);

			$payment = $mollie->payments->create([
				"amount" => [
					"currency" => "EUR",
					"value" => $amount
				],
				"description" => "{$description}",
				"redirectUrl" => "{$redirectUrl}",
				"webhookUrl" => "{$webhookUrl}",
				"customerId" => "{$customerid}",
				"sequenceType" => "first",
				"metadata" => [
					"order_id" => $order_id,
					"formid" => $formid,
				],
				"issuer" => $issuer_id
			]);
			redirect_to_mollie($payment);
		}else{
			/*
			 * Set payment parameters
			 */
			$payment = $mollie->payments->create([
				"amount" => [
					"currency" => "EUR",
					"value" => $amount
				],
				"method" => $method_id,
				"description" => "{$description}",
				"redirectUrl" => "{$redirectUrl}",
				"webhookUrl" => "{$webhookUrl}",
				"metadata" => [
					"order_id" => $order_id,
					"formid" => $formid,
				],
				"issuer" => $issuer_id
			]);
			redirect_to_mollie($payment);
		}
				
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "Paymenthandler: API call failed: " . htmlspecialchars($e->getMessage());
	}
}

function redirect_to_mollie($payment){
	//Redirect to Mollie using javascript
	echo '<script type="text/javascript">
			window.location = "'.$payment->getCheckoutUrl().'"
		</script>';
	  
    echo "CheckOutURL=".$payment->getCheckoutUrl();
    die();
}
