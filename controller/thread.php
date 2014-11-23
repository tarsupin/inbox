<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure that the appropriate information was sent
if(!isset($_GET['id']) or !Me::$loggedIn)
{
	header("Location: /"); exit;
}

// Pull the thread data
if(!$thread = AppThread::get((int) $_GET['id']))
{
	header("Location: /"); exit;
}

// Prepare Values
$threadID = (int) $thread['id'];
$thread['posts'] = (int) $thread['posts'];
$folderData = AppThread::getFolderData(Me::$id, $threadID);

// Make sure the user is allowed access
if(!AppThread::allowedAccess(Me::$id, $threadID))
{
	header("Location: /"); exit;
}

// Mark the thread as read
AppThread::markAsRead(Me::$id, $threadID);

// Prepare Values
$postsPerPage = 20;
$highestPage = ceil($thread['posts'] / $postsPerPage);
$isMod = (Me::$clearance >= 6 ? true : false);
$socialCom = URL::unifaction_social();

$_GET['page'] = (isset($_GET['page']) ? ($_GET['page'] == 'last' ? (int) $highestPage : (int) $_GET['page']) : 1);
$pageLine = '';
$subData = array();

// Run Actions
if(isset($_GET['action']))
{
	// Move the Thread
	if($_GET['action'] == "moveThread")
	{
		header("Location: /tools/moveThread?id=" . $threadID); exit;
	}
	
	// Delete a Post
	else if($_GET['action'] == "deletePost" && isset($_GET['pID']))
	{
		if(AppFolderAdmin::deletePost(Me::$id, $threadID, (int) $_GET['pID']))
		{
			Alert::success("Post Deleted", "You have deleted a post.");
		}
	}
	
	// Delete a Thread
	else if($_GET['action'] == "deleteThread")
	{
		if(AppFolderAdmin::deleteThread(Me::$id, $threadID))
		{
			Alert::success("Thread Deleted", "You have deleted a post.");
		}
	}
}

// Get the posts
$posts = AppThread::getPosts($threadID, $_GET['page'], $postsPerPage);

// Get Pagination Values
$paginate = new Pagination($thread['posts'], $postsPerPage, $_GET['page'], "division");

if($paginate->highestPage > 1)
{
	$pageLine = '
	<div style="float:right;">Page: ';
	
	foreach($paginate->pages as $page)
	{
		$pageLine .= '
		<a class="thread-page' . ($page == $_GET['page'] ? ' thread-page-active' : '') . '" href="/thread?id=' . $threadID . '&page=' . $page . '">' . $page . '</a>';
	}
	
	$pageLine .= '
	</div>';
}

// Get the list of users
$threadUsers = AppThread::getUsers($threadID);

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
<div class="thread-tline"><a href="/">Home</a> &gt; <a href="/folder?id=' . $folderData['folder_id'] . '">' . $folderData['title'] . '</a> &gt; ' . $thread['title'] . '</div>';

echo '
<div class="thread-tline">' . $pageLine;

if(Me::$loggedIn)
{
	echo '
	<a href="/post?id=' . $threadID . '">Reply</a>';
	
	// Display Moderator Options
	echo '
	<select id="mod-tool-dropdown" name="mod-dropdown">
		<option value="">-- Thread Tools --</option>
		<option value="moveThread">Move Thread</option>
		<option value="deleteThread">Delete Thread</option>
	</select>
	
	<script>
		var modDrop = document.getElementById("mod-tool-dropdown");
		
		modDrop.onchange = function()
		{
			var confirmTool = window.confirm("Are you sure you want to " + modDrop[modDrop.selectedIndex].text + "?");
			
			if(confirmTool)
			{
				var modList = ["deleteThread", "moveThread"];
				
				if(modList.indexOf(modDrop.value) > -1)
				{
					window.location="/thread?id=' . $threadID . '&action=" + modDrop.value;
				}
			}
		}
	</script>';
}

echo '
</div>';

// Display the thread participants
echo '
<style>
.participant { float:left; padding:6px; text-align:center; background-color:#E5F4FD; border-radius:6px; margin-right:6px; }
</style>

<div class="overwrap-box" style="overflow:hidden;">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">Thread Participants</div>
	</div>';
	
foreach($threadUsers as $tUsr)
{
	echo '
	<div class="participant"><a href="' . $socialCom . '/' . $tUsr['handle'] . '"><img src="' . ProfilePic::image($tUsr['uni_id'], "large") . '" /></a><br /><a href="/' . $tUsr['handle'] . '">@' . $tUsr['handle'] . '</a></div>';
}
	
echo '
</div>';

// Prepare Values
$social = URL::unifaction_social();

foreach($posts as $post)
{
	// Prepare Values
	$uniID = (int) $post['uni_id'];
	$aviID = (int) $post['avi_id'];
	
	// Prepare the differences between AVATAR and PROFILE sites
	if($aviID)
	{
		$img = Avatar::image($uniID, $aviID);
	}
	else
	{
		$img = ProfilePic::image($uniID, "large");
	}
	
	// Display the Post
	echo '
	<div class="thread-post">
		<div class="post-left' . ($aviID ? "-avatar" : "") . '">
			<div><a href="' . $social . '/' . $post['handle'] . '"><img class="post-img' . ($aviID ? "-avatar" : "") . '" src="' . $img . '" /></a></div>
			<div class="post-status">
				<div class="post-status-top">@<a href="' . $social . '/' . $post['handle'] . '">' . $post['handle'] . '</a></div>
				<div class="post-status-bottom">
					<div>Posted ' . Time::fuzzy((int) $post['date_post']) . '</div>
					<div style="margin-top:6px;"><span class="icon-clock"></span> Joined ' . Time::fuzzy((int) $post['date_joined']) . '</div>
				</div>
			</div>
		</div>
		<div class="post-right' . ($aviID ? "-avatar" : "") . '">
			<div class="post-options"><div class="show-800"><a href="' . $social . '/' . $post['handle'] . '">' . $post['display_name'] . '</a> <a href="' . $social . '/' . $post['handle'] . '">@' . $post['handle'] . '</a></div>';
			
			// Delete Option
			if(Me::$id == $uniID || $isMod)
			{
				echo '
				<a href="/thread?id=' . $threadID . '&pID=' . $post['id'] . '&action=deletePost"><img src="' . CDN . '/images/forum/delete.png" /></a>';
			}
			
			// Edit Option
			if(Me::$id == $uniID || $isMod)
			{
				echo '
				<a href="/post?id=' . $threadID . '&edit=' . $post['id'] . '"><img src="' . CDN . '/images/forum/edit.png" /></a>';
			}
			
			echo '
			</div>
			' . nl2br(UniMarkup::parse($post['body']));
			
			if($post['signature'])
			{
				echo '<div class="thread-signature">' . nl2br($post['signature']) . '</div>';
			}
			
		echo '
		</div>
	</div>
	<div style="clear:both;"></div>';
}

// Breadcrumbs
echo '
<div class="thread-tline"><a href="/">Home</a> &gt; <a href="/folder?id=' . $folderData['folder_id'] . '">' . $folderData['title'] . '</a> &gt; ' . $thread['title'] . $pageLine . '</div>';

// Quick Reply Box
if(Me::$loggedIn)
{
	echo '
	<div class="overwrap-box">
		<div class="overwrap-line" style="margin-bottom:10px;">
			<div class="overwrap-name">Quick Reply</div>
		</div>
		' . UniMarkup::buttonLine() . '
		<div style="padding:6px;">
			<form class="uniform" action="/post?id=' . $threadID . '" method="post" style="padding-right:20px;">' . Form::prepare(SITE_HANDLE . 'post-thrd') . '
				<textarea id="core_text_box" name="body" placeholder="Enter your message here . . ." style="resize:vertical; width:100%; height:300px;"></textarea>
				<div style="margin-top:10px;"><input type="submit" name="submit" value="Post to Thread" /></div>
			</form>
		</div>
	</div>';
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
