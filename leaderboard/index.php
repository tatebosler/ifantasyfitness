<?php
#  establish user identity
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
$mode = 'a';
if(isset($_GET['disp'])) {
	$mode = filter_var($_GET['disp'],FILTER_SANITIZE_SPECIAL_CHARS);
}

if(isset($_GET['season'])) {
	$season = filter_var($_GET['season'],FILTER_SANITIZE_SPECIAL_CHARS);
	$s = true;
} else {
	# figure out what season it is
	$now = time();
	$season_count = @mysqli_query($db, "SELECT * FROM seasons");
	if(mysqli_num_rows($season_count) == 1) {
		$season_data = mysqli_fetch_array($season_count);
		$season = $season_data['name'];
		$s = true;
	} elseif (mysqli_num_rows($season_count) == 0) {
		$s = false;
	} else {
		$season_finder = @mysqli_query($db, "SELECT * FROM seasons WHERE comp_start <= $now ORDER BY comp_start DESC");
		if(mysqli_num_rows($season_finder) == 0) {
			$s = false;
		} else {
			$season_data = mysqli_fetch_array($season_finder);
			$season = $season_data['name'];
			$s = true;
		}
	}
}

# Function for printing stars
function star($number, $level) {
	$return = ' <abbr title="'.$level.' Distance Award recipient!">';
	for ($i = 0; $i < $number; $i++) {
		$return .= '<i class="fa fa-star"></i>';
	}
	$return .= "</abbr>";
	return $return;
}

function stars($miles, $gender) {
	# Women's awards
	if($gender == 1) {
		if($miles >= 400) {
			return star(5, "Women's Diamond");
		} elseif ($miles >= 300) {
			return star(4, "Women's Platinum");
		} elseif ($miles >= 250) {
			return star(3, "Women's Gold");
		} elseif ($miles >= 200) {
			return star(2, "Women's Silver");
		} elseif ($miles >= 150) {
			return star(1, "Women's Bronze");
		}
	} else {
		# Men's awards
		if($miles >= 500) {
			return star(5, "Men's Diamond");
		} elseif ($miles >= 400) {
			return star(4, "Men's Platinum");
		} elseif ($miles >= 325) {
			return star(3, "Men's Gold");
		} elseif ($miles >= 250) {
			return star(2, "Men's Silver");
		} elseif ($miles >= 150) {
			return star(1, "Men's Bronze");
		}
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
		<h4 class="hidden-xs hidden-sm">Select Season</h4>
		<div class="hidden-xs hidden-sm list-group">
			<?php
			$season_fetcher = @mysqli_query($db, "SELECT * FROM seasons ORDER BY display_name ASC");
			while($se = mysqli_fetch_array($season_fetcher)) {
				echo '<a href="?season='.$se['name'].'&disp='.$mode;
				echo '" class="list-group-item';
				if($se['name'] == $season) echo ' active';
				echo '">'.$se['display_name'].'</a>';
			}
			?>
		</div>
		<h4 class="hidden-xs hidden-sm">Select Division</h4>
		<div class="hidden-xs hidden-sm list-group">
			<?php
			$divisions = array('a' => "All Individuals", 'r' => "Running", 't' => "Teams", 1 => "Upperclassmen", 2 => "Underclassmen", 3 => "Middle School", 4 => "Staff", 5 => "Parents", 6 => "Alumni");
			foreach($divisions as $key => $value) {
				echo '<a href="?season='.$season.'&disp='.$key.'" class="list-group-item';
				if($mode == $key) echo ' active';
				echo '">'.$value.'</a>';
			}
			?>
		</div>
		<div class="row visible-xs visible-sm">
			<div class="col-xs-6">
				<h4>Select Season</h4>
				<form name="sel_season">
					<select name="SelSeason" onchange="document.location.href=document.sel_season.SelSeason.options[document.sel_season.SelSeason.selectedIndex].value" class="form-control">
					<?php
					$season_fetcher = @mysqli_query($db, "SELECT * FROM seasons ORDER BY display_name ASC");
					while($se = mysqli_fetch_array($season_fetcher)) {
						echo '<option value="/leaderboard?season='.$se['name'].'&disp='.$mode.'"';
						if($se['name'] == $season) echo ' selected';
						echo '>'.$se['display_name'].'</option>';
					}
					?>
					</select>
				</form>
			</div>
			<div class="col-xs-6">
				<h4>Select Division</h4>
				<form name="sel_disp">
					<select name="SelDisp" onchange="document.location.href=document.sel_disp.SelDisp.options[document.sel_disp.SelDisp.selectedIndex].value" class="form-control">
					<?php
					$divisions = array('a' => "All Individuals", 'r' => "Running", 't' => "Teams", 1 => "Upperclassmen", 2 => "Underclassmen", 3 => "Middle School", 4 => "Staff", 5 => "Parents", 6 => "Alumni");
					foreach($divisions as $key => $value) {
						echo '<option value="/leaderboard?season='.$season.'&disp='.$key.'"';
						if($mode == $key) echo ' selected';
						echo '>'.$value.'</option>';
					}
					?>
					</select>
				</form>
			</div>
		</div>
	</div>
	<div class="col-md-9">
		<?php
		# This page can be parameterized via GET parameters
		# season - restricts to a specific season id, if omitted, assumes most recent competition start
		# disp - restricts display of results to a specific group.
		#      0-5 gives division, t gives teams only, r gives running only
		# If we don't have a season, don't present data
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
				case 1: # Upperclassmen
				case 2: # Underclassmen
				case 3: # Middle school
				case 4: # Staff
				case 5: # Parents
				case 6: # Alumni
					$data_fetcher = @mysqli_query($db, "SELECT * FROM tMembers WHERE season='$season' AND division='$mode' ORDER BY season_total DESC, season_run DESC, user ASC");
					if(mysqli_num_rows($data_fetcher) == 0) {
						echo '<h4>Nobody has registered for this division!</h4>';
					} else {
						echo '<table class="table table-striped table-hover">
						<thead>
						<tr>
						<th class="col-xs-2">Place</th>
						<th class="col-xs-7 col-sm-4">User</th>
						<th class="col-xs-3">Points</th>
						<th class="hidden-xs col-sm-3">Running</th>
						</tr>
						</thead>
						<tbody>';
						$pl = 0;
						while($person = mysqli_fetch_array($data_fetcher)) {
							$pl++;
							echo '<tr';
							if($person['user'] == $id) echo ' class="success"';
							if($person['team'] == $team and $team > 1 and $person['user'] != $id) echo ' class="info"';
							echo '><td>'.$pl.'</td><td>';
							if($person['user'] == $id) echo '<abbr title="This is you!"><i class="fa fa-user"></i></abbr> ';
							if($person['team'] == $team and $team > 1 and $person['user'] != $id) echo '<abbr title="This is a teammate!"><i class="fa fa-users"></i></abbr> ';
							# Figure out their name!
							$pid = $person['user'];
							$the_user_fetcher = @mysqli_query($db, "SELECT * FROM users WHERE id=$pid");
							$the_user = mysqli_fetch_array($the_user_fetcher);
							echo $the_user['first'].' '.$the_user['last'];
							if($person['season_run'] >= 150) echo stars($person['season_run'], $the_user['gender']);
							echo '</td>
							<td>'.round($person['season_total'],3).'</td>
							<td class="hidden-xs">'.round($person['season_run'],3).'</td>
							</tr>';
						}
						echo '</tbody>
							</table>';
					}
					break;
				case 't': # Display team scores
					$data_fetcher = @mysqli_query($db, "SELECT * FROM tData WHERE season='$season' ORDER BY total DESC");
					if(mysqli_num_rows($data_fetcher) == 0) {
						echo '<h4>No teams have been configured for this season!</h4>';
					} else {
						echo '<table class="table table-striped table-hover">
						<thead>
						<tr>
						<th class="col-xs-2">Place</th>
						<th class="col-xs-7 col-sm-4">Team</th>
						<th class="col-xs-3">Points</th>
						<th class="hidden-xs col-sm-3">Running</th>
						</tr>
						</thead>
						<tbody>';
						$pl = 0;
						while($the_team = mysqli_fetch_array($data_fetcher)) {
							$pl++;
							echo '<tr';
							if($the_team['id'] == $team) echo ' class="success"';
							echo '><td>'.$pl.'</td><td>'.$the_team['name'].'</td>
							<td>'.round($the_team['total'],4).'</td>
							<td class="hidden-xs">'.round($the_team['running'],4).'</td>
							</tr>';
						}
						echo '</tbody>
							</table>';
					}
					break;
				case 'r': # Display sorted by running scores
				case 'a': # all individuals by points
				default: # Anything else, assume all individuals by points.
					$dftext = "SELECT * FROM tMembers WHERE season='$season' ORDER BY season_";
					if($_GET['disp'] == 'r') {
						$dftext .= "run DESC, season_total";
					} else {
						$dftext .= "total DESC, season_run";
					}
					$dftext .= " DESC, user ASC";
					$data_fetcher = @mysqli_query($db, $dftext);
					if(mysqli_num_rows($data_fetcher) == 0) {
						echo '<h4>Nobody has registered for this season!</h4>';
					} else {
						echo '<table class="table table-striped table-hover">
						<thead>
						<tr>
						<th class="col-xs-2">Place</th>
						<th class="col-xs-7 col-sm-4">User</th>';
						if($mode == 'r') {
							echo '<th class="col-xs-3">Running</th>
							<th class="hidden-xs col-sm-3">Points</th>';
						} else {
							echo '<th class="col-xs-3">Points</th>
							<th class="hidden-xs col-sm-3">Running</th>';
						}
						
						echo '</tr>
						</thead>
						<tbody>';
						$pl = 0;
						while($person = mysqli_fetch_array($data_fetcher)) {
							$pl++;
							echo '<tr';
							if($person['user'] == $id) echo ' class="success"';
							if($person['team'] == $team and $team > 1 and $person['user'] != $id) echo ' class="info"';
							echo '><td>'.$pl.'</td><td>';
							if($person['user'] == $id) echo '<abbr title="This is you!"><i class="fa fa-user"></i></abbr> ';
							if($person['team'] == $team and $team > 1 and $person['user'] != $id) echo '<abbr title="This is a teammate!"><i class="fa fa-users"></i></abbr> ';
							# Figure out their name!
							$pid = $person['user'];
							$the_user_fetcher = @mysqli_query($db, "SELECT * FROM users WHERE id=$pid");
							$the_user = mysqli_fetch_array($the_user_fetcher);
							echo $the_user['first'].' '.$the_user['last'];
							if($person['season_run'] >= 150) echo stars($person['season_run'], $the_user['gender']);
							echo '</td>';
							if($mode == 'r') {
								echo '<td>'.round($person['season_run'],3).'</td>
								<td class="hidden-xs">'.round($person['season_total'],3).'</td>';
							} else {
								echo '<td>'.round($person['season_total'],3).'</td>
								<td class="hidden-xs">'.round($person['season_run'],3).'</td>';
							}
							echo '</tr>';
						}
						echo '</tbody>
							</table>';
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
