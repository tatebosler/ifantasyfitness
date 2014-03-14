<?php
$valid = false;
include('../php/db.php');
if(isset($_COOKIE['iff-id'])) {
	$id = $_COOKIE['iff-id'];
	$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
	if(mysqli_num_rows($check_q) > 0) {
		$user = mysqli_fetch_array($check_q);
		# confirm with social token
		if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
		if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	}
} 

$title = 'Leaderboard';
$connected = true;
if($valid) {
	include('../php/head-auth.php');
} else {
	include('../php/head.php');
}
?>
<div class="row">
	<div class="col-xs-12">
		<h2>Leaderboard</h2>
		
	</div>
</div>
<?php
include('../php/foot.php');
?>