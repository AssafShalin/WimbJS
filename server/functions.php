<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jerusalem');
function getStationsListInfo($stations_array)
{
	$query = "";
	foreach($stations_array as $key=>$val)
	{
		$query.= 'stop_code eq '. $val;
		if($key+1 < count($stations_array)) $query.= ' or ';
	}
	$query = str_replace(' ', '%20', $query);
	$url = 'http://wimb.azure-mobile.net/tables/AllStops?$filter=(' . $query . ')';
	$curl = curl_init($url);
	
	curl_setopt_array($curl, array(
		CURLOPT_HTTPHEADER => array("X-ZUMO-APPLICATION: oaISskurhJtituuYtszOvQypeIAGeE85"),
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:27.0) Gecko/20100101 Firefox/27.0',
	    CURLOPT_VERBOSE => 1
	));
	$resp = curl_exec($curl);
	$resp = json_decode($resp);
	foreach($resp as $k=>$s)
	{
		$s->stop_desc = str_replace('כתובת:', '', $s->stop_desc);
	}
	// Open the file using the HTTP headers set above
	return $resp;
}
function getStationInfo($stop_code)
{
	$url = "http://54.243.87.53:8080/MyServlet3/Send?stop_code=". $stop_code ."&uuid=56c97211-09d1-421e-8109-aceb17feec7a";
	$curl = curl_init($url);
	
	curl_setopt_array($curl, array(
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:27.0) Gecko/20100101 Firefox/27.0',
	    CURLOPT_VERBOSE => 1
	));
	$resp = curl_exec($curl);
	$resp = preg_replace("/([sS]:|)/", "", $resp);
	//$resp = str_replace(":", "", $resp);
	//echo $resp;
	//die();
	$a = simplexml_load_string($resp);
	//$a = $a['MonitoredStopVisit'];
	$a = $a->Body->GetStopMonitoringServiceResponse->Answer->StopMonitoringDelivery;
	$prdata = array();
	foreach($a->MonitoredStopVisit as $data)
	{
		$c = array();
		$c['id'] = (string)$data->ItemIdentifier;
		$c['line_id'] = (string)$data->MonitoredVehicleJourney->LineRef;
		$c['line_number'] = (string)$data->MonitoredVehicleJourney->PublishedLineName;
		$c['operator'] = (string)$data->MonitoredVehicleJourney->OperatorRef;
		$c['dest'] = (string)$data->MonitoredVehicleJourney->DestinationRef;
		$c['arrive'] = (string)$data->MonitoredVehicleJourney->MonitoredCall->ExpectedArrivalTime;
		$c['arrive'] = ceil((strtotime($c['arrive']) - time()) / 60);
		$prdata[] = $c;
	}
	return $prdata;

}
function getFave()
{
	return json_decode(file_get_contents('../stations.json'));
}