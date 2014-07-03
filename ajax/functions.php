<?php
define('MOCK', false);
date_default_timezone_set('Asia/Jerusalem');
function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'Km') {
     $theta = $longitude1 - $longitude2;
     $distance = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
     $distance = acos($distance);
     $distance = rad2deg($distance);
     $distance = $distance * 60 * 1.1515; switch($unit) {
          case 'Mi': break; case 'Km' : $distance = $distance * 1.609344;
     }
     return (round($distance,2));
}


class Line
{
    public $__type__ = 'Line';
    public $id;
    public $number;
    public $destination;
    public $destinationName;
    public $operator;
    public $eta;
}
class Station
{
    public $__type__ = 'Station';
    public $id;
    public $name;
    public $alias;
    public $description;

    public function castToNearBy($distance)
    {
    	$this->distance = $distance;
    	$this->__type__ = 'Nearby';
    }
}
class Trip
{
	public $__type__ = 'Trip';
	public $id;
	public $operator;
	public $line;
	public $source;
	public $dest;
}
class HttpRequest
{
	private $curl;
	public function __construct($url)
	{
		$this->curl = curl_init($url);
		curl_setopt_array($this->curl, array(
										CURLOPT_HTTPHEADER => array("X-ZUMO-APPLICATION: oaISskurhJtituuYtszOvQypeIAGeE85"),
										CURLOPT_RETURNTRANSFER => 1,
										CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:27.0) Gecko/20100101 Firefox/27.0',
										CURLOPT_VERBOSE => 1
										));
		
	}
	public function getResponse()
	{
		return curl_exec($this->curl);
	}
}
class StationsQuery
{

	private $stationsArray;
	public function __construct($stationsArray)
	{
		$this->stationsArray = $stationsArray;
	}
	private function createQuery()
	{
		$query = "";
		foreach($this->stationsArray as $key=>$stationId)
		{
			$query.= 'stop_code eq '. $stationId;
			if($key+1 < count($this->stationsArray)) $query.= ' or ';
		}
		$query = str_replace(' ', '%20', $query);
		$url = 'http://wimb.azure-mobile.net/tables/AllStops?$filter=(' . $query . ')';
		return $url;
	}
	public function fetchStationsData()
	{
		$query = $this->createQuery();
		$httpRequest = new HttpRequest($query);
		$response = $httpRequest->getResponse();
		$parsedResponse = $this->parseResponse($response);
		return $parsedResponse;
	}
	private function convertJSONToStations($responseJSON)
	{
		$json = json_decode($responseJSON);
		$stations = array();
		foreach ($json as $stationData) {
			$station = new Station();
			$station->id = (int)$stationData->stop_code;
			$station->name = (string)$stationData->stop_name;
			$station->description = (string)$stationData->stop_desc;
			$station->alias = "";
			$stations[] = $station;
		}
		return $stations;
	}
	private function parseResponse($response)
	{
		$response = str_replace('כתובת:', '', $response);
		$stations = $this->convertJSONToStations($response);
		return $stations;
	}
	public function getStationsDestinationName($stationsData)
	{
		$destinationName = array();
		foreach($stationsData as $station)
		{
			$destinationName[$station->id] = $station->name;
		} 
		return $destinationName;
	}
}
class LinesETAQuery
{
	private $stationId;
	private $station;
	public function __construct($stationId)
	{
		$this->stationId = $stationId;
	}
	public function fetchLinesETA()
	{
		$response = $this->doRequest();
		return $this->parseResponse($response);
	}
	private function doRequest()
	{
		$url = "http://54.243.87.53:8080/MyServlet3/Send?stop_code=". $this->stationId ."&uuid=56c97211-09d1-421e-8109-aceb17feec7a";
		$httpRequest = new HttpRequest($url);
		return $httpRequest->getResponse();

	}
	private function removeMalData($response)
	{
		return preg_replace("/([sS]:|)/", "", $response);

	}
	private function convertXMLToLines($xml)
	{
		$xml = $xml->Body->GetStopMonitoringServiceResponse->Answer->StopMonitoringDelivery->MonitoredStopVisit;

		$lines = array();
		foreach($xml as $lineData)
		{
			$line = new Line();
			//$line->stopId = (string)$lineData->ItemIdentifier;
			$line->id = (string)$lineData->MonitoredVehicleJourney->LineRef;
			$line->number = (string)$lineData->MonitoredVehicleJourney->PublishedLineName;
			$line->operator = (string)$lineData->MonitoredVehicleJourney->OperatorRef;
			$line->destination = (string)$lineData->MonitoredVehicleJourney->DestinationRef;
			$line->eta = (string)$lineData->MonitoredVehicleJourney->MonitoredCall->ExpectedArrivalTime;
			$line->eta = ceil((strtotime($line->eta) + 1 - time()) / 60);
			$lines[] = $line;
		}
		$lines = $this->fetchDestinationName($lines);
		return $lines;
	}
	private function loadStationData($stationsData)
	{	
		foreach($stationsData as $station)
			if($station->id == $this->stationId) return $station;
	}
	private function fetchDestinationName(array $lines)
	{
		$query = $this->createDestinationNameQuery($lines);
		$stationsData = $query->fetchStationsData();
		$this->station = $this->loadStationData($stationsData);
		$destinationName = $query->getStationsDestinationName($stationsData);
		$lines = $this->assosiateLineWithDestinationName($lines,$destinationName);
		return $lines;
	}
	private function assosiateLineWithDestinationName($lines,$destinationName)
	{
		foreach($lines as $key => $line)
		{
			@$lines[$key]->destinationName = $destinationName[$line->destination];
			if($lines[$key]->destinationName == null) $lines[$key]->destinationName = "";
		}
		return $lines;
	}
	private function createDestinationNameQuery(array $lines)
	{
		$stationIds = array();
		foreach($lines as $line)
		{
			$stationIds[] = $line->destination;
		}
		$stationIds[] = $this->stationId;
		$stationsQuery = new StationsQuery($stationIds);
		return $stationsQuery;
	}
	public function getStation()
	{
		return $this->station;
	}
	private function parseResponse($response)
	{
		$response = $this->removeMalData($response);
		$xml = simplexml_load_string($response);
		$lines = $this->convertXMLToLines($xml);
		return $lines;
	}

}

function getMockStationsData($stations)
{
	$stationArray = array();
	foreach($stations as $key => $stationId)
	{
		$station = new Station();
		$station->id = $stationId;
    	$station->name = 'תחנה מדומה ' . $key;
    	$station->alias = '';
    	$station->description = 'כתובת מדומה';
		$stationArray[] = $station;
	}
	return $stationArray;
}
function getMockLines($stationId)
{
	$lineArray = array();

	for($i=0;$i<15;$i++)
	{
		$line = new Line();
		$line->id = $i;
	    $line->number = 'קו מדומה ' . $i;
	    $line->destination = $i;
	    $line->destinationName = 'יעד מדומה ' . $i;
	    $line->operator = 3;
	    $line->eta = $i+3;
	    $lineArray[] = $line;
	}
	return $lineArray;

}
function stationDistanceCompare($a, $b)
{
	return $a->distance > $b->distance;
}
class NearByQuery
{
	private $range = 0.00835; //about 2.435 km range
	private $lat;
	private $lng;
	private $responseJSON;
	public function __construct($lat, $lng)
	{
		$this->lat = $lat;
		$this->lng = $lng;
	}

	private function doRequest()
	{
		$url = 'http://wimb.azure-mobile.net/tables/AllStops?$filter=(stop_lat ge ' . ($this->lat - $this->range) . ') and (stop_lat le ' . ($this->lat + $this->range) . ') and (stop_lon ge ' . ($this->lng - $this->range) . ') and (stop_lon le ' . ($this->lng + $this->range) . ')&$select=stop_lat,stop_lon,stop_desc,stop_code,stop_id,stop_name';
		$url = str_replace(' ', '%20', $url);
		$httpRequest = new HttpRequest($url);
		$response = $httpRequest->getResponse();
		$response = str_replace('כתובת:', '', $response);
		return $response;	
	}
	private function calcDistance($station)
	{
		return getDistanceBetweenPointsNew($this->lat,$this->lng,$station->lat,$station->lng);
	}
	private function convertJSONToStations()
	{
		$json = json_decode($this->responseJSON);
		$stations = array();
		foreach ($json as $stationData) {
			$station = new Station();
			
			$station->id = (int)$stationData->stop_code;
			$station->name = (string)$stationData->stop_name;
			$station->description = (string)$stationData->stop_desc;
			$station->lat = (float)$stationData->stop_lat;
			$station->lng = (float)$stationData->stop_lon;
			
			$station->distance = $this->calcDistance($station);
			
			$station->alias = "";
			$stations[] = $station;
		}
		return $stations;
	}
	public function fetchNearBy()
	{

		$this->responseJSON = $this->doRequest();
		$stations =  $this->convertJSONToStations();
		usort($stations, create_function('$a,$b', 'return $a->distance > $b->distance;'));
		foreach($stations as $station)
		{
			$distance = $station->distance;
			$station->distance = ($distance < 1)?$distance*1000:$distance;
			$station->disatnceMeter = ($distance<1)?'M':'Km';
		}
		return $stations;
	}
}

class TripQuery
{
	private $line_num;
	public function __construct($line_num)
	{
		$this->line_num = $line_num;
	}
	private function createQuery()
	{
		$url = 'http://wimb.azure-mobile.net/tables/RoutesTrips?$filter=(route_short_name eq \''. $this->line_num .'\')&$select=route_short_name,route_long_name,agency_id,trip_id';
		$url = str_replace(' ', '%20', $url);
		return $url;
	}

	private function doRequest()
	{
		$query = $this->createQuery();
		$http = new HttpRequest($query);
		$response = $http->getResponse();
		echo $response;
		exit();
		return $response;
	}
	public function fetchTrips()
	{
		$response = $this->doRequest();
		$trips = $this->parseToTrips($response);
		return $trips;
	}
	private function parseToTrips($response)
	{
		$response = json_decode($response);
		$trips = array();
		foreach($response as $resp)
		{
			$trip = new Trip();
			$trip->operator =  (string)$resp->agency_id;
			$trip->id = (string)$resp->trip_id;
			$trip->line = (string)$resp->route_short_name;
			
			$way = explode('<->', $resp->route_long_name);
			$trip->source = $way[0];
			$trip->dest = $way[1];
			$trips[] = $trip;
		}
		return $trips;
	}
}

class RouteQuery
{
	private $trip;

	public function __construct($trip)
	{
		$this->trip = $trip;
	}
	private function createQuery()
	{
		$query = 'http://wimb.azure-mobile.net/tables/Stop?$filter=(trip_id eq \''. $this->trip .'\')&$orderby=stop_sequence&$select=stop_headsign,stop_desc,stop_code,stop_sequence,stop_id,trip_id,stop_lon,stop_lat';
		$query = str_replace(' ', '%20', $query);
		return $query;
	}
	private function doRequest()
	{
		$http = new HttpRequest($this->createQuery());
		return $http->getResponse();
	}
	public function fetchRoute()
	{
		$response = $this->doRequest();
		$response = str_replace('כתובת:', '', $response);
		$route = $this->convertJSONToStations($response);
		return $route;
	}

		private function convertJSONToStations($responseJSON)
	{
		$json = json_decode($responseJSON);

		$stations = array();
		foreach ($json as $stationData) {
			$station = new Station();
			
			$station->id = (int)$stationData->stop_code;
			$station->name = (string)$stationData->stop_headsign;
			$station->description = (string)$stationData->stop_desc;
			$station->alias = "";
			$stations[] = $station;
		}
		return $stations;
	}
}


header('Content-Type: application/json; charset=utf-8');
