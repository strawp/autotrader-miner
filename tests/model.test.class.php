<?php
  /*
  * Unit test for the oracle database model
  */
  require_once( "settings.php" );
  class TestOfModel extends UnitTestCase {
    
    function __construct(){
      $this->modelname = "TestModel";
      $this->tablename = "test_model";
    }
    
    function testConstructModel(){
      $m = new Model( $this->modelname );
      $this->assertEqual( $m->name, $this->modelname );
    }
    
    function testCreateAndDropTable(){
      $m = new Model( $this->modelname );
      $db = new DB();
      $aTables = $db->getTables();
      $this->assertTrue( array_search( $this->tablename, $aTables ) === false );
      $m->addField( Field::create( "strName" ) );
      $m->createTable();
      $aTables = $db->getTables();
      $this->assertTrue( array_search( $this->tablename, $aTables ) !== false );
      $m->dropTable();
      $aTables = $db->getTables();
      $this->assertTrue( array_search( $this->tablename, $aTables ) === false );
    }
    
    function testGetEmailAddressesOneField(){
      $m = new Model( $this->modelname );
      $m->addField( Field::create( "emaEmail" ) );
      $email = "asdf@qwre.com";
      $m->aFields["email"]->set( $email );
      $correct = array(
        array( "id" => $email, "name" => $m->displayname." Email: ".$email )
      );
      $this->assertEqual( $m->getEmailAddresses(), $correct );
    }
    function testGetEmailAddressesTwoFields(){
      $m = new Model( $this->modelname );
      $m->addField( Field::create( "emaEmail" ) );
      $m->addField( Field::create( "emaEmailTwo" ) );
      $email = "asdf@qwre.com";
      $email2 = "qwert@asdf.com";
      $m->aFields["email"]->set( $email );
      $m->aFields["email_two"]->set( $email2 );
      $aAddresses = $m->getEmailAddresses();
      $correct = array(
        array( "id" => $email, "name" => $m->displayname." Email: ".$email ),
        array( "id" => $email2, "name" => $m->displayname." Email Two: ".$email2 )
      );
      for( $i=0; $i<sizeof($aAddresses); $i++ ){
        $address = $aAddresses[$i];
        $c = $correct[$i];
        $this->assertEqual( $address["id"], $c["id"] );
        $this->assertEqual( $address["name"], $c["name"] );
      }
    }
    
    /**
    * Test that all models can be constructed - covers cases with Interfaces not correct
    */
    function constructModels(){
      $dir = opendir( SITE_WEBROOT."/models" );
      while( $file = readdir( $dir ) ){
        if( !preg_match( "/^(.*)\.model\.class\.php/", $file, $m ) ) continue;
        $name = underscoreToCamel( $m[1] );
        try{
          $m = Cache::getModel( $name );
          $this->assertIsA( $m, "object" );
        }
        catch(Exception $e){
          $this->fail( "Exception thrown trying to create a Model called \"$name\"" );
        }
      }
      closedir( $modeldir );
    }
  }
?>