<?php
# Sign out of iFF
$names = array();
foreach($_COOKIE as $name => $value) {
	setcookie($name, 0, 16, '/', '.ifantasyfitness.com');
	setcookie($name, 0, 16, '/', '.www.ifantasyfitness.com');
}
header("Location: http://www.ifantasyfitness.com/");
?>