<?php
include 'functions.php';
if(!isset($_GET['stationId']) || !is_numeric($_GET['stationId'])) die(json_encode(array('error'=>'invalid params')));
$stationId = $_GET['stationId'];
if(MOCK)
{
	$stations = getMockStationsData($stations);
}
else
{

	$stationsQuery = new StationsQuery(array($stationId));
	$stations = $stationsQuery->fetchStationsData();
}
$station = $stations[0];

if(MOCK)
{
	$station->id = $stationId;
	$station->name = 'תחנה מדומה ' . $stationId;
}



echo json_encode($station);