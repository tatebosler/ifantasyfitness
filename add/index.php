<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
$id = $_COOKIE['iff-id'];

# Validate the user
include('../php/db.php');
$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
if(mysqli_num_rows($check_q) > 0) {
	$user = mysqli_fetch_array($check_q);
	# confirm with social token
	$valid = false;
	if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
	if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	if(!$valid) header('Location: http://www.ifantasyfitness.com');
	
	# now grab the user's team number
	# If no team number is in use, use 0.
	# If season is not in competition mode, use 0 (even if user has been assigned to a team).
	$team_grabber = @mysqli_query($db, "SELECT * FROM tMembers WHERE user=$id ORDER BY team DESC");
	if(mysqli_num_rows($team_grabber) >= 1) {
		$team_data = mysqli_fetch_array($team_grabber);
		$team_no = $team_data['team'];
	} else {
		$team_no = 0;
	}
	$now = time();
	$season_grabber = @mysqli_query($db, "SELECT * FROM seasons WHERE $now > comp_start AND $now < comp_end");
	if(mysqli_num_rows($season_grabber) == 0) $team_no = 0;
} else {
	setcookie('iff-id',0,4,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com');
}

if(isset($_POST['submitted'])) {
	$record_types = array('run','run_team','rollerski','walk','hike','bike','swim','paddle','strength','sports');
	if($_POST['submitted'] == 'quick') {
		$type = $_POST['type'];
		$value = $_POST['distance'];
		$comments = filter_var($_POST['comments'],FILTER_SANITIZE_STRING);
		# Get the multiplier
		$mult_fname = 'mult_'.$type;
		$mult_grabber = @mysqli_query($db, "SELECT * FROM globals WHERE name='$mult_fname'");
		$mult_info = mysqli_fetch_array($mult_grabber);
		$mult = $mult_info['value'];
		
		# Insert to database
		if($mult_info['special'] == 0) {
			$total = $value * $mult;
		} else {
			$total = $value / $mult;
		}
		$now = time();
		if(strlen($comments) <= 3) $comments = "";
		if($total > 0) {
			$inserter = @mysqli_query($db, "INSERT INTO records (user, team, timestamp, `$type`, `$type".'_p'."`, total, comments, source) VALUES ($id, $team_no, $now, $value, $total, $total, '$comments', 'quick')");
			setcookie('total',round($total,2),$now+10,'/','.ifantasyfitness.com');
		}
		header("Location: http://www.ifantasyfitness.com/home");
	} elseif ($_POST['submitted'] == 'standard') {
		# Data is coming from full add
		$types = array('run','run_team','rollerski','walk','hike','swim','bike','paddle','strength','sports');
		$data_fields = array();
		$data_values = array();
		$total = 0;
		# Grab altitude
		$alt_fname = 'alt_'.$_POST['altitude'];
		$alt_grabber = @mysqli_query($db, "SELECT * FROM globals WHERE name='$alt_fname'");
		$alt_info = mysqli_fetch_array($alt_grabber);
		$alt = $alt_info['value'];
		foreach($types as $type) {
			$mult_fname = 'mult_'.$type;
			if(empty($_POST[$type])) {
				$value = 0;
			} else {
				$value = $_POST[$type];
			}
			$mult_fname = 'mult_'.$type;
			$mult_grabber = @mysqli_query($db, "SELECT * FROM globals WHERE name='$mult_fname'");
			$mult_info = mysqli_fetch_array($mult_grabber);
			$mult = $mult_info['value'];
			$data_fields[] = $type;
			$data_fields[] = $type . '_p';
			if ($type == 'swim' and $_POST['swim_unit'] != 'miles') { # Swimming can have its own units, so treat it first
				switch($_POST['swim_unit']) {
					case 'meters':
						$value /= 1609.344;
						break;
					case 'yards':
						$value /= 1760;
						break;
					default: # assume feet as default case
						$value /= 5280;
				}
				$points = $value * $mult * $alt;
			} elseif($mult_info['special'] == 0) {
				$points = $value * $mult * $alt;
			} else {
				$points = $value / $mult * $alt;
			}
			$total += $points;
			$data_values[] = $value;
			$data_values[] = $points;
		}
		# Data collected and stored.
		# Next - put the data into a query command
		$add_query = "INSERT INTO records (";
		foreach($data_fields as $type) {
			$add_query .= $type.', ';
		}
		$add_query .= "user, timestamp, total, team, comments, source, altitude) VALUES (";
		foreach($data_values as $value) {
			$add_query .= $value.', ';
		}
		# grab and clean up things
		$now = time();
		$comments = filter_var($_POST['comments'],FILTER_SANITIZE_STRING);
		if(strlen($comments) <= 3) $comments = "";
		$add_query .= "$id, $now, $total, $team_no, '$comments', 'standard', $alt)";
		if($total > 0) {
			$add_cmd = @mysqli_query($db, $add_query);
			setcookie('total',round($total,2),$now+10,'/','.ifantasyfitness.com');
		}
		header("Location: http://www.ifantasyfitness.com/home");
	}
}

$title = 'Add Points';
$connected = true;
include('../php/head-auth.php');
?>
<div class="row">
	<div class="col-xs-12 col-md-8">
		<h2>Add Points</h2>
		<?php
		if($user['profile'] == 1) {
			echo '<div class="alert alert-warning">
				<h4><strong>You need to set up your profile before you can add records!</strong></h4>
				<a class="btn btn-danger btn-block" href="/settings/profile">Set up profile</a>
				</div>';
			echo '<p>Once you set up your profile (click the above button), you will be able to:</p>
				<ul>
					<li>Post records</li>
					<li>Import records from MapMyRun, RunKeeper, Garmin, and Strava</li>
					<li>Print record reports</li>
					<li>Share records to Facebook, Twitter and Google+</li>
				</ul>
				<p>... and more!</p>
				<p><a href="/settings/profile">Click here</a> (or the above button) to set up your profile.</p>';
		} else {
			echo '<form name="add-full" method="post" class="form-horizontal">';
			$multiplier_list_q = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'mult\_%'");
			while($mult_data = mysqli_fetch_array($multiplier_list_q)) {
				echo '<div class="form-group">
				<label class="col-xs-3 control-label">'.$mult_data['display'].'</label>
				<div class="col-xs-9 col-md-8 input-group">
					<input type="text" class="form-control" name="'.substr($mult_data['name'],5).'">
					<span class="input-group-addon">';
				if($mult_data['special'] < 2 and $mult_data['name'] != 'mult_swim') {
					echo 'miles';
				} elseif ($mult_data['special'] == 2) {
					echo 'minutes';
				} else {
					echo '<span class="visible-xs">miles</span>
					<select name="swim_unit" class="hidden-xs">
							<option value="miles" selected>miles</option>
							<option value="meters">meters</option>
							<option value="yards">yards</option>
							<option value="feet">feet</option>
						</select>';
				}
				echo'</span></div>
				</div>';
			}
			echo '<div class="form-group">
				<label class="col-xs-3 control-label">Altitude</label>
				<div class="col-xs-9 col-md-8 input-group">
					<select name="altitude" class="form-control">
						<option value="to_5280">Below 5,280 feet</option>
						<option value="5280_8000">5,280 to 8,000 feet</option>
						<option value="8000_10000">8,000 to 10,000 feet</option>
						<option value="10000_12500">10,000 to 12,500 feet</option>
						<option value="12500_15000">12,500 to 15,000 feet</option>
						<option value="from_15000">Over 15,000 feet</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-3 control-label">Comments</label>
				<div class="col-xs-9 col-md-8 input-group">
					<textarea class="form-control" rows="5" name="comments" placeholder="Maximum length 65,535 characters. This comment will be visible in the Activity Feed, but not on the leaderboards."></textarea>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-offset-3 col-xs-9 col-sm-8 input-group">
					<input type="submit" class="btn btn-primary" value="Save Record">
					<input type="hidden" name="submitted" value="standard">
				</div>
			</div>
			</form>';
		}
		?>	
	</div>
	<div class="hidden-xs hidden-sm col-md-4">
		<h2>Rules</h2>
		<p>Please be sure to read <a href="/rules">the rules</a> before posting your record so you know what counts for points.</p>
		<p>If you have a question about whether something counts, you can <a href="/rules/ask">ask the Rules Committee here</a>.</p>
		<h2>Multipliers</h2>
		<table class="table">
			<thead>
				<tr>
					<th>Activity</th>
					<th>Multiplier</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$multiplier_list_q = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'mult\_%'");
			while($mult_data = mysqli_fetch_array($multiplier_list_q)) {
				echo '<tr><td>'.$mult_data['display'].'</td>';
				if($mult_data['special'] == 1) {
					echo '<td>1 point per '.$mult_data['value'].' miles</td>';
				} elseif ($mult_data['special'] == 0) {
					echo '<td>'.$mult_data['value'].' points per mile</td>';
				} else {
					echo '<td>1 point per '.$mult_data['value'].' minutes</td>';
				}
				echo '</tr>';
			}
			?>
			</tbody>
		</table>
	</div>
</div>
<?php
include('../php/foot.php');
?>