<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  class GrdField extends Field{
    function GrdField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->hascolumn = false;
      $this->length = 11;
      $this->showtotal     = true;      // Show totals in the grid layout
      $this->iscalculated = false;
      if( $this->type == "grd" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Field grid";
    }
    
    function renderUneditable( $el_id="", $modifiers=DISPLAY_FIELD ){
      return $this->toGrdField( "", $el_id, DISPLAY_HTML );
    }

    /**
    * Render grd field
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
    function toGrdField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      $o = $this->getLinkstoModel();
      if( !$o ) return;
      
      $where = "";
      if( array_key_exists( "is_visible", $o->aFields ) ){
        $where .= "WHERE is_visible = 1";
      }
      $dbr = $o->getAll($where);
      
      $where = "WHERE ".$this->parent_tablename."_id = ".$this->parentid;
      $l = $this->getLinkstoMemberModel();
      if( !$l ) return false;
      $a = array();
      foreach( $l->aFields as $key => $field ){
        if( ( DISPLAY_HTML & $modifiers ) == DISPLAY_HTML ){ 
          $l->aFields[$key]->editable = false;
        }
        $l->aFields[$key]->autojoin = true;
        $a[] = $key;
      }
      $l->listby = implode(",",$a);
      $ldbr = $l->getAll( $where );
      
      // Re-key values by year ID
      $aTmp = array();
      while( $value = $ldbr->fetchRow() ){
        $aTmp[$value[$o->tablename."_id"]] = $value;
      }
      // $ldbr->close();
      // unset( $ldbr );
      $aValues = $aTmp;
      
      $tbl = new Table();
      $tbl->classname = "grd";
      $tbl->addHeaderName("");
      
      // Render grid of that junk
      $aTotals = array();
      
      foreach( $l->aFields as $key => $field ){
        if( !$field->display ) continue;
        if( $field->columnname == "id" ) continue;
        $tbl->addHeaderName($field->displayname,$field->columnname);
      }
      $dbr->dataSeek( 0 );
      while( $aColumns = $dbr->fetchRow() ){
        $tr = new TableRow();
        $tr->classname .= " values";
        $row = array_key_exists( $aColumns["id"], $aValues ) ? $aValues[$aColumns["id"]] : array();
        $cols = 0;
        $tr->addCell( new TableCell( htmlentities( $aColumns["name"] ), htmlentities( $aColumns["name"] ), true ) );
        
        // Fields associated with this row
        $fieldcount = 0;
        foreach( $l->aFields as $key => $field ){
          if( !$field->display ) continue;
          if( $field->columnname == "id" ) continue;
          if( array_key_exists( $field->columnname, $row ) ) $l->aFields[$key]->value = $row[$field->columnname];
          else $l->aFields[$key]->value = "";
        }
        // $l->doCalculations();
        foreach( $l->aFields as $key => $field ){
          if( !$field->display ) continue;
          if( $field->columnname == "id" ) continue;
          $n = "";
          $n = $this->htmlname."[".$aColumns["id"]."][".$field->columnname."]";
          $field->htmlname = $n;
          // $field->editable = $this->editable;
          $cols++;
          if( !array_key_exists( $key, $aTotals ) ){
            $aTotals[$key] = Field::create( $field->name );
          }
          $aTotals[$key]->value += $field->value;
          $fieldcount++;
          $tr->addCell( new TableCell( $field->toField( '', '', $modifiers ), $field->columnname ) );
        }
        $cols++;
        $tbl->addRow( $tr );
      }
      
      // Totals row
      if( $this->showtotal ){
        $tr = new TableRow("totals");
        $tr->addCell( new TableCell( "Total", true ) );
        foreach( $l->aFields as $key => $field ){
          if( !$field->display ) continue;
          if( $field->columnname == "id" ) continue;
          $total = $aTotals[$key];
          if( $field->type == "csh" ){
            $cell = new TableCell( "<var>".htmlentities( $total->toString() )."</var>", $key);
          }else{
            $cell = new TableCell( "", $key );
          }
          $cell->classname .= " ".$total->type;
          $tr->addCell( $cell );
        }
        $tbl->addRow( $tr );
      }
      if( $this->editable && SessionUser::isAdmin() ){   
        $tr = new TableRow();
        $cell = new TableCell( "<a href=\"".SITE_ROOT.$this->linksto."\" class=\"more\">Show more ".plural( $o->displayname )."</a>", "more" );
        $cell->colspan = $cols;
        $tr->addCell( $cell );
        $tbl->addRow( $tr );
      }

      unset( $o );
      addLogMessage( "End", $this->name."->toGrdField()" );
      return $tbl->getHtml();
    }
    
    /**
    * Set ->value on the field with some parsing depending on field type
    * @param mixed $value
    * @param bool $is_search True if the value has been posted from a search page
    */
    function set( $value, $is_search=false ){
      addLogMessage( "Start", $this->name."->set()" );
      if( !$this->editable && !$is_search ) return;
      
      $original_value = $this->value;

      /*
        $value is an array keyed by the ID of the row in the table you're linking to, which contains an array of any values to go in the joining table
        e.g.
          grdProjectYear[3][cshGrantTotal] = £123123.21
          grdProjectYear[3][strMonkeyName] = "Boris"
          grdProjectYear[8][cshGrantTotal] = £346346.21
          grdProjectYear[1][cshGrantTotal] = £789.21
        
        Need to pull out the hidden values from the DB so that they're not lost on member interface delete->update
      */
      $this->value = $value;
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
    }
    
    
  
    
    /*
      // $aRows = $o->getAll();
      $dbr = $o->getAll();
      unset( $o );
      
      $l = Cache::getModel( $link_model );
      
    }*/
    
    /**
    * Return the field ->value as a string
    * @return string
    * @param array $aData row data to be passed in which the method can use to avoid having to look up foriegn key values etc
    */
    function toString( $aData=array() ){
      addLogMessage( "Start", $this->name."->toString()" );
      
      // Data from join already present?
      if( sizeof( $aData ) > 0 ){
        $return = "";
        foreach( $aData as $c ){
          $return .= $c." ";
        }
        return $return;
      }
      switch( $this->type ){

        default:
          // Work out what this thing is linking to
          if( $this->linksto == "" ) break;
          $table_name = $this->linksto;
          $object_name = underscoreToCamel( $this->linksto );
          
          $parent_table = camelToUnderscore( $this->parentmodel );
          
          $link_model = $this->parentmodel.$object_name;
          $link_table = $parent_table."_".$table_name;
          
          if( file_exists( "../models/".$table_name.".model.class.php" ) && file_exists( "../models/".$link_table.".model.class.php" ) ){
            require_once( "models/".$table_name.".model.class.php" );
            require_once( "models/".$link_table.".model.class.php" );
          }else{
            echo "Class file not found: models/$table_name.model.class.php ";
            break;
          }
          $o = Cache::getModel( $object_name );
          // $aRows = $o->getAll();
          $dbr = $o->getAll();
          unset( $o );
          
          $l = Cache::getModel( $link_model );
          $ldbr = $l->getAll( "WHERE ".$parent_table."_id = ".$this->parentid );
          
          // Re-key values by year ID
          $aTmp = array();
          while( $value = $ldbr->fetchRow() ){
            $aTmp[$value[$table_name."_id"]] = $value;
          }
          $aValues = $aTmp;
          
          // Render grid of that junk
          $aTotals = array();
          $str = "";
          while( $aColumns = $dbr->fetchRow() ){
            // $str .= "\n  ".str_pad( htmlentities( $aColumns["name"] ), 15, " " );
            $row = array_key_exists( $aColumns["id"], $aValues ) ? $aValues[$aColumns["id"]] : array();
            
            $str .= $aColumns["name"].": ";
            
            $cols = 0;
            
            // Fields associated with this row
            foreach( $l->aFields as $key => $field ){
              if( !$field->display ) continue;
              if( $field->columnname == "id" ) continue;
              if( array_key_exists( $field->columnname, $row ) ) $field->value = $row[$field->columnname];
              // $str .= str_pad( trim( $field->toString() ), 15, " " );
              $str .= $field->toString()."\t";
              $cols++;
              if( !array_key_exists( $key, $aTotals ) ){
                $aTotals[$key] = Field::create( $field->name );
              }
              $aTotals[$key]->value += $field->value;
            }
            $cols++;
          }
          
          // Totals row
          /*
          $str .= "\n  Total          ";
          foreach( $aTotals as $total ){
            $str .= str_pad( $total->toString(), 15, " " );
          }
          $str .= "\n";
          */
          // $dbr->close();
          // unset( $dbr );
          return $str;
          break;
      }
    }
    
    
  }
?>
