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

function task_inactive_user($task)
{
  echo "inside task function<br>";

	// task operations
	echo "creating settings object...<br>";
  $iu_settings = new inactiveUserSettings();
  echo "creating inactiveUsers object...<br>";
  $inusers = new inactiveUsers($iu_settings);
  echo "identifying...<br>";
  $inusers->identify($iu_settings);
  echo "exited identification method<br>";

	add_task_log($task, "The Inactive User Identification task successfully ran.");
  echo "task logged<br>";
}
