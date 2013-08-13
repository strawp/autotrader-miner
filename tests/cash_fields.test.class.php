<?php
  /*
  * Unit test for fields
  */
  require_once( "settings.php" );
  class TestOfCashFields extends UnitTestCase {
    
    /** 
    * Cash fields:
    *  - Test min value works in search
    *  - Test max value works in search
    *  - Test both min and max together work in search
    *  - Test negative values appear in search
    */
    
    function __construct(){
    }
    
    
    /**
    * Test for min value filled in
    */
    function testGetUrlArgFromMinValue(){
      $f = Field::create( "cshCash" );
      $a = array( 123, 0 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "cash/12300,0" );
    }
    function testGetUrlArgFromMaxValue(){
      $f = Field::create( "cshCash" );
      $a = array( 0, 123 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "cash/0,12300" );
    }
    function testGetUrlArgFromValueRange(){
      $f = Field::create( "cshCash" );
      $a = array( 123, 321 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "cash/12300,32100" );
    }
    function testGetUrlArgFromNegativeMinValue(){
      $f = Field::create( "cshCash" );
      $a = array( -123, 0 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "cash/-12300,0" );
    }
    function testGetUrlArgFromNegativeMaxValue(){
      $f = Field::create( "cshCash" );
      $a = array( 0, -123 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "cash/0,-12300" );
    }
    function testGetUrlArgFromNegativeValueRange(){
      $f = Field::create( "cshCash" );
      $a = array( -321, -123 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "cash/-32100,-12300" );
    }

  }
?>