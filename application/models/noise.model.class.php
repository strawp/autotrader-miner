<?php
  /*
    AUTO-GENERATED CLASS
    Generated 27 Aug 2013 10:30
  */
  class Noise extends Model{
    
    function __construct(){
      $this->Model( get_class($this) );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "strBrand" ) );
      $this->addField( Field::create( "strModel" ) );
      $this->addField( Field::create( "strSpec" ) );
      $this->addField( Field::create( "intYear" ) );
      $this->addField( Field::create( "dcmIdle" ) );
      $this->addField( Field::create( "dcmFiftyKph" ) );
      $this->addField( Field::create( "dcmEightyKph" ) );
      $this->addField( Field::create( "dcmOneHundredKph" ) );
      $this->addField( Field::create( "dcmOneTwentyKph" ) );
      $this->addField( Field::create( "dcmOneFortyKph" ) );
      $this->addField( Field::create( "dcmSeventyMph" ) );
      $this->addField( Field::create( "dcmEngineSize" ) );
      $this->addField( Field::create( "chdCar" ) );
    }

    function setName(){
      $this->aFields["name"]->set( $this->aFields["brand"]->toString()." ".$this->aFields["model"]->toString()." ".$this->aFields["spec"]->toString()." ".$this->aFields["year"]->toString().": ".$this->aFields["seventy_mph"]->toString()."dB @ 70mph" );
    }

    static function findClosestMatchId( $brand, $model, $year, $engine ){
      $db = new DB();
      $sql = "SELECT id FROM noise WHERE 
        brand  like '".$db->escape( $brand )."' AND
        model  like '".$db->escape( $model )."' AND
        year   = ".intval( $year )." AND
        engine_size = ".(intval( $engine * 10 )/10);
      $db->query( $sql );

      if( $db->numrows == 0 ) return false;
      $row = $db->fetchRow();
      return $row["id"];
    }

    function setEngineSize(){
      // Guess engine size from spec
      if( preg_match( "/(\d\.\d)/", $this->aFields["spec"]->toString(), $m ) ){
        $this->aFields["engine_size"]->set( $m[1] );
      }
    }

    function noiseFinally(){
      $this->setEngineSize();
      $this->setName();
    }

    function afterCreateTable(){
      $url = "http://www.auto-decibel-db.com";
      echo "Getting noise levels from $url...\n";
      $page = file_get_contents( $url );
      require_once( "lib/simplehtmldom/simple_html_dom.php" );
      $dom = str_get_html( $page );
      if( !$dom ) return false;
      $aKeys = array_keys( $this->aFields );
      array_shift( $aKeys );
      foreach( $dom->find( "#resultTable tbody tr" ) as $tr ){
        echo ".";
        $n = new Noise();
        foreach( $tr->find( "td" ) as $k => $td ){
          $txt = $td->innertext();
          $txt = str_replace( ",", ".", $txt );
          $key = $aKeys[$k];
          // echo $key."=".$txt."\n";
          $n->aFields[$key]->set( $txt );
        }
        $n->save();
      }
      echo "Done\n";
    }
  }
?>
