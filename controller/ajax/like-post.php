<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// The user must be logged in
if(!Me::$loggedIn)
{
	exit;
}

// Check if the thread is posted
if(!isset($_POST['threadID']) or !isset($_POST['postID']))
{
	exit;
}

// Like the post
if(AppPost::like((int) $_POST['threadID'], (int) $_POST['postID'], Me::$id))
{
	echo json_encode(array("postID" => (int) $_POST['postID']));
}