<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/mem.field.class.php" );
  class ChkField extends MemField{
    function ChkField( $fieldname, $options="" ){
      $this->MemField( $fieldname, $options );
      $this->hascolumn = false;
      $this->length = 11;
      $this->iscalculated = false;
      if( $this->type == "chk" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Checklist";
    }
    
    /**
    * Set the field's value automatically from the $_POST array
    * @param bool $present Whether the field is *supposed* to be there. Used to work out checkbox values
    */
    function getSubmittedValue( $present=true ){
      if( !isset( $_POST[$this->htmlname] ) ){ 
        $value = array();
      }else{
        $value = $_POST[$this->htmlname];
      }
      $this->set( $value );
    }
    
    /**
    * Render chk field
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
    function toChkField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start", $this->name."->toChkField()" );
      // Get member interface class
      $return = "";
      $membertable = camelToUnderscore( substr( $this->name, 3 ) );
      $classfile = "models/".$membertable.".model.class.php";
      // if( !file_exists( $classfile ) ) return $return;
      require_once( $classfile );
      $m = substr( $this->name, 3 );
      addLogMessage( "Creating new member, $m", $this->name."->toChkField()" );
      $m = Cache::getModel( $m );
      $m->id = $this->parentid;
      $m->context = camelToUnderscore( $this->parentmodel );
      addLogMessage( "context: ".$m->context );
      $return .= $m->renderFields();
      addLogMessage( "End", $this->name."->toChkField()" );
      return $return;
    }
    
  }
?>
