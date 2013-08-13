<?php
  /**
  * Set up a selection of default widgets for any user that doesn't already have them, based on which groups they're in
  */
  
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require_once( "../core/settings.php" );
  
  // Get list of people who haven't set up their dashboard yet
  $sql = "
    SELECT u.id
    FROM user u
    WHERE u.id NOT IN (SELECT user_id FROM user_widget) and name <> '' AND has_left <> 1
    ORDER by last_name
  ";
  
  $db = new DB();
  $db->query( $sql );
  
  while( $row = $db->fetchRow() ){
    $u = new User();
    $u->debug = true;
    $u->get( $row["id"] );
    $u->setupDefaultWidgets();
  }
  
?>