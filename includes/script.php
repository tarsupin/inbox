<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Don't forget to use: ?uni6scriptadm=1

Database::initRoot();

/*
DatabaseAdmin::addColumn("posts", "likes", "tinyint(1) unsigned not null", 0);

Database::exec("
CREATE TABLE IF NOT EXISTS `posts_likes`
(
	`post_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
	`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
	
	UNIQUE (`post_id`, `uni_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(post_id) PARTITIONS 5;
");

Database::exec("
CREATE TABLE IF NOT EXISTS `posts_recent`
(
	`date_posted`			int(10)			unsigned	NOT NULL	DEFAULT '0',
	
	`thread_title`			varchar(72)					NOT NULL	DEFAULT '',
	`thread_posts`			mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
	`thread_views`			int(10)			unsigned	NOT NULL	DEFAULT '0',
	
	`post_link`				varchar(120)				NOT NULL	DEFAULT '',
	`post_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
	
	`poster_handle`			varchar(22)					NOT NULL	DEFAULT '',
	`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
	
	`body`					varchar(255)				NOT NULL	DEFAULT '',
	
	INDEX (`date_posted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
*/
echo SITE_HANDLE . " updated";