<?php
  /*
    AUTO-GENERATED CLASS
    Generated 6 Apr 2012 21:02
  */
  class Search extends Model{
    
    function Search(){
      $this->Model( "Search" );
      $this->addField( Field::create( "strUrl", "required=1;length=1000" ) );
      $this->addField( Field::create( "strName", "required=0" ) );
      $this->addField( Field::create( "dtmLastRan" ) );
    }
    function run(){
      require_once( "lib/simplehtmldom/simple_html_dom.php" );
      $this->debug = true;
      $totalpages = 1;
      $currentpage = 1;
      do{ 
        $url = $this->Fields->Url->toString();
        $url = preg_replace( "/\/page\/\d+/", "", $url );
        $url .= "/page/$currentpage";
        echo "Getting page ".$this->getName()." $currentpage of $totalpages\n";
        if( $this->debug ) echo $url."\n";
        list( $html, $inf ) = AutotraderConnector::getUrl( $url ); // file_get_contents( $url );
        if( $inf['http_code'] == '204' ){
          die( "HTTP error 204 - probably been blocked by Autotrader" );
        }
        // Extract postcode
        if( preg_match( "/\/postcode\/([^\/]+)/", $url, $m ) ){
          $postcode = $m[1];
        }else{
          $postcode = '';
        }
        /*
        print_r( $inf );
        print_r( $html );
        */
        $dom = str_get_html( $html );
        $tp = $dom->find( "li.paginationMini__count strong", 1 );
        if( $tp ) $totalpages = intval($tp->innertext());
        foreach( $dom->find( "h1.search-result__title a" ) as $link ){
          if( !preg_match( AUTOTRADER_MATCH, $link->href, $m ) ) continue;
          if( $this->debug ) echo "Found: ".$link->href."\n";
          if( $this->debug ) echo "ID: ".$m[1]."\n";
          Car::getByAutotraderNumber( $m[1], $postcode );
        }
        $currentpage++;
      }while( $totalpages >= $currentpage );
      $this->Fields->LastRan->value = time();
      $this->save();
    }

    function searchFinally(){
      if( $this->Fields->Name->value == "" ) $this->fetchSearchName();
    }

    function fetchSearchName(){
      require_once( "lib/simplehtmldom/simple_html_dom.php" );
      $totalpages = 1;
      $currentpage = 1;
      $url = $this->Fields->Url->toString();
      if( $this->debug ) echo $url."\n";
      list( $html, $inf ) = AutotraderConnector::getUrl( $url ); // file_get_contents( $url );
      print_r( $inf );
      $dom = str_get_html( $html );
      // $dom = file_get_html( $url );
      $aSel = $dom->find( "button.is-selected" );
      $aName = array();
      foreach( $aSel as $sel ){
        $attr = $sel->find( "span.options-button__name", 0 )->innertext();
        $val = $sel->find( "span.options-button__value", 0 )->innertext();
        $val = strip_tags( $val );
        $val = preg_replace( "/Any/", "", $val );
        $val = preg_replace( "/  +/", " ", $val );
        $val = trim( $val );
        $aName[] = trim( $attr.": ".$val );
      }
      print_r( $aName );
      $this->Fields->Name = join( ", ", $aName );
      echo $this->toString(); 
    }

    static function runAllSince( $date ){
      if( !is_int( $date ) ) $date = strtotime( $date );
      $db = new DB();
      $sql = "SELECT * FROM search WHERE last_ran is null or last_ran < ".intval( $date )." order by last_ran";
      echo "Running ".$db->numrows." searches\n";
      $db->query( $sql );
      while( $row = $db->fetchRow() ){
        $search = new Search();
        // $search->debug = true;
        $search->initFromRow( $row );
        echo $search->toString();
        $search->run();
      }
    }
  }
?>
