<?php
  /**
  * Run miscellaneous jobs that need doing approximately daily
  *
  * Do not assume this is *only* run daily - more frequent runs (manually executed etc) should be non-harmful.
  * Group all tasks into functions and add the function call at the top of the script.
  */
  
  // Make sure the current dir is the dir the file is in
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  $db = new DB();
  $start = time();
  echo "Starting cron_daily at ".date( SITE_DATETIMEFORMAT )."\n";
  
  // Backup DB
  $rlt = $db->dumpTablesToGzipExcluding( array("finance") );
  
  // Clear up tmp folder, run every garbageCollect method
  doGarbageCollection();
  
  // Run anything in the queue. If the export_queue_daemon script is working, this shouldn't pick up anything
  ExportQueue::processQueue("-10 minutes");

  // List of things which will probably have changed in the last day
  $aVolatileModels = array(
  );
  
  echo "Recursively recaching models: ".join( ", ", $aVolatileModels )."\n";
  
  // Recursively recache database figures where needed
  foreach( $aVolatileModels as $mname ){
    $m = new $mname();
    if( !$m ) continue;
    $oTree = $m->getCacheDependencyTree();
    echo "$mname Recache tree:\n".printBranch( $oTree );
    $m->recache();
    $m->recacheDependants();
  }
  
  // Report subscriptions
  UserReport::runReports();
  
  echo "Finished cron_daily at ".date( SITE_DATETIMEFORMAT ).", took ".formatPeriod( time() - $start, true )."\n";
  
  
  /**
   * Function to print the tree structure of a model's recache dependents
   * 
   * @param obj $branch
   * @param int $depth
   * @return string
   */
  function printBranch( $branch, $depth=0 ){
    $space = str_pad( "", $depth*2, " " );
    $rtn = "";
    $rtn .= $space.=$branch->name."\n";
    foreach( $branch->dependants as $dep ){
      $rtn.=printBranch( $dep, $depth+1 );
    }
    return $rtn;
  }
  
  
  
  /**
  * Garbage collection
  */
  function doGarbageCollection(){
    echo "Garbage collection\n";
    // Clear up tmp folder
    $time = strtotime( "yesterday" );
    
    // Get everything called export*
    foreach( glob( SITE_TEMPDIR."export*" ) as $file ){
      // Delete if older than 24hrs
      if( filemtime( $file ) < $time ) unlink( $file );
    }
    
    // Run every static method called garbageCollect
    // Get list of all models
    $modeldir = opendir( SITE_WEBROOT."/models" );
    $aStoredData = array();
    while( $file = readdir( $modeldir ) ){
      if( !preg_match( "/^(.*)\.model\.class\.php/", $file, $m ) ) continue;
      $name = underscoreToCamel( $m[1] );
      $m = Cache::getModel( $name );
      if( !$m ) continue;
      if( !method_exists( $m, "garbageCollect" ) ) continue;
      $aGarbageCollecters[] = $m->name;
    }
    closedir( $modeldir );
    foreach( $aGarbageCollecters as $m ){
      echo " - $m\n";
      $m::garbageCollect();
    }
    echo "Done garbage collection\n";
  }
  
  /**
  * Send the email report for issue responses due
  */
  function sendIssueResponseDueReport(){
    $day = date( "N" );
    
    // Don't send these at the weekend
    if( $day > 5 ) return false;
    
    $r = new IssueResponseDueReport();
    $r->compile();
    
    if( preg_match( "/There are currently no issues/", $r->html ) ) return false;
    
    // Get list of admins
    $sql = "SELECT name FROM user WHERE is_admin = 1";
    $db = new DB();
    $db->query( $sql );
    
    while( $row = $db->fetchRow() ){
      $r->sendHtmlEmail( $row["name"] );
    }
  }

  
?>
