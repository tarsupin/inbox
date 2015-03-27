<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Must log in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

if(Me::$clearance < 2)
{
	Alert::saveError("Invalid Permissions", "You must have higher permissions to create threads.");
	header("Location: /"); exit;
}

// Make sure you have a valid ID for this folder
if(!isset($_GET['folder']) or !$folder = AppFolder::get(Me::$id, (int) $_GET['folder']))
{
	header("Location: /"); exit;
}

// Recognize Integers
$folderID = (int) $folder['folder_id'];

// Prepare Variables
$_POST['body'] = isset($_POST['body']) ? Security::purify($_POST['body']) : '';

// Create the thread
if(Form::submitted(SITE_HANDLE . '-folder-thrd'))
{
	$_POST['title'] = trim($_POST['title']);
	$_POST['body'] = trim($_POST['body']);
	FormValidate::text("Title", $_POST['title'], 1, 48);
	$_POST['body'] = isset($_POST['body']) ? Security::purify($_POST['body']) : '';
	if(strlen($_POST['body']) < 1)
	{
		Alert::error("Post Length", "Please enter a message.");
	}
	elseif(strlen($_POST['body']) > 32000)
	{
		Alert::error("Post Length", "Your post length may not exceed 32000 characters.");
	}
	
	if(strlen($_POST['body']) < 1)
	{
		Alert::error("Message", "Please enter a message.");
	}
	
	// Get the list of users that were send in "To"
	$userList = str_replace(",", " ", $_POST['to']);
	$userList = preg_replace('/\s+/', ' ', $userList);
	$uniIDList = array();
	
	if($userList = explode(" ", Sanitize::variable($userList, " ")))
	{
		if(count($userList) > 10)
		{
			Alert::error("Too Many Users", "You can only add a maximum of ten users to a thread.");
		}
	}
	else
	{
		Alert::error("Invalid Users", "You have no valid users chosen to deliver the message to.");
	}
	
	if(FormValidate::pass())
	{
		list($sqlWhere, $sqlArray) = Database::sqlFilters(array("handle" => $userList));
		$handleList = array();
		
		// Attempt to access each user
		$getList = Database::selectMultiple("SELECT uni_id, handle FROM users_handles WHERE " . $sqlWhere, $sqlArray);
		
		foreach($getList as $gl)
		{
			$handleList[$gl['handle']] = true;
			$uniIDList[] = (int) $gl['uni_id'];
		}
		
		// Check each user list to see if they are valid
		foreach($userList as $user)
		{
			if(!isset($handleList[$user]))
			{
				User::silentRegister($user);
				
				if(!$check = (int) Database::selectValue("SELECT uni_id FROM users_handles WHERE handle=? LIMIT 1", array($user)))
				{
					Alert::error("Invalid", "The user @" . $user . " was not located.");
				}
				else
				{
					AppFolder::generateDefaultFolders($check);
				}
			}
		}
	}
	
	// Make sure there are UniID's that you are sending the message to
	if(!$uniIDList)
	{
		Alert::error("No Recipient", "You must have a valid recipient.");
	}
	
	if(FormValidate::pass())
	{
		// Create the Thread
		Database:: startTransaction();
		
		if($threadID = AppThread::create(Me::$id, $_POST['title'], $uniIDList))
		{
			// Create the post itself
			if(AppPost::create($threadID, Me::$id, $_POST['body'], (int) Me::$vals['avatar_opt']))
			{
				Database::endTransaction();
				
				// Display success
				Alert::saveSuccess("Post Successful", "You have successfully created a new inbox thread.");
				
				// Remove yourself from the UniIDList
				if(($key = array_search(Me::$id, $uniIDList)) !== false)
				{
					unset($uniIDList[$key]);
				}
				
				// Send notifications to everyone in the UniIDList
				Notifications::createMultiple($uniIDList, URL::inbox_unifaction_com() . "/thread?id=" . $threadID, "You've received a new inbox message from @" . Me::$vals['handle'] . ".");
				
				// Go to the thread
				header("Location: /thread?id=" . $threadID); exit;
			}
		}
		
		Database::endTransaction(false);
	}
}
else
{
	// Final Sanitization ($_POST['body'] was done above with full purification)
	$_POST['to'] = (isset($_POST['to']) ? Sanitize::variable($_POST['to'], "@, ") : "");
	$_POST['title'] = (isset($_POST['title']) ? Sanitize::safeword($_POST['title']) : "");
}

// Prepare the "to" column
if(isset($_GET['to']) and !$_POST['to'])
{
	$_POST['to'] = Sanitize::variable($_GET['to']);
}

$config['pageTitle'] = $config['site-name'] . " > " . $folder['title'] . " > New Thread";

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
<div class="thread-tline"><a href="/">Home</a> &gt; <a href="/folder?id=' . $folder['folder_id'] . '">' . $folder['title'] . '</a> &gt; New Thread</div>';

echo '
<div class="overwrap-box">
	<div class="overwrap-line">
		<div class="overwrap-name">Post New Thread</div>
	</div>
	<div style="padding:6px;">
		<form class="uniform" action="/new-thread?folder=' . $folderID . '" method="post" style="padding-right:20px;">' . Form::prepare(SITE_HANDLE . '-folder-thrd') . '
			<div><strong>To</strong>:<br /><input type="text" name="to" value="' . trim($_POST['to']) . '" placeholder="JoeSmith, Brandon1, CarmelAppleGuy, ..." style="width:100%;margin-bottom:10px;" autocomplete="off" maxlength="240" tabindex="10" autofocus /></div>
			<div><strong>Title</strong>:<br /><input type="text" name="title" value="' . $_POST['title'] . '" placeholder="Title . . ." style="width:100%;margin-bottom:10px;" autocomplete="off" maxlength="48" tabindex="20" /></div>
			' . UniMarkup::buttonLine() . '
			<textarea id="core_text_box" name="body" placeholder="Enter your message here . . ." style="resize:vertical;width:100%;height:300px;" tabindex="30">' . $_POST['body'] . '</textarea>
			<div style="margin-top:10px;"><input type="button" value="Preview" onclick="previewPost();"/> <input type="submit" name="submit" value="Post to Thread" /></div>
			<div id="preview" class="thread-post" style="display:none; padding:4px; margin-top:10px;"></div>
		</form>
	</div>
</div>';

echo '
</div>
<script>
function previewPost()
{
	var text = encodeURIComponent(document.getElementById("core_text_box").value);
	getAjax("", "preview-post", "parse", "body=" + text);
}
function parse(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("preview").style.display = "block";
	document.getElementById("preview").innerHTML = response;
}
function quotePost(forum, thread, post)
{
	getAjax("", "quote-post", "parseadd", "forumID=" + forum, "threadID=" + thread, "postID=" + post);
}
function parseadd(response)
{
	if(!response) { response = ""; }
	
	document.getElementById("core_text_box").value += response + "\n\n";
}
</script>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
