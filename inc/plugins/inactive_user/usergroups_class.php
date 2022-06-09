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

echo "creating userGroups class<br>";
/**
 * Only holds the usergroup IDs assigned to the inactive usergroups.
 */
class userGroups 
{
  // make static methods as much as possible
  
  const INACTIVE_USER_XML = '../repos/inactive_user/inc/plugins/inactive_user/inactive_user.xml';
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
    if (file_exists(self::INACTIVE_USER_XML)) 
    {
      $inactive_user = simplexml_load_file(self::INACTIVE_USER_XML);
    } 
    else 
    {
      $inactive_user = new SimpleXMLElement(self::INACTIVE_USER_XML_CONTENT);
    }
  
    if ($inactive != 0) $inactive_user->usergroups->inactive = $inactive;
    if ($self_ban != 0) $inactive_user->usergroups->{'self-ban'} = $self_ban;
    
    $inactive_user->saveXML(self::INACTIVE_USER_XML);
  
  }
  
  // accessors and mutators
  public function get_inactive()
  {
    if (file_exists(self::INACTIVE_USER_XML)) 
    {
      $inactive_user = simplexml_load_file(self::INACTIVE_USER_XML);
      return $inactive_user->usergroups->inactive;
    }
    return "function:get_inactive\n";
  }
  
  public function set_inactive($inactive)
  {
    if (file_exists(self::INACTIVE_USER_XML)) 
    {
      $inactive_user = simplexml_load_file(self::INACTIVE_USER_XML);
      $inactive_user->usergroups->inactive = $inactive;
      $inactive_user->saveXML(self::INACTIVE_USER_XML);
    }
    return "function:set_inactive\n";
  }
  
  public function get_self_ban()
  {
    if (file_exists(self::INACTIVE_USER_XML)) 
    {
      $inactive_user = simplexml_load_file(self::INACTIVE_USER_XML);
      return $inactive_user->usergroups->{'self-ban'};
    }
    return "function:get_self_ban\n";
  }
  
  public function set_self_ban($self_ban)
  {
    if (file_exists(self::INACTIVE_USER_XML)) 
    {
      $inactive_user = simplexml_load_file(self::INACTIVE_USER_XML);
      $inactive_user->usergroups->{'self-ban'} = $self_ban;
      $inactive_user->saveXML(self::INACTIVE_USER_XML);
    }
    return "function:set_self_ban\n";
  }
  
  // add/delete usergroups from the database
  // consider tagging the inactive usergroups with a span tag with a data- attribute
  // <span data-inactive-user-group="inactive">usergroup description</span>
  public static function next_gid ()
  {
    global $db;
    
    $max_gid = $db->fetch_field(
      $db->simple_select(
        "usergroups", 
        $fields="max(gid) as gid"),"gid");
    settype($max_gid,"integer");
    return $max_gid;
  }
  
  public static function add_usergroup ()
  {
    return "function:add_usergroup\n";
  }
  
  public static function delete_usergroup ()
  {
    // sets usergroups to zero on the xml file
    return "function:delete_usergroup\n";
  }
  
  // create a group retriever for orphaned inactive usergroups
  // method to query for tagged usergroups
  // implement such a method with iterator
  public static function search_usergroups ()
  {
    return "function:search_usergroups\n";
  }
  
  public static function next_usergroup ()
  {
    return "function:next_usergroup\n";
  }
  
}
echo "userGroups class created<br>";
// $inactive_usergroups = new userGroups();
