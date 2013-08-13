<?php
  session_start();
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  
  if( empty( $_GET["model"] ) ) exit;
  
  $db = new DB();
  $sql = "SELECT id from ".$db->escape( $_GET["model"] )." order by rand() limit 1";
  $db->query( $sql );
  if( $db->numrows == 0 ) exit;
  $row = $db->fetchRow();
  $return_url = "Location: ".SITE_ROOT.$_GET["model"]."/edit/".$row["id"];
  header( $return_url );
?>