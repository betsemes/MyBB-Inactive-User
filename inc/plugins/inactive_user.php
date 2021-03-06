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
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
 
/* ./inc/plugins/inactive_user.php 
   Plugin core file containing the basic functions called by MyBB.*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

if (!defined("DEBUG"))
{
  define("DEBUG", false);
}

if(!defined("IUIUPLUGINLIBRARY"))
{
  define("IUIUPLUGINLIBRARY", MYBB_ROOT."inc/plugins/inactive_user/pluginlibrary.php");
}

require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveusersettings_class.php";
require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveuser_class.php";
require_once MYBB_ROOT . "inc/plugins/inactive_user/usergroups_class.php";

$plugins->add_hook('datahandler_login_complete_end', 'user_reactivate');

/**
 * Returns the information about the plugin that MyBB expects.
 *
 * @internal Called by MyBB.
 */
function inactive_user_info()
{
	return array(
		"name"          => "Inactive User",
		"description"   => "Monitors user activity, moves inactive users to the 'inactive users' usergroup, moves them back to their previous usergroup upon logging back in, and prunes long inactive users.",
		"website"       => "/index.html",
		"author"        => "Betsemes",
		"authorsite"    => "mailto:betsemes@gmail.com",
		"version"       => "0.1.1-alpha.1",
		"codename"      => "inactive_user",
		"compatibility" => "1830"
	);
}

/**
 * Installs the plugin. Creates the settings, and the inactive users table.
 *
 * @internal Called by MyBB.
 */
function inactive_user_install()
{
	if(!file_exists(IUIUPLUGINLIBRARY))
  {
    flash_message("PluginLibrary is missing.", "error");
    admin_redirect("index.php?module=config-plugins");
  }

  // Create settings or load them from the database
	$iu_settings = new inactiveUserSettings();
	
  if(DEBUG) echo "entering inactive users table creation<br>";
  //Create the inactive users table
  $inactives = new inactiveUsers($iu_settings);
  
  // Run the inactive user idetification method.
  $inactives->identify($iu_settings);
  
}

/**
 * Returns true if the settings exists, false otherwise.
 *
 * @internal
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
 * Deletes the inactive usergroups and settings. Drops the tables.
 *
 * @internal Called by MyBB.
 */
function inactive_user_uninstall()
{
  global $db, $cache, $PL;
  $PL or require_once IUIUPLUGINLIBRARY;

  if(DEBUG) echo 'Delete the inactive usergroups.<br>';
  $iu_usergroups = new userGroups();
  $iu_usergroups->delete_inactive();
  $iu_usergroups->delete_self_ban();
  
  $iu_settings = new inactiveUserSettings();
  
  if(DEBUG) echo 'Drop the tables.<br>';
  if (!$iu_settings->get('keeptables'))
  {
    $db->drop_table('inactive_users');
  }
  
  if(DEBUG) echo 'Delete the settings<br>';
  $iu_settings->delete_settings();
  
  $PL->edit_core (
      "inactive_user", 
      "inc/plugins/inactive_user/usergroups_class.php", 
      array(), 
      true);
  
}

/**
 * Assigns the inactive usergroup to users in the inactive users table.
 * Enables the task.
 *
 * @internal Called by MyBB.
 */
function inactive_user_activate()
{
  global $db, $PL, $inactive_usergroups;
  $inactive_usergroups or require_once MYBB_ROOT ."inc/plugins/inactive_user/usergroups_class.php";
  if(DEBUG) echo "loading pluginlibrary<br>";
  $PL or require_once IUIUPLUGINLIBRARY;
  
  if(DEBUG) echo "loading usergroups_class<br>";
  
  if(DEBUG) echo "activating...<br>";
  if(DEBUG) echo 'get inactive users data<br>';
  $inactives = $db->simple_select('inactive_users', '*');
  
  if(DEBUG) echo 'Assign the inactive usergroups: ';
  if(DEBUG) echo 'self-ban: '. $inactive_usergroups->get_self_ban(). ' ';
  if(DEBUG) echo 'inactive: '. $inactive_usergroups->get_inactive(). '<br>';
  while($inactive = $db->fetch_array($inactives))
  {
    $gid = $inactive['deactmethod'] == 3 
      ? $inactive_usergroups->get_self_ban() 
      : $inactive_usergroups->get_inactive();
    $db->update_query("users", 
      array( 
        "usergroup" => $gid, 
        "displaygroup" => 0),
      'uid=' .$inactive['uid']
    );    
  }
  
  if(DEBUG) echo "Schedule the 'inactive_user' task.<br>";
  $PL->tasks(array(
    'title' => 'Inactive User Identification',
    'description' => 'Identifies users who have stopped visiting.',
    'file' => 'inactive_user',
    'minute' => '*'
    )
  );
}

/**
 * Deletes the task and restores usergroups
 *
 * @internal Called by MyBB.
 */
function inactive_user_deactivate()
{
  global $db, $PL;
  $PL or require_once IUIUPLUGINLIBRARY;
  
  if(DEBUG) echo "Unschedule the 'inactive_user' task.<br>";
  $PL->tasks_delete('inactive_user');
  
  if(DEBUG) echo 'restore the original usergroups to users.<br>';
  // get usergroups and displaygroups for all inactive users
  $inactives = $db->simple_select('inactive_users', '*');
  
  if(DEBUG) echo 'loop through the users table, for each inactive user in the users table, assign the usergroup and displaygroup gotten from the inactive users table<br>';
  while($inactive = $db->fetch_array($inactives))
  {
    $db->update_query('users',
      array(
        'usergroup'=>$inactive['oldgroup'],
        'displaygroup'=>$inactive['olddisplaygroup']),
      'uid=' .$inactive['uid']
    );
  }
  
}

/**
 * Sets a user back to active status.
 * 
 * This function is intended to be hooked to the 
 * *datahandler_login_complete_end* MyBB hook to change the user status 
 * back to active. Do not confuse with the _activate or _deactivate 
 * functions intended for activating/deactivating
 * the plugin itself.
 *
 * @param $handler Data handler provided by the runhooks MyBB function.
 * @internal Called by MyBB.
 */
function user_reactivate($handler)
{
  global $db;
  
  // Restore the usergroups from the inactive users table
  $data = $db->fetch_array($db->simple_select(
    'inactive_users', "*", "uid=" .$handler->login_data['uid']));

  // Set the usergroups in the users table
  $db->update_query('users', array(
    'usergroup' => $data['oldgroup'],
    'displaygroup' => $data['olddisplaygroup'],
    'additionalgroups' => $data['oldadditionalgroups'],
    'usertitle' => $data['usertitle']
    ), 'uid=' .$handler->login_data['uid']);

  // Delete the user from the inactive users table
  $db->delete_query('inactive_users','uid=' .$handler->login_data['uid']);
}
