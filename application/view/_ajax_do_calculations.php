<?php
  session_start();
  globalAuth();
  require_once( "../core/settings.php" );
  $model = setupModel();  
  if( $model === false ) die( "Model not initiated" );
  if( !$model->hasinterface ) exit;
  if( isset( $_POST["id"] ) ) $model->get( $_POST["id"] );
  $model->doInits();
  $model->getForm();
  $model->doCalculations();
  Flash::clear();
  
  // Set up which fields to return (only the calculated ones)
  $model->aResultsFields = array();
  foreach( $model->aFields as $field ){
    if( sizeof( $field->aUsesFields ) > 0) $model->aResultsFields[] = $field->columnname;
  }
  
  echo $model->toJson();
?>