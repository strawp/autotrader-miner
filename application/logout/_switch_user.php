<?php
  require_once( "../core/settings.php" );
  if( SITE_AUTH == "db" ) require_once( "core/db.login.class.php" );
  else require_once( "core/login.class.php" );
  require_once( "core/cache.class.php" );
  $login = new Login();
  $details = $login->switchUser( $_GET["user"] );
  if( $details ){
    Cache::flushModels();
    SessionUser::setDetails( $details );
    Flash::setNotice( "You are now logged on as ".$details["firstname"]." ".$details["lastname"] );
  }
  header( "Location: ".SITE_ROOT );
?>