<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Require a login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Gather all folders
if(!$folders = AppFolder::getList(Me::$id))
{
	// Create the default folders for the user
	AppFolder::generateDefaultFolders(Me::$id);
	
	if(!$folders = AppFolder::getList(Me::$id))
	{
		Alert::error("Generation Error", "There was an error setting up your inbox. Please try again later.");
	}
}

// Run Global Script
require(CONF_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display();

// Display the inbox folders
echo '
<div class="overwrap-box">
	<div class="overwrap-line">
		<div class="overwrap-name">My Inbox</div>
		<div class="overwrap-details"><span class="hide-800"> &nbsp; &nbsp;</span> Details</div>
	</div>
	<div class="inner-box">';

foreach($folders as $folder)
{
	// Display the Folder Line
	AppFolder::displayLine($folder);
}

echo '
	</div>
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
