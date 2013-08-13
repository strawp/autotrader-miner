<?php
  
  // I test the user's details against an LDAP server
  
  require_once( "../core/settings.php" );
  if( SITE_AUTH == "db" ) require_once( "core/db.login.class.php" );
  else require_once( "core/login.class.php" );
  require_once( "models/user.model.class.php" );
  require_once( "models/user_log.model.class.php" );
  
  if( !isset( $_POST["lstRole"] ) ) $_POST["lstRole"] = "Staff";
  
  // Remember firstname, lastname in cookies
  if( SITE_AUTH == "ldap" ){
    setcookie( 
      "login", 
      serialize( 
        array( 
          "first_name" => $_POST["strFirstName"],
          "last_name"  => $_POST["strLastName"]
        )
      )
    );
  }
  
  // session_start();
  
  if( trim( $_POST["strUsername"] ) != "" ){
    $user = new User();
    $user->getByName( $_POST["strUsername"] );
    if( $user->id != 0 ){
      $first_name = $user->aFields["first_name"]->toString();
      $last_name = $user->aFields["last_name"]->toString();
    }else{
      $first_name = $_POST["strFirstName"];
      $last_name = $_POST["strLastName"];
    }
  }else{
    $first_name = $_POST["strFirstName"];
    $last_name = $_POST["strLastName"];
  }
  
  $login = new Login();
  $details = $login->doLogin( stripslashes( $_POST["strUsername"] ), stripslashes( $_POST["pasPassword"] ), stripslashes( $_POST["lstRole"] ), $first_name, $last_name );
  
  // Success
  if( $details ){
    SessionUser::setDetails( $details );
    unset( $_SESSION["mailform"] );
    SessionUser::setAuthenticationScheme("web");
    
    // Add entry to successful log table
    $ul = new UserLog();
    $ul->aFields["user_id"]->set( intval($details["id"]) );
    $ul->aFields["user_agent"]->set( $_SERVER["HTTP_USER_AGENT"] );
    $ul->aFields["remote_ip"]->set( $_SERVER["REMOTE_ADDR"] );
    $ul->save();
    
    $message = "Login successful";
    if( $details["last_logged_in"] == "" ){
      $message .= ". If this is the first time you have used ".SITE_NAME.", it may be beneficial for you to arrange an orientation session. "
        ."Please <a href=\"mailto:".SITE_ADMINEMAIL."\">contact the ".SITE_NAME." team</a> to arrange training.";
    }
    
    Flash::clear();
    
    $aUa = parseUserAgent( $_SERVER["HTTP_USER_AGENT"] );
    if( $aUa["browser"] == "Internet Explorer" && $aUa["version"] <= 7 ){
      Flash::addWarning( 
        " <strong>Warning:</strong> You appear to be using an out of date version of Internet Explorer. Some features of this site may not work as intended. "
        ."Please upgrade to a newer browser if possible, or switch to a computer which has been kept up to date." 
      );
    }
    
    Flash::setHtmlAllowed(true);
    Flash::setNotice( $message );
  }
  
  // Fail
  else{
    SessionUser::logout();
    Flash::setHtmlAllowed(true);
    Flash::addError(
      "<p>Login failed.</p>"
    );
  }
  
  if( isset( $_SESSION["redirect"] ) ){
    $loc = $_SESSION["redirect"];
    unset( $_SESSION["redirect"] );
  }else{
    $loc = SITE_ROOT;
  }
  
  header( "Location: ".$loc );
