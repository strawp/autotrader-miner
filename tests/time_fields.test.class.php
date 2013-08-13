<?php
  /*
  * Unit test for fields
  */
  require_once( "settings.php" );
  class TestOfTimeFields extends UnitTestCase {
    
    function __construct(){
      $this->othertimezone = "America/Los_Angeles";
    }
    function testTimeSetToMidnightGmt(){
      $f = Field::create("tmeTime");
      $tz = @date_default_timezone_get();
      date_default_timezone_set('Europe/London');
      $str = "00:00";
      $f->set($str);
      $this->assertEqual($str,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToMidnightOther(){
      $f = Field::create("tmeTime");
      $tz = @date_default_timezone_get();
      date_default_timezone_set($this->othertimezone);
      $str = "00:00";
      $f->set($str);
      $this->assertEqual($str,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToMiddayGmt(){
      $f = Field::create("tmeTime");
      $tz = @date_default_timezone_get();
      date_default_timezone_set('GMT');
      $str = "12:00";
      $f->set($str);
      $this->assertEqual($str,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToMiddayOther(){
      $f = Field::create("tmeTime");
      $tz = @date_default_timezone_get();
      date_default_timezone_set($this->othertimezone);
      $str = "12:00";
      $f->set($str);
      $this->assertEqual($str,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToArbitraryGmt(){
      $f = Field::create("tmeTime");
      $tz = @date_default_timezone_get();
      date_default_timezone_set('GMT');
      $str = "01:01";
      $f->set($str);
      $this->assertEqual($str,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToArbitraryOther(){
      $f = Field::create("tmeTime");
      $tz = @date_default_timezone_get();
      date_default_timezone_set($this->othertimezone);
      $str = "01:01";
      $f->set($str);
      $this->assertEqual($str,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToDefaultGmt(){
      $f = Field::create("tmeTime");
      $f->default = "13:45";
      $tz = @date_default_timezone_get();
      date_default_timezone_set('GMT');
      $f->setDefault();
      $this->assertEqual($f->default,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
    function testTimeSetToDefaultOther(){
      $f = Field::create("tmeTime");
      $f->default = "13:45";
      $tz = @date_default_timezone_get();
      date_default_timezone_set($this->othertimezone);
      $f->setDefault();
      $this->assertEqual($f->default,$f->toString());
      if( $tz ) date_default_timezone_set($tz);
    }
  }
?>