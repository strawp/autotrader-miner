<?php
  /*
  * Unit test for the oracle database model
  */
  require_once( "settings.php" );
  class TestOfFunctions extends UnitTestCase {
    
    function __construct(){
    }
    
    function testAcademicPeriodToDate(){
      $this->assertEqual( date( "Y-m-d", academicPeriodToDate( "201001" ) ), "2010-08-01" );
      $this->assertEqual( date( "Y-m-d", academicPeriodToDate( "201002" ) ), "2010-09-01" );
      $this->assertEqual( date( "Y-m-d", academicPeriodToDate( "201005" ) ), "2010-12-01" );
      $this->assertEqual( date( "Y-m-d", academicPeriodToDate( "201006" ) ), "2011-01-01" );
      $this->assertEqual( date( "Y-m-d", academicPeriodToDate( "201011" ) ), "2011-06-01" );
      $this->assertEqual( date( "Y-m-d", academicPeriodToDate( "201012" ) ), "2011-07-01" );
    }
    
    function testDateToAcademicPeriod(){
      $this->assertEqual( dateToAcademicPeriod( "2010-08-01" ), "201001" );
      $this->assertEqual( dateToAcademicPeriod( "2010-09-01" ), "201002" );
      $this->assertEqual( dateToAcademicPeriod( "2010-12-01" ), "201005" );
      $this->assertEqual( dateToAcademicPeriod( "2011-01-01" ), "201006" );
      $this->assertEqual( dateToAcademicPeriod( "2011-06-01" ), "201011" );
      $this->assertEqual( dateToAcademicPeriod( "2011-07-01" ), "201012" );
    }
    
    function testUnderscoreSplit(){
      $this->assertEqual( underscoreSplit( "first_second" ), "First Second" );
      $this->assertEqual( underscoreSplit( "first_second_third" ), "First Second Third" );
    }
    function testUnderscoreToCamel(){
      $this->assertEqual( underscoreToCamel( "first_second" ), "FirstSecond" );
      $this->assertEqual( underscoreToCamel( "first_second_third" ), "FirstSecondThird" );
    }
    function testCamelSplit(){
      $this->assertEqual( camelSplit( "FirstSecond" ), "First Second" );
      $this->assertEqual( camelSplit( "FirstSecondThird" ), "First Second Third" );
    }
    function testCamelToUnderscore(){
      $this->assertEqual( camelToUnderscore( "FirstSecond" ), "first_second" );
      $this->assertEqual( camelToUnderscore( "FirstSecondThird" ), "first_second_third" );
    }
     
    /**
    * Tests h() function
    */
    function testEscapeHtml(){
    
      // Dangerous HTML attribute values
      // Single quote
      $this->assertEqual( h( "' onclick='whatevs'" ), "&#039; onclick=&#039;whatevs&#039;" );
      
      // Double quote
      $this->assertEqual( h( '" onclick="whatevs"' ), "&quot; onclick=&quot;whatevs&quot;" );
      
      // HTML markup
      $this->assertEqual( h( "<script>alert('xss');</script>" ), "&lt;script&gt;alert(&#039;xss&#039;);&lt;/script&gt;" );
      
      // Ampersand
      $this->assertEqual( h( "&" ), "&amp;" );
      
      // Known bad string
      $bad = 'South Korea â€" English Education Providers';
      $good = 'South Korea ... English Education Providers';
      $this->assertEqual( h( $bad ), $good );
    }
  }
?>