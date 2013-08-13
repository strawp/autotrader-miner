<?php
  require_once( "core/settings.php" );
  require_once( "core/db.class.php" );
  require_once( "core/flash.class.php" );
  require_once( "core/field.class.php" );
  require_once( "core/fields/chd.field.class.php" );
  class RptField extends ChdField{
    function RptField( $fieldname, $options="" ){
      $this->ChdField( $fieldname, $options );
      $this->linksto = substr( $fieldname, 3 );
      $this->linkkey = "";
      $this->rptlinkidfield = "id";
      $this->rptlinkobject = $this->columnname;
      $this->length = 11;
      if( $this->type == "rpt" ) $this->init();
    }
        
    /**
    * Return the name of the field type
    * @return string Name of the field type
    */
    function getTypeName(){
      return "Repeater";
    }

    /**
    * Render rpt field
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
    function toRptField( $options="", $el_id="", $modifiers=DISPLAY_FIELD ){
      addLogMessage( "Start", $this->name."->toRptField()" );
      $o = Cache::getModel( $this->linksto );
      $o->setAction( "new" );
      $o->doInits();
      $return = $this->toChdField( $options, $el_id, $modifiers );
      $o->aFields[$this->linkkey]->value = $this->parentid;
      $o->aFields[$this->linkkey]->editable = false;
      
      // $o->context = $table;
      $o->access = $o->getAuth();
      if( strstr( $o->getAuth(), "c" ) === false ) return $return;
      
      // Form for new items
      $return .= "      <h4 id=\"hdr".$o->name."\">Add a new ".$this->displayname."</h4>\n";
      $return .= "      <a name=\"hdr".$o->name."\" class=\"anchor\"></a>\n";
      addLogMessage( $this->linkkey."=".$this->parentid, $this->name."->toRptField()" );
      $return .= $o->renderForm( "_repeat", "post", "Save and add another");
      addLogMessage( "End", $this->name."->toRptField()" );
      return $return;
    }

  }
?>
