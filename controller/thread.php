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
if(AppThread::markAsRead(Me::$id, $threadID))
{
	AppFolder::updateDetails(Me::$id, (int) $folderData['folder_id']);
}

// Prepare Values
$postsPerPage = 20;
$highestPage = ceil($thread['posts'] / $postsPerPage);
$isMod = (Me::$clearance >= 6 ? true : false);
$social = URL::unifaction_social();

$script = '';
if(isset($_GET['page']) && $_GET['page'] == 'last')
{
	$script = '
if (window.location.href.indexOf("page=last") >= 0)
{
	var posts = document.getElementsByClassName("post-anchor");
	var target = posts[posts.length-1];
	target.scrollIntoView(true);
}';
}
$_GET['page'] = (isset($_GET['page']) ? ($_GET['page'] == 'last' ? (int) $highestPage : (int) $_GET['page']) : 1);
$pageLine = '';

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
			Alert::saveSuccess("Thread Deleted", "You have deleted the thread.");
			header("Location: /folder?id=" . $folderData['folder_id']); exit;
		}
	}
	
	// Mark a Thread as Unread
	else if($_GET['action'] == "unreadThread")
	{
		if(AppThread::markAsUnread(Me::$id, $threadID))
		{
			AppFolder::updateDetails(Me::$id, (int) $folderData['folder_id']);
			header("Location: /folder?id=" . $folderData['folder_id']); exit;
		}
	}
}

// Get the posts
$posts = AppThread::getPosts($threadID, $_GET['page'], $postsPerPage);

// Get all users listed
$avatarName = array();

foreach($posts as $post)
{
	$uniID = (int) $post['uni_id'];
	$aviID = (int) $post['avi_id'];
	
	if(!isset($avatarName[$uniID][$aviID]))
	{
		$avatarName[$uniID][$aviID] = "";
	}
	if($aviID > 0)
	{
		$avatarName[$uniID][$aviID] = AppThread::getName($uniID, $aviID);
	}
}

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

$config['pageTitle'] = $config['site-name'] . " > " . $folderData['title'] . " > " . $thread['title'];

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
	$comma = "";
	if(Me::$clearance >= 2)
	{
		echo '
	<a href="/post?id=' . $threadID . '">Reply</a>';
		$comma = " | ";
	}
	
	if($isMod || $thread['owner_id'] == Me::$id)
	{
		echo '
	' . $comma . '<a href="javascript:changeTitle(' . $thread['id'] . ');">Change Title</a>
	 | <a href="javascript:addParticipant(' . $thread['id'] . ');">Add Participant</a>';
		$comma = " | ";
	}
	
	// Display Moderator Options
	echo '
	' . $comma . '<select id="mod-tool-dropdown" name="mod-dropdown">
		<option value="">-- Thread Tools --</option>
		<option value="moveThread">Move Thread</option>
		<option value="deleteThread">Delete Thread</option>
		<option value="unreadThread">Mark Thread as Unread</option>
	</select>
	
	<script>
		var modDrop = document.getElementById("mod-tool-dropdown");
		
		modDrop.onchange = function()
		{
			var confirmTool = window.confirm("Are you sure you want to " + modDrop[modDrop.selectedIndex].text + "?");
			
			if(confirmTool)
			{
				var modList = ["deleteThread", "moveThread", "unreadThread"];
				
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
	$userList[$tUsr['uni_id']] = $tUsr;
	echo '
	<div class="participant"><a href="' . $social . '/' . $tUsr['handle'] . '"><img src="' . ProfilePic::image((int) $tUsr['uni_id'], "large") . '" /></a><br /><a ' . ($tUsr['role'] != '' ? 'class="role-' . $tUsr['role'] . '" ' : '') . 'href="' . $social . '/' . $tUsr['handle'] . '">@' . $tUsr['handle'] . '</a></div>';
}
	
echo '
</div>';

foreach($posts as $post)
{
	// Prepare Values
	$uniID = (int) $post['uni_id'];
	$aviID = (int) $post['avi_id'];
	
	// fallback if the participant no longer has this PM in their inbox
	if(!isset($userList[$uniID]))
		$userList[$uniID] = User::get($uniID, "uni_id, handle, display_name, role");
	
	
	// Prepare the differences between AVATAR and PROFILE sites
	if($aviID)
	{
		$img = Avatar::image($uniID, $aviID);
	}
	else
	{
		$img = ProfilePic::image($uniID, "large");
	}
	
	// Anchor needs to be offset by the height of the fixed header
	echo '
	<span class="post-anchor" id="p' . $post['id'] . '" style="display:block; position:relative; top:-60px; height:0px;"></span>';
	
	// Display the Post
	echo '
	<div class="thread-post">
		<div class="post-left">
			<div><a href="' . $social . '/' . $post['handle'] . '"><img class="post-img" src="' . $img . '" /></a></div>
			<div class="post-status">
				<div class="post-status-top">' . (lcfirst($userList[$uniID]['display_name']) != lcfirst($userList[$uniID]['handle']) ? $userList[$uniID]['display_name'] . ' ' : '') . '<a ' . ($userList[$uniID]['role'] != '' ? 'class="role-' . $userList[$uniID]['role'] . '" ' : '') . 'href="' . $social . '/' . $userList[$uniID]['handle'] . '">@' . $userList[$uniID]['handle'] . '</a>' . (!in_array($avatarName[$uniID][$aviID], array('', $userList[$uniID]['display_name'], lcfirst($userList[$uniID]['display_name']))) ? ' (' . $avatarName[$uniID][$aviID] . ')' : '') . '</div>
				<div class="post-status-bottom">					
					<div><a href="/thread?id=' . $threadID . '&page=' . $_GET['page'] . '#p' . $post['id'] . '"><span class="icon-link"></span></a> <span title="' . date("M j, Y g:ia", $post['date_post']) . '" onclick="this.nextSibling.style.display=(this.nextSibling.style.display == \'none\' ? \'block\' : \'none\');">Posted ' . Time::fuzzy((int) $post['date_post']) . '</span><span style="display:none;">' . date("M j, Y g:ia", $post['date_post']) . '</span></div>
					<div style="margin-top:6px;"><span class="icon-clock"></span> Joined ' . date("M j, Y g:ia", $post['date_joined']) . '</div>
				</div>
			</div>
		</div>
		<div class="post-right">
			<div class="post-options">';
			
			// Delete Option
			if(Me::$id == $uniID || $isMod)
			{
				echo '
				<a href="/thread?id=' . $threadID . '&pID=' . $post['id'] . '&action=deletePost"><img src="' . CDN . '/images/forum/delete.png" onclick="return confirm(\'Are you sure you want to delete this post?\');"/></a>';
			}
			
			// Edit Option
			if(Me::$clearance >= 2 && (Me::$id == $uniID || $isMod))
			{
				echo '
				<a href="/post?id=' . $threadID . '&edit=' . $post['id'] . '"><img src="' . CDN . '/images/forum/edit.png" /></a>';
			}
			
			// Quote Option
			if(Me::$clearance >= 2)
			{
				echo '
				<a href="javascript:quotePost(' . $threadID . ', ' . $post['id'] . ');"><img src="' . CDN . '/images/forum/quote.png" /></a>';
			}
			
			echo '
			</div>
			' . html_entity_decode(nl2br(UniMarkup::parse($post['body'])));
			
			if($post['signature'])
			{
				echo '<div class="thread-signature">' . nl2br($post['signature']) . '</div>';
			}
			
		echo '
			<div class="post-status-mobile">' . (lcfirst($userList[$uniID]['display_name']) != lcfirst($userList[$uniID]['handle']) ? $userList[$uniID]['display_name'] . ' ' : '') . '<a ' . ($userList[$uniID]['role'] != '' ? 'class="role-' . $userList[$uniID]['role'] . '" ' : '') . 'href="' . $social . '/' . $userList[$uniID]['handle'] . '">@' . $userList[$uniID]['handle'] . '</a>' . (!in_array($avatarName[$uniID][$aviID], array('', $userList[$uniID]['display_name'], lcfirst($userList[$uniID]['display_name']))) ? ' (' . $avatarName[$uniID][$aviID] . ')' : '') . '
			<br/>' . Time::fuzzy((int) $post['date_post']) . ' (' . date("M j, Y g:ia", $post['date_post']) . ')</div>
		</div>
	</div>
	<div style="clear:both;"></div>';
}

// Breadcrumbs
echo '
<div class="thread-tline"><a href="/">Home</a> &gt; <a href="/folder?id=' . $folderData['folder_id'] . '">' . $folderData['title'] . '</a> &gt; ' . $thread['title'] . $pageLine . '</div>';

// Quick Reply Box
if(Me::$loggedIn && Me::$clearance >= 2)
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
				<div style="margin-top:10px;"><input type="button" value="Preview" onclick="previewPost();"/> <input type="submit" name="submit" value="Send Message" /></div>
				<div id="preview" class="thread-post" style="display:none; padding:4px; margin-top:10px;"></div>
			</form>
		</div>
	</div>';
}

echo '
</div>
<script>
function previewPost()
{
	var text = encodeURIComponent(document.getElementById("core_text_box").value);
	getAjax("", "preview-post", "parse", "body=" + text);
}
function parse(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("preview").style.display = "block";
	document.getElementById("preview").innerHTML = response;
}
function quotePost(thread, post)
{
	getAjax("", "quote-post", "parseadd", "threadID=" + thread, "postID=" + post);
}
function parseadd(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("core_text_box").value += response + "\n\n";
}

function changeTitle(thread)
{
	var new_title = prompt("New Thread Title:", "' . $thread['title'] . '");
	if(new_title)
	{
		getAjax("", "rename-thread", "renameActivated", "threadID=" + thread, "title=" + new_title);
	}
}
function renameActivated(response)
{
	if(!response) { return; }
	if(response == "") { return; }
	
	window.location="/thread?id=' . $threadID . '";
}

function addParticipant(thread)
{
	var new_participant = prompt("New Participant:", "");
	if(new_participant)
	{
		getAjax("", "add-participant", "addActivated", "threadID=" + thread, "participant=" + new_participant);
	}
}
function addActivated(response)
{
	if(!response) { return; }
	if(response == "") { return; }

	window.location="/thread?id=' . $threadID . '";
}
' . $script . '
</script>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");