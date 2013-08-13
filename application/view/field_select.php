<?php
  session_start();
  require_once( "core/functions.php" );
  $model = setupModel();
  if( !$model->hasinterface ){ 
    header( "Location: ".SITE_ROOT );
    exit;
  }
  $model->setAction( "field_select" );
  if( !$model->allowfieldselect ){
    header( "Location: ".SITE_ROOT.$model->tablename );
    exit;
  }
  require_once( "core/header.php" );
  $model->doInits();
  echo "    <h2>".$model->displayname." Fields</h2>\n";
  switch( $model->name ){
    default:
      $model->setupUserFields();
      echo $model->renderFieldSelectForm();
      break;
  }
  require( "core/footer.php" );
?>