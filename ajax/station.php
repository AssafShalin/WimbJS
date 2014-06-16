<?php
include 'functions.php';
$stations = json_decode(file_get_contents('stations.json'));
if(!isset($_GET['stationId']) || !is_numeric($_GET['stationId'])) die(json_encode(array('error'=>'invalid params')));
$stationId = $_GET['stationId'];
if(MOCK)
{
	$stations = getMockStationsData($stations);
}
else
{
	$stationsQuery = new StationsQuery($stations);
	$stations = $stationsQuery->fetchStationsData();	
}
$station = $stations[0];
$station->id = $stationId;
$station->name = 'תחנה מדומה ' . $stationId;

echo json_encode($station);