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

// TODO: Add requirements list and installation/uninstallation instructions.
// I need to resolve the PluginLibrary problem. Maybe redirect interested users to my PluginLibrary clone repo while it's still not being pulled into the original repo.
if(!defined("PLUGINLIBRARY"))
{
  define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveusersettings_class.php";
require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveuser_class.php";

// It should be the place where the user has been successfully logged in.
// The right hook is datahandler_login_complete_end as per this post: https://community.mybb.com/thread-235652-post-1377346.html#pid1377346
// This post: https://community.mybb.com/thread-170142-post-1155681.html#pid1155681 has useful information.
$plugins->add_hook('datahandler_login_complete_end', 'user_reactivate');

function inactive_user_info()
{
	return array(
		"name"          => "Inactive User",
		"description"   => "Monitors user activity, moves inactive users to the 'inactive users' usergroup, moves them back to their previous usergroup upon logging back in, and prunes long inactive users.",
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
  $inactives = new inactiveUsers($iu_settings);
  
  // Run the inactive user idetification method.
  $inactives->identify($iu_settings);
  
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
  
  // Drop the tables.
  // $db->drop_table('inactive_user_settings');
  if (!$iu_settings->get('keeptables'))
  {
    $db->drop_table('inactive_users');
  }
  
  // Delete the settings
  $iu_settings->delete_settings();
  
  $PL->edit_core (
      "inactive_user", 
      "inc\plugins\inactive_user\usergroups_class.php", 
      array(), 
      true);
  
}

function inactive_user_activate()
{
  global $db, $PL;
  $PL or require_once PLUGINLIBRARY;
  
  require_once MYBB_ROOT ."inc\plugins\inactive_user\usergroups_class.php";
  
  // get inactive users data
  $inactives = $db->simple_select('inactive_users', '*');
  
  // Assign the inactive usergroups
  while($inactive = $db->fetch_array($inactives))
  {
    $gid = $inactive['deactmethod'] == 3 
      ? userGroups::$self_ban 
      : userGroups::$inactive;
    $db->update_query("users", 
      array( 
        "usergroup" => $gid, 
        "displaygroup" => 0),
      'uid=' .$inactive['uid']
    );    
  }
  
  // Schedule the 'inactive_user' task.
  $PL->tasks(array(
    'title' => 'Inactive User Identification',
    'description' => 'Identifies users who have stopped visiting.',
    'file' => 'inactive_user',
    'minute' => '*'
    )
  );
}

function inactive_user_deactivate()
{
  global $db, $PL;
  $PL or require_once PLUGINLIBRARY;
  
  // Unschedule the 'inactive_user' task.
  $PL->tasks_delete('inactive_user');
  
  // restore the original usergroups to users.
  // get usergroups and displaygroups for all inactive users
  $inactives = $db->simple_select('inactive_users', '*');
  
  // loop through the users table, for each inactive user in the users table, assign the usergroup and displaygroup gotten from the inactive users table
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
 * This function hooks to the datahandler_login_complete_end MyBB hook
 * to change the user status back to active. Do not confuse with the 
 * _activate or _deactivate functions intended for activating/deactivating
 * the plugin itself.
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
