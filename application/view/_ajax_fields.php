<?php

  /* Return the fields in XHTML so they can be slotted right into where ever */

  require_once( "../core/settings.php" );
  @session_start();
  globalAuth();
  $model = setupModel();
  if( !$model->hasinterface ){
    // header( "Location: ".SITE_ROOT );
    exit;
  }
  $model->get( $_GET["id"] );
  $model->setupUserFields();
  echo $model->getResultsFieldsAsList();

?>