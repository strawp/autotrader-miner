<?php
  require_once( "core/settings.php" );
  require_once( "db.class.php" );
  require_once( "flash.class.php" );
  require_once( "cache.class.php" );
  class Field{
    function Field( $fieldname="", $options="" ){
      
      // The name of the field
      $this->htmlname = $fieldname;     // Used to base the ID attrib of the field on
      $this->name = $fieldname;         // The name that this is referred by in the model class
      
      // The type of the field
      $this->type         = substr( $fieldname, 0, 3 );   // 3 character field type identifier
      $this->displayname  = preg_replace( "/ Id$/", "", camelSplit( substr( $fieldname, 3 ) ) );    // What's displayed in labels
      $this->columnname   = camelToUnderscore( substr( $fieldname, 3 ) );
      $this->classname    = "";         // Additional CSS classnames to add to field DIV
      $this->display      = true; // $this->type == "int" ? false : true;        // True if this field doesn't need a form entry
      $this->allownull    = true;       // Allow a NULL value in the DB for this field
      $this->options      = $options;    // Property options
      $this->displaylabel = true;       // Turn labels on and off
      $this->appendlabel  = "";         // Text to append to a label when rendering the field (e.g. formatting hint)
      $this->value        = "";         // The literal database-friendly value
      $this->length       = 255;        // Database or string length
      $this->index        = false;      // Create index on this field in the database. Foreign keys ignore this and have indexes regardless
      $this->orderclause  = "";         // Code/store order by fragment to speed up getOrderClause()
      // $this->aFkColumnNames = array();  // Coded/cached list of column names to go in a select statement
      $this->default      = "";         // A default value for this field
      $this->helphtml     = "";         // HTML of a help bubble to display alongside fields
      $this->listitems    = array();    // Cached / manually set list items
      $this->listlabels   = array();    // For each key of listitems an optgroup label can be added
      $this->labelcolumn  = "";         // Which column to use to assign a label to the list
      $this->listsql      = "";         // SQL to get list items
      $this->listgroup    = "";         // Create grouped drop-downs by grouping by this field
      $this->required     = $this->name == "strName" ? true : false;  // Throw an error if this field is missing
      $this->important    = false;      // Only throw up a warning if a field is missing
      $this->unique       = false;      // If the value here can't be used anywhere else
      $this->autojoin     = false;      // Join it to the table it's referencing automatically
      $this->issearchedon = false;      // Flag to true if this has been used in a search
      $this->belongsto    = "";         // Which model this field (if it's a foreign key) points to
      $this->left         = "";         // Members field left model name
      $this->right        = "";         // Members field right model name
      $this->listby       = "name";     // Which field is to be displayed
      $this->hascolumn    = true;       // Has a corresponding column in the database 
      $this->parentmodel  = "";         // Name of the object that contains this instance if there is one
      $this->parentid     = 0;          // The row ID of the model that this belongs to
      $this->parent_tablename = "";     // Table name of the parent model that instantiated this field
      $this->parent_displayname = "";   // Display name of the parent model that instantiated this field
      $this->fieldset     = "";         // Used to track what fieldset this is in 
      $this->multiselect  = false;	    // Is it a multiselect field
      $this->formfriendly = true;       // If this can be inserted right onto an HTML form (or if it needs to be rendered separately)
      $this->editable     = true;       // User is allowed to edit this field
      $this->enabled      = true;       // Whether this field can be used at all in search etc
      $this->linksto      = "";         // Grid uses this
      $this->lookup       = array();    // Lookup fields. 
      $this->pretendtype  = "";         // Calc fields pretending to be other fields
      $this->findblanks   = false;      // str fields use this so they know whether to return blank strings or not
      $this->customsearch = false;      // Whether this field has a set of custom search value handlers e.g. date ranges like "last week"
      $this->customsearchvalue = "";    // The value of the above custom search
      $this->dbclauses    = array();    // What gets looked up in the database if it's a calc field
      $this->sigfigures   = 2;          // Cash uses this for outputting to X significant figures
      $this->originalvalue = "";        // The value the field was before the edit form was submitted
      $this->haschanged   = false;      // Used to monitor if a field has changed from its original value when submitted
      $this->aUsesFields  = array();    // Calculation fields use the fields in this to come out at its eventual value
      $this->aSearchFields= array();    // If this is a repeater or child list then this allows custom columns
      $this->prependHTML  = "";         // manual prepended HTML inside the field div 
      $this->appendHTML   = "";         // manual appended HTML inside the field div 
      $this->uploadFunction = "";       // Function to call after a file has been uploaded
      $this->regexp		    = "";         // Regex Checking.
      $this->afterAddColumnMethod = ""; // If the method named here is present in the parent model it is run when the column for this field is added to the DB
      $this->iscalculated = false;      // If this field is an uneditable calculated field
      $this->preservewhitespace = false;    // Whether to preserve whitespace on list/child display
      $this->defaultorderdir = "asc";   // Default order direction when clicking on a column heading
    }
    
    /**
    * Guess the belongsto property based on the field name (lst and int type)
    * Bases belongsto on regexp /^(lst|int)([A-Za-z]+)Id$/ where belongsto is $2 - the object name this field belongs to
    */
    function setBelongsto( $belongsto = "" ){
      if( $belongsto != "" ){
        $this->$belongsto = $belongsto;
      }else{
        if( !$this->hascolumn ){ 
          // echo "setBelongsto: ".$this->name."->belongsto = ".$match[2]."<br>\n";
          $this->belongsto = "";
        }else{
          if( preg_match( "/^(lst|int)([A-Za-z]+)Id$/", $this->name, $match ) ){ 
            $this->belongsto = $match[2];    // Which table this ID field belongs to
          }else $this->belongsto = "";
        }
      }
      // addLogMessage( "setting ".$this->name."->belongsto as \"".$this->belongsto."\"" );
    }
    
    /**
    * Initialise the remainder of the field, calls:
    *  - setOptions
    *  - setDefault
    *  - setAutocolumn
    */
    function init(){
      $this->setIsCalculated();
      $this->setOptions( $this->options );
      $this->setDefault();
      $this->setAutocolumn();
    }
    
    /**
    * Set the autocolumn property based on foriegn key fields with the belongsto property set
    */
    function setAutocolumn(){
      if( $this->belongsto != "" ){
        $alias = $this->columnname;
        $alias = preg_replace( "/_id$/", "", $alias );
        $c = $alias."_";
        $a = array();
        foreach( preg_split( "/,/", $this->listby ) as $l ){ // OK
          $a[] = $c.$l;
        }
        $this->autocolumn = $a;
      }else{
        $this->autocolumn = array();
      }
    }

    /**
    * Set the autocolumn property based on foriegn key fields with the belongsto property set
    */
    function formatAutocolumnData( $table, $aData ){
      addLogMessage( "Start", $this->name."->formatAutocolumnData()" );
      addLogMessage( join( ", ", $aData ), $this->name."->formatAutocolumnData()" );
      if( $this->belongsto != "" ){
        $alias = $this->columnname;
        $alias = preg_replace( "/_id$/", "", $alias );
        $c = $table."_".$alias."_";
        $o = Cache::getModel( $this->belongsto );
        $aFields = array();
        foreach( preg_split( "/,/", $this->listby ) as $key => $l ){ // OK
          $column = $c.$l;
          if( !isset( $o->aFields[$l] ) ) continue;
          $field = $o->aFields[$l];
          $aFields[$column] = $field->type;
        }
        foreach( $aFields as $key => $type ){
          $str = Field::format( $type, $aData[$key] );
          addLogMessage( $field->name." ( ".$type." ) $key = ".$aData[$key] );
          addLogMessage( "Format as : ".$str );
          $aData[$key] = $str;
        }
      }
      addLogMessage( "End", $this->name."->formatAutocolumnData()" );
      return $aData;
    }

    /**
    * Set the value of this field based on the default property
    */
    function setDefault(){
      if( $this->default != "" ){ 
        $this->value = $this->default;
      }
    }
    
    /**
    * Set the options string into properties for this field
    */
    function setOptions( $options ){
      if( $options != "" ){
        $opts = preg_split( "/;/", $options ); // OK
        foreach( $opts as $opt ){
          $a = preg_split( "/=/", $opt ); // OK
          $this->$a[0] = $a[1];
          // echo "setOptions: ".$this->name."->".$a[0]." = ".$a[1]."<br>\n";
        }
      }
    }
    
    /**
    * Set the list items for this field to a given array
    *
    * Array param in the format
    * <pre>
    *  array( 
    *    array( "id" => 1, "name" => "Name 1" ),
    *    array( "id" => 2, "name" => "Name 2" ),
    *    array( "id" => 3, "name" => "Name 3" ),
    *    ...
    *  )
    * </pre>
    * @param array $data list items to set
    */
    function setListItems( $data ){
      $a = array();
      $b = array();
      foreach( $data as $row ){
        $a[$row["id"]] = convert_smart_quotes( $row["name"] );
        if( $this->labelcolumn != "" && array_key_exists( $this->labelcolumn, $row ) ){
          $b[$row["id"]] = $row[$this->labelcolumn];
        }
      }
      $this->listitems = $a;
      if( sizeof( $b ) > 0 ) $this->listlabels = $b;
    }
    
    /**
    * Execute ->listsql to propagate the ->listitems array
    */
    function setListItemsFromSql( $addnullrow = true ){
      if( $this->listsql == "" ) return false;
      $this->listsql = str_replace( ":id:", $this->parentid, $this->listsql );
      $this->listsql = str_replace( ":value:", $this->getDBString(), $this->listsql );
      $db = Cache::getModel( "DB" );
      $db->query( $this->listsql );
      $aData = array();
      while( $row = $db->fetchRow() ){
        $aData[] = $row;
      }
      if( $addnullrow ) array_unshift( $aData, array( "id" => 0, "name" => "Not selected" ) );
      $this->setListItems( $aData );
      // $db->close();
      // unset( $db );
    }
    
    /**
    * Run automatic validation on this field
    * 
    * Validation does the following tests
    *  - Checks ->required and flags an error if there is no value set
    *  - Checks ->important and flags a warning if there is no value
    *  - Checks ->unique and queries the database to make sure there are no other rows with this field value
    *  - Checks against ->regexp if present
    *  - Checks format of data on a by-type basis
    *
    * @return mixed Returns array of error details if validation fails or true if it succeeds
    */
    function validate(){
      
      // Required?
      if( $this->required ){
        addLogMessage( "Required", $this->name."->validate()" );
        if( $this->type != "mem" && empty( $this->value ) ){
          addLogMessage( "No value: ".$this->value, $this->name."->validate()" );
          return array( 
            "message" => $this->displayname." is a required field",
            "fieldname" => $this->name,
            "columnname" => $this->columnname,
            "type" => "error"
          );
        }elseif( $this->type == "mem" ){
          if( $this->parentid == 0 ){
            return array(
              "message" => $this->displayname." is a required field",
              "fieldname" => $this->name,
              "columnname" => $this->columnname,
              "type" => "warning"
            );
          }
          $o->context = camelToUnderscore( $this->parentmodel );
          $sql = "SELECT * FROM ".camelToUnderscore( $this->left )."_".camelToUnderscore( $this->right )." WHERE ".$o->context."_id = ".$this->parentid;
          $db = Cache::getModel( "DB" );
          $db->query( $sql );
          if( $db->numrows == 0 ){
            return array( 
              "message" => $this->displayname." is a required field",
              "fieldname" => $this->name,
              "columnname" => $this->columnname,
              "type" => "error"
            );
          }
        }
      }
      
      // Important?
      if( $this->important && empty( $this->value ) ){
        addLogMessage( "Important", $this->name."->validate()" );
        return array( 
          "message" => $this->displayname." is an important field and should be entered if possible",
          "fieldname" => $this->name,
          "columnname" => $this->columnname,
          "type" => "warning"
        );
      }
      
      // Unique?
      if( $this->unique && $this->parentmodel != "" && $this->value != "" ){ // && $this->parentid > 0 ){
        addLogMessage( "Unique", $this->name."->validate()" );
        $p = Cache::getModel( $this->parentmodel );
        $p->retrieveByClause( "WHERE ".$this->getDBString( false, true ) ); // // $fuzzy, $named, $insert
        if( $p->id > 0 && $p->id != $this->parentid ){
          return array( 
            "message" => $this->displayname." must be unique. \"".$this->toString()."\" has already been used with another ".$p->displayname.".",
            "fieldname" => $this->name,
            "columnname" => $this->columnname,
            "type" => "error"
          );
        }
      }

      // Validate against regexp
      if (strlen($this->regexp)>0 and strlen($this->value)>0){
        addLogMessage( "Regexp validate", $this->name."->validate()" );
        if(!eregi($this->regexp, $this->value)) {
          return array( 
            "message" => "The value of ".$this->displayname." does not match the kind of value that this field expects (".$this->getTypeName().")",
            "fieldname" => $this->name,
            "columnname" => $this->columnname,
            "type" => "error"
          );
        }
      }	  
      return true;
    }
    
    /**
    * Set ->value on the field with some parsing depending on field type
    * @param mixed $value
    * @param bool $is_search True if the value has been posted from a search page
    */
    function set( $value, $is_search=false ){

      addLogMessage( "Start", $this->name."->set()" );
      if( !$this->editable && !$is_search ) return;
      $original_value = $this->value;
      
      $this->value = $value;
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
    }
    
    /**
    * Most fields store the literal DB value when being retrieved. Others may not
    * @param mixed $value
    */
    function setFromDb( $value ){
      $this->value = $value;
    }
    
    /**
    * Check the field value is not larger than the allowed length
    */
    function truncate(){
      if( $this->length > 0 && !is_array( $this->value ) ){
        $this->value = substr( $this->value, 0, $this->length );
      }
    }
    
    /**
    * Set the haschanged property based on previous and current values
    * @param $original_value string 
    */
    function setHaschanged( $original_value ){
      if( $original_value != $this->value ){ 
        $this->haschanged = true;
      }
      $this->originalvalue = $original_value;
    }
    
    /**
    * Set haschanged and originalvalue back to defaults
    */
    function resetHasChanged(){
      $this->haschanged = false;
      $this->originalvalue = "";
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Unknown field type";
    }    
    
    /**
    * Determine whether this field has been searched on or not and set the property issearchedon
    */
    function setIsSearchedOn(){
      if( isset( $_GET[$this->columnname] ) ) $this->issearchedon = true;
      return $this->issearchedon;
    }
    
    /**
    * Set the field's value automatically from the $_POST array
    * This should be the info that an edit form has for each field
    * @param bool $present Whether the field is *supposed* to be there. Used to work out checkbox values
    */
    function getSubmittedValue( $present=true ){
      $cf = "lst".substr( $this->htmlname, 3 )."-Custom";
      if( !empty( $_POST[$cf] ) ) $this->customsearchvalue = $_POST[$cf];
      if( !isset( $_POST[$this->htmlname] ) ) return false;
      $value = $_POST[$this->htmlname];
      $this->set( $value );
    }
    
    /**
    * Get the slash-separated URL arguments for returning to a search page
    * @return string
    */
    function getUrlArg(){
      $return = "";
      if( is_array( $this->value ) ){
        if( sizeof( $this->value ) == 0 ) return "";
        $c = "";
        $a = $this->columnname."/";
        foreach( $this->value as $v ){
          $a .= $c.$v;
          $c = ",";
        }
        $return = $a;
      }
      else if( trim( $this->value ) !== "" || $this->findblanks ){ 
        $return = $this->columnname."/".urlencode( urlencode( $this->value ) );
        if( $this->findblanks ){ 
          $return .= "/".$this->columnname."-blank/1";
        }
        if( $this->customsearch ){
          $return .= $this->getCustomUrlArg();
        }
      }
      // echo $this->name."->getUrlArg() = ".$return."<br>\n";
      return $return;
    }
    
    /**
    * Method designed to be overloaded by field type specific method to handle any generic custom search fields as required
    */
    function getCustomUrlArg(){
      if( $this->customsearchvalue != "" ) return $this->columnname."-custom/".$this->customsearchvalue;
      return "";
    }
    
    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      return "int(".$this->length.")";
    }    
    
    /**
    * Get options to follow datatype in an ALTER or CREATE TABLE, combining Null and Default
    */ 
    function getColumnOptions(){
      $rtn = "";
      if( !$this->allownull ) $rtn .= " NOT NULL ";
      $rtn .= $this->getDefault();
      return $rtn;
    }
    
    /**
    * Get the fields needed in a select statement to produce a statistical summary for this field
    * @return array
    */
    function getStatsSelectStatement(){
      return false;
    }
    
    /**
    * Get the GROUP statement if necessary for this field's stats summary
    */
    function getStatsGroupStatement(){
      return false;
    }
    
    /**
    * Format a figure from the summary stats
    * @param  int
    * @return string
    */
    function formatStatsSummaryFigure( $v ){
      return $v;
    }
    
    /**
    * Get a list of values that relate to the string being currently typed in
    * The default method is empty - by field type only
    * @param string $q What has currently been typed in
    * @return string a list of values to return, one per line
    */
    function getAutoComplete($q){
      return "";
    }
    
    /**
    * Get the default value of the field
    * @return mixed
    */
    function getDefault(){
      $return = "";
      if( $this->default != "" ){
        $return .= " default ".$this->default;
      }
      return $return;
    }
    
    /**
    * Get YES or NO depending on allownull property
    */
    function getNull(){
      return $this->allownull ? "YES" : "NO";
    }
    
    /**
    * Get SQL for a search query that is representative of what is in ->value
    * @return string
    */
    function getSearchString(){
      if( sizeof( $this->dbclauses ) > 0 ){
        addLogMessage( "Getting from dbclauses", $this->name."->getSearchString()" );
        if( !is_array( $this->value ) ){
          if( array_key_exists( $this->value, $this->dbclauses ) ){
            addLogMessage( "End", $this->name."->getSearchString()" );
            return $this->dbclauses[$this->value];
          }else{
            addLogMessage( "End", $this->name."->getSearchString()" );
            return "";
          }
        }else{
          $return = "";
          $or = "";
          foreach( $this->value as $key=>$value ){
            if( array_key_exists( $value, $this->dbclauses ) ){
              $return .= $or.$this->dbclauses[$value];
              $or = " ) OR ( ";
            }
          }
          if( $return != "" ) $return = " ( ( ".$return." ) ) ";
          addLogMessage( "End", $this->name."->getSearchString()" );
          return $return;
        }
      }
      addLogMessage( "End", $this->name."->getSearchString()" );
      return $this->getDBString( true, true, false ); // $fuzzy, $named, $insert
    }
    
    /**
    * Get the suitable name of the index for this field 
    * @return string 
    */
    function getIndexName(){
      if( $this->belongsto != "" ){
        $indexname = "FK_";
      }else{
        $indexname = "IDX_";
      }
      $indexname .= $this->columnname;
      return $indexname;
    }
    
        
    /**
    * Set up params for getDBString based on whether it's a search, insert, update, what ->value is etc
    * Sets up:
    *  - $data if anything needs to be prepended to the returned data
    *  - $s for making string searches fuzzy (%)
    *  - $eq representing either 'LIKE' or '='
    *  - $null representing either ' = 0' or 'IS NOT NULL'
    * returns all in a keyed array
    */
    function setupDBStringParams( $fuzzy=false, $named=false, $insert=true ){
      $data = "";
      $s = $fuzzy ? "%" : "";
      $eq = $fuzzy ? "LIKE" : "=";
      $eq = $named ? $eq : "";
      $null = $insert ? " = 0 " : " IS NOT NULL ";
      if( $named && !is_array( $this->value ) ){ 
        if( sizeof( $this->lookup ) > 0 ){
          $data .= $this->lookup["where"];
        }else{
          if( !$this->findblanks ) $data .= $this->parent_tablename.".".$this->columnname;
        }
      }
      $params = array( "data", "s", "eq", "null" );
      $aRtn = array();
      foreach( $params as $k => $v ){
        $aRtn[$v] = $$v;
      }
      return $aRtn;
    }
    
    /**
    * Get the SQL representation of ->value for searches, insertions and updates
    * @param bool $fuzzy True if the search is non-exact
    * @param bool $named True for things like updates where the column name is included
    * @param bool $insert True if it's an insert
    * @return string
    */
    function getDBString( $fuzzy=false, $named=false, $insert=true ){
      addLogMessage( "Start ".get_class( $this ), $this->name."->getDBString()" );
      $params = $this->setupDBStringParams( $fuzzy, $named, $insert );
      foreach( $params as $k => $v ){
        $$k = $v;
      }
      $db = Cache::getModel("DB");
      $data .= " $eq '$s".$db->escape( $this->value )."$s'";
      return $data;
    }
    
    
    
    /**
    * Get the fragment of an ORDER BY statement for this field, using the table and columns that this field points to or itself
    * @param string $tablename the name of the parent model table
    * @param string $dir Order direction (DESC/ASC)
    * @return string $order the fragment of the order statement for this field
    */
    function getOrderClause($tablename, $dir){
      addLogMessage( "Start", $this->name."->getOrderClause()" );
      $order = "";
      
      if( $this->orderclause != "" ){
        addLogMessage( "Start", $this->name."->getOrderClause()" );
        return $this->orderclause;
      }
      
      // Normal column-based fields
      if( $this->belongsto != "" && sizeof( $this->lookup ) == 0 ){
        $o = $this->getBelongstoModel();
        $table = $tablename."_".preg_replace( "/_id$/", "", $this->columnname );
        if( array_key_exists( "idx", $o->aFields ) ){
          $order = $table."_idx ".$dir;
        }else{
          $a = preg_split( "/,/", $o->listby ); // OK
          addLogMessage( $o->name, $this->name."->getOrderClause()" );
          $b = array();
          foreach( $a as $k ){
            
            if( !isset( $o->aFields[$k] ) ) continue;
            $b[] = $table."_".$o->aFields[$k]->columnname." ".$dir;
          }
          $order = implode( ", ", $b );
        }
      }
      
      // DB lookup fields or fields without columns
      else{
        // Calculated fields
        if( sizeof( $this->lookup ) == 0 && !$this->hascolumn ) return "";
        if( sizeof( $this->lookup ) == 0 ) $order = $tablename.".";
        $order .= $this->columnname." ".$dir;
      }
      addLogMessage( "End - returning ".$order, $this->name."->getOrderClause()" );
      addLogMessage( "End", $this->name."->getOrderClause()" );
      return $order;
    }
    
    /**
    * If a field belongs to another table (i.e. foreign key), get an instance of that model
    * @param bool $init whether to init the object with the value of the field
    * @param bool $calculations flag to turn off ->get() call automatically doing object calculations
    * @return object the model this field points to
    */
    function getBelongstoModel( $init=false, $calculations=true ){
      if( $init && !empty( $this->belongstoinstance ) && !$this->haschanged ){ 
        return $this->belongstoinstance;
      }
      if( $this->belongsto == "" ) return false;
      $o = Cache::getModel( $this->belongsto );
      if( $init ){ 
        if( !$calculations ){
          $aCalc = $o->calculations;
          $o->calculations = array();
        }
        $o->get( $this->value );
        if( !$calculations ){
          $o->calculations = $aCalc;
        }
        $this->belongstoinstance = $o;
      }
      return $o;
    }
    
    /**
    * Member fields are "linked" with another table, get an instance of that table
    * @return object the model linksto refers to
    */
    function getLinkstoModel(){
      // Work out what this thing is linking to
      if( $this->linksto == "" ) return false;
      $object_name = underscoreToCamel( $this->linksto );
      $o = Cache::getModel( $object_name );
      return $o;
    }
    
    /**
    * Get the model which links the parent model with the one specified in linksto
    * @return object the member model
    */
    function getLinkstoMemberModel(){
      if( $this->linksto == "" ) return;
      if( $this->parentmodel == "" ) return;
      $object_name = underscoreToCamel( $this->linksto );
      $link_model = $this->parentmodel.$object_name;
      $l = Cache::getModel( $link_model );
      return $l;
    }
    
    /**
    * Get an instance of the parent model from ->parentmodel
    * @param bool $getinstance whether to get the specific model by ->parentid or just a general blank one
    * @return object the parent model for this field or false if none exists
    */
    function getParentModel( $getinstance=false ){
      if( $this->parentmodel == "" ) return false;
      $o = Cache::getModel( $this->parentmodel );
      if( $getinstance && intval( $this->parentid ) > 0 ){
        $o->retrieve( intval( $this->parentid ) );
      }
      return $o;
    }
    
    /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      // print_r( $aData );
      addLogMessage( "Start", $this->name."->toString()" );
      if( !empty( $this->textfield ) && isset( $aData[$this->textfield] ) ) return $aData[$this->textfield];
      // Data from join already present?
      if( sizeof( $aData ) > 0 ){
        $return = "";
        foreach( $aData as $c ){
          $return .= $c." ";
        }
        $return = convert_smart_quotes($return);
        addLogMessage( "End", $this->name."->toString()" );
        return $return;
      }
      addLogMessage( "End", $this->name."->toString()" );
      return convert_smart_quotes( $this->value );
    }
    function __toString(){
      return $this->toString();
    }
    
    
    /**
    * Return results list friendly string
    */
    function toResultString( $aData=array() ){
      return h( $this->toString( $aData ) );
    }
    /**
    * Determine if this field is a "calculated" field
    * !$this->hascolumn && sizeof( $this->lookup ) == 0
    * @return bool
    */
    function isCalculated(){
      return $this->iscalculated;
    }
    
    /**
    * Guess if this is a calculated field or not
    */
    function setIsCalculated(){
      if( $this->type == "mem" || $this->type == "chk" || $this->type == "grd" || $this->type == "fle" ){ 
        $this->iscalculated = false;
        return;
      }
      $this->iscalculated = ( !$this->hascolumn && sizeof( $this->lookup ) == 0 );
    }
    
    
    /**
    * Determine if this field is a "lookup" field
    * !$this->hascolumn && sizeof( $this->lookup ) > 0
    * @return bool
    */
    function isLookup(){
      return ( !$this->hascolumn && sizeof( $this->lookup ) > 0 );
    }
    
    /**
    * Render the field as uneditable HTML
    * @param string $options Currently unused
    * @param string $el_id HTML id to use
    * @return string 
    */
    function toHtml( $options="", $el_id="" ){
      $type = $this->type;
      $html = "";
      $html .= "        <div class=\"".$this->columnname." field ".$this->type." ".$this->classname."\">\n";
      // if( $this->type != "img" ) $html .= "          <h4>".$this->displayname."</h4>\n";
      $html .= "          <div class=\"value\">";
      $html .= htmlentities( $this->toString() );
      $html .= "</div>\n";
      $html .= "        </div>\n";
      return $html;
    }
    
    
    /**
    * Render this field as the string value of the field inside a span, with the actual value in a hidden input next to it.
    * @return string HTML of the field
    * @param string $el_id optional ID of the span element, defaults to $this->id
    */
    function renderUneditable( $el_id = "", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Not editable", $this->name."->renderUneditable()" );
      if( $el_id == "" ) $el_id = $this->id;
      
      // Add help text
      $return = $this->getHelpHtml();
      
      $return .= "<span class=\"".$this->type." disabled\" id=\"".$el_id."\" title=\"".htmlentities( $this->displayname )."\">";
      if( $this->belongsto != "" ){
        $o = $this->getBelongstoModel();
      }
      if( isset( $o ) ){ 
        $o->access = $o->getAuth();
      }
      if( ( $modifiers & DISPLAY_FIELD ) && intval( $this->value ) > 0 && $this->belongsto != "" && isset( $o ) && strstr( $o->access, "r" ) !== false ){ 
        $return .= $this->toString();
        if( $this->showviewlink ){ 
          $return .= " <a href=\"".SITE_ROOT.$o->tablename;
          if( $o->hasinterface ){
            $return .= "/edit/";
          }else{
            $return .= "/".$this->columnname."/";
          }
          $return .= $this->value."\" class=\"view\">Go to ".h( trim( $this->toString() ) )."'s page</a>";
        }
      }elseif( $this->type == "htm" ){
        $return .= $this->toString();
      }else{
        $str = $this->toString();
        if( $str != "" ){
          $return .= htmlentities( $this->toString() );
        }else{
          $return .= "&nbsp;";
        }
      }
      $return .= "</span>\n"
        ."          <input type=\"hidden\" name=\"".$this->htmlname."\" value=\"".htmlentities( $this->value )."\" />";
      addLogMessage( "End", $this->name."->renderUneditable()" );
      return $return;
    }
    
    /**
    * Render the field, taking into account if it's set editable, a search field etc
    * @param string $rowid This is currently only used to pass the row ID of an item in rdo, chk etc and in member interfaces
    * @param string $el_id The HTML element ID
    * @param int $modifiers defaults to DISPLAY_FIELD to display an editable field. 
    *   $modifiers values:
    *     - DISPLAY_STRING: Render as a string
    *     - DISPLAY_HTML: Render as uneditable HTML
    *     - DISPLAY_FIELD: Render as editable HTML
    *     - DISPLAY_SEARCH: Render as a search field for that column
    *     - DISPLAY_FIELDSELECT: Render a checkbox to select the field
    *     - DISPLAY_INCLUDE_SEARCH: Flag if this field is included in the user's search field list
    *     - DISPLAY_INCLUDE_RESULTS: Flag if this field is included in the user's results field list
    * @return string The rendered field
    */
    function toField( $rowid="", $el_id="", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start", $this->name."->toField()" );
      if( $this->pretendtype != "" ) $type = $this->pretendtype;
      else $type = $this->type;
      
      if( $this->listsql != "" && sizeof( $this->listitems ) == 0 ) $this->setListItemsFromSql();
      
      if( ( $this->isCalculated() || $this->isLookup() ) && !( DISPLAY_SEARCH & $modifiers ) )  $this->editable = false;
      
      $return = "";
      if( $el_id == "" ) $el_id = $this->htmlname;
      
      // Add help text
      $return .= $this->getHelpHtml();
      
      if( !$this->editable && $this->formfriendly ){
        return $this->renderUneditable( $el_id, $modifiers );
      }
      
      // Get the class name of this field
      $method = "to".ucwords( $this->type )."Field";
      if( method_exists( $this, $method ) ){
        $return .= $this->$method( $rowid, $el_id, $modifiers );
      }else{
        addLogMessage( "Default", $this->name."->toField()" );
        $v = $this->toString();
        $v = h( $v  );
        if( !isset( $disabled ) ) $disabled = "";
        // $v = str_replace( '"', "&quot;", $v );
        $return .= "<input title=\"".htmlentities( $this->displayname )."\" type=\"text\" ";
        if( $this->type == "str" ) $return .= "maxlength=\"".$this->length."\" ";
        $return .= "class=\"".$this->type." text\" id=\"".preg_replace( "/[\]\[]/", "_", $el_id );
        if( intval( $rowid ) > 0 ) $return .= "[".intval($rowid)."]";
        $return .= "\" name=\"".$this->htmlname;
        if( intval( $rowid ) > 0 ) $return .= "[".intval($rowid)."]";
        $return .= "\" value=\"".$v."\" ".$disabled." />";
        
        if( $this->type == "str" && ( $modifiers & DISPLAY_SEARCH ) ){
          $cnf = Field::create( "cnf".substr( $this->name, 3 )."_Blank" );
          $cnf->displayname = "Find blank entries for ".$this->displayname;
          if( $this->findblanks ){ 
            $cnf->value = 1;
          }
          $return .= "\n          <div class=\"blank_search\">";
          $return .= "\n            ".$cnf->toField()." <label for=\"cnf".substr( $this->name, 3 )."_Blank\">Find blanks</label>";
          $return .= "\n          </div>";
        }
        
        addLogMessage( "End ".$this->name, $this->name."->toField()" );
      }
      
      addLogMessage( "End", $this->name."->toField()" );
      return $return;
    }
    
    /**
    * Get the field's helphtml formatted as a help box or a blank string it there is no help html
    * @return string 
    */
    function getHelpHtml(){
      if( $this->helphtml != "" ){
        if( !preg_match( "/^</", trim( $this->helphtml ) ) ) $this->helphtml = "<p>".$this->helphtml."</p>";
        $return = "\n          <div class=\"help\"><h3>Help for ".$this->displayname."</h3><div class=\"body\">".$this->helphtml."</div></div>\n";
      }else{
        $return = "";
      }
      return $return;
    }    
    
    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
    
      if( $this->pretendtype != "" ) $type = $this->pretendtype;
      else $type = $this->type;
      
      if( $this->listsql != "" && sizeof( $this->listitems ) == 0 ) $this->setListItemsFromSql();
      
      $return = "";
      if( $el_id == "" ) $el_id = $this->htmlname;
      
      $return .= $this->toField( $options, $el_id, DISPLAY_SEARCH );
      return $return;
    }
    
    /**
    * Render "from" and "to" fields for fields which can be searched on by range
    * @return string HTML code for the search fields
    */
    function renderRangedSearchField(){
      $from = Field::create( $this->name."[]" );
      $to = Field::create( $this->name."[]" );
      
      if( is_array( $this->value ) ){
        if( isset( $this->value[0] ) ) $from->value = $this->value[0];
        if( isset( $this->value[1] ) ) $to->value = $this->value[1];
      }
      
      $return = "<div class=\"group\">\n"
        ."          <div class=\"from\"><label for=\"".$this->name."_From\">From</label> ".$from->toField( "", $this->name."_From" )."</div>\n"
        ."          <div class=\"to\"><label for=\"".$this->name."_To\">to</label> ".$to->toField( "", $this->name."_To" )."</div>\n"
        ."        </div>\n"
        ."        <br/>\n";
      return $return;
    }
    
    
    /**
    * Renders the field as a checkbox 
    * @param bool $included Whether it's selected. Defaults to true
    * @return string HTML checkbox
    */
    function renderFieldSelect( $search = false, $results = false ){
      $checked = $search ? " checked=\"checked\"" : "";
      $rtn = "<span class=\"field_select\">";
      $class = $search ? " selected" : "";
      $title =  "title=\"Make '".htmlentities( $this->displayname )."' available on the search form\"";
      $rtn .= "<span><label ".$title." for=\"".$this->htmlname."_search\" class=\"search".$class."\">Search</label>"
        ."<input ".$title." type=\"checkbox\" id=\"".$this->htmlname."_search\" name=\"".$this->name."_search\"".$checked." class=\"search check field_select\" /></span>";
      $checked = $results ? " checked=\"checked\"" : "";
      $class = $results ? " selected" : "";
      $title = "title=\"Make '".htmlentities( $this->displayname )."' available in the search results\"";
      $rtn .= " <span><label ".$title." for=\"".$this->htmlname."_results\" class=\"results".$class."\">Results</label>"
        ."<input ".$title." type=\"checkbox\" id=\"".$this->htmlname."_results\" name=\"".$this->name."_results\"".$checked." class=\"results check field_select\" /></span>";
      $rtn .= "</span>\n";
      return $rtn;
    }
    
    
    /**
    * Render the field and associated label
    * @param string $extraclasses space separated list of HTML classes to add to field DIV
    * @param int $modifiers Options on how the field is rendered in ->toField()
    * @return HTML of the field DIV
    * @see toField
    */
    function render( $extraclasses = "", $modifiers=DISPLAY_FIELD ){
      global $aElementIds;
      
      $extraclasses .= " ".$this->classname;
      
      if( is_null( $aElementIds ) ) $aElementIds = array();
      
      addLogMessage( "Start", $this->name."->render()" );
      
      if( ( $this->type == "rpt" || $this->type == "chd" ) && ( $modifiers & DISPLAY_FIELDSELECT || $this->parentid == 0 ) ) return "";
      
      $html = "";
      $html .= "        <div class=\"field ".$extraclasses." ".$this->columnname." ".$this->type." ".$this->belongsto." ".$this->linksto;
      if( $this->helphtml != "" ){
        $html .= " hashelp";
      }
      if( $this->hasError() ){
        $html .= " amend";
      }
      if( $this->required ){
        $html .= " required";
      }
      if( $this->required ){
        $html .= " unique";
      }
      if( ($modifiers & DISPLAY_SEARCH) && ( $this->value != "" || $this->findblanks ) ){
        /*
        if( $this->type != "dte" || ( $this->type == "dte" && ( $this->value[0] > 0 || $this->value[1] > 0 ) ) ){
          $html .= " searched_on";
        }
        */
        if( $this->issearchedon ) $html .= " searched_on";
      }
      $html .= "\">\n";
      $html .= $this->prependHTML;
      $el_id = $this->htmlname;
      if( $this->displaylabel ){
        if( ( $this->type == "rpt" || $this->type == "chd" || $this->type == "ajx" ) && !( $modifiers & DISPLAY_FIELDSELECT ) ){
          $html .= "          <h3>";
          if( $this->linksto ){
            addLogMessage( "Attempting to determine whether the model this field linksto has an \"active\" field" );
            $o = Cache::getModel( $this->linksto );
            if( $o !== false && isset( $o->aFields["active"] ) && !$this->listinactive ){
              $html .= "Active ";
              addLogMessage( "Active field found" );
            }else{
              addLogMessage( "No active field" );
            }
          }
          $el_id = "";
        }
        else{
          // Check what ID this thing should be 
          $unique = false;
          $index=0;
          $searchname = $this->htmlname;
          while( !$unique ){
            $search = array_search( $searchname, $aElementIds );
            if( $search === false ){
              $unique = true;
              $el_id = $searchname;
              continue;
            }else{
              $index++;
              $searchname = $this->htmlname."_".( $index );
            } 
          }
          $html .= "          <label";
          if( ( $modifiers & DISPLAY_SEARCH ) ){
            if( 
              $this->type != "dte" && 
              $this->type != "dtm" && 
              $this->type != "tme" && 
              $this->type != "csh" && 
              $this->type != "pct" 
            ) $html .= " for=\"".$el_id."\"";
          }elseif( ( $modifiers & DISPLAY_FIELDSELECT ) ){
            $html .= " for=\"".$el_id."\"";
          }else{
            if( 
              $this->type != "chk" && 
              $this->type != "mem" && 
              $this->type != "grd" 
            ) $html .= " for=\"".$el_id."\"";
          }
          $html .= ">";
        }
        $html .= $this->type == "rpt" ? plural( $this->displayname ) : $this->displayname;
        $html .= $this->appendlabel;
        if( $this->required && !($modifiers & DISPLAY_SEARCH) && !( $modifiers & DISPLAY_FIELDSELECT ) ) $html .= " <span class=\"required\">(required)</span>";
        if( ( $this->type == "rpt" || $this->type == "chd" || $this->type == "ajx" ) && !( $modifiers & DISPLAY_FIELDSELECT ) ){
          $html .= "</h3>\n";
        }else{
          $html .= "</label>\n";
        }
      }
      if( $modifiers & DISPLAY_SEARCH ) $html .= "          ".$this->toSearchField( "", $el_id )."\n";
      elseif( $modifiers & DISPLAY_FIELDSELECT && $this->type != "rpt" && $this->type != "chd" ){ 
        $html .= "          ".$this->renderFieldSelect( $modifiers & DISPLAY_INCLUDE_SEARCH, $modifiers & DISPLAY_INCLUDE_RESULTS  )."\n";
      }else $html .= "          ".$this->toField( "", $el_id )."\n";
      
      // Any error messages?
      $err = $this->hasError();
      if( $err !== false ){
        $html .= "          <div class=\"error\">".$err."</div>\n";
      }
      
      if( $el_id != "" ) $aElementIds[] = $el_id;
      $html .= $this->appendHTML;
      $html .= "        </div>\n";
      addLogMessage( "End", $this->name."->render()" );
      return $html;
    }

    
    /**
    * Does an error exist in the Flash for this field?
    * @return bool 
    */
    function hasError(){
        foreach( Flash::getError() as $error ){
          if( isset( $error["field"] ) && $error["field"] == $this->htmlname ){
            if( isset( $error["msg"] ) ) return $error["msg"];
            return true;
          }
        }
      return false;
    }
    
    
    /**
    * Take the array of values in this member field and save the association in the members table
    * @param int $fkid ID of the foreign key to bind with
    */
    function saveMemberField( $fkid=0 ){
      addLogMessage( "Start", $this->name."->saveMemberField()" );
    
      if( $fkid != 0 ) $this->parentid = $fkid;
      if( $this->linksto == "" ){ 
        addLogMessage( "End", $this->name."->saveMemberField()" );
        return;
      }
      $table_name = $this->linksto;
      $object_name = underscoreToCamel( $this->linksto );
      $link_model = $this->parentmodel.$object_name;
      $link_table = camelToUnderscore( $this->parentmodel )."_".$this->linksto;
      $fk_right = $table_name."_id";
      $fk_left = camelToUnderscore( $this->parentmodel )."_id";
      foreach( array( $link_table, $table_name ) as $table ){
        if( !file_exists( "../models/".$table.".model.class.php" ) ){
          addLogMessage( "End", $this->name."->saveMemberField()" );
          return;
        }
      }
      
      
      if( $this->value == "" || !is_array( $this->value ) ){ 
        addLogMessage( "End", $this->name."->saveMemberField()" );
        return;
      }
      
      $db = Cache::getModel("DB");
      /*
      $sql = "DELETE FROM $link_table WHERE $fk_left = ".$this->parentid;
      
      // $right_object = new $object_name();
      $right_object = Cache::getModel( $object_name );
      if( array_key_exists( "is_visible", $right_object->aFields ) ){
        $sql .= " AND ".$fk_right." NOT IN ( SELECT id FROM ".$table_name." WHERE is_visible = 0 )";
      }
      $db->query( $sql );
      */
      
      $aNotDelete = array();
      foreach( $this->value as $fkid => $aFields ){
        $aColumns = array();
        $aData = array();
        addLogMessage( "Creating new ".$link_model, $this->name."->saveMemberField()" );
        // $l = new $link_model();
        $l = Cache::getModel( $link_model );
        if( $l->name == "MemberInterface" ){
          $l->id = $this->parentid;
          $l->context = camelToUnderscore( $this->parentmodel );
          $l->aPartnerIds = array_keys( $this->value );
        }
        
        $l->aFields[$fk_right]->value = $fkid;
        $l->aFields[$fk_left]->value = $this->parentid;
        $aColumns[] = $fk_right;
        $aData[] = $fkid;
        $aColumns[] = $fk_left;
        $aData[] = $this->parentid;
        $l->retrieveByClause( "WHERE $fk_right = $fkid AND $fk_left = ".$this->parentid );
        if( is_array( $aFields ) ){
          foreach( $aFields as $fieldname => $v ){
            if( $l->aFields[$fieldname]->editable == false ) continue;
            $l->aFields[$fieldname]->set( $v );
          }
          $l->doCalculations();
          foreach( $aFields as $fieldname => $v ){
            $aColumns[] = $l->aFields[$fieldname]->columnname;
            $aData[] = $l->aFields[$fieldname]->getDBString( false, false, true );
          }
        }
        $db->updateOrInsert( $l->tablename, $aColumns, $aData, "WHERE ".$aColumns[0]." = ".$aData[0]." AND ".$aColumns[1]." = ".$aData[1] );
        // $l->save();
      }
      
      // Delete member items that weren't in that list
      
      
      addLogMessage( "End", $this->name."->saveMemberField()" );
      // $db->close();
      // unset( $db );
    }

    /** A static method to fetch a non-model formatted value 
     * @param $type Field type
     * @param $value Value to convert/format
     * @return mixed
     * @example Field::format("dte",$_GET["value"]);
     * */
    public static function format($type,$value,$plain=false){
      $f = Field::create($type."Dummy");
      if ($plain){
        $f->set($value);
        return $f->value;
      }
      else {
        /* formatted */
        $f->value = $value;
        return $f->toString();
      }
    }
    
    /**
    * Create a field from a field name
    */
    static function create( $fieldname, $options="" ){
      
      // Determine the type
      $type = strtolower( substr( $fieldname, 0, 3 ) );
      
      if( !file_exists( SITE_COREDIR."/fields/".$type.".field.class.php" ) ) return false;
      
      // Require the class
      require_once( "core/fields/".$type.".field.class.php" );
      
      // Class name
      $classname = ucfirst( $type )."Field";
      
      // Return instance
      return new $classname( $fieldname, $options );
    }
    
  }
?>
