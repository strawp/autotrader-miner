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
  if( !isset( $model->aFields[trim($_POST["column"])] ) ) die( "Field \"".htmlentities($_POST["column"])."\" doesn't exist" );
  $field = $model->aFields[trim($_POST["column"])];
  $field->parent_tablename = $model->tablename;
  echo $field->getAutoComplete($_POST["term"]);
?>