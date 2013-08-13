<?php
  session_start();
  require_once( "../core/settings.php" );

  require_once( "core/functions.php" );
  require_once( "core/field.class.php" );
  
  if( sizeof( $_POST ) == 0 ) $_POST = $_GET;
  switch( $_GET["report"] ){
 
    case "dashboard":
      $aFields = array( 
        "type" => Field::create( "strType" ),
        "show_tasks" => Field::create( "cnfShowTasks" ),
        "user_widget" => Field::create( "lstUserWidget" ),
        "limit" => Field::create( "lstLimit" ),
        "model" => Field::create( "strModel" ),
      );      
      break;

  }
  
  $argv = array();
  foreach( $aFields as $key=>$f ){
    // if( isset( $_GET[$f->name] ) ) $argv[] = $key."/".$_GET[$f->name];
    $f->getSubmittedValue();
    $s = $f->getUrlArg();
    if( $s != "" ) $argv[] = $s;
  }
  
  $args = join( "/", $argv );
  $return_url = "Location: ".SITE_ROOT."report/".$_GET["report"];
  if( isset( $_GET["screen"] ) ) $return_url .= "/screen/".$_GET["screen"];
  $return_url .= "/$args";
  header( $return_url );
?>
