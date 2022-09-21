<?php
if (!defined('PmWiki'))
	exit();
/*  Copyright 2016 David Gilbert.
This file is recaptcha.php; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

For installation and usage instructions refer to: http://pmwiki.com/wiki/Cookbook/ReCaptcha
*/
$RecipeInfo['ReCaptcha']['Version'] = '20160328'; //0.0.2
if ($VersionNum < 2001950)
	Abort("<h3>You are running PmWiki version {$Version}. In order to use ReCaptcha please update to 2.2.1 or later.</h3>");

// ----------------------------------------
// - User settings
SDVA($rc_Settings, array(
	'enabled' => 1,
	'sitekey' => '',  // public key
	'secret' => '',  // secret key
	'language' => 'en',  // https://developers.google.com/recaptcha/docs/language
	'curl' => 0,  // set to 1 if you receive "invalid-json" errors
	'script' => 'https://www.google.com/recaptcha/api.js'
	)
);
SDVA($rc_Settings['options'], array(
	'theme' => 'light',
	'type' => 'image',
	'size' => 'normal',
	'tabindex' => '0',
	'callback' => '',
	'expired-callback' => '',
	'sitekey' => $rc_Settings['sitekey']
));
XLSDV('en', array('missing-input-response'=>'Please verify you are not a robot.'));
SDVA($HTMLFooterFmt, array('recaptcha.js' => '<script src="'. $rc_Settings['script']. '?hl='. $rc_Settings['language']. '"></script>'));
Markup('recaptcha', 'directives', '/\(:recaptcha\s*(.*?):\)/i', "re_ReCaptcha_MU");  //make sure args pattern is lazy with ? otherwise picks up other markup on same line
array_unshift($EditFunctions, 'rc_RequireReCaptcha');
SDV($Conditions['recaptcha'], '(boolean)rc_IsReCaptcha()->isSuccess()');
require_once("$FarmD/cookbook/recaptcha/autoload.php");

//generate the recaptcha div with data arguments. called with (:recaptcha arg1=val1:)
function re_ReCaptcha_MU($args){
	function rc_ParseArgs(&$i, $k){ if($i>"") $i=" data-$k=\"$i\""; }
	global $rc_Settings;
	if(is_array($args)){
		$opt = ParseArgs($args[1]);
		unset($opt['#']);
		$rc_Settings['options'] = array_merge($rc_Settings['options'], $opt);
	}
	$p = $rc_Settings['options'];
	array_walk($p, 'rc_ParseArgs');
	return '<div class="g-recaptcha"'.implode("",$p).'></div>';
}

function rc_RequireReCaptcha($pagename, $page, $new) {
	global $rc_Settings,$MessagesFmt,$EnablePost;
	if (!IsEnabled($rc_Settings['enabled'], 0)) return;
	$rc = rc_IsReCaptcha();
	if ($rc->isSuccess())  return;
	foreach ($rc->getErrorCodes() as $code)
		$MessagesFmt[] = "<div class='wikimessage'>$[". $code. "]</div>";
	$EnablePost = 0;
}

//"success": true|false, "errorCodes": "missing-input"
function rc_IsReCaptcha() {
	global $rc_Settings;
	//curl request prevents "invalid-json" on some servers.
	if ($rc_Settings['curl'])
		$recaptcha = new \ReCaptcha\ReCaptcha($rc_Settings['secret'], new \ReCaptcha\RequestMethod\CurlPost());
	else
		$recaptcha = new \ReCaptcha\ReCaptcha($rc_Settings['secret']);
	return $recaptcha->verify(@$_POST["g-recaptcha-response"], $_SERVER["REMOTE_ADDR"]);
}

