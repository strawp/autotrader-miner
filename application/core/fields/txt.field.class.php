<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class TxtField extends Field{
    function TxtField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->length = 0;  // 0 = "unlimited"
      $this->rows = 5;
      $this->cols = 20;
      if( $this->type == "txt" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Text area";
    }

    /**
    * Get the SQL datatype of the field
    * @return string 
    */
    function getDataType(){
      return "text";
    }
    
    /**
    * Get the suitable name of the index for this field 
    * @return string 
    */
    function getIndexName(){
      return "TXT_".$this->columnname;
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
      $data .= " $eq ";
      $a = preg_split( "/\n/", str_replace( "\r", "", $this->value ) ); // OK
      foreach( $a as $k => $l ){
        $a[$k] = $db->escape( $l );
      }
      $data .= "'$s".join( chr( 13 ).chr(10), $a )."$s'";
      return $data;
    }
    
    /**
    * Render txt field
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
    function toTxtField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      return "<textarea title=\"".$this->displayname."\" id=\"".$el_id."\" class=\"".$this->type." text\" name=\"".$this->htmlname."\" "
        ."rows=\"".$this->rows."\" cols=\"".$this->cols."\" ".$options." >"
        .htmlentities( $this->value )."</textarea>";
    }
    
    /**
    * Render the field as uneditable HTML
    * @param string $options Currently unused
    * @param string $el_id HTML id to use
    * @return string 
    */
    function toHtml( $options="", $el_id="" ){
      $str = trim( $this->value );
      $a = preg_split( "/".chr(13)."/", $str ); // OK
      $str = "        <div class=\"txt\">\n";
      foreach( $a as $line ){
        if( trim( $line ) != "" ){
          $str .= "<p>".htmlentities( $line )."</p>\n";
        }else{
          $str .= "<p>&nbsp;</p>\n";
        }
      }
      return $str."</div>\n";
    }

    /**
    * Render this field as the string value of the field inside a span, with the actual value in a hidden input next to it.
    * @return string HTML of the field
    * @param string $el_id optional ID of the span element, defaults to $this->id
    */
    function renderUneditable( $el_id = "", $modifiers="" ){
      addLogMessage( "Not editable", $this->name."->renderUneditable()" );
      if( $el_id == "" ) $el_id = $this->id;
      $return = "<div class=\"".$this->type." disabled\" id=\"".$el_id."\" title=\"".htmlentities( $this->displayname )."\">";
      $return .= $this->toHtml();
      $return .= "</div>\n"
        ."          <input type=\"hidden\" name=\"".$this->htmlname."\" value=\"".h( $this->value )."\" />";
      $return .= $this->getHelpHtml();
      $return .= "<br/>\n";
      addLogMessage( "End", $this->name."->renderUneditable()" );
      return $return;
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
      $this->value = stripslashes( $value );
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      
      addLogMessage( "End", $this->name."->set()" );
    }
    
    /**
    * Set the haschanged property based on previous and current values
    * @param $original_value string 
    */
    function setHaschanged( $original_value ){
    
      // Ignore it if it has a line feed char in it
      if( strcmp( $original_value, str_replace( chr(10), "", $this->value ) ) != 0 ){ 
        $this->haschanged = true;
      }
      $this->originalvalue = $original_value;
    }
    
    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
    
      if( $el_id == "" ) $el_id = $this->htmlname;
      return "<input type=\"text\" value=\"".h( $this->value )."\" title=\"".$this->displayname."\" id=\"".$el_id."\" class=\"".$this->type." text\" name=\"".$this->htmlname."\" />";
    }
  }
?>
