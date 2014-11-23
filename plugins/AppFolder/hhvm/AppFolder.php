<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

---------------------------------------
------ About the AppFolder Class ------
---------------------------------------

This class provides handling for inbox folders.


-------------------------------
------ Methods Available ------
-------------------------------

$uniqueID = UniqueID::get("folder");

*/

abstract class AppFolder {
	
	
/****** Retrieve the folder data ******/
	public static function get
	(
		int $uniID		// <int> The UniID that owns the folder.
	,	int $folderID	// <int> The folder ID to retrieve data from.
	): array <str, mixed>				// RETURNS <str:mixed> the folder's data.
	
	// $folderData = AppFolder::get($uniID, $folderID);
	{
		return Database::selectOne("SELECT * FROM folders WHERE uni_id=? AND folder_id=? LIMIT 1", array($uniID, $folderID));
	}
	
	
/****** Retrieve the folder data by a title ******/
	public static function getByTitle
	(
		int $uniID		// <int> The UniID that owns the folder.
	,	string $title		// <str> The title of the folder to retrieve.
	): array <str, mixed>				// RETURNS <str:mixed> the folder's data.
	
	// $folderData = AppFolder::get($uniID, $title);
	{
		return Database::selectOne("SELECT * FROM folders WHERE uni_id=? AND title=? LIMIT 1", array($uniID, $title));
	}
	
	
/****** Retrieve the folder list ******/
	public static function getList
	(
		int $uniID		// <int> The UniID to get the folder list for.
	): array <int, array<str, mixed>>				// RETURNS <int:[str:mixed]> an array of inbox folders.
	
	// $folders = AppFolder::getList($uniID);
	{
		return Database::selectMultiple("SELECT f.*, u.handle, u.display_name FROM folders f LEFT JOIN users u ON f.last_poster = u.uni_id WHERE f.uni_id=? ORDER BY f.folder_id ASC", array($uniID));
	}
	
	
/****** Get a list of threads within the folder specified ******/
	public static function getThreads
	(
		int $folderID		// <int> The ID of the folder you're retrieving threads from.
	,	int $page 			// <int> The page that you're viewing.
	,	int $numRows = 20	// <int> The number of threads to show.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $threads = AppFolder::getThreads($folderID, [$page], [$numRows]);
	{
		return Database::selectMultiple("SELECT ft.is_read, t.*, u.handle, u.display_name FROM folders_threads ft INNER JOIN threads t ON ft.thread_id=t.id  INNER JOIN users u ON t.last_poster_id=u.uni_id WHERE ft.folder_id=? ORDER BY ft.date_last_post DESC LIMIT " . (($page - 1) * $numRows) . ', ' . ($numRows + 0), array($folderID));
	}
	
	
/****** Update the details for a designated folder ******/
	public static function updateDetails
	(
		int $uniID			// <int> The UniID that owns the folder.
	,	int $folderID		// <int> The ID of the folder to update details for.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFolder::updateDetails($uniID, $folderID);
	{
		// Get the last entry in the folder
		$getFolder = Database::selectOne("SELECT *, t.last_poster_id, t.date_last_post FROM folders_threads ft INNER JOIN threads t ON ft.thread_id=t.id WHERE ft.folder_id=? ORDER BY ft.date_last_post DESC LIMIT 1", array($folderID));
		
		// Get the count of unread entries in the forum
		$count = (int) Database::selectValue("SELECT COUNT(*) as totalNum FROM folders_threads WHERE folder_id=? AND is_read=? LIMIT 1", array($folderID, 0));
		
		// Update the folder entry
		return Database::query("UPDATE folders SET unread=?, last_poster=?, date_lastPost=? WHERE uni_id=? AND folder_id=? LIMIT 1", array($count, $getFolder['last_poster_id'], $getFolder['date_last_post'], $uniID, $folderID));
	}
	
	
/****** Display a Forum Line by the ID ******/
	public static function displayLine
	(
		array <str, mixed> $folder				// <str:mixed> The data of the folder to display.
	,	bool $newPost = false	// <bool> TRUE will show a "new" icon, FALSE does not.
	): void						// RETURNS <void> Outputs the line.
	
	// AppFolder::displayLine($folder, [$newPost]);
	{
		// Prepare Values
		echo '
		<div class="inner-line">
			<div class="inner-name">
				<a href="/folder?id=' . $folder['folder_id'] . '">' . ($newPost ? '<img src="' . CDN . '/images/new.png" /> ' :  '') . $folder['title'] . '</a>
				<div class="inner-desc">' . $folder['description'] . '</div>
			</div>
			<div class="inner-details">' . ($folder['handle'] ? '<a href="' . URL::unifaction_social() . '/' . $folder['handle'] . '">' . $folder['display_name'] . '</a><br />' . Time::fuzzy((int) $folder['date_lastPost']) : "") . '</div>
		</div>';
	}
	
	
/****** Create default folders for inboxes ******/
	public static function generateDefaultFolders
	(
		int $uniID			// <int> The UniID to generate default folders for.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppFolder::generateDefaultFolders($uniID);
	{
		// Make sure the user doesn't already possess any folders
		if($check = Database::selectValue("SELECT uni_id FROM folders WHERE uni_id=? LIMIT 1", array($uniID)))
		{
			return false;
		}
		
		Database::startTransaction();
		
		// Create the first folder "Inbox"
		$nextFolderID = (int) UniqueID::get("folder");
		
		if(!Database::query("INSERT INTO folders (uni_id, folder_id, sort_order, title, description) VALUES (?, ?, ?, ?, ?)", array($uniID, $nextFolderID, 0, "General Inbox", "All messages go here by default until sorted into other folders.")))
		{
			return Database::endTransaction(false);
		}
		
		// Create the second folder "Personal"
		$nextFolderID = (int) UniqueID::get("folder");
		
		if(!Database::query("INSERT INTO folders (uni_id, folder_id, sort_order, title, description) VALUES (?, ?, ?, ?, ?)", array($uniID, $nextFolderID, 1, "Personal", "Correspondence with friends and family.")))
		{
			return Database::endTransaction(false);
		}
		
		// Create the third folder "Group Discussions"
		$nextFolderID = (int) UniqueID::get("folder");
		
		$pass = Database::query("INSERT INTO folders (uni_id, folder_id, sort_order, title, description) VALUES (?, ?, ?, ?, ?)", array($uniID, $nextFolderID, 2, "Group Discussions", "Correspondence with groups or hangouts."));
		
		return Database::endTransaction($pass);
	}
	
}