<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/admin/forums/create		{this is specific to forums}
	
	This page allows you to create new forums.
*/

// Submit Form
if(Form::submitted("forum-create"))
{
	FormValidate::number("Category ID", $_POST['category_id'], 1);
	
	FormValidate::text("Title", $_POST['title'], 1, 42);
	FormValidate::text("Description", $_POST['description'], 0, 128);
	
	FormValidate::number("Visible To", $_POST['perm_read'], 0, 9);
	FormValidate::number("Post Clearance", $_POST['perm_post'], 2, 9);
	
	// Confirm that the category ID exists
	if(!$getCat = Database::selectValue("SELECT id FROM forum_categories WHERE id=? LIMIT 1", array($_POST['category_id'])))
	{
		Alert::error("Category", "That category doesn't exist.");
	}
	
	if(FormValidate::pass())
	{
		$forumID = AppFolderAdmin::createForum($_POST['category_id'], $_POST['title'], $_POST['description'], $_POST['perm_read'], $_POST['perm_post']);
		
		Alert::success("Forum Created", "You have successfully created a forum.");
	}
}
else
{
	$_POST['title'] = Sanitize::safeword($_POST['title']);
	$_POST['description'] = Sanitize::safeword($_POST['description']);
	$_POST['perm_read'] = $_POST['perm_read'] + 0;
	$_POST['perm_post'] = $_POST['perm_post'] + 0;
}

// Run Permissions
require(SYS_PATH . "/controller/includes/admin_perm.php");

// Run Header
require(SYS_PATH . "/controller/includes/admin_header.php");

// Gather the list of categories available
$categories = Database::selectMultiple("SELECT id, parent_forum, cat_order, title FROM forum_categories", array());

if(!$categories)
{
	Alert::saveSuccess("No Categories", "You cannot create a forum until you have at least one category.");
	
	header("Location: /admin/categories/create"); exit;
}

echo '
<h2>Create a Forum</h2>
<form class="uniform" action="/admin/forums/create" method="post">' . Form::prepare("forum-create") . '
	
	<p>Category:
	<select name="category_id">';
	
	foreach($categories as $cat)
	{
		echo '
		<option name="category_id" value="' . $cat['id'] . '">' . $cat['title'] . '</option>';
	}
	
	echo '
	</select>
	</p>
	
	<p>Title: <input type="text" name="title" value="' . htmlspecialchars($_POST['title']) . '" /></p>
	<p>Description: <input type="text" name="description" value="' . htmlspecialchars($_POST['description']) . '" /></p>
	
	<p>Visible To:
	<select name="perm_read">' . str_replace('value="' . ($_POST['perm_read'] + 0) . '"', 'value="' . ($_POST['perm_read'] + 0) . '" selected', '
		<option value="0" selected>Public Forum</option>
		<option value="2">Users (not guests)</option>
		<option value="3">VIPs / Trusted Users</option>
		<option value="5">Staff Only</option>') . '
	</select>
	</p>
	
	<p>Post Allowance:
	<select name="perm_post">' . str_replace('value="' . ($_POST['perm_post'] + 0) . '"', 'value="' . ($_POST['perm_post'] + 0) . '" selected', '
		<option value="2">Users</option>
		<option value="3">VIPs / Trusted Users</option>
		<option value="5">Staff Only</option>') . '
	</select>
	</p>
	
	<p><input type="submit" name="submit" value="Create Forum" /></p>
</form>';

// Display the Footer
require(SYS_PATH . "/controller/includes/admin_footer.php");
