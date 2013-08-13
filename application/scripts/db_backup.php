<?php
  // Make sure the current dir is the dir the file is in
  chdir( dirname( $_SERVER["PHP_SELF"] ) ); 
  require_once( "../core/settings.php" );
  
  // Which tables to include or exclude, 
  //  e.g. db_backup.php -i="finance"
  //  e.g. db_backup.php -x="project deadline"
  $aOpts = getopt("x::i::");
  $aInclude = array();
  if( isset( $aOpts["x"] ) ){
    $aExclude = preg_split( "/ /", $aOpts["x"] );
  }
  if( isset( $aOpts["i"] ) ){
    $aInclude = preg_split( "/ /", $aOpts["i"] );
  }
  
  // No reason anyone would have an include AND exclude list, but anyway...
  if( sizeof( $aInclude ) > 0 && sizeof( $aExclude ) > 0 ){
    $aInclude = array_diff( $aInclude, $aExclude );
    unset( $aExclude );
  }
  
  
  $db = new DB();
  
  if( isset( $aExclude ) ){
    $file = SITE_BACKUPDIR.DB_NAME."_excluding_".join( "_", $aExclude )."_".date( "Ymd-His" ).".sql.gz";
    $rlt = $db->dumpTablesToGzipExcluding( $aExclude, $file );
  }else{
    $file = SITE_BACKUPDIR.DB_NAME."_".join( "_", $aInclude )."_".date( "Ymd-His" ).".sql.gz";
    $rlt = $db->dumpTablesToGzip( $aInclude, $file );
  }
  if( !$rlt ) echo "BACKUP FAILED!\n";
  else echo "Backup completed OK\n";
  
?>