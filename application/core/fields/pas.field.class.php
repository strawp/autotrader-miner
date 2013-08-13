<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/str.field.class.php" );
  class PasField extends StrField{
    function PasField( $fieldname, $options="" ){
      $this->StrField( $fieldname, $options );
      if( $this->type == "pas" ) $this->init();
      $this->length=74;
      // Ability to turn off password hashing, e.g. for the password change dialog where passwords need to be compared in plaintext before storage
      $this->hashonset = true; 
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Password";
    }
    
    /**
    * Passwords stored as 10 char salt + hash
    * @param mixed $value
    * @param bool $is_search True if the value has been posted from a search page
    */
    function set( $value, $is_search=false ){
      addLogMessage( "Start", $this->name."->set()" );
      if( !$this->editable && !$is_search ){ 
        addLogMessage( "End", $this->name."->set()" );
        return;
      }
      
      // Never set blank strings
      if( $value == "" ) return;
      
      // For ability to compare passwords in password change dialog, run set as with string field
      if( !$this->hashonset ) return parent::set( $value, $is_search );
      
      $salt = randomstring(10);
      
      // Hash the value
      $value = $salt.hash( SITE_HASHALGO, $salt.$value );
      
      $original_value = $this->value;
      if( $is_search && isset( $_GET[$this->columnname."-blank"] ) ){
        $this->findblanks = true;
      }
      $this->value = $value;
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      addLogMessage( "End", $this->name."->set()" );
    }

    /**
    * Render pas field
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
    function toPasField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return "<input title=\"".$this->displayname."\" type=\"password\" class=\"{$this->type} text\" id=\"".$el_id."\" name=\"{$this->htmlname}\" value=\"\" $options/>";
    }
    
    function toSearchField($options="", $el_id=""){
      return "";
    }
    
    function toString($aData=array()){
      return "**********";
    }
  }
?>
