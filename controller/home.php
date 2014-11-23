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

// Display the Recent Post Modules
if($recentPosts = AppPost::getRecentPosts())
{
	// Prepare Values
	$hourAgo = time() - 3600;
	
	// Begin the HTML
	echo '
	<div class="overwrap-box overwrap-module">
		<div class="overwrap-line">
			<div class="overwrap-name" style="font-size:1.0em;">Recent Posts</div>
		</div>
		<div class="inner-box">';
	
	foreach($recentPosts as $post)
	{
		// Prepare Values
		$drawDesc = "";
		
		if(strlen($post['body']) >= 255)
		{
			$post['body'] .= '...';
		}
		
		// Prepare Pagination
		if($post['thread_posts'] > 20)
		{
			$paginate = new Pagination((int) $post['thread_posts'], 20, 1, "division");
			
			foreach($paginate->pages as $page)
			{
				$drawDesc .= '
					<a href="' . $post['post_link'] . '?page=' . $page . '"><span>' . $page . '</span></a>';
			}
		}
		
		// Draw a recent post line
		echo '
			<div class="inner-line">
				<div class="inner-name">
					<a href="' . $post['post_link'] . '">' . $post['thread_title'] . '</a>
					<div class="inner-desc">' . $post['body'] . '</div>
					<div class="inner-paginate">' . $drawDesc . '</div>
				</div>
				<div class="inner-details"><a href="' . URL::unifaction_social() . '/' . $post['poster_handle'] . '">@' . $post['poster_handle'] . '</a>' . (($post['date_posted'] > $hourAgo) ? ' - ' . Time::fuzzy((int) $post['date_posted']) : '') . '<div style="margin-top:6px;">' . $post['thread_posts'] . ' Posts, ' . $post['thread_views'] . ' Views</div></div>
			</div>';
	}
	
	echo '
		</div>
	</div>';
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
