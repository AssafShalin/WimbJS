<?php
date_default_timezone_set('Asia/Jerusalem');
class Line
{
    public $id;
    public $number;
    public $destination;
    public $destinationDescription;
    public $operator;
    public $eta;
}
class Station
{
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
	public function getStationsDestinationDescription($stationsData)
	{
		$destinationDescription = array();
		foreach($stationsData as $station)
		{
			$destinationDescription[$station->id] = $station->description;
		} 
		return $destinationDescription;
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
			$line->eta = ceil((strtotime($line->eta) - time()) / 60);
			$lines[] = $line;
		}
		$lines = $this->fetchDestinationDescription($lines);
		return $lines;
	}
	private function loadStationData($stationsData)
	{	
		foreach($stationsData as $station)
			if($station->id == $this->stationId) return $station;
	}
	private function fetchDestinationDescription(array $lines)
	{
		$query = $this->createDestinationDescriptionQuery($lines);
		$stationsData = $query->fetchStationsData();
		$this->station = $this->loadStationData($stationsData);
		$destinationDescription = $query->getStationsDestinationDescription($stationsData);
		$lines = $this->assosiateLineWithDestinationDescription($lines,$destinationDescription);
		return $lines;
	}
	private function assosiateLineWithDestinationDescription($lines,$destinationDescription)
	{
		foreach($lines as $key => $line)
		{
			$lines[$key]->destinationDescription = $destinationDescription[$line->destination];
		}
		return $lines;
	}
	private function createDestinationDescriptionQuery(array $lines)
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
header('Content-Type: application/json; charset=utf-8');