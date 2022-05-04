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

//TODO: use PluginLibrary to make any change on this class. 
// It's possible that I'll drop the use of a settings table and adopt the way PluginLibrary does things.

//TODO: Add a "keeptables" setting to prevent table drop on uninstall.

/**
 * Manages the settings table.
 */
class inactiveUserSettings
{
  /**
   * Defines the default settings for the plugin
   *
   * Settings: 
   * <ul>
   * <li>inactivityinterval<br>
   *     how many days should pass since the last sign of activity to mark a user inactive, default: 90</li>
   * <li>deletiontime<br>
   *     how many days should pass for an already identified inactive user to be deleted, default: 730</li>
   * <li>reminders<br>
   *     how many reminders should be emailed to a user before account deletion, default: 90</li>
   * <li>reminderspacing<br>
   *     how much time in hours should pass between reminders, default: 24</li>
   * <li>includenonverifiedaccounts<br>
   *     whether or not to include unverified accounts in the inactivity identification process, default: false</li>
   * <li>includeawayusers<br>
   *     include users that have set their status to away, default: true</li>
   * <li>inactiveusergroup<br>
   *     usergroup used to mark the inactive users with</li>
   * </ul>
   */
  public $settings;
  
  // private $dbase;
  
  //TODO: replace code used to update the settings attribute with database update.
  /**
   * Constructor; creates and populates the settings table, or loads settings if it exists.
   */
  public function __construct()
  {
    global $db, $PL;
    $PL or require_once PLUGINLIBRARY;
    
    //TODO: IN PROGRESS Changing settings functionality from having a settings table.
    // Need to store the settings in MyBB settings table so that they can be accessed and modified through the settings control panel.
    
    // Check if PluginLibrary is the required version.
    if($PL->version < 13)
    {
      flash_message("PluginLibrary is too old, please update.", "error");
      admin_redirect("index.php?module=config-plugins");
    }
    
    // TESTING: Code to create the settings in MyBB settings table.
    $PL->settings(
      'inactive_user',
      'Inactive User Settings',
      'Modify the way inactive users are handled.',
      array(
        'inactivityinterval' => array(
                  'title' => 'Inactivity Time Interval',
                  'description' => 'How many days should pass since last seen for a user to be considered inactive.',
                  'optionscode' => "numeric\nmin=0",
                  'value' => 90,
                  ),
        'deletiontime' => array(
                  'title' => 'Deletion Time',
                  'description' => 'How much time in days should a user remain inactive before being deleted. Zero(0) for unlimited.',
                  'optionscode' => "numeric\nmin=0",
                  'value' => 730,
                  ),
        'reminders' => array(
                  'title' => 'Reminders',
                  'description' => 'How many reminders should be emailed to a user before account deletion.',
                  'optionscode' => "numeric\nmin=0",
                  'value' => 90,
                  ),
        'reminderspacing' => array(
                  'title' => 'Reminder Spacing',
                  'description' => 'How much time in hours should pass between reminders.',
                  'optionscode' => "numeric\nmin=0",
                  'value' => 24,
                  ),
        'includenonverifiedaccounts' => array(
                  'title' => 'Include Non-Verified Accounts',
                  'description' => 'Whether or not to consider unverified accounts as inactive users.',
                  'optionscode' => 'yesno',
                  'value' => 0,
                  ),
        'includeawayusers' => array(
                  'title' => 'Include Away Users',
                  'description' => 'Allow identifying Away users as inactive.',
                  'optionscode' => 'yesno',
                  'value' => 1,
                  ),
        'inactiveusergroup' => array(
                  'title' => 'Inactive Usergroup',
                  'description' => 'Do NOT modify.',
                  'optionscode' => "numeric\nmin=0",
                  'value' => 0,
                  ),
        'selfbanusergroup' => array(
                  'title' => 'Self-Ban Usergroup',
                  'description' => 'Do NOT modify.',
                  'optionscode' => "numeric\nmin=0",
                  'value' => 0,
                  ),
        'keeptables' => array(
                  'title' => 'Keep Inactive Users Data',
                  'description' => 'Keep inactive users data after uninstall.',
                  'optionscode' => 'yesno',
                  'value' => 1,
                  ),
        )
    );
    
    // Create our table collation
    $collation = $db->build_create_table_collation(); // what is a "table collation"?
    
    if ($db->table_exists('inactive_user_settings'))
    {
      $this->load();
    }
    else
    {
      // getting the max group id
      $this->$settings = array(
        array("isid" => 1, "setting" => "inactivityinterval", "value" => "90"),
        array("isid" => 2, "setting" => "deletiontime", "value" => "730"),
        array("isid" => 3, "setting" => "reminders", "value" => "90"),
        array("isid" => 4, "setting" => "reminderspacing", "value" => "24"),
        array("isid" => 5, "setting" => "includenonverifiedaccounts", "value" => "0"),
        array("isid" => 6, "setting" => "includeawayusers", "value" => "1"),
        array("isid" => 7, "setting" => "inactiveusergroup", "value" => "0"),
        array("isid" => 8, "setting" => "selfbanusergroup", "value" => "0")
      );
      
      $max_gid = mysqli_fetch_all($db->write_query('select max(gid) as gid from ' .TABLE_PREFIX. 'usergroups;'), MYSQLI_ASSOC);  
      $this->set("inactiveusergroup", (string)((int)$max_gid[0]['gid'] + 1));
      $this->set("selfbanusergroup", (string)((int)$max_gid[0]['gid'] + 2));
      
      switch($db->type)
      {
      // only the "default" section is done
        case "pgsql": 
          $db->write_query("CREATE TABLE ".TABLE_PREFIX."inactive_user_settings (
            mid serial,
            message varchar(100) NOT NULL default '',
            PRIMARY KEY (mid)
          );");
          break;
        case "sqlite":
          $db->write_query("CREATE TABLE ".TABLE_PREFIX."inactive_user_settings (
            mid INTEGER PRIMARY KEY,
            message varchar(100) NOT NULL default ''
          );");
          break;
        default:
          $db->write_query("CREATE TABLE ".TABLE_PREFIX."inactive_user_settings (
            isid int unsigned NOT NULL,
            setting varchar(50),
            value varchar(250),
            PRIMARY KEY (isid)
          ) ENGINE=MyISAM{$collation};");
          break;
      }
  
    // append default settings to the settings table
    $db->insert_query_multiple("inactive_user_settings", $this->$settings);
    }
    
  }
  
  /**
   * Settings getter.
   *
   * @return array Associative array containing the settings and the setting names as keys.
   */
  public function settings()
  {
    return array_column($this->$settings, "value", "setting");
  }

  /**
   * Gets the specified setting.
   *
   * @param string $setting The setting name as it appears in the settings table.
   * @return string The setting value.
   */
  public function get($setting)
  {
    $S = $this->settings(); //TODO: replace this with getting it from the database
    return $S[$setting];
  }
  
  /**
   * Sets the specified setting to the provided value.
   *
   * @param string $setting The setting name as it appears in the settings table.
   * @param string $value The value to be used to set the setting with.
   * @return boolean True if the setting was updated successfully. False otherwise.
   */
  public function set($setting, $value)
  {
    //TODO: add code to update the database before doing this.
    for ($i = 0; $i <= sizeof($this->$settings); $i++)
    {
      if($this->$settings[$i]["setting"] === $setting)
      {
        $this->$settings[$i]["value"] = (string)$value;
        return true;
      }
    }
    return false;
  }
  
  //TODO: Consider deleting this.
  public function load()
  {
    global $db;
    
    $this->$settings = mysqli_fetch_all($db->write_query(
    "select * from ".TABLE_PREFIX."inactive_user_settings;"
    ), MYSQLI_ASSOC);
  }

  /**
   * Deletes the usergroups added to the usergroups table.
   */
  public function delete_usergroups()
  {
    global $db, $cache;
    //add the delete query to delete the inactive usergroups from MyBB usergroups table.
    /*$db->delete_query

    Used to perform a delete query on a table in a database. Receives three parameters:

    table
        The name of the table.
    where
        The where clause.
    limit
    The maximum amount of rows to be deleted. Default is unlimited. 
    
    Example:
    $db->delete_query("awaitingactivation", "uid='".(int)$user['uid']."' AND code='".$db->escape_string($mybb->input['token'])."' AND type='l'");

    */
    $db->delete_query("usergroups", "gid in (" .$this->get('inactiveusergroup'). "," .$this->get('selfbanusergroup'). ")");
    $cache->update_usergroups();
  }
  
  public function delete_settings()
  {
    global $PL;
    $PL or require_once PLUGINLIBRARY;
    
    $PL->settings_delete("inactive_user");
  }
}
