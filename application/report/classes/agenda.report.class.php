<?php
  require_once( "core/report.class.php" );

  class AgendaReport extends Report implements iFeature {
    function __construct(){
      parent::__construct();
      $this->id = "agenda";
      $this->title = "IT Systems Technical Review Agenda";
      $this->addCssFile( "reports.css" );
    }
    function getFeatureDescription(){
      return "Automatically creates a systems review agenda based on which features added to the system's change log and issues that have been marked for review";
    }
    
    
    static function renderIssues( $sql, $label, $linkthrough=false ){
      $db = new DB();
      $db->query( $sql );
      $html = "";
      if( $db->numrows == 0 ){
        $html .= "      <p>There are currently no issues ".h($label)."</p>\n";
      }else{
        $html .= "      <ol class=\"issues\">\n";
        $lastrow = false;
        $aFields = array(
          "url",
          "description",
          "reporter",
          "related",
          "type",
          "date"
        );
        
        while( $row = $db->fetchRow() ){
          if( !$lastrow || $lastrow["id"] != $row["id"] ){
            if( $lastrow ){ 
              if( $lastrow["comment"] != "" ) $html .= "          </table>\n";
              $html .= "        </li>\n";
            }
            $html .= "        <li class=\"issue\">\n";
            $summary = $linkthrough ? "<a href=\"".SITE_BASE."issue/edit/".$row["id"]."\">".htmlentities( $row["summary"] )."</a>" : htmlentities( $row["summary"] );
            $html .= "          <h4>".$summary."</h4>\n";
            $html .= "          <table>\n";
            $row["date"] = date( SITE_DATEFORMAT, $row["date"] );
            foreach( $aFields as $f ){
              if( $row[$f] == "" ) continue;
              $html .= "            <tr class=\"$f\">\n";
              $html .= "              <th>".ucfirst($f)."</th>\n";
              $html .= "              <td>".h($row[$f])."</td>\n";
              $html .= "            </tr>\n";
            }
            $html .= "          </table>\n";
            if( $row["comment"] != "" ){
              $html .= "          <h5>Comments:</h5>\n";
              $html .= "          <table class=\"comments\" cellspacing=\"0\">\n";
            }
          }
          if( $row["comment"] != "" ){
            $html .= "            <tr>\n";
            $html .= "              <th class=\"details\">\n";
            $html .= "                <p class=\"commenter\">".$row["commenter"]."</p>\n";
            $html .= "                <p class=\"date\">".date( SITE_DATEFORMAT, $row["comment_date"] )."</p>\n";
            $html .= "              </th>\n";
            $html .= "              <td class=\"comment\">".Field::format( "txt", $row["comment"] )."</td>\n";
            $html .= "            </tr>\n";
          }
          $lastrow = $row;
        }
        $html .= "          </table>\n";
        $html .= "        </li>\n";
        $html .= "      </ol>\n";
      }
      return $html;
    }
  
    function compile(){
      require_once( "core/last_review_date.php" );
      $html = "";
      $html .= "<h2>".$this->title."</h2>\n";
      $this->title .= " - ".date( SITE_DATEFORMAT ); // Makes the page title change but not the header
      $html .= "  <ol>\n";
      $html .= "    <li>\n";
      $html .= "      <h3>Changes to ".h( SITE_NAME )." since ".date( SITE_DATEFORMAT, SITE_LASTREVIEW )."</h3>\n";
      $cl = Cache::getModel( "ChangeLog" );
      $html .= $cl->renderList( "SELECT * FROM change_log WHERE date >= ".SITE_LASTREVIEW." ORDER BY date ASC ", false );
      $html .= "    </li>\n";
      $html .= "    <li>\n";
      $html .= "      <h3>Issues to review</h3>\n";
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
        WHERE s.code = 'REVI'
        ORDER BY i.date, ic.date
      ";
      
      $html .= self::renderIssues( $sql, "in need of formal review" );
      $html .= "    <li>\n";
      $html .= "      <h3>Other / External</h3>\n";
      $otheritemsfile = SITE_WEBROOT."/report/agenda_other_items.html";
      if( file_exists( $otheritemsfile ) ) $html .= file_get_contents( $otheritemsfile );
      $html .= "    </li>\n";
      
      /*
      $html .= "    <li>\n";
      $html .= "      <h3>Future development - issues queued for development</h3>\n";
      // Future dev
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
        WHERE s.code = 'QUEU'
        ORDER BY i.date, i.id, ic.date
      ";
      $html .= $this->renderIssues( $sql, "queued for development" );
      $html .= "    </li>\n";
      */
      // $this->filename = "Agenda_".date("M_Y").".pdf";
      $this->setHtml( $html );
    }
  }
?>