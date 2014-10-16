<?php
require('permissions.php');
$stats = Stats::fetchAll();
?>
<html>
<head>
	<style>
		body {
			background-color: #000000;
			font-family: monospace;
			color: #ffffff;
		}
		table {
			border-width: 1px;
		}
	</style>
</head>
<body>
<center>
	<h1>Wimb Usage Log</h1>
<table border="1">
<tr><?php
foreach($stats[0] as $key=>$val)
{
	echo "<td>{$key}</td>";
}
?>
</tr>
<?php
foreach($stats as $val)
{
	echo "<tr>";
	foreach($val as $item)
		echo "<td>{$item}</td>";
	echo "</tr>";
}
?>
</table>
</center>
</body>
</html>