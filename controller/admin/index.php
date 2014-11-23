<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Prepare Values
$adminPages = array();

// Managers of the Site
if(Me::$clearance >= 7)
{
	$adminPages[] = array("Create Categories", "/admin/categories/create");
}

// Mod & Staff Functions
if(Me::$clearance >= 6)
{
	$adminPages[] = array("Manage Forums", "/admin/forums");
}

// Display Functions
echo '
<br /><br />
<table class="mod-table">
	<tr>
		<td>Site Actions</td>
		<td>URL</td>
	</tr>';

foreach($adminPages as $page)
{
	echo '
	<tr>
		<td><a href="' . $page[1] . '">' . $page[0] . '</a></td>
		<td>' . $page[1] . '</td>
	</tr>';
}

echo '
</table>';