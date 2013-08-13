<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  require_once( "core/fields/int.field.class.php" );
  class OdtField extends IntField{
    function OdtField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->defaultorderdir = "desc";
      $this->length = 8;
      switch( $this->name ){
        case "odtCreatedAt":
          $this->display = false;
          break;
        case "odtUpdatedAt":
          $this->display = false;
          break;
      }
      $this->dbdatemask = "YYYYMMDD";
      $this->valdatemask = "Ymd";
      if( $this->type == "odt" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Oracle Date";
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
      foreach( $params as $k => $v ){
        $$k = $v;
      }
      if( is_array( $this->value ) ){
        if( $this->value[0] != "" ) $data .= $this->parent_tablename.".".$this->columnname." > to_date( '".$this->value[0]."', '".$this->dbdatemask."' )";
        if( $this->value[0] != "" && $this->value[1] > $this->value[0] ) $data .= " AND ";
        if( $this->value[1] > $this->value[0] ) $data .= $this->parent_tablename.".".$this->columnname." < to_date( '".$this->value[1]."', '".$this->dbdatemask."' )";
      }
      else{
        if( $named ) $data .= " = ";
        if( $this->belongsto != "" && ( $this->value == 0 || $this->value == "" ) ) $data .= "NULL";
        else $data .= empty( $this->value ) ? 0 : "to_date( '".$this->value."', '".$this->dbdatemask."' )";
      }
      return $data;
    }
    
    /**
    * Date-specific defaults
    */
    function setDefault(){
      if( $this->default != "" ){ 
        $this->value = $this->default;
        $this->value = intval( date( $this->valdatemask, strtotime( $this->default ) ) );
      }
    }    
    
    /**
    * Get the SQL default value of the field
    * @return mixed
    */
    function getDefault(){
      $return = "";
      if( $this->default != "" ){
        if( $this->default == "now" ){
          // $return .= " default ".time();
        }else{
          $return .= " default ".intval( $this->default );
        }
      }
      return $return;
    }
    
    /**
    * Date validate
    */
    function validate(){
      parent::validate();
      if( $this->value < 0 ){
        return array( 
          "message" => $this->displayname." is not in a valid date format",
          "fieldname" => $this->name,
          "columnname" => $this->columnname,
          "type" => "error"
        );
      }
    }
    
    /**
    * Render dtm field
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
    function toOdtField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      if( $this->value == "" && $this->default == "now" ) $this->value = intval( date( $this->valdatemask, time() ) );
      $v = $this->toString();
      $v = htmlentities( $v );
      // $v = str_replace( '"', "&quot;", $v );
      $return = "<input title=\"".$this->displayname."\" type=\"text\" ";
      $return .= "class=\"".$this->type." text\" id=\"".preg_replace( "/[\]\[]/", "_", $el_id )."\" name=\"".$this->htmlname."\" value=\"".$v."\" ".$options." ".$disabled." />";
      return $return;
    }
    
    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
      return $this->renderRangedSearchField();
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
      
      switch( $type ){
      
        default:
          
          if( $is_search ){
            $this->value = preg_split( "/,/", $value ); // OK
            break;
          }
          
          if( !is_array( $value ) ){
            $value = trim( $value );
          }
          
          if( $value == "" ){ 
            $this->value = "";
            break;
          }
          
          if( !is_array( $value ) ){
            
            $date = "";
            
            // This assumes an English date format and spoon feeds it to PHP
            if( preg_match( "/(\d+)\/(\d+)\/(\d+)/", $value, $a ) ){
              $this->value = intval( date( $this->valdatemask, mktime( 12, 0, 0, $a[2], $a[1], $a[3] ) ) );
              break;
            }
            
            // Universal timestamp, apparently not all that universal
            if( preg_match( "/(\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})((\+|\-)(\d{2}):(\d{2}))?/", $value, $a ) ){
              $this->value = intval( date( $this->valdatemask, mktime( $a[4], $a[5], $a[6], $a[2], $a[3], $a[1] ) ) );
              break;
            }
            if( $date == "" ) $this->value = intval( date( $this->valdatemask, strtotime( $value ) ) );
            else $this->value = $date;
          }else{
            
            $this->value = array();
            foreach( $value as $key => $v ){
              
              
              // This assumes an English date format and spoon feeds it to PHP
              if( preg_match( "/(\d+)\/(\d+)\/(\d+)/", $v, $a ) ){
                $this->value[$key] = intval( date( $this->valdatemask, mktime( 12, 0, 0, $a[2], $a[1], $a[3] ) ) );
              }
              
              // Universal timestamp, apparently not all that universal
              if( preg_match( "/(\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})((\+|\-)(\d{2}):(\d{2}))?/", $v, $a ) ){
                $this->value[$key] = intval( date( $this->valdatemask, mktime( $a[4], $a[5], $a[6], $a[2], $a[3], $a[1] ) ) );
              }
              if( !isset($this->value[$key]) ){
                if( $v != "" ) $this->value[$key] = intval( date( $this->valdatemask, strtotime( $v ) ) );
                else $this->value[$key] = 0;
              }
            }
            if( intval( $this->value[0] ) == 0 && intval( $this->value[1] ) == 0 ){
              $this->value = "";
            }
          }
          break;
          
      }
      
      // Check the lengths of all the fields
      $this->truncate();
      
      // You've changed, man
      $this->setHaschanged( $original_value );
      addLogMessage( "End", $this->name."->set()" );
    }
    
    
    function setFromDb( $value ){
      $this->set( $value );
    }
    
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
        addLogMessage( "End", $this->name."->toString()" );
        return $return;
      }
      addLogMessage( "End", $this->name."->toString()" );
      if( $this->value == 0 ) return "";
      return date( SITE_DATEFORMAT, strtotime( $this->value ) );
    }    
  }
?>
