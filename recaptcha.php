<?php
if (!defined('PmWiki'))
	exit();
/*  Copyright 2016 David Gilbert.
This file is recaptcha.php; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

For installation and usage instructions refer to: http://pmwiki.com/wiki/Cookbook/ReCaptcha

Also ref: https://github.com/google/recaptcha/tree/1.0.0

*/
$RecipeInfo['ReCaptcha']['Version'] = '20160328'; //0.0.1
if ($VersionNum < 2001950)
	Abort("<h3>You are running PmWiki version {$Version}. In order to use ReCaptcha please update to 2.2.1 or later.</h3>");

// ----------------------------------------
// - User settings
SDVA($rc_Settings, array(
	'enabled' => 1,
	'secret' => '',  //secret key
	'language' => 'en',  //https://developers.google.com/recaptcha/docs/language
	'script' => 'https://www.google.com/recaptcha/api.js'
	)
);
SDVA($rc_Settings['options'], array(
	"sitekey" => '',  //public key
	"theme" => "light",
	"type" => "image",
	"size" => "normal",
	"tabindex" => "0",
	"callback" => "",
	"expired-callback" => ""
	)
);
XLSDV('en', array('missing-input'=>'Please verify you are not a robot.'));
SDVA($HTMLFooterFmt, array('recaptcha.js' => '<script src="'. $rc_Settings['script']. '?hl='. $rc_Settings['language']. '"></script>'));
Markup('recaptcha', 'directives', '/\(:recaptcha\s*(.*):\)/i', "re_ReCaptcha_MU");
array_unshift($EditFunctions, 'rc_RequireReCaptcha');
SDV($Conditions['recaptcha'], '(boolean)rc_IsReCaptcha()->success');
require_once("$FarmD/cookbook/recaptcha/recaptchalib.php");

//generate the recaptcha div with data arguments. called with (:recaptcha arg1=val1:)
function re_ReCaptcha_MU($args){
	global $rc_Settings;
	if(is_array($args)){
		$opt = ParseArgs($args[1]);
		unset($opt['#']);
		$rc_Settings['options'] = array_merge($rc_Settings['options'], $opt);
	}
	$p = $rc_Settings['options'];
	array_walk($p, create_function('&$i,$k','if($i>"") $i=" data-$k=\"$i\"";'));
	return '<div class="g-recaptcha"'.implode($p,"").'></div>';
}

function rc_RequireReCaptcha($pagename, $page, $new) {
	global $rc_Settings,$MessagesFmt,$EnablePost;
	if (!IsEnabled($rc_Settings['enabled'], 0)) return;
	$rc = rc_IsReCaptcha();
	if ($rc->success)  return;
	$MessagesFmt[] = "<div class='wikimessage'>$[". $rc->errorCodes. "]</div>";
	$EnablePost = 0;
}

//"success": true|false, "errorCodes": "missing-input"
function rc_IsReCaptcha() {
	global $rc_Settings;
	$re_ReCaptcha = new ReCaptcha($rc_Settings['secret']);
	return $re_ReCaptcha->verifyResponse($_SERVER["REMOTE_ADDR"], @$_POST["g-recaptcha-response"]);
}

