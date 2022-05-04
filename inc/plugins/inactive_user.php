<?php 
/**
 * Inactive User 0.1 plugin for MyBB 1.8.x
 *
 * A simple system for the identification of inactive users for MyBB. It involves:
 * - identification of users who have not visited for a certain amount of time
 * - provides a way for users to deactivate their accounts
 *
 * @author  Betsemes <betsemes@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
 */

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
	$settings = new inactiveUserSettings();
	
  echo "entering inactive users table creation<br>";
  //Create the inactive users table
  new inactiveUsers($settings);
}

/**
 * Returns true if the inactive users table exists, false otherwise.
 */
function inactive_user_is_installed()
{
  global $db;

  //DONE: _is_installed() function: This must be changed when the plugin changes to keep tables after uninstallation.
  // An "installed" setting must be added to signal whether or not the plugin is not installed. The inactive users table is intended to be created along with the settings. If that table do exist, check that setting.

  $query = $db->simple_select("settinggroups", "*", "name='inactive_user'", array(
    "order_by" => 'name',
    "order_dir" => 'DESC',
    "limit" => 1
  ));

  $settings = $db->fetch_array($query);

	/*
  echo "<pre>";
  print_r($settings);
  echo "</pre>";
*/
  
  // If the inactive_user setting group exists then it means the plugin is installed because we only delete it on uninstallation
  return $settings['name'] == 'inactive_user';
  // If the table exists then it means the plugin is installed because we only drop it on uninstallation (no longer true)
	// return $db->table_exists('inactive_users');
}

/**
 * Deletes the inactive usergroups. Drops the tables.
 */
function inactive_user_uninstall()
{
  global $db, $cache;
  
  $settings = new inactiveUserSettings();
  
  // Delete the inactive usergroups.
  $settings->delete_usergroups();
  
  // Delete the settings
  $settings->delete_settings();
  
  // Drop the tables.
  $db->drop_table('inactive_user_settings');
  $db->drop_table('inactive_users');
  
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
