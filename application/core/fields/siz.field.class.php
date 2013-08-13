<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class SizField extends Field{
    function SizField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->length = 20;
      $this->defaultorderdir = "desc";
      if( $this->type == "siz" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "File size";
    }
    
    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      return "bigint(".$this->length.")";
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
        if( $this->value[0] != "" ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." > ".$this->value[0];
        if( $this->value[0] != "" && $this->value[1] > $this->value[0] ) $data .= " AND ";
        if( $this->value[1] > $this->value[0] ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." < ".$this->value[1];
      }
      else{
        if( $named ) $data .= " = ";
        if( $this->belongsto != "" && ( $this->value == 0 || $this->value == "" ) ) $data .= "NULL";
        else $data .= empty( $this->value ) ? 0 : $db->escape( $this->value );
      }
      return $data;
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
      if( !$this->editable && !$is_search ) return;
      
      $original_value = $this->value;
      if( $is_search ){
        $this->value = preg_split( "/,/", $value ); // OK
      }else{
      
        if( !is_array( $value ) ){
          $value = preg_replace( "/[^\.0-9]/", "", $value );
          $this->value = round( $value );
        }else{
          $value[0] = round( preg_replace( "/[^\.0-9]/", "", $value[0] )  );
          $value[1] = round( preg_replace( "/[^\.0-9]/", "", $value[1] )  );
          if( $value[0] != 0 || $value[1] != 0 ) $this->value = $value;
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
      if( $this->value == 0 ) return "0";
      addLogMessage( "End", $this->name."->toString()" );
      return formatFilesize( $this->value );
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
        "FORMAT( SUM( ".$this->parent_tablename.".".$this->columnname." ), 2 ) as ".$this->columnname."_sum",
        "COUNT( * ) as ".$this->columnname."_count",
        "FORMAT( AVG( ".$this->parent_tablename.".".$this->columnname." ), 2 ) as ".$this->columnname."_average",
        "FORMAT( STD( ".$this->parent_tablename.".".$this->columnname." ), 2 ) as ".$this->columnname."_deviation",
        "FORMAT( MAX( ".$this->parent_tablename.".".$this->columnname." ), 2 ) as ".$this->columnname."_max",
        "FORMAT( MIN( ".$this->parent_tablename.".".$this->columnname." ), 2 ) as ".$this->columnname."_min"
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
