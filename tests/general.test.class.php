<?php
  /*
  * Unit test for general site-wide things
  */
  require_once( "settings.php" );
  class TestOfGeneral extends UnitTestCase {
    
    function __construct(){
    }
    
    /**
    * Test all defined paths exist
    */
    function testOfDefinedPathsExist(){
      $a = get_defined_constants(true);
      foreach( $a["user"] as $k => $v ){
        if( !preg_match( "/(_PATH|DIR)$/", $k ) ) continue;
        $this->assertTrue( file_exists( $v ), "Path of $k, $v doesn't exist" );
      }
    }
    
    function testOfTempDirWrite(){
      $this->directoryIsWriteable( SITE_TEMPDIR );
    }
    function testOfBackupDirWrite(){
      $this->directoryIsWriteable( SITE_BACKUPDIR );
    }
    
    function directoryIsWriteable( $dir ){
      $tmpfile = tempnam( $dir, "test" );
      $this->assertTrue( $tmpfile !== false );
      unlink( $tmpfile );
    }
  }
?>