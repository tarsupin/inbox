<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/admin/forums		{this is specific to forums}
	
	This page allows you to manage forums.
*/

// Run Permissions
require(SYS_PATH . "/controller/includes/admin_perm.php");

// Run Header
require(SYS_PATH . "/controller/includes/admin_header.php");

// Move Forum Up
if(isset($_POST['moveUp']))
{
	AppFolderAdmin::moveForum($_POST['moveUp'], -1);
}

// Get list of forums
$forums = Database::selectMultiple("SELECT f.id, c.title as catTitle, f.posts, f.views, f.title, f.description, f.forum_order, f.perm_read, f.perm_post FROM forums f LEFT JOIN forum_categories c ON c.id=f.category_id ORDER BY category_id, forum_order", array());

// Prepare Permission Selections
$clearances = User::clearance();

echo '
<h2>Forum List</h2>

<table class="mod-table">
	<tr>
		<td>Options</td>
		<td>Category</td>
		<td>Forum</td>
		<td>Description</td>
		<td>Posts</td>
		<td>Views</td>
		<td>Visible To</td>
		<td>Post Clearance</td>
	</tr>';

foreach($forums as $forum)
{
	// Display Row
	echo '
	<tr>
		<td><a href="/admin/forums/edit?forum=' . $forum['id'] . '">Edit</a>' . ($forum['forum_order'] > 1 ? ', <a href="/admin/forums?moveUp=' . $forum['id'] . '">UP</a>' : '') . '</td>
		<td>' . $forum['catTitle'] . '</td>
		<td>' . $forum['title'] . '</td>
		<td>' . $forum['description'] . '</td>
		<td>' . $forum['posts'] . '</td>
		<td>' . $forum['views'] . '</td>
		<td>' . $clearances[$forum['perm_read']] . '</td>
		<td>' . $clearances[$forum['perm_post']] . '</td>
	</tr>';
}

echo '
</table>

<a class="button" href="/admin/forums/create">Create Forum</a>';

// Display the Footer
require(SYS_PATH . "/controller/includes/admin_footer.php");
