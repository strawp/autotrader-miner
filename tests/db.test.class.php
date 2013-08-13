<?php
  /*
  * Unit test for the oracle database model
  */
  require_once( "settings.php" );
  class TestOfDb extends UnitTestCase {
    
    function __construct(){
      $this->safestring = "safestring";           // This string should be unchanged
      $this->dbiquotestring = "string ' string";  // The single quote in this should be escaped
      $this->db = new DB();
    }
    
    function testConnectToDbServer(){
      $db = new DB();
      $this->assertNotNull( $db->db );
    }
    
    /**
    * Check basic queries work
    */
    function testOfQuery(){
      
      // A query which should work
      $this->db->query( "SELECT * FROM user LIMIT 10" );
      $this->assertEqual( "", $this->db->error );
      $row = $this->db->fetchRow();
      $this->assertTrue( $row );
      
      
      // A query which shouldn't work
      $this->db->query( "SPLURGLE eggs FROM eisengard purple dishwasher monkey" );
      $this->assertNotEqual( "", $this->db->error );
    }
    
    function testRetrieveWhereEqualTo(){
      $this->assertTrue( $this->db->retrieveWhereEqualTo( "user", "id", "1" ) );
      $row = $this->db->fetchRow();
      $this->assertFalse( empty( $row ) );
    }

    /**
    * Check at least a few of the necessary tables should be present
    */
    function testOfGetTables(){
      $aPresent = array( "user", "user_group" );
      $aTables = $this->db->getTables();
      foreach( $aPresent as $table ){
        $this->assertTrue( in_array( $table, $aTables ) );
      }
    } 
    
    /**
    * Check db can query columns
    */
    function testofGetColumns(){
      $aPresent = array( "name", "first_name", "last_name" );
      $aCols = $this->db->getColumns("user");
      foreach( $aPresent as $col ){
        $pass = false;
        foreach( $aCols as $a ){
          if( $col == $a["Field"] ){
            $pass = true;
            continue;
          }
        }
        $this->assertTrue($pass);
      }
    }
    
    /**
    * Table manipulation tests
    */
    function testOfTableManipulation(){
      // Create a table
      $tablename = "test_table_".substr( sha1( time() ), 0, 5 );
      $this->db->createTable( 
        $tablename, 
        array(
          array( 
            "name" => "name",
            "datatype" => "varchar(255)",
            "default" => "null"
          ),
          array( 
            "name" => "count_of_things",
            "datatype" => "int(11)",
            "default" => "null"
          ),
          array(
            "name" => "description",
            "datatype" => "text",
            "default" => "null"
          )
        )
        , array()
      );
      $this->assertEqual( "", $this->db->error );
      
      // Check that the table is now in there
      $aTables = $this->db->getTables();
      $this->assertTrue( in_array( $tablename, $aTables ) );
      
      // Alter a column
      $this->db->modifyColumn( $tablename, "count_of_things", "DOUBLE(3,2)", "");
      $this->assertEqual( "", $this->db->error );
      
      // Drop a column
      $this->db->removeColumn( $tablename, "count_of_things" );
      $this->assertEqual( "", $this->db->error );
      
      // Add a column
      $this->db->addColumn( $tablename, "count_of_things", "int(11)" );
      $this->assertEqual( "", $this->db->error );
      
      // Drop table
      $this->db->dropTable( $tablename );
      $this->assertEqual( "", $this->db->error );
      
      // Check that the table has gone
      $aTables = $this->db->getTables();
      $this->assertFalse( in_array( $tablename, $aTables ) );
    } 
    
    /**
    * Check FOUND_ROWS is working properly
    */
    function testOfFoundRows(){
      // Assumption: There is more than one user present in table "user"
      $this->db->query( "SELECT SQL_CALC_FOUND_ROWS * FROM user LIMIT 1" );
      $this->assertTrue( $this->db->foundrows > 1 );
      $this->assertTrue( $this->db->numrows == 1 );
    }
    
    function testOfBackup(){
      $this->assertTrue( $this->db->dumpTablesToGzip( array( "user" ) ) );
      if($this->db->dumpfile) unlink( $this->db->dumpfile );
    }
    
    /*
    function testOfParameterisedQuery(){
      $this->db->parameterisedQuery( "SELECT * FROM user LIMIT ?", array( 10 ) );
      pre_r( $this->db->fetchRow() );
    }
    */
    
    /**
    * Test a simple select count... query from a known table
    */
    function testExecSelectStatement(){
      $db = $this->db;
      $db->query( "SELECT COUNT(*) as num FROM user" );
      $this->assertTrue( $db->numrows > 0 );
    }
    
    function testEscapeSafeString(){
      $db = $this->db;
      $this->assertEqual( $this->safestring, $db->escape( $this->safestring ) );
    }
    
    function testEscapeDbiQuoteString(){
      $db = $this->db;
      $this->assertNotEqual( $this->dbiquotestring, $db->escape( $this->dbiquotestring ) );
    }
  }
?>
