<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppSettings_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppSettings";
	public $title = "Inbox Settings API";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides settings that you can work with.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `settings`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			`signature`				text						NOT NULL	DEFAULT '',
			`signature_orig`		text						NOT NULL	DEFAULT '',
			`avatar_list`			varchar(255)				NOT NULL	DEFAULT '',
			
			UNIQUE (`uni_id`)
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
		$pass1 = DatabaseAdmin::columnsExist("settings", array("uni_id", "signature"));
		
		return ($pass1);
	}
	
}