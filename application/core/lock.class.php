<?php

/**
* Static methods for creating and deleting lock files uniformly
* Lock files are simply uniquely named files in the temp dir
*/

require_once( "core/settings.php" );
require_once( "core/functions.php" );

class Lock{
  static function create( $lockname, $info="" ){
    $file = self::getFilename( $lockname );
    if( !file_exists( $file ) ){
      $rtn = file_put_contents( $file, $lockname."\nThis is a lock file created @ ".date( "Y-m-d H:i:s" )."\n".$info );
      if( $rtn !== false ) return true;
      return false;
    }
    return true;
  }
  
  static function remove( $lockname ){
    $file = self::getFilename( $lockname );
    if( !file_exists( $file ) ) return true;
    return unlink( $file );
  }
  
  static function getFilename( $lockname ){
    return SITE_TEMPDIR.preg_replace( "/[^-0-9_a-zA-Z\.]/", "_", $lockname ).".lock";
  }
  
  static function exists( $lockname ){
    $file = self::getFilename( $lockname );
    return file_exists( $file );
  }
}
