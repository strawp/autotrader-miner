<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/dtm.field.class.php" );
  class DteField extends DtmField{
    function DteField( $fieldname, $options="" ){
      $this->DtmField( $fieldname, $options );
      $this->length = 11;
      if( $this->type == "dte" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Date";
    }
    
    /**
    * Render dte field
    * @param string $options miscellaneous options to pass to fields
    * @param string $el_id The HTML element ID
    * @param int $modifiers defaults to DISPLAY_FIELD to display an editable field. 
    *   $modifiers values:
    *     - DISPLAY_STRING: Render as a string
    *     - DISPLAY_HTML: Render as uneditable HTML
    *     - DISPLAY_FIELD: Render as editable HTML
    *     - DISPLAY_SEARCH: Render as a search field for that column
    *     - DISPLAY_FIELDSELECT: Render a checkbox to select the field
    * @return string The rendered field
    */
    function toDteField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return $this->toDtmField( $options, $el_id, $modifiers );
    }
    
    /**
    * Set the haschanged property based on previous and current values
    * @param $original_value string 
    */
    function setHaschanged( $original_value ){
      switch( $this->type ){
      
        // dte values are too precise and might flag changes for the field when they haven't really occurred so compare using date mask
        case "dte":
          if( is_array( $this->value ) ) break;
          if( $original_value == "" && $this->value == "" ) break;
          if( $original_value == "" ) $original_value = 0;
          if( $this->value == "" ) $this->value = 0;
          if( date( "Y-m-d", $original_value ) != date( "Y-m-d", $this->value ) ) $this->haschanged = true;
          break;
      }
      $this->originalvalue = $original_value;
    }
    
        /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      addLogMessage( "Start", $this->name."->toString()" );
      addLogMessage( "aData: Array( ".join( ", ", $aData )." )", $this->name."->toString()" );
      addLogMessage( $this->name."->value=".$this->value, $this->name."->toString()" );
      if( $this->listsql != "" && sizeof( $this->listitems ) == 0 ) $this->setListItemsFromSql();
      if( $this->pretendtype != "" ) $type = $this->pretendtype;
      else $type = $this->type;
      
      // Data from join already present?
      if( sizeof( $aData ) > 0 ){
        $return = "";
        foreach( $aData as $c ){
          $return .= $c." ";
        }
        addLogMessage( "End", $this->name."->toString()" );
        return $return;
      }
      addLogMessage( "End", $this->name."->toString()" );
      if( $this->value <= 0 ) return "";
      return date( SITE_DATEFORMAT, $this->value );
    }
  }
?>
