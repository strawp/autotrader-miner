<?php
  /**
  * Update the manual custom index fields. 
  *
  * Pass table names as arguments for models to re-cache or leave empty for all models to be re-cached
  */
  
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  
  if( !isset( $argv ) || sizeof( $argv ) == 1 ){
    
    // Get the models using idx from the field and model tables
    $sql = "SELECT m.name FROM model m INNER JOIN field f ON f.model_id = m.id WHERE f.name = 'idx' GROUP BY m.id";
    $db = new DB();
    $db->query( $sql );
    $aModels = array();
    while( $row = $db->fetchRow() ){
      $aModels[] = $row["name"];
    }
  }else{
    array_shift( $argv );
    $aModels = $argv;
  }
  
  foreach( $aModels as $model ){
    if( !file_exists( "../models/".$model.".model.class.php" ) ) continue;
    require_once( "models/".$model.".model.class.php" );
    $model_name = underscoreToCamel( $model );
    $o = new $model_name;
    echo $o->name." ".date( "Y-m-d H:i:s" )."\n";
    $o->populateIdx();
    echo $o->name." finished ".date( "Y-m-d H:i:s" )."\n";
  }

?>