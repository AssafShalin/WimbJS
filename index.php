<?php header('Origin: http://wimb.azure-mobile.net');
		header('Access-Control-Allow-Origin: wimb.azure-mobile.net')
 ?>
<html>
	<head>
		<title>Wimb - Where is my bus?</title>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<link rel="icon" type="image/png" href="img/icon.png">
		<link rel="apple-touch-icon" href="img/icon.png"/>
		<link rel="stylesheet" type="text/css" href="style/wimb.css">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="user-scalable=no, width=device-width" />
		<script src="js/jquery.js"></script>
		<script src="js/wimb.js"></script>
	</head>
	<body ontouchstart="">
		<div class="header">
			<div class="logo"></div>
			<div class="header_button_container">
				<div class="header_button header_search"></div>
				<div class="header_button header_fave"></div>
				<div class="header_button header_refresh"></div>
			</div>
		</div>
		<div class="container">
			<div class="station_title">התחנות שלי</div>
			<table class="stations" id="station_list">
			</table>
		</div>
		<div id="loader"></div>
	</body>
</html>
