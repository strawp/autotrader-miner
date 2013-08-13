<?php
  
  require_once( "core/model.class.php" );

  class ChangeLog extends Model implements iFeature {
    
    function ChangeLog( $id=0 ){
      $this->Model( "ChangeLog", $id );
      $this->addAuth( "role", "Staff", "r" );
      $this->addField( Field::create( "dteDate", "default=now" ) );
      $this->addField( Field::create( "txtDetails" ) );
      $this->listby = "date";
      $this->orderdir = "desc";
      $this->liststyle = "list";
    }
    function getFeatureDescription(){
      return "Records all changes and updates made to the system for users to review";
    }
    
  }


?>