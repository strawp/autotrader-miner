<?php
  session_start();
  require_once( "../core/settings.php" );
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  foreach( $model->aFields as $key => $field ){
    $model->aFields[$key]->editable = true;
  }
  if( $model->allowfieldselect && !empty( $_GET["fields"] ) ){
    $model->aSearchFields = array();
    $model->aResultsFields = array();
    $a = preg_split( "/,/", $_GET["fields"] ); // OK
    foreach( $a as $f ){
      if( array_key_exists( $f, $model->aFields ) ){
        $model->aSearchFields[] = $f;
      }
    }
    $model->getForm( $model->aSearchFields );
  }else{
    $model->getForm();
    if( $model->allowfieldselect ) $model->setupUserFields();
  }
  $model->doInits();
  $args = "";
  $argv = array();
  
  if( array_key_exists( "active", $model->aFields ) && array_search( "active", $model->aSearchFields ) === false ) $model->aSearchFields[] = "active";
  if( sizeof( $model->aSearchFields  ) == 0 ) $model->aSearchFields = array_keys( $model->aFields );
  foreach( $model->aSearchFields as $fieldname ){
    $field = $model->aFields[$fieldname];
    $a = "";
    if( method_exists( $field, "getUrlArg" ) ) $a = $field->getUrlArg();    
    if( $a != "" ) $argv[] = $a;
  }
  $args = join( "/", $argv );
  $return_url = "Location: ".SITE_ROOT.$model->returnpage."/$args#results";
  header( $return_url );
?>
