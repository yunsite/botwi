<?php
/////////////////////////////////////////////////////////////////////////////
// BoTwi // index.php
/////////////////////////////////////////////////////////////////////////////
/**
 * Auto oAuth (OAUTH_CALLBACK_URL must point to this URL+'/oauth',Curl By ./t/index.php)
 * @author @iamzzm iamzzm@gmail.com  http://www.iamzzm.net
 * @version 2010-8-26
 */

if (! isset ( $_GET['q'] ))
	exit ( 0 );
if ($_GET['q'] != 'oauth')
	exit ( 0 );
if (! isset ( $_POST['u'] ) || ! isset ( $_POST['p'] ))
	exit ( 0 );

include './t/config.php';
require './t/OAuth.php';

$sig_method = new OAuthSignatureMethod_HMAC_SHA1 ();
$consumer = new OAuthConsumer ( OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET );

$token = NULL;
$postdata = array (
	'oauth_callback' => OAUTH_CALLBACK_URL 
);
$url = 'https://twitter.com/oauth/request_token';
$request = OAuthRequest::from_consumer_and_token ( $consumer, $token, 'POST', $url, $postdata );
$request->sign_request ( $sig_method, $consumer, $token );
parse_str ( p_curl ( $request->get_normalized_http_url (), $request->to_postdata () ), $token );

$str = p_curl ( 'https://twitter.com/oauth/authorize?oauth_token=' . $token['oauth_token'] );

preg_match ( '/name=\"authenticity_token\" type=\"hidden\" value=\"(.*?)\"/', $str, $matches );
$postdata = 'authenticity_token=' . $matches[1];
preg_match ( '/name=\"oauth_token\" type=\"hidden\" value=\"(.*?)\"/', $str, $matches );
$postdata .= '&oauth_token=' . $matches[1];
$postdata .= '&session[username_or_email]=' . $_POST['u'] . '&session[password]=' . $_POST['p'] . '&submit=Allow';
$url = 'https://twitter.com/oauth/authorize';
preg_match ( '/oauth_verifier=(.*?)\"/', p_curl ( $url, $postdata ), $matches );

$postdata = array (
	'oauth_verifier' => $matches[1] 
);
$url = 'https://twitter.com/oauth/access_token';
$token = new OAuthConsumer ( $token['oauth_token'], $token['oauth_token_secret'] );
$request = OAuthRequest::from_consumer_and_token ( $consumer, $token, 'POST', $url, $postdata );
$request->sign_request ( $sig_method, $consumer, $token );
echo p_curl ( $request->get_normalized_http_url (), $request->to_postdata () );

/**
 * Exe Curl Method
 * @return string
 */
function p_curl($url, $postdata = false) {
	$ch = curl_init ( $url );
	if ($postdata) {
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postdata );
	}
	curl_setopt ( $ch, CURLOPT_VERBOSE, 0 );
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	$result = curl_exec ( $ch );
	curl_close ( $ch );
	return $result;
}
?>