<?php
  /*
  * Unit test for common javascript errors (to the extent that you can in PHP)
  */
  require_once( "settings.php" );
  class TestOfJavascript extends UnitTestCase {
    
    function __construct(){
      $this->jsdir = realpath( SITE_COREDIR."/../js" );
    }
    
    /**
    * Test that there are no references to "console" which are not commented out
    * One assertion per file found
    */
    function testForUncommentedConsoleReferences(){
      $aDirs = array( 
        $this->jsdir, 
        $this->jsdir."/model" 
      );
      
      // For each search dir
      foreach( $aDirs as $dir ){
        $dh = opendir( $dir );
        if( !$dh ) continue;
        
        // Read all filenames of dir
        while( $file = readdir( $dh ) ){
          $file = trim( $file );
          if( !preg_match( "/\.js$/", $file ) ) continue;
          
          // For *.js files found
          $path = realpath( $dir."/".$file );
          $aJs = file( $path );
          $aFound = array();
          
          // Search each line for "console"
          foreach( $aJs as $num => $line ){
            if( preg_match( "/^\s*console/", $line ) ){
            
              // Keep record of line numbers encountered
              $aFound[] = $num+1;
            }
          }
          $count = sizeof( $aFound );
          
          // A clean file will have a count of zero
          $this->assertTrue( 
            $count == 0, 
            "Uncommented ".plural( "reference", $count )." to \"console\" found in ".$path." "
              .plural( "line", $count )." ".join( ", ", $aFound ) 
          );
        }
      }
    }
  }
?>