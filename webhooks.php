<?php

define('SHOPIFY_APP_SECRET', 'put-shopify-webhook-secret-here');
$api_login = 'put-onepage-email-here';
$api_password = 'put-onepage-password-here';

function verify_webhook($data, $hmac_header){
	$calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
	return ($hmac_header == $calculated_hmac);
}

function make_api_call($url, $http_method, $post_data = array(), $uid = null, $key = null){
	$full_url = 'https://app.onepagecrm.com/api/v3/'.$url;
	$ch = curl_init($full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
	$timestamp = time();
	$auth_data = array($uid, $timestamp, $http_method, sha1($full_url));
    $request_headers = array();
	// For POST and PUT requests we will send data as JSON
    // as with regular "form data" request we won't be able
    // to send more complex structures
	if($http_method == 'POST' || $http_method == 'PUT'){
        $request_headers[] = 'Content-Type: application/json';
        $json_data = json_encode($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        $auth_data[] = sha1($json_data);
    }
	// Set auth headers if we are logged in
	if($key != null){
        $hash = hash_hmac('sha256', implode('.', $auth_data), $key);
        $request_headers[] = "X-OnePageCRM-UID: $uid";
        $request_headers[] = "X-OnePageCRM-TS: $timestamp";
        $request_headers[] = "X-OnePageCRM-Auth: $hash";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
	$result = json_decode(curl_exec($ch));
	curl_close($ch);
	if($result->status > 99){
        echo "API call error: {$result->message}\n";
        return null;
    }
	return $result;
}


$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
$jsondata = file_get_contents('php://input');
http_response_code(200);
$verified = verify_webhook($jsondata, $hmac_header);

//function check_for_user($email,$uid,$key){
//contacts.json?email=$email	

//}

if($verified){

	$data = make_api_call('login.json', 'POST', array('login' => $api_login, 'password' => $api_password));
	if($data == null){
    exit;
	}
	// Get UID and API key from result
	$uid = $data->data->user_id;
	$key = base64_decode($data->data->auth_key);

	$json_obj = json_decode($jsondata);


	$contact_data = array(
		'first_name' => $json_obj->customer->first_name,
		'last_name' =>  $json_obj->customer->last_name,
		'company_name' =>  $json_obj->shipping_address->company,
		'tags' => 'Website Sale',
		'status_id' => '520dbf64d31bb56fdd00088a',
		'address_list' => array(array(
			'address'=> $json_obj->shipping_address->address1,
			'city'=> $json_obj->shipping_address->city,
			'state'=> $json_obj->shipping_address->province_code,
			'zip_code'=> $json_obj->shipping_address->zip,
			'country_code'=> $json_obj->shipping_address->country_code
		)),
		'phones' => array(
			array('type' => 'work', 'value' =>  $json_obj->shipping_address->phone)    
		),
		'emails' => array(
			array('type' => 'work', 'value' =>  $json_obj->email)    
		)
	);
	$deal_data = array(
		'date' => date("Y-m-d"),
		'name' => "Website order ".$json_obj->name,
		'status' => "won",
		'amount' => $json_obj->total_price	
	);
	
	$new_contact = make_api_call('contacts.json', 'POST', $contact_data, $uid, $key);
	
	if($new_contact == null){
		exit;
	}
	
	$cid = $new_contact->data->contact->id;
	$new_deal = make_api_call("deals.json?contact_id=$cid", 'POST', $deal_data, $uid, $key);
	if($new_deal == null){
		exit;
	}

}

?>



