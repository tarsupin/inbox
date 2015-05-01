<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-------------------------------------
------ About the AppPost Class ------
-------------------------------------

This plugin provides handling for posts.


-------------------------------
------ Methods Available ------
-------------------------------


*/

abstract class AppPost {
	
	
/****** Create a new Post ******/
	public static function create
	(
		$threadID		// <int> The ID of the thread you're posting to.
	,	$uniID	 		// <int> The uniID of the user creating the post.
	,	$body			// <str> The post message.
	,	$aviID = 0		// <int> The avatar ID being used in this post.
	)					// RETURNS <int> ID of the post that was created, or 0 on failure.
	
	// $postID = AppPost::create($threadID, $uniID, $body, [$aviID]);
	{
		// Prepare Values
		$postID = (int) UniqueID::get("post");
		$timestamp = time();
		
		Database::startTransaction();
		
		// Create the post
		if(!Database::query("INSERT INTO posts (thread_id, id, uni_id, avi_id, body, date_post) VALUES (?, ?, ?, ?, ?, ?)", array($threadID, $postID, $uniID, $aviID, $body, $timestamp)))
		{
			Database::endTransaction(false);
			return 0;
		}
		
		// Update the thread values
		if(!Database::query("UPDATE threads SET last_poster_id=?, posts=posts+1, date_last_post=? WHERE id=? LIMIT 1", array($uniID, $timestamp, $threadID)))
		{
			Database::endTransaction(false);
			return 0;
		}
		
		// Get the list of users in this thread
		$threadUsers = AppThread::getUsers($threadID);
		
		// Prepare Values
		$sqlIn = "";
		$sqlArray = array($threadID, $uniID, $timestamp, $timestamp, 0, $threadID);
		
		foreach($threadUsers as $user)
		{
			$sqlIn .= ($sqlIn == "" ? "" : ", ") . "?";
			$sqlArray[] = $user['uni_id'];
		}
		
		// Update all "read" messages to unread
		$pass = Database::query("UPDATE threads_users tu INNER JOIN folders f ON f.uni_id=tu.uni_id INNER JOIN folders_threads ft ON ft.folder_id=f.folder_id AND ft.thread_id=? SET f.unread=f.unread+1, f.last_poster=?, f.date_lastPost=?, ft.date_last_post=?, ft.is_read=? WHERE tu.thread_id=? AND tu.uni_id IN (" . $sqlIn . ")", $sqlArray);
		
		// Add to the recent threads
		$thread = AppThread::get($threadID);
		$userData = User::get($uniID, "handle, role");

		$values = array();
		foreach($threadUsers as $user)
		{
			$values[] = '(' . $user['uni_id'] . ', ' . $timestamp . ', "' . $thread['title'] . '", ' . $thread['posts'] . ', "/thread?id=' . $threadID . '", ' . $postID . ', "' . $userData['handle'] . '", "' . $userData['role'] . '", "' . addslashes(substr(UniMarkup::strip($body), 0, 255)) . '")';
		}

		Database::query("INSERT INTO posts_recent (uni_id, date_posted, thread_title, thread_posts, post_link, post_id, poster_handle, role, body) VALUES " . implode(",", $values), array());
		
		if(!Database::endTransaction($pass))
		{
			return 0;
		}
		
		return $postID;
	}
	
	
/****** Edit a Post ******/
	public static function edit
	(
		$threadID		// <int> The ID of the thread the post is in.
	,	$postID			// <int> The ID of the post you're editing.
	,	$body			// <str> The new (edited) post message.
	,	$aviID			// <int> The ID of the avatar to post with.
	)					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppPost::edit($threadID, $postID, "Hey everyone! Edit: Oh yeah, check out my blog!");
	{
		return Database::query("UPDATE posts SET body=?, avi_id=? WHERE thread_id=? AND id=?", array($body, $aviID, $threadID, $postID));
	}



/****** Pull data from the recent posts list ******/
	public static function getRecentPosts (
		$uniID			// <int> The ID of the user.
	)					// RETURNS <int:[str:mixed]> a list of data pulled from recent posts, array() on failure.
	
	// $recentPosts = AppPost::getRecentPosts();
	{
		// Check if you should purge any recent posts from the list
		if(mt_rand(0, 200) == 22 or true)
		{
			if($delDate = (int) Database::selectValue("SELECT date_posted FROM posts_recent WHERE uni_id=? ORDER BY date_posted DESC LIMIT 6, 1", array($uniID)))
			{
				Database::query("DELETE FROM posts_recent WHERE uni_id=? AND date_posted <= ?", array($uniID, $delDate));
			}
		}
		
		return Database::selectMultiple("SELECT * FROM posts_recent WHERE uni_id=? ORDER BY date_posted DESC LIMIT 5", array($uniID));
	}
	
}