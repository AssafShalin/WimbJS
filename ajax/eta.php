<?php
include 'functions.php';
if(!isset($_GET['stationId']) || !is_numeric($_GET['stationId'])) die(json_encode(array('error'=>'invalid params')));
$stationId = $_GET['stationId'];

if(MOCK)
{
	$stationInfo = getMockStationsData(array('443211'));
	$stationInfo = $stationInfo[0];
	$eta = getMockLines('234234');
	sleep(2.7);
}
else
{
	$etaQuery = new LinesETAQuery($stationId);
	$eta = $etaQuery->fetchLinesETA();
	$stationInfo = $etaQuery->getStation();
}
$results = array('station' => $stationInfo, 'eta'=> $eta);
echo json_encode($results);
