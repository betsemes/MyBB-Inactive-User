<?php 
/**
 * Inactive User 0.1 plugin for MyBB 1.8.x
 *
 * A simple system for the identification of inactive users for MyBB. It involves:
 * - identification of users who have not visited for a certain amount of time
 * - provides a way for users to deactivate their accounts
 * 
 *
 * @author  Betsemes <betsemes@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
 */
 
/* ./inc/plugins/inactive_user.php 
   Plugin core file containing the basic functions called by MyBB.*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

if(!defined("PLUGINLIBRARY"))
{
  define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveusersettings_class.php";
require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveuser_class.php";

function inactive_user_info()
{
	return array(
		"name"          => "Inactive User",
		"description"   => "Monitors user activity, moves inactive users to the 'inactive users' usergroup, moves them back to their previous usergroup upon signing up, and prunes long inactive users.",
		"website"       => "/index.html",
		"author"        => "Betsemes",
		"authorsite"    => "mailto:betsemes@gmail.com",
		"version"       => "0.1.0-alpha",
		"codename"      => "inactive_user",
		"compatibility" => "1830"
	);
}

/**
 * Creates the settings and inactive users tables.
 */
function inactive_user_install()
{
	if(!file_exists(PLUGINLIBRARY))
  {
    flash_message("PluginLibrary is missing.", "error");
    admin_redirect("index.php?module=config-plugins");
  }

  // Create settings or load them from the database
	$iu_settings = new inactiveUserSettings();
	
  echo "entering inactive users table creation<br>";
  //Create the inactive users table
  new inactiveUsers($iu_settings);
}

/**
 * Returns true if the inactive users table exists, false otherwise.
 */
function inactive_user_is_installed()
{
  global $settings;

  // This plugin creates settings on install. Check if setting exists.
  if(isset($settings['inactive_user_inactivityinterval']))
  {
    return true;
  }
  return false;
   
}

/**
 * Deletes the inactive usergroups. Drops the tables.
 */
function inactive_user_uninstall()
{
  global $db, $cache, $PL;
  $PL or require_once PLUGINLIBRARY;

  $iu_settings = new inactiveUserSettings();
  
  // Delete the inactive usergroups.
  $iu_settings->delete_usergroups();
  
  // Delete the settings
  $iu_settings->delete_settings();
  
  // Drop the tables.
  // $db->drop_table('inactive_user_settings');
  $db->drop_table('inactive_users');
  
  $PL->edit_core (
      "inactive_user", 
      "inc\plugins\inactive_user\usergroups_class.php", 
      array(), 
      true);
  
}

function inactive_user_activate()
{
  //TODO: mark each user in the inactive user table with the inactive usergroup.
  //TODO: schedule the inactive user identification script.
}

function inactive_user_deactivate()
{
  //TODO: unschedule the inactive user identification script.
  //TODO: restore the original usergroups to users.
}
