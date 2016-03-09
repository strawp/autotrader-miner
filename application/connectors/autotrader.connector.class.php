<?php
  class AutotraderConnector extends Car {
    
    static function getUrl($url){
      $ch = curl_init( $url );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0" );
      $txt = curl_exec( $ch );
      $inf = curl_getinfo($ch);
      return array( $txt, $inf );
    }
    
    function fetchDetails(){
      require_once( "lib/simplehtmldom/simple_html_dom.php" );
      $this->debug = true;
      if( $this->Fields->AutotraderNumber->toString() == "" ) return;
      $url = AUTOTRADER_BASE.$this->Fields->AutotraderNumber->toString();
      if( $this->debug ) echo "Getting new car: $url\n";
      list( $txt, $inf ) = self::getUrl( $url );
      $dom = str_get_html( $txt );
      $this->Fields->LastChecked->value = time();
      if( !$inf || $inf["http_code"] == "404" ){
        $this->Fields->Active = false;
        return false;
      }

      $page = $txt;
  
      // Name
      $str = (string)$dom->find( "title", 0 )->innerText();
      if( $this->debug ) echo "Name: ".$str."\n";
      $this->Fields->Name = $str;
      // if( preg_match( "/<span id=\"fullPageMainTitle\"[^>]*>([^<]+)<\/span>/", $txt, $m ) ) $this->Fields->Name->set( $m[1] );
     
      // Desc
      $desc = (string)$dom->find( "section.fpaDescription", 0 )->innerText();

      // Meta data
      // if( preg_match( "/<meta name=\"bannerMetaData\" content=\"make=([^,]+),model=([^,]+),mileage=([0-9]+),year-of-manufacture=([0-9]{4})\"\/>/", $txt, $m ) ){
      if( preg_match( "/<var data-oas-name=\"query-string\" data-oas-value=\"([\"]+)\"/", $txt, $m ) ){
        $data = parse_str( url_decode( $m[1] ) );
        print_r( $data );
        $this->Fields->Make->set( $data["CAR_MAKE"] );
        $this->Fields->CarModel->set( $data["CAR_MODEL"] );
        $this->Fields->Year->set( $data["CAR_AGE"] );
        $this->Fields->Body->set( $data["CAR_BODY"] );
        $this->Fields->FuelType->set( $data["CAR_FUEL"] );
      }
      
      // Colour
      $c = new Colour();
      $dbr = $c->getAll();
      $aColours = array();
      while( $row = $dbr->fetchRow() ){
        $aColours[] = $row["name"];
      }
      $match = "/(".join( "|", $aColours ).")/i";
      // if( preg_match( "/<span id=\"leadDescription\" class=\"descriptionLeadSentence\"><strong>([^<]+)/", $txt, $m ) ){
      if( preg_match( $match, $desc, $m2 ) ){
        $this->Fields->ColourId->set( $m2[1] );
      }else $this->Fields->ColourId = "Unlisted";
    
      // Engine size
      if( preg_match( "/([0-9]\.[0-9]) litres/", $page, $m ) ){ 
        $cc = intval( $m[1] * 1000 );
        $this->Fields->EngineSize->set( $cc );
      }

      // Price
      $this->Fields->Price->set( (string)$dom->find( "span.priceTitle__price", 0 )->innerText() );
      
      if( preg_match( "/<meta name=\"description\" content='([^']+)' class=\"facebookDescription\" \/>/", $txt, $m ) ){
        $fbdesc = $m[1];
      }
      
      // Transmission
      if( preg_match( "/(Manual|Automatic)/i", $fbdesc, $m ) ) $this->Fields->Transmission->set( strtolower( $m[1] ) );

      // Parse the stats tables
      $aStats = array();
      $aFeatures = array();
      foreach( $dom->find( "div.fpaSpecifications__listItem" ) as $div ){
        $key = (string)$div->find( "div.fpaSpecifications__term", 0 )->innerText();
        $val = (string)$div->find( "div.fpaSpecifications__description", 0 )->innerText();
          if( $val == "Standard" ) $aFeatures[] = $key;
          else $aStats[$key] = $val;
      }
      /*
      print_r( $aStats );
      print_r( $aFeatures );
      */

      // Description
      $find = "section.fpaDescription";
      if( $dom->find($find,0)){
        $this->Fields->Description = trim(strip_tags($dom->find($find,0)->innertext()));
      }else{
        echo "ERROR: Didn't find $find in:\n\n $txt\n\n";
      }
      
      $aMap = array(
       "doors" => "No. of doors",
       "seats" => "No. of seats",
       "co2" => "CO2 rating (g/km)",
       // "insurance_group" => "Insurance group",
       "tax_band_id" => "Vehicle tax band",
       "urban_fuel_consumption" => "Urban mpg",
       "extraurban_fuel_consumption" => "Extra Urban mpg",
       "combined_fuel_consumption" => "Average mpg",
       "zero_to_sixty_two" => "Acceleration (0-60mph)",
       "top_speed" => "Top speed",
       "power" => "Engine power",
       // "torque" => "Engine torque",
      );
      foreach( $aMap as $col => $key ){
        if( !isset( $this->aFields[$col] ) ){
          // echo "$col not defined!\n";
          continue;
        }
        if( !isset( $aStats[$key] ) ){
          // echo "$key not in stats!\n";
          continue;
        }
        $this->aFields[$col]->set( $aStats[$key] );
      }
      $this->Fields->Features = join("\n",$aFeatures);
      return true;
    }

    function determineActive(){
      $url = AUTOTRADER_BASE.$this->Fields->AutotraderNumber->toString();
      $ch = curl_init( $url );
      curl_setopt( $ch, CURLOPT_NOBODY, true );
      $txt = curl_exec( $ch );
      $inf = curl_getinfo($ch);
      if( $inf["http_code"] != "200" ){
        $this->Fields->Active = false;
        return false;
      }
      return true;
    }

    static function determineAllActive($since="-2 days"){
      if( !is_int( $since ) ) $since = strtotime( $since );
      $db = new DB();
      $db->query( "SELECT * FROM car WHERE active = 1 AND last_checked < $since" );
      echo $db->numrows." cars to check if still active\n"; 
      $inactivecount = 0;
      $i = 1;
      while( $row = $db->fetchRow() ){
        echo $i."\r";
        $car = new AutotraderConnector();
        $car->initFromRow( $row );
        if( !$car->determineActive() ) $inactivecount++;
        $car->Fields->LastChecked->value = time();
        $car->save();
        $i++;
      }
      echo "Set $inactivecount inactive\n";
    }
    static function getColours(){
      $url = "http://".AUTOTRADER_DOMAIN."/search/used/cars/"; 
      $txt = file_get_contents( $url );
      if( !preg_match( "/<select id=\"searchVehiclesColour\"[^>]+>(\s*<option [^>]+>([^<]+)<\/option>\s*)+<\/select>/s", $txt, $m ) ) return false;
      if( !preg_match_all( "/<option[^>]+>([^<\(]+) \(\d+\).*?<\/option>/", $m[0], $m ) ) return false;
      return $m[1];
    }
  }
?>
