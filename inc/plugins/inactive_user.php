<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

//TODO: Create an object to hold the settings data
//TODO: Create an object to hold the inactive users data

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
$inactive_user_settings = array(
  array("isid" => 1, "setting" => "inactivityinterval", "value" => "90"),
  array("isid" => 2, "setting" => "deletiontime", "value" => "730"),
  array("isid" => 3, "setting" => "reminders", "value" => "90"),
  array("isid" => 4, "setting" => "reminderspacing", "value" => "24"),
  array("isid" => 5, "setting" => "includenonverifiedaccounts", "value" => "0"),
  array("isid" => 6, "setting" => "includeawayusers", "value" => "1")
);
$iu_settings = array_column($inactive_user_settings, "value", "setting");
    
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
	global $db, $cache, $inactive_user_settings, $iu_settings;
  
	// Create our table collation
	$collation = $db->build_create_table_collation(); // what is a "table collation"?
  
  //TODO: there is a MyBB settings table that the plugins I have installed use to store their own settings. May need to figure out whether or not to create a settings table or to use MyBB one.
  //JonesCore may have something to aid me on this, although I suspect lack of documentation will make it harder actually.

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
  
    // append default settings to the settings table
    $db->insert_query_multiple("inactive_user_settings", $inactive_user_settings);

	}
  else //if the settings table does exist, load settings from it
  {
    $inactive_user_settings = mysqli_fetch_all($db->write_query(
    "select * from ai_inactive_user_settings;"
    ), MYSQLI_ASSOC);
    $iu_settings = array_column($inactive_user_settings, "value", "setting");
  }
  
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

  //Create the "inactive" usergroup. Do nothing if it already exists.
  //$inactive_usergroup = TABLE_PREFIX. "inactive_user_plugin";
  //$self_banned_usergroup = TABLE_PREFIX. "self_banned_user_plugin";
  //if the inactive usergroup does not exist... 
  //append the inactive and self-banned(to be called "self_banned_user_plugin" maybe) inactive usergroups to the database.

//  ********finally, after much frustration, this seems to work:
        // var_dump($db->simple_select("usergroups", "max(gid)"));
// This worked as well:
        // var_dump($db->write_query("select gid from ai_usergroups;"));
// ...and this worked as well.
        // var_dump($db->write_query("select max(gid) as gid from ai_usergroups;"));
// The problem seems to have been caused by having deleted two records from the usergroups table on LibreOffice Base without having rebuilt the cache.

  // echo "getting the max group id";
  // $max_gid = mysqli_fetch_all($db->write_query('select max(gid) as gid from ai_usergroups;'), MYSQLI_ASSOC);
  // echo $max_gid
  // $inactive_usergroups = array(
    // array(
      // "gid" => $max_gid[0]['gid'] + 1,
      // "type" => 2,
      // "title" => TABLE_PREFIX. "inactive_user_plugin",
      // "description" => "Inactive User",
      // "namestyle" => '<span style="color:#8c8c8c,">{username}</span>',
      // "usertitle" => "Inactive",
      // "stars" => 0,
      // "starimage" => "images/star.png",
      // "image" => "",
      // "disporder" => 0,
      // "isbannedgroup" => 0,
      // "canview" => 0,
      // "canviewthreads" => 0,
      // "canviewprofiles" => 0,
      // "candlattachments" => 0,
      // "canviewboardclosed" => 0,
      // "canpostthreads" => 0,
      // "canpostreplys" => 0,
      // "canpostattachments" => 0,
      // "canratethreads" => 0,
      // "modposts" => 0,
      // "modthreads" => 0,
      // "mod_edit_posts" => 0,
      // "modattachments" => 0,
      // "caneditposts" => 0,
      // "candeleteposts" => 0,
      // "candeletethreads" => 0,
      // "caneditattachments" => 0,
      // "canviewdeletionnotice" => 0,
      // "canpostpolls" => 0,
      // "canvotepolls" => 0,
      // "canundovotes" => 0,
      // "canusepms" => 0,
      // "cansendpms" => 0,
      // "cantrackpms" => 0,
      // "candenypmreceipts" => 0,
      // "pmquota" => 0,
      // "maxpmrecipients" => 0,
      // "cansendemail" => 0,
      // "cansendemailoverride" => 0,
      // "maxemails" => 1,
      // "emailfloodtime" => 1,
      // "canviewmemberlist" => 0,
      // "canviewcalendar" => 0,
      // "canaddevents" => 0,
      // "canbypasseventmod" => 0,
      // "canmoderateevents" => 0,
      // "canviewonline" => 0,
      // "canviewwolinvis" => 0,
      // "canviewonlineips" => 0,
      // "cancp" => 0,
      // "issupermod" => 0,
      // "cansearch" => 0,
      // "canusercp" => 0,
      // "canbeinvisible" => 1,
      // "canuploadavatars" => 0,
      // "canratemembers" => 0,
      // "canchangename" => 0,
      // "canbereported" => 0,
      // "canchangewebsite" => 0,
      // "showforumteam" => 0,
      // "usereputationsystem" => 0,
      // "cangivereputations" => 0,
      // "candeletereputations" => 0,
      // "reputationpower" => 0,
      // "maxreputationsday" => 0,
      // "maxreputationsperuser" => 0,
      // "maxreputationsperthread" => 0,
      // "candisplaygroup" => 0,
      // "attachquota" => 0,
      // "cancustomtitle" => 0,
      // "canwarnusers" => 0,
      // "canreceivewarnings" => 0,
      // "maxwarningsday" => 0,
      // "canmodcp" => 0,
      // "showinbirthdaylist" => 0,
      // "canoverridepm" => 0,
      // "canusesig" => 0,
      // "canusesigxposts" => 0,
      // "signofollow" => 0,
      // "edittimelimit" => 0,
      // "maxposts" => 0,
      // "showmemberlist" => 0,
      // "canmanageannounce" => 0,
      // "canmanagemodqueue" => 0,
      // "canmanagereportedcontent" => 0,
      // "canviewmodlogs" => 0,
      // "caneditprofiles" => 0,
      // "canbanusers" => 0,
      // "canviewwarnlogs" => 0,
      // "canuseipsearch" => 0
    // ), array(
      // "gid" => $max_gid[0]['gid'] + 2,
      // "type" => 2,
      // "title" => TABLE_PREFIX. "self_banned_user_plugin",
      // "description" => "Self-Banned User",
      // "namestyle" => '<span style="color:#8c8c8c; text-decoration-line: line-through;">{username}</span>',
      // "usertitle" => "Inactive",
      // "stars" => 0,
      // "starimage" => "images/star.png",
      // "image" => "",
      // "disporder" => 0,
      // "isbannedgroup" => 0,
      // "canview" => 0,
      // "canviewthreads" => 0,
      // "canviewprofiles" => 0,
      // "candlattachments" => 0,
      // "canviewboardclosed" => 0,
      // "canpostthreads" => 0,
      // "canpostreplys" => 0,
      // "canpostattachments" => 0,
      // "canratethreads" => 0,
      // "modposts" => 0,
      // "modthreads" => 0,
      // "mod_edit_posts" => 0,
      // "modattachments" => 0,
      // "caneditposts" => 0,
      // "candeleteposts" => 0,
      // "candeletethreads" => 0,
      // "caneditattachments" => 0,
      // "canviewdeletionnotice" => 0,
      // "canpostpolls" => 0,
      // "canvotepolls" => 0,
      // "canundovotes" => 0,
      // "canusepms" => 0,
      // "cansendpms" => 0,
      // "cantrackpms" => 0,
      // "candenypmreceipts" => 0,
      // "pmquota" => 0,
      // "maxpmrecipients" => 0,
      // "cansendemail" => 0,
      // "cansendemailoverride" => 0,
      // "maxemails" => 1,
      // "emailfloodtime" => 1,
      // "canviewmemberlist" => 0,
      // "canviewcalendar" => 0,
      // "canaddevents" => 0,
      // "canbypasseventmod" => 0,
      // "canmoderateevents" => 0,
      // "canviewonline" => 0,
      // "canviewwolinvis" => 0,
      // "canviewonlineips" => 0,
      // "cancp" => 0,
      // "issupermod" => 0,
      // "cansearch" => 0,
      // "canusercp" => 0,
      // "canbeinvisible" => 1,
      // "canuploadavatars" => 0,
      // "canratemembers" => 0,
      // "canchangename" => 0,
      // "canbereported" => 0,
      // "canchangewebsite" => 0,
      // "showforumteam" => 0,
      // "usereputationsystem" => 0,
      // "cangivereputations" => 0,
      // "candeletereputations" => 0,
      // "reputationpower" => 0,
      // "maxreputationsday" => 0,
      // "maxreputationsperuser" => 0,
      // "maxreputationsperthread" => 0,
      // "candisplaygroup" => 0,
      // "attachquota" => 0,
      // "cancustomtitle" => 0,
      // "canwarnusers" => 0,
      // "canreceivewarnings" => 0,
      // "maxwarningsday" => 0,
      // "canmodcp" => 0,
      // "showinbirthdaylist" => 0,
      // "canoverridepm" => 0,
      // "canusesig" => 0,
      // "canusesigxposts" => 0,
      // "signofollow" => 0,
      // "edittimelimit" => 0,
      // "maxposts" => 0,
      // "showmemberlist" => 0,
      // "canmanageannounce" => 0,
      // "canmanagemodqueue" => 0,
      // "canmanagereportedcontent" => 0,
      // "canviewmodlogs" => 0,
      // "caneditprofiles" => 0,
      // "canbanusers" => 0,
      // "canviewwarnlogs" => 0,
      // "canuseipsearch" => 0
    // )
  // );
  
  // add the usergroups to the usergroups table
  // $db->insert_query_multiple("usergroups", $inactive_usergroups);
  // update the cache
  // $cache->update_usergoups();
  
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
      .TIME_NOW. " + (60 * 60 * 24 * " .$iu_settings["deletiontime"]. ") as returndate
    from
      ai_users
    where
      (if(lastactive > lastvisit, lastactive, lastvisit) 
        < ".TIME_NOW." - (60 * 60 * 24 * " .$iu_settings["inactivityinterval"]. "))
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
