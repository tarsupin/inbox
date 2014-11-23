<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// You must be logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/"); exit;
}

// Make sure the proper information was prepared
if(!isset($_GET['id']))
{
	header("Location: /"); exit;
}

// Get thread details
if(!$threadData = AppThread::get((int) $_GET['id']))
{
	header("Location: /"); exit;
}

// Make sure you have clearance to view this thread
if(!AppThread::allowedAccess(Me::$id, (int) $threadData['id']))
{
	header("Location: /"); exit;
}

// Pull a list of my available folders
$folders = AppFolder::getList(Me::$id);

// Run the Form
if(Form::submitted("move-thread"))
{
	// Forum Sort
	if(!isset($_POST['folder_move']) or $_POST['folder_move'] < 1)
	{
		Alert::error("Chosen Folder", "You must select a valid folder to sort the thread into.");
	}
	
	if(FormValidate::pass())
	{
		// Get the current folder data of the thread
		$folderData = AppThread::getFolderData(Me::$id, (int) $threadData['id']);
		
		// Make sure the folder you're moving to is different than your current one
		if($folderData['folder_id'] == $_POST['folder_move'])
		{
			Alert::error("No Movement", "The thread is already sorted into that folder.");
		}
	}
	
	if(FormValidate::pass())
	{
		Database::startTransaction();
		
		// Move the thread
		if($pass = AppFolderAdmin::moveThread((int) $folderData['folder_id'], (int) $threadData['id'], (int) $_POST['folder_move']))
		{
			// Update the details of the folder you're moving from
			if($pass = AppFolder::updateDetails(Me::$id, (int) $folderData['folder_id']))
			{
				// Update the details of the folder you're moving to
				$pass = AppFolder::updateDetails(Me::$id, (int) $_POST['folder_move']);
			}
		}
		
		if(Database::endTransaction($pass))
		{
			Alert::success("Thread Sorted", "You have successfully moved the thread.");
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

// Display your list of avatars available
echo '
<div class="overwrap-box">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">Move Thread: "' . $threadData['title'] . '"</div>
	</div>
	<div class="inner-box">
		<div style="padding:8px;">
		<form class="uniform" action="/tools/moveThread?id=' . $threadData['id'] . '" method="post">' . Form::prepare("move-thread") . '
		Which folder would you like to sort this thread into?<br />
		
		<select name="folder_move">
			<option value="0">-- Choose a Folder --</option>';
		
		foreach($folders as $folder)
		{
			echo '
			<option value="' . $folder['folder_id'] . '">' . $folder['title'] . '</option>';
		}
		
		echo '
		</select>
		
		<div style="margin-top:12px;"><input type="submit" name="submit" value="Submit" /></div>
		</form>
		</div>
	</div>
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
