<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

---------------------------------------
------ About the AppThread Class ------
---------------------------------------

This class provides handling for inbox threads.


-------------------------------
------ Methods Available ------
-------------------------------

$threadData = AppThread::get($threadID);

$posts = AppThread::getPosts($threadID, [$page], [$numRows]);

$threadID = AppThread::create($folder, $uniID, $title, $sticky);

AppThread::edit($threadID, $title);

*/

abstract class AppThread {
	
	
/****** Retrieve Thread Data ******/
	public static function get
	(
		int $threadID		// <int> The ID of the thread.
	): array <str, mixed>					// RETURNS <str:mixed> an array of the thread data.
	
	// $threadData = AppThread::get($threadID);
	{
		return Database::selectOne("SELECT t.*, u.handle, u.display_name FROM threads t INNER JOIN users u ON t.last_poster_id=u.uni_id WHERE id=? LIMIT 1", array($threadID));
	}
	
	
/****** Get a list of posts within the thread specified ******/
	public static function getPosts
	(
		int $threadID		// <int> The ID of the thread you're retrieving posts from.
	,	int $page 			// <int> The thread page that you're viewing.
	,	int $numRows = 20	// <int> The number of posts to show.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> an array of posts.
	
	// $posts = AppThread::getPosts($threadID, [$page], [$numRows]);
	{
		return Database::selectMultiple("SELECT p.*, s.signature, u.handle, u.display_name, u.date_joined FROM posts p LEFT JOIN settings s ON p.uni_id=s.uni_id INNER JOIN users u ON p.uni_id=u.uni_id WHERE p.thread_id=? ORDER BY p.id ASC LIMIT " . (($page - 1) * $numRows) . ', ' . ($numRows + 0), array($threadID));
	}
	
	
/****** Get the ID of the folder that the thread belongs to ******/
	public static function getFolderData
	(
		int $uniID			// <int> The UniID that we're identifying the folder for.
	,	int $threadID		// <int> The ID of the thread.
	): array <str, mixed>					// RETURNS <str:mixed> an array of the folder data.
	
	// $folderData = AppThread::getFolderData($uniID, $threadID);
	{
		return Database::selectOne("SELECT f.folder_id, f.title FROM folders f INNER JOIN folders_threads ft ON f.folder_id=ft.folder_id WHERE f.uni_id=? AND ft.thread_id=? LIMIT 1", array($uniID, $threadID));
	}
	
	
/****** Verify if a user is allowed access to a thread ******/
	public static function allowedAccess
	(
		int $uniID			// <int> The UniID to verify access.
	,	int $threadID		// <int> The ID of the thread.
	): bool					// RETURNS <bool> TRUE if the user can access the thread, FALSE on failure.
	
	// AppThread::allowedAccess($uniID, $threadID);
	{
		return (bool) Database::selectValue("SELECT uni_id FROM threads_users WHERE thread_id=? AND uni_id=? LIMIT 1", array($threadID, $uniID));
	}
	
	
/****** Get a list of users allowed in this thread ******/
	public static function getUsers
	(
		int $threadID		// <int> The ID of the thread.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> a list of user data.
	
	// $threadUsers = AppThread::getUsers($threadID);
	{
		return Database::selectMultiple("SELECT u.uni_id, u.handle, u.display_name, u.role FROM threads_users tu INNER JOIN users u ON tu.uni_id=u.uni_id WHERE tu.thread_id=?", array($threadID));
	}
	
	
/****** Get a UniID List for users in this thread ******/
	public static function getUniIDList
	(
		int $threadID		// <int> The ID of the thread.
	): array <int, int>					// RETURNS <int:int> a list of user data.
	
	// $uniIDList = AppThread::getUniIDList($threadID);
	{
		$uniIDList = array();
		
		$getUsers = Database::selectMultiple("SELECT u.uni_id FROM threads_users tu INNER JOIN users u ON tu.uni_id=u.uni_id WHERE tu.thread_id=?", array($threadID));
		
		foreach($getUsers as $gu)
		{
			$uniIDList[] = (int) $gu['uni_id'];
		}
		
		return $uniIDList;
	}
	
	
/****** Create a new thread ******/
	public static function create
	(
		int $uniID	 		// <int> The uni_id of the user creating the thread.
	,	string $title			// <str> The title of the thread.
	,	array <int, int> $uniIDList		// <int:int> An array of UniID's that are being added to the thread.
	): int					// RETURNS <int> ID of the thread that was created, or FALSE on failure.
	
	// $threadID = AppThread::create($uniID, $title, $uniIDList);
	{
		// Prepare Values
		$threadID = 0;
		$timestamp = time();
		
		// Add yourself to the UniID List
		if(!in_array($uniID, $uniIDList))
		{
			$uniIDList[] = $uniID;
		}
		
		$uniIDList = array_unique($uniIDList);
		
		Database::startTransaction();
		
		// Create the thread
		if(!Database::query("INSERT INTO threads (title, owner_id, last_poster_id, date_created, date_last_post) VALUES (?, ?, ?, ?, ?)", array($title, $uniID, $uniID, $timestamp, $timestamp)))
		{
			Database::endTransaction(false);
			return 0;
		}
		
		$threadID = Database::$lastID;
		
		// Add each of the users in the list
		foreach($uniIDList as $nextUniID)
		{
			if(!Database::query("REPLACE INTO threads_users (thread_id, uni_id) VALUES (?, ?)", array($threadID, $nextUniID)))
			{
				Database::endTransaction(false);
				return 0;
			}
		}
		
		// Get the SQL Filters for the user list
		list($sqlWhere, $sqlArray) = Database::sqlFilters(array("uni_id" => $uniIDList, "title" => array("General Inbox")));
		
		// Add these to the user's general inbox
		$generalInboxes = Database::selectMultiple("SELECT folder_id FROM folders WHERE " . $sqlWhere, $sqlArray);
		
		// If not every user has a general inbox, we need to create their default inboxes
		if(count($generalInboxes) != count($uniIDList))
		{
			foreach($uniIDList as $uniID)
			{
				AppFolder::generateDefaultFolders($uniID);
			}
			
			// Try to retrieve the inboxes again
			$generalInboxes = Database::selectMultiple("SELECT folder_id FROM folders WHERE " . $sqlWhere, $sqlArray);
			
			// If there's still a miscount, break the thread
			if(count($generalInboxes) != count($uniIDList))
			{
				Database::endTransaction(false);
				return 0;
			}
		}
		
		// Loop through each inbox and add the thread
		foreach($generalInboxes as $inbox)
		{
			if(!$pass = Database::query("INSERT INTO folders_threads (folder_id, thread_id, date_last_post) VALUES (?, ?, ?)", array($inbox['folder_id'], $threadID, $timestamp)))
			{
				Database::endTransaction(false);
				return 0;
			}
		}
		
		Database::endTransaction(true);
		
		return $threadID;
	}
	
	
/****** Edit an existing Thread ******/
	public static function edit
	(
		int $threadID		// <int> The ID of the thread you're editing.
	,	string $title			// <str> The title of the thread.
	): bool					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppThread::edit($threadID, $title);
	{
		return Database::query("UPDATE threads SET title=? WHERE id=? LIMIT 1", array($title, $threadID));
	}
	
	
/****** Mark a thread as read ******/
	public static function markAsRead
	(
		int $uniID			// <int> The UniID who is marking the thread read.
	,	int $threadID		// <int> The ID of the thread.
	): bool					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppThread::markAsRead($uniID, $threadID);
	{
		return Database::query("UPDATE threads_users tu INNER JOIN folders f ON f.uni_id=tu.uni_id INNER JOIN folders_threads ft ON ft.folder_id=f.folder_id AND ft.thread_id=? SET f.unread=f.unread-1, ft.is_read=? WHERE tu.thread_id=? AND tu.uni_id=? AND ft.is_read=?", array($threadID, 1, $threadID, $uniID, 0));
	}
	
	
/****** Mark a thread as unread ******/
	public static function markAsUnread
	(
		int $uniID			// <int> The UniID who is marking the thread unread.
	,	int $threadID		// <int> The ID of the thread.
	): bool					// RETURNS <bool> TRUE on success, or FALSE on failure.
	
	// AppThread::markAsUnread($uniID, $threadID);
	{
		return Database::query("UPDATE threads_users tu INNER JOIN folders f ON f.uni_id=tu.uni_id INNER JOIN folders_threads ft ON ft.folder_id=f.folder_id AND ft.thread_id=? SET f.unread=f.unread+1, ft.is_read=? WHERE tu.thread_id=? AND tu.uni_id=? AND ft.is_read=?", array($threadID, 0, $threadID, $uniID, 1));
	}
	

/****** Get a user's avatar name ******/
	public static function getName
	(
		int $uniID			// <int> The UniID of the user to retrieve the signature of.
	,	int $aviID			// <int> The ID of the avatar to get the name of.
	): string					// RETURNS <str> The name for the avatar.
	
	// $aviname = AppForum::getName($uniID, $aviID);
	{
		if($aviID == 0)
		{
			return "";
		}
		
		if($name = Cache::get($uniID . "-" . $aviID . "-avi-name"))
		{
			return $name;
		}
		
		if($name = Database::selectOne("SELECT avatar_list FROM settings WHERE uni_id=? LIMIT 1", array($uniID)))
		{
			if($name['avatar_list'] != "")
			{
				$name = json_decode($name['avatar_list'], true);
				if(isset($name[$aviID]))
				{
					Cache::set($uniID . "-" . $aviID . "-avi-name", $name['aviID'], 60 * 60);
					if($name[$aviID] != "")
					{
						return $name[$aviID];
					}
				}
			}
		}
		
		return "";
	}
	
}