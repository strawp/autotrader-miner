<?php

  /**
  * Generate a report of all features of the system:
  *   - Reports
  *   - Recorded data / list of models
  *   - Wizards
  */
  class FeaturesReport extends Report implements iFeature {
    function __construct(){
      parent::__construct();
      $this->title = SITE_NAME." System Features";
      $this->addCssFile( "reports.css" );
    }
    function getFeatureDescription(){
      return "Creates this report";
    }
    
    
    function compile(){
      $html = "";
      $html .= "<h2>".h($this->title)."</h2>\n";
      
      // Get list of all reports
      $reportdir = opendir( SITE_WEBROOT."/report/classes" );
      $aReports = array();
      while( $file = readdir( $reportdir ) ){
        if( !preg_match( "/^(.*)\.report\.class\.php$/", $file, $m ) ) continue;
        $name = underscoreToCamel( $m[1] );
        $r = Cache::getModel( $name."Report" );
        if( !$r ) continue;
        if( !( $r instanceof iFeature ) ) continue;
        $aReports[] = array( "name" => $r->title, "description" => $r->getFeatureDescription() );
      }
      closedir( $reportdir );
      
      // Get list of all models
      $modeldir = opendir( SITE_WEBROOT."/models" );
      $aStoredData = array();
      while( $file = readdir( $modeldir ) ){
        if( !preg_match( "/^(.*)\.model\.class\.php/", $file, $m ) ) continue;
        $name = underscoreToCamel( $m[1] );
        $m = Cache::getModel( $name );
        if( !$m ) continue;
        if( !( $m instanceof iFeature ) ) continue;
        $aStoredData[] = array( "name" => $m->displayname, "description" => $m->getFeatureDescription() );
      }
      closedir( $modeldir );
      
      // Get list of all wizards
      $dir = opendir( SITE_WEBROOT."/wizards" );
      $aWorkflow = array();
      while( $file = readdir( $dir ) ){
        if( !preg_match( "/^(.*)\.wizard\.class\.php$/", $file, $m ) ) continue;
        $name = underscoreToCamel( $m[1]."Wizard" );
        $w = Cache::getModel( $name );
        if( !$w ) continue;
        if( !( $w instanceof iFeature ) ) continue;
        $aWorkflow[] = array( "name" => $w->name, "description" => $w->getFeatureDescription() );
      }
      closedir( $dir );
      
      // Get list of all connectors
      $dir = opendir( SITE_WEBROOT."/connectors" );
      $aConnectors = array();
      while( $file = readdir( $dir ) ){
        if( !preg_match( "/^(.*)\.connector\.class\.php$/", $file, $m ) ) continue;
        $name = underscoreToCamel( $m[1]."Connector" );
        $w = Cache::getModel( $name );
        if( !$w ) continue;
        if( !( $w instanceof iFeature ) ) continue;
        $aConnectors[] = array( "name" => ucwords( str_replace( "_", " ", $m[1] ) ), "description" => $w->getFeatureDescription() );
      }
      closedir( $dir );

      // Get list of all fields
      $fielddir = opendir( SITE_WEBROOT."/core/fields" );
      $aFields = array();
      while( $file = readdir( $fielddir ) ){
        if( !preg_match( "/^(.*)\.field\.class\.php$/", $file, $m ) ) continue;
        $name = $m[1]."Field";
        $f = Field::create( $name );
        if( !$f ) continue;
        if( !( $f instanceof Field ) ) continue;
        $aFields[] = array( "name" => $f->type, "description" => $f->getTypeName() );
      }
      closedir( $fielddir );
      
      $aFeatureLists = array(
        "Workflow",
        "Reports",
        "StoredData",
        "Connectors",
        "Fields"
      );
      
      $tbl = new Table("Features");
      $tbl->addHeaderNames( array( "Feature", "Description" ) );
      foreach( $aFeatureLists as $f ){
        $name = "a".$f;
        $aList = $$name;
        usort( $aList, array( $this, "sortFeatureList" ) );
        $tr = new TableRow( $f );
        $tr->classname .= " section";
        $cell = new TableCell( camelSplit( $f ) );
        $cell->colspan = 2;
        $tr->addCell( $cell );
        $tbl->addRow( $tr );
        
        // List the stuff
        foreach( $aList as $feature ){
          $tr = new TableRow( str_replace( " ", "_", $feature["name"] )." ".$f );
          $tr->addCell( new TableCell( $feature["name"], "feature" ));
          $tr->addCell( new TableCell( $feature["description"], "description" ));
          $tbl->addRow( $tr );
        }
      }
      $html .= $tbl->getHtml();
      
      $this->setHtml( $html );
    }
  
    function sortFeatureList($a,$b){
      if( $a["name"] > $b["name"] ) return 1;
      if( $a["name"] < $b["name"] ) return -1;
      return 0;
    }
    
    function getCustomCss(){
      return "
        #tblFeatures tr th,
        #tblFeatures tr td {
          padding: 0.25em 0.5em;
          vertical-align: top;
          border-bottom: 1px solid gray;
        }
        #tblFeatures tr td.feature {
          width: 35%;
        }
        #tblFeatures tr.section td {
          padding-top: 1em;
          font-weight: bold;
        }
        #tblFeatures tr.Workflow td {
          background-color: #fffdbb;
        }
        #tblFeatures tr.Reports td {
          background-color: #DAF4D9; 
        }
        #tblFeatures tr.StoredData td {
          background-color: #B2C2F0;
        }
        #tblFeatures tr.Connectors td {
          background-color: #FFCCFF;
        }
        #tblFeatures tr.Fields td {
          background-color: #F3D6D6;
        }
        
      ";
    }
    
    function userHasReadAccess($user=null){
      if( !$user || $user->id == 0 ) return false;
      return $user->isAdmin();
    }
  }
?>