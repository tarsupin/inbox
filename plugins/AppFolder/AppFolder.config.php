<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppFolder_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppFolder";
	public $title = "Folder Tools";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides methods to work with inbox folders.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `folders`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`folder_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`sort_order`			tinyint(2)		unsigned	NOT NULL	DEFAULT '0',
			
			`title`					varchar(32)					NOT NULL	DEFAULT '',
			`description`			varchar(128)				NOT NULL	DEFAULT '',
			
			`unread`				smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			
			`last_poster`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_lastPost`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `folder_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY (`uni_id`) PARTITIONS 61;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `folders_threads`
		(
			`folder_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_last_post`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`is_read`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`folder_id`, `thread_id`),
			INDEX (`folder_id`, `date_last_post`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY (`folder_id`) PARTITIONS 61;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("folders", array("uni_id", "folder_id"));
		$pass2 = DatabaseAdmin::columnsExist("folders_threads", array("folder_id", "thread_id"));
		
		return ($pass1 and $pass2);
	}
	
}