<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class ChdField extends Field{
    function ChdField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->linksto = substr( $fieldname, 3 );
      $this->linkkey = "";
      $this->rptextraclause = "";
      $this->rptlinkidfield = "id";
      $this->rptlinkobject = $this->columnname;
      $this->editable = false;
      $this->autojoin = true;
      $this->hascolumn = false;
      $this->formfriendly = false; // Should not be rendered directly within a form element
      $this->length = 11;
      $this->listinactive = false;
      $this->customsubheadhtml = "";
      if( $this->type == "chd" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Child";
    }

    /**
    * Render chd field
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
    function toChdField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start", $this->name."->toChdField()" );
      
      // Get any of this item related to the parent id
      require_once( "models/".$this->columnname.".model.class.php" );
      $o = Cache::getModel( $this->linksto );
      // $o->setAction( "new" );
      $o->setupUserFields();
      $o->doInits();
      $aColumns = sizeof( $o->aResultsFields ) > 0 ? $o->aResultsFields : array_keys( $o->aFields );
   
      // Check display is on for each selected column
      $aNewCol = array();
      foreach( $aColumns as $c ){
        if( isset( $o->aFields[$c] ) && $o->aFields[$c]->display ) $aNewCol[] = $c;
      }
      $aColumns = $aNewCol;
      unset( $aNewCol );
      
      foreach( $o->aFields as $key => $field ){
        if( array_search( $key, $aColumns ) !== false ) $o->aFields[$key]->autojoin = true;
      }
      
      $table = camelToUnderscore( $this->parentmodel );
      if( $this->linkkey == "" ) $this->linkkey = $table."_id";
      $link_key = $this->linkkey; 
      if( !preg_match( "/\./", $link_key ) ) $link_key = $o->tablename.".".$link_key;
      $o->listby = implode( ",", $aColumns );
      if( !$this->listinactive && isset( $o->aFields["active"] ) ) $active = " AND ".$o->tablename.".active = 1";
      else $active = "";
      $dbr = $o->getAll( "WHERE ".$link_key." = ".$this->parentid.$active." ".$this->rptextraclause, '', intval( REPEATER_ROW_LIMIT ) );
      $return = $this->customsubheadhtml;
      
      // List any items already related
      if( $dbr->numrows > 0 ){
        $return .= "<p>";
        if( $dbr->numrows == REPEATER_ROW_LIMIT ){
          $return .= "There are too many items in this list to display here. ";
        }
        $return .= "For the full list, view the <a href=\"".SITE_ROOT.$this->rptlinkobject;
        if( $o->tablename == $this->rptlinkobject ) 
          $return .= "/".$this->linkkey."/";
        else
          $return .= "/".$this->columnname."/";
        $return .= $this->parentid;
        $o = Cache::getModel( $this->linksto );
        if( isset( $o->aFields["active"] ) ) $return .= "/active/-1";
        $return .= "\">";
        $return .= underscoreSplit( $this->rptlinkobject );
        $return .= " search area</a>";
        $return .= "</p>\n";
        $return .= "<table class=\"rpt list ".camelToUnderscore( $this->linksto )."\" cellspacing=\"0\">\n";
        $return .= "          <thead>\n";
        $return .= "            <tr>\n";
        // $return .= "              <th class=\"controls\"></th>\n";
        foreach( $aColumns as $key ){
          $field = $o->aFields[$key];
          if( $field->display ){
            $return .= "              <th class=\"".$field->type."\">".$field->displayname."</th>\n";
          }
        }
        $return .= "            </tr>\n";
        $return .= "          </thead>\n";
        $return .= "          <tbody>\n";
        while( $row = $dbr->fetchRow() ){
          /*
          print_r( $row );
          echo "<br>\n<br>\n";
          */
          $o = Cache::getModel( $this->linksto );
          $row["id"] = $row[$this->rptlinkidfield];
          $o->returnpage = $this->rptlinkobject;
          $return .= $o->renderTableRow( $row, $aColumns )."\n";
        }
        $return .= "          </tbody>\n";
        $return .= "      </table>\n";
      }else{
        $parent = $this->getParentModel();
        $return .= "<p>There are no active ".$this->displayname." with this ".$parent->displayname.".";
        $o = Cache::getModel( $this->linksto );
        if( isset( $o->aFields["active"] ) ){
          $return .= " To review inactive ".$this->displayname." <a href=\""
            .SITE_ROOT.$this->rptlinkobject;
          if( $o->tablename == $this->rptlinkobject ) 
            $return .= "/".$this->linkkey."/";
          else
            $return .= "/".$this->columnname."/";
          $return .= $this->parentid;
          $return .= "/active/0"
          ."\">go to the ".$this->displayname." search area.</a>";
        }
        $return .= "</p>\n";
      }
      addLogMessage( "End", $this->name."->toChdField()" );
      // $dbr->close();
      // unset( $dbr );
      return $return;
    }
  }
?>
