<?php
  /**
  * Binary data field. Stores data as base64 encoded 
  */
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class BinField extends Field{
    function BinField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->length = 0;  // 0 = "unlimited"
      $this->display = false;
      if( $this->type == "bin" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Binary Data";
    }

    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      return "longblob";
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
      $data .= " $eq ";
      $data .= "'$s".$db->escape( $this->value )."$s'";
      return $data;
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
      $this->value = base64_encode( $value );
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      
      addLogMessage( "End", $this->name."->set()" );
    }
    
    function toString($aData = array()){
      return base64_decode( $this->value );
    }
    
    /**
    * Set the haschanged property based on previous and current values
    * @param $original_value string 
    */
    function setHaschanged( $original_value ){
    
      // Ignore it if it has a line feed char in it
      if( strcmp( $original_value, str_replace( chr(10), "", $this->value ) ) != 0 ){ 
        $this->haschanged = true;
      }
      $this->originalvalue = $original_value;
    }
    
  }
?>
