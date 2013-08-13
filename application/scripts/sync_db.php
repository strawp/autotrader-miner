<?php
  
  // Compares what the DB currently looks like with the model classes, makes any changes on the DB
  
  require_once( "../core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/model.class.php" );

  
  error_reporting( E_ALL & ~E_DEPRECATED );
  $starttime = microtime(true);
  
  $aLog = array();
  if( isset( $argv ) && array_search( "-v", $argv ) !== false ) $verbose = true;
  else $verbose = false;
  
  if( isset( $argv ) && array_search( "-i", $argv ) !== false ) $run_indexes = true;
  else $run_indexes = false;
  
  echo "Syncing database with framework models...\n";
  
  // Get list of models
  $dir = opendir( "../models" );
  $aModels = array();
  while( ($f = readdir( $dir )) !== false ){
    $f = trim( $f );
    if( $verbose ) echo $f."\n";
    if( preg_match( "/^([^\.]+)\.model.class\.php/", $f, $aMatch ) ){
      $name = underscoreToCamel( $aMatch[1] );
      if( $verbose ) echo $name."\n";
      require_once( "../models/".$f );
      if( $verbose ) echo "../models/".$f." included\n";
      $o = @new $name();
      if( $verbose ) echo "New ".$o->name." created\n";
      if( $o && @$o->hastable && $o->autosync ){
        $aModels[] = $name;
      }
      if( $verbose ) echo "Done with ".$o->name."\n";
    }
  }
  closedir( $dir );
  if( $verbose ) echo "Done reading class list\n";
  
  // Get list of tables
  $db = new DB();
  $aTables = $db->getTables();
  
  // Add in model list in case it got out of sync
  $aMetaList = array();
  $sql = "SELECT name FROM model";
  $db->query( $sql );
  while( $row = $db->fetchRow() ){
    $aMetaList[] = $row["name"];
  }
  // $aTables = array_merge( $aTables, $aMetaList );
  $aTables = array_unique( $aTables );
  
  
  // Do we have the model and field tables?
  $aCore = array();
  $model = new Model( "Model" );
  $model->addField( Field::create( "strName" ) );
  $aCore[] = $model;
  
  $model = new Model( "FieldModel" );
  $model->tablename = "field";
  $model->addField( Field::create( "strName" ) );
  $model->addField( Field::create( "intModelId" ) );
  $aCore[] = $model;
  
  $model = new Model( "FieldUser" );
  $model->addField( Field::create( "intFieldId" ) );
  $model->addField( Field::create( "intUserId" ) );
  $aCore[] = $model;
  
  $aToAlter = array();

  $aCreate = array();
  foreach( $aCore as $model ){
    if( array_search( $model->tablename, $aTables ) === false ){
      $rtn = $model->createTable();
      if( $rtn !== true ){
        $aCreate[$model->tablename] = $model;
      }else{
        $aLog[] = "Created table ".$model->tablename;
        $aToAlter[] = $model;
      }
    }
  }
  if( $verbose ) print_r( $aTables );
  
  // Do any tables need creating?
  if( $verbose ) echo "\nChecking if any tables need creating...\n";
  foreach( $aModels as $model ){
    require_once( "models/".camelToUnderscore( $model ).".model.class.php" );

    $o = new $model();
    if( $o->dbclass != "DB" ){
      continue;
    }
    if( $verbose ) echo $model.": (".$o->tablename.")";
    $search = array_search( $o->tablename, $aTables );
    if( $search === false ){
      if( $verbose ) echo "creating...";
      $return = $o->createTable();
      if( $return !== true ){ 
        $aCreate[$o->tablename] = $o;
        // exit;
      }else{
        $aLog[] = "Created table ".$o->tablename;
        $aToAlter[] = $o;
      }
    }else{
      if( $verbose ) echo "ok, ".$o->tablename." exists, found at location $search";
    }
    if( $verbose ) echo "\n";
    if( $search || !isset( $return ) || $return === true ){
      $o->getMetaId();
      if( $o->metaid == 0 ){
        $o->addToMetaTable();
        $aLog[] = "Added ".$o->tablename." to meta table";
      }
    }
  }
  
  // print_r( $aCreate );
  
  // Go through the models which might have failed on create
  while( sizeof( $aCreate ) > 0 ){
    echo join( ", ", array_keys( $aCreate ) )."\n";
    foreach( $aCreate as $k => $o ){
      if( $verbose ) echo $o->name."\n";
      echo ".";
      if( $verbose ) echo "Retrying create of ".$o->tablename."... ";
      $return = $o->createTable();
      if( $return !== true ){ 
        // $aCreate[] = $o;
        // echo $o->tablename." create failed\n";
        // echo "$return\n";
      }else{
        unset( $aCreate[$k] );
        echo $o->tablename." SUCCESS\n";
        $aLog[] = "Created table ".$o->tablename;
        $aToAlter[] = $o;
        $o->getMetaId();
        if( $o->metaid == 0 ){
          $o->addToMetaTable();
          $aLog[] = "Added ".$o->tablename." to meta table";
        }
      }
    }
  }
  
  $db = new DB();
  
  // Do any tables need removing?
  if( $verbose ) echo "\nChecking if any tables need removing...\n";
  foreach( $aTables as $table ){
    
    // Skip over views
    if( preg_match( "/^vw_/", $table ) ) continue;
  
    $modelname = underscoreToCamel( $table );
    if( $verbose ) echo $table.": ";
    if( array_search( $modelname, $aModels ) === false ){
      if( $table == "model" || $table == "field" || $table == "field_user" ) continue;
      if( $verbose ) echo "removing...";
      $db->dropTable( $table );
      $aLog[] = "Dropped table ".$table;
      
      $sql = "DELETE FROM model WHERE name = '".$db->escape( $table )."'";
      $db->query( $sql );
      $aLog[] = "Removed ".$table." from model table";
    }else{
      if( $verbose ) echo "ok";
    }
    if( $verbose ) echo "\n";
  }
  
  // For each model
  if( $verbose ) echo "\nChecking individual columns...\n";
  foreach( $aModels as $model ){
    require_once( "models/".camelToUnderscore( $model ).".model.class.php" );

    $o = new $model();
    
    // Non-native DB class, skip
    if( $o->dbclass != "DB" ){
      continue;
    }
    $o->metaid = $o->getMetaId();
    if( $verbose ) echo "\n".$model."\n";
    
    // Do any columns need adding?
    if( $verbose ) echo "  Checking if any columns need adding...\n";
    $db = new DB();
    $aColumns = $db->getColumns( $o->tablename );
    if( !isset( $aColumns[0]["Field"] ) ){
      echo $db->lastquery."\n\n";
      echo $db->error."\n";
      print_r( $aColumns );
      die("Query returned bad results. Lock on Mysql tmpdir?\n");
    }
    foreach( $o->aFields as $field ){
      if( $verbose ) echo "    ".$field->columnname.": ";
      
      // Is this field in the meta-table?
      $sql = "SELECT * FROM field WHERE model_id = ".$o->metaid." AND name = '".$field->columnname."'";
      $db->query( $sql );
      if( $db->numrows == 0 ){
        $sql = "INSERT INTO field ( model_id, name ) VALUES ( ".$o->metaid.", '".$field->columnname."' )";
        $db->query( $sql );
      }
      
      if( !$field->hascolumn ) continue;
      
      $ok = false;
      foreach( $aColumns as $column ){
        if( $column["Field"] == $field->columnname ){
          $ok = true;
          break;
        }
      }
      if( $ok ){ 
        if( $verbose ) echo "ok\n";
        
        // Alter options?
        if( $verbose ) echo "      Right datatype? ";
        $modify = false;
        if( $field->getDataType() != $column["Type"] ) $modify = true;
        if( $column["Null"] != $field->getNull() ) $modify = true;
        if( $column["Default"] == "NULL" && $field->default != "" ) $modify = true;
        if( $modify ){
          if( $verbose ) echo "No, altering...";
          $o->modifyColumn( $field->columnname, $column["Type"] );
          $aLog[] = "Modified ".$field->columnname." (".$field->getDataType()." ".$field->getColumnOptions().") in ".$o->tablename;
        }else{
          if( $verbose ) echo "yes";
        }
      }else {
        if( $verbose ) echo "need to add ".$field->columnname;
        $o->addColumn( $field->columnname );
        $aLog[] = "Added column ".$field->columnname." (".$field->getDataType()." ".$field->getDefault().") to ".$o->tablename;
        
        // Populate index for this table
        if( $field->columnname == "idx" ){
          echo "Populating idx for ".$o->name."\n";
          $o->populateIdx();
        }
      }
      
      if( $field->index && $run_indexes ){
        // Check index exists for this field - requires select privileges on information_schema
        // If the privilege doesn't exist then index will attempt to be created
        $sql = "SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '".DB_NAME."' AND TABLE_NAME = '".$o->tablename."' AND COLUMN_NAME = '".$field->columnname."'";
        $db->query( $sql );
        if( $db->numrows == 0 ){
          echo "Attempting to create index on ".$o->tablename.".".$field->columnname."...\n";
          $dbr = $o->createIndex( $field->columnname );
          if( $dbr ) $aLog[] = "Created index on ".$o->tablename.".".$field->columnname;
        }
      }      
      if( $verbose ) echo "\n";
    }
    
    // Do any columns need removing?
    if( $verbose ) echo "  Checking if any columns need removing...\n";
    foreach( $aColumns as $column ){
      if( $column["Field"] == "id" ) continue;
      if( trim( $column["Field"] ) == "" ) continue;
      if( $verbose ) echo "    ".$column["Field"].": ";
      if( array_key_exists( $column["Field"], $o->aFields ) === false ){
        
        // Delete from field table
        $sql = "DELETE FROM field WHERE name = '".$column["Field"]."' AND model_id = ".$o->metaid;
        $db->query( $sql );
        
        if( $verbose ) echo "removing";
        $o->removeColumn( $column["Field"] );
        $aLog[] = "Removed column ".$column["Field"]." from ".$o->tablename;
      }else{
        if( $verbose ) echo "keep";
      }
      if( $verbose ) echo "\n";
    }
  }
  
  // Tidy up field-user table
  $sql = "DELETE FROM field_user WHERE field_id NOT IN (SELECT id FROM field)";
  $db->query( $sql );
  
  // Add options to each table
  echo "Checking table options...\n";
  foreach( $aToAlter as $o ){
    $rtn = $o->alterTableOptions();
  }
 
  // if( $run_indexes ) include( "ensure_fks.php" );
  
  echo "Database sync'd. ";
  if( sizeof( $aLog ) == 0 ){
    echo "No changes made to DB\n";
  }else{
    echo "Did the following things:\n";
    echo " - ".join( "\n - ", $aLog )."\n";
  }
  echo "Took ".formatPeriod( microtime(true)-$starttime, true )."\n";
?>
