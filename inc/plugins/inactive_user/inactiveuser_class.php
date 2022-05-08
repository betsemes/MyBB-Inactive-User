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
 * Creates and accesses the inactive users table.
 */
class inactiveUsers {
  
  /**
   * Creates the inactive users table if it still doesn't exist, the inactive usergroups.
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
    }
    echo "exiting inactive users table creation<br>";

    // Get the highest gid number within the usergroups table
    $max_gid = $db->fetch_field($db->simple_select("usergroups", $fields="max(gid) as gid"),"gid");
    settype($max_gid,"integer");
    echo "calculated highest gid: ";
    var_dump ($max_gid);
    echo "<br />";
    
    // Based on $max_gid, edit the usergroups into "inc\plugins\inactive_user\usergroups_class.php"
    $replacement = 'const INACTIVE = '. ($max_gid + 1) .'; const SELF_BAN = '. ($max_gid + 2) .';';
    echo '<br />Replacement: "' .$replacement. '"<br />';
    var_dump($PL->edit_core (
      "inactive_user", 
      "inc\plugins\inactive_user\usergroups_class.php", 
      array(
        'search'  => 'const INACTIVE = 0; const SELF_BAN = 0;', 
        'replace'  => $replacement), 
      true,
      $debug));
    echo "<br />";
    print_r($debug);
    
    echo "<br />edited gids into the userGroup class<br />";
    //TODO: undo these changes after deleting the usergroups on the uninstall function
    
    require_once MYBB_ROOT ."inc\plugins\inactive_user\usergroups_class.php";
    
    //Create the "inactive" usergroup. Do nothing if it already exists.
    //if the inactive usergroup does not exist... 
    //append the inactive and self-banned(to be called "self_banned_user_plugin" maybe) inactive usergroups to the database.

    var_dump(userGroups::INACTIVE);
    echo "<br>";
    var_dump(userGroups::SELF_BAN);
    echo "<br>";
    $inactive_usergroups = array(
      array(
        "gid" => userGroups::INACTIVE,
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
        "gid" => userGroups::SELF_BAN,
        "type" => 2,
        "title" => "Self-Banned User",
        "description" => "Users who have banned themselves in the process of deactivating.",
        "namestyle" => '<span style="color:#8c8c8c; text-decoration-line: line-through;">{username}</span>',
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
      )
    );
    
    // add the usergroups to the usergroups table
    echo "inserting inactive_usergroups<br>";
    $db->insert_query_multiple("usergroups", $inactive_usergroups);
    // update the cache
    echo "updating the cache<br>";
    $cache->update_usergroups();
    
    // Run the inactive user idetification script.
    require_once MYBB_ROOT . "inc/plugins/inactive_user/inactive_user_ident.php";

    //TODO: Assign the "inactive" usergroup to the newly identified inactive user's primary and display.
    //update the primary and display usergroups to "inactive", and "additional" to an empty string with an update query with the user list gotten above.
    
    
  }
}

