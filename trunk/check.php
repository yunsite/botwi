<?php
/////////////////////////////////////////////////////////////////////////////
// BoTwi // check.php
/////////////////////////////////////////////////////////////////////////////
/**
 * Check if your host be able to run this proxy
 * @author @iamzzm iamzzm@gmail.com  http://www.iamzzm.net
 * @version 2010-8-26
 */
$ok = true;
echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Server Check</title></head><body>';
echo '<b>BoTwi</b><br />============<br />';

if (! function_exists ( 'file_get_contents' )) {
	$ok = false;
	echo 'The host doesn\'t support PHP5! 需要PHP5版本以上<br />';
}
if (! function_exists ( 'curl_init' )) {
	$ok = false;
	echo 'The host doesn\'t support curl! 需要支持curl<br />';
} else {
	$url = substr ( 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 0, - 9 );
	$ch = curl_init ( $url );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
	$result = curl_exec ( $ch );
	curl_close ( $ch );
	if (! empty ( $result )) {
		$ok = false;
		echo 'The host has AD! 需要去除广告<br />';
	}
	$ch = curl_init ( $url . 'isHTwork' );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
	$result = curl_exec ( $ch );
	$responseInfo = curl_getinfo ( $ch );
	curl_close ( $ch );
	$http_code = intval ( $responseInfo['http_code'] );
	if ($http_code != 200 && $http_code != 206) {
		$ok = false;
		echo 'The host doesn\'t support .htaccess! 需要支持.htaccess<br />';
	}
}
if ($ok)
	echo 'Well done. 服务器功能测试通过<br />';
echo '============<br />';
echo '<a href="http://code.google.com/p/botwi/">OpenSource</a><br />';
echo '============<br />';
echo '</body></html>';
?>