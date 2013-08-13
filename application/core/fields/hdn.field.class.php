<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/str.field.class.php" );
  class HdnField extends StrField{
    function HdnField( $fieldname, $options="" ){
      $this->StrField( $fieldname, $options );
      $this->displaylabel = false;
      if( $this->type == "hdn" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Hidden";
    }


    /**
    * Render hdn field
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
    function toHdnField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return "<input title=\"".$this->displayname."\" type=\"hidden\" class=\"{$this->type} text\" id=\"".$el_id."\" name=\"{$this->htmlname}\" value=\"".htmlentities($this->value)."\" $options/>";
    }
    
    function toSearchField($options="", $el_id=""){
      return "";
    }
  }
?>
