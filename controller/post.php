<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you have a valid ID for this forum and thread
if(!isset($_GET['id']))
{
	header("Location: /"); exit;
}

// Require Login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/post?id=" . ($_GET['id'] + 0));
}

if(!$thread = AppThread::get((int) $_GET['id']))
{
	header("Location: /"); exit;
}

// Prepare Values
$threadID = (int) $thread['id'];
$folderData = AppThread::getFolderData(Me::$id, $threadID);

// Make sure the user is allowed access
if(!AppThread::allowedAccess(Me::$id, $threadID))
{
	header("Location: /"); exit;
}

// Check Edit Mode & Post if applicable
$post = array();

if($editMode = (isset($_GET['edit']) ? true : false))
{
	if(!$post = Database::selectOne("SELECT p.id, p.uni_id, p.avi_id, p.body, p.date_post, u.handle, u.display_name FROM posts p INNER JOIN users u ON u.uni_id=p.uni_id WHERE p.thread_id=? AND p.id=? LIMIT 1", array($threadID, $_GET['edit'])))
	{
		header('Location: /thread?id=' . $threadID . '&page=last'); exit;
	}
	
	// Recognize Integers
	$post['id'] = (int) $post['id'];
	$post['uni_id'] = (int) $post['uni_id'];
	$post['avi_id'] = (int) $post['avi_id'];
	$post['date_post'] = (int) $post['date_post'];
	
	// Prepare Values
	$myPost = (Me::$id == $post['uni_id']);
	
	// If the user isn't you, or a moderator
	if(!$myPost && Me::$clearance < 6)
	{
		Alert::saveError("No Permissions", "You do not have permission to edit this post.");
		
		header('Location: /thread?id=' . $threadID . '&page=last'); exit;
	}
}

// Sanitize the message
$_POST['body'] = isset($_POST['body']) ? Security::purify($_POST['body']) : '';

if(!$_POST['body'] and isset($post['body']))
{
	$_POST['body'] = $post['body'];
}

// Create the post
if(Form::submitted(SITE_HANDLE . 'post-thrd'))
{
	FormValidate::text("Message", $_POST['body'], 1, 32000);
	
	if(FormValidate::pass())
	{
		// If we're editing this post, run the edit script
		if($editMode)
		{
			if(AppPost::edit($threadID, $post['id'], $_POST['body']))
			{
				Alert::saveSuccess("Post Edited", 'The post has been successfully modified.');
				
				header('Location: /thread?id=' . $threadID . '&page=last'); exit;
			}
		}
		
		// Standard Post Mode
		else if($postID = AppPost::create($threadID, Me::$id, $_POST['body']))
		{
			// Set this post as having been read by the poster
			AppThread::markAsRead(Me::$id, $threadID);
			
			// Get the list of users that this affects
			$uniIDList = AppThread::getUniIDList($threadID);
			
			// Remove yourself from the UniIDList
			if(($key = array_search(Me::$id, $uniIDList)) !== false)
			{
				unset($uniIDList[$key]);
			}
			
			// Send notifications to everyone in the UniIDList
			Notifications::createMultiple($uniIDList, URL::inbox_unifaction_com() . "/thread?id=" . $threadID, 'Your inbox message, "' . $thread['title'] . '", has been updated.');
			
			Alert::saveSuccess("Post Successful", 'You have successfully posted to the thread.');
			
			header('Location: /thread?id=' . $threadID . '&page=last'); exit;
		}
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

// Breadcrumbs
echo '
<div class="thread-tline"><a href="/">Home</a> &gt; <a href="/folder?id=' . $folderData['folder_id'] . '">' . $folderData['title'] . '</a> &gt; <a href="/thread?id=' . $threadID . '">' . $thread['title'] . '</a> &gt; Reply</div>';

// Post Box
echo '
<div class="overwrap-box">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">' . ($editMode ? 'Edit Post by ' . $post['display_name'] . ' (@' . $post['handle'] . ')' : 'Reply To Thread') . '</div>
	</div>
	' . UniMarkup::buttonLine() . '
	<div style="padding:6px;">
		<form class="uniform" action="/post?&id=' . $threadID . ($editMode ? "&edit=" . $_GET['edit'] : "") . '" method="post" style="padding-right:20px;">' . Form::prepare(SITE_HANDLE . 'post-thrd') . '
			<textarea id="core_text_box" name="body" placeholder="Enter your message here . . ." style="resize:vertical; width:100%; height:300px;" tabindex="10" autofocus>' . $_POST['body'] . '</textarea>
			<div style="margin-top:10px;"><input type="submit" name="submit" value="Post to Thread" /></div>
		</form>
	</div>
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
