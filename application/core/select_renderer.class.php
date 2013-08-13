<?php
  /**
  * Rendering class for easily creating select lists for whatever reason from provided data
  * no association with field class, no data lookup
  */
  class SelectRenderer{
    function SelectRenderer($id=""){
      $this->title = "";
      $this->id = $id;
      $this->name = $id;
      $this->multiselect = false;
      $this->checklist = false;
      $this->listitems = array();
      $this->listlabels = array();
      $this->selected = array();
      $this->size = 1;
      $this->maxrows = 3;
    }
    function addSelection( $key ){
      $this->selected[] = $key;
    }
    /**
    * Render the list as a checklist instead of a drop down list
    */
    function renderCheckList(){
      $return = "<ul class=\"checklist\">\n"; // title=\"".$this->title."\" id=\"".$this->id."\" name=\"".$this->name."\"".$options.">\n";
      
      // Manually set list items
      if( sizeof( $this->listitems ) > 0 ){
        
        // The optgroup label
        $lastlabel = "";
        $itemcount = 0;
        foreach( $this->listitems as $key => $value ){
          if( array_search( $key, $this->selected, true ) !== false ){
            $checked = "checked";
          }else{
            $checked = "";
          }
          if( array_key_exists( $key, $this->listlabels ) ){
            if( $this->listlabels[$key] != $lastlabel ){
              if( $lastlabel != "" ) $return .= "            </ul></li>\n";
              $return .= "            <li><span class=\"group\">".htmlentities( $this->listlabels[$key] )."</span>\n<ul>\n";
            }
            $lastlabel = $this->listlabels[$key];
          }
          if( $lastlabel != "" ) $return .= "  ";
          $id = $this->id."_".htmlentities($key);
          $chk = $checked == "" ? "" : " checked=\"$checked\"";
          $return .= "          <li class=\"$checked\">\n";
          $return .= "            <input id=\"$id\" name=\"".$this->id."[]\" type=\"checkbox\"$chk value=\"$key\" />\n";
          $return .= "            <label for=\"$id\">".htmlentities( $value )."</label>\n";
          $return .= "          </li>\n";
          $itemcount++;
        }
        if( $lastlabel != "" ) $return .= "            </ul></li>\n";
      }
      $return .= "          </ul>\n";
      return $return;
    }
    
    /**
    * Render the list as a radio button list instead of a dropdown list
    */
    function renderRadioList(){
      $return = "<ul class=\"radiolist\">\n"; // title=\"".$this->title."\" id=\"".$this->id."\" name=\"".$this->name."\"".$options.">\n";
      
      // Manually set list items
      if( sizeof( $this->listitems ) > 0 ){
        
        // The optgroup label
        $lastlabel = "";
        $itemcount = 0;
        foreach( $this->listitems as $key => $value ){
          if( array_search( $key, $this->selected, true ) !== false ){
            $checked = "checked";
          }else{
            $checked = "";
          }
          if( array_key_exists( $key, $this->listlabels ) ){
            if( $this->listlabels[$key] != $lastlabel ){
              if( $lastlabel != "" ) $return .= "            </ul></li>\n";
              $return .= "            <li><span class=\"group\">".htmlentities( $this->listlabels[$key] )."</span>\n<ul>\n";
            }
            $lastlabel = $this->listlabels[$key];
          }
          if( $lastlabel != "" ) $return .= "  ";
          $id = $this->id."_".htmlentities($key);
          $chk = $checked == "" ? "" : " checked=\"$checked\"";
          $return .= "          <li class=\"$checked\">\n";
          $return .= "            <input id=\"$id\" name=\"".$this->id."\" type=\"radio\"$chk value=\"$key\" />\n";
          $return .= "            <label for=\"$id\">".htmlentities( $value )."</label>\n";
          $return .= "          </li>\n";
          $itemcount++;
        }
        if( $lastlabel != "" ) $return .= "            </ul></li>\n";
      }
      $return .= "          </ul>\n";
      return $return;
    }
    function render(){
      $options = "";
      if( $this->checklist && $this->multiselect ) return $this->renderCheckList();
      if( $this->checklist && !$this->multiselect ) return $this->renderRadioList();
      if( $this->multiselect ){ 
        $this->title .= " (hold ctrl and click to select multiple items)";
        if( !preg_match( "/\[\]$/", $this->name ) ) $this->name.="[]"; 
        $this->size = sizeof( $this->listitems ) > $this->maxrows ? min( $this->maxrows, sizeof( $this->listitems ) ) : sizeof( $this->listitems );
        $options .= " multiple=\"multiple\" size=\"".$this->size."\" "; 
      }
      $return = "<select title=\"".h($this->title)."\" id=\"".$this->id."\" name=\"".h($this->name)."\"".$options.">\n";
      
      // Manually set list items
      if( sizeof( $this->listitems ) > 0 ){
        
        // The optgroup label
        $lastlabel = "";
        $itemcount = 0;
        foreach( $this->listitems as $key => $value ){
          if( array_search( $key, $this->selected, true ) !== false ){
            $selected = " selected=\"selected\"";
          }else{
            $selected = "";
          }
          if( array_key_exists( $key, $this->listlabels ) ){
            if( $this->listlabels[$key] != $lastlabel ){
              if( $lastlabel != "" ) $return .= "            </optgroup>\n";
              $return .= "            <optgroup label=\"".htmlentities( $this->listlabels[$key] )."\">\n";
            }
            $lastlabel = $this->listlabels[$key];
          }
          if( $lastlabel != "" ) $return .= "  ";
          $return .= "            <option value=\"$key\"$selected>".htmlentities( $value )."</option>\n";
          $itemcount++;
        }
        if( $lastlabel != "" ) $return .= "            </optgroup>\n";
      }
      $return .= "          </select>";
      return $return;
    }
  }
?>