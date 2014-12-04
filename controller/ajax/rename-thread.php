<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// The user must be logged in
if(!Me::$loggedIn)
{
	exit;
}

// Check if the thread is posted
if(!isset($_POST['threadID']))
{
	exit;
}

$_POST['threadID'] = (int) $_POST['threadID'];

// Get Thread
$thread = AppThread::get($_POST['threadID']);

// Check permission
if($thread['owner_id'] != Me::$id)
{
	exit;
}

$_POST['title'] = Sanitize::safeword($_POST['title'], "'/\"!?@#$%^&*()[]+={}");

if(AppThread::edit($_POST['threadID'], $_POST['title']))
{
	echo Sanitize::variable(str_replace(" ", "-", strtolower($_POST['title'])), "-");
}