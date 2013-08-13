<?php
  /*
    Link to a specific model edit field using a field other than the ID
    field is $model->gotofield
  */
  session_start();
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  if( !isset( $_GET["value"] ) && isset( $_POST["value"] ) ) $_GET = $_POST;
  
  if( empty( $_GET["model"] ) ) exit;
  globalAuth();
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  if( empty( $_GET["value"] ) ){ 
    Flash::addWarning( "You used the quick search but didn't type anything in" );
    header( "Location: ".SITE_ROOT.$model->returnpage."/" );
    exit;
  }
  
  if( $model->gotofield == "" ){
    header( "Location: ".SITE_ROOT.$model->returnpage."/" );
    exit;
  }else{
    $db = new DB();
    $model->aFields[$model->gotofield]->editable = true;
    $model->aFields[$model->gotofield]->set( $_GET["value"] );
    $sql = "SELECT id FROM ".$model->tablename." WHERE ".$model->aFields[$model->gotofield]->getDBString(true,true,false);
    $db->query( $sql );
    $loc = SITE_ROOT.$model->returnpage."/";
    if( $db->numrows != 1 ){
      // Send to search
      $loc .= $model->aFields[$model->gotofield]->columnname."/".$_GET["value"]."#results";
    }else{
      $row = $db->fetchRow();
      $loc .= "edit/".$row["id"];
    }
    header( "Location: ".$loc );
    exit;
  }
?>