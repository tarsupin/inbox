<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppThread_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppThread";
	public $title = "Thread Handler";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides tools for working with folder threads.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `threads`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`title`					varchar(48)					NOT NULL	DEFAULT '',
			
			`owner_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`last_poster_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`posts`					mediumint(8)	unsigned	NOT NULL	DEFAULT '0',
			
			`date_created`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_last_post`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(`id`) PARTITIONS 121;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `threads_users`
		(
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`thread_id`, `uni_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(`thread_id`) PARTITIONS 31;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("threads", array("id", "title"));
		$pass2 = DatabaseAdmin::columnsExist("threads_users", array("thread_id", "uni_id"));
		
		return ($pass1);
	}
	
}