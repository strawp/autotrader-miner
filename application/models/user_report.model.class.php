<?php
  /*
    AUTO-GENERATED CLASS
    Generated 18 Nov 2011 09:54
  */
  require_once( "core/model.class.php" );
  require_once( "core/db.class.php" );

  class UserReport extends Model implements iFeature {
    
    public static $debug = false;
    
    function getFeatureDescription(){
      return "Allows users to store customised reports or bookmarks within the system to enabled automatic periodic reports in area area they have access to in PDF, Excel or HTML format";
    }
    
    function UserReport(){
      $this->Model( "UserReport" );
      $this->addAuth( "role", "Staff", "cu" );
      $this->displayname = "Personal Report";
      $this->description = "Once you have subscribed to a personal report, you will be emailed the report as often as you have chosen. "
        ."You can edit your subscriptions through the \"<a href=\"".SITE_ROOT."wizard/my_profile/step/2\">Email report subscriptions</a>\" area of your profile.";
      // $this->returnpage = "wizard/my_profile/step/3";
      
      $this->addField( Field::create( "strName" ) );
      if( !SessionUser::isAdmin() ) $opts = ";editable=0;default=".SessionUser::getId();
      else $opts = "";
      $this->addField( Field::create( "lstUserId", "listby=first_name,last_name".$opts ) );
      $this->addField( Field::create( "strSubscriptionType", "helphtml=\"periodic\" or \"event\";required=1" ) ); // Periodic / event
      $f = Field::create( "strFormat", "helphtml=pdf/html/xls;required=1" );
      $f->aUsesFields = array( "subscription_type" );
      $this->addField( $f );
      $this->addField( Field::create( "strUrl", "helphtml=Relative URL of page to run report against;required=1" ) );
      $this->addField( Field::create( "txtColumns", "editable=0;display=0" ) );
      $f = Field::create( "lstFrequencyId", "helphtml=How often to send this report;required=1" );
      $f->listsql = "
        SELECT id, name 
        FROM frequency 
        ORDER BY size
      ";
      $f->aUsesFields = array( "subscription_type" );
      $this->addField( $f );
      $this->addField( Field::create( "dteStartDate", "helphtml=Date to start sending reports. Leave blank to start immediately" ) );
      $this->addField( Field::create( "dtmLastRun", "helphtml=Date this was last successfully run;editable=0" ) );
      $this->inits[] = "setPrivileges";
      $this->calculations[] = "setRequiredFields";
      $this->calculations[] = "setUserFields";
    }
   
    /**
    * Store the user's current selection of columns
    */
    function setUserFields(){
      if( $this->action != "new" ) return false;
      $m = $this->getModelFromUrl();
      if( !$m ) return false;
      if( !method_exists( $m, "setupUserFields" ) ) return false;
      $m->setupUserFields();
      $aFields = $m->aResultsFields;
      if( sizeof( $aFields ) == 0 ) $aFields = array_keys( $m->aFields );
      $this->Fields->Columns->editable = true;
      $this->Fields->Columns = join(",",$aFields);
      $this->Fields->Columns->editable = false;
    }
   
    /**
    * Override the default required fields
    */
    function setRequiredFields(){
      // If the subscription_type is "bookmark", format and frequency are not required
      if( $this->Fields->SubscriptionType == "bookmark" ){
        $this->Fields->FrequencyId->required = false;
        
        $this->Fields->Format->required = false;
        
        $this->Fields->StartDate->display = false;
        if( $this->action != "search" ){
          $this->Fields->LastRun->display = false;
          $this->Fields->Format->display = false;
          $this->Fields->FrequencyId->display = false;
        }
        
        $this->description = "Reports saved here will appear in the \"My Areas\" area of <a href=\"".SITE_ROOT."wizard/my_profile/step/3\">your profile</a> for accessing later.";
        // $this->returnpage = "wizard/my_profile/step/3";
      }else{
        $this->Fields->FrequencyId->required = true;
        $this->Fields->FrequencyId->display = true;
        $this->Fields->Format->required = true;
        $this->Fields->Format->display = true;
        
        // $this->returnpage = "wizard/my_profile/step/2";
      }
    }
    
    function setPrivileges(){
      if( intval( SessionUser::getId() ) == intval( $this->Fields->UserId->value ) || SessionUser::isAdmin() ){
        $this->access = "crud";
      }else{
        $this->access = "cr";
        foreach( $this->aFields as $k => $f ){
          $this->aFields[$k]->editable = false;
        }
      }
    }
    
    function user_reportValidate(){
      // Check that the URL parses into something reportable
      $m = $this->getModelFromUrl();
      if( !($m instanceof iReportable) ) {
        $this->aErrors[] = array( 
          "message" => "The URL doesn't relate to a reportable part of ".SITE_NAME, 
          "fieldname" => "strUrl" 
        );
        return false;
      }
      
      // Check that the user has a user name
      $user = $this->Fields->UserId->getBelongstoModel(true);
      if( $user->Fields->Name == "" ){
        $this->aErrors[] = array( 
          "message" => "The chosen user doesn't have a valid known email address", 
          "fieldname" => "lstUserId" 
        );
      }
      
      // Check that the model supports the chosen format
      if( $this->Fields->SubscriptionType != "bookmark" ){
        $aFormats = $m->getAvailableReportFormats();
        if( !in_array( $this->Fields->Format, $aFormats ) ){
          $msg = "<p>The report format \"".htmlentities( $this->Fields->Format )."\" isn't supported for the chosen report: ".$m->getDisplayName();
          $msg .= ". Please choose one of:</p>\n<ul>\n";
          foreach( $aFormats as $format ){
            $msg .= "  <li>$format</li>\n";
          }
          $msg .= "</ul>\n";
          $this->aErrors[] = array( 
            "message" => $msg, 
            "fieldname" => "strFormat"
          );
          Flash::setHtmlAllowed();
        }
      }
      
      // Check that the subscription type is of a supported type
      $aTypes = self::getAvailableSubscriptionTypes();
      if( !in_array( $this->Fields->SubscriptionType, $aTypes ) ){
        $msg = "<p>The subscription type \"".htmlentities( $this->Fields->SubscriptionType )."\" isn't supported. Please choose one of: </p>\n<ul>\n";
        foreach( $aTypes as $type ){
          $msg .= "  <li>$type</li>\n";
        }
        $msg .= "</ul>\n";
        $this->aErrors[] = array( 
          "message" => $msg, 
          "fieldname" => "strSubscriptionType",
        );
        Flash::setHtmlAllowed();
      }
      
      // Check that user is actually allowed read access to this info
      if( !$m->userHasReadAccess($user) ){
        $this->aErrors[] = array( 
          "message" => "You are not authorised to access ".$m->getDisplayName(), 
          "fieldname" => "strUrl",
        );
      }
    }
    
    /**
    * Run the specified report
    */
    function runReport(){
      $model = $this->getModelFromUrl();
      if( !($model instanceof iReportable) ) return false;
      if( $this->debug ) echo get_class( $model )." is reportable\n";
      $model->debug = self::$debug;
      $user = $this->Fields->UserId->getBelongstoModel(true);
      if( !$model->userHasReadAccess( $user ) ){
        echo "WARNING: Attempted to run ".$model->getDisplayName()." report (".$this->Fields->Url.") for ".$user->getName().", who does not have authorisation. Deleting.\n";
        $this->delete();
        return false;
      }
      echo date("Y-m-d H:i:s").": Sending ".get_class( $model )." ".trim( $this->Fields->Format." " )." report to ".$user->getName()."... ";
      $rtn = $model->sendEmailReport($this->Fields->Format,$user,$this->getName());
      if( $rtn ){
        echo "done. ";
        $this->Fields->LastRun->editable = true;
        $this->Fields->LastRun = "now";
        if( $this->id ){
          if( $this->save() ){
            echo "Saved OK\n";
          }else{
            echo "Save failed\n";
          }
        }
        if( $this->debug ) echo $this->toString();
      }else{
        echo "failed\n";
      }
      return $rtn;
    }
    
    /**
    * Make a model from the current URL
    */
    function getModelFromUrl(){
      $m = self::parseUrlIntoModel( $this->Fields->Url );
      if( self::$debug ) echo "Returning ".get_class( $m )."\n";
      if( ($m instanceof Model) && $this->Fields->Columns != "" ){
        $m->aResultsFields = preg_split( "/,/", $this->Fields->Columns );
      }
      return $m;
    }
    
    /**
    * Get list of all user reports, run the ones which need running
    */
    static function runReports(){
      if( self::$debug ) echo "Running reports\n";
      ini_set( "memory_limit", "500M" );
      
      // Only run on weekdays
      if( date( "N" ) > 5 ) return true;
      
      if( self::$debug ) echo "Day OK\n";
      $db = new DB();
      
      // Get Lookup of frequencies
      $sql = "
        SELECT *
        FROM frequency 
      ";
      $db->query( $sql );
      $aFreq = array();
      while( $row = $db->fetchRow() ){
        $aFreq[$row["id"]] = $row;
      }
      
      // Get list of reports
      $sql = "
        SELECT ur.*, f.relative_date
        FROM user_report ur
        INNER JOIN frequency f ON f.id = ur.frequency_id
        WHERE subscription_type = 'periodic'
          AND ( start_date < ".time()." OR start_date IS NULL )
      ";
      $db->query( $sql );
      echo $db->error;
      while( $row = $db->fetchRow() ){
        if( self::$debug ) echo $aFreq[$row["frequency_id"]]["name"]."\n";
        $date = strtotime( $row["relative_date"] );
        $day = intval(date( "Ymd", $date ));
        $lastrun = intval(date( "Ymd", $row["last_run"]));
        if( self::$debug ) echo "Last run: $lastrun, Check date = $day\n";
        if( $lastrun > $day ) continue;
        $ur = new UserReport();
        // $ur->debug = self::$debug;
        $ur->initFromRow( $row );
        $ur->runReport();
      }
    }
    
    /**
    * Parse a URL into something that can produce a report
    */
    static function parseUrlIntoModel( $url ){
    
      if( self::$debug ) echo "Parsing $url\n";
      
      // Reports
      if( preg_match( "/^\/?report\/([^\/]+)\/?(.*)/", $url, $m ) ){
        $name = underscoreToCamel( $m[1] )."Report";
        if( self::$debug ) echo $name."\n";
        $model = new $name();
        
        // Get options
        $aOptions = self::parseUrlIntoArray($m[2]);
        if( self::$debug ) print_r( $aOptions );
        $model->setOptions( $aOptions );
        if( self::$debug ) echo "Returning ".get_class( $model )."\n";
        return $model;
      }
      
      // Other models
      if( preg_match( "/([^\/]+)\/?(.*)/", $url, $m ) ){
        $name = underscoreToCamel( $m[1] );
        if( !class_exists( $name ) ){ 
          return false;
        }
        $model = new $name();
    
        // model searches work on the $_GET array
        $_GET = self::parseUrlIntoArray($m[2]);
        if( self::$debug ) print_r( $_GET );
        return $model;
      }
      return false;
    }
    
    /**
    * Get keyed array from URL segment
    */
    static function parseUrlIntoArray($url){
      $aOptions = array();
      if( $url != "" ){
        $a = preg_split( "/\//", $url );
        for( $i=0; $i<sizeof( $a ); $i+=2 ){
          if( $a[$i] == "" ) continue;
          $aOptions[$a[$i]] = isset( $a[$i+1] ) ? $a[$i+1] : null;
        }
      }
      return $aOptions;
    }
  }
?>
