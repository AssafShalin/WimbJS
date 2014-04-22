<?php
include 'functions.php';
if(!isset($_GET['stationId']) || !is_numeric($_GET['stationId'])) die(json_encode(array('error'=>'invalid params')));
$stationId = $_GET['stationId'];

//$etaQuery = new LinesETAQuery($stationId);
//$eta = $etaQuery->fetchLinesETA();
//$stationInfo = $etaQuery->getStation();
$stationInfo = getMockStationsData(array('443211'));
$eta = getMockLines('234234');
$results = array('station' => $stationInfo, 'eta'=> $eta);
echo json_encode($results);
