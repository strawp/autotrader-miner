<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  require_once( "core/fields/int.field.class.php" );
  class DtmField extends IntField{
    function DtmField( $fieldname, $options="" ){
      $this->Field( $fieldname, $options );
      $this->length = 11;
      $this->defaultorderdir = "desc";
      $this->customsearch = true;
      switch( $this->name ){
        case "dtmCreatedAt":
          $this->display = false;
          break;
        case "dtmUpdatedAt":
          $this->display = false;
          break;
      }
      if( $this->type == "dtm" ) $this->init();
    }
    
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Datetime";
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
        if( $this->value[0] != "" ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." >= ".$this->value[0];
        if( $this->value[0] != "" && $this->value[1] > $this->value[0] ) $data .= " AND ";
        if( $this->value[1] > $this->value[0] ) $data .= camelToUnderscore( $this->parentmodel ).".".$this->columnname." < ".$this->value[1];
      }
      else{
        if( $named ) $data .= " = ";
        if( $this->belongsto != "" && ( $this->value == 0 || $this->value == "" ) ) $data .= "NULL";
        else $data .= empty( $this->value ) ? 0 : $db->escape( $this->value );
      }
      return $data;
    }
    
    /**
    * Set value to now
    */
    function setNow(){
      $this->value = strtotime( "now" );
    }
    
    /**
    * Date-specific defaults
    */
    function setDefault(){
      if( $this->default != "" ){ 
        $this->value = $this->default;
        $this->value = strtotime( $this->default );
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
    function toDtmField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      if( $this->value == "" && $this->default == "now" ) $this->value = time();
      $v = $this->toString();
      $v = htmlentities( $v );
      // $v = str_replace( '"', "&quot;", $v );
      $return = "<input title=\"".$this->displayname."\" type=\"text\" ";
      $return .= "class=\"".$this->type." text\" id=\"".preg_replace( "/[\]\[]/", "_", $el_id )."\" name=\"".$this->htmlname."\" value=\"".$v."\" ".$options." />";
      return $return;
    }
    
    /**
    * Generate HTML to display this field as a search field
    * @param string $options miscellaneous options 
    * @param string $el_id HTML ID
    * @return string HTML of the search field
    */
    function toSearchField( $options="", $el_id="" ){
      $name = "lst".substr( $this->name, 3 )."-Custom";
      $rtn = "<div class=\"custom\"><label for=\"$name\">Date range:</label>";
      $lst = new SelectRenderer( $name );
      $lst->listitems = array(
        "" => "Custom",
        "next year" => "Within the next year",
        "6 months" => "Within the next six months",
        "3 months" => "Within the next three months",
        "next month" => "Within the next month",
        "2 weeks" => "Within the next two weeks",
        "next week" => "Within the next week",
        "tomorrow" => "Before tomorrow",
        "yesterday" => "Since yesterday",
        "last week" => "Within the last week",
        "2 weeks ago" => "Within the last two weeks",
        "last month" => "Within the last month",
        "3 months ago" => "Within the last three months",
        "6 months ago" => "Within the last six months",
        "last year" => "Within the last year",
      );
      $lst->selected = array( $this->customsearchvalue );
      $rtn .= $lst->render();
      $rtn .= "</div>\n";
      $rtn .= $this->renderRangedSearchField();
      return $rtn;
    }
    
    /**
    * Determine whether this field has been searched on or not and set the property issearchedon
    */
    function setIsSearchedOn(){
      parent::setIsSearchedOn();
      if( !empty( $_GET[$this->columnname."-custom"] ) ) $this->issearchedon = true;
      return $this->issearchedon;
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
      if( $is_search && $this->customsearch ){
        if( isset( $_GET[$this->columnname."-custom"] ) ) $this->customsearchvalue = urldecode($_GET[$this->columnname."-custom"]);
        
        // Set later date to now, first date to the one based on the custom value
        if( !empty( $this->customsearchvalue ) ){
          $cv = strtotime( $this->customsearchvalue );
          if( $cv > 0 ){
            if( $cv > time() ){
              $value = time().",".$cv;
            }else{
              $value = $cv.",".time();
            }
          }else{
            $value = ",";
            $this->customsearchvalue = "";
          }
        }
      }
      
      switch( $type ){
      
        default:
        
          if( is_int( $value ) ){
            $this->value = $value;
            break;
          }
          
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
              $this->value = mktime( 12, 0, 0, $a[2], $a[1], $a[3] );
              break;
            }
            
            // Universal timestamp, apparently not all that universal
            if( preg_match( "/(\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})((\+|\-)(\d{2}):(\d{2}))?/", $value, $a ) ){
              $this->value = mktime( $a[4], $a[5], $a[6], $a[2], $a[3], $a[1] );
              break;
            }
            if( !is_int( $value ) ){ 
              $this->value = strtotime( $value );
            }else $this->value = $value;
          }else{
            
            $this->value = array();
            foreach( $value as $key => $v ){
              
              
              // This assumes an English date format and spoon feeds it to PHP
              if( preg_match( "/(\d+)\/(\d+)\/(\d+)/", $v, $a ) ){
                $this->value[$key] = mktime( 12, 0, 0, $a[2], $a[1], $a[3] );
              }
              
              // Universal timestamp, apparently not all that universal
              if( preg_match( "/(\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})((\+|\-)(\d{2}):(\d{2}))?/", $v, $a ) ){
                $this->value[$key] = mktime( $a[4], $a[5], $a[6], $a[2], $a[3], $a[1] );
              }
              if( !isset($this->value[$key]) ){
                if( $v != "" ) $this->value[$key] = strtotime( $v );
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
      return date( SITE_DATETIMEFORMAT, $this->value );
    }
    /**
    * Get the slash-separated URL arguments for returning to a search page
    * ints need either "To" or "From" in a range for searches
    * @return string
    */
    function getUrlArg(){
      $return = "";
      $return .= $this->getCustomUrlArg();
      if( is_array( $this->value ) ){
        if( sizeof( $this->value ) != 2 ) return $return;
        if( $this->value[0] == "" && $this->value[1] == "" ) return $return;
        $c = "";
        $a = $this->columnname."/";
        foreach( $this->value as $v ){
          $a .= $c.$v;
          $c = ",";
        }
        if( $return != "" ) $return .= "/";
        $return .= $a;
      }else{
        if( $this->value > 0 ) $return = $this->columnname."/".$this->value;
      }
      return $return;
    }
    
    /**
    * Override of int getStatsSelectStatement
    */
    function getStatsSelectStatement(){
      return "";
    }
    
  }
?>
