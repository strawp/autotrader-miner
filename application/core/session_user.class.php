<?php

class SessionUser{

    /**
    * Get the ID of the currently logged in user
    */
    public static function getId(){
      return intval( self::getProperty("id") );
    }
    /**
    * Set the user's ID
    * @arg int $id
    */
    public static function setId( $id ){
      self::setProperty( "id", intval($id) );
    }
    
    /**
    * Check if the logged in user has the given user ID
    * @arg int $userId
    * @return bool
    */
    public static function userMatch($userId=null){
      if (self::getId() == $userId) return true; else return false;
    }
    
    /**
    * Check if the current user is logged in
    */
    public static function isLoggedIn(){
      if(!empty( $_SESSION["login"] )) return true;
      else return false;
    }
    
    /**
    * Log the current user out
    */
    public static function logout(){
      @session_start();
      unset($_SESSION["login"]);
      session_destroy();
      @session_start();
      setcookie( session_name(), "", time() - 3600 );
    }
    
    /**
    * Take the current user's ID and add any groups they are a member of to their session
    */
    public static function setupGroups(){
      if( !self::isLoggedIn() ) return false;
      $_SESSION["login"]["groups"] = array();
      $uug = new UserUserGroup();
      $uug->context = "user";
      $uug->id = self::getId();
      
      $dbr = $uug->getAssociated();
      while( $group = $dbr->fetchRow() ){
        $_SESSION["login"]["groups"][$group["code"]] = $group["name"];
      }
    }
    
    /**
    * Check if current user is an admin - based on session
    * @return bool true if the user is admin
    */
    public static function isAdmin(){
      if( isset( $_SESSION ) 
        && array_key_exists( "login", $_SESSION ) 
        && array_key_exists( "is_admin", $_SESSION["login"] ) 
        && $_SESSION["login"]["is_admin"] == "Yes" 
      ){ 
        return true;
      }
      return false;
    }
    
    /**
    * Check if current user is in a certain group
    *
    * @param string $code four-letter code of the user group to test
    * @return bool true if the logged-in user is in the queried group
    */
    public static function isInGroup( $code ){
      if( !isset( $_SESSION["login"] ) ) return false;
      if( !isset( $_SESSION["login"]["groups"] ) ) return false;
      if( isset( $_SESSION["login"]["groups"][$code] ) ) return true;
      return false;
    }
  
    /**
    * Check if a user has a property set
    * @arg string $name property name
    * @return bool
    */
    public static function hasProperty($name){
      return array_key_exists( $name, $_SESSION["login"] );
    }
    
    /**
    * Get the value of a property in the user's session
    * @arg string $name
    * @return mixed
    */
    public static function getProperty($name){
      if (isset($_SESSION["login"][$name])) return $_SESSION["login"][$name];  
      else return false;
    }
    
    /**
    * Set a property to a value
    * @arg string $name name of the property
    * @arg mixed $value value to set the property to
    * @return bool success status
    */
    public static function setProperty($name,$value){
      if (!isset($_SESSION["login"])) $_SESSION["login"] = array();
      if( !is_array( $_SESSION["login"] ) ) return false;
      $_SESSION["login"][$name] = $value;
      return true;
    }
    
    /**
    * Get the full name (first name, last name) of the user
    * @return string
    */
    public static function getFullName(){
      return self::getProperty("firstname")." ".self::getProperty("lastname");
    }
    
    /**
    * Set the currently logged in user as the one represented by a User model
    * $arg object $user User object
    */
    public static function setByUser( $user ){
      if( !isset( $_SESSION["login"] ) ) $_SESSION["login"] = array();
      self::setId( $user->id );
      self::setProperty( "username", $user->aFields["name"]->toString() );
      self::setProperty( "firstname", $user->aFields["first_name"]->toString() );
      self::setProperty( "lastname", $user->aFields["last_name"]->toString() );
      self::setProperty( "title", $user->aFields["title"]->toString() );
      self::setProperty( "is_admin", $user->aFields["is_admin"]->value ? "Yes" : "No" );
      self::setProperty( "role", "Staff" );
      self::setupGroups();
    }
    
    /**
    * Get the user object of the currently logged in user
    */
    public static function getUser(){
      $user = Cache::getModel("User");
      $user->get( self::getId() );
      return $user;
    }
    
    /**
    * Get the whole login data structure
    */
    public static function getLoginData(){
      if( isset( $_SESSION["login"] ) ) return $_SESSION["login"];
      else return array();
    }
    
    /**
    * Set the whole login structure
    */
    public static function setLoginData( $data ){
      $_SESSION["login"] = $data;
    }
    
    /**
    * If full details exist in a keyed array, set them
    */
    public static function setDetails( $aDetails ){
      foreach( $aDetails as $k => $v ){
        self::setProperty( $k, $v );
      }
      self::setupGroups();
    }
    /**
    * Set how the user in session was authenticated
    */
    public static function setAuthenticationScheme( $scheme ){
      if( !isset( $_SESSION["login"] ) ) $_SESSION["login"] = array();
      $_SESSION["login"]["authentication"] = $scheme;
    }
    
    /**
    * Get how the user in session was authenticated
    */
    public static function getAuthenticationScheme(){
      if( !self::isLoggedIn() ) return false;
      if( !isset( $_SESSION["login"]["authentication"] ) ) return false;
      return $_SESSION["login"]["authentication"];
    }
}
