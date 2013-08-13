<?php
  /*
    AUTO-GENERATED CLASS
    Generated 30 Jun 2009 15:07
  */
  require_once( "core/model.class.php" );
  class IssueStatus extends Model{
    
    function IssueStatus(){
      $this->Model( "IssueStatus" );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "bleIsDefault" ) );
      $this->addField( Field::create( "bleIsArchive" ) );
      $this->addField( Field::create( "strCode", "length=4" ) );
      $this->hasinterface = false;
    }
    function afterCreateTable(){
    
      // To run after a model is created on the DB
      $aData = array(
        array( "1) New", "NEW", 1 ),
        array( "2) Report acknowledged", "ACK" ),
        array( "3) Issue confirmed", "CONF" ),
        array( "4) Feedback required", "FEED" ),
        array( "5) Formal review required", "REVI" ),
        array( "6) Queued for development", "QUEU" ),
        array( "7) Resolved, awaiting next system update", "RESO" ),
        array( "8) Closed", "CLOS", 0, 1 )
      );
      
      foreach( $aData as $row ){
        $this->id = 0;
        $this->aFields["name"]->value = $row[0];
        $this->aFields["code"]->value = $row[1];
        
        // Default option
        if( array_key_exists( 2, $row ) ) $this->aFields["is_default"]->value = $row[2];
        else $this->aFields["is_default"]->value = 0;
        
        // Option to set the issue to archive
        if( array_key_exists( 3, $row ) ) $this->aFields["is_archive"]->value = $row[3];
        else $this->aFields["is_archive"]->value = 0;
        $this->save();
      }      
    }   
  }
?>