<?php
include 'functions.php';
$stations = json_decode(file_get_contents('stations.json'));
$stationsQuery = new StationsQuery($stations);
$stations = $stationsQuery->fetchStationsData();
echo json_encode($stations);