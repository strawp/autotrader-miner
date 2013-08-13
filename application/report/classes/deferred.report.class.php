<?php
  require_once( "core/report.class.php" );

  class DeferredReport extends Report implements iFeature {
    function __construct(){
      parent::__construct();
      $this->id = "agenda_report";
      $this->title = "IT Systems Deferred Items";
      $this->addCssFile( "reports.css" );
    }
    function getFeatureDescription(){
      return "Automatically creates a report of deferred items from the system issue tracker";
    }
    
    
    function compile(){
      require_once( "core/last_review_date.php" );
      $html = "";
      $html .= "<h2>".h($this->title)."</h2>\n";
      $this->title .= " - ".date( SITE_DATEFORMAT ); // Makes the page title change but not the header
      $sql = "
        SELECT i.*, ic.comment, CONCAT( uc.first_name, ' ', uc.last_name ) as commenter, ic.date as comment_date,
          CONCAT( u.first_name, ' ', u.last_name ) as reporter, ri.summary as related, s.name as status, it.name as type
        FROM issue i
        LEFT OUTER JOIN issue_comment ic ON i.id = ic.issue_id
        LEFT OUTER JOIN user uc ON ic.user_id = uc.id
        INNER JOIN user u ON u.id = i.user_id
        LEFT OUTER JOIN issue ri ON i.related_issue_id = ri.id
        INNER JOIN issue_status s ON i.issue_status_id = s.id
        INNER JOIN issue_type it ON i.issue_type_id = it.id
        WHERE s.code = 'DEF'
        ORDER BY i.date, ic.date
      ";
      
      $html .= AgendaReport::renderIssues( $sql, "deferred" );
      $this->setHtml( $html );
    }
  }
?>