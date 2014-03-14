<?php
$google_id = $_COOKIE['g-uuid'];
$first = $_COOKIE['g-first'];
$last = $_COOKIE['g-last'];

// Check if this user exists in the Database
include('../../php/db.php');
$check_acct = @mysqli_query($db, "SELECT * FROM users WHERE google='$google_id'");
if(mysqli_num_rows($check_acct) >= 1) {
	$user = mysqli_fetch_array($check_acct);
	$exp = time()+30*(24*60*60);
	setcookie('iff-id',$user['id'],$exp,'/','.ifantasyfitness.com');
	setcookie('iff-google',$user['google'],$exp,'/','.ifantasyfitness.com');
	setcookie('iff-facebook',$user['facebook'],$exp,'/','.ifantasyfitness.com');
	setcookie('iff-exp',$exp,$exp,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com/home');
} else {
	$insert = @mysqli_query($db, "INSERT INTO users (google, first, last) VALUES ($google_id, '$first','$last')");
	$exp = time()+30*(24*60*60);
	setcookie('iff-id',mysqli_insert_id($db),$exp,'/','.ifantasyfitness.com');
	setcookie('iff-google',$google_id,$exp,'/','.ifantasyfitness.com');
	setcookie('iff-facebook',0,$exp,'/','.ifantasyfitness.com');
	setcookie('iff-exp',$exp,$exp,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com/home?hi');
}
?>