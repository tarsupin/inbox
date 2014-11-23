<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// You must be logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/"); exit;
}

// Run the Form
if(Form::submitted("settings-inbox"))
{
	FormValidate::text("Signature", $_POST['signature'], 0, 20000, chr(13) . "
");
	
	if(FormValidate::pass())
	{
		AppSettings::updateSignature(Me::$id, $_POST['signature']);
		
		Alert::success("Signature Updated", "You have successfully updated your settings.");
	}
}

// Run Global Script
require(CONF_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display();

// Display your list of avatars available
echo '
<div class="overwrap-box">
	<div class="overwrap-line" style="margin-bottom:10px;">
		<div class="overwrap-name">My Avatars</div>
	</div>
	<div class="inner-box">
	Test
	</div>
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
