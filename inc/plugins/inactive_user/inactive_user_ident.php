<?php 
/**
 * Inactive User 0.1 plugin for MyBB 1.8.x
 *
 * A simple system for the identification of inactive users for MyBB. It involves:
 * - identification of users who have not visited for a certain amount of time
 * - providing a way for users to deactivate their accounts
 *
 * File: ./inc/plugins/inactive_user/inactive_user_ident.php
 * Inactive users identification script used by both the inactiveUsers 
 * class constructor and the ongoing identification task.
 *
 * @author  Betsemes <betsemes@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

    // Get the inactive users.
    echo "getting the inactive users<br>";
    $inactives = mysqli_fetch_all($db->write_query(
      "select
        uid,"
        .TIME_NOW. " as deactdate,
        2 as deactmethod,
        usergroup as oldgroup,
        displaygroup as olddisplaygroup,
        additionalgroups as oldadditionalgroups,
        usertitle as usertitle,
        if(
          regdate > if(lastactive > lastvisit, lastactive, lastvisit),
          regdate + (60 * 60 * 24 * " .$iu_settings->get("deletiontime"). "), 
          if(lastactive > lastvisit, lastactive, lastvisit) + (60 * 60 * 24 * " .$iu_settings->get("deletiontime"). ")
        ) as returndate
      from " .TABLE_PREFIX. "users
      where
        (if(lastactive > lastvisit, lastactive, lastvisit) 
          < ".TIME_NOW." - (60 * 60 * 24 * " .$iu_settings->get("inactivityinterval"). "))
        and (uid not in (select uid from " .TABLE_PREFIX. "inactive_users))
        and (usergroup in (select gid from " .TABLE_PREFIX. "usergroups
          where isbannedgroup=0));"
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
      echo "inactive users identified: ". count($inactives). "<br>";
      if (count($inactives) != 0 )
      { 
        echo 'inserting inactive users<br>';
        var_dump($inactives); echo '<br>';
        $db->insert_query_multiple("inactive_users", $inactives);
        echo "inactive users inserted<br>";
      
        $inactive_usergroups or require_once MYBB_ROOT ."inc/plugins/inactive_user/usergroups_class.php";
        
        echo '$inactive_usergroups->inactive: ' .$inactive_usergroups->inactive. '<br>';
        echo '$inactive_usergroups->self_ban: ' .$inactive_usergroups->self_ban. '<br>';
        echo 'Assign the inactive usergroups to identified users<br>';
        //TODO: Set displaygroup to zero
        $db->update_query("users", 
          array( 
            "usergroup" => $inactive_usergroups->inactive, 
            "displaygroup" => 0,
            "usertitle" => ''),
          'uid in (' .implode(',',array_column($inactives,'uid')). ')'
        );
        echo 'User table updated.<br>';
      }
      echo "exiting inactive_user_ident.php<br>";
