<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
if(!isset($_GET['season'])) header("Location: http://www.ifantasyfitnes.com/home");
include('../php/db.php');
$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);
$slug = filter_var($_GET['season'], FILTER_SANITIZE_SPECIAL_CHARS);

$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
if(mysqli_num_rows($check_q) > 0) {
	$user = mysqli_fetch_array($check_q);
	# confirm with social token
	$valid = false;
	if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
	if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	if(!$valid) header('Location: http://www.ifantasyfitness.com');
}
?>
