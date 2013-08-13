<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/dtm.field.class.php" );
  class TmeField extends DtmField{
    function TmeField( $fieldname, $options="" ){
      $this->DtmField( $fieldname, $options );
      $this->length = 11;
      $this->appendlabel = " (HH:mm)";
      if( $this->type == "tme" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Time";
    }
    
    /**
    * Time-specific defaults
    */
    function setDefault(){
      if( $this->default != "" ){ 
        $this->set( $this->default );
      }
    }
    
    /**
    * Date validate
    */
    function validate(){
      parent::validate();
      if( $this->value < 0 ){
        return array( 
          "message" => $this->displayname." is not in a valid time format",
          "fieldname" => $this->name,
          "columnname" => $this->columnname,
          "type" => "error"
        );
      }
    }
    
    /**
    * Render tme field
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
    function toTmeField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return $this->toDtmField( $options, $el_id, $modifiers );
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
      
      if( $is_search ){ 
        $this->value = preg_split( "/,/", $value ); // OK
        foreach( $this->value as $k => $v ){
          $this->value[$k] = intval( $v );
        }
      }else{
        
        if( is_array( $value ) ){
          $aValues = $value;
        }else{
          $aValues = array( $value );
        }
        $aRtn = array();
        foreach( $aValues as $key => $v ){
          if( $v == "" || intval( $v ) == 0 ){ 
            // $this->value = "";
            $aRtn[$key] = "";
          }else{
            $v = str_replace('.',':',$v);
            if (strlen($v)==4){
              $hours = substr($v,0,2);
              $minutes = substr($v,2,2);
            }
            else {
              $a = preg_split( "/:/", $v ); // OK
              $hours = intval( $a[0] );
              $minutes = isset( $a[1] ) ? intval( $a[1] ) : 0;
            }
            $aRtn[$key] = ( $hours*60*60 ) + ( $minutes * 60 );
            // $this->value = ( $hours*60*60 ) + ( $minutes * 60 );
          }
        }
        if( is_array( $value ) ){
          $this->value = $aRtn;
        }else{
          $this->value = $aRtn[0];
        }
      }
          
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      
      addLogMessage( "End", $this->name."->set()" );
    }
    
    /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      addLogMessage( "Start", $this->name."->toString()" );
      
      // Data from join already present?
      if( sizeof( $aData ) > 0 ){
        $return = "";
        foreach( $aData as $c ){
          $return .= $c." ";
        }
        addLogMessage( "End", $this->name."->toString()" );
        return $return;
      }
      if( $this->value === "" ) return "";
      
      // Value is basically seconds after midnight, therefore GMT will work
      $dtz = new DateTimeZone( "GMT" );
      $dt = new DateTime( "", $dtz );
      $dt->setTimestamp( $this->value );
      addLogMessage( "End", $this->name."->toString()" );
      return $dt->format( SITE_TIMEFORMAT );
    }    
  }
?>
