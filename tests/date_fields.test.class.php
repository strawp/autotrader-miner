<?php
  /*
  * Unit test for fields
  */
  require_once( "settings.php" );
  class TestOfDateFields extends UnitTestCase {
    
    /** 
    * Dates:
    * - YYYY-MM-DD format
    * - dd/mm/yyy format
    * - format chosen in SITE_DATEFORMAT
    * - format chosen in SITE_DATETIMEFORMAT
    * - int timestamp
    */
    
    function __construct(){
      $this->knowndatetime = mktime( 
        14,   // Hour
        30,   // Minute
        0,    // Seconds
        5,    // Month
        18,   // Day
        2011  // Year
      );
      $this->knowndatestring = date( SITE_DATEFORMAT, $this->knowndatetime );
      $this->knowndatetimestring = date( SITE_DATETIMEFORMAT, $this->knowndatetime );
    }
    
    function testDateSetByDatabaseFormat(){
      $f = Field::create( "dteDate" );
      $f->set( date("Y-m-d", $this->knowndatetime) );
      $this->assertEqual( $f->toString(), $this->knowndatestring );
    }
    
    function testDateSetByInternationalFormat(){
      $f = Field::create( "dteDate" );
      $f->set( date("d/m/Y", $this->knowndatetime) );
      $this->assertEqual( $f->toString(), $this->knowndatestring );
    }
    
    function testDateSetBySiteFormat(){
      $f = Field::create( "dteDate" );
      $f->set( date(SITE_DATEFORMAT, $this->knowndatetime) );
      $this->assertEqual( $f->toString(), $this->knowndatestring );
    }
    
    function testDateSetByTimestamp(){
      $f = Field::create( "dteDate" );
      $f->set( intval( $this->knowndatetime ) );
      $this->assertEqual( $f->toString(), $this->knowndatestring );
    }
  }
?>