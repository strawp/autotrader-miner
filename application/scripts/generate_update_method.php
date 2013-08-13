<?php
  
  /**
  * Given a model and a column name, generate some code which can be pasted into an afterAddColumn method
  */
  
  chdir( dirname( $_SERVER["PHP_SELF"] ) );
  require_once( "../core/settings.php" );
  $name = isset( $argv[1] ) ? $argv[1] : "";
  $column = isset( $argv[2] ) ? $argv[2] : "";
  
  if( $name == "" ) die( "Usage: php ".basename( $_SERVER["PHP_SELF"] )." Model column" );
  
  $model = Cache::getModel( $name );
  if( !$model ) die( "Couldn't create $name" );
  
  // if( !isset( $model->aFields[$column] ) ) die( "Didn't find $column in $name" );
  if( isset( $model->aFields[$column] ) ) $column = $model->aFields[$column]->columnname;
  
  if( !isset( $model->aFields["name"] ) ) die( "$name doesn't have a name field" );
  
  // Get the data that's in there currently
  $aData = array();
  if( $column ) $sql = "SELECT name, ".$column." FROM ".$model->tablename;
  else $sql = "SELECT * FROM ".$model->tablename;
  $db = new DB();
  $db->query( $sql );
  echo "\$aData = array(\n";
  while( $row = $db->fetchRow() ){
    if( $column ){
      if( $row[$column] != "" ) echo "  \"".$row["name"]."\" => \"".$row[$column]."\",\n";
    }
    else {
      echo "  array(\n";
      foreach( $row as $k => $v ){
        if( $k == "id" ) continue;
        echo "    \"$k\" => \"$v\",\n";
      }
      echo "  ),\n";
    }
  }
  echo ");\n";
  if( $column ){
  echo "
foreach( \$aData as \$k => \$v ){
  \$this->id = 0;
  \$this->retrieveByClause( \"WHERE name LIKE '\".\$db->escape( \$k ).\"'\" );
  \$this->aFields[\"$column\"]->set( \$v );
  \$this->save();
}
  ";
  }
 
  // Create rows
  else{
    echo "
foreach( \$aData as \$row ){
  \$this->id = 0;
  \$this->initFromRow( \$row );
  \$this->save();
}
    ";
  }
?>