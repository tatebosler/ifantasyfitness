<?php
# Must have provider.
if(!isset($_GET['provider'])) header("Location: http://www.ifantasyfitness.com/login");

$provider = filter_var($_GET['provider'], FILTER_SANITIZE_STRING);
$time = filter_var($_GET['rq'],FILTER_SANITIZE_NUMBER_INT);

# For requests to be considered "authentic" they must have been initiated within the last 15 seconds. If not, redo
if(time()-15 > $time) header("Location: http://www.ifantasyfitness.com/login");

include('../php/db.php');
$exp = time() + 90 * 24 * 60 * 60;

$uid = filter_var($_GET['uid'],FILTER_SANITIZE_NUMBER_INT);
if($provider == "twitter") {
	# Twitter is special so handle it differently
	$ue_check = @mysqli_query($db, "SELECT * FROM users WHERE twitter=$uid");
	if(mysqli_num_rows($ue_check) == 0) {
		$ue_insert = @mysqli_query($db, "INSERT INTO users (twitter) VALUES ($uid)");
		$id = mysqli_insert_id($db);
	} else {
		$ue_grab = mysqli_fetch_array($ue_check);
		$id = $ue_grab['id'];
		if(!empty($ue_grab['facebook'])) setcookie('iff-facebook',$ue_grab['facebook'],$exp,'/','.ifantasyfitness.com');
		if(!empty($ue_grab['google'])) setcookie('iff-facebook',$ue_grab['google'],$exp,'/','.ifantasyfitness.com');
	}
	setcookie('iff-twitter',$uid,$exp,'/','.ifantasyfitness.com');
} else {
	$first = filter_var($_GET['first'], FILTER_SANITIZE_SPECIAL_CHARS);
	$last = filter_var($_GET['last'], FILTER_SANITIZE_SPECIAL_CHARS);
	$ue_check = @mysqli_query($db, "SELECT * FROM users WHERE $provider=$uid");
	if(mysqli_num_rows($ue_check) == 0) {
		# The account doesn't exist.
		# Rather than create the account, let's figure out what's going on by printing words to the user.
		$connected = true;
		$title = "Whoops! Something went wrong.";
		include('../php/head.php');
		echo "<h2>Hello, $first!</h2>
		<p>Well this is unfortunate. It doesn't look like you have an account. We could be wrong, so for now, account creation is disabled. Please copy and paste the contents of this page into an email to admin@ifantasyfitness.com, and we will try to get you hooked up as quickly as possible.</p>
		<pre>Provided account data looks good. (9)<br>
		First name: $first<br>
		Last name: $last<br>
		User ID from provider: $uid<br>
		Provider: $provider<br>
		Request time: ".date("F j, Y h:i:s A", $time)."<br>
		<br>
		----------------------
		BEGIN DATABASE QUERIES
		----------------------
		UE CHECK (31) -> \"SELECT * FROM users WHERE $provider=$uid\"<br>
		UE CHECK ROW COUNT: 0<br>
		MYSQL ERROR DATA: ".mysqli_error($db)."<br>
		
		------------------------
		ACCOUNT FETCH TERMINATED
		</pre>
		<p><strong>Please email the contents of the box to us!</strong> We'll help you as quickly as we can.</p>";
		/* $ue_name_check = @mysqli_query($db, "SELECT * FROM users WHERE LOWER(first)=LOWER('$first') AND LOWER(last)=LOWER('$last')");
		if(mysqli_num_rows($ue_name_check) == 0) {
			# Welcome
			$ue_insert = @mysqli_query($db, "INSERT INTO users (first, last, $provider) VALUES ('$first', '$last', $uid)");
			$id = mysqli_insert_id($db);
			
			if($provider == 'facebook') setcookie('iff-facebook',$uid,$exp,'/','.ifantasyfitness.com');
			if($provider == 'google') setcookie('iff-google',$uid,$exp,'/','.ifantasyfitness.com');
		} else {
			$ue_grab = mysqli_fetch_array($ue_name_check);
			
			$id = $ue_grab['id'];
			
			# Let's update the database
			$user_updater = @mysqli_query($db, "UPDATE users SET $provider=$uid WHERE id=$id");
			if(!empty($ue_grab['facebook'])) setcookie('iff-facebook',$ue_grab['facebook'],$exp,'/','.ifantasyfitness.com');
			if(!empty($ue_grab['twitter'])) setcookie('iff-twitter',$ue_grab['twitter'],$exp,'/','.ifantasyfitness.com');
			if(!empty($ue_grab['google'])) setcookie('iff-google',$ue_grab['google'],$exp,'/','.ifantasyfitness.com');
		}
		*/
	} else {
		$ue_grab = mysqli_fetch_array($ue_check);
		$id = $ue_grab['id'];
		if(!empty($ue_grab['facebook'])) setcookie('iff-facebook',$ue_grab['facebook'],$exp,'/','.ifantasyfitness.com');
		if(!empty($ue_grab['twitter'])) setcookie('iff-twitter',$ue_grab['twitter'],$exp,'/','.ifantasyfitness.com');
		if(!empty($ue_grab['google'])) setcookie('iff-google',$ue_grab['google'],$exp,'/','.ifantasyfitness.com');
	}
}

setcookie('iff-id',$id,$exp,'/','.ifantasyfitness.com');
if(isset($ue_insert) or $ue_grab['profile'] == 1) {
	header("Location: http://www.ifantasyfitness.com/setup/register.php");
} else {
	header("Location: http://www.ifantasyfitness.com/home");
}
?>
