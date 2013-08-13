<?php
  /*
    AUTO-GENERATED CLASS
    Generated 30 Jun 2009 15:07
  */
  require_once( "core/model.class.php" );
  class IssueSystem extends Model{
    
    function IssueSystem(){
      $this->Model( "IssueSystem" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "bleIsDefault" ) );
      $this->addField( Field::create( "strCode", "length=4" ) );
      $this->hasinterface = false;
    }
    function afterCreateTable(){
    
      // To run after a model is created on the DB
      $aData = array(
        array( "Intranet", "INTR", 1 ),
        array( "Document Management", "DOCU" ),
        array( "Email", "EMAI" ),
        array( "General", "GENE" )
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
      
      // Make everything point to the default
      $sql = "SELECT id FROM issue_system WHERE is_default = 1";
      $db = new DB();
      $db->query( $sql );
      $row = $db->fetchRow();
      $sql = "UPDATE issue SET issue_system_id = ".$row["id"];
      $db->query( $sql );
      
    }   
  }
?>