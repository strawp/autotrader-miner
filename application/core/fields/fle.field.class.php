<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class FleField extends Field{
    function FleField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->hascolumn = false;
      $this->length = 11;
      $this->iscalculated = false;
      if( $this->type == "fle" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "File upload";
    }

    /**
    * Render fle field
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
    function toFleField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return "<input title=\"".$this->displayname."\" type=\"file\" class=\"".$this->type."\" id=\"".preg_replace( "/[\]\[]/", "_", $el_id )."\" name=\"".$this->htmlname."\" ".$options." />";
    }

  }
?>
