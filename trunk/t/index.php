<?
/////////////////////////////////////////////////////////////////////////////
// BoTwi // t/index.php
/////////////////////////////////////////////////////////////////////////////
/**
 * Proxy API (some method need username/password OR oauth_token/oauth_token_secret)
 * @author @iamzzm iamzzm@gmail.com  http://www.iamzzm.net
 * @version 2010-8-26
 */
include './config.php';

$method = $_SERVER['REQUEST_METHOD'];
$request_api = strval ( substr ( $_SERVER['REQUEST_URI'], strlen ( substr ( $_SERVER["SCRIPT_NAME"], 0, - 10 ) ) ) );

$post_data = false;
if ($method == 'POST') {
	$post_str = @file_get_contents ( 'php://input' );
	parse_str ( $post_str, $post_data );
}
if (strpos ( $request_api, 'api/' ) === 1) //workaround for twhirl
	$request_api = substr ( $request_api, 4 );

$type = request_type ( $request_api );

if ($type == 'search')
	$url = TWITTER_SEARCH . $request_api;
else
	$url = TWITTER_URL . $request_api;

if ($type != 'oauth') {
	$result = process_curl ( $url, $post_data );
	if ($type == 'maybe') {
		$checkResult = json_decode ( $result );
		if (isset ( $checkResult->error ))
			$type = 'oauth';
	}
}

if ($type == 'oauth') {
	require './OAuth.php';
	if ($request_api === '/takeoAuth.json') {
		$url = TWITTER_URL . '/account/verify_credentials.json';
		$oauthStr = oauth_str ( $url, $post_data );
	} else {
		$url = TWITTER_URL . $request_api;
		if (isset ( $post_data['oauth_token'] ) && isset ( $post_data['oauth_token_secret'] )) {
			$oauthStr = $post_str;
		} else {
			if (preg_match ( '/\?(.*)/', $request_api, $get_key ) != 0) {
				parse_str ( $get_key[1], $get_data );
				if (isset ( $get_data['oauth_token'] ) && isset ( $get_data['oauth_token_secret'] ))
					$oauthStr = $get_key[1];
			}
			if (! isset ( $oauthStr ))
				$oauthStr = oauth_str ( $url, $post_data );
		}
	}
	user_oauth_sign ( $url, $oauthStr, $post_data );
	$result = process_curl ( $url, $post_data );
	
	if ($request_api === '/takeoAuth.json') { //Special For get oauth_token&&oauth_token_secret
		$checkResult = json_decode ( $result );
		if (! isset ( $checkResult->error )) {
			parse_str ( $oauthStr, $tokenF );
			$result = '{"oauth_token":"' . $tokenF['oauth_token'] . '","oauth_token_secret":"' . $tokenF['oauth_token_secret'] . '"}';
		}
	}
}
echo $result;
/**
 * Check the request type
 * @return string
 */
function request_type($request) {
	$matches = array (
		'search' => array (
			'search', 
			'trends' 
		), 
		'noauth' => array (
			'statuses/public_timeline', 
			'friendships/show', 
			'users/show' 
		), 
		'maybe' => array (
			'statuses/user_timeline', 
			'statuses/friends', 
			'statuses/followers', 
			'friends/ids', 
			'followers/ids' 
		) 
	);
	foreach ( $matches as $type => $match ) {
		foreach ( $match as $mat ) {
			if (strpos ( $request, $mat ) === 1)
				return $type;
		}
	}
	return 'oauth';
}
/**
 * Exe Curl Method
 * @return string
 */
function process_curl($url, $postargs = false) {
	$ch = curl_init ( $url );
	$curl_opt = array ();
	if ($postargs !== false) {
		$curl_opt[CURLOPT_POST] = true;
		$curl_opt[CURLOPT_POSTFIELDS] = $postargs;
	}
	$curl_opt[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
	$curl_opt[CURLOPT_RETURNTRANSFER] = true;
	$curl_opt[CURLOPT_HTTPHEADER]['expect'] = 'Expect:';
	curl_setopt_array ( $ch, $curl_opt );
	$result = curl_exec ( $ch );
	curl_close ( $ch );
	return $result;
}
/**
 * Do url and args by oAuth
 * @return &
 */
function user_oauth_sign(&$url, $oauthStr, &$args = false) {
	if (empty ( $oauthStr ))
		return;
	
	$method = $args !== false ? 'POST' : 'GET';
	
	if (preg_match_all ( '#[?&]([^=]+)=([^&]+)#', $url, $matches, PREG_SET_ORDER )) {
		foreach ( $matches as $match ) {
			$args[$match[1]] = $match[2];
		}
		$url = substr ( $url, 0, strpos ( $url, '?' ) );
	}
	$sig_method = new OAuthSignatureMethod_HMAC_SHA1 ();
	$consumer = new OAuthConsumer ( OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET );
	
	parse_str ( $oauthStr, $tokenF );
	
	$token = NULL;
	$token = new OAuthConsumer ( $tokenF['oauth_token'], $tokenF['oauth_token_secret'] );
	
	$request = OAuthRequest::from_consumer_and_token ( $consumer, $token, $method, $url, $args );
	$request->sign_request ( $sig_method, $consumer, $token );
	
	switch ($method) {
		case 'GET' :
			$url = $request->to_url ();
			$args = false;
			return;
		case 'POST' :
			$url = $request->get_normalized_http_url ();
			$args = $request->to_postdata ();
			return;
	}
}
/**
 * Get oauth string by username & password
 * @return string
 */
function oauth_str() {
	$chr = curl_init ( OAUTH_CALLBACK_URL );
	curl_setopt ( $chr, CURLOPT_POST, TRUE );
	curl_setopt ( $chr, CURLOPT_POSTFIELDS, user_pw () );
	curl_setopt ( $chr, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt ( $chr, CURLOPT_FOLLOWLOCATION, TRUE );
	$oauthStr = curl_exec ( $chr );
	curl_close ( $chr );
	return trim ( strip_tags ( $oauthStr ) );
}
/**
 * Parse username & password
 * @return string
 */
function user_pw() {
	if (! empty ( $_SERVER['PHP_AUTH_USER'] )) {
		return 'u=' . $_SERVER['PHP_AUTH_USER'] . '&p=' . $_SERVER['PHP_AUTH_PW'];
	} else if (! empty ( $_SERVER['HTTP_AUTHORIZATION'] ) || ! empty ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] )) {
		$auth = empty ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
		$a = base64_decode ( substr ( $auth, 6 ) );
		list ( $username, $password ) = explode ( ':', $a );
		return 'u=' . $username . '&p=' . $password;
	}
}
?>