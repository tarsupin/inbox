<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Get the active hashtag of the page
$activeHashtag = isset($_POST['activeHashtag']) ? Sanitize::variable($_POST['activeHashtag']) : '';


// Display a Chat Widget
if($activeHashtag)
{
	$chatWidget = new ChatWidget($activeHashtag);
	echo $chatWidget->get();
}

// Dynamic Content Loader
echo '
<!-- Content gets dynamically shifted to this section -->
<div id="dynamic-content-loader"></div>';

// Load the Featured Widget for the main page
if(isset($_SERVER['HTTP_REFERER']))
{
	$parseURL = URL::parse($_SERVER['HTTP_REFERER']);
	
	if($parseURL['urlSegments'][0] == "")
	{
		// Prepare the Featured Widget Data
		$categories = array("articles", "people", "communities");
		
		// Create a new featured content widget
		$featuredWidget = new FeaturedWidget($activeHashtag, $categories);
		
		// If you want to display the FeaturedWidget by itself:
		echo $featuredWidget->get();
	}
}