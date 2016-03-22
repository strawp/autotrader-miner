<?php
  class AutotraderConnector extends Car {
    
    static function getUrl($url, $mobile=false){
      $ch = curl_init( $url );
      if( $mobile ) $ua = MOBILE_USERAGENT;
      else $ua = DESKTOP_USERAGENT;
      $ua .= " /v".rand(1000,9999); // Add randomness to the end
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_USERAGENT, $ua ); 
      $txt = curl_exec( $ch );
      $inf = curl_getinfo($ch);
      if( $inf["http_code"] == "204" ){
        echo "Error code 204 detected - probably blocked by rate limiting!\n";
      }
      return array( $txt, $inf );
    }
    
    function fetchDetails(){
      require_once( "lib/simplehtmldom/simple_html_dom.php" );
      $this->debug = true;
      if( $this->Fields->AutotraderNumber->toString() == "" ) return;
      if( trim($this->Fields->DesktopPageCache->value) == '' || trim($this->Fields->MobilePageCache->value) == '' ){
        $url = AUTOTRADER_BASE.$this->Fields->AutotraderNumber->toString();
        if( $this->debug ) echo "Getting new car: $url\n";
        list( $txt, $inf ) = self::getUrl( $url );
        // print_r( $inf );
        if( !$inf || $inf["http_code"] == "301" ){
          $this->Fields->Active = false;
          return false;
        }

        $url = AUTOTRADER_MOBILE_BASE.$this->Fields->AutotraderNumber->toString();
        if( $this->debug ) echo "Getting new car: $url\n";
        list( $mobpage, $inf ) = self::getUrl( $url, true );
        /*
        print_r( $inf );
        print_r( $mobpage );
        */
        $this->Fields->LastChecked->value = time();
        $page = $txt;
    
        $this->Fields->DesktopPageCache->set( $page );
        $this->Fields->MobilePageCache->set( $mobpage );
      }else{
        $page = $this->Fields->DesktopPageCache->toString();
        $mobpage = $this->Fields->MobilePageCache->toString();
      }
      // echo $page;
      $dom = str_get_html( $page );
      $mobdom = str_get_html( $mobpage );

      // Name
      $str = (string)$dom->find( "title", 0 )->innerText();
      if( $this->debug ) echo "Name: ".$str."\n";
      $this->Fields->Name = $str;
      // if( preg_match( "/<span id=\"fullPageMainTitle\"[^>]*>([^<]+)<\/span>/", $txt, $m ) ) $this->Fields->Name->set( $m[1] );
     
      // Distance
      if( preg_match( "/(\d+) miles from ([A-Z]{2}[0-9]+ [0-9]+[A-Z]{2})/", $page, $m ) ){
        $pc = $m[0];
        $c->Fields->DistanceStr->set( $pc );
      }

      // Insurance Category
      if( preg_match( "/CATEGORY <i class=\"category icon ([a-z])\">/", $page, $m ) ){
        $c->Fields->InsuranceCategory->set( strtoupper($m[1]) );
      }
      
      // Desc
      $desc = (string)$dom->find( "section.fpaDescription", 0 )->innerText();

      // Year
      if( preg_match( "/keyFacts__item\">(\d+)<\/li>/", $page, $m ) ){
        $this->Fields->Year->set( $m[1] );
      }
      
      // Mileage
      if( preg_match( "/keyFacts__item\">([,0-9]+) miles<\/li>/", $page, $m ) ){
        $v = preg_replace( "/,/", "", $m[1] );
        $this->Fields->Mileage->set( $v );
      }

      // Fuel
      if( preg_match( "/keyFacts__item\">(Petrol|Diesel)<\/li>/", $page, $m ) ){
        $this->Fields->FuelType->set( $m[1] );
      }

      // Some metadata
      if( preg_match( "/var utag_data = {(.*?)};/", $page, $m ) ){
        $data = json_decode( "{".$m[1]."}" );
        $this->Fields->Make->set( $data->make );
        $this->Fields->CarModel->set( $data->model );
        if( !empty( $data->insurance_group ) ) $this->Fields->InsuranceGroup->set( $data->insurance_group );
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
      
      if( preg_match( "/<meta name=\"description\" content='([^']+)' class=\"facebookDescription\" \/>/", $page, $m ) ){
        $fbdesc = $m[1];
      }
      
      // Transmission
      if( preg_match( "/(Manual|Automatic)/i", $fbdesc, $m ) ) $this->Fields->Transmission->set( strtolower( $m[1] ) );

      // Parse the stats tables
      $aStats = array();
      $aFeatures = array();

      // Get stuff off mobile site
      foreach( $mobdom->find( "section#fpa_techspec_container li" ) as $item ){
        $oKey = $item->find( "div.left-align", 0 );
        if( $oKey ){
          $key = trim($oKey->innerText());
          $oVal = $item->find( "div.right-align", 0 );
          if( $oVal ){
            $val = trim($oVal->innerText());
            $aStats[$key] = $val;
          }
        }
      }

      foreach( $dom->find( "div.fpaSpecifications__listItem" ) as $div ){
        $key = (string)$div->find( "div.fpaSpecifications__term", 0 )->innerText();
        $key = strip_tags( $key );
        $val = (string)$div->find( "div.fpaSpecifications__description", 0 )->innerText();
        if( $val == "Standard" ) $aFeatures[] = $key;
        else $aStats[$key] = $val;
      }
      
      // Description
      $find = "section.fpaDescription";
      if( $dom->find($find,0)){
        $this->Fields->Description = trim(strip_tags($dom->find($find,0)->innertext()));
      }else{
        echo "ERROR: Didn't find $find in:\n\n $page\n\n";
      }
      
      $aMap = array(
       "doors" => "Number of doors",
       "seats" => "Number of seats",
       "boot_space_seats_down" => "Boot space (seats down)",
       "boot_space_seats_up" => "Boot space (seats up)",
       "height" => "Height",
       "length" => "Length",
       "wheelbase" => "Wheelbase",
       "width" => "Width",
       "co2" => "CO2 emissions",
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
      if( $inf["http_code"] == "204" ) die ( "Error 204 - blocked by rate limiting\n" );
      if( $inf["http_code"] == "301" ){
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
