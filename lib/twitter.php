<?php
/**
* 	Oauth.php
*
*	Created by Jon Hurlock on 2013-03-20.
* 	
*	Jon Hurlock's Twitter Application-only Authentication App by Jon Hurlock (@jonhurlock)
*	is licensed under a Creative Commons Attribution-ShareAlike 3.0 Unported License.
*	Permissions beyond the scope of this license may be available at http://www.jonhurlock.com/.
*/

if(!TWITTER_API_KEY || !TWITTER_API_SECRET) {
	die("Missing Twitter API configuration!");
}

/**
*	Get the Bearer Token, this is an implementation of steps 1&2
*	from https://dev.twitter.com/docs/auth/application-only-auth
*/
function get_twitter_token(){
	// Step 1
	// step 1.1 - url encode the consumer_key and consumer_secret in accordance with RFC 1738
	$encoded_consumer_key = urlencode(TWITTER_API_KEY);
	$encoded_consumer_secret = urlencode(TWITTER_API_SECRET);
	// step 1.2 - concatinate encoded consumer, a colon character and the encoded consumer secret
	$bearer_token = $encoded_consumer_key.':'.$encoded_consumer_secret;
	// step 1.3 - base64-encode bearer token
	$base64_encoded_bearer_token = base64_encode($bearer_token);
	// step 2
	$url = "https://api.twitter.com/oauth2/token"; // url to send data to for authentication
	$headers = array( 
		"POST /oauth2/token HTTP/1.1", 
		"Host: api.twitter.com", 
		"User-Agent: jonhurlock Twitter Application-only OAuth App v.1",
		"Authorization: Basic ".$base64_encoded_bearer_token."",
		"Content-Type: application/x-www-form-urlencoded;charset=UTF-8", 
		"Content-Length: 29"
	); 

	$ch = curl_init();  // setup a curl
	curl_setopt($ch, CURLOPT_URL,$url);  // set url to send to
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // set custom headers
	curl_setopt($ch, CURLOPT_POST, 1); // send as post
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return output
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials"); // post body/fields to be sent
	$header = curl_setopt($ch, CURLOPT_HEADER, 1); // send custom headers
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$retrievedhtml = curl_exec ($ch); // execute the curl
	curl_close($ch); // close the curl
	$output = explode("\n", $retrievedhtml);
	$bearer_token = '';
	foreach($output as $line)
	{
		if($line === false)
		{
			// there was no bearer token
		}else{
			$bearer_token = $line;
		}
	}
	$bearer_token = json_decode($bearer_token);
	return $bearer_token->{'access_token'};
}

/**
* Invalidates the Bearer Token
* Should the bearer token become compromised or need to be invalidated for any reason,
* call this method/function.
*/
function invalidate_twitter_token($bearer_token){
	$encoded_consumer_key = urlencode(TWITTER_API_KEY);
	$encoded_consumer_secret = urlencode(TWITTER_API_SECRET);
	$consumer_token = $encoded_consumer_key.':'.$encoded_consumer_secret;
	$base64_encoded_consumer_token = base64_encode($consumer_token);
	// step 2
	$url = "https://api.twitter.com/oauth2/invalidate_token"; // url to send data to for authentication
	$headers = array( 
		"POST /oauth2/invalidate_token HTTP/1.1", 
		"Host: api.twitter.com", 
		"User-Agent: jonhurlock Twitter Application-only OAuth App v.1",
		"Authorization: Basic ".$base64_encoded_consumer_token."",
		"Accept: */*", 
		"Content-Type: application/x-www-form-urlencoded", 
		"Content-Length: ".(strlen($bearer_token)+13).""
	); 
    
	$ch = curl_init();  // setup a curl
	curl_setopt($ch, CURLOPT_URL,$url);  // set url to send to
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // set custom headers
	curl_setopt($ch, CURLOPT_POST, 1); // send as post
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return output
	curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=".$bearer_token.""); // post body/fields to be sent
	$header = curl_setopt($ch, CURLOPT_HEADER, 1); // send custom headers
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$retrievedhtml = curl_exec ($ch); // execute the curl
	curl_close($ch); // close the curl
	return $retrievedhtml;
}

/* addition by c3o */

function fetchTwitterJSON($url) {
	$u = parse_url($url);
	$twitter_token = get_twitter_token();
	$headers = array(
		"GET ".$u['path']."?".$u['query']." HTTP/1.1", 
		"Host: ".$u['host'], 
		"Authorization: Bearer ".$twitter_token."",
	);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$u['scheme'].'://'.$u['host'].$u['path'].'?'.$u['query']);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return output
	$jsonString = curl_exec($ch);
	curl_close($ch);
	invalidate_twitter_token($twitter_token);
	return $jsonString;
}

?>