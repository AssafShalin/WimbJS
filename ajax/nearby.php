<?php
include 'functions.php';

if(!isset($_GET['lat']) || !is_numeric($_GET['lat'])) die(json_encode(array('error'=>'invalid params')));
if(!isset($_GET['lng']) || !is_numeric($_GET['lng'])) die(json_encode(array('error'=>'invalid params')));
$lat = $_GET['lat'];
$lng = $_GET['lng'];

if(MOCK)
{
	$stations = json_decode(file_get_contents('stations.json'));
	$stations = getMockStationsData($stations);
	foreach($stations as $station) $station->castToNearBy(0.5);
}
else
{
	
	$nearby = new NearByQuery($lat, $lng);
	$stations = $nearby->fetchNearBy(); //range of about 2 km


}
echo json_encode($stations);
