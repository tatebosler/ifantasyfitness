<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);

# Validate the user
include('../../php/db.php');
$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
if(mysqli_num_rows($check_q) > 0) {
	$user = mysqli_fetch_array($check_q);
	# confirm with social token
	$valid = false;
	if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
	if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	if(!$valid) header('Location: http://www.ifantasyfitness.com');
}

# Grab user's permission value
# 0 - normal user
# 1 - captain
# 2 - coach
# 3 - administrator (this option rarely used)
$perms = $user['permissions'];

# User is valid
$current_set = 'People';
$title = $current_set . ' - Settings';
$connected = true;
include('../../php/head-auth.php');

function settingsType($name, $link, $min_perm) {
	global $current_set;
	global $perms;
	if($perms >= $min_perm) {
		echo '<li';
		if($name == $current_set) echo ' class="active"';
		echo '><a href="/settings/'.$link.'">'.$name.'</a></li>';
	}
}

$role = 'all';
if(isset($_GET['role'])) {
	$role = filter_var($_GET['role'],FILTER_SANITIZE_SPECIAL_CHARS);
}

$season = 'all';
if(isset($_GET['season'])) {
	$season = filter_var($_GET['season'],FILTER_SANITIZE_SPECIAL_CHARS);
}

$teams = array();
$teams[1] = "No team";
$tlq = "SELECT * FROM tData";
if($season != 'all') $tlq .= " WHERE season='$season'";
$team_lister = @mysqli_query($db, $tlq);
while($team = mysqli_fetch_array($team_lister)) {
	$teams[$team['id']] = $team['name']; # Puts all teams for listed season into an array
}

switch($_POST['action']) {
	case 'edit':
		$ePlayer = filter_var($_POST['ePlayer'], FILTER_SANITIZE_NUMBER_INT);
		$eSeason = filter_var($_POST['eSeason'], FILTER_SANITIZE_SPECIAL_CHARS);
		
		
}

$divisions = array(1 => "Upperclassmen", 2 => "Underclassmen", 3 => "Middle School", 4 => "Staff", 5 => "Parents", 6 => "Alumni");
?>
<div class="row hidden-print">
	<div class="col-xs-12">
		<?php
		if(!empty($message)) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-check"></i> OK! '.$message.'</h4>This change has been made and is now visible to everyone.</div>';
		?>
		<h2>Settings</h2>
	</div>
</div>
<div class="row">
	<div class="col-sm-3 col-md-2 hidden-print">
		<ul class="nav nav-pills nav-stacked">
			<?php
			settingsType('My profile','profile',0);
			settingsType('Goals', 'goals', 0);
			settingsType('My team', 'team', 1);
			settingsType('Seasons', 'seasons', 2);
			settingsType('People', 'people', 2);
			settingsType('Admin settings', 'admin', 2);
			?>
		</ul>
	</div>
	<div class="col-sm-9 col-md-10">
		<div class="panel panel-default hidden-print">
			<div class="panel-heading">
				<h3 class="panel-title">Filter</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<label class="col-xs-6">Season</label>
					<label class="col-xs-6">Role</label>
				</div>
				<div class="row">
					<div class="col-xs-6">
						<form name="f_season">
							<select name="season" onchange="document.location.href=document.f_season.season.options[document.f_season.season.selectedIndex].value" class="form-control">
								<option value="/settings/people?season=all&role=<?=$role?>">All seasons</option>
								<?php
								$seasons_fetcher = @mysqli_query($db, "SELECT * FROM seasons");
								while($the_season = mysqli_fetch_array($seasons_fetcher)) {
									echo '<option value="/settings/people?season='.$the_season['name'].'&role='.$role.'"';
									if($season == $the_season['name']) echo ' selected';
									echo '>'.$the_season['display_name'].'</option>';
								}
								?>
							</select>
						</form>
					</div>
					<div class="col-xs-6">
						<form name="f_role">
							<select name="role" onchange="document.location.href=document.f_role.role.options[document.f_role.role.selectedIndex].value" class="form-control">
							<?php
							$roles = array(0=>'Athletes',1=>'Team leaders',2=>'Coaches',3=>'Administrators','all'=>'All participants');
							foreach($roles as $key=>$name) {
								echo '<option value="/settings/people?season='.$season.'&role='.$key.'"';
								if($role == $key) echo ' selected';
								echo '>'.$name.'</option>';
							}
							?>
							</select>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="hidden-print alert alert-info">
			<h4><i class="fa fa-print"></i> This page is printer friendly</h4>
			Not all elements of this page will be printed - just the important ones.
		</div>
		<?php
		$people_fetcher_q = "SELECT * FROM tMembers";
		if($season != 'all') $people_fetcher_q .= " WHERE season='$season'";
		$people_fetcher = @mysqli_query($db, $people_fetcher_q);
		if(mysqli_num_rows($people_fetcher) == 0) {
			echo '<p class="lead">Looks like there isn\'t anyone that matches your search criteria.</p>';
		} else {
			# At least one person is listed in the season, now check all returned ranks.
			$people = array(); # an array of people to be displayed
			while($person = mysqli_fetch_array($people_fetcher)) {
				$person_id = $person['user'];
				$person_checker = @mysqli_query($db, "SELECT * FROM users WHERE id=$person_id");
				$fullPerson = mysqli_fetch_array($person_checker);
				$person['name'] = $fullPerson['first'].' '.$fullPerson['last'];
				$person['role'] = $fullPerson['permissions'];
				if($role != 'all') {
					if($fullPerson['permissions'] == $role) {
						$people[] = $person; # add to array
					}
				} else {
					$people[$person['name']] = $person;
				}
			}
			
			if(empty($people)) {
				echo '<p class="lead">Looks like there isn\'t anyone that matches your search criteria.</p>';
			} else {
				ksort($people);
				echo '<table class="table table-striped table-hover">
				<thead>
				<tr>
				<th>Name</th>
				<th>Season</th>
				<th>Prediction</th>
				<th>Division</th>
				<th class="hidden-print">Actions</th>
				</tr>
				</thead>
				<tbody>';
				foreach($people as $person) {
					echo '<tr>
					<td>'.$person['name'].'</td>
					<td>'.$person['season'].' - '.$teams[$person['team']].'</td>
					<td>'.$person['prediction'].'</td>
					<td>'.$divisions[$person['division']].'</td>
					<td class="hidden-print"><a data-toggle="modal" data-target="#edit-'.$person['user'].'-'.$person['season'].'">edit</a> - drop</td>
					</tr>';
				}
				echo '</tbody></table>';
			}
		}
		?>
	</div>
</div>
<?php
$theRoles = array(0 => "Athlete", 1 => "Team leader", 2 => "Coach", 3 => "Administrator");
foreach($people as $person) {
	echo '<form name="edit-'.$person['user'].'-'.$person['season'].'" method="post" class="form-horizontal">
	<div id="edit-'.$person['user'].'-'.$person['season'].'" aria-hidden="true" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title">Edit '.$person['name'].' ('.$person['season'].' season)</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label class="col-xs-4 control-label">Team</label>
						<div class="col-xs-8">
							<select name="team-'.$person['user'].'-'.$person['season'].'" class="form-control">';
							foreach($teams as $id => $name) {
								echo '<option value="'.$id.'"';
								if($id == $person['team']) echo ' selected';
								echo '>'.$name.'</option>';
							}
							echo '</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-xs-4 control-label">Prediction</label>
						<div class="col-xs-8">
							<input type="number" name="predict-'.$person['user'].'-'.$person['season'].'" class="form-control" max="5000" value="'.$person['prediction'].'">
						</div>
					</div>
					<div class="form-group">
						<label class="col-xs-4 control-label">Division</label>
						<div class="col-xs-8">';
						foreach($divisions as $key => $name) {
							echo '<div class="radio">
							<label>
							<input type="radio" name="div-'.$person['user'].'-'.$person['season'].'" value="'.$key.'"';
							if($key == $person['division']) echo ' checked';
							echo '>'.$name.'</label></div>';
						}
						echo '</div>
					</div>
					<div class="form-group" style="margin-bottom:0px;">
						<label class="col-xs-4 control-label">Permission</label>
						<div class="col-xs-8">';
						foreach($theRoles as $id => $name) {
							echo '<div class="radio">
							<label>
							<input type="radio" name="role-'.$person['user'].'-'.$person['season'].'" value="'.$id.'"';
							if($id == $person['role']) echo ' checked';
							echo '>'.$name.'</label></div>';
						}
						echo '<span class="help-block"><strong>Note:</strong> changing this setting will affect '.$person['name'].' on <em>all seasons</em> across the site, including future seasons.</span></div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<input type="submit" class="btn btn-primary" value="Save changes">
					<input type="hidden" name="action" value="edit">
					<input type="hidden" name="ePlayer" value="'.$person['user'].'">
					<input type="hidden" name="eSeason" value="'.$person['season'].'">
				</div>
			</div>
		</div>
	</div>
</form>';
}

include('../../php/foot.php');
?>