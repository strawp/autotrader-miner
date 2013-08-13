<?php
  /*
  * Unit test for LDAP features
  */
  require_once( "settings.php" );
  class TestOfLdap extends UnitTestCase {
    
    function __construct(){
    }
    
    function testForLdapConnectDefined(){
      $this->assertTrue( function_exists( "ldap_connect" ) );
    }
    function testOfLdapConnect(){
      $ds = ldap_connect( LDAP_URL );
      $this->assertTrue( $ds != null );
    }
  }
?>