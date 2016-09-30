<?php

  // addLogMessage( "Start of Model class file", "Model" );
  require_once( "core/field.class.php" );
  require_once( "core/db.class.php" );
  require_once( "core/functions.php" );
  require_once( "core/session_user.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/reportable.interface.php" );
  class Model implements iReportable{
    function Model( $name="", $id=0 ){
      
      addLogMessage( "Constructing new ".$name, $name."->".$name."()" );
      $this->id = $id;
      $this->capturedsessidhash = ""; // The sessidhash that was sent with a form
      $this->metaid = 0;  // The ID of the row for this model in the model table
      $this->name         = $name;
      $this->aFields      = array();
      $this->tablename    = camelToUnderscore( $name );
      $this->displayname  = camelSplit( $name );
      $this->description  = "";
      $this->returnpage   = $this->tablename;
      $this->formtype     = "";
      $this->action       = "search";       // Set by action pages to pass delete, mail, new etc
      $this->aAuth        = array();
      $this->aErrors      = array();
      $this->aWarnings    = array();
      $this->id           = $id;
      $this->hasmany      = array();
      $this->listby       = "name";
      $this->aSearchFields = array();
      $this->aResultsFields = array();
      $this->aFieldSets   = array();
      $this->currentfieldset = "";
      $this->hastable = true;
      $this->dbclass = "DB";
      $this->keyfield = "id";
      $this->hasinterface = true;       // Whether a class is set up with an edit interface or not
      $this->autosync = true;
      $this->context = "";
      if( $this->id != 0 ) $this->get( $this->id );
      $this->access = "";
      $this->orderdir = "asc";
      $this->columnnames = "";          // Cacheable/codeable statement to put into the SELECT for this model
      $this->usegroupby = true;         // Uses GROUP BY statement in getWithJoins() - turn off to speed up things 
      $this->avoidjoinsinsearch = false;// For large data sets, avoid joining tables in getWithJoins()
      $this->calculations = array();    // Calculations on an existing model after any data has been added to them
      $this->inits = array();           // Optional things to init before any rendering, before data has been added to them
      $this->aEmailFields         = array();
      $this->allowfieldselect     = false;  // Allow users to select which fields they want to see in the search and results
      $this->allowcontactform     = false;  // Allow users to contact referenced users through the edit page of the model
      $this->allowfullexcelexport = true;  // Allow users to export the full result set prior to a search
      $this->allowsearchsummary   = true;   // Allow the statistical search summary for this model
      $this->allowemailcreate     = false;  // Let this object be created by an email sent to the server
      $this->allowattachments     = false;  // Whether this model can have attachments
      $this->allowduplicatelink   = false;  // If true, adds a link to a "new" form, propagated with form info from the current object
      $this->aAttachmentIds = array();      // Array of IDs of attachments being submitted to be saved
      $this->bodytextfield = "name";        // If this object can be inserted by email, put the body text in this field
      $this->authorfield = "user_id";       // If this object can be inserted by email, set the sender's ID to this field
      $this->aEditFormOptions = array();  // Extra links for the edit page next to "save", "delete", "add another" etc
      $this->aSearchListOptions = array();  // Extra links to render above search results with the "Add new", "Jump to top" and "Export for Excel" links
      $this->gotofield = "name";              // Use this field for external linking in "<model>/_goto/<something>" URLs
      $this->liststyle = "table";         // use "table" to render as a table
      $this->customjs = "";
      $this->issearchrow = false;
      $this->autoindex = true;            // Whether to run populateIdx on save or not
      $this->timecreated = time();        // Time the instance of this object was created
      
      addLogMessage( $name." constructor ended", $this->name."->".$this->name."()" );
    }
    
    
    /**
    * Initialise the object from an existing one in the database
    * @param int $id DB ID of the object to retrieve
    * @return bool true if successful
    */
    function get( $id=0 ){
      if( $id != 0 ) $this->id = $id;
      return $this->retrieve( $this->id );
    }
    
    /**
    * Initialise object by its name. Initialises to first found row if there is more than one.
    * @param string $name The name of the object to initialise
    * @return bool true if successful
    */
    function getByName( $name ){
      $db = Cache::getModel("DB");
      return $this->retrieveByClause( "WHERE name = '".$db->escape( $name )."'" );
    }
    
    /**
    * To be called before a group of fields in a model object
    * @param string $name The name of the fieldset
    */
    function startFieldSet( $name ){
      $this->currentfieldset = $name;
    }
    
    /**
    * To be called at the end of a group of fields in a model object
    */
    function endFieldSet(){
      $this->currentfieldset = "";
    }
    
    /**
    * Return the field object by name
    * @param string The column name to return
    * @return object The field
    */
    function getField($name){
      if (isset($this->aFields[$name])){
        return $this->aFields[$name];
      }
      else { 
        $name = camelToUnderscore(substr($name,3));
        if (isset($this->aFields[$name])){
          return $this->aFields[$name];
        }
      }
      return false;
    } 
    
    /**
    * Add an authorisation clause to the class
    * @param string $attrib Attribute of session to check
    * @param string $req Value to check attribute against
    * @param string Access that valid authorisation grants, the maximum access being "crud"
    *   - c: create
    *   - r: read/retrieve 
    *   - u: update
    *   - d: delete/destroy
    */
    function addAuth( $attrib, $req, $access="crud" ){
      $this->aAuth[] = array( $attrib, $req, $access );
    }
    
    /**
    * Add an authorisation clause based on user group
    * @param string $group Four-letter user group code
    * @param string $access Access that is granted - the maximum being "crud"
    * @see addAuth
    */
    function addAuthGroup( $group, $access="crud" ){
      if( !array_key_exists( "groups", $this->aAuth ) ) $this->aAuth["groups"] = array();
      $this->aAuth["groups"][] = array( "name" => $group, "access" => $access );
    }    
    
    /**
    * Add a field to the model
    * @param object $field 
    */
    function addField( $field ){
      if( !$field ) return false;
      $field->parentmodel = $this->name;
      $field->parent_tablename = $this->tablename;
      if( $field->type == "mem" ) $field->displayname = plural( preg_replace( "/ ?".$field->parentmodel." ?/", "", $field->displayname ) );
      if( $this->currentfieldset != "" ){
        if( !array_key_exists( $this->currentfieldset, $this->aFieldSets ) ) $this->aFieldSets[$this->currentfieldset] = array();
        $this->aFieldSets[$this->currentfieldset][] = $field->columnname;
        $field->fieldset = $this->currentfieldset;
      }
      $this->aFields[$field->columnname] = $field;
      if( $field->type == "fle" ) $this->formtype = "multipart/form-data";
    }
    
    /**
    * Remove a field, denoted by its field name
    * @param string $fieldname
    */
    function removeField( $fieldname ){
      $fieldname = camelToUnderscore( substr( $fieldname, 3 ) );
      unset( $this->aFields[$fieldname] );      
    }
    
    /**
    * Run any functions named in the ->inits array. This is called before a model is rendered 
    * e.g. to restructure a model based on the user's login
    */
    function doInits(){
      if( !isset( $this->inits ) ) return;
      foreach( $this->inits as $init ){
        if( method_exists( $this, $init ) ){
          $this->$init();
        }
      }
    }
    
    /**
    * Run any field calculations named in the ->calculations array. 
    * Calculations are run after data is brought into the object
    */
    function doCalculations(){
      if( !isset( $this->calculations ) ) return;
      foreach( $this->calculations as $calc ){
        if( method_exists( $this, $calc ) ){
          $this->$calc();
        }
      }
    }
    
    /**
    * Determine if an upload has succeeded on a file upload field
    * @param string $field_key The field key name to query
    * @return mixed false if upload fails, if successful returns field info array 
    */
    function uploadExists( $field_key ){
      if( !isset( $this->aFields[$field_key] ) ) return false;
      $field = $this->aFields[$field_key];
      if( !isset( $_FILES[$field->name] ) ) return false;
      $aFile = $_FILES[$field->name];
      if( !isset( $aFile["tmp_name"] ) ) return false;
      if( $aFile["tmp_name"] == "" ) return false;
      if( $aFile["size"] == 0 ) return false;
      if( !file_exists( $aFile["tmp_name"] ) ) return false;
      return $aFile;
    }
    
    /**
    * Run before a save to handle custom file upload functions 
    * @return bool false if a named upload function doesn't exist
    */
    function doUploadFunctions(){
    
      $return = true;
      foreach( $this->aFields as $field ){
        $func = $field->uploadFunction;
        if( $field->type == "fle" && $func != "" && method_exists( $this, $func ) ){
          if( !$this->$func($field->columnname) ) $return = false;
        }
      }
      return $return;
    }
    
    /*
    * Align any attachments for this model with the model
    */
    function alignAttachments(){
      if( !$this->allowattachments ) return false;
      if( sizeof( $this->aAttachmentIds ) == 0 ) return false;
      $db = Cache::getModel("DB");
      $sql = "
        UPDATE attachment
        SET model_name = '".$db->escape( get_class( $this ) )."',
          model_id = ".intval( $this->id )."
        WHERE id IN (".join(",",$this->aAttachmentIds).")
      ";
      $db->query( $sql );
    }
    
    /**
    * Set the "active" field to true, if it exists
    */
    function activate(){
      if( isset( $this->aFields["active"] ) ) $this->aFields["active"]->value = 1;
    }

    /**
    * Set the "active" field to false, if it exists
    */
    function deactivate(){
      if( isset( $this->aFields["active"] ) ) $this->aFields["active"]->value = 0;
    }
    
    /**
    * Determine if the user is authorised to access this object
    * @return bool
    */
    function isAuth(){
      $auth = $this->getAuth();
      if( $auth == "" ) return false;
      if( $auth !== false ) return true;
      return false;
    }
    
    /**
    * Get the authorisation string for the logged in user on this object
    * @return string 
    * @see addAuth
    */
    function getAuth(){
      if( !SessionUser::isLoggedIn() ){ 
        return false;
      }
      if( SessionUser::isAdmin() ) return "crud";
      if( sizeof( $this->aAuth ) == 0 ){ 
        return "";
      }
      
      // All authorisation clauses must be true
      $auth = "";
      $return = "";
      foreach( $this->aAuth as $key => $auth ){
        if( is_string( $auth[0] ) && !SessionUser::hasProperty( $auth[0] ) ){ 
          return false;
        }
        if( $key === "groups" ){
          foreach( $auth as $group ){
            if( SessionUser::isInGroup( $group["name"] ) ){ 
              $return .= $group["access"];
            }
          }
        }else{
          if( SessionUser::getProperty($auth[0]) != $auth[1] ){
            return false;
          }else{
            $return .= $auth[2];
          }
        }
      }
      return $return;
    }
    
    
    /**
    * Get the name of this object based on which fields are listed in ->listby (which defaults to "name"
    * @return string 
    */
    function getName(){
      $a = preg_split("/,/", $this->listby ); // OK
      $s = "";
      foreach( $a as $name ){
        if( $name == "id" ){
          $s .= $this->id." ";
          continue;
        }
        $s .= $this->aFields[$name]->toString()." ";
      }
      return trim( $s );
    }
    
    
    /**
    * Determine if a column is being used to list the object
    * @param string $column
    * @return bool
    */
    function isListedBy( $column ){
      $a = preg_split("/,/", $this->listby ); // OK
      foreach( $a as $name ){
        if( $column == $name ) return true;
      }
      return false;
    }
    
    /**
    * If this field has an integer field called "idx" then populate it with the correct ordering of all rows for this model (based on listby)
    */
    function populateIdx(){
      if( !array_key_exists( "idx", $this->aFields ) ) return false;
      $db = new $this->dbclass;
      $db->query( "SET @row = 0;" );
      $sql = "SELECT @row := @row + 1 AS row_num, id, idx FROM ".$this->tablename." ORDER BY ".$this->listby;
      $db->query( $sql );
      $db2 = new $this->dbclass;
      $aRows = array();
      while( $row = $db->fetchRow() ){
        if( $row["idx"] != $row["row_num"] ) $db->query( "UPDATE ".$this->tablename." SET idx = ".$row["row_num"]." WHERE id = ".$row["id"] );
      }
    }
    
    /**
    * Set the action property so that the model knows what the user is attempting to do (for validation)
    * @param string action
    */
    function setAction( $action ){
      if( $action == "" ) $action = "edit";
      $this->action = preg_replace( "/[^_a-z]+/", "", $action );
    }
    
    /* Get class action, this wrongly assumes that when unset it is a search, however for general case it is ok 
     * @return string;
     */
    function getAction(){
      return $this->action;
    }
    
    /**
    * Validate the values of each field against the current user.
    * Checks:
    *  - create, updated, delete permissions of user
    *  - automatic validation methods on each field object
    *  - custom validation method on this object
    * and propagates the ->aErrors and ->aWarnings arrays
    * @return bool false if not valid
    */
    function validate(){
      
      if( $this->action == "new" && strstr( $this->getAuth(), "c" ) === false ){
        $this->aErrors[] = array( "message" => "You do not have permission to create ".plural( $this->displayname ) );
      }
      if( $this->action == "edit" && strstr( $this->getAuth(), "u" ) === false ){
        $this->aErrors[] = array( "message" => "You do not have permission to edit ".plural( $this->displayname ) );
      }
      if( $this->action == "delete" && strstr( $this->getAuth(), "d" ) === false ){
        $this->aErrors[] = array( "message" => "You do not have permission to delete ".plural( $this->displayname ) );
      }
      
      // Check CSRF preventing sessidhash 
      if( preg_match( "/new|edit|delete/", $this->action ) ){
        if( $this->capturedsessidhash != hash( SITE_HASHALGO, session_id().SITE_SALT ) ){
          $this->aErrors[] = array( "message" => "The page you were previously on has expired. Please refresh the page and try again." );
        }
      }
    
      foreach( $this->aFields as $key => $field ){
        $err = $field->validate();
        if( is_array( $err ) ){ 
          if( $err["type"] == "error" ){
            $this->aErrors[] = $err;
          }else{
            $this->aWarnings[] = $err;
          }
        }
      }
      
      // Call specific validator if it exists for this class
      $validator = $this->tablename."Validate";
      if( method_exists( $this, $validator ) ){
        $this->$validator();
      }      
      
      if( sizeof( $this->aErrors ) > 0 ){ 
        return false;
      }
      return true;
    }
    
    
    /**
    * Goes through all fields and assigns values from the $_POST array
    * @param array $aFields An array of field keys which are supposed to be present on the submitted form
    */
    function getForm( $aFields = array() ){
      addLogMessage( "Start", $this->name."->getForm()" );
      if( isset( $_POST["id"] ) ){
        $this->id = intval( $_POST["id"] );
        $this->get();
        $this->doInits();
      }
      if( isset( $_POST["sessidhash"] ) ) $this->capturedsessidhash = $_POST["sessidhash"];
      if( empty( $_POST ) ) $_POST = $_GET;
      if( sizeof( $aFields ) == 0 ){
        $aFields = array_keys( $this->aFields );
      }
      foreach( $aFields as $key ){
        $this->aFields[$key]->getSubmittedValue($this->aFields[$key]->display);
      }
      
      // Get attachments
      if( $this->allowattachments && !empty( $_POST["attachments"] ) ){
        $a = preg_split("/,/",$_POST["attachments"]);
        foreach( $a as $v ){
          $v = intval($v);
          if( $v == 0 ) continue;
          $this->aAttachmentIds[] = $v;
        }
        
        // Only allow attachments that the current user has uploaded
        $sql = "
          SELECT id 
          FROM attachment
          WHERE id IN (".join(",",$this->aAttachmentIds).")
            AND created_by_id = ".intval(SessionUser::getId())."
        ";
        $db = Cache::getModel("DB");
        $db->query( $sql );
        $this->aAttachmentIds = array();
        while( $row = $db->fetchRow() ){
          $this->aAttachmentIds[] = intval( $row["id"] );
        }
      }
      addLogMessage( "End", $this->name."->getForm()" );
    }
    
    
    /**
    * Initialise the object from a database row ID
    * @param int $id 
    * @param bool $join Whether to automatically join foreign keys in the select
    * @return bool true if successful, false if no rows found
    */
    function retrieve( $id, $join=false ){
      if( $id < 1 ) return false;
      $db = Cache::getModel("DB");
      return $this->retrieveByClause( "WHERE ".$this->tablename.".id = ".$db->escape( $id ), $join );
    }
    
    // Allows for parameterised queries
    function retrieveWhereEqualTo( $column, $value ){
      $db = Cache::getModel( $this->dbclass );
      $db->retrieveWhereEqualTo( $this->tablename, $column, $value );
      $row = $db->fetchRow();
      $this->initFromRow( $row );
      return $this->id != "";
      
    }
    /**
    * Initialise the object from an arbitrary database clause on the object's table
    * @param string $clause The "WHERE" clause (including the word "WHERE")
    * @param bool $join whether to join foreign keys
    * @return bool true if successful, false if no rows found
    */
    function retrieveByClause( $clause, $join=false ){
      
      addLogMessage( "Start", $this->name."->retrieveByClause()" );
      
      if( $join ){
        $dbr = $this->getWithJoins( $clause );
	      if( !$dbr->rlt ){ 
          addLogMessage( "End", $this->name."->retrieveByClause()" );
          $this->id = 0;
          return false;
        }
        $data = $dbr->fetchRow();
      }else{
	      $db = new $this->dbclass;
	      $dbr = $db->getByClause( $this->tablename, $clause );
	      if( empty( $dbr ) || !$dbr->rlt ){ 
          addLogMessage( "End", $this->name."->retrieveByClause()" );
          $this->id = 0;
          return false;
	      }
        $data = $db->fetchRow();
      }
      $this->initFromRow( $data );
      addLogMessage( "End", $this->name."->retrieveByClause()" );
      return true;
    }
    
    /**
    * Pass a query result row to init values
    */
    function initFromRow( $data ){
      if( sizeof( $this->aFields ) > 0 ){
        foreach( $this->aFields as $key => $field ){
          if( $key == "id" ) continue;
          if( isset( $data[$key] ) ){ 
            $this->aFields[$key]->setFromDb( $data[$key] );
            $this->aFields[$key]->resetHasChanged();
          }
          if( isset( $data["id"] ) ) $this->aFields[$key]->parentid = $data["id"];
        }
      }
      
      // Guess at the fields if they haven't been defined 
      else{
        foreach( $data as $key => $value ){
          if( $key == "id" ) continue;
          $t = is_int( $value ) ? "int" : "str";
          $this->addField( Field::create( $t.underscoreToCamel( $key ) ) );
          $this->aFields[$key]->value = $value;
          if( isset( $data["id"] ) ) $this->aFields[$key]->parentid = $data["id"];
        }
      }
      // unset( $db );
      // unset( $dbr );
      if( isset( $data["id"] ) ) $this->id = $data["id"];
      $this->doCalculations();
      return true;
    }
    
    /**
    * Get the ID of this object in the model metadata table
    * @return int 
    */
    function getMetaId(){
      if( $this->metaid != 0 ) return $this->metaid;
      if( !isset( $this->db ) ) $this->db = new $this->dbclass;
      $sql = "SELECT * FROM model WHERE name = '".$this->tablename."'";
      $this->db->query( $sql );
      if( $this->db->numrows > 0 ){
        $row = $this->db->fetchRow();
        $this->metaid = $row["id"];
      }
      return $this->metaid;
    }
    
    /**
    * Add this model to the metadata table
    * @return int The ID of this model in the metadata table
    */
    function addToMetaTable(){
      $db = new $this->dbclass;
      $this->metaid = $db->insert( "model", "name", "'".$this->tablename."'" );
      // unset( $db );
      return $this->metaid;
    }
    
    /**
    * Remove this model from the metadata table
    */
    function removeFromMetaTable(){
      if( $this->metaid == 0 ){
        $this->metaid = $this->getMetaId();
      }
      if( $this->metaid == 0 ){
        $db = new $this->dbclass;
        $db->delete( "model", $this->metaid );
        // unset( $db );
      }
    }
    
    
    
    /**
    * Create a table in the database for this object
    * @return mixed true if successful, SQL error string if unsuccessful
    */
    function createTable(){
      $aColumns = array();
      $aOptions = $this->getTableOptions();
      foreach( $this->aFields as $key => $field ){
        if( $field->hascolumn ){ 
          $aColumns[] = array( "name" => $key, "datatype" => $field->getDataType(), "default" => $field->getColumnOptions() );
        }
      }
      $db = new $this->dbclass;
      $return = $db->createTable( $this->tablename, $aColumns, $aOptions );
      if( $return === true && method_exists( $this, "afterCreateTable" ) ) $this->afterCreateTable();
      // unset( $db );
      return $return;
    }
    
    /**
    * Get array of options strings on the table such as foreign keys, indexes
    */
    function getTableOptions(){
      $aOptions = array();
      foreach( $this->aFields as $key => $field ){
        if( $field->hascolumn ){ 
          if( $field->index ) $aOptions[] = "KEY IDX_".$field->columnname." (".$field->columnname.")";
          if( trim( $field->belongsto ) != "" ){
            $m = Cache::getModel( $field->belongsto );
            $table = $m instanceof Model && $m->tablename != "" ? $m->tablename : camelToUnderscore( $field->belongsto );
            // Create foreign key
            $fk = "FK_".$this->tablename."_".$field->columnname;
            $aOptions[] = "CONSTRAINT ".$fk." FOREIGN KEY ".$fk." (".$field->columnname.") REFERENCES ".$table." (id)";
          }
        }
      }
      return $aOptions;
    }
    
    /**
    * Alter table options
    */
    function alterTableOptions(){
      $aOptions = $this->getTableOptions();
      if( sizeof( $aOptions ) == 0 ) return true;
      $db = new $this->dbclass;
      $rtn = $db->alterTableOptions( $this->tablename, $aOptions );
      return $rtn;
    }
    
    
    /**
    * Create an index on the specified column in the database
    * @param string $columnname 
    * @return bool Currently always true
    */
    function createIndex( $columnname ){
      $db = new $this->dbclass;
      if( !isset( $this->aFields[$columnname] ) ){
        return false;
      }
      $field = $this->aFields[$columnname];
      $indexname = $field->getIndexName();
      $db->createIndex( $this->tablename, $columnname, $indexname );
      return true;
    }
    
    /**
    * Drop the table for this object
    * @return bool Currently always true
    */
    function dropTable(){
      $db = new $this->dbclass;
      $db->dropTable( $this->tablename );
      // unset( $db );
      return true;
    }
    
    /**
    * Add a column to the DB based on one of the fields in this object
    * @return object the DB interface object instance of the query
    */
    function addColumn( $columnname ){
      $db = new $this->dbclass;
      $return = $db->addColumn( $this->tablename, $columnname, $this->aFields[$columnname]->getDataType().$this->aFields[$columnname]->getColumnOptions() );
      if( $this->aFields[$columnname]->belongsto != "" ){
        $m = Cache::getModel( $this->aFields[$columnname]->belongsto );
        $db->addForeignKey( $this->tablename, $columnname, $m->tablename, "id" );
      }
      if( $this->aFields[$columnname]->afterAddColumnMethod != "" && method_exists( $this, $this->aFields[$columnname]->afterAddColumnMethod ) ){ 
        $method = $this->aFields[$columnname]->afterAddColumnMethod;
        $this->$method( $columnname );
      }
      return $return;
      // unset( $db );
    }
    
    /**
    * Remove a field's column from the DB
    * @param string $columnname
    * @return object the DB object that executed the query
    */
    function removeColumn( $columnname ){
      $db = new $this->dbclass;
      return $db->removeColumn( $this->tablename, $columnname );
    }
    
    /**
    * Modify a columns DB options / data type / default value
    *
    * Uses the values returned from Field->getDataType() and Field->getColumnOptions() 
    * @param string $columnname
    * @return object DB object
    */
    function modifyColumn( $columnname, $previoustype ){
      $db = new $this->dbclass;
      $field = $this->aFields[$columnname];
      $field->parent_tablename = $this->tablename;
      $rtn = $db->modifyColumn( $this->tablename, $columnname, $field->getDataType(), $field->getColumnOptions() );
      if( method_exists( $field, "afterModifyColumn" ) ) $field->afterModifyColumn( $previoustype );
      return $rtn;
    }
    
    /**
    * The very last thing to do before saving an object
    *
    * Runs methods named <tablename>Finally() in the object if they exist
    * @return mixed returns from the custom method or true if there are no custom methods
    * @see afterInsert
    * @see afterUpdate
    */
    function doFinally(){
      $finally = $this->tablename."Finally";
      if( method_exists( $this, $finally ) ){
        return $this->$finally();
      }      
      return true;
    }
    
    /**
    * First thing to do after saving
    *
    * Calls custom method <tablename>AfterInsert() in the object if it exists
    * @return mixed returns from the custom method or true 
    * @see finally
    * @see afterUpdate
    */
    function afterInsert(){
      $afterinsert = $this->tablename."AfterInsert";
      if( method_exists( $this, $afterinsert ) ){
        return $this->$afterinsert();
      }
      return true;
    }
    
    /**
    * First thing to do after an update
    * @return mixed returns from the custom method or true 
    * @see finally
    * @see afterInsert
    */
    function afterUpdate(){
      $afterupdate = $this->tablename."AfterUpdate";
      if( method_exists( $this, $afterupdate ) ){
        return $this->$afterupdate();
      }
      return true;
    }
    
    /**
    * Save the object to the database
    *
    * This uses ->add() if ->id is zero or ->update() if not. Propagates the Flash with messages about the save.
    * @return mixed The ID of the added row or boolean status of the update attempt
    */
    function save(){
      addLogMessage( "Start", $this->name."->save()" );
      $this->doFinally();
      addLogMessage( "Update or insert?", $this->name."->save()" );
      if( 
        ( $this->keyfield == "id" && $this->id != 0 ) 
        || 
        ( $this->keyfield != "id" && $this->id )
      ){
        addLogMessage( "Update", $this->name."->save()" );
        $return = $this->update();
        $this->afterUpdate();
        if( $this->listFieldsHaveChanged() && $this->autoindex ) $this->populateIdx();
      }else{
        addLogMessage( "Insert", $this->name."->save()" );
        $return = $this->add();
        // if( intval( $return ) > 0 ) $this->id = intval( $return );
        $this->afterInsert();
        if( $this->autoindex ) $this->populateIdx();
      }
      $notice = $this->displayname;
      if( $this->getName() != "" ) $notice .= " \"".$this->getName()."\"";
      
      // Save successful
      if( $return ){
        $notice .= " saved at ".date( SITE_TIMEFORMAT );
        if (count($this->aWarnings)>0) $notice .= ", however there are some warnings:";
        foreach( $this->aWarnings as $warn ){
          Flash::addWarning($warn["message"],$warn["fieldname"]);
        }
        // if( $this->id != 0 ) $notice .= " <a href=\"".SITE_ROOT.$this->tablename."/edit/".$this->id."\" class=\"edit\">edit</a>";
        Flash::setNotice($notice);
        
        // Unflag any changed fields to denote unchanged
        $this->resetFieldChangedFlags();
      }
      
      // Unsuccessful save
      else{
        Flash::addError($notice." could not be saved");
      }
      addLogMessage( "End", $this->name."->save()" );
      return $return;
    }
    
    /**
    * Unflag all fields back to unchanged status
    */
    function resetFieldChangedFlags(){
      foreach( $this->aFields as $f => $field ){
        $this->aFields[$f]->resetHasChanged();
      }
    }
    
    /**
    * Check if the fields which this model is typically listed by have changed with the last update
    * @return bool 
    */
    function listFieldsHaveChanged(){
      $a = preg_split( "/,/", $this->listby ); // OK
      foreach( $a as $f ){
        if( !array_key_exists( $f, $this->aFields ) ) continue;
        if( $this->aFields[$f]->haschanged ) return true;
      }
      return false;
    }

    
    /**
    * Add the object to the database
    *
    * Before calling a database insert, this also sets the following auto fields:
    *  - created_at: Time the object was created
    *  - updated_at: Time the object was updated
    *  - created_by_id: User ID of the person who created this, if that information exists
    *  - updated_by_id: User ID of the person who updated this, if that information exists
    * @return int the ID of the new row
    */
    function add(){
      $db = new $this->dbclass;
       
      // Any auto fields?
      if( isset( $this->aFields["created_at"] ) ){
        $this->aFields["created_at"]->value = time();
      }
      if( isset( $this->aFields["updated_at"] ) ){
        $this->aFields["updated_at"]->value = time();
      }
      if( isset( $this->aFields["created_by_id"] ) ){
        $this->aFields["created_by_id"]->value = SessionUser::isLoggedIn() ? SessionUser::getId() : 0;
      }
      if( isset( $this->aFields["updated_by_id"] ) ){
        $this->aFields["updated_by_id"]->value = SessionUser::isLoggedIn() ? SessionUser::getId() : 0;
      }
    
      // Collect all field names. Ignore non-text that are empty
      $comma = "";
      $fields = "";
      foreach( $this->aFields as $col => $field ){
        if( !$field->hascolumn ) continue;
        if( !empty( $field->value ) || ( $field->type == "str" || $field->type == "txt" ) ){
          $fields .= $comma." ".$field->columnname;
          $comma = ",";
        }else{
        }
      }
      unset( $field );
      
      // Collect all data
      $comma = "";
      $data = "";
      $aData = array();
      foreach( $this->aFields as $field ){
        if( !$field->hascolumn ) continue;
        if( !empty( $field->value ) || ( $field->type == "str" || $field->type == "txt" ) ){
          $data .= $comma;
          $data .= $field->getDBString();
          $aData[] = $field->getDBString();
          $comma = ",";
        }
      }
      unset( $field );
      if( $this->keyfield != "id" ) $id = $db->insert( $this->tablename, $fields, $aData, $this->keyfield );
      else $id = $db->insert( $this->tablename, $fields, $aData );
      $this->id = $id;
      
      // Save off associated fields which have no columns
      foreach( $this->aFields as $field ){
        switch( $field->type ){
          case "grd":
          case "chk":
          case "rdo":
            if( $field->editable ) $field->saveMemberField( $id );
            break;
        }
      }
      // $db->close();
      unset( $data );
      // unset( $db );
      unset( $field );
      unset( $aData );
      return $id;
    }
    
    
    /**
    * Update an existing object in the DB
    * Before calling the database update, this also sets the following auto fields:
    *  - updated_at: Time the object was updated
    *  - updated_by_id: User ID of the person who updated this, if that information exists
    * @return bool true if successful
    */
    function update(){
      if( isset( $this->aFields["updated_at"] ) ){
        $this->aFields["updated_at"]->value = time();
      }
      
      if( isset( $this->aFields["updated_by_id"] ) ){
        $this->aFields["updated_by_id"]->value = SessionUser::isLoggedIn() ? SessionUser::getId() : 0;
      }
      
      $update = "";
      $comma = "";
      $aValues = array();
      foreach( $this->aFields as $key => $field ){
        if( $key == "created_at" ) continue;
        if( $key == "created_by" ) continue;
        if( $field->hascolumn ){
          // $update .= "$comma $key = ";
          $update .= $comma." ".$field->getDBString( false, true );
          $aValues[$field->columnname] = $field->getDBString( false, false );
          $comma = ",";
        }
        switch( $field->type ){
          case "grd":
          case "chk":
            if( $field->editable ) $field->saveMemberField( $this->id );
            break;
        }
      }
      $db = new $this->dbclass;
      if( preg_match( "/[^0-9]/", $this->id ) ) $id = "'".$this->id."'";
      else $id = $this->id;
      
      // Pass by keyed array
      $return = $db->updateOne( $this->tablename, $id, $aValues, $this->keyfield );
      
      // Pass by pre-escaped string
      // $return = $db->updateOne( $this->tablename, $id, $update, $this->keyfield );
      
      // $db->close();
      // unset( $db );
      return $return;
    }
    
    /**
    * Truncate the model table
    * 
    * Simply calls TRUNCATE on the model table
    */
    function truncate(){
      $db = new $this->dbclass;
      return $db->query( "TRUNCATE ".$this->tablename );
    }
    
    /**
    * Remove the object from the database
    *
    * This also calls ->deleteTidy() 
    * @see deleteTidy
    */
    function delete( ){
      $this->doFinally();
      $db = new $this->dbclass;
      $notice = $this->displayname;
      $notice .= " \"".$this->getName()."\"";
      
      // Deleted OK
      if( $db->delete( $this->tablename, $this->id ) ){
        $notice .= " successfully deleted at ".date( SITE_TIMEFORMAT );
        $rtn = true;
        Flash::setNotice($notice);
      }
      
      // Did not delete OK
      else{
        $notice .= " could not be deleted ".date( SITE_TIMEFORMAT );
        $rtn = false;
        Flash::addError($notice);
      }
      // unset( $db );
      $this->deleteTidy();
      return $rtn;
    }
    
    
    /**
    * Attempts to remove any rows in other tables which may now be orphaned due to foreign key relationships
    */
    function deleteTidy(){
      foreach( $this->aFields as $field ){
        switch( $field->type ){
          case "grd":
          case "mem":
          case "chk":
          
            // Delete the member table row that relates to this user
            $sql = "DELETE FROM ".$field->columnname." WHERE ".$this->tablename."_id = ".$this->id;
            $db = new $this->dbclass;
            $db->query( $sql );
            
            // Just in case there are some loose ends
            $sql = "DELETE FROM ".$field->columnname." WHERE ".$this->tablename."_id IS NULL";
            $db = new $this->dbclass;
            $db->query( $sql );
            break;
        }
      }
    }    
    
    
    /**
    * Get the ID of an object by it's name
    * @param string $name
    * @return int 
    */
    function getIdByName( $name ){
      $db = new $this->dbclass;
      $name = trim( $name );
      return $db->getIdByField( $this->tablename, "name", $name );
    }
    
    
    /**
    * Get the names of columns expected from a search query, taking into account autojoins with foreign keys and other tables
    * @return array
    */
    function getColumnNames( $aColumns=array() ){
      addLogMessage( "Start", $this->name."->getColumnNames()" );
      addLogMessage( implode( ", ", $aColumns ), $this->name."->getColumnNames()" );
      if( $this->columnnames != "" ){ 
        addLogMessage( "Returning cached columnnames, Array( ".join( ", ", $this->columnnames )." )" );
        addLogMessage( "End", $this->name."->getColumnNames()" );
        return $this->columnnames;
      }
      $a = array();
      if( sizeof( $aColumns ) == 0 ) $aColumns = array_keys( $this->aFields );
      if( array_key_exists( "idx", $this->aFields ) && array_search( "idx", $aColumns ) === false ) $aColumns[] = "idx";
      foreach( $aColumns as $key ){
        if( !isset( $this->aFields[$key] ) ) continue;
        $field = $this->aFields[$key];
        
        // Anything required for calculations?
        /*
        echo "<br>\n".$key."<br>\n";
        print_r( $field->aUsesFields );
        if( sizeof( $field->aUsesFields ) > 0 ){
          foreach( $field->aUsesFields as $f ){
            echo $f."<br>\n";
            if( array_search( $f, $aColumns ) === false ){
              $aColumns[] = $f;
            }
          }
        }
        */
        
        // These column names cached?
        /*
        if( sizeof( $field->aFkColumnNames ) > 0 ){
          addLogMessage( "Columns cached for ".$field->name.": Array( ".join( ", ", $field->aFkColumnNames )." )" );
          foreach( $field->aFkColumnNames as $c ){
            $a[] = $c;
          }
          continue;
        }
        */
        
        if( sizeof( $field->lookup ) > 0 && $field->autojoin ){
          foreach( $field->lookup["columns"] as $c ){
            $a[] = $c;
          }
        }
        if( $field->type == "grd" && $field->autojoin ){
          require_once( "models/".$field->columnname.".model.class.php" );
          $l = substr( $field->name, 3 );
          $l = Cache::getModel( $l );
          $key = "";
          foreach( $l->aFields as $k => $f ){
            if( $f->belongsto == "" ){ 
              $key = $k;
              break;
            }
          }
          $joinedto = $field->linksto;
          // $joinedto = preg_replace( "/_?".$this->tablename."_?/", "", $field->columnname, 1 );
          $a[] = "GROUP_CONCAT( DISTINCT CONCAT( ".$field->columnname."_".$joinedto.".name, ': ', ".$this->tablename."_".$field->columnname.".".$key." ) SEPARATOR '; ' ) as ".$field->columnname;
          // $a[] = "GROUP_CONCAT( ".$field->columnname."_".$joinedto.".name SEPARATOR '; ' ) as ".$field->columnname;
        }
        
        if( ( $field->type == "mem" || $field->type == "chk" ) && $field->autojoin ){ 
          require_once( "models/".$field->columnname.".model.class.php" );
          $l = substr( $field->name, 3 );
          $l = Cache::getModel( $l );
          $joinedto = camelToUnderscore( $this->name == $l->left ? $l->right : $l->left );
          // $joinedto = preg_replace( "/_?".$this->tablename."_?/", "", $field->columnname, 1 );
          $a[] = "GROUP_CONCAT( DISTINCT ".$field->columnname."_".$joinedto.".name SEPARATOR '; ' ) as ".$field->columnname;
        }
        if( !$field->hascolumn ) continue;
        $a[] = $this->tablename.".".$field->columnname;
        if( $field->belongsto != "" && $field->autojoin ){
          addLogMessage( "belongsto=".$field->belongsto." and autojoin on" );
          /*
          $table = camelToUnderscore( $field->belongsto );
          require_once( "models/".$table.".model.class.php" );
          $mdl = $field->belongsto;
          */
          
          // What to list the autojoined table by
          $oTbl = Cache::getModel( $field->belongsto );
          if( $field->listby != "" && $field->listby != "name" ){
            $aList = preg_split( "/,/", $field->listby ); // OK
          }else{
            $aList = preg_split( "/,/", $oTbl->listby ); // OK
          }
          
          // Add in idx field if it exists
          if( array_key_exists( "idx", $oTbl->aFields ) ){
            array_unshift( $aList, "idx" );
          }
          $alias = $field->columnname;
          $alias = preg_replace( "/_id$/", "", $alias );
          foreach( $aList as $key ){
            if( !isset( $oTbl->aFields[$key] ) ) continue;
            $f = $oTbl->aFields[$key];
            // foreach( $oTbl->aFields as $key => $f ){
            if( array_search( $key, $oTbl->aResultsFields ) !== false || array_search( $key, $aList ) !== false ){
              $f->autojoin = true;
            }
            if( !$f->hascolumn || !$f->autojoin ) continue;
            $a[] = $this->tablename."_".$alias.".".$f->columnname." AS ".$this->tablename."_".$alias."_".$f->columnname;
          }
          // $a[] = $table.".*";
        }
      }
      if( sizeof( $a ) > 0 ) $a[] = $this->tablename.".id";
      addLogMessage( "return: Array( ".join(", ",$a)." )", $this->name."->getColumnNames()" );
      addLogMessage( "End", $this->name."->getColumnNames()" );
      return $a;
    }
    
    
    /**
    * Get information about which tables to join to in a search query
    * @return array
    */
    function getJoins(){
      $aJoin = array();
      foreach( $this->aFields as $field ){
        if( $field->belongsto != "" && $field->autojoin && sizeof( $field->lookup ) == 0 ){
          $alias = $field->columnname;
          $alias = preg_replace( "/_id$/", "", $alias );
          $table = camelToUnderscore( $field->belongsto );
          $aJoin[] = array( "table" => $table,  "column" => $field->columnname, "alias" => $alias );
        }
        
        // Member fields
        if( ( $field->type == "mem" || $field->type == "grd" || $field->type == "chk" ) && $field->autojoin ){
          $aJoin[] = array( 
            "table" => $field->columnname, 
            "column" => $field->columnname, 
            "alias" => $this->tablename."_".$field->columnname, 
            "mem" => true, 
            "side" => "left" 
          );
          require_once( "models/".$field->columnname.".model.class.php" );
          $l = substr( $field->name, 3 );
          $l = Cache::getModel( $l );
          if( $field->type == "grd" ) $joinedto = $field->linksto;
          else $joinedto = camelToUnderscore( $this->name == $l->left ? $l->right : $l->left );
          
          // $joinedto = preg_replace( "/_?".$this->tablename."_?/", "", $field->columnname, 1 );
          // $key = $membertable."_".$joinedto."_name";
          $aJoin[] = array( 
            "table" => $joinedto, 
            "column" => $this->tablename."_".$field->columnname, 
            "alias" => $field->columnname."_".$joinedto, 
            "mem" => true, 
            "side" => "right" 
          );
        }
      }
      // print_r( $aJoin );
      return $aJoin;
    }
    
    
    /**
    * Constructs and executes an SQL query, auto-joining foreign keys and taking a search clause
    * @param string $clause A "WHERE" clause
    * @param string $extrajoins SQL for adding arbitrary joins to the query
    * @param int $limit Impose a row limit on the query
    * @param bool $searchonly Limit the returned columns to only those that are relevant to the search/list for this model
    * @return object DB object that ran the query
    */
    function getWithJoins( $clause = '', $extrajoins = "", $limit=0, $aUseColumns=array() ){
      
      addLogMessage( "Start", $this->name."->getWithJoins()" );
      
      // Check that all the fields required for calculations are present
      foreach( $aUseColumns as $c ){
        if( !isset( $this->aFields[$c] ) ) continue;
        $field = $this->aFields[$c];
        if( $field->isCalculated() ){
          foreach( $field->aUsesFields as $f ){
            if( array_search( $f, $aUseColumns ) === false ){
              $aUseColumns[] = $f;
            }
          }
        }
      }
      
      if( $this->avoidjoinsinsearch ){
        for( $i=0; $i<sizeof($aUseColumns); $i++ ){
          $c = $aUseColumns[$i];
          if( !empty( $this->aFields[$c]->textfield ) && isset($this->aFields[$this->aFields[$c]->textfield]) ){
            $aUseColumns[$i] = $this->aFields[$c]->textfield;
          }
        }
      }
      
      if( sizeof( $aUseColumns ) > 0 ){
        $aColumns = $this->getColumnNames($aUseColumns);
      }else{
        $aColumns = $this->getColumnNames();
      }
      if( sizeof( $aColumns ) == 0 ) $aColumns[] = "*";
      
      // JOIN with other tables?
      $j = '';
      
      if( !$this->avoidjoinsinsearch ){
        $aJoin = $this->getJoins();
        if( sizeof( $aJoin ) > 0 ){ 
          foreach( $aJoin as $join ){
            $j .= " ";
            // $j .= $this->aFields[$join["column"]]->required ? "INNER" : "LEFT OUTER";
            $j .= "LEFT OUTER";
            if( !isset( $join["mem"] ) ){
              $j .= " JOIN ".$join["table"]." ".$this->tablename."_".$join["alias"]." ON ".$this->tablename."_".$join["alias"].".id = ".$this->tablename.".".$join["column"]."\n";
            }else{
              if( $join["side"] == "left" ) $j .= " JOIN ".$join["table"]." ".$join["alias"]." ON ".$join["alias"].".".$this->tablename."_id = ".$this->tablename.".id\n";
              else $j .= " JOIN ".$join["table"]." ".$join["alias"]." ON ".$join["column"].".".$join["table"]."_id = ".$join["alias"].".id\n";
            }
          }
        }
        foreach( $this->aFields as $field ){
          if( sizeof( $field->lookup ) > 0 && $field->autojoin ){
            addLogMessage( "Using join from lookup from ".$field->name.": ".$field->lookup["join"], $this->name."->getWithJoins()" );
            if( isset( $field->lookup["join"] ) ) $j .= " ".$field->lookup["join"]."\n";
            // if( isset( $field->lookup["search"]["join"] ) ) $j .= $field->lookup["search"]["join"]."\n";
          }
        }
      }
      $j .= " ".$extrajoins."\n";
      /*
      $aListBy = split( ",", $this->listby );
      $groupby = "";
      $comma = "";
      foreach( $aListBy as $f ){
        $groupby .= $comma.$this->tablename.".".$f;
        $comma = ", ";
      }
      */
      $groupby = $this->tablename.".id";
      if( $this->usegroupby ) $clause = str_replace( "ORDER BY", "GROUP BY ".$groupby."\n ORDER BY", $clause );
      $sql = "SELECT SQL_CALC_FOUND_ROWS ".implode( ", ", $aColumns )." \nFROM ".$this->tablename.$j.$clause;
      $db = new $this->dbclass;
      
      if( $limit > 0 ){
        $sql .= " LIMIT ".intval( $limit );
      }
      $db->query( $sql );
      // pre_r( $db->getSummary() );
      SessionDb::setLastSearchSql($sql);
      addLogMessage( "End", $this->name."->getWithJoins()" );
      return $db;
    }
    
    /**
    * Get all of this type of object as a DB query
    * @param string $clause An SQL clause to impose on the query
    * @param string $extrajoins Arbitrary extra joins to add to the query
    * @param int $limit DB Row limit
    * @return object DB query that ran the query
    */
    function getAll( $clause="", $extrajoins = "", $limit=0 ){
      addLogMessage( "Start", $this->name."->getAll()" );
      // $order = " $clause \nORDER BY ";
      addLogMessage( "Listby: ".$this->listby, $this->name."->getAll()" );
      $a = preg_split( "/,/", $this->listby ); // OK
      $aKeys = $a;
      $b = array();
      foreach( $a as $k => $c ){
        if( isset( $this->aFields[$c] ) ) $field = $this->aFields[$c];
        else continue;
        addLogMessage( "Order by column: ".$field->name, $this->name."->getAll()" );
        $s = $field->getOrderClause( $this->tablename, $this->orderdir );
        addLogMessage( $s, $this->name."->getAll()" );
        if( $s != "" ) $b[] = $s;
        if( $field->isCalculated() ){
          foreach( $field->aUsesFields as $f ){
            if( array_search( $f, $aKeys ) === false ){
              $aKeys[] = $f;
            }
          }
        }
      }
      if( array_key_exists( "idx", $this->aFields ) ){
        $order = "idx";
      }else{
        $order = join( ", ", $b );
      }
      if( isset( $order ) && $order != "" ) $order = " $clause \nORDER BY ".$order;
      else $order = " ".$clause;
      addLogMessage( "order: ".$order.", extrajoins: ".$extrajoins.", limit: ".$limit.", Keys: ".join("; ",$aKeys), $this->name."->getAll()" );
      $rtn = $this->getWithJoins( $order, $extrajoins, $limit, $aKeys );
      addLogMessage( "End", $this->name."->getAll()" );
      return $rtn;
    }
    
    
    /**
    * Delete all object rows matching the clause (or all if no clause)
    * @param string $clause Clause to restrict delete
    * @param bool success of delete query
    */
    function deleteAll( $clause="" ){
      $db = new $this->dbclass;
      return $db->deleteByClause( $this->tablename, $clause );
    }
    
    /**
    * Examine $_GET to construct an SQL WHERE clause
    * @return array array of clauses to attach after a "WHERE"
    */
    function getSearchWhereClauses(){
      addLogMessage( "Start", $this->name."->getSearchWhereClauses()" );
      $aWhere = array();
      if( array_key_exists( "active", $this->aFields ) ){ 
        if( $this->aFields["active"]->value > -1 ) $aWhere[] = $this->tablename.".active = ".($this->aFields["active"]->value == 1 ? 1 : 0);
      }
      foreach( $this->aFields as $key => $field ){
        if( array_search( $field->columnname, $this->aSearchFields ) !== false ) $this->aFields[$field->columnname]->autojoin = true;
        if( $field->columnname == "name" ) continue;
        if( $field->columnname == "active" ) continue;
        if( $this->aFields[$key]->setIsSearchedOn() ){
          $field->issearchedon = true;
        }
        if( $field->issearchedon ){
          addLogMessage( $field->name." searched on", $this->name."->getSearchWhereClauses()" );
          $where = $field->getSearchString();
          if( $this->debug ) echo $field->name." = ".$where."\n";
          if( strlen( $where ) > 0 ) $aWhere[] = $where;
        }
      }
      $aOr = array();
      $db = new $this->dbclass;
      $aListBy = preg_split( "/,/", $this->listby ); // OK
      
      // Name searches search for whatever it's listed by
      if( ( !empty( $_GET["name"] ) || isset( $_GET["name-blank"] ) ) && isset( $this->aFields["name"] ) ){
        foreach( $aListBy as $f ){
          if( isset( $_GET[$f."-blank"] ) ) $aOr[] = $this->tablename.".$f = ''";
          if( !empty( $_GET[$f] ) ) $aOr[] = $this->tablename.".$f like '%".$db->escape( urldecode( urldecode( $_GET["name"] ) ) )."%'";
        }
        if( $this->listby != "name" ){ 
          if( isset( $_GET["name-blank"] ) ) $aOr[] = $this->tablename.".name = ''";
          if( !empty( $_GET["name"] ) ) $aOr[] = $this->tablename.".name like '%".$db->escape( urldecode( urldecode( $_GET["name"] ) ) )."%'";
        }
        // Now, concatenate these fields and compare to that
        if( sizeof( $aListBy ) > 1 ){
          $conc = "CONCAT( ";
          $sp = "";
          foreach( $aListBy as $f ){
            $conc .= $sp.$this->tablename.".".$f;
            $sp = ", ' ', ";
          }
          $conc .= " ) ";
          if( isset( $_GET["name-blank"] ) ) $conc .= " = ''";
          else $conc .= " like '%".$db->escape( urldecode( urldecode( $_GET["name"] ) ) )."%'";
          $aOr[] = $conc;
        }
        $aWhere[] = "(".join( " OR ", $aOr ).")";
      }
      addLogMessage( "Start", $this->name."->getSearchWhereClauses()" );
      return $aWhere;
    }
    
    /**
    * Examine $_GET to construct the ORDER BY clause of an SQL statement for a search
    * @return string A WHERE clause
    */
    function getSearchOrderClause(){
      addLogMessage( "Start", $this->name."->getSearchOrderClause()" );
      $order = "ORDER BY ";
      if( 
        !empty( $_GET["orderby"] ) && 
        array_key_exists( $_GET["orderby"], $this->aFields ) && 
        ( 
          $this->aFields[$_GET["orderby"]]->hascolumn || 
          sizeof( $this->aFields[$_GET["orderby"]]->lookup ) > 0 ||
          $this->aFields[$_GET["orderby"]]->type == "mem" ||
          $this->aFields[$_GET["orderby"]]->type == "chk" 
        ) 
      ){
        $field = $this->aFields[$_GET["orderby"]];
        if( !empty( $_GET["orderdir"] ) && ( $_GET["orderdir"] == "desc" || $_GET["orderdir"] == "asc" ) ){
          $db = Cache::getModel("DB");
          $orderdir = " ".$db->escape( $_GET["orderdir"] );
        }else{
          $orderdir = "";
        }
        if( $field->belongsto != "" ){
          $tablename = camelToUnderscore( $field->belongsto );
          $modelname = $field->belongsto;
          require_once( "models/".$tablename.".model.class.php" );
          $tablename = $this->tablename."_".preg_replace( "/_id$/", "", $field->columnname );
          $o = Cache::getModel( $modelname );
          if( array_key_exists( "idx", $o->aFields ) ){
            $order .= $tablename.".idx".$orderdir;
          }else{
            $a = preg_split( "/,/", $field->listby ); // OK
            $comma = "";
            foreach( $a as $f ){
              $order .= $comma.$this->tablename."_".preg_replace( "/_id$/", "", $field->columnname ).".".$f.$orderdir;
              $comma = ", ";
            }
          }
        }else{
          $order .= $field->columnname.$orderdir;
        }
      }else{
        if( array_key_exists( "idx", $this->aFields ) ){
          $order .= $this->tablename.".idx";
        }else{
          $aListBy = preg_split( "/,/", $this->listby ); // OK
          foreach( $aListBy as $k => $c ){
            $a[$k] = $this->tablename.".".$c." ".$this->orderdir;
          }
          $order .= join( ", ", $a );
        }
      }
      addLogMessage( "End", $this->name."->getSearchOrderClause()" );
      return $order;
    }
    
    /**
    * Use the current $_GET values to construct a search query and return the results
    * @param int $limit
    * @see getWithJoins
    */
    function getBySearch( $limit="" ){
      addLogMessage( "Start", $this->name."->getBySearch()" );
      $extrajoins = "";
      
      $aWhere = $this->getSearchWhereClauses();
      $aFields = $this->aResultsFields;
      
      foreach( $this->aFields as $field ){
        if( array_search( $field->columnname, $this->aSearchFields ) !== false ) $this->aFields[$field->columnname]->autojoin = true;
        if( array_search( $field->columnname, $this->aResultsFields ) !== false ) $this->aFields[$field->columnname]->autojoin = true;
        if( $field->columnname == "name" ) continue;
        if( $field->columnname == "active" ) continue;
        if( $field->issearchedon ){
          addLogMessage( $field->name." searched on" );
          if( sizeof( $field->lookup ) > 0 && isset( $field->lookup["search"] ) && isset( $field->lookup["search"]["join"] ) ){
            addLogMessage( "adding in join: ".$field->lookup["search"]["join"] );
            $extrajoins .= "\n ".$field->lookup["search"]["join"];
          }
        }
        if( sizeof( $field->aUsesFields ) > 0 ){
          foreach( $field->aUsesFields as $col ){
            $aFields[] = $col;
          }
        }
      }
      
      $aFields = array_unique( $aFields );
      
      $clause = "";
      if( sizeof( $aWhere ) > 0 ) $clause .= " WHERE ".join( " AND ", $aWhere );
    
      // Order by
      $order = " ".$clause." \n".$this->getSearchOrderClause();
      
      // Limit
      if( $limit != "" ) $order .= " LIMIT ".$limit;
      
      addLogMessage( "End", $this->name."->getBySearch()" );
      return $this->getWithJoins( $order, $extrajoins, 0, $aFields );
    }
    
    
    /**
    * Get a statistical summary of the search that was performed on the fields that are visible such as
    *  - For cash fields, get:
    *    - Total
    *    - Average
    *    - Deviation
    *  - For foreign keys and boolean fields get totals for each selected value
    *  - Percentage fields
    *    - Average
    *    - Deviation
    */
    function getSearchSummaryStats(){
      addLogMessage( "Start", $this->name."->getSearchSummaryStats()" );
      $aWhere = $this->getSearchWhereClauses();
      $aSummary = array();
      $extrajoins = "";
      
      if( sizeof( $this->aResultsFields ) == 0 ) $this->aResultsFields = array_keys( $this->aFields );
      
      // Add lookups to the search fields array
      foreach( $this->aFields as $f ){
        if( sizeof( $f->lookup ) > 0 ){
          $this->aResultsFields[] = $f->columnname;
        }
      }
      $this->aResultsFields = array_unique( $this->aResultsFields );
      
      // Turn off autojoin on member fields which aren't in the results
      $aNonResultFields = array_diff( array_keys( $this->aFields ), array_merge( $this->aResultsFields, $this->aSearchFields ) );
      foreach( $aNonResultFields as $f ){
        if( !isset( $this->aFields[$f] ) ) continue;
        if( $this->aFields[$f]->issearchedon ) continue;
        if( $this->aFields[$f]->type == "mem" ) $this->aFields[$f]->autojoin = false;
      }
      
      // Get all the joins needed for lookup fields
      foreach( $this->aResultsFields as $f ){
        $field = $this->aFields[$f];
        if( sizeof( $field->lookup ) > 0 ){
          if( $field->issearchedon ) $extrajoins .= "\n ".$field->lookup["search"]["join"];
          $extrajoins .= "\n ".$field->lookup["join"];
        }
      }
      
      // Get particulars for constructing stats summary SQL
      foreach( $this->aResultsFields as $f ){
        unset( $a );
        $field = $this->aFields[$f];
        // If complete query exists for this field, get it
        if( method_exists( $field, "getStatsCompleteQuery" ) ){
          $this->aFields[$f]->autojoin = true;
          $aJoins = $this->getJoins();
          $joins = $extrajoins;
          $name = $this->tablename.".".$field->columnname;
          $filter = "CONCAT( '".$field->columnname."/', ".$this->tablename.".".$field->columnname." )";
          foreach( $aJoins as $join ){
            if( $join["column"] == $field->columnname ){ 
              $name = "CONCAT( ".$this->tablename."_".$join["alias"].".".join( ", ' ', ".$this->tablename."_".$join["alias"].".", preg_split( "/,/", $field->listby ) ).")";
              $filter = "CONCAT( '".$field->columnname."/', ".$this->tablename."_".$join["alias"].".id )";
            }
            /*
            $id = isset($join["mem"]) ? "_id" : "";
            $joins .= "\nLEFT OUTER JOIN ".$join["table"]." ".$join["alias"]." ON ".$this->tablename.".".$join["column"].$id." = ".$join["alias"].".id";
            */
            // $joins .= "\nLEFT OUTER";
            if( !isset( $join["mem"] ) ){
              $joins .= "\nLEFT OUTER JOIN ".$join["table"]." ".$this->tablename."_".$join["alias"]." ON ".$this->tablename."_".$join["alias"].".id = ".$this->tablename.".".$join["column"]."\n";
            }else{
              /*
              if( $join["side"] == "left" ) $joins .= " JOIN ".$join["table"]." ".$join["alias"]." ON ".$join["alias"].".".$this->tablename."_id = ".$this->tablename.".id\n";
              else{ 
                $joins .= " JOIN ".$join["table"]." ".$join["alias"]." ON ".$join["column"].".".$join["table"]."_id = ".$join["alias"].".id\n";
              }
              */
            }
          }
          if( sizeof( $aWhere ) > 0 ) $where = join( " AND ", $aWhere );
          else $where = "";
          $a = $field->getStatsCompleteQuery( $this->tablename, $joins, $name, $where, $filter );
        }
        
        // Or get individual select statements
        else{
          $a = $field->getStatsSelectStatement();
          $grp = $field->getStatsGroupStatement();
        }
        if( $a ) $aSummary[$field->getTypeName()][$field->columnname] = $a;
        // if( $grp ) $aSummary[$field->getTypeName()]["_grp"] = $grp;
      }
      
      // Group stats by column type and column, then stat
      $aReturn = array();
      $db = new $this->dbclass;
      foreach( $aSummary as $type => $aColumns ){
        $a = array();
        
        // Collect together all selects into one query
        $complete = false;
        foreach( $aColumns as $column => $aSelects ){
          if( !is_array( $aSelects ) ){
            $complete = true;
            
            // This must be a complete query specifically for this column
            $db->query( $aSelects );
            $field = $this->aFields[$column];
            /*
            $aReturn[$type][$field->displayname]["sql"] = $aSelects;
            $aReturn[$type][$field->displayname]["error"] = $db->error;
            */
            if( $db->numrows > 0 ){
              while( $row = $db->fetchRow() ){
                $row["name"] = convert_smart_quotes( $row["name"] );
                $stat = new StdClass();
                $stat->figure = $row["figure"];
                if( isset( $row["filter"] ) ) $stat->filter = h($row["filter"]);
                // $aReturn[$type][$field->displayname][$row["name"]] = $row["figure"];
                $aReturn[$type][$field->displayname][$row["name"]] = $stat;
              }
            }
            continue;
          }
          $a = array_merge( $a, $aSelects );
        }
        
        // Piece together the columns into one query for this type
        if( !$complete ){
          $aJoins = $this->getJoins();
          $j = '';
          if( sizeof( $aJoins ) > 0 ){ 
            foreach( $aJoins as $join ){
              $j .= " ";
              // $j .= $this->aFields[$join["column"]]->required ? "INNER" : "LEFT OUTER";
              // $j .= "LEFT OUTER";
              if( !isset( $join["mem"] ) ){
                $j .= "LEFT OUTER JOIN ".$join["table"]." ".$this->tablename."_".$join["alias"]." ON ".$this->tablename."_".$join["alias"].".id = ".$this->tablename.".".$join["column"]."\n";
              }else{
                /*
                if( $join["side"] == "left" ) $j .= " JOIN ".$join["table"]." ".$join["alias"]." ON ".$join["alias"].".".$this->tablename."_id = ".$this->tablename.".id\n";
                else $j .= " JOIN ".$join["table"]." ".$join["alias"]." ON ".$join["column"].".".$join["table"]."_id = ".$join["alias"].".id\n";
                */
              }
            }
          }
          // echo $j."\n\n";
          $sql = "SELECT ".join( ",\n  ", $a )." FROM ".$this->tablename.$j.$extrajoins;
          if( sizeof( $aWhere ) > 0 ) $sql .= " WHERE ".join( " AND ", $aWhere );
          if( isset( $aColumns["_grp"] ) ) $sql .= " ".$aColumns["_grp"];
          // echo $sql;
          $db->query( $sql );

          // $aReturn[$type]["debug"]["sql"] = $sql;
          // $aReturn[$type]["debug"]["error"] = $db->error;
          if( $db->numrows > 0 ){
            $row = $db->fetchRow();
            foreach( $row as $col => $value ){
              $a = preg_split( "/_/", $col ); // OK
              $stat = array_pop( $a );
              $field = $this->aFields[join( "_", $a )];
              if( $value === null ) $value = "null";
              $aReturn[$type][$field->displayname][ucfirst( $stat )] = $value;
            }
          }
        }
      }
      addLogMessage( "End", $this->name."->getSearchSummaryStats()" );
      // print_r( $aReturn );
      return $aReturn;
    }
    
    /**
    * Query based on row ID
    * @param int $id 
    * @return object the DB object that ran the query
    */
    function getById( $id ){
      return $this->getWithJoins( " \nWHERE ".$this->tablename.".id = $id" );
    }
    
    /**
    * Run arbitrary SQL. 
    * This method seems pointless now, but it was useful once
    * @param string $sql 
    * @param object DB object that ran the SQL
    */
    function getBySQL( $sql ){
      addLogMessage( "Start", $this->name."->getBySQL( ".$sql." );" );
      $db = new $this->dbclass;
      $db->query( $sql );
      addLogMessage( "End", $this->name."->getBySQL( ".$sql." );" );
      return $db;
    }
    
    
  
    /**
    * Get list of objects that depend on this one for their own cached table data to be up to date
    * @return array 
    */
    function getCacheDependants(){
      // For each object available
      $aReturn = array();
      $sql = "SELECT name FROM model";
      $db = new DB();
      $db->query( $sql );
      while( $row = $db->fetchRow() ){
        $modelname = underscoreToCamel( $row["name"] );
        $o = Cache::getModel( $modelname );
        $r = $o->getCacheReliance();
        if( array_search( $this->name, $r ) !== false ){
          $aReturn[] = $modelname;
          continue;
        }
      }
      return $aReturn;
    }
    
    /**
    * Construct dependency tree of all objects whose data would need to be recached if this one significantly changed
    * @return object
    */
    function getCacheDependencyTree($depth=0){
      $Tree = new StdClass();
      $Tree->name = get_clasS($this);
      $Tree->depth = $depth;
      $Tree->dependants = array();
      
      // Only allow to descent 10 levels deep
      if( $Tree->depth >= 10 ) return $Tree;
      
      $aDeps = $this->getCacheDependants();
      foreach( $aDeps as $name ){
        $o = Cache::getModel( $name );
        $Tree->dependants[] = $o->getCacheDependencyTree($depth+1);
      }
      return $Tree;
    }
    
    /**
    * Recache all objects that rely on this one for their table data to be up to date
    */
    function recacheDependants($descend=true){
      $aDependants = $this->getCacheDependants();
      foreach( $aDependants as $name ){
        echo "Caching $name... ";
        $o = Cache::getModel( $name );
        $o->recache();
        echo "done\n";
        if( $descend ) $o->recacheDependants($descend);
      }
    }
    
    /**
    * Recache method to be overridden
    */
    function recache($aArgs=array()){
      echo "No recache method defined for ".$this->name."\n";
      return false;
    }
    
    /**
    * Recache method arguments help text, to be overridden
    */
    function getRecacheHelpText(){
      return "No help text available for ".$this->name."->recache()\n";
    }
    
    /**
    * Get a list of objects that this object depends on for its own cached table data to be up to date
    * custom function overridden in each model which has cached data
    * @return array
    */
    function getCacheReliance(){
      return array();
    }
    
    
    /**
    * Render a search result row as a list item containing all fields in ->aSearchFields, formatted depending on their search type.
    * Whatever the object is listed by will become the H3 header for this list item.
    * @param array $row Assoc. array of DB row data
    * @return string HTML of a single list item
    */
    function renderRow( $row ){
      $html = "";
      $html .= "        <li class=\"".intval($row["id"])."\">\n";
      $this->issearchrow = true;
      $aFields = sizeof( $this->aResultsFields ) > 0 ? $this->aResultsFields : array_keys( $row );
      foreach( $this->aFields as $key => $field ){
        if( $field->hascolumn ) $this->aFields[$key]->setFromDb( $row[$key] );
      }
      if( strstr( $this->access, "r" ) !== false ) $name = "<a href=\"".SITE_ROOT.$this->tablename."/edit/".intval($row["id"])."\">".htmlentities( $this->getName() )."</a>";
      else $name = htmlentities( $this->getName() );
      $html .= "          <h3>".$name."</h3>\n";
      foreach( $aFields as $key ){
        if( empty( $this->aFields[$key] ) ) continue;
        if( $this->isListedBy( $key ) ) continue;
        // Don't display the row ID
        if( preg_match( "/^id/", $key ) ) continue;
        if( !$this->aFields[$key]->display ) continue;
        $html .= "          <h4 class=\"".$this->aFields[$key]->type." ".$this->aFields[$key]->columnname." field\">".$this->aFields[$key]->displayname."</h4>\n";
        $html .= "          <div class=\"".$this->aFields[$key]->type." ".$this->aFields[$key]->columnname." field\">".$this->aFields[$key]->toHtml()."</div>\n";
      }
      $html .= "          <hr/>\n";
      if( strstr( $this->access, "u" ) ) $html .= "          <p class=\"edit\"><a class=\"edit\" href=\"".SITE_ROOT.$this->tablename."/edit/".intval($row["id"])."\">edit</a></p>\n";
      if( strstr( $this->access, "d" ) ) $html .= "          <p class=\"delete\"><a class=\"delete\" href=\"".SITE_ROOT.$this->tablename."/delete/".intval($row["id"])."\">delete</a></p>\n";
      $html .= "          <hr/>\n";
      $html .= "        </li>\n";
      return $html;
    }
    
    
    /**
    * Get duplication URL 
    */
    function getDuplicateLink(){
      $url = SITE_BASE.$this->tablename."/new";
      foreach( $this->aFields as $k => $f ){
        $url .= "/$k/".urlencode(urlencode($f->value));
      }
      return $url;
    }
    
    /**
    * Gets options for the search results
    */
    function getSearchOptionsList(){
      $args = constructSearchArgs();
      $allowsearchsummary = $this->allowsearchsummary ? " allowsearchsummary" : "";
      $options = "    <ul class=\"searchoptions".$allowsearchsummary."\">\n";
      if( strstr( $this->access, "c" ) !== false ) $options .= "      <li><a class=\"new\" href=\"".SITE_ROOT.$this->tablename."/new\">Add new ".$this->displayname."</a></li>\n";
      if( $args != "" || $this->allowfullexcelexport ) $options .= "      <li><a class=\"export\" href=\"".SITE_ROOT.$this->tablename."/_export".$args."\">Export this search for Excel</a></li>\n";
      if( $args != "" || $this->allowfullexcelexport ){ 
        $url = SITE_ROOT."user_report/new";
        $name = "/name/".urlencode( urlencode( $this->displayname." Excel export" ));
        $url .= "/subscription_type/periodic";
        $f = "/format/xls";
        $url .= "/url/".urlencode( urlencode( $this->tablename.$args ));
        $options .= "      <li><a class=\"subscribe\" href=\"".$url.$f.$name."\">Subscribe to emails of this data in Excel</a></li>\n";
        
        $name = "/name/".urlencode( urlencode( $this->displayname." HTML export" ));
        $f .= "/format/html";
        $options .= "      <li><a class=\"html_subscribe\" href=\"".$url.$f.$name."\">Subscribe to HTML emails of this data</a></li>\n";

        $url = SITE_ROOT."user_report/new/name/".urlencode( $this->displayname." search" );
        $url .= "/subscription_type/bookmark";
        $url .= "/url/".urlencode( urlencode( $this->tablename.$args ));
        $options .= "      <li><a class=\"bookmark\" href=\"".$url."\">Add this to \"My Areas\"</a></li>\n";
      }
      // $options .= "      <li><a href=\"".SITE_ROOT.$this->returnpage.$args."/#content\" class=\"jump top\">Jump to the top</a></li>\n";
      foreach( $this->aSearchListOptions as $link ){
        $link = str_replace( ":args:", $args, $link );
        $options .= "      <li>".$link."</li>\n";
      }
      $options .= "   </ul>\n";
      $options = "<div class=\"optionscontainer\"><p>Options</p>$options</div>\n";
      return $options;
    }
    /**
    * Render an object's search form, controls and paged results.
    * ->liststyle is used to determine whether this is rendered as an unordered list or a table.
    * @param string $qry Optional SQL query to use to produce results list
    * @param bool $include_search Whether to include the search form in the returned HTML
    * @return string Returned HTML
    * @see renderTableList
    */
    function renderList( $qry="", $include_search = true ){
      addLogMessage( "Start render list", $this->name."->renderList()" );
      $html = "";
      $this->setupUserFields();
      $aColumns = $this->aResultsFields;
      if( $include_search ){
        $html  .= "    <h3>Search</h3>\n";
        $html .= $this->renderSearchForm( $this->aSearchFields );
        if( $this->liststyle != "table" ){
          $options = $this->getSearchOptionsList();
        }else $options = "";
        $html .= $options;
      }else{
        $options = "";
      }
      /*
      if( $qry == "" ) $a = $this->getBySearch();
      else $a = $this->getBySQL( $qry );
      */
      if( $this->liststyle == "table" ){ 
        addLogMessage( "Start render list", $this->name."->renderList()" );
        $rtn = $html.$this->renderTableList( $qry, $aColumns );
        addLogMessage( "End", $this->name."->renderList()" );
        return $rtn;
      }
      if( $qry == "" ) $dbr = $this->getBySearch();
      else $dbr = $this->getBySQL( $qry );
      if( $dbr->numrows > 0 ){
        $paging = renderPaging( plural( $this->displayname ), $dbr->numrows );
        $html .= "      <h4 id=\"results\">Search results</h4>\n";
        $html .= $paging;
        $html .= "      <ul class=\"".$this->tablename." list\">\n";
        $count = 0;
        $startrow = getStartrow($dbr->numrows) - 1;
        // $a = array_slice( $a, $startrow );
        $dbr->dataSeek( $startrow );
        while( $item = $dbr->fetchRow() ){
          $html .= $this->renderRow( $item );
          $count++;
          if( $count == SITE_PAGING ) break;
        }
        $html .= "      </ul>\n";
        $html .= $paging;
        $html .= $options;
      }else{
        $html .= $options;
        $html .= "      <p>No items found</p>\n";
      }
      addLogMessage( "End", $this->name."->renderList()" );
      return $html;
    }


    /**
    * Render an object's controls, paging and results as an HTML table
    * @param string $qry Optional query to run instead of basing the results on search args
    * @param array $aColumns List of columns to render
    * @return string HTML rendered table
    * @see renderList
    */
    function renderTableList( $qry="", $aColumns = array() ){
      $html = "";
      addLogMessage( "Start", $this->name."->renderTableList()" );
      $args = constructSearchArgs();
      /*
      // $aSummary = $this->getSearchSummaryStats();
      $aSummary = array();
      if( sizeof( $aSummary ) > 0 ){
        $html .= "      <h4>Statistical Summary</h4>\n";
        $html .= "      <div id=\"summary\">\n";
        foreach( $aSummary as $type => $field ){
          $html .= "        <h5>$type</h5>\n";
          $html .= "        <ul class=\"type\">\n";
          foreach( $field as $name => $stats ){
            $html .= "          <li>\n";
            $html .= "            <h6>$name</h6>\n";
            $html .= "            <dl class=\"stats\">\n";
            foreach( $stats as $stat => $value ){
              $html .= "              <dt>$stat</dt>\n";
              $html .= "              <dd>$value</dd>\n";
            }
            $html .= "            </dl>\n";
            $html .= "          </li>\n";
          }
          $html .= "        </ul>\n";
        }
        $html .= "      </div>\n";
      }
      */
      $html .= "      <h4 id=\"results\">Search results</h4>\n";
      $startrow = getStartrow(0) - 1;
      if( $qry == "" ) $dbr = $this->getBySearch($startrow.",".SITE_PAGING);
      else $dbr = $this->getBySQL( $qry );
      $options = $this->getSearchOptionsList();
      if( $dbr->numrows > 0 ){
        $paging = renderPaging( plural( $this->displayname ), $dbr->foundrows );
        $html .= $paging;
        $html .= $options;
        // echo print_r( array_keys( $a[0] ) );
        $html .= "      <table class=\"list ".$this->tablename."\" cellspacing=\"0\">\n";
        $html .= "        <tr>\n";
        if( strstr( $this->access, "u" ) !== false ) $html .= "          <th class=\"controls collapsed\"></th>\n";
        $count = 0;
        if(count($aColumns)==0) $aColumns = $this->aResultsFields;
        
        foreach( $aColumns as $i => $key ){
          // Switch out field for cached text field equiv
          $field = $this->aFields[$key];
          if( !empty( $field->textfield ) && isset( $this->aFields[$field->textfield] ) ){
            $this->aFields[$key]->display = false;
            $this->aFields[$field->textfield]->display = true;
            $aColumns[$i] = $field->textfield;
          }
        }
        
        // Each column
        foreach( $aColumns as $key ){
          $field = $this->aFields[$key];
          
          // Drop precision for just this list
          $this->aFields[$key]->sigfigures = 0;
          
          if( sizeof( $aColumns ) == 0 || array_search( $field->columnname, $aColumns ) !== false ){ 
            $link = SITE_ROOT.$this->returnpage;
            foreach( $_GET as $k => $g ){
              if( $k == "model" ) continue;
              if( $k == "orderby" ) continue;
              if( $k == "orderdir" ) continue;
              if( $k == "startrow" ) continue;
              $link .= "/".htmlentities( $k )."/".htmlentities( $g );
            }
            $link .= "/orderby/";
            $col = "";
            /*
            if( sizeof( $field->autocolumn ) > 0 ){
              $comma = "";
              foreach( $field->autocolumn as $c ){
                $col .= $comma.$this->tablename."_".$c;
                $comma = ",";
              }
            }else{
              $col .= $field->columnname;
            }
            */
            $col .= $field->columnname;
            $link .= $col;
            $dir = "";
            if( !empty( $_GET["orderby"] ) && $_GET["orderby"] == $col ){
              $link .= "/orderdir/";
              $dir = empty( $_GET["orderdir"] ) ? $field->defaultorderdir : $_GET["orderdir"];
              $link .= !empty( $_GET["orderdir"] ) && $_GET["orderdir"] == "desc" ? "asc" : "desc";
            }else{
              $link .= "/orderdir/".$field->defaultorderdir;
            }
            $class = "";
            $class .= $field->columnname." sort ".$dir." ";
            if( $count == 0 ){
              $class .= "first ";
            }
            if( $count == sizeof( $aColumns ) -1 ){
              $class .= "last ";
            }
            if( $field->display ){
              if( $field->hascolumn || $field->type == "mem" ) $name = "<a href=\"".$link."#results\" title=\"Order by ".$field->displayname."\">".$field->displayname."</a>";
              else $name = $field->displayname;
              $html .= "          <th class=\"".$class."\">".$name."</th>\n";
            }
            $count++;
          }
        }
        $html .= "        </tr>\n";
        $count = 0;
        while( $item = $dbr->fetchRow() ){
          $html .= $this->renderTableRow( $item, $aColumns );
          $count++;
          if( $count == SITE_PAGING ) break;
        }
        $html .= "      </table>\n";
        $html .= $paging;
      }else{
        $html .= $options;
        $html .= "      <p>No items found</p>\n";
      }
      addLogMessage( "End", $this->name."->renderTableList()" );
      return $html;
    }
    
    
    /**
    * Render one row of a table for search results
    * @param array $row Assoc. array of a DB results row
    * @param array $aColumns a list of columns to render
    * @return string HTML table row
    * @see renderRow
    */
    function renderTableRow( $row, $aColumns=array() ){
      $html = "";
      $html .= "        <tr class=\"".intval( $row["id"] )."\">\n";
      if( strstr( $this->access, "u" ) !== false ){ 
        $html .= "          <td class=\"controls collapsed\" id=\"".$this->tablename."_".intval($row["id"])."_controls\">";
        $html .= "</td>\n";
      }
      $this->id = $row["id"];
      $this->issearchrow = true;
      
      $colcount=0;
      foreach( $this->aFields as $key => $field ){
        if( !array_key_exists( $key, $row ) ){ 
          continue;
        }
        $this->aFields[$key]->setFromDb( $row[$key] );
        if( $this->aFields[$key]->type == "mem" || $this->aFields[$key]->type == "chk" ) $this->aFields[$key]->pretendtype = "";
        
      }
      
      $this->doCalculations();
      foreach( $aColumns as $key ){
      
        // Don't display the row ID
        if( preg_match( "/^id/", $key ) ) continue;
        if( !$this->aFields[$key]->display ) continue;
        
        if( sizeof( $aColumns ) == 0 || array_search( $key, $aColumns ) !== false ) {
          $class = $this->aFields[$key]->type." ".$this->aFields[$key]->columnname." ";
          if( $colcount == 0 ){
            $class .= "first ";
          }
          if( $colcount == sizeof( $aColumns ) -1 ){
            $class .= "last ";
          }
          if( $this->aFields[$key]->preservewhitespace ){
            $class .= "preservewhitespace ";
          }
          $html .= "          <td class=\"".h($class)."\">";
          $canview = $colcount == 0 && strstr( $this->getAuth(), "r" ) !== false;
          if( $canview ) $html .= "<a href=\"".SITE_ROOT.$this->returnpage."/edit/".intval($row["id"])."\">";
          $aData = array();
          if( sizeof( $this->aFields[$key]->autocolumn ) > 0 ){
            foreach( $this->aFields[$key]->autocolumn as $c ){
              $aData[$this->tablename."_".$c] = isset( $row[$this->tablename."_".$c] ) ? $row[$this->tablename."_".$c] : "";
              // $aData[$this->tablename."_".$c] = $data;
            }
            
            // Format this data in the format of the field it is retrieved from
            $aData = $this->aFields[$key]->formatAutocolumnData( $this->tablename, $aData );
          }
          
          if( 
            array_key_exists( $key, $this->aFields ) && 
            sizeof( $this->aFields[$key]->lookup ) > 0 && 
            array_key_exists( $key, $row ) 
          ){
            $str = h($row[$key]);
          }else{
            $str = $this->aFields[$key]->toResultString($aData);
          }
          $html .= trim( $str ) == "" ? "..." : $str;
          if( $canview ) $html .= "</a>";
          $html .= "</td>\n";
          $colcount++;
        }
      }
      /*
      if( strstr( $this->access, "u" ) ) $html .= "<a class=\"edit\" href=\"".SITE_ROOT.$this->tablename."/edit/".$row["id"]."\">edit</a>";
      if( strstr( $this->access, "d" ) ) $html .= "<a class=\"delete\" href=\"".SITE_ROOT.$this->tablename."/delete/".$row["id"]."\">delete</a>";
      */
      // $html .= "</td>\n";
      $html .= "        </tr>\n";
      return $html;
    }
    
    /**
    * Write results as HTML to a file in the temp folder. Remember to delete it afterwards
    * @return $string filename
    */
    function writeSearchResultsToTempHtmlFile(){
      $tmpfile = tempnam( SITE_TEMPDIR, "export" );
      $this->writeSearchResultsToHtmlFile($tmpfile);
      return $tmpfile;
    }
    
    /**
    * Avoid memory issues by writing search results to an HTML file
    */
    function writeSearchResultsToHtmlFile( $file ){
      // $this->debug = true;
      $includeforeignkeys = false; // Adds the FK values onto the right hand side of the results table
    
      // Get the results fields
      if( sizeof( $this->aResultsFields ) == 0 ) $this->aResultsFields = array_keys( $this->aFields );
      if( sizeof( $this->aSearchFields ) == 0 ) $this->aSearchFields = array_keys( $this->aFields );
      if( $this->allowfieldselect ) $this->setupUserFields();
      
      // Turn on autojoin, set URL args
      foreach( $this->aSearchFields as $key ){
        if( !isset( $this->aFields[$key] ) ) continue;
        $field = $this->aFields[$key];
        $this->aFields[$key]->autojoin = true;
      }
      foreach( $this->aFields as $key => $field ){
        $v = isset( $_GET[$key] ) ? $_GET[$key] : "";
        if( $field->setIsSearchedOn() ) $this->aFields[$key]->set( urldecode( urldecode( $v ) ), true );
        else $this->aFields[$key]->set( $this->aFields[$key]->default );
      }
     
      $this->initCustomResults();
      $dbr = $this->getBySearch();
      if( $this->debug ) echo $dbr->getSummary()."\n\n";
      
      $aFKHeaders = array();
      $tbl = new Table();
      
      // Header row
      $tbl->addHeaderName( "ID" );
      foreach( $this->aResultsFields as $key ){
        if( !isset( $this->aFields[$key] ) ) continue;
        $field = $this->aFields[$key];
        if( !$field->display ) continue;
        if( $field->type != "grd" ){
          $tbl->addHeaderName( $field->displayname );
        }
        if( $field->belongsto != "" ) $aFKHeaders[] = $field->displayname." ID";
      }
      $tbl = $this->addCustomResultsTableHeaders( $tbl );
      
      // Put FKs at the end
      if( $includeforeignkeys ) $tbl->addHeaderNames( $aFKHeaders );
      
      $this->issearchrow = true;
      
      file_put_contents( $file, "<table><thead>".$tbl->headers->getHtml()."</thead><tbody>\n", FILE_APPEND );

      // Row data
      while( $row = $dbr->fetchRow() ){
      
        $aFKVals = array();
        
        // Assign DB row data
        foreach( $this->aFields as $key => $field ){
          if( !empty( $field->textfield ) ){
            if( !array_key_exists( $field->textfield, $row ) ) continue;
            if( !array_key_exists( $field->textfield, $this->aFields ) ) continue;
            $this->aFields[$field->textfield]->value = $row[$field->textfield];
          }elseif( !array_key_exists( $key, $row ) ){ 
            continue;
          }else{
            $this->aFields[$key]->value = $row[$key];
          }
          if( $this->aFields[$key]->type == "mem" ) $this->aFields[$key]->pretendtype = "";
        }
        $this->id = intval( $row["id"] );
        $this->doCalculations();
        
        $tr = new TableRow( $this->id );
        
        // Row ID
        $tr->addCell( new TableCell( "<a href=\"".SITE_BASE.$this->tablename."/edit/".intval($this->id)."\">".$this->id."</a>", "id" ) );
        
        // Go through each field required
        foreach( $this->aResultsFields as $key ){
          if( !isset( $this->aFields[$key] ) ) continue;
          $field = $this->aFields[$key];
          if( !$field->display && !$tbl->hasHeader( $key ) ){ 
            continue;
          }
          
          $aData = array();

          if( sizeof( $this->aFields[$key]->autocolumn ) > 0 ){
            foreach( $this->aFields[$key]->autocolumn as $c ){
              if( isset( $row[$this->tablename."_".$c] ) ) $aData[$this->tablename."_".$c] = $row[$this->tablename."_".$c];
            }
          }
          
          // print_r( array_keys( $row ) );
          
          if( array_key_exists( $key, $this->aFields ) && sizeof( $this->aFields[$key]->lookup ) > 0 && array_key_exists( $key, $row ) ){
            $tr->addCell( new TableCell( $row[$key], $key ) );
          }elseif( $field->type == "grd" && array_key_exists( $key, $row ) ){
            $a = preg_split( "/; /", $field->value ); // OK
            $aY = array();
            if( sizeof( $a ) > 0 ){
              foreach( $a as $v ){
                $b = preg_split( "/: /", $v ); // OK
                if( sizeof( $b ) == 2 ) $aY[$b[0]] = $b[1];
                if( sizeof( $b ) == 3 ) $aY[$b[1]] = $b[2];
              }
            }
            $aRows = $this->getCustomGridFieldColumns( $field );
            foreach( $aRows as $year ){
              if( isset( $aY[$year["name"]] ) ){
                $tr->addCell( new TableCell( number_format( $aY[$year["name"]]/100 ), h($year["name"]) ) );
              }else{
                $tr->addCell( new TableCell( 0, h($year["name"]) ) );
              }
            }
          /*
          }elseif( $field->type == "mem" && array_key_exists( $key, $row ) ){
            $str .= $row[$key];
          */
          }else{
            if( !empty( $field->textfield ) && !empty( $this->aFields[$field->textfield] ) ){
              $tmp = $this->aFields[$field->textfield]->toString();
            }else{
              $tmp = trim( $field->toString( $aData ) );
            }
            $tmp = preg_replace( "/[\n\r\t]/", "", $tmp );
            $tmp = eregi_replace( '"', "'", $tmp );
            $tr->addCell( new TableCell( $tmp, $key ) );
          }
          
          // Foreign key IDs
          if( $field->belongsto != "" ){
            $aFKVals[$field->displayname] = $field->value;
          }
        }
        $tr = $this->addCustomResultsTableCells( $tr, $row );
        if( $includeforeignkeys ){
          foreach( $aFKVals as $k => $v ){
            $tr->addCell( new TableCell( $v, $k ) );
          }
        }
        file_put_contents( $file, $tr->getHtml(), FILE_APPEND );
        // $tbl->addRow( $tr );
      }   
      file_put_contents( $file, "</tbody></table>", FILE_APPEND );
      // return $tbl;
    }
    /**
    * Special things to do to results table before running the query
    */
    function initCustomResults(){
      return true;
    }
    /**
    * Model-specific override for adding in custom table headers to a search results export table
    */
    function addCustomResultsTableHeaders( $tbl ){
      return $tbl;
    }
    /**
    * Model-specific override for adding in custom cells to a search results export table
    */
    function addCustomResultsTableCells( $tr, $row, $aOpt = array() ){
      return $tr;
    }
    /**
    * Provided for model to override an add grid results
    */
    function getCustomGridFieldColumns( $f ){
      return array();
    }
    /**
    * Render the search form for this object, taking into account user-picked search fields
    * @param array $aFields A list of fields to include in the form
    * @return string HTML search form
    */
    function renderSearchForm( $aFields=array() ){
      $html = "";
      addLogMessage( "Start", $this->name."->renderSearchForm()" );
      $this->doInits();
      if( sizeof( $this->aSearchFields ) == 0 ) $this->aSearchFields = array_keys( $this->aFields );
      if( sizeof( $this->aResultsFields ) == 0 ) $this->aResultsFields = array_keys( $this->aFields );
      if( sizeof( $aFields  ) == 0 ){
        $aFields = $this->aSearchFields;
      }
      if( isset( $_GET["active"] ) ){
        if( isset( $this->aFields["active"] ) ) $this->aFields["active"]->set( $_GET["active"] );
      }else{
        if( isset( $this->aFields["active"] ) ) $this->aFields["active"]->value = $this->aFields["active"]->default;
      }
      if( !isset( $this->db ) ) $this->db = new $this->dbclass;
      // $db = new DB();
      foreach( $aFields as $key ){
        if( !array_key_exists( $key, $this->aFields ) ){ 
          continue;
        }
        $field = $this->aFields[$key];
        $this->aFields[$key]->editable = true;
        
        // I don't think this entire block has been run in quite some time and shouldn't be here
        if( ( $field->type == "lst" ) && $field->belongsto != "" && $field->autojoin && sizeof( $field->lookup ) == 0 ){
          
          // Just test if anyone does actually manage to run it 20/2/12
          mail( SITE_ADMINEMAIL, 
            "Obscure code accessed", 
            "Section of model.class.php, starting line 2251 ran. Called by ".$this->name.", field ".$field->name." at ".date(SITE_DATETIMEFORMAT) 
          );
          $tablename = camelToUnderscore( $field->belongsto );
          $o = Cache::getModel( $field->belongsto );
          $a = preg_split( "/,/", $o->listby ); // OK
          $sql = "select ".$tablename.".id, concat( ";
          foreach( $a as $f ){
            $sql .= $tablename.".".$f.", ' ', ";
          }
          $sql .= "'(',  count( ".$this->tablename.".id ), ' in total)' ) as name ";
          $sql .= "from ".$tablename." inner join ".$this->tablename." on ".$this->tablename.".".$key." = ".$tablename.".id ";
          $sql .= " group by ".$tablename.".id ORDER BY name";
          $dbr = $this->db->query( $sql );
          $aData = array();
          if( $dbr->numrows > 0 ){
            while( $row = $dbr->fetchRow() ){
              $aData[] = $row;
            }
          }
          // array_unshift( $aData, array( "id" => 0, "name" => "Not selected" ) );
          $this->aFields[$key]->setListItems( $aData );
        }
      }
      
      // All fields should be assigned values if they're in GET even if they're not selected by the user
      addLogMessage( "Assign all URL args", $this->name."->renderSearchForm()" );
      $this->setFieldsFromSearchArgs();
      $html .= "      <p><a href=\"#results\" class=\"jump results\">Jump to results</a></p>\n";
      $html .= $this->renderForm( "_search", "get", "Search", $aFields );
      addLogMessage( "End", $this->name."->renderSearchForm()" );
      return $html;
    }
    
    /**
    * Set up the search values from the search URL page
    */
    function setFieldsFromSearchArgs($search=true){
      foreach( $this->aFields as $key => $field ){
        if( $field->setIsSearchedOn() && $field->enabled ){ 
          $v = isset( $_GET[$field->columnname] ) && $_GET[$field->columnname] != "" ? $_GET[$field->columnname] : "";
          $this->aFields[$key]->set( urldecode( urldecode( $v ) ), $search );
        }
      }
    }
    
    /** 
    * Get a list of all rows of a certain object that are related to this object.
    * e.g. passing "User" returns all people linked to user foreign keys on this object
    * @param string $belongsto The class name of an object to list
    * @return array A list of related rows, keyed by their ID
    */
    public function getRelated($belongsto){
      $classname = camelToUnderscore($belongsto);
      require_once("models/".$classname.".model.class.php"); 
      $ret = array();
      foreach($this->aFields as $field){
        if ($field->belongsto == $belongsto && $field->value !=""){
          // echo $field->name.": ".$field->belongsto.": ".$field->value."<br>";
          $o = Cache::getModel( $classname );
          if( sizeof( $field->lookup ) > 0 ){
            $where = "WHERE ";
            $a = preg_split( "/,/", $o->listby ); // OK
            if( sizeof( $a ) > 1 ) $where .= "CONCAT( ".implode( ", ' ', ", $a )." )";
            else $where .= $a[0];
            $db = Cache::getModel("DB");
            $where .= " LIKE '".$db->escape($field->value)."'";
            $o->retrieveByClause( $where );
            $ret[$o->id]  =  $field->displayname.": ".$field->value;
          }else{
            $o->get($field->value);
            $name = "";
            foreach(explode(',',$o->listby) as $one){
              $name .= $o->getField($one)->value ." ";
            }
            $ret[$field->value]  =  $field->displayname.": $name";
          }
        }
      }
      
      // Allows the class to have an additional custom getRelated function to arbitrarily add user IDs.
      if( method_exists( $this, "getCustomRelated" ) ) $ret = $this->getCustomRelated( $belongsto, $ret );
      natsort($ret);
      $ret = array_reverse( $ret, true );   // Requirement to have the items in reverse alphabetical order
      return $ret;
    }

    
    /**
    * Render an email form for this object.
    * This will automatically pull in all people who are related to this object based on any fields linked to the user object
    * @return string HTML mail form
    */
    function renderMailForm( $rendercontrols=true ){
      $html = "";
      $class = "mail";
      $html .= "      <h2>Log a comment against this ".$this->displayname."</h2>\n";
      if( $rendercontrols ) $html .= "      <form id=\"frm".$this->name."\" class=\"".$class."\" action=\"".SITE_ROOT.$this->tablename."/_action\" method=\"post\">\n";
      $html .= "        <p>You can use this form to send a message to people involved with ".$this->displayname." \"".h($this->getName())."\".</p>\n";
      if( isset( $this->aFields[$this->tablename."_log"] ) ) $html .= "        <p>All comments logged here will be listed under the \"".$this->displayname." Log\" tab.</p>\n";
      if( $rendercontrols ) $html .= "        <p><a href=\"".SITE_ROOT.$this->tablename."/edit/".$this->id."\">Return to edit page</a></p>\n";
      
      // $to = Field::create("lstUserId","multiselect=1;displayname=Select recipients");
      // $to->listitems = $this->getRelated("User");
      $aTo = $this->getRelated("User");
      $cc = Field::create( "cnfCc","displayname=Cc me in" );
      $occ = Field::create( "emaOcc","displayname=Other People to CC" );
      $aExternal = $this->getEmailAddresses();
      /*
      $other_addresses = Field::create( "lstOtherAddresses", "multiselect=1" );
      $other_addresses->setListItems( $aExternal );
      */
      $subject = Field::create( "strSubject","required=1" );
      $subject->set( SITE_NAME.", Re: \"".$this->getName()."\"" );
      $body = Field::create( "txtBody", "required=1;rows=20" );
      
      // Get form values from previous attempt
      if( isset( $_SESSION["mailform"][$this->name] ) ){
        $aValues = unserialize( $_SESSION["mailform"][$this->name] );
        foreach( $aValues as $k => $v ){
          if( isset( $$k ) ) $$k->value = $v;
          else $$k = $v;
        }
      }
      if( $cc->value ) $cc->value = true;
      
      $html .= "<fieldset class=\"contacts\">";
      $html .= "<legend>Select which people to email (optional):</legend>\n";
      foreach( $aTo as $k => $v ){
        $cnf = Field::create( "cnfTo" );
        $cnf->htmlname = "cnfTo[]";
        $cnf->checkboxvalue = $k;
        $cnf->displayname = h($v);
        // if( sizeof( $to ) > 0 && in_array( $k, $to ) ) $cnf->value = true;
        $html .= $cnf->render();
      }
      foreach( $aExternal as $e ){
        $cnf = Field::create( "cnfOtherAddresses" );
        $cnf->htmlname = "cnfOtherAddresses[]";
        $cnf->checkboxvalue = $e["id"];
        $cnf->displayname = $e["name"];
        // if( sizeof( $other_addresses ) > 0 && in_array( $e["id"], $other_addresses ) ) $cnf->value = true;
        $html .= $cnf->render();
      }
      // $html .= $to->render();
      // if( sizeof( $aExternal ) > 0 ) $html .= $other_addresses->render();
      $html .= $cc->render();
      $html .= "</fieldset>\n";
      $html .= $occ->render();
      $html .= $subject->render();
      $html .= $body->render();
      /*
      $f = Field::create( "cnfLogit","displayname=Log this message" );
      $html .= $f->render();
      */
      if( $rendercontrols ) {
        $html .= "        <div class=\"meta\">\n";
        $html .= "        <input type=\"hidden\" value=\"".$this->id."\" name=\"id\" />\n";
        $html .= "        <input type=\"hidden\" value=\"".preg_replace( "/[^-a-z0-9]/", "", $this->action )."\" name=\"action\" />\n";
        $html .= "        <input type=\"hidden\" value=\"".$this->tablename."\" name=\"model\" />\n";
        $html .= "        <input type=\"hidden\" value=\"".preg_replace( "/[^-a-z0-9]/", "", SessionUser::getProperty("sessidhash"))."\" name=\"sessidhash\" />\n";
        $html .= "        <input type=\"hidden\" value=\"\" name=\"attachments\" class=\"attachments\" />\n";
        $html .= "        <input type=\"submit\" class=\"button\" value=\"Send\" name=\"btnSubmit\" />\n";
        $html .= "        </div>\n";
        $html .= "      </form>\n";
      }
      return $html;
    }
    
    /**
    * Get any email addresses listed in email fields
    */
    function getEmailAddresses(){
      $aRtn = array();
      foreach( $this->aFields as $field ){
        if( $field->type == "ema" && $field->toString() != "" ){
          // Split email addresses on , or ;
          $a = preg_split( "/[,;]/", $field->toString() );
          foreach( $a as $str ){
            $str = trim( $str );
            $aRtn[] = array( "id" => $str, "name" => $this->displayname." ".$field->displayname.": ".$str );
          }
        }
      }
      return $aRtn;
    }

    
    /**
    * Renders a form to select which fields should appear in a search form
    * @return string HTML field select form
    */
    function renderFieldSelectForm(){
      $html = "";
      $class = "field_select";
      $html .= "      <div>\n";
      $html .= "        <h3>Select fields to display/search on</h3>\n";
      $html .= "        <p>Fields selected here will be visible in the ".$this->displayname." search form and results table respectively</p>\n";
      $html .= "        <p class=\"help\">Click <label class=\"search\">Search</label> to include the field in the <strong>search</strong> form and "
        ."<label class=\"results\">Results</label> to include it in the <strong>results</strong> table</p>\n";
      $html .= "        <form id=\"frm".$this->name."FieldSelect\" class=\"".$class."\" action=\"".SITE_ROOT.$this->tablename."/_action\" method=\"POST\">\n";
      $html .= "          <input type=\"submit\" class=\"button first\" value=\"Set\" name=\"btnSubmit\" />\n";
      // $html .= "          <div class=\"colnames\"><span class=\"search\">Search on</span><span class=\"results\">View in results</span></div>\n";
      $html .= $this->renderFieldSelectFields();
      $html .= "          <input type=\"hidden\" value=\"field_select\" name=\"action\" />\n";
      $html .= "          <input type=\"hidden\" value=\"".$this->tablename."\" name=\"model\" />\n";
      $html .= "          <input type=\"hidden\" value=\"".urlencode(constructSearchArgs())."\" name=\"args\" />\n";
      $html .= "          <input type=\"hidden\" value=\"".htmlentities(SessionUser::getProperty("sessidhash"))."\" name=\"sessidhash\" />\n";
      $html .= "          <input type=\"submit\" class=\"button\" value=\"Set\" name=\"btnSubmit\" />\n";
      $html .= "          <p><a href=\"".SITE_ROOT.$this->tablename."/_field_select_reset\">Reset to defaults</a></p>\n";
      $html .= "        </form>\n";
      $html .= "      </div>\n";
      return $html;
    }
    
    
    /**
    * Render all the fields as field select fields
    * @return string HTML field select fields
    */
    function renderFieldSelectFields(){
      return $this->renderFields( array(), DISPLAY_FIELDSELECT );
    }
    
  
    /**
    * Render a wizard for this model
    */
    function renderWizard($step=1){
      $wiz = $this->getWizard();
      $wiz->setCurrentStep( $step );
      return $wiz->render();
    }
    
    /**
    * Get this model as a wizard
    */
    function getWizard(){
      $wiz = new Wizard();
      $wiz->initFromModel( $this );
      return $wiz;
    }
    
    
    /**
    * Render the form for this object
    * @param string $action name of the action script to go to
    * @param string $method form method
    * @param string $button Text to write on the submit button
    * @param array $aColumns List of columns to render
    * @return string HTML rendered form
    */
    function renderForm( $action="_action", $method="post", $button="Save", $aColumns=array() ){
      addLogMessage( "Start", $this->name."->renderForm()" );
      
      if( $button == "" ){
        switch( $this->action ){
          case "delete":
            $button = "Delete";
            break;
        }
      }
      
      $class = "";
      $class .= $this->tablename." ";
      if( $action == "_repeat" ) $class .= "rpt ";
      if( $button == "Search" ) $class .= "search ";
      $class .= $this->action;
      
      $aRepeaters = array();
      if( sizeof( $aColumns ) == 0 ) $aColumns = array_keys( $this->aFields );
      foreach( $aColumns as $key ){
        if( !array_key_exists( $key, $this->aFields ) ) continue;
        $field = $this->aFields[$key];
        if( !$field->formfriendly && $field->display ){ 
          $aRepeaters[] = $field;
        }
      }
      
      $controls = "";
      $controls .= "        <div class=\"controls\">\n";
      $controls .= "          <ul class=\"options\">\n";
      if( $this->action == "search" || strstr( $this->access, "u" ) !== false || strstr( $this->access, "c" ) !== false ){
        $controls .= "            <li><input type=\"submit\" class=\"button\" value=\"".h($button)."\" name=\"btnSubmit\" /></li>\n";
      }
      
      // Edit form options: delete, add, return, mail owner
      if( $this->action == "edit" && $this->name != "MemberInterface" && $action != "_repeat" ){ 
        if( strstr( $this->access, "d" ) !== false ) $controls .= "            <li><a href=\"".SITE_ROOT.$this->tablename."/delete/".$this->id."\" class=\"delete\">Delete</a></li>\n";
        if( strstr( $this->access, "c" ) !== false ) $controls .= "            <li><a class=\"new\" href=\"".SITE_ROOT.$this->tablename."/new\">Add another</a></li>\n";
        if( strstr( $this->access, "c" ) !== false && $this->allowduplicatelink ) $controls .= "            <li><a class=\"duplicate\" href=\"".$this->getDuplicateLink()."\">Duplicate this</a></li>\n";
        $backitem = Breadcrumb::getBackLinkItemFromCurrentPage();
        if( $backitem ){
          $controls .= "            <li><a class=\"back\" href=\"".$backitem["url"]."\">Back to ".$backitem["name"]."</a></li>\n";
        }
        if( $this->allowcontactform ) $controls .= "            <li><a class=\"mail\" href=\"".SITE_ROOT.$this->tablename."/mail/".$this->id."\">Log a comment with this ".$this->displayname."</a></li>\n";
        foreach( $this->aEditFormOptions as $option ){
          $controls .= "            <li>".$option."</li>\n";
        }
      }
      
      // Search form
      if( $action == "_search" ){
        $controls .= "            <li><a href=\"".SITE_ROOT.$this->returnpage."/\" class=\"reset\">Clear form</a></li>\n";
        $args = constructSearchArgs();
        if( $this->allowfieldselect ){ 
          $controls .= "            <li><a href=\"".SITE_ROOT.$this->tablename."/field_select";
          if( $args != "" ) $controls .= $args;
          $controls .= "\" class=\"field_select\">Choose fields</a></li>\n";
        }
      }
      
      $controls .= "          </ul>\n";
      $controls .= "        </div>\n";
      
      $html = "";
      if( sizeof( $aRepeaters ) > 0 ) $html .= "      <div id=\"fragments\">\n";
      $html .= "      <form id=\"frm".$this->name."\" class=\"".$class."\" action=\"".SITE_ROOT.$this->tablename."/".h($action)."\" method=\"".h($method)."\"";
      if( $this->formtype != "" ) $html .= " enctype=\"".h($this->formtype)."\"";
      $html .= ">\n";
      
      if( $this->action != "search" && $this->action != "delete" && $button != "Save and add another" && $button != "Log in" ){
        $html .= $controls;
      }
      if( $this->action == "search" && array_key_exists( "active", $this->aFields ) ){
        $this->aFields["active"]->display = true;
      }
      $modifiers = $action == "_search" ? DISPLAY_SEARCH : DISPLAY_FIELD;
      $html .= $this->renderFields( $aColumns, $modifiers );
      // Hide in results
      /*
      if( $this->action == "search" && array_key_exists( "active", $this->aFields ) ){
        $this->aFields["active"]->display = false;
      }
      */
      
      $html .= $controls;
      
      $html .= "        <div>\n";
      if( $action != "" ) $html .= "          <input type=\"hidden\" value=\"".$this->tablename."\" name=\"model\" />\n";
      if( $this->action != "search" ) $html .= "          <input type=\"hidden\" value=\"".htmlentities( $this->action )."\" name=\"action\" />\n";
      if( $this->action != "search" ) $html .= "          <input type=\"hidden\" value=\"".htmlentities( SessionUser::getProperty("sessidhash") )."\" name=\"sessidhash\" />\n";
      if( $this->id != 0 ){
        $html .= "          <input type=\"hidden\" value=\"".$this->id."\" name=\"id\" />\n";
      }
      if( !empty( $_GET["context"] ) || $action == "_repeat" ){
        $context = $action == "_repeat" ? $this->context : $_GET["context"];
        $html .= "          <input type=\"hidden\" value=\"".preg_replace( "/[^-_a-z0-9]/", "", $context )."\" name=\"context\" />\n";
        if( $action == "_repeat" && isset( $_GET["id"] ) ) $html .= "          <input type=\"hidden\" value=\"".preg_replace( "/[^-_a-z0-9]/", "", $_GET["id"] )."\" name=\"context_id\" />\n";
      }
      $html .= "        </div>\n";
      $html .= "      </form>\n";
      
      if( sizeof( $aRepeaters ) > 0 && $action != "_search" && $action != "_repeat" ){
        if( $this->id == 0 ){
          $html .= "      <div class=\"rpt\">\n";
          $html .= "        <p>Once this ".$this->displayname." has been saved, you can add ";
          $comma = "";
          $names = "";
          $first = "";
          $a = array();
          foreach( $aRepeaters as $field ){
            if( $field->type != "rpt" ) continue;
            $a[] = $comma.plural( $field->displayname );
          }
          $html .= arrayToSentence( $a );
          $html .= " to it";
          $html .= "</p>\n";
          $html .= "      </div>\n";
        }
        else{
          $fragment_idx = 1;
          foreach( $aRepeaters as $field ){
            $html .= "      <div id=\"fragment-".$fragment_idx."\" class=\"fragment\">\n".$field->render()."    </div>\n";
            $fragment_idx++;
          }
        }
      }
      if( sizeof( $aRepeaters ) > 0 ) $html .= "      </div>\n";
      addLogMessage( "End", $this->name."->renderForm()" );
      return $html;
    }

    
    /**
    * Render a list of fields using the specified DISPLAY_ modifier
    * @param array $aColumns list of columns to display
    * @param int $modifiers Type of field to display
    * @see toField
    * @return string HTML rendered fields
    */
    function renderFields( $aColumns = array(), $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start", $this->name."->renderFields()" );
      if( sizeof( $aColumns ) == 0 ){
        foreach( $this->aFields as $key => $f ){
          if( sizeof( $f->lookup ) == 0 || ( $modifiers & DISPLAY_FIELDSELECT ) ) $aColumns[] = $key;
        }
      }
      
      $html = "";
      $count = 0;
      
      foreach( $aColumns as $c ){
        
        if( !array_key_exists( $c, $this->aFields ) && !( $modifiers & DISPLAY_SEARCH ) ) continue;
        $field = $this->aFields[$c];
        
        if( !$field->formfriendly || ( $field->type == "htm" && ( $modifiers & DISPLAY_SEARCH ) ) ) continue;
        
        // Lookup fields
        
        if( empty( $field->display ) || !$field->display ) continue;
        if( ( $modifiers & DISPLAY_FIELDSELECT ) && $field->type == "grd" ) continue;
        
        
        // Field sets?
        
        // End
        if( ( $field->fieldset == "" || $field->fieldset != $this->currentfieldset ) && $this->currentfieldset != "" ){
          $html .= "        </fieldset>\n";
          $this->currentfieldset = "";
        }
        // Start
        if( $field->fieldset != "" ){
          if( $this->currentfieldset == "" ){
            $fieldset_class = strtolower( str_replace( "__", "_", preg_replace( "/[^a-z0-9]/i", "_", $field->fieldset ) ) );
            $html .= "        <fieldset class=\"".$fieldset_class."\">\n";
            $html .= "          <legend>".$field->fieldset."</legend>\n";
          }
        }
        $this->currentfieldset = $field->fieldset;
        $class = "";
        $class .= $count % 2 == 0 ? "even" : "odd";
        $class .= " ";
        
        // Modifiers
        if( $modifiers & DISPLAY_FIELDSELECT ){ 
          $mod = $modifiers;
          if( array_search( $field->columnname, $this->aSearchFields ) !== false ){
            $mod = $mod | DISPLAY_INCLUDE_SEARCH;
          }
          if( array_search( $field->columnname, $this->aResultsFields ) !== false ){
            $mod = $mod | DISPLAY_INCLUDE_RESULTS;
          }
        }else $mod = $modifiers;
        
        // Display field
        $html .= $field->render( $class, $mod );
        $count++;
      }
      if( $this->currentfieldset != "" ){
        $html .= "        </fieldset>\n";
      }
      addLogMessage( "End", $this->name."->renderFields()" );
      return $html;
    }

    
    /**
    * Render the object as static uneditable HTML
    * @return string HTML 
    */
    function render(){
      if( $this->id == 0 ) return false;
      $html = "";
      $html .= "      <div class=\"".$this->tablename." view\">\n";
      if( isset( $this->aFields["name"] ) && $this->aFields["name"]->display ) $html .= "        <h3>".h($this->aFields["name"]->value)."</h3>\n";
      foreach( $this->aFields as $key => $field ){
        if( !$field->display ) continue;
        if( $field->columnname == "name" ) continue;
        $html .= $field->toHtml();
      }
      $html .= "      </div>\n";
      return $html;
    }
    
    /**
    * Email the owner of this object
    * @param array $toArr Array of user IDs to send to
    * @param string $cc Email address to send CC to
    * @param string $occ Second CC email address
    * @param string $subject Subject line
    * @param bool $logit True to log in the objects _log table
    */
    function emailOwner( $toArr=array(), $cc, $occ, $body, $subject="",$logit=false, $other=array(), $attachments="" ){
      if( !file_exists( "../models/".$this->tablename."_log.model.class.php" ) ) $logit = false;
      if( $logit ) require_once( "models/".$this->tablename."_log.model.class.php" );
      
      $b = Field::create( "txtBody" );
      $s = Field::create( "strSubject" );
      $oc = Field::create( "emaOcc" );
      /*
      if( sizeof( $other ) > 0 ){
        $otheraddresses = Field::create("lstOtherAddresses");
        $otheraddresses->multiselect = true;
        $otheraddresses->set( $other );
      }
      */
      $b->set( trim($body) );
      $s->set( trim($subject) );
      $oc->set(trim($occ));
      $oc->regexp="^(([^,;]+ )?<?[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})>?[ ,;]*)+$";
      $body = $b->toString();
      $subject = $s->toString();
      
      // Attachments
      $aAttachmentIds = array();
      if( $attachments != "" ){
        $a = preg_split( "/,/", $attachments );
        foreach( $a as $id ){
          $id = intval( $id );
          if( $id == 0 ) continue; 
          
          // Check in DB that the sender is the owner of this attachment
          $sql = "SELECT * FROM attachment WHERE id = $id AND created_by_id = ".intval(SessionUser::getId());
          $db = Cache::getModel("DB");
          $db->query( $sql );
          if( $db->numrows > 0 ) $aAttachmentIds[] = $id;
        }
      }
      
      // Save this lot to session in case of errors
      $aFields = array(
        "body" => $b->value,
        "subject" => $s->value,
        "occ" => $oc->value,
        "to" => $toArr,
        "cc" => $cc,
        "other_addresses" => $other
      );
      if( !isset( $_SESSION["mailform"] ) ) $_SESSION["mailform"] = array();
      $_SESSION["mailform"][$this->name] = serialize( $aFields );
      
      // Email the owner of this object with some text
      if( $body == "" ){
        Flash::addError("Please enter some text to send.");
      }
      /*
      if( count($toArr)<1 ){
        Flash::addError("Please select recipients.");
      }
      */
      $occ = str_replace( ";", ",", $occ );
      if( strlen(trim($occ))>0 && is_array($oc->validate())){
        Flash::addError("Other Cc addresses are not valid. They should contain e-mails separated by a comma.");
      }
      else{
        if (trim(strlen($cc))>0) $cc .= ",";
        $cc .= trim($occ);
      }
      
      $to = "";
      
      // Check that people aren't trying to CC without a "to"
      if( sizeof( $toArr ) == 0 && $cc != "" ){
      
        // Convert CC list to "to" list
        $to = $cc;
        $cc = "";
      }
      
      if (!Flash::isOk()) return false;
      
      $details = "";
      if( sizeof( $this->aEmailFields ) > 0 ){
        $details .= "This message is concerning the ".$this->displayname." with the following details:\n";
        foreach( $this->aEmailFields as $f ){
          $field = $this->aFields[$f];
          $details .= $field->displayname.": ".$this->aFields[$f]->toString()."\n";
        }
      }
      $details .= "The edit page for this ".$this->displayname." is: ".SITE_BASE.$this->tablename."/edit/".$this->id;
      $body = $details."\n---\n\n".$body;

      require_once( "models/user.model.class.php" );
      foreach($toArr as $id) {
        $user = new User();
        $user->get($id);        
        if (trim($user->aFields["name"]->toString()) != ""){ 
          if( $user->aFields["has_left"]->value == 1 ){
            $msg = $user->getName()." has left, no email will be sent to them";
            Flash::addWarning($msg);
          }else{
            $to .= $user->aFields["first_name"]->toString()." ".$user->aFields["last_name"]->toString() ." <".$user->aFields["name"]->toString()."@".SITE_EMAILDOMAIN.">, ";
          }
        }
      }
      
      if( sizeof( $other ) > 0 ){
        foreach( $other as $email ){
          $to .= $email.", ";
        }
      }
      
      // Headers
      $headers = "From: ".SessionUser::getFullName()." <".SessionUser::getProperty("username")."@".SITE_EMAILDOMAIN.">\r\n";
      if (trim($cc) != "") $headers .= "Cc: $cc\r\n";
      
      // Log the email as if it was one email (it will be if there are no external addresses, externals will go in a separate email)
      $replyto = "";
      if ($logit) {
        $classname = $this->name."Log";
        $pl = Cache::getModel( $classname );
        // $pl = new $classname();
        $pl->getField("user_id")->set(SessionUser::getId());
        $recipients = $to;
        if( trim( $cc ) != "" ) $recipients .= "CC: ".$cc;
        $pl->getField("recipients")->set($recipients);
        $pl->getField($this->tablename."_id")->set($this->id);
        $pl->getField("name")->set($subject);
        $pl->getField("content")->set($body);
        if( sizeof( $aAttachmentIds ) > 0 && isset( $pl->aFields["has_attachments"] ) ) $pl->aFields["has_attachments"]->value = true;
        $pl->save();
        
        // Align attachments to this entry
        if( isset( $aAttachmentIds ) && sizeof( $aAttachmentIds ) > 0 ){
          $logid = $pl->id;
          $logname = get_class( $pl );
          $sql = "
            UPDATE attachment 
            SET model_name = '".$db->escape($logname)."', model_id = ".intval($logid)." 
            WHERE id IN (".join(",",$aAttachmentIds).")";
          $db->query( $sql );
        }
        
        // Can users add follow up logs directly by email?
        if( $pl->allowemailcreate ){
          $replyto = createReplyToAddress( $classname, $this->tablename."_id", $this->id, $pl->id )."\r\n";
        }
        Flash::addOk("The message has been logged in the ".$this->name." log tab");
      }
      
      // Find out if any of the recipients are external
      $aTo = preg_split( "/,/", $to );
      $aExternal = array();
      foreach( $aTo as $k => $str ){
        
        // Extract address portion
        if( !preg_match( "/([^ <>,]+@[-\.a-zA-Z0-9]+)/", $str, $m ) ) continue;
        
        // Test if address is external
        if( preg_match( "/@".SITE_EMAILDOMAIN."$/", $m[1] ) ) continue;
        
        $aExternal[] = $str;
        if( $k !== false ){
          unset( $aTo[$k] );
        }
      }
      $to = trim( join( ", ", $aTo ) );
      
      // Do same for CC
      $aCC = preg_split( "/,/", $cc );
      foreach( $aCC as $str ){
        
        // Extract address portion
        if( !preg_match( "/([^ <>,]+@[-\.a-zA-Z0-9]+)/", $str, $m ) ) continue;
        
        // Test if address is external
        if( preg_match( "/@".SITE_EMAILDOMAIN."$/", $m[1] ) ) continue;
        
        $aExternal[] = $to;
        $k = array_search( $to, $aTo );
        if( $k !== false ){
          unset( $aCC[$k] );
        }
      }
      $cc = trim( join( ", ", $aCC ) );
      $exto = trim( join( ", ", $aExternal ) );
            
      $mailer = new Mailer();
      $mailer->wrapBody( $body );  
      $mailer->setSubject( $subject );
      $mailer->setCurrentUserAsSender();
      if (trim($cc) != "") $mailer->AddCC( $cc );
      
      // Add attachments 
      if( $aAttachmentIds ){
        foreach( $aAttachmentIds as $id ){
          $attachment = Cache::getModel("Attachment");
          $attachment->get($id);
          if( $attachment->id == 0 ) continue;
          $mailer->AddStringAttachment( $attachment->Fields->Data->toString(), $attachment->Fields->Name->toString(), "base64", $attachment->Fields->MimeType->toString() );
        }
      }
      
      // External emails, no reply-to address
      if( $exto != "" ){
        foreach( $aExternal as $to ){
          $mailer->AddRecipient( $to );
        }
        if( $mailer->Send() ){
          Flash::addOk("Your message has been sent to external recipients.");
        }else{
          Flash::addWarning("There was a problem sending the emails. No emails were sent to external addresses.");
        }
        $mailer->ClearAddresses();
      }
      
      // Normal internal emails
      if( $to != "" ){
        foreach( $aTo as $to ){
          $mailer->AddRecipient( $to );
        }
        if( $replyto != "" ) $mail->AddReplyTo( $replyto );
        if( $mailer->Send() ){
          Flash::addOk("Your message has been sent to internal recipients.");
        }else{
          Flash::addWarning("There was a problem sending internal emails. No internal emails were sent.");
        }
        if( isset( $_SESSION["mailform"][$this->name] ) ) unset( $_SESSION["mailform"][$this->name] );
        return true;
      }else{
        if( isset( $_SESSION["mailform"][$this->name] ) ) unset( $_SESSION["mailform"][$this->name] );
        Flash::addOk("No internal emails were sent");
      }
      return false;
    }
    
    /**
    * Take a POST containing a series of checkboxes relating to fields and create the aSearchFields based on it
    * @return bool Currently always true
    */
    function setUserFields(){
      $this->aSearchFields = array();
      $this->aResultsFields = array();
      foreach( $this->aFields as $field ){
        if( isset( $_POST[$field->htmlname."_search"] ) || $field->columnname == "active" ){ 
          if( $field->formfriendly ) $this->aSearchFields[] = $field->columnname;
        }
        if( isset( $_POST[$field->htmlname."_results"] )  ){ 
          $this->aResultsFields[] = $field->columnname;
        }
      }
      return true;
    }
    
    
    /**
    * Save the field selection in ->aSearchFields to database
    * @return bool True if successful
    */
    function saveUserFields(){
      
      // Save whatever is in the aSearchFields array as the custom search fields for this user looking at this model
      if( $this->metaid == 0 ) $this->metaid = $this->getMetaId();
      if( !SessionUser::isLoggedIn() ) return false;
      if( sizeof( $this->aSearchFields ) == 0 ) return false;
      $sql = "";
      
      // Get the fields for this model
      $sql = "SELECT * FROM field WHERE model_id = ".intval( $this->metaid );
      $db = new $this->dbclass;
      $db->query( $sql );
      $aFields = array();
      $ids = "";
      $comma = "";
      while( $row = $db->fetchRow() ){
        $aFields[$row["name"]] = $row;
        $ids .= $comma.$row["id"];
        $comma = ",";
      }
      
      $aIns = array();
      $search = "";
      $results = "";
      foreach( $this->aSearchFields as $f ){
        if( isset( $aFields[$f] ) && isset( $this->aFields[$f] ) ){
          $search .= "<li>".$this->aFields[$f]->displayname."</li>\n";
        }
      }
      $results = "";
      foreach( $this->aResultsFields as $f ){
        if( isset( $aFields[$f] ) && isset( $this->aFields[$f] ) ){
          $results .= "<li>".$this->aFields[$f]->displayname."</li>\n";
        }
      }
      
      // Build insert statements
      foreach( $aFields as $k => $row ){
        $incsearch = array_search( $k, $this->aSearchFields ) === false ? 0 : 1;
        $incresults = array_search( $k, $this->aResultsFields ) === false ? 0 : 1;
        $aIns[] = " ( ".$row["id"].", ".intval( SessionUser::getId() ).", $incsearch, $incresults )";
      }
      
      // Delete existings model/user associations
      $sql = "DELETE FROM field_user WHERE field_id IN (".$ids.") AND user_id = ".intval( SessionUser::getId() );
      $db->query( $sql );
      
      /*
        New structure:
        ALTER TABLE `intranet`.`field_user` 
        ADD COLUMN `search` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `user_id`, 
        ADD COLUMN `results` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `search`;
      */
      
      // Set up new associations
      if( sizeof( $aIns ) > 0 ){
        $sql = "INSERT INTO field_user ( field_id, user_id, search, results ) VALUES ".join( ",", $aIns );
        $db->query( $sql );
        $msg = "<p>Your search fields for ".plural( $this->displayname )." have been set as:</p>\n<ul>\n".$search."</ul>\n";
        $msg .= "<p>Your results fields for ".plural( $this->displayname )." have been set as:</p>\n<ul>\n".$results."</ul>\n";
        Flash::addOk($msg);
        Flash::setHtmlAllowed();
      }
      return true;
    }
    
    
    /**
    * If the user has selected custom fields for this model, set them up
    * @return bool True if successful
    */
    function setupUserFields(){
      addLogMessage( "Start", $this->name."->setupUserFields()" );
      
      if( !SessionUser::isLoggedIn()) return false;
      if( $this->metaid == 0 ) $this->metaid = $this->getMetaId();
      if( !$this->db ) $this->db = new $this->dbclass;
      $sql = "
        SELECT f.name, fu.search, fu.results
        FROM field f 
        INNER JOIN field_user fu ON fu.field_id = f.id 
        WHERE f.model_id = ".intval( $this->metaid )." AND fu.user_id = ".intval(SessionUser::getId())."
        ORDER BY fu.id ASC";
      $this->db->query( $sql );
      $aSearch = array();
      $aResults = array();
      if( $this->db->numrows > 0 ){
        while( $row = $this->db->fetchRow() ){
          if( $row["search"] == 1 ) $aSearch[] = $row["name"];
          if( $row["results"] == 1 ) $aResults[] = $row["name"];
        }
      }
      
      // Get the fields in the right order
      if( sizeof( $aSearch ) > 0 ){   
        $this->aSearchFields = array();
        foreach( $this->aFields as $k => $f ){
          if( array_search( $k, $aSearch ) !== false ) $this->aSearchFields[] = $k;
        }
      }
      if( sizeof( $aResults ) > 0 ){ 
        $this->aResultsFields = array();
        foreach( $this->aFields as $k => $f ){
          if( array_search( $k, $aResults ) !== false ) $this->aResultsFields[] = $k;
        }
      }
      addLogMessage( "End", $this->name."->setupUserFields()" );
      return true;
    }
    
    
    /**
    * Get an HTML list of search field names
    * @return string HTML unordered list of field names
    */
    function getResultsFieldsAsList(){
      if( sizeof( $this->aResultsFields ) > 0 ){
        $aColumns = $this->aResultsFields;
      }else{
        $aColumns = array_keys( $this->aFields );
      }
      $html = "<div>\n";
      $html .= " <ul>\n";
      foreach( $aColumns as $key ){
        $field = $this->aFields[$key];
        $field->showviewlink = false;
        if( !$field->hascolumn ) continue;
        $html .= "   <li id=\"".$field->columnname."\">\n";
        $html .= "     ".$field->toField()."\n";
        $html .= "   </li>\n";
      }
      $html .= " </ul>\n";
      $html .= "</div>\n";
      return $html;
    }
    
    
    
    /**
    * Return a cut down version of the object, for AJAX/WS
    */
    function toSimpleObject(){
      $obj = array( 
        "tablename" => $this->tablename,
        "name" => $this->name,
        "id" => $this->id,
        "flash" => array( "positive" => true ),
        "fields" => array()
      );
      if( sizeof( $this->aErrors ) > 0 ){
        $obj["flash"]["errors"] = $this->aErrors;
        $obj["flash"]["positive"] = false;
      }
      if( sizeof( $this->aResultsFields ) > 0 ){
        $aColumns = $this->aResultsFields;
      }else{
        $aColumns = array_keys( $this->aFields );
      }
      foreach( $aColumns as $key ){
        $field = $this->aFields[$key];
        $obj["fields"][] = array(
          "column" => $key,
          "name" => $field->name,
          "displayname" => $field->displayname,
          "value" => $field->value,
          "string" => $field->toString(),
          "list" => $field->listitems,
          "helphtml" => $field->helphtml,
          "required" => $field->required,
          "display" => $field->display,
        );
      }
      return $obj;
    }
    
    /**
    * Return a cut-down version of the object with results fields serialised as JSON
    * @return string A JSON encoded string of the object
    */
    function toJson(){
      return json_encode( $this->toSimpleObject() );
    }
    
    
    /**
    * Method to get id, label and value for a row item to be returned by AJAX
    */
    function getAjaxResultRow(){
      return array(
        "id" => $this->id,
        "label" => $this->getName(),
        "value" => $this->getName()
      );
    }
    
    /**
    * Determine whether this model has custom javascript
    * @return bool
    */
    function hasCustomJs(){
      $file = "../js/model/".$this->tablename.".js";
      if( file_exists( $file ) ) return true;
      return false;
    }
    
    /**
    * If this model has a custom js file, return it with the web root
    * @return string
    */
    function getCustomJsPath(){
      if( !$this->hasCustomJs() ) return false;
      return SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].SITE_ROOT."js/model/".$this->tablename.".js";
    }
    
    /**
    * Determine whether this model has custom CSS
    * @return bool
    */
    function hasCustomCss(){
      $file = "../css/model/".$this->tablename.".css";
      if( file_exists( $file ) ) return true;
      return false;
    }
    
    /**
    * If this model has a custom css file, return it with the web root
    * @return string
    */
    function getCustomCssPath(){
      if( !$this->hasCustomCss() ) return false;
      return SITE_PROTOCOL."://".$_SERVER["SERVER_NAME"].SITE_ROOT."css/model/".$this->tablename.".css";
    }
    
    /**
    * Return the object as static HTML
    * @return string 
    */
    function toHtml(){
      $html = "";
      if( isset( $this->aFields["name"] ) ) $html .= "<h2>".h($this->getName())."<h2>\n";
      foreach( $this->aFields as $key => $field ){
        if( !$field->display ) continue;
        // if( $field->columnname == "name" ) continue;
        $html .= $field->toHtml()."\n";
      }
      $html .= "\n";
      return $html;
    }
    
    
    /**
    * Express the entire object as flat string data
    * @return string
    */
    function toString(){
      $html = "";
      if( isset( $this->aFields["name"] ) ) $html .= $this->aFields["name"]->value."\n";
      foreach( $this->aFields as $key => $field ){
        if( !$field->display ) continue;
        if( $field->columnname == "name" ) continue;
        $html .= $field->displayname.": ".$field->toString()."\n";
      }
      $html .= "\n";
      return $html;
    }
    function __toString(){
      return $this->toString();
    }
    
    
    /**
    * List the object's fields with field types etc
    * @return string
    */
    function describe(){
      $str = $this->name.":\n";
      foreach( $this->aFields as $field ){
        $str .= " - ".$field->displayname." : ".$field->name." : ".$field->columnname." : ".$field->type." : ".$field->getDataType()."\n";
      }
      return $str;
    }
    
    
    /**
    * Create a classfile for this object.
    * This can be used to quickly develop classes in memory and then create the class file
    * @return bool true if successful
    */
    function createClassFile(){
      $classfile = "../models/".$this->tablename.".model.class.php";
      if( file_exists( $classfile ) ) return false;
      $fp = fopen( $classfile, "w" );
      $str = "<?php
  /*
    AUTO-GENERATED CLASS
    Generated ".date( SITE_DATETIMEFORMAT )."
  */
  class ".$this->name." extends Model{
    
    function __construct(){
      \$this->Model( get_class(\$this) );\n";
      foreach( $this->aFields as $field ){
        $str .= "      \$this->addField( Field::create( \"".$field->name."\" ) );\n";
      }
      $str .= "    }
  }
?>";
      fwrite( $fp, $str );
      fclose( $fp );
      if( file_exists( $classfile ) ) return true;
      return false;
    }

    /* Checks if the record is a new one (id=0) or fetched from DB(id>0) 
     * @return boolean;
     */
    function isNewRecord(){
      if ($this->id == 0) return true;
      else return false;
    }

    /* Checks if the fetched record is owned by the logged in User 
     * @param string $column Optional, to redefine field that the logged in user_id is checked against
     * @return boolean;
     */
    function isOwnedRecord($column="user_id"){
      if(!$this->isNewRecord() && isset($this->aFields[$column]) && $this->aFields[$column]->value == SessionUser::getProperty("id"))
        return true;
      else
        return false;
    }  
    function __get( $name ){
      switch( $name ){
        case "Fields":
          return new Fields( $this->aFields );
          break;
      }
    }
    
    /**
    * Implementations of methods for iReportable
    */
    function getAvailableReportFormats(){
      return array(
        "xls",
        "html"
      );
    }
    function getAvailableSubscriptionTypes(){
      return array( "periodic", "event", "bookmark" );
    }
    
    function sendEmailReport($format="",$user,$name=""){
      if( $this->debug ) echo "sendEmailReport( $format,".$user->fullName().")\n";

      if( $format == "" ){
        $a = $this->getAvailableReportFormats();
        $format = $a[0];
      }else{
        
        // Check report is in list of available report formats
        if( !in_array( $format, $this->getAvailableReportFormats() ) ){ 
          if( $this->debug ) echo $format." isn't an available format\n";
          return false;
        }
      }
      
      if( !is_object( $user ) ){
        return false;
      }
    
      if( $user->Fields->Name == "" ) return false;
      
      if( $this->debug ) echo "initing ".$this->name."... ";
      $this->doInits();
      if( $this->debug )  echo "done\n";
      if( $this->debug ) echo "Getting results from search as a table... ";
      // $tbl = $this->getSearchResultsAsTable();
      $tmpfile = $this->writeSearchResultsToTempHtmlFile();
      
      if( $this->debug ) echo "done\n";
      if( $this->debug ) echo "Constructing email... ";
      if( $name != "" ) $subject = $name;
      else $subject = $this->displayname." export";
      
      if( $name != "" ) $filename = preg_replace( "/[^A-Za-z0-9\.]/", "_", $name );
      else $filename = $this->tablename;
      $filename .= "_".date( "Y-m-d" ).".xls";
      
      // Check that the report is OK to send with this optional method
      if( method_exists( $this, "sendEmailReportValidate" ) ){
        if( !$this->sendEmailReportValidate() ){ 
          return false;
        }
      }
          
      // Test attachment size, zip if it's very large
      $maxsize = 5*1024*1024; // 5MB
      if( $format == "xls" && filesize( $tmpfile ) > $maxsize ){
      
        // Zip the file
        try{
          $zip = new ZipArchive();
          $zip->open( $tmpfile.".zip", ZIPARCHIVE::CREATE );
          $zip->addFile( $tmpfile, $filename );
          $zip->close();
          unlink( $tmpfile );
          $tmpfile .= ".zip";
          $filename .= ".zip";
        }
        catch( Exception $e ){
          if( file_exists( $tmpfile.".zip" ) ) unlink( $tmpfile.".zip" );
        }        
      }
      
      $mailer = new Mailer();
      $mailer->setSubject( $subject );
      $mailer->AddRecipient( $user->getFormattedEmailAddress() );
      
      switch( $format ){
        case "xls":
          $mailer->AddAttachment( $tmpfile, $filename, "base64", "text/csv" );
          $mailer->wrapBody( "Please find attached a spreadsheet of the data you requested.\n\nYou can manage which email reports you receive under \"My Profile\" ".SITE_BASE."wizard/my_profile" );  
          break;
          
        case "html":
          if( $this->debug ) echo "Adding HTML to email\n";
          $html = "<p>Please find attached a spreadsheet of the data you requested.</p>\n\n"
            ."<p>You can manage which email reports you receive under <a href=\"".SITE_BASE."wizard/my_profile\">\"My Profile\"</a></p>\n";
          $html = "<html><head></head><body>".file_get_contents( $tmpfile )."</body></html>";
          $mailer->MsgHTML( $html );  
          break;
      }
      if( $this->debug ) echo "Sending...\n";
      $mailer->Send();
      copy( $tmpfile, "/home/iain/Dropbox/cars/exports/".$this->id."_".basename( $name ).".html" );
      unlink( $tmpfile );
      return true;
    }
    /**
     * Send an instance of this report to each member of the supplied group code
     * 
     * @param string $group
     * @param string $format
     * @return void
     */
    function sendToUsersInGroup( $group="", $format="" ){
      
      $ug = new UserGroup();
      $ug->getByCode($group);
      $aUserIds = $ug->getAllUserIds();
      
      foreach( $aUserIds as $id ){
        $u = new User();
        $u->get( $id );
        if( !$u->id ) continue;
        if( !$this->userHasReadAccess( $u ) ) continue;
        $this->sendEmailReport( $format, $u );
      }
    }
    
    function getDisplayName(){
      return $this->displayname;
    }
    function userHasReadAccess($user=null){ 
      if( isset( $user ) ){ 
        $currentuser = SessionUser::getLoginData();
        SessionUser::setByUser( $user );
      }
      $auth = $this->getAuth();
      $rtn = (strstr( $this->getAuth(), "r" ) !== false );
      if( isset( $user ) ) SessionUser::setLoginData( $currentuser );
      return $rtn;
    }
  }
  class Fields implements Iterator{
    private $position = 0;
    private $keys = array();
    function __construct( &$fields ){
      $this->aFields = $fields;
      $this->keys = array_keys( $this->aFields );
      $this->position = 0;
    }
    
    // magic get / set methods for being able to do $model->Fields->SomethingId
    function __get( $name ){
      $colname = camelToUnderscore( $name );
      if( isset( $this->aFields[$colname] ) ) return $this->aFields[$colname];
      return false;
    }
    function __set( $name, $value ){
      if( $name == "aFields" ){
        $this->aFields = $value;
        return true;
      }
      $colname = camelToUnderscore( $name );
      if( !isset( $this->aFields[$colname] ) ) return false;
      return $this->aFields[$colname]->set($value);
    }
    
    // Iterator interface methods
    function current(){
      return $this->aFields[$this->keys[$this->position]];
    }
    function key(){
      return $this->keys[$this->position];
    }
    function next(){
      $this->position++;
    }
    function rewind(){
      $this->position = 0;
    }
    function valid(){
      if( !isset( $this->keys[$this->position] ) ) return false;
      return isset( $this->aFields[$this->keys[$this->position]] );
    }
  }
?>
