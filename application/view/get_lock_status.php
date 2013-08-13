<?php
  /**
  * Return the status of a lock
  * @param string lock
  */
  require_once( "../core/settings.php" );
  @session_start();
  if( !SessionUser::isLoggedIn() ) exit;
  if( !isset( $_GET["lock"] ) ) exit;
  if( Lock::exists( $_GET["lock"] ) ) die( "locked" );
  echo "unlocked";
?>