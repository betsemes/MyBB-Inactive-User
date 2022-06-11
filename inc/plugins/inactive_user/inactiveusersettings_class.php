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

/* ./inc/plugins/inactive_user/inactiveusersettings_class.php
   File containing the class for interfacing with the plugin settings. */

/**
 * Manages the settings table.
 *
 * @internal
 */
class inactiveUserSettings
{
  /**
   * TODO: Provide a setting for specifying the inactive users username color.
   * TODO: delete this attribute.
   *
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
  
  /**
   * Constructor; creates the settings, or loads settings if they already exist.
   */
  public function __construct()
  {
    global $db, $PL;
    $PL or require_once IUIUPLUGINLIBRARY;
    
    // Check if PluginLibrary is the required version.
    if($PL->version < 13)
    {
      flash_message("PluginLibrary is too old, please update.", "error");
      admin_redirect("index.php?module=config-plugins");
    }
    
    // Code to create the settings in MyBB settings table.
    if (!$this->exist_settings())
    {
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
          'keeptables' => array(
                    'title' => 'Keep Inactive Users Data',
                    'description' => 'Keep inactive users data after uninstall.',
                    'optionscode' => 'yesno',
                    'value' => 0,
                    ),
          )
      );
      
    }
    
  }
  
  /**
   * General settings getter.
   *
   * @return array Associative array containing the settings and the setting names as keys.
   */
  public function settings()
  {
    // Get the inactive_user settings group
    // Get the settings from the database.
    return array_column($this->$settings, "value", "setting");
  }

  /**
   * Gets the specified setting.
   *
   * Settings that can be retrieved are:
   * * inactivityinterval
   * * deletiontime
   * * reminders
   * * reminderspacing
   * * includenonverifiedaccounts
   * * includeawayusers
   * * keeptables
   *
   * @param string $setting The setting name as it appears in the settings table.
   * @return string The setting value.
   */
  public function get($setting)
  {
    global $settings;
    
    return $settings["inactive_user_" .$setting];
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
    //TODO: outdated code. Rewrite this to access MyBB settings.
    for ($i = 0; $i <= sizeof($this->settings); $i++)
    {
      if($this->settings[$i]["setting"] === $setting)
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
   * Returns true if the settings exist; false otherwise.
   */
  private function exist_settings()
  {
    global $settings;

    // This plugin creates settings on install and deletes them on uninstall.
    // Check if settings exist.
    // Another example would be $db->table_exists() for database tables.
    if(isset($settings['inactive_user_inactivityinterval']))
    {
        return true;
    }
    return false;
  }
  
  /**
   * Deletes the settings created by this plugin.
   */
  public function delete_settings()
  {
    global $PL;
    $PL or require_once IUIUPLUGINLIBRARY;
    echo "about to delete the settings<br>";
    $PL->settings_delete("inactive_user",true);
  }
}
