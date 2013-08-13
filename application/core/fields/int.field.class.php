<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class IntField extends Field{
    function IntField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->setBelongsto();
      $this->length = 11;
      if( $this->type == "int" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Integer";
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
      if( is_array( $this->value ) ){
        if( $this->value[0] != "" && $this->value[1] != "" && $this->value[0] == $this->value[1] ){
          $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." = ".$this->value[0];
        }else{
          if( $this->value[0] != "" ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." >= ".$this->value[0];
          if( $this->value[0] != "" && $this->value[1] > $this->value[0] ) $data .= " AND ";
          if( isset( $this->value[1] ) && $this->value[1] > $this->value[0] ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." <= ".$this->value[1];
        }
      }
      else{
        if( $named ) $data .= " = ";
        if( $this->belongsto != "" && ( $this->value == 0 || $this->value == "" ) ) $data .= "NULL";
        else $data .= empty( $this->value ) ? 0 : $db->escape( $this->value );
      }
      return $data;
    }
    
    /**
    * Get the slash-separated URL arguments for returning to a search page
    * ints need either "To" or "From" in a range for searches
    * @return string
    */
    function getUrlArg(){
      if( is_array( $this->value ) ){
        if( sizeof( $this->value ) != 2 ) return "";
        if( $this->value[0] == "" && $this->value[1] == "" ) return "";
        $c = "";
        $a = $this->columnname."/";
        foreach( $this->value as $v ){
          $a .= $c.$v;
          $c = ",";
        }
        $return = $a;
      }else{
        $return = $this->columnname."/".$this->value;
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
      return $this->renderRangedSearchField();
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
          $v = preg_replace( "/[^0-9]/", "", $v );
          $this->value[] = $v;
        }
      }else{
        if( is_array( $value ) ){
          $this->value = array();
          foreach( $value as $v ){
            $v = preg_replace( "/[^0-9]/", "", $v );
            $this->value[] = $v;
          }
        }else{
          $this->value = intval( $value );
        }
        if( $this->belongsto != "" ){
        
          if( $value == "Not selected" || $value == "" ){
            $this->value = 0;
          }
          
          else if( intval( $value ) > 0 ){
            $this->value = intval( $value );
          }
          
          else if( $this->belongsto != "" && $value != "" ){
            $o = new Model( $this->belongsto );
            $this->value = $o->getIdByName( trim( $value ) );
            unset( $o );
          }
          
          else if( sizeof( $this->listitems ) > 0 ){
            if (is_numeric($value)){
              $this->value = array_search( $value, $this->listitems );
            }
            else{
              $this->value = $value;
            }
          }
        }
      }
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      addLogMessage( "End", $this->name."->set()" );
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
      if( !$this->hascolumn  ){ 
        return "";
      }
      if( !$this->display ){ 
        return "";
      }
      return array(
        "SUM( ".$this->parent_tablename.".".$this->columnname." ) as ".$this->columnname."_sum",
        "COUNT( * ) as ".$this->columnname."_count",
        "AVG( ".$this->parent_tablename.".".$this->columnname." ) as ".$this->columnname."_average",
        "STD( ".$this->parent_tablename.".".$this->columnname." ) as ".$this->columnname."_deviation",
        "MAX( ".$this->parent_tablename.".".$this->columnname." ) as ".$this->columnname."_max",
        "MIN( ".$this->parent_tablename.".".$this->columnname." ) as ".$this->columnname."_min"
      );
    }
  }
?>
