<?php
include 'functions.php';

if(!isset($_GET['trip']) || !is_numeric($_GET['trip'])) die(json_encode(array('error'=>'invalid params')));
$trip = $_GET['trip'];

$routeQuery = new RouteQuery($trip);
$route = $routeQuery->fetchRoute();
echo json_encode($route);