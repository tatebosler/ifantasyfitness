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

if(isset($_GET['disp'])) {
	$mode = $_GET['disp'];
} else {
	$mode = 'a';
}

if(isset($_GET['season'])) {
	$season = $_GET['season'];
	$s = true;
} else {
	# figure out what season it is
	$now = time();
	$season_finder = @mysqli_query($db, "SELECT * FROM seasons WHERE comp_start <= $now ORDER BY comp_start DESC");
	if(mysqli_num_rows($season_finder) == 0) {
		$s = false;
	} else {
		$season_data = mysqli_fetch_array($season_finder);
		$season = $season_data['name'];
		$s = true;
	}
}
?>
<div class="row">
	<div class="col-xs-12">
		<h2>Leaderboard</h2>
	</div>
</div>
<div class="row">
	<div class="col-md-3">
		<div class="hidden-xs hidden-sm list-group">
			<?php
			$season_fetcher = @mysqli_query($db, "SELECT * FROM seasons");
			while($se = mysqli_fetch_array($season_fetcher)) {
				echo '<a href="?season='.$se['name'].'&disp='.$mode;
				echo '" class="list-group-item';
				if($se['name'] == $season) echo ' active';
				echo '">'.$se['display_name'].'</a>';
			}
			?>
		</div>
		<div class="hidden-xs hidden-sm list-group">
			<?php
			$divisions = array('a' => "Individuals", 'r' => "Running", 't' => "Teams", 0 => "Upperclassmen", 1 => "Underclassmen", 2 => "Middle School", 3 => "Staff", 4 => "Parents", 5 => "Alumni");
			foreach($divisions as $key => $value) {
				echo '<a href="?season='.$season.'&disp='.$key.'" class="list-group-item';
				if($mode == $key) echo ' active';
				echo '">'.$value.'</a>';
			}
			?>
		</div>
		<div class="hidden-md hidden-lg">
			
		</div>
	</div>
	<div class="col-md-9">
		<?php
		# This page can be parameterized via GET parameters
		# season - restricts to a specific season id, if omitted, assumes most recent competition start
		# disp - restricts display of results to a specific group.
		#      0-5 gives division, t gives teams only, r gives running only
		# If we don't have a season, stop
		if($s) {
			# Figure out team number
			if(isset($id)) {
				$team_fetcher = @mysqli_query($db, "SELECT * FROM tMembers WHERE season='$season' AND user=$id");
				if(mysqli_num_rows($team_fetcher) == 0) {
					$team = 0;
				} else {
					$my_data = mysqli_fetch_array($team_fetcher);
					$team = $my_data['team'];
				}
			} else {
				$team = 0;
			}
			switch($mode) {
				case 0: # Upperclassmen
				case 1: # Underclassmen
				case 2: # Middle school
				case 3: # Staff
				case 4: # Parents
				case 5: # Alumni
					$data_fetcher = @mysqli_query($db, "SELECT * FROM tMembers WHERE season='$season' AND division='$mode' ORDER BY season_total DESC");
					if(mysqli_num_rows($data_fetcher) == 0) {
						echo '<h4>Nobody has registered for this division!</h4>';
					} else {
						echo '<table class="table table-striped table-hover">
						<thead>
						<tr>
						<th class="col-xs-2">#</th>
						<th class="col-xs-4">User</th>
						<th class="col-xs-3">Points</th>
						<th class="col-xs-3">Running</th>
						</tr>
						</thead>';
						$pl = 1;
						while($person = mysqli_fetch_array($data_fetcher)) {
							echo '<tr';
							if($person['user'] == $id) echo ' class="success"';
							if($person['team'] == $team and $team > 0) echo ' class="info"';
							echo '><td>'.$i.'</td><td>';
							if($person['user'] == $id) echo '<abbr title="This is you!"><i class="fa fa-user"></i></abbr> ';
							if($person['team'] == $team and $team > 0) echo '<abbr title="This is a teammate!"><i class="fa fa-users"></i></abbr> ';
							# Figure out their name!
							$pid = $person['user'];
							$the_user_fetcher = @mysqli_query($db, "SELECT * FROM users WHERE id=$pid");
							$the_user = mysqli_fetch_array($the_user_fetcher);
							echo $the_user['first'].' '.$the_user['last'].'</td>
							<td>'.round($person['season_total'],3).'</td>
							<td>'.round($person['season_running'],3).'</td>
							</tr>';
						}
					}
					break;
				case 't': # Display team scores
					$data_fetcher = @mysqli_query($db, "SELECT * FROM tData WHERE season='$season'");
					if(mysqli_num_rows($data_fetcher) == 0) {
						echo '<h4>No teams have been configured for this season!</h4>';
					} else {
						echo '<table class="table table-striped table-hover">
						<thead>
						<tr>
						<th class="col-xs-2">#</th>
						<th class="col-xs-4">Team</th>
						<th class="col-xs-3">Points</th>
						<th class="col-xs-3">Running</th>
						</tr>
						</thead>';
						$pl = 1;
						while($the_team = mysqli_fetch_array($data_fetcher)) {
							echo '<tr';
							if($the_team['id'] == $team) echo ' class="success"';
							echo '><td>'.$i.'</td><td>'.$the_team['name'].'</td>
							<td>'.round($the_team['total'],4).'</td>
							<td>'.round($the_team['running'],4).'</td>
							</tr>';
						}
					}
					break;
				case 'r': # Display sorted by running scores
				default: # Anything else, assume all individuals by points.
					$dftext = "SELECT * FROM tMembers WHERE season='$season' ORDER BY season_";
					if($_GET['disp'] == 'r') {
						$dftext .= "run";
					} else {
						$dftext .= "total";
					}
					$dftext .= " DESC";
					$data_fetcher = @mysqli_query($db, $dftext);
					if(mysqli_num_rows($data_fetcher) == 0) {
						echo '<h4>Nobody has registered for this season!</h4>';
					} else {
						echo '<table class="table table-striped table-hover">
						<thead>
						<tr>
						<th class="col-xs-2">#</th>
						<th class="col-xs-4">User</th>
						<th class="col-xs-3">Points</th>
						<th class="col-xs-3">Running</th>
						</tr>
						</thead>';
						$pl = 1;
						while($person = mysqli_fetch_array($data_fetcher)) {
							echo '<tr';
							if($person['user'] == $id) echo ' class="success"';
							if($person['team'] == $team and $team > 0) echo ' class="info"';
							echo '><td>'.$i.'</td><td>';
							if($person['user'] == $id) echo '<abbr title="This is you!"><i class="fa fa-user"></i></abbr> ';
							if($person['team'] == $team and $team > 0) echo '<abbr title="This is a teammate!"><i class="fa fa-users"></i></abbr> ';
							# Figure out their name!
							$pid = $person['user'];
							$the_user_fetcher = @mysqli_query($db, "SELECT * FROM users WHERE id=$pid");
							$the_user = mysqli_fetch_array($the_user_fetcher);
							echo $the_user['first'].' '.$the_user['last'].'</td>
							<td>'.round($person['season_total'],3).'</td>
							<td>'.round($person['season_running'],3).'</td>
							</tr>';
						}
					}
			}
		} else {
			echo '<h4>No seasons exist!</h4>
			<p>Ask your coach to make one in Settings &rarr; Seasons &rarr; Create Season.</p>';
		}
		?>
	</div>
</div>
<?php
include('../php/foot.php');
?>