<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// The user must be logged in
if(!Me::$loggedIn)
{
	exit;
}

// Check if the thread is posted
if(!isset($_POST['threadID']) or !isset($_POST['participant']))
{
	exit;
}

$_POST['threadID'] = (int) $_POST['threadID'];

// Get Thread
$thread = AppThread::get($_POST['threadID']);

// Check permission
if($thread['owner_id'] != Me::$id)
{
	exit;
}

// check number of current participants
$threadUsers = AppThread::getUsers($_POST['threadID']);
if(count($threadUsers) >= 10)
{
	Alert::saveError("Too Many Users", "You can only add a maximum of ten users to a thread.");
	echo "error";
	exit;
}

$_POST['participant'] = Sanitize::variable($_POST['participant']);

// check that this user is not already participating
foreach($threadUsers as $u)
{
	if($u['handle'] == $_POST['participant'])
	{
		Alert::saveError($user . " Invalid", "@" . $_POST['participant'] . " is already a participant of this thread.");
		echo "error";
		exit;
	}
}

// find user
if(!$getList = Database::selectOne("SELECT uni_id, handle FROM users_handles WHERE handle=? LIMIT 1", array($_POST['participant'])))
{
	User::silentRegister($_POST['participant']);
	if(!$check = (int) Database::selectValue("SELECT uni_id FROM users_handles WHERE handle=? LIMIT 1", array($_POST['participant'])))
	{
		Alert::saveError($user . " Invalid", "The user @" . $_POST['participant'] . " was not located.");
		echo "error";
		exit;
	}
	$user = $check;
}
else
{
	$user = (int) $getList['uni_id'];
}

// add user
Database::startTransaction();
if($pass = Database::query("REPLACE INTO threads_users (thread_id, uni_id) VALUES (?, ?)", array($_POST['threadID'], $user)))
{
	if(!$folderData = AppFolder::getByTitle($user, "General Inbox"))
	{
		$pass = false;
	}
	else
	{
		$pass = Database::query("INSERT INTO folders_threads (folder_id, thread_id, date_last_post) VALUES (?, ?, ?)", array((int) $folderData['folder_id'], $_POST['threadID'], $thread['date_last_post']));
		AppFolder::updateDetails($user, (int) $folderData['folder_id']);
		Notifications::create($user, URL::inbox_unifaction_com() . "/thread?id=" . $_POST['threadID'], "You've been added to an inbox thread by @" . Me::$vals['handle'] . ".");
	}
}
Database::endTransaction($pass);
if($pass)
{
	echo "success";
}