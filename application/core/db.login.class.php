<?php

  /**
  * Local DB login class
  */
  
  require_once( "core/model.class.php" );
  require_once( "ldap.class.php" );
  require_once( "db.class.php" );
  require_once( "models/user.model.class.php" );
  require_once( "models/user_user_group.model.class.php" );
  
  class Login extends Model{
  
    function Login(){
      $this->Model( "Login" );
      
      $this->addField( Field::create( "strUsername", "displayname=Username e.g. \"abc123\"" ) );
      $this->addField( Field::create( "pasPassword" ) );
      
      /*
      $role = Field::create( "lstRole", "required=1" );
      $role->listitems = array( "Staff" => "Staff", "Student" => "Student" );
      $this->addField( $role );
      $this->startFieldSet( "New Users, please also enter..." );
      $this->addField( Field::create( "strFirstName" ) );
      $this->addField( Field::create( "strLastName" ) );
      $this->endFieldSet();
      */
      
      $this->hastable = false;
      $this->tablename = "logout";
      $this->prompt = "Please log in with the username and password you have been provided.";
    }
    
    /**
    * Super user switch user method
    **/
    function switchUser( $username ){
      if( !SessionUser::isAdmin() ) return;
      $user = new User();
      $user->getByName( $username );
      if( $user->id == 0 ) return false;
      return $this->buildUserLoginSessionData( $user );
    }
    
    /**
    * Create a bunch of data to insert into a user's login session
    **/
    function buildUserLoginSessionData($user){
      if( $user->id == 0 ) return false;
      $aReturn = array( 
        "username"    => $user->aFields["name"]->toString(),
        "firstname"   => $user->aFields["first_name"]->toString(),
        "lastname"    => $user->aFields["last_name"]->toString(),
        "title"       => $user->aFields["title"]->toString(),
        "mail"        => $user->aFields["name"]."@".SITE_EMAILDOMAIN,
        "role"        => "Staff",
        "sessidhash"  => hash( SITE_HASHALGO, session_id().SITE_SALT ),
        "sessioncreated" => date( "Y-m-d H:i:s" )
      );
      if( $aReturn["role"] == "Employee" ) $aReturn["role"] = "Staff";
            
      $aReturn["id"] = $user->id;
      foreach( array( "is_admin", "last_logged_in" ) as $name ){
        $aReturn[$name] = $user->aFields[$name]->toString();
      }
      
      // Set last logged in date/time
      $user->aFields["last_logged_in"]->value = time();
      $user->save();
      
      return $aReturn;
    }
    
    /**
    * Get login credentials, return false or user's info
    **/
    function doLogin( $username="", $password="" ){
      
      Cache::flushModels();
      
      $user = new User();
      $user->getByCredentials( $username, $password, $role=0, $fn="", $ln="" );
      if( $user->id == 0 ) return false;
      
      // Check they have widgets
      $user->setupDefaultWidgets();
      
      return $this->buildUserLoginSessionData( $user );
    }
  }
?>