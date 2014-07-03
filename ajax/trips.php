<?php
include 'functions.php';

if(!isset($_GET['line'])) die(json_encode(array('error'=>'invalid params')));
$line = $_GET['line'];

$tripQuery = new TripQuery($line);
$trips = $tripQuery->fetchTrips();
echo json_encode($trips);