<?php
  /**
  * Get a statistical summary of the search that a user is looking at
  */
  session_start();
  globalAuth();
  require_once( "../core/settings.php" );
  $model = setupModel();
  if( $model === false ) die( "Model not initiated" );
  if( !$model->hasinterface ) exit;
  if( !$model->allowsearchsummary ) die( "Search summary not enabled for this model" );
  $model->getForm();
  $model->doInits();
  $model->setupUserFields();
  $model->setFieldsFromSearchArgs();
  $aReturn = $model->getSearchSummaryStats();
  echo json_encode( $aReturn );
?>