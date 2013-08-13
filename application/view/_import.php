<?php
  session_start();
  require_once( "../core/settings.php" );
  require_once( "models/user.model.class.php" );
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  $model->aFields["name"]->set( $_GET["name"] );
  
  $user = new User();
  $user->getByName( $model->aFields["name"]->value );
  $ldap = $model->getBySearch();
  
  $return_url = "Location: ".SITE_ROOT.$model->returnpage."/name/".$_GET["name"];


  switch ($_GET["from"]){
  case "ldap":
    if( $ldap->numrows > 0 ){
      while( $row = $ldap->fetchRow() ){
        if( $row["name"] != $model->aFields["name"]->value ) continue;
        foreach( $row as $key => $value ){
          if( $key == "id" ) continue;
          $user->aFields[$key]->set( $value );
        }
        $user->save();
        $user->setupDefaultWidgets();
      }
    }
    break;
  } 
  
  header( $return_url );
?>
