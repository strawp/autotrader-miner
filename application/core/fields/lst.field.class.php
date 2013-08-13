<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/fields/int.field.class.php" );
  require_once( "core/select_renderer.class.php" );
  class LstField extends IntField{
    function LstField( $fieldname, $options="" ){
      $this->IntField( $fieldname, $options );
      $this->setBelongsto();
      if( $this->belongsto != "" ) $this->indextype = "int";
      else $this->indextype = "";
      $this->length = 11;
      $this->multiselect = false;
      $this->checklist = false;     // Display multiselect items as a checklist/radio instead of select
      $this->showviewlink = true;   // Show the "View" link next to a field
      $this->textfield  = "";       // Field which shows the text version of this foreign key, to avoid having to join just to show that information in search results
      $this->allowcreatefk = false;
      if( $this->type == "lst" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Dropdown list";
    }
    
    /**
    * Set ->value on the field with some parsing depending on field type
    * This is the part where having a softly typed language sucks. This receives:
    *  - int, set it directly to value
    *  - string, use it to look up item this links to if belongsto is set
    *  - string, if belongsto isn't set, this should be the value as list fields can also be keyed by string
    *  - array, this sets the value to the array as this could be a multi-select or search
    * It would all be a lot easier proper overloading could be done
    * @param mixed $value
    * @param bool $is_search True if the value has been posted from a search page
    */
    function set( $value, $is_search=false ){
      addLogMessage( "Start", $this->name."->set()" );
      
      if( !$this->editable && !$is_search ){ 
        addLogMessage( "End", $this->name."->set()" );
        return;
      }
      if( $this->listsql != "" && sizeof( $this->listitems ) == 0 ) $this->setListItemsFromSql();
      if( $this->pretendtype != "" ) $type = $this->pretendtype;
      else $type = $this->type;
      
      $original_value = $this->value;
      
      switch( $type ){
      
        // List
        case "lst":
          
          if( $is_search ){
            $this->value = preg_split( "/,/", $value ); // OK
            break;
          }
          if( is_array( $value ) ){
            $this->value = $value;
            break;
          }
        
          if( $value == "Not selected" || $value == "" ){
            $this->value = 0;
            break;
          }
          if( is_int( $value ) || strcmp( intval( $value ), trim( $value ) ) == 0 ){
            $this->value = intval( $value );
            break;
          }
          if( $this->belongsto != "" && $value != "" ){
            $o = $this->getBelongstoModel();
            $id = $o->getIdByName( trim( $value ) );
            if( !$id && $this->allowcreatefk && !empty( $o->aFields["name"] ) ){
              $o->Fields->Name->value = trim( $value );
              $o->save();
              $id = $o->id;
            }
            $this->value = $id;
            unset( $o );
            break;
          }
          
          else if( sizeof( $this->listitems ) > 0 ){
            if (is_numeric($value)){
              $this->value = array_search( $value, $this->listitems );
            }
            else{
              $this->value = $value;
            }
          }else{
            // Setting literal?
            $this->value = $value;
          }
          break;        
      }
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      
      // addLogMessage( "Value: ".$this->value, $this->name."->set()" );
      addLogMessage( "End", $this->name."->set()" );
    }


    
    
    /**
    * Set the field's value automatically from the $_POST array
    * Pass to set as an int
    * @param bool $present Whether the field is *supposed* to be there. Used to work out checkbox values
    */
    function getSubmittedValue( $present=true ){
      if( !isset( $_POST[$this->htmlname] ) ) return false;
      if( $this->indextype == "int" && !is_array( $_POST[$this->htmlname] ) ) $value = intval($_POST[$this->htmlname]);
      else $value = $_POST[$this->htmlname];
      $this->set( $value );
    }
    
    /**
    * Get the slash-separated URL arguments for returning to a search page
    * Lists return the value array as comma separated ints
    * @return string
    */
    function getUrlArg(){
      $return = "";
      if( is_array( $this->value ) ){
        $c = "";
        $a = $this->columnname."/";
        foreach( $this->value as $v ){
          $a .= $c.$v;
          $c = ",";
        }
        $return = $a;
      }else{
        if( $this->value != "" ) return $this->columnname."/".urlencode( $this->value );
      }
      return $return;
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
      addLogMessage( "Start", $this->name."->getDBString()" );
      // ID list or string list?
      $a = preg_split( "/_/", $this->columnname ); // OK
      if( array_pop( $a ) == "id" ){ 
        
        addLogMessage( "Field is a foreign key", $this->name."->getDBString()" );
        // Multi-value search
        if( is_array( $this->value ) ){
          $aOr = array();
          foreach( $this->value as $v ){
            $or = "";
            if( sizeof( $this->lookup ) > 0 ){
              $or .= $this->lookup["where"];
            }else{
              $or .= camelToUnderscore( $this->parentmodel ).".".$this->columnname;
            }
            if( $named ){ 
              if( $v == 0 ) $or .= " IS NULL ";
              else $or .= trim( $v ) == "" ? $null : " = ".$db->escape( $v );
            }else{
              $or .= trim( $v ) == "" ? 0 : $db->escape( $v );
            }
            $aOr[] = $or;
          }
          $data .= " ( ".join( " OR ", $aOr )." ) ";
        }
        
        // Single value
        else{
        
          if( $named ){ 
            
            if( $this->value == 0 ) $data .= " = NULL";
            else $data .= trim( $this->value ) == "" ? $null : " = ".$db->escape( $this->value );
          }else{
          
            if( $this->value == 0 ) $data .= "NULL";
            else $data .= trim( $this->value ) == "" ? 0 : $db->escape( $this->value );
          }
        }
      }
      else {
        if( is_array( $this->value ) ){
          addLogMessage( "Value is array", $this->name."->getDBString()" );
          foreach( $this->value as $v ){
            $or = "";
            if( sizeof( $this->lookup ) > 0 ){
              addLogMessage( "Using where clause from lookup array", $this->name."->getDBString()" );
              $or .= $this->lookup["where"];
            }else{
              addLogMessage( "Using where clause from column name", $this->name."->getDBString()" );
              $or .= camelToUnderscore( $this->parentmodel ).".".$this->columnname;
            }
            if( $named ){ 
              $or .= trim( $v ) == "" ? $null : " = ".$db->escape( $v );
            }else{
              $or .= trim( $v ) == "" ? 0 : $db->escape( $v );
            }
            $aOr[] = $or;
          }
          $data .= " ( ".join( " OR ", $aOr )." ) ";
        }else{
          $data .= " $eq ";
          $data .= "'$s".$db->escape( $this->value )."$s'";
        }
      }
      addLogMessage( "Start", $this->name."->getDBString()" );
      return $data;
    }
    

    /**
    * Render lst field
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
    function toLstField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start", $this->name."->toLstField()" );
      $select = new SelectRenderer();
      $select->title = $this->displayname;
      $select->name = $this->htmlname;
      $select->id = $el_id;
      $select->checklist = $this->checklist;
      
      $not_selected = ($modifiers & DISPLAY_SEARCH) ? $this->type == "lst" ? "Find blanks" : "All" : "Not selected";
      addLogMessage( "Type: lst", $this->name."->toField()" );
      $title = $this->displayname;
      if( DISPLAY_SEARCH & $modifiers || $this->multiselect ){ 
        $select->multiselect = true;
      }
      
      // addLogMessage( "value=".$this->value );
      
      // Manually set list items
      if( sizeof( $this->listitems ) > 0 ){
        addLogMessage( "From list items", $this->name."->toField()" );
        $select->listitems = $this->listitems;
        $select->listlabels = $this->listlabels;
        if( $this->belongsto != "" ){
          $modelname = $this->belongsto;
          require_once( "models/".camelToUnderscore( $modelname ).".model.class.php" );
          addLogMessage( "Belongs to ".$modelname );          
          // $o = new $modelname();
          $o = Cache::getModel( $modelname );
        }
        
        // The optgroup label
        $lastlabel = "";
        $itemcount = 0;
        foreach( $select->listitems as $key => $value ){
          if( ($modifiers & DISPLAY_SEARCH) && $value === "Not selected" && $not_selected != "Not selected" ){
            $value = $not_selected;
          }
          if ($key == 0 && $value === "All") continue; /* Drop "ALL" for looked up fields */
          if( is_array( $this->value ) ){ 
            if( array_search( $key, $this->value ) !== false ){
              $select->selected[] = $key;
            }
          }else{
            if( $key == $this->value && ( $key != 0 || !($modifiers & DISPLAY_SEARCH) ) ){
              $select->selected[] = $key;
            }
          }
          $itemcount++;
        }
      }
      
      // Automatically pull out of database
      else if( $this->belongsto != "" ){
        $tablename = camelToUnderscore( $this->belongsto );
        require_once( "models/".$tablename.".model.class.php" );
        $modelname = $this->belongsto;
        // $o = new $modelname();
        $o = Cache::getModel( $modelname );
        
        if( is_array( $this->value ) ){ 
          if( array_search( 0, $this->value ) !== false ){
            $select->selected[] = 0;
          }
        }else{
          if( 0 == $this->value && !array_key_exists( "is_default", $o->aFields ) && ( !($modifiers & DISPLAY_SEARCH) ) ){
            $select->selected[] = 0;
          }
        }
        $select->listitems[0] = $not_selected;
        
        $o->listby = $this->listby;
        $a = preg_split( "/,/", $this->listby ); // OK
        if( isset( $o->aFields["active"] ) ){
          
          // Include the selected inactive item if relevant
          $w = "WHERE ".$o->tablename.".active = 0 AND ".$o->tablename.".id IN ( ";
          if( is_array( $this->value ) ){
            $w .= join( ", ", $this->value )." )";
          }else{
            $w .= intval( $this->value )." )";
          }
          $dbr = $o->getAll( $w );
          if( $dbr->numrows > 0 ){
            while( $row = $dbr->fetchRow() ){
              $select->selected[] = intval( $row["id"] );
              $name = "";
              foreach( $a as $c ){
                if( array_key_exists( $c, $row ) ) $name .= htmlentities( $row[$c] )." ";
                else $name .= $c;
              }
              $name = trim( $name );
              $select->listitems[$row["id"]] = $name;
            }
          }
          $where = $o->tablename.".active = 1";
        }else $where = "";
        if( isset($o->aFields["is_default"]) ) $o->listby .= ",is_default";
        if( sizeof( $this->lookup ) > 0 ){
          $where = str_replace( ":parentid:", $this->parentid, $this->lookup["where"] )." AND ".$where;
          $dbr = $o->getAll( $where, $this->lookup["join"] );
        }else{
          if( $where != "" ) $where = "WHERE ".$where;
          $dbr = $o->getAll( $where );
        }
        addLogMessage( "Starting to loop through ".$dbr->numrows." result rows", $this->name."->toField()" );
        if( is_array( $this->value ) ){
          addLogMessage( "Value: Array( ".join( ", ", $this->value )." ) " );
        }else{
          addLogMessage( "Value: ".$this->value );
        }
        while( $row = $dbr->fetchRow() ){
          if( 
            intval( $this->value ) == 0 
            && $this->parentid == 0 
            && array_key_exists( "is_default", $o->aFields ) 
            && !( $modifiers & DISPLAY_SEARCH ) 
          ){
            // addLogMessage( "Default available, no value set" );
            if( $row["is_default"] == 1 ){
              $select->selected[] = intval( $row["id"] );
            }
          }else{
            // addLogMessage( "Default not available or value set" );
            if( is_array( $this->value ) ){
              if( array_search( $row["id"], $this->value ) !== false ){
                $select->selected[] = intval( $row["id"] );
              }
            }else{
              // addLogMessage( "Value not array" );
              if( intval( $this->value ) > 0 && $row["id"] == $this->value ){ 
                addLogMessage( "selecting ".$row["id"] );
                $select->selected[] = intval( $row["id"] );
              }
            }
          }
          $name = "";
          foreach( $a as $c ){
            if( array_key_exists( $c, $row ) ) $name .= $row[$c]." ";
            else $name .= $c;
          }
          $name = trim( $name );
          $select->listitems[intval($row["id"])] = $name;
        }
        addLogMessage( "Finished looping through results", $this->name."->toField()" );        
      }
      if( isset( $o ) ) $o->access = $o->getAuth();
      $return = $select->render();
      if( 
        ( $modifiers & DISPLAY_FIELD ) 
        && intval( $this->value ) > 0 
        && $this->belongsto != "" 
        && isset( $o ) 
        && strstr( $o->access, "r" ) !== false 
      ){ 
        if( $this->showviewlink ) $return .= "          <a href=\"".SITE_ROOT.$o->tablename."/edit/".$this->value."#hdr".$this->parentmodel."\" class=\"view\">View</a>\n";
      }
      addLogMessage( "End", $this->name."->toLstField()" );
      return $return;
    }

    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
      $el_id = $this->htmlname;
      $return = $this->toField( $options, $el_id, DISPLAY_SEARCH );
      return $return;
    }


  
    /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      addLogMessage( "Start", $this->name."->toString()" );
      addLogMessage( join(", ",$aData), $this->name."->toString()" );
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
      
      if( sizeof( $this->lookup ) > 0 && $this->value != "" ){
        $val = $this->value;
        if( is_array( $val ) ) $val = 0;
        if( array_key_exists( $val, $this->listitems ) ){
          addLogMessage( "Match in listitems", $this->name."->toString()" );
          addLogMessage( "End", $this->name."->toString()" );
          return htmlentities( $this->listitems[$val] );
        }else{
          addLogMessage( "End", $this->name."->toString()" );
          return htmlentities( $val );
        }
      }
      
      if( $this->belongsto != "" && $this->value != 0 ){
        
        $return = "";
        
        // Data from join already present?
        if( sizeof( $aData ) > 0 ){
          foreach( $aData as $c ){
            $return .= $c." ";
          }
        }
        
        // Look up the info instead
        else{
          
          $a = preg_split( "/,/", $this->listby ); // OK
          /*
          $o = $this->belongsto;
          require_once( "models/".camelToUnderscore( $o ).".model.class.php" );
          $o = new $o();
          */
          $o = $this->getBelongstoModel(true,false);
          /*
          $o = Cache::getModel( $this->belongsto );
          $o->get( $this->value );
          */
          foreach( $a as $c ){
            if( isset( $o->aFields[$c] ) ) $return .= $o->aFields[$c]->toString()." ";
            else $return .= $c;
          }
          unset( $o );
        }
        addLogMessage( "End", $this->name."->toString()" );
        return htmlentities( $return );
      }
      
      else if( sizeof( $this->listitems ) > 0 ){
        if( $this->value == "" ) $this->value = 0;
        if( !is_array($this->value) ){
          if( isset( $this->listitems[$this->value] ) ) return $this->listitems[$this->value];
        }else{
        }
      }
      addLogMessage( "End", $this->name."->toString()" );
      return "Not selected";
    }
    
    /**
    * Get the complete SQL query for this field
    * GROUP by field, giving frequency against each value
    */
    function getStatsCompleteQuery( $table, $joins, $name, $where, $filter="" ){
      if( !$this->hascolumn ) return false;
      if( $where != "" ) $where = "WHERE $where";
      if( $filter != "" ) $filter = ", $filter as filter";
      $case = "";
      $a = array();
      if( sizeof( $this->listitems ) > 0 ){
        $db = Cache::getModel( "DB" );
        foreach( $this->listitems as $k => $v ){
          $a[] = "WHEN $k THEN '".$db->escape($v)."'";
        }
        $case = "CASE $name ".join("\n",$a)." END ";
        $name = $case;
      }
      $rtn = "
        SELECT COUNT(*) as figure, ".$name." as name$filter
        FROM ".$table."
        $joins
        $where
        GROUP BY ".$table.".".$this->columnname."
        HAVING COUNT(*) > 0
        ORDER BY COUNT(*) DESC";
      // echo $rtn;
      return $rtn;
    }
  }
?>
