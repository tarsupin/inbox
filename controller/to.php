<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Require Login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Make sure you have a username designated
if(!isset($url[1]))
{
	header("Location: /"); exit;
}

// Attempt to retrieve the user
if(!$userData = User::getDataByHandle($url[1], "uni_id, handle"))
{
	User::silentRegister($url[1]);
	
	// Try again to retrieve the recently silent-registered user
	if(!$userData = User::getDataByHandle($url[1], "uni_id, handle"))
	{
		header("Location: /"); exit;
	}
	
	AppFolder::generateDefaultFolders((int) $userData['uni_id']);
}

// Get the appropriate folder ("General Inbox")
if(!$folderData = AppFolder::getByTitle(Me::$id, "General Inbox"))
{
	header("Location: /"); exit;
}

// Redirect to a new thread with designated individual
header("Location: /new-thread?folder=" . $folderData['folder_id'] . "&to=" . $userData['handle']);