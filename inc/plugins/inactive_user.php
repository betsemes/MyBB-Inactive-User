<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

//TODO: Create an object to hold the inactive users data

function inactive_user_info()
{
	return array(
		"name"          => "Inactive User",
		"description"   => "Monitors user activity, moves inactive users to the 'inactive users' usergroup, moves them back to their previous usergroup upon signing up, and prunes long inactive users.",
		"website"       => "/index.html",
		"author"        => "Betsemes",
		"authorsite"    => "mailto:betsemes@gmail.com",
		"version"       => "0.0.1a",
		"codename"      => "inactive_user",
		"compatibility" => "1830"
	);
}

function inactive_user_install()
{
	global $db;
  
	// Create our table collation
	$collation = $db->build_create_table_collation(); // what is a "table collation"?
  
  //TODO: there is a MyBB settings table that the plugins I have installed use to store their own settings. May need to figure out whether or not to create a settings table or to use MyBB one.

  // Create settings table if it doesn't exist already
	if (!$db->table_exists('inactive_user_settings'))
	{
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
	}
  
  //TODO: populate the settings table with default values

  // Create table if it doesn't exist already
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

  //TODO: Create the "inactive" usergroup (to be called "inactive_user_plugin" maybe). Do nothing if it already exists.
  //global $config;
  //$inactive_usergroup = $config['database']['table_prefix']. "inactive_user_plugin";
  //if the inactive usergroup does not exist...
  //append the inactive and self-banned(to be called "self_banned_user_plugin" maybe) inactive usergroups to the database.
  //use the alternative to $db->insert_query_multiple() method which inserts one record.
  //update the cache
  
  //if the inactive usergroup does exist... what to do?
  
  // Begin of .inc/tasks/inactive_user.php
  // This portion will identify inactive users. Require it once here.
  
  //TODO: delete from the inactive_users table each user with activity if any.
    
  // Get the inactive users.
  $inactives = mysqli_fetch_all($db->write_query(
    "select
      uid,"
      .TIME_NOW. " as deactdate,
      2 as deactmethod,
      usergroup as oldgroup,
      displaygroup as olddisplaygroup,
      additionalgroups as oldadditionalgroups,
      usertitle as usertitle,"
      .TIME_NOW. " + (60 * 60 * 24 * 365 * 2) as returndate
    from
      ai_users
    where
      (if(lastactive > lastvisit, lastactive, lastvisit) 
        < ".TIME_NOW." - (60 * 60 * 24 * 90))
      and (uid not in (select uid from ai_inactive_users));"
    ), MYSQLI_ASSOC);
    
    // Set the fields to the appropriate data types
    for ($i = 0; $i < count($inactives); $i++)
    {
      settype($inactives[$i]["uid"], "integer");
      settype($inactives[$i]["deactdate"], "integer");
      settype($inactives[$i]["deactmethod"], "integer");
      settype($inactives[$i]["oldgroup"], "integer");
      settype($inactives[$i]["olddisplaygroup"], "integer");
      settype($inactives[$i]["returndate"], "integer");
    }

    // If there are inactive users identified, add them to the table.
    if (count($inactives) != 0 )
    { 
      $db->insert_query_multiple("inactive_users", $inactives);
    }

  //TODO: Assign the "inactive" usergroup to the newly identified inactive user's primary and display.
  //update the primary and display usergroups to "inactive", and "additional" to an empty string with an update query with the user list gotten above.
  
  // End of .inc/tasks/inactive_user.php
}

function inactive_user_is_installed()
{
  global $db;

	// If the table exists then it means the plugin is installed because we only drop it on uninstallation
	return $db->table_exists('inactive_users');
}

function inactive_user_uninstall()
{
  global $db;

  // $db->delete_query ("inactive_users", "deactdate > 0");

  //TODO: restore original usergroups to each inactive user
  
  $db->drop_table('inactive_user_settings');
  $db->drop_table('inactive_users');
  
  //TODO: drop the settings table
}

function inactive_user_activate()
{

}

function inactive_user_deactivate()
{

}
