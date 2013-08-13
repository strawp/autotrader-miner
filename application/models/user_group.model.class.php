<?php
  /*
    AUTO-GENERATED CLASS
  */
  require_once( "core/model.class.php" );

  class UserGroup extends Model implements iFeature {
    function getFeatureDescription(){
      return "List of user groups - these inform access and customisation options for users within the system";
    }
    function UserGroup( $id=0 ){
      $this->Model( "UserGroup", $id );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "strCode", "unique=1;required=1;length=4;helphtml=<p><strong>Do not</strong> edit this field unless you know exactly what you are doing. The content of this field is essential for the operation of the system.</p>" ) );
      $this->addField( Field::create( "txtDescription", "visible=0" ) );
      $this->addField( Field::create( "memUserUserGroup", "displayname=Users;parent_tablename=".$this->tablename.";parent_displayname=".$this->displayname ) );
      $this->addAuth( "is_admin", "Yes" );
      $this->aSearchFields = array( "name" );
      $this->aResultsFields = $this->aSearchFields;
      $this->inits[] = "showDescription";
    }
    function getByCode( $code ){
      $db = new DB();
      return $this->retrieveByClause( "WHERE code = '".$db->escape( $code )."'" );
    }
    function showDescription(){
      if( !SessionUser::isAdmin() ) return;
      $this->aFields["description"]->visible = true;
    }
    function addUserById( $id ){
      if( $this->userIsAMember( $id ) ) return true;
      if( $this->id == 0 ) return false;
      $db = new DB();
      $db->query( "INSERT INTO user_user_group ( user_id, user_group_id ) VALUES ( ".intval( $id ).", ".intval( $this->id )." )" );
      if( $db->affectedrows > 0 ) return true;
      return false;
    }
    function removeUserById( $id ){
      if( $this->id == 0 ) return false;
      $db = new DB();
      $db->query( "DELETE FROM user_user_group WHERE user_id = ".intval( $id )." AND user_group_id = ".intval( $this->id ) );
      return true;
    }
    function getAllUserIds(){
      if( $this->id == 0 ) return false;
      $db = new DB();
      $db->query( "
        SELECT DISTINCT user_id 
        FROM user_user_group uug
        WHERE user_group_id = ".intval($this->id)."
      ");
      $aUserIds = array();
      while( $row = $db->fetchRow() ){
        $aUserIds[] = $row["user_id"];
      }
      return $aUserIds;
    }
    
    /**
    * Check if a user is a member of this group
    * @param int $id the user to check
    * @return bool
    */
    function userIsAMember( $id ){
      if( $this->id == 0 ) return false;
      $db = new DB();
      $db->query( "SELECT id FROM user_user_group WHERE user_id = ".intval( $id )." AND user_group_id = ".intval( $this->id ) );
      if( $db->numrows > 0 ) return true;
      return false;
    }
    
    /**
    * Default user groups
    */
    function afterCreateTable(){
    
      // To run after a model is created on the DB
      $aData = array(
        array( "Editors", "EDIT", "Can edit some data on the site" ),
        array( "Reviewers", "REVI", "Has read access to more than the default member of staff" )
      );
      
      foreach( $aData as $row ){
        $this->id = 0;
        $this->aFields["name"]->value = $row[0];
        $this->aFields["code"]->value = $row[1];
        $this->aFields["description"]->value = $row[2];
        
        $this->save();
      }
    }
  }
?>