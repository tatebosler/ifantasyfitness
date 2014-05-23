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
$current_set = 'My team';
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

# Process name changes
if(isset($_POST['team'])) {
	$teamNo = filter_var($_POST['team'], FILTER_SANITIZE_NUMBER_INT);
	$teamName = filter_var($_POST['name-'.$teamNo], FILTER_SANITIZE_SPECIAL_CHARS);
	$oldTeamName = filter_var($_POST['old-name-'.$teamNo], FILTER_SANITIZE_SPECIAL_CHARS);
	if(strlen($teamName) > 0 and strlen($teamName) <= 40) {
		$team_updater = @mysqli_query($db, "UPDATE tData SET name='$teamName' WHERE id=$teamNo");
		if($team_updater) {
			$message = "$oldTeamName has been updated and is now known as $teamName.";
		}
	} elseif (strlen($teamName) > 40) {
		$error_msg = "$teamName is too long! It must be 32 characters or less.";
	}
}

# Ok, what team(s) do you own? If many - we will display them in reverse chronological order
$teams = array();
$team_grab = @mysqli_query($db, "SELECT * FROM tData WHERE captain=$id ORDER BY season DESC, name ASC");
while($team = mysqli_fetch_array($team_grab)) {
	$teams[$team['id']] = $team;
}
?>
<div class="row">
	<div class="col-xs-12">
		<?php
		if(isset($message)) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-check"></i> OK! '.$message.'</div>';
		?>
		<h2>Settings</h2>
	</div>
</div>
<div class="row">
	<div class="col-sm-3 col-md-2">
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
		<h2>My Team</h2>
		<p>You may change the name of your team(s) here - just edit the name and click "Save" next to it. To add members to your team, you will need to ask your coaches to add them in People settings.</p>
		<?php
		# It's possible that the user could be a member of many teams from over the years.
		foreach($teams as $tid => $team) {
		echo '<form name="team-'.$tid.'" method="post" class="form-horizontal">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">'.$team['name'].' ('.$team['season'].' season)</h3>
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label class="col-xs-2 control-label">Team name</label>
					<div class="col-xs-10">
						<div class="input-group">
							<input type="text" name="name-'.$tid.'" class="form-control" maxlength="32" value="'.$team['name'].'">
							<span class="input-group-btn">
								<input class="btn btn-primary" type="submit" value="Save">
								<input type="hidden" name="team" value="'.$tid.'">
								<input type="hidden" name="old-name-'.$tid.'" value="'.$team['name'].'">
							</span>
						</div>
						<p class="help-block">Team names are limited to 40 characters - be creative, but succinct.</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-2 control-label">Season</label>
					<div class="col-xs-10">
						<p class="form-control-static">'.$team['season'].'</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-2 control-label">Team members</label>
					<div class="col-xs-10">
						<ul class="form-control-static">';
						$team_members = @mysqli_query($db, "SELECT * FROM tMembers WHERE team=$tid");
						$tm = array(); # array of team members
						while($person = mysqli_fetch_array($team_members)) {
							$pid = $person['user'];
							$person_finder = @mysqli_query($db, "SELECT * FROM users WHERE id=$pid");
							$thePerson = mysqli_fetch_array($person_finder);
							$tm[] = $thePerson['first'].' '.$thePerson['last']; # adds team member to array
						}
						asort($tm); # Sort team members by name, this makes it easier to read
						foreach($tm as $person) {
							echo '<li>'.$person.'</li>'; # prints each member of team
						}
						echo '</ul>
					</div>
				</div>
			</div>
		</div>
		</form>';
		}
		?>
	</div>
</div>
<?php
include('../../php/foot.php');
?>