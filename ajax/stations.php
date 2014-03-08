<?php
include '../server/functions.php';
$stations = getFave();
$info = getStationsListInfo($stations);
echo json_encode($info);