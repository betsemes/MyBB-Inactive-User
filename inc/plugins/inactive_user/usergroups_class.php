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

require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveusersettings_class.php";

if(DEBUG) echo "creating userGroups class<br>";
/**
 * Creates and manages the usergroups created to assign inactive users to.
 *
 * @api
 */
class userGroups 
{
  //TODO: The constructor should also create the usergroups.
  /**
   * File holding the calculated and assigned inactive gids.
   *
   * The inactive_user.xml file keeps configuration data not intended
   * for access from MyBB admincp, thus, not intended to be changed 
   * once it's set.
   * @internal
   */
  const INACTIVE_USER_XML = MYBB_ROOT.'inc/plugins/inactive_user/inactive_user.xml';
  // const INACTIVE_USER_XML = '../repos/inactive_user/inc/plugins/inactive_user/inactive_user.xml';
  const INACTIVE_USER_XML_CONTENT = <<<'XMLCONTENT'
  <inactive-user>
    <usergroups>
      <inactive>0</inactive>
      <self-ban>0</self-ban>
    </usergroups>
  </inactive-user>
XMLCONTENT;
  
	// Usergroup properties
  
  public $inactive = 0, $self_ban = 0;
  
  public function __construct ($inactive=0, $self_ban=0) 
  {
    if(DEBUG) echo "userGroups constructor: loading xml<br>";
    $inactive_user = $this->load();
      
    if(DEBUG) echo "userGroups constructor: setting gids<br>";
    if ($inactive != 0) $inactive_user->usergroups->inactive = $inactive;
    if ($self_ban != 0) $inactive_user->usergroups->{'self-ban'} = $self_ban;
    
    if(DEBUG) echo "userGroups constructor: saving xml<br>";
    $inactive_user->saveXML(self::INACTIVE_USER_XML);
  
    if(DEBUG) echo "userGroups constructor: exiting...<br>";
  }
  
  // accessors and mutators
  
  public function get_inactive()
  {
    //if inactive usergroup is zero...
    // generate the next gid
    // if self ban == next gid, then set inactive to gid + 1 
    // and return it.
    if(DEBUG) echo "get_inactive(): loading...<br>";
    $inactive_user = $this->load();
    if(DEBUG) echo "get_inactive(): xml loaded<br>";
    if ($inactive_user->usergroups->inactive == 0)
    {
      if ($inactive_user->usergroups->{'self-ban'} == self::max_gid() + 1)
      {
        $inactive_user->usergroups->inactive = self::max_gid() + 2;
      }
      else
      {
        $inactive_user->usergroups->inactive = self::max_gid() + 1;
      }
      $inactive_user->saveXML(self::INACTIVE_USER_XML);
    }
    return $inactive_user->usergroups->inactive;
  }
  
  public function set_inactive($inactive=0)
  {
    $inactive_user = $this->load();
    if ($inactive == 0)
    {
      if ($inactive_user->usergroups->inactive == 0)
      {
        if ($inactive_user->usergroups->{'self-ban'} == self::max_gid() + 1)
        {
          $inactive_user->usergroups->inactive = self::max_gid() + 2;
        }
        else
        {
          $inactive_user->usergroups->inactive = self::max_gid() + 1;
        }
        $inactive_user->saveXML(self::INACTIVE_USER_XML);
      }
    }
    else
    {
      $inactive_user->usergroups->inactive = $inactive;
    }
    $inactive_user->saveXML(self::INACTIVE_USER_XML);
  }
  
  public function get_self_ban()
  {
    $inactive_user = $this->load();
    if ($inactive_user->usergroups->{'self-ban'} == 0)
    {
      if ($inactive_user->usergroups->inactive == self::max_gid() + 1)
      {
        $inactive_user->usergroups->{'self-ban'} = self::max_gid() + 2;
      }
      else
      {
        $inactive_user->usergroups->{'self-ban'} = self::max_gid() + 1;
      }
      $inactive_user->saveXML(self::INACTIVE_USER_XML);
    }
    return $inactive_user->usergroups->{'self-ban'};
  }
  
  public function set_self_ban($self_ban=0)
  {
    $inactive_user = $this->load();
    if ($self_ban == 0)
    {
      if ($inactive_user->usergroups->{'self-ban'} == 0)
      {
        if ($inactive_user->usergroups->inactive == self::max_gid() + 1)
        {
          $inactive_user->usergroups->{'self-ban'} = self::max_gid() + 2;
        }
        else
        {
          $inactive_user->usergroups->{'self-ban'} = self::max_gid() + 1;
        }
        $inactive_user->saveXML(self::INACTIVE_USER_XML);
      }
    }
    else
    {
      $inactive_user->usergroups->{'self-ban'} = $self_ban;
    }
  }
  
  /**
   * Sets the inactive usergroup id to zero.
   */
  public function reset_inactive()
  {
    $inactive_user = $this->load();
    $inactive_user->usergroups->inactive = 0;
    $inactive_user->saveXML(self::INACTIVE_USER_XML);
  }
  
  /**
   * Sets the self ban usergroup id to zero.
   */
  public function reset_self_ban()
  {
    $inactive_user = $this->load();
    $inactive_user->usergroups->{'self-ban'} = 0;
    $inactive_user->saveXML(self::INACTIVE_USER_XML);
  }
  
  // add/delete usergroups from the database
  // consider tagging the inactive usergroups with a span tag with a data- attribute
  // <span data-inactive-user-group="inactive">usergroup description</span>
  public static function max_gid ()
  {
    global $db;
    
    $max_gid = $db->fetch_field(
      $db->simple_select(
        "usergroups", 
        $fields="max(gid) as gid"),
      "gid");
    settype($max_gid,"integer");
    return $max_gid;
  }
  
  /**
   * Adds the Inactive usergroup to the usergroups table.
   */
  public function add_inactive ()
  {
    global $db, $cache;
    
    $iu_settings = new inactiveUserSettings();
    
    //Create the "inactive" usergroup. Do nothing if it already exists.
    //if the inactive usergroup does not exist... 
    //append the inactive and self-banned usergroups to the database.

    //TODO: Look for trimming the following array for simplification.
    // Most of those fields are holding the default value defined 
    // in the usergroups table. 
    if(DEBUG) echo "add_inactive(): creating inactive usergroup array.<br>";
    $inact_usergroups = array(
      "gid" => $this->get_inactive(),
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
      );
      
      // add the usergroups to the usergroups table
      if(DEBUG) echo "add_inactive(): inserting inactive_usergroup<br>";
      $db->insert_query("usergroups", $inact_usergroups);
      // update the cache
      if(DEBUG) echo "add_inactive(): updating the cache<br>";
      $cache->update_usergroups();
  }
  
  /**
   * Adds the Self-ban usergroup to the usergroups table.
   */
  public function add_self_ban ()
  {
    global $db, $cache;
    
    //Create the "inactive" usergroup. Do nothing if it already exists.
    //if the inactive usergroup does not exist... 
    //append the inactive and self-banned usergroups to the database.

    // $inactive_usergroups = $this->load();
    //TODO: Look for trimming the following array for simplification.
    // Most of those fields are holding the default value defined 
    // in the usergroups table. 
    if(DEBUG) echo "add_self_ban(): creating inactive usergroup array.<br>";
    $inact_usergroups = array(
      "gid" => $this->get_self_ban(),
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
      );
      
      // add the usergroups to the usergroups table
      if(DEBUG) echo "add_self_ban(): inserting inactive_usergroup<br>";
      $db->insert_query("usergroups", $inact_usergroups);
      // update the cache
      if(DEBUG) echo "add_self_ban(): updating the cache<br>";
      $cache->update_usergroups();
  }
  
  /**
   * Deletes the Inactive usergroup added to the usergroups table.
   */
  public function delete_inactive()
  {
    global $db, $cache;
    
    $db->delete_query("usergroups", "gid in (" .$this->get_inactive(). ")");
    $cache->update_usergroups();
    $this->reset_inactive();
  }
  
  public function delete_self_ban ()
  {
    global $db, $cache;
    
    $db->delete_query("usergroups", "gid in (" .$this->get_self_ban(). ")");
    $cache->update_usergroups();
    $this->reset_self_ban();
  }
  
  // create a group retriever for orphaned inactive usergroups
  // method to query for tagged usergroups
  // implement such a method with iterator
  public function search_usergroups ()
  {
    return "function:search_usergroups not implemented<br>";
  }
  
  public function next_usergroup ()
  {
    return "function:next_usergroup not implemented<br>";
  }

  /**
   * Returns a SimpleXMLElement with the inactive_user.xml file data.
   */
  private function load()
  {
    if(DEBUG) echo "load(): loading xml ". self::INACTIVE_USER_XML ."<br>";
    if (file_exists(self::INACTIVE_USER_XML)) 
    {
      $inactive_user = simplexml_load_file(self::INACTIVE_USER_XML);
    } 
    else 
    {
      $inactive_user = new SimpleXMLElement(self::INACTIVE_USER_XML_CONTENT);
    }
    if(DEBUG) echo "load(): xml loaded<br>";
    return $inactive_user;
  }
}
if(DEBUG) echo "userGroups class created<br>";
global $inactive_usergroups;
$inactive_usergroups = new userGroups();
