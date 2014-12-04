<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// The user must be logged in
if(!Me::$loggedIn)
{
	exit;
}

// Check if the thread is posted
if(!isset($_POST['threadID']) or !isset($_POST['postID']))
{
	exit;
}

// Get the current thread
if(!$thread = AppThread::get((int) $_POST['threadID']))
{
	exit;
}

// Make sure you have permission to post
if(Me::$clearance < 2)
{
	exit;
}

// Get post content
$post = array();

if(!$post = Database::selectOne("SELECT p.body, u.handle FROM posts p INNER JOIN users u ON u.uni_id=p.uni_id WHERE p.thread_id=? AND p.id=? LIMIT 1", array((int) $thread['id'], (int) $_POST['postID'])))
{
	exit;
}

// Sanitize the message
echo '[quote=' . $post['handle'] . ']' . Security::purify($post['body']) . '[/quote]';