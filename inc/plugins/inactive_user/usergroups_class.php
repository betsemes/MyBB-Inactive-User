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

/**
 * Only holds the usergroup IDs assigned to the inactive usergroups.
 */
class userGroups 
{
  
	// Usergroup constants;
  
  const INACTIVE = 0; const SELF_BAN = 0;
  
}