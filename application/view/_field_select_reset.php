<?php
  
  /*
    Remove all references to selected fields from user_field table
  */
  
  session_start();
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  
  if( !SessionUser::isLoggedIn() ) exit;
  
  $db = new DB();
  $model = $db->escape( $_GET["model"] );
  if( $model == "" ) exit;
  
  $sql = "DELETE
    FROM field_user 
    WHERE user_id = ".intval( SessionUser::getId() )."
    AND field_id IN (
      SELECT f.id 
      FROM field f
      INNER JOIN model m ON f.model_id = m.id
    )";
    
  $db->query( $sql );
  // echo $db->error;

  header( "Location: ".SITE_ROOT.$model."/field_select" );

?>