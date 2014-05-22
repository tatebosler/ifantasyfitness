<?php
$valid = false;
include('../php/db.php');
if(isset($_COOKIE['iff-id'])) {
	$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);
	$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
	if(mysqli_num_rows($check_q) > 0) {
		$user = mysqli_fetch_array($check_q);
		# confirm with social token
		if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
		if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	} else {
		$id = 0;
	}
} else {
	$id = 0;
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
		<div class="jumbotron">
			<h1>Rules</h1>
			<p>iFantasyFitness, sometimes written <em>!FantasyFitness</em>, is a summer training game. You get points by exercising, and the people and teams with the most points win.</p>
		</div>
		<h2>What Counts</h2>
		<p><strong>Generally, anything that requires you to change clothes and results in a high heart rate.</strong> This includes things like aerobically-intensive sports, strength training, fitness classes, biking, and even power walks. Just make sure that you're getting a good workout when you count your points.</p>
		<p>It does <strong>not</strong> include low-intensity activity like household chores, short walks, wrestling with younger cousins, or your commute between classes, nor does it include non-aerobic activity like dancing.</p>
		<p>The point is: you should only give yourself points if you believe that you got a good workout in.</p>
		<h2>Miles to Points Conversion</h2>
		<p>Everything you do gets converted to points. The current conversions are:</p>
		<ul>
		<?php
		$type_fetch = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'mult\_%'");
		while($type = mysqli_fetch_array($type_fetch)) {
			echo '<li><strong>'.$type['display'].':</strong> ';
			switch($type['special']) {
				case 2:
					# Minutes per point
					echo '1 point per '.$type['value'].' minute';
					if($type['value'] != 1) echo 's'; # Likely yes but just in case
					break;
				case 1:
					# Miles per point
					echo '1 point per '.$type['value'].' mile';
					if($type['value'] != 1) echo 's'; # Likely yes but just in case
					break;
				case 0:
				default:
					# Points per mile
					echo $type['value'].' point';
					if($type['value'] != 1) echo 's'; # This is usually actually a no (for 1-to-1 points)
					echo ' per mile';
			}
			echo '</li>';
		}
		?>
		</ul>
		<p>You can get Team Running points at Tuesday Monument and Saturday Brunch practices. Details are generally posted on the team wiki page ahead of time.</p>
		<h3>Altitude Compensation Bonus</h3>
		<p>If you're at altitude - no worries, we recognize that your oxygen supply is limited, so you get a multiplier depending on how high up you are:</p>
		<ul>
		<?php
		$alt_fetch = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'alt\_%' ORDER BY value ASC");
		while($alt = mysqli_fetch_array($alt_fetch)) {
			echo '<li><strong>'.$alt['display'].'</strong>: '.$alt['value'].'&times; multiplier of your total score</li>';
		}
		?>
		</ul>
		<p><strong>Note:</strong> coaches may ask you for verification of your altitude, so we suggest taking photos or using a GPS watch while you're up there.</p>
		<h2>Point Limits</h2>
	</div>
</div>
<?php
include('../php/foot.php');
?>