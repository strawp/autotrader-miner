<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class PctField extends IntField{
    function PctField( $fieldname, $options="" ){
      $this->IntField( $fieldname, $options );
      $this->length = 11;
      if( $this->type == "pct" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Percentage";
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
      return $this->value."%";
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
        "FORMAT( SUM( ".$this->columnname." ), 0 ) as ".$this->columnname."_sum",
        "COUNT( * ) as ".$this->columnname."_count",
        "FORMAT( AVG( ".$this->columnname." ), 0 ) as ".$this->columnname."_average",
        "FORMAT( STD( ".$this->columnname." ), 0 ) as ".$this->columnname."_deviation",
        "FORMAT( MAX( ".$this->columnname." ), 0 ) as ".$this->columnname."_max",
        "FORMAT( MIN( ".$this->columnname." ), 0 ) as ".$this->columnname."_min"
      );
    }
  }
?>
