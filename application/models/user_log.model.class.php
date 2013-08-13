<?php
  /*
    AUTO-GENERATED CLASS
    Generated 24 Jun 2008 09:40
  */
  require_once( "core/model.class.php" );

  class UserLog extends Model implements iFeature {
    
    function getFeatureDescription(){
      return "Records successful user logins, IPs and user agents. Used in security audits";
    }
    
    function UserLog( $id=0 ){
      $this->Model( "UserLog", $id );
      $this->addField( Field::create( "dtmCreatedAt", "display=1;displayname=Date" ) );
      $this->addField( Field::create( "lstUserId" ) );
      $this->addField( Field::create( "strUserAgent" ) );
      $this->addField( Field::create( "strRemoteIp" ) );
      $this->listby = "user_id";
      $this->orderdir = "desc";
    }
  }
?>