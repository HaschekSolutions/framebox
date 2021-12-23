<?php

// found on https://html-online.com/articles/php-get-ip-cloudflare-proxy/
function getUserIP() {
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
			  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			  $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}
	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];

	if(filter_var($client, FILTER_VALIDATE_IP)) { $ip = $client; }
	elseif(filter_var($forward, FILTER_VALIDATE_IP)) { $ip = $forward; }
	else { $ip = $remote; }

	return $ip;
}


// from https://stackoverflow.com/a/834355/1174516
function startsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    return substr( $haystack, 0, $length ) === $needle;
}
function endsWith( $haystack, $needle ) {
   $length = strlen( $needle );
   if( !$length ) {
       return true;
   }
   return substr( $haystack, -$length ) === $needle;
}

function addToLog($text,$module=false)
{
    $fp = fopen(ROOT.DS.'..'.DS.'log'.DS.'page.log','a');
    fwrite($fp,'['.date("y.m.d H:i").']'.($module?"[$module]\t":"\t").$text.PHP_EOL);
    fclose($fp);
}

function translate($what)
{
    return ($GLOBALS['translation'][$what]?:$what);
}