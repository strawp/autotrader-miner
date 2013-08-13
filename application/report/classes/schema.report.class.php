<?php

  /**
  * Top level description of the entire DB schema / forms
  */
  class SchemaReport extends Report implements iFeature {
    function __construct(){
      parent::__construct();
      $this->title = SITE_NAME." Database Schema";
      $this->addCssFile( "reports.css" );
    }
    function getFeatureDescription(){
      return "Shows a top level description of the entire DB schema and forms";
    }
    
    
    function compile(){
      $html = "";
      $html .= "<h2>".$this->title."</h2>\n";
      
      // Get list of all models
      $modeldir = opendir( SITE_WEBROOT."/models" );
      $aModels = array();
      while( $file = readdir( $modeldir ) ){
        if( !preg_match( "/^(.*)\.model\.class\.php/", $file, $m ) ) continue;
        $name = underscoreToCamel( $m[1] );
        $m = Cache::getModel( $name );
        if( !$m ) continue;
        if( !$m->hastable ) continue;
        if( $m->dbclass != "DB" ) continue;
        $aModels[] = $m;
      }
      closedir( $modeldir );
      
      $db = Cache::getModel("DB");
      
      // Get list of user groups
      $db->query( "SELECT * FROM user_group" );
      $aUserGroups = array();
      while( $row = $db->fetchRow() ){
        $aUserGroups[$row["code"]] = $row;
      }
      
      $html .= "<p>Showing all ".sizeof( $aModels )." current ".SITE_NAME." database tables as of ".date( SITE_DATEFORMAT )."</p>\n";
      $html .= "<ol>\n";
      foreach( $aModels as $m ){
        $html .= "  <li>";
        $tbl = new Table();
        $tbl->classname .= " list table";
        $tr = new TableRow("name");
        $tr->addCell( new TableCell( "Name", "name", true ) );
        $tr->addCell( new TableCell( $m->displayname ) );
        $tbl->addRow( $tr );
        
        $tr = new TableRow("object");
        $tr->addCell( new TableCell( "Object", "object", true ) );
        $tr->addCell( new TableCell( get_class( $m ) ) );
        $tbl->addRow( $tr );
        
        $tr = new TableRow("table");
        $tr->addCell( new TableCell( "Table", "table", true ) );
        $tr->addCell( new TableCell( $m->tablename ) );
        $tbl->addRow( $tr );
        
        $tr = new TableRow("access");
        $tr->addCell( new TableCell( "Access", "access", true ) );
        $access = "";
        $c = "";
        // pre_r( $m->aAuth );
        foreach( $m->aAuth as $k => $auth ){
          if( $k === "groups" ){
            foreach( $m->aAuth[$k] as $group ){
              if( !isset( $aUserGroups[$group["name"]] ) ) continue;
              $access .= $c."<a href=\"".SITE_BASE."user_group/edit/".intval($aUserGroups[$group["name"]]["id"])."\">".h( $aUserGroups[$group["name"]]["name"] )."</a> (".strtoupper($group["access"]).")";
              $c = ", ";
            }
          }else{
            $access .= $c.$auth[0].": ".$auth[1]." (".strtoupper($auth[2]).")";
          }
        }
        if( $access == "" ) $access = "No default access";
        $access .= " (may also be set at runtime depending on other factors)";
        $tr->addCell( new TableCell( $access ) );
        $tbl->addRow( $tr );
        
        if( isset( $this->aOptions["rowcount"] ) ){
          $tr = new TableRow("rowcount");
          $tr->addCell( new TableCell( "Row count", "rowcount", true ) );
          $db->query( "SELECT COUNT(*) as num FROM ".$m->tablename );
          $row = $db->fetchRow();
          $tr->addCell( new TableCell( h($row["num"] ) ) );
          $tbl->addRow( $tr );
        }
        
        $tr = new TableRow("notes");
        $tr->addCell( new TableCell( "Notes", "notes", true ) );
        $notes = method_exists( $m, "getFeatureDescription" ) ? "<p>".$m->getFeatureDescription()."</p>" : "";
        
        // Which models link to this one?
        $aLinked = array();
        foreach( $aModels as $o ){
          foreach( $o->aFields as $field ){
            if( $field->belongsto == $m->name ) $aLinked[] = array( "model" => get_class( $o ), "field" => $field->name );
          }
        }
        if( sizeof( $aLinked ) > 0 ){
          $notes .= "<p>Linked to from:</p>\n<ul>\n";
          foreach( $aLinked as $link ){
            $notes .= "  <li>".$link["model"]."-&gt;".$link["field"]."</li>\n";
          }
          $notes .= "</ul>\n";
        }
        if( $m->name == "MemberInterface" ){
          $m->init();
          $notes .= "<p>This table provides a many:many link between ".$m->left." and ".$m->right."<p>\n";
        }
        
        $tr->addCell( new TableCell( $notes ) );
        $tbl->addRow( $tr );
    
        $html .= "<h3>".get_class( $m )."</h3>\n";
        $html .= $tbl->getHtml();
        $html .= "<h4>Fields in ".get_class( $m )."</h4>\n";
        $tbl = new Table();
        $tbl->classname .= " list fields";
        $tbl->addHeaderNames( array( "Name", "Field", "Field Type", "Column", "Data Type", "Help text", "Notes" ) );
        foreach( $m->aFields as $k=>$f ){
          $tr = new TableRow( $k );
          $tr->addCell( new TableCell( $f->name, "name" ) );
          $tr->addCell( new TableCell( $f->displayname, "field" ) );
          $tr->addCell( new TableCell( $f->getTypeName(), "field_type" ) );
          $tr->addCell( new TableCell( $f->hascolumn ? $f->columnname : "n/a", "column" ) );
          $tr->addCell( new TableCell( $f->hascolumn ? $f->getDataType() : "n/a", "data_type" ) );
          $tr->addCell( new TableCell( $f->helphtml, "help_text" ) );
          $notes = "";
          if( $f->belongsto != "" ) $notes .= "<p>Foreign key which links to ".$f->belongsto."</p>";
          if( !$f->hascolumn ){ 
            $tr->classname .= " nocolumn";
          }
          if( $f->type == "rpt" || $f->type == "chd" ){
            $notes .= "<p>Lists associated items in ".$f->linksto." in a tab";
            if( $f->type == "rpt" ) $notes .= " and provides form for quickly adding items to that list.</p>\n";
            $notes .= "</p>\n";
          }
          if( $f->type == "ajx" ){
            $notes .= "<p>Renders a tab with on the main form page which fetches another page in the background</p>";
          }
          if( $f->type == "grd" ){
            $l = $f->getLinkstoMemberModel();
            $notes .= "<p>Renders a grid of fields taken from ".get_class( $l )."</p>";
          }
          if( sizeof( $f->aUsesFields ) > 0 ){
            $notes .= "<p>The value of this field is calculated from the values of other fields:</p>";
            $notes .= "<ul>\n";
            foreach( $f->aUsesFields as $u ){
              if( !isset( $m->aFields[$u] ) ) continue;
              $notes .= "<li>".$m->aFields[$u]->name."</li>\n";
            }
            $notes .= "</ul>\n";
          }
          $tr->addCell( new TableCell( $notes, "notes" ) );
          $tbl->addRow( $tr );
        }
        $html .= $tbl->getHtml();
        $html .= "</li>\n";
      }
      $html .= "</ol>\n";
      
      
      $this->setHtml( $html );
    }
    function getCustomCss(){
      return "
        table {
          width: 100%;
        }
        table.table th {
          width: 15%;
        }
        table.fields td,
        table.fields th {
          width: 16%;
        }
        table.fields td.column,
        table.fields th.column,
        table.fields td.field_type,
        table.fields th.field_type {
          width: 8%;
        }
        table.fields td.notes,
        table.fields th.notes {
          width: 25%;
        }
        table.fields tr.nocolumn {
          color: gray;
        }
        ul, ol {
          margin: 0 0 0 1em;
          padding: 0 0 0 2em;
        }
      ";
    }
    
    function userHasReadAccess($user=null){
      if( !$user || $user->id == 0 ) return false;
      return $user->isAdmin();
    }
  }
?>