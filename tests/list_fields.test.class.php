<?php
  /*
  * Unit test for fields
  */
  require_once( "settings.php" );
  class TestOfListFields extends UnitTestCase {
    
    /** 
    * Lists:
    * - List / foreign keys by string name
    * - List / ints
    * - List / string literals
    * - List / indexes of item in list array
    * - List / arrays
    */
    
    function __construct(){
      $this->known_username = "test";
      $this->known_userid = 1;
    }
    
    /**
    * Test that a foreign key can be set by string name of that item
    */
    function testListSetsForeignKeyByString(){
      $f = Field::create( "lstUserId" );
      $f->set( $this->known_username );
      $this->assertEqual( $f->value, $this->known_userid );
    }
    
    /**
    * Set a foreign key by the key int value
    */
    function testListSetsForeignKeyByInt(){
      $f = Field::create( "lstUserId" );
      $f->set( $this->known_userid );
      $this->assertEqual( $f->value, $this->known_userid );
    }
    
    /**
    * Set a list to a literal value string
    */
    function testListSetsStringLiteral(){
      $f = Field::create( "lstAString" );
      $val = "the value";
      $f->set( $val );
      $this->assertEqual( $f->value, $val );
    }
    
    /**
    * Set to the index of that value in a pre-defined list
    */
    function testListSetsIndexOfListItem(){
      $f = Field::create( "lstList" );
      $f->listitems = array(
        "Dave",
        "Mary",
        "John",
        "Pete",
        "2-pac",
        "Ben"
      );
      $f->set( 2 );
      $this->assertEqual( $f->toString(), "John" );
    }
    
    function testListSetsIndexOfListItemKeyedByString(){
      $f = Field::create( "lstList" );
      $f->listitems = array(
        "LIVE" => "Production server",
        "DEV" => "Development server",
        "TEST" => "Test server",
        "DEMO" => "Demo server"
      );
      $f->set( "TEST" );
      $this->assertEqual( $f->toString(), "Test server" );
    }
    
    /**
    * Set the value to an array
    */
    function testListSetsValueToArray(){
      $f = Field::create( "lstList" );
      $a = array( 123, 456 );
      $f->set( $a );
      $this->assertIsA( $f->value, "array" );
      $this->assertNotNull( $f->value[0] );
      $this->assertNotNull( $f->value[1] );
      $this->assertEqual( $f->value[0], $a[0] );
      $this->assertEqual( $f->value[1], $a[1] );
    }
    
    /*
    * Test if the value can be set from an array of strings
    */
    function testListSetsValueFromStringArray(){
      $f = Field::create( "lstList" );
      $f->listitems = array(
        "asfd@asfd.asfd" => "Bloke",
        "lkiuyb@oluib.com" => "No-one",
        "kujyb@asf.acsd" => "Someone"
      );
      $a = array( "asfd@asfd.asfd", "kujyb@asf.acsd" );
      $f->set( $a );
      $this->assertIsA( $f->value, "array" );
      $this->assertNotNull( $f->value[0] );
      $this->assertNotNull( $f->value[1] );
      $this->assertEqual( $f->value[0], $a[0] );
      $this->assertEqual( $f->value[1], $a[1] );
    }
    
    /**
    * Test for getUrlArg with a foreign key
    */
    function testListGetUrlArgFromForeignKey(){
      $f = Field::create( "lstUserId" );
      $f->set( $this->known_userid );
      $this->assertEqual( $f->getUrlArg(), "user_id/".$this->known_userid );
    }
    
    /**
    * Test for values in an array
    */
    function testListGetUrlArgFromArray(){
      $f = Field::create( "lstList" );
      $a = array( 123, 456 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "list/123,456" );
    }
    function testListGetUrlArgFromArray2(){
      $f = Field::create( "lstList" );
      $a = array( 3 );
      $f->set( $a );
      $this->assertEqual( $f->getUrlArg(), "list/3" );
    }
    function testListGetUrlArgSubmittedValue(){
      $f = Field::create( "lstList" );
      $_POST["lstList"] = array( 3, 4 );
      $f->getSubmittedValue();
      $this->assertEqual( $f->getUrlArg(), "list/3,4" );
    }
    
    function testListGetUrlArgKeyedByString(){
      $f = Field::create( "lstList" );
      $f->listitems = array(
        "LIVE" => "Production server",
        "DEV" => "Development server",
        "TEST" => "Test server",
        "DEMO" => "Demo server"
      );
      $f->set( "TEST" );
      $this->assertEqual( $f->getUrlArg(), "list/TEST" );
    }
  }
?>