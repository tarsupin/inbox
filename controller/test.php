<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(Me::$vals['handle'] != "Brint")
{
	exit;
}

Database::initRoot();
//echo Security::randHash(100, 74);

DatabaseAdmin::addIndex("threads", "forum_id, date_last_post", "INDEX");

echo 'Database updated on ' . SITE_HANDLE;