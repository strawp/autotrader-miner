<?php
  require_once( "core/settings.php" );
  require_once( "db.class.php" );

  /*
    Oracle data access class
  */
  
  class OracleDB extends DB{
    function OracleDB(){
      addLogMessage( "Initing new DB", "DB->DB()" );
      $this->rlt = false;
      $this->numrows = 0;    // Number of rows in a result
      $this->affectedrows = 0; // Number of rows affected in an update / insert / delete
      $this->error = "";
      $this->foundrows = false;
      $this->lastquery = "";
      $this->timecreated = time();            // Time this instance was created
      $this->hosts = array();
      $this->hosts[] = array( "host" => ORACLEDB_HOST, "port" => ORACLEDB_PORT );
      $i = 2;
      while( defined( "ORACLEDB_HOST$i" ) ){
        $name = "ORACLEDB_HOST$i";
        $port = defined( "ORACLEDB_PORT$i" ) ? constant( "ORACLEDB_PORT$i" ) : ORACLEDB_PORT;
        $this->hosts[] = array( "host" => constant($name), "port" => $port );
        $i++;
      }
      $this->service = ORACLEDB_SERVICE;
      $this->user = ORACLEDB_USER;
      $this->pass = ORACLEDB_PASS;
      $this->debug = false;
      // $this->connect();
      addLogMessage( "End", "DB->DB()" );
    }
    
    function getConnectionString(){
      $rtn = "(DESCRIPTION = (ADDRESS_LIST = ";
      foreach( $this->hosts as $host ){
        $rtn .= "(ADDRESS = (PROTOCOL = TCP) (HOST = ".$host["host"].")(PORT = ".$host["port"]."))";
      }
      $rtn .= ") (CONNECT_DATA = (SERVICE_NAME = ".$this->service.")))";
      return $rtn;
    }
    
    function connect($name = '', $user = '', $pass = '', $host = '', $port = ''){
      /*
      echo "Connecting to ".$this->host."...\n";
      echo "Connection string: ".$this->getConnectionString()."\n";
      echo "Credentials: ".$this->user.":".$this->pass."\n";
      */
      // TODO: Implement connecting by provided params  
      if( $this->debug ) echo $this->getConnectionString()."\n";
      if( $this->debug ) echo "Attempting to connect...\n"; 
      if( !function_exists( "oci_connect" ) ){ 
        if( $this->debug ) echo "no function oci_connect exists\n";
        return false;
      }
      if( $this->debug ) echo "Connecting using user: ".$this->user.", pass: ".$this->pass."\n";
      $this->db = oci_connect( $this->user, $this->pass, $this->getConnectionString() );
      if( !$this->db ){ 
        if( $this->debug ) echo "Connect failed\n";
        return false;
      }
      else {
        if( $this->debug ) echo "Connected\n";
        return true;
      }
    }
    
    function close(){
      return @oci_close( $this->db );
    }
    
    function query( $sql ){
      global $query_count;
      $sql = trim( $sql );
      // if( !preg_match( "/;$/", $sql ) ) $sql .= ";";
      $this->error = "";
      $this->lastquery = $sql;
      addLogMessage( $sql, "DB->query()", "db_query" );
      if( !isset( $this->db ) ) if( !$this->connect() ){ 
        
        return false;
      }
      if( $this->debug ) echo "Parsing query:$sql\n";
      $stid = oci_parse( $this->db, $sql );
      if( $this->debug ) echo "Execing query\n";
      oci_execute( $stid, OCI_COMMIT_ON_SUCCESS );
      if( $this->debug ) echo "Query executed\n";
      $query_count++;
      if( $this->debug ) echo "Checking for errors...\n";
      $aErr = oci_error();
      if( $aErr["message"] != "" ){ 
        addLogMessage( $aErr["message"], "DB->query()", "db_error" );
        $this->error = $aErr["message"];
        if( $this->debug ) echo $this->error."\n";
      }

      if( $this->debug ) echo "Checking affected rows...\n";
      $this->affectedrows = @oci_num_rows( $stid );
      $this->rlt = $stid;
      if( $this->debug && !$this->rlt ) echo "No result\n";
      addLogMessage( "End", "DB->query()" );
      if( $this->debug ) echo "Returning\n";
      return $this;
    }
    
    
    function fetchRow(){
      if( $this->rlt ) return oci_fetch_assoc( $this->rlt );
      return false;
    }
    function updateOne( $table, $id, $values, $keyfield ){
      global $query_count;
      // Decode keyed array, assign bind vars
      if( is_array( $values ) ){
        $aBindVars = array();
        $sql = "UPDATE $table SET ";
        $aData = array();
        foreach( $values as $col => $v ){
          $bv = "bv_$col";
          $$bv = $v;
          $aBindVars[] = $bv;
          $aData[] = $col." = :".$bv;
        }
        $sql .= join( ", ", $aData );
        $sql .= " WHERE $keyfield = :id";
        $aBindVars[] = "id";
        die( $sql );
        $stid = oci_parse( $this->db, $sql );
        foreach( $aBindVars as $var ){
          oci_bind_by_name( $stid, ":".$var, $$var, 32 );
        }
        oci_execute( $stid );
      }else{
        $sql = "UPDATE $table SET ".$values." WHERE $keyfield = $id";
        if( !$this->query( $sql ) ){
          return false;
        }
      }
      oci_free_statement( $stid );
      $this->close();
      $aErr = oci_error();
      if( $aErr["message"] != "" ){ 
        $this->error = $aErr["message"];
        return false;
      }
      return true;
    }
    function insert( $table, $columns, $data, $keyfield="id" ){
      global $query_count;
      if( !isset( $this->db ) ) if( !$this->connect() ){ 
        return false;
      }
      $sql = "INSERT INTO ".$table." ( ".$columns." ) VALUES ( ";
      $aColumns = preg_split( "/,/", $columns );
      $aBindVars = array();
      if( is_array( $data ) ) $aData = $data;
      else $aData = preg_split( "/,/", $data ); // OK
      for( $i=0; $i<sizeof($aData); $i++ ){
        $colname = trim( $aColumns[$i] );
        
        // Put strings in bind vars
        $val = trim( $aData[$i] );
        if( preg_match( "/^'(.*)'$/", $val, $m ) ){
          $bv = "BV_$colname";
          $$bv = str_replace( "\'", "'", $m[1] );
          $aBindVars[] = $bv;
          $aData[$i] = ":".$bv; // All data is represented as bind vars
        }
      }
      $data = implode( ", ", $aData );
      $sql .= $data." ) returning $table.$keyfield into :id";
      $id = null;
      $stid = oci_parse( $this->db, $sql );
      foreach( $aBindVars as $var ){
        oci_bind_by_name( $stid, ":".$var, $$var, 32 );
      }
      oci_bind_by_name( $stid, ":id", $id, 32 );
      oci_execute( $stid );
      $aErr = oci_error( $stid );
      if( $aErr["message"] != "" ){ 
        addLogMessage( $aErr["message"], "DB->query()", "db_error" );
        $this->error = $aErr["message"];
        if( $this->debug ) echo $this->error."\n";
        /*
        print_r( $aErr );
        foreach( $aBindVars as $var ){
          echo $var." = ".$$var."<br>\n";
        }
        exit;
        */
      }
      return $id;
    }
    
    
    
    /**
    * Execute a stored procedure
    * @param string $name name of stored procedure
    * @param array $aInArgs assoc array of input params
    * @param array $aOutArgs assoc array of output params containing 
    */
    function execStoredProcedure( $name, $aInArgs=array(), $cursorname="" ){
      $this->connect();
      $args = "";
      $aBindings = array();
      $delim="";
      foreach( $aInArgs as $k => $v ){
        switch( gettype( $v ) ){
          case "object":
            switch( get_class( $v ) ){
              case "DateTime":
                $args .= $delim."to_date('".$v->format( "Ymd" )."', 'YYYYMMDD')";
                break;
            }
            break;
          
          default:
            $args .= $delim.$k;
            $aBindings[$k] = $v;
            break;
        }
        $delim = ", ";
      }
      $args .= $delim.$cursorname;
      $sql = "BEGIN $name($args); END;";
      echo $sql."\n";

      $stmt = oci_parse($this->db,$sql);

      //  Bind the input parameters
      // print_r($aInArgs);
      foreach( $aBindings as $k => $v ){
        oci_bind_by_name($stmt,$k,$aInArgs[$k]);
      }
      
      if( $cursorname != "" ){
        $recordset = oci_new_cursor($this->db);
        oci_bind_by_name($stmt, $cursorname, $recordset, -1, OCI_B_CURSOR);

        oci_execute($stmt);
        oci_execute($recordset, OCI_DEFAULT);
        oci_fetch_all($recordset, $cursor, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        return $cursor;
      }else{
        return $aBindings;
      }
    }
    
    /**
    * Run a parameterised statement in the form "WHERE $column = $value"
    */
    function retrieveWhereEqualTo( $table, $column, $value ){
      $table = preg_replace( "/[^a-z0-9A-Z\._]/", "", $table );
      $column = preg_replace( "/[^a-z0-9A-Z\._]/", "", $column );
      $this->connect();
      if( @!$this->db ) return false;
      $sql = "SELECT * FROM $table WHERE $column = :value";
      $this->debug = false;
      $stid = oci_parse( $this->db, $sql );
      switch( gettype( $value ) ){
        case "integer":
          if( $this->debug ) echo "Integer\n";
          $type = SQLT_INT;
          break;
          
        default:
        case "string":
          if( $this->debug ) echo "String\n";
          $type = SQLT_CHR;
          break;
      }
      oci_bind_by_name( $stid, ":value", $value );
      oci_execute( $stid, OCI_COMMIT_ON_SUCCESS );
      if( $this->debug ) echo "Query executed\n";
      $query_count++;
      if( $this->debug ) echo "Checking for errors...\n";
      $aErr = oci_error();
      if( $aErr["message"] != "" ){ 
        addLogMessage( $aErr["message"], "DB->query()", "db_error" );
        $this->error = $aErr["message"];
        if( $this->debug ) echo $this->error."\n";
      }

      if( $this->debug ) echo "Checking affected rows...\n";
      $this->affectedrows = @oci_num_rows( $stid );
      $this->rlt = $stid;
      if( $this->debug && !$this->rlt ) echo "No result\n";
      addLogMessage( "End", "DB->query()" );
      if( $this->debug ) echo "Returning\n";
      return $this;
    }
    
    // This shouldn't be running
    function escape( $str ){
      // return $str;
      exit;
    }
    
    /*
    function updateOrInsert( $table, $aColumns, $aData, $where ){
      $sql = "MERGE INTO ".$table." USING DUAL ON (".str_replace( "WHERE", "", $where).")
        when not matched then insert (".join(","$aColumns).") values (".join(",",$aData).")
        when matched then update set ";
      $aSets = array();
      for( $i=0; $i<sizeof( $aColumns ); $i++ ){
        $aSets[] = $aColumns[$i]." = ".$aData[$i];
      }
      $sql .= join( ", ", $aSets );
      $this->query( $sql );
    }
    */
  }
?>
