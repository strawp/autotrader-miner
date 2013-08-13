<?php
  /*
    AUTO-GENERATED CLASS
  */
  require_once( "core/member_interface.class.php" );
  class UserUserGroup extends MemberInterface{
    function UserUserGroup( $id=0 ){
      $this->MemberInterface( "User", "UserGroup", $id );
      $this->addAuth( "is_admin", "Yes" );
    }
  }
?>