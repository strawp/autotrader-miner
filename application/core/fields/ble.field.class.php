<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  require_once( "core/select_renderer.class.php" );
  class BleField extends Field{
    function BleField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->displayname = preg_replace( "/^Is /", "", $this->displayname )."?";
      $this->length = 3;
      $this->allownull    = false;       // Allow a NULL value in the DB for this field
      if( $this->type == "ble" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Boolean";
    }
    
    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      return "tinyint(".$this->length.")";
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
      if( $insert ){
        if( $named ) $data .= " = ";
        $data .= empty( $this->value ) ? 0 : $db->escape( $this->value );
      }else{
        // Search
        $data = "";
        if( $this->value > -1 ){
          if( $named ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." = ";
          $data .= empty( $this->value ) ? 0 : $db->escape( $this->value );
        }
      }
      return $data;
    }
    
    /**
    * Get the slash-separated URL arguments for returning to a search page
    * @return string
    */
    function getUrlArg(){
      $return = "";
      if( $this->default == "" ) $this->default = -1;
      if( trim( $this->value ) !== "" && $this->value != $this->default ){ 
        $return = $this->columnname."/".urlencode( urlencode( $this->value ) );
        if( $this->findblanks ){ 
          $return .= "/".$this->columnname."-blank/1";
        }
      }
      return $return;
    }

    /**
    * Get SQL for a search query that is representative of what is in ->value
    * @return string
    */
    function getSearchString(){
      $this->pretendtype = "";
      return $this->getDBString( true, true, false );
    }
    
    /**
    * Render ble field
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
    function toBleField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      $select = new SelectRenderer();
      $select->title = $this->displayname;
      $select->name = $this->htmlname;
      $select->id = $el_id;
      
      // $return = "<select title=\"".$this->displayname."\" id=\"".$el_id."\" name=\"{$this->htmlname}\" $options $disabled>\n";
      // $return .= "            <option value=\"\">Select</option>\n";
      $aVals = array();
      if( ( $modifiers & DISPLAY_SEARCH ) ){
        $aVals[-1] = "Either";
        if( $this->default == "" ) $this->default = -1;
        if( $this->value === "" ) $this->value = $this->default;
      }else{
        $select->selected = array( $this->value );
        $aVals[""] = "Not selected";
      }
      addLogMessage( "default=".$this->default, $this->name."->toBleField()" );
      addLogMessage( "value=".$this->value, $this->name."->toBleField()" );
      $aVals[1] = "Yes";
      $aVals[0] = "No";
      $select->listitems = $aVals;
      if( $this->default != "" ) $this->default = intval( $this->default );
      addLogMessage( "default=".$this->default, $this->name."->toBleField()" );
      foreach( $aVals as $key => $value ){
        
        // existing models or searches or not action pages 
        if( $this->parentid != 0 || ( !( $modifiers & DISPLAY_SEARCH ) && !( $modifiers & DISPLAY_FIELDSELECT ) ) || $this->value !== "" ){ 
          
          addLogMessage( "Non-blank model ->value = ".$this->value );
        
          $s = false;
          
          // Quite explicit because of "" and 0 getting confused in submitted data etc
          if( $this->value == 1 && $key == 1 ) $s = true;
          else if( strlen( $this->value ) == 0 && $key === "" ) $s = true;
          else if( strlen( $this->value ) > 0 && $this->value == $key ) $s = true;
          if( $s ){ 
            addLogMessage( "Selecting ".$key );
            $select->selected[] = intval( $key );
          }
        }
        
        // Blank models and action pages
        else{
          addLogMessage( "Blank model search thing, val=$value key=$key" );
          if( $this->default === $key ){ 
            addLogMessage( "select" );
            if( $key === "" ) $select->selected[] = $key;
            else $select->selected[] = intval( $key );
          }
        }
      }
      addLogMessage( "End", $this->name."->toBleField()" );
      return $select->render();
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
      
      switch( $this->type ){
        
        // Boolean
        default:
        
          if( strlen( $value ) == 0 ){
            $this->value = "";
            break;
          }
          if( preg_match( "/^(Y|yes|true|1)/i", $value ) || $value == 1 ){ 
            $this->value = 1;
          }else{
            $this->value = $value;
          }
          if( $this->value > 0 ){
            $this->value = 1;
          }elseif( $this->value < 0 ){
            $this->value = -1;
          }else{
            $this->value = 0;
          }
          break;
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
      addLogMessage( "End", $this->name."->toString()" );
      return $this->value == 1 ? "Yes" : "No";
    }
    
  
    /**
    * Get select statements required to produce a statistical summary for this field
    *  - Total count
    *  - False count
    *  - False percentage
    *  - True count
    *  - True percentage
    * @return array
    */
    function getStatsSelectStatement(){
      return array(
        "COUNT( * ) as ".$this->columnname."_count",
        "SUM( ".$this->parent_tablename.".".$this->columnname." ) as ".$this->columnname."_true",
        "SUM( IF( ".$this->parent_tablename.".".$this->columnname." IS NULL OR ".$this->parent_tablename.".".$this->columnname." = 0, 1, 0 ) ) as ".$this->columnname."_false",
        "CONCAT( ROUND( 100*SUM( ".$this->parent_tablename.".".$this->columnname." )/COUNT( * ) ), '% / ', ROUND( 100*SUM( IF( ".$this->parent_tablename.".".$this->columnname." IS NULL OR ".$this->parent_tablename.".".$this->columnname." = 0, 1, 0 ) )/COUNT( * ) ), '%' ) as ".$this->columnname."_percentages"
      );
    }
    
    /**
    * Format a figure from the summary stats
    * @param  int
    * @return string
    */
    function formatStatsSummaryFigure( $v ){
      $this->value = $v;
      return $this->toString();
    }  
  }
?>
