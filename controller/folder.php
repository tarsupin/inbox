<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure the proper information was sent
if(!isset($_GET['id']))
{
	header("Location: /"); exit;
}

// Get the folder in question
$folder = AppFolder::get(Me::$id, (int) $_GET['id']);

// Recognize Integers
$folderID = (int) $folder['folder_id'];

// Ensure if you have proper permissions to access this folder
$clearance = (isset(Me::$clearance) ? Me::$clearance : 0);

// Prepare Values
$page = (isset($_GET['page']) ? (int) $_GET['page'] : 1);
$pageList = "";
$threadsToShow = 20;
$postsPerPage = 20;

if($page > 1)
{
	$pageList = '<a href="/folder?id=' . $folderID . '&page=' . ($page - 1) . '"><span class="icon-arrow-left"></span> Previous Page</a>';
}

$socialURL = URL::social_unifaction_com();

// Get Forum Threads
$threads = AppFolder::getThreads($folderID, $page, $threadsToShow);

if(count($threads) > $threadsToShow)
{
	$pageList = '<a href="/folder?id=' . $folderID . '&page=' . ($page + 1) . '">Next Page <span class="icon-arrow-right"></span></a>';
	array_pop($threads);
}

$config['pageTitle'] = $config['site-name'] . " > " . $folder['title'];

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

// Breadcrumbs
echo '
<div class="thread-tline"><a href="/">Home</a> &gt; ' . $folder['title'] . '</div>';

echo '
<div class="thread-tline">';
if(Me::$clearance >= 2)
{
	echo '
	<a href="/new-thread?folder=' . $folderID . '">New Thread</a>';
}
echo ($pageList ? '<div style="float:right;">' . $pageList . '</div>' : "") .'
</div>';

echo '
<div class="overwrap-box">
	<div class="overwrap-line">
		<div class="overwrap-name">Threads</div>
		<div class="overwrap-posts">Posts</div>
		<div class="overwrap-details">Details</div>
	</div>
	<div class="inner-box">';
	
// Cycle through threads
foreach($threads as $thread)
{
	// Draw Description
	$drawDesc = "";
	
	if($thread['posts'] > $postsPerPage)
	{
		$paginate = new Pagination((int) $thread['posts'], $postsPerPage, 1, "division");
		
		foreach($paginate->pages as $page)
		{
			$drawDesc .= '
				<a href="/thread?id=' . $thread['id'] . 'page=' . $page . '"><span>' . $page . '</span></a>';
		}
	}
	
	// Prepare New Icons
	$thread['id'] = (int) $thread['id'];
	
	// Display each thread
	echo '
	<div class="inner-line">
		<div class="inner-name">
			<a href="/thread?id=' . $thread['id'] . 'page=' . $page . '">' . (!$thread['is_read'] ? '<img src="' . CDN . '/images/new.png" /> ' :  '') . $thread['title'] . '</a> <a title="last post" href="/thread?id=' . $thread['id'] . '&page=last"><span class="icon-arrow-right"></span></a>
			<div class="inner-paginate">' . $drawDesc . '</div>
		</div>
		<div class="inner-posts">' . $thread['posts'] . '</div>
		<div class="inner-details"><a ' . ($thread['role'] != '' ? 'class="role-' . $thread['role'] . '" ' : '') . 'href="' . $socialURL . '/' . $thread['handle'] . '">@' . $thread['handle'] . '</a><br />' . Time::fuzzy((int) $thread['date_last_post']) . '</div>
	</div>';
}

echo '
	</div>
</div>';

echo '
<div class="thread-tline">';
if(Me::$clearance >= 2)
{
	echo '
	<a href="/new-thread?folder=' . $folderID . '">New Thread</a>';
}
echo ($pageList ? '<div style="float:right;">' . $pageList . '</div>' : "") .'
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
