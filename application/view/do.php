<?php

  /*
    Prompt for confirmation to remove a user from a team
     - I prompt the user if they're sure they want to remove the user from the team
     - I submit to an action page which removes the user
  */
  
  $header = true;
  if( !empty( $_GET["options"] ) && $_GET["options"] == "_ajax" ) $header = false;
  
  require_once( "../core/settings.php" );
  @session_start();
  if( $header !== false ){ 
  }else{
    globalAuth();
    $aElementIds = array();
  }
  $model = setupModel();
  $model->setAction( $_GET["action"] );
  if( !$model->hasinterface ){
    header( "Location: ".SITE_ROOT );
    exit;
  }
  if( $header !== false ){ 
    require_once( "core/header.php" );
  }
  
  if( empty( $_GET["id"] ) ){
    echo "<p>Need a valid ID</p>\n";
  }
  
  else{
  
    $model->retrieve( $_GET["id"] );
    
    echo "      <div class=\"".$model->tablename." ".strip_tags( $model->action )."\">\n";
    
    switch( $_GET["model"] ){
      
      // Default
      default:
        switch( $model->action ){
          case "view":
            echo $model->render();
            echo "      <p class=\"return\"><a href=\"".SITE_ROOT.$model->returnpage."/\">&laquo; Return to ".$model->displayname." list</a></p>\n";
            break;
          
          case "delete":
            echo "        <p class=\"prompt\">Are you sure you want to delete ".trim(h($model->getName()))."?</p>\n";
            // unset( $model->aFields["verification"] );
            foreach( $model->aFields as $field ){
              $model->removeField( $field->name );
            }
            echo $model->renderForm( "_action", "post", "Delete" );
            echo "      <p class=\"return\"><a href=\"".SITE_ROOT.$model->returnpage."/\">&laquo; Return to ".$model->displayname." list</a></p>\n";
            break;
        }
        break;
      
    }
    echo "      </div>\n";
  }
  
  
  
  if( $header !== false ) require_once( "core/footer.php" );
  

?>