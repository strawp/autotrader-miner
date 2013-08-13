<?php
  require_once( "core/settings.php" );
  require_once( "session_db.class.php" );
  SessionDb::init();

  /*
    MySQLi data access class
  */
  
  class DB{
    function DB(){
      addLogMessage( "Initing new DB", "DB->DB()" );
      $this->connect();
      $this->rlt = false;
      $this->numrows = 0;    // Number of rows in a result
      $this->affectedrows = 0; // Number of rows affected in an update / insert / delete
      $this->matchedrows = 0; // Number of rows matched in an update 
      $this->error = "";
      $this->foundrows = false;
      $this->executiontime = null;
      $this->maxallowedexecload = null; // Maximum load CPU is under before query is allowed to exec
      $this->lastquery = "";
      $this->timecreated = time();            // Time this instance was created
      $this->debug = false;
      addLogMessage( "End", "DB->DB()" );
    }
    
    /**
    * Method to call when restoring from session
    */
    function restore(){
      $this->connect();
    }
    
    function connect(){
      // if( isset( $this->db ) && is_object( $this->db ) && get_class( $this->db ) == "mysqli" ) return;
      $port = defined( "DB_PORT" ) ? DB_PORT : ini_get("mysqli.default_port");
      if( defined( "DB_SOCK" ) ){
        $this->db = new mysqli( DB_HOST , DB_USER, DB_PASS, DB_NAME, $port, DB_SOCK );
      }else{
        $this->db = new mysqli( DB_HOST , DB_USER, DB_PASS, DB_NAME, $port );
      }
    }
    
    function close(){
      return @$this->db->close();
    }
    
    function getTables(){
      $this->query( "show tables" );
      $return = array();
      if( $this->numrows > 0 ){
        while( $row = $this->fetchRow() ){
          $return[] = array_shift( $row );
        }
      }
      return $return;
    }
    
    function getColumns( $table ){
      $this->query( "show columns from ".$this->escape( $table ) );
      $return = array();
      while( $row = $this->fetchRow() ){
        $return[] = $row;
      }
      return $return;
    }
    
    function createIndex( $table, $column, $indexname ){
      return $this->query( "CREATE INDEX ".$indexname." ON ".$table." (".$column.")" );
    }
    
    function addColumn( $table, $name, $options="" ){
      $sql = "ALTER TABLE ".$this->escape( $table )." ADD COLUMN ".$this->escape( $name )." ".$this->escape( $options );
      return $this->query( $sql );
    }
    
    function addForeignKey( $table, $column, $reftable, $refcolumn ){
      $fk = "FK_".$this->escape( $table )."_".$this->escape( $column );
      $sql = "ALTER TABLE ".$this->escape( $table )." ADD CONSTRAINT ".$fk." FOREIGN KEY ".$fk." (".$this->escape( $column ).")
          REFERENCES ".$this->escape( $reftable )." (".$this->escape($refcolumn).")
          ON DELETE SET NULL";
      $this->query( $sql );
    }
    
    function removeColumn( $table, $name ){
      
      // If this is a FK, attempt to remove it
      if( preg_match( "/_id$/", $name ) ){ 
        $sql = "ALTER TABLE ".$this->escape( $table )." DROP FOREIGN KEY FK_".$this->escape( $table )."_".$this->escape( $name );
        $this->query( $sql );
      }	
      return $this->query( "ALTER TABLE ".$this->escape( $table )." DROP COLUMN ".$this->escape( $name ) );
    }
    
    function modifyColumn( $table, $name, $type, $options ){
      return $this->query( "ALTER TABLE ".$this->escape( $table )." MODIFY COLUMN ".$this->escape( $name )." ".$this->escape( $type )." ".$this->escape( $options ) );
    }
    
    function createTable( $table, $aColumns, $aOptions ){
      $sql = "";
      $sql .= "CREATE TABLE ".$table." (\n".
        "  id int(11) NOT NULL auto_increment,\n";
      foreach( $aColumns as $column ){
        $sql .= "  ".$column["name"]." ".$column["datatype"]." ".$column["default"].",\n";
      }
      $sql .= "  PRIMARY KEY  (id)\n";
      $sql .= ") ENGINE=".DB_ENGINE." DEFAULT CHARSET=".DB_CHARSET;
      $this->query( $sql );
      if( $this->error != "" ) return $this->error;
      return true;
    }
    
    function alterTableOptions( $table, $aOptions ){
      if( sizeof( $aOptions ) == 0 ) return true;
      $sql = "ALTER TABLE ".$this->escape( $table )." ".join( ", ", $aOptions );
      $this->query( $sql );
      if( $this->error != "" ) return $this->error;
      return true;
    }
    
    function dropTable( $table ){
      return $this->query( "DROP TABLE IF EXISTS ".$this->escape( $table ) );
    }
    
    function insert( $table, $columns, $data ){
      $sql = "INSERT INTO ".$this->escape( $table )." ( ".$this->escape( $columns )." ) VALUES ( ";
      if( is_array( $data ) ) $aData = $data;
      else $aData = preg_split( "/,/", $data ); // OK
      for( $i=0; $i<sizeof($aData); $i++ ){
        $aData[$i] = trim( $aData[$i] );
        if( preg_match( "/'[^']+'/", $aData[$i] ) ){
          $aData[$i] = $aData[$i];  // NOTE: Escaping should already have happened, e.g. at getDBString() stage
        }
      }
      $data = implode( ", ", $aData );
      $sql .= $data." )";
      addLogMessage( $sql, "DB->insert()", "db_query" );
      $this->query( $sql );
      addLogMessage( "End", "DB->insert()", "db_error" );
      return $this->db->insert_id;
    }
    
    /**
    * Attempt to do an update, if no rows are updated, insert
    * @param string $table Table to operate on
    * @param array $aColumns Array of strings of column names
    * @param array $aData Array of strings of escaped data
    * @param string $where SQL where clause to update on
    * @return the ID of the affected row
    */
    function updateOrInsert( $table, $aColumns, $aData, $where ){
      $sql = "UPDATE ".$this->escape( $table )." SET "; 
      $aSets = array();
      for( $i=0; $i<sizeof( $aColumns ); $i++ ){
        $aSets[] = $this->escape( $aColumns[$i] )." = ".$aData[$i];
      }
      $sql .= join( ", ", $aSets );
      $sql .= " ".$where;
      $this->query( $sql );
      if( $this->matchedrows > 0 ){ 
        return true;
      }
      // die( "Inserting row" );
      return $this->insert( $table, join( ", ", $aColumns), join( ", ", $aData ) );
    }
    
    function updateOne( $table, $id, $values, $keyfield ){
      // values escaped one layer up already, which isn't particularly great. Passing as keyed array allows checks
      if( is_array( $values ) ){ 
        $aVals = array();
        foreach( $values as $col => $v ){
          $aVals[] = $col." = ".$v;
        }
        $values = join( ", ", $aVals );
      }
      $sql = "UPDATE ".$this->escape( $table )." SET ".$values." WHERE $keyfield = ".$this->escape( $id );
      addLogMessage( $sql, "DB->updateOne()", "db_query" );
      $this->query( $sql );
      $err = $this->error;
      if( $err != "" ){ 
        echo $err."\n\n$sql\n\n---\n\n";
        addLogMessage( "End", "DB->updateOne()", "db_query" );
        return false;
      }
      addLogMessage( "End", "DB->updateOne()", "db_query" );
      return true;
    }
    
    function delete( $table, $id ){
      $sql = "DELETE FROM $table WHERE ID = ".$this->escape( $id );
      $this->query( $sql );
      if( $this->error == "" ) return true;
      else return false;
    }
    
    function deleteByClause( $table, $clause ){
      $sql = "DELETE FROM $table $clause";
      $this->query( $sql );
      if( $this->error == "" ) return true;
      else return false;
    }
    
    /**
    * Execute a parameterised query. Params MUST be passed in the correct type
    * NOT YET FULLY WORKING
    */
    function parameterisedQuery( $sql, $aParams ){
      global $query_count;
      $this->error = "";
      $this->lastquery = $sql;
      $this->affectedrows = 0;
      $this->matchedrows = 0;
      
      $stmt = $this->db->prepare($sql);
      $types = "";
      foreach( $aParams as $p ){
        switch( gettype( $p ) ){
          case "integer":
            $types .= "i";
            break;
          case "double":
            $types .= "d";
            break;
          case "string":
            $types .= "s";
            break;
        }
      }
      
      // Bind params by reference voodoo
      $aBinds = array($types);
      for( $i=0; $i<sizeof( $aParams ); $i++ ){
        $bn = "bind".$i;
        $$bn = $aParams[$i];
        $aBinds[] = &$$bn;   // Wooaaah...
      }
      // The passed params are now in existence with variable names bind0, bind1, bind2 etc...
      // aBinds contains the types and address references to each of these in order to pass to mysqli->bind_param
      // Use call_user_func_array to turn the aBinds into params
      call_user_func_array(array($stmt,"bind_param"),$aBinds);
      $t = microtime(true);
      $stmt->execute();
      $this->executiontime = microtime(true)-$t;
      $this->rlt = $stmt->get_result();
      
    }
    
    function query( $sql ){
      global $query_count;
      $this->error = "";
      $this->lastquery = $sql;
      $this->affectedrows = 0;
      $this->matchedrows = 0;
      addLogMessage( $sql, "DB->query()", "db_query" );
      if( $this->maxallowedexecload !== null ) sleepUntilLoadIsBelow( $this->maxallowedexecload );
      $t = microtime(true);
      $this->rlt = $this->db->query( $sql );
      $this->executiontime = microtime(true)-$t;
      $info = $this->db->info;
      if( $info != "" ){
        if( preg_match( "/Rows matched: (\d+)/", $info, $m ) ){
          $this->matchedrows = intval( $m[1] );
        }
      }
      $query_count++;
      if( $this->db->error != "" ){ 
        $this->error = $this->db->error;
      }
      if( $this->rlt && $this->rlt !== true ) $this->numrows = $this->rlt->num_rows;
      // addLogMessage( "Returned ".$this->numrows." rows", "DB->query()" );
      
      if( preg_match( "/SELECT SQL_CALC_FOUND_ROWS/", $sql ) ){
        $this->setFoundRows();
      }
      
      // Update, note how many rows have been updated/inserted/deleted
      $this->affectedrows = $this->db->affected_rows;
      addLogMessage( "End", "DB->query()" );
      return $this;
    }
    
    /**
    * Set the $foundrows property
    *
    * Assumes that the previous query used SQL_CALC_FOUND_ROWS. Has to create a new version of itself in order to not overwrite the 
    * current DB connection object
    */
    function setFoundRows(){
      $rlt = $this->db->query("SELECT FOUND_ROWS() as total");
      if( $rlt->num_rows == 0 ){ 
        $this->foundrows = false;
        return;
      }
      $row = $rlt->fetch_assoc();
      $this->foundrows = intval( $row["total"] );
    }
    
    public static function fetchOne($sql){
      $db = Cache::getModel("DB");
      $db->query($sql);
      if( $db->rlt ){ 
        $a = $db->rlt->fetch_array();
        if( isset( $a[0] ) ) return $a[0];
      }
      return false;
    }
    
    function fetchRow(){
      if( $this->rlt && $this->rlt !== true ){ // true == successful query e.g. insert
        if( $this->error != "" ){
          echo $this->getSummary();
          return false;
        }
        return $this->rlt->fetch_assoc();
      }
    }
    
    function dataSeek( $rownum ){
      $this->rlt->data_seek( $rownum );
    }
    
    function getByDate( $table, $field, $date, $format, $aColumns=array(), $aJoin=array(), $user=0, $before=0, $after=0 ){
    
      $join = "";
      foreach( $aJoin as $j ){
        $aColumns[] = $table."_".$j["table"].".name as {$j["table"]}_name";
        $join .= " INNER JOIN {$j["table"]} ".$table."_".$j["table"]." on ".$table."_".$j["table"].".id = {$j["column"]} ";
      }
    
      $columns = sizeof( $aColumns ) == 0 ? "*" : implode( ", ", $aColumns );
    
      // JOIN with other tables?
      $sql = "SELECT $columns FROM $table $join";
      
      $sql .= " WHERE DATE_FORMAT( FROM_UNIXTIME( ".$this->escape( $field )." ), '".$this->escape( $format )."' ) = '".$this->escape( $date )."'";
      if( $user != 0 ) $sql .= " AND user_id = ".$this->escape( $user );
      $sql .= " ORDER BY ".$this->escape( $field )." ASC";
      return $this->query( $sql );
    
    }
    
    function getByClause( $table, $clause ){
      $sql = "SELECT * FROM ".$this->escape( $table )." $clause";
      return $this->query( $sql );
    }
    
    function retrieveWhereEqualTo ( $table, $column, $value ){
      $column = preg_replace( "/[^A-Za-z0-9._]/", "", $column );
      $clause = "WHERE $column = ";
      switch( gettype( $value ) ){
        case "integer":
          $clause .= intval( $value );
          break;
          
        case "string":
          $clause .= "'".$this->escape( $value )."'";
          break;
      }
      return $this->getByClause( $table, $clause );
    }
    
    function getIdByField( $table, $field, $name ){
      $sql = "SELECT id FROM ".$this->escape( $table )." WHERE ".$this->escape( $field )." = '".$this->escape( $name )."'";
      $this->query( $sql );
      if( !$this->rlt ) return false;
      $return = array();
      if( $this->numrows > 0 ){
        $row = $this->fetchRow();
        return $row["id"];
      }
      return false;
    }
    
    function escape( $str ){
      return $this->db->real_escape_string( $str );
    }
    
    function getIdByWhere(  $table, $where ){
      $sql = "SELECT id FROM ".$this->escape( $table )." WHERE $where";
      $this->query( $sql );
      $return = array();
      if( $this->numrows == 1 ){
        $row = $this->fetchRow();
        return $row["id"];
      }
      return false;
    }
    
    /**
    * Create database dump file of all tables or just the ones passed 
    */
    function dumpTablesToGzip( $aTables = array(), $file="" ){
      if( $file == "" ){
        if( sizeof( $aTables ) > 0 ) $tables = "_".join( "_", $aTables );
        $file = SITE_BACKUPDIR.DB_NAME.$tables."_".date( "Ymd-His" ).".sql.gz";
      }
      $tables = "";
      if( sizeof( $aTables ) == 0 ){
        $aTables = $this->getTables();
      }
      $tables = join( " ", $aTables );

      // Dump all of database to file
      if( $this->debug ) echo "Dumping to $file\n";
      $args = " -u ".escapeshellarg( DB_USER )." -p".DB_PASS;
      if( defined( "DB_SOCK" ) ) $args .= " -S ".escapeshellarg( DB_SOCK );
      else $args .= " -h ".DB_HOST;
      if( defined( "DB_PORT" ) ) $args .= " -P ".intval( DB_PORT );
      $args .= " ".DB_NAME;
      
      $dump = MYSQLDUMP_PATH.$args." --tables $tables | ".GZIP_PATH." -c > ".escapeshellarg( $file );
      // echo $dump."\n";
      system( $dump );
      $rtn = file_exists( $file );
      $this->dumpfile = $rtn ? $file : "";
      return $rtn;
    }
    
    /**
    * Dump all database tables to gzip file except for the ones mentioned
    */
    function dumpTablesToGzipExcluding( $aExcludeTables = array(), $file="" ){
      if( $file == '' ){
        $file = SITE_BACKUPDIR.DB_NAME."_excluding_".join( "_", $aExcludeTables )."_".date( "Ymd-His" ).".sql.gz";
      }
      $aTables = $this->getTables();
      $aTables = array_diff( $aTables, $aExcludeTables );
      return $this->dumpTablesToGzip( $aTables, $file );
    }
    
    // Returns a string on how the native database can format a unix timestamp
    function dateFormat( $column, $format ){
      return "DATE_FORMAT( FROM_UNIXTIME( ".$this->escape( $column )." ), '".$this->escape( $format )."' ) ";
    }
    
    /**
    * Get a summary of the last query for debug purposes
    */
    function getSummary( $connectioninfo=false ){
      $a = array(
        "lastquery",
        "error",
        "numrows",
        "affectedrows",
        "foundrows",
        "executiontime"
      );
      $str = "\n=== Query Summary ===\n";
      if( $connectioninfo ){
        foreach( get_defined_constants() as $k => $v ){
          if( !preg_match( "/^DB_/", $k ) ) continue;
          $str .= $k.": ".$v."\n";
        }
      }
      foreach( $a as $p ){
        $str .= $p.": ".$this->$p."\n";
      }

      return $str;
    }

  /*
   * Fetches an array of results (an array of rows) for a given SQL query with a static invocation.
   *
   * Example: 
   *    DB::fetchArrayBySQL("...");
   *
   * @param $sql - SQL query
   * 
   */
   public static function fetchArrayBySQL($sql){
      $db = Cache::getModel("DB");
      $db->query($sql);
      $arr = array();
      if( $db->numrows > 0 ){
        while( $row = $this->fetchRow() ){
          $arr[]=$row;
        }
      }
      return $arr;
    }

   
  /*
   * Fetches an array of pairs ID=>Name for a given SQL query with a static invocation.
   *
   * Example: 
   *    DB::fetchPairsBySQL("SELECT id,name FROM projects");
   *
   * @param $sql - SQL query
   * 
   */
    public static function fetchPairsBySql($sql){
      $db = Cache::getModel("DB");
      $db->query($sql);
      $arr = array();
      if( $db->numrows > 0 ){
        while( list($k,$v) = $this->fetchRow() ){
          $arr[$k]=$v;
        }
      }
      return $arr;
    }
    
    function fetchArray(){
      $arr = array();
      while( $row = $this->fetchRow() ){
        $arr[] = $row;
      }
      return $arr;
    }
    
    /*
     * Static function to execute non-select queries (only for UPDATE,DELETE)
     * @return int Number of rows afffected
     * @example $affected = DB::execute("UPDATE table SET a=1");
     *
     */
    public static function execute($query){
      $db = Cache::getModel("DB");
      $db->query($query);
      return $db->affectedrows;
    }
  }
?>
