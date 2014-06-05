<?php
# check for existence of cookie

if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
include('../php/db.php');  # include data base
$id = $_COOKIE['iff-id'];

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
	$team_grabber = @mysqli_query($db, "SELECT * FROM tMembers WHERE user=$id ORDER BY team DESC");
	if(mysqli_num_rows($team_grabber) >= 1) {
		$team_data = mysqli_fetch_array($team_grabber);
		$myTeam = $team_data['team'];
	} else {
		$myTeam = 0;
	}
} else {
	setcookie('iff-id',0,4,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com');
}

function star($count) {
	for($i=0; $i<$count; $i++) {
		echo '<i class="fa fa-star"></i>';
	}
}
function unstar($count) {
	for($i=0; $i<$count; $i++) {
		echo '<i class="fa fa-star-o"></i>';
	}
}

$now = time();
$season_start = $now + (7*24*60*60);
$season_end = $now - (14*24*60*60);
$seasons = array();
$seasonDataQ = @mysqli_query($db, "SELECT * FROM seasons WHERE reg_start <= $season_start AND comp_end >= $season_end");
while($seasonData = mysqli_fetch_array($seasonDataQ)) {
	if($seasonData['reg_start'] <= $now and $seasonData['reg_end'] >= $now) {
		# Registration for this Season is open!
		$seasons[$seasonData['name']] = 'r_open_'.$seasonData['display_name'];
	} elseif ($seasonData['comp_start'] <= $now and $seasonData['comp_end'] >= $now) {
		# Competition for this Season is open!
		$day = ceil(($now - $seasonData['dailygoal_start']) / (24*60*60));
		$seasons[$seasonData['name']] = 'c_open_'.$seasonData['display_name'];
	} else {
		# Season is inactive
		if($seasonData['reg_start'] > $now) {
			$seasons[$seasonData['name']] = 'rg_soon'.$seasonData['display_name'];
		} elseif ($seasonData['comp_end'] < $now) {
			$seasons[$seasonData['name']] = 'com_end'.$seasonData['display_name'];
		}
	}
}

$stars = array("None", "Bronze", "Silver", "Gold", "Platinum", "Diamond");
if($user['gender'] == 0) {
	$da_query = "SELECT * FROM globals WHERE name LIKE 'da\-m\-%'";
} else {
	$da_query = "SELECT * FROM globals WHERE name LIKE 'da\-f\-%'";
}
$da_fetch = @mysqli_query($db, $da_query);
while($da_value = mysqli_fetch_array($da_fetch)) {
	$star_values[$da_value['display']] = $da_value['value'];
}
$star_values[0] = 0;

$title = 'Home';
$connected = true;
include('../php/head-auth.php');
?>
<div class="row">
	<div class="col-xs-12">
		<?php
		# Display Distance Running Goals, if any.
		echo '<div class="panel panel-success">
			<div class="panel-heading" id="toggle-daily-goals">
				<h3 class="panel-title">Today\'s running plans - '.date("F j, Y").'
				<a class="pull-right">Toggle plans</a>
				</h3>
			</div>
			<div class="panel-body" id="daily-goal-data" style="display: none;">';
			$goals_query = @mysqli_query($db, "SELECT * FROM dailygoals WHERE start<$now ORDER BY start DESC");
			if(mysqli_num_rows($goals_query) == 0) {
				echo '<h4>No daily running goals today!</h4>
				Take a day off, or make up your own running goal!';
			} else {
				echo '<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th class="col-xs-3 col-md-2">Stars</th>
						<th class="col-xs-3 col-md-2">Miles</th>
						<th class="col-xs-4 col-md-6">Workout notes</th>
						<th class="col-xs-2">Quick add</th>
					</tr>
				</thead>
				<tbody>';
				$goal = mysqli_fetch_array($goals_query);
				for($j = 1; $j < 6; $j++) {
					echo '<tr><td>'.$stars[$j].'</td><td>';
					if($user['gender'] == 1) {
						$field = 'f';
					} else {
						$field = 'm';
					}
					$field .= '-'.strtolower($stars[$j]);
					echo $goal[$field].'</td><td>';
					if($goal[$field] == 0) {
						echo "Rest day (or make your own goal!)";
					} else {
						echo $goal[$field . 'Notes'];
					}
					echo '</td>
					<td><a class="populate" data-value="'.$goal[$field].'" data-notes="'.$goal[$field.'Notes'].' (for '.$stars[$j].' Distance)">Quick Add</a></td>
					</tr>';
				}
				echo '</tbody></table>';
			}
			echo '</div>
		</div>';
		
		# Display messages depending on actions of other pages.
		if(isset($_COOKIE['total']) and $_COOKIE['total'] > 0) {
			echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-check"></i> Huzzah! Your record has been saved.</h4>';
			if($_COOKIE['total'] == 1) {
				echo '<p>1 point has been deposited to your account.</p>';
			} else {
				echo '<p>'.$_COOKIE['total'].' points have been deposited to your account.</p>';
			}
			echo 'You can view your record below, and edit it on your <a href="/records" class="alert-link">record transcript</a>.';
			if($day > 0) echo ' This record has been posted to the <a href="/leaderboard" class="alert-link">leaderboard</a>.';
			echo '</div>';
		}
		if(isset($_COOKIE['star'])) {
			echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-star"></i> Hooray! You got a Distance Award star!</h4>
			You are now at the '.$stars[$_COOKIE['star']].' level of Distance Awards. Keep going!';
		}
		if(isset($_COOKIE['reg-confirmed'])) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-check"></i> You have successfully registered for the '.$_COOKIE['reg-confirmed'].' season!</div>';
		if(isset($_COOKIE['reg-welcome'])) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-check"></i> Welcome to iFantasyFitness!</h4> Your account has been created, and you have successfully registered for the '.$_COOKIE['reg-welcome'].' season! Come back when the season starts to <a href="/add" class="alert-link">start adding points</a>!</div>';
		if(isset($_COOKIE['reg-fail'])) echo '<div class="alert alert-warning">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-info"></i> The '.$_COOKIE['reg-fail'].' season is not accepting registrations.</h4> The season might not exist, or we might be outside its registration window. Please check with coaches if you believe this is an error. For best results, use the buttons below to start a registration.</div>';
		if(isset($_COOKIE['reg-exists'])) echo '<div class="alert alert-info">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-info"></i> It looks like you\'re already registered for the '.$_COOKIE['reg-exists'].' season.</h4> If you need to change your settings or drop out, you can do that <a class="alert-link" href="/settings/goals">here</a>.</div>';
		if(isset($_COOKIE['cap'])) echo '<div class="alert alert-warning">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-warning"></i> Cap Exceeded</h4>
			Your record has been saved. However, it exceeded the '.$_COOKIE['cap'].' cap, and your point totals have been adjusted accordingly.</div>';
		?>
	</div>
</div>
<?php
# If registration is open give link to registration file
if(!empty($seasons)) {
	# Valid seasons
	foreach($seasons as $key => $status) {
		$season_status = substr($status, 0, 7);
		if($season_status == 'r_open_') {
			# registration is open
			# Only give the link if user IS NOT ALREADY REGISTERED TO THAT SEASON
			$season_checker = @mysqli_query($db, "SELECT * FROM tMembers WHERE user=$id AND season='$key'");
			if(mysqli_num_rows($season_checker) == 0) echo '<div class="row"><div class="col-xs-12">
			<p class="lead">Registration is open for the '.substr($status,7).' season!<a href="/register?season='.$key.'" class="btn btn-primary pull-right">Register for '.$key.'</a></p>
			</div></div>';
		} elseif ($season_status == 'rg_soon') {
			echo '<div class="row"><div class="col-xs-12">
			<p class="lead">Registration for the '.substr($status,7).' season will open soon!</p></div></div>';
		} elseif ($season_status == 'com_end') {
			echo '<div class="row"><div class="col-xs-12">
			<p class="lead">Competition in the '.substr($status,7).' season has ended.<a href="/leaderboard?filter=ind&season='.$key.'" class="btn btn-primary pull-right">Go to '.$key.' leaderboard</a></p></div></div>';
		}
	}
}
?>
<div class="row">
	<div class="col-xs-12 col-sm-7 col-md-8">
		<h2>Activity</h2>
		<?php
		$activities = @mysqli_query($db, "SELECT * FROM records ORDER BY timestamp DESC, team DESC LIMIT 100");
		$current_disp_id = 0;
		$record_types = array("run" => "Running", "run_team" => "Running at Monument", "rollerski" => "Rollerskiing", "walk" => "Walking", "hike" => "Hiking with packs", "bike" => "Biking", "swim" => "Swimming", "paddle" => "Paddling, Rowing or Kayaking", "strength" => "Strength or core training", "sports" => "Aerobic sports");
		$use_minutes = array('paddle','strength','sports');
		while($record = mysqli_fetch_array($activities)) {
			if($record['disp_id'] == $current_disp_id) {
				continue;
			} else {
				$current_disp_id = $record['disp_id'];
			}
			echo '<div class="panel';
			if($record['user'] == $id) { # this is your record
				echo ' panel-success';
			} elseif ($record['team'] == $myTeam and $myTeam != 0) { # made by a teammate
				echo ' panel-info';
			} else {
				echo ' panel-default';
			}
			echo '">
				<div class="panel-heading">
					<h3 class="panel-title">';
			$record_user = $record['user'];
			$retr_name = @mysqli_query($db, "SELECT * FROM users WHERE id=$record_user");
			$record_u_info = mysqli_fetch_array($retr_name);
			echo $record_u_info['first'].' '.$record_u_info['last'].'<span class="pull-right">';
			if($record['user'] == $id) { # this is your record
				echo '<abbr title="This record was posted by you."><i class="fa fa-user"></i></abbr> ';
			} elseif ($record['team'] == $myTeam and $myTeam != 0) { # made by a teammate
				echo '<abbr title="This record was posted by a teammate."><i class="fa fa-users"></i></abbr> ';
			}
			echo round($record['total'],2).'</span></h3>
			</div>
			<div class="panel-body">
			<table class="table">
				<thead>
					<tr>
						<th class="col-xs-6">Activity</th>
						<th class="col-xs-3">Duration</th>
						<th class="col-xs-3">Points</th>
					</tr>
				</thead>
				<tbody>';
			# Record Data
			foreach($record_types as $data=>$disp) {
				$points = $data . '_p';
				if($record[$data] != 0) {
					echo '<tr>
					<td>'.$disp.'</td>
					<td>'.round($record[$data],2);
					if(in_array($data, $use_minutes)) {
						echo ' minute';
					} else {
						echo ' mile';
					}
					if($record[$data] != 1) echo 's';
					echo '</td>
					<td>'.round($record[$points],2).'</td>
					</tr>';
				}
			}
			echo '</tbody>
			</table>';
			if($record['altitude'] != 1) echo '<p><strong>Altitude bonus awarded:</strong> x'.$record['altitude'].'</p>';
			if(!empty($record['comments'])) echo '<p><strong>Comment:</strong> '.$record['comments'].'</p>';
			echo 'Total: '.round($record['total'],2).' point';
			if($record['total'] != 1) echo 's';
			echo '<span class="pull-right">Posted: '.date('F j, Y g:i:s a',$record['timestamp']).'</span>';
			echo '</div>
			</div>';
		}
		?>
	</div>
	<div class="col-xs-12 col-sm-5 col-md-4">
		<h2>Quick Add Points</h2>
		<?php
		if($user['profile'] == 0) {
			echo ('
		<form name="quick-add" action="/add/index.php" method="post">
			<div class="row">
				<div class="col-xs-6">
					<select name="type" class="form-control">
						<option value="run">Running</option>
						<option value="run_team">Monument Running</option>
						<option value="rollerski">Rollerskiing</option>
						<option value="walk">Walking</option>
						<option value="hike">Hiking with Packs</option>
						<option value="swim">Swimming</option>
						<option value="bike">Biking</option>
					</select>
				</div>
				<div class="col-xs-6">
					<div class="input-group">
						<input type="text" class="form-control" name="distance" id="miles">
						<span class="input-group-addon">miles</span>
					</div>
					<br>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<input type="text" name="comments" placeholder="Comments (will be shared)" id="notes" class="form-control"><br>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<input type="hidden" name="submitted" value="quick">
					<input type="submit" class="btn btn-primary btn-block" value="Save Record"><br><p>If you want to record aerobic sports, strength training or paddling, if you are at altitude, or if you want to add multiple workout types in one record, please use the <a href="/add">full Add Points</a> page.</p>
				</div>
			</div>
		</form>');
		} else {
			echo '<p>You need to set up your profile first!</p><a href="/settings/profile" class="btn btn-primary btn-block">Set up profile</a>';
		}
		?>
		<hr>
		<h2>Goals and Awards</h2>
		<?php
		echo '<p><strong>Distance Awards:</strong> ';
		
		for($i = 5; $i >= 0; $i--) {
			if($team_data['season_run'] >= $star_values[$i]) {
				star($i);
				unstar(5 - $i);
				$level = $i;
				$full = $star_values[$i];
				if($i < 5) $toNext = $star_values[$i + 1] - $team_data['season_run'];
				break;
			}
		}
		echo ' ('.$stars[$level].')';
		if($team_data == $full) {
			$aw_value = 100;
		} else {
			$aw_value = ($team_data['season_run'] / ($team_data['season_run'] + $toNext)) * 100;
		}
		echo '<br>'.round($team_data['season_run'],2).' mile';
		if($team_data['season_run'] != 1) echo 's';
		echo ' ran.';
		if($toNext > 0) echo ' '.round($toNext, 2).' mile';
		if($toNext != 1 and $toNext > 0) echo 's'; # Silly grammar and pluralization.
		if($toNext > 0) echo ' until '.$stars[$level + 1].'!';
		echo '</p>
		<div class="progress">
			<div class="progress-bar ';
		if($aw_value >= 100) {
			echo ' progress-bar-warning';
		} elseif ($aw_value >= 75) {
			echo ' progress-bar-info';
		} elseif ($aw_value >= 50) {
			echo ' progress-bar-success';
		} elseif ($aw_value >= 25) {
			echo ' progress-bar-danger';
		}
		echo '" aria-valuenow="'.$aw_value.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$aw_value.'%;"></div>
		</div>';
		
		# Season prediction
		if($team_data['prediction'] == 0) {
			$prog_value = 0;
			echo '<p><strong>Season goal:</strong> No prediction set!</p>';
		} else {
			$prog_value = ($team_data['season_total'] / $team_data['prediction']) * 100;
			echo '<p><strong>Season goal:</strong> '.round($team_data['season_total'],2).' of '.$team_data['prediction'].' points scored</p>';
		}
		echo '<div class="progress">
			<div class="progress-bar ';
		if($prog_value >= 100) {
			echo ' progress-bar-warning';
		} elseif ($prog_value >= 75) {
			echo ' progress-bar-info';
		} elseif ($prog_value >= 50) {
			echo ' progress-bar-success';
		} elseif ($prog_value >= 25) {
			echo ' progress-bar-danger';
		}
		echo '" aria-valuenow="'.$prog_value.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$prog_value.'%;"></div>
		</div>';
		?>
		<hr>
		<h2>Quick Links</h2>
		<ul>
			<li><a href="/add">Add points</a></li>
			<li><a href="/leaderboard">View leaderboard</a></li>
			<li><a href="/records">View records</a></li>
			<li><a href="/records/print">Print reports</a></li>
			<li><a href="/add/import">Import records</a></li>
			<li><a href="http://dft.ba/-iFFRules">Message Rules Committee</a></li>
			<li><a href="http://blog.ifantasyfitness.com">iFF Blog</a></li>
			<li><a href="/settings/profile">Add/remove social networks</a></li>
			<li><a href="http://www.dreamhost.com/donate.cgi?id=17581">Support us</a> (Help offset our hosting bill)</li>
			<li><a href="/logout">Sign out</a></li>
		</ul>
		<hr>
		<h2>No Ads Here!</h2>
		<p>We could put an ad here, but we didn't, and we won't. :)</p>
		<p>There are two main reasons for this:
			<ul>
				<li>we don't like ads, and</li>
				<li>there's a better way for us to keep the site online: <a href="http://www.dreamhost.com/donate.cgi?id=17581">voluntary donations</a>.</li>
			</ul>
		</p>
		<p>More details are <a href="http://blog.ifantasyfitness.com/2014/05/advertisements-donations-and-financial-transparency/">on the blog</a>.</p>
	</div>
</div>
<?php
include('../php/foot.php');
?>
