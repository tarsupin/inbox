<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppSettings Class ------
-----------------------------------------



-------------------------------
------ Methods Available ------
-------------------------------


*/

abstract class AppSettings {
	
	
/****** Get a user's signature ******/
	public static function getSettings
	(
		int $uniID			// <int> The UniID of the user to retrieve the signature of.
	,	bool $orig = false	// <bool> TRUE if you're retrieving the original (no markup).
	): array <str, str>					// RETURNS <str:str> The settings for the user.
	
	// $signature = AppSettings::getSettings($uniID, [$orig]);
	{
		return Database::selectOne("SELECT signature" . ($orig ? "_orig" : "") . " as signature, avatar_list FROM settings WHERE uni_id=? LIMIT 1", array($uniID));
	}
	
	
/****** Update a user's signature ******/
	public static function updateSignature
	(
		int $uniID		// <int> The UniID of the user to retrieve the signature of.
	,	string $signature	// <str> The signature to set for the user.
	): bool				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppSettings::updateSignature($uniID, $signature);
	{
		return Database::query("INSERT INTO settings (uni_id, signature, signature_orig) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE uni_id=?, signature=?, signature_orig=?", array($uniID, UniMarkup::parse($signature), $signature, $uniID, UniMarkup::parse($signature), $signature));
	}
	
}