<?php
  
  // Checks that all non-valid FKs are null and not zero
  
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  
  echo "Ensuring foreign keys are set up...\n";
  
  // Get list of all foreign keys
  $sql = "SELECT f.name as field, m.name as model
    FROM field f
    inner join model m on m.id = f.model_id
    where f.name like '%_id'
    order by model
    ";
    
  $db = new DB();
  $db->query( $sql );
  if( $db->numrows == 0 ){
    echo "No foreign keys found\n";
  }else{
  
    // Make sure all loose IDs are set to NULL
    $db2 = new DB();
    while( $row = $db->fetchRow() ){
      $modelname = underscoreToCamel( $row["model"] );
      if( !file_exists( "../models/".$row["model"].".model.class.php" ) ) continue;
      require_once( "models/".$row["model"].".model.class.php" );
      $m = new $modelname();
      $f = $m->aFields[$row["field"]];
      if( !$f->hascolumn ) continue;
      echo $m->name."::".$f->name."\n";
      
      $parent = camelToUnderscore( $f->belongsto );
      $sql = "UPDATE ".$row["model"]." SET ".$row["field"]." = NULL WHERE ".$row["field"]." IS NOT NULL AND ".$row["field"]." NOT IN ( SELECT id FROM ".$parent." )";
      $db2->query( $sql );
      
      $fk = "FK_".$row["model"]."_".$row["field"];
      
      $sql = "SELECT * FROM information_schema.statistics WHERE INDEX_NAME = '".$fk."' AND TABLE_SCHEMA = '".DB_NAME."'";
      $db2->query( $sql );
      
      if( $db2->numrows == 0 ){
        // echo "Attempting to create index on ".$row["model"].".".$row["field"]."...\n";
        $sql = "ALTER TABLE ".$row["model"]." ADD CONSTRAINT ".$fk." FOREIGN KEY ".$fk." (".$row["field"].")
          REFERENCES ".$parent." (id)
          ON DELETE SET NULL";
        $db2->query( $sql );
        if( $db2->error == "" ) $aLog[] = "Created index on ".$row["model"].".".$row["field"];
      }
    }
  }

?>