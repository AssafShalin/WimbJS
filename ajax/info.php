<?php
include '../server/functions.php';
$stations = getStationInfo($_GET['stop_code']);
$dest_arr = array();
foreach($stations as $s)
{
	$dest_arr[] = $s['dest'];
}
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
echo json_encode($stations);
