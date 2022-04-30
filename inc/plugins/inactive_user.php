<?php 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

//TODO: Create an object to hold the inactive users data

require_once MYBB_ROOT . "inc/plugins/inactive_user/inactiveusersettings_class.php";
function inactive_user_info()
{
	return array(
		"name"          => "Inactive User",
		"description"   => "Monitors user activity, moves inactive users to the 'inactive users' usergroup, moves them back to their previous usergroup upon signing up, and prunes long inactive users.",
		"website"       => "/index.html",
		"author"        => "Betsemes",
		"authorsite"    => "mailto:betsemes@gmail.com",
		"version"       => "0.0.0a",
		"codename"      => "inactive_user",
		"compatibility" => "1830"
	);
}

function inactive_user_install()
{
	global $db, $cache;
  
	// Create our table collation
	$collation = $db->build_create_table_collation(); // what is a "table collation"?
  
  //TODO: there is a MyBB settings table that the plugins I have installed use to store their own settings. May need to figure out whether or not to use it.
  //JonesCore may have something to aid me on this, although I suspect lack of documentation will make it harder actually.

  // Create settings table if it doesn't exist already
	
  $inactive_user_settings = new inactiveUserSettings();
	
  echo "entering inactive users table creation\n";
  
 //TODO: REFACTOR: move this section to the inactive users class
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
  echo "exiting inactive users table creation\n";

  //Create the "inactive" usergroup. Do nothing if it already exists.
  //if the inactive usergroup does not exist... 
  //append the inactive and self-banned(to be called "self_banned_user_plugin" maybe) inactive usergroups to the database.

  var_dump($inactive_user_settings->get("inactiveusergroup"));
  echo "\n";
  var_dump($inactive_user_settings->get("selfbanusergroup"));
  $inactive_usergroups = array(
    array(
      "gid" => (int)$inactive_user_settings->get("inactiveusergroup"),
      "type" => 2,
      "title" => TABLE_PREFIX. "inactive_user_plugin",
      "description" => "Inactive User",
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
      "gid" => (int)$inactive_user_settings->get("selfbanusergroup"),
      "type" => 2,
      "title" => TABLE_PREFIX. "self_banned_user_plugin",
      "description" => "Self-Banned User",
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
  echo "inserting inactive_usergroups\n";
  $db->insert_query_multiple("usergroups", $inactive_usergroups);
  // update the cache
  $cache->update_usergroups();
  
  //TODO: add code to delete the groups recorded in the settings table
  
  //if the inactive usergroup does exist... what to do?
  
  //TODO: REFACTOR: move inactive users identification code to its own file.
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
      .TIME_NOW. " + (60 * 60 * 24 * " .$inactive_user_settings->get("deletiontime"). ") as returndate
    from " .TABLE_PREFIX. "users
    where
      (if(lastactive > lastvisit, lastactive, lastvisit) 
        < ".TIME_NOW." - (60 * 60 * 24 * " .$inactive_user_settings->get("inactivityinterval"). "))
      and (uid not in (select uid from " .TABLE_PREFIX. "inactive_users));"
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
  global $db, $cache;
  
  $inactive_user_settings = new inactiveUserSettings($db);
  
  //TODO: replace the delete query below with the delete_usergroups method.
  // $inactive_user_settings->delete_usergroups();
  $db->delete_query("usergroups", "gid in (" .$inactive_user_settings->get('inactiveusergroup'). "," .$inactive_user_settings->get('selfbanusergroup'). ")");
  $cache->update_usergroups();
  //TODO: uninstall: restore original usergroups to each inactive user
  
  $db->drop_table('inactive_user_settings');
  $db->drop_table('inactive_users');
  
}

function inactive_user_activate()
{
  //TODO: mark each user in the inactive user table with the inactive usergroup.
  // schedule the inactive user identification script.
}

function inactive_user_deactivate()
{
  //TODO: restore the original usergroups to users.
  // unschedule the inactive user identification script.
}
