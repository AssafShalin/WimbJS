<?php
include 'functions.php';
$stations = array(20051,26763,33440,33280,21394,21389,25557,33494,33507,33737);

if(MOCK)
{
	$stations = getMockStationsData($stations);
}
else
{
	$stationsQuery = new StationsQuery($stations);
	$stations = $stationsQuery->fetchStationsData();	
}

echo json_encode($stations);