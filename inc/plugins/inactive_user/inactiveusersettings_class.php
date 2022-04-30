<?php
//TODO: REFACTOR: move the inactiveUserSettings class to its own file.
// Have a "require_once" in its place.
class inactiveUserSettings
{
  //Define the default settings for the plugin
      // Settings: 
      // inactivityinterval
          // how many days should pass since the last sign of activity to mark a user inactive, default: 90
      // deletiontime
          // how many days should pass for an already identified inactive user to be deleted, default: 730
      // reminders
          // how many reminders should be emailed to a user before account deletion, default: 90
      // reminderspacing
          // how much time in hours should pass between reminders, default: 24
      // includenonverifiedaccounts
          // whether or not to include unverified accounts in the inactivity identification process, default: false
      // includeawayusers
          // include users that have set their status to away, default: true 
  public $settings;
  
  // private $dbase;
  
  public function __construct()//$db) 
  {
    global $db;
    
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
  
  public function settings()
  {
    return array_column($this->$settings, "value", "setting");
  }

  public function get($setting)
  {
    $S = $this->settings(); //TODO: replace this with getting it from the database
    return $S[$setting];
  }
  
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
  
  public function load()
  {
    global $db;
    
    $this->$settings = mysqli_fetch_all($db->write_query(
    "select * from ".TABLE_PREFIX."inactive_user_settings;"
    ), MYSQLI_ASSOC);
  }

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
    $db->delete_query("usergroups", "gid in (18,19)");
    $cache->update_usergroups();
  }
}
