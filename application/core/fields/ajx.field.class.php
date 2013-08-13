<?php
  /**
  * Ajax loaded content field. Use to load large slow bits of content, such as a report
  */
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class AjxField extends Field{
    function AjxField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->editable = false;
      $this->enabled = false;
      $this->formfriendly = false; // Should not be rendered directly within a form element
      $this->hascolumn = false;
      $this->value = ""; // Contains URL of AJAX content to load
      if( $this->type == "ajx" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "AJAX content";
    }
    
    function toAjxField(){
      $return = "";
      // $return .= "      <h3>".$this->displayname."</h3>\n";
      $return .= "<a class=\"unloaded\" href=\"".htmlentities($this->value)."\">".$this->displayname."</a>";
      return $return;
    }
    function toResultString($aData=array()){
      return "<a href=\"".$this->value."\">".$this->displayname."</a>";
    }
  }
?>
