<?php 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

//TODO: create a class to handle error logging.--in progress

class error_log 
{
  public function log($msg)
  {
    $error_log = fopen("./inactive_user/error.log", "a") or die("Unable to open error log!");
    fwrite($error_log, $msg."\n");
    fclose($error_log);
  }
}