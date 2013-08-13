<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/ble.field.class.php" );
  require_once( "core/select_renderer.class.php" );
  class CnfField extends BleField{
    function CnfField( $fieldname, $options="" ){
      $this->BleField( $fieldname, $options );
      $this->length = 3;
      $this->checkboxvalue = 1;
      if( $this->type == "cnf" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Confirm checkbox";
    }

    /**
    * Set the field's value automatically from the $_POST array
    * @param bool $present Whether the field is *supposed* to be there. Used to work out checkbox values
    */
    function getSubmittedValue( $present=true ){
      if( isset( $_POST[$this->htmlname] ) ){
        $value = intval( $_POST[$this->htmlname] );
      }elseif( $present && !isset( $_POST[$this->htmlname] ) ){
        $value = 0;
      }else{
        return false;
      }
      $this->set( $value );
    }
    
    /**
    * Render confirm box
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
    function toCnfField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      if( $this->parentid == 0 && !$this->value ) $this->value = $this->default;
      $checked = $this->value == 1 ? " checked=\"checked\"" : "";
      $return = "<input title=\"".htmlentities( $this->displayname )."\" type=\"checkbox\" value=\"".htmlentities( $this->checkboxvalue )."\" name=\"".$this->htmlname."\" id=\"".$el_id."\"$checked />";
      return $return;
    }
    
    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
      $this->pretendtype = "lst";
      if( $this->value == "" ) $this->value = -1;
      $select = new SelectRenderer();
      $select->selected = array( intval( $this->value ) );
      $select->title = $this->displayname;
      $select->name = $this->htmlname;
      $select->id = $el_id;
      $select->listitems = array( -1 => "Either", 0 => "No", 1 => "Yes" );
      return $select->render().$this->getHelpHtml();
      // return $this->toField();
    }
    
    /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      addLogMessage( "Start", $this->name."->toString()" );
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
      return $this->value == 1 ? "Confirmed" : "Not confirmed";
    }
        
  }
?>
