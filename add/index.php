<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);

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
	$now = time();
	$team_grabber = @mysqli_query($db, "SELECT * FROM tMembers WHERE user=$id ORDER BY team DESC");
	$team_no = array(0 => 0);
	$teamstuff = array();
	while($team_data = mysqli_fetch_array($team_grabber)) {
		$team_no_temp = $team_data['team'];
		# Let's check that the season *is* in competition mode.
		# Grab season
		$the_team_grabber = @mysqli_query($db, "SELECT * FROM tData WHERE id=$team_no_temp");
		$the_team = mysqli_fetch_array($the_team_grabber);
		$the_season_name = $the_team['season'];
		$the_season_checker = @mysqli_query($db, "SELECT * FROM seasons WHERE name='$the_season_name' AND $now > comp_start AND $now < comp_end");
		
		# If the season is not in competition mode do not include it for records.
		if(mysqli_num_rows($the_season_checker) == 0) $team_no_temp = 0;
		if($team_no_temp != 0) {
			$team_no[] = $team_no_temp;
			$teamstuff[$team_data['team']] = $team_data;
		}
	}
} else {
	setcookie('iff-id',0,4,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com');
}

# Grab CAPS!
$capped_types = array();
$cap_fetcher = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'cap\_week\_%'");
while($type = mysqli_fetch_array($cap_fetcher)) {
	$capped_types[substr($type['name'], 9)] = $type['value'];
}

if(isset($_POST['submitted'])) {
	$record_types = array('run','run_team','rollerski','walk','hike','bike','swim','paddle','strength','sports');
	if($_POST['submitted'] == 'quick') {
		$type = filter_var($_POST['type'],FILTER_SANITIZE_SPECIAL_CHARS);
		$value = filter_var($_POST['distance'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$comments = filter_var($_POST['comments'],FILTER_SANITIZE_SPECIAL_CHARS);
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
		
		if(array_key_exists($type, $capped_types) and $team_no > 0) {
			# This record is capped.
			$cap_start = $team_data['week_'.$type];
			if($cap_start + $total > $capped_types[$type]) {
				# This record exceeds the cap.
				$total = $capped_types[$type] - $cap_start;
				# Update value accordingly
				if($mult_info['special'] == 0) {
					$value = $total / $mult;
				} else {
					$value = $total * $mult;
				}
				setcookie('cap',$type,$now+10,'/','.ifantasyfitness.com');
			}
		}
		
		if(strlen($comments) <= 3) $comments = "";
		if($total > 0) {
			$disp_id = $id.$now;
			foreach($team_no as $no) {
				$inserter = @mysqli_query($db, "INSERT INTO records (user, team, timestamp, `$type`, `$type".'_p'."`, total, comments, source, disp_id) VALUES ($id, $no, $now, $value, $total, $total, '$comments', 'quick', $disp_id)");
				if($no > 0) {
					$newSeasonTotal = $teamstuff[$no]['season_total'] + $total;
					$newWeekTotal = $teamstuff[$no]['week_total'] + $total;
					$newDayTotal = $teamstuff[$no]['day_total'] + $total;
					$updater_q = "UPDATE tMembers SET season_total=$newSeasonTotal, day_total=$newDayTotal, week_total=$newWeekTotal";
					if($type == "run" or $type == "run_team") {
						$newSeasonRun = $teamstuff[$no]['season_run'] + $value;
						$newWeekRun = $teamstuff[$no]['week_run'] + $value;
						$newDayRun = $teamstuff[$no]['day_run'] + $value;
						$updater_q .= ", day_run=$newDayRun, week_run=$newWeekRun, season_run=$newSeasonRun";
					}
					$updater_q .= " WHERE user=$id AND team=$no";
					$updater = @mysqli_query($db, $updater_q);
					
					$team_info_grab = @mysqli_query($db, "SELECT * FROM tData WHERE id=$no");
					$team_info = mysqli_fetch_array($team_info_grab);
					$newTotal = $team_info['total'] + $total;
					$newRTotal = $team_info['running'] + $run_total;
					$team_update = @mysqli_query($db, "UPDATE tData SET total=$newTotal, running=$newRTotal WHERE id=$no");
				}
			}
			setcookie('total',round($total,2),$now+10,'/','.ifantasyfitness.com');
		}
		header("Location: http://www.ifantasyfitness.com/home");
	} elseif ($_POST['submitted'] == 'standard') {
		# Data is coming from full add
		$types = array('run','run_team','rollerski','walk','hike','swim','bike','paddle','strength','sports');
		$data_fields = array();
		$data_values = array();
		$total = 0;
		$disp_id = $id.$now;
		
		# Grab altitude
		$alt_fname = 'alt_'.filter_var($_POST['altitude'],FILTER_SANITIZE_SPECIAL_CHARS);
		$alt_grabber = @mysqli_query($db, "SELECT * FROM globals WHERE name='$alt_fname'");
		$alt_info = mysqli_fetch_array($alt_grabber);
		$alt = $alt_info['value'];
		$now = time();
		$run_flag = false;
		$update_data = array();
		
		foreach($types as $type) {
			$mult_fname = 'mult_'.$type;
			if(empty($_POST[$type])) {
				$value = 0;
			} else {
				$value = filter_var($_POST[$type], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
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
			
			# Quick - Is the record capped?
			if(array_key_exists($type, $capped_types) and $team_no > 0) {
				# Yes.
				$cap_start = $team_data['week_'.$type];
				if($cap_start + $points > $capped_types[$type]) {
					# Record has exceeded cap.
					$points = $capped_types[$type] - $cap_start;
					# Update value accordingly
					if($mult_info['special'] == 0) {
						$value = $points / $mult / $alt;
					} else {
						$value = $points * $mult / $alt;
					}
					setcookie('cap',$type,$now+10,'/','.ifantasyfitness.com');
				}
			}
			
			$total += $points;
			$data_values[] = $value;
			$data_values[] = $points;
			if($team_no > 0) {
				if($type == "run" or $type == "run_team") {
					$run_total += $value;
					$run_flag = true;
				}
				if($type != "run") {
					$update_value = $teamstuff[$no]['week_'.$type] + $points;
					$update_data[$type] = $update_value;
				}
			}
		}
		
		# Stage an update
		foreach($team_no as $no) {
			if($no > 0) {
				$updater_q = "UPDATE tMembers SET flag=1";
				foreach($update_data as $type => $value) {
					$updater_q .= ", week_$type=$value";
				}
				if($run_flag) {
					$newSeasonRun = $team_data['season_run'] + $run_total;
					$newWeekRun = $team_data['week_run'] + $run_total;
					$newDayRun = $team_data['day_run'] + $run_total;
					$updater_q .= ", day_run=$newDayRun, week_run=$newWeekRun, season_run=$newSeasonRun";
				}
				$newSeasonTotal = $team_data['season_total'] + $total;
				$newWeekTotal = $team_data['week_total'] + $total;
				$newDayTotal = $team_data['day_total'] + $total;
				$updater_q .= ", season_total=$newSeasonTotal, day_total=$newDayTotal, week_total=$newWeekTotal WHERE user=$id AND team=$no";
				$updater = @mysqli_query($db, $updater_q);
				
				$team_info_grab = @mysqli_query($db, "SELECT * FROM tData WHERE id=$no");
				$team_info = mysqli_fetch_array($team_info_grab);
				$newTotal = $team_info['total'] + $total;
				$newRTotal = $team_info['running'] + $run_total;
				$team_update = @mysqli_query($db, "UPDATE tData SET total=$newTotal, running=$newRTotal WHERE id=$no");
				
				# If there are any oddities they will be resolved by cron each hour.
			}
			# Data collected and stored.
			# Next - put the data into a query command
			$add_query = "INSERT INTO records (";
			foreach($data_fields as $type) {
				$add_query .= $type.', ';
			}
			$add_query .= "user, timestamp, total, team, comments, source, altitude, disp_id) VALUES (";
			foreach($data_values as $value) {
				$add_query .= $value.', ';
			}
			
			# grab and clean up things
			$comments = filter_var($_POST['comments'],FILTER_SANITIZE_SPECIAL_CHARS);
			if(strlen($comments) <= 3) $comments = "";
			$add_query .= "$id, $now, $total, $no, '$comments', 'standard', $alt, $disp_id)";
			if($total > 0) {
				$add_cmd = @mysqli_query($db, $add_query);
			}
		}
		if($total > 0) {
			setcookie('total',round($total,2),$now+10,'/','.ifantasyfitness.com');
			header("Location: http://www.ifantasyfitness.com/home");
		}
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
		<h2>Caps</h2>
		<p>Caps limit the number of points you can earn per week in certain activities.</p>
		<?php
		foreach($capped_types as $type=>$cap) {
			$data_str = "week_".$type;
			$name_fetcher = @mysqli_query($db, "SELECT * FROM globals WHERE name='mult_$type'");
			$name_data = mysqli_fetch_array($name_fetcher);
			echo '<h4>'.$name_data['display'].'</h4>
			<p>The cap is <strong>'.$cap.'</strong> points per week. In the last 7 days, you have logged <strong>'.$team_data[$data_str].' point';
			if($team_data[$data_str] != 1) echo 's';
			echo '</strong>.</p>
			<div class="progress">
				<div class="progress-bar';
			if((100 * $team_data[$data_str] / $cap) >= 90) {
				echo '"';
			} elseif ((100 * $team_data[$data_str] / $cap) >= 80) {
				echo ' progress-bar-danger"';
			} else {
				echo ' progress-bar-success"';
			}
			echo ' style="width: '.(100 * $team_data[$data_str] / $cap).'%;"></div>
			</div>';
		}
		?>
	</div>
</div>
<?php
include('../php/foot.php');
?>