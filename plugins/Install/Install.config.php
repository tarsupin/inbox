<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class Install_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "install";
	public $pluginName = "Install";
	public $title = "Forum Installer";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides installation processes for UniFaction's Forum system.";
	
	public $data = array();
	
}