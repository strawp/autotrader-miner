<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class RdoField extends Field{
    function RdoField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->length = 11;
      if( $this->type == "rdo" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Radio button";
    }

    /**
    * Render rdo field
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
    function toRdoField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      $checked = $this->value == 1 ? " checked=\"checked\"" : "";
      return "<input title=\"".$this->displayname."\" type=\"radio\" value=\"".$options."\" name=\"".$this->htmlname."\" id=\"".$el_id."[".$options."]\"$checked />";
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
      }else{
        $this->value = $value;
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
      return $this->value == 1 ? "(".$this->displayname.")" : "";
    }    
  }
?>
