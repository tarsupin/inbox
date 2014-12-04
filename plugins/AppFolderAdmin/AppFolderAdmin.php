<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

---------------------------------------------
------ About the AppFolderAdmin Plugin ------
---------------------------------------------

This plugin provides the ability to administer the folder, folder categories, threads, and posts.


-------------------------------
------ Methods Available ------
-------------------------------

$folderID	= AppFolderAdmin::createFolder($categoryID, $title, $desc, $readPerm, $postPerm);

AppFolderAdmin::editFolder($folderID, $categoryID, $parentID, $title, $desc, $readPerm, $postPerm);
AppFolderAdmin::moveFolder($folderID, $moveVal = 0, $categoryID = 0);
AppFolderAdmin::moveThread($curFolderID, $threadID, $newfolderID);

AppFolderAdmin::deleteFolder($folderID);
AppFolderAdmin::deleteThread($folderID, $threadID);
AppFolderAdmin::deletePost($folderID, $threadID, $postID);

*/

abstract class AppFolderAdmin {
	
	
/****** Create a new Folder ******/
	public static function createFolder
	(
		$uniID				// <int> The UniID that owns the folder.
	,	$title				// <str> The title of the folder.
	,	$description		// <str> The caption (description) of the folder.
	)						// RETURNS <int> ID of the folder that was created, or 0 on failure.
	
	// $folderID = AppFolderAdmin::createFolder($uniID, $title, $description);
	{
		// Get the Slot Order
		if($sortOrder = (int) Database::selectValue("SELECT sort_order FROM folders WHERE uni_id=? ORDER BY sort_order DESC LIMIT 1", array($uniID)))
		{
			$sortOrder += 1;
		}
		
		// Prepare Values
		$lastID = 0;
		$pass = false;
		
		Database::startTransaction();
		
		// Create the first folder "Inbox"
		if($nextFolderID = (int) UniqueID::get("folder"))
		{
			$pass = Database::query("INSERT INTO `folders` (uni_id, folder_id, sort_order, title, description) VALUES (?, ?, ?, ?, ?)", array($uniID, $nextFolderID, $sortOrder, $title, $description));
			
			$lastID = (int) Database::$lastID;
		}
		
		Database::endTransaction($pass);
		
		return $lastID;
	}
	
	
/****** Edit an existing Folder ******/
	public static function editFolder
	(
		$uniID			// <int> The UniID that owns the folder.
	,	$folderID		// <int> The ID of the folder you're editing.
	,	$title			// <str> The title of the folder.
	,	$description	// <str> The caption (description) of the folder.
	)					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppFolderAdmin::editFolder($uniID, $folderID, $title, $description);
	{
		return Database::query("UPDATE folders SET title=?, description=? WHERE uni_id=? AND folder_id=? LIMIT 1", array($title, $description, $uniID, $folderID));
	}
	
	
/****** Move a Thread to a new Folder ******/
	public static function moveThread
	(
		$curFolderID	// <int> The ID of the folder where the thread currently exists.
	,	$threadID		// <int> The ID of the thread.
	,	$newFolderID	// <int> The ID of the folder to move the thread to.
	)					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppFolderAdmin::moveThread($curFolderID, $threadID, $newFolderID);
	{
		return Database::query("UPDATE folders_threads SET folder_id=? WHERE folder_id=? AND thread_id=? LIMIT 1", array($newFolderID, $curFolderID, $threadID));
	}
	
	
/****** Delete a Folder ******/
	public static function deleteFolder
	(
		$uniID		// <int> The UniID that owns the folder.
	,	$folderID	// <int> The ID of the folder you're deleting.
	)				// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppFolderAdmin::deleteFolder(Me::$id, $folderID);
	{		
		// Get the appropriate folder ("General Inbox")
		if(!$folderData = AppFolder::getByTitle($uniID, "General Inbox"))
		{
			return false;
		}
		
		// move all threads to the default folder
		$threads = Database::selectMultiple("SELECT thread_id FROM folders_threads WHERE folder_id=?", array($folderID));
		
		Database::startTransaction();		
		
		$pass = true;
		foreach($threads as $thread)
		{
			// Move the thread
			if(!$pass = AppFolderAdmin::moveThread($folderID, (int) $thread['thread_id'], (int) $folderData['folder_id']))
			{
				break;
			}
		}
		// Update the details of the folder you're moving to
		$pass = AppFolder::updateDetails($uniID, (int) $folderData['folder_id']);
		
		if(Database::endTransaction($pass))
		{
			return Database::query("DELETE FROM folders WHERE uni_id=? AND folder_id=? LIMIT 1", array($uniID, $folderID));
		}
		return false;
	}
	
	
/****** Delete a Thread ******/
	public static function deleteThread
	(
		$uniID			// <int> The UniID to remove the thread of.
	,	$threadID		// <int> The ID of the thread you're deleting.
	)					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppFolderAdmin::deleteThread($uniID, $threadID);
	{
		// Get the folder that this thread is in (for the user)
		if(!$folderData = AppThread::getFolderData($uniID, $threadID))
		{
			return false;
		}
		
		Database::startTransaction();
		
		// Delete the Thread from the user's thread
		if($pass = Database::query("DELETE FROM folders_threads WHERE folder_id=? AND thread_id=? LIMIT 1", array((int) $folderData['folder_id'], $threadID)))
		{
			// Delete the Thread's Posts
			$pass = Database::query("DELETE FROM threads_users WHERE thread_id=? AND uni_id=? LIMIT 1", array($threadID, $uniID));
			
			AppFolder::updateDetails($uniID, (int) $folderData['folder_id']);
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Delete a Post ******/
	public static function deletePost
	(
		$uniID			// <int> The UniID attempting to delete this post.
	,	$threadID		// <int> The ID of the thread that the post is contained in.
	,	$postID			// <int> The ID of the post you're deleting.
	)					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppFolderAdmin::deletePost($uniID, $threadID, $postID);
	{
		Database::startTransaction();
		
		// Delete the Post
		if($pass = Database::query("DELETE FROM posts WHERE thread_id=? AND id=? AND uni_id=? LIMIT 1", array($threadID, $postID, $uniID)))
		{
			$postCount = (int) Database::selectValue("SELECT COUNT(*) as totalNum FROM posts WHERE thread_id=? AND uni_id=? LIMIT 1", array($threadID, $uniID));

			if($postCount > 0)
			{
				$lastPost = Database::selectOne("SELECT uni_id, date_post FROM posts WHERE thread_id=? ORDER BY id DESC LIMIT 1", array($threadID));
				$pass = Database::query("UPDATE threads SET last_poster_id=?, posts=?, date_last_post=? WHERE id=? LIMIT 1", array((int) $lastPost['uni_id'], $postCount, (int) $lastPost['date_post'], $threadID));
			}
			else
			{
				// Retrieve a list of users that follow this thread
				$threadUsers = AppThread::getUsers($threadID);
				
				foreach($threadUsers as $usr)
				{
					if(!$pass = AppFolderAdmin::deleteThread((int) $usr['uni_id'], $threadID))
					{
						break;
					}
				}
				
				// Make sure everything up to now has passed
				if($pass)
				{
					$pass = Database::query("DELETE FROM threads WHERE id=? LIMIT 1", array($threadID));
					
					if($folderData = AppThread::getFolderData($uniID, $threadID))
					{
						AppFolder::updateDetails($uniID, (int) $folderData['folder_id']);
					}
				}
			}
		}
		
		return Database::endTransaction($pass);
	}
}
