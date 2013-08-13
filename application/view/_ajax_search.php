<?php
  /**
  * Currently a very user-specific way of searching with autocomplete and returning email addresses. Make more general, be more useful
  */
  session_start();
  globalAuth();
  require_once( "../core/settings.php" );
  $model = setupModel();
  if( !$model->hasinterface ) exit;
  foreach( $model->aFields as $key => $field ){
    $model->aFields[$key]->editable = true;
  }
  $q = !empty( $_POST["term"] ) ? urldecode( $_POST["term"] ) : "";
  $limit = isset( $_POST["limit"] ) ? intval( $_POST["limit"] ) : SITE_PAGING;
  
  // Split on ",;", take the last bit only
  $a = preg_split( "/[;,]/", $q );
  
  $_GET["name"] = array_pop( $a );
  
  foreach( $_POST as $k => $v ){
    switch( $k ){
      case "term":
      case "limit":
        continue;
        break;
    }
    $_GET[$k] = $v;
  }
  
  $model->doInits();
  
  $model->getForm();
  $args = "";
  $argv = array();
  
  if( array_key_exists( "active", $model->aFields ) && array_search( "active", $model->aSearchFields ) === false ) $model->aSearchFields[] = "active";
  if( sizeof( $model->aSearchFields  ) == 0 ) $model->aSearchFields = array_keys( $model->aFields );
  // if( $model->allowfieldselect ) $model->setupUserFields();
  $rlt = $model->getBySearch();
  $aResults = array();
  while( $row = $rlt->fetchRow() ){
    $model = Cache::getModel( get_class( $model ) ); // Create new row object to avoid inadvertently caching values
    $model->initFromRow( $row );
    $item = $model->getAjaxResultRow();
    /*
    $item = array( 
      "id" => $row["id"],
      "label" => $row["first_name"]." ".$row["last_name"],
      "value" => $row["first_name"]." ".$row["last_name"]." <".$row["name"]."@".SITE_EMAILDOMAIN.">"
    );
    */
    $aResults[] = $item;
    if( $limit > 0 && sizeof( $aResults ) > $limit ) break;
  }
  
  echo json_encode( $aResults );
?>