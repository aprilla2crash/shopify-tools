<?php
  //Modify these
  $API_KEY = 'blablakey';
  $SECRET = 'blablasecret';

  $STORE_URL = 'entershopurlhere.myshopify.com';
  $toEmail = 'toperson@place.com'
  $fromEmail = 'person@place.com';
  $replyEmail = 'otherperson@place.com';
  
  
function shopify_api_call($a_key , $a_secret , $url , $call){
	$url = 'https://' . $a_key . ':' . $a_secret . '@' . $url . '/admin/'. $call. '.json';

	$session = curl_init();

	curl_setopt($session, CURLOPT_URL, $url);
	curl_setopt($session, CURLOPT_HTTPGET, 1); 
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

	if(ereg("^(https)",$url)) curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);

	$response = curl_exec($session);
	curl_close($session);
    return $response;


}
  
  $headers  = 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  $headers .= 'To: Ronan <'.$toEmail.'>' . "\r\n";
	$headers .= 'From: Abandoned Checkout Checker '. $fromEmail . "\r\n" .
    'Reply-To: '. $replyEmail . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	$message = '
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>New abandoned checkout</title>
</head>
<body>
  <p>Check it out <a href="https://'. $STORE_URL .'/admin/checkouts">Here</a></p>
</body>
</html>
';
	
  $response = shopify_api_call($API_KEY, $SECRET, $STORE_URL, 'checkouts/count');

  $abandoned = json_decode($response,true); 
  //echo $response;
  $file = '/var/www/html/number.txt';
	// Open the file to get existing content
	$current = file_get_contents($file);
	//echo "current ". $current . "\n";
	//echo "\n";
	if ((int)$current < $abandoned['count']){
		file_put_contents($file, $abandoned['count']);
		mail('robrien@shadowmansports.com', 'New abandoned checkout', $message ,$headers);
    }
?>