<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/int.field.class.php" );
  class TmrField extends IntField{
    function TmrField( $fieldname, $options="" ){
      $this->IntField( $fieldname, $options );
      $this->length = 11;
      $this->appendlabel = " (HH:mm)";
      if( $this->type == "tmr" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Timer";
    }

    /**
    * Render tmr field
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
    function toTmrField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return "<input title=\"".$this->displayname."\" type=\"text\" class=\"{$this->type} text\" id=\"".$el_id."\" name=\"{$this->htmlname}\" value=\"".$this->toString()."\" $options $disabled/>";
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
          if( trim( $v ) == "" ){ 
            // $this->value = 0;
            $aRtn[] = "";
          }else{
            $a = preg_split( "/:/", $v ); // OK
            $hours = intval( $a[0] );
            $minutes = isset( $a[1] ) ? intval( $a[1] ) : 0;
            // $this->value = ( $hours*60*60 ) + ( $minutes * 60 );
            $aRtn[] = ( $hours*60*60 ) + ( $minutes * 60 );
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
      if( $this->value == 0 || $this->value == "" ) return "";
      $hours = str_pad( floor( $this->value / ( 60*60 ) ), 2, "0", STR_PAD_LEFT );
      $minutes = str_pad( floor( $this->value / 60 ) - ( $hours * 60 ), 2, "0", STR_PAD_LEFT );
      addLogMessage( "End", $this->name."->toString()" );
      return "$hours:$minutes";
    }
    
    /**
    * Get the fields needed in a select statement to produce a statistical summary for this field
    * Gets:
    *  - Sum
    *  - Count
    *  - Average
    *  - Deviation
    *  - Max
    *  - Min
    * @return array
    */
    function getStatsSelectStatement(){
      return array(
        "CONCAT( FLOOR( SUM( ".$this->columnname." )/3600 ), ':', ROUND(MOD(SUM(".$this->columnname."),3600)/60) ) as ".$this->columnname."_sum",
        "COUNT( * ) as ".$this->columnname."_count",
        "CONCAT( FLOOR( AVG( ".$this->columnname." )/3600 ), ':', ROUND(MOD(AVG(".$this->columnname."),3600)/60) ) as ".$this->columnname."_average",
        "CONCAT( FLOOR( STD( ".$this->columnname." )/3600 ), ':', ROUND(MOD(STD(".$this->columnname."),3600)/60) ) as ".$this->columnname."_deviation",
        "CONCAT( FLOOR( MAX( ".$this->columnname." )/3600 ), ':', ROUND(MOD(MAX(".$this->columnname."),3600)/60) ) as ".$this->columnname."_max",
        "CONCAT( FLOOR( MIN( ".$this->columnname." )/3600 ), ':', ROUND(MOD(MIN(".$this->columnname."),3600)/60) ) as ".$this->columnname."_min"
        /*
        "FORMAT( AVG( ".$this->columnname." )/100, 2 ) as ".$this->columnname."_average",
        "FORMAT( STD( ".$this->columnname." )/100, 2 ) as ".$this->columnname."_deviation",
        "FORMAT( MAX( ".$this->columnname." )/100, 2 ) as ".$this->columnname."_max",
        "FORMAT( MIN( ".$this->columnname." )/100, 2 ) as ".$this->columnname."_min"
        */
      );
    }

    /**
    * Get the SQL representation of ->value for searches, insertions and updates
    * @param bool $fuzzy True if the search is non-exact
    * @param bool $named True for things like updates where the column name is included
    * @param bool $insert True if it's an insert
    * @return string
    */
    /*
    function getDBString( $fuzzy=false, $named=false, $insert=true ){
      $params = $this->setupDBStringParams( $fuzzy, $named, $insert );
      foreach( $params as $k => $v ){
        $$k = $v;
      }
      if( $named ) $data .= " = ";
      if( $this->belongsto != "" && ( $this->value == 0 || $this->value == "" ) ) $data .= "NULL";
      else $data .= empty( $this->value ) ? 0 : $db->escape( $this->value );
      return $data;
    }
    */
  }
?>
