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

$stars = array("None", "Bronze", "Silver", "Gold", "Platinum", "Diamond");

$title = 'Rules of the Game';
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
		<p>It does <strong>not</strong> include low-intensity activity like household chores, short walks, wrestling with younger cousins, or your commute between classes, nor does it include non-aerobic activity like dancing, golf, or Wii Fit.</p>
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
			echo '<li><strong>'.$alt['display'].':</strong> '.$alt['value'].'&times; multiplier of your total score</li>';
		}
		?>
		</ul>
		<p><strong>Note:</strong> coaches may ask you for verification of your altitude, so we suggest taking photos or using a GPS watch while you're up there.</p>
		<h2>Caps and Limits</h2>
		<p>There is only one "true" limit: be honest with what you log. We generally don't have an issue, but please don't put in more than what you actually did. If in doubt, round down.</p>
		<p>Some activities are limited, or "capped", at certain thresholds. The current caps are:</p>
		<ul>
		<?php
		$cap_fetch = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'cap\_week\_%' ORDER BY value ASC");
		while($cap = mysqli_fetch_array($cap_fetch)) {
			echo '<li><strong>'.$cap['display'].':</strong> '.$cap['value'].' points per week</li>';
		}
		?>
		</ul>
		<p>For iFantasyFitness, one week is 7 consecutive days. We check and clear cap data every night.</p>
		<h2>What You Get</h2>
		<p>Aside from a better athletic base, iFantasyFitness has a couple awards that you can be eligible for:</p>
		<ul>
			<li><strong>Top individual:</strong> be in the top two men/women of your division. (This is for Upperclassmen and Underclassmen divisions only)</li>
			<li><strong>Top team:</strong> be on the winning team.</li>
			<li><strong>Distance awards:</strong> run a lot! Thresholds are below.</li>
		</ul>
		<p>We hand out awards at the August Team BBQ at the end of the season.</p>
		<h3>Distance awards</h3>
		<p>Distance stars are awarded to people that run a lot. You'll get a certificate if you reach Gold, a t-shirt if you reach Platinum, and a bar for your letter jacket if you reach Diamond. Pretty sweet stuff. The thresholds are:</p>
		<div class="row">
			<div class="col-xs-6">
				<h4>Men</h4>
				<ul>
				<?php
				$mStar_fetch = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'da-m-%' ORDER BY value ASC");
				while($mStar = mysqli_fetch_array($mStar_fetch)) {
					echo '<li><strong>'.$stars[$mStar['display']].':</strong> '.$mStar['value'].' miles</li>';
				}
				?>
				</ul>
			</div>
			<div class="col-xs-6">
				<h4>Women</h4>
				<ul>
				<?php
				$mStar_fetch = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'da-f-%' ORDER BY value ASC");
				while($mStar = mysqli_fetch_array($mStar_fetch)) {
					echo '<li><strong>'.$stars[$mStar['display']].':</strong> '.$mStar['value'].' miles</li>';
				}
				?>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php
include('../php/foot.php');
?>