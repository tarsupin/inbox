<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppPost_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppPost";
	public $title = "Post Handler";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides tools for working with posts.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `posts`
		(
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`id`					int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`avi_id`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			`body`					text						NOT NULL	DEFAULT '',
			`date_post`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`thread_id`, `id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(thread_id) PARTITIONS 121;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `posts_recent`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_posted`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`thread_title`			varchar(72)					NOT NULL	DEFAULT '',
			`thread_posts`			mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			
			`post_link`				varchar(120)				NOT NULL	DEFAULT '',
			`post_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`poster_handle`			varchar(22)					NOT NULL	DEFAULT '',
			
			`body`					varchar(255)				NOT NULL	DEFAULT '',
			
			INDEX (`uni_id`, `date_posted`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY (`uni_id`) PARTITIONS 13;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("posts", array("id", "thread_id"));
		$pass2 = DatabaseAdmin::columnsExist("posts_recent", array("date_posted", "post_id"));
		
		return ($pass1 and $pass2);
	}
	
}