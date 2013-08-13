<?php
  require( "../core/settings.php" );
  session_start();
  if( isset( $_POST["btnSubmit"] ) && $_POST["btnSubmit"] == "Yes" ){
    SessionUser::logout();
  }
  header( "Location: ".SITE_ROOT );
?>