<?php
include '../server/functions.php';
if(!isset($_GET['stop_code']) || !is_numeric($_GET['stop_code'])) die(json_encode(array('error'=>'invalid params')));
$stations = getStationsETA($_GET['stop_code']);

$dest_arr = array();
foreach($stations as $s)
{
	$dest_arr[] = $s['dest'];
}

$dest_arr[] = $_GET['stop_code'];

$destInfo = getStationsListInfo($dest_arr);
//create a dict with station number as key
$dInfo = array();
foreach($destInfo as $d)
{
	$dInfo[$d->stop_code] = $d->stop_name;
}
foreach($stations as $key => $s)
{
	$stations[$key]['dest_desc'] = $dInfo[$s['dest']];
}
$results = array('stop_code' => $_GET['stop_code'], 'stop_name' => $dInfo[$_GET['stop_code']], 'eta'=> $stations);
echo json_encode($results);
