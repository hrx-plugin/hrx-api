<?php
namespace HrxApi;

use HrxApi\Helper;

class NearestLocation
{
    private $locations_list = array();
    private $address_coordinates;
    private $nearest_location;

    /**
     * Constructor
     * @since 1.0.8
     */
    public function __construct()
    {
        //Nothing
    }

    /**
     * Set locations list
     * @since 1.0.8
     * 
     * @param (array) $locations_list - List of all locations
     * @return (object) - Edited this class object
     */
    public function setLocationsList( $locations_list )
    {
        $this->locations_list = $locations_list;
        return $this;
    }

    /**
     * Get locations list
     * @since 1.0.8
     * 
     * @return (array) List of all locations. May be modificated
     */
    private function getLocationsList()
    {
        return $this->locations_list;
    }

    /**
     * Set the address coordinates from which to search for the nearest location
     * @since 1.0.8
     * 
     * @param (string) $latitude - Address latitude
     * @param (string) $longitude - Address longitude
     * @return (object) - Edited this class object
     */
    public function setAddressCoordinates( $latitude, $longitude )
    {
        $this->address_coordinates = (object) array(
            'latitude' => $latitude,
            'longitude' => $longitude
        );
        return $this;
    }

    /**
     * Get the address coordinates from which to search for the nearest location
     * @since 1.0.8
     * 
     * @return (object) - Address coordinates
     */
    private function getAddressCoordinates()
    {
        return $this->address_coordinates;
    }

    /**
     * Set the nearest location to the given address coordinates
     * @since 1.0.8
     * 
     * @param (object) $location - Location data
     * @return (object) - Edited this class object
     */
    private function setNearestLocation( $location )
    {
        $this->nearest_location = $location;
        return $this;
    }

    /**
     * Get the nearest location which was found
     * @since 1.0.8
     * 
     * @return (object) - Location data
     */
    public function getNearestLocation()
    {
        return $this->nearest_location;
    }

    /**
     * Get coordinates based on the specified address
     * @since 1.0.8
     * 
     * @param (string) $address - Address
     * @param (string) $country - Country code
     * @return (array) - Address coordinates
     */
    public static function getCoordinatesByAddress( $address, $country )
    {
        $url = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates';
        $query_array = array(
            'f' => 'pjson',
            'maxLocations' => 1,
            'forStorage' => 'false',
            'singleLine' => $address,
            'sourceCountry' => $country,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($query_array));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        $response_json = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response_json, true);

        if ( empty($response['candidates']) ) {
            return false;
        }

        return array(
            'latitude' => $response['candidates'][0]['location']['y'],
            'longitude' => $response['candidates'][0]['location']['x'],
        );
    }

    /**
     * Calculation of the distance between two coordinates
     * @since 1.0.8
     * 
     * @param (string) $latitude_from - The latitude of the first location
     * @param (string) $longitude_from - The longitude of the first location
     * @param (string) $latitude_to - The latitude of the second location
     * @param (string) $longitude_to - The longitude of the second location
     * @param (string) $unit - Distance units of the returned result
     * @return (float) - Distance between coordinates in the specified unit
     */
    public static function calculateDistanceBetweenPoints( $latitude_from, $longitude_from, $latitude_to, $longitude_to, $unit = 'km' )
    {
        switch ( $unit ) {
            case 'km': //kilometers
                $earth_radius = 6371;
            case 'mi': //miles
                $earth_radius = 3959;
                break;
            default: //meters
                $earth_radius = 6371000;
                break;
        }

        $lat_from = deg2rad($latitude_from);
        $lon_from = deg2rad($longitude_from);
        $lat_to = deg2rad($latitude_to);
        $lon_to = deg2rad($longitude_to);

        $lat_delta = $lat_to - $lat_from;
        $lon_delta = $lon_to - $lon_from;

        $angle = 2 * asin(sqrt(pow(sin($lat_delta / 2), 2) + cos($lat_from) * cos($lat_to) * pow(sin($lon_delta / 2), 2)));

        return $angle * $earth_radius;
    }

    /**
     * Finding the nearest location
     * @since 1.0.8
     */
    public function findNearestLocation()
    {
        if ( empty($this->address_coordinates) ) {
            Helper::throwError('Address coordinates are required to calculate the distance to locations');
        }

        $nearest_location_key = '';
        $nearest_location_distance = 999999;

        foreach ( $this->locations_list as $key => $location ) {
            if ( ! property_exists($location, 'latitude') || ! property_exists($location, 'longitude') ) {
                continue;
            }
            $distance = self::calculateDistanceBetweenPoints($this->address_coordinates->latitude, $this->address_coordinates->longitude, $location->latitude, $location->longitude);
            $location_array = (array) $location;
            $location_array['distance'] = $distance;
            $this->locations_list[$key] = $location_array;
            if ( $distance < $nearest_location_distance ) {
                $nearest_location_key = $key;
                $nearest_location_distance = $distance;
            }
        }

        if ( $nearest_location_key !== '' ) {
            $this->setNearestLocation($this->locations_list[$nearest_location_key]);
        }
    }
}
