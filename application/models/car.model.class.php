<?php
  /*
    AUTO-GENERATED CLASS
    Generated 31 Mar 2012 23:20
  */
  class Car extends Model{
    
    function Car(){
      $this->Model( "Car" );
      $this->addField( Field::create( "strAutotraderNumber", "unique=1" ) );
      $this->addField( Field::create( "strName", "required=0;autojoin=1" ) );
      $this->addField( Field::create( "cnfShortlist" ) );
      $this->addField( Field::create( "htmUrl" ) );
      $this->Fields->Url->aUsesFields = array( "name", "autotrader_number" );
      $this->addField( Field::create( "txtDescription" ) );
      $this->addField( Field::create( "txtFeatures" ) );
      $this->addField( Field::create( "lstUserId", "default=".SessionUser::getId() ) );
      $this->addField( Field::create( "intMileage" ) );
      $this->addField( Field::create( "intYear" ) );
      $this->addField( Field::create( "dcmApproxEngineSize", "displayname=Approx Engine Size (L)" ) );
      $this->addField( Field::create( "intEngineSize", "displayname=Engine Size (CC)" ) );
      $this->addField( Field::create( "strMake", "display=0" ) );
      $this->addField( Field::create( "lstMakeId", "allowcreatefk=1" ) );
      $this->addField( Field::create( "strCarModel", "display=0" ) );
      $this->addField( Field::create( "lstCarModelId", "allowcreatefk=1" ) );
      $this->addField( Field::create( "lstColourId" ) );
      $this->addField( Field::create( "cshPrice" ) );
      $this->addField( Field::create( "intDoors" ) );
      $this->addField( Field::create( "intSeats" ) );
      $this->addField( Field::create( "strTransmission" ) );
      $this->addField( Field::create( "strFuelType" ) );
      $this->addField( Field::create( "intCo2" ) );
      $this->addField( Field::create( "cnfHasAircon" ) );
      $this->addField( Field::create( "cnfHasCupholders" ) );
      $this->addField( Field::create( "cnfHasAuxInput" ) );
      $this->addField( Field::create( "cnfHasBluetooth" ) );
      $this->addField( Field::create( "cnfHasUsb", "displayname=Has USB" ) );
      $this->addField( Field::create( "intInsuranceGroup" ) );
      $this->addField( Field::create( "lstTaxBandId", "allowcreatefk=1" ) );
      $this->addField( Field::create( "dcmUrbanFuelConsumption" ) );
      $this->addField( Field::create( "dcmExtraurbanFuelConsumption" ) );
      $this->addField( Field::create( "dcmCombinedFuelConsumption" ) );
      $this->addField( Field::create( "dcmZeroToSixtyTwo" ) );
      $this->addField( Field::create( "intTopSpeed" ) );
      $this->addField( Field::create( "intPower" ) );
      $this->addField( Field::create( "intTorque" ) );
      $this->addField( Field::create( "lstNoiseId" ) );
      $this->addField( Field::create( "cshInsuranceQuote", "helphtml=If you get an insurance quote for this car you can enter it here and it will be taken into account when calculating annual cost of ownership" ) );
      $this->addField( Field::create( "cshFuelAndTaxCost", "helphtml=This assumes tax based on TC48 and TC49 type cars, registered after 2001 and more than a year old plus cost of running annual mileage for the car's fuel type" ) );
      $this->addField( Field::create( "cshAnnualCost", "helphtml=This is fuel and tax cost plus insurance quote (if there is one)" ) );
      $this->addField( Field::create( "cshTcoThreeYears", "helphtml=Price of car + 3 * annual cost" ) );
      $this->addField( Field::create( "dtmLastChecked", "default=now" ) );
      $this->addField( Field::create( "dtmUpdatedAt" ) );
      $this->addField( Field::create( "dtmCreatedAt", "display=1" ) );
      $this->addField( Field::create( "bleActive", "default=1" ) );
      $this->allowfieldselect = true;
      $this->gotofield="autotrader_number";
      $this->createongoto = true;
      $this->calculations[] = "setHtmFields";
      $this->aResultsFields = array( "name", "shortlist", "url", "make_id", "car_model_id", "colour_id", "price", "combined_fuel_consumption", "zero_to_sixty_two", "tco_three_years" );
      $this->aSearchFields = $this->aResultsFields;
    }

    function setHtmFields(){
      // $this->aFields["url"]->value = "<a href=\"".AUTOTRADER_BASE.$this->aFields["autotrader_number"]->toString()."\">".strip_tags($this->Fields->Name->toString())."</a>";
      $this->aFields["url"]->value = "<a href=\"".AUTOTRADER_BASE.$this->aFields["autotrader_number"]->toString()."\">View on Autotrader</a>";
    }

    function carFinally(){
      if( $this->id == 0 ) $this->fetchDetails();
      $this->setAnnualCostOfOwnership();
      $this->setTcoThreeYears();
      $this->setHasAircon();
      $this->setHasCupholders();
      $this->setFeatures();
      $this->setLists();
      $this->setApproxEngineSize();
      $this->setNoiseId();
    }

    function fetchDetails(){
      $at = new AutotraderConnector();
      $at->aFields = $this->aFields;
      $at->fetchDetails();
      $this->aFields = $at->aFields;
    }

    function setNoiseId(){
      // Join to noise table using make, model, year and engine size
      $id = Noise::findClosestMatchId( $this->aFields["make"]->toString(), $this->aFields["car_model"]->toString(), $this->aFields["year"]->toString(), $this->aFields["approx_engine_size"]->toString() );
      if( $id ) $this->aFields["noise_id"]->set( intval( $id ) );
    }

    function setApproxEngineSize(){
      $size = $this->aFields["engine_size"]->value;
      $size = round( $size/100 )/10;
      $this->aFields["approx_engine_size"]->set( $size );
    }

    function setFeatures(){
      // Pick things in list of features
      $feat = $this->Fields->Features->toString();
      $feat .= "\n".$this->Fields->Description->toString();
      $feat = str_replace( "&nbsp;", "", $feat );
      if(preg_match("/air[\s-]?conditioning/i",$feat)) $this->Fields->HasAircon = true;
      if(preg_match("/cup[\s-]?holder[s]?/i",$feat)) $this->Fields->HasCupholders = true;
      if(preg_match("/\baux(iliary)?\b/i",$feat)) $this->Fields->HasAuxInput = true;
      if(preg_match("/\bbluetooth\b/i",$feat)) $this->Fields->HasBluetooth = true;
      if(preg_match("/\busb\b/i",$feat)) $this->Fields->HasUsb = true;
      return true;
    }

    /**
    * Set the list values from the string values
    */
    function setLists(){
      $aFields = array(
        "make" => "make_id",
        "car_model" => "car_model_id",
      );
      foreach( $aFields as $k => $v ){
        if( !isset( $this->aFields[$v] ) ) continue;
        if( !isset( $this->aFields[$k] ) ) continue;
        $this->aFields[$v]->set($this->aFields[$k]->toString());
      }
    }

    /**
    * Try and work out if it's got cup holders
    */
    function setHasCupholders(){
      $desc = $this->Fields->Description->toString()."\n".$this->Fields->Features->toString();
      if( preg_match( "/\b(cup([\s-])?holder(s)?)\b/i", $desc, $m ) ){ 
        $this->Fields->HasCupholders = true;
      }
    }

    /**
    * Attempt to determine from description if the car has air conditioning
    */
    function setHasAircon(){
      $desc = $this->Fields->Description->toString();
      if( preg_match( "/\b(climate control|a\/?c|air([\s-])?con(ditioning)?)\b/i", $desc, $m ) ){ 
        $this->Fields->HasAircon = true;
      }
    }

    /**
    * Get total cost of ownership over three years: price plus 3x annual cost
    */
    function setTcoThreeYears(){
      $cost = $this->Fields->Price->value;
      $cost += ( $this->Fields->AnnualCost->value * 3 );
      $this->Fields->TcoThreeYears->value = $cost;
    }
  
    function setAnnualCostOfOwnership(){
      $this->Fields->AnnualCost->value = $this->getAnnualCostOfOwnership();
    }

    // Get annual cost of ownership
    function getAnnualCostOfOwnership(){
      $csh = Field::create( "cshTotal" );
      $csh->value += $this->Fields->InsuranceQuote->value;
      $u = $this->Fields->UserId->getBelongstoModel(true);
      
      // Annual Mileage
      $fuel = $this->Fields->FuelType->toString();
      if( isset( $u->aFields[$fuel."_cost_per_litre"] ) ){
        $f = $u->aFields[$fuel."_cost_per_litre"];
        // Miles per gallon in miles per litre
        $mpl = $this->Fields->CombinedFuelConsumption->value * 0.264172052;
        
        // Total litres used in year
        if( $mpl > 0 ){
          $ltrs = $u->Fields->AnnualMileage->value / $mpl;
          $this->Fields->FuelAndTaxCost->value = round($f->value*$ltrs);
        }
      }

      // Tax
      $this->Fields->FuelAndTaxCost->value += $this->Fields->TaxBandId->getBelongstoModel(true)->Fields->AnnualCost->value;
      return $csh->value + $this->Fields->FuelAndTaxCost->value;
    }
    
    static function getByAutotraderNumber( $number ){
      $car = new Car();
      $car->retrieveByClause( "WHERE autotrader_number = '".intval($number)."'" );
      if( $car->id == 0 ){
        $car->debug = true;
        $car->Fields->AutotraderNumber = $number;
        $car->Fields->UserId->value = 1;
        if( $car->validate() ) $car->save();
      }
      return $car;
    }

  }
?>
