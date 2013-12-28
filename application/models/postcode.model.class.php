<?php
  /*
    AUTO-GENERATED CLASS
    Generated 14 Aug 2013 15:30
  */
  class Postcode extends Model{
    
    function __construct(){
      $this->Model( get_class($this) );
      $this->addField( Field::create( "strName" ) );
      $this->addField( Field::create( "fltLatitude" ) );
      $this->addField( Field::create( "fltLongitude" ) );
    }

    function afterCreateTable(){
      $aData = array(
        "TR19 6JW",
        "NR29 4DY",
        "IV27 4PT"
      );
      $gmapi = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=';
      foreach( $aData as $pc ){
        // Lookup long / lat
        $loc =  json_decode(file_get_contents($gmapi.urlencode($pc)));
        $p = new Postcode();
        $p->Fields->Name = $pc;
        $p->Fields->Latitude = $loc->results[0]->geometry->location->lat;
        $p->Fields->Longitude = $loc->results[0]->geometry->location->lng;
        $p->save();
      }
    }
  }
?>
