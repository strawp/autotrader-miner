<?php
  
  /**
  * LDAP login class
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
      $role = Field::create( "lstRole", "required=1" );
      $role->listitems = array( "Staff" => "Staff", "Student" => "Student" );
      $this->addField( $role );
      
      $this->startFieldSet( "New Users, please also enter..." );
      $this->addField( Field::create( "strFirstName" ) );
      $this->addField( Field::create( "strLastName" ) );
      $this->endFieldSet();
      
      $this->hastable = false;
      $this->tablename = "logout";
      $this->prompt = "Please log in.";
    }
    
    /**
    * Super user switch user method
    **/
    function switchUser( $username ){
      if( !SessionUser::isAdmin() ) return;
      
      $ldap = new Ldap();
      $ldap->bindWithApplicationCredentials();
      if( !$ldap->ds ){
        return false;
      }
      
      // Search for the user
      $role = "Staff";
      $search = "sAMAccountName=".$ldap->escape( $username );
      $aInfo = $ldap->search( "OU=".$ldap->escape( $role ), $search );
      if( !$aInfo || sizeof( $aInfo ) == 0 || $aInfo["count"] == 0 ) return false;
      $aInfo = $aInfo[0];
      return $this->buildUserLoginSessionData( $aInfo );
    }
    
    /**
    * Create a bunch of data to insert into a user's login session
    **/
    function buildUserLoginSessionData($aInfo){
      if( !isset( $aInfo["description"][0] ) || empty( $aInfo["description"][0] ) ) $aInfo["description"][0] = "Staff";
      $aReturn = array( 
        "username"    => $aInfo["samaccountname"][0],
        "firstname"   => $aInfo["givenname"][0],
        "lastname"    => $aInfo["sn"][0],
        "title"       => $aInfo["title"][0],
        "mail"        => $aInfo["mail"][0],
        "role"        => $aInfo["description"][0],
        "sessidhash"  => hash( SITE_HASHALGO, session_id().SITE_SALT ),
        "sessioncreated" => date( "Y-m-d H:i:s" ),
        "remote_address" => $_SERVER["REMOTE_ADDR"]
      );
      if( $aReturn["role"] == "Employee" ) $aReturn["role"] = "Staff";
      // $ldap->close();
            
      // Does this user exist in our table?
      $user = Cache::getModel( "User" );
      $id = $user->getIdByName( $aReturn["username"] );
      if( !$id ){
        
        // Check they're not in there without a uni name
        $user->retrieveByClause( "WHERE first_name like '".$aReturn["firstname"]."' AND last_name like '".$aReturn["lastname"]."'" );
            
        // Create user
        $user->aFields["name"]->value = $aReturn["username"];
        $user->aFields["first_name"]->value = $aReturn["firstname"];
        $user->aFields["last_name"]->value = $aReturn["lastname"];
        $user->aFields["title"]->value = $aReturn["title"];
        $id = $user->save();
      }
      $user->retrieve( $id );
      $aReturn["id"] = $user->id;
      foreach( array( "is_admin", "last_logged_in" ) as $name ){
        $aReturn[$name] = $user->aFields[$name]->toString();
      }
      
      // Check they have widgets
      $user->setupDefaultWidgets();
      
      // Set last logged in date/time
      $user->aFields["last_logged_in"]->value = time();
      $user->save();
      
      return $aReturn;
    }
    
    /**
    * Get login credentials, return false or user's info
    **/
    function doLogin( $username="", $password="", $role="", $firstname="", $lastname="" ){
      
      Cache::flushModels();
      
      // Bind to LDAP
      $ldap = Cache::getModel( "LDAP" );
      $ldap->bind( $username, $password, $role, $firstname, $lastname );
      if( !$ldap->ds ){
        return false;
      }
      
      // Search for the user
      $search = "sAMAccountName=".$ldap->escape( $username );
      $aInfo = $ldap->search( "OU=".$ldap->escape( $role ), $search );
      if( !$aInfo || sizeof( $aInfo ) == 0 || $aInfo["count"] == 0 ) return false;
      $aInfo = $aInfo[0];
      $aInfo["sessidhash"] = hash( SITE_HASHALGO, session_id().SITE_SALT );
      return $this->buildUserLoginSessionData( $aInfo );
    }
  }
?>