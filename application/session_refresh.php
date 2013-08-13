<?php
  require_once( "core/settings.php" );
  session_start();
  echo json_encode( array( "loggedin" => SessionUser::isLoggedIn() ) );
?>