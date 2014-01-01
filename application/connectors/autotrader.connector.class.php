<?php
  class AutotraderConnector extends Car {
    function fetchDetails(){
      if( $this->Fields->AutotraderNumber->toString() == "" ) return;
      $url = AUTOTRADER_BASE.$this->Fields->AutotraderNumber->toString();
      if( $this->debug ) echo "Getting new car: $url\n";
      $ch = curl_init( $url );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      $txt = curl_exec( $ch );
      $inf = curl_getinfo($ch);
      $this->Fields->LastChecked->value = time();
      if( !$inf || $inf["http_code"] == "404" ){
        $this->Fields->Active = false;
        return false;
      }

      // $txt = file_get_contents( $url );

      // Name
      if( preg_match( "/<span id=\"fullPageMainTitle\"[^>]*>([^<]+)<\/span>/", $txt, $m ) ) $this->Fields->Name->set( $m[1] );
      
      // Meta data
      if( preg_match( "/<meta name=\"bannerMetaData\" content=\"make=([^,]+),model=([^,]+),mileage=([0-9]+),year-of-manufacture=([0-9]{4})\"\/>/", $txt, $m ) ){
        $this->Fields->Make->set( $m[1] );
        $this->Fields->CarModel->set( $m[2] );
        if( $m[3] < 1000 ) $m[3].="000";
        $this->Fields->Mileage->set( $m[3] );
        $this->Fields->Year->set( $m[4] );
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
      if( preg_match( "/<p class=\"sellerspecs-para\">([^<]+)/", $txt, $m ) ){
        if( preg_match( $match, $m[1], $m2 ) ){
          $this->Fields->ColourId->set( $m2[1] );
        }else $this->Fields->ColourId = "Unlisted";
      }
      
      // Engine size
      if( preg_match( "/([0-9]+) cc/", $txt, $m ) ) $this->Fields->EngineSize->set( $m[1] );

      // Price
      if( preg_match( "/<span id=\"price\" >([^<]+)<\/span>/", $txt, $m ) ) $this->Fields->Price->set( $m[1] );
      
      if( preg_match( "/<meta name=\"description\" content='([^']+)' class=\"facebookDescription\" \/>/", $txt, $m ) ){
        $fbdesc = $m[1];
      }
      // Fuel
      if( preg_match( "/(Petrol|Diesel)/i", $fbdesc, $m ) ) $this->Fields->FuelType->set( strtolower( $m[1] ) );
      
      // Transmission
      if( preg_match( "/(Manual|Automatic)/i", $fbdesc, $m ) ) $this->Fields->Transmission->set( strtolower( $m[1] ) );

      // Get the page as a DOM
      require_once( "lib/simplehtmldom/simple_html_dom.php" );
      $dom = str_get_html( $txt );
     
      if( !$dom ) return false;
      
      // Parse the stats tables
      $aStats = array();
      $aFeatures = array();
      foreach( $dom->find( "div.fpa-main" ) as $div ){
        foreach( $div->find( "table" ) as $tbl ){
          if( !$tbl ) continue;
          foreach( $tbl->find( "tr" ) as $row ){
            for( $i=0; $i<sizeof($tbl->find('tr')); $i++ ){
              $th = $row->find("th",$i);
              $td = $row->find("td",$i);
              if( !$td ) continue;
              if( !$th ) continue; 
              $key = trim(strip_tags($th->innertext()));
              $val = trim(strip_tags($td->innertext()));
              if( $val == "Standard" ) $aFeatures[] = $key;
              else $aStats[$key] = $val;
            }
          }
        }
      }
      
      // Description
      $find = "p.sellerspecs-para";
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
       "zero_to_sixty_two" => "Acceleration (0-62mph)",
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
