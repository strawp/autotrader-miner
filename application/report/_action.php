<?php
  session_start();
  require_once( "../core/settings.php" );
  require_once( "core/functions.php" );
  require_once( "arc_arg.class.php" );
  
  $argv = array();

  if (!isset($_GET["btnReset"])){
    foreach( $_GET as $k=>$v ){
      if( is_array( $v ) ){
        $c = "";
        $a = $k."/";
        foreach( $v as $value ){
          $value = Field::Format(substr($k,0,3),$value,true);
          $a .= $c.$value;
          $c = ",";
        }
        $argv[] = $a;
      } else{ 
        //$v= Field::Format(substr($k,0,3),$v,true);
        $argv[] = "$k/$v";
      }
    }
  }

  $args = join( "/", $argv );
  $return_url = "Location: ".SITE_ROOT."report/timesheet/$args";
  header( $return_url );
?>
