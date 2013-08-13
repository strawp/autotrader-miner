<?php
  /**
  * Script to help tidy up items in any model that need to be deleted due to duplication
  * - Updates any items that refer to this model and update it with the replacement ID
  * - Deletes any rows of member tables referring to this item
  */
  require_once( "../core/settings.php" );  
  require_once( "core/db.class.php" );  
  require_once( "cli_compat.php" );
  require_once( "core/console.class.php" );
  
  echo "Model tidying tool\n";
  
  $mt = new ModelTidy();
  
  // Get the model
  $ok = false;
  if( isset( $argv[1] ) ) $ok = $mt->setModelName( $argv[1] );
  while( !$ok ){
    $ok = $mt->setModelName( prompt( "Model to use" ) );
  }
  
  // Guess which ids are the old and new, if there's a search
  $aIds = array();
  
  // Preliminary search if present
  if( isset( $argv[2] ) ){
    $m = new $mt->ModelName();
    if( isset( $m->aFields["name"] ) ){ 
      $_GET["name"] = urlencode( urlencode( $argv[2] ) );
      echo "Looking up \"".$argv[2]."\" in ".$m->displayname." table:\n";
      $dbr = $m->getBySearch();
      while( $row = $dbr->fetchRow() ){
        $aIds[] = $row["id"];
        echo " - ".$row["id"].": ".$row["name"]."\n";
      }
      rsort( $aIds );
    }
  }
  
  // The ID of the incorrect item
  $ok = false;
  while( !$ok ){
    if( sizeof( $aIds ) > 0 ) $guess = $aIds[0];
    else $guess = "";
    $id = prompt( "ID of ".$mt->ModelName." to remove [".$guess."]" );
    if( intval( $id ) == 0 && $guess != "" ) $id = $guess;
    $ok = $mt->setOldId( $id );
  }
  
  // Pluck the chosen ID out of possible IDs for next pick
  $idx = array_search( $id, $aIds );
  if( $idx !== false ){
    array_splice( $aIds, $idx, 1 );
  }
  
  // The ID of the correct item
  $ok = false;
  while( !$ok ){
    if( sizeof( $aIds ) > 0 ) $guess = $aIds[0];
    else $guess = "";
    $id = prompt( "ID of the ".$mt->ModelName." which will replace the incorrect one [".$guess."]" );
    if( intval( $id ) == 0 && $guess != "" ) $id = $guess;
    $ok = $mt->setNewId( $id );
  }
  
  echo "Please review the details of these users before continuing:\n";
  echo "Old ".$mt->ModelName.":\n".Console::boldFirstLine($mt->getOldModel()->toString())."\n\n";
  echo "New ".$mt->ModelName.":\n".Console::boldFirstLine($mt->getNewModel()->toString())."\n\n";
  
  $ok = false;
  while( $ok != "y" ){
    $ok = prompt( "\"y\" to continue, ^C to quit" );
  }
  
  echo "\nThe script will now replace associations of the ".$mt->ModelName." \"".$mt->getOldModel()->getName()."\" (".$mt->oldid.") "
    ."with \"".$mt->getNewModel()->getName()."\" (".$mt->newid.")\n";
  
  $mt->switchFkAssociations();
  
  $mt->deleteOld();
  
  echo "Finished.\n";
  
  class ModelTidy{
    function ModelTidy(){
      $this->oldid = 0;
      $this->newid = 0;
      $this->ModelName = "";
      $this->aModels = array();
      $this->newModel = false;
      $this->oldModel = false;
      $this->aRelated = false;
      $this->aMembers = false;
      $this->output = true;
      $this->db = new DB();
    }
    
    /**
    * Set the model to operate on
    * @return true if set OK, false if model doesn't exist
    */
    function setModelName( $name ){
      if( sizeof( $this->aModels ) == 0 ) $this->findModelNames();
      if( array_search( $name, $this->aModels ) !== false ){
        $this->ModelName = $name;
        return true;
      }else{
        return false;
      }
    }
    
    /**
    * Set the ID of the new item to switch to
    * @return true if set OK, false if the item doesn't exist in this model's table
    */
    function setNewId( $id ){
      $m = $this->getNewModel( intval( $id ) );
      if( $m->id > 0 ) return true;
      return false;
    }
    
    /**
    * Set the ID of the old item to remove
    * @return true if OK, false if model doesn't exist
    */
    function setOldId( $id ){
      $m = $this->getOldModel( intval( $id ) );
      if( $m->id > 0 ) return true;
      return false;
    }
    
    /**
    * Get an actual instance of the old model
    */
    function getOldModel( $id=0 ){
      if( $id > 0 ) $this->oldid = intval( $id );
      // if( $this->oldModel ) return $this->oldModel;
      if( $this->ModelName == "" ) return false;
      if( $this->oldid == 0 ) return false;
      $m = new $this->ModelName();
      $m->get( $this->oldid );
      $this->oldModel = $m;
      return $m;
    }
    
    /**
    * Get an instance of the new model
    */
    function getNewModel( $id=0 ){
      if( $id > 0 ) $this->newid = intval( $id );
      if( $this->newModel ) return $this->newModel;
      if( $this->ModelName == "" ) return false;
      if( $this->newid == 0 ) return false;
      $m = new $this->ModelName();
      $m->get( $this->newid );
      $this->newModel = $m;
      return $m;
    }
    
    /**
    * Get the names of all models with a database table
    */
    function findModelNames(){
      $dh = opendir( "../models" );
      $this->aModels = array();
      while( $file = readdir( $dh ) ){
        if( !preg_match( "/([^\.]+)\.model\.class\.php$/", $file, $m ) ) continue;
        $this->aModels[] = underscoreToCamel( $m[1] );
      }
    }
    
    /**
    * Gets the names of all models which have a foreign key with the currently selected one, missing out grd, mem and chk fields
    */
    function findRelatedModels(){
      if( $this->aRelated !== false ) return;
      if( sizeof( $this->aModels ) == 0 ) $this->findModelNames();
      $this->aRelated = array();
      $this->aMembers = array();
      foreach( $this->aModels as $model ){
        $m = new $model();
        $cols = array();
        if( !$m->hastable ) continue;
        if( !is_array( $m->aFields ) ){
          echo "no fields: $model: ".$m->displayname."\n";
        }
        foreach( $m->aFields as $field ){
          if( $field->belongsto == $this->ModelName ){
            $aInfo = array(
              "model" => $model,
              "table" => $m->tablename,
              "field" => $field->name,
              "column" => $field->columnname
            );
            if( $m->name == "MemberInterface" ){
              $this->aMembers[] = $aInfo;
            }else{
              $this->aRelated[] = $aInfo;
            }
          }
        }
      }    
    }
    
    /**
    * Switch associations from the old item to the new item
    */
    function switchFkAssociations(){
      $this->findRelatedModels();
      $madeupdates = false;
      foreach( $this->aRelated as $field ){
        $sql = "UPDATE ".$field["table"]." SET ".$field["column"]." = ".$this->newid." WHERE ".$field["column"]." = ".$this->oldid;
        $this->db->query( $sql );
        if( $this->db->affectedrows > 0 ){
          $madeupdates = true;
          if( $this->output ) echo " - ".$field["table"].".".$field["column"]." (".$this->db->affectedrows." affected)\n";
        }
      }
      if( !$madeupdates && $this->output ) echo "No updates were necessary to any related models\n";
    }
    
    /**
    * Remove the old item
    */
    function deleteOld(){
      if( !$this->oldModel ) $this->getOldModel();
      $this->removeMembers();
      if( $this->output ) echo "Deleting old model\n";
      $this->oldModel->delete();
    }
    
    /**
    * Delete potentially orphaned member table associations
    */
    function removeMembers(){
      if( $this->output && sizeof( $this->aMembers ) > 0 ){
        echo "Looking for any member associations in:\n";
      }
      foreach( $this->aMembers as $field ){
        if( $this->output ) echo " - ".$field["table"].".".$field["column"];
        
        // See if there are any associated items
        $sql = "SELECT * FROM ".$field["table"]." WHERE ".$field["column"]." = ".$this->oldid;
        $this->db->query( $sql );
        if( $this->db->numrows == 0 ){ 
          if( $this->output ) echo " - No associations\n";
          continue;
        }
        if( $this->output ) echo " - ".$this->db->numrows." associated member items";
        
        // See if this member row is needed
        $db2 = new DB();
        while( $row = $this->db->fetchRow() ){
          $sql = "SELECT * FROM ".$field["table"]." WHERE ";
          $where = "";
          foreach( $row as $k => $v ){
            if( !preg_match( "/_id$/", $k ) ) continue;
            if( $k == $field["column"] ) continue;
            $where .= $k." = ".$v." AND ";
          }
          $where .= $field["column"]." = ";
          $sql .= $where.$this->newid;
          $db2->query( $sql );
          echo $db2->error;
          if( $db2->numrows > 0 ){
          
            // Don't need this
            $sql = "DELETE FROM ".$field["table"]." WHERE id = ".$row["id"];
            $db2->query( $sql );
            if( $this->output ) echo " - ID ".$row["id"]." Not needed, deleting\n";
          }else{
          
            // Need it, transfer to new model 
            $sql = "UPDATE ".$field["table"]." SET ".$field["column"]." = ".$this->newid." WHERE id = ".$row["id"];
            $db2->query( $sql );
            if( $this->output ) echo " - ID ".$row["id"]." association needs to be kept, updating\n";
          }
          echo $db2->error;
        }
      }
    }
  }
  
?>
