<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class MemField extends Field{
    function MemField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->hascolumn = false;
      $this->length = 11;
      $this->iscalculated = false;
      $this->autojoin = true;
      if( $this->type == "mem" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Members list";
    }


    /**
    * Get SQL for a search query that is representative of what is in ->value
    * @return string
    */
    function getSearchString(){
      $db = Cache::getModel("DB");
      $str = "";
      if( is_array( $this->value ) && sizeof( $this->value ) > 0 ){
      
        // Generate the name of the column which this field uses in the member table
        $parent = camelToUnderscore( $this->parentmodel );
        require_once( "models/".$this->columnname.".model.class.php" );
        $o = substr( $this->name, 3 );
        $o = Cache::getModel( $o );
        $joinedto = $o->left == $this->parentmodel ? $o->right : $o->left;
        $joinedto = camelToUnderscore( $joinedto );
        $column = $parent."_".$this->columnname.".".$joinedto."_id = ";
        $a = array();
        foreach( $this->value as $v ){
          $a[] = $column.$v;
          $aIds[] = intval($v);
        }
        $str = " ( ".join( " OR ", $a )." ) ";
      }
      return $db->escape($this->parent_tablename).".id 
        IN (SELECT ".$db->escape($this->parent_tablename)."_id 
          FROM ".$db->escape($this->columnname)." 
          WHERE ".$db->escape($joinedto)."_id 
          IN (".join(",",$aIds)."))";
      // return $str; // This is the old way of searching - causes double counting in stat summary
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
      return $data;
    }
    
    /**
    * Render mem field
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
    function toMemField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start member interface", $this->name."->toField()" );
      
      $return = "";
      
      // Create an object relating to the member table
      $membertable = $this->columnname;
      $classfile = "models/".$membertable.".model.class.php";
      addLogMessage( "Requiring ".$classfile, $this->name."->toField()" );
      require_once( $classfile );
      addLogMessage( $classfile." included", $this->name."->toField()" );
      $m = substr( $this->name, 3 );
      addLogMessage( "Creating new ".$m, $this->name."->toField()" );
      $m = Cache::getModel( $m );
      $m->listby= "id";
      
      // Create an object relating to the object that this field belongs to
      if( $this->parent_displayname == "" || $this->parent_tablename == "" ){
        $p = $this->parentmodel;
        $p = Cache::getModel( $p );
        $this->parent_displayname = $p->displayname;
        $this->parent_tablename = $p->tablename;
      }
      $comma = "";
      $return = "";
      
      // What is the other object?
      $joinedto = $m->left == $this->parentmodel ? $m->right : $m->left;
      addLogMessage( "Joined to ".$joinedto, $this->name."->toField()" );
      // if( !file_exists( "models/".camelToUnderscore( $joinedto ).".model.class.php" ) ) return $return;
      require_once( "models/".camelToUnderscore( $joinedto ).".model.class.php" );
      $l = Cache::getModel( $joinedto );
      $joinedto = camelToUnderscore($joinedto);
      
      // Turn on auto-join
      if( $this->listby != "name" && $this->listby != "" ){
        $m->listby = $this->listby;
      }else{ 
        $m->listby = $joinedto."_id";
      }
      
      // Add on the other fields if they're not already added
      $aListBy = preg_split( "/,/", $m->listby );
      foreach( $m->aFields as $field ){
        if( preg_match( "/_id$/", $field->columnname ) ) continue;
        if( array_search( $field->columnname, $aListBy ) === false ){
          $m->listby.=",".$field->columnname;
        }
      }
      
      $m->aFields[$l->tablename."_id"]->autojoin = true;
      $dbr = $m->getAll( "WHERE ".$this->columnname.".".$this->parent_tablename."_id = ".$this->parentid );
      $l->access = $l->getAuth();
      $can_edit = strstr( $l->access, "u" ) !== false;
      if( $dbr->numrows > 0 ){
        $return .= "<ul class=\"memberlist\">\n";
        while( $member = $dbr->fetchRow() ){
          $class = "";
          foreach( $l->aFields as $k => $f ){
            $c = $membertable."_".$joinedto."_".$f->columnname;
            if( array_key_exists( $c, $member ) ) $l->aFields[$f->columnname]->value = $member[$c];
          }
          $l->id = $member[$l->tablename."_id"];
          $fieldhtml = "";
          foreach( $m->aFields as $k => $f ){
            if( array_key_exists( $f->columnname, $member ) ) $m->aFields[$k]->value = $member[$f->columnname];
            if( !$f->display ) continue;
            if( $f->columnname == "name" ) continue;
            if( !empty( $member[$f->columnname] ) && $member[$f->columnname] > 0 ) $class .= $f->columnname." ";
            if( preg_match( "/_id$/", $f->columnname ) ) continue;
            if( $f->type != "rdo" ) $fieldhtml .= "<div><span class=\"name\">".$f->displayname."</span> <span class=\"value\">".$f->toString()."</span></div>\n";
          }
          if( $can_edit ){
            // Add an edit link along with this item
            $name = "<a href=\"".SITE_ROOT.$l->tablename."/edit/".$l->id."\">".h($l->getName())."</a>";
          }else{
            $name = h($l->getName());
          }
          $return .= "            <li class=\"".$class."\">".$name.$fieldhtml."</li>\n";
        }
        $return .= "          </ul>\n";
      }
      // $dbr->close();
      // unset( $dbr );
      if( $this->parentid != 0 && $this->editable ){ 
        $return .= " <a class=\"edit\" href=\"".SITE_ROOT.$this->columnname."/edit/".camelToUnderscore( $this->parentmodel )."/".$this->parentid."\">edit</a>";
      }else{
        $return .= " Save this ".$this->parent_displayname." before adding any ".$this->displayname;
      }
      addLogMessage( "End", $this->name."->toField()" );
      return $return;
    }    
    
    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
      addLogMessage( "Start", $this->name."->toSearchField()" );
      $membertable = camelToUnderscore( substr( $this->name, 3 ) );
      require_once( "models/".$membertable.".model.class.php" );
      $m = substr( $this->name, 3 );
      $m = Cache::getModel( $m );
      $comma = "";
      $return = "";
      $l = $m->left == $this->parentmodel ? $m->right : $m->left;
      $joinedto = camelToUnderscore( $l );
      require_once( "models/".$joinedto.".model.class.php" );
      $l = Cache::getModel( $l );
      $this->listby = $l->listby;
      $this->listsql = "SELECT CONCAT( ".str_replace( ",", ", ' ', ", $l->listby )." ) as name, id FROM ".$joinedto." ORDER BY ".$l->listby;
      $this->setListItemsFromSql( false );
          
      $select = new SelectRenderer();
      $select->title = $this->displayname;
      $select->name = $this->htmlname;
      $select->id = $el_id;
      $select->multiselect = true;
      
      // Manually set list items
      addLogMessage( "From list items", $this->name."->toField()" );
      $select->listitems = $this->listitems;
      $select->listlabels = $this->listlabels;
      
      // The optgroup label
      $lastlabel = "";
      $itemcount = 0;
      foreach( $select->listitems as $key => $value ){
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
      $rtn = $select->render();
      addLogMessage( "End", $this->name."->toSearchField()" );
      return $rtn;
    }

    /**
    * Render the field as uneditable HTML
    * @param string $options Currently unused
    * @param string $el_id HTML id to use
    * @return string 
    */
    function toHtml( $options="", $el_id="" ){
      $membertable = camelToUnderscore( substr( $this->name, 3 ) );
      $classfile = $membertable.".class.php";
      /*
      if( !file_exists( $classfile ) ){ 
        echo "No class file <strong>$classfile</strong><br>";
        break;
      }
      */
      // require_once( $classfile );
      $m = substr( $this->name, 3 );
      $m = Cache::getModel( $m );
      $m->listby= "id";
      if( $this->parent_displayname == "" || $this->parent_tablename == "" ){
        $p = $this->parentmodel;
        $p = Cache::getModel( $p );
        $this->parent_displayname = $p->displayname;
        $this->parent_tablename = $p->tablename;
      }
      $db = $m->getAll( "WHERE ".$this->columnname.".".$this->parent_tablename."_id = ".$this->parentid );
      $comma = "";
      $str = "";
      require_once( $this->columnname.".class.php" );
      $o = substr( $this->name, 3 );
      $o = Cache::getModel( $o );
      $joinedto = $o->left == $this->parentmodel ? $o->right : $o->left;
      $joinedto = camelToUnderscore( $joinedto );
      $key = $membertable."_".$joinedto."_name";
      if( $db->numrows > 0 ){
        $str .= "<ul class=\"".$this->type."\">\n";
        while( $member = $db->fetchRow() ){
          // if( $member[$key] == "" ) continue;
          $str .= "      <li>".h($member[$key])."</li>\n";
        }
        $str .= "    </ul>\n";
      }
      // $db->close();
      // unset( $db );
      return $str;
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
      
      if( $is_search ){
        $this->value = preg_split( "/,/", $value ); // OK
      }else{
        $this->value = $value;
      }
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      addLogMessage( "End", $this->name."->set()" );
    }    
    
    /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      addLogMessage( "Start", $this->name."->toString()" );
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
      if( $this->value != "" ) return $this->value;
      $membertable = camelToUnderscore( substr( $this->name, 3 ) );
      $classfile = "models/".$membertable.".model.class.php";
      require_once( $classfile );
      $m = substr( $this->name, 3 );
      $m = Cache::getModel( $m );
      foreach( $m->aFields as $key => $f ){
        $m->aFields[$key]->autojoin = true;
      }
      $m->listby= "id";
      if( $this->parent_displayname == "" || $this->parent_tablename == "" ){
        $p = $this->parentmodel;
        $p = Cache::getModel( $p );
        $this->parent_displayname = $p->displayname;
        $this->parent_tablename = $p->tablename;
      }
      $where = "WHERE ".$this->columnname.".".$this->parent_tablename."_id = ".$this->parentid;
      $db = $m->getAll( $where );
      $comma = "";
      $str = "";
      require_once( "models/".$this->columnname.".model.class.php" );
      $o = substr( $this->name, 3 );
      $o = Cache::getModel( $o );
      $joinedto = $o->left == $this->parentmodel ? $o->right : $o->left;
      $joinedto = camelToUnderscore( $joinedto );
      $key = "name";
      if( $db->numrows > 0 ){
        $str .= "\n";
        while( $member = $db->fetchRow() ){
          $str .= " ".$member[$key].";\n";
        }
      }
      addLogMessage( "End", $this->name."->toString()" );
      return $str;
    }
    
  }
?>
