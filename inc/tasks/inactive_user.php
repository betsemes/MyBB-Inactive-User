<?php 
/**
 * Inactive User 0.1 plugin for MyBB 1.8.x
 *
 * A simple system for the identification of inactive users for MyBB. It involves:
 * - identification of users who have not visited for a certain amount of time
 * - provides a way for users to deactivate their accounts
 *
 * @author  Betsemes <betsemes@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

/**
 * Task function to schedule the identification of inactive users.
 *
 * @internal
 */
function task_inactive_user($task)
{
  if(DEBUG) echo "inside task function<br>";

	// task operations
	if(DEBUG) echo "creating settings object...<br>";
  $iu_settings = new inactiveUserSettings();
  if(DEBUG) echo "creating inactiveUsers object...<br>";
  $inusers = new inactiveUsers($iu_settings);
  if(DEBUG) echo "identifying...<br>";
  $inusers->identify($iu_settings);
  if(DEBUG) echo "exited identification method<br>";

	add_task_log($task, "The Inactive User Identification task successfully ran.");
  if(DEBUG) echo "task logged<br>";
}
