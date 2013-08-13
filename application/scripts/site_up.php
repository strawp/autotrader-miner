<?php
  /**
  * Remove holding page and restore htaccess if it is newer than the current one
  */
  echo "Bringing site back up...\n";
  require_once( "../core/settings.php" );
  $htaccess_path = "../_htaccess";
  
  if( file_exists( $htaccess_path."_old" ) ){
    $htaccess_old = file_get_contents( $htaccess_path."_old", $htaccess_old );
    echo " - Restoring old htaccess\n";
    file_put_contents( $htaccess_path, $htaccess_old );
    unlink( $htaccess_path."_old" );
  }else{
    echo " - old htaccess doesn't exist\n";
  }
  
  echo "Done.\n";
  
?>