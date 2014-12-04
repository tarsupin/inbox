<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// The user must be logged in
if(!Me::$loggedIn)
{
	exit;
}

$_POST['body'] = isset($_POST['body']) ? Security::purify($_POST['body']) : '';
echo html_entity_decode(nl2br(UniMarkup::parse($_POST['body'])));