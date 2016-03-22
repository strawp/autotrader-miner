<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class StrField extends Field{
    function StrField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      if( $this->type == "str" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Text line";
    }
    
    /**
    * Get the suitable name of the index for this field 
    * @return string 
    */
    function getIndexName(){
      return "TXT_".$this->columnname;
    }    
    
    /**
    * Get the SQL representation of ->value for searches, insertions and updates
    * @param bool $fuzzy True if the search is non-exact
    * @param bool $named True for things like updates where the column name is included
    * @param bool $insert True if it's an insert
    * @return string
    */
    function getDBString( $fuzzy=false, $named=false, $insert=true ){
      $params = $this->setupDBStringParams( $fuzzy, $named, $insert );
      $db = Cache::getModel("DB");
      foreach( $params as $k => $v ){
        $$k = $v;
      }
      if( strlen( $this->value ) == $this->length ) $s = ""; // If data length is the same length as the column max length, fuzzy searches are pointless
      if( !$insert && $this->findblanks ){
        $a = array();
        if( $this->value != "" ) $a[] = $this->parent_tablename.".".$this->columnname." like '".$s.$db->escape( $this->value ).$s."'";
        $a[] = $this->parent_tablename.".".$this->columnname." = ''";
        $data .= " ( ".implode( " OR ", $a )." ) ";
      }else{
        $data .= " $eq '$s".$db->escape( $this->value )."$s'";
      }
      if( $fuzzy && !$insert ){ 
        $data = str_replace( "?", "_", $data );
        $data = str_replace( "*", "%", $data );
      }
      return $data;
    }
    
    /**
    * Set the field's value automatically from the $_POST array
    * @param bool $present Whether the field is *supposed* to be there. Used to work out checkbox values
    */
    function getSubmittedValue( $present=true ){
    
      // Check if there is a confirm field submitted with this one for flagging this field as "find blanks" mode
      if( isset( $_POST["cnf".substr( $this->name, 3 )."_Blank"] ) ){
        $this->findblanks = true;
      }
      if( !isset( $_POST[$this->htmlname] ) ) return false;
      $value = $_POST[$this->htmlname];
      $this->set( $value );
    }
    
    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      if( $this->length == 1 ) $return = "char(1)";
      else $return = "varchar(".$this->length.")";
      return $return;
    }
    
    /**
    * Set ->value on the field with some parsing depending on field type
    * @param mixed $value
    * @param bool $is_search True if the value has been posted from a search page
    */
    function set( $value, $is_search=false ){
      addLogMessage( "Start", $this->name."->set()" );
      if( !$this->editable && !$is_search ){ 
        addLogMessage( "End", $this->name."->set()" );
        return;
      }
      
      $original_value = $this->value;
      if( $is_search && isset( $_GET[$this->columnname."-blank"] ) ){
        $this->findblanks = true;
      }
      $value = convert_smart_quotes( $value );
      $this->value = stripslashes( substr( $value, 0, intval($this->length) ) );
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      addLogMessage( "End", $this->name."->set()" );
    }
    
    /**
    * Get the complete SQL query for this field
    * GROUP by field, giving frequency against each value
    */
    function getStatsCompleteQuery( $table, $joins, $name, $where, $filter="" ){
      if( !$this->hascolumn ) return false;
      if( $where != "" ) $where = "WHERE $where";
      if( $filter != "" ) $filter = ", $filter as filter";
      return "
        SELECT COUNT(*) as figure, ".$name." as name$filter
        FROM ".$table."
        $joins
        $where
        GROUP BY ".$table.".".$this->columnname."
        HAVING COUNT(*) > 0
        ORDER BY COUNT(*) DESC";
    }
    
    /**
    * Get autocomplete results for this field
    */
    function getAutoComplete( $str ){
      if( !$this->hascolumn ) return "No column";
      $db = new DB();
      $sql = "
        SELECT id, ".$this->columnname." as label, ".$this->columnname." as value
        FROM ".$this->parent_tablename."
        WHERE ".$this->columnname." LIKE '%".$db->escape($str)."%'
        GROUP BY ".$this->columnname."
        ORDER BY COUNT( * )
        LIMIT ".intval( SITE_PAGING )."
      ";
      $db->query( $sql );
      $aReturn = array();
      while( $row = $db->fetchRow() ){
        $aReturn[] = $row;
      }
      return json_encode( $aReturn );
    }
    
  }
?>
