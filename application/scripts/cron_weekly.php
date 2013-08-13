<?php
  /**
  * Script to be run once a week by cron
  */
  
  // Make sure the current dir is the dir the file is in
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require_once( "../core/settings.php" );
  
  // Back up finance table
  $db = new DB();
  $rlt = $db->dumpTablesToGzip( array("finance") );
  
?>