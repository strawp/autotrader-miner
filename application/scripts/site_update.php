<?php
  // Updates the site to the current version
  $update_start_time = time();
  
  // Make sure the current dir is the dir the file is in
  chdir( dirname( $_SERVER["PHP_SELF"] ) ); 
  require_once( "../core/settings.php" );
  
  // Backup DB except finance
  $backup_script = "db_backup.php -x=\"finance\"";
  echo "Executing backup...\n";
  $backup = PHP_PATH." ".$backup_script;
  system( $backup );
  
  // When the site went down
  $site_down_start = time();
  
  // Take site down
  include( "site_down.php" );
  
  // Overwrite last_updated_date.php
  include( "reset_last_updated_date.php" );
  
  // Move to site root
  chdir( ".." );
  
  echo "Updating from SVN...\n";
  $svn = "svn update";
  system( $svn );
  
  // Move back to scripts
  chdir( "scripts" );
  
  // Execute DB sync as separate file as opposed to include
  $sync = PHP_PATH." sync_db.php";
  system( $sync );
  
  // Bring site back up
  include( "site_up.php" );
  
  // How long site was unavailable for
  $unavailable_duration = time() - $site_down_start;
  
  $duration = time() - $update_start_time;
  
  // Get last 3 log messages to SVN
  chdir( ".." );
  $svn = "svn log --limit 4";
  echo "Last 4 log messages from SVN:\n";
  system( $svn );
  
  // Check any issues are in "Resolved, awaiting update" status
  require_once( "core/db.class.php" );
  $db = new DB();
  $sql = "
    SELECT i.id, i.summary
    FROM issue i
    INNER JOIN issue_status s ON s.id = i.issue_status_id
    WHERE s.code = 'RESO'
  ";
  $db->query( $sql );
  if( $db->numrows > 0 ){
    echo "\n\nCheck these issues are now OK to be closed:\n";
    while( $row = $db->fetchRow() ){
      echo " - ".$row["summary"].": ".SITE_DEFAULTBASE."issue/edit/".$row["id"]."\n";
    }
  }
  echo "\n\nAll done. Took ".$duration."s, was unavailable for ".$unavailable_duration."s. Now add a change log! ".SITE_DEFAULTBASE."change_log/new/\n";
  
?>
