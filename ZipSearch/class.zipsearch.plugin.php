<?php if (!defined('APPLICATION')) exit();
/**
 * This plugin is primarily based on code written by 
 * Micah Carrick (http://www.micahcarrick.com - email@micahcarrick.com) 
 * and with optimizations by Jeff Bearer (http://www.jeffbearer.com) 
 * that is released under the GPL2.
 * 
 * The plugin author's role was to convert the code to run on Garden,
 * clean up and reformat the code to follow Garden's conventions,
 * and auto-create the database from a zip upon Setup.
 *
 * @copyright Trademark Productions 2010
 */

// Define the plugin:
$PluginInfo['ZipSearch'] = array(
   'Name' => 'Zip Search (US)',
   'Description' => 'Provides functions for finding the distance between US zip codes or finding all zip codes within a given range. Requires PHP\'s zip library for installation.',
   'Version' => '1.2',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://tmprod.com',
   'License' => 'GNU GPLv3'
);

// $Units data member
define('_UNIT_MILES', 'm');
define('_UNIT_KILOMETERS', 'k');

// Miles to kilometers conversion
define('_M2KM_FACTOR', 1.609344);

class ZipSearchPlugin extends Gdn_Plugin {
   
   var $LastError = "";            // last error message set by this class
   var $Units = _UNIT_MILES;        // miles or kilometers
   var $Decimals = 2;               // decimal places for returned distance
   
   public function Setup() {
      $Structure = Gdn::Structure();
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      $Structure->Table('Zipcode')
         ->PrimaryKey('ZipID')
         ->Column('Zipcode', 'varchar(12)')
         ->Column('City', 'varchar(50)')
         ->Column('County', 'varchar(50)')
         ->Column('CountyID', 'int(11)')
         ->Column('State', 'varchar(2)')
         ->Column('Areacode', 'varchar(3)')
         ->Column('Timezone', 'varchar(50)')
         ->Column('Lat', 'float')
         ->Column('Lon', 'float')
         ->Set();
      
      // Unzip & import zip db table
      if(file_exists(PATH_PLUGINS.DS.'ZipSearch'.DS.'zipsearch.zip')) {
         $Zip = zip_open(PATH_PLUGINS.DS.'ZipSearch'.DS.'zipsearch.zip');
         if (is_resource($Zip)) {
            while($Entry = zip_read($Zip)) {
               if(zip_entry_open($Zip, $Entry, "r")) {
                  $Buffer = zip_entry_read($Entry, zip_entry_filesize($Entry));
                  $Database->Query($Buffer);
               }
            }
           zip_close($Zip);
         }
      }
      
      // There are 6K Canadian zips in the DB for some reason. 0.o
      // Dropping it because A) I believe 6K is woefully incomplete and
      // B) there is currently no mechanism to limit results by country
      $SQL->Delete('Zipcode', array('State' => 'QC'));
      $SQL->Delete('Zipcode', array('State' => 'ON'));
      $SQL->Delete('Zipcode', array('State' => 'BC'));
      $SQL->Delete('Zipcode', array('State' => 'PE'));
      $SQL->Delete('Zipcode', array('State' => 'NS'));
      $SQL->Delete('Zipcode', array('State' => 'NB'));
      $SQL->Delete('Zipcode', array('State' => 'MB'));
      $SQL->Delete('Zipcode', array('State' => 'SK'));
      $SQL->Delete('Zipcode', array('State' => 'AB'));
      $SQL->Delete('Zipcode', array('State' => 'YK'));
      
   }

   /**
    * Returns the distance between to zip codes.  
    * Return false on error.
    */
   public function GetDistance($Zip1, $Zip2) {
      if ($Zip1 == $Zip2) 
         return 0; // same zip code means 0 miles between. :)
   
      // Get details about each zip and exit on error
      $Details1 = $this->GetZipPoint($Zip1);
      $Details2 = $this->GetZipPoint($Zip2);
      if ($Details1 == false) {
         $this->LastError = "No details found for zip code: $Zip1";
         return false;
      }
      if ($Details2 == false) {
         $this->LastError = "No details found for zip code: $Zip2";
         return false;
      }     

      // Calculate the distance between the two points based on the lattitude and longitude.
      $Miles = $this->CalculateMileage($Details1[0], $Details2[0], $Details1[1], $Details2[1]);
       
      if ($this->Units == _UNIT_KILOMETERS) 
         return round($Miles * _M2KM_FACTOR, $this->Decimals);
      else 
         return round($Miles, $this->Decimals);       // must be miles
      
   }   

   /**
    * Gets details for a given zip code.
    */
   public function GetZipDetails($Zip) {
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      return $SQL
         ->Select('Lat','lattitude')
         ->Select('Lon','longitude')
         ->Select('City')
         ->Select('County')
         ->Select('State')
         ->Select('Areacode')
         ->Select('Timezone')
         ->From('Zipcode')
         ->Where('Zipcode',$Zip)
         ->Get()
         ->FirstRow();
   }

   /**
    * Gets lattitude and longitude for given zip.
    */
   public function GetZipPoint($Zip) {
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      return $SQL
         ->Select('Lat')
         ->Select('Lon')
         ->From('Zipcode')
         ->Where('Zipcode', $Zip)
         ->Get()
         ->FirstRow();
   }

   /**
    * Determine the mileage between 2 points defined by lattitude and
    * longitude coordinates. Based on http://www.cryptnet.net/fsp/zipdy/
    */
   public function CalculateMileage($Lat1, $Lat2, $Lon1, $Lon2) {
      // Convert lattitude/longitude (degrees) to radians for calculations
      $Lat1 = deg2rad($Lat1);
      $Lon1 = deg2rad($Lon1);
      $Lat2 = deg2rad($Lat2);
      $Lon2 = deg2rad($Lon2);
      // Find the deltas
      $DeltaLat = $Lat2 - $Lat1;
      $DeltaLon = $Lon2 - $Lon1;
      // Find the Great Circle distance 
      $Temp = pow(sin($DeltaLat/2.0),2) + cos($Lat1) * cos($Lat2) * pow(sin($DeltaLon/2.0),2);
      $Distance = 3956 * 2 * atan2(sqrt($Temp),sqrt(1-$Temp));
      return $Distance;
   }
   
   /**
    * Returns array of [zip code] => [distance] within $Range of $Zip.
    *
    * @param Sort string Here's a list of what you can sort zip codes by:
    *    1: distance asc
    *    2: distance desc
    *    3: zipcode asc
    *    4: zipcode desc
    */
   public function GetZipsInRange($Zip, $Range, $Sort = 'distance asc', $IncludeBase=TRUE) {
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      $Return = array(); // declared here for scope
      $Details = $this->GetZipPoint($Zip);  // base zip details
      if ($Details == false) 
         return false;
      // Calculates the min and max lat and lon within a given range.    
      // Find Max - Min Lat / Long for Radius and zero point and query only zips in that range.
      $LatRange = $Range/69.172;
      $LonRange = abs($Range/(cos($Details->Lat) * 69.172));
      $MinLat = number_format($Details->Lat - $LatRange, "4", ".", "");
      $MaxLat = number_format($Details->Lat + $LatRange, "4", ".", "");
      $MinLon = number_format($Details->Lon - $LonRange, "4", ".", "");
      $MaxLon = number_format($Details->Lon + $LonRange, "4", ".", "");
      $SQL
         ->Select('Zipcode')
         ->Select('Lat')
         ->Select('Lon')
         ->From('Zipcode')
         ->Where('Lat >=', $MinLat)
         ->Where('Lat <=', $MaxLat)
         ->Where('Lon >=', $MinLon)
         ->Where('Lon <=', $MaxLon);
      if (!$IncludeBase)   
         $SQL->Where('Zipcode <>', $Zip);
      $Result = $SQL->Get();

      if (!$Result) { // sql error
         return false;
      } 
      else {
         foreach ($Result as $Row) {
            // Loop through all 40K zip codes and determine whether it's within specified range.
            $Dist = $this->CalculateMileage($Details->Lat, $Row->Lat, $Details->Lon, $Row->Lon);
            if ($this->Units == _UNIT_KILOMETERS) 
               $Dist = $Dist * _M2KM_FACTOR;
            if ($Dist <= $Range) {
               $Return[str_pad($Row->Zipcode, 5, "0", STR_PAD_LEFT)] = round($Dist, $this->Decimals);
            }
         }
      }
      switch($Sort) {
         case 'distance asc': asort($Return); break;
         case 'distance desc': arsort($Return); break;
         case 'zipcode asc': ksort($Return); break;
         case 'zipcode desc': krsort($Return); break; 
         default: asort($Return); break; # Bad value, so go with 'distance asc'
      }
            
      if (empty($Return)) 
         return false;
      return $Return;
   }
   
   /**
    * Determine whether a postal code is in the given state
    */
   public function InState($Zipcode, $PostalCode) {
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      $Data = $SQL->Select('State')
         ->From('Zipcode')
         ->Where('Zipcode', $Zipcode)
         ->Get()
         ->FirstRow();
      if(is_object($Data) && strlen($PostalCode) == 2 && $Data->State == $PostalCode)
         return true;
      else 
         return false;
   }

}

?>