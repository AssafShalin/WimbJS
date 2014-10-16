<?php
require('../ajax/functions.php');
header('Content-Type: text/html');
function render_login()
{
	?>
	<html>
	<head>
		<title>login</title>
	</head>
	<body>
		<center>
			<form method="POST"> 
			<h1>Login</h1>
			user:<input type="text" name="user"/></br>
			pass:<input type="password" name="pass"/></br>
			<input type="submit" name=""/>
			</form>
		</center>
	</body>
	</html>
	<?php
}

function is_logged()
{
	return isset($_SESSION['logged']);
}

function login($usr, $pass)
{
	if($usr == ADMIN_USER_NAME && $pass == ADMIN_PASS)
		$_SESSION['logged'] = true;
}

if(isset($_POST['user']) && isset($_POST['pass'])) 
	login($_POST['user'], $_POST['pass']);

if(!is_logged()) 
{
	render_login();
	die();
}