<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Forum Installation
abstract class Install extends Installation {
	
	
/****** Plugin Variables ******/
	public static array <str, bool> $addonPlugins = array(		// <str:bool>
		"Avatar"			=> true
	,	"FeaturedWidget"	=> true
	,	"Notifications"		=> true
	);
	
	
/****** App-Specific Installation Processes ******/
	public static function setup(
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	{
		// Add UniqueID Trackers
		UniqueID::newCounter("folder");
		UniqueID::newCounter("post");
		
		return true;
	}
}