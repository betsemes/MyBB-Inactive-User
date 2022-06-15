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
 * Creates and accesses the inactive users data, the inactive usergroups,
 * and provides a user API.
 */
class inactiveUsers {
  
  /**
   * Creates the inactive users table if it still doesn't exist, and 
   * the inactive usergroups.
   * 
   * @param inactiveUserSettings $iu_settings A settings object.
   */
  public function __construct($iu_settings) 
  {
    //TODO: Eliminate the parameter. Create a local variable instead.
    global $db, $cache, $PL, $inactive_usergroups;
    $PL or require_once IUIUPLUGINLIBRARY;
    $inactive_usergroups or require_once MYBB_ROOT ."inc/plugins/inactive_user/usergroups_class.php";
    
    
   // Create inactive users table if it doesn't exist already
    if (!$db->table_exists('inactive_users'))
    {
      switch($db->type)
      {
      // only the "default" section is done
        case "pgsql": 
          $db->write_query("CREATE TABLE ".TABLE_PREFIX."inactive_users (
            mid serial,
            message varchar(100) NOT NULL default '',
            PRIMARY KEY (mid)
          );");
          break;
        case "sqlite":
          $db->write_query("CREATE TABLE ".TABLE_PREFIX."inactive_users (
            mid INTEGER PRIMARY KEY,
            message varchar(100) NOT NULL default ''
          );");
          break;
        default:
          $db->write_query("CREATE TABLE ".TABLE_PREFIX."inactive_users (
            uid int unsigned NOT NULL,
            deactdate int NOT NULL,
            deactmethod tinyint NOT NULL,
            oldgroup smallint NOT NULL,
            olddisplaygroup smallint NOT NULL,
            oldadditionalgroups varchar(200) NOT NULL,
            usertitle varchar(250),
            returndate int NOT NULL,
            UNIQUE KEY (uid)
          ) ENGINE=MyISAM{$collation};");
          break;
      }
    if(DEBUG) echo "exiting inactive users table creation<br>";
    }

       
    // if the usergroups are still not created, create them.
    if(DEBUG) echo "$inactive_usergroups->inactive: ". $inactive_usergroups->get_inactive() ."<br>";
    if(DEBUG) echo "$inactive_usergroups->self_ban: ". $inactive_usergroups->get_self_ban() ."<br>";
    $inactive_usergroups->add_inactive();
    $inactive_usergroups->add_self_ban();

  }
  
  //TODO: copy the code in inactive_user/inactive_user_ident.php here 
  // to replace the require_once. Delete inactive_user/inactive_user_ident.php
  /**
   * Identifies the users who have not visited for the configured 
   * amount of days.
   */
  public function identify($iu_settings) 
  {
    global $db;
    // Run the inactive user idetification script.
    require_once MYBB_ROOT . "inc/plugins/inactive_user/inactive_user_ident.php";
  }

  /**
   * Returns whether or not the specified user is inactive.
   *
   * Public API service function. Returns true if the specified user 
   * has not visited for the days amount configured into the settings.
   *
   * @param $user The username or user id.
   * @return boolean **TRUE**: the user specified in the parameter is inactive.
   * @api
   */
  public function is_inactive($user) 
  {
    //TODO: define the is_inactive() function
    // valid data types are integer and string. otherwise throw an error
    switch (gettype($user))
    {
      case 'string': //code for username
        break;
      case 'integer': //code for uid
        break;
      default: //throw error
    }
    return false; 
  }
  
  /**
   * Returns whether or not the specified user is active.
   *
   * Public API service function. Returns true if the specified user 
   * has visited within the period of days configured into the settings.
   *
   * @param $user The username or user id.
   * @return boolean **TRUE**: the user specified in the parameter is active.
   * @api
   */
  public function is_active($user)
  {
    return !is_inactive($user);
  }
  
/* 
 *TODO: Public API definition
 * The inactiveUserSettings class is not intended to have serviceable 
 * methods for the user. These accessors and mutators are to be used 
 * by the inactiveUsers class instead to provide these services. The 
 * user may use the inactiveUserSettings methods in his code; but it's 
 * not included as a public API, thus they may change without being 
 * considered backwards incompatible.
 * Add serviceable accessors and mutators for retrieving specific
 * settings for the public API.
 * In order to do this successfully, the general inactiveUserSettings 
 * accessor and mutator should be up to date.
 * reactivate_user()
 * deactivate_user()
 * delete_inactive_user()
 * get_deactdate()
 * get_deactmethod()
 * get_oldgroup()
 * get_olddisplaygroup()
 * get_oldadditionalgroups()
 * get_usertitle()
 * get_returndate()
 * set_deactdate()
 * set_deactmethod()
 * set_oldgroup()
 * set_olddisplaygroup()
 * set_oldadditionalgroups()
 * set_usertitle()
 * set_returndate()
 * get_inactivity_interval()
 * get_deletion_time()
 * get_reminders()
 * get_reminder_spacing()
 * get_include_nonverified_accounts()
 * get_include_away_users()
 * get_keep_tables()
 * set_inactivity_interval()
 * set_deletion_time()
 * set_reminders()
 * set_reminder_spacing()
 * set_include_nonverified_accounts()
 * set_include_away_users()
 * set_keep_tables()
 * Add serviceable accessors and mutators for retrieving specific data 
 * inactive users for the public API.
 * 
 */

}
