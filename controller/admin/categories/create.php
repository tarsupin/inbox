<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/admin/categories/create		{this is specific to forums}
	
	This page allows you to create categories.
*/

// Submit Form
if(Form::submitted("forum-category-create"))
{
	FormValidate::text("Category", $_POST['category'], 1, 32);
	
	// Check if forum is valid
	if(isset($_POST['parent_forum']))
	{
		if($_POST['parent_forum'] != 0)
		{
			// Check for the legitimate forum
			if(!Database::selectValue("SELECT id FROM forums WHERE id=? LIMIT 1", array($_POST['parent_forum'])))
			{
				Alert::error("Parent Forum", "You have selected an invalid parent forum.");
			}
		}
	}
	else
	{
		Alert::error("Parent Forum", "You have selected an invalid parent forum.");
	}
	
	// If form has passed
	if(FormValidate::pass())
	{
		$catID = AppFolderAdmin::createCategory($_POST['parent_forum'] + 0, $_POST['category']);
		
		Alert::success("Category", "You have created a new category!");
	}
}

// Run Permissions
require(SYS_PATH . "/controller/includes/admin_perm.php");

// Run Header
require(SYS_PATH . "/controller/includes/admin_header.php");

echo '
<form class="uniform" action="/admin/categories/create" method="post">' . Form::prepare("forum-category-create") . '
	<p>
		Parent Forum:
		<select name="parent_forum">
			<option value="0">Home Page</option>';
		
		$forums	= AppFolder::getForums(0);
		
		foreach($forums as $forum)
		{
			echo '
			<option value="' . $forum['id'] . '">' . $forum['title'] . '</option>';
		}
		
		echo '
		</select>
	</p>
	
	<p>Category: <input type="text" name="category" value="" /></p>
	<p><input type="submit" name="submit" value="Create Category" /></p>
</form>';

// Display the Footer
require(SYS_PATH . "/controller/includes/admin_footer.php");
