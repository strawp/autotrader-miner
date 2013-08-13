<?php
  /*
  * Unit test for fields
  */
  require_once( "settings.php" );
  class TestOfBooleanFields extends UnitTestCase {
    
    function __construct(){
      $this->model = new Model("Test");
      $this->model->addField( Field::create( "bleTest" ) );
    }
    
    function testSetZeroInSearchUrl(){
      $_GET["test"] = 0;
      $this->model->setFieldsFromSearchArgs();
      $this->assertEqual( $this->model->Fields->Test->value, 0 );
    }
    function testSetOneInSearchUrl(){
      $_GET["test"] = 1;
      $this->model->setFieldsFromSearchArgs();
      $this->assertEqual( $this->model->Fields->Test->value, 1 );
    }
    function testSetEitherInSearchUrl(){
      $_GET["test"] = -1;
      $this->model->setFieldsFromSearchArgs();
      $this->assertEqual( $this->model->Fields->Test->value, -1 );
    }


  }
?>