<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class DcmField extends IntField{
    function DcmField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->setBelongsto();
      $this->length = 11;
      $this->decimalplaces = 2;
      if( $this->type == "dcm" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Decimal";
    }
    
    /**
    * Check the field value is not larger than the allowed length
    */
    function truncate(){
      if( $this->length > 0 && !is_array( $this->value ) ){
        if( preg_match( "/([0-9]{1,".$this->length."}(\.[0-9]{0,".$this->decimalplaces."})?)/", $this->value, $m ) ) $this->value = $m[1];
      }
    }
    
    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      return "decimal(".$this->length.",".$this->decimalplaces.")";
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
      
      if( $this->listsql != "" && sizeof( $this->listitems ) == 0 ) $this->setListItemsFromSql();
      if( $this->pretendtype != "" ) $type = $this->pretendtype;
      else $type = $this->type;
      
      $original_value = $this->value;
      
      if( $is_search ){
        $a = preg_split( "/,/", $value ); // OK
        $this->value = array();
        foreach( $a as $v ){
          $v = preg_replace( "/[^-0-9\.]/", "", $v );
          $this->value[] = $v;
        }
      }else{
        if( is_array( $value ) ){
          $this->value = array();
          foreach( $value as $v ){
            $v = preg_replace( "/[^-0-9\.]/", "", $v );
            $this->value[] = $v;
          }
        }else{
          $this->value = preg_replace( "/[^-0-9\.]/", "", $value );
        }
      }
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      addLogMessage( "End", $this->name."->set()" );
    }
  }
?>
