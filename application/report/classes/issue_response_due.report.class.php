<?php
  require_once( "core/report.class.php" );
  class IssueResponseDueReport extends Report implements iFeature {
    function __construct(){
      parent::__construct();
      $this->id = "issue_response_due";
      $this->title = "Issues due a response";
      $this->addCssFile( "reports.css" );
    }
    function getFeatureDescription(){
      return "Shows a report of which issues are due for a response, based on last comment and what status they are in";
    }
    
    function compile(){
      $html = "";
      $html .= "<h2>".h($this->title)."</h2>\n";
      
      $db = new DB();
      $monthago = strtotime( "-1 month" );      
      
      /* Get all issues that are:
        - New
        - Active
        And that are not:
        - For review
        - Deferred
        - Commented on in last month
        */
      
      // Get list of issues first
      $sql = "
        SELECT i.id
        FROM issue i
        INNER JOIN issue_type it ON i.issue_type_id = it.id
        INNER JOIN issue_status s ON s.id = i.issue_status_id
        WHERE i.active = 1 AND s.code <> 'DEF' AND s.code <> 'REVI' AND s.code <> 'QUEU'
      ";
      $db->query($sql);
      $aIssues = array(0);
      while( $row = $db->fetchRow() ){
        $aIssues[] = $row["id"];
      }
      
      // Remove issues that haven't been commented on in last month
      foreach( $aIssues as $k=>$id ){
        $sql = "SELECT date FROM issue_comment WHERE issue_id = ".intval( $id )." ORDER BY date DESC LIMIT 1";
        $db->query( $sql );
        if( $db->numrows == 0 ) continue;
        $row = $db->fetchRow();
        if( $row["date"] > $monthago ) unset( $aIssues[$k] );
      }
      
      $sql = "
        SELECT i.*, ic.comment, CONCAT( uc.first_name, ' ', uc.last_name ) as commenter, ic.date as comment_date,
          CONCAT( u.first_name, ' ', u.last_name ) as reporter,
          ri.summary as related, s.name as status, it.name as type,
          s.code, i.deferred_date
        FROM issue i
        INNER JOIN issue_comment ic ON ic.issue_id = i.id
        LEFT OUTER JOIN user uc ON ic.user_id = uc.id
        INNER JOIN user u ON u.id = i.user_id
        LEFT OUTER JOIN issue ri ON i.related_issue_id = ri.id
        INNER JOIN issue_status s ON s.id = i.issue_status_id
        INNER JOIN issue_type it ON i.issue_type_id = it.id
        WHERE i.id IN (".join(",",$aIssues).")
        ORDER BY i.date, ic.date
      ";
      $html .= AgendaReport::renderIssues( $sql, "due a response", true );
      
      $this->setHtml($html);
    }    
  }
?>