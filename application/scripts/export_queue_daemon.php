<?php
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require_once( "../core/settings.php" );
  $site_updated = SITE_LASTUPDATE;
  $aFailedDeletes = array();
  $aSuccessfulDeletes = array();
  
  checkAlreadyRunning();
  echo "Starting export queue daemon\n";
  processQueueLoop();
  
  function checkAlreadyRunning(){
    if( !function_exists( "posix_getpid" ) ){ 
      echo "WARNING: No posix functions, not checking for another version of itself, continuing...\n";
      return false;
    }
    $aInstances = getListOfInstances();
    $thispid = intval( posix_getpid() );
    foreach( $aInstances as $instance ){
      $pid = $instance["pid"];
      if( $thispid != $pid ){
        exit;
        // die( "Script already running, exiting\n" );
      }
    }
  }
  
  function getListOfInstances(){
    $script = basename(__FILE__);
    $cmd = "ps ax | grep \"php .*$script\" | grep -v \"sh -c\" | grep -v grep";
    exec( $cmd, $out );
    $rtn = array();
    foreach( $out as $line ){
      if( preg_match( "/^\s*(?P<pid>\d+).*?([\s]+)/", $line, $m ) ){
        $m["pid"] = intval( $m["pid"] );
        $rtn[] = $m;
      }
    }
    return $rtn;
  }
  
  function processQueueLoop(){
    global $aSuccessfulDeletes, $aFailedDeletes;
    $db = new DB();
    while( true ){
      checkUpdatedDate();
      ExportQueue::processQueue();
      sleep(3);
    }
  }
  
  /**
  * Check the date the master server software was last updated, run svn update if it is greater than this server's own updated date
  */
  function checkUpdatedDate(){
    global $site_updated;
    $date = SiteGlobal::getValueFromName("LASTUPDATE");
    if( $date > $site_updated ){
      echo $date." > ".$site_updated."\n";
      echo "Master site updated, running update...\n";
      doSiteUpdate();
    }
  }
  
  /**
  * Update the site from SVN, set the last updated date var
  */
  function doSiteUpdate(){
    global $site_updated;
    
    if( SITE_SLAVE ){
      // Move to site root
      chdir( ".." );
      
      echo "Updating from SVN...\n";
      $svn = "svn update";
      system( $svn );
      
      // Move back to scripts
      chdir( "scripts" );
      include( "reset_last_updated_date.php" );
    }
    $site_updated = time();
    
    die( "Software updated, exiting. Manual restart required\n" );
  }
  
?>
