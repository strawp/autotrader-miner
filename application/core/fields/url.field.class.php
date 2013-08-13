<?php
  require_once( "core/settings.php" );
  class UrlField extends StrField{
    function UrlField( $fieldname, $options="" ){
      $this->StrField( $fieldname, $options );
      $this->regexp="^((http|https):\/\/)?([-a-zA-Z0-9\.]+)\.([a-zA-Z]+)([^\"'<>]+)?$";
      if( $this->type == "url" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "URL";
    }
    function toHtml( $options="", $el_id="" ){
      $str = "<p><a href=\"".htmlentities($this->value)."\" class=\"url\">".htmlentities( strip_tags( $this->value ) )."</a></p>";
      return $str;
    }
    function toUrlField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      $rtn = "<div class=\"url_pair\">"
        ."<input title=\"".$this->displayname."\" type=\"text\" class=\"{$this->type} text\" id=\"".$el_id."\" name=\"{$this->htmlname}\" value=\"".h($this->value)."\" $options/>"
        .$this->toHtml()
        ."</div>";
      return $rtn;
    }
    function toSearchField( $options="", $el_id="" ){
    
      if( $el_id == "" ) $el_id = $this->htmlname;
      return "<input type=\"text\" value=\"".h( $this->value )."\" title=\"".$this->displayname."\" id=\"".$el_id."\" class=\"".$this->type." text\" name=\"".$this->htmlname."\" />";
    }
    /**
    * Render this field as just the link, but with the value in a hidden field below it
    * @return string HTML of the field
    * @param string $el_id optional ID of the span element, defaults to $this->id
    */
    function renderUneditable( $el_id = "", $modifiers="" ){
      if( $el_id == "" ) $el_id = $this->id;
      $return = "<div class=\"".$this->type." disabled\" id=\"".$el_id."\" title=\"".h( $this->displayname )."\">";
      $return .= $this->toHtml();
      $return .= "</div>\n"
        ."          <input type=\"hidden\" name=\"".$this->htmlname."\" value=\"".h( $this->value )."\" />";
      $return .= $this->getHelpHtml();
      $return .= "<br/>\n";
      return $return;
    }
  }
?>
