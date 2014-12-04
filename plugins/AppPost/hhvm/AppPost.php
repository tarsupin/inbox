<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

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
		int $threadID		// <int> The ID of the thread you're posting to.
	,	int $uniID	 		// <int> The uniID of the user creating the post.
	,	string $body			// <str> The post message.
	,	int $aviID = 0		// <int> The avatar ID being used in this post.
	): int					// RETURNS <int> ID of the post that was created, or 0 on failure.
	
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
		
		if(!Database::endTransaction($pass))
		{
			return 0;
		}
		
		return $postID;
	}
	
	
/****** Edit a Post ******/
	public static function edit
	(
		int $threadID		// <int> The ID of the thread the post is in.
	,	int $postID			// <int> The ID of the post you're editing.
	,	string $body			// <str> The new (edited) post message.
	,	int $aviID			// <int> The ID of the avatar to post with.
	): bool					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppPost::edit($threadID, $postID, "Hey everyone! Edit: Oh yeah, check out my blog!");
	{
		return Database::query("UPDATE posts SET body=?, avi_id=? WHERE thread_id=? AND id=?", array($body, $aviID, $threadID, $postID));
	}
	
}