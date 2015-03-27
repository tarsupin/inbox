<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// You must be logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/settings"); exit;
}

// Edit Folder
if(Form::submitted("folder-edit"))
{
	// make sure it is your folder and not a default one; important!
	$folders = AppFolder::getList(Me::$id);
	$pass = false;
	foreach($folders as $key => $f)
	{
		if($key < 3) { continue; }
		if($f['folder_id'] == $_POST['id'])
		{
			$pass = true;
			break;
		}
	}
	
	if(!$pass)
	{
		Alert::error("Not Allowed", "You do not have permission to edit or delete this folder.");
	}
	
	if(isset($_POST['edit']))
	{
		$_POST['title'] = Sanitize::safeword($_POST['title']);
		$_POST['description'] = Sanitize::text($_POST['description']);
		FormValidate::text("Title", $_POST['title'], 1, 32);
		FormValidate::text("Description", $_POST['description'], 1, 128);
		FormValidate::number("Folder", $_POST['id'], 0);
		
		if(FormValidate::pass())
		{
			AppFolderAdmin::editFolder(Me::$id, (int) $_POST['id'], $_POST['title'], $_POST['description']);
			
			Alert::success("Category", "You have updated the folder!");
		}
	}
	elseif(isset($_POST['delete']))
	{
		if(FormValidate::pass())
		{
			if(AppFolderAdmin::deleteFolder(Me::$id, (int) $_POST['id']))
			{
				Alert::success("Folder Deleted", "You have successfully deleted the folder.");
			}
		}
	}
}

// Create Folder
if(Form::submitted("folder-create"))
{
	$_POST['title'] = Sanitize::safeword($_POST['title']);
	$_POST['description'] = Sanitize::text($_POST['description']);
	FormValidate::text("Title", $_POST['title'], 1, 32);
	FormValidate::text("Description", $_POST['description'], 1, 128);
	
	if(FormValidate::pass())
	{
		$catID = AppFolderAdmin::createFolder(Me::$id, $_POST['title'], $_POST['description']);
		
		Alert::success("Category", "You have created a new folder!");
	}
}

// Set Signature
if(Form::submitted("settings-inbox"))
{
	$_POST['signature'] = isset($_POST['signature']) ? Security::purify($_POST['signature']) : '';
	if(strlen($_POST['signature']) > 20000)
	{
		Alert::error("Signature Length", "Your signature length may not exceed 20000 characters.");
	}
	
	if(FormValidate::pass())
	{
		AppSettings::updateSignature(Me::$id, $_POST['signature']);
		
		Alert::success("Signature Updated", "You have successfully updated your settings.");
	}
}

// If you chose an avatar
if(isset($_GET['def']))
{
	// Check if that avatar is valid
	if($_GET['def'] == 0)
	{
		$avatarExists = true;
	}
	else
	{
		$packet = array(
			"uni_id"	=> Me::$id
		,	"avi_id"	=> (int) $_GET['def']		// The ID of the avatar to test for
		);
		$avatarExists = Connect::to("avatar", "AvatarExists", $packet);
	}	
	
	if($avatarExists)
	{
		// If the avatar is valid, update your default avatar
		Database::query("UPDATE users SET avatar_opt=? WHERE uni_id=? LIMIT 1", array((int) $_GET['def'], Me::$id));
		
		Me::$vals['avatar_opt'] = (int) $_GET['def'];
		
		Alert::success("Avatar Updated", "You have chosen your default avatar.");
	}
	else
	{
		Alert::error("Avatar Error", "There was an error trying to load this avatar.");
	}
}

// Get the user's signature
if(!$settings = AppSettings::getSettings(Me::$id, true))
{
	AppSettings::updateSignature(Me::$id, "");
	
	$settings = array("avatar_list" => json_encode(array()), "signature" => "");
}

// Prepare Values
$avatarList = json_decode($settings['avatar_list'], true);

// If you're loading your avatars
if($value = Link::clicked() and $value == "load-avatars")
{
	// Prepare a list of plugins and their current versions
	$packet = array(
		"uni_id"			=> Me::$id			// The UniID to check avatars for
	);
	
	if($avatarList = Connect::to("avatar", "MyAvatarsAPI", $packet))
	{
		// Update your avatar list
		Database::query("UPDATE settings SET avatar_list=? WHERE uni_id=? LIMIT 1", array(json_encode($avatarList), Me::$id));
		
		if(!Me::$vals['avatar_opt'])
		{
			Database::query("UPDATE users SET avatar_opt=? WHERE uni_id=? LIMIT 1", array(1, Me::$id));
		}
	}
}

$config['pageTitle'] = $config['site-name'] . " > Settings";

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

// Display form for new folders
echo '
<div class="overwrap-box">
	<div class="overwrap-line">
		<div class="overwrap-name">My Folders</div>
	</div>';
$folders = AppFolder::getList(Me::$id);
foreach($folders as $key => $f)
{
	// skip default folders; important!
	if($key < 3) { continue; }
	echo '
	<form class="uniform" method="post">' . Form::prepare("folder-edit") . '
		<input type="hidden" name="id" value="' . $f['folder_id'] . '"/>
		<p><input type="text" name="title" value="' . $f['title'] . '" maxlength="32" /> <input type="text" name="description" value="' . $f['description'] . '" maxlength="128"/> <input type="submit" name="edit" value="Edit" /> <input type="submit" name="delete" value="Delete" onclick="return confirm(\'Are you sure you want to delete this folder?\');"/></p>
	</form>';
}
echo '
	<form class="uniform" method="post">' . Form::prepare("folder-create") . '
		<p><input type="text" name="title" value="" maxlength="32" placeholder="Title" /> <input type="text" name="description" value="" maxlength="128" placeholder="Description"/> <input type="submit" name="submit" value="Create" /></p>
	</form>
</div>';

// Display your signature options
echo '
<div class="overwrap-box">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">My Signature</div>
	</div>
	' . UniMarkup::buttonLine() . '
	<div style="padding:6px;">
		<form class="uniform" action="/settings" method="post" style="padding-right:20px;">' . Form::prepare('settings-inbox') . '
			<textarea id="core_text_box" name="signature" placeholder="Enter your signature here . . ." style="resize:vertical; width:100%; height:280px;" maxlength="20000">' . $settings['signature'] . '</textarea>
			<div style="margin-top:10px;"><input type="button" value="Preview" onclick="previewPost();"/> <input type="submit" name="submit" value="Update My Signature" /></div>
			<div id="preview" class="thread-post" style="display:none; padding:4px; margin-top:10px;"></div>
		</form>
	</div>
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
</script>';


// Display your list of avatars available
echo '
<div class="overwrap-box">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">My Avatars</div>
	</div>
	<div class="inner-box">
		<div style="display:inline-block; padding:6px; text-align:center;"><img src="' . ProfilePic::image(Me::$id, "large") . '" /><br /><a class="button" href="/settings?def=0">Set as Default</a></div>';
if($avatarList)
{
	foreach($avatarList as $aviID => $aviName)
	{
		echo '
		<div style="display:inline-block; padding:6px; text-align:center;"><img src="' . Avatar::image(Me::$id, (int) $aviID) . '" /><br /><a class="button" href="/settings?def=' . $aviID . '">Set as Default</a></div>';
	}
}

echo '
	<div style="padding:8px;"><a class="button" href="/settings?loadAvis=1&' . Link::prepare("load-avatars") . '">Load My Avatars</a> <a class="button" href="' . URL::avatar_unifaction_com() . Me::$slg . '">Create an Avatar</a></div>
	</div>
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
