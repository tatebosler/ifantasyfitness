<?php
if(!isset($_GET['id']) and !isset($_POST['id'])) header('Location: http://www.ifantasyfitness.com');
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

# This page requires Level 2 permissions.
# As such, anyone with lower permissions is hereby banished.
if($perms < 2) header('Location: http://www.ifantasyfitness.com/settings/profile');

if(isset($_POST['id'])) {
	# Deletion is confirmed - GO.
	$sid = filter_var($_POST['id'], FILTER_SANITIZE_SPECIAL_CHARS);
	
	# Delete teams
	$team_fetcher = @mysqli_query($db, "SELECT * FROM tData WHERE season='$sid'");
	if(mysqli_num_rows($team_fetcher) > 0) {
		$teams = array();
		while($team = mysqli_fetch_array($team_fetcher)) {
			$tid = $team['id'];
			$record_deleter = @mysqli_query($db, "DELETE FROM records WHERE team=$tid");
		}
	}
	
	$team_deleter = @mysqli_query($db, "DELETE FROM tData WHERE season='$sid'");
	
	# Delete registrations
	$reg_deleter = @mysqli_query($db, "DELETE FROM tMembers WHERE season='$sid'");
	
	# Delete season
	$season_deleter = @mysqli_query($db, "DELETE FROM seasons WHERE name='$sid'");
	
	# Done
	setcookie('confirm_message','delete',time()+3,'/','.ifantasyfitness.com');
	header("Location: http://www.ifantasyfitness.com/settings/seasons");
}

# User is valid, and has proper permissions.
$current_set = 'Seasons';
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
?>
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
		<h2>Delete Season</h2>
		<p><a href="/settings/seasons">&larr; Back to season list</a></p>
		<div class="alert alert-danger">
			<h4><strong>WARNING!</strong></h4>
			<p>You are about to <strong>permanently</strong> delete the <?=$_GET['id']?> season! This action cannot be undone.</p>
			<p>Deleting the season <strong>will immediately delete all records and teams associated with it.</strong> If you would like to preserve this data, do not delete the season (the data is automatically archived after the season's competition period ends).</p>
			<p>If you would like to make changes to the season, <a href="/settings/seasons/edit.php?id=<?=$_GET['id']?>" class="alert-link">click here</a>.</p>
			<p>If you are sure that you want to delete the season, click "Delete Team" below.</p>
		</div>
		<form method="post">
			<input type="hidden" name="id" value="<?=$_GET['id']?>">
			<div class="row">
				<div class="col-xs-6">
					<input type="submit" class="btn btn-primary btn-block" value="Delete Season">
				</div>
				<div class="col-xs-6">
					<a href="/settings/seasons" class="btn btn-default btn-block">Cancel</a>
				</div>
			</div>
		</form>
	</div>
</div>
<?php
include('../../php/foot.php');
?>