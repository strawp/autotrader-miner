<?php
  /**
  * Report generating class
  */
  require_once( "core/settings.php" );
  require_once( "lib/mailer.class.php" );
  require_once( "core/reportable.interface.php" );
  require_once( "core/feature.interface.php" );
  require_once("lib/wkpdf.class.php");
  class Report implements iReportable {

    function __construct(){
      $this->aGraphs = array();
      $this->aOptions = array();
      $this->html = "";
      $this->pdf = null;   // DOM PDF object
      $this->id = preg_replace( "/_report$/", "", camelToUnderScore(get_class($this)));      // The report shortname
      $this->title = "";
      $this->filename = ""; // Place to write PDF filename, do this in compile()
      $this->aCssFiles = array();
      $this->aJsFiles = array();
      $this->aTables = array(); // If the report contains tables, the data is stored in here
      $this->classname = ""; // CSS class to put on content div for report
      $this->debug = false;
    }
    
    /**
    * Get the unique name (classname minus ...Report)
    */
    function getName(){
      return preg_replace( "/Report$/", "", get_class( $this ) );
    }
    
    /**
    * Do this after setting all the options and everything, as if it was executed on a page load in a conventional scripted page
    */
    function compile(){
      $this->setHtml("");
    }
    
    /**
    * Set the report HTML content
    * @param HTML $html
    */
    function setHtml( $html ){
      $this->html = $html;
    }
    
    /**
    * Add a reference to a CSS file, needed for non-web display
    * @param string $file File location to include
    */
    function addCssFile( $file ){
      $this->aCssFiles[] = $file;
    }
    
    /**
    * Add a reference to a JS file, to be included on the web display of this report
    * @param string $file filename in /js/ to include
    */
    function addJsFile( $file ){
      $this->aJsFiles[] = $file;
    }
    
    
    /**
    * Add a graph object by reference on the report page
    * @param string $name the unique name of the graph on this report
    * @param Object $graph the graph object to add
    */
    function addGraph( $graph ){
      $this->aGraphs[$graph->id] = $graph;
    }
    
    /**
    * Get a graph by ID
    */
    function getGraph( $id ){
      if( !isset( $this->aGraphs[$id] ) ) return false;
      return $this->aGraphs[$id];
    }
    
    /**
    * Get array of all graphs
    */
    function getGraphs(){
      if( !isset( $this->aGraphs ) ) $this->aGraphs = array();
      return $this->aGraphs;
    }
    
    /**
    * Get list of graph IDs
    */
    function getGraphIds(){
      return array_keys( $this->aGraphs );
    }
    
    
    /**
    * Set the kind of options that might be in the URL if this was a web page
    * @param array $aOptions Array of option values keyed by option name
    */
    function setOptions( $aOptions=array() ){
      $this->aOptions = $aOptions;
    }
    
    /**
    * Set the options from a URL param format string
    */
    function setOptionsFromString( $str ){
      parse_str( $str, $a );
      $this->setOptions($a);
    }
    
    /**
    * Get the options URL formatted string
    */
    function getOptionsString(){
      $str = "";
      $amp = "";
      if( sizeof( $this->aOptions ) > 0 ){
        foreach( $this->aOptions as $k => $v ){
          $str .= $amp.urlencode( $k )."=".urlencode( $v );
          $amp = "&";
        }
      }
      return $str;
    }
    
    /**
    * Get the entire options list as slash separated URL
    */
    function getOptionsUrl(){
      $str = SITE_BASE."report/".$this->id;
      foreach( $this->aOptions as $k => $v ){
        if( $k == "" ) continue;
        if( $k == "report" ) continue;
        if( $k == "_contentonly" ) continue;
        $str .= "/".htmlentities( $k )."/".htmlentities( $v );
      }
      return $str;
    }
    
    /**
    * Get the URL for exporting a table from a report to TSV
    */
    function getTableExportUrl( $tablename ){
      return $this->getOptionsUrl()."/gettable/".$this->getTable($tablename)->name;
    }
    
    /**
    * Render the table export link HTML
    */
    function getTableExportLink( $tablename ){
      return "<p class=\"tableoption\"><a class=\"export\" href=\"".$this->getTableExportUrl($tablename)."\">Export</a></p>\n";
    }
    
    /**
    * Set a single option
    */
    function setOption($name,$value){
      $this->aOptions[$name] = $value;
    }
    
    /**
    * Get a single option
    */
    function getOption( $name ){
      if( isset( $this->aOptions[$name] ) ) return $this->aOptions[$name];
      return false;
    }
    
    /**
    * Get HTML that can be output directly to the web 
    * @return HTML $html
    */
    function renderWebPage(){
      $html = $this->replaceGraphsWithUrls();
      return $html;
    }
    
    /**
    * Get inline CSS element
    */
    function getInlineCssElement(){
      $str = "";
      foreach( $this->aCssFiles as $file ){
        if( file_exists( SITE_WEBROOT."/css/".$file ) ) $str .= file_get_contents( SITE_WEBROOT."/css/".$file );
      }
      $str .= $this->getCustomCss();
      $str = "<style>".$str."</style>";
      return $str;
    }
    
    function getCustomCss(){
      return "";
    }
    
    /**
    * Switch out graph placeholders for web URLs
    * @return HTML
    */
    function replaceGraphsWithUrls( $mode="web" ){
      $html = $this->html;
      $aMethods = array(
        "datauri" => "getDataUriImgElement",
        "temp" => "getLocalTempImgElement",
        "web" => "getWebImgElement"
      );
      if( !array_key_exists( $mode, $aMethods ) ) $mode == "web";
      $method = $aMethods[$mode];
      if( sizeof( $this->aGraphs ) > 0 ){
        foreach( $this->aGraphs as $name => $graph ){
          if( $graph->rendermode && array_key_exists( $graph->rendermode, $aMethods ) ){ 
            $method = $aMethods[$graph->rendermode];
          }
          $html = str_replace( ":".$name.":", $graph->$method(), $html );
        }
      }
      return $html;
    }
  
    /**
    * Get the report HTML wrapped in headers, required <div>, css
    * @return string
    */
    function getWrappedHtml($html=""){
      if( $html == "" ) $html = $this->html;
      $html = "<div id=\"content\" class=\"report ".$this->id."\">".$html."</div>";
      $html = "<html><head>".$this->getInlineCssElement()."</head><body>".$html."</body></html>";
      return $html;
    }
    
    /**
    * Add a table to the report
    */
    function addTable( $table ){
      $this->aTables[$table->name] = $table;
    }
    
    function hasTable($tablename){
      return isset( $this->aTables[$tablename] );
    }
    
    /**
    * Get table data array
    */
    function getTable($tablename){
      if( !isset( $this->aTables[$tablename] ) ) return false;
      return $this->aTables[$tablename];
    }
    
    /**
    * Delete all temp generated files associated with this report
    */
    function deleteTempFiles(){
      // Graphs
      foreach( $this->aGraphs as $graph ){
        if( $graph->tempfile != "" && file_exists( $graph->tempfile ) ){
          $graph->deleteTempFile();
        }
      }
    }
    
    /**
    * Render report to ->pdf
    */
    function renderPdf(){
      if( $this->debug ) echo get_class($this)."->renderPdf()\n";
      $this->addCssFile( "pdf_fix.css" );
      $html = $this->replaceGraphsWithUrls("temp");
      $html = $this->getWrappedHtml($html);
      if( $this->debug ) echo "Size of HTML string generated: ".strlen( $html )."\n";
      if( $this->debug ) echo "Creating WKPDF class\n";
      $pdf = new WKPDF();
      $pdf->debug = $this->debug;
      if( $this->debug ) echo "Setting HTML\n";
      $pdf->set_html( $html );
      if( $this->debug ) echo "Rendering...\n";
      $pdf->render();
      $this->pdf = $pdf;
      $this->deleteTempFiles();
    }
    
    /**
    * Write the PDF of this report to a file location
    * @param string $file
    */
    function writePdf( $filename ){
      if( !isset( $this->pdf ) ) $this->renderPdf();
      if( $this->filename == "" ) $this->filename = $filename;
      // file_put_contents($filename, $this->pdf->output());
      if( $this->debug ) echo "writePdf( $filename );\n";
      $this->pdf->output( WKPDF::$PDF_SAVEFILE, $filename );
    }
    
    /** 
    * Send this report as an HTML email
    * @param mixed $to person to send it to, can be (int)user ID, (string)user name, (string)email address
    * @param string $subject optional subject, defaults to report title
    */
    function sendHtmlEmail( $to, $subject="" ){
      $this->addCssFile( "html_email_fix.css" );
      $html = $this->replaceGraphsWithUrls("temp");
      $html = "<p>Please find below the report you requested.</p>\n\n"
        ."<p>You can manage which email reports you receive under <a href=\"".SITE_BASE."wizard/my_profile\">\"My Profile\"</a></p>\n".$html;
      $html = $this->getWrappedHtml($html);
      $mailer = new Mailer();
      $mailer->MsgHTML( $html );  // This method mime-embeds local image references
      if( $subject == "" ) $subject = $this->title;
      $mailer->setSubject( $subject );
      $mailer->AddRecipient( $to );
      $mailer->Send();
    }
    
    /**
    * Send this report as a PDF attachment to an email
    * @param mixed $to person to send it to, can be (int)user ID, (string)user name, (string)email address
    * @param string $subject optional subject, defaults to report title
    * @param string $body optional message body for the email
    */
    function sendPdfEmail( $to, $subject="", $body="" ){
      $filename = tempnam(sys_get_temp_dir(), 'rpt');
      if( $this->debug ) echo "Writing temp report PDF to $filename\n";
      if( $this->filename == "" ){
        $this->filename = $this->id.".pdf";
      }
      $this->writePdf( $filename );
      $mailer = new Mailer();
      if( $body == "" ){
        $body = "Please find a PDF of the report \"".$this->title."\" attached.\n\n"
          ."You can manage which email reports you receive under \"My Profile\" ".SITE_BASE."wizard/my_profile";
      }
      if( $subject == "" ) $subject = $this->title;
      $mailer->wrapBody( $body );  
      $mailer->setSubject( $subject );
      $mailer->AddRecipient( $to );
      if( !file_exists( $filename ) && $this->debug ) echo "$filename not found!\n";
      if( $this->debug ) echo "Attaching $filename (".filesize($filename).") as name ".$this->filename."\n";
      $mailer->AddAttachment( $filename, $this->filename );
      $mailer->Send();
    }    
    
    /**
    * Implementations of methods for iReportable
    */
    function getAvailableReportFormats(){
      return array(
        "pdf",
        "html"
      );
    }
    function getAvailableSubscriptionTypes(){
      return array( "periodic", "event", "bookmark" );
    }
    function sendEmailReport($format="",$user,$name=""){
      if( !is_object( $user ) ){
        if( $this->debug ) "User passed isn't a class\n";
        return false;
      }
      if( $this->debug ) echo "$format,".$user->fullName()."\n";
      
      if( $format == "" ){
        $a = $this->getAvailableReportFormats();
        $format = $a[0];
      }else{
      
        // Check report is in list of available report formats
        if( !in_array( $format, $this->getAvailableReportFormats() ) ) return false;
      }
      
      if( $user->Fields->Name == "" ) return false;
      $this->requesting_user = $user;
      
      if( $this->debug ) echo "Compiling...\n";
      $this->compile();
      if( $this->debug ) echo "Compiled \"".$this->title."\"\n";
      
      // Check that the report is OK to send with this optional method
      if( method_exists( $this, "sendEmailReportValidate" ) ){
        if( !$this->sendEmailReportValidate() ){ 
          return false;
        }
      }

      
      $subject = "Report: ".$this->title;
      
      switch( $format ){
        case "pdf":
          if( $name != "" ) $this->filename = preg_replace( "/[^A-Za-z0-9]/", "_", $name ).".pdf";
          $this->sendPdfEmail( $user->getFormattedEmailAddress(), $subject );
          break;
          
        case "html":
          $this->sendHtmlEmail( $user->getFormattedEmailAddress(), $subject );
          break;
      }
      return true;
    }
    function getDisplayName(){
      return $this->title;
    }
    function userHasReadAccess($user=null){
      return true;
    }
    /**
     * Send an instance of this report to each member of the supplied group code
     * 
     * @param string $group
     * @param string $format
     * @return void
     */
    function sendToUsersInGroup( $group="", $format="" ){
      
      $ug = new UserGroup();
      $ug->getByCode($group);
      $aUserIds = $ug->getAllUserIds();
      
      foreach( $aUserIds as $id ){
        $u = new User();
        $u->get( $id );
        if( !$u->id ) continue;
        if( !$this->userHasReadAccess( $u ) ) continue;
        $this->sendEmailReport( $format, $u );
      }
    }
  }
?>