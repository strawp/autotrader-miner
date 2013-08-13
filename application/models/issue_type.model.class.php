<?php
  /*
    AUTO-GENERATED CLASS
    Generated 30 Jun 2009 15:07
  */
  require_once( "core/model.class.php" );
  class IssueType extends Model{
    
    function IssueType(){
      $this->Model( "IssueType" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "bleIsDefault" ) );
      $this->addField( Field::create( "strCode", "length=4" ) );
      $this->hasinterface = false;
    }
    function afterCreateTable(){
    
      // To run after a model is created on the DB
      $aData = array(
        array( "Bug / Error", "BUG", 1 ),
        array( "Feature request / Suggestion / Idea", "REQU" ),
        array( "Miscellaneous question", "QUES" )
      );
      
      foreach( $aData as $row ){
        $this->id = 0;
        $this->aFields["name"]->value = $row[0];
        $this->aFields["code"]->value = $row[1];
        
        // Default option
        if( array_key_exists( 2, $row ) ) $this->aFields["is_default"]->value = $row[2];
        else $this->aFields["is_default"]->value = 0;
        
        $this->save();
      }
    }   
  }
?>