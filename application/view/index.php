<?php
  session_start();
  require_once( "core/functions.php" );
  globalAuth();
  $model = setupModel();
  if( !$model->hasinterface ){ 
    header( "Location: ".SITE_ROOT );
    exit;
  }
  if( !$model->isAuth() ){  
    header( "Location: ".SITE_ROOT );
    exit;
  }
  require_once( "core/header.php" );
  addLogMessage( "Start", "index.php" );
  echo "    <h2>".plural( $model->displayname )." Search</h2>\n";
  echo "    <p>Search for and list ".plural( $model->displayname )."</p>\n";
  switch( $model->name ){
    default:
      addLogMessage( "Default index for ".$model->name, "index.php" );
      $text = "Add new";
      $action = "new";
      echo $model->renderList();
      break;
  }
  require( "core/footer.php" );
?>