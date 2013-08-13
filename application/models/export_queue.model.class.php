<?php
  /*
    AUTO-GENERATED CLASS
    Generated 6 Nov 2012 13:43
  */
  class ExportQueue extends Model implements iFeature {
    
    function __construct(){
      $this->Model( get_class($this) );
      $this->addField( Field::create( "strName" ) ); 
      $this->addField( Field::create( "strUrl" ) );
      $this->addField( Field::create( "txtColumns" ) ); // Comma separated list of columns to be in export
      $this->addField( Field::create( "dtmCreatedAt" ) );
      $this->addField( Field::create( "lstUserId" ) );
      $this->addField( Field::create( "strToken" ) );
      $this->addAuth( "is_admin", "Yes" );
     }

      function getFeatureDescription(){
        return "Provides a queue of searches or reports that users wish to export, in order for exports to be offloaded onto another replicated server";
      }
    

    /**
    * Set the token to something unguessable
    */
    function export_queueFinally(){
      $this->Fields->Token->set( uniqid() );
      $this->Fields->UserId->set( SessionUser::getId() );
    }
    
    /**
    * Web service method to delete an item from the queue, requires "id" and "token" params
    */
    static function deleteWS($aOptions){
      $db = new DB();
      if( empty( $aOptions["id"] ) ) return false;
      if( empty( $aOptions["token"] ) ) return false;
      
      $sql = "
        DELETE FROM export_queue
        WHERE id = ".intval( $aOptions["id"] )."
          AND token = '".$db->escape( $aOptions["token"] )."'
      ";
      $db->query( $sql );
      if( $db->affectedrows > 0 ) return true;
      return false;
    }
    
    static function processQueue($olderthan=""){
      global $aSuccessfulDeletes, $aFailedDeletes;
      if( $olderthan != "" ) $olderthan = "WHERE created_at < ".strtotime( $olderthan );
    
      // Check contents of the queue
      $db = new DB();
      $db->query("
        SELECT *
        FROM export_queue 
        $olderthan
        ORDER BY created_at ASC
      ");
      while( $row = $db->fetchRow() ){
        if( in_array( $row["id"], $aSuccessfulDeletes ) ){
          echo "ERROR: Delete previously successful on master, found in slave DB (ID:".$row["id"]."). Replication probably failed\n";
          return false;
        }
        if( in_array( $row["id"], $aFailedDeletes ) ){
          echo "WARNING: ID ".$row["id"]." found in list of failed deletes from Live. Skipping...\n";
          continue;
        }
        
        $start = microtime(true);
        $ur = new UserReport();
        // $ur->debug = true;
        $ur->Fields->UserId->value = intval( $row["user_id"] );
        $ur->Fields->Url->value = $row["url"];
        $ur->Fields->Name->value = $row["name"];
        $ur->Fields->Columns->value = $row["columns"];
        $ur->runReport();
        $dur = microtime(true) - $start;
        echo "Took ".formatPeriod( $dur, true ).". ";
        
        // Delete the queue item
        if( SITE_SLAVE ){
          // If this site instance is a read-only slave of the master, use a web service to request delete on the master
          $delete = SITE_MASTERBASE."ws/export_queue_delete.php?id=".intval($row["id"])."&token=".preg_replace( "/[^0-9a-zA-Z]/", "", $row["token"] );
          echo "Requesting: $delete ,";
          $rtn = trim( file_get_contents( $delete ) );
        }else{
          $eq = new ExportQueue();
          $eq->get( $row["id"] );
          $rtn = $eq->delete() ? "OK" : "FAIL";
        }
        echo "Deleted: $rtn\n";
        if( $rtn == "OK" ){
          $aSuccessfulDeletes[] = $row["id"];
        }else{
          $aFailedDeletes[] = $row["id"];
        }
      }
    }
  }
?>
