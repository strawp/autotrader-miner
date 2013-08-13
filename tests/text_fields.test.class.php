<?php
  /*
  * Unit test for fields
  */
  require_once( "settings.php" );
  class TestOfTextFields extends UnitTestCase {
    
    function __construct(){
      $this->knownstring = str_replace( chr(10), "", "line one
      line two
      
      line four
      
      
      line seven
      " );
    }
    function testSetMultiline(){
      $f = Field::create( "txtText" );
      $f->set( $this->knownstring );
      $this->assertEqual( $this->knownstring, $f->toString() );
    }
    function testSaveAndRetrieveFromDb(){
      $t = Cache::getModel("Test");
      $t->aFields["textarea"]->editable = true;
      $t->aFields["textarea"]->set( $this->knownstring );
      $t->save();
      if( !$this->assertEqual( $this->knownstring, $t->aFields["textarea"]->toString()) ){
        $this->showFirstDifferentChar( $this->knownstring, $t->aFields["textarea"]->toString() );
      }
      
      $t->get($t->id);
      if( !$this->assertEqual( $this->knownstring, $t->aFields["textarea"]->toString()) ){
        $this->showFirstDifferentChar( $this->knownstring, $t->aFields["textarea"]->toString());
      }
      $t->save();
      if( !$this->assertEqual( $this->knownstring, $t->aFields["textarea"]->toString()) ){
        $this->showFirstDifferentChar( $this->knownstring, $t->aFields["textarea"]->toString());
      }
      
      $t->get($t->id);
      if( !$this->assertEqual( $this->knownstring, $t->aFields["textarea"]->toString()) ){
        $this->showFirstDifferentChar( $this->knownstring, $t->aFields["textarea"]->toString());
      }
      $t->delete();
    }
    
    // Show the first different char between str1 and str2
    function showFirstDifferentChar( $str1, $str2 ){
      $a1 = str_split( $str1 );
      $a2 = str_split( $str2 );
      for( $i=0; $i<sizeof($a1); $i++ ){
        $c1 = $a1[$i];
        $c2 = $a2[$i];
        $o1 = ord($c1);
        $o2 = ord($c2);
        if( ord($c1) != ord( $c2 ) ){
          echo "At char $i $o1 != $o2\n";
          return;
        }
      }
    }
  }
?>