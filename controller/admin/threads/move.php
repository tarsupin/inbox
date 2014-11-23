<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/admin/threads/move		{this is specific to forums}
	
	This page allows you to move threads.
*/

// Run Permissions
require(SYS_PATH . "/controller/includes/admin_perm.php");

// Run Header
require(SYS_PATH . "/controller/includes/admin_header.php");

// Only moderators or higher can use this function
if(Me::$clearance < 6)
{
	header("Location: /"); exit;
}

// Get Thread and Forum List
if(!$thread = Database::selectOne("SELECT id, forum_id, title FROM threads WHERE forum_id=? AND id=? LIMIT 1", array($_POST['forum'], $_POST['id'])))
{
	header("Location: /"); exit;
}

// Recognize Integers
$thread['id'] = (int) $thread['id'];
$thread['forum_id'] = (int) $thread['forum_id'];

// Submit Form
if(Form::submitted("move-thread"))
{
	if(!$forumData = Database::selectOne("SELECT id, title FROM forums WHERE id=? AND perm_read <= ? LIMIT 1", array($_POST['toForum'], Me::$clearance)))
	{
		Alert::error("Forum Inexistent", "That forum does not exist, or an error has occurred.");
	}
	else
	{
		// Recognize Integers
		$forumData['id'] = (int) $forumData['id'];
		
		if($thread['forum_id'] == $forumData['id'])
		{
			Alert::error("Thread Not Moved", "The thread is already in that forum.");
		}
	}
	
	if(FormValidate::pass())
	{
		if(AppFolderAdmin::moveThread($thread['forum_id'], $thread['id'], $forumData['id']))
		{
			Alert::saveSuccess("Thread Moved", 'You have successfully moved the thread to "' . $forumData['title'] . '"!');
			
			header("Location: /thread?forum=" . $forumData['id'] . '&id=' . $thread['id']); exit;
		}
	}
}

// Get the Forum List
if(!$forums = Database::selectMultiple("SELECT f.id, f.title, c.title as catTitle FROM forums f INNER JOIN forum_categories c ON c.id=f.category_id WHERE f.perm_read <= ? ORDER BY c.title, f.title ASC", array(Me::$clearance)))
{
	header("Location: /"); exit;
}

// Prepare Dropdown List
$forumList = array();

foreach($forums as $forum)
{
	$forumList[$forum['catTitle']][] = array("id" => (int) $forum['id'], "title" => $forum['title']);
}

echo '
<form class="uniform" action="/admin/threads/move?forum=' . $thread['forum_id'] . '&id=' . $thread['id'] . '" method="post">' . Form::prepare("move-thread") . '

<p>Moving the Thread: ' . $thread['title'] . '</p>

<p>To Forum:
<select name="toForum">';

foreach($forumList as $category => $forums)
{
	echo '
	<option value="" disabled style="font-weight:bold;">' . $category . '</option>';
	
	foreach($forums as $forum)
	{
		echo '
		<option value="' . $forum['id'] . '"' . ($thread['forum_id'] == $forum['id'] ? ' selected' : '') . '> &bull; ' . $forum['title'] . '</option>';
	}
}

echo '
</select>
</p>

<p><input type="submit" name="submit" value="Move Thread" /></p>
</form>';

// Display the Footer
require(SYS_PATH . "/controller/includes/admin_footer.php");
