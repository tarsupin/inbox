<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

// Provide custom login details
// $loginResponse is provided here, which includes Auth's auth_id if this site requires it
/*
function custom_login($loginResponse)
{
	
}
*/

// Run the universal login script
require(SYS_PATH . "/controller/login.php");

// Return Home
header("Location: /");