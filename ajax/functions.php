<?php
define('MOCK', true);
date_default_timezone_set('Asia/Jerusalem');
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
			$lines[$key]->destinationName = $destinationName[$line->destination];
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

header('Content-Type: application/json; charset=utf-8');