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

/**
 * Creates and accesses the inactive users table, the inactive usergroups,
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
    global $db, $cache, $PL;
    $PL or require_once PLUGINLIBRARY;
    
    
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
    echo "exiting inactive users table creation<br>";
    }

    require_once MYBB_ROOT ."inc\plugins\inactive_user\usergroups_class.php";
    // if the usergroups are still not created, create them.
    // echo "userGroups::$inactive: ". userGroups::$inactive ."<br>";
    if (userGroups::$inactive == 0)
    {
      // echo "Get the highest gid number within the usergroups table<br>";
      $max_gid = $db->fetch_field($db->simple_select("usergroups", $fields="max(gid) as gid"),"gid");
      settype($max_gid,"integer");
      // echo "calculated highest gid: ";
      // var_dump ($max_gid);
      // echo "<br />";
      userGroups::$inactive = $max_gid + 1;
      userGroups::$self_ban = $max_gid + 2;
      
      // echo 'Based on $max_gid, edit the usergroups into "inc\plugins\inactive_user\usergroups_class.php"<br>';
      $replacement = 'public static $inactive = ' .userGroups::$inactive. ', $self_ban = ' .userGroups::$self_ban. ';';
      // echo '<br />Replacement: "' .$replacement. '"<br />';
      // var_dump(
      $PL->edit_core (
        "inactive_user", 
        "inc\plugins\inactive_user\usergroups_class.php", 
        array(
          'search'  => 'public static $inactive = 0, $self_ban = 0;', 
          'replace'  => $replacement), 
        true,
        $debug);
        // );
      // echo "<br />";
      // print_r($debug);
      
      // echo "<br />edited gids into the userGroup class<br />";
      
      
      //Create the "inactive" usergroup. Do nothing if it already exists.
      //if the inactive usergroup does not exist... 
      //append the inactive and self-banned(to be called "self_banned_user_plugin" maybe) inactive usergroups to the database.

      // var_dump(userGroups::$inactive);
      // echo "<br>";
      // var_dump(userGroups::$self_ban);
      // echo "<br>";
      $inactive_usergroups = array(
        array(
          "gid" => userGroups::$inactive,
          "type" => 2,
          "title" => "Inactive User",
          "description" => "Users who have not being seen in more than " .$iu_settings->get("inactivityinterval"). " days.",
          "namestyle" => '<span style="color:#8c8c8c;">{username}</span>',
          "usertitle" => "Inactive",
          "stars" => 0,
          "starimage" => "images/star.png",
          "image" => "",
          "disporder" => 0,
          "isbannedgroup" => 0,
          "canview" => 0,
          "canviewthreads" => 0,
          "canviewprofiles" => 0,
          "candlattachments" => 0,
          "canviewboardclosed" => 0,
          "canpostthreads" => 0,
          "canpostreplys" => 0,
          "canpostattachments" => 0,
          "canratethreads" => 0,
          "modposts" => 0,
          "modthreads" => 0,
          "mod_edit_posts" => 0,
          "modattachments" => 0,
          "caneditposts" => 0,
          "candeleteposts" => 0,
          "candeletethreads" => 0,
          "caneditattachments" => 0,
          "canviewdeletionnotice" => 0,
          "canpostpolls" => 0,
          "canvotepolls" => 0,
          "canundovotes" => 0,
          "canusepms" => 0,
          "cansendpms" => 0,
          "cantrackpms" => 0,
          "candenypmreceipts" => 0,
          "pmquota" => 0,
          "maxpmrecipients" => 0,
          "cansendemail" => 0,
          "cansendemailoverride" => 0,
          "maxemails" => 1,
          "emailfloodtime" => 1,
          "canviewmemberlist" => 0,
          "canviewcalendar" => 0,
          "canaddevents" => 0,
          "canbypasseventmod" => 0,
          "canmoderateevents" => 0,
          "canviewonline" => 0,
          "canviewwolinvis" => 0,
          "canviewonlineips" => 0,
          "cancp" => 0,
          "issupermod" => 0,
          "cansearch" => 0,
          "canusercp" => 0,
          "canbeinvisible" => 1,
          "canuploadavatars" => 0,
          "canratemembers" => 0,
          "canchangename" => 0,
          "canbereported" => 0,
          "canchangewebsite" => 0,
          "showforumteam" => 0,
          "usereputationsystem" => 0,
          "cangivereputations" => 0,
          "candeletereputations" => 0,
          "reputationpower" => 0,
          "maxreputationsday" => 0,
          "maxreputationsperuser" => 0,
          "maxreputationsperthread" => 0,
          "candisplaygroup" => 0,
          "attachquota" => 0,
          "cancustomtitle" => 0,
          "canwarnusers" => 0,
          "canreceivewarnings" => 0,
          "maxwarningsday" => 0,
          "canmodcp" => 0,
          "showinbirthdaylist" => 0,
          "canoverridepm" => 0,
          "canusesig" => 0,
          "canusesigxposts" => 0,
          "signofollow" => 0,
          "edittimelimit" => 0,
          "maxposts" => 0,
          "showmemberlist" => 0,
          "canmanageannounce" => 0,
          "canmanagemodqueue" => 0,
          "canmanagereportedcontent" => 0,
          "canviewmodlogs" => 0,
          "caneditprofiles" => 0,
          "canbanusers" => 0,
          "canviewwarnlogs" => 0,
          "canuseipsearch" => 0
        ), array(
          "gid" => userGroups::$self_ban,
          "type" => 2,
          "title" => "Self-Banned User",
          "description" => "Users who have banned themselves in the process of deactivating.",
          "namestyle" => '<span style="color:#8c8c8c; text-decoration-line: line-through;">{username}</span>',
          "usertitle" => "Inactive",
          "stars" => 0,
          "starimage" => "images/star.png",
          "image" => "",
          "disporder" => 0,
          "isbannedgroup" => 1,
          "canview" => 0,
          "canviewthreads" => 0,
          "canviewprofiles" => 0,
          "candlattachments" => 0,
          "canviewboardclosed" => 0,
          "canpostthreads" => 0,
          "canpostreplys" => 0,
          "canpostattachments" => 0,
          "canratethreads" => 0,
          "modposts" => 0,
          "modthreads" => 0,
          "mod_edit_posts" => 0,
          "modattachments" => 0,
          "caneditposts" => 0,
          "candeleteposts" => 0,
          "candeletethreads" => 0,
          "caneditattachments" => 0,
          "canviewdeletionnotice" => 0,
          "canpostpolls" => 0,
          "canvotepolls" => 0,
          "canundovotes" => 0,
          "canusepms" => 0,
          "cansendpms" => 0,
          "cantrackpms" => 0,
          "candenypmreceipts" => 0,
          "pmquota" => 0,
          "maxpmrecipients" => 0,
          "cansendemail" => 0,
          "cansendemailoverride" => 0,
          "maxemails" => 1,
          "emailfloodtime" => 1,
          "canviewmemberlist" => 0,
          "canviewcalendar" => 0,
          "canaddevents" => 0,
          "canbypasseventmod" => 0,
          "canmoderateevents" => 0,
          "canviewonline" => 0,
          "canviewwolinvis" => 0,
          "canviewonlineips" => 0,
          "cancp" => 0,
          "issupermod" => 0,
          "cansearch" => 0,
          "canusercp" => 0,
          "canbeinvisible" => 1,
          "canuploadavatars" => 0,
          "canratemembers" => 0,
          "canchangename" => 0,
          "canbereported" => 0,
          "canchangewebsite" => 0,
          "showforumteam" => 0,
          "usereputationsystem" => 0,
          "cangivereputations" => 0,
          "candeletereputations" => 0,
          "reputationpower" => 0,
          "maxreputationsday" => 0,
          "maxreputationsperuser" => 0,
          "maxreputationsperthread" => 0,
          "candisplaygroup" => 0,
          "attachquota" => 0,
          "cancustomtitle" => 0,
          "canwarnusers" => 0,
          "canreceivewarnings" => 0,
          "maxwarningsday" => 0,
          "canmodcp" => 0,
          "showinbirthdaylist" => 0,
          "canoverridepm" => 0,
          "canusesig" => 0,
          "canusesigxposts" => 0,
          "signofollow" => 0,
          "edittimelimit" => 0,
          "maxposts" => 0,
          "showmemberlist" => 0,
          "canmanageannounce" => 0,
          "canmanagemodqueue" => 0,
          "canmanagereportedcontent" => 0,
          "canviewmodlogs" => 0,
          "caneditprofiles" => 0,
          "canbanusers" => 0,
          "canviewwarnlogs" => 0,
          "canuseipsearch" => 0
        )
      );
      
      // add the usergroups to the usergroups table
      // echo "inserting inactive_usergroups<br>";
      $db->insert_query_multiple("usergroups", $inactive_usergroups);
      // update the cache
      // echo "updating the cache<br>";
      $cache->update_usergroups();
      
    }

  }
  
  public function identify($iu_settings) 
  {
    global $db;
    // Run the inactive user idetification script.
    require_once MYBB_ROOT . "inc/plugins/inactive_user/inactive_user_ident.php";
  }

  /**
   * Returns whether or not the specified user is inactive.
   *
   * Public API service function. Returns true if the specified user has not visited
   * for the days amount configured into the settings.
   *
   * @param $user The username or user id.
   * @return boolean Whether or not the user specified in the parameter is inactive.
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
   * Public API service function. Returns true if the specified user has visited
   * within the period of days configured into the settings.
   *
   * @param $user The username or user id.
   * @return boolean Whether or not the user specified in the parameter is active.
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
 * getter and setter should be up to date.
 * get_inactivity_interval
 * get_deletion_time
 * get_reminders
 * get_reminder_spacing
 * get_include_nonverified_accounts
 * get_include_away_users
 * get_keep_tables
 * set_inactivity_interval
 * set_deletion_time
 * set_reminders
 * set_reminder_spacing
 * set_include_nonverified_accounts
 * set_include_away_users
 * set_keep_tables
 * Add serviceable accessors and mutators for retrieving specific data 
 * inactive users for the public API.
 * 
 */
 
}
